<?php

declare(strict_types=1);

require_once __DIR__ . '/WhatsAppProviderInterface.php';

class EvolutionV2Provider implements WhatsAppProviderInterface
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
        $url = "{$this->server}/message/sendText/{$instanceName}";

        $jsonPayload = json_encode([
            'number' => $target,
            'text' => $message,
            'delay' => $delay
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'apikey: ' . $this->apiKey
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
