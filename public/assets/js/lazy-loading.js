/**
 * Sistema Centralizado de Lazy Loading para Imagens
 * Funciona tanto para área pública quanto admin
 * Uso: adicione class="lazy-load" e data-src="url" nas imagens
 */
(function() {
  'use strict';

  /**
   * Inicializa o lazy loading para todas as imagens com class="lazy-load"
   */
  function initLazyLoading() {
    const lazyImages = document.querySelectorAll('img.lazy-load');
    
    if (!lazyImages.length) {
      return;
    }

    // Verifica se o navegador suporta IntersectionObserver
    if ('IntersectionObserver' in window) {
      const imageObserver = new IntersectionObserver(function(entries, observer) {
        entries.forEach(function(entry) {
          if (entry.isIntersecting) {
            loadImage(entry.target);
            observer.unobserve(entry.target);
          }
        });
      }, {
        // Começar a carregar 50px antes da imagem aparecer
        rootMargin: '50px 0px',
        threshold: 0.01
      });

      lazyImages.forEach(function(img) {
        imageObserver.observe(img);
      });
    } else {
      // Fallback para navegadores antigos - carrega todas as imagens
      lazyImages.forEach(function(img) {
        loadImage(img);
      });
    }
  }

  /**
   * Carrega uma imagem e aplica transição suave
   */
  function loadImage(img) {
    const src = img.dataset.src || img.getAttribute('data-src');
    
    if (!src) {
      img.classList.add('lazy-loaded');
      return;
    }

    // Cria uma nova imagem para pré-carregar
    const tempImg = new Image();
    
    tempImg.onload = function() {
      img.src = src;
      img.classList.add('lazy-loaded');
      img.classList.remove('lazy-loading');
      
      // Remove o atributo data-src após carregar
      img.removeAttribute('data-src');
      
      // Dispara evento customizado para outras funcionalidades
      img.dispatchEvent(new CustomEvent('lazyloaded', {
        bubbles: true,
        detail: { src: src }
      }));
    };
    
    tempImg.onerror = function() {
      // Em caso de erro, marca como carregado mesmo assim
      img.classList.add('lazy-error');
      img.classList.remove('lazy-loading');
      
      // Se houver um fallback definido, usa ele
      const fallbackSrc = img.dataset.fallback || img.getAttribute('data-fallback');
      if (fallbackSrc && fallbackSrc !== src) {
        img.src = fallbackSrc;
      }
      
      img.dispatchEvent(new CustomEvent('lazyerror', {
        bubbles: true,
        detail: { src: src }
      }));
    };
    
    img.classList.add('lazy-loading');
    tempImg.src = src;
  }

  /**
   * Função pública para inicializar lazy loading em novos elementos
   * Útil para conteúdo carregado dinamicamente via AJAX
   */
  window.reinitLazyLoading = function(container) {
    const scope = container || document;
    const newLazyImages = scope.querySelectorAll('img.lazy-load:not(.lazy-loaded):not(.lazy-loading)');
    
    if (!newLazyImages.length) {
      return;
    }

    if ('IntersectionObserver' in window) {
      const imageObserver = new IntersectionObserver(function(entries, observer) {
        entries.forEach(function(entry) {
          if (entry.isIntersecting) {
            loadImage(entry.target);
            observer.unobserve(entry.target);
          }
        });
      }, {
        rootMargin: '50px 0px',
        threshold: 0.01
      });

      newLazyImages.forEach(function(img) {
        imageObserver.observe(img);
      });
    } else {
      newLazyImages.forEach(function(img) {
        loadImage(img);
      });
    }
  };

  // Inicializa quando o DOM estiver pronto
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLazyLoading);
  } else {
    initLazyLoading();
  }

  // Re-inicializa quando houver mudanças no DOM (útil para SPAs ou conteúdo dinâmico)
  if ('MutationObserver' in window) {
    let debounceTimer;
    const observer = new MutationObserver(function(mutations) {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(function() {
        window.reinitLazyLoading();
      }, 100);
    });

    // Observa mudanças no body
    if (document.body) {
      observer.observe(document.body, {
        childList: true,
        subtree: true
      });
    } else {
      document.addEventListener('DOMContentLoaded', function() {
        observer.observe(document.body, {
          childList: true,
          subtree: true
        });
      });
    }
  }
})();
