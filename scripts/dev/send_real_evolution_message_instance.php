<?php
declare(strict_types=1);
require __DIR__ . '/../app/config/db.php';
require __DIR__ . '/../app/models/Company.php';
require __DIR__ . '/../vendor/autoload.php';

$slug = $argv[1] ?? 'wollburger';
$instanceName = $argv[2] ?? 'WollBurger';
$to = $argv[3] ?? '551920017687';
$text = $argv[4] ?? "Teste via instanceName WollBurger";

$company = Company::findBySlug($slug);
if (!$company) { echo "Empresa {$slug} não encontrada\n"; exit(1); }

echo "Using company id={$company['id']} server={$company['evolution_server_url']}\n";
echo "InstanceName={$instanceName}, to={$to}\n";

try {
    $apiKey = $company['evolution_api_key'] ?? null;
    $apiUrl = rtrim($company['evolution_server_url'] ?? '', '/');
    if (!$apiKey || !$apiUrl) { echo "Config Evolution ausente\n"; exit(2); }

    $client = new \EvolutionApiPlugin\EvolutionApi($apiKey, $apiUrl, 'v2');
    $resp = $client->sendTextMessage($instanceName, $to, $text);
    echo "Response:\n"; var_export($resp); echo "\n";
    exit(0);
} catch (Throwable $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    exit(3);
}
