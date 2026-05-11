<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../services/WebPushService.php';

/**
 * Controller para API de Web Push Notifications
 */
class AdminPushController extends Controller
{
    private ?\App\Services\WebPushService $pushService = null;

    private function getPushService(): \App\Services\WebPushService
    {
        if ($this->pushService === null) {
            $this->pushService = new \App\Services\WebPushService(db());
        }
        return $this->pushService;
    }

    /**
     * Guard que valida sessão e retorna empresa pelo slug
     */
    private function guard(string $slug): array
    {
        Auth::start();
        $user = Auth::user();

        if (!$user) {
            $this->jsonResponse(['error' => 'Não autenticado'], 401);
            exit;
        }

        $company = Company::findBySlug($slug);

        if (!$company) {
            $this->jsonResponse(['error' => 'Empresa não encontrada'], 404);
            exit;
        }

        if ($user['role'] !== 'root' && (int)$user['company_id'] !== (int)$company['id']) {
            $this->jsonResponse(['error' => 'Acesso negado'], 403);
            exit;
        }

        return [$user, $company];
    }

    /**
     * Guard para mobile - usa MOBILE_SLUG se slug não for passado
     */
    private function guardMobile(?string $slug = null): array
    {
        // Se não há slug, tentar pegar do contexto mobile
        if (empty($slug)) {
            $slug = $_SERVER['MOBILE_SLUG'] ?? null;
        }
        
        if (empty($slug)) {
            $this->jsonResponse(['error' => 'Slug da empresa não encontrado'], 400);
            exit;
        }
        
        return $this->guard($slug);
    }

    /**
     * GET /admin/{slug}/push/vapid-key
     * Retorna a chave pública VAPID para o frontend
     */
    public function getVapidKey($params): void
    {
        // Esta rota não requer autenticação pois é usada antes do subscribe
        $publicKey = $this->getPushService()->getPublicKey();
        $this->jsonResponse([
            'success' => !empty($publicKey),
            'vapidPublicKey' => $publicKey,
            'publicKey' => $publicKey // compatibilidade
        ]);
    }

    /**
     * POST /admin/{slug}/push/subscribe ou /push/subscribe (mobile)
     * Registra uma nova subscription
     */
    public function subscribe($params): void
    {
        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guardMobile($slug);

        $input = $this->getJsonInput();
        
        if (empty($input['subscription'])) {
            $this->jsonResponse(['error' => 'Subscription é obrigatória'], 400);
            return;
        }

        $userId = (int)$user['id'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $result = $this->getPushService()->subscribe(
            (int) $company['id'],
            $input['subscription'],
            $userId,
            $userAgent
        );

        $this->jsonResponse($result, $result['success'] ? 200 : 400);
    }

    /**
     * POST /admin/{slug}/push/unsubscribe ou /push/unsubscribe (mobile)
     * Remove uma subscription
     */
    public function unsubscribe($params): void
    {
        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guardMobile($slug);

        $input = $this->getJsonInput();
        
        if (empty($input['endpoint'])) {
            $this->jsonResponse(['error' => 'Endpoint é obrigatório'], 400);
            return;
        }

        $result = $this->getPushService()->unsubscribe(
            (int) $company['id'],
            $input['endpoint']
        );

        $this->jsonResponse($result);
    }

    /**
     * POST /admin/{slug}/push/test ou /push/test (mobile)
     * Envia uma notificação de teste
     */
    public function test($params): void
    {
        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guardMobile($slug);

        // Simular um pedido de teste
        $testOrder = [
            'id' => 'TESTE',
            'customer_name' => 'Cliente Teste',
            'total' => 99.90
        ];

        $result = $this->getPushService()->notifyNewOrder((int) $company['id'], $testOrder);

        $this->jsonResponse([
            'success' => $result['sent'] > 0,
            'message' => "Enviado para {$result['sent']} de {$result['total']} dispositivos",
            'details' => $result
        ]);
    }

    /**
     * GET /admin/{slug}/push/status ou /push/status (mobile)
     * Retorna status das subscriptions
     */
    public function status($params): void
    {
        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guardMobile($slug);

        $model = new \App\Models\PushSubscription(db());
        $subscriptions = $model->getAllForCompany((int) $company['id']);
        $activeCount = $model->countActive((int) $company['id']);

        $this->jsonResponse([
            'activeCount' => $activeCount,
            'subscriptions' => $subscriptions,
            'vapidConfigured' => !empty($this->getPushService()->getPublicKey())
        ]);
    }

    /**
     * Obtém input JSON
     */
    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: [];
    }

    /**
     * Envia resposta JSON
     */
    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
