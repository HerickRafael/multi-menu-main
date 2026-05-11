<?php

declare(strict_types=1);

require_once __DIR__ . '/WhatsAppProviderInterface.php';

class EvoGoProvider implements WhatsAppProviderInterface
{
    private string $server;
    private string $apiKey;

    public function __construct(string $server, string $apiKey)
    {
        $this->server = rtrim($server, '/');
        $this->apiKey = $apiKey;
    }

    public function sendText(
        string $instanceName,
        string $target,
        string $message,
        int $delay,
        int $timeout
    ): array {
        // EvoGo usa endpoint global de envio. instanceName mantido por compatibilidade da assinatura.
        $url = "{$this->server}/send/text";

        $jsonPayload = json_encode([
            'number' => $target,
            'text' => $message,
            'delay' => $delay,
            'id' => $instanceName
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'apikey: ' . $this->apiKey,
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonPayload
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        return [
            'http_code' => $httpCode,
            'curl_error' => $curlError,
            'response' => $response === false ? null : $response
        ];
    }
}
