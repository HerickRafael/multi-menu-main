<?php

declare(strict_types=1);

namespace App\Services\IFood;

use IFoodService;
use PDO;
use QueueJob;
use Throwable;

/**
 * Helper de enfileiramento para `ifood.shipping.*`.
 *
 * Diferentemente do StockSync/DriverRequest, aqui o dispatcher também é
 * responsável por **persistir o pedido localmente** antes de enfileirar o
 * job — assim retries no worker sempre encontram a row, e o job carrega
 * apenas o `external_reference` (não o payload completo).
 *
 * Idempotência:
 *   - UNIQUE KEY (company, env, external_reference) → 2 chamadas com o
 *     mesmo external_reference NUNCA criam 2 rows.
 *   - Coalescing via dedup_key (no_job_duplicate_pending).
 *   - O caller pode passar `external_reference` (ex: order_number do sistema
 *     local) ou deixar nulo para o dispatcher gerar.
 *
 * Validação mínima — não tentamos validar o schema completo do iFood,
 * só os campos sem os quais o request ia falhar de qualquer jeito.
 */
final class ShippingDispatcher
{
    /**
     * Cria (ou recupera) uma row de shipping_orders e enfileira o job de criação.
     *
     * @param array<string,mixed> $iFoodPayload  Body que será POSTado em /shipping/v1.0/orders
     * @param array{order_id?:int, external_reference?:string, environment?:string} $opts
     *
     * @return array{
     *   ok: bool,
     *   external_reference: string,
     *   status: string,
     *   enqueued: bool,
     *   already_existed: bool,
     *   message: ?string,
     * }
     */
    public static function createShippingOrder(int $companyId, array $iFoodPayload, array $opts = []): array
    {
        if ($companyId <= 0) {
            return self::fail('company_id inválido');
        }

        // Validação mínima
        if (!self::looksLikeValidPayload($iFoodPayload)) {
            return self::fail('payload incompleto (precisa pelo menos de customer, delivery e items)');
        }

        $db = self::db();
        if ($db === null) {
            return self::fail('falha ao conectar no banco');
        }

        try {
            $integration = self::loadIntegration($db, $companyId);
            if ($integration === null) {
                return self::fail('integração iFood inativa ou ausente');
            }
        } catch (Throwable $e) {
            return self::fail('precheck falhou: ' . $e->getMessage());
        }

        $env = IFoodEndpoints::normalizeEnvironment(
            (string) ($opts['environment'] ?? $integration['environment'] ?? 'production')
        );

        $externalRef = trim((string) ($opts['external_reference'] ?? ''));
        if ($externalRef === '') {
            $externalRef = self::generateReference();
        }

        $orderId = isset($opts['order_id']) ? (int) $opts['order_id'] : null;
        if ($orderId !== null && $orderId <= 0) {
            $orderId = null;
        }

        $payloadJson = json_encode($iFoodPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payloadJson === false) {
            return self::fail('falha ao serializar payload');
        }

        // INSERT IGNORE para garantir idempotência exata: se a row já existe
        // com o mesmo (company, env, external_reference), não recria.
        try {
            $stmt = $db->prepare(
                'INSERT IGNORE INTO ifood_shipping_orders
                    (company_id, environment, order_id, external_reference, status, request_payload)
                  VALUES (:cid, :env, :oid, :ref, \'PENDING\', :payload)'
            );
            $stmt->execute([
                ':cid'     => $companyId,
                ':env'     => $env,
                ':oid'     => $orderId,
                ':ref'     => $externalRef,
                ':payload' => $payloadJson,
            ]);
        } catch (Throwable $e) {
            return self::fail('insert falhou: ' . $e->getMessage());
        }

        // Carrega para reportar status.
        $row = self::loadRow($db, $companyId, $env, $externalRef);
        if ($row === null) {
            return self::fail('row não encontrada após insert');
        }

        $alreadyExisted = (int) $row['id'] > 0 && (string) $row['status'] !== 'PENDING';
        if ($alreadyExisted) {
            return [
                'ok'                  => true,
                'external_reference'  => $externalRef,
                'status'              => (string) $row['status'],
                'enqueued'            => false,
                'already_existed'     => true,
                'message'             => 'shipping order já existia (idempotente)',
            ];
        }

        // Enfileira (com coalescing para o caso de o caller chamar 2x em rajada).
        $dedupKey = sprintf('ifood.shipping.create:%d:%s:%s', $companyId, $env, $externalRef);
        if (!class_exists('QueueJob', false)) {
            require_once __DIR__ . '/../../models/QueueJob.php';
        }

        $enqueued = QueueJob::enqueueCoalesced(
            'ifood.shipping.create',
            $dedupKey,
            [
                'external_reference' => $externalRef,
                'environment'        => $env,
            ],
            $companyId,
            3, // priority alta — pedido é tempo-crítico
            5,
            date('Y-m-d H:i:s')
        );

        return [
            'ok'                  => true,
            'external_reference'  => $externalRef,
            'status'              => (string) $row['status'],
            'enqueued'            => $enqueued,
            'already_existed'     => false,
            'message'             => $enqueued ? null : 'já existe job pendente',
        ];
    }

    /**
     * Enfileira cancelamento de um shipping order.
     *
     * @return array{ok:bool, enqueued:bool, message:?string, status:?string}
     */
    public static function cancelShippingOrder(
        int $companyId,
        string $externalReference,
        string $reason = 'admin_cancel'
    ): array {
        if ($companyId <= 0 || trim($externalReference) === '') {
            return ['ok' => false, 'enqueued' => false, 'message' => 'parâmetros inválidos', 'status' => null];
        }

        $db = self::db();
        if ($db === null) {
            return ['ok' => false, 'enqueued' => false, 'message' => 'falha ao conectar', 'status' => null];
        }

        try {
            $integration = self::loadIntegration($db, $companyId);
            if ($integration === null) {
                return ['ok' => false, 'enqueued' => false, 'message' => 'integração inativa', 'status' => null];
            }
        } catch (Throwable $e) {
            return ['ok' => false, 'enqueued' => false, 'message' => $e->getMessage(), 'status' => null];
        }

        $env = IFoodEndpoints::normalizeEnvironment((string) $integration['environment']);
        $row = self::loadRow($db, $companyId, $env, $externalReference);
        if ($row === null) {
            return ['ok' => false, 'enqueued' => false, 'message' => 'shipping order não encontrada', 'status' => null];
        }

        $current = (string) $row['status'];
        if (in_array($current, ['DELIVERED', 'CANCELLED', 'REJECTED', 'FAILED'], true)) {
            return ['ok' => true, 'enqueued' => false, 'message' => "estado já terminal ({$current})", 'status' => $current];
        }

        $dedupKey = sprintf('ifood.shipping.cancel:%d:%s:%s', $companyId, $env, $externalReference);
        if (!class_exists('QueueJob', false)) {
            require_once __DIR__ . '/../../models/QueueJob.php';
        }

        $enqueued = QueueJob::enqueueCoalesced(
            'ifood.shipping.cancel',
            $dedupKey,
            [
                'external_reference' => $externalReference,
                'environment'        => $env,
                'reason'             => $reason,
            ],
            $companyId,
            2,
            3,
            date('Y-m-d H:i:s')
        );

        return [
            'ok'       => true,
            'enqueued' => $enqueued,
            'message'  => $enqueued ? null : 'já existe cancelamento pendente',
            'status'   => $current,
        ];
    }

    /**
     * Cota o custo/ETA de um shipping order ANTES de criá-lo.
     *
     * Síncrono — não usa fila, porque quote tem timeout natural curto
     * (alguns segundos) e o caller geralmente precisa do resultado
     * para decidir se aceita o frete.
     *
     * @param array<string,mixed> $iFoodPayload  pickup + delivery + items (mínimo)
     * @return array{
     *   ok: bool,
     *   quote: ?array<string,mixed>,
     *   http_status: ?int,
     *   message: ?string,
     * }
     */
    public static function quoteShippingOrder(int $companyId, array $iFoodPayload): array
    {
        if ($companyId <= 0) {
            return ['ok' => false, 'quote' => null, 'http_status' => null, 'message' => 'company_id inválido'];
        }
        if (empty($iFoodPayload['pickup']) || empty($iFoodPayload['delivery'])) {
            return ['ok' => false, 'quote' => null, 'http_status' => null, 'message' => 'pickup e delivery obrigatórios'];
        }

        $db = self::db();
        if ($db === null) {
            return ['ok' => false, 'quote' => null, 'http_status' => null, 'message' => 'falha ao conectar'];
        }

        try {
            $integration = self::loadIntegration($db, $companyId);
            if ($integration === null) {
                return ['ok' => false, 'quote' => null, 'http_status' => null, 'message' => 'integração inativa'];
            }
        } catch (Throwable $e) {
            return ['ok' => false, 'quote' => null, 'http_status' => null, 'message' => $e->getMessage()];
        }

        $env = IFoodEndpoints::normalizeEnvironment((string) $integration['environment']);

        if (!class_exists('IFoodService', false)) {
            require_once __DIR__ . '/../IFoodService.php';
        }
        $service = new IFoodService($db, $companyId);
        $token = $service->getAccessToken();
        if ($token === null || $token === '') {
            return ['ok' => false, 'quote' => null, 'http_status' => 401, 'message' => 'sem access_token'];
        }

        $logger = new IFoodApiLogger($db);
        $client = new IFoodClient(
            companyId: $companyId,
            environment: $env,
            accessToken: $token,
            logger: $logger,
            jobId: null,
            maxAttempts: 2,
            timeoutSeconds: 15
        );

        $response = $client->post(
            IFoodEndpoints::shippingQuote($env),
            IFoodEndpoints::MODULE_SHIPPING,
            $iFoodPayload
        );

        if ($response->ok) {
            return [
                'ok'          => true,
                'quote'       => is_array($response->body) ? $response->body : null,
                'http_status' => $response->status,
                'message'     => null,
            ];
        }

        return [
            'ok'          => false,
            'quote'       => null,
            'http_status' => $response->status,
            'message'     => (string) ($response->error ?? 'erro desconhecido'),
        ];
    }

    /**
     * Validação mínima: precisa de customer + delivery + items.
     * Não tentamos validar o schema completo do iFood — o servidor dele
     * vai recusar com 400/422 se faltar campo, e o handler trata como REJECTED.
     *
     * @param array<string,mixed> $payload
     */
    private static function looksLikeValidPayload(array $payload): bool
    {
        if (empty($payload['customer']) || !is_array($payload['customer'])) {
            return false;
        }
        if (empty($payload['delivery']) || !is_array($payload['delivery'])) {
            return false;
        }
        if (empty($payload['items']) || !is_array($payload['items'])) {
            return false;
        }
        return true;
    }

    /**
     * Gera external_reference UUID-like sem depender de extensão.
     * Formato: SHP-{timestamp}-{random_hex} (legível e único o suficiente).
     */
    private static function generateReference(): string
    {
        return sprintf('SHP-%d-%s', time(), bin2hex(random_bytes(6)));
    }

    /**
     * @return array{ok:false, external_reference:string, status:string, enqueued:false, already_existed:false, message:string}
     */
    private static function fail(string $msg): array
    {
        return [
            'ok'                 => false,
            'external_reference' => '',
            'status'             => 'PENDING',
            'enqueued'           => false,
            'already_existed'    => false,
            'message'            => $msg,
        ];
    }

    private static function db(): ?PDO
    {
        try {
            $db = \db();
            return $db instanceof PDO ? $db : null;
        } catch (Throwable $e) {
            error_log('[ShippingDispatcher] db() falhou: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function loadIntegration(PDO $db, int $companyId): ?array
    {
        $stmt = $db->prepare(
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
    private static function loadRow(PDO $db, int $companyId, string $env, string $externalRef): ?array
    {
        $stmt = $db->prepare(
            'SELECT id, status, ifood_shipping_id
               FROM ifood_shipping_orders
              WHERE company_id = :cid AND environment = :env AND external_reference = :ref
              LIMIT 1'
        );
        $stmt->execute([':cid' => $companyId, ':env' => $env, ':ref' => $externalRef]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
