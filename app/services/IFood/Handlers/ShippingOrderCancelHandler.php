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
 * Job: `ifood.shipping.cancel`
 *
 * Cancela um pedido de logística iFood que ainda está em andamento.
 * Idempotente: 404 do iFood é tratado como sucesso (já não existe).
 *
 * Payload:
 *   external_reference  string (obrigatório)
 *   environment         string (opcional)
 *   reason              string (opcional)
 *
 * Skipa quando:
 *   - row não existe localmente
 *   - row já está em estado terminal (DELIVERED, CANCELLED, REJECTED, FAILED)
 *   - row não tem ifood_shipping_id (nada para cancelar do lado iFood)
 */
final class ShippingOrderCancelHandler implements IFoodJobHandler
{
    private const TERMINAL_STATUSES = ['DELIVERED', 'CANCELLED', 'REJECTED', 'FAILED'];

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
            throw new RuntimeException('ShippingOrderCancelHandler: company_id ausente');
        }

        $externalRef = trim((string) ($payload['external_reference'] ?? ''));
        if ($externalRef === '') {
            throw new RuntimeException('ShippingOrderCancelHandler: external_reference ausente');
        }

        $reason = trim((string) ($payload['reason'] ?? 'admin_cancel'));

        $integration = $this->loadIntegration($companyId);
        if ($integration === null) {
            throw new RuntimeException('ShippingOrderCancelHandler: integração inativa');
        }

        $env = IFoodEndpoints::normalizeEnvironment(
            (string) ($payload['environment'] ?? $integration['environment'] ?? 'production')
        );

        $row = $this->loadRow($companyId, $env, $externalRef);
        if ($row === null) {
            error_log("[ShippingOrderCancel] skip company={$companyId} ext={$externalRef} (row não existe)");
            return;
        }

        $current = (string) ($row['status'] ?? '');
        if (in_array($current, self::TERMINAL_STATUSES, true)) {
            error_log("[ShippingOrderCancel] skip company={$companyId} ext={$externalRef} status já terminal ({$current})");
            return;
        }

        $ifoodId = trim((string) ($row['ifood_shipping_id'] ?? ''));
        if ($ifoodId === '') {
            // Não chegamos a submeter ao iFood — só marca localmente.
            $this->markCancelledLocal((int) $row['id'], $reason);
            error_log("[ShippingOrderCancel] cancelamento só local company={$companyId} ext={$externalRef}");
            return;
        }

        $token = $this->resolveToken($companyId);
        if ($token === null || $token === '') {
            throw new IFoodRetryableException('ShippingOrderCancelHandler: sem access_token');
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

        $url = IFoodEndpoints::shippingOrder($env, $ifoodId);
        $response = $client->delete($url, IFoodEndpoints::MODULE_SHIPPING);

        // 2xx ou 404 → idempotente (404 = já não existe lá).
        if ($response->ok || $response->status === 404) {
            $this->markCancelled((int) $row['id'], $reason, (int) ($response->status ?? 0));
            error_log("[ShippingOrderCancel] cancelado company={$companyId} ext={$externalRef} http={$response->status}");
            return;
        }

        $errMsg = (string) ($response->error ?? 'erro desconhecido');

        if ($response->status === 401) {
            throw new IFoodRetryableException("ShippingOrderCancel: 401 ({$errMsg})");
        }
        if ($response->status === null || $response->status === 429 || $response->status >= 500) {
            throw new IFoodRetryableException($errMsg);
        }

        throw new RuntimeException("ShippingOrderCancel: {$response->status} ({$errMsg})");
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
    private function loadRow(int $companyId, string $env, string $externalRef): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, status, ifood_shipping_id
               FROM ifood_shipping_orders
              WHERE company_id = :cid AND environment = :env AND external_reference = :ref
              LIMIT 1'
        );
        $stmt->execute([':cid' => $companyId, ':env' => $env, ':ref' => $externalRef]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function markCancelled(int $rowId, string $reason, int $httpStatus): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ifood_shipping_orders SET
                status               = \'CANCELLED\',
                cancelled_at         = NOW(),
                cancel_reason        = :reason,
                last_response_status = :hs,
                last_error           = NULL,
                next_poll_at         = NULL
              WHERE id = :id'
        );
        $stmt->execute([
            ':reason' => mb_substr($reason, 0, 255, 'UTF-8'),
            ':hs'     => $httpStatus,
            ':id'     => $rowId,
        ]);
    }

    private function markCancelledLocal(int $rowId, string $reason): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ifood_shipping_orders SET
                status        = \'CANCELLED\',
                cancelled_at  = NOW(),
                cancel_reason = :reason,
                last_error    = NULL,
                next_poll_at  = NULL
              WHERE id = :id'
        );
        $stmt->execute([
            ':reason' => mb_substr($reason, 0, 255, 'UTF-8'),
            ':id'     => $rowId,
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
