<?php

declare(strict_types=1);

namespace App\Services\IFood\Handlers;

use App\Services\IFood\IFoodApiLogger;
use App\Services\IFood\IFoodClient;
use App\Services\IFood\IFoodEndpoints;
use App\Services\IFood\IFoodJobHandler;
use App\Services\IFood\IFoodRetryableException;
use IFoodService;
use PDO;
use RuntimeException;

/**
 * Job: `ifood.driver.cancel`
 *
 * Cancela uma solicitação de entregador em andamento (admin desistiu, vai
 * usar entregador próprio, ou houve erro operacional).
 *
 * Payload:
 *   ifood_order_id  string  (obrigatório)
 *   environment     string  (opcional)
 *   reason          string  (opcional)
 *
 * Skipa silenciosamente se o pedido não tem entry em ifood_order_drivers
 * (nada a cancelar) ou se já está em estado terminal.
 *
 * Erros:
 *   2xx           → CANCELLED + cancelled_at + cancel_reason
 *   404           → trata como sucesso (pedido sem driver no iFood = já cancelado lá)
 *   401/429/5xx   → IFoodRetryableException
 *   Outros 4xx    → RuntimeException (dead); estado fica como está
 */
final class DriverCancelHandler implements IFoodJobHandler
{
    /** Estados que NÃO permitem cancelamento (já é terminal). */
    private const TERMINAL_STATUSES = ['COMPLETED', 'CANCELLED', 'FAILED'];

    private PDO $db;
    private IFoodApiLogger $logger;

    public function __construct(PDO $db, IFoodApiLogger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function handle(array $job, array $payload): void
    {
        $companyId = (int) ($job['company_id'] ?? 0);
        if ($companyId <= 0) {
            throw new RuntimeException('DriverCancelHandler: company_id ausente');
        }

        $ifoodOrderId = trim((string) ($payload['ifood_order_id'] ?? ''));
        if ($ifoodOrderId === '') {
            throw new RuntimeException('DriverCancelHandler: ifood_order_id ausente');
        }

        $reason = trim((string) ($payload['reason'] ?? 'admin_cancel'));

        $integration = $this->loadIntegration($companyId);
        if ($integration === null) {
            throw new RuntimeException('DriverCancelHandler: integração inativa');
        }

        $env = IFoodEndpoints::normalizeEnvironment(
            (string) ($payload['environment'] ?? $integration['environment'] ?? 'production')
        );

        $state = $this->loadDriverState($companyId, $env, $ifoodOrderId);
        if ($state === null) {
            error_log(sprintf(
                '[DriverCancelHandler] skip company=%d order=%s (sem entry em ifood_order_drivers)',
                $companyId,
                $ifoodOrderId
            ));
            return;
        }

        $current = (string) ($state['request_status'] ?? '');
        if (in_array($current, self::TERMINAL_STATUSES, true)) {
            error_log(sprintf(
                '[DriverCancelHandler] skip company=%d order=%s (já está em %s)',
                $companyId,
                $ifoodOrderId,
                $current
            ));
            return;
        }

        $token = $this->resolveToken($companyId);
        if ($token === null || $token === '') {
            throw new IFoodRetryableException('DriverCancelHandler: sem access_token');
        }

        $client = new IFoodClient(
            companyId: $companyId,
            environment: $env,
            accessToken: $token,
            logger: $this->logger,
            jobId: isset($job['id']) ? (int) $job['id'] : null,
            maxAttempts: 3,
            timeoutSeconds: 20
        );

        $url = IFoodEndpoints::orderAction($env, $ifoodOrderId, 'cancelDriver');
        $response = $client->post($url, IFoodEndpoints::MODULE_LOGISTICS, []);

        // 2xx = ok. 404 = já não existe → tratamos como sucesso idempotente.
        if ($response->ok || $response->status === 404) {
            $this->markCancelled($companyId, $env, $ifoodOrderId, $reason, $response->status);
            error_log(sprintf(
                '[DriverCancelHandler] cancelado company=%d env=%s order=%s http=%s',
                $companyId,
                $env,
                $ifoodOrderId,
                (string) $response->status
            ));
            return;
        }

        $errMsg = (string) ($response->error ?? 'erro desconhecido');

        if ($response->status === 401) {
            throw new IFoodRetryableException('DriverCancelHandler: 401 (' . $errMsg . ')');
        }
        if ($response->status === null || $response->status === 429 || $response->status >= 500) {
            throw new IFoodRetryableException($errMsg);
        }

        throw new RuntimeException(sprintf('DriverCancelHandler: %d %s', (int) $response->status, $errMsg));
    }

    /**
     * @return array<string,mixed>|null
     */
    private function loadIntegration(int $companyId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT environment, is_active FROM ifood_integrations WHERE company_id = :cid LIMIT 1'
        );
        $stmt->execute([':cid' => $companyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || (int) ($row['is_active'] ?? 0) !== 1) {
            return null;
        }
        return $row;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function loadDriverState(int $companyId, string $env, string $ifoodOrderId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT request_status FROM ifood_order_drivers
              WHERE company_id = :cid AND environment = :env AND ifood_order_id = :oid
              LIMIT 1'
        );
        $stmt->execute([':cid' => $companyId, ':env' => $env, ':oid' => $ifoodOrderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function markCancelled(int $companyId, string $env, string $ifoodOrderId, string $reason, ?int $httpStatus): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ifood_order_drivers
                SET request_status      = \'CANCELLED\',
                    cancelled_at        = NOW(),
                    cancel_reason       = :reason,
                    last_response_status= :hs,
                    last_error          = NULL
              WHERE company_id = :cid AND environment = :env AND ifood_order_id = :oid'
        );
        $stmt->execute([
            ':cid'    => $companyId,
            ':env'    => $env,
            ':oid'    => $ifoodOrderId,
            ':reason' => mb_substr($reason, 0, 255, 'UTF-8'),
            ':hs'     => $httpStatus,
        ]);
    }

    private function resolveToken(int $companyId): ?string
    {
        if (!class_exists('IFoodService', false)) {
            require_once __DIR__ . '/../../IFoodService.php';
        }
        $service = new IFoodService($this->db, $companyId);
        return $service->getAccessToken();
    }
}
