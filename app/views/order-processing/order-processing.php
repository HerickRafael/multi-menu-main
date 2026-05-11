<?php

declare(strict_types=1);

$company = is_array($company ?? null) ? $company : [];
$slug = isset($slug) ? (string) $slug : (string) ($company['slug'] ?? '');
$slugClean = trim($slug, '/');

$title = 'Processando pedido... — ' . e($company['name'] ?? 'Cardápio');

ob_start();
?>
<!doctype html>
<html lang="pt-br">
<head>
<?php include __DIR__ . '/partials/head.php'; ?>
</head>
<body>
  <div class="container">
    <?php include __DIR__ . '/partials/animation.php'; ?>
    <?php include __DIR__ . '/partials/steps.php'; ?>
    <?php include __DIR__ . '/partials/progress.php'; ?>
  </div>

  <script src="<?= e(base_url('assets/order-processing.js')) ?>" defer></script>
</body>
</html>
<?php
$content = ob_get_clean();
echo $content;
