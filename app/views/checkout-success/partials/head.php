<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Pedido confirmado — <?= e($successOrder->companyName ?: 'Checkout') ?></title>
<?php if ($successOrder->companyLogo !== ''): ?>
<link rel="icon" type="image/png" href="<?= e(base_url($successOrder->companyLogo)) ?>">
<link rel="apple-touch-icon" href="<?= e(base_url($successOrder->companyLogo)) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= e(base_url('assets/css/checkout-success.css')) ?>">
