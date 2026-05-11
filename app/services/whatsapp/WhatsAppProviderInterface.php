<?php

declare(strict_types=1);

interface WhatsAppProviderInterface
{
    /**
     * Envia texto para o destinatário pelo provedor.
     *
     * @return array{http_code:int,curl_error:string,response:?string}
     */
    public function sendText(
        string $instanceName,
        string $target,
        string $message,
        int $delay,
        int $timeout
    ): array;
}
