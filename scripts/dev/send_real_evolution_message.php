<?php
declare(strict_types=1);
require __DIR__ . '/../app/config/db.php';
require __DIR__ . '/../app/models/Company.php';
require __DIR__ . '/../app/models/EvolutionInstance.php';
require __DIR__ . '/../vendor/autoload.php';

$slug = $argv[1] ?? 'wollburger';
$to = $argv[2] ?? '+5551920017687';
$text = $argv[3] ?? "Mensagem de teste (envio real) - confira";

$company = Company::findBySlug($slug);
if (!$company) { echo "Empresa {$slug} não encontrada\n"; exit(1); }

$instances = EvolutionInstance::allForCompany((int)$company['id']);
$chosen = null;
foreach ($instances as $inst) {
    if (!empty($inst['instance_identifier']) && !empty($inst['status']) && strtolower($inst['status']) === 'connected') { $chosen = $inst; break; }
}
if (!$chosen && !empty($instances)) { foreach ($instances as $inst) { if (!empty($inst['instance_identifier'])) { $chosen = $inst; break; } } }

if (!$chosen) { echo "Nenhuma instância Evolution encontrada para empresa {$slug}. Impossível enviar.\n"; exit(2); }

echo "Usando instância: " . ($chosen['instance_identifier'] ?? '(n/a)') . "\n";

// instantiate client
try {
    $apiKey = $company['evolution_api_key'] ?? null;
    $apiUrl = $company['evolution_server_url'] ?? null;
    if (!$apiKey || !$apiUrl) { echo "Configurações Evolution ausentes para empresa\n"; exit(3); }

    $client = new \EvolutionApiPlugin\EvolutionApi($apiKey, rtrim($apiUrl, '/'), 'v2');
    // try sendTextMessage
    echo "Enviando mensagem para {$to}...\n";
    $resp = $client->sendTextMessage($chosen['instance_identifier'], $to, $text);
    echo "Resposta:\n";
    var_export($resp);
    echo "\n";
    exit(0);
} catch (Throwable $e) {
    echo "Erro ao enviar: " . $e->getMessage() . "\n";
    if (isset($resp)) { var_export($resp); }
    exit(4);
}
