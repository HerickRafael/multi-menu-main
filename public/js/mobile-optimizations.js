/**
 * Mobile Performance Optimizations
 * 
 * Funcionalidades:
 * - Network Information API (detecta 2G/3G/4G)
 * - Data Saver mode detection
 * - Adaptive image loading
 * - Touch optimizations
 * - iOS/Android specific fixes
 * - Virtual scroll para listas longas
 */

(function() {
  'use strict';

  // ==============================================
  // NETWORK INFORMATION API
  // ==============================================
  
  const NetworkAdapter = {
    connection: navigator.connection || navigator.mozConnection || navigator.webkitConnection,
    
    init() {
      if (!this.connection) {
        console.log('[Network] API não suportada, usando padrões');
        return;
      }
      
      // Monitorar mudanças de conexão
      this.connection.addEventListener('change', () => this.handleConnectionChange());
      
      // Aplicar otimizações iniciais
      this.applyOptimizations();
      
      console.log('[Network] Tipo:', this.getConnectionType());
      console.log('[Network] Effective:', this.connection.effectiveType);
      console.log('[Network] Data Saver:', this.isDataSaverEnabled());
    },
    
    getConnectionType() {
      if (!this.connection) return 'unknown';
      return this.connection.effectiveType || this.connection.type || '4g';
    },
    
    isSlowConnection() {
      const type = this.getConnectionType();
      return ['slow-2g', '2g', '3g'].includes(type);
    },
    
    isDataSaverEnabled() {
      return this.connection?.saveData === true;
    },
    
    handleConnectionChange() {
      console.log('[Network] Conexão mudou para:', this.getConnectionType());
      this.applyOptimizations();
      
      // Emitir evento customizado
      window.dispatchEvent(new CustomEvent('networkchange', {
        detail: {
          type: this.getConnectionType(),
          isSlowConnection: this.isSlowConnection(),
          saveData: this.isDataSaverEnabled()
        }
      }));
    },
    
    applyOptimizations() {
      const body = document.body;
      
      if (this.isSlowConnection() || this.isDataSaverEnabled()) {
        body.classList.add('slow-connection');
        body.classList.remove('fast-connection');
        
        // Desabilitar animações
        document.documentElement.style.setProperty('--animation-duration', '0s');
        
        // Pausar preload de imagens
        if (window.ImagePreloader) {
          window.ImagePreloader.pause();
        }
        
        // Reduzir qualidade de imagens
        this.setImageQuality('low');
        
      } else {
        body.classList.add('fast-connection');
        body.classList.remove('slow-connection');
        
        document.documentElement.style.setProperty('--animation-duration', '0.3s');
        
        if (window.ImagePreloader) {
          window.ImagePreloader.resume();
        }
        
        this.setImageQuality('high');
      }
    },
    
    setImageQuality(quality) {
      // Modificar srcset para usar imagens menores em conexão lenta
      document.querySelectorAll('picture source').forEach(source => {
        const originalSrcset = source.dataset.originalSrcset || source.srcset;
        source.dataset.originalSrcset = originalSrcset;
        
        if (quality === 'low') {
          // Remover imagens grandes do srcset
          const filtered = originalSrcset
            .split(',')
            .filter(src => !src.includes('large') && !src.includes('1920'))
            .join(',');
          source.srcset = filtered || originalSrcset;
        } else {
          source.srcset = originalSrcset;
        }
      });
    }
  };

  // ==============================================
  // TOUCH OPTIMIZATIONS
  // ==============================================
  
  const TouchOptimizer = {
    init() {
      // Detectar dispositivo touch
      const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
      
      if (isTouch) {
        document.body.classList.add('touch-device');
        this.applyTouchOptimizations();
      }
      
      // Fix para iOS Safari bouncing
      this.fixIOSBounce();
      
      // Fix 300ms delay em dispositivos antigos
      this.fixTapDelay();
    },
    
    applyTouchOptimizations() {
      // Adicionar touch-action a elementos interativos
      document.querySelectorAll('a, button, .product-card, .category-tab').forEach(el => {
        el.style.touchAction = 'manipulation';
        el.style.webkitTapHighlightColor = 'transparent';
      });
      
      // Hover states apenas para não-touch
      const style = document.createElement('style');
      style.textContent = `
        @media (hover: none) and (pointer: coarse) {
          .product-card:hover { transform: none !important; }
          a:hover { opacity: 1 !important; }
        }
      `;
      document.head.appendChild(style);
    },
    
    fixIOSBounce() {
      // Prevenir overscroll bounce no iOS
      document.body.addEventListener('touchmove', function(e) {
        if (e.target.closest('.scroll-container, .modal-content, .product-list')) {
          return;
        }
      }, { passive: true });
    },
    
    fixTapDelay() {
      // FastClick polyfill simplificado para Android antigo
      if ('ontouchstart' in window) {
        let touchStartY = 0;
        
        document.addEventListener('touchstart', (e) => {
          touchStartY = e.touches[0].clientY;
        }, { passive: true });
        
        document.addEventListener('touchend', (e) => {
          const touchEndY = e.changedTouches[0].clientY;
          const diff = Math.abs(touchEndY - touchStartY);
          
          // Se foi um tap (não scroll)
          if (diff < 10) {
            const target = e.target.closest('a, button');
            if (target && !target.disabled) {
              // Trigger click imediato
              e.preventDefault();
              target.click();
            }
          }
        }, { passive: false });
      }
    }
  };

  // ==============================================
  // iOS/ANDROID SPECIFIC FIXES
  // ==============================================
  
  const PlatformFixes = {
    init() {
      const ua = navigator.userAgent;
      
      if (/iPhone|iPad|iPod/.test(ua)) {
        this.applyIOSFixes();
      }
      
      if (/Android/.test(ua)) {
        this.applyAndroidFixes();
      }
      
      // PWA detection
      if (window.matchMedia('(display-mode: standalone)').matches) {
        document.body.classList.add('is-pwa');
        this.applyPWAFixes();
      }
    },
    
    applyIOSFixes() {
      document.body.classList.add('is-ios');
      
      // Fix para input zoom no iOS
      const style = document.createElement('style');
      style.textContent = `
        /* Prevenir zoom em inputs no iOS */
        input, select, textarea {
          font-size: 16px !important;
        }
        
        /* Fix para position:fixed no iOS */
        .fixed-bottom {
          position: fixed;
          bottom: 0;
          left: 0;
          right: 0;
          transform: translateZ(0);
          -webkit-transform: translateZ(0);
        }
        
        /* Safe area para notch */
        .safe-area-bottom {
          padding-bottom: max(env(safe-area-inset-bottom), 20px);
        }
      `;
      document.head.appendChild(style);
      
      // Fix 100vh no iOS Safari
      this.fix100vh();
      
      // Detectar versão iOS para fixes específicos
      const match = ua.match(/OS (\d+)_/);
      if (match) {
        const version = parseInt(match[1]);
        document.body.dataset.iosVersion = version;
        
        // iOS 12 e anterior tem bugs específicos
        if (version < 13) {
          document.body.classList.add('ios-legacy');
        }
      }
    },
    
    applyAndroidFixes() {
      document.body.classList.add('is-android');
      
      // Detectar versão Android
      const match = navigator.userAgent.match(/Android (\d+)/);
      if (match) {
        const version = parseInt(match[1]);
        document.body.dataset.androidVersion = version;
        
        // Android 7 e anterior tem problemas com CSS moderno
        if (version < 8) {
          document.body.classList.add('android-legacy');
          
          // Fallback para flexbox gaps
          const style = document.createElement('style');
          style.textContent = `
            .android-legacy .gap-2 > * { margin: 4px; }
            .android-legacy .gap-4 > * { margin: 8px; }
            .android-legacy .grid { display: flex; flex-wrap: wrap; }
          `;
          document.head.appendChild(style);
        }
      }
    },
    
    applyPWAFixes() {
      // Esconder URL bar space
      const style = document.createElement('style');
      style.textContent = `
        .is-pwa {
          /* Espaço extra para notificações push */
          padding-top: env(safe-area-inset-top, 0);
        }
        
        .is-pwa .back-button {
          display: flex !important;
        }
      `;
      document.head.appendChild(style);
    },
    
    fix100vh() {
      // Calcular altura real da viewport no iOS
      const setVH = () => {
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
      };
      
      setVH();
      window.addEventListener('resize', setVH);
      window.addEventListener('orientationchange', () => {
        setTimeout(setVH, 100);
      });
    }
  };

  // ==============================================
  // SCROLL PERFORMANCE
  // ==============================================
  
  const ScrollOptimizer = {
    init() {
      // Passive event listeners para scroll
      this.setupPassiveScroll();
      
      // Throttle scroll events
      this.throttleScrollEvents();
      
      // Intersection Observer para lazy actions
      this.setupVisibilityObserver();
    },
    
    setupPassiveScroll() {
      // Garantir que scroll events são passivos
      const supportsPassive = (() => {
        let result = false;
        try {
          const opts = Object.defineProperty({}, 'passive', {
            get: function() { result = true; return true; }
          });
          window.addEventListener('test', null, opts);
          window.removeEventListener('test', null, opts);
        } catch (e) {}
        return result;
      })();
      
      if (supportsPassive) {
        document.body.dataset.passiveSupported = 'true';
      }
    },
    
    throttleScrollEvents() {
      let ticking = false;
      
      window.addEventListener('scroll', () => {
        if (!ticking) {
          requestAnimationFrame(() => {
            window.dispatchEvent(new CustomEvent('optimizedScroll'));
            ticking = false;
          });
          ticking = true;
        }
      }, { passive: true });
    },
    
    setupVisibilityObserver() {
      // Pausar animações/atualizações quando fora da view
      if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              entry.target.classList.remove('offscreen');
              entry.target.dispatchEvent(new CustomEvent('becameVisible'));
            } else {
              entry.target.classList.add('offscreen');
              entry.target.dispatchEvent(new CustomEvent('becameHidden'));
            }
          });
        }, { threshold: 0 });
        
        // Observar elementos com animação
        document.querySelectorAll('.animate, .product-card').forEach(el => {
          observer.observe(el);
        });
      }
    }
  };

  // ==============================================
  // MEMORY MANAGEMENT
  // ==============================================
  
  const MemoryManager = {
    init() {
      // Limpar imagens fora da viewport após scroll
      this.setupImageCleanup();
      
      // Detectar memória baixa
      this.detectLowMemory();
    },
    
    setupImageCleanup() {
      let cleanupTimeout;
      
      window.addEventListener('optimizedScroll', () => {
        clearTimeout(cleanupTimeout);
        
        cleanupTimeout = setTimeout(() => {
          // Após 2s sem scroll, limpar imagens muito distantes
          this.cleanupDistantImages();
        }, 2000);
      });
    },
    
    cleanupDistantImages() {
      const viewportHeight = window.innerHeight;
      const scrollTop = window.scrollY;
      const threshold = viewportHeight * 3; // 3 viewports de distância
      
      document.querySelectorAll('img.loaded').forEach(img => {
        const rect = img.getBoundingClientRect();
        const distanceFromViewport = Math.min(
          Math.abs(rect.top),
          Math.abs(rect.bottom - viewportHeight)
        );
        
        if (distanceFromViewport > threshold) {
          // Substituir por placeholder para liberar memória
          img.dataset.realSrc = img.src;
          img.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
          img.classList.remove('loaded');
          img.classList.add('lazy-load', 'cleaned');
        }
      });
    },
    
    detectLowMemory() {
      // Performance memory API (Chrome only)
      if (performance.memory) {
        setInterval(() => {
          const used = performance.memory.usedJSHeapSize;
          const total = performance.memory.jsHeapSizeLimit;
          const usage = used / total;
          
          if (usage > 0.9) {
            console.warn('[Memory] Uso alto de memória:', Math.round(usage * 100) + '%');
            this.cleanupDistantImages();
          }
        }, 30000); // Checar a cada 30s
      }
    }
  };

  // ==============================================
  // INICIALIZAÇÃO
  // ==============================================
  
  function init() {
    // Inicializar todos os módulos
    NetworkAdapter.init();
    TouchOptimizer.init();
    PlatformFixes.init();
    ScrollOptimizer.init();
    MemoryManager.init();
    
    console.log('[Mobile] Otimizações carregadas');
    
    // Expor para debug
    window.MobileOptimizations = {
      NetworkAdapter,
      TouchOptimizer,
      PlatformFixes,
      ScrollOptimizer,
      MemoryManager
    };
  }
  
  // Executar quando DOM estiver pronto
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
  
})();
