<?php
declare(strict_types=1);
// scripts/generate_pdf_only.php

require __DIR__ . '/../app/config/db.php';
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/models/Company.php';
require __DIR__ . '/../app/services/ThermalReceipt.php';

$slug = $argv[1] ?? 'wollburger';

$company = Company::findBySlug($slug);
if (!$company) {
    fwrite(STDERR, "Empresa '{$slug}' nao encontrada\n");
    exit(1);
}

$orderId = 'TESTPDF-' . date('YmdHis');
$order = [
    'id' => $orderId,
    'customer_name' => 'Cliente Teste',
    'customer_phone' => '+559999999999',
    'customer_address' => "Rua Teste, 123\nBairro",
    'subtotal' => 25.0,
    'delivery_fee' => 5.0,
    'discount' => 0.0,
    'total' => 30.0,
    'notes' => 'Gerado sem envio',
];

$items = [
    ['quantity' => 1, 'product_name' => 'Produto A', 'line_total' => 20.0, 'modifiers' => [['name' => 'Sem sal']]],
    ['quantity' => 1, 'product_name' => 'Produto B', 'line_total' => 10.0],
];

echo "Gerando PDF (sem envio) para empresa '{$slug}'...\n";
$tmpPdf = ThermalReceipt::generatePdf($company, $order, $items);

if (!file_exists($tmpPdf)) {
    fwrite(STDERR, "Falha: PDF temporário nao foi gerado\n");
    exit(2);
}

$destDir = __DIR__ . '/../public/uploads/receipts';
if (!is_dir($destDir)) {
    if (!mkdir($destDir, 0755, true) && !is_dir($destDir)) {
        fwrite(STDERR, "Falha ao criar diretorio '{$destDir}'\n");
        unlink($tmpPdf);
        exit(3);
    }
}

$destName = 'pedido_' . $orderId . '.pdf';
$destPath = $destDir . '/' . $destName;
if (!rename($tmpPdf, $destPath)) {
    // fallback to copy
    if (!copy($tmpPdf, $destPath)) {
        fwrite(STDERR, "Falha ao mover/copy PDF para '{$destPath}'\n");
        unlink($tmpPdf);
        exit(4);
    }
    unlink($tmpPdf);
}

echo "PDF salvo em: {$destPath}\n";
echo "Tamanho: " . number_format(filesize($destPath)/1024, 2) . " KB\n";

exit(0);
