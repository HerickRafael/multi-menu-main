/**
 * Image Preload & Service Worker Manager
 * 
 * Funcionalidades:
 * - Registra Service Worker
 * - Preload de imagens críticas
 * - Prefetch inteligente (hover/scroll)
 * - Link prediction
 */

(function() {
  'use strict';

  // ==============================================
  // SERVICE WORKER REGISTRATION
  // ==============================================
  
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/sw.js')
        .then((registration) => {
          console.log('[PWA] Service Worker registered:', registration.scope);
          
          // Verificar atualizações a cada 1 hora
          setInterval(() => {
            registration.update();
          }, 3600000);
        })
        .catch((error) => {
          console.error('[PWA] Service Worker registration failed:', error);
        });
    });

    // Escutar mensagens do SW
    navigator.serviceWorker.addEventListener('message', (event) => {
      if (event.data && event.data.type === 'CACHE_UPDATED') {
        console.log('[PWA] Cache updated');
      }
    });
  }

  // ==============================================
  // PRELOAD DE IMAGENS CRÍTICAS
  // ==============================================

  class ImagePreloader {
    constructor() {
      this.preloadedUrls = new Set();
      this.prefetchQueue = [];
      this.isProcessing = false;
      
      this.init();
    }

    init() {
      // Preload automático de imagens above-the-fold
      this.preloadCriticalImages();
      
      // Setup hover prefetch
      this.setupHoverPrefetch();
      
      // Setup scroll prefetch
      this.setupScrollPrefetch();
      
      // Setup link prediction
      this.setupLinkPrediction();
    }

    /**
     * Preload imagens críticas (hero, logo, primeiros produtos)
     */
    preloadCriticalImages() {
      // Buscar <link rel="preload"> do servidor
      const preloadLinks = document.querySelectorAll('link[rel="preload"][as="image"]');
      preloadLinks.forEach((link) => {
        this.preload(link.href);
      });

      // Preload de imagens do primeiro viewport
      if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              const img = entry.target;
              const sources = img.parentElement?.querySelectorAll('source');
              
              // Preload de todas as sources do <picture>
              sources?.forEach((source) => {
                const srcset = source.srcset;
                if (srcset) {
                  const urls = this.parseSrcset(srcset);
                  urls.forEach(url => this.preload(url));
                }
              });
              
              observer.unobserve(img);
            }
          });
        }, {
          rootMargin: '200px' // Preload 200px antes de aparecer
        });

        // Observar imagens lazy
        document.querySelectorAll('img[loading="lazy"]').forEach((img) => {
          observer.observe(img);
        });
      }
    }

    /**
     * Prefetch ao passar o mouse (link/produto)
     */
    setupHoverPrefetch() {
      let hoverTimeout;

      document.addEventListener('mouseover', (e) => {
        const target = e.target.closest('a, .product-card');
        if (!target) return;

        // Aguardar 100ms para evitar prefetch acidental
        hoverTimeout = setTimeout(() => {
          // Link hover: prefetch da página
          if (target.tagName === 'A') {
            const url = target.href;
            if (url && url.startsWith(window.location.origin)) {
              this.prefetchPage(url);
            }
          }

          // Card hover: prefetch da imagem
          const img = target.querySelector('img');
          if (img) {
            const sources = target.querySelectorAll('source');
            sources.forEach((source) => {
              const srcset = source.srcset;
              if (srcset) {
                const urls = this.parseSrcset(srcset);
                // Prefetch apenas medium size no hover
                const mediumUrl = urls.find(u => u.includes('medium') || u.includes('600'));
                if (mediumUrl) {
                  this.prefetch(mediumUrl);
                }
              }
            });
          }
        }, 100);
      });

      document.addEventListener('mouseout', () => {
        clearTimeout(hoverTimeout);
      });
    }

    /**
     * Prefetch baseado em scroll (próximas seções)
     */
    setupScrollPrefetch() {
      if (!('IntersectionObserver' in window)) return;

      const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            // Elemento entrou no viewport, prefetch de imagens próximas
            const nextImages = this.getNextImages(entry.target, 5);
            nextImages.forEach((img) => {
              const picture = img.closest('picture');
              if (picture) {
                const sources = picture.querySelectorAll('source');
                sources.forEach((source) => {
                  const srcset = source.srcset;
                  if (srcset) {
                    const urls = this.parseSrcset(srcset);
                    urls.forEach(url => this.prefetch(url));
                  }
                });
              }
            });
          }
        });
      }, {
        rootMargin: '400px 0px' // 400px antes de entrar no viewport
      });

      // Observar seções
      document.querySelectorAll('.product-grid, .category-section').forEach((section) => {
        observer.observe(section);
      });
    }

    /**
     * Link prediction (analytics + ML simples)
     */
    setupLinkPrediction() {
      // Detectar padrão de navegação
      const navigationPattern = this.getNavigationPattern();
      
      // Prefetch de próximas páginas prováveis
      if (navigationPattern.nextLikely) {
        setTimeout(() => {
          this.prefetchPage(navigationPattern.nextLikely);
        }, 2000); // Aguardar 2s após load
      }
    }

    /**
     * Preload (alta prioridade)
     */
    preload(url) {
      if (this.preloadedUrls.has(url)) return;
      
      const link = document.createElement('link');
      link.rel = 'preload';
      link.as = 'image';
      link.href = url;
      document.head.appendChild(link);
      
      this.preloadedUrls.add(url);
      console.log('[Preload]', url);
    }

    /**
     * Prefetch (baixa prioridade, via Service Worker)
     */
    prefetch(url) {
      if (this.preloadedUrls.has(url)) return;
      
      if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
        navigator.serviceWorker.controller.postMessage({
          type: 'PREFETCH_IMAGES',
          urls: [url]
        });
      } else {
        // Fallback: fetch simples
        fetch(url, { mode: 'no-cors', priority: 'low' }).catch(() => {});
      }
      
      this.preloadedUrls.add(url);
      console.log('[Prefetch]', url);
    }

    /**
     * Prefetch de página inteira
     */
    prefetchPage(url) {
      if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
        navigator.serviceWorker.controller.postMessage({
          type: 'PREFETCH_PAGE',
          url: url
        });
      }
      console.log('[Prefetch Page]', url);
    }

    /**
     * Parse srcset para array de URLs
     */
    parseSrcset(srcset) {
      return srcset.split(',')
        .map(s => s.trim().split(' ')[0])
        .filter(Boolean);
    }

    /**
     * Pega próximas N imagens após elemento
     */
    getNextImages(element, count) {
      const allImages = Array.from(document.querySelectorAll('img'));
      const currentIndex = allImages.indexOf(element.querySelector('img'));
      
      if (currentIndex === -1) return [];
      
      return allImages.slice(currentIndex + 1, currentIndex + 1 + count);
    }

    /**
     * Detecta padrão de navegação (simple ML)
     */
    getNavigationPattern() {
      // Obter histórico do sessionStorage
      const history = JSON.parse(sessionStorage.getItem('navHistory') || '[]');
      
      // Salvar página atual
      const currentPath = window.location.pathname;
      history.push(currentPath);
      if (history.length > 10) history.shift(); // Manter últimas 10
      sessionStorage.setItem('navHistory', JSON.stringify(history));

      // Predição simples: se está em /produto/123, prefetch /cart
      if (currentPath.includes('/produto/')) {
        return { nextLikely: '/cart' };
      }

      // Se está no cart, prefetch /checkout
      if (currentPath.includes('/cart')) {
        return { nextLikely: '/checkout' };
      }

      return { nextLikely: null };
    }
  }

  // ==============================================
  // PERFORMANCE MONITORING
  // ==============================================

  class PerformanceMonitor {
    constructor() {
      this.metrics = {
        imagesLoaded: 0,
        imagesCached: 0,
        totalLoadTime: 0
      };

      this.init();
    }

    init() {
      if ('PerformanceObserver' in window) {
        // Monitorar carregamento de imagens
        const imgObserver = new PerformanceObserver((list) => {
          list.getEntries().forEach((entry) => {
            this.metrics.imagesLoaded++;
            this.metrics.totalLoadTime += entry.duration;

            // Detectar cache hit (muito rápido = cache)
            if (entry.duration < 50) {
              this.metrics.imagesCached++;
            }
          });
        });

        imgObserver.observe({ entryTypes: ['resource'] });

        // Log de métricas a cada 10s
        setInterval(() => {
          if (this.metrics.imagesLoaded > 0) {
            const cacheRate = (this.metrics.imagesCached / this.metrics.imagesLoaded * 100).toFixed(1);
            const avgTime = (this.metrics.totalLoadTime / this.metrics.imagesLoaded).toFixed(1);
            
            console.log(`[Performance] Images: ${this.metrics.imagesLoaded} | Cache hit: ${cacheRate}% | Avg load: ${avgTime}ms`);
          }
        }, 10000);
      }
    }
  }

  // ==============================================
  // INICIALIZAÇÃO
  // ==============================================

  window.addEventListener('DOMContentLoaded', () => {
    window.imagePreloader = new ImagePreloader();
    window.performanceMonitor = new PerformanceMonitor();
    
    console.log('[PWA] Image optimization system loaded');
  });

})();
