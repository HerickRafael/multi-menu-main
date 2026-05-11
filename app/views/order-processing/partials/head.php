<?php

declare(strict_types=1);

$config = [
    'companyWhatsApp' => $company['whatsapp'] ?? '',
    'companyName' => $company['name'] ?? 'Restaurante',
    'confirmUrl' => base_url($slugClean . '/checkout/processing'),
    'checkoutUrl' => base_url($slugClean . '/checkout'),
    'successUrl' => base_url($slugClean . '/checkout/success'),
    'csrfToken' => \App\Middleware\CsrfProtection::generateToken(),
];
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
<meta name="csrf-token" content="<?= e($config['csrfToken']) ?>">
<title><?= e($title) ?></title>
<?php if (!empty($company['logo'])): ?>
<link rel="icon" type="image/png" href="<?= e(base_url($company['logo'])) ?>">
<link rel="apple-touch-icon" href="<?= e(base_url($company['logo'])) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= e(base_url('assets/order-processing.css')) ?>">
<script id="order-processing-config" type="application/json"><?= json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
