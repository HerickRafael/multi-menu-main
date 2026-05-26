<?php

declare(strict_types=1);

namespace App\Services\IFood;

use PDO;
use Throwable;

/**
 * Observabilidade da Dead-Letter-Queue e da saúde das chamadas iFood.
 *
 * Agrega `queue_jobs` (status, retries, errors) + `ifood_api_logs`
 * (latência, http_status, módulo) em métricas operacionais e dá ações
 * de remediação (retry de jobs mortos).
 *
 * NOTA: queries são cross-company por design — DLQ é problema de
 * infra/operação, não de tenant. Endpoints admin filtram por company
 * quando relevante.
 */
final class DLQObservabilityService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Resposta consolidada — uma chamada pra UI de operação.
     *
     * @return array<string,mixed>
     */
    public function health(): array
    {
        return [
            'generated_at'  => date('Y-m-d H:i:s'),
            'queue'         => $this->queueStats(),
            'api'           => $this->apiHealth(),
            'latency'       => $this->latencyStats(),
            'top_errors'    => $this->topErrors(),
            'thresholds'    => $this->thresholds(),
        ];
    }

    /**
     * Counters de queue_jobs por status + job_type, com janela de 24h e 1h
     * para detecção de aumentos súbitos.
     *
     * @return array<string,mixed>
     */
    public function queueStats(): array
    {
        $out = [
            'by_status'        => [],
            'dead_total'       => 0,
            'dead_1h'          => 0,
            'dead_24h'         => 0,
            'retrying_total'   => 0,
            'by_type_dead_24h' => [],
        ];

        try {
            $stmt = $this->db->query(
                "SELECT status, COUNT(*) AS c
                   FROM queue_jobs
                  WHERE job_type LIKE 'ifood.%'
                  GROUP BY status"
            );
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $out['by_status'][(string) $row['status']] = (int) $row['c'];
            }
            $out['dead_total']     = (int) ($out['by_status']['dead']     ?? 0);
            $out['retrying_total'] = (int) ($out['by_status']['retrying'] ?? 0);

            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM queue_jobs
                  WHERE job_type LIKE 'ifood.%'
                    AND status = 'dead'
                    AND updated_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            $out['dead_1h'] = (int) $stmt->fetchColumn();

            $stmt = $this->db->query(
                "SELECT job_type, COUNT(*) AS c
                   FROM queue_jobs
                  WHERE job_type LIKE 'ifood.%'
                    AND status = 'dead'
                    AND updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  GROUP BY job_type
                  ORDER BY c DESC
                  LIMIT 20"
            );
            $by = [];
            $total24h = 0;
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $by[(string) $row['job_type']] = (int) $row['c'];
                $total24h += (int) $row['c'];
            }
            $out['by_type_dead_24h'] = $by;
            $out['dead_24h'] = $total24h;
        } catch (Throwable $e) {
            error_log('[DLQObservability] queueStats falhou: ' . $e->getMessage());
        }

        return $out;
    }

    /**
     * Lista jobs mortos com paginação + filtro opcional por job_type e company.
     *
     * @return array<int,array<string,mixed>>
     */
    public function deadJobs(?int $companyId = null, ?string $jobType = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT id, company_id, job_type, attempts, max_attempts,
                       last_error, payload_json,
                       created_at, updated_at, reserved_at
                  FROM queue_jobs
                 WHERE status = 'dead'
                   AND job_type LIKE 'ifood.%'";
        $bind = [];
        if ($companyId !== null) {
            $sql .= ' AND company_id = ?';
            $bind[] = $companyId;
        }
        if ($jobType !== null && $jobType !== '') {
            $sql .= ' AND job_type = ?';
            $bind[] = $jobType;
        }
        $sql .= ' ORDER BY updated_at DESC LIMIT ? OFFSET ?';
        $bind[] = max(1, min(200, $limit));
        $bind[] = max(0, $offset);

        try {
            $stmt = $this->db->prepare($sql);
            foreach ($bind as $i => $v) {
                $stmt->bindValue($i + 1, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('[DLQObservability] deadJobs falhou: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Resuscita um job morto: volta para `retrying`, zera reserved_at,
     * available_at = NOW(). O worker pega na próxima rodada.
     *
     * Não zera attempts — quem retry manualmente assume que sabe o que faz;
     * se quiser começar do zero, usar `retryDeadJob($id, true)`.
     *
     * @return array{ok:bool, id:int, message:?string}
     */
    public function retryDeadJob(int $jobId, bool $resetAttempts = false): array
    {
        if ($jobId <= 0) {
            return ['ok' => false, 'id' => $jobId, 'message' => 'id inválido'];
        }
        try {
            $sql = "UPDATE queue_jobs
                       SET status        = 'retrying',
                           reserved_at   = NULL,
                           available_at  = NOW(),
                           last_error    = NULL"
                . ($resetAttempts ? ', attempts = 0' : '')
                . " WHERE id = :id AND status = 'dead'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $jobId]);
            if ($stmt->rowCount() === 0) {
                return ['ok' => false, 'id' => $jobId, 'message' => 'job não existe ou não está dead'];
            }
            return ['ok' => true, 'id' => $jobId, 'message' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'id' => $jobId, 'message' => $e->getMessage()];
        }
    }

    /**
     * Resuscita em lote — todos os dead de um job_type (opcionalmente
     * limitado à company).
     *
     * @return array{ok:bool, retried:int, message:?string}
     */
    public function retryDeadJobsByType(string $jobType, ?int $companyId = null, int $limit = 100): array
    {
        if (trim($jobType) === '' || !str_starts_with($jobType, 'ifood.')) {
            return ['ok' => false, 'retried' => 0, 'message' => 'job_type deve começar com ifood.'];
        }
        try {
            $sql = "UPDATE queue_jobs
                       SET status        = 'retrying',
                           reserved_at   = NULL,
                           available_at  = NOW(),
                           last_error    = NULL
                     WHERE status = 'dead'
                       AND job_type = :jt";
            $bind = [':jt' => $jobType];
            if ($companyId !== null) {
                $sql .= ' AND company_id = :cid';
                $bind[':cid'] = $companyId;
            }
            $sql .= ' ORDER BY updated_at DESC LIMIT ' . max(1, min(1000, $limit));
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bind);
            return ['ok' => true, 'retried' => $stmt->rowCount(), 'message' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'retried' => 0, 'message' => $e->getMessage()];
        }
    }

    /**
     * Health da API iFood: taxa de erro, sucesso, top status codes.
     *
     * @return array<string,mixed>
     */
    public function apiHealth(): array
    {
        $out = [
            'window_hours'    => 1,
            'total_calls'     => 0,
            'success'         => 0,
            'errors_4xx'      => 0,
            'errors_5xx'      => 0,
            'network_errors'  => 0,
            'error_rate'      => 0.0,
            'by_module'       => [],
            'by_status'       => [],
        ];

        try {
            $stmt = $this->db->query(
                "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN http_status BETWEEN 200 AND 299 THEN 1 ELSE 0 END) AS success,
                    SUM(CASE WHEN http_status BETWEEN 400 AND 499 THEN 1 ELSE 0 END) AS e4xx,
                    SUM(CASE WHEN http_status >= 500 THEN 1 ELSE 0 END) AS e5xx,
                    SUM(CASE WHEN http_status IS NULL THEN 1 ELSE 0 END) AS network
                   FROM ifood_api_logs
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $out['total_calls']    = (int) ($row['total']   ?? 0);
            $out['success']        = (int) ($row['success'] ?? 0);
            $out['errors_4xx']     = (int) ($row['e4xx']    ?? 0);
            $out['errors_5xx']     = (int) ($row['e5xx']    ?? 0);
            $out['network_errors'] = (int) ($row['network'] ?? 0);
            if ($out['total_calls'] > 0) {
                $errors = $out['errors_4xx'] + $out['errors_5xx'] + $out['network_errors'];
                $out['error_rate'] = round($errors / $out['total_calls'], 4);
            }

            $stmt = $this->db->query(
                "SELECT module, http_status, COUNT(*) AS c
                   FROM ifood_api_logs
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                  GROUP BY module, http_status
                  ORDER BY c DESC
                  LIMIT 30"
            );
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $module = (string) $row['module'];
                $status = $row['http_status'] !== null ? (int) $row['http_status'] : 0;
                $key = $status === 0 ? 'network' : (string) $status;
                $out['by_module'][$module][$key] = (int) $row['c'];
                $out['by_status'][$key] = ($out['by_status'][$key] ?? 0) + (int) $row['c'];
            }
        } catch (Throwable $e) {
            error_log('[DLQObservability] apiHealth falhou: ' . $e->getMessage());
        }

        return $out;
    }

    /**
     * Estatísticas de latência (ms) das chamadas iFood na última hora.
     *
     * @return array<string,mixed>
     */
    public function latencyStats(): array
    {
        $out = [
            'window_hours' => 1,
            'count'        => 0,
            'avg_ms'       => null,
            'p50_ms'       => null,
            'p95_ms'       => null,
            'p99_ms'       => null,
            'max_ms'       => null,
        ];

        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) AS c, AVG(latency_ms) AS avg_l, MAX(latency_ms) AS max_l
                   FROM ifood_api_logs
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                    AND latency_ms IS NOT NULL"
            );
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $count = (int) ($row['c'] ?? 0);
            $out['count']  = $count;
            $out['avg_ms'] = $row['avg_l'] !== null ? (int) round((float) $row['avg_l']) : null;
            $out['max_ms'] = $row['max_l'] !== null ? (int) $row['max_l'] : null;

            if ($count > 0) {
                // Percentis sem CTE/window functions (compatível com MySQL 5.7+):
                // pegamos a linha de offset = floor(N * p) ordenada por latency.
                // Nota: chaves float em array literals viram int 0 no PHP — invertemos col=>p.
                $percentiles = ['p50_ms' => 0.50, 'p95_ms' => 0.95, 'p99_ms' => 0.99];
                foreach ($percentiles as $col => $p) {
                    $offset = max(0, (int) floor($count * $p) - 1);
                    $stmt = $this->db->prepare(
                        "SELECT latency_ms FROM ifood_api_logs
                          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                            AND latency_ms IS NOT NULL
                          ORDER BY latency_ms ASC
                          LIMIT 1 OFFSET ?"
                    );
                    $stmt->bindValue(1, $offset, PDO::PARAM_INT);
                    $stmt->execute();
                    $v = $stmt->fetchColumn();
                    $out[$col] = $v !== false ? (int) $v : null;
                }
            }
        } catch (Throwable $e) {
            error_log('[DLQObservability] latencyStats falhou: ' . $e->getMessage());
        }

        return $out;
    }

    /**
     * Top mensagens de erro em queue_jobs (dead + retrying) das últimas 24h.
     *
     * @return array<int,array{error:string, count:int, sample_job_id:int, sample_job_type:string}>
     */
    public function topErrors(int $limit = 10): array
    {
        try {
            // Agrupamos pelos primeiros 80 chars do error pra mensagens longas
            // (caminho de arquivo + linha) virem em buckets coerentes.
            $stmt = $this->db->prepare(
                "SELECT LEFT(last_error, 80) AS err,
                        COUNT(*) AS c,
                        MAX(id) AS sample_id,
                        MAX(job_type) AS sample_type
                   FROM queue_jobs
                  WHERE last_error IS NOT NULL
                    AND status IN ('dead', 'retrying')
                    AND job_type LIKE 'ifood.%'
                    AND updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  GROUP BY LEFT(last_error, 80)
                  ORDER BY c DESC
                  LIMIT ?"
            );
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $out = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $out[] = [
                    'error'           => (string) $row['err'],
                    'count'           => (int) $row['c'],
                    'sample_job_id'   => (int) $row['sample_id'],
                    'sample_job_type' => (string) $row['sample_type'],
                ];
            }
            return $out;
        } catch (Throwable $e) {
            error_log('[DLQObservability] topErrors falhou: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Thresholds para alertas — env-overridável.
     *
     * @return array<string,mixed>
     */
    public function thresholds(): array
    {
        return [
            'dead_jobs_1h'    => (int)   (getenv('IFOOD_DLQ_DEAD_1H')        ?: 5),
            'api_error_rate'  => (float) (getenv('IFOOD_DLQ_API_ERROR_RATE') ?: 0.10),
            'latency_p95_ms'  => (int)   (getenv('IFOOD_DLQ_LATENCY_P95')    ?: 5000),
        ];
    }

    /**
     * Avalia thresholds e devolve lista de alertas que estão disparando agora.
     * Usado pelo cron de alertas para decidir se notifica (Slack/log).
     *
     * @return array<int,array{level:string, type:string, message:string, value:mixed, threshold:mixed}>
     */
    public function alertingViolations(): array
    {
        $alerts = [];
        $th = $this->thresholds();

        $queue = $this->queueStats();
        if ($queue['dead_1h'] > $th['dead_jobs_1h']) {
            $alerts[] = [
                'level'     => 'critical',
                'type'      => 'dlq_burst',
                'message'   => "{$queue['dead_1h']} jobs morreram na última hora (limite {$th['dead_jobs_1h']})",
                'value'     => $queue['dead_1h'],
                'threshold' => $th['dead_jobs_1h'],
            ];
        }

        $api = $this->apiHealth();
        if ($api['total_calls'] >= 20 && $api['error_rate'] > $th['api_error_rate']) {
            $alerts[] = [
                'level'     => 'warning',
                'type'      => 'api_error_rate',
                'message'   => sprintf(
                    'taxa de erro da API iFood em %.1f%% na última hora (limite %.1f%%, %d chamadas)',
                    $api['error_rate'] * 100,
                    $th['api_error_rate'] * 100,
                    $api['total_calls']
                ),
                'value'     => $api['error_rate'],
                'threshold' => $th['api_error_rate'],
            ];
        }

        $lat = $this->latencyStats();
        if ($lat['count'] >= 20 && $lat['p95_ms'] !== null && $lat['p95_ms'] > $th['latency_p95_ms']) {
            $alerts[] = [
                'level'     => 'warning',
                'type'      => 'api_latency_p95',
                'message'   => "p95 da API iFood em {$lat['p95_ms']}ms na última hora (limite {$th['latency_p95_ms']}ms)",
                'value'     => $lat['p95_ms'],
                'threshold' => $th['latency_p95_ms'],
            ];
        }

        return $alerts;
    }
}
