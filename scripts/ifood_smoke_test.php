#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Smoke test da infra de Fase 0:
 *  1) Instancia IFoodClient
 *  2) Chama endpoint de auth com credenciais inválidas
 *  3) Verifica que a tentativa foi logada em ifood_api_logs
 *
 * NÃO precisa de credenciais reais — espera-se um 400/401 e o pipeline grava.
 */

require_once __DIR__ . '/../app/bootstrap.php';

$base = __DIR__ . '/../app/services/IFood';
require_once $base . '/IFoodEndpoints.php';
require_once $base . '/IFoodApiLogger.php';
require_once $base . '/IFoodResponse.php';
require_once $base . '/IFoodClient.php';

use App\Services\IFood\IFoodApiLogger;
use App\Services\IFood\IFoodClient;
use App\Services\IFood\IFoodEndpoints;

$db = db();
$logger = new IFoodApiLogger($db);

$companyId = (int)($argv[1] ?? 1);
$env = IFoodEndpoints::ENV_SANDBOX;

echo "→ Disparando POST contra " . IFoodEndpoints::authToken($env) . " (credenciais inválidas, esperado 4xx)\n";

// Auth endpoint do iFood é application/x-www-form-urlencoded
$client = new IFoodClient($companyId, $env, null, $logger, null, maxAttempts: 1);
$response = $client->post(
    IFoodEndpoints::authToken($env),
    IFoodEndpoints::MODULE_AUTH,
    [
        'grantType'     => 'client_credentials',
        'clientId'      => 'smoketest-invalid',
        'clientSecret'  => 'smoketest-invalid',
    ],
    ['Content-Type: application/x-www-form-urlencoded']
);

echo sprintf(
    "← ok=%s status=%s latency=%dms attempts=%d error=%s\n",
    $response->ok ? 'true' : 'false',
    $response->status ?? 'null',
    $response->latencyMs,
    $response->attempts,
    $response->error ? mb_substr($response->error, 0, 120) : 'null'
);

$count = (int)$db->query("SELECT COUNT(*) FROM ifood_api_logs WHERE company_id={$companyId} AND module='auth'")->fetchColumn();
echo "ifood_api_logs (company={$companyId}, module=auth) → {$count} rows total\n";

$last = $db->query("SELECT id, environment, request_method, request_url, http_status, latency_ms, attempt_number, LEFT(error_message,80) AS err FROM ifood_api_logs WHERE company_id={$companyId} ORDER BY id DESC LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
if ($last) {
    echo "\nÚltimo log gravado:\n";
    foreach ($last as $k => $v) echo "  {$k}: " . ($v ?? '(null)') . "\n";
}
