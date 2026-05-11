<?php
declare(strict_types=1);
require __DIR__ . '/../app/config/db.php';
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/models/Company.php';
require __DIR__ . '/../app/models/EvolutionInstance.php';
require __DIR__ . '/../app/services/ThermalReceipt.php';

$slug = $argv[1] ?? 'wollburger';
$instanceName = $argv[2] ?? 'WollBurger';
$to = $argv[3] ?? '5551920017687';

$company = Company::findBySlug($slug);
if (!$company) { echo "Empresa {$slug} nao encontrada\n"; exit(1); }

$apiKey = $company['evolution_api_key'] ?? null;
$apiUrl = rtrim($company['evolution_server_url'] ?? '', '/');
if (!$apiKey || !$apiUrl) { echo "Config Evolution ausente\n"; exit(2); }

$client = new \EvolutionApiPlugin\EvolutionApi($apiKey, $apiUrl, 'v2');

// generate a sample minimal order structure for PDF
$order = ['id' => 'TEST123', 'customer_name' => 'Teste PDF', 'customer_phone' => '+559999999999', 'total' => 12.5, 'customer_address' => "Rua Teste, 1", 'notes' => 'Teste anexo PDF'];
$items = [['quantity' => 1, 'product_name' => 'Produto X', 'line_total' => 12.5]];
// build message text similar to notifier
$lines = [];
$lines[] = "Novo pedido #" . ($order['id'] ?? '');
$lines[] = 'Empresa: ' . ($company['name'] ?? '');
$lines[] = 'Cliente: ' . ($order['customer_name'] ?? '') . ' (' . ($order['customer_phone'] ?? '') . ')';
$lines[] = 'Total: R$ ' . number_format((float)($order['total'] ?? 0), 2, ',', '.');
if (!empty($order['customer_address'])) {
    $lines[] = "Endereço: " . str_replace("\n", ' / ', $order['customer_address']);
}
if (!empty($order['notes'])) {
    $lines[] = 'Notas: ' . str_replace("\n", ' ', $order['notes']);
}
$lines[] = 'Itens:';
foreach ($items as $it) {
    $qty = (int)($it['quantity'] ?? $it['qty'] ?? 0);
    $name = $it['product_name'] ?? $it['name'] ?? '';
    $lineTotal = number_format((float)($it['line_total'] ?? 0), 2, ',', '.');
    $lines[] = "•  {$qty} x {$name} (R$ {$lineTotal})";
}
$caption = implode("\n", $lines);
$pdfPath = ThermalReceipt::generatePdf($company, $order, $items, $caption);

if (!file_exists($pdfPath)) { echo "PDF nao gerado\n"; exit(3); }

$b64 = base64_encode(file_get_contents($pdfPath));
$fileName = 'pedido_TEST123.pdf';
// build message text similar to notifier
$lines = [];
$lines[] = "Novo pedido #" . ($order['id'] ?? '');
$lines[] = 'Empresa: ' . ($company['name'] ?? '');
$lines[] = 'Cliente: ' . ($order['customer_name'] ?? '') . ' (' . ($order['customer_phone'] ?? '') . ')';
$lines[] = 'Total: R$ ' . number_format((float)($order['total'] ?? 0), 2, ',', '.');
if (!empty($order['customer_address'])) {
    $lines[] = "Endereço: " . str_replace("\n", ' / ', $order['customer_address']);
}
if (!empty($order['notes'])) {
    $lines[] = 'Notas: ' . str_replace("\n", ' ', $order['notes']);
}
$lines[] = 'Itens:';
foreach ($items as $it) {
    $qty = (int)($it['quantity'] ?? $it['qty'] ?? 0);
    $name = $it['product_name'] ?? $it['name'] ?? '';
    $lineTotal = number_format((float)($it['line_total'] ?? 0), 2, ',', '.');
    $lines[] = "•  {$qty} x {$name} (R$ {$lineTotal})";
}
$caption = implode("\n", $lines);
$media = $client->createMediaStructure('document', 'application/pdf', $caption, $b64, $fileName);

try {
    $res = $client->sendMediaMessage($instanceName, $to, $media);
    echo "Response:\n"; var_export($res); echo "\n";
    unlink($pdfPath);
} catch (Throwable $e) {
    echo "Erro sending media: " . $e->getMessage() . "\n";
    unlink($pdfPath);
}

exit(0);
