<?php

declare(strict_types=1);

namespace App\Services\IFood;

use PDO;
use QueueJob;
use Throwable;

/**
 * Helper de enfileiramento para `ifood.driver.request`.
 *
 * Coalesce múltiplos requests para o mesmo pedido (mesmo se vierem de UI e
 * de auto-trigger simultaneamente). O handler é idempotente quanto ao 409.
 *
 * Skip silencioso quando:
 *   - integração iFood inativa/ausente
 *   - pedido não existe em ifood_orders
 *   - pedido não está em delivered_by=MERCHANT
 *   - pedido está em CONCLUDED ou CANCELLED
 *
 * O handler ainda revalida tudo em runtime; este pre-check só evita
 * enfileirar jobs que vão skipar instantaneamente.
 */
final class DriverRequestDispatcher
{
    private const DEFAULT_DELAY_SECONDS = 0;

    /**
     * Estados finais — não faz sentido pedir driver.
     */
    private const TERMINAL_STATUSES = ['CONCLUDED', 'CANCELLED'];

    /**
     * @return bool true se um job foi inserido; false se foi coalescido ou pulado.
     */
    public static function requestForOrder(
        int $companyId,
        string $ifoodOrderId,
        int $delaySeconds = self::DEFAULT_DELAY_SECONDS
    ): bool {
        if ($companyId <= 0 || trim($ifoodOrderId) === '') {
            return false;
        }

        $db = self::db();
        if ($db === null) {
            return false;
        }

        try {
            $integration = self::loadIntegration($db, $companyId);
            if ($integration === null) {
                return false;
            }

            $order = self::loadOrder($db, $companyId, $ifoodOrderId);
            if ($order === null) {
                return false;
            }

            $deliveredBy = (string) ($order['delivered_by'] ?? '');
            if ($deliveredBy !== 'MERCHANT') {
                return false; // iFood entrega; sem necessidade de request driver
            }

            $status = (string) ($order['status'] ?? '');
            if (in_array($status, self::TERMINAL_STATUSES, true)) {
                return false; // pedido encerrado
            }
        } catch (Throwable $e) {
            error_log('[DriverRequestDispatcher] precheck falhou: ' . $e->getMessage());
            return false;
        }

        $env = IFoodEndpoints::normalizeEnvironment((string) ($integration['environment'] ?? 'production'));
        $dedupKey = sprintf('ifood.driver:%d:%s:%s', $companyId, $env, $ifoodOrderId);
        $availableAt = date('Y-m-d H:i:s', time() + max(0, $delaySeconds));

        if (!class_exists('QueueJob', false)) {
            require_once __DIR__ . '/../../models/QueueJob.php';
        }

        return QueueJob::enqueueCoalesced(
            'ifood.driver.request',
            $dedupKey,
            [
                'ifood_order_id' => $ifoodOrderId,
                'environment'    => $env,
            ],
            $companyId,
            4, // priority ligeiramente acima do default (5) — driver é tempo-crítico
            5,
            $availableAt
        );
    }

    /**
     * Enfileira cancelamento de entregador. Skipa silenciosamente quando
     * não há entry em ifood_order_drivers ou quando já está em estado terminal.
     */
    public static function cancelForOrder(
        int $companyId,
        string $ifoodOrderId,
        string $reason = 'admin_cancel',
        int $delaySeconds = self::DEFAULT_DELAY_SECONDS
    ): bool {
        if ($companyId <= 0 || trim($ifoodOrderId) === '') {
            return false;
        }

        $db = self::db();
        if ($db === null) {
            return false;
        }

        try {
            $integration = self::loadIntegration($db, $companyId);
            if ($integration === null) {
                return false;
            }
        } catch (Throwable $e) {
            error_log('[DriverRequestDispatcher::cancel] precheck falhou: ' . $e->getMessage());
            return false;
        }

        $env = IFoodEndpoints::normalizeEnvironment((string) ($integration['environment'] ?? 'production'));

        // Skipa quando não há driver request OU já está em estado terminal.
        $stmt = $db->prepare(
            "SELECT request_status FROM ifood_order_drivers
              WHERE company_id=:cid AND environment=:env AND ifood_order_id=:oid LIMIT 1"
        );
        $stmt->execute([':cid' => $companyId, ':env' => $env, ':oid' => $ifoodOrderId]);
        $current = (string) ($stmt->fetchColumn() ?: '');
        if ($current === '' || in_array($current, ['COMPLETED', 'CANCELLED', 'FAILED'], true)) {
            return false;
        }

        $dedupKey = sprintf('ifood.driver-cancel:%d:%s:%s', $companyId, $env, $ifoodOrderId);
        $availableAt = date('Y-m-d H:i:s', time() + max(0, $delaySeconds));

        if (!class_exists('QueueJob', false)) {
            require_once __DIR__ . '/../../models/QueueJob.php';
        }

        return QueueJob::enqueueCoalesced(
            'ifood.driver.cancel',
            $dedupKey,
            [
                'ifood_order_id' => $ifoodOrderId,
                'environment'    => $env,
                'reason'         => $reason,
            ],
            $companyId,
            3, // ainda mais urgente que request
            3,
            $availableAt
        );
    }

    private static function db(): ?PDO
    {
        try {
            $db = \db();
            return $db instanceof PDO ? $db : null;
        } catch (Throwable $e) {
            error_log('[DriverRequestDispatcher] db() falhou: ' . $e->getMessage());
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
    private static function loadOrder(PDO $db, int $companyId, string $ifoodOrderId): ?array
    {
        $stmt = $db->prepare(
            'SELECT status, delivered_by FROM ifood_orders
              WHERE company_id = :cid AND ifood_order_id = :oid LIMIT 1'
        );
        $stmt->execute([':cid' => $companyId, ':oid' => $ifoodOrderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
