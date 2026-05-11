/**
 * Admin Common JavaScript Functions
 * Arquivo centralizado para reutilização de código JavaScript comum no admin
 * 
 * NOTA: Este arquivo foi refatorado para usar sistemas centralizados.
 * Toast System: /assets/js/toast-system.js
 * Skeleton System: /assets/js/skeleton-system.js
 */

// =============================================================================
// TOAST/NOTIFICATION SYSTEM (usando ToastSystem centralizado)
// =============================================================================

/**
 * Exibe uma notificação toast unificada
 * @param {string} message - Mensagem a ser exibida
 * @param {string} type - Tipo: 'info', 'success', 'warn', 'error'
 * @param {number} duration - Duração em ms (padrão: 5000)
 */
function showToast(message, type = 'info', duration = 5000) {
  // Usar ToastSystem centralizado se disponível
  if (window.ToastSystem) {
    return window.ToastSystem.show(message, type === 'warn' ? 'warning' : type, { duration });
  }
  
  // Fallback para compatibilidade (versão simplificada)
  console.log(`Toast: ${message} (${type})`);
}

// =============================================================================
// STATUS SYSTEM
// =============================================================================

/**
 * Mapeia status para classes CSS unificadas
 * @param {string} status - Status original
 * @returns {string} Classe CSS correspondente
 */
function getStatusClass(status) {
  const statusMap = {
    'connected': 'status-connected',
    'open': 'status-connected',
    'conectado': 'status-connected',
    'concluido': 'status-connected',
    'completed': 'status-connected',
    'delivered': 'status-connected',
    
    'connecting': 'status-connecting',
    'preparando': 'status-connecting',
    'preparing': 'status-connecting',
    
    'pending': 'status-pending',
    'pendente': 'status-pending',
    'novo': 'status-pending',
    'waiting': 'status-pending',
    
    'disconnected': 'status-disconnected',
    'close': 'status-disconnected',
    'desconectado': 'status-disconnected',
    'cancelado': 'status-disconnected',
    'cancelled': 'status-disconnected',
    'error': 'status-error'
  };
  
  return statusMap[status?.toLowerCase()] || 'status-pending';
}

/**
 * Mapeia status para texto exibido
 * @param {string} status - Status original
 * @returns {string} Texto para exibição
 */
function getStatusText(status) {
  const textMap = {
    'open': 'Conectado',
    'connected': 'Conectado',
    'connecting': 'Conectando',
    'pending': 'Pendente',
    'close': 'Desconectado',
    'disconnected': 'Desconectado',
    'error': 'Erro',
    'completed': 'Concluído',
    'delivered': 'Entregue',
    'preparing': 'Preparando',
    'cancelled': 'Cancelado',
    'novo': 'Novo',
    'preparando': 'Preparando',
    'concluido': 'Concluído',
    'cancelado': 'Cancelado'
  };
  
  return textMap[status?.toLowerCase()] || 
         (status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Indefinido');
}

/**
 * Cria um elemento de status pill unificado
 * @param {string} status - Status
 * @param {string} customText - Texto customizado (opcional)
 * @param {boolean} showDot - Mostrar ponto indicador (padrão: true)
 * @returns {string} HTML do status pill
 */
function createStatusPill(status, customText = null, showDot = true) {
  const statusClass = getStatusClass(status);
  const displayText = customText || getStatusText(status);
  const dot = showDot ? '<span class="status-dot"></span>' : '';
  
  return `<span class="status-pill ${statusClass}">${dot}${displayText}</span>`;
}

// =============================================================================
// API/FETCH UTILITIES
// =============================================================================

/**
 * Realiza requisição POST com JSON
 * @param {string} url - URL do endpoint
 * @param {object} body - Dados para enviar
 * @param {object} options - Opções adicionais
 * @returns {Promise<object>} Resposta em JSON
 */
async function postJson(url, body = {}, options = {}) {
  const csrfToken = window._csrfToken || (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  const defaultOptions = {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify(body)
  };

  try {
    const response = await fetch(url, { ...defaultOptions, ...options });
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    return await response.json();
  } catch (error) {
    console.error('Erro na requisição POST:', error);
    throw error;
  }
}

/**
 * Realiza requisição GET com tratamento de erro
 * @param {string} url - URL do endpoint
 * @param {object} options - Opções adicionais
 * @returns {Promise<object>} Resposta em JSON
 */
async function getJson(url, options = {}) {
  const defaultOptions = {
    method: 'GET',
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }
  };

  try {
    const response = await fetch(url, { ...defaultOptions, ...options });
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    return await response.json();
  } catch (error) {
    console.error('Erro na requisição GET:', error);
    throw error;
  }
}

// =============================================================================
// DOM UTILITIES
// =============================================================================

/**
 * Seletor de elemento mais conciso
 * @param {string} selector - Seletor CSS ou ID
 * @returns {Element|null} Elemento encontrado
 */
function $(selector) {
  return selector.startsWith('#') ? 
    document.getElementById(selector.slice(1)) : 
    document.querySelector(selector);
}

/**
 * Seletor de múltiplos elementos
 * @param {string} selector - Seletor CSS
 * @returns {NodeList} Lista de elementos
 */
function $$(selector) {
  return document.querySelectorAll(selector);
}

/**
 * Aguarda elemento aparecer no DOM
 * @param {string} selector - Seletor do elemento
 * @param {number} timeout - Timeout em ms (padrão: 5000)
 * @returns {Promise<Element>} Elemento encontrado
 */
function waitForElement(selector, timeout = 5000) {
  return new Promise((resolve, reject) => {
    const element = $(selector);
    if (element) {
      resolve(element);
      return;
    }

    const observer = new MutationObserver(() => {
      const element = $(selector);
      if (element) {
        observer.disconnect();
        resolve(element);
      }
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true
    });

    setTimeout(() => {
      observer.disconnect();
      reject(new Error(`Elemento ${selector} não encontrado após ${timeout}ms`));
    }, timeout);
  });
}

// =============================================================================
// LOADING STATES
// =============================================================================

/**
 * Adiciona estado de loading a um botão
 * @param {Element|string} button - Elemento ou seletor do botão
 * @param {string} loadingText - Texto durante loading (opcional)
 * @returns {function} Função para remover o loading
 */
function setButtonLoading(button, loadingText = 'Carregando') {
  const btn = typeof button === 'string' ? $(button) : button;
  if (!btn) return () => {};

  const originalHtml = btn.innerHTML;
  const originalDisabled = btn.disabled;

  btn.disabled = true;
  btn.innerHTML = `
    <svg class="inline h-4 w-4 loading-refresh-icon mr-2" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
      <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"></path>
      <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"></path>
    </svg>
    ${loadingText}
  `;

  // Função para remover o loading
  return () => {
    btn.innerHTML = originalHtml;
    btn.disabled = originalDisabled;
  };
}

/**
 * Mostra/esconde indicador de loading automático
 * @param {Element|string} indicator - Elemento indicador
 * @param {boolean} show - Mostrar ou esconder
 */
function toggleAutoIndicator(indicator, show = true) {
  const elem = typeof indicator === 'string' ? $(indicator) : indicator;
  if (!elem) return;

  if (show) {
    elem.classList.remove('hidden');
    elem.classList.add('flex');
  } else {
    elem.classList.add('hidden');
    elem.classList.remove('flex');
  }
}

// =============================================================================
// FORM UTILITIES
// =============================================================================

/**
 * Converte FormData para objeto
 * @param {FormData} formData - Dados do formulário
 * @returns {object} Objeto com dados do formulário
 */
function formDataToObject(formData) {
  const obj = {};
  for (const [key, value] of formData.entries()) {
    if (obj[key]) {
      // Se já existe, transformar em array
      obj[key] = Array.isArray(obj[key]) ? obj[key] : [obj[key]];
      obj[key].push(value);
    } else {
      obj[key] = value;
    }
  }
  return obj;
}

/**
 * Submete formulário via AJAX
 * @param {Element|string} form - Formulário ou seletor
 * @param {string} url - URL de destino (opcional, usa action do form)
 * @param {function} onSuccess - Callback de sucesso
 * @param {function} onError - Callback de erro
 */
async function submitFormAjax(form, url = null, onSuccess = null, onError = null) {
  const formElement = typeof form === 'string' ? $(form) : form;
  if (!formElement) return;

  const formData = new FormData(formElement);
  const submitUrl = url || formElement.action;
  const submitButton = formElement.querySelector('[type="submit"]');
  
  const removeLoading = submitButton ? setButtonLoading(submitButton, 'Enviando') : () => {};

  try {
    const response = await postJson(submitUrl, formDataToObject(formData));
    
    if (response.error) {
      throw new Error(response.error);
    }
    
    if (onSuccess) {
      onSuccess(response);
    } else {
      showToast('Operação realizada com sucesso!', 'success');
    }
    
  } catch (error) {
    console.error('Erro no formulário:', error);
    
    if (onError) {
      onError(error);
    } else {
      showToast(error.message || 'Erro ao processar formulário', 'error');
    }
  } finally {
    removeLoading();
  }
}

// =============================================================================
// CLIPBOARD UTILITIES
// =============================================================================

/**
 * Copia texto para área de transferência
 * @param {string} text - Texto para copiar
 * @param {string} successMessage - Mensagem de sucesso
 */
async function copyToClipboard(text, successMessage = 'Copiado para área de transferência!') {
  try {
    await navigator.clipboard.writeText(text);
    showToast(successMessage, 'success');
  } catch (error) {
    console.error('Erro ao copiar:', error);
    showToast('Não foi possível copiar', 'error');
  }
}

// =============================================================================
// REFRESH/POLLING UTILITIES
// =============================================================================

/**
 * Classe para gerenciar refresh automático
 */
class AutoRefresh {
  constructor(refreshFunction, interval = 30000) {
    this.refreshFunction = refreshFunction;
    this.interval = interval;
    this.intervalId = null;
    this.isActive = false;
  }

  start() {
    if (this.isActive) return;
    
    this.isActive = true;
    this.intervalId = setInterval(() => {
      if (document.visibilityState === 'visible') {
        this.refreshFunction(true); // true indica refresh automático
      }
    }, this.interval);
  }

  stop() {
    if (this.intervalId) {
      clearInterval(this.intervalId);
      this.intervalId = null;
    }
    this.isActive = false;
  }

  toggle() {
    if (this.isActive) {
      this.stop();
    } else {
      this.start();
    }
    return this.isActive;
  }
}

// =============================================================================
// SEARCH/FILTER UTILITIES
// =============================================================================

/**
 * Implementa busca em tempo real em elementos
 * @param {string} inputSelector - Seletor do input de busca
 * @param {string} itemSelector - Seletor dos itens para filtrar
 * @param {function} filterFunction - Função customizada de filtro (opcional)
 */
function setupLiveSearch(inputSelector, itemSelector, filterFunction = null) {
  const searchInput = $(inputSelector);
  if (!searchInput) return;

  searchInput.addEventListener('input', (e) => {
    const query = e.target.value.toLowerCase().trim();
    const items = $$(itemSelector);

    items.forEach(item => {
      let shouldShow = false;
      
      if (filterFunction) {
        shouldShow = filterFunction(item, query);
      } else {
        // Filtro padrão baseado no dataset.name ou textContent
        const searchText = (item.dataset.name || item.textContent || '').toLowerCase();
        shouldShow = query === '' || searchText.includes(query);
      }

      item.style.display = shouldShow ? '' : 'none';
    });
  });
}

// =============================================================================
// INITIALIZATION
// =============================================================================

// Inicialização automática quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
  // Configurar toast container automático se necessário
  const existingToasts = document.getElementById('toasts');
  if (existingToasts && !document.getElementById('toast-container')) {
    existingToasts.id = 'toast-container';
  }
  
  console.log('Admin Common JS carregado');
});

// Exportar para uso global
window.AdminCommon = {
  showToast,
  getStatusClass,
  getStatusText,
  createStatusPill,
  postJson,
  getJson,
  $,
  $$,
  waitForElement,
  setButtonLoading,
  toggleAutoIndicator,
  formDataToObject,
  submitFormAjax,
  copyToClipboard,
  AutoRefresh,
  setupLiveSearch,
  
  // Skeleton loading utilities (usando SkeletonSystem centralizado)
  showSkeletonElement: function(element) {
    if (window.SkeletonSystem) {
      return window.SkeletonSystem.SkeletonUtils.showSkeletonElement(element);
    }
    // Fallback básico
    if (element) element.classList.add('skeleton-basic');
  },
  
  hideSkeletonElement: function(element, delay = 0) {
    if (window.SkeletonSystem) {
      return window.SkeletonSystem.SkeletonUtils.hideSkeletonElement(element, delay);
    }
    // Fallback básico
    if (element) element.classList.remove('skeleton-basic');
  },
  
  smoothReveal: function(elements, staggerDelay = 100) {
    if (window.SkeletonSystem) {
      return window.SkeletonSystem.SkeletonUtils.smoothReveal(elements, staggerDelay);
    }
    // Fallback básico
    if (!Array.isArray(elements)) elements = [elements];
    elements.forEach(el => el && el.classList.remove('skeleton-basic'));
  }
};

// =============================================================================
// REFRESH ICON ANIMATION
// =============================================================================
document.addEventListener('click', function (e) {
  var btn = e.target.closest('button');
  if (!btn) return;
  var icon = btn.querySelector('.refresh-icon');
  if (!icon) return;
  icon.classList.remove('refresh-icon-spin');
  void icon.offsetWidth; // força reflow para reiniciar a animação
  icon.classList.add('refresh-icon-spin');
  setTimeout(function () { icon.classList.remove('refresh-icon-spin'); }, 600);
});