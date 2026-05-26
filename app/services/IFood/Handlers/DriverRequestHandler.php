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
 * Job: `ifood.driver.request`
 *
 * Solicita um entregador iFood para um pedido nativo (criado na plataforma).
 * Equivalente ao botão "Solicitar entregador" do gestor iFood, mas automatizado
 * e com retry/observabilidade.
 *
 * Payload:
 *   ifood_order_id  string  (obrigatório) UUID do pedido no iFood
 *   environment     string  (opcional)    sandbox|production; default = config da company
 *
 * Pré-requisitos validados em runtime:
 *   - Integração iFood ativa para a company
 *   - Pedido existe em `ifood_orders` (mesmo company_id)
 *   - Pedido está em estado que permite request (CONFIRMED ou superior, ainda
 *     não CONCLUDED/CANCELLED)
 *   - `delivered_by = 'MERCHANT'` (se for IFOOD, não faz sentido pedir driver)
 *
 * Fluxo de erros:
 *   - 200/202 OK            → request_status=REQUESTED
 *   - 409 Conflict          → já tem driver; request_status=REQUESTED (no-op)
 *   - 422 No driver         → request_status=NO_DRIVER; IFoodRetryableException (re-tenta)
 *   - 5xx/429/rede          → IFoodRetryableException
 *   - 401                   → IFoodRetryableException (token expirou)
 *   - 404                   → request_status=FAILED; RuntimeException (dead)
 *   - Outros 4xx            → request_status=FAILED; RuntimeException (dead)
 */
final class DriverRequestHandler implements IFoodJobHandler
{
    /** Estados de pedido que permitem solicitar entregador. */
    private const ALLOWED_ORDER_STATUSES = ['CONFIRMED', 'READY_TO_PICKUP', 'DISPATCHED'];

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
            throw new RuntimeException('DriverRequestHandler: company_id ausente');
        }

        $ifoodOrderId = trim((string) ($payload['ifood_order_id'] ?? ''));
        if ($ifoodOrderId === '') {
            throw new RuntimeException('DriverRequestHandler: ifood_order_id ausente');
        }

        $integration = $this->loadIntegration($companyId);
        if ($integration === null) {
            throw new RuntimeException('DriverRequestHandler: integração iFood inativa ou ausente');
        }

        $envFromPayload = isset($payload['environment']) ? (string) $payload['environment'] : null;
        $env = IFoodEndpoints::normalizeEnvironment($envFromPayload ?? (string) ($integration['environment'] ?? 'production'));

        $order = $this->loadOrder($companyId, $ifoodOrderId);
        if ($order === null) {
            // Pedido sumiu localmente — não acionamos a API.
            $this->upsertState($companyId, $env, $ifoodOrderId, null, 'FAILED', 'order_not_found_locally', null, null);
            throw new RuntimeException(sprintf(
                'DriverRequestHandler: pedido %s não encontrado em ifood_orders para company %d',
                $ifoodOrderId,
                $companyId
            ));
        }

        $orderStatus = (string) ($order['status'] ?? '');
        $deliveredBy = (string) ($order['delivered_by'] ?? '');

        if ($deliveredBy !== 'MERCHANT') {
            // iFood faz a logística — nada a solicitar.
            error_log(sprintf(
                '[DriverRequestHandler] skip company=%d order=%s delivered_by=%s (não-MERCHANT)',
                $companyId,
                $ifoodOrderId,
                $deliveredBy
            ));
            $this->upsertState($companyId, $env, $ifoodOrderId, (string) ($order['ifood_display_id'] ?? ''), 'CANCELLED', 'delivered_by_ifood', null, null);
            return;
        }

        if (!in_array($orderStatus, self::ALLOWED_ORDER_STATUSES, true)) {
            // Estado errado: pedido ainda não foi confirmado, ou já está concluído.
            throw new RuntimeException(sprintf(
                'DriverRequestHandler: pedido %s em status %s não permite request driver',
                $ifoodOrderId,
                $orderStatus
            ));
        }

        $token = $this->resolveToken($companyId);
        if ($token === null || $token === '') {
            throw new IFoodRetryableException('DriverRequestHandler: sem access_token (falha transitória de auth)');
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

        $url = IFoodEndpoints::orderAction($env, $ifoodOrderId, 'requestDriver');
        $response = $client->post($url, IFoodEndpoints::MODULE_LOGISTICS, []);

        $displayId = (string) ($order['ifood_display_id'] ?? '');
        $rawJson = $response->body !== null ? json_encode($response->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

        if ($response->ok || $response->status === 409) {
            // 409 = pedido já tem driver — tratamos como sucesso (idempotente).
            $this->upsertState(
                $companyId,
                $env,
                $ifoodOrderId,
                $displayId,
                'REQUESTED',
                null,
                $response->status,
                $rawJson === false ? null : $rawJson
            );
            error_log(sprintf(
                '[DriverRequestHandler] ok company=%d env=%s order=%s status=REQUESTED http=%s',
                $companyId,
                $env,
                $ifoodOrderId,
                (string) $response->status
            ));
            return;
        }

        $errMsg = (string) ($response->error ?? 'erro desconhecido');

        // 422 = "no driver available" — transitório com semântica específica.
        if ($response->status === 422) {
            $this->upsertState(
                $companyId,
                $env,
                $ifoodOrderId,
                $displayId,
                'NO_DRIVER',
                $errMsg,
                $response->status,
                $rawJson === false ? null : $rawJson
            );
            throw new IFoodRetryableException(sprintf('no driver available (422): %s', $errMsg));
        }

        // 401 → token expirou.
        if ($response->status === 401) {
            throw new IFoodRetryableException('DriverRequestHandler: 401 unauthorized (' . $errMsg . ')');
        }

        // 429 / 5xx / rede → transitório.
        if ($response->status === null || $response->status === 429 || $response->status >= 500) {
            throw new IFoodRetryableException($errMsg);
        }

        // 4xx demais (400, 403, 404, …) → permanente.
        $this->upsertState(
            $companyId,
            $env,
            $ifoodOrderId,
            $displayId,
            'FAILED',
            $errMsg,
            $response->status,
            $rawJson === false ? null : $rawJson
        );
        throw new RuntimeException(sprintf('DriverRequestHandler: %d %s', (int) $response->status, $errMsg));
    }

    /**
     * @return array<string,mixed>|null
     */
    private function loadIntegration(int $companyId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT environment, is_active
               FROM ifood_integrations
              WHERE company_id = :cid
              LIMIT 1'
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
    private function loadOrder(int $companyId, string $ifoodOrderId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ifood_order_id, ifood_display_id, status, delivered_by, order_type
               FROM ifood_orders
              WHERE company_id = :cid AND ifood_order_id = :oid
              LIMIT 1'
        );
        $stmt->execute([':cid' => $companyId, ':oid' => $ifoodOrderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function upsertState(
        int $companyId,
        string $env,
        string $ifoodOrderId,
        ?string $displayId,
        string $requestStatus,
        ?string $error,
        ?int $httpStatus,
        ?string $rawResponseJson
    ): void {
        // requested_at: setado na primeira vez que entramos em REQUESTED.
        // Preservamos o anterior via IF() para não perder histórico.
        $sql = 'INSERT INTO ifood_order_drivers
              (company_id, environment, ifood_order_id, order_display_id,
               request_status, last_error, last_response_status, raw_response,
               requested_at, retries)
              VALUES (:cid, :env, :oid, :did, :st, :err, :hs, :raw, :req_at, 0)
              ON DUPLICATE KEY UPDATE
                request_status        = VALUES(request_status),
                last_error            = VALUES(last_error),
                last_response_status  = VALUES(last_response_status),
                raw_response          = VALUES(raw_response),
                requested_at          = IF(VALUES(requested_at) IS NULL,
                                           requested_at,
                                           IF(requested_at IS NULL, VALUES(requested_at), requested_at)),
                order_display_id      = COALESCE(VALUES(order_display_id), order_display_id),
                retries               = retries + IF(VALUES(request_status) IN (\'NO_DRIVER\',\'FAILED\'), 1, 0)';

        $requestedAt = ($requestStatus === 'REQUESTED') ? date('Y-m-d H:i:s') : null;

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cid'    => $companyId,
            ':env'    => $env,
            ':oid'    => $ifoodOrderId,
            ':did'    => $displayId !== '' ? $displayId : null,
            ':st'     => $requestStatus,
            ':err'    => $error !== null ? mb_substr($error, 0, 500, 'UTF-8') : null,
            ':hs'     => $httpStatus,
            ':raw'    => $rawResponseJson,
            ':req_at' => $requestedAt,
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
