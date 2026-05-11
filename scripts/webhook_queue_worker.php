<?php
/**
 * webhook_queue_worker.php
 * 
 * Worker que processa a fila de webhooks WhatsApp.
 * Chamado via cron a cada minuto (ou mais frequente).
 * 
 * Rodar via cron:
 *   * * * * * php /var/www/html/scripts/webhook_queue_worker.php >> /var/www/html/storage/logs/webhook_worker.log 2>&1
 */

declare(strict_types=1);

$root = dirname(__DIR__);
if (is_file($root . '/.env')) {
    foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            $_ENV[$k] = $v;
            putenv("{$k}={$v}");
        }
    }
}

$internalSecret = (string)($_ENV['WEBHOOK_INTERNAL_SECRET'] ?? getenv('WEBHOOK_INTERNAL_SECRET') ?: '');
$headers = ['Content-Type: application/json'];
if ($internalSecret !== '') {
    $headers[] = 'X-Webhook-Internal: ' . $internalSecret;
}

// Invocar o endpoint do worker via HTTP local (roda no mesmo Apache, com autoload correto)
$url = 'http://localhost/webhook/evolution-worker';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => '',
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 58, // Worker roda até 55s + margem
    CURLOPT_CONNECTTIMEOUT => 5,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$timestamp = date('Y-m-d H:i:s');

if ($error) {
    echo "[{$timestamp}] ERRO curl: {$error}\n";
    exit(1);
}

if ($httpCode !== 200) {
    echo "[{$timestamp}] HTTP {$httpCode}: {$response}\n";
    exit(1);
}

$result = json_decode($response, true);
$processed = $result['processed'] ?? 0;
$errors = $result['errors'] ?? 0;
$skipped = $result['skipped'] ?? 0;
$elapsed = $result['elapsed_ms'] ?? 0;

// Só logar se houve atividade (evitar spam no log)
if ($processed > 0 || $errors > 0 || $skipped > 0) {
    echo "[{$timestamp}] OK:{$processed} RETRY:{$errors} DROP:{$skipped} {$elapsed}ms\n";
}
