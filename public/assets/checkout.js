const checkoutConfig = (() => {
  const configEl = document.getElementById('checkout-page-config');
  if (!configEl) {
    return {};
  }

  try {
    return JSON.parse(configEl.textContent || '{}');
  } catch (error) {
    if (window.__DEBUG) console.error('checkout config parse error', error);
    return {};
  }
})();

const checkoutSession = checkoutConfig.session || {};
const checkoutDataConfig = checkoutConfig.data || {};
const checkoutFlags = checkoutConfig.flags || {};
const checkoutUrls = checkoutConfig.urls || {};
const checkoutPaymentsConfig = checkoutConfig.paymentMethods || {};
const checkoutSelectionConfig = checkoutConfig.selection || {};
// ============================================================================
// 🔐 SECURE STORAGE - Sistema de armazenamento isolado por usuário
// ============================================================================
(() => {
  window.MM_SESSION = {
    customerId: parseInt(checkoutSession.customerId || 0, 10) || 0,
    companySlug: String(checkoutSession.companySlug || "")
  };
  
  // Prefixo único para storage baseado no usuário e empresa
  const STORAGE_PREFIX = `mm_${MM_SESSION.companySlug}_${MM_SESSION.customerId}_`;
  
  // Sistema de Storage Seguro - isolado por usuário
  window.SecureStorage = {
    // Salva dado isolado por usuário
    set: function(key, value) {
      const fullKey = STORAGE_PREFIX + key;
      try {
        sessionStorage.setItem(fullKey, JSON.stringify({
          value: value,
          customerId: MM_SESSION.customerId,
          timestamp: Date.now()
        }));
      } catch (e) {
        console.warn('SecureStorage.set error:', e);
      }
    },
    
    // Lê dado validando propriedade do usuário
    get: function(key) {
      const fullKey = STORAGE_PREFIX + key;
      try {
        const raw = sessionStorage.getItem(fullKey);
        if (!raw) return null;
        
        const data = JSON.parse(raw);
        
        // Validar que pertence ao usuário atual
        if (data.customerId !== MM_SESSION.customerId) {
          console.warn('🔐 Security: Storage data belongs to different user, removing');
          sessionStorage.removeItem(fullKey);
          return null;
        }
        
        // Validar idade (máximo 2 horas = 7200000ms)
        if (Date.now() - data.timestamp > 7200000) {
          console.warn('🔐 Security: Storage data expired, removing');
          sessionStorage.removeItem(fullKey);
          return null;
        }
        
        return data.value;
      } catch (e) {
        console.warn('SecureStorage.get error:', e);
        return null;
      }
    },
    
    // Remove dado específico
    remove: function(key) {
      const fullKey = STORAGE_PREFIX + key;
      try {
        sessionStorage.removeItem(fullKey);
        localStorage.removeItem(fullKey);
      } catch (e) {}
    },
    
    // Limpa TODOS os dados do usuário atual
    clearAll: function() {
      try {
        const keysToRemove = [];
        for (let i = 0; i < sessionStorage.length; i++) {
          const key = sessionStorage.key(i);
          if (key && key.startsWith(STORAGE_PREFIX)) {
            keysToRemove.push(key);
          }
        }
        keysToRemove.forEach(key => sessionStorage.removeItem(key));
        
        // Limpar localStorage também
        const localKeysToRemove = [];
        for (let i = 0; i < localStorage.length; i++) {
          const key = localStorage.key(i);
          if (key && key.startsWith(STORAGE_PREFIX)) {
            localKeysToRemove.push(key);
          }
        }
        localKeysToRemove.forEach(key => localStorage.removeItem(key));
      } catch (e) {
        console.warn('SecureStorage.clearAll error:', e);
      }
    },
    
    // Limpa dados de OUTROS usuários (segurança)
    clearOtherUsers: function() {
      try {
        const currentPrefix = STORAGE_PREFIX;
        const companyPrefix = `mm_${MM_SESSION.companySlug}_`;
        
        // SessionStorage
        const sessionKeysToRemove = [];
        for (let i = 0; i < sessionStorage.length; i++) {
          const key = sessionStorage.key(i);
          if (key && key.startsWith(companyPrefix) && !key.startsWith(currentPrefix)) {
            sessionKeysToRemove.push(key);
          }
        }
        sessionKeysToRemove.forEach(key => {
          sessionStorage.removeItem(key);
        });
        
        // LocalStorage
        const localKeysToRemove = [];
        for (let i = 0; i < localStorage.length; i++) {
          const key = localStorage.key(i);
          if (key && key.startsWith(companyPrefix) && !key.startsWith(currentPrefix)) {
            localKeysToRemove.push(key);
          }
        }
        localKeysToRemove.forEach(key => {
          localStorage.removeItem(key);
        });
        
        // Limpar dados antigos sem prefixo (legacy - versões anteriores)
        const legacyKeys = ['couponCode', 'couponDiscount', 'couponSyncAttempted', 
                          'checkoutFormData', 'orderSummary', 'lastUserPhone'];
        legacyKeys.forEach(key => {
          if (sessionStorage.getItem(key)) {
            sessionStorage.removeItem(key);
          }
          if (localStorage.getItem(key)) {
            localStorage.removeItem(key);
          }
        });
        
      } catch (e) {
        console.warn('SecureStorage.clearOtherUsers error:', e);
      }
    }
  };
  
  // ============================================================================
  // 🔐 VALIDAÇÃO INICIAL DE SEGURANÇA
  // ============================================================================
  // Verificar se há dados de outro usuário e limpar
  const lastCustomerId = sessionStorage.getItem('mm_last_customer_id');
  if (lastCustomerId && parseInt(lastCustomerId) !== MM_SESSION.customerId) {
    SecureStorage.clearOtherUsers();
  }
  sessionStorage.setItem('mm_last_customer_id', MM_SESSION.customerId.toString());
  
})();
(() => {
  // Verificar se o usuário logado mudou (detectar troca de conta)
  // ============================================================================
  // 🔐 VERIFICAÇÃO DE SEGURANÇA DO USUÁRIO (usando SecureStorage)
  // ============================================================================
  // Sincronizar cupom do SecureStorage com a sessão PHP
  const couponCode = SecureStorage.get('couponCode');
  const couponDiscount = SecureStorage.get('couponDiscount');
  
  // Verificar se já tentou sincronizar antes (evitar loops)
  const syncAttempted = SecureStorage.get('couponSyncAttempted');
  
  if (couponCode && couponDiscount && !syncAttempted) {
    if (!checkoutFlags.hasSessionCoupon && checkoutUrls.syncCouponUrl) {
      // Marcar que está tentando sincronizar
      SecureStorage.set('couponSyncAttempted', true);

      // Enviar para sessão PHP via fetch com token de segurança
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      const csrf = csrfMeta ? csrfMeta.getAttribute('content') : '';
      fetch(checkoutUrls.syncCouponUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf || ''
        },
        body: JSON.stringify({
          coupon_code: couponCode,
          coupon_discount: couponDiscount,
          customer_id: MM_SESSION.customerId
        })
      }).then(() => {
        location.reload();
      }).catch(() => {
        SecureStorage.remove('couponSyncAttempted');
      });
    }
  } else if (checkoutFlags.hasSessionCoupon) {
    // Se já tem cupom na sessão, limpar flag de sincronização
    SecureStorage.remove('couponSyncAttempted');
  }

  // Limpar cupom do SecureStorage quando o pedido for enviado
  const checkoutForm = document.getElementById('checkout-form');
  if (checkoutForm) {
    checkoutForm.addEventListener('submit', function(e) {
      // NÃO fazer preventDefault aqui - deixar o LISTENER 2 cuidar disso
      SecureStorage.remove('couponCode');
      SecureStorage.remove('couponDiscount');
      SecureStorage.remove('couponSyncAttempted');
      // NÃO bloquear o evento - deixar propagar para o LISTENER 2
    });
  }

  // Toast helper para frontend público
  try {
    let publicToastContainer = document.getElementById('public-toasts');
    if (!publicToastContainer) {
      publicToastContainer = document.createElement('div');
      publicToastContainer.id = 'public-toasts';
      publicToastContainer.className = 'public-toasts';
      document.body.appendChild(publicToastContainer);
    }

    window.showToast = function(message, type) {
      try {
        const el = document.createElement('div');
        const toastType = type === 'error' ? 'error' : (type === 'success' ? 'success' : 'info');
        el.className = 'public-toast ' + toastType;
        el.textContent = message || '';
        publicToastContainer.appendChild(el);
        requestAnimationFrame(() => {
          el.classList.add('show');
        });

        setTimeout(() => {
          el.classList.remove('show');
          setTimeout(() => {
            try {
              publicToastContainer.removeChild(el);
            } catch (_) {}
          }, 160);
        }, 3000);
      } catch (e) {
        console.error(e);
      }
    };
  } catch (e) {
    console.error(e);
  }

  const data = {
    subtotal: parseFloat(checkoutDataConfig.subtotal || 0) || 0,
    loyaltyDiscount: parseFloat(checkoutDataConfig.loyaltyDiscount || 0) || 0,
    couponDiscount: parseFloat(checkoutDataConfig.couponDiscount || 0) || 0,
    freeShippingMin: parseFloat(checkoutDataConfig.freeShippingMin || 0) || 0,
    cities: Array.isArray(checkoutDataConfig.cities) ? checkoutDataConfig.cities : [],
    zonesByCity: (checkoutDataConfig.zonesByCity && typeof checkoutDataConfig.zonesByCity === 'object')
      ? checkoutDataConfig.zonesByCity
      : {},
    selectedCityId: parseInt(checkoutDataConfig.selectedCityId || 0, 10) || 0,
    selectedZoneId: parseInt(checkoutDataConfig.selectedZoneId || 0, 10) || 0,
    zonesPresent: !!checkoutDataConfig.zonesPresent
  };

  const citySelect = document.getElementById('checkout-city');
  const zoneSelect = document.getElementById('checkout-zone');
  const cityNameInput = document.getElementById('address-city-name');
  const zoneNameInput = document.getElementById('address-zone-name');
  const deliveryInput = document.getElementById('delivery-fee-input');
  const deliveryAmount = document.getElementById('delivery-amount');
  const subtotalAmount = document.getElementById('subtotal-amount');
  const totalAmount = document.getElementById('total-amount');
  const paymentInput = document.getElementById('payment-method-id');
  const paymentTypeInput = document.getElementById('payment-type');
  const paymentBrandInput = document.getElementById('payment-brand');
  const paymentBox = document.getElementById('payment-instructions');

  document.querySelectorAll('img[data-fallback-src]').forEach((image) => {
    image.addEventListener('error', function handleImageError() {
      const fallbackSrc = image.getAttribute('data-fallback-src');
      if (fallbackSrc && image.src !== fallbackSrc) {
        image.src = fallbackSrc;
      }
      image.removeEventListener('error', handleImageError);
    });

    if (image.complete && image.naturalWidth === 0) {
      const fallbackSrc = image.getAttribute('data-fallback-src');
      if (fallbackSrc && image.src !== fallbackSrc) {
        image.src = fallbackSrc;
      }
    }
  });

  // Payment data
  const paymentMethods = (checkoutPaymentsConfig && typeof checkoutPaymentsConfig === 'object')
    ? checkoutPaymentsConfig
    : {};

  // Expor paymentMethods globalmente para outros scripts
  window.checkoutPaymentMethods = paymentMethods;

  let selectedPaymentType = '';
  let selectedCardBrand = '';
  let selectedMethodId = parseInt(checkoutSelectionConfig.selectedPaymentId || 0, 10) || 0;

  // Expor selectedMethodId globalmente via getter para sempre obter valor atualizado
  Object.defineProperty(window, 'checkoutSelectedMethodId', {
    get: function() { return selectedMethodId; },
    enumerable: true
  });

  // Payment functions
  window.selectPaymentType = function(type) {
    const selectedBtn = document.querySelector(`.payment-type-btn[data-type="${type}"]`);
    const isCurrentlyActive = selectedBtn && selectedBtn.classList.contains('active');
    
    // Remove active state from all payment type buttons
    document.querySelectorAll('.payment-type-btn').forEach(btn => btn.classList.remove('active'));
    
    // Hide all brand sections
    document.querySelectorAll('.card-brands').forEach(brands => brands.classList.remove('show'));
    
    // Hide PIX instructions when switching to other methods
    if (type !== 'pix' && paymentBox) {
      paymentBox.innerHTML = '';
      paymentBox.classList.add('hidden');
    }
    
    // Hide cash payment block when switching to other methods
    const cashBlock = document.getElementById('cash-payment-block');
    if (type !== 'cash' && cashBlock) {
      cashBlock.classList.add('hidden');
    }
    
    // If clicking the same button that was active, just close it (toggle behavior)
    if (isCurrentlyActive) {
      selectedPaymentType = '';
      selectedMethodId = 0;
      selectedCardBrand = '';
      // Hide payment instructions when deselecting
      if (paymentBox) {
        paymentBox.innerHTML = '';
        paymentBox.classList.add('hidden');
      }
      // Hide cash payment block when deselecting
      if (cashBlock) {
        cashBlock.classList.add('hidden');
      }
      updatePaymentData();
      return;
    }
    
    // Add active state to selected type
    if (selectedBtn) {
      selectedBtn.classList.add('active');
    }
    
    selectedPaymentType = type;
    
    if (type === 'credit') {
      // Show credit card brands
      const cardBrandsDiv = document.getElementById('credit-brands');
      if (cardBrandsDiv) {
        cardBrandsDiv.classList.add('show');
      }
      selectedCardBrand = '';
      selectedMethodId = 0;
      updatePaymentData();
    } else if (type === 'debit') {
      // Show debit card brands
      const debitBrandsDiv = document.getElementById('debit-brands');
      if (debitBrandsDiv) {
        debitBrandsDiv.classList.add('show');
      }
      selectedCardBrand = '';
      selectedMethodId = 0;
      updatePaymentData();
    } else if (type === 'voucher') {
      // Show voucher brands
      const voucherBrandsDiv = document.getElementById('voucher-brands');
      if (voucherBrandsDiv) {
        voucherBrandsDiv.classList.add('show');
      }
      selectedCardBrand = '';
      selectedMethodId = 0;
      updatePaymentData();
    } else if (type === 'others') {
      // Show other methods
      const otherBrandsDiv = document.getElementById('others-brands');
      if (otherBrandsDiv) {
        otherBrandsDiv.classList.add('show');
      }
      selectedCardBrand = '';
      selectedMethodId = 0;
      updatePaymentData();
    } else if (type === 'pix') {
      // Find first PIX method and select it
      const pixMethod = Object.values(paymentMethods).find(method => method.type === 'pix');
      if (pixMethod) {
        selectedMethodId = pixMethod.id;
        updatePaymentData();
        showPaymentInstructions(pixMethod);
      }
    } else if (type === 'cash') {
      // Show cash payment block
      const cashBlock = document.getElementById('cash-payment-block');
      if (cashBlock) {
        cashBlock.classList.remove('hidden');
      }
      // Find first cash method and select it
      const cashMethod = Object.values(paymentMethods).find(method => method.type === 'cash');
      if (cashMethod) {
        selectedMethodId = cashMethod.id;
        updatePaymentData();
      }
    }
  };

  window.selectCardBrand = function(paymentType, brand, methodId) {
    // Check if this brand is currently active - SEM optional chaining para iOS 13
    const currentBrandsDiv = document.getElementById(paymentType + '-brands');
    const selectedBtn = currentBrandsDiv ? currentBrandsDiv.querySelector(`.brand-btn[data-brand="${brand}"]`) : null;
    const isCurrentlyActive = selectedBtn && selectedBtn.classList.contains('active');
    
    // Remove active state from all brand buttons in the current type
    if (currentBrandsDiv) {
      currentBrandsDiv.querySelectorAll('.brand-btn').forEach(btn => btn.classList.remove('active'));
    }
    
    // If clicking the same brand that was active, just deselect it (toggle behavior)
    if (isCurrentlyActive) {
      selectedCardBrand = '';
      selectedMethodId = 0;
      // Hide payment instructions when deselecting
      if (paymentBox) {
        paymentBox.innerHTML = '';
        paymentBox.classList.add('hidden');
      }
      updatePaymentData();
      return;
    }
    
    // Add active state to selected brand
    if (selectedBtn) {
      selectedBtn.classList.add('active');
    }
    
    selectedCardBrand = brand;
    
    // Use the specific method ID if provided
    if (methodId) {
      selectedMethodId = methodId;
    } else {
      // Find first method of the current type
      const typeMethod = Object.values(paymentMethods).find(method => method.type === paymentType);
      if (typeMethod) {
        selectedMethodId = typeMethod.id;
      }
    }
    
    updatePaymentData();
    
    const method = paymentMethods[selectedMethodId];
    if (method) {
      showPaymentInstructions(method);
    }
  };

  const paymentsContainer = document.getElementById('checkout-payment');
  if (paymentsContainer) {
    paymentsContainer.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-payment-select="1"]');
      if (btn) {
        const type = btn.getAttribute('data-type') || '';
        if (type) window.selectPaymentType(type);
      }
    });
  }

  document.querySelectorAll('[data-brand-select="1"]').forEach((button) => {
    button.addEventListener('click', () => {
      const paymentType = button.getAttribute('data-payment-type') || '';
      const brand = button.getAttribute('data-brand') || '';
      const methodId = parseInt(button.getAttribute('data-method-id') || '0', 10) || 0;
      if (paymentType && brand) {
        window.selectCardBrand(paymentType, brand, methodId);
      }
    });
  });

  function updatePaymentData() {
    if (paymentInput) paymentInput.value = selectedMethodId || '';
    if (paymentTypeInput) paymentTypeInput.value = selectedPaymentType || '';
    if (paymentBrandInput) paymentBrandInput.value = selectedCardBrand || '';
  }

  function syncInitialPaymentState(methodId) {
    const normalizedMethodId = parseInt(methodId || 0, 10) || 0;

    document.querySelectorAll('.payment-type-btn').forEach((btn) => btn.classList.remove('active'));
    document.querySelectorAll('.card-brands').forEach((brands) => brands.classList.remove('show'));
    document.querySelectorAll('[data-brand-select="1"]').forEach((btn) => btn.classList.remove('active'));

    if (paymentBox) {
      paymentBox.innerHTML = '';
      paymentBox.classList.add('hidden');
      paymentBox.classList.remove('payment-note-pix');
    }

    const cashBlock = document.getElementById('cash-payment-block');
    if (cashBlock) {
      cashBlock.classList.add('hidden');
    }

    if (!normalizedMethodId || !paymentMethods[normalizedMethodId]) {
      selectedPaymentType = '';
      selectedMethodId = 0;
      selectedCardBrand = '';
      updatePaymentData();
      return;
    }

    const method = paymentMethods[normalizedMethodId];
    selectedPaymentType = method.type || '';
    selectedMethodId = normalizedMethodId;
    selectedCardBrand = '';

    const typeBtn = document.querySelector('.payment-type-btn[data-type="' + selectedPaymentType + '"]');
    if (typeBtn) {
      typeBtn.classList.add('active');
    }

    const brandBtn = document.querySelector('[data-brand-select="1"][data-method-id="' + normalizedMethodId + '"]');
    if (brandBtn) {
      brandBtn.classList.add('active');
      selectedCardBrand = brandBtn.getAttribute('data-brand') || '';
      const brandType = brandBtn.getAttribute('data-payment-type') || '';
      const brandsContainer = document.getElementById(brandType + '-brands');
      if (brandsContainer) {
        brandsContainer.classList.add('show');
      }
    }

    if (selectedPaymentType === 'pix') {
      showPaymentInstructions(method);
    }

    if (selectedPaymentType === 'cash' && cashBlock) {
      cashBlock.classList.remove('hidden');
    }

    updatePaymentData();
  }

  function showPaymentInstructions(method) {
    if (!paymentBox) return;
    
    const instructions = method.instructions || '';
    const type = method.type || '';
    const pxKey = method.pix_key || '';
    
    if (type === 'pix' && pxKey) {
      // Parse meta data for PIX
      let metaData = {};
      if (method.meta) {
        try {
          metaData = typeof method.meta === 'string' ? JSON.parse(method.meta) : method.meta;
        } catch (e) {
          metaData = {};
        }
      }
      
      const holderName = metaData.px_holder_name || '';
      const provider = metaData.px_provider || '';
      const keyType = metaData.px_key_type || '';
      
      // Detectar tipo de chave para exibir label
      let keyTypeLabel = 'Chave Pix';
      if (keyType === 'cpf') keyTypeLabel = 'Chave Pix (CPF)';
      else if (keyType === 'cnpj') keyTypeLabel = 'Chave Pix (CNPJ)';
      else if (keyType === 'email') keyTypeLabel = 'Chave Pix (E-mail)';
      else if (keyType === 'telefone') keyTypeLabel = 'Chave Pix (Telefone)';
      else if (keyType === 'aleatoria') keyTypeLabel = 'Chave Pix (Aleatória)';
      
      let pixHtml = `<div class="pix-card">
        <div class="pix-key-box">
          <div class="pix-key-info">
            <span class="pix-key-label">${keyTypeLabel}</span>
            <span class="pix-key-value" id="pix-key-value">${pxKey}</span>
          </div>
          <button type="button" class="pix-copy-btn" id="copy-pix-btn">Copiar</button>
        </div>`;
      
      if (holderName || provider) {
        pixHtml += `<div class="pix-details">`;
        if (holderName) {
          pixHtml += `<strong>Titular:</strong> ${holderName}<br>`;
        }
        if (provider) {
          pixHtml += `<strong>Instituição:</strong> ${provider}`;
        }
        pixHtml += `</div>`;
      }
      
      pixHtml += `<div class="pix-feedback" id="pix-feedback">Chave Pix copiada. Agora é só pagar 😉</div>`;
      
      if (instructions) {
        pixHtml += `<div class="pix-instruction">${instructions.replace(/\n/g, '<br>')}</div>`;
      }
      
      pixHtml += `</div>`;
      
      paymentBox.innerHTML = pixHtml;
      paymentBox.classList.remove('hidden');
      paymentBox.classList.add('payment-note-pix');

      const pixKeyBox = paymentBox.querySelector('.pix-key-box');
      if (pixKeyBox) {
        pixKeyBox.addEventListener('click', () => {
          window.copyPixKey(pxKey);
        });
      }

      const copyPixButton = paymentBox.querySelector('#copy-pix-btn');
      if (copyPixButton) {
        copyPixButton.addEventListener('click', (event) => {
          event.stopPropagation();
          window.copyPixKey(pxKey);
        });
      }
    } else if (instructions) {
      paymentBox.classList.remove('payment-note-pix');
      paymentBox.innerHTML = instructions.replace(/\n/g, '<br>');
      paymentBox.classList.remove('hidden');
    } else {
      paymentBox.classList.remove('payment-note-pix');
      paymentBox.innerHTML = '';
      paymentBox.classList.add('hidden');
    }
  }

  // Function to copy PIX key
  window.copyPixKey = function(pixKey) {
    const copyBtn = document.getElementById('copy-pix-btn');
    
    // Try to use the Clipboard API
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(pixKey).then(() => {
        showCopySuccess(copyBtn);
      }).catch(() => {
        fallbackCopyTextToClipboard(pixKey, copyBtn);
      });
    } else {
      // Fallback for older browsers or non-HTTPS
      fallbackCopyTextToClipboard(pixKey, copyBtn);
    }
  };

  function fallbackCopyTextToClipboard(text, button) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
      document.execCommand('copy');
      showCopySuccess(button);
    } catch (err) {
      console.error('Erro ao copiar:', err);
      showToast('Não foi possível copiar automaticamente. Use o toque longo e copiar.', 'error');
      if (button) {
        button.textContent = 'Erro';
        setTimeout(() => {
          button.textContent = 'Copiar';
        }, 2000);
      }
    }
    
    document.body.removeChild(textArea);
  }

  function showCopySuccess(button) {
    if (button) {
      button.textContent = 'Copiado!';
      button.classList.add('copied');
      setTimeout(() => {
        button.textContent = 'Copiar';
        button.classList.remove('copied');
      }, 2500);
    }
    
    // Mostrar feedback visual no card Pix
    const feedback = document.getElementById('pix-feedback');
    if (feedback) {
      feedback.classList.add('show');
      setTimeout(() => {
        feedback.classList.remove('show');
      }, 2500);
    }
  }

  // Initialize payment selection based on PHP selection
  syncInitialPaymentState(selectedMethodId);

  const formatBRL = (value) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);

  const syncCityName = () => {
    if (!citySelect) return;
    const opt = citySelect.options[citySelect.selectedIndex];
    cityNameInput.value = opt ? opt.textContent : '';
  };

  const syncZoneName = () => {
    if (!zoneSelect) return;
    const opt = zoneSelect.options[zoneSelect.selectedIndex];
    zoneNameInput.value = opt ? opt.textContent : '';
  };

  // Elementos extras para desconto na entrega
  const deliveryDiscountInfo = document.getElementById('delivery-discount-info');
  const deliverySavingBadge = document.getElementById('delivery-saving-badge');
  const loyaltyDiscountRow = document.getElementById('loyalty-discount-row');
  const loyaltyDiscountAmount = document.getElementById('loyalty-discount-amount');

  const applyLocalSummary = (fee, zoneSelected) => {
    if (typeof fee !== 'number' || Number.isNaN(fee)) fee = 0;
    if (data.freeShippingMin > 0 && data.subtotal >= data.freeShippingMin) {
      fee = 0;
    }
    const originalFee = fee;
    let deliveryDiscountApplied = 0;
    let remainingLoyaltyDiscount = 0;
    let finalDeliveryFee = fee;
    if (data.loyaltyDiscount > 0 && fee > 0) {
      if (data.loyaltyDiscount >= fee) {
        deliveryDiscountApplied = fee;
        remainingLoyaltyDiscount = data.loyaltyDiscount - fee;
        finalDeliveryFee = 0;
      } else {
        deliveryDiscountApplied = data.loyaltyDiscount;
        remainingLoyaltyDiscount = 0;
        finalDeliveryFee = fee - data.loyaltyDiscount;
      }
    } else if (data.loyaltyDiscount > 0 && fee <= 0) {
      remainingLoyaltyDiscount = data.loyaltyDiscount;
    }
    if (zoneSelected) {
      deliveryInput.value = finalDeliveryFee.toFixed(2);
      if (deliveryDiscountApplied > 0) {
        deliveryAmount.textContent = finalDeliveryFee > 0 ? formatBRL(finalDeliveryFee) : 'Grátis';
        deliveryAmount.classList.toggle('delivery-free', finalDeliveryFee <= 0);
        if (deliveryDiscountInfo) {
          const discountDiv = deliveryDiscountInfo.querySelector('div');
          if (discountDiv) {
            discountDiv.innerHTML = '<span class="delivery-original">' + formatBRL(originalFee) + '</span> <span class="delivery-discount">(– ' + formatBRL(deliveryDiscountApplied) + ')</span>';
          }
          deliveryDiscountInfo.classList.remove('is-hidden');
          deliveryDiscountInfo.style.display = '';
        }
        if (deliverySavingBadge) {
          const badgeSpan = deliverySavingBadge.querySelector('span:last-child');
          if (badgeSpan) {
            badgeSpan.textContent = 'Você economizou ' + formatBRL(deliveryDiscountApplied) + ' na entrega';
          }
          deliverySavingBadge.classList.remove('is-hidden');
          deliverySavingBadge.style.display = '';
        }
      } else {
        deliveryAmount.textContent = fee > 0 ? formatBRL(fee) : 'Grátis';
        deliveryAmount.classList.toggle('delivery-free', fee <= 0);
        if (deliveryDiscountInfo) {
          deliveryDiscountInfo.classList.add('is-hidden');
          deliveryDiscountInfo.style.display = 'none';
        }
        if (deliverySavingBadge) {
          deliverySavingBadge.classList.add('is-hidden');
          deliverySavingBadge.style.display = 'none';
        }
      }
      if (loyaltyDiscountRow && loyaltyDiscountAmount) {
        if (remainingLoyaltyDiscount > 0) {
          loyaltyDiscountRow.classList.remove('is-hidden');
          loyaltyDiscountRow.style.display = '';
          loyaltyDiscountAmount.textContent = '- ' + formatBRL(remainingLoyaltyDiscount);
        } else {
          loyaltyDiscountRow.classList.add('is-hidden');
          loyaltyDiscountRow.style.display = 'none';
        }
      }
    } else {
      deliveryInput.value = '';
      deliveryAmount.textContent = data.zonesPresent ? 'Selecione a cidade' : 'A calcular';
      deliveryAmount.classList.remove('delivery-free');
      if (deliveryDiscountInfo) {
        deliveryDiscountInfo.classList.add('is-hidden');
        deliveryDiscountInfo.style.display = 'none';
      }
      if (deliverySavingBadge) {
        deliverySavingBadge.classList.add('is-hidden');
        deliverySavingBadge.style.display = 'none';
      }
    }
    if (subtotalAmount) subtotalAmount.textContent = formatBRL(data.subtotal);
    const currentDelivery = zoneSelected ? finalDeliveryFee : 0;
    const finalTotal = data.subtotal + currentDelivery - remainingLoyaltyDiscount - data.couponDiscount;
    if (totalAmount) totalAmount.textContent = formatBRL(finalTotal);
  };

  const updateSummary = (fee, zoneSelected) => {
    const url = checkoutUrls.calculateTotalsUrl;
    if (url && zoneSelected && zoneSelect && zoneSelect.value) {
      const zoneId = parseInt(zoneSelect.value, 10) || 0;
      const cityId = citySelect ? (parseInt(citySelect.value, 10) || 0) : 0;
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      const csrf = csrfMeta ? csrfMeta.getAttribute('content') : '';
      fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf || ''
        },
        body: JSON.stringify({ zone_id: zoneId, city_id: cityId })
      }).then(function (r) { return r.json(); }).then(function (j) {
        if (!j.success || !j.data) {
          applyLocalSummary(fee, zoneSelected);
          return;
        }
        var d = j.data;
        data.couponDiscount = parseFloat(d.coupon_discount) || 0;
        var finalDel = parseFloat(d.final_delivery_fee) || 0;
        var delDisc = parseFloat(d.delivery_discount_applied) || 0;
        var origDel = parseFloat(d.delivery_fee) || 0;
        var remLoy = parseFloat(d.remaining_loyalty_discount) || 0;
        if (zoneSelected) {
          deliveryInput.value = finalDel.toFixed(2);
          if (delDisc > 0) {
            deliveryAmount.textContent = d.delivery_label || (finalDel > 0 ? formatBRL(finalDel) : 'Grátis');
            deliveryAmount.classList.toggle('delivery-free', finalDel <= 0);
            if (deliveryDiscountInfo) {
              var discountDiv = deliveryDiscountInfo.querySelector('div');
              if (discountDiv) {
                discountDiv.innerHTML = '<span class="delivery-original">' + formatBRL(origDel) + '</span> <span class="delivery-discount">(– ' + formatBRL(delDisc) + ')</span>';
              }
              deliveryDiscountInfo.classList.remove('is-hidden');
              deliveryDiscountInfo.style.display = '';
            }
            if (deliverySavingBadge) {
              var badgeSpan = deliverySavingBadge.querySelector('span:last-child');
              if (badgeSpan) {
                badgeSpan.textContent = 'Você economizou ' + formatBRL(delDisc) + ' na entrega';
              }
              deliverySavingBadge.classList.remove('is-hidden');
              deliverySavingBadge.style.display = '';
            }
          } else {
            deliveryAmount.textContent = d.delivery_label || (origDel > 0 ? formatBRL(origDel) : 'Grátis');
            deliveryAmount.classList.toggle('delivery-free', origDel <= 0);
            if (deliveryDiscountInfo) {
              deliveryDiscountInfo.classList.add('is-hidden');
              deliveryDiscountInfo.style.display = 'none';
            }
            if (deliverySavingBadge) {
              deliverySavingBadge.classList.add('is-hidden');
              deliverySavingBadge.style.display = 'none';
            }
          }
          if (loyaltyDiscountRow && loyaltyDiscountAmount) {
            if (remLoy > 0) {
              loyaltyDiscountRow.classList.remove('is-hidden');
              loyaltyDiscountRow.style.display = '';
              loyaltyDiscountAmount.textContent = '- ' + formatBRL(remLoy);
            } else {
              loyaltyDiscountRow.classList.add('is-hidden');
              loyaltyDiscountRow.style.display = 'none';
            }
          }
        }
        if (subtotalAmount) subtotalAmount.textContent = formatBRL(parseFloat(d.subtotal) || 0);
        if (totalAmount) totalAmount.textContent = formatBRL(parseFloat(d.total) || 0);
      }).catch(function () {
        applyLocalSummary(fee, zoneSelected);
      });
      return;
    }
    applyLocalSummary(fee, zoneSelected);
  };

  const populateZones = (cityId, preselectedZoneId) => {
    if (!zoneSelect) return [];
    const key = String(cityId);
    const zones = data.zonesByCity[key] || [];
    const zoneToSelect = parseInt(preselectedZoneId || 0, 10) || 0;
    let selectedApplied = false;
    zoneSelect.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = cityId ? 'Selecione o bairro' : 'Escolha a cidade primeiro';
    zoneSelect.appendChild(placeholder);
    zones.forEach(zone => {
      const option = document.createElement('option');
      option.value = zone.id;
      option.textContent = zone.name;
      option.dataset.fee = zone.fee;
      option.dataset.cityName = zone.city_name || '';
      option.dataset.zoneName = zone.name;
      if (zoneToSelect > 0 && parseInt(zone.id || 0, 10) === zoneToSelect) {
        option.selected = true;
        selectedApplied = true;
      }
      zoneSelect.appendChild(option);
    });
    if (!selectedApplied) {
      zoneSelect.value = '';
    }
    zoneSelect.disabled = !cityId;
    return zones;
  };

  if (citySelect) {
    citySelect.addEventListener('change', () => {
      const cityId = parseInt(citySelect.value, 10) || 0;
      syncCityName();
      populateZones(cityId, 0);
      // NÃO auto-selecionar bairro - usuário deve escolher manualmente
      zoneSelect.value = '';
      syncZoneName();
      const opt = zoneSelect.options[zoneSelect.selectedIndex];
      const fee = opt && opt.dataset.fee ? parseFloat(opt.dataset.fee) : NaN;
      updateSummary(fee, !!opt && zoneSelect.value !== '');
    });
  }

  if (zoneSelect) {
    zoneSelect.addEventListener('change', () => {
      const opt = zoneSelect.options[zoneSelect.selectedIndex];
      syncZoneName();
      const fee = opt && opt.dataset.fee ? parseFloat(opt.dataset.fee) : NaN;
      updateSummary(fee, !!opt && zoneSelect.value !== '');
    });
  }

  if (citySelect) {
    // NÃO auto-selecionar cidade - usuário deve escolher manualmente
    syncCityName();
    populateZones(parseInt(citySelect.value || '0', 10), data.selectedZoneId);
    
    // Mantém seleção inicial apenas quando já veio válida do backend
    syncZoneName();
    const opt = zoneSelect ? zoneSelect.options[zoneSelect.selectedIndex] : null;
    const fee = opt && opt.dataset.fee ? parseFloat(opt.dataset.fee) : NaN;
    // Só atualizar summary se há zona válida — senão, deixa valores do PHP intactos
    if (data.selectedZoneId > 0 && opt && zoneSelect.value !== '') {
      updateSummary(fee, true);
    }
  }

  // ======= AUTOCOMPLETE DE RUA - ENTERPRISE (DB + Redis + Overpass) =======
  (function() {
    var streetInput = document.getElementById('checkout-street');
    var acList = document.getElementById('street-autocomplete-list');
    if (!streetInput || !acList) return;

    var acTimer = null;
    var acAbort = null;
    var acIndex = -1;
    var selectedFromList = false; // Track if user picked from suggestions

    function getCityName() {
      if (!citySelect) return '';
      var opt = citySelect.options[citySelect.selectedIndex];
      return opt && opt.value ? opt.textContent.trim() : '';
    }

    function getNeighborhoodName() {
      var zoneSelect = document.getElementById('checkout-zone');
      if (!zoneSelect) return '';
      var opt = zoneSelect.options[zoneSelect.selectedIndex];
      return opt && opt.value ? (opt.getAttribute('data-zone-name') || opt.textContent.trim()) : '';
    }

    function closeList() {
      acList.innerHTML = '';
      acList.classList.remove('active');
      acIndex = -1;
    }

    // Send popularity increment (fire-and-forget)
    function trackPopularity(streetId) {
      if (!streetId || streetId <= 0) return;
      var csrfToken = document.querySelector('input[name="csrf_token"]');
      var headers = { 'Content-Type': 'application/json' };
      if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken.value;
      try {
        fetch('/' + encodeURIComponent(MM_SESSION.companySlug) + '/street-autocomplete/popularity', {
          method: 'POST',
          headers: headers,
          body: JSON.stringify({ street_id: streetId })
        }).catch(function() {});
      } catch(e) {}
    }

    // Learn a typed street that wasn't from suggestions
    function learnStreet(street) {
      if (!street || street.length < 3) return;
      var city = getCityName();
      var nb = getNeighborhoodName();
      if (!city) return;
      var csrfToken = document.querySelector('input[name="csrf_token"]');
      var headers = { 'Content-Type': 'application/json' };
      if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken.value;
      try {
        fetch('/' + encodeURIComponent(MM_SESSION.companySlug) + '/street-autocomplete/learn', {
          method: 'POST',
          headers: headers,
          body: JSON.stringify({ city: city, neighborhood: nb, street: street })
        }).catch(function() {});
      } catch(e) {}
    }

    function fetchStreets(query) {
      var city = getCityName();
      var neighborhood = getNeighborhoodName();
      if (!city || query.length < 2) {
        closeList();
        return;
      }

      // Cancel previous request
      if (acAbort) { try { acAbort.abort(); } catch(e){} }
      acAbort = new AbortController();

      acList.innerHTML = '<div class="street-autocomplete-loading">Buscando...</div>';
      acList.classList.add('active');

      var url = '/' + encodeURIComponent(MM_SESSION.companySlug) + '/street-autocomplete'
        + '?q=' + encodeURIComponent(query)
        + '&city=' + encodeURIComponent(city)
        + '&neighborhood=' + encodeURIComponent(neighborhood);

      fetch(url, { signal: acAbort.signal, cache: 'no-store' })
        .then(function(res) { return res.json(); })
        .then(function(json) {
          var results = json.results || [];
          acList.innerHTML = '';
          if (results.length === 0) {
            acList.innerHTML = '<div class="street-autocomplete-loading">Nenhuma rua encontrada</div>';
            acList.classList.add('active');
            return;
          }
          acIndex = -1;
          results.forEach(function(item) {
            var div = document.createElement('div');
            div.className = 'street-autocomplete-item';
            div.textContent = item.street;
            
            div.addEventListener('mousedown', function(e) {
              e.preventDefault();
              streetInput.value = item.street;
              selectedFromList = true;
              closeList();
              // Track popularity
              if (item.id) trackPopularity(item.id);
              var numInput = document.querySelector('input[name="address[number]"]');
              if (numInput) numInput.focus();
            });
            acList.appendChild(div);
          });
          acList.classList.add('active');
        })
        .catch(function(err) {
          if (err.name !== 'AbortError') closeList();
        });
    }

    streetInput.addEventListener('input', function() {
      selectedFromList = false;
      var val = streetInput.value.trim();
      if (val.length < 2) {
        closeList();
        return;
      }
      clearTimeout(acTimer);
      acTimer = setTimeout(function() { fetchStreets(val); }, 300);
    });

    streetInput.addEventListener('keydown', function(e) {
      var items = acList.querySelectorAll('.street-autocomplete-item');
      if (!items.length) return;

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        acIndex = Math.min(acIndex + 1, items.length - 1);
        items.forEach(function(el, i) { el.classList.toggle('active', i === acIndex); });
        items[acIndex].scrollIntoView({ block: 'nearest' });
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        acIndex = Math.max(acIndex - 1, 0);
        items.forEach(function(el, i) { el.classList.toggle('active', i === acIndex); });
        items[acIndex].scrollIntoView({ block: 'nearest' });
      } else if (e.key === 'Enter' && acIndex >= 0) {
        e.preventDefault();
        items[acIndex].dispatchEvent(new Event('mousedown'));
      } else if (e.key === 'Escape') {
        closeList();
      }
    });

    // Learn street on blur if user typed something not from suggestions
    streetInput.addEventListener('blur', function() {
      var val = streetInput.value.trim();
      if (val.length >= 5 && !selectedFromList) {
        learnStreet(val);
      }
      setTimeout(closeList, 200);
    });

    // Close on outside click
    document.addEventListener('click', function(e) {
      if (!streetInput.contains(e.target) && !acList.contains(e.target)) {
        closeList();
      }
    });
  })();

  // Validação do campo número - apenas números
  const numberInput = document.querySelector('input[name="address[number]"]');
  if (numberInput) {
    numberInput.addEventListener('input', (e) => {
      // Remove todos os caracteres que não são números
      e.target.value = e.target.value.replace(/[^\d]/g, '');
    });
    
    numberInput.addEventListener('keydown', (e) => {
      // Permitir apenas números, backspace, delete, tab e arrow keys
      const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'];
      if (!allowedKeys.includes(e.key) && (e.key < '0' || e.key > '9')) {
        e.preventDefault();
      }
    });
  }

  // Máscara para campo de telefone
  const phoneInput = document.getElementById('checkout-phone');
  if (phoneInput) {
    // Função para aplicar a máscara
    const applyPhoneMask = (value) => {
      const digits = value.replace(/\D/g, ''); // Remove tudo que não é dígito
      
      // Limita a 11 dígitos
      const limited = digits.substring(0, 11);
      
      // Aplica a máscara
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
    
    // Aplicar máscara no valor inicial (se vier do banco)
    if (phoneInput.value) {
      phoneInput.value = applyPhoneMask(phoneInput.value);
    }
    
    phoneInput.addEventListener('input', (e) => {
      e.target.value = applyPhoneMask(e.target.value);
    });
    
    phoneInput.addEventListener('keydown', (e) => {
      // Permitir apenas números, backspace, delete, tab e arrow keys
      const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'];
      if (!allowedKeys.includes(e.key) && (e.key < '0' || e.key > '9')) {
        e.preventDefault();
      }
    });
  }

  // Funções para pagamento em dinheiro
  window.calculateChange = function() {
    const cashAmountInput = document.getElementById('cash-amount');
    const changeInfo = document.getElementById('change-info');
    const changeAmount = document.getElementById('change-amount');
    const orderTotalDisplay = document.getElementById('order-total-display');
    const cashError = document.getElementById('cash-error');
    
    if (!cashAmountInput || !changeInfo || !changeAmount || !orderTotalDisplay || !cashError) return;
    
    const cashValue = parseFloat(cashAmountInput.value) || 0;
    const orderTotal = (function() {
      // tenta extrair número do elemento total-amount (formatado)
      const el = document.getElementById('total-amount');
      if (!el) return 0;
      const text = el.textContent || '';
      // Remover tudo que não seja dígito ou vírgula/ponto
      const digits = text.replace(/[^\d,\.]/g, '').replace(',', '.');
      return parseFloat(digits) || 0;
    })();
    
    // Atualizar display do total do pedido
    orderTotalDisplay.textContent = `R$ ${orderTotal.toFixed(2).replace('.', ',')}`;
    
    if (cashValue === 0) {
      changeInfo.style.display = 'none';
      cashError.style.display = 'none';
      return;
    }
    
    if (cashValue < orderTotal) {
      changeInfo.style.display = 'none';
      cashError.style.display = 'block';
      cashError.textContent = `Valor insuficiente. Você informou R$ ${cashValue.toFixed(2).replace('.', ',')}, mas o total é R$ ${orderTotal.toFixed(2).replace('.', ',')}`;
      return;
    }
    
    const change = cashValue - orderTotal;
    changeAmount.textContent = `R$ ${change.toFixed(2).replace('.', ',')}`;
    changeInfo.style.display = 'block';
    cashError.style.display = 'none';
  };

  const cashAmountInput = document.getElementById('cash-amount');
  if (cashAmountInput) {
    cashAmountInput.addEventListener('input', () => {
      window.calculateChange();
    });
  }

  // Reaplica calculateChange quando updateSummary é chamado (em caso de cash ativo)
  const originalUpdateSummary = window.updateSummary;
  window.updateSummary = function(fee, hasValidZone) {
    if (originalUpdateSummary) {
      try { originalUpdateSummary(fee, hasValidZone); } catch(e) {}
    }
    // Recalcular troco se o pagamento em dinheiro estiver ativo
    if (selectedPaymentType === 'cash') {
      setTimeout(calculateChange, 100);
    }
  };

  // ====== Gerenciamento de endereços salvos ======
  const savedAddressesList = document.getElementById('saved-addresses-list');
  const manualAddressForm = document.getElementById('manual-address-form');
  const addNewAddressBtn = document.getElementById('add-new-address-btn');
  const cancelNewAddressBtn = document.getElementById('cancel-new-address-btn');
  const useSavedAddressInput = document.getElementById('use-saved-address');

  if (addNewAddressBtn && manualAddressForm && savedAddressesList) {
    // Função para gerenciar campos required E disabled
    const toggleManualFormRequired = (enable) => {
      // Desabilitar/habilitar TODOS os campos do formulário manual
      const allFields = manualAddressForm.querySelectorAll('input, select, textarea');
      allFields.forEach(field => {
        if (enable) {
          field.removeAttribute('disabled');
          // Restaurar required se era required antes
          if (field.hasAttribute('data-was-required')) {
            field.setAttribute('required', 'required');
          }
        } else {
          field.setAttribute('disabled', 'disabled');
          // Salvar estado de required antes de remover
          if (field.hasAttribute('required')) {
            field.setAttribute('data-was-required', 'true');
            field.removeAttribute('required');
          }
        }
      });
    };
    
    // Mostrar formulário manual
    addNewAddressBtn.addEventListener('click', () => {
      manualAddressForm.style.display = 'block';
      if (savedAddressesList.parentElement) {
        savedAddressesList.parentElement.style.display = 'none';
      }
      if (useSavedAddressInput) {
        useSavedAddressInput.value = '0';
      }
      // Desmarcar todos os radio buttons de endereços salvos
      document.querySelectorAll('.address-radio').forEach(radio => {
        radio.checked = false;
      });
      // Limpar campos hidden de endereço salvo
      document.getElementById('saved-address-name').value = '';
      document.getElementById('saved-address-phone').value = '';
      document.getElementById('saved-address-street').value = '';
      document.getElementById('saved-address-number').value = '';
      document.getElementById('saved-address-complement').value = '';
      document.getElementById('saved-address-reference').value = '';
      document.getElementById('saved-address-city-id').value = '';
      document.getElementById('saved-address-zone-id').value = '';
      document.getElementById('saved-address-city').value = '';
      document.getElementById('saved-address-neighborhood').value = '';
      // Habilitar campos required
      toggleManualFormRequired(true);
    });

    // Voltar para endereços salvos
    if (cancelNewAddressBtn) {
      cancelNewAddressBtn.addEventListener('click', () => {
        manualAddressForm.style.display = 'none';
        if (savedAddressesList.parentElement) {
          savedAddressesList.parentElement.style.display = 'block';
        }
        if (useSavedAddressInput) {
          useSavedAddressInput.value = '1';
        }
        // Selecionar o primeiro endereço
        const firstRadio = document.querySelector('.address-radio');
        if (firstRadio) {
          firstRadio.checked = true;
          firstRadio.dispatchEvent(new Event('change'));
        }
        // Desabilitar campos required
        toggleManualFormRequired(false);
      });
    }
    
    // Desabilitar campos required inicialmente se houver endereços salvos
    if (useSavedAddressInput && useSavedAddressInput.value === '1') {
      toggleManualFormRequired(false);
    }

    // Atualizar visual quando selecionar um endereço
    document.querySelectorAll('.address-radio').forEach(radio => {
      radio.addEventListener('change', function() {
        // Remover classe 'selected' de todos
        document.querySelectorAll('.address-option').forEach(opt => {
          opt.classList.remove('selected');
        });
        // Adicionar ao selecionado
        if (this.checked) {
          this.closest('.address-option').classList.add('selected');
          
          // Preencher campos hidden com dados do endereço salvo
          document.getElementById('saved-address-name').value = this.dataset.addressName || '';
          document.getElementById('saved-address-phone').value = this.dataset.addressPhone || '';
          document.getElementById('saved-address-street').value = this.dataset.addressStreet || '';
          document.getElementById('saved-address-number').value = this.dataset.addressNumber || '';
          document.getElementById('saved-address-complement').value = this.dataset.addressComplement || '';
          document.getElementById('saved-address-reference').value = this.dataset.addressReference || '';
          document.getElementById('saved-address-city-id').value = this.dataset.cityId || '';
          document.getElementById('saved-address-zone-id').value = this.dataset.zoneId || '';
          document.getElementById('saved-address-city').value = this.dataset.addressCity || '';
          document.getElementById('saved-address-neighborhood').value = this.dataset.addressNeighborhood || '';
          
          // Atualizar taxa de entrega com base no endereço selecionado
          const zoneId = parseInt(this.dataset.zoneId || '0', 10);
          const fee = parseFloat(this.dataset.fee || '0');
          
          if (zoneId > 0) {
            // Atualizar o resumo com a taxa do endereço selecionado
            if (typeof updateSummary === 'function') {
              updateSummary(fee, true);
            } else if (typeof window.updateSummary === 'function') {
              window.updateSummary(fee, true);
            }
            
            // Atualizar input hidden da taxa de entrega
            const deliveryInput = document.getElementById('delivery-fee-input');
            if (deliveryInput) {
              deliveryInput.value = fee.toFixed(2);
            }
          }
        }
      });
    });

    // Clicar no card também seleciona o radio
    document.querySelectorAll('.address-option').forEach(option => {
      option.addEventListener('click', function(e) {
        if (e.target.tagName !== 'INPUT') {
          const radio = this.querySelector('.address-radio');
          if (radio) {
            radio.checked = true;
            radio.dispatchEvent(new Event('change'));
          }
        }
      });
    });
    
    // Aplicar taxa do endereço pré-selecionado ao carregar a página
    const preSelectedRadio = document.querySelector('.address-radio:checked');
    
    if (preSelectedRadio) {
      // Preencher campos hidden com dados do endereço pré-selecionado
      document.getElementById('saved-address-name').value = preSelectedRadio.dataset.addressName || '';
      document.getElementById('saved-address-phone').value = preSelectedRadio.dataset.addressPhone || '';
      document.getElementById('saved-address-street').value = preSelectedRadio.dataset.addressStreet || '';
      document.getElementById('saved-address-number').value = preSelectedRadio.dataset.addressNumber || '';
      document.getElementById('saved-address-complement').value = preSelectedRadio.dataset.addressComplement || '';
      document.getElementById('saved-address-reference').value = preSelectedRadio.dataset.addressReference || '';
      document.getElementById('saved-address-city-id').value = preSelectedRadio.dataset.cityId || '';
      document.getElementById('saved-address-zone-id').value = preSelectedRadio.dataset.zoneId || '';
      document.getElementById('saved-address-city').value = preSelectedRadio.dataset.addressCity || '';
      document.getElementById('saved-address-neighborhood').value = preSelectedRadio.dataset.addressNeighborhood || '';
      
      const zoneId = parseInt(preSelectedRadio.dataset.zoneId || '0', 10);
      const fee = parseFloat(preSelectedRadio.dataset.fee || '0');
      
      if (zoneId > 0) {
        // Atualizar o resumo com a taxa do endereço pré-selecionado
        if (typeof updateSummary === 'function') {
          updateSummary(fee, true);
        } else if (typeof window.updateSummary === 'function') {
          window.updateSummary(fee, true);
        }
        
        // Atualizar input hidden da taxa de entrega
        const deliveryInput = document.getElementById('delivery-fee-input');
        if (deliveryInput) {
          deliveryInput.value = fee.toFixed(2);
        }
      }
    }
  }
})();
(() => {
  // Limpar dados antigos de pedidos anteriores
  try {
    sessionStorage.removeItem('checkoutFormData');
    localStorage.removeItem('checkoutFormData');
    document.cookie = 'checkoutFormData=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    sessionStorage.removeItem('orderSummary');
    localStorage.removeItem('orderSummary');
    document.cookie = 'orderSummary=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
  } catch (e) {}
  
  const hasMultipleAddresses = !!checkoutFlags.hasMultipleAddresses;
  
  const form = document.getElementById('checkout-form');
  const modal = document.getElementById('address-confirmation-modal');
  const modalAddressDisplay = document.getElementById('modal-address-display');
  const confirmBtn = document.getElementById('confirm-address-btn');
  const changeBtn = document.getElementById('change-address-btn');
  
  let isConfirmed = false;
  let shouldShowModal = hasMultipleAddresses; // Só mostra modal se tiver múltiplos endereços
  let _modalAddressOpener = null;

  // Verificar se deve mostrar modal (2+ endereços)
  const savedAddressRadios = document.querySelectorAll('input[name="selected_address_id"]');
  shouldShowModal = savedAddressRadios.length >= 2;

  // Verifica se o topo da seção já foi visualizado pelo usuário
  // Verifica se o topo do elemento está na viewport (acima do footer fixo)
  function hasUserSeenElement(el) {
    if (!el) return true;
    var rect = el.getBoundingClientRect();
    var windowHeight = window.innerHeight || document.documentElement.clientHeight;
    return rect.top < windowHeight - 90;
  }

  // Rastrear quais seções o usuário já viu ao rolar
  var seenSummary = false;
  var seenPayment = false;

  function checkVisibility() {
    var summaryEl = document.getElementById('checkout-summary');
    var paymentEl = document.getElementById('checkout-payment');
    // Resumo: só conta como "visto" quando está bem exposto (topo próximo ao topo da tela)
    if (!seenSummary && summaryEl) {
      if (summaryEl.getBoundingClientRect().top < 150) seenSummary = true;
    }
    // Pagamento: só é rastreado DEPOIS de o resumo ter sido visto
    if (seenSummary && !seenPayment && paymentEl && hasUserSeenElement(paymentEl)) seenPayment = true;
  }

  // Verificar visibilidade no scroll
  window.addEventListener('scroll', checkVisibility, { passive: true });
  // Verificar estado inicial (caso a página já esteja scrollada)
  checkVisibility();

  // Interceptar o submit do formulário
  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      e.stopPropagation();

      var summaryEl = document.getElementById('checkout-summary');
      var paymentEl = document.getElementById('checkout-payment');

      // Primeiro clique: se não viu resumo → mostrar resumo; se já viu → ir para pagamento
      if (!seenSummary && summaryEl) {
        summaryEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        seenSummary = true;
        return;
      }

      if (!seenPayment && paymentEl) {
        paymentEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        seenPayment = true;
        return;
      }

      // Ambas seções já vistas — prosseguir com submit
      if (shouldShowModal && !isConfirmed) {
        showConfirmationModal();
      } else {
        submitFormAndRedirect();
      }
    });
  }

  function submitFormAndRedirect() {
    const form = document.getElementById('checkout-form');
    
    if (!form) {
      console.error('❌ ERRO CRÍTICO: Formulário checkout-form não encontrado!');
      return;
    }
    
    // Validar telefone/WhatsApp obrigatório antes de enviar
    const phoneEl = document.getElementById('checkout-phone');
    const phoneVal = phoneEl ? phoneEl.value.replace(/\D/g, '') : '';
    if (phoneVal.length < 10) {
      if (phoneEl) {
        phoneEl.style.borderColor = '#ef4444';
        phoneEl.focus();
        phoneEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      const hintEl = document.getElementById('checkout-phone-hint');
      if (hintEl) {
        hintEl.textContent = '⚠ Informe seu WhatsApp com DDD. Precisamos para contato sobre a entrega.';
        hintEl.style.color = '#ef4444';
      }
      if (typeof window.showToast === 'function') {
        window.showToast('Informe seu WhatsApp com DDD para finalizar o pedido.', 'error');
      }
      return;
    }
    
    // Desabilitar botão para evitar duplo clique
    const submitBtn = document.querySelector('button.cta[type="submit"]');
    if (submitBtn) {
      submitBtn.disabled = true;
    }
    
    // IMPORTANTE: Garantir que os campos hidden estão preenchidos antes de enviar
    // Remover optional chaining para compatibilidade iOS 13 (iPhone 11)
    const usingSavedAddressEl = document.getElementById('use-saved-address');
    const usingSavedAddress = usingSavedAddressEl ? usingSavedAddressEl.value === '1' : false;
    
    if (usingSavedAddress) {
      const selectedRadio = document.querySelector('.address-radio:checked');
      if (selectedRadio) {
        // Preencher AGORA os campos hidden
        document.getElementById('saved-address-name').value = selectedRadio.dataset.addressName || '';
        document.getElementById('saved-address-phone').value = selectedRadio.dataset.addressPhone || '';
        document.getElementById('saved-address-street').value = selectedRadio.dataset.addressStreet || '';
        document.getElementById('saved-address-number').value = selectedRadio.dataset.addressNumber || '';
        document.getElementById('saved-address-complement').value = selectedRadio.dataset.addressComplement || '';
        document.getElementById('saved-address-reference').value = selectedRadio.dataset.addressReference || '';
        document.getElementById('saved-address-city-id').value = selectedRadio.dataset.cityId || '';
        document.getElementById('saved-address-zone-id').value = selectedRadio.dataset.zoneId || '';
        document.getElementById('saved-address-city').value = selectedRadio.dataset.addressCity || '';
        document.getElementById('saved-address-neighborhood').value = selectedRadio.dataset.addressNeighborhood || '';
      }
    }
    
    // Coletar informações do resumo do pedido para o sessionStorage
    // SEM optional chaining para compatibilidade iOS 13
    const summaryItems = [];
    const summaryWrappers = document.querySelectorAll('.summary-item-wrapper');
    
    document.querySelectorAll('.summary-item-wrapper').forEach(wrapper => {
      const mainItem = wrapper.querySelector('.summary-item');
      const productEl = mainItem ? mainItem.querySelector('.product') : null;
      const priceEl = mainItem ? mainItem.querySelector('.price') : null;
      const product = productEl ? productEl.textContent : '';
      const price = priceEl ? priceEl.textContent : '';
      
      const itemData = { product, price, details: [] };
      
      // Coletar complementos/detalhes
      const details = wrapper.querySelectorAll('.summary-item-detail');
      details.forEach(detail => {
        const spans = detail.querySelectorAll('span');
        const detailName = spans[0] ? spans[0].textContent : '';
        // Se tiver 2 spans, o segundo é o preço. Se tiver 1, não tem preço (incluso)
        const detailPrice = spans.length > 1 ? (spans[1] ? spans[1].textContent : '') : '';
        if (detailName) {
          itemData.details.push({ name: detailName, price: detailPrice });
        }
      });
      
      summaryItems.push(itemData);
    });
    
    const subtotalEl = document.getElementById('subtotal-amount');
    const totalEl = document.getElementById('total-amount');
    const deliveryEl = document.getElementById('delivery-amount');
    const loyaltyDiscountEl = document.getElementById('loyalty-discount-amount');
    
    // Função para salvar cookie (fallback para dispositivos iOS/Safari)
    function setCookie(name, value, minutes = 10) {
      const date = new Date();
      date.setTime(date.getTime() + (minutes * 60 * 1000));
      const expires = "expires=" + date.toUTCString();
      document.cookie = name + "=" + encodeURIComponent(value) + ";" + expires + ";path=/;SameSite=Lax";
    }
    
    // Função para ler cookie
    function getCookie(name) {
      const nameEQ = name + "=";
      const ca = document.cookie.split(';');
      for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
      }
      return null;
    }
    
    // Salvar dados do formulário para a página de processing
    if (form) {
      const formData = new FormData(form);
      const formDataObj = {};
      formData.forEach((value, key) => {
        formDataObj[key] = value;
      });
      
      const checkoutData = JSON.stringify({
        action: form.action,
        data: formDataObj
      });
      
      // 1. TENTAR SESSIONSTORAGE (3 TENTATIVAS)
      let sessionSaved = false;
      for (let attempt = 1; attempt <= 3; attempt++) {
        try {
          sessionStorage.setItem('checkoutFormData', checkoutData);
          const test = sessionStorage.getItem('checkoutFormData');
          if (test && test.length > 100) {
            sessionSaved = true;
            break;
          }
        } catch (e) {
          console.warn('Erro sessionStorage:', e);
        }
      }
      
      // 2. SALVAR EM COOKIE (fallback iOS/Safari) - 3 TENTATIVAS
      let cookieSaved = false;
      for (let attempt = 1; attempt <= 3; attempt++) {
        try {
          setCookie('checkoutFormData', checkoutData, 10);
          const testCookie = getCookie('checkoutFormData');
          if (testCookie && testCookie.length > 100) {
            cookieSaved = true;
            break;
          }
        } catch (e) {
          console.warn('Erro cookie:', e);
        }
      }
      
      // 3. ÚLTIMO RECURSO: LOCALSTORAGE
      let localSaved = false;
      for (let attempt = 1; attempt <= 3; attempt++) {
        try {
          localStorage.setItem('checkoutFormData', checkoutData);
          const testLocal = localStorage.getItem('checkoutFormData');
          if (testLocal && testLocal.length > 100) {
            localSaved = true;
            break;
          }
        } catch (e) {
          console.warn('Erro localStorage:', e);
        }
      }
      
      if (!sessionSaved && !cookieSaved && !localSaved) {
        alert('AVISO: Houve um problema ao salvar os dados. Por favor, contacte o suporte.');
      }
    }
    
    // Capturar a forma de pagamento no momento do submit
    let currentPaymentMethodName = '';
    
    // Usar variáveis globais expostas
    const globalMethodId = typeof window.checkoutSelectedMethodId !== 'undefined' ? window.checkoutSelectedMethodId : 0;
    const globalMethods = typeof window.checkoutPaymentMethods !== 'undefined' ? window.checkoutPaymentMethods : null;
    
    if (globalMethodId > 0 && globalMethods && globalMethods[globalMethodId]) {
      currentPaymentMethodName = globalMethods[globalMethodId].name || '';
    }
    
    // Fallback: Ler do input hidden payment-method-id
    if (!currentPaymentMethodName) {
      const paymentMethodInput = document.getElementById('payment-method-id');
      const hiddenMethodId = paymentMethodInput ? parseInt(paymentMethodInput.value, 10) : 0;
      
      if (hiddenMethodId > 0 && globalMethods && globalMethods[hiddenMethodId]) {
        currentPaymentMethodName = globalMethods[hiddenMethodId].name || '';
      }
    }
    
    // Fallback: Tentar obter pelo tipo de pagamento ativo (botão com classe 'active')
    if (!currentPaymentMethodName) {
      const activePaymentBtn = document.querySelector('.payment-type-btn.active');
      
      if (activePaymentBtn) {
        const paymentType = activePaymentBtn.getAttribute('data-type');
        const titleEl = activePaymentBtn.querySelector('.payment-title');
        
        if (titleEl && titleEl.textContent) {
          currentPaymentMethodName = titleEl.textContent.trim();
        } else if (paymentType) {
          const typeNames = {
            'pix': 'PIX',
            'cash': 'Dinheiro',
            'credit': 'Cartão de Crédito',
            'debit': 'Cartão de Débito',
            'voucher': 'Voucher',
            'others': 'Outros'
          };
          currentPaymentMethodName = typeNames[paymentType] || paymentType;
        }
      }
    }
    
    // Último fallback: Tentar obter a marca/método selecionado
    if (!currentPaymentMethodName) {
      const activeBrandBtn = document.querySelector('.brand-btn.active');
      
      if (activeBrandBtn) {
        const brandSpan = activeBrandBtn.querySelector('span');
        if (brandSpan && brandSpan.textContent) {
          currentPaymentMethodName = brandSpan.textContent.trim();
        }
      }
    }
    
    // Capturar tipo de pagamento
    let currentPaymentType = '';
    const activePaymentTypeBtn = document.querySelector('.payment-type-btn.active');
    if (activePaymentTypeBtn) {
      currentPaymentType = activePaymentTypeBtn.getAttribute('data-type') || '';
    }
    
    const deliveryOriginalEl = document.querySelector('#delivery-discount-info .delivery-original');
    const deliveryOriginalText = deliveryOriginalEl ? deliveryOriginalEl.textContent.trim() : '';
    const deliveryBaseText = deliveryOriginalText || (deliveryEl ? deliveryEl.textContent : '');
    const loyaltyDiscountTotalValue = (typeof data !== 'undefined' && typeof data.loyaltyDiscount === 'number')
      ? data.loyaltyDiscount
      : 0;
    const loyaltyDiscountTotalText = loyaltyDiscountTotalValue > 0
      ? '- ' + formatBRL(loyaltyDiscountTotalValue)
      : null;

    // Criar orderSummaryData com a forma de pagamento
    const updatedOrderSummaryData = JSON.stringify({
      items: summaryItems,
      subtotal: subtotalEl ? subtotalEl.textContent : '',
      delivery: deliveryEl ? deliveryEl.textContent : '',
      deliveryOriginal: deliveryBaseText,
      loyaltyDiscount: loyaltyDiscountEl ? loyaltyDiscountEl.textContent : null,
      loyaltyDiscountTotal: loyaltyDiscountTotalText,
      total: totalEl ? totalEl.textContent : '',
      paymentMethod: currentPaymentMethodName,
      paymentType: currentPaymentType
    });
    
    // Salvar orderSummary em múltiplos lugares
    let summarySessionSaved = false;
    for (let attempt = 1; attempt <= 3; attempt++) {
      try {
        sessionStorage.setItem('orderSummary', updatedOrderSummaryData);
        const test = sessionStorage.getItem('orderSummary');
        if (test && test.length > 50) {
          summarySessionSaved = true;
          break;
        }
      } catch (e) {
        console.warn('Erro sessionStorage orderSummary:', e);
      }
    }
    
    // Cookie
    let summaryCookieSaved = false;
    for (let attempt = 1; attempt <= 3; attempt++) {
      try {
        setCookie('orderSummary', updatedOrderSummaryData, 10);
        const test = getCookie('orderSummary');
        if (test && test.length > 50) {
          summaryCookieSaved = true;
          break;
        }
      } catch (e) {
        console.warn('Erro cookie orderSummary:', e);
      }
    }
    
    // LocalStorage
    let summaryLocalSaved = false;
    for (let attempt = 1; attempt <= 3; attempt++) {
      try {
        localStorage.setItem('orderSummary', updatedOrderSummaryData);
        const test = localStorage.getItem('orderSummary');
        if (test && test.length > 50) {
          summaryLocalSaved = true;
          break;
        }
      } catch (e) {
        console.warn('Erro localStorage orderSummary:', e);
      }
    }
    
    // Auto-copiar chave PIX se pagamento selecionado for PIX
    if (currentPaymentType === 'pix') {
      var pixMethodId = globalMethodId || 0;
      var pixKeyValue = '';
      if (pixMethodId > 0 && globalMethods && globalMethods[pixMethodId]) {
        pixKeyValue = globalMethods[pixMethodId].pix_key || '';
      }
      if (pixKeyValue) {
        try {
          if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(pixKeyValue).catch(function() {});
          } else {
            var ta = document.createElement('textarea');
            ta.value = pixKeyValue;
            ta.style.position = 'fixed';
            ta.style.left = '-999999px';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            try { document.execCommand('copy'); } catch (_) {}
            document.body.removeChild(ta);
          }
        } catch (_) {}
      }
    }

    // Aguardar um pouco para garantir persistência
    setTimeout(() => {
      if (form) {
        form.submit();
      }
    }, 300);
  }

  function _addressModalTrapFocus(e) {
    if (!modal || !modal.classList.contains('show')) return;
    if (e.key === 'Escape') { hideModal(); return; }
    if (e.key !== 'Tab') return;
    const focusable = Array.from(modal.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    )).filter(function(el) { return !el.disabled; });
    if (!focusable.length) { e.preventDefault(); return; }
    var first = focusable[0];
    var last = focusable[focusable.length - 1];
    if (e.shiftKey) {
      if (document.activeElement === first) { e.preventDefault(); last.focus(); }
    } else {
      if (document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  }

  function showConfirmationModal() {
    _modalAddressOpener = document.activeElement;

    // Limpar conteúdo anterior
    while (modalAddressDisplay.firstChild) {
      modalAddressDisplay.removeChild(modalAddressDisplay.firstChild);
    }

    const selectedRadio = document.querySelector('input[name="selected_address_id"]:checked');
    const usingSavedAddressEl = document.getElementById('use-saved-address');
    const usingSavedAddress = usingSavedAddressEl ? usingSavedAddressEl.value === '1' : false;

    const labelDiv = document.createElement('div');
    labelDiv.className = 'modal-address-label';
    const detailsDiv = document.createElement('div');
    detailsDiv.className = 'modal-address-details';

    if (selectedRadio && usingSavedAddress) {
      // Endereço salvo — usar data attributes (já escapados pelo servidor)
      const addrName = selectedRadio.dataset.addressName || '';
      const addrStreet = selectedRadio.dataset.addressStreet || '';
      const addrNumber = selectedRadio.dataset.addressNumber || '';
      const addrComplement = selectedRadio.dataset.addressComplement || '';
      const addrNeighborhood = selectedRadio.dataset.addressNeighborhood || '';
      const addrCity = selectedRadio.dataset.addressCity || '';
      const addrPhone = selectedRadio.dataset.addressPhone || '';

      const addressOption = selectedRadio.closest('.address-option');
      const labelEl = addressOption ? addressOption.querySelector('.badge-label') : null;
      const label = labelEl ? labelEl.textContent.trim() : '';

      labelDiv.textContent = label ? label + ' - ' + addrName : addrName;

      detailsDiv.appendChild(document.createTextNode(
        addrStreet + ', ' + addrNumber + (addrComplement ? ' - ' + addrComplement : '')
      ));
      if (addrNeighborhood) {
        detailsDiv.appendChild(document.createElement('br'));
        detailsDiv.appendChild(document.createTextNode(addrNeighborhood));
      }
      if (addrCity) {
        detailsDiv.appendChild(document.createElement('br'));
        detailsDiv.appendChild(document.createTextNode(addrCity));
      }
      detailsDiv.appendChild(document.createElement('br'));
      var phoneStrong = document.createElement('strong');
      phoneStrong.textContent = 'Telefone: ';
      detailsDiv.appendChild(phoneStrong);
      detailsDiv.appendChild(document.createTextNode(addrPhone));
    } else {
      // Endereço manual
      const nameInput = document.querySelector('input[name="address[name]"]');
      const phoneInput = document.querySelector('input[name="address[phone]"]');
      const streetInput = document.querySelector('input[name="address[street]"]');
      const numberInput = document.querySelector('input[name="address[number]"]');
      const complementInput = document.querySelector('input[name="address[complement]"]');
      const zoneSelect = document.getElementById('checkout-zone');
      const citySelect = document.getElementById('checkout-city');

      const name = nameInput ? nameInput.value : '';
      const phone = phoneInput ? phoneInput.value : '';
      const street = streetInput ? streetInput.value : '';
      const number = numberInput ? numberInput.value : '';
      const complement = complementInput ? complementInput.value : '';
      const zone = (zoneSelect && zoneSelect.selectedIndex >= 0 && zoneSelect.options[zoneSelect.selectedIndex])
        ? zoneSelect.options[zoneSelect.selectedIndex].textContent : '';
      const city = (citySelect && citySelect.selectedIndex >= 0 && citySelect.options[citySelect.selectedIndex])
        ? citySelect.options[citySelect.selectedIndex].textContent : '';

      labelDiv.textContent = name;

      detailsDiv.appendChild(document.createTextNode(
        street + ', ' + number + (complement ? ' - ' + complement : '')
      ));
      detailsDiv.appendChild(document.createElement('br'));
      detailsDiv.appendChild(document.createTextNode(zone + ' - ' + city));
      detailsDiv.appendChild(document.createElement('br'));
      var phoneStrong2 = document.createElement('strong');
      phoneStrong2.textContent = 'Telefone: ';
      detailsDiv.appendChild(phoneStrong2);
      detailsDiv.appendChild(document.createTextNode(phone));
    }

    modalAddressDisplay.appendChild(labelDiv);
    modalAddressDisplay.appendChild(detailsDiv);

    modal.classList.add('show');
    document.body.style.overflow = 'hidden';

    document.addEventListener('keydown', _addressModalTrapFocus);
    var firstBtn = modal.querySelector('.modal-btn');
    if (firstBtn) firstBtn.focus();
  }

  function hideModal() {
    modal.classList.remove('show');
    document.body.style.overflow = '';
    document.removeEventListener('keydown', _addressModalTrapFocus);
    if (_modalAddressOpener && typeof _modalAddressOpener.focus === 'function') {
      _modalAddressOpener.focus();
    }
    _modalAddressOpener = null;
  }

  // Confirmar endereço e enviar formulário
  if (confirmBtn) {
    confirmBtn.addEventListener('click', () => {
      isConfirmed = true;
      hideModal();
      
      // Submeter e redirecionar
      submitFormAndRedirect();
    });
  }

  // Fechar modal e permitir alterar endereço
  if (changeBtn) {
    changeBtn.addEventListener('click', () => {
      hideModal();
    });
  }

  // Fechar modal clicando fora
  modal.addEventListener('click', (e) => {
    if (e.target === modal) {
      hideModal();
    }
  });
})();
