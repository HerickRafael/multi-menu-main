<?php

declare(strict_types=1);

namespace App\Services\IFood;

use PDO;
use Throwable;

/**
 * Worker que consome `queue_jobs` cujo job_type começa com 'ifood.'.
 *
 * Funcionamento:
 *  1) Reserva atomicamente o próximo job pendente/retrying disponível
 *     usando `SELECT … FOR UPDATE SKIP LOCKED` (MySQL 8.0+), o que permite
 *     múltiplas instâncias do worker rodando em paralelo sem race.
 *  2) Despacha pelo IFoodJobDispatcher.
 *  3) Classifica o resultado:
 *       - sucesso → status=done, completed_at=NOW()
 *       - IFoodRetryableException E attempts < max → status=retrying,
 *           available_at = NOW() + backoff exponencial (1m, 2m, 4m, 8m…)
 *       - IFoodRetryableException E attempts >= max → status=dead
 *       - qualquer outra Throwable → status=dead (permanente)
 *  4) Loga last_error.
 *
 * Operação:
 *  - `run($maxJobs, $sleepMs)` processa até N jobs e sai (recomendado para cron).
 *  - `loop($durationSec, $sleepMs)` roda por X segundos (recomendado para daemon).
 */
final class IFoodJobWorker
{
    private const JOB_TYPE_PREFIX = 'ifood.';
    private const BASE_RETRY_DELAY_SECONDS = 60; // 1 min para a primeira retentativa

    private PDO $db;
    private IFoodJobDispatcher $dispatcher;

    public function __construct(PDO $db, IFoodJobDispatcher $dispatcher)
    {
        $this->db = $db;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Processa até $maxJobs jobs e retorna. Bom para cron de 1 minuto.
     *
     * @return array{processed:int, succeeded:int, retried:int, dead:int}
     */
    public function run(int $maxJobs = 50, int $sleepBetweenMs = 100): array
    {
        $stats = ['processed' => 0, 'succeeded' => 0, 'retried' => 0, 'dead' => 0];

        for ($i = 0; $i < $maxJobs; $i++) {
            $job = $this->reserveNext();
            if ($job === null) {
                break; // nada disponível
            }

            $result = $this->processJob($job);
            $stats['processed']++;
            $stats[$result]++;

            if ($sleepBetweenMs > 0) {
                usleep($sleepBetweenMs * 1000);
            }
        }

        return $stats;
    }

    /**
     * Loop por X segundos (daemon mode). Útil para systemd / supervisor.
     */
    public function loop(int $durationSeconds = 60, int $idleSleepMs = 500): array
    {
        $stats = ['processed' => 0, 'succeeded' => 0, 'retried' => 0, 'dead' => 0];
        $deadline = time() + $durationSeconds;

        while (time() < $deadline) {
            $job = $this->reserveNext();
            if ($job === null) {
                usleep($idleSleepMs * 1000);
                continue;
            }

            $result = $this->processJob($job);
            $stats['processed']++;
            $stats[$result]++;
        }

        return $stats;
    }

    /**
     * Reserva atomicamente o próximo job disponível.
     *
     * @return array<string,mixed>|null
     */
    private function reserveNext(): ?array
    {
        try {
            $this->db->beginTransaction();

            $select = $this->db->prepare(
                "SELECT id, company_id, job_type, payload_json, attempts, max_attempts
                   FROM queue_jobs
                  WHERE status IN ('pending', 'retrying')
                    AND job_type LIKE :prefix
                    AND available_at <= NOW()
                  ORDER BY priority ASC, available_at ASC, id ASC
                  LIMIT 1
                  FOR UPDATE SKIP LOCKED"
            );
            $select->bindValue(':prefix', self::JOB_TYPE_PREFIX . '%', PDO::PARAM_STR);
            $select->execute();
            $row = $select->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $this->db->commit();
                return null;
            }

            // dedup_key é nullado na reserva: assim, novas mudanças que cheguem
            // durante o processamento podem agendar outro job (sem perda de updates).
            $update = $this->db->prepare(
                "UPDATE queue_jobs
                    SET status      = 'processing',
                        reserved_at = NOW(),
                        attempts    = attempts + 1,
                        dedup_key   = NULL
                  WHERE id = :id"
            );
            $update->execute([':id' => (int) $row['id']]);

            $this->db->commit();

            $row['attempts'] = ((int) $row['attempts']) + 1;
            return $row;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[IFoodJobWorker] reserveNext failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @return 'succeeded'|'retried'|'dead'
     */
    private function processJob(array $job): string
    {
        $jobId = (int) $job['id'];
        $payload = $this->decodePayload($job['payload_json'] ?? null);

        try {
            $this->dispatcher->dispatch([
                'id'           => $jobId,
                'company_id'   => isset($job['company_id']) ? (int) $job['company_id'] : null,
                'job_type'     => (string) $job['job_type'],
                'attempts'     => (int) $job['attempts'],
                'max_attempts' => (int) $job['max_attempts'],
            ], $payload);

            $this->markCompleted($jobId);
            return 'succeeded';
        } catch (IFoodRetryableException $e) {
            $attempts = (int) $job['attempts'];
            $max = (int) $job['max_attempts'];
            if ($attempts >= $max) {
                $this->markDead($jobId, 'Retryable exhausted: ' . $e->getMessage());
                return 'dead';
            }
            $this->scheduleRetry($jobId, $attempts, $e->getMessage());
            return 'retried';
        } catch (Throwable $e) {
            $this->markDead($jobId, 'Permanent: ' . $e->getMessage());
            return 'dead';
        }
    }

    private function markCompleted(int $jobId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE queue_jobs
                SET status       = 'done',
                    completed_at = NOW(),
                    last_error   = NULL
              WHERE id = :id"
        );
        $stmt->execute([':id' => $jobId]);
    }

    private function scheduleRetry(int $jobId, int $attempt, string $error): void
    {
        // Backoff exponencial em segundos: 60, 120, 240, 480, ...
        $delay = self::BASE_RETRY_DELAY_SECONDS * (1 << max(0, $attempt - 1));
        $stmt = $this->db->prepare(
            "UPDATE queue_jobs
                SET status       = 'retrying',
                    available_at = DATE_ADD(NOW(), INTERVAL :delay SECOND),
                    reserved_at  = NULL,
                    last_error   = :err
              WHERE id = :id"
        );
        $stmt->execute([
            ':delay' => $delay,
            ':err'   => $this->trimError($error),
            ':id'    => $jobId,
        ]);
    }

    private function markDead(int $jobId, string $error): void
    {
        $stmt = $this->db->prepare(
            "UPDATE queue_jobs
                SET status     = 'dead',
                    last_error = :err
              WHERE id = :id"
        );
        $stmt->execute([
            ':err' => $this->trimError($error),
            ':id'  => $jobId,
        ]);
    }

    private function decodePayload($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (!is_string($raw) || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function trimError(string $msg): string
    {
        return mb_substr($msg, 0, 1000, 'UTF-8');
    }
}
