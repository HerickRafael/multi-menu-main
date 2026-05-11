<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers/svg_helper.php';

$company = is_array($company ?? null) ? $company : [];
$customer = is_array($customer ?? null) ? $customer : [];
$order = is_array($order ?? null) ? $order : [];

$slug = isset($slug) ? (string) $slug : (string) ($company['slug'] ?? '');
$slugClean = trim($slug, '/');

$homeUrl = function_exists('base_url') ? base_url($slugClean) : '#';
$profileUrl = function_exists('base_url') ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'profile') : '#';

$orderId = (int) ($order['id'] ?? 0);
$orderNumber = (int) ($order['order_number'] ?? $order['id'] ?? 0);
$title = 'Pedido #' . $orderNumber . ' — ' . e($company['name'] ?? 'Cardápio');

$status = $order['status'] ?? 'pending';
$statusLabels = [
    'pending' => '⏳ Pendente',
    'paid' => '🎉 Concluído',
    'completed' => '🎉 Concluído',
    'canceled' => '❌ Cancelado',
];
$statusColors = [
    'pending' => 'background:#fef3c7;color:#92400e;border-color:#fbbf24;',
    'paid' => 'background:#d1fae5;color:#065f46;border-color:#6ee7b7;',
    'completed' => 'background:#d1fae5;color:#065f46;border-color:#6ee7b7;',
    'canceled' => 'background:#fee2e2;color:#991b1b;border-color:#fca5a5;',
];
$statusLabel = $statusLabels[$status] ?? $status;
$statusStyle = $statusColors[$status] ?? 'background:#f3f4f6;color:#374151;border-color:#d1d5db;';

ob_start();
$sharedJsPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/shared.js';
$orderJsPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/order.js';
$sharedJsVersion = is_file($sharedJsPath) ? (string) filemtime($sharedJsPath) : (string) time();
$orderJsVersion = is_file($orderJsPath) ? (string) filemtime($orderJsPath) : (string) time();
?>
<!doctype html>
<html lang="pt-br">
<head>
<?php include __DIR__ . '/partials/head.php'; ?>
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/partials/topbar.php'; ?>

  <main class="content">
    <?php include __DIR__ . '/partials/order-status.php'; ?>
    <?php include __DIR__ . '/partials/order-items.php'; ?>
    <?php include __DIR__ . '/partials/order-actions.php'; ?>
  </main>
</div>

<script src="<?= e(base_url('assets/shared.js')) ?>?v=<?= e($sharedJsVersion) ?>" defer></script>
<script src="<?= e(base_url('assets/order.js')) ?>?v=<?= e($orderJsVersion) ?>" defer></script>
</body>
</html>
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
