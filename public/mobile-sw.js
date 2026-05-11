/**
 * Service Worker - Admin Mobile
 * Multi Menu - PWA Dedicado para Gestão Mobile
 * 
 * Estratégias:
 * - Network-First para APIs e HTML (dados sempre frescos)
 * - Cache-First para assets estáticos
 * - Background Sync para ações offline
 */

const CACHE_VERSION = 'v3';
const STATIC_CACHE = `mobile-static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `mobile-dynamic-${CACHE_VERSION}`;
const IMAGE_CACHE = `mobile-images-${CACHE_VERSION}`;

// Assets para pré-cache (instalar junto com SW) - SOMENTE assets estáticos
const PRECACHE_ASSETS = [
    '/assets/css/mobile.css',
    '/mobile-manifest.webmanifest',
    '/assets/icons/mobile/icon-192x192.png',
    '/assets/icons/mobile/icon-512x512.png'
];

// Limite de itens nos caches dinâmicos
const CACHE_LIMITS = {
    dynamic: 50,
    images: 100
};

// ========= INSTALL =========
self.addEventListener('install', (event) => {
    console.log('[Mobile SW] Instalando...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('[Mobile SW] Pré-cacheando assets');
                return cache.addAll(PRECACHE_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// ========= ACTIVATE =========
self.addEventListener('activate', (event) => {
    console.log('[Mobile SW] Ativando...');
    
    event.waitUntil(
        caches.keys()
            .then((keys) => {
                return Promise.all(
                    keys
                        .filter((key) => key.startsWith('mobile-') && 
                                        key !== STATIC_CACHE && 
                                        key !== DYNAMIC_CACHE && 
                                        key !== IMAGE_CACHE)
                        .map((key) => {
                            console.log('[Mobile SW] Removendo cache antigo:', key);
                            return caches.delete(key);
                        })
                );
            })
            .then(() => self.clients.claim())
    );
});

// ========= FETCH =========
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Ignorar requests não-GET
    if (request.method !== 'GET') {
        return;
    }

    // Ignorar extensões e chrome-extension
    if (url.protocol === 'chrome-extension:' || url.protocol === 'extension:') {
        return;
    }

    // Estratégia baseada no tipo de recurso
    if (isApiRequest(url)) {
        // API: Network-First com timeout
        event.respondWith(networkFirstWithTimeout(request, 5000));
    } else if (isImageRequest(url)) {
        // Imagens: Cache-First
        event.respondWith(cacheFirstWithFallback(request, IMAGE_CACHE));
    } else if (isStaticAsset(url)) {
        // Assets estáticos: Cache-First
        event.respondWith(cacheFirst(request, STATIC_CACHE));
    } else {
        // HTML/páginas: Network-Only (nunca cachear páginas dinâmicas)
        event.respondWith(networkOnly(request));
    }
});

// ========= HELPERS =========

function isApiRequest(url) {
    return url.pathname.startsWith('/api/');
}

function isImageRequest(url) {
    return /\.(jpg|jpeg|png|gif|webp|svg|ico)$/i.test(url.pathname) ||
           url.pathname.startsWith('/uploads/');
}

function isStaticAsset(url) {
    return /\.(css|js|woff2?|ttf|eot)$/i.test(url.pathname) ||
           url.pathname.includes('/assets/');
}

// Cache-First: tenta cache, fallback para rede
async function cacheFirst(request, cacheName) {
    const cached = await caches.match(request);
    if (cached) {
        return cached;
    }
    
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        console.error('[Mobile SW] Fetch falhou:', error);
        return new Response('Offline', { status: 503 });
    }
}

// Cache-First com fallback para imagens
async function cacheFirstWithFallback(request, cacheName) {
    const cached = await caches.match(request);
    if (cached) {
        return cached;
    }
    
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, response.clone());
            await limitCacheSize(cacheName, CACHE_LIMITS.images);
        }
        return response;
    } catch (error) {
        // Retorna placeholder para imagens
        return caches.match('/assets/icons/mobile/placeholder.svg') || 
               new Response('', { status: 404 });
    }
}

// Network-Only: sempre busca da rede, nunca cacheia HTML
async function networkOnly(request) {
    try {
        return await fetch(request);
    } catch (error) {
        // Página offline para navegação
        if (request.mode === 'navigate') {
            return new Response(getOfflineHTML(), {
                headers: { 'Content-Type': 'text/html' }
            });
        }
        return new Response('Offline', { status: 503 });
    }
}

// Network-First com timeout
async function networkFirstWithTimeout(request, timeout) {
    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout);
        
        const response = await fetch(request, { signal: controller.signal });
        clearTimeout(timeoutId);
        
        if (response.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, response.clone());
            await limitCacheSize(DYNAMIC_CACHE, CACHE_LIMITS.dynamic);
        }
        
        return response;
    } catch (error) {
        console.log('[Mobile SW] Rede indisponível, tentando cache');
        
        const cached = await caches.match(request);
        if (cached) {
            return cached;
        }
        
        // Página offline para navegação
        if (request.mode === 'navigate') {
            return caches.match('/offline.html') || 
                   new Response(getOfflineHTML(), {
                       headers: { 'Content-Type': 'text/html' }
                   });
        }
        
        return new Response('Offline', { status: 503 });
    }
}

// Limitar tamanho do cache (FIFO)
async function limitCacheSize(cacheName, maxItems) {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();
    
    if (keys.length > maxItems) {
        const toDelete = keys.slice(0, keys.length - maxItems);
        await Promise.all(toDelete.map((key) => cache.delete(key)));
    }
}

// HTML de fallback offline
function getOfflineHTML() {
    return `
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline - Admin Mobile</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            padding: 20px;
        }
        .offline {
            text-align: center;
            max-width: 320px;
        }
        .offline svg {
            width: 80px;
            height: 80px;
            color: #9ca3af;
            margin-bottom: 20px;
        }
        .offline h1 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 8px;
        }
        .offline p {
            color: #6b7280;
            margin-bottom: 24px;
        }
        .offline button {
            background: #4361ee;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="offline">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="1" y1="1" x2="23" y2="23"/>
            <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/>
            <path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/>
            <path d="M10.71 5.05A16 16 0 0 1 22.58 9"/>
            <path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/>
            <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
            <line x1="12" y1="20" x2="12.01" y2="20"/>
        </svg>
        <h1>Você está offline</h1>
        <p>Verifique sua conexão com a internet e tente novamente.</p>
        <button onclick="location.reload()">Tentar novamente</button>
    </div>
</body>
</html>
    `;
}

// ========= PUSH NOTIFICATIONS - iOS Safari Compatible =========
self.addEventListener('push', (event) => {
    console.log('[Mobile SW] Push event received');
    
    if (!event.data) {
        console.log('[Mobile SW] Push event has no data');
        return;
    }

    let data;
    try {
        data = event.data.json();
    } catch (e) {
        data = {
            title: 'Novo Pedido',
            body: event.data.text() || 'Você tem uma nova notificação'
        };
    }
    
    console.log('[Mobile SW] Push data:', data);
    
    // Detectar iOS
    const isIOS = /iPhone|iPad|iPod/i.test(self.navigator?.userAgent || '');
    
    // Opções compatíveis com iOS Safari
    const options = {
        body: data.body || 'Novo pedido recebido!',
        icon: '/assets/icons/mobile/icon-192x192.png',
        badge: '/assets/icons/mobile/badge-72x72.png',
        tag: data.tag || 'order-notification',
        data: {
            url: data.data?.url || data.url || '/orders',
            orderId: data.data?.orderId || data.orderId,
            type: data.data?.type || data.type || 'notification'
        },
        silent: isIOS ? false : (data.silent || false)
    };
    
    // Adicionar opções extras apenas para não-iOS
    if (!isIOS) {
        options.vibrate = data.vibrate || [200, 100, 200];
        options.renotify = data.renotify !== undefined ? data.renotify : true;
        options.actions = data.actions || [
            { action: 'view', title: 'Ver Pedido' },
            { action: 'dismiss', title: 'Dispensar' }
        ];
    }

    event.waitUntil(
        self.registration.showNotification(data.title || 'Novo Pedido!', options)
    );
});

// Click na notificação - iOS Compatible
self.addEventListener('notificationclick', (event) => {
    console.log('[Mobile SW] Notification clicked:', event.notification.tag);
    event.notification.close();

    if (event.action === 'dismiss') return;

    const data = event.notification.data || {};
    
    // Mobile SW sempre usa URL mobile: /orders/show?id=X
    const orderId = data.orderId;
    let url = data.url || '/orders';
    
    // Garantir que a URL é para mobile
    if (orderId) {
        url = `/orders/show?id=${orderId}`;
    }
    
    const fullUrl = new URL(url, self.location.origin).href;
    console.log('[Mobile SW] Navigating to:', fullUrl);
    
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Se já tem uma janela aberta, foca nela
                for (const client of clientList) {
                    if (client.url.includes(self.location.origin) && 'focus' in client) {
                        return client.navigate(fullUrl).then(() => client.focus()).catch(() => client.focus());
                    }
                }
                // Senão, abre nova janela
                return clients.openWindow(fullUrl);
            })
    );
});

// ========= BACKGROUND SYNC =========
self.addEventListener('sync', (event) => {
    console.log('[Mobile SW] Background Sync:', event.tag);
    
    if (event.tag === 'sync-order-status') {
        event.waitUntil(syncOrderStatus());
    }
});

async function syncOrderStatus() {
    // Implementar sincronização de status de pedidos quando voltar online
    // Buscar do IndexedDB e enviar para API
    console.log('[Mobile SW] Sincronizando status de pedidos...');
}

console.log('[Mobile SW] Carregado com sucesso!');
