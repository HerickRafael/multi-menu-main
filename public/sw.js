/**
 * Service Worker Enterprise-Grade
 * 
 * Funcionalidades:
 * - Cache de imagens agressivo (1 ano)
 * - Prefetch inteligente
 * - Offline-first para imagens
 * - Network-first para HTML/API
 * - Preload de próximas páginas
 */

const CACHE_VERSION = 'v4';
const IMAGE_CACHE = `images-${CACHE_VERSION}`;
const STATIC_CACHE = `static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `dynamic-${CACHE_VERSION}`;

// Tamanho máximo dos caches
const MAX_IMAGE_CACHE_SIZE = 500; // 500 imagens
const MAX_DYNAMIC_CACHE_SIZE = 50; // 50 páginas

// Imagens críticas (preload automático)
const CRITICAL_IMAGES = [
  '/uploads/logo.png',
  '/uploads/banner.jpg'
];

// ==============================================
// INSTALAÇÃO
// ==============================================
self.addEventListener('install', (event) => {
  console.log('[SW] Installing Service Worker...');
  
  event.waitUntil(
    caches.open(STATIC_CACHE).then((cache) => {
      console.log('[SW] Pre-caching static assets');
      return cache.addAll([
        '/',
        '/assets/css/ui.css',
        '/assets/js/ui.js',
        '/js/lazy-load.js',
        ...CRITICAL_IMAGES
      ]);
    }).then(() => {
      console.log('[SW] Installation complete');
      return self.skipWaiting(); // Ativa imediatamente
    })
  );
});

// ==============================================
// ATIVAÇÃO
// ==============================================
self.addEventListener('activate', (event) => {
  console.log('[SW] Activating Service Worker...');
  
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          // Remover caches antigos
          if (cacheName !== IMAGE_CACHE && 
              cacheName !== STATIC_CACHE && 
              cacheName !== DYNAMIC_CACHE) {
            console.log('[SW] Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      console.log('[SW] Activation complete');
      return self.clients.claim(); // Controla todas as páginas
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

  // Super Admin: sempre network-first para evitar bundle stale
  if (url.pathname.startsWith('/superadmin')) {
    event.respondWith(networkFirstStrategy(request, DYNAMIC_CACHE));
    return;
  }

  // Ignorar requisições de outros domínios (exceto CDN)
  const cdnDomain = self.registration.scope.match(/https?:\/\/[^\/]+/)?.[0];
  if (url.origin !== self.location.origin && url.origin !== cdnDomain) {
    return;
  }

  // ESTRATÉGIA 1: IMAGENS (Cache-First + CDN)
  if (isImageRequest(request)) {
    event.respondWith(cacheFirstStrategy(request, IMAGE_CACHE));
    
    // Prefetch inteligente: carregar próximas imagens
    event.waitUntil(prefetchNearbyImages(request));
    return;
  }

  // ESTRATÉGIA 2: ASSETS ESTÁTICOS (Cache-First)
  if (isStaticAsset(request)) {
    event.respondWith(cacheFirstStrategy(request, STATIC_CACHE));
    return;
  }

  // ESTRATÉGIA 3: API/HTML (Network-First)
  if (isApiRequest(request) || isHtmlRequest(request)) {
    event.respondWith(networkFirstStrategy(request, DYNAMIC_CACHE));
    return;
  }

  // ESTRATÉGIA 4: PADRÃO (Network-First)
  event.respondWith(networkFirstStrategy(request, DYNAMIC_CACHE));
});

// ==============================================
// ESTRATÉGIAS DE CACHE
// ==============================================

/**
 * Cache-First: Tenta cache, depois rede
 * Ideal para: Imagens, CSS, JS
 */
async function cacheFirstStrategy(request, cacheName) {
  try {
    const cachedResponse = await caches.match(request);
    
    if (cachedResponse) {
      console.log('[SW] Cache hit:', request.url);
      return cachedResponse;
    }

    console.log('[SW] Cache miss, fetching:', request.url);
    const networkResponse = await fetch(request);

    // Salvar no cache se sucesso
    if (networkResponse && networkResponse.status === 200) {
      const cache = await caches.open(cacheName);
      cache.put(request, networkResponse.clone());
      
      // Limitar tamanho do cache
      if (cacheName === IMAGE_CACHE) {
        await limitCacheSize(cacheName, MAX_IMAGE_CACHE_SIZE);
      }
    }

    return networkResponse;

  } catch (error) {
    console.error('[SW] Fetch failed:', error);
    
    // Fallback: tentar cache mesmo que seja antigo
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }

    // Último recurso: placeholder SVG
    if (isImageRequest(request)) {
      return generatePlaceholderImage();
    }

    throw error;
  }
}

/**
 * Network-First: Tenta rede, depois cache
 * Ideal para: HTML, API
 */
async function networkFirstStrategy(request, cacheName) {
  try {
    const networkResponse = await fetch(request);

    // Salvar no cache se sucesso
    if (networkResponse && networkResponse.status === 200) {
      const cache = await caches.open(cacheName);
      cache.put(request, networkResponse.clone());
      
      await limitCacheSize(cacheName, MAX_DYNAMIC_CACHE_SIZE);
    }

    return networkResponse;

  } catch (error) {
    console.log('[SW] Network failed, trying cache:', request.url);
    
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }

    throw error;
  }
}

// ==============================================
// PREFETCH INTELIGENTE
// ==============================================

/**
 * Prefetch de imagens próximas (scroll prediction)
 */
async function prefetchNearbyImages(request) {
  // TODO: Implementar lógica de predição
  // Por enquanto, apenas cacheia a imagem atual
  return Promise.resolve();
}

/**
 * Prefetch de próxima página (link prediction)
 */
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'PREFETCH_PAGE') {
    const url = event.data.url;
    console.log('[SW] Prefetching page:', url);
    
    fetch(url, { mode: 'no-cors' }).then((response) => {
      if (response && response.status === 200) {
        caches.open(DYNAMIC_CACHE).then((cache) => {
          cache.put(url, response);
        });
      }
    }).catch((err) => {
      console.error('[SW] Prefetch failed:', err);
    });
  }

  // Prefetch de imagens
  if (event.data && event.data.type === 'PREFETCH_IMAGES') {
    const urls = event.data.urls;
    console.log('[SW] Prefetching images:', urls.length);
    
    urls.forEach((url) => {
      fetch(url, { mode: 'no-cors' }).then((response) => {
        if (response && response.status === 200) {
          caches.open(IMAGE_CACHE).then((cache) => {
            cache.put(url, response);
          });
        }
      }).catch(() => {});
    });
  }
});

// ==============================================
// UTILITÁRIOS
// ==============================================

function isImageRequest(request) {
  return request.destination === 'image' || 
         /\.(jpg|jpeg|png|gif|webp|avif|svg)$/i.test(request.url);
}

function isStaticAsset(request) {
  return /\.(css|js|woff|woff2|ttf)$/i.test(request.url);
}

function isApiRequest(request) {
  return request.url.includes('/api/');
}

function isHtmlRequest(request) {
  return request.destination === 'document' || 
         request.headers.get('accept')?.includes('text/html');
}

async function limitCacheSize(cacheName, maxSize) {
  const cache = await caches.open(cacheName);
  const keys = await cache.keys();
  
  if (keys.length > maxSize) {
    // Remover mais antigos (FIFO)
    const toDelete = keys.slice(0, keys.length - maxSize);
    await Promise.all(toDelete.map(key => cache.delete(key)));
    console.log(`[SW] Cleaned ${toDelete.length} items from ${cacheName}`);
  }
}

function generatePlaceholderImage() {
  const svg = `
    <svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">
      <rect fill="#f3f4f6" width="400" height="300"/>
      <text x="200" y="150" font-family="Arial" font-size="14" fill="#9ca3af" text-anchor="middle">
        Imagem offline
      </text>
    </svg>
  `;
  
  return new Response(svg, {
    headers: { 'Content-Type': 'image/svg+xml' }
  });
}

// ==============================================
// BACKGROUND SYNC (para analytics offline)
// ==============================================
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-analytics') {
    console.log('[SW] Background sync triggered');
    event.waitUntil(syncAnalytics());
  }
});

async function syncAnalytics() {
  // TODO: Enviar analytics acumulados durante offline
  return Promise.resolve();
}

console.log('[SW] Service Worker loaded successfully');
