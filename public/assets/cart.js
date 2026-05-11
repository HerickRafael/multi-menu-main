(function () {
  function readConfig() {
    var el = document.getElementById('cart-page-config');
    if (!el) {
      return {};
    }

    try {
      return JSON.parse(el.textContent || '{}');
    } catch (err) {
      return {};
    }
  }

  var config = readConfig();

  function bindToggleRows() {
    var buttons = document.querySelectorAll('.toggle-row');
    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var targetId = btn.getAttribute('data-target');
        var ext = targetId ? document.getElementById(targetId) : null;
        var item = btn.closest('.item');
        var open = !(ext && ext.classList.contains('open'));

        if (ext) {
          ext.classList.toggle('open', open);
        }
        if (item) {
          item.classList.toggle('open', open);
        }

        btn.classList.toggle('open', open);
        btn.setAttribute('aria-expanded', String(open));
      });
    });
  }

  function bindLinkedToggles() {
    var rows = document.querySelectorAll('.linked.toggle');
    rows.forEach(function (row) {
      row.addEventListener('click', function () {
        var nextState = !row.classList.contains('open');
        row.classList.toggle('open', nextState);
        row.setAttribute('aria-expanded', String(nextState));
      });
    });
  }

  function bindEditableSectionTitles() {
    var editableTitles = document.querySelectorAll('.section-title.editable[data-edit-url]');
    editableTitles.forEach(function (title) {
      title.addEventListener('click', function () {
        var editUrl = title.getAttribute('data-edit-url');
        if (editUrl) {
          window.location.href = editUrl;
        }
      });
    });
  }

  function injectCsrfIntoPostForms() {
    var tokenMeta = document.querySelector('meta[name="csrf-token"]');
    if (!tokenMeta || !tokenMeta.content) {
      return;
    }

    var token = tokenMeta.content;
    document.querySelectorAll('form').forEach(function (form) {
      var method = (form.getAttribute('method') || '').toUpperCase();
      if (method !== 'POST') {
        return;
      }

      if (form.querySelector('input[name="csrf_token"]')) {
        return;
      }

      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'csrf_token';
      input.value = token;
      form.appendChild(input);
    });

    window._csrfToken = token;
  }

  function syncCouponSessionForUser() {
    var currentUserPhone = String(config.currentUserPhone || '');
    var lastUserPhone = sessionStorage.getItem('lastUserPhone');

    if (lastUserPhone && lastUserPhone !== currentUserPhone) {
      sessionStorage.removeItem('couponCode');
      sessionStorage.removeItem('couponDiscount');
    }

    if (currentUserPhone) {
      sessionStorage.setItem('lastUserPhone', currentUserPhone);
    }
  }

  function renderAppliedCouponState(couponBtn, couponCode, couponDiscount) {
    couponBtn.innerHTML = '' +
      '<svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16">' +
      '  <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>' +
      '</svg>' +
      '<span class="coupon-status-text">Cupom ' + couponCode + ' aplicado (-' + couponDiscount + '%)</span>' +
      '<button class="coupon-remove-btn" data-action="remove-coupon" type="button">Remover</button>';

    couponBtn.classList.add('coupon-btn--applied');

    var removeBtn = couponBtn.querySelector('[data-action="remove-coupon"]');
    if (removeBtn) {
      removeBtn.addEventListener('click', function (event) {
        event.stopPropagation();
        removeCoupon();
      });
    }
  }

  function createCouponModal(couponPrefix) {
    var modal = document.createElement('div');
    modal.className = 'cart-coupon-modal';

    modal.innerHTML = '' +
      '<div class="cart-coupon-modal__dialog">' +
      '  <h3 class="cart-coupon-modal__title">Cupom de desconto</h3>' +
      '  <p class="cart-coupon-modal__description">Digite o código do seu cupom para aplicar o desconto</p>' +
      '  <input type="text" id="coupon-input" class="cart-coupon-modal__input" placeholder="Ex: ' + couponPrefix + '123ABC">' +
      '  <div id="coupon-error" class="cart-coupon-modal__error"></div>' +
      '  <div class="cart-coupon-modal__actions">' +
      '    <button id="cancel-coupon" class="cart-coupon-modal__button cart-coupon-modal__button--cancel" type="button">Cancelar</button>' +
      '    <button id="apply-coupon" class="cart-coupon-modal__button cart-coupon-modal__button--apply" type="button">Aplicar cupom</button>' +
      '  </div>' +
      '</div>';

    return modal;
  }

  function removeCoupon() {
    if (window.confirm('Deseja remover o cupom de desconto?')) {
      sessionStorage.removeItem('couponCode');
      sessionStorage.removeItem('couponDiscount');
      window.location.reload();
    }
  }

  function bindCouponFlow() {
    var couponBtn = document.getElementById('coupon-btn');
    if (!couponBtn) {
      return;
    }

    var couponApplied = sessionStorage.getItem('couponCode');
    var couponDiscount = sessionStorage.getItem('couponDiscount');

    if (couponApplied && couponDiscount) {
      renderAppliedCouponState(couponBtn, couponApplied, couponDiscount);
    }

    couponBtn.addEventListener('click', function () {
      if (sessionStorage.getItem('couponCode')) {
        return;
      }

      var couponPrefix = String(config.couponPlaceholderPrefix || 'WOLL');
      var validateCouponUrl = String(config.validateCouponUrl || '');
      var modal = createCouponModal(couponPrefix);

      document.body.appendChild(modal);

      var couponInput = document.getElementById('coupon-input');
      var errorDiv = document.getElementById('coupon-error');
      var cancelBtn = document.getElementById('cancel-coupon');
      var applyBtn = document.getElementById('apply-coupon');

      if (couponInput) {
        couponInput.focus();
      }

      modal.addEventListener('click', function (event) {
        if (event.target === modal) {
          document.body.removeChild(modal);
        }
      });

      if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
          document.body.removeChild(modal);
        });
      }

      if (applyBtn) {
        applyBtn.addEventListener('click', function () {
          if (!couponInput || !errorDiv) {
            return;
          }

          var code = (couponInput.value || '').trim().toUpperCase();
          if (!code) {
            errorDiv.textContent = 'Digite um código de cupom';
            errorDiv.style.display = 'block';
            return;
          }

          applyBtn.disabled = true;
          applyBtn.textContent = 'Validando...';
          errorDiv.style.display = 'none';

          var controller = new AbortController();
          var timeoutId = setTimeout(function () {
            controller.abort();
          }, 10000);

          fetch(validateCouponUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ coupon_code: code }),
            signal: controller.signal,
          })
            .then(function (response) {
              clearTimeout(timeoutId);
              return response.json();
            })
            .then(function (result) {
              if (result && result.success) {
                sessionStorage.setItem('couponCode', code);
                sessionStorage.setItem('couponDiscount', result.discount);
                document.body.removeChild(modal);
                window.location.reload();
                return;
              }

              errorDiv.textContent = (result && result.message) || 'Cupom inválido ou já utilizado';
              errorDiv.style.display = 'block';
              applyBtn.disabled = false;
              applyBtn.textContent = 'Aplicar';
            })
            .catch(function (error) {
              if (error && error.name === 'AbortError') {
                errorDiv.textContent = 'Tempo esgotado. Verifique sua conexão.';
              } else {
                errorDiv.textContent = 'Erro ao validar cupom. Tente novamente.';
              }

              errorDiv.style.display = 'block';
              applyBtn.disabled = false;
              applyBtn.textContent = 'Aplicar';
            });
        });
      }

      if (couponInput && applyBtn) {
        couponInput.addEventListener('keypress', function (event) {
          if (event.key === 'Enter') {
            applyBtn.click();
          }
        });
      }
    });

    window.removeCoupon = removeCoupon;
  }

  bindToggleRows();
  bindLinkedToggles();
  bindEditableSectionTitles();
  injectCsrfIntoPostForms();
  syncCouponSessionForUser();
  bindCouponFlow();
})();
