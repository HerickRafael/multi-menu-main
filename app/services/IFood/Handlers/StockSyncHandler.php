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
 * Job: `ifood.stock.sync`
 *
 * Sincroniza o status de disponibilidade (AVAILABLE/UNAVAILABLE) de um produto
 * local com o catálogo do iFood (endpoint /catalog/v2.0/.../items/{id}/status).
 *
 * Payload:
 *   product_id   int     (obrigatório) products.id
 *   environment  string  (opcional)    sandbox|production; default = config da company
 *
 * O handler NÃO confia no payload para decidir o estado: relê `products.active`
 * no momento da execução. Isso garante que múltiplas alterações coalescidas
 * resultem no estado final correto (a última verdade do banco).
 *
 * Fluxo:
 *   1. Valida company_id e product_id.
 *   2. Resolve mapping em ifood_product_mapping (skip se não existe ou inativo).
 *   3. Lê estado desejado atual: products.active → AVAILABLE/UNAVAILABLE.
 *   4. Compara com last_synced_status; se igual, marca como ok e sai (no-op).
 *   5. Resolve merchant_id ativo e token.
 *   6. PUT /catalog/v2.0/merchants/{merchant}/items/{ifood_product_id}/status.
 *   7. Sucesso → upsert em ifood_stock_sync_state (zera consecutive_failures).
 *   8. 404 → desativa mapeamento e morre permanente.
 *   9. 401/429/5xx/rede → IFoodRetryableException.
 *  10. Outros 4xx → RuntimeException (dead).
 */
final class StockSyncHandler implements IFoodJobHandler
{
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
            throw new RuntimeException('StockSyncHandler: company_id ausente');
        }

        $productId = (int) ($payload['product_id'] ?? 0);
        if ($productId <= 0) {
            throw new RuntimeException('StockSyncHandler: product_id ausente');
        }

        $mapping = $this->loadMapping($companyId, $productId);
        if ($mapping === null) {
            // Produto sem mapeamento ativo → nada a fazer. Não é erro.
            error_log(sprintf('[StockSyncHandler] skip company=%d product=%d (sem mapping)', $companyId, $productId));
            return;
        }

        $integration = $this->loadIntegration($companyId);
        if ($integration === null) {
            throw new RuntimeException('StockSyncHandler: integração iFood inativa ou ausente');
        }

        $envFromPayload = isset($payload['environment']) ? (string) $payload['environment'] : null;
        $env = IFoodEndpoints::normalizeEnvironment($envFromPayload ?? (string) ($integration['environment'] ?? 'production'));

        $product = $this->loadProductActive($companyId, $productId);
        if ($product === null) {
            // Produto sumiu do banco → o mapping ficou órfão. Não acionamos o iFood.
            $this->deactivateMapping($companyId, (string) $mapping['ifood_product_id']);
            error_log(sprintf('[StockSyncHandler] product=%d removido localmente; mapping desativado', $productId));
            return;
        }

        $desiredStatus = ((int) $product['active'] === 1) ? 'AVAILABLE' : 'UNAVAILABLE';

        $state = $this->loadSyncState($companyId, $env, $productId);
        if ($state !== null && $state['last_synced_status'] === $desiredStatus) {
            // Snapshot já bate com o desejado: nada a sincronizar.
            // Ainda assim atualizamos desired_status caso tenha mudado entre snapshots.
            $this->upsertState($companyId, $env, $productId, (string) $mapping['ifood_product_id'], $desiredStatus, $desiredStatus, null, 0);
            return;
        }

        $token = $this->resolveToken($companyId);
        if ($token === null || $token === '') {
            throw new IFoodRetryableException('StockSyncHandler: sem access_token (falha transitória de auth)');
        }

        $merchantId = $this->resolveMerchantId($companyId);
        if ($merchantId === '') {
            throw new RuntimeException('StockSyncHandler: merchant_id ausente para o environment ' . $env);
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

        $url = IFoodEndpoints::catalogItemStatus($env, $merchantId, (string) $mapping['ifood_product_id']);
        $response = $client->put($url, IFoodEndpoints::MODULE_CATALOG, ['status' => $desiredStatus]);

        if ($response->ok) {
            $this->upsertState(
                $companyId,
                $env,
                $productId,
                (string) $mapping['ifood_product_id'],
                $desiredStatus,
                $desiredStatus,
                null,
                0
            );
            error_log(sprintf(
                '[StockSyncHandler] ok company=%d env=%s product=%d ifood=%s status=%s',
                $companyId,
                $env,
                $productId,
                (string) $mapping['ifood_product_id'],
                $desiredStatus
            ));
            return;
        }

        // Falha: persiste estado de erro antes de propagar (visibilidade no dashboard).
        $errMsg = (string) ($response->error ?? 'erro desconhecido');
        $this->upsertState(
            $companyId,
            $env,
            $productId,
            (string) $mapping['ifood_product_id'],
            $desiredStatus,
            $state['last_synced_status'] ?? null,
            $errMsg,
            (int) ($state['consecutive_failures'] ?? 0) + 1
        );

        // 404 → mapeamento inválido. Desativa e mata permanente.
        if ($response->status === 404) {
            $this->deactivateMapping($companyId, (string) $mapping['ifood_product_id']);
            throw new RuntimeException(sprintf(
                'StockSyncHandler: produto %s não existe no iFood (mapping desativado)',
                (string) $mapping['ifood_product_id']
            ));
        }

        // 401 → token estourou; vale tentar de novo (com refresh no próximo poll).
        if ($response->status === 401) {
            throw new IFoodRetryableException('StockSyncHandler: 401 unauthorized (' . $errMsg . ')');
        }

        // 429 / 5xx / network → transitório.
        if ($response->status === null || $response->status === 429 || $response->status >= 500) {
            throw new IFoodRetryableException($errMsg);
        }

        // Outros 4xx → permanente.
        throw new RuntimeException($errMsg);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function loadMapping(int $companyId, int $productId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ifood_product_id
               FROM ifood_product_mapping
              WHERE company_id = :cid
                AND product_id = :pid
                AND is_active = 1
              LIMIT 1'
        );
        $stmt->execute([':cid' => $companyId, ':pid' => $productId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
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
    private function loadProductActive(int $companyId, int $productId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT active
               FROM products
              WHERE id = :pid AND company_id = :cid
              LIMIT 1'
        );
        $stmt->execute([':pid' => $productId, ':cid' => $companyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function loadSyncState(int $companyId, string $env, int $productId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT last_synced_status, consecutive_failures
               FROM ifood_stock_sync_state
              WHERE company_id = :cid AND environment = :env AND product_id = :pid
              LIMIT 1'
        );
        $stmt->execute([':cid' => $companyId, ':env' => $env, ':pid' => $productId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function upsertState(
        int $companyId,
        string $env,
        int $productId,
        string $ifoodProductId,
        string $desired,
        ?string $lastSynced,
        ?string $error,
        int $consecutiveFailures
    ): void {
        // Em falhas, last_synced_at vem NULL. Preservamos o valor antigo via IF()
        // para não perder o histórico do último sync bem-sucedido.
        $sql = 'INSERT INTO ifood_stock_sync_state
              (company_id, environment, product_id, ifood_product_id,
               desired_status, last_synced_status, last_synced_at,
               last_error, consecutive_failures)
              VALUES (:cid, :env, :pid, :ifid, :des, :ls, :sat, :err, :cf)
              ON DUPLICATE KEY UPDATE
                desired_status       = VALUES(desired_status),
                last_synced_status   = VALUES(last_synced_status),
                last_synced_at       = IF(VALUES(last_synced_at) IS NULL,
                                          last_synced_at,
                                          VALUES(last_synced_at)),
                last_error           = VALUES(last_error),
                consecutive_failures = VALUES(consecutive_failures),
                ifood_product_id     = VALUES(ifood_product_id)';

        $syncedAt = ($error === null && $lastSynced !== null) ? date('Y-m-d H:i:s') : null;

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cid'  => $companyId,
            ':env'  => $env,
            ':pid'  => $productId,
            ':ifid' => $ifoodProductId,
            ':des'  => $desired,
            ':ls'   => $lastSynced,
            ':sat'  => $syncedAt,
            ':err'  => $error !== null ? mb_substr($error, 0, 500, 'UTF-8') : null,
            ':cf'   => $consecutiveFailures,
        ]);
    }

    private function deactivateMapping(int $companyId, string $ifoodProductId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ifood_product_mapping
                SET is_active = 0, updated_at = NOW()
              WHERE company_id = :cid AND ifood_product_id = :ifid'
        );
        $stmt->execute([':cid' => $companyId, ':ifid' => $ifoodProductId]);
    }

    private function resolveToken(int $companyId): ?string
    {
        if (!class_exists('IFoodService', false)) {
            require_once __DIR__ . '/../../IFoodService.php';
        }
        $service = new IFoodService($this->db, $companyId);
        return $service->getAccessToken();
    }

    private function resolveMerchantId(int $companyId): string
    {
        if (!class_exists('IFoodService', false)) {
            require_once __DIR__ . '/../../IFoodService.php';
        }
        $service = new IFoodService($this->db, $companyId);
        return $service->getActiveMerchantId();
    }
}
