<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers/svg_helper.php';

$company = is_array($company ?? null) ? $company : [];
$customer = is_array($customer ?? null) ? $customer : [];
$addresses = is_array($addresses ?? null) ? $addresses : [];

$slug = isset($slug) ? (string) $slug : (string) ($company['slug'] ?? '');
$slugClean = trim($slug, '/');

$homeUrl = function_exists('base_url') ? base_url($slugClean) : '#';
$cartUrl = function_exists('base_url') ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'cart') : '#';
$profileUrl = function_exists('base_url') ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'profile') : '#';
$logoutUrl = function_exists('base_url') ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'customer-logout') : '#';
$updateUrl = function_exists('base_url') ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'profile/update') : '#';
$addressesUrl = function_exists('base_url') ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'addresses') : '#';

$title = 'Perfil — ' . e($company['name'] ?? 'Cardápio');
$showFooterMenu = false;

$successMsg = '';
$errorMsg = '';

if (isset($_GET['updated'])) {
    $successMsg = 'Dados atualizados com sucesso!';
} elseif (isset($_GET['deleted'])) {
    $successMsg = 'Endereço excluído com sucesso!';
} elseif (isset($_GET['default'])) {
    $successMsg = 'Endereço padrão atualizado!';
} elseif (isset($_GET['address_created'])) {
    $successMsg = 'Endereço adicionado com sucesso!';
} elseif (isset($_GET['address_updated'])) {
    $successMsg = 'Endereço atualizado com sucesso!';
} elseif (isset($_GET['canceled'])) {
    $successMsg = 'Pedido cancelado com sucesso!';
} elseif (isset($_GET['deletion_requested'])) {
    $successMsg = 'Solicitação de exclusão registrada. Entraremos em contato em breve.';
} elseif (isset($_GET['error'])) {
    $errorCode = $_GET['error'];
    switch ($errorCode) {
        case 'cancel_not_allowed':
            $errorMsg = 'Este pedido não pode ser cancelado.';
            break;
        case 'deletion_pending':
            $errorMsg = 'Já existe uma solicitação de exclusão pendente.';
            break;
        case 'reorder_not_found':
            $errorMsg = 'Pedido não encontrado para repetição.';
            break;
        case 'reorder_not_yours':
            $errorMsg = 'Este pedido não pertence à sua conta.';
            break;
        case 'reorder_unavailable':
            $errorMsg = 'Nenhum item deste pedido está disponível no momento.';
            break;
        case 'required':
            $errorMsg = 'Preencha os campos obrigatórios.';
            break;
        case 'birthdate_invalid_format':
            $errorMsg = 'Data de nascimento inválida. Use o formato correto.';
            break;
        case 'birthdate_future':
            $errorMsg = 'Data de nascimento não pode ser no futuro.';
            break;
        case 'birthdate_too_young':
            $errorMsg = 'Você precisa ter pelo menos 13 anos.';
            break;
        case 'birthdate_too_old':
            $errorMsg = 'Data de nascimento inválida. Verifique o ano.';
            break;
        default:
            $errorMsg = 'Ocorreu um erro. Tente novamente.';
    }
}

ob_start();
$sharedJsPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/shared.js';
$profileJsPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets/profile.js';
$sharedJsVersion = is_file($sharedJsPath) ? (string) filemtime($sharedJsPath) : (string) time();
$profileJsVersion = is_file($profileJsPath) ? (string) filemtime($profileJsPath) : (string) time();
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
    <?php include __DIR__ . '/partials/alerts.php'; ?>
    <?php include __DIR__ . '/partials/personal-data-section.php'; ?>
    <?php include __DIR__ . '/partials/addresses-section.php'; ?>
    <?php include __DIR__ . '/partials/orders-section.php'; ?>
    <?php include __DIR__ . '/partials/loyalty-progress-section.php'; ?>
    <?php include __DIR__ . '/partials/privacy-section.php'; ?>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</div>

<script src="<?= e(base_url('assets/shared.js')) ?>?v=<?= e($sharedJsVersion) ?>" defer></script>
<script src="<?= e(base_url('assets/profile.js')) ?>?v=<?= e($profileJsVersion) ?>" defer></script>
</body>
</html>
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
