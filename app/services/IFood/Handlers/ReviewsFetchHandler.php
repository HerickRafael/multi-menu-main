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
 * Job: `ifood.reviews.fetch`
 *
 * Payload:
 *   merchant_id  string   (obrigatório) merchant a consultar
 *   environment  string   (opcional, default 'production')
 *   page_size    int      (opcional, default 50; máximo 50 do iFood)
 *   max_pages    int      (opcional, default 100; safety cap)
 *
 * Fluxo:
 *   1. Resolve token via IFoodService legado.
 *   2. Lê páginas sequencialmente até a API dizer que acabou ou hit do max_pages.
 *   3. Upsert idempotente em `ifood_reviews` (ON DUPLICATE KEY UPDATE).
 *   4. Em qualquer falha transitória do client → IFoodRetryableException; o
 *      worker reagenda e como o upsert é idempotente as páginas já gravadas
 *      não vão duplicar.
 */
final class ReviewsFetchHandler implements IFoodJobHandler
{
    private const DEFAULT_PAGE_SIZE = 50;
    private const DEFAULT_MAX_PAGES = 100;

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
            throw new RuntimeException('ReviewsFetchHandler: company_id ausente');
        }

        $env = IFoodEndpoints::normalizeEnvironment((string) ($payload['environment'] ?? 'production'));
        $merchantId = trim((string) ($payload['merchant_id'] ?? ''));
        if ($merchantId === '') {
            throw new RuntimeException('ReviewsFetchHandler: merchant_id ausente');
        }

        $pageSize = max(1, min(self::DEFAULT_PAGE_SIZE, (int) ($payload['page_size'] ?? self::DEFAULT_PAGE_SIZE)));
        $maxPages = max(1, (int) ($payload['max_pages'] ?? self::DEFAULT_MAX_PAGES));

        $token = $this->resolveToken($companyId);
        if ($token === null || $token === '') {
            // sem token → o caller precisa configurar credenciais primeiro
            throw new RuntimeException('ReviewsFetchHandler: sem access_token (credenciais não configuradas)');
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

        $base = IFoodEndpoints::reviews($env, $merchantId);
        $page = 0;
        $totalUpserted = 0;

        while ($page < $maxPages) {
            $url = $base . '?' . http_build_query(['page' => $page, 'pageSize' => $pageSize]);
            $response = $client->get($url, IFoodEndpoints::MODULE_REVIEW);

            if (!$response->ok) {
                // Transitório? Reagenda. Permanente? Mata.
                if ($response->status === null || $response->status >= 500 || $response->status === 429) {
                    throw new IFoodRetryableException($response->error ?? 'transient fetch error');
                }
                throw new RuntimeException($response->error ?? 'permanent fetch error');
            }

            $reviews = $this->extractReviews($response->body);
            if (empty($reviews)) {
                break; // fim natural
            }

            foreach ($reviews as $review) {
                $this->upsert($companyId, $env, $merchantId, $review);
                $totalUpserted++;
            }

            // Se o iFood devolveu menos do que pedimos, assumimos fim da lista.
            if (count($reviews) < $pageSize) {
                break;
            }

            $page++;
        }

        error_log(sprintf(
            '[ReviewsFetchHandler] company=%d env=%s merchant=%s pages=%d upserted=%d',
            $companyId,
            $env,
            $merchantId,
            $page + 1,
            $totalUpserted
        ));
    }

    /**
     * O iFood costuma retornar `{ reviews: [...] }`, mas alguns endpoints devolvem
     * um array direto. Toleramos ambos.
     *
     * @return array<int,array<string,mixed>>
     */
    private function extractReviews(mixed $body): array
    {
        if (is_array($body)) {
            if (isset($body['reviews']) && is_array($body['reviews'])) {
                return $body['reviews'];
            }
            // Se vier array sequencial direto
            if (array_is_list($body)) {
                return $body;
            }
        }
        return [];
    }

    /**
     * Mapeia o payload do iFood para colunas locais e faz INSERT ON DUPLICATE KEY UPDATE.
     * Idempotente: rodar duas vezes a mesma review apenas atualiza fetched_at + raw_data.
     */
    private function upsert(int $companyId, string $env, string $merchantId, array $review): void
    {
        $reviewId = trim((string) ($review['id'] ?? ''));
        if ($reviewId === '') {
            return; // sem id externo não tem como deduplicar
        }

        $rating = (int) ($review['score'] ?? $review['rating'] ?? 0);
        $rating = max(1, min(5, $rating));

        $comment = isset($review['comment']) ? (string) $review['comment'] : null;
        if ($comment !== null && trim($comment) === '') {
            $comment = null;
        }

        $customerName = null;
        if (isset($review['customer']['name'])) {
            $customerName = (string) $review['customer']['name'];
        } elseif (isset($review['customerName'])) {
            $customerName = (string) $review['customerName'];
        }

        $moderation = isset($review['moderationStatus']) ? (string) $review['moderationStatus'] : null;

        $orderId = null;
        $orderDisplayId = null;
        if (isset($review['order']['id'])) {
            $orderId = (string) $review['order']['id'];
        } elseif (isset($review['orderId'])) {
            $orderId = (string) $review['orderId'];
        }
        if (isset($review['order']['displayId'])) {
            $orderDisplayId = (string) $review['order']['displayId'];
        } elseif (isset($review['orderDisplayId'])) {
            $orderDisplayId = (string) $review['orderDisplayId'];
        }

        $reviewDate = $this->parseDate($review['createdAt'] ?? $review['publishedAt'] ?? null);

        $raw = json_encode($review, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $sql = "INSERT INTO ifood_reviews
            (company_id, environment, ifood_review_id, merchant_id, ifood_order_id,
             order_display_id, rating, comment, customer_name, moderation_status,
             review_date, raw_data, fetched_at)
            VALUES
            (:cid, :env, :rid, :mid, :oid, :odid, :rating, :comment, :cname,
             :mod, :rdate, :raw, NOW())
            ON DUPLICATE KEY UPDATE
              rating            = VALUES(rating),
              comment           = VALUES(comment),
              customer_name     = VALUES(customer_name),
              moderation_status = VALUES(moderation_status),
              review_date       = VALUES(review_date),
              raw_data          = VALUES(raw_data),
              fetched_at        = NOW()";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cid'    => $companyId,
            ':env'    => $env,
            ':rid'    => $reviewId,
            ':mid'    => $merchantId,
            ':oid'    => $orderId,
            ':odid'   => $orderDisplayId,
            ':rating' => $rating,
            ':comment'=> $comment,
            ':cname'  => $customerName,
            ':mod'    => $moderation,
            ':rdate'  => $reviewDate,
            ':raw'    => $raw,
        ]);
    }

    private function parseDate(?string $iso): ?string
    {
        if (!$iso || trim($iso) === '') {
            return null;
        }
        $ts = strtotime($iso);
        return $ts === false ? null : date('Y-m-d H:i:s', $ts);
    }

    /**
     * Reusa a infra de token do IFoodService legado: já lida com refresh, encrypt,
     * cache em memória e DB. Quando essa lógica for migrada para o novo módulo,
     * troca-se aqui.
     */
    private function resolveToken(int $companyId): ?string
    {
        if (!class_exists('IFoodService', false)) {
            require_once __DIR__ . '/../../IFoodService.php';
        }
        $service = new IFoodService($this->db, $companyId);
        return $service->getAccessToken();
    }
}
