<?php

declare(strict_types=1);

namespace App\Services\IFood;

/**
 * Cliente HTTP central para todas as chamadas à API do iFood.
 *
 * Responsabilidades:
 *  - Executar requests via cURL com timeout sensato.
 *  - Tentar novamente em erros transitórios (5xx, 429, network) com backoff exponencial.
 *  - Respeitar `Retry-After` em respostas 429.
 *  - Logar cada tentativa em `ifood_api_logs` via IFoodApiLogger (request/response/latência).
 *  - Devolver IFoodResponse tipado (não nulo).
 *
 * Não faz:
 *  - Refresh de token (caller pode interpretar 401 e providenciar novo token).
 *  - Decidir quando autenticar (caller indica via passou ou não authToken).
 *
 * O isolamento permite que cada handler (StockSync, DriverRequest, FetchReviews, etc.)
 * use o client sem reimplementar boilerplate.
 */
final class IFoodClient
{
    /** HTTP statuses que serão tentados novamente. */
    private const RETRYABLE_STATUSES = [408, 425, 429, 500, 502, 503, 504];

    /** Tentativas totais máximas (incluindo a primeira). */
    private const DEFAULT_MAX_ATTEMPTS = 3;

    /** Delay base do backoff exponencial, em milissegundos. */
    private const BACKOFF_BASE_MS = 1000;

    /** Teto de espera quando Retry-After for absurdamente alto, em segundos. */
    private const MAX_RETRY_AFTER_SECONDS = 60;

    private int $companyId;
    private string $environment;
    private ?string $accessToken;
    private IFoodApiLogger $logger;
    private ?int $jobId;
    private int $maxAttempts;
    private int $timeoutSeconds;

    public function __construct(
        int $companyId,
        string $environment,
        ?string $accessToken,
        IFoodApiLogger $logger,
        ?int $jobId = null,
        int $maxAttempts = self::DEFAULT_MAX_ATTEMPTS,
        int $timeoutSeconds = 30
    ) {
        $this->companyId = $companyId;
        $this->environment = IFoodEndpoints::normalizeEnvironment($environment);
        $this->accessToken = $accessToken;
        $this->logger = $logger;
        $this->jobId = $jobId;
        $this->maxAttempts = max(1, $maxAttempts);
        $this->timeoutSeconds = max(5, $timeoutSeconds);
    }

    public function get(string $url, string $module, array $headers = []): IFoodResponse
    {
        return $this->execute('GET', $url, $module, null, $headers);
    }

    public function post(string $url, string $module, array|string|null $body, array $headers = []): IFoodResponse
    {
        return $this->execute('POST', $url, $module, $body, $headers);
    }

    public function put(string $url, string $module, array|string|null $body, array $headers = []): IFoodResponse
    {
        return $this->execute('PUT', $url, $module, $body, $headers);
    }

    public function patch(string $url, string $module, array|string|null $body, array $headers = []): IFoodResponse
    {
        return $this->execute('PATCH', $url, $module, $body, $headers);
    }

    public function delete(string $url, string $module, array $headers = []): IFoodResponse
    {
        return $this->execute('DELETE', $url, $module, null, $headers);
    }

    /**
     * Loop principal: dispara request, decide se faz retry, devolve a última resposta.
     */
    private function execute(string $method, string $url, string $module, array|string|null $body, array $extraHeaders): IFoodResponse
    {
        $attempt = 0;
        $last = null;

        while ($attempt < $this->maxAttempts) {
            $attempt++;
            $last = $this->dispatchOnce($method, $url, $module, $body, $extraHeaders, $attempt);

            if ($last->ok) {
                return $last;
            }

            // Só tenta de novo se for transitório.
            $isRetryable = $last->status === null
                || in_array($last->status, self::RETRYABLE_STATUSES, true);

            if (!$isRetryable || $attempt >= $this->maxAttempts) {
                break;
            }

            $waitMs = $this->computeBackoffMs($attempt, $last);
            usleep($waitMs * 1000);
        }

        return $last ?? IFoodResponse::failure(null, 'no attempt executed', null, null, 0, 0);
    }

    /**
     * Uma tentativa única. Sempre loga (mesmo falhando).
     */
    private function dispatchOnce(string $method, string $url, string $module, array|string|null $body, array $extraHeaders, int $attempt): IFoodResponse
    {
        $headers = $this->buildHeaders($body, $extraHeaders);
        $payload = $this->serializeBody($body, $headers);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true, // precisamos dos response headers (Retry-After)
            CURLOPT_TIMEOUT        => $this->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => min(10, $this->timeoutSeconds),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT      => 'MultiMenu-iFood/1.0',
        ]);

        if ($payload !== null && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $start = microtime(true);
        $raw = curl_exec($ch);
        $latencyMs = (int) round((microtime(true) - $start) * 1000);

        $errno = curl_errno($ch);
        $errstr = curl_error($ch);
        $status = (int) (curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        // Erro de rede (sem resposta HTTP)
        if ($raw === false || $errno !== 0) {
            $this->logger->log([
                'company_id'      => $this->companyId,
                'environment'     => $this->environment,
                'module'          => $module,
                'request_method'  => $method,
                'request_url'     => $url,
                'http_status'     => null,
                'latency_ms'      => $latencyMs,
                'attempt_number'  => $attempt,
                'request_body'    => $body,
                'response_body'   => null,
                'error_message'   => sprintf('cURL[%d]: %s', $errno, $errstr),
                'job_id'          => $this->jobId,
            ]);
            return IFoodResponse::failure(null, $errstr ?: 'network error', null, null, $latencyMs, $attempt);
        }

        $rawHeaders = (string) substr((string) $raw, 0, $headerSize);
        $rawBody = (string) substr((string) $raw, $headerSize);
        $headersMap = $this->parseHeaders($rawHeaders);

        $decoded = $this->decodeBody($rawBody, $headersMap['content-type'] ?? '');

        $statusOrNull = $status > 0 ? $status : null;
        $ok = $statusOrNull !== null && $status >= 200 && $status < 300;
        $err = $ok ? null : $this->extractError($status, $rawBody);

        $this->logger->log([
            'company_id'      => $this->companyId,
            'environment'     => $this->environment,
            'module'          => $module,
            'request_method'  => $method,
            'request_url'     => $url,
            'http_status'     => $statusOrNull,
            'latency_ms'      => $latencyMs,
            'attempt_number'  => $attempt,
            'request_body'    => $body,
            'response_body'   => $rawBody,
            'error_message'   => $err,
            'job_id'          => $this->jobId,
        ]);

        return $ok
            ? IFoodResponse::success($status, $decoded, $rawBody, $latencyMs, $attempt, $headersMap)
            : IFoodResponse::failure($statusOrNull, $err, $decoded, $rawBody, $latencyMs, $attempt, $headersMap);
    }

    private function buildHeaders(array|string|null $body, array $extra): array
    {
        $headers = ['Accept: application/json'];

        // Content-Type só faz sentido em requests com body.
        // O caller pode sobrescrever via $extra (ex: 'Content-Type: application/x-www-form-urlencoded').
        $hasExtraContentType = false;
        foreach ($extra as $h) {
            if (stripos($h, 'content-type:') === 0) {
                $hasExtraContentType = true;
                break;
            }
        }
        if ($body !== null && !$hasExtraContentType) {
            $headers[] = 'Content-Type: application/json';
        }

        if ($this->accessToken !== null && $this->accessToken !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }

        return array_merge($headers, $extra);
    }

    private function serializeBody(array|string|null $body, array $headers): ?string
    {
        if ($body === null) {
            return null;
        }
        if (is_string($body)) {
            return $body;
        }
        // Form-encoded se o caller pediu explicitamente
        foreach ($headers as $h) {
            if (stripos($h, 'content-type: application/x-www-form-urlencoded') === 0) {
                return http_build_query($body);
            }
        }
        $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json === false ? null : $json;
    }

    private function decodeBody(string $rawBody, string $contentType): mixed
    {
        if ($rawBody === '') {
            return null;
        }
        // Se vier JSON, decodifica. Caso contrário, devolve string crua.
        if (str_contains(strtolower($contentType), 'json')
            || (str_starts_with($rawBody, '{') || str_starts_with($rawBody, '['))) {
            $decoded = json_decode($rawBody, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : $rawBody;
        }
        return $rawBody;
    }

    /**
     * @return array<string,string> headers normalizados em lowercase => value
     */
    private function parseHeaders(string $raw): array
    {
        $out = [];
        foreach (preg_split("/\r?\n/", trim($raw)) as $line) {
            if ($line === '' || !str_contains($line, ':')) {
                continue;
            }
            [$k, $v] = explode(':', $line, 2);
            $out[strtolower(trim($k))] = trim($v);
        }
        return $out;
    }

    private function extractError(int $status, string $rawBody): string
    {
        $msg = sprintf('HTTP %d', $status);
        $decoded = json_decode($rawBody, true);
        if (is_array($decoded)) {
            $detail = $decoded['error']['message']
                ?? $decoded['message']
                ?? $decoded['error_description']
                ?? null;
            if (is_string($detail) && $detail !== '') {
                $msg .= ': ' . $detail;
            }
        } elseif ($rawBody !== '') {
            $msg .= ': ' . substr($rawBody, 0, 200);
        }
        return $msg;
    }

    /**
     * Calcula quanto esperar antes da próxima tentativa.
     *  - Se a resposta tiver Retry-After, respeita (até o teto de 60s).
     *  - Senão, exponencial: 1s, 2s, 4s ...
     */
    private function computeBackoffMs(int $attempt, IFoodResponse $last): int
    {
        $retryAfter = $last->responseHeaders['retry-after'] ?? null;
        if ($retryAfter !== null) {
            $seconds = $this->parseRetryAfter($retryAfter);
            if ($seconds !== null) {
                return min($seconds, self::MAX_RETRY_AFTER_SECONDS) * 1000;
            }
        }
        // Backoff exponencial com jitter pequeno (±10%)
        $base = self::BACKOFF_BASE_MS * (1 << ($attempt - 1));
        $jitter = (int) ($base * 0.1 * (mt_rand(-100, 100) / 100));
        return max(100, $base + $jitter);
    }

    private function parseRetryAfter(string $value): ?int
    {
        if (ctype_digit(trim($value))) {
            return (int) $value;
        }
        $ts = strtotime($value);
        if ($ts === false) {
            return null;
        }
        return max(0, $ts - time());
    }
}
