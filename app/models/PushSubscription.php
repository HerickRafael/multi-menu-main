<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Model para gerenciar subscriptions de Web Push Notifications
 */
class PushSubscription
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Salva ou atualiza uma subscription
     */
    public function saveSubscription(int $companyId, array $subscription, ?int $userId = null, ?string $userAgent = null): int
    {
        $endpoint = $subscription['endpoint'] ?? '';
        $p256dhKey = $subscription['keys']['p256dh'] ?? '';
        $authKey = $subscription['keys']['auth'] ?? '';

        if (empty($endpoint) || empty($p256dhKey) || empty($authKey)) {
            throw new \InvalidArgumentException('Subscription inválida: campos obrigatórios faltando');
        }

        // Detectar nome do dispositivo a partir do User Agent
        $deviceName = $this->detectDeviceName($userAgent);

        // Tentar atualizar primeiro (upsert)
        $stmt = $this->db->prepare("
            INSERT INTO push_subscriptions 
                (company_id, user_id, endpoint, p256dh_key, auth_key, user_agent, device_name, is_active, failed_count)
            VALUES 
                (:company_id, :user_id, :endpoint, :p256dh_key, :auth_key, :user_agent, :device_name, 1, 0)
            ON DUPLICATE KEY UPDATE
                p256dh_key = VALUES(p256dh_key),
                auth_key = VALUES(auth_key),
                user_agent = VALUES(user_agent),
                device_name = VALUES(device_name),
                is_active = 1,
                failed_count = 0,
                updated_at = CURRENT_TIMESTAMP
        ");

        $stmt->execute([
            'company_id' => $companyId,
            'user_id' => $userId,
            'endpoint' => $endpoint,
            'p256dh_key' => $p256dhKey,
            'auth_key' => $authKey,
            'user_agent' => $userAgent,
            'device_name' => $deviceName,
        ]);

        return (int) $this->db->lastInsertId() ?: $this->getIdByEndpoint($companyId, $endpoint);
    }

    /**
     * Remove uma subscription
     */
    public function removeSubscription(int $companyId, string $endpoint): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM push_subscriptions 
            WHERE company_id = :company_id AND endpoint = :endpoint
        ");

        return $stmt->execute([
            'company_id' => $companyId,
            'endpoint' => $endpoint,
        ]);
    }

    /**
     * Obtém todas as subscriptions ativas de uma empresa
     */
    public function getActiveSubscriptions(int $companyId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, endpoint, p256dh_key, auth_key, device_name, last_used_at
            FROM push_subscriptions
            WHERE company_id = :company_id 
              AND is_active = 1
              AND failed_count < 5
            ORDER BY created_at DESC
        ");

        $stmt->execute(['company_id' => $companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Marca uma subscription como usada
     */
    public function markAsUsed(int $id): void
    {
        $stmt = $this->db->prepare("
            UPDATE push_subscriptions 
            SET last_used_at = CURRENT_TIMESTAMP, failed_count = 0
            WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
    }

    /**
     * Incrementa contador de falhas
     */
    public function incrementFailure(int $id): void
    {
        $stmt = $this->db->prepare("
            UPDATE push_subscriptions 
            SET failed_count = failed_count + 1
            WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
    }

    /**
     * Desativa uma subscription (endpoint expirado)
     */
    public function deactivate(int $id): void
    {
        $stmt = $this->db->prepare("
            UPDATE push_subscriptions SET is_active = 0 WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
    }

    /**
     * Obtém ID da subscription pelo endpoint
     */
    private function getIdByEndpoint(int $companyId, string $endpoint): int
    {
        $stmt = $this->db->prepare("
            SELECT id FROM push_subscriptions 
            WHERE company_id = :company_id AND endpoint = :endpoint
            LIMIT 1
        ");
        $stmt->execute([
            'company_id' => $companyId,
            'endpoint' => $endpoint,
        ]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    /**
     * Detecta nome do dispositivo a partir do User Agent
     */
    private function detectDeviceName(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Dispositivo desconhecido';
        }

        $ua = strtolower($userAgent);

        // Mobile
        if (str_contains($ua, 'iphone')) return 'iPhone';
        if (str_contains($ua, 'ipad')) return 'iPad';
        if (str_contains($ua, 'android') && str_contains($ua, 'mobile')) return 'Android Phone';
        if (str_contains($ua, 'android')) return 'Android Tablet';

        // Desktop
        if (str_contains($ua, 'windows')) return 'Windows';
        if (str_contains($ua, 'macintosh') || str_contains($ua, 'mac os')) return 'Mac';
        if (str_contains($ua, 'linux')) return 'Linux';
        if (str_contains($ua, 'cros')) return 'Chromebook';

        // Navegador
        if (str_contains($ua, 'chrome')) return 'Chrome';
        if (str_contains($ua, 'firefox')) return 'Firefox';
        if (str_contains($ua, 'safari')) return 'Safari';
        if (str_contains($ua, 'edge')) return 'Edge';

        return 'Navegador';
    }

    /**
     * Conta subscriptions ativas
     */
    public function countActive(int $companyId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM push_subscriptions
            WHERE company_id = :company_id AND is_active = 1
        ");
        $stmt->execute(['company_id' => $companyId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Lista todas as subscriptions de uma empresa (para admin)
     */
    public function getAllForCompany(int $companyId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, device_name, user_agent, is_active, last_used_at, failed_count, created_at
            FROM push_subscriptions
            WHERE company_id = :company_id
            ORDER BY is_active DESC, created_at DESC
        ");
        $stmt->execute(['company_id' => $companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
