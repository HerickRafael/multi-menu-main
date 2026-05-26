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
 * Job: `ifood.shipping.create`
 *
 * Cria um pedido de logística no iFood a partir de uma row de
 * `ifood_shipping_orders` em status PENDING.
 *
 * Payload:
 *   external_reference  string (obrigatório) — chave de idempotência local
 *   environment         string (opcional)
 *
 * O handler NÃO recebe o body do request iFood — ele lê o `request_payload`
 * da tabela, garantindo que retries sempre usam o mesmo corpo (idempotência
 * sólida + auditabilidade).
 *
 * Idempotência em camadas:
 *   1) UNIQUE KEY (company, env, external_reference) — impossível duplicar localmente
 *   2) Antes de POST, verifica se `ifood_shipping_id` já está populado
 *      (significa que já houve sucesso parcial em retry anterior)
 *   3) Passa o external_reference no body do request para o iFood deduplicar
 *      (se o iFood aceitar este campo; senão é só metadado para o response)
 *
 * Erros:
 *   2xx                    → SUBMITTED + ifood_shipping_id + agenda primeira poll
 *   400/422 (validação)    → REJECTED + dead (não retry)
 *   409 (já existe)        → tenta GET no iFood pra resolver o id; SUBMITTED ou REJECTED
 *   401/429/5xx/rede       → IFoodRetryableException
 *   Outros 4xx             → FAILED + dead
 */
final class ShippingOrderCreateHandler implements IFoodJobHandler
{
    /** Polling intervalo inicial após submissão bem-sucedida. */
    private const FIRST_POLL_DELAY_SECONDS = 30;

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
            throw new RuntimeException('ShippingOrderCreateHandler: company_id ausente');
        }

        $externalRef = trim((string) ($payload['external_reference'] ?? ''));
        if ($externalRef === '') {
            throw new RuntimeException('ShippingOrderCreateHandler: external_reference ausente');
        }

        $integration = $this->loadIntegration($companyId);
        if ($integration === null) {
            throw new RuntimeException('ShippingOrderCreateHandler: integração inativa');
        }

        $env = IFoodEndpoints::normalizeEnvironment(
            (string) ($payload['environment'] ?? $integration['environment'] ?? 'production')
        );

        $row = $this->loadRow($companyId, $env, $externalRef);
        if ($row === null) {
            throw new RuntimeException(sprintf(
                'ShippingOrderCreateHandler: row %s não encontrada para company %d env %s',
                $externalRef,
                $companyId,
                $env
            ));
        }

        // Já foi submetido com sucesso em retry anterior → no-op idempotente.
        if (!empty($row['ifood_shipping_id'])
            && in_array((string) $row['status'], ['SUBMITTED', 'ACCEPTED', 'CONFIRMED', 'PICKED_UP', 'DELIVERED'], true)
        ) {
            error_log(sprintf(
                '[ShippingOrderCreate] skip company=%d ext=%s ifood_id=%s status=%s (já submetido)',
                $companyId,
                $externalRef,
                (string) $row['ifood_shipping_id'],
                (string) $row['status']
            ));
            return;
        }

        // Estados terminais negativos → não re-submete.
        if (in_array((string) $row['status'], ['CANCELLED', 'REJECTED', 'FAILED'], true)) {
            error_log(sprintf(
                '[ShippingOrderCreate] skip company=%d ext=%s status terminal (%s)',
                $companyId,
                $externalRef,
                (string) $row['status']
            ));
            return;
        }

        $requestBody = json_decode((string) $row['request_payload'], true);
        if (!is_array($requestBody)) {
            $this->markFailed($row['id'], 'invalid_request_payload', null);
            throw new RuntimeException('ShippingOrderCreateHandler: request_payload inválido');
        }

        // Garante que o external_reference vai no body (o iFood pode usar
        // como deduplication key se suportar — sem custo se ignorar).
        $requestBody['externalReference'] = $requestBody['externalReference'] ?? $externalRef;

        $token = $this->resolveToken($companyId);
        if ($token === null || $token === '') {
            throw new IFoodRetryableException('ShippingOrderCreateHandler: sem access_token');
        }

        $client = new IFoodClient(
            companyId: $companyId,
            environment: $env,
            accessToken: $token,
            logger: $this->logger,
            jobId: isset($job['id']) ? (int) $job['id'] : null,
            maxAttempts: 3,
            timeoutSeconds: 30
        );

        $response = $client->post(
            IFoodEndpoints::shippingOrders($env),
            IFoodEndpoints::MODULE_SHIPPING,
            $requestBody
        );

        $rawJson = $response->body !== null
            ? json_encode($response->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : null;

        if ($response->ok) {
            $ifoodId = $this->extractShippingId($response->body);
            $this->markSubmitted((int) $row['id'], $ifoodId, (string) $rawJson, (int) $response->status, self::FIRST_POLL_DELAY_SECONDS);
            error_log(sprintf(
                '[ShippingOrderCreate] ok company=%d ext=%s ifood_id=%s http=%d',
                $companyId,
                $externalRef,
                (string) $ifoodId,
                (int) $response->status
            ));
            return;
        }

        $errMsg = (string) ($response->error ?? 'erro desconhecido');
        $status = $response->status;

        // 409 → já existe no iFood (provável retry após sucesso parcial onde
        // a resposta nunca chegou). Tentamos extrair o id e marcar SUBMITTED.
        if ($status === 409) {
            $ifoodId = $this->extractShippingId($response->body);
            if ($ifoodId !== null && $ifoodId !== '') {
                $this->markSubmitted((int) $row['id'], $ifoodId, (string) $rawJson, $status, self::FIRST_POLL_DELAY_SECONDS);
                error_log("[ShippingOrderCreate] 409 idempotente company={$companyId} ext={$externalRef} ifood_id={$ifoodId}");
                return;
            }
            // 409 sem id retornado → trata como rejeição.
            $this->markRejected((int) $row['id'], $errMsg, (string) $rawJson, $status);
            throw new RuntimeException("ShippingOrderCreate: 409 sem id retornado ({$errMsg})");
        }

        // 400/422 → validação/regra de negócio. Permanente.
        if ($status === 400 || $status === 422) {
            $this->markRejected((int) $row['id'], $errMsg, (string) $rawJson, $status);
            throw new RuntimeException("ShippingOrderCreate: {$status} rejected ({$errMsg})");
        }

        // 401 → token expirou.
        if ($status === 401) {
            $this->touchError((int) $row['id'], $errMsg, $status);
            throw new IFoodRetryableException("ShippingOrderCreate: 401 ({$errMsg})");
        }

        // 429/5xx/rede → retryable.
        if ($status === null || $status === 429 || $status >= 500) {
            $this->touchError((int) $row['id'], $errMsg, $status);
            throw new IFoodRetryableException($errMsg);
        }

        // Outros 4xx → falha permanente.
        $this->markFailed((int) $row['id'], $errMsg, $status);
        throw new RuntimeException("ShippingOrderCreate: {$status} ({$errMsg})");
    }

    /**
     * @param array<string,mixed>|string|null $body
     */
    private function extractShippingId(mixed $body): ?string
    {
        if (!is_array($body)) {
            return null;
        }
        // Tenta vários nomes possíveis (iFood varia entre versões/endpoints).
        $candidates = ['id', 'orderId', 'shippingId', 'externalId'];
        foreach ($candidates as $key) {
            if (isset($body[$key]) && is_scalar($body[$key])) {
                $v = trim((string) $body[$key]);
                if ($v !== '') {
                    return $v;
                }
            }
        }
        return null;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function loadIntegration(int $companyId): ?array
    {
        $stmt = $this->db->prepare(
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
    private function loadRow(int $companyId, string $env, string $externalRef): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, status, ifood_shipping_id, request_payload
               FROM ifood_shipping_orders
              WHERE company_id = :cid AND environment = :env AND external_reference = :ref
              LIMIT 1'
        );
        $stmt->execute([':cid' => $companyId, ':env' => $env, ':ref' => $externalRef]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function markSubmitted(int $rowId, ?string $ifoodId, string $responsePayload, int $httpStatus, int $firstPollSeconds): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ifood_shipping_orders SET
                status               = \'SUBMITTED\',
                ifood_shipping_id    = COALESCE(:ifid, ifood_shipping_id),
                response_payload     = :resp,
                last_response_status = :hs,
                last_error           = NULL,
                submitted_at         = COALESCE(submitted_at, NOW()),
                next_poll_at         = DATE_ADD(NOW(), INTERVAL :poll SECOND)
              WHERE id = :id'
        );
        $stmt->execute([
            ':ifid' => $ifoodId,
            ':resp' => $responsePayload === '' ? null : $responsePayload,
            ':hs'   => $httpStatus,
            ':poll' => $firstPollSeconds,
            ':id'   => $rowId,
        ]);
    }

    private function markRejected(int $rowId, string $error, string $responsePayload, ?int $httpStatus): void
    {
        $this->writeFinalStatus($rowId, 'REJECTED', $error, $responsePayload, $httpStatus);
    }

    private function markFailed(int $rowId, string $error, ?int $httpStatus): void
    {
        $this->writeFinalStatus($rowId, 'FAILED', $error, null, $httpStatus);
    }

    private function writeFinalStatus(int $rowId, string $status, string $error, ?string $responsePayload, ?int $httpStatus): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ifood_shipping_orders SET
                status               = :st,
                last_error           = :err,
                response_payload     = COALESCE(:resp, response_payload),
                last_response_status = :hs,
                next_poll_at         = NULL,
                retries              = retries + 1
              WHERE id = :id'
        );
        $stmt->execute([
            ':st'   => $status,
            ':err'  => mb_substr($error, 0, 500, 'UTF-8'),
            ':resp' => $responsePayload,
            ':hs'   => $httpStatus,
            ':id'   => $rowId,
        ]);
    }

    private function touchError(int $rowId, string $error, ?int $httpStatus): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ifood_shipping_orders SET
                last_error           = :err,
                last_response_status = :hs,
                retries              = retries + 1
              WHERE id = :id'
        );
        $stmt->execute([
            ':err' => mb_substr($error, 0, 500, 'UTF-8'),
            ':hs'  => $httpStatus,
            ':id'  => $rowId,
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
