<?php

declare(strict_types=1);

namespace App\Services\IFood;

use PDO;
use Throwable;

/**
 * Central de logística — agrega o estado de pedidos iFood (nativos + shipping),
 * entregadores, estoque e infra para dar visão operacional em tempo (quase) real.
 *
 * Cada método retorna estrutura JSON-safe pronta pra responder ao caller.
 * Não toca em HTTP — é pura camada de dados.
 *
 * Cache: as queries são pesadas para chamar a cada 1-2s. Service mantém um
 * cache em memória por instância (escopo de request). Para cache cross-request,
 * o caller pode wrappear com cache de filesystem/Redis.
 *
 * SLAs default (env-overridável):
 *   - Cozinha: CONFIRMED → READY_TO_PICKUP em até 25 min
 *   - Retirada: READY_TO_PICKUP → DISPATCHED em até 10 min
 *   - Entrega: DISPATCHED → CONCLUDED em até 45 min
 *   - NO_DRIVER: tolera 10 min antes de virar crítico
 *   - SUBMITTED (shipping): tolera 60 min sem confirmação
 */
final class LogisticsDashboardService
{
    private PDO $db;
    /** @var array<string,mixed> */
    private array $cache = [];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Devolve TUDO em uma chamada — para o frontend polar 1x por refresh.
     *
     * @return array<string,mixed>
     */
    public function dashboard(int $companyId): array
    {
        return [
            'company_id'  => $companyId,
            'generated_at'=> date('Y-m-d H:i:s'),
            'sla'         => $this->slaThresholds(),
            'summary'     => $this->summary($companyId),
            'active'      => $this->activeOrders($companyId, 30),
            'metrics_24h' => $this->metrics24h($companyId),
            'alerts'      => $this->alerts($companyId),
            'queue_health'=> $this->queueHealth(),
        ];
    }

    /**
     * Counters do estado atual.
     *
     * @return array<string,int|array<string,int>>
     */
    public function summary(int $companyId): array
    {
        $key = "summary:{$companyId}";
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $out = [
            'ifood_orders_active'    => 0,
            'ifood_orders_by_status' => [],
            'drivers_in_route'       => 0,
            'drivers_waiting'        => 0,
            'shipping_active'        => 0,
            'shipping_by_status'     => [],
            'stock_drift'            => 0,
        ];

        try {
            // ifood_orders ativos (não terminais)
            $stmt = $this->db->prepare(
                "SELECT status, COUNT(*) AS c
                   FROM ifood_orders
                  WHERE company_id = ? AND status NOT IN ('CONCLUDED','CANCELLED')
                  GROUP BY status"
            );
            $stmt->execute([$companyId]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $st = (string) $row['status'];
                $out['ifood_orders_by_status'][$st] = (int) $row['c'];
                $out['ifood_orders_active'] += (int) $row['c'];
            }

            // Entregadores em rota (ASSIGNED com picked_up_at) vs aguardando (ASSIGNED sem picked_up)
            $stmt = $this->db->prepare(
                "SELECT
                    SUM(CASE WHEN picked_up_at IS NOT NULL THEN 1 ELSE 0 END) AS in_route,
                    SUM(CASE WHEN picked_up_at IS NULL THEN 1 ELSE 0 END) AS waiting
                   FROM ifood_order_drivers
                  WHERE company_id = ? AND request_status = 'ASSIGNED'"
            );
            $stmt->execute([$companyId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $out['drivers_in_route'] = (int) ($row['in_route'] ?? 0);
            $out['drivers_waiting']  = (int) ($row['waiting']  ?? 0);

            // Shipping ativos
            $stmt = $this->db->prepare(
                "SELECT status, COUNT(*) AS c
                   FROM ifood_shipping_orders
                  WHERE company_id = ?
                    AND status NOT IN ('DELIVERED','CANCELLED','REJECTED','FAILED')
                  GROUP BY status"
            );
            $stmt->execute([$companyId]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $st = (string) $row['status'];
                $out['shipping_by_status'][$st] = (int) $row['c'];
                $out['shipping_active'] += (int) $row['c'];
            }

            // Stock drift
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM ifood_stock_sync_state
                  WHERE company_id = ?
                    AND last_synced_status IS NOT NULL
                    AND desired_status <> last_synced_status"
            );
            $stmt->execute([$companyId]);
            $out['stock_drift'] = (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('[LogisticsDashboard] summary falhou: ' . $e->getMessage());
        }

        $this->cache[$key] = $out;
        return $out;
    }

    /**
     * Lista pedidos ativos (iFood + shipping) com tempo no estado atual e SLA flag.
     *
     * @return array<string,array<int,array<string,mixed>>>
     */
    public function activeOrders(int $companyId, int $limit = 30): array
    {
        $key = "active:{$companyId}:{$limit}";
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $out = ['ifood' => [], 'shipping' => []];
        $sla = $this->slaThresholds();

        try {
            // iFood orders ativos com tempo de cada etapa
            $stmt = $this->db->prepare(
                "SELECT ifood_order_id, ifood_display_id, status, delivered_by,
                        confirmed_at, ready_at, dispatched_at,
                        TIMESTAMPDIFF(MINUTE,
                                      COALESCE(dispatched_at, ready_at, confirmed_at, ifood_created_at, created_at),
                                      NOW()) AS minutes_in_state
                   FROM ifood_orders
                  WHERE company_id = ?
                    AND status NOT IN ('CONCLUDED','CANCELLED')
                  ORDER BY minutes_in_state DESC
                  LIMIT ?"
            );
            $stmt->bindValue(1, $companyId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $minutes = (int) ($row['minutes_in_state'] ?? 0);
                $status = (string) $row['status'];
                $row['minutes_in_state'] = $minutes;
                $row['sla_breach'] = $this->isOrderSlaBreached($status, $minutes, $sla);
                $out['ifood'][] = $row;
            }

            // Shipping orders ativos
            $stmt = $this->db->prepare(
                "SELECT external_reference, ifood_shipping_id, status,
                        submitted_at, accepted_at, picked_up_at,
                        TIMESTAMPDIFF(MINUTE,
                                      COALESCE(picked_up_at, accepted_at, submitted_at, created_at),
                                      NOW()) AS minutes_in_state
                   FROM ifood_shipping_orders
                  WHERE company_id = ?
                    AND status NOT IN ('DELIVERED','CANCELLED','REJECTED','FAILED')
                  ORDER BY minutes_in_state DESC
                  LIMIT ?"
            );
            $stmt->bindValue(1, $companyId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $minutes = (int) ($row['minutes_in_state'] ?? 0);
                $status = (string) $row['status'];
                $row['minutes_in_state'] = $minutes;
                $row['sla_breach'] = $this->isShippingSlaBreached($status, $minutes, $sla);
                $out['shipping'][] = $row;
            }
        } catch (Throwable $e) {
            error_log('[LogisticsDashboard] activeOrders falhou: ' . $e->getMessage());
        }

        $this->cache[$key] = $out;
        return $out;
    }

    /**
     * Métricas operacionais das últimas 24h.
     *
     * @return array<string,mixed>
     */
    public function metrics24h(int $companyId): array
    {
        $key = "metrics:{$companyId}";
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $out = [
            'window_hours'           => 24,
            'orders_received'        => 0,
            'orders_completed'       => 0,
            'orders_cancelled'       => 0,
            'cancellation_rate'      => 0.0,
            'avg_kitchen_minutes'    => null,
            'avg_pickup_minutes'     => null,
            'avg_delivery_minutes'   => null,
            'driver_acceptance_rate' => null,
            'shipping_success_rate'  => null,
            'no_driver_events'       => 0,
        ];

        try {
            $stmt = $this->db->prepare(
                "SELECT
                    COUNT(*) AS received,
                    SUM(CASE WHEN status='CONCLUDED' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN status='CANCELLED' THEN 1 ELSE 0 END) AS cancelled,
                    AVG(CASE WHEN confirmed_at IS NOT NULL AND ready_at IS NOT NULL
                             THEN TIMESTAMPDIFF(MINUTE, confirmed_at, ready_at) END)        AS avg_kitchen,
                    AVG(CASE WHEN ready_at IS NOT NULL AND dispatched_at IS NOT NULL
                             THEN TIMESTAMPDIFF(MINUTE, ready_at, dispatched_at) END)       AS avg_pickup,
                    AVG(CASE WHEN dispatched_at IS NOT NULL AND concluded_at IS NOT NULL
                             THEN TIMESTAMPDIFF(MINUTE, dispatched_at, concluded_at) END)   AS avg_delivery
                   FROM ifood_orders
                  WHERE company_id = ?
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            $stmt->execute([$companyId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $received = (int) ($row['received'] ?? 0);
            $completed = (int) ($row['completed'] ?? 0);
            $cancelled = (int) ($row['cancelled'] ?? 0);

            $out['orders_received'] = $received;
            $out['orders_completed'] = $completed;
            $out['orders_cancelled'] = $cancelled;
            $out['cancellation_rate'] = $received > 0 ? round($cancelled / $received, 4) : 0.0;
            $out['avg_kitchen_minutes']  = $row['avg_kitchen']  !== null ? round((float) $row['avg_kitchen'], 1) : null;
            $out['avg_pickup_minutes']   = $row['avg_pickup']   !== null ? round((float) $row['avg_pickup'], 1) : null;
            $out['avg_delivery_minutes'] = $row['avg_delivery'] !== null ? round((float) $row['avg_delivery'], 1) : null;

            // Taxa de aceite de driver (REQUESTED → ASSIGNED nas últimas 24h)
            $stmt = $this->db->prepare(
                "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN request_status IN ('ASSIGNED','COMPLETED') THEN 1 ELSE 0 END) AS assigned,
                    SUM(CASE WHEN request_status = 'NO_DRIVER' THEN 1 ELSE 0 END) AS no_driver
                   FROM ifood_order_drivers
                  WHERE company_id = ?
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            $stmt->execute([$companyId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $total = (int) ($row['total'] ?? 0);
            $assigned = (int) ($row['assigned'] ?? 0);
            $out['driver_acceptance_rate'] = $total > 0 ? round($assigned / $total, 4) : null;
            $out['no_driver_events'] = (int) ($row['no_driver'] ?? 0);

            // Shipping success rate (DELIVERED / total nas últimas 24h)
            $stmt = $this->db->prepare(
                "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'DELIVERED' THEN 1 ELSE 0 END) AS delivered
                   FROM ifood_shipping_orders
                  WHERE company_id = ?
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            $stmt->execute([$companyId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $total = (int) ($row['total'] ?? 0);
            $delivered = (int) ($row['delivered'] ?? 0);
            $out['shipping_success_rate'] = $total > 0 ? round($delivered / $total, 4) : null;
        } catch (Throwable $e) {
            error_log('[LogisticsDashboard] metrics24h falhou: ' . $e->getMessage());
        }

        $this->cache[$key] = $out;
        return $out;
    }

    /**
     * Alertas críticos: condições que precisam atenção operacional agora.
     *
     * @return array<int,array{level:string, type:string, message:string, count:int, ids?:array<int,string>}>
     */
    public function alerts(int $companyId): array
    {
        $key = "alerts:{$companyId}";
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $alerts = [];
        $sla = $this->slaThresholds();

        try {
            // Pedidos cozinha estourada
            $stmt = $this->db->prepare(
                "SELECT ifood_order_id, ifood_display_id,
                        TIMESTAMPDIFF(MINUTE, confirmed_at, NOW()) AS minutes
                   FROM ifood_orders
                  WHERE company_id = ?
                    AND status = 'CONFIRMED'
                    AND confirmed_at IS NOT NULL
                    AND TIMESTAMPDIFF(MINUTE, confirmed_at, NOW()) > ?
                  ORDER BY minutes DESC
                  LIMIT 20"
            );
            $stmt->bindValue(1, $companyId, PDO::PARAM_INT);
            $stmt->bindValue(2, $sla['kitchen_minutes'], PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $alerts[] = [
                    'level'   => 'warning',
                    'type'    => 'kitchen_sla_breach',
                    'message' => count($rows) . " pedido(s) há mais de {$sla['kitchen_minutes']} min na cozinha",
                    'count'   => count($rows),
                    'ids'     => array_map(static fn($r) => (string) ($r['ifood_display_id'] ?: $r['ifood_order_id']), $rows),
                ];
            }

            // Entregas estouradas
            $stmt = $this->db->prepare(
                "SELECT ifood_order_id, ifood_display_id,
                        TIMESTAMPDIFF(MINUTE, dispatched_at, NOW()) AS minutes
                   FROM ifood_orders
                  WHERE company_id = ?
                    AND status = 'DISPATCHED'
                    AND dispatched_at IS NOT NULL
                    AND TIMESTAMPDIFF(MINUTE, dispatched_at, NOW()) > ?
                  ORDER BY minutes DESC
                  LIMIT 20"
            );
            $stmt->bindValue(1, $companyId, PDO::PARAM_INT);
            $stmt->bindValue(2, $sla['delivery_minutes'], PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $alerts[] = [
                    'level'   => 'critical',
                    'type'    => 'delivery_sla_breach',
                    'message' => count($rows) . " entrega(s) há mais de {$sla['delivery_minutes']} min em rota",
                    'count'   => count($rows),
                    'ids'     => array_map(static fn($r) => (string) ($r['ifood_display_id'] ?: $r['ifood_order_id']), $rows),
                ];
            }

            // NO_DRIVER prolongado
            $stmt = $this->db->prepare(
                "SELECT ifood_order_id,
                        TIMESTAMPDIFF(MINUTE, updated_at, NOW()) AS minutes
                   FROM ifood_order_drivers
                  WHERE company_id = ?
                    AND request_status = 'NO_DRIVER'
                    AND TIMESTAMPDIFF(MINUTE, updated_at, NOW()) > ?
                  ORDER BY minutes DESC
                  LIMIT 20"
            );
            $stmt->bindValue(1, $companyId, PDO::PARAM_INT);
            $stmt->bindValue(2, $sla['no_driver_minutes'], PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $alerts[] = [
                    'level'   => 'warning',
                    'type'    => 'no_driver_prolonged',
                    'message' => count($rows) . " pedido(s) sem entregador há > {$sla['no_driver_minutes']} min",
                    'count'   => count($rows),
                    'ids'     => array_map(static fn($r) => (string) $r['ifood_order_id'], $rows),
                ];
            }

            // Shipping travados
            $stmt = $this->db->prepare(
                "SELECT external_reference,
                        TIMESTAMPDIFF(MINUTE, submitted_at, NOW()) AS minutes
                   FROM ifood_shipping_orders
                  WHERE company_id = ?
                    AND status = 'SUBMITTED'
                    AND submitted_at IS NOT NULL
                    AND TIMESTAMPDIFF(MINUTE, submitted_at, NOW()) > ?
                  ORDER BY minutes DESC
                  LIMIT 20"
            );
            $stmt->bindValue(1, $companyId, PDO::PARAM_INT);
            $stmt->bindValue(2, $sla['shipping_submitted_minutes'], PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $alerts[] = [
                    'level'   => 'warning',
                    'type'    => 'shipping_stuck_submitted',
                    'message' => count($rows) . " shipping(s) sem confirmação iFood há > {$sla['shipping_submitted_minutes']} min",
                    'count'   => count($rows),
                    'ids'     => array_map(static fn($r) => (string) $r['external_reference'], $rows),
                ];
            }

            // Shipping failed/rejected nas últimas 24h
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM ifood_shipping_orders
                  WHERE company_id = ?
                    AND status IN ('FAILED','REJECTED')
                    AND updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            $stmt->execute([$companyId]);
            $count = (int) $stmt->fetchColumn();
            if ($count > 0) {
                $alerts[] = [
                    'level'   => 'critical',
                    'type'    => 'shipping_failures_24h',
                    'message' => "{$count} shipping(s) em FAILED/REJECTED nas últimas 24h",
                    'count'   => $count,
                ];
            }

            // Drift de estoque
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM ifood_stock_sync_state
                  WHERE company_id = ?
                    AND last_synced_status IS NOT NULL
                    AND desired_status <> last_synced_status"
            );
            $stmt->execute([$companyId]);
            $count = (int) $stmt->fetchColumn();
            if ($count > 0) {
                $alerts[] = [
                    'level'   => 'info',
                    'type'    => 'stock_drift',
                    'message' => "{$count} produto(s) com estoque dessincronizado (cron próximo cycle resolve)",
                    'count'   => $count,
                ];
            }
        } catch (Throwable $e) {
            error_log('[LogisticsDashboard] alerts falhou: ' . $e->getMessage());
        }

        $this->cache[$key] = $alerts;
        return $alerts;
    }

    /**
     * Saúde da fila de jobs iFood (cross-company — visão global).
     *
     * @return array<string,int>
     */
    public function queueHealth(): array
    {
        $key = 'queue_health';
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $out = [
            'pending'    => 0,
            'processing' => 0,
            'retrying'   => 0,
            'dead'       => 0,
            'dead_24h'   => 0,
        ];

        try {
            $stmt = $this->db->query(
                "SELECT status, COUNT(*) AS c
                   FROM queue_jobs
                  WHERE job_type LIKE 'ifood.%'
                  GROUP BY status"
            );
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $st = (string) $row['status'];
                if (isset($out[$st])) {
                    $out[$st] = (int) $row['c'];
                }
            }

            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM queue_jobs
                  WHERE job_type LIKE 'ifood.%'
                    AND status = 'dead'
                    AND updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            $out['dead_24h'] = (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('[LogisticsDashboard] queueHealth falhou: ' . $e->getMessage());
        }

        $this->cache[$key] = $out;
        return $out;
    }

    /**
     * Thresholds de SLA em minutos, env-overridáveis.
     *
     * @return array<string,int>
     */
    public function slaThresholds(): array
    {
        return [
            'kitchen_minutes'             => (int) (getenv('IFOOD_SLA_KITCHEN_MIN')        ?: 25),
            'pickup_minutes'              => (int) (getenv('IFOOD_SLA_PICKUP_MIN')         ?: 10),
            'delivery_minutes'            => (int) (getenv('IFOOD_SLA_DELIVERY_MIN')       ?: 45),
            'no_driver_minutes'           => (int) (getenv('IFOOD_SLA_NO_DRIVER_MIN')      ?: 10),
            'shipping_submitted_minutes'  => (int) (getenv('IFOOD_SLA_SHIPPING_SUB_MIN')   ?: 60),
        ];
    }

    /**
     * @param array<string,int> $sla
     */
    private function isOrderSlaBreached(string $status, int $minutesInState, array $sla): bool
    {
        return match ($status) {
            'CONFIRMED'        => $minutesInState > $sla['kitchen_minutes'],
            'READY_TO_PICKUP'  => $minutesInState > $sla['pickup_minutes'],
            'DISPATCHED'       => $minutesInState > $sla['delivery_minutes'],
            default            => false,
        };
    }

    /**
     * @param array<string,int> $sla
     */
    private function isShippingSlaBreached(string $status, int $minutesInState, array $sla): bool
    {
        return match ($status) {
            'SUBMITTED'   => $minutesInState > $sla['shipping_submitted_minutes'],
            'ACCEPTED'    => $minutesInState > $sla['no_driver_minutes'],
            'CONFIRMED'   => $minutesInState > $sla['pickup_minutes'],
            'PICKED_UP'   => $minutesInState > $sla['delivery_minutes'],
            default       => false,
        };
    }
}
