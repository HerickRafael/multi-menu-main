<?php

declare(strict_types=1);

namespace App\Services;

require_once __DIR__ . '/../models/PushSubscription.php';

use App\Models\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use PDO;

/**
 * Service para envio de Web Push Notifications usando minishlink/web-push
 * 
 * Implementa o protocolo Web Push com VAPID de forma confiável
 */
class WebPushService
{
    private PDO $db;
    private PushSubscription $subscriptionModel;
    private string $publicKey;
    private string $privateKey;
    private string $subject;
    private ?WebPush $webPush = null;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
        $this->subscriptionModel = new PushSubscription($this->db);
        
        $this->publicKey = config('vapid_public_key') ?? '';
        $this->privateKey = config('vapid_private_key') ?? '';
        $this->subject = config('vapid_subject') ?? 'mailto:admin@example.com';
    }

    /**
     * Inicializa o cliente WebPush
     */
    private function getWebPush(): WebPush
    {
        if ($this->webPush === null) {
            $auth = [
                'VAPID' => [
                    'subject' => $this->subject,
                    'publicKey' => $this->publicKey,
                    'privateKey' => $this->privateKey,
                ],
            ];

            $this->webPush = new WebPush($auth, [
                'TTL' => 86400, // 24 horas
                'urgency' => 'high',
                // Não definir topic para compatibilidade com iOS/Safari
            ]);
            
            // Limitar requisições simultâneas
            $this->webPush->setAutomaticPadding(false);
        }

        return $this->webPush;
    }

    /**
     * Obtém a chave pública VAPID para o frontend
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Salva uma nova subscription
     */
    public function subscribe(int $companyId, array $subscription, ?int $userId = null, ?string $userAgent = null): array
    {
        try {
            $id = $this->subscriptionModel->saveSubscription($companyId, $subscription, $userId, $userAgent);
            return [
                'success' => true,
                'id' => $id,
                'message' => 'Notificações ativadas com sucesso'
            ];
        } catch (\Exception $e) {
            error_log("WebPushService::subscribe error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Remove uma subscription
     */
    public function unsubscribe(int $companyId, string $endpoint): array
    {
        try {
            $this->subscriptionModel->removeSubscription($companyId, $endpoint);
            return ['success' => true];
        } catch (\Exception $e) {
            error_log("WebPushService::unsubscribe error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Envia notificação de novo pedido para todos os dispositivos da empresa
     */
    public function notifyNewOrder(int $companyId, array $order, ?string $slug = null): array
    {
        $subscriptions = $this->subscriptionModel->getActiveSubscriptions($companyId);
        
        if (empty($subscriptions)) {
            return ['sent' => 0, 'failed' => 0, 'message' => 'Nenhuma subscription ativa'];
        }

        // Buscar slug da empresa se não fornecido
        if (empty($slug)) {
            $stmt = $this->db->prepare('SELECT slug FROM companies WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $companyId]);
            $company = $stmt->fetch(\PDO::FETCH_ASSOC);
            $slug = $company['slug'] ?? '';
        }

        $total = number_format((float)($order['total'] ?? $order['subtotal'] ?? 0), 2, ',', '.');
        $customerName = $order['customer_name'] ?? 'Cliente';
        $orderNumber = $order['order_number'] ?? $order['id'] ?? 0;
        $orderId = $order['id'] ?? 0;

        // Payload compatível com iOS Safari (campos simplificados)
        // iOS não suporta: vibrate, actions no payload (são ignorados)
        // URLs: mobile usa /orders/show, desktop usa /admin/{slug}/orders/show
        $payload = json_encode([
            'title' => "Novo Pedido #{$orderNumber}",
            'body' => "{$customerName} - R$ {$total}",
            'icon' => '/assets/icons/admin/icon-192x192.png',
            'badge' => '/assets/icons/admin/badge-72x72.png',
            'tag' => 'order-' . $orderId,
            'data' => [
                'type' => 'new_order',
                'orderId' => $orderId,
                'orderNumber' => $orderNumber,
                'slug' => $slug,
                'url' => "/orders/show?id={$orderId}",
                'desktopUrl' => "/admin/{$slug}/orders/show?id={$orderId}"
            ]
        ], JSON_UNESCAPED_UNICODE);

        return $this->sendToSubscriptions($subscriptions, $payload);
    }

    /**
     * Envia notificação personalizada
     */
    public function sendNotification(int $companyId, string $title, string $body, array $data = []): array
    {
        $subscriptions = $this->subscriptionModel->getActiveSubscriptions($companyId);
        
        if (empty($subscriptions)) {
            return ['sent' => 0, 'failed' => 0, 'message' => 'Nenhuma subscription ativa'];
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => '/assets/icons/admin/icon-192x192.png',
            'badge' => '/assets/icons/admin/badge-72x72.png',
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);

        return $this->sendToSubscriptions($subscriptions, $payload);
    }

    /**
     * Envia notificação de teste
     */
    public function sendTestNotification(int $companyId): array
    {
        return $this->sendNotification(
            $companyId,
            '🔔 Teste de Notificação',
            'Push notifications estão funcionando corretamente!',
            ['type' => 'test', 'timestamp' => time()]
        );
    }

    /**
     * Envia para múltiplas subscriptions
     */
    private function sendToSubscriptions(array $subscriptions, string $payload): array
    {
        $webPush = $this->getWebPush();
        $sent = 0;
        $failed = 0;

        // Enfileirar todas as notificações
        foreach ($subscriptions as $sub) {
            try {
                $subscription = Subscription::create([
                    'endpoint' => $sub['endpoint'],
                    'publicKey' => $sub['p256dh_key'],
                    'authToken' => $sub['auth_key'],
                ]);

                $webPush->queueNotification($subscription, $payload);
            } catch (\Exception $e) {
                error_log("WebPush queue error for sub {$sub['id']}: " . $e->getMessage());
                $failed++;
            }
        }

        // Processar fila e coletar resultados
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();
            
            // Encontrar subscription por endpoint
            $subId = null;
            foreach ($subscriptions as $sub) {
                if ($sub['endpoint'] === $endpoint) {
                    $subId = (int)$sub['id'];
                    break;
                }
            }

            if ($report->isSuccess()) {
                $sent++;
                if ($subId) {
                    $this->subscriptionModel->markAsUsed($subId);
                }
            } else {
                $failed++;
                error_log("WebPush failed for endpoint {$endpoint}: {$report->getReason()}");
                
                if ($subId) {
                    $this->subscriptionModel->incrementFailure($subId);
                    
                    // Se subscription expirou, desativar
                    if ($report->isSubscriptionExpired()) {
                        $this->subscriptionModel->deactivate($subId);
                        error_log("WebPush subscription expired, deactivated: {$subId}");
                    }
                }
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'total' => count($subscriptions)
        ];
    }

    /**
     * Obtém status das subscriptions de uma empresa
     */
    public function getStatus(int $companyId): array
    {
        $subscriptions = $this->subscriptionModel->getAllSubscriptions($companyId);
        
        $active = 0;
        $inactive = 0;
        $devices = [];

        foreach ($subscriptions as $sub) {
            if ($sub['is_active']) {
                $active++;
            } else {
                $inactive++;
            }

            $devices[] = [
                'id' => $sub['id'],
                'device' => $sub['device_name'] ?: 'Dispositivo',
                'active' => (bool)$sub['is_active'],
                'lastUsed' => $sub['last_used_at'],
                'createdAt' => $sub['created_at'],
            ];
        }

        return [
            'enabled' => !empty($this->publicKey),
            'totalDevices' => count($subscriptions),
            'activeDevices' => $active,
            'inactiveDevices' => $inactive,
            'devices' => $devices,
        ];
    }
}
