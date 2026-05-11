<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers/svg_helper.php';
?>
<!doctype html>
<html lang="pt-br">
<head>
<?php include __DIR__ . '/partials/head.php'; ?>
</head>
<body>
  <div class="app">
    <div class="card">
      <?php include __DIR__ . '/partials/hero.php'; ?>
      <?php include __DIR__ . '/partials/summary.php'; ?>
      <?php include __DIR__ . '/partials/payment.php'; ?>
      <?php include __DIR__ . '/partials/address.php'; ?>
      <?php include __DIR__ . '/partials/items.php'; ?>
    </div>

    <div class="alert">
      ⚠️ Importante: Clique no botão abaixo para enviar seu pedido via WhatsApp
    </div>

    <?php include __DIR__ . '/partials/actions.php'; ?>
  </div>
</body>
</html>
