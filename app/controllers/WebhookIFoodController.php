<?php
/**
 * WebhookIFoodController
 * 
 * Recebe webhooks do iFood (alternativa ao polling).
 * O iFood envia eventos via POST com JSON no mesmo formato do polling.
 * A URL deve ser configurada no portal do desenvolvedor iFood.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/IFoodService.php';

class WebhookIFoodController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = db();
    }

    /**
     * Endpoint principal: POST /webhook/ifood
     * 
     * O iFood envia um array de eventos ou um único evento.
     * Cada evento contém merchantId para identificar a empresa.
     */
    public function handle(): void
    {
        \App\Middleware\WebhookGate::requireInboundWebhookSecret('WEBHOOK_IFOOD_SECRET');

        $rawInput = file_get_contents('php://input');
        error_log('[Webhook iFood] Recebido bytes=' . strlen($rawInput));

        // Parse payload
        $payload = json_decode($rawInput, true);

        if ($payload === null) {
            error_log("[Webhook iFood] Payload inválido");
            $this->jsonResponse(['status' => 'error', 'message' => 'Invalid JSON'], 400);
            return;
        }

        // Normalizar: pode vir como array de eventos ou evento único
        $events = isset($payload['id']) ? [$payload] : ($payload ?? []);

        if (empty($events)) {
            $this->jsonResponse(['status' => 'ok', 'message' => 'No events']);
            return;
        }

        $processed = 0;
        $errors = [];

        foreach ($events as $event) {
            $fullCode = $event['fullCode'] ?? '';
            $eventId = $event['id'] ?? 'unknown';

            // KEEPALIVE events don't have merchantId — just acknowledge
            if ($fullCode === 'KEEPALIVE') {
                $processed++;
                continue;
            }

            $merchantId = $event['merchantId'] ?? '';

            if (empty($merchantId)) {
                error_log("[Webhook iFood] Evento sem merchantId: {$eventId}");
                $errors[] = ['event_id' => $eventId, 'error' => 'Missing merchantId'];
                continue;
            }

            // Buscar company_id pelo merchant_id
            $companyId = $this->findCompanyByMerchant($merchantId);

            if (!$companyId) {
                error_log("[Webhook iFood] Merchant não encontrado: {$merchantId}");
                $errors[] = ['event_id' => $eventId, 'error' => "Unknown merchant: {$merchantId}"];
                continue;
            }

            try {
                $service = new IFoodService($this->db, $companyId);
                $service->processWebhookEvent($event);
                $processed++;
            } catch (\Exception $e) {
                error_log("[Webhook iFood] Erro ao processar evento {$eventId}: " . $e->getMessage());
                $errors[] = ['event_id' => $eventId, 'error' => $e->getMessage()];
            }
        }

        error_log("[Webhook iFood] Processados: {$processed}, Erros: " . count($errors));

        // Sempre retornar 200 para o iFood não reenviar desnecessariamente
        $this->jsonResponse([
            'status' => 'ok',
            'processed' => $processed,
            'errors' => count($errors),
        ]);
    }

    /**
     * Busca company_id pela merchant_id do iFood
     */
    private function findCompanyByMerchant(string $merchantId): ?int
    {
        $stmt = $this->db->prepare("
            SELECT company_id 
            FROM ifood_integrations 
            WHERE merchant_id = :merchant_id 
              AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute(['merchant_id' => $merchantId]);
        $row = $stmt->fetch();

        return $row ? (int) $row['company_id'] : null;
    }

    /**
     * Resposta JSON
     */
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
