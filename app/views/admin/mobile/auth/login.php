<?php
/**
 * Login Mobile - Tela de autenticação
 * 
 * @var array $company
 * @var string|null $error
 * @var string $pageTitle
 * @var bool $hideBottomNav
 */

$themeColor = $company['menu_header_bg_color'] ?? $company['theme_color'] ?? '#4361ee';
$companyName = $company['name'] ?? 'Multi Menu';
$logo = $company['logo'] ?? '/assets/icons/admin/logo-multimenu.png';

$errorMessages = [
    'empty' => 'Preencha todos os campos.',
    'credentials' => 'E-mail ou senha incorretos.',
    'permission' => 'Você não tem permissão para acessar.',
    'company' => 'Empresa não encontrada.',
];

$errorMessage = $error ? ($errorMessages[$error] ?? 'Erro ao fazer login.') : null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="<?= htmlspecialchars($themeColor) ?>">
    <title>Login - <?= htmlspecialchars($companyName) ?></title>
    
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/icons/mobile/favicon-32x32.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/icons/mobile/apple-touch-icon.png">
    <link rel="manifest" href="/mobile-manifest.webmanifest">
    <link rel="stylesheet" href="/assets/css/mobile.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --primary: <?= htmlspecialchars($themeColor) ?>; }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-card animate-scaleIn">
            <div class="login-logo">
                <img src="<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars($companyName) ?>">
            </div>
            
            <h1 class="login-title"><?= htmlspecialchars($companyName) ?></h1>
            <p class="login-subtitle">Acesse o painel administrativo</p>
            
            <?php if (($_GET['msg'] ?? '') === 'sessao_expirada'): ?>
                <div class="form-error mb-md" style="text-align: center; padding: 12px; background: var(--warning-light, #fef9c3); color: #854d0e; border-radius: var(--radius-md);">
                    Sua sessão expirou. Por favor, entre novamente.
                </div>
            <?php endif; ?>
            <?php if ($errorMessage): ?>
                <div class="form-error mb-md" style="text-align: center; padding: 12px; background: var(--danger-light); border-radius: var(--radius-md);">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="/login">
                <?= \App\Middleware\CsrfProtection::field() ?>
                <div class="form-group">
                    <label class="form-label" for="email">E-mail</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="seu@email.com"
                        autocomplete="email"
                        inputmode="email"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Senha</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                </div>
                
                <button type="submit" class="btn-login">
                    Entrar
                </button>
            </form>
        </div>
    </div>

    <script>
        // Registra SW para funcionar offline
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/mobile-sw.js');
        }
    </script>
</body>
</html>
