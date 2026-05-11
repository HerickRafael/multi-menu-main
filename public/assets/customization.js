  // Bloquear zoom por gestos no mobile
  document.addEventListener('gesturestart', function(e) { e.preventDefault(); }, { passive: false });
  document.addEventListener('gesturechange', function(e) { e.preventDefault(); }, { passive: false });
  document.addEventListener('gestureend', function(e) { e.preventDefault(); }, { passive: false });

  const clamp = (n,min,max)=> Math.max(min, Math.min(max, n));

  document.querySelectorAll('img[data-fallback-target]').forEach((img) => {
    img.addEventListener('error', () => {
      const targetId = img.getAttribute('data-fallback-target');
      const fallback = targetId ? document.getElementById(targetId) : null;
      img.style.display = 'none';
      if (fallback) {
        fallback.style.display = 'flex';
      }
    });
  });

  document.querySelectorAll('[data-action="close-modal"]').forEach((btn) => {
    btn.addEventListener('click', function() {
      if (typeof closeInfoModal === 'function') {
        closeInfoModal();
      }
    });
  });

  // ═══════════════════════════════════════════════════════════════
  // ADICIONAR CROSS-SELLS PENDENTES AO FORM ANTES DE SUBMETER
  // ═══════════════════════════════════════════════════════════════
  const customForm = document.getElementById('customForm');
  if (customForm) {
    customForm.addEventListener('submit', function(e) {
      // Recuperar cross-sells salvos no sessionStorage (selecionados ANTES da personalização)
      const pendingCrossSells = sessionStorage.getItem('pendingCrossSells');

      if (pendingCrossSells) {
        try {
          const crossSellIds = JSON.parse(pendingCrossSells);

          if (Array.isArray(crossSellIds) && crossSellIds.length > 0) {
            // Remover campos antigos para evitar duplicação
            customForm.querySelectorAll('input[name="cross_sell[]"]').forEach(inp => inp.remove());

            // Adicionar cada cross-sell como campo hidden (somente IDs numéricos)
            crossSellIds.forEach((id) => {
              if (/^\d+$/.test(String(id).trim())) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'cross_sell[]';
                input.value = String(id).trim();
                customForm.appendChild(input);
              }
            });
          }
        } catch (err) {
          // JSON inválido — ignorar silenciosamente
        }
      }
    });
  }

  // Stepper (linhas com data-min/max) — ignora radios e pool rows
  document.querySelectorAll('.row:not(.pool-row)').forEach(row=>{
    const min = parseInt(row.dataset.min || '0',10);
    const max = parseInt(row.dataset.max || '99',10);
    const valEl = row.querySelector('.st-val');
    const hidden = row.querySelector('input[type="hidden"]');
    const stepper = row.querySelector('.stepper');

    if(!valEl || !row.querySelector('.st-btn')) return;

    row.querySelectorAll('.st-btn').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const act = btn.dataset.act;
        const cur = parseInt(valEl.textContent || '0', 10);
        const desired = cur + (act==='inc'?1:-1);
        const next = clamp(desired, min, max);
        
        // Se bateu no limite, mostrar animação vermelha
        if (stepper && desired !== next) {
          stepper.classList.remove('limit-hit');
          void stepper.offsetWidth; // força reflow para reiniciar animação
          stepper.classList.add('limit-hit');
          setTimeout(() => stepper.classList.remove('limit-hit'), 600);
        }
        
        valEl.textContent = String(next);
        if(hidden) hidden.value = String(next);
      });
    });
  });

  // ═══════════════════════════════════════════════════════════════
  // POOL MODE (açaí/poke) — steppers com total compartilhado
  // Primeiros poolFree são grátis; extras cobram sale_price
  // ═══════════════════════════════════════════════════════════════
  document.querySelectorAll('.pool-group').forEach(poolEl => {
    const groupKey = poolEl.dataset.group;
    const poolMin = parseInt(poolEl.dataset.poolMin || '0', 10);
    const poolFree = parseInt(poolEl.dataset.poolFree || '4', 10);
    const counterEl = document.querySelector(`.pool-counter[data-group="${groupKey}"]`);
    const sumEl = counterEl ? counterEl.querySelector('[data-role="pool-sum"]') : null;
    const extrasEl = counterEl ? counterEl.querySelector('[data-role="pool-extras"]') : null;

    const getSum = () => {
      let s = 0;
      poolEl.querySelectorAll('.pool-row .st-val').forEach(v => { s += parseInt(v.textContent || '0', 10); });
      return s;
    };

    const updateCounter = () => {
      const s = getSum();
      const freeUsed = Math.min(s, poolFree);
      const extras = Math.max(0, s - poolFree);
      if (sumEl) sumEl.textContent = String(freeUsed);
      if (counterEl) {
        counterEl.classList.toggle('full', s >= poolFree && extras === 0);
        counterEl.classList.toggle('extras', extras > 0);
      }
      if (extrasEl) {
        extrasEl.textContent = extras > 0 ? `+${extras} extra${extras > 1 ? 's' : ''}` : '';
      }

      // Por item: qty=0 → oculta | dentro da cota → Incluso (+ preço se pool cheio) | pago → preço · extra
      const isAtCapacity = s >= poolFree;
      let running = 0;
      poolEl.querySelectorAll('.pool-row').forEach(row => {
        const qty = parseInt(row.querySelector('.st-val')?.textContent || '0', 10);
        const priceEl = row.querySelector('[data-role="pool-price"]');
        const unitPrice = parseFloat(row.dataset.price || '0');
        const paidInThisRow = Math.max(0, qty - Math.max(0, poolFree - running));
        running += qty;
        if (!priceEl) return;
        if (qty === 0) {
          if (isAtCapacity && unitPrice > 0) {
            // Pool cheio, item não selecionado — mostra preço em cinza como aviso
            priceEl.textContent = 'R$ ' + unitPrice.toFixed(2).replace('.', ',');
            priceEl.className = 'pool-item-price';
            priceEl.style.display = '';
          } else {
            priceEl.style.display = 'none';
            priceEl.className = 'pool-item-price';
          }
        } else if (paidInThisRow > 0) {
          // Além da cota — preço · extra
          priceEl.textContent = unitPrice > 0
            ? 'R$ ' + unitPrice.toFixed(2).replace('.', ',') + ' · extra'
            : 'extra';
          priceEl.className = 'pool-item-price charged';
          priceEl.style.display = '';
        } else {
          // Dentro da cota — "Incluso" e, quando pool cheio, mostra o valor ao lado
          priceEl.textContent = (isAtCapacity && unitPrice > 0)
            ? 'Incluso · R$ ' + unitPrice.toFixed(2).replace('.', ',')
            : 'Incluso';
          priceEl.className = 'pool-item-price free';
          priceEl.style.display = '';
        }
      });
    };

    updateCounter();

    poolEl.querySelectorAll('.pool-row').forEach(row => {
      const valEl = row.querySelector('.st-val');
      const hidden = row.querySelector('input[type="hidden"]');
      const stepper = row.querySelector('.stepper');
      const itemMax = parseInt(row.dataset.max || '99', 10);

      if (!valEl || !row.querySelector('.st-btn')) return;

      row.querySelectorAll('.st-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          const act = btn.dataset.act;
          const cur = parseInt(valEl.textContent || '0', 10);
          let desired = cur + (act === 'inc' ? 1 : -1);

          // Limite individual
          desired = clamp(desired, 0, itemMax);

          valEl.textContent = String(desired);
          if (hidden) hidden.value = String(desired);
          updateCounter();
        });
      });
    });
  });

  // Validar mínimo do pool no submit
  if (customForm) {
    customForm.addEventListener('submit', function(e) {
      const pools = document.querySelectorAll('.pool-group');
      for (const poolEl of pools) {
        const poolMin = parseInt(poolEl.dataset.poolMin || '0', 10);
        if (poolMin <= 0) continue;
        let sum = 0;
        poolEl.querySelectorAll('.pool-row .st-val').forEach(v => { sum += parseInt(v.textContent || '0', 10); });
        if (sum < poolMin) {
          e.preventDefault();
          e.stopImmediatePropagation();
          const counterEl = document.querySelector(`.pool-counter[data-group="${poolEl.dataset.group}"]`);
          if (counterEl) {
            counterEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            counterEl.style.color = '#ef4444';
            // Mostrar alerta de mínimo obrigatório
            let msg = counterEl.querySelector('.pool-min-alert');
            if (!msg) {
              msg = document.createElement('span');
              msg.className = 'pool-min-alert';
              msg.style.cssText = 'display:block;font-size:13px;font-weight:600;color:#ef4444;margin-top:4px;';
              counterEl.parentNode.insertBefore(msg, counterEl.nextSibling);
            }
            msg.textContent = 'Escolha ' + poolMin + ' item' + (poolMin > 1 ? 's' : '') + ' (faltam ' + (poolMin - sum) + ')';
            setTimeout(() => { counterEl.style.color = ''; msg.remove(); }, 3000);
          }
          return;
        }
      }
    }, { capture: true }); // capture para executar antes do handler de cross-sells
  }

  // Grupos 'single' com quantity-selector (suporta múltiplas seleções)
  document.querySelectorAll('.single-group').forEach(groupEl => {
    const groupKey = groupEl.dataset.group;
    const maxSel = parseInt(groupEl.dataset.max || '1', 10);
    const minSel = parseInt(groupEl.dataset.min || '0', 10);
    
    // Contar seleções ativas
    const countActive = () => {
      return groupEl.querySelectorAll('.quantity-selector.active').length;
    };
    
    // Atualizar hidden input de um item
    const updateHidden = (row, qty) => {
      const hidden = row.querySelector('.single-item-input');
      if (hidden) hidden.value = String(qty);
    };
    
    groupEl.querySelectorAll('.quantity-selector').forEach(selector => {
      const row = selector.closest('.row.radio');
      const countEl = selector.querySelector('.qs-count');
      const decBtn = selector.querySelector('.qs-dec');
      const incBtn = selector.querySelector('.qs-inc');
      const defaultQty = parseInt(row?.dataset.defaultQty || '1', 10);
      
      // Desativar outros (usado quando max=1 para comportamento de radio)
      const deactivateOthers = () => {
        groupEl.querySelectorAll('.quantity-selector.active').forEach(other => {
          if (other !== selector) {
            other.classList.remove('active');
            const otherRow = other.closest('.row.radio');
            updateHidden(otherRow, 0);
          }
        });
      };
      
      // Ativar/desativar seletor ao clicar
      const toggle = () => {
        if (selector.classList.contains('active')) {
          // Desativar - verificar se pode (min)
          if (countActive() <= minSel) {
            return; // Não pode desativar, atingiu mínimo
          }
          selector.classList.remove('active');
          updateHidden(row, 0);
        } else {
          // Ativar - verificar se pode (max)
          if (countActive() >= maxSel) {
            // Se max=1, comportamento de radio: desativa outros e ativa este
            if (maxSel === 1) {
              deactivateOthers();
            } else {
              return; // Não pode ativar mais, atingiu máximo
            }
          }
          selector.classList.add('active');
          // Usar a quantidade atual (que pode ser a default_qty)
          const currentQty = parseInt(countEl.textContent, 10) || defaultQty;
          updateHidden(row, currentQty);
        }
      };
      
      // Clicar na row toggle o seletor
      if (row) {
        row.addEventListener('click', (e) => {
          if (e.target.closest('.qs-btn')) return;
          toggle();
        });
      }
      
      // Diminuir quantidade
      decBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        let val = parseInt(countEl.textContent, 10);
        
        if (val <= 1) {
          // Desativar se pode
          if (countActive() <= minSel) {
            return;
          }
          selector.classList.remove('active');
          countEl.textContent = String(defaultQty); // Manter qty padrão para próxima ativação
          updateHidden(row, 0);
          return;
        }
        
        val--;
        countEl.textContent = val;
        if (selector.classList.contains('active')) {
          updateHidden(row, val);
        }
      });
      
      // Aumentar quantidade
      incBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        
        // Se não está ativo, ativa primeiro (se puder)
        if (!selector.classList.contains('active')) {
          if (countActive() >= maxSel) {
            return;
          }
          selector.classList.add('active');
        }
        
        let val = parseInt(countEl.textContent, 10);
        val++;
        countEl.textContent = val;
        updateHidden(row, val);
      });
    });
  });

  // ═══════════════════════════════════════════════════════════════
  // MODAL EXPLICATIVO - Exibir uma vez por sessão
  // ═══════════════════════════════════════════════════════════════
  let _modalOpener = null;

  // Trap de foco: Tab/Shift+Tab cicla dentro do .modal-box
  function _modalTrapFocus(e) {
    if (e.key !== 'Tab') return;
    const modal = document.getElementById('infoModal');
    if (!modal || !modal.classList.contains('show')) return;
    const box = modal.querySelector('.modal-box');
    if (!box) return;
    const focusable = Array.from(
      box.querySelectorAll('button:not([disabled]), a[href], input:not([disabled]), [tabindex]:not([tabindex="-1"])')
    ).filter(el => el.offsetParent !== null);
    if (focusable.length === 0) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (e.shiftKey) {
      if (document.activeElement === first) { e.preventDefault(); last.focus(); }
    } else {
      if (document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  }

  function closeInfoModal() {
    const modal = document.getElementById('infoModal');
    if (modal) {
      modal.classList.remove('show');
      sessionStorage.setItem('customization_explained', '1');
      document.removeEventListener('keydown', _modalTrapFocus);
      if (_modalOpener && typeof _modalOpener.focus === 'function') {
        _modalOpener.focus();
        _modalOpener = null;
      }
    }
  }

  // Fechar com Escape (obrigatório para role="dialog" per ARIA spec)
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closeInfoModal(); }
  });

  // Fechar ao clicar no overlay (fora do modal-box)
  (function() {
    const overlay = document.getElementById('infoModal');
    if (overlay) {
      overlay.addEventListener('click', function(e) {
        if (e.target === overlay) { closeInfoModal(); }
      });
    }
  })();

  // Verificar se deve exibir o modal
  (function() {
    const alreadyExplained = sessionStorage.getItem('customization_explained');
    if (!alreadyExplained) {
      const modal = document.getElementById('infoModal');
      if (modal) {
        // Pequeno delay para animação suave
        setTimeout(() => {
          _modalOpener = document.activeElement;
          modal.classList.add('show');
          document.addEventListener('keydown', _modalTrapFocus);
          const btn = modal.querySelector('.modal-btn');
          if (btn) { btn.focus(); }
        }, 400);
      }
    }
  })();
