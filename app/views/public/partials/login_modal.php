<?php
/**
 * Modal de Login Compartilhado
 * Usado tanto na home (via layout.php) quanto na página do produto
 * 
 * Variáveis esperadas:
 * - $requireLogin: bool - Se login é obrigatório
 * - $isLogged: bool - Se usuário está logado  
 * - $company: array - Dados da empresa (precisa do slug)
 * - $forceLoginModal: bool (opcional) - Se deve abrir modal automaticamente
 */

// Garantir que as variáveis existem
$requireLogin = $requireLogin ?? false;
$isLogged = $isLogged ?? false;
$company = $company ?? [];
$forceLoginModal = $forceLoginModal ?? false;
?>

<!-- Configuração global de login (sempre renderizada) -->
<script>
(function() {
  // Configurações do modal de login - SEMPRE definidas
  window.__LOGIN_CONFIG = {
    requiresLogin: <?= $requireLogin ? 'true' : 'false' ?>,
    userLogged: <?= $isLogged ? 'true' : 'false' ?>,
    forceOpen: <?= $forceLoginModal ? 'true' : 'false' ?>
  };
  
  // Funções globais - SEMPRE definidas para evitar erros de referência
  window.openLoginModal = function() {
    const modal = document.getElementById('login-modal');
    if (!modal) return; // Modal não existe (usuário logado ou login não requerido)
    const redirectInput = modal.querySelector('input[name="redirect_to"]');
    if (redirectInput) {
      // Prioriza redirect_to da URL (ex: veio do carrinho com ?login=1&redirect_to=/slug/checkout)
      const urlParams = new URLSearchParams(window.location.search);
      const urlRedirect = urlParams.get('redirect_to');
      redirectInput.value = urlRedirect || (window.location.pathname + window.location.search);
    }
    modal.classList.remove('hidden');
  };
  
  window.closeLoginModal = function() {
    const modal = document.getElementById('login-modal');
    if (modal) modal.classList.add('hidden');
  };
  
  window.allowAction = function() {
    if (!window.__LOGIN_CONFIG.requiresLogin) return true;
    if (window.__LOGIN_CONFIG.userLogged) return true;
    window.openLoginModal();
    return false;
  };
})();
</script>

<?php if ($requireLogin && !$isLogged): ?>
<script>
// ⚡ FUNÇÃO GLOBAL DE LOGIN - Definida antes do DOM para estar disponível no onsubmit
window.handleLoginSubmit = function(event) {
  console.log('🔒 handleLoginSubmit CHAMADA!', event);
  
  if (!event) {
    console.error('❌ Event não fornecido');
    return false;
  }
  
  event.preventDefault();
  event.stopPropagation();
  
  console.log('✅ Submit interceptado, processando AJAX...');
  
  const loginForm = document.getElementById('login-form');
  const loginMsg = document.getElementById('login-msg');
  const submitBtn = document.getElementById('login-submit-btn');
  
  if (!loginForm) {
    console.error('❌ Form de login não encontrado');
    return false;
  }
  
  const formData = new FormData(loginForm);
  
  // Desabilitar botão
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = 'Aguarde...';
  }
  
  // Limpar mensagem anterior
  if (loginMsg) {
    loginMsg.classList.add('hidden');
    loginMsg.textContent = '';
  }
  
  console.log('📡 Enviando requisição AJAX para:', loginForm.action);
  
  // AJAX request
  fetch(loginForm.action, {
    method: 'POST',
    body: formData,
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json'
    },
    credentials: 'same-origin',
    cache: 'no-cache'
  })
  .then(function(response) {
    console.log('📥 Resposta recebida, status:', response.status, 'ok:', response.ok);
    
    // Primeiro pegar o texto bruto para debug
    return response.text().then(function(text) {
      console.log('📄 Resposta bruta:', text);
      
      // Tentar parsear como JSON
      try {
        var data = JSON.parse(text);
        return { status: response.status, data: data, ok: response.ok };
      } catch (parseError) {
        console.error('❌ Erro ao parsear JSON:', parseError, 'Texto:', text);
        throw new Error('Resposta inválida do servidor');
      }
    });
  })
  .then(function(result) {
    console.log('📦 Dados recebidos:', result.data);
    // Se for erro CSRF (403 sem campo ok), recarregar a página para renovar o token
    if (result.status === 403 || result.data.code === 'CSRF_TOKEN_INVALID') {
      if (loginMsg) {
        loginMsg.textContent = 'Sessão expirada. A página será recarregada...';
        loginMsg.classList.remove('hidden');
      }
      setTimeout(function() { window.location.reload(); }, 1500);
      return;
    }
    if (result.data.ok) {
      console.log('✅ Login bem-sucedido, redirecionando para:', result.data.redirect);
      window.location.href = result.data.redirect || window.location.href;
    } else {
      console.log('❌ Erro no login:', result.data.message);
      if (loginMsg) {
        loginMsg.textContent = result.data.message || 'Erro ao fazer login. Tente novamente.';
        loginMsg.classList.remove('hidden');
      }
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Entrar';
      }
    }
  })
  .catch(function(error) {
    console.error('❌ Erro na requisição:', error.message || error);
    if (loginMsg) {
      // Mensagem mais específica baseada no tipo de erro
      var errorMessage = 'Erro ao processar login. Tente novamente.';
      if (error.message && error.message.includes('Failed to fetch')) {
        errorMessage = 'Erro de conexão. Verifique sua internet.';
      } else if (error.message && error.message.includes('inválida')) {
        errorMessage = 'Erro no servidor. Tente novamente.';
      }
      loginMsg.textContent = errorMessage;
      loginMsg.classList.remove('hidden');
    }
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Entrar';
    }
  });
  
  return false;
};
console.log('✅ window.handleLoginSubmit definida (login_modal.php)');
</script>
<style>
  #login-modal { display: none; }
  #login-modal:not(.hidden) { display: flex; }
</style>
<div id="login-modal" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-[60] items-center justify-center p-4">
  <div class="bg-white max-w-sm w-full rounded-2xl overflow-hidden shadow-xl">
    <div class="p-4 border-b flex items-center">
      <h3 class="font-semibold text-lg">Login do Cliente</h3>
      <button type="button" id="login-close" class="ml-auto px-3 py-1.5 rounded-xl border">Fechar</button>
    </div>
    <form id="login-form" class="p-4" method="post" action="<?= base_url(rawurlencode((string)($company['slug'] ?? '')).'/customer-login') ?>" onsubmit="return handleLoginSubmit(event);">
      <?php if (function_exists('csrf_field')) {
          echo csrf_field();
      } ?>
      <input type="hidden" name="redirect_to" value="<?= e($_GET['redirect_to'] ?? $_SERVER['REQUEST_URI'] ?? '') ?>">
      <div class="mb-3">
        <label class="block text-sm font-medium mb-1">WhatsApp</label>
        <input type="tel" id="login-whatsapp" name="whatsapp" required placeholder="(51) 99999-0000" class="w-full border rounded-lg px-3 py-2" />
        <p id="login-whatsapp-status" class="text-xs text-gray-500 mt-1">Somente números; inclua DDD.</p>
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Nome</label>
        <input type="text" id="login-name" name="name" required class="w-full border rounded-lg px-3 py-2" />
      </div>
      <div id="lgpd-consent-wrapper" class="mb-4">
        <label class="flex items-start gap-2 text-xs text-gray-500 cursor-pointer">
          <input type="checkbox" name="lgpd_consent" id="lgpd-consent-checkbox" value="1" required class="mt-0.5 accent-yellow-400" />
          <span>Concordo com o uso dos meus dados para processamento de pedidos e comunicações, conforme a <a href="<?= base_url(rawurlencode((string)($company['slug'] ?? '')) . '/politica-privacidade') ?>" target="_blank" class="underline text-blue-600">Política de Privacidade</a>.</span>
        </label>
      </div>
      <button type="submit" id="login-submit-btn" class="w-full bg-yellow-400 text-black font-semibold py-2 rounded-lg hover:bg-yellow-300">
        Entrar
      </button>
      <div id="login-msg" class="text-sm mt-3 text-red-600 hidden"></div>
    </form>
  </div>
</div>

<script>
(function() {
  // Inicializar eventos do modal quando DOM estiver pronto
  function initLoginModalEvents() {
    const modal = document.getElementById('login-modal');
    const closeBtn = document.getElementById('login-close');
    
    if (closeBtn) {
      closeBtn.addEventListener('click', window.closeLoginModal);
    }
    
    if (modal) {
      modal.addEventListener('click', function(ev) {
        if (ev.target === modal) window.closeLoginModal();
      });
      
      // Fechar com Escape
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
          window.closeLoginModal();
        }
      });
      
      // Focus trap - manter foco dentro do modal quando aberto
      const focusableElements = modal.querySelectorAll('button, input, [tabindex]:not([tabindex="-1"])');
      const firstFocusable = focusableElements[0];
      const lastFocusable = focusableElements[focusableElements.length - 1];
      
      modal.addEventListener('keydown', function(e) {
        if (e.key !== 'Tab') return;
        
        if (e.shiftKey) {
          if (document.activeElement === firstFocusable) {
            e.preventDefault();
            lastFocusable.focus();
          }
        } else {
          if (document.activeElement === lastFocusable) {
            e.preventDefault();
            firstFocusable.focus();
          }
        }
      });
    }
    
    // Inicializar máscara de telefone
    const phoneInput = document.getElementById('login-whatsapp');
    const nameInput = document.getElementById('login-name');
    const statusEl = document.getElementById('login-whatsapp-status');
    
    // Variável para controlar debounce da busca
    let lookupTimeout = null;
    let lastLookedUpPhone = '';
    let originalCustomerName = '';
    
    // Função para buscar cliente por WhatsApp
    const lookupCustomer = function(phoneValue) {
      const digits = phoneValue.replace(/\D/g, '');
      
      // Só buscar se tiver 10 ou 11 dígitos (DDD + telefone)
      if (digits.length < 10 || digits.length > 11) {
        return;
      }
      
      // Evitar busca duplicada para o mesmo número
      if (digits === lastLookedUpPhone) {
        return;
      }
      lastLookedUpPhone = digits;
      
      // Atualizar status
      if (statusEl) {
        statusEl.textContent = 'Verificando número...';
        statusEl.className = 'text-xs text-blue-500 mt-1';
      }
      
      // Pegar o slug da empresa da URL
      const slug = '<?= rawurlencode((string)($company['slug'] ?? '')) ?>';
      
      const formData = new FormData();
      formData.append('whatsapp', phoneValue);
      
      // Adicionar CSRF token se existir
      const csrfInput = document.querySelector('input[name="csrf_token"]');
      if (csrfInput) {
        formData.append('csrf_token', csrfInput.value);
      }
      
      fetch('/' + slug + '/customer-lookup', {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        credentials: 'same-origin'
      })
      .then(function(response) {
        return response.json();
      })
      .then(function(data) {
        if (data.ok && data.valid) {
          // Número válido
          if (statusEl) {
            if (data.whatsapp_warning) {
              // Aviso — número pode não existir no WhatsApp, mas NÃO bloqueia
              statusEl.innerHTML = '⚠️ ' + data.whatsapp_warning;
              if (data.wame_link) {
                statusEl.innerHTML += ' <a href="' + data.wame_link + '" target="_blank" rel="noopener" style="text-decoration:underline;color:#d97706;">Clique aqui para testar</a>';
              }
              statusEl.className = 'text-xs text-amber-600 mt-1';
            } else if (data.checked && data.exists) {
              statusEl.textContent = '✓ Número verificado no WhatsApp';
              statusEl.className = 'text-xs text-green-600 mt-1';
            } else {
              statusEl.textContent = 'Somente números; inclua DDD.';
              statusEl.className = 'text-xs text-gray-500 mt-1';
            }
          }
          
          // Auto-preencher nome se cliente existir
          if (data.customer && data.customer.name && nameInput) {
            nameInput.value = data.customer.name;
            originalCustomerName = data.customer.name;
            nameInput.classList.add('bg-green-50');
            setTimeout(function() {
              nameInput.classList.remove('bg-green-50');
            }, 1500);
          }

          // Ocultar LGPD se cliente já aceitou
          var lgpdWrapper = document.getElementById('lgpd-consent-wrapper');
          var lgpdCheckbox = document.getElementById('lgpd-consent-checkbox');
          if (lgpdWrapper && lgpdCheckbox) {
            if (data.customer && data.customer.lgpd_accepted) {
              lgpdWrapper.style.display = 'none';
              lgpdCheckbox.removeAttribute('required');
              lgpdCheckbox.checked = true;
            } else {
              lgpdWrapper.style.display = '';
              lgpdCheckbox.setAttribute('required', 'required');
              lgpdCheckbox.checked = false;
            }
          }
        } else if (!data.valid) {
          // Número inválido no WhatsApp
          if (statusEl) {
            statusEl.textContent = '✗ ' + (data.message || 'Número não encontrado no WhatsApp');
            statusEl.className = 'text-xs text-red-600 mt-1';
          }
          lastLookedUpPhone = ''; // Permitir nova tentativa
        }
      })
      .catch(function(error) {
        console.error('Erro ao verificar WhatsApp:', error);
        if (statusEl) {
          statusEl.textContent = 'Somente números; inclua DDD.';
          statusEl.className = 'text-xs text-gray-500 mt-1';
        }
        lastLookedUpPhone = ''; // Permitir nova tentativa
      });
    };
    
    if (phoneInput && !phoneInput.dataset.maskApplied) {
      phoneInput.dataset.maskApplied = '1';
      
      const applyPhoneMask = function(value) {
        const digits = value.replace(/\D/g, '');
        const limited = digits.substring(0, 11);
        
        let formatted = '';
        if (limited.length > 0) {
          formatted = '(' + limited.substring(0, 2);
          if (limited.length > 2) {
            formatted += ') ' + limited.substring(2, 7);
          }
          if (limited.length > 7) {
            formatted += '-' + limited.substring(7, 11);
          }
        }
        
        return formatted;
      };
      
      if (phoneInput.value) {
        phoneInput.value = applyPhoneMask(phoneInput.value);
      }
      
      phoneInput.addEventListener('input', function(e) {
        e.target.value = applyPhoneMask(e.target.value);
        
        // Debounce para buscar cliente após digitar
        clearTimeout(lookupTimeout);
        const digits = e.target.value.replace(/\D/g, '');
        
        // Aguardar 500ms após parar de digitar para buscar
        if (digits.length >= 10) {
          lookupTimeout = setTimeout(function() {
            lookupCustomer(e.target.value);
          }, 500);
        } else {
          // Resetar status se número incompleto
          if (statusEl) {
            statusEl.textContent = 'Somente números; inclua DDD.';
            statusEl.className = 'text-xs text-gray-500 mt-1';
          }
          lastLookedUpPhone = '';
          // Mostrar LGPD novamente ao limpar número
          var lgpdW = document.getElementById('lgpd-consent-wrapper');
          var lgpdC = document.getElementById('lgpd-consent-checkbox');
          if (lgpdW && lgpdC) {
            lgpdW.style.display = '';
            lgpdC.setAttribute('required', 'required');
            lgpdC.checked = false;
          }
        }
      });
      
      // Também verificar quando sair do campo (blur)
      phoneInput.addEventListener('blur', function(e) {
        const digits = e.target.value.replace(/\D/g, '');
        if (digits.length >= 10) {
          clearTimeout(lookupTimeout);
          lookupCustomer(e.target.value);
        }
      });
      
      phoneInput.addEventListener('keydown', function(e) {
        const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'];
        if (!allowedKeys.includes(e.key) && (e.key < '0' || e.key > '9')) {
          e.preventDefault();
        }
      });
    }
    
    // Abrir modal automaticamente se forceOpen estiver ativado
    if (window.__LOGIN_CONFIG.forceOpen && window.__LOGIN_CONFIG.requiresLogin && !window.__LOGIN_CONFIG.userLogged) {
      window.openLoginModal();
    }
  }
  
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLoginModalEvents);
  } else {
    initLoginModalEvents();
  }
})();
</script>
<?php endif; ?>
