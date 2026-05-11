/**
 * admin-pwa.js — PWA Service Worker registration + install prompt
 *
 * Lê cores do tema via CSS custom properties (--admin-primary-gradient)
 * definidas pelo layout em <style>:root{...}</style>
 */
(function () {
  'use strict';

  var SW_URL   = '/admin-sw.js';
  var SW_SCOPE = '/admin';

  // Registrar Service Worker
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register(SW_URL, { scope: SW_SCOPE })
        .then(function (registration) {
          console.log('\u2713 Admin Service Worker registered:', registration.scope);

          registration.addEventListener('updatefound', function () {
            var newWorker = registration.installing;
            console.log('[Admin PWA] New version installing...');

            newWorker.addEventListener('statechange', function () {
              if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                showUpdateNotification();
              }
            });
          });
        })
        .catch(function (error) {
          console.error('\u2717 Admin SW registration failed:', error);
        });
    });

    // Reload quando novo SW assumir controle
    var refreshing = false;
    navigator.serviceWorker.addEventListener('controllerchange', function () {
      if (!refreshing) {
        refreshing = true;
        window.location.reload();
      }
    });
  }

  function getPrimaryGradient() {
    var cssVar = getComputedStyle(document.documentElement)
      .getPropertyValue('--admin-primary-gradient').trim();
    return cssVar
      || (window.ADMIN_THEME && window.ADMIN_THEME.primaryGradient)
      || 'linear-gradient(135deg, #3b82f6, #3b82f6cc)';
  }

  // Notificação de atualização disponível
  function showUpdateNotification() {
    var toast = document.createElement('div');
    toast.className = 'admin-order-toast info show';
    toast.style.cssText = 'position:fixed;bottom:1rem;left:50%;transform:translateX(-50%);z-index:9999;';
    toast.innerHTML =
      '<div style="display:flex;align-items:center;gap:0.75rem;">' +
        '<svg style="width:24px;height:24px;flex-shrink:0;" fill="none" stroke="currentColor"' +
        ' stroke-width="1.5" viewBox="0 0 24 24">' +
          '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"' +
          ' d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0' +
          ' 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>' +
        '</svg>' +
        '<div>' +
          '<strong style="display:block;margin-bottom:2px;">Nova vers\u00e3o dispon\u00edvel</strong>' +
          '<span style="font-size:0.8rem;opacity:0.9;">Clique para atualizar o aplicativo</span>' +
        '</div>' +
      '</div>';
    toast.style.cursor = 'pointer';
    toast.onclick = function () {
      navigator.serviceWorker.ready.then(function (reg) {
        if (reg.waiting) reg.waiting.postMessage({ type: 'SKIP_WAITING' });
      });
      toast.remove();
    };
    document.body.appendChild(toast);
    setTimeout(function () { if (toast.parentNode) toast.remove(); }, 10000);
  }

  // Detectar modo standalone (PWA instalada)
  window.addEventListener('DOMContentLoaded', function () {
    var isStandalone = window.matchMedia('(display-mode: standalone)').matches
      || window.navigator.standalone === true
      || document.referrer.includes('android-app://');
    if (isStandalone) {
      document.body.classList.add('pwa-standalone');
      console.log('[Admin PWA] Running in standalone mode');
    }
  });

  // Prompt de instalação A2HS (Add to Home Screen)
  var deferredPrompt = null;

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    setTimeout(function () {
      if (deferredPrompt && !localStorage.getItem('pwa-install-dismissed')) {
        showInstallPrompt();
      }
    }, 30000);
  });

  function showInstallPrompt() {
    if (!deferredPrompt) return;
    var gradient = getPrimaryGradient();

    var prompt = document.createElement('div');
    prompt.id = 'pwa-install-prompt';
    prompt.style.cssText = [
      'position:fixed', 'bottom:1rem', 'left:1rem', 'right:1rem',
      'max-width:400px', 'margin:0 auto', 'background:white',
      'border-radius:1rem', 'padding:1.25rem',
      'box-shadow:0 20px 40px -10px rgba(0,0,0,0.2)', 'z-index:9999',
      'display:flex', 'align-items:center', 'gap:1rem',
      'border:1px solid #e2e8f0'
    ].join(';');
    prompt.innerHTML =
      '<div style="width:48px;height:48px;background:' + gradient + ';border-radius:12px;' +
        'display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
        '<svg style="width:24px;height:24px;color:white;" fill="none" stroke="currentColor"' +
          ' stroke-width="1.5" viewBox="0 0 24 24">' +
          '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"' +
            ' d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>' +
        '</svg>' +
      '</div>' +
      '<div style="flex:1;">' +
        '<strong style="display:block;color:#1e293b;margin-bottom:2px;">Instalar App</strong>' +
        '<span style="font-size:0.85rem;color:#64748b;">Acesse o painel direto da tela inicial</span>' +
      '</div>' +
      '<div style="display:flex;gap:0.5rem;">' +
        '<button id="pwa-install-dismiss" style="padding:0.5rem;color:#64748b;background:none;border:none;cursor:pointer;">' +
          '<svg style="width:20px;height:20px;" fill="none" stroke="currentColor"' +
            ' stroke-width="1.5" viewBox="0 0 24 24">' +
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"' +
              ' d="M6 18L18 6M6 6l12 12"/>' +
          '</svg>' +
        '</button>' +
        '<button id="pwa-install-accept" style="padding:0.5rem 1rem;background:' + gradient + ';' +
          'color:white;border:none;border-radius:0.5rem;font-weight:600;cursor:pointer;">' +
          'Instalar' +
        '</button>' +
      '</div>';

    document.body.appendChild(prompt);

    document.getElementById('pwa-install-accept').onclick = function () {
      prompt.remove();
      if (deferredPrompt) {
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(function (result) {
          console.log('[Admin PWA] Install prompt outcome:', result.outcome);
          deferredPrompt = null;
        });
      }
    };

    document.getElementById('pwa-install-dismiss').onclick = function () {
      prompt.remove();
      localStorage.setItem('pwa-install-dismissed', 'true');
    };
  }

  // API pública: instalar manualmente via console ou botão customizado
  window.installAdminPWA = function () {
    if (!deferredPrompt) return Promise.resolve(false);
    deferredPrompt.prompt();
    return deferredPrompt.userChoice.then(function (result) {
      console.log('[Admin PWA] Manual install outcome:', result.outcome);
      deferredPrompt = null;
      return result.outcome === 'accepted';
    });
  };
})();
