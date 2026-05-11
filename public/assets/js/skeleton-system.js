/**
 * ============================================================================
 * SKELETON LOADING SYSTEM - Funcionalidades centralizadas
 * ============================================================================
 */

window.SkeletonSystem = (function() {
  'use strict';

  // Configurações padrão
  const defaults = {
    staggerDelay: 150,
    revealDuration: 500,
    minLoadingTime: 1200,
    maxLoadingTime: 6000,
    animationEasing: 'cubic-bezier(0.16, 1, 0.3, 1)'
  };

  // Utilitário para seleção de elementos
  function el(selector) {
    return typeof selector === 'string' ? document.getElementById(selector) : selector;
  }

  // Utilitário para seleção múltipla
  function $(selector) {
    return document.querySelectorAll(selector);
  }

  /**
   * Classe principal para gerenciar skeleton loading
   */
  class SkeletonLoader {
    constructor(elements = {}) {
      this.elements = elements;
      this.isLoading = false;
    }

    // Mostrar skeleton loading
    show() {
      this.isLoading = true;
      
      Object.values(this.elements).forEach(({ skeleton, content }) => {
        const skeletonEl = el(skeleton);
        const contentEl = el(content);
        
        if (skeletonEl && contentEl) {
          contentEl.classList.add('hidden');
          skeletonEl.classList.remove('hidden');
        }
      });
    }

    // Esconder skeleton com reveal progressivo
    hide() {
      if (!this.isLoading) return;
      
      this.isLoading = false;
      let currentDelay = 0;
      
      Object.entries(this.elements).forEach(([key, { skeleton, content }]) => {
        setTimeout(() => {
          this.revealElement(skeleton, content, this.getDisplayType(key));
        }, currentDelay);
        currentDelay += defaults.staggerDelay;
      });
    }

    // Revelar elemento individual
    revealElement(skeletonId, contentId, displayType = 'block') {
      const skeleton = el(skeletonId);
      const content = el(contentId);
      
      if (!skeleton || !content) return;
      
      // Esconder skeleton
      skeleton.classList.add('hidden');
      
      // Mostrar conteúdo
      content.classList.remove('hidden');
      if (displayType !== 'block') {
        content.style.display = displayType;
      }
      
      // Aplicar animação de reveal
      this.smoothRevealElement(content);
    }

    // Animação suave de revelação
    smoothRevealElement(element, delay = 0) {
      if (!element) return;
      
      setTimeout(() => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = `all ${defaults.revealDuration}ms ${defaults.animationEasing}`;
        
        // Trigger reflow
        element.offsetHeight;
        
        element.style.opacity = '1';
        element.style.transform = 'translateY(0)';
        
        // Limpar estilos após animação
        setTimeout(() => {
          element.style.opacity = '';
          element.style.transform = '';
          element.style.transition = '';
        }, defaults.revealDuration);
      }, delay);
    }

    // Determinar tipo de display baseado na chave do elemento
    getDisplayType(key) {
      const types = {
        stats: 'contents',
        info: 'grid',
        settings: 'block',
        header: 'block'
      };
      return types[key] || 'block';
    }

    // Revelar cards de estatísticas com animação especial
    revealStatsCards() {
      const skeleton = el(this.elements.stats?.skeleton);
      const content = el(this.elements.stats?.content);
      
      if (!skeleton || !content) return;
      
      skeleton.style.display = 'none';
      content.classList.remove('hidden');
      content.style.display = 'contents';
      
      const cards = content.querySelectorAll('.stat-card');
      cards.forEach((card, index) => {
        setTimeout(() => {
          card.style.transition = `all 0.6s ${defaults.animationEasing}`;
          card.style.opacity = '1';
          card.style.transform = 'translateY(0)';
          
          // Micro-bounce no final
          setTimeout(() => {
            card.style.transform = 'translateY(-2px)';
            setTimeout(() => {
              card.style.transform = 'translateY(0)';
            }, 150);
          }, 400);
        }, index * 150);
      });
    }
  }

  /**
   * Sistema de loading com indicadores de progresso
   */
  class PageLoader {
    constructor(options = {}) {
      this.options = { ...defaults, ...options };
      this.progressEl = null;
      this.textEl = null;
    }

    // Configurar elementos de progresso
    setProgressElements(progressSelector, textSelector) {
      this.progressEl = el(progressSelector);
      this.textEl = el(textSelector);
    }

    // Mostrar indicador de progresso
    showProgress(text = 'Carregando...') {
      if (this.progressEl && this.textEl) {
        this.textEl.textContent = text;
        this.progressEl.classList.remove('hidden');
        this.progressEl.classList.add('flex');
      }
    }

    // Esconder indicador de progresso
    hideProgress() {
      if (this.progressEl) {
        this.progressEl.classList.add('hidden');
        this.progressEl.classList.remove('flex');
      }
    }

    // Garantir tempo mínimo de loading
    ensureMinimumLoadingTime(startTime) {
      const elapsed = Date.now() - startTime;
      const remaining = Math.max(0, this.options.minLoadingTime - elapsed);
      return new Promise(resolve => setTimeout(resolve, remaining));
    }

    // Criar timeout promise
    createTimeoutPromise() {
      return new Promise((_, reject) => {
        setTimeout(() => reject(new Error('Loading timeout')), this.options.maxLoadingTime);
      });
    }
  }

  /**
   * Sistema de estados visuais e micro-interações
   */
  class VisualStates {
    // Aplicar animação de revelação
    static revealWithAnimation(element, animation = 'fadeInScale') {
      if (!element) return;
      
      element.style.animation = `${animation} 0.4s ${defaults.animationEasing}`;
      element.addEventListener('animationend', () => {
        element.style.animation = '';
      }, { once: true });
    }

    // Bounce effect para feedback positivo
    static bounce(element) {
      if (!element) return;
      
      element.style.transform = 'scale(1.05)';
      element.style.transition = 'transform 0.15s ease-out';
      setTimeout(() => {
        element.style.transform = 'scale(1)';
      }, 150);
    }

    // Shake effect para erros
    static shake(element) {
      if (!element) return;
      
      element.classList.add('shake');
      setTimeout(() => {
        element.classList.remove('shake');
      }, 500);
    }

    // Aplicar micro-feedback a botões
    static enhanceButtons() {
      $('button').forEach(button => {
        if (button.hasAttribute('data-skeleton-enhanced')) return;
        
        button.setAttribute('data-skeleton-enhanced', 'true');
        button.addEventListener('click', () => {
          this.bounce(button);
        });
      });
    }
  }

  /**
   * Utilitários para skeleton loading compatível com admin-common.js
   */
  const SkeletonUtils = {
    // Aplicar skeleton a elemento
    showSkeletonElement(element) {
      if (!element) return;
      
      element.style.opacity = '0';
      element.style.transform = 'translateY(10px)';
      element.classList.add('skeleton-basic');
    },

    // Remover skeleton de elemento
    hideSkeletonElement(element, delay = 0) {
      if (!element) return;
      
      setTimeout(() => {
        element.classList.remove('skeleton-basic');
        element.style.transition = `all ${defaults.revealDuration}ms ease-in-out`;
        element.style.opacity = '1';
        element.style.transform = 'translateY(0)';
      }, delay);
    },

    // Reveal suave para múltiplos elementos
    smoothReveal(elements, staggerDelay = 100) {
      if (!Array.isArray(elements)) elements = [elements];
      
      elements.forEach((element, index) => {
        if (element) {
          this.hideSkeletonElement(element, index * staggerDelay);
        }
      });
    }
  };

  /**
   * Factory para criar skeleton loaders específicos
   */
  function createSkeletonLoader(elements) {
    return new SkeletonLoader(elements);
  }

  function createPageLoader(options) {
    return new PageLoader(options);
  }

  // Interface pública
  return {
    SkeletonLoader,
    PageLoader,
    VisualStates,
    SkeletonUtils,
    createSkeletonLoader,
    createPageLoader,
    defaults,
    
    // Funções de conveniência para compatibilidade
    showSkeletonLoading: (elements) => {
      const loader = new SkeletonLoader(elements);
      loader.show();
      return loader;
    },
    
    hideSkeletonLoading: (loader) => {
      if (loader && loader.hide) {
        loader.hide();
      }
    }
  };
})();

/**
 * Integração com admin-common.js
 */
if (window.AdminCommon) {
  // Estender AdminCommon com funcionalidades de skeleton
  Object.assign(window.AdminCommon, {
    SkeletonSystem: window.SkeletonSystem,
    showSkeletonElement: window.SkeletonSystem.SkeletonUtils.showSkeletonElement,
    hideSkeletonElement: window.SkeletonSystem.SkeletonUtils.hideSkeletonElement,
    smoothReveal: window.SkeletonSystem.SkeletonUtils.smoothReveal
  });
}

/**
 * Auto-inicialização para páginas com data-skeleton-auto
 */
document.addEventListener('DOMContentLoaded', () => {
  if (document.body.hasAttribute('data-skeleton-auto')) {
    window.SkeletonSystem.VisualStates.enhanceButtons();
  }
});