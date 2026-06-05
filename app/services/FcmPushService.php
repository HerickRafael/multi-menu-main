<?php

declare(strict_types=1);

namespace App\Services;

require_once __DIR__ . '/../models/DeviceToken.php';

use DeviceToken;
use Throwable;

/**
 * Push nativo para o app (Android/iOS) via Firebase Cloud Messaging HTTP v1.
 *
 * Requer credenciais (config fcm_project_id + fcm_service_account). Sem elas,
 * todos os envios degradam graciosamente (reason=fcm_not_configured) — o
 * registro/remoção de devices continua funcionando normalmente.
 *
 * Espelha a interface do WebPushService (notifyNewOrder / sendNotification),
 * para conviver com o web push existente.
 */
class FcmPushService
{
    private const FCM_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private const TOKEN_URI_FALLBACK = 'https://oauth2.googleapis.com/token';

    private string $projectId;
    private string $serviceAccountPath;
    private ?array $serviceAccount = null;

    public function __construct()
    {
        $this->projectId = (string) (config('fcm_project_id') ?? '');
        $this->serviceAccountPath = (string) (config('fcm_service_account') ?? '');
    }

    public function isConfigured(): bool
    {
        return $this->projectId !== ''
            && $this->serviceAccountPath !== ''
            && is_readable($this->serviceAccountPath);
    }

    /** Notifica todos os devices da loja sobre um novo pedido. */
    public function notifyNewOrder(int $companyId, array $order): array
    {
        $orderNumber = $order['order_number'] ?? $order['id'] ?? 0;
        $orderId = (int) ($order['id'] ?? 0);
        $total = number_format((float) ($order['total'] ?? $order['subtotal'] ?? 0), 2, ',', '.');
        $customer = $order['customer_name'] ?? 'Cliente';

        return $this->sendToCompany(
            $companyId,
            "Novo Pedido #{$orderNumber}",
            "{$customer} - R$ {$total}",
            [
                'type'         => 'new_order',
                'order_id'     => (string) $orderId,
                'order_number' => (string) $orderNumber,
            ]
        );
    }

    /** Notificação de teste para os devices da loja. */
    public function sendTest(int $companyId): array
    {
        return $this->sendToCompany(
            $companyId,
            '🔔 Teste de Notificação',
            'Push do app está funcionando corretamente!',
            ['type' => 'test', 'timestamp' => (string) time()]
        );
    }

    /**
     * Envia uma notificação para todos os devices ativos da empresa.
     *
     * @return array{sent:int, failed:int, total:int, skipped?:bool, reason?:string}
     */
    public function sendToCompany(int $companyId, string $title, string $body, array $data = []): array
    {
        $tokens = DeviceToken::listActiveByCompany($companyId);
        $total = count($tokens);

        if ($total === 0) {
            return ['sent' => 0, 'failed' => 0, 'total' => 0, 'reason' => 'no_devices'];
        }

        if (!$this->isConfigured()) {
            // Sem credenciais: não envia, mas não é erro (registro segue válido).
            return ['sent' => 0, 'failed' => 0, 'total' => $total, 'skipped' => true, 'reason' => 'fcm_not_configured'];
        }

        $accessToken = $this->getAccessToken();
        if ($accessToken === null) {
            return ['sent' => 0, 'failed' => 0, 'total' => $total, 'skipped' => true, 'reason' => 'fcm_auth_failed'];
        }

        // FCM v1 exige data como strings.
        $stringData = [];
        foreach ($data as $k => $v) {
            $stringData[(string) $k] = is_scalar($v) ? (string) $v : json_encode($v);
        }

        $sent = 0;
        $failed = 0;

        foreach ($tokens as $row) {
            $token = (string) $row['fcm_token'];
            if ($this->sendToToken($accessToken, $token, $title, $body, $stringData)) {
                $sent++;
                DeviceToken::markSuccess($token);
            } else {
                $failed++;
                DeviceToken::markFailure($token);
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'total' => $total];
    }

    /** POST de uma mensagem para um device específico (FCM v1). */
    private function sendToToken(string $accessToken, string $deviceToken, string $title, string $body, array $data): bool
    {
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
        $message = [
            'message' => [
                'token'        => $deviceToken,
                'notification' => ['title' => $title, 'body' => $body],
                'data'         => $data,
                'android'      => ['priority' => 'high'],
                'apns'         => ['headers' => ['apns-priority' => '10']],
            ],
        ];

        [$status, $resp] = $this->httpPost($url, json_encode($message), [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ]);

        if ($status >= 200 && $status < 300) {
            return true;
        }

        error_log("FCM send falhou (HTTP {$status}): " . substr((string) $resp, 0, 300));

        return false;
    }

    /** Obtém o OAuth2 access token a partir da service account (JWT RS256). */
    private function getAccessToken(): ?string
    {
        static $cache = ['token' => null, 'exp' => 0];
        if ($cache['token'] !== null && $cache['exp'] > time() + 30) {
            return $cache['token'];
        }

        try {
            $sa = $this->loadServiceAccount();
            if (!$sa || empty($sa['client_email']) || empty($sa['private_key'])) {
                return null;
            }

            $now = time();
            $tokenUri = $sa['token_uri'] ?? self::TOKEN_URI_FALLBACK;

            $claims = [
                'iss'   => $sa['client_email'],
                'scope' => self::FCM_SCOPE,
                'aud'   => $tokenUri,
                'iat'   => $now,
                'exp'   => $now + 3600,
            ];

            $jwt = $this->signJwtRs256($claims, (string) $sa['private_key']);

            [$status, $resp] = $this->httpPost(
                $tokenUri,
                http_build_query([
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $jwt,
                ]),
                ['Content-Type: application/x-www-form-urlencoded']
            );

            if ($status < 200 || $status >= 300) {
                error_log("FCM OAuth falhou (HTTP {$status}): " . substr((string) $resp, 0, 300));
                return null;
            }

            $data = json_decode((string) $resp, true);
            $token = $data['access_token'] ?? null;
            if ($token) {
                $cache = ['token' => $token, 'exp' => $now + (int) ($data['expires_in'] ?? 3600)];
            }

            return $token;
        } catch (Throwable $e) {
            error_log('FCM getAccessToken erro: ' . $e->getMessage());
            return null;
        }
    }

    private function loadServiceAccount(): ?array
    {
        if ($this->serviceAccount !== null) {
            return $this->serviceAccount;
        }
        if (!is_readable($this->serviceAccountPath)) {
            return null;
        }
        $json = json_decode((string) file_get_contents($this->serviceAccountPath), true);
        $this->serviceAccount = is_array($json) ? $json : null;

        return $this->serviceAccount;
    }

    private function signJwtRs256(array $claims, string $privateKey): string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $segments = [
            $this->base64Url(json_encode($header)),
            $this->base64Url(json_encode($claims)),
        ];
        $signingInput = implode('.', $segments);

        $signature = '';
        if (!openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Falha ao assinar JWT da service account');
        }
        $segments[] = $this->base64Url($signature);

        return implode('.', $segments);
    }

    private function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /** @return array{0:int,1:string|false} [statusCode, body] */
    private function httpPost(string $url, string $body, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $resp = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status, $resp];
    }
}
