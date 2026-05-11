/**
 * order-notifications.js — Polling de pedidos + Toast + Push notifications
 *
 * Dependências (devem ser carregadas antes):
 *   - kds-chime.js  (expõe window.KdsChime, window._kdsChimePrepareUri, etc.)
 *
 * Configuração via window.ADMIN_NOTIFICATIONS_CONFIG (definido no PHP):
 *   kdsUrl      — URL do endpoint KDS data
 *   orderUrl    — Base URL para abrir pedido
 *   kdsLink     — URL da página KDS
 *   bellUrl     — URL do arquivo de áudio para alarme
 *   companySlug — Slug da empresa (para push subscription)
 */
(function () {
  'use strict';

  /* ---- Lê config do ponto único (APP_CONFIG emitido pelo layout) ---- */
  var cfg        = window.APP_CONFIG || {};
  var dataUrl      = cfg.kdsUrl    || '';
  var orderUrlBase = cfg.orderUrl  || '';
  var kdsLink      = cfg.kdsLink   || cfg.kdsPage || '';
  var bellConfig   = (cfg.bellUrl  || '').trim();

  if (!dataUrl) return; // sem URL de polling → nada a fazer

  /* ---- Helpers (usa funções expostas por kds-chime.js) ---- */
  var prepareUri    = window._kdsChimePrepareUri    || function (v) { return v; };
  var isPageActive  = window._kdsChimeIsPageActive  || function () { return true; };
  var trackActivity = window._kdsChimeTrackActivity || function () {};

  /* ---- Chime ---- */
  var chime = new KdsChime(prepareUri(bellConfig) || window.KDS_DEFAULT_BELL_URI || '');
  window.chime = chime; // expõe para debug no console

  var ensureChimeActivated = function () {
    if (!chime.isActivated()) chime.activate();
  };

  /* Rastrear atividade do usuário */
  window._kdsChimeTrackActivity();
  ['pointerdown', 'touchstart', 'keydown', 'click'].forEach(function (evt) {
    document.addEventListener(evt, function () {
      ensureChimeActivated();
      trackActivity();
      chime.handleUserActivity();
    }, { passive: true });
  });

  /* ---- Estado de polling ---- */
  var POLL_INTERVAL = 6000;
  var isFetching   = false;
  var syncToken    = null;

  var KNOWN_PENDING_KEY = 'admin_known_pending_orders';
  var INIT_FLAG_KEY     = 'admin_orders_initialized';

  function loadKnownPending() {
    try {
      var stored = sessionStorage.getItem(KNOWN_PENDING_KEY);
      if (stored) return new Set(JSON.parse(stored));
    } catch {}
    return new Set();
  }

  function saveKnownPending(set) {
    try {
      sessionStorage.setItem(KNOWN_PENDING_KEY, JSON.stringify([...set]));
      sessionStorage.setItem(INIT_FLAG_KEY, 'true');
    } catch {}
  }

  var initialized   = sessionStorage.getItem(INIT_FLAG_KEY) === 'true';
  var knownPending  = loadKnownPending();
  var toastContainer = document.getElementById('admin-order-toasts');

  /* ---- Formatação ---- */
  function formatCurrency(value) {
    try {
      return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value || 0));
    } catch {
      return 'R$ ' + Number(value || 0).toFixed(2).replace('.', ',');
    }
  }

  /* ---- Toast ---- */
  function showToast(order) {
    if (!toastContainer) return;

    var toast     = document.createElement('article');
    toast.className = 'admin-order-toast';

    var total       = formatCurrency(order.total || order.subtotal || 0);
    var name        = order.customer_name || 'Cliente';
    var phone       = order.customer_phone || '';
    var orderLink   = orderUrlBase ? orderUrlBase + encodeURIComponent(order.id) : '';
    var orderNumber = order.order_number || order.id;
    var startTime   = Date.now();

    function formatElapsed() {
      var s = Math.floor((Date.now() - startTime) / 1000);
      if (s < 60) return s + 's';
      return Math.floor(s / 60) + 'm ' + (s % 60) + 's';
    }

    toast.innerHTML = [
      '<div class="toast-urgency-bar"></div>',
      '<div class="toast-header">',
        '<div class="toast-icon-wrapper">',
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">',
            '<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>',
            '<line x1="3" y1="6" x2="21" y2="6"/>',
            '<path d="M16 10a4 4 0 0 1-8 0"/>',
          '</svg>',
        '</div>',
        '<div class="toast-content">',
          '<div class="toast-title-row">',
            '<h3 class="toast-title">Pedido #' + orderNumber + '</h3>',
            '<span class="toast-badge">',
              '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>',
              'NOVO',
            '</span>',
          '</div>',
          '<p class="toast-subtitle">',
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">',
              '<path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 015.19 12.83a19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z" stroke-linecap="round" stroke-linejoin="round"/>',
            '</svg>',
            name + (phone ? ' · ' + phone : ''),
          '</p>',
        '</div>',
        '<button type="button" class="toast-close" data-dismiss aria-label="Fechar">',
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">',
            '<path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>',
          '</svg>',
        '</button>',
      '</div>',
      '<div class="toast-divider"></div>',
      '<div class="toast-info-row">',
        '<div class="toast-value">',
          '<div>',
            '<div class="toast-value-label">Total</div>',
            '<div class="toast-value-amount">' + total + '</div>',
          '</div>',
        '</div>',
        '<div class="toast-timer" data-timer>',
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">',
            '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>',
            '<line x1="16" y1="2" x2="16" y2="6" stroke-linecap="round" stroke-linejoin="round"/>',
            '<line x1="8" y1="2" x2="8" y2="6" stroke-linecap="round" stroke-linejoin="round"/>',
            '<line x1="3" y1="10" x2="21" y2="10" stroke-linecap="round" stroke-linejoin="round"/>',
          '</svg>',
          '<span data-elapsed>0s</span>',
        '</div>',
      '</div>',
      '<div class="toast-actions">',
        (kdsLink ? '<a class="toast-btn toast-btn-kds" href="' + kdsLink + '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71" stroke-linecap="round" stroke-linejoin="round"/></svg>Abrir KDS</a>' : ''),
        (orderLink ? '<a class="toast-btn toast-btn-primary" href="' + orderLink + '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke-linecap="round" stroke-linejoin="round"/><line x1="3" y1="6" x2="21" y2="6" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 10a4 4 0 01-8 0" stroke-linecap="round" stroke-linejoin="round"/></svg>Ver Pedido</a>' : ''),
      '</div>',
      '<div class="toast-progress"><div class="toast-progress-bar"></div></div>',
    ].join('');

    /* Timer elapsed */
    var timerEl = toast.querySelector('[data-elapsed]');
    var timerInterval = setInterval(function () {
      if (timerEl) timerEl.textContent = formatElapsed();
    }, 1000);

    function removeToast() {
      clearInterval(timerInterval);
      chime.stopAlarm();
      toast.classList.add('removing');
      toast.classList.remove('show');
      setTimeout(function () { toast.remove(); }, 300);
    }

    toast.querySelectorAll('[data-dismiss]').forEach(function (btn) {
      btn.addEventListener('click', removeToast);
    });

    toastContainer.appendChild(toast);
    requestAnimationFrame(function () { toast.classList.add('show'); });
    setTimeout(removeToast, 15000);
  }

  /* ---- Push / Browser Notifications ---- */
  var companySlug = (cfg.companySlug) || null;

  var NotificationManager = {
    permission: 'default',
    pushSubscription: null,
    companySlug: companySlug,
    isIOS: /iPhone|iPad|iPod/.test(navigator.userAgent),
    isPWA: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,

    async init() {
      if (!('Notification' in window)) return false;
      if (this.isIOS && !this.isPWA) { this.showIOSInstallPrompt(); return false; }
      this.permission = Notification.permission;
      if (this.permission === 'granted') await this.checkExistingSubscription();
      else if (this.permission === 'default') this.showPermissionPrompt();
      return this.permission === 'granted';
    },

    _themeGradient() {
      return (window.ADMIN_THEME && window.ADMIN_THEME.primaryGradient) || '#475569';
    },

    showIOSInstallPrompt() {
      var lastShown = localStorage.getItem('ios-pwa-prompt-shown');
      if (lastShown && (Date.now() - parseInt(lastShown)) < 86400000) return;
      var gradient = this._themeGradient();
      setTimeout(function () {
        var prompt = document.createElement('div');
        prompt.id = 'ios-pwa-prompt';
        prompt.style.cssText = 'position:fixed;bottom:1rem;left:1rem;right:1rem;max-width:380px;margin:0 auto;background:white;border-radius:1rem;padding:1.25rem;box-shadow:0 10px 40px -10px rgba(0,0,0,0.25);z-index:9998;border:1px solid #e2e8f0;animation:slideUp 0.3s ease;';
        prompt.innerHTML = '<style>@keyframes slideUp{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}</style>' +
          '<div style="display:flex;gap:0.75rem;align-items:flex-start;">' +
            '<div style="width:50px;height:50px;background:' + gradient + ';border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
              '<svg style="width:28px;height:28px;color:white;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>' +
            '</div>' +
            '<div style="flex:1;">' +
              '<strong style="display:block;color:#1e293b;margin-bottom:6px;font-size:1rem;">Instale o App para Notificações</strong>' +
              '<p style="font-size:0.85rem;color:#64748b;margin:0 0 10px 0;line-height:1.4;">Para receber notificações de novos pedidos no iPhone:</p>' +
              '<ol style="font-size:0.8rem;color:#475569;margin:0 0 12px 0;padding-left:1.2rem;line-height:1.5;">' +
                '<li>Toque no botão <strong>Compartilhar</strong></li>' +
                '<li>Selecione <strong>"Adicionar à Tela de Início"</strong></li>' +
                '<li>Abra o app pela tela inicial e ative as notificações</li>' +
              '</ol>' +
              '<button onclick="this.closest(\'#ios-pwa-prompt\').remove();localStorage.setItem(\'ios-pwa-prompt-shown\',Date.now().toString());" style="padding:0.5rem 1rem;background:#f1f5f9;color:#64748b;border:none;border-radius:0.5rem;cursor:pointer;font-size:0.85rem;width:100%;">Entendi</button>' +
            '</div>' +
          '</div>';
        document.body.appendChild(prompt);
      }, 2000);
    },

    async requestPermission() {
      if (!('Notification' in window)) return false;
      if (this.isIOS && !this.isPWA) { this.showIOSInstallPrompt(); return false; }
      try {
        this.permission = await Notification.requestPermission();
        if (this.permission === 'granted') await this.subscribeToPush();
        return this.permission === 'granted';
      } catch (err) {
        console.error('[Notifications] Erro ao solicitar permissão:', err);
        return false;
      }
    },

    async checkExistingSubscription() {
      if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
      try {
        var registration = await navigator.serviceWorker.ready;
        var subscription = await registration.pushManager.getSubscription();
        if (subscription) { this.pushSubscription = subscription; await this.sendSubscriptionToServer(subscription); }
        else await this.subscribeToPush();
      } catch (err) {
        console.error('[Push] Erro ao verificar subscription:', err);
      }
    },

    async subscribeToPush() {
      if (!this.companySlug || !('serviceWorker' in navigator) || !('PushManager' in window)) return null;
      if (this.isIOS && !this.isPWA) return null;
      try {
        var keyResponse = await fetch('/admin/' + encodeURIComponent(this.companySlug) + '/push/vapid-key', { credentials: 'include' });
        if (!keyResponse.ok) throw new Error('Falha ao obter chave VAPID');
        var keyData = await keyResponse.json();
        if (!keyData.success || !keyData.vapidPublicKey) throw new Error('Chave VAPID inválida');

        var vapidPublicKey = this.urlBase64ToUint8Array(keyData.vapidPublicKey);
        var registration   = await navigator.serviceWorker.ready;
        var subscription   = await registration.pushManager.getSubscription();

        if (!subscription) {
          subscription = await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: vapidPublicKey });
        }
        await this.sendSubscriptionToServer(subscription);
        return subscription;
      } catch (err) {
        console.error('[Push] Erro ao inscrever:', err);
        return null;
      }
    },

    async sendSubscriptionToServer(subscription) {
      if (!subscription || !this.companySlug) return false;
      try {
        var resp = await fetch('/admin/' + encodeURIComponent(this.companySlug) + '/push/subscribe', {
          method: 'POST', credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ subscription: subscription.toJSON() })
        });
        if (!resp.ok) throw new Error('Falha ao registrar subscription no servidor');
        var data = await resp.json();
        if (data.success) { this.pushSubscription = subscription; return true; }
        return false;
      } catch (err) {
        console.error('[Push] Erro ao enviar subscription:', err);
        return false;
      }
    },

    async unsubscribeFromPush() {
      if (!this.pushSubscription) return true;
      try {
        await fetch('/admin/' + encodeURIComponent(this.companySlug) + '/push/unsubscribe', {
          method: 'POST', credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ endpoint: this.pushSubscription.endpoint })
        });
        await this.pushSubscription.unsubscribe();
        this.pushSubscription = null;
        return true;
      } catch (err) {
        console.error('[Push] Erro ao cancelar subscription:', err);
        return false;
      }
    },

    urlBase64ToUint8Array(base64String) {
      var padding = '='.repeat((4 - base64String.length % 4) % 4);
      var base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
      var rawData = window.atob(base64);
      var output  = new Uint8Array(rawData.length);
      for (var i = 0; i < rawData.length; ++i) output[i] = rawData.charCodeAt(i);
      return output;
    },

    showPermissionPrompt() {
      if (localStorage.getItem('notification-prompt-dismissed')) return;
      var gradient = this._themeGradient();
      var prompt = document.createElement('div');
      prompt.id = 'notification-permission-prompt';
      prompt.style.cssText = 'position:fixed;bottom:1rem;right:1rem;max-width:320px;background:white;border-radius:1rem;padding:1rem;box-shadow:0 10px 40px -10px rgba(0,0,0,0.2);z-index:9998;border:1px solid #e2e8f0;animation:slideUp 0.3s ease;';
      prompt.innerHTML =
        '<div style="display:flex;gap:0.75rem;align-items:flex-start;">' +
          '<div style="width:40px;height:40px;background:' + gradient + ';border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
            '<svg style="width:20px;height:20px;color:white;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>' +
          '</div>' +
          '<div style="flex:1;">' +
            '<strong style="display:block;color:#1e293b;margin-bottom:4px;">Ativar Notificações?</strong>' +
            '<p style="font-size:0.85rem;color:#64748b;margin:0 0 12px 0;">Receba alertas de novos pedidos mesmo quando o navegador estiver em segundo plano.</p>' +
            '<div style="display:flex;gap:0.5rem;">' +
              '<button id="notif-allow" style="padding:0.5rem 1rem;background:' + gradient + ';color:white;border:none;border-radius:0.5rem;font-weight:600;cursor:pointer;font-size:0.85rem;">Ativar</button>' +
              '<button id="notif-later" style="padding:0.5rem 1rem;background:#f1f5f9;color:#64748b;border:none;border-radius:0.5rem;cursor:pointer;font-size:0.85rem;">Depois</button>' +
            '</div>' +
          '</div>' +
        '</div>';

      document.body.appendChild(prompt);
      var self = this;
      document.getElementById('notif-allow').onclick = async function () { prompt.remove(); await self.requestPermission(); };
      document.getElementById('notif-later').onclick = function () { prompt.remove(); localStorage.setItem('notification-prompt-dismissed', Date.now().toString()); };
    },

    async show(title, options) {
      options = options || {};
      if (this.permission !== 'granted') {
        var granted = await this.requestPermission();
        if (!granted) return null;
      }
      try {
        if ('serviceWorker' in navigator) {
          var registration = await navigator.serviceWorker.ready;
          if (registration && registration.showNotification) {
            var notifOptions = {
              icon: '/assets/icons/admin/icon-192x192.png',
              badge: '/assets/icons/admin/badge-72x72.png',
              tag: options.tag || 'admin-notification',
              body: options.body || '',
              data: { url: options.url || null, orderId: (options.data && options.data.orderId) || null, type: (options.data && options.data.type) || 'notification' }
            };
            if (!this.isIOS) { notifOptions.vibrate = [200, 100, 200]; notifOptions.requireInteraction = true; notifOptions.renotify = true; }
            await registration.showNotification(title, notifOptions);
            return true;
          }
        }
        if (!this.isIOS) {
          var notification = new Notification(title, {
            icon: '/assets/icons/admin/icon-192x192.png',
            badge: '/assets/icons/admin/badge-72x72.png',
            vibrate: [200, 100, 200], requireInteraction: true,
            tag: options.tag || 'admin-notification', renotify: true,
            body: options.body || ''
          });
          notification.onclick = function (event) {
            event.preventDefault();
            window.focus();
            if (options.url) window.location.href = options.url;
            notification.close();
          };
          return notification;
        }
        return null;
      } catch (err) {
        console.error('[Notifications] Erro ao mostrar:', err);
        return null;
      }
    },

    async showOrderNotification(order) {
      var total       = formatCurrency(order.total || order.subtotal || 0);
      var name        = order.customer_name || 'Cliente';
      var orderNumber = order.order_number || order.id;
      return this.show('Novo Pedido #' + orderNumber, {
        body: name + ' - ' + total,
        tag: 'order-' + order.id,
        url: orderUrlBase ? orderUrlBase + encodeURIComponent(order.id) : null,
        data: { orderId: order.id, orderNumber: orderNumber }
      });
    }
  };

  NotificationManager.init();

  /* ---- Processamento de dados ---- */
  function orderKey(value) {
    var num = Number(value);
    return Number.isFinite(num) ? Math.trunc(num) : 0;
  }

  function collectPending(orders) {
    var set = new Set();
    orders.forEach(function (order) {
      if (!order) return;
      if (String(order.status || '').toLowerCase() !== 'pending') return;
      var id = orderKey(order.id || order.order_id);
      if (id > 0) set.add(id);
    });
    return set;
  }

  function processData(data) {
    var orders     = Array.isArray(data.orders) ? data.orders : [];
    var pendingSet = collectPending(orders);

    if (!initialized) {
      knownPending = pendingSet;
      saveKnownPending(pendingSet);
      initialized = true;
      return;
    }

    var newOrders = [];
    orders.forEach(function (order) {
      if (!order) return;
      if (String(order.status || '').toLowerCase() !== 'pending') return;
      var id = orderKey(order.id || order.order_id);
      if (id > 0 && !knownPending.has(id)) newOrders.push(order);
    });

    knownPending = pendingSet;
    saveKnownPending(pendingSet);
    if (!newOrders.length) return;

    newOrders.forEach(function (order) { showToast(order); });
    chime.ring();

    if (document.hidden || !document.hasFocus()) {
      newOrders.forEach(function (order) { NotificationManager.showOrderNotification(order); });
    }
  }

  /* ---- Polling ---- */
  function fetchData() {
    if (isFetching) return;
    isFetching = true;
    var url = dataUrl;
    if (syncToken) url += (url.includes('?') ? '&' : '?') + 'since=' + encodeURIComponent(syncToken);
    fetch(url, { credentials: 'include' })
      .then(function (res) { return res.ok ? res.json() : Promise.reject(); })
      .then(function (data) { syncToken = data.sync_token || data.server_time || syncToken; processData(data); })
      .catch(function () {})
      .finally(function () { isFetching = false; });
  }

  fetchData();
  var pollIntervalId = setInterval(fetchData, POLL_INTERVAL);

  /* Pausar polling quando aba em background */
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      if (pollIntervalId) { clearInterval(pollIntervalId); pollIntervalId = null; }
    } else {
      if (!pollIntervalId) { fetchData(); pollIntervalId = setInterval(fetchData, POLL_INTERVAL); }
    }
  });

  window.addEventListener('beforeunload', function () { chime.dispose(); });

})();
