<?php

declare(strict_types=1);

namespace App\Services\IFood;

use PDO;
use QueueJob;
use Throwable;

/**
 * Helper de enfileiramento para `ifood.stock.sync`.
 *
 * - Coalesce múltiplas mudanças do mesmo produto numa janela curta em 1 job.
 * - Skipa silenciosamente quando o produto não tem mapeamento iFood ou a
 *   integração está desativada (não é erro — produto simplesmente não está no iFood).
 *
 * O handler (`StockSyncHandler`) NÃO confia no payload — ele relê o estado
 * atual de `products.active` na hora de processar. Aqui só passamos os
 * identificadores mínimos.
 */
final class StockSyncDispatcher
{
    /** Janela de coalescing em segundos (debounce). */
    private const DEFAULT_DELAY_SECONDS = 2;

    /**
     * Enfileira um sync para um produto. Retorna true se um job foi inserido,
     * false se foi coalescido com um existente ou pulado por falta de pré-requisito.
     */
    public static function syncProduct(int $companyId, int $productId, int $delaySeconds = self::DEFAULT_DELAY_SECONDS): bool
    {
        if ($companyId <= 0 || $productId <= 0) {
            return false;
        }

        $db = self::db();
        if ($db === null) {
            return false;
        }

        try {
            // Skip se não há integração iFood ativa.
            $integration = self::loadIntegration($db, $companyId);
            if ($integration === null) {
                return false;
            }

            // Skip se o produto não está mapeado no iFood.
            if (!self::hasMapping($db, $companyId, $productId)) {
                return false;
            }
        } catch (Throwable $e) {
            error_log('[StockSyncDispatcher] precheck falhou: ' . $e->getMessage());
            return false;
        }

        $env = IFoodEndpoints::normalizeEnvironment((string) ($integration['environment'] ?? 'production'));
        $dedupKey = sprintf('ifood.stock:%d:%s:%d', $companyId, $env, $productId);
        $availableAt = date('Y-m-d H:i:s', time() + max(0, $delaySeconds));

        if (!class_exists('QueueJob', false)) {
            require_once __DIR__ . '/../../models/QueueJob.php';
        }

        return QueueJob::enqueueCoalesced(
            'ifood.stock.sync',
            $dedupKey,
            [
                'product_id'  => $productId,
                'environment' => $env,
            ],
            $companyId,
            5,
            5,
            $availableAt
        );
    }

    /**
     * Enfileira sync para todos os produtos mapeados de uma company.
     * Útil para "sincronizar tudo" no admin.
     *
     * Os jobs são escalonados (delay crescente) para amaciar o rate limit
     * caso a fila esteja vazia e tudo seja processado em sequência rápida.
     *
     * @return int número de jobs efetivamente enfileirados (após coalescing)
     */
    public static function syncAllForCompany(int $companyId, int $baseDelaySeconds = 0, int $stepSeconds = 1): int
    {
        $db = self::db();
        if ($db === null) {
            return 0;
        }

        $stmt = $db->prepare(
            'SELECT m.product_id
               FROM ifood_product_mapping m
              WHERE m.company_id = :cid
                AND m.is_active = 1
                AND m.product_id IS NOT NULL
              ORDER BY m.product_id ASC'
        );
        $stmt->execute([':cid' => $companyId]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $count = 0;
        $i = 0;
        foreach ($rows as $pid) {
            $productId = (int) $pid;
            if ($productId <= 0) {
                continue;
            }
            $delay = $baseDelaySeconds + ($i++ * $stepSeconds);
            if (self::syncProduct($companyId, $productId, $delay)) {
                $count++;
            }
        }

        return $count;
    }

    private static function db(): ?PDO
    {
        try {
            $db = \db();
            return $db instanceof PDO ? $db : null;
        } catch (Throwable $e) {
            error_log('[StockSyncDispatcher] db() falhou: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function loadIntegration(PDO $db, int $companyId): ?array
    {
        $stmt = $db->prepare(
            'SELECT environment, is_active
               FROM ifood_integrations
              WHERE company_id = :cid
              LIMIT 1'
        );
        $stmt->execute([':cid' => $companyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        if ((int) ($row['is_active'] ?? 0) !== 1) {
            return null;
        }
        return $row;
    }

    private static function hasMapping(PDO $db, int $companyId, int $productId): bool
    {
        $stmt = $db->prepare(
            'SELECT 1 FROM ifood_product_mapping
              WHERE company_id = :cid
                AND product_id = :pid
                AND is_active = 1
              LIMIT 1'
        );
        $stmt->execute([':cid' => $companyId, ':pid' => $productId]);
        return $stmt->fetchColumn() !== false;
    }
}
