/**
 * Service Worker - Admin Panel
 * 
 * Funcionalidades:
 * - Cache de assets estáticos (CSS, JS, fontes)
 * - Network-first para páginas HTML e APIs
 * - Cache de imagens de produtos
 * - Offline fallback com página de erro amigável
 * - Background sync para ações pendentes
 */

const CACHE_VERSION = 'admin-v1';
const STATIC_CACHE = `admin-static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `admin-dynamic-${CACHE_VERSION}`;
const IMAGE_CACHE = `admin-images-${CACHE_VERSION}`;

// Tamanho máximo dos caches
const MAX_IMAGE_CACHE_SIZE = 200;
const MAX_DYNAMIC_CACHE_SIZE = 30;

// Assets estáticos para pré-cache
const STATIC_ASSETS = [
  '/assets/css/ui.css',
  '/assets/css/skeleton.css',
  '/assets/css/admin-responsive.css',
  '/assets/css/lazy-loading.css',
  '/assets/js/toast-system.js',
  '/assets/js/skeleton-system.js',
  '/assets/js/admin-common.js',
  '/assets/js/admin.js',
  '/assets/js/lazy-loading.js',
  '/audio/notification.mp3'
];

// Páginas offline fallback
const OFFLINE_PAGE = '/admin/offline';

// ==============================================
// INSTALAÇÃO
// ==============================================
self.addEventListener('install', (event) => {
  console.log('[Admin SW] Installing...');
  
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then((cache) => {
        console.log('[Admin SW] Pre-caching static assets');
        // Usar addAll com catch para não falhar se algum asset não existir
        return Promise.allSettled(
          STATIC_ASSETS.map(url => 
            cache.add(url).catch(err => {
              console.warn(`[Admin SW] Failed to cache: ${url}`, err);
            })
          )
        );
      })
      .then(() => {
        console.log('[Admin SW] Installation complete');
        return self.skipWaiting();
      })
  );
});

// ==============================================
// ATIVAÇÃO
// ==============================================
self.addEventListener('activate', (event) => {
  console.log('[Admin SW] Activating...');
  
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames
            .filter((cacheName) => {
              // Remover caches antigos do admin
              return cacheName.startsWith('admin-') && 
                     cacheName !== STATIC_CACHE && 
                     cacheName !== DYNAMIC_CACHE && 
                     cacheName !== IMAGE_CACHE;
            })
            .map((cacheName) => {
              console.log('[Admin SW] Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            })
        );
      })
      .then(() => {
        console.log('[Admin SW] Activation complete');
        return self.clients.claim();
      })
  );
});

// ==============================================
// FETCH (ESTRATÉGIAS DE CACHE)
// ==============================================
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorar requisições não-GET
  if (request.method !== 'GET') {
    return;
  }

  // Ignorar requisições de outros domínios
  if (url.origin !== self.location.origin) {
    return;
  }

  // Ignorar requisições que não são do admin (exceto assets compartilhados)
  const isAdminPage = url.pathname.startsWith('/admin');
  const isSharedAsset = url.pathname.startsWith('/assets/') || 
                        url.pathname.startsWith('/uploads/') ||
                        url.pathname.startsWith('/audio/');
  
  if (!isAdminPage && !isSharedAsset) {
    return;
  }

  // ESTRATÉGIA 1: Imagens (Cache-First)
  if (isImageRequest(request)) {
    event.respondWith(cacheFirstStrategy(request, IMAGE_CACHE));
    return;
  }

  // ESTRATÉGIA 2: Assets estáticos (Cache-First)
  if (isStaticAsset(request)) {
    event.respondWith(cacheFirstStrategy(request, STATIC_CACHE));
    return;
  }

  // ESTRATÉGIA 3: APIs (Network-Only com timeout)
  if (isApiRequest(request)) {
    event.respondWith(networkOnlyWithTimeout(request, 10000));
    return;
  }

  // ESTRATÉGIA 4: Páginas HTML (Network-First com fallback)
  event.respondWith(networkFirstStrategy(request, DYNAMIC_CACHE));
});

// ==============================================
// FUNÇÕES AUXILIARES
// ==============================================

function isImageRequest(request) {
  const url = new URL(request.url);
  return request.destination === 'image' || 
         /\.(jpg|jpeg|png|gif|webp|svg|ico)$/i.test(url.pathname);
}

function isStaticAsset(request) {
  const url = new URL(request.url);
  return /\.(css|js|woff|woff2|ttf|eot|mp3|wav)$/i.test(url.pathname);
}

function isApiRequest(request) {
  const url = new URL(request.url);
  return url.pathname.includes('/api/') || 
         url.pathname.includes('/kds/data') ||
         url.pathname.includes('/orders/update-status');
}

// Cache-First: Ideal para assets que mudam raramente
async function cacheFirstStrategy(request, cacheName) {
  const cache = await caches.open(cacheName);
  const cachedResponse = await cache.match(request);
  
  if (cachedResponse) {
    // Atualiza cache em background
    fetchAndCache(request, cache);
    return cachedResponse;
  }
  
  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      cache.put(request, networkResponse.clone());
      await trimCache(cacheName, MAX_IMAGE_CACHE_SIZE);
    }
    return networkResponse;
  } catch (error) {
    console.warn('[Admin SW] Network failed for:', request.url);
    return new Response('', { status: 404, statusText: 'Not Found' });
  }
}

// Network-First: Ideal para páginas HTML dinâmicas
async function networkFirstStrategy(request, cacheName) {
  const cache = await caches.open(cacheName);
  
  try {
    const networkResponse = await fetchWithTimeout(request, 5000);
    if (networkResponse.ok) {
      cache.put(request, networkResponse.clone());
      await trimCache(cacheName, MAX_DYNAMIC_CACHE_SIZE);
    }
    return networkResponse;
  } catch (error) {
    console.warn('[Admin SW] Network failed, trying cache:', request.url);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Retornar página offline se disponível
    return cache.match(OFFLINE_PAGE) || offlineFallback();
  }
}

// Network-Only com timeout para APIs
async function networkOnlyWithTimeout(request, timeout) {
  try {
    return await fetchWithTimeout(request, timeout);
  } catch (error) {
    console.warn('[Admin SW] API request failed:', request.url);
    return new Response(
      JSON.stringify({ error: 'Offline', message: 'Sem conexão com o servidor' }),
      { 
        status: 503, 
        statusText: 'Service Unavailable',
        headers: { 'Content-Type': 'application/json' }
      }
    );
  }
}

// Fetch com timeout
function fetchWithTimeout(request, timeout) {
  return new Promise((resolve, reject) => {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => {
      controller.abort();
      reject(new Error('Timeout'));
    }, timeout);
    
    fetch(request, { signal: controller.signal })
      .then((response) => {
        clearTimeout(timeoutId);
        resolve(response);
      })
      .catch((error) => {
        clearTimeout(timeoutId);
        reject(error);
      });
  });
}

// Atualiza cache em background
async function fetchAndCache(request, cache) {
  try {
    const response = await fetch(request);
    if (response.ok) {
      cache.put(request, response);
    }
  } catch (error) {
    // Silently fail
  }
}

// Limita tamanho do cache
async function trimCache(cacheName, maxSize) {
  const cache = await caches.open(cacheName);
  const keys = await cache.keys();
  
  if (keys.length > maxSize) {
    const toDelete = keys.slice(0, keys.length - maxSize);
    await Promise.all(toDelete.map(key => cache.delete(key)));
  }
}

// Fallback offline
function offlineFallback() {
  const html = `
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Offline - Admin</title>
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
          background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
          min-height: 100vh;
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 1rem;
        }
        .container {
          background: white;
          border-radius: 1.5rem;
          padding: 3rem 2rem;
          text-align: center;
          max-width: 400px;
          box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1);
        }
        .icon {
          width: 80px;
          height: 80px;
          background: linear-gradient(135deg, #4361ee, #6366f1);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          margin: 0 auto 1.5rem;
        }
        .icon svg { width: 40px; height: 40px; color: white; }
        h1 { 
          font-size: 1.5rem; 
          color: #1e293b; 
          margin-bottom: 0.75rem;
        }
        p { 
          color: #64748b; 
          line-height: 1.6;
          margin-bottom: 2rem;
        }
        button {
          background: linear-gradient(135deg, #4361ee, #6366f1);
          color: white;
          border: none;
          padding: 0.875rem 2rem;
          border-radius: 0.75rem;
          font-size: 1rem;
          font-weight: 600;
          cursor: pointer;
          transition: transform 0.2s, box-shadow 0.2s;
        }
        button:hover {
          transform: translateY(-2px);
          box-shadow: 0 8px 20px -5px rgba(91,33,182,0.4);
        }
        button:active { transform: translateY(0); }
      </style>
    </head>
    <body>
      <div class="container">
        <div class="icon">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3"/>
          </svg>
        </div>
        <h1>Você está offline</h1>
        <p>Não foi possível conectar ao servidor. Verifique sua conexão com a internet e tente novamente.</p>
        <button onclick="window.location.reload()">Tentar novamente</button>
      </div>
    </body>
    </html>
  `;
  
  return new Response(html, {
    status: 503,
    statusText: 'Service Unavailable',
    headers: { 'Content-Type': 'text/html; charset=utf-8' }
  });
}

// ==============================================
// PUSH NOTIFICATIONS - iOS Safari Compatible
// ==============================================
self.addEventListener('push', (event) => {
  console.log('[Admin SW] Push event received');
  
  if (!event.data) {
    console.log('[Admin SW] Push event has no data');
    return;
  }
  
  let data;
  try {
    data = event.data.json();
  } catch (e) {
    // Fallback para texto simples
    data = {
      title: 'Novo Pedido',
      body: event.data.text() || 'Você tem uma nova notificação'
    };
  }
  
  console.log('[Admin SW] Push data:', data);
  
  // Opções compatíveis com iOS Safari (remover opções não suportadas)
  // iOS Safari NÃO suporta: vibrate, actions, renotify, requireInteraction (como true)
  const isIOS = /iPhone|iPad|iPod/i.test(self.navigator?.userAgent || '');
  
  const options = {
    body: data.body || 'Novo pedido recebido!',
    icon: '/assets/icons/admin/icon-192x192.png',
    badge: '/assets/icons/admin/badge-72x72.png',
    tag: data.tag || 'admin-notification',
    data: {
      url: data.data?.url || data.url || '/admin/orders',
      orderId: data.data?.orderId || data.orderId,
      type: data.data?.type || data.type || 'notification'
    },
    // Silencioso por padrão no iOS, o som é gerenciado pelo sistema
    silent: isIOS ? false : (data.silent || false)
  };
  
  // Adicionar opções extras apenas se não for iOS
  if (!isIOS) {
    options.vibrate = data.vibrate || [200, 100, 200];
    options.renotify = data.renotify !== undefined ? data.renotify : true;
    options.requireInteraction = data.requireInteraction || false;
    options.actions = data.actions || [
      { action: 'view', title: 'Ver pedido' },
      { action: 'dismiss', title: 'Dispensar' }
    ];
  }
  
  const notificationPromise = self.registration.showNotification(
    data.title || 'Multi Menu Admin', 
    options
  );
  
  event.waitUntil(notificationPromise);
});

// Clique na notificação - iOS Compatible
self.addEventListener('notificationclick', (event) => {
  console.log('[Admin SW] Notification clicked:', event.notification.tag);
  event.notification.close();
  
  const action = event.action;
  const data = event.notification.data || {};
  
  // Determinar URL baseado na ação
  // Desktop usa desktopUrl ou constrói a partir do slug
  let url = '/admin/orders';
  const slug = data.slug || '';
  const orderId = data.orderId;
  
  if (action === 'dismiss') {
    return; // Apenas fechar
  } else if (action === 'view' && orderId) {
    // Preferir desktopUrl, senão construir com slug
    url = data.desktopUrl || (slug ? `/admin/${slug}/orders/show?id=${orderId}` : `/admin/orders/show?id=${orderId}`);
  } else if (action === 'kds') {
    url = slug ? `/admin/${slug}/kds` : '/admin/kds';
  } else if (data.desktopUrl) {
    url = data.desktopUrl;
  } else if (data.url && slug) {
    // Se tem url mobile e slug, converter para desktop
    url = data.url.replace('/orders/show', `/admin/${slug}/orders/show`);
  } else if (data.url) {
    url = data.url;
  }
  
  console.log('[Admin SW] Navigating to:', url);
  
  // Garantir URL absoluta
  const fullUrl = new URL(url, self.location.origin).href;

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then((clientList) => {
        // Tentar focar em janela existente do admin
        for (const client of clientList) {
          if (client.url.includes('/admin') && 'focus' in client) {
            // Navegar para a URL correta
            return client.navigate(fullUrl).then(() => client.focus()).catch(() => {
              // Se navigate falhar, tentar apenas focar
              return client.focus();
            });
          }
        }
        // Abrir nova janela se não existir
        if (clients.openWindow) {
          return clients.openWindow(fullUrl);
        }
      })
  );
});

// Fechar notificação
self.addEventListener('notificationclose', (event) => {
  console.log('[Admin SW] Notification closed:', event.notification.tag);
});

// ==============================================
// MESSAGE HANDLER (Comunicação com a página)
// ==============================================
self.addEventListener('message', (event) => {
  if (!event.data) return;
  
  switch (event.data.type) {
    case 'SKIP_WAITING':
      self.skipWaiting();
      break;
      
    case 'CLEAR_CACHE':
      event.waitUntil(
        caches.keys().then((names) => 
          Promise.all(names.filter(n => n.startsWith('admin-')).map(n => caches.delete(n)))
        )
      );
      break;
      
    case 'GET_VERSION':
      event.ports[0]?.postMessage({ version: CACHE_VERSION });
      break;
  }
});

console.log('[Admin SW] Service Worker loaded');
