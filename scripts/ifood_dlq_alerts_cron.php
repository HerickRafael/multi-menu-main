#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * iFood DLQ Alerts Cron
 *
 * Avalia thresholds de saúde da queue e da API iFood e dispara alertas
 * quando violados. Destinos suportados (em ordem):
 *   1) error_log (sempre)
 *   2) Slack incoming webhook se IFOOD_DLQ_SLACK_WEBHOOK estiver setado
 *
 * Deduplicação:
 *   - Se o mesmo `type` de alerta já foi notificado nos últimos X minutos
 *     (default 15, env IFOOD_DLQ_ALERT_COOLDOWN_MIN), pula. Usa o próprio
 *     `ifood_api_logs` como storage do estado (linha sintética módulo='dlq_alert').
 *
 * Thresholds env-overridáveis:
 *   IFOOD_DLQ_DEAD_1H              (default 5)
 *   IFOOD_DLQ_API_ERROR_RATE       (default 0.10)
 *   IFOOD_DLQ_LATENCY_P95          (default 5000 ms)
 *   IFOOD_DLQ_ALERT_COOLDOWN_MIN   (default 15)
 *   IFOOD_DLQ_SLACK_WEBHOOK        (opcional; URL completa)
 *
 * Cron sugerido (a cada 5 min):
 *   *\/5 * * * * php /path/to/scripts/ifood_dlq_alerts_cron.php >> /var/log/ifood_dlq_alerts.log 2>&1
 */

if (php_sapi_name() !== 'cli') {
    die("CLI only.\n");
}

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/IFood/DLQObservabilityService.php';

use App\Services\IFood\DLQObservabilityService;

$db = db();
$service = new DLQObservabilityService($db);
$ts = static fn(): string => '[' . date('Y-m-d H:i:s') . ']';
$cooldownMin = max(1, (int) (getenv('IFOOD_DLQ_ALERT_COOLDOWN_MIN') ?: '15'));
$webhook = (string) (getenv('IFOOD_DLQ_SLACK_WEBHOOK') ?: '');

echo $ts() . " Avaliando thresholds (cooldown={$cooldownMin}min, slack=" . ($webhook !== '' ? 'on' : 'off') . ")\n";

$violations = $service->alertingViolations();
if (empty($violations)) {
    echo $ts() . " Sem violações — saúde OK.\n";
    exit(0);
}

foreach ($violations as $alert) {
    $type = (string) $alert['type'];

    // Cooldown: skip se já alertamos este type recentemente.
    if (alreadyNotified($db, $type, $cooldownMin)) {
        echo $ts() . " {$type} → suprimido (cooldown ativo)\n";
        continue;
    }

    $level = (string) $alert['level'];
    $msg = (string) $alert['message'];
    $line = "[{$level}] {$type}: {$msg}";
    echo $ts() . " {$line}\n";
    error_log("[DLQ Alert] {$line}");

    if ($webhook !== '') {
        notifySlack($webhook, $alert);
    }

    recordNotified($db, $type, $alert);
}

echo $ts() . " Concluído. violations=" . count($violations) . "\n";

/**
 * @return bool true se já notificado dentro do cooldown
 */
function alreadyNotified(PDO $db, string $type, int $cooldownMin): bool
{
    try {
        $stmt = $db->prepare(
            "SELECT 1 FROM ifood_api_logs
              WHERE module = 'dlq_alert'
                AND error_message = :type
                AND created_at >= DATE_SUB(NOW(), INTERVAL :cool MINUTE)
              LIMIT 1"
        );
        $stmt->execute([':type' => $type, ':cool' => $cooldownMin]);
        return (bool) $stmt->fetchColumn();
    } catch (\Throwable $e) {
        // Em caso de erro na verificação, assume não-notificado (prefere alertar)
        error_log('[DLQ Alert] cooldown check falhou: ' . $e->getMessage());
        return false;
    }
}

/**
 * Marca como notificado usando ifood_api_logs (reusa schema existente).
 * module='dlq_alert' e error_message=type permitem buscas eficientes.
 *
 * @param array<string,mixed> $alert
 */
function recordNotified(PDO $db, string $type, array $alert): void
{
    try {
        $payload = json_encode($alert, JSON_UNESCAPED_UNICODE);
        $db->prepare(
            "INSERT INTO ifood_api_logs
                (company_id, environment, module, request_method, request_url,
                 http_status, latency_ms, attempt_number, request_body, response_body, error_message)
             VALUES
                (0, 'production', 'dlq_alert', 'INTERNAL', 'cron://dlq_alerts',
                 NULL, NULL, 1, NULL, :body, :type)"
        )->execute([':body' => $payload, ':type' => $type]);
    } catch (\Throwable $e) {
        error_log('[DLQ Alert] record falhou: ' . $e->getMessage());
    }
}

/**
 * @param array<string,mixed> $alert
 */
function notifySlack(string $webhook, array $alert): void
{
    $emoji = match ((string) ($alert['level'] ?? '')) {
        'critical' => ':rotating_light:',
        'warning'  => ':warning:',
        default    => ':information_source:',
    };
    $text = sprintf(
        "%s *iFood DLQ Alert* — `%s`\n%s",
        $emoji,
        (string) ($alert['type'] ?? '?'),
        (string) ($alert['message'] ?? '')
    );

    $body = json_encode(['text' => $text]);
    $ch = curl_init($webhook);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 5,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err !== '' || $code < 200 || $code >= 300) {
        error_log("[DLQ Alert] Slack falhou: http={$code} err={$err} resp=" . substr((string) $resp, 0, 200));
    }
}
