<?php
/**
 * Layout Mobile Admin - Multi Menu
 * 
 * Layout minimalista otimizado para toque.
 * Sem sidebar pesada, usando bottom navigation.
 * 
 * Variáveis esperadas:
 * - $pageTitle: string - Título da página
 * - $content: string - Conteúdo da página (via ob_get_clean)
 * - $company: array - Dados da empresa
 * - $user: array - Usuário logado
 * - $activeNav: string - Item ativo do menu (dashboard, orders, products, etc)
 * - $hideBottomNav: bool - Ocultar navegação inferior (para login, etc)
 */

$pageTitle = $pageTitle ?? 'Admin Mobile';
$activeNav = $activeNav ?? 'dashboard';
$hideBottomNav = $hideBottomNav ?? false;

// Suporte a array ou objeto - Extrai todas as cores do sistema
$getData = function($key, $default) use ($company) {
    if (is_array($company)) {
        return $company[$key] ?? $default;
    }
    return $company->$key ?? $default;
};

// Cores do sistema configuradas em settings (cascata: config → theme_color → fallback)
$_themeBase = $getData('theme_color', '#4361ee');
$headerBgColor      = $getData('menu_header_bg_color', $_themeBase);
$headerTextColor    = $getData('menu_header_text_color', '#FFFFFF');
$headerButtonColor  = $getData('menu_header_button_color', '#F59E0B');
$logoBorderColor    = $getData('menu_logo_border_color', $headerBgColor);
$groupTitleBgColor  = $getData('menu_group_title_bg_color', '#F59E0B');
$groupTitleTextColor = $getData('menu_group_title_text_color', '#000000');
$welcomeBgColor     = $getData('menu_welcome_bg_color', $headerBgColor);
$welcomeTextColor   = $getData('menu_welcome_text_color', '#FFFFFF');

// Fallback para tema principal (usa fundo do cabeçalho como primary)
$themeColor = $headerBgColor;
$companyName = $getData('name', 'Multi Menu');
$companyLogo = $getData('logo', null);
$slug = $_SERVER['MOBILE_SLUG'] ?? 'wollburger';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="<?= \App\Middleware\CsrfProtection::generateToken() ?>">
    <meta name="theme-color" content="<?= htmlspecialchars($themeColor) ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($companyName) ?>">
    
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($companyName) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/icons/mobile/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/icons/mobile/favicon-16x16.png">
    <link rel="shortcut icon" href="/favicon.ico">
    
    <!-- PWA -->
    <link rel="manifest" href="/mobile-manifest.webmanifest">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/icons/mobile/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="192x192" href="/assets/icons/mobile/icon-192x192.png">
    
    <!-- CSS Mobile Isolado -->
    <link rel="stylesheet" href="/assets/css/mobile.css?v=<?= filemtime(__DIR__ . '/../../../../public/assets/css/mobile.css') ?>">
    
    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Theme - Cores do Sistema -->
    <style>
        :root {
            /* Cores principais baseadas nas configurações */
            --primary: <?= htmlspecialchars($headerBgColor) ?>;
            --primary-dark: <?= htmlspecialchars($headerBgColor) ?>dd;
            --primary-light: <?= htmlspecialchars($headerBgColor) ?>22;
            
            /* Variáveis de compatibilidade com admin desktop */
            --admin-primary-color: <?= htmlspecialchars($headerBgColor) ?>;
            --admin-primary-soft: <?= htmlspecialchars($headerBgColor) ?>33;
            --admin-primary-gradient: linear-gradient(135deg, <?= htmlspecialchars($headerBgColor) ?>, <?= htmlspecialchars($headerBgColor) ?>);
            
            /* Cores do cabeçalho */
            --header-bg: <?= htmlspecialchars($headerBgColor) ?>;
            --header-text: <?= htmlspecialchars($headerTextColor) ?>;
            --header-button: <?= htmlspecialchars($headerButtonColor) ?>;
            --logo-border: <?= htmlspecialchars($logoBorderColor) ?>;
            
            /* Cores de grupos/seções */
            --group-title-bg: <?= htmlspecialchars($groupTitleBgColor) ?>;
            --group-title-text: <?= htmlspecialchars($groupTitleTextColor) ?>;
            
            /* Cores de boas-vindas/destaque */
            --welcome-bg: <?= htmlspecialchars($welcomeBgColor) ?>;
            --welcome-text: <?= htmlspecialchars($welcomeTextColor) ?>;
        }
        
        /* Aplicar cores ao header */
        .mobile-header {
            background: var(--header-bg) !important;
            color: var(--header-text) !important;
        }
        .mobile-header .header-title,
        .mobile-header .header-logo {
            color: var(--header-text) !important;
        }
        .mobile-header .btn-back svg,
        .mobile-header .header-right svg {
            stroke: var(--header-text) !important;
        }
        .mobile-header .header-right a,
        .mobile-header .header-right button {
            color: var(--header-button) !important;
        }
        .header-logo {
            border-color: var(--logo-border) !important;
        }
        
        /* Bottom nav usa a cor primária */
        .bottom-nav {
            border-top: 2px solid var(--primary);
        }
        .nav-item.active {
            color: var(--primary);
        }
        .nav-item.active svg {
            stroke: var(--primary);
        }
        
        /* Botões e elementos interativos */
        .btn-primary,
        .fab {
            background: var(--primary) !important;
        }
        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        
        /* Cards e seções destacadas */
        .stat-card--primary .stat-card__icon {
            background: var(--primary-light);
            color: var(--primary);
        }
    </style>
</head>
<body class="mobile-body">
    <!-- Header Mobile -->
    <header class="mobile-header">
        <div class="header-content">
            <div class="header-left">
                <?php if (isset($showBackButton) && $showBackButton): ?>
                    <button type="button" class="btn-back" onclick="history.back()">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                    </button>
                <?php else: ?>
                    <div class="header-logo">
                        <img src="/assets/icons/admin/logo-multimenu.png" alt="<?= htmlspecialchars($companyName) ?>" style="width:28px;height:28px;object-fit:contain;border-radius:6px;">
                    </div>
                <?php endif; ?>
            </div>
            
            <h1 class="header-title"><?= htmlspecialchars($pageTitle) ?></h1>
            
            <div class="header-right">
                <?php if (isset($headerActions)): ?>
                    <?= $headerActions ?>
                <?php else: ?>
                    <button type="button" class="btn-icon" id="btnNotifications">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                        <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="mobile-main">
        <?= $content ?? '' ?>
    </main>

    <!-- Bottom Navigation -->
    <?php if (!$hideBottomNav): ?>
    <nav class="mobile-bottom-nav">
        <a href="/dashboard" class="nav-item <?= $activeNav === 'dashboard' ? 'active' : '' ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span>Início</span>
        </a>
        
        <a href="/orders" class="nav-item <?= $activeNav === 'orders' ? 'active' : '' ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            <span>Pedidos</span>
            <span class="nav-badge" id="ordersBadge" style="display: none;">0</span>
        </a>
        
        <a href="/products" class="nav-item <?= $activeNav === 'products' ? 'active' : '' ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
            <span>Produtos</span>
        </a>
        
        <a href="/customers" class="nav-item <?= $activeNav === 'customers' ? 'active' : '' ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            <span>Clientes</span>
        </a>
        
        <a href="/settings" class="nav-item <?= $activeNav === 'settings' ? 'active' : '' ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
            <span>Config</span>
        </a>
    </nav>
    <?php endif; ?>

    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner"></div>
    </div>

    <!-- JS Mobile Isolado -->
    <script src="/assets/js/mobile.js"></script>
    
    <!-- Sistema de Notificações Push para Mobile - iOS Compatible -->
    <script>
    // Cores do tema admin mobile
    window.ADMIN_THEME = {
      primaryColor: '<?= htmlspecialchars($themeColor) ?>',
      primaryGradient: 'linear-gradient(135deg, <?= htmlspecialchars($themeColor) ?>, <?= htmlspecialchars($themeColor) ?>)'
    };
    (function() {
        'use strict';
        
        const MobileNotificationManager = {
            permission: 'default',
            pushSubscription: null,
            companySlug: <?= json_encode($slug ?? null) ?>,
            isIOS: /iPhone|iPad|iPod/.test(navigator.userAgent),
            isPWA: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
            
            async init() {
                console.log('[Mobile Notifications] Inicializando...', {
                    isIOS: this.isIOS,
                    isPWA: this.isPWA,
                    hasNotification: 'Notification' in window,
                    hasSW: 'serviceWorker' in navigator,
                    hasPush: 'PushManager' in window
                });
                
                if (!('Notification' in window)) {
                    console.log('[Mobile Notifications] API não suportada');
                    return false;
                }
                
                // iOS requer PWA instalado
                if (this.isIOS && !this.isPWA) {
                    console.log('[Mobile Notifications] iOS requer app instalado como PWA');
                    this.showIOSInstallPrompt();
                    return false;
                }
                
                this.permission = Notification.permission;
                
                if (this.permission === 'granted') {
                    await this.checkExistingSubscription();
                } else if (this.permission === 'default') {
                    this.showPermissionPrompt();
                }
                
                return this.permission === 'granted';
            },
            
            showIOSInstallPrompt() {
                const lastShown = localStorage.getItem('ios-mobile-pwa-prompt-shown');
                if (lastShown && (Date.now() - parseInt(lastShown)) < 86400000) return;
                
                setTimeout(() => {
                    const prompt = document.createElement('div');
                    prompt.id = 'ios-pwa-prompt';
                    prompt.style.cssText = `
                        position: fixed;
                        bottom: 80px;
                        left: 12px;
                        right: 12px;
                        background: white;
                        border-radius: 16px;
                        padding: 16px;
                        box-shadow: 0 10px 40px -10px rgba(0,0,0,0.3);
                        z-index: 9998;
                        animation: slideUp 0.3s ease;
                    `;
                    prompt.innerHTML = `
                        <style>@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }</style>
                        <div style="display:flex;gap:12px;align-items:flex-start;">
                            <div style="width:44px;height:44px;background:${window.ADMIN_THEME?.primaryGradient || 'linear-gradient(135deg,<?= htmlspecialchars($headerBgColor) ?>,<?= htmlspecialchars($headerBgColor) ?>cc)'};border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg style="width:24px;height:24px;color:white;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                            </div>
                            <div style="flex:1;">
                                <strong style="display:block;color:#1e293b;margin-bottom:4px;font-size:0.95rem;">Receba Notificações de Pedidos</strong>
                                <p style="font-size:0.8rem;color:#64748b;margin:0 0 8px 0;line-height:1.4;">Adicione à Tela de Início para receber alertas:</p>
                                <ol style="font-size:0.75rem;color:#475569;margin:0 0 10px 0;padding-left:1rem;line-height:1.5;">
                                    <li>Toque em <strong>Compartilhar</strong> <svg style="width:12px;height:12px;vertical-align:middle;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <li>Selecione <strong>"Tela de Início"</strong></li>
                                </ol>
                                <button onclick="this.closest('#ios-pwa-prompt').remove();localStorage.setItem('ios-mobile-pwa-prompt-shown', Date.now().toString());" style="padding:8px 16px;background:#f1f5f9;color:#64748b;border:none;border-radius:8px;cursor:pointer;font-size:0.8rem;width:100%;">Entendi</button>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(prompt);
                }, 3000);
            },
            
            showPermissionPrompt() {
                if (localStorage.getItem('mobile-notification-prompt-dismissed')) return;
                
                setTimeout(() => {
                    const prompt = document.createElement('div');
                    prompt.id = 'notification-permission-prompt';
                    prompt.style.cssText = `
                        position: fixed;
                        bottom: 80px;
                        left: 12px;
                        right: 12px;
                        background: white;
                        border-radius: 16px;
                        padding: 16px;
                        box-shadow: 0 10px 40px -10px rgba(0,0,0,0.3);
                        z-index: 9998;
                        animation: slideUp 0.3s ease;
                    `;
                    prompt.innerHTML = `
                        <style>@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }</style>
                        <div style="display:flex;gap:12px;align-items:flex-start;">
                            <div style="width:44px;height:44px;background:${window.ADMIN_THEME?.primaryGradient || 'linear-gradient(135deg,<?= htmlspecialchars($headerBgColor) ?>,<?= htmlspecialchars($headerBgColor) ?>cc)'};border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg style="width:24px;height:24px;color:white;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                            </div>
                            <div style="flex:1;">
                                <strong style="display:block;color:#1e293b;margin-bottom:4px;font-size:0.95rem;">Ativar Notificações?</strong>
                                <p style="font-size:0.8rem;color:#64748b;margin:0 0 10px 0;line-height:1.4;">Receba alertas de novos pedidos em tempo real, mesmo com o app fechado.</p>
                                <div style="display:flex;gap:8px;">
                                    <button id="notif-allow-mobile" style="flex:1;padding:10px;background:${window.ADMIN_THEME?.primaryGradient || 'linear-gradient(135deg,<?= htmlspecialchars($headerBgColor) ?>,<?= htmlspecialchars($headerBgColor) ?>cc)'};color:white;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:0.85rem;">Ativar</button>
                                    <button id="notif-later-mobile" style="padding:10px 16px;background:#f1f5f9;color:#64748b;border:none;border-radius:8px;cursor:pointer;font-size:0.85rem;">Depois</button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.body.appendChild(prompt);
                    
                    document.getElementById('notif-allow-mobile').onclick = async () => {
                        prompt.remove();
                        await MobileNotificationManager.requestPermission();
                    };
                    
                    document.getElementById('notif-later-mobile').onclick = () => {
                        prompt.remove();
                        localStorage.setItem('mobile-notification-prompt-dismissed', Date.now().toString());
                    };
                }, 2000);
            },
            
            async requestPermission() {
                if (!('Notification' in window)) return false;
                
                if (this.isIOS && !this.isPWA) {
                    this.showIOSInstallPrompt();
                    return false;
                }
                
                try {
                    this.permission = await Notification.requestPermission();
                    console.log('[Mobile Notifications] Permissão:', this.permission);
                    
                    if (this.permission === 'granted') {
                        await this.subscribeToPush();
                    }
                    
                    return this.permission === 'granted';
                } catch (err) {
                    console.error('[Mobile Notifications] Erro ao solicitar permissão:', err);
                    return false;
                }
            },
            
            async checkExistingSubscription() {
                if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                    return;
                }
                
                try {
                    const registration = await navigator.serviceWorker.ready;
                    const subscription = await registration.pushManager.getSubscription();
                    
                    if (subscription) {
                        this.pushSubscription = subscription;
                        console.log('[Mobile Push] Subscription existente encontrada');
                        await this.sendSubscriptionToServer(subscription);
                    } else {
                        await this.subscribeToPush();
                    }
                } catch (err) {
                    console.error('[Mobile Push] Erro ao verificar subscription:', err);
                }
            },
            
            async subscribeToPush() {
                if (!this.companySlug) {
                    console.log('[Mobile Push] Slug da empresa não disponível');
                    return null;
                }
                
                if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                    console.log('[Mobile Push] Push não suportado');
                    return null;
                }
                
                if (this.isIOS && !this.isPWA) {
                    return null;
                }
                
                try {
                    console.log('[Mobile Push] Buscando chave VAPID...');
                    const keyResponse = await fetch(`/push/vapid-key`, {
                        credentials: 'include'
                    });
                    
                    if (!keyResponse.ok) {
                        throw new Error('Falha ao obter chave VAPID');
                    }
                    
                    const keyData = await keyResponse.json();
                    if (!keyData.success || !keyData.vapidPublicKey) {
                        throw new Error('Chave VAPID inválida');
                    }
                    
                    const vapidPublicKey = this.urlBase64ToUint8Array(keyData.vapidPublicKey);
                    
                    console.log('[Mobile Push] Aguardando Service Worker...');
                    const registration = await navigator.serviceWorker.ready;
                    
                    let subscription = await registration.pushManager.getSubscription();
                    
                    if (!subscription) {
                        console.log('[Mobile Push] Criando nova subscription...');
                        subscription = await registration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: vapidPublicKey
                        });
                        console.log('[Mobile Push] Nova subscription criada');
                    }
                    
                    await this.sendSubscriptionToServer(subscription);
                    
                    return subscription;
                } catch (err) {
                    console.error('[Mobile Push] Erro ao inscrever:', err);
                    return null;
                }
            },
            
            async sendSubscriptionToServer(subscription) {
                if (!subscription || !this.companySlug) return false;
                
                try {
                    console.log('[Mobile Push] Enviando subscription para servidor...');
                    const response = await fetch(`/push/subscribe`, {
                        method: 'POST',
                        credentials: 'include',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ subscription: subscription.toJSON() })
                    });
                    
                    if (!response.ok) {
                        throw new Error('Falha ao registrar subscription');
                    }
                    
                    const data = await response.json();
                    if (data.success) {
                        this.pushSubscription = subscription;
                        console.log('[Mobile Push] Subscription registrada com sucesso');
                        return true;
                    }
                    return false;
                } catch (err) {
                    console.error('[Mobile Push] Erro ao enviar subscription:', err);
                    return false;
                }
            },
            
            urlBase64ToUint8Array(base64String) {
                const padding = '='.repeat((4 - base64String.length % 4) % 4);
                const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
                const rawData = window.atob(base64);
                const outputArray = new Uint8Array(rawData.length);
                for (let i = 0; i < rawData.length; ++i) {
                    outputArray[i] = rawData.charCodeAt(i);
                }
                return outputArray;
            }
        };
        
        // Inicializar notificações
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/mobile-sw.js', { scope: '/' })
                .then(reg => {
                    console.log('SW Mobile registrado:', reg.scope);
                    // Inicializar notificações após SW registrar
                    MobileNotificationManager.init();
                })
                .catch(err => console.error('SW Mobile erro:', err));
        }
        
        // Expor globalmente
        window.MobileNotificationManager = MobileNotificationManager;
    })();
    </script>

    <script>
    (function(){
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (!meta) return;
        var token = meta.getAttribute('content');
        if (!token) return;
        window._csrfToken = token;
        function injectCsrf() {
            document.querySelectorAll('form').forEach(function(form){
                if ((form.getAttribute('method') || '').toUpperCase() === 'POST' && !form.querySelector('input[name="csrf_token"]')) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'csrf_token';
                    input.value = token;
                    form.appendChild(input);
                }
            });
        }
        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', injectCsrf); } else { injectCsrf(); }
        // Interceptar fetch para injetar CSRF em POSTs
        if (window.fetch) {
            var origFetch = window.fetch;
            window.fetch = function(url, opts) {
                opts = opts || {};
                if (opts.method && opts.method.toUpperCase() === 'POST') {
                    if (opts.headers instanceof Headers) {
                        if (!opts.headers.has('X-CSRF-TOKEN')) opts.headers.set('X-CSRF-TOKEN', token);
                    } else {
                        opts.headers = opts.headers || {};
                        if (!opts.headers['X-CSRF-TOKEN']) opts.headers['X-CSRF-TOKEN'] = token;
                    }
                }
                return origFetch.call(this, url, opts);
            };
        }
    })();
    </script>

    <?php if (isset($scripts)): ?>
        <?= $scripts ?>
    <?php endif; ?>
</body>
</html>
