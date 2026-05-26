<?php

declare(strict_types=1);

namespace App\Services\IFood;

use PDO;
use Throwable;

/**
 * Grava trilhas de auditoria de cada chamada à API do iFood em `ifood_api_logs`.
 *
 * Princípios:
 * - Sanitiza tokens Bearer, client_secret e PII básico do payload antes de gravar.
 * - Trunca corpos grandes (>64KB) para não inflar o banco.
 * - Falha sempre silenciosa: log nunca interrompe a chamada de API.
 * - Cada linha = 1 tentativa (com retry, gera N linhas para o mesmo job_id).
 */
final class IFoodApiLogger
{
    private const MAX_BODY_BYTES = 65536; // 64KB
    private const PII_KEYS = ['client_secret', 'access_token', 'refresh_token', 'password',
                              'cpf', 'document', 'phone', 'customerPhone', 'whatsapp'];

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * @param array{
     *   company_id:int,
     *   environment:string,
     *   module:string,
     *   request_method:string,
     *   request_url:string,
     *   http_status?:?int,
     *   latency_ms?:?int,
     *   attempt_number?:int,
     *   request_body?:string|array|null,
     *   response_body?:string|null,
     *   error_message?:string|null,
     *   job_id?:?int,
     * } $entry
     */
    public function log(array $entry): void
    {
        try {
            $sql = 'INSERT INTO ifood_api_logs
                (company_id, environment, module, request_method, request_url,
                 http_status, latency_ms, attempt_number,
                 request_body, response_body, error_message, job_id)
                VALUES
                (:company_id, :environment, :module, :request_method, :request_url,
                 :http_status, :latency_ms, :attempt_number,
                 :request_body, :response_body, :error_message, :job_id)';

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':company_id'      => (int)$entry['company_id'],
                ':environment'     => IFoodEndpoints::normalizeEnvironment($entry['environment'] ?? null),
                ':module'          => (string)($entry['module'] ?? 'unknown'),
                ':request_method'  => strtoupper((string)$entry['request_method']),
                ':request_url'     => $this->truncate((string)$entry['request_url'], 500),
                ':http_status'     => isset($entry['http_status']) ? (int)$entry['http_status'] : null,
                ':latency_ms'      => isset($entry['latency_ms']) ? (int)$entry['latency_ms'] : null,
                ':attempt_number'  => (int)($entry['attempt_number'] ?? 1),
                ':request_body'    => $this->encodeBody($entry['request_body'] ?? null),
                ':response_body'   => $this->encodeBody($entry['response_body'] ?? null),
                ':error_message'   => isset($entry['error_message'])
                    ? $this->truncate((string)$entry['error_message'], 500)
                    : null,
                ':job_id'          => isset($entry['job_id']) ? (int)$entry['job_id'] : null,
            ]);
        } catch (Throwable $e) {
            // Logger NUNCA pode propagar erro. Cai para error_log nativo.
            error_log('[IFoodApiLogger] log() failed: ' . $e->getMessage());
        }
    }

    /**
     * Limpa logs antigos. Retenção padrão: 30 dias.
     * Use em cron para evitar crescimento sem limite.
     */
    public function purgeOlderThan(int $days = 30): int
    {
        try {
            $stmt = $this->db->prepare(
                'DELETE FROM ifood_api_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL :d DAY)'
            );
            $stmt->bindValue(':d', $days, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount();
        } catch (Throwable $e) {
            error_log('[IFoodApiLogger] purge failed: ' . $e->getMessage());
            return 0;
        }
    }

    private function encodeBody($body): ?string
    {
        if ($body === null) {
            return null;
        }
        if (is_string($body)) {
            $body = $this->sanitizeStringBody($body);
            return $this->truncate($body, self::MAX_BODY_BYTES);
        }
        if (is_array($body)) {
            $body = $this->sanitizeArray($body);
            $encoded = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $encoded === false ? null : $this->truncate($encoded, self::MAX_BODY_BYTES);
        }
        return $this->truncate((string)$body, self::MAX_BODY_BYTES);
    }

    /**
     * Tenta detectar JSON em strings; se for, decodifica + sanitiza + reserializa.
     * Caso contrário, faz substituição direta de tokens Bearer e padrões comuns.
     */
    private function sanitizeStringBody(string $body): string
    {
        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            $sanitized = $this->sanitizeArray($decoded);
            $reencoded = json_encode($sanitized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($reencoded !== false) {
                return $reencoded;
            }
        }
        // Não-JSON: faz scrubbing por regex
        $body = preg_replace('/(Bearer\s+)[A-Za-z0-9\._\-]+/i', '$1[REDACTED]', $body) ?? $body;
        $body = preg_replace('/(client_secret=)[^&\s]+/i', '$1[REDACTED]', $body) ?? $body;
        $body = preg_replace('/(access_token["\']?\s*[:=]\s*["\']?)[A-Za-z0-9\._\-]+/', '$1[REDACTED]', $body) ?? $body;
        return $body;
    }

    private function sanitizeArray(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            $keyLower = is_string($k) ? strtolower($k) : '';
            if (in_array($keyLower, array_map('strtolower', self::PII_KEYS), true)) {
                $out[$k] = '[REDACTED]';
                continue;
            }
            if (is_array($v)) {
                $out[$k] = $this->sanitizeArray($v);
                continue;
            }
            $out[$k] = $v;
        }
        return $out;
    }

    private function truncate(string $s, int $maxBytes): string
    {
        if (strlen($s) <= $maxBytes) {
            return $s;
        }
        return substr($s, 0, $maxBytes - 20) . '... [TRUNCATED]';
    }
}
