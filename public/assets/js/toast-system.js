/**
 * ============================================================================
 * SISTEMA DE NOTIFICAÇÕES TOAST - Sistema centralizado
 * ============================================================================
 */

window.ToastSystem = (function() {
  'use strict';

  // Configurações padrão
  const defaults = {
    duration: 4000,
    position: 'top-right',
    maxToasts: 5,
    animationDuration: 300,
    stackOffset: 10
  };

  // Container para toasts
  let toastContainer = null;
  let toastQueue = [];
  let toastCounter = 0;

  // Tipos de toast predefinidos
  const toastTypes = {
    success: {
      icon: `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
      </svg>`,
      className: 'bg-green-50 border-green-200 text-green-800',
      iconColor: 'text-green-400'
    },
    error: {
      icon: `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
      </svg>`,
      className: 'bg-red-50 border-red-200 text-red-800',
      iconColor: 'text-red-400'
    },
    warning: {
      icon: `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
      </svg>`,
      className: 'bg-yellow-50 border-yellow-200 text-yellow-800',
      iconColor: 'text-yellow-400'
    },
    info: {
      icon: `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
      </svg>`,
      className: 'bg-blue-50 border-blue-200 text-blue-800',
      iconColor: 'text-blue-400'
    }
  };

  /**
   * Inicializar container de toasts
   */
  function initToastContainer() {
    if (toastContainer) return;

    toastContainer = document.createElement('div');
    toastContainer.id = 'toast-container';
    toastContainer.className = `fixed z-50 p-4 space-y-${defaults.stackOffset/4} pointer-events-none`;
    
    // Posicionamento baseado na configuração
    const positions = {
      'top-right': 'top-4 right-4',
      'top-left': 'top-4 left-4',
      'top-center': 'top-4 left-1/2 transform -translate-x-1/2',
      'bottom-right': 'bottom-4 right-4',
      'bottom-left': 'bottom-4 left-4',
      'bottom-center': 'bottom-4 left-1/2 transform -translate-x-1/2'
    };
    
    toastContainer.className += ` ${positions[defaults.position] || positions['top-right']}`;
    document.body.appendChild(toastContainer);
  }

  /**
   * Criar elemento de toast
   */
  function createToastElement(message, type = 'info', options = {}) {
    const toastId = `toast-${++toastCounter}`;
    const typeConfig = toastTypes[type] || toastTypes.info;
    
    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = `
      relative w-auto ${typeConfig.className} border rounded-lg shadow-lg p-4 
      pointer-events-auto transform transition-all duration-${defaults.animationDuration} 
      ease-in-out translate-x-full opacity-0
    `.replace(/\s+/g, ' ').trim();
    
    // Adicionar estilos inline para garantir que não quebre
    toast.style.whiteSpace = 'nowrap';
    toast.style.minWidth = 'max-content';
    toast.style.maxWidth = '90vw';

    toast.innerHTML = `
      <div class="flex items-center">
        <div class="flex-shrink-0 ${typeConfig.iconColor}">
          ${typeConfig.icon}
        </div>
        <div class="ml-3 flex-1">
          <p class="text-sm font-medium whitespace-nowrap">${message}</p>
        </div>
        <div class="ml-4 flex-shrink-0 flex">
          <button class="inline-flex ${typeConfig.iconColor} hover:opacity-75 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-50 focus:ring-blue-600 rounded-md" onclick="ToastSystem.dismiss('${toastId}')">
            <span class="sr-only">Fechar</span>
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
          </button>
        </div>
      </div>
    `;

    return toast;
  }

  /**
   * Mostrar toast
   */
  function show(message, type = 'info', options = {}) {
    initToastContainer();
    
    // Limitar número de toasts
    if (toastQueue.length >= defaults.maxToasts) {
      const oldestToast = toastQueue.shift();
      dismiss(oldestToast.id);
    }

    const toast = createToastElement(message, type, options);
    const duration = options.duration || defaults.duration;

    toastContainer.appendChild(toast);
    toastQueue.push({ id: toast.id, element: toast });

    // Animar entrada
    requestAnimationFrame(() => {
      toast.classList.remove('translate-x-full', 'opacity-0');
      toast.classList.add('translate-x-0', 'opacity-100');
    });

    // Auto-dismiss se duração for definida
    if (duration > 0) {
      setTimeout(() => dismiss(toast.id), duration);
    }

    return toast.id;
  }

  /**
   * Dispensar toast
   */
  function dismiss(toastId) {
    const toastIndex = toastQueue.findIndex(t => t.id === toastId);
    if (toastIndex === -1) return;

    const toast = toastQueue[toastIndex].element;
    
    // Animar saída
    toast.classList.remove('translate-x-0', 'opacity-100');
    toast.classList.add('translate-x-full', 'opacity-0');

    setTimeout(() => {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
      toastQueue.splice(toastIndex, 1);
    }, defaults.animationDuration);
  }

  /**
   * Dispensar todos os toasts
   */
  function dismissAll() {
    toastQueue.forEach(toast => dismiss(toast.id));
  }

  /**
   * Funções de conveniência para tipos específicos
   */
  function success(message, options = {}) {
    return show(message, 'success', options);
  }

  function error(message, options = {}) {
    return show(message, 'error', options);
  }

  function warning(message, options = {}) {
    return show(message, 'warning', options);
  }

  function info(message, options = {}) {
    return show(message, 'info', options);
  }

  /**
   * Função compatível com toast() legado
   */
  function toast(message, type = 'success') {
    return show(message, type);
  }

  // Interface pública
  return {
    show,
    dismiss,
    dismissAll,
    success,
    error,
    warning,
    info,
    toast, // Compatibilidade com código legado
    
    // Configurações
    setDefaults(newDefaults) {
      Object.assign(defaults, newDefaults);
    },
    
    getDefaults() {
      return { ...defaults };
    }
  };
})();

/**
 * Integração com admin-common.js
 */
if (window.AdminCommon) {
  // Adicionar toast system ao AdminCommon
  window.AdminCommon.showToast = window.ToastSystem.show;
  window.AdminCommon.ToastSystem = window.ToastSystem;
}

/**
 * Função toast global para compatibilidade
 */
window.toast = window.ToastSystem.toast;