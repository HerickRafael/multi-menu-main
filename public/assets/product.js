  /* VERSÃO: 2025-12-07 - USANDO PARTIAL COMPARTILHADO */

  // Dados da página injetados via PHP (server-side), lidos de forma cacheável via JSON
  const _pd = JSON.parse(document.getElementById('page-data').textContent);
  const requiresLogin    = _pd.requiresLogin;
  let   userLogged       = _pd.isLogged;

  // Variáveis do produto — declaradas aqui (topo) para evitar TDZ em IIFEs abaixo
  const slug             = _pd.slug;
  const currentProductId = _pd.productId;
  const customerId       = _pd.customerId;

  // Sistema de debug: defina DEBUG = true localmente para ver os logs
  const DEBUG = false;
  function log(...a) { if (DEBUG) console.log(...a); }

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

  // Fallback seguro para allowAction (definida pelo login_modal.php)
  function safeAllowAction() {
    if (typeof window.allowAction === 'function') return window.allowAction();
    alert('Erro de sessão. Por favor, recarregue a página.');
    return false;
  }

  // Proteger botão do carrinho
  const productCartBtn = document.getElementById('product-cart-btn');
  if (productCartBtn && requiresLogin && !userLogged) {
    productCartBtn.addEventListener('click', function(e) {
      e.preventDefault();
      if (typeof window.openLoginModal === 'function') {
        window.openLoginModal();
      }
    });
  }

  const stepper = document.querySelector('.stepper');
  const qval   = document.getElementById('qval');
  const qfield = document.getElementById('qtyField');
  const minus  = stepper ? stepper.querySelector('[data-act="dec"]') : null;
  const plus   = stepper ? stepper.querySelector('[data-act="inc"]') : null;
  const clamp  = n => Math.max(1, Math.min(99, n|0));
  function setQty(n){ const v = clamp(n); if(qval) qval.textContent = String(v); if(qfield) qfield.value = String(v); }
  if (minus) {
    minus.addEventListener('click', ()=> {
      const qvalText = qval ? qval.textContent : '1';
      setQty(parseInt(qvalText||'1',10)-1);
    });
  }
  if (plus) {
    plus.addEventListener('click', ()=> {
      const qvalText = qval ? qval.textContent : '1';
      setQty(parseInt(qvalText||'1',10)+1);
    });
  }

  const getSelectedCrossSellIds = () => {
    const ids = [];
    document.querySelectorAll('.cross-sell-item.sel').forEach((item) => {
      const id = item.dataset.productId;
      if (id && !ids.includes(id)) {
        ids.push(id);
      }
    });
    return ids;
  };

  function syncCrossSellHiddenFields() {
    const formRef = document.querySelector('form.footer');
    if (!formRef) {
      log('syncCrossSellHiddenFields: form não encontrado');
      return;
    }

    log('╔═══════════════════════════════════════════════════════════════');
    log('║ 🔄 syncCrossSellHiddenFields()');
    log('╠═══════════════════════════════════════════════════════════════');

    // Remover apenas os campos gerados automaticamente
    const removedFields = [];
    formRef.querySelectorAll('input[name="cross_sell[]"][data-generated="1"]').forEach((input) => {
      removedFields.push(input.value);
      input.remove();
    });
    if (removedFields.length > 0) {
      log('║ 🗑️ Campos removidos:', removedFields);
    }

    const selectedIds = getSelectedCrossSellIds();
    log('║ � IDs VISUAIS (.cross-sell-item.sel):', selectedIds);
    
    // DEBUG: Verificar TODOS os items
    const allItems = document.querySelectorAll('.cross-sell-item');
    log('║ 🔍 Total de .cross-sell-item:', allItems.length);
    allItems.forEach((item, idx) => {
      const id = item.dataset.productId;
      const hasSel = item.classList.contains('sel');
      log(`║   [${idx}] ID=${id}, .sel=${hasSel}`);
    });
    
    selectedIds.forEach((id) => {
      // Verificar se já existe um campo (pode ter sido adicionado manualmente)
      const existing = formRef.querySelector(`input[name="cross_sell[]"][value="${id}"]`);
      if (!existing) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'cross_sell[]';
        hidden.value = id;
        hidden.setAttribute('data-generated', '1');
        formRef.insertBefore(hidden, formRef.firstChild);
        log('║   ✅ Campo criado para ID:', id);
      } else {
        log('║   ✔️ Campo já existe para ID:', id, '(generated:', existing.getAttribute('data-generated'), ')');
      }
    });
    
    // Log final detalhado
    const allFields = formRef.querySelectorAll('input[name="cross_sell[]"]');
    const allIds = Array.from(allFields).map(f => f.value);
    log('║ 📊 Total de campos no form:', allFields.length, '→ IDs:', allIds);
    log('╚═══════════════════════════════════════════════════════════════');
  }

  function selectCrossSellItem(item) {
    if (!item) {
      log('⚠️ selectCrossSellItem: item é null/undefined');
      return;
    }

    const productId = item.dataset.productId;
    const wasSel = item.classList.contains('sel');
    log(`🎯 selectCrossSellItem: ID=${productId}, já selecionado=${wasSel}`);

    if (!wasSel) {
      item.classList.add('sel');
      const ring = item.querySelector('.ring');
      if (ring) {
        ring.setAttribute('aria-pressed', 'true');
      }
      
      log(`  ✅ Classe .sel ADICIONADA ao item ${productId}`);
      
      // VERIFICAR imediatamente após adicionar
      const nowHasClass = item.classList.contains('sel');
      const totalSel = document.querySelectorAll('.cross-sell-item.sel').length;
      log(`  📊 Item ${productId} tem .sel: ${nowHasClass}, Total com .sel: ${totalSel}`);

      log(`  🔍 Procurando botão Personalizar...`);
      // Mostrar botão Personalizar se existir
      const customizeLink = item.querySelector('.choice-customize.cross-sell-customize');
      if (customizeLink) {
        log(`  ✓ Botão encontrado, removendo .hidden`);
        customizeLink.classList.remove('hidden');
      } else {
        log(`  ℹ️ Botão não encontrado (item não customizável)`);
      }

      log(`  🔍 Iniciando track interaction...`);
      // Track interaction com proteção
      if (productId) {
        try {
          trackInteraction(productId, 'add_to_cart');
          log(`  ✓ Track concluído`);
        } catch (e) {
          log('  ⚠️ Erro ao trackear interação:', e);
        }
      }
      
      log(`  ✅ Item ${productId} SELECIONADO`);
    } else {
      log(`  ℹ️ Item ${productId} JÁ ESTAVA selecionado`);
    }

    // SEMPRE sincronizar após selecionar
    try {
      log(`  🔧 Chamando syncCrossSellHiddenFields...`);
      syncCrossSellHiddenFields();
      log(`  ✅ syncCrossSellHiddenFields concluído`);
    } catch (error) {
      log(`  ❌ ERRO em syncCrossSellHiddenFields:`, error);
    }
  }

  function deselectCrossSellItem(item) {
    if (!item || !item.classList.contains('sel')) {
      return;
    }

    item.classList.remove('sel');
    const ring = item.querySelector('.ring');
    if (ring) {
      ring.setAttribute('aria-pressed', 'false');
    }

    // Esconder botão Personalizar se existir
    const customizeLink = item.querySelector('.choice-customize.cross-sell-customize');
    if (customizeLink) {
      customizeLink.classList.add('hidden');
      // Manter o texto "Personalizado" se foi customizado
      // O texto só volta para "Personalizar" se limpar a personalização
    }

    syncCrossSellHiddenFields();
  }

  function attach(ev){
    log('🚀 ATTACH() INICIADO');
    const form = ev ? ev.target : document.querySelector('form.footer');
    
    // Sincronizar campos hidden dos cross-sells
    syncCrossSellHiddenFields();
    
    // Atualizar quantidade
    const qvalText = qval ? qval.textContent : '1';
    setQty(parseInt(qvalText||'1',10)||1);
    
    // Verificar login
    if (!safeAllowAction()) {
      if (ev) ev.preventDefault();
      return false;
    }
    
    // Obter configurações do formulário
    const hasCombo = form && form.dataset ? form.dataset.hasCombo === '1' : false;
    const hasCustomization = form && form.dataset ? form.dataset.hasCustomization === '1' : false;
    const customizeUrl = form && form.dataset ? form.dataset.customizeUrl : null;
    
    // ═══════════════════════════════════════════════════════════════
    // VERIFICAR SE CLIENTE VIU SEÇÕES (CROSS-SELL OU COMBO GROUPS)
    // Usa tracking para não fazer scroll repetido
    // ═══════════════════════════════════════════════════════════════
    
    // Inicializar tracking de seções vistas (uma vez por página)
    if (!window._sectionsViewed) {
      window._sectionsViewed = new Set();
    }
    
    // Determinar quais seções verificar
    let sectionsToCheck = [];
    
    if (hasCombo) {
      // Para combo: verificar grupos de combo (excluindo cross-sell)
      document.querySelectorAll('.combo:not(.cross-sell-section) .group:not(.cross-sell-group)').forEach((group, idx) => {
        sectionsToCheck.push({ element: group, id: 'combo_group_' + idx, type: 'combo' });
      });
    } else {
      // Para produto simples: verificar seções de cross-sell
      document.querySelectorAll('.cross-sell-section').forEach((section, idx) => {
        sectionsToCheck.push({ element: section, id: 'cross_sell_' + idx, type: 'crosssell' });
      });
    }
    
    // Verificar cada seção
    for (const sectionInfo of sectionsToCheck) {
      const { element, id, type } = sectionInfo;
      
      // Se já foi marcada como vista, pular
      if (window._sectionsViewed.has(id)) {
        continue;
      }
      
      const rect = element.getBoundingClientRect();
      const windowHeight = window.innerHeight || document.documentElement.clientHeight;
      
      // Calcular visibilidade
      const visibleTop = Math.max(0, rect.top);
      const visibleBottom = Math.min(windowHeight, rect.bottom);
      const visibleHeight = Math.max(0, visibleBottom - visibleTop);
      const sectionHeight = rect.height;
      const visiblePercentage = sectionHeight > 0 ? (visibleHeight / sectionHeight) : 0;
      
      // Considerar "vista" se pelo menos 40% está visível
      if (visiblePercentage >= 0.4) {
        window._sectionsViewed.add(id);
        log(`✅ Seção '${id}' marcada como vista (${Math.round(visiblePercentage * 100)}% visível)`);
        continue;
      }
      
      // Não foi vista ainda - fazer scroll
      log(`📜 Seção '${id}' não visualizada (${Math.round(visiblePercentage * 100)}%), rolando...`);
      ev?.preventDefault();
      
      element.scrollIntoView({ 
        behavior: 'smooth', 
        block: 'center' 
      });
      
      // Marcar como vista após o scroll (com delay para o scroll terminar)
      setTimeout(() => {
        window._sectionsViewed.add(id);
        log(`✅ Seção '${id}' marcada como vista após scroll`);
      }, 800);
      
      return false;
    }
    
    // ═══════════════════════════════════════════════════════════════
    // VALIDAÇÃO DE COMBO: VERIFICAR SE GRUPOS FORAM PREENCHIDOS
    // ═══════════════════════════════════════════════════════════════
    if (hasCombo) {
      const choiceRows = document.querySelectorAll('.choice-row:not(.cross-sell-row)');
      for (let i = 0; i < choiceRows.length; i++) {
        const row = choiceRows[i];
        const groupIndex = row.dataset.groupIndex;
        
        // Pular se não tem groupIndex (não é um grupo de combo)
        if (groupIndex === undefined) continue;
        
        const minRequired = parseInt(row.dataset.min || '1', 10);
        const hiddenField = document.getElementById('combo_field_' + groupIndex);
        
        // Se o grupo requer pelo menos 1 item e não tem nenhum selecionado
        if (minRequired > 0 && (!hiddenField || !hiddenField.value || hiddenField.value === '')) {
          ev?.preventDefault();
          
          const groupContainer = row.closest('.group');
          const groupTitleElement = groupContainer ? groupContainer.querySelector('h2') : null;
          const groupTitle = groupTitleElement ? groupTitleElement.textContent : 'grupo';
          
          if (groupContainer) {
            groupContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            groupContainer.classList.add('highlight-missing');
            setTimeout(() => groupContainer.classList.remove('highlight-missing'), 2000);
          }
          
          alert('Por favor, selecione um item em "' + groupTitle + '"');
          return false;
        }
      }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // REDIRECIONAMENTO PARA PERSONALIZAÇÃO (SE APLICÁVEL)
    // ═══════════════════════════════════════════════════════════════
    if (!hasCombo && hasCustomization && customizeUrl) {
      ev?.preventDefault();
      
      const qty = parseInt(qval ? qval.textContent : '1', 10) || 1;
      const url = new URL(customizeUrl, window.location.origin);
      url.searchParams.set('qty', String(qty));
      
      // Salvar cross-sells selecionados
      const selectedCrossSells = [];
      document.querySelectorAll('.cross-sell-item.sel').forEach(item => {
        const id = item.dataset.productId;
        if (id) selectedCrossSells.push(id);
      });
      
      if (selectedCrossSells.length > 0) {
        sessionStorage.setItem('pendingCrossSells', JSON.stringify(selectedCrossSells));
      } else {
        sessionStorage.removeItem('pendingCrossSells');
      }
      
      window.location.href = url.toString();
      return false;
    }
    
    return true;
  }

  const btnCust = document.getElementById('btn-customize');
  if (btnCust) {
    const handleBtnCust = (ev) => {
      if (!safeAllowAction()) { ev.preventDefault(); return; }
      const _form = document.querySelector('form.footer');
      const base = btnCust.getAttribute('href') || (_form && _form.dataset.customizeUrl) || '';
      if (!base) return;
      const qtyText = qval ? qval.textContent : '1';
      const qty  = parseInt(qtyText||'1',10) || 1;
      const url  = new URL(base, window.location.origin);
      url.searchParams.set('qty', String(qty));
      btnCust.setAttribute('href', url.toString());
    };
    btnCust.addEventListener('click', handleBtnCust);
  }

  document.querySelectorAll('.choice-row').forEach(row=>{
    // Ignorar cross-sell rows (só processar rows de combo)
    if (row.classList.contains('cross-sell-row')) {
      return;
    }
    
    const gi = row.dataset.groupIndex;
    const hidden = document.getElementById('combo_field_' + gi);
    const items = row.querySelectorAll('.choice');
    function revealCustomize(target){
      items.forEach(it=>{
        const link=it.querySelector('.choice-customize');
        if(link){
          link.classList.add('hidden');
        }
      });
      if(target){
        const link=target.querySelector('.choice-customize');
        if(link){ 
          link.classList.remove('hidden');
          // Reconstruir URL com parâmetros de unidade corretos
          const baseUrl = link.dataset.baseUrl;
          const totalUnits = parseInt(target.dataset.defaultQty || '1', 10);
          if (baseUrl && totalUnits > 1) {
            const url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('unit', '1');
            url.searchParams.set('total_units', String(totalUnits));
            link.setAttribute('href', url.toString());
          } else if (baseUrl) {
            link.setAttribute('href', baseUrl);
          }
        }
      }
    }
    const selectChoice = target => {
      items.forEach(i=>{
        i.classList.remove('sel');
        const ringElement = i.querySelector('.ring');
        if (ringElement) {
          ringElement.setAttribute('aria-pressed','false');
        }
      });
      target.classList.add('sel');
      const targetRing = target.querySelector('.ring');
      if (targetRing) {
        targetRing.setAttribute('aria-pressed','true');
      }
      if (hidden) hidden.value = target.dataset.id || '';
      revealCustomize(target);
      
      // Verificar se o item selecionado tem personalização salva
      const productId = target.dataset.productId;
      const customizeLink = target.querySelector('.choice-customize.combo-customize');
      
      if (productId && customizeLink && !customizeLink.classList.contains('customized')) {
        // Checar se tem personalização
        const ctrl3 = new AbortController();
          const timer3 = setTimeout(() => ctrl3.abort(), 5000);
          fetch(`${_pd.checkCustomizationUrl}?product_id=${productId}&parent_id=${currentProductId}`, {
          method: 'GET',
          credentials: 'same-origin',
          signal: ctrl3.signal
        })
        .then(res => res.json())
        .then(data => {
          if (data.has_customization) {
            customizeLink.textContent = 'Personalizado';
            customizeLink.classList.add('customized');
            target.setAttribute('data-is-customized', '1');
          }
        })
        .catch(() => {}).finally(() => clearTimeout(timer3));
      }
    };

    const clearSelection = () => {
      items.forEach(i=>{
        i.classList.remove('sel');
        const ringElement = i.querySelector('.ring');
        if (ringElement) {
          ringElement.setAttribute('aria-pressed','false');
        }
      });
      if (hidden) hidden.value = '';
      revealCustomize(null);
    };

    const defaultChoice = row.querySelector('.choice[data-default="1"]');

    items.forEach(item=>{
      const ring = item.querySelector('.ring');
      if (ring) {
        ring.addEventListener('click', () => {
          const isDefault = item.dataset.default === '1';
          if (item.classList.contains('sel')) {
            if (!isDefault) {
              if (defaultChoice && defaultChoice !== item) {
                selectChoice(defaultChoice);
              } else if (row.dataset.min === '0') {
                clearSelection();
              }
            }
            return;
          }
          selectChoice(item);
        });
      }
    });

    const initial = row.querySelector('.choice.sel');
    if(initial){
      if (hidden) hidden.value = initial.dataset.id || '';
      revealCustomize(initial);
    } else if (defaultChoice) {
      selectChoice(defaultChoice);
    }
  });

  document.querySelectorAll('.choice-customize').forEach(link=>{
    const handleCustomizeClick = (ev) => {
      if (!safeAllowAction()) { ev.preventDefault(); return; }
      
      // Se for cross-sell, tratar diferente
      const crossSellId = link.dataset.crossSellId;
      if (crossSellId) {
        ev.preventDefault();
        
        // Salvar cross-sells já selecionados (exceto o que vamos personalizar)
        const selectedCrossSells = [];
        document.querySelectorAll('.cross-sell-item.sel').forEach(item => {
          const itemId = item.dataset.productId;
          if (itemId !== crossSellId) {
            selectedCrossSells.push(itemId);
          }
        });
        if (selectedCrossSells.length > 0) {
          sessionStorage.setItem('pendingCrossSells', JSON.stringify(selectedCrossSells));
        }
        
        // Marcar que o produto atual é o produto principal
        const mainProductId = _pd.productId;
        sessionStorage.setItem('pendingMainProduct', mainProductId);
        sessionStorage.setItem('pendingProductContext', 'cross_sell');
        
        // Redirecionar para página de personalização do cross-sell
        const base = link.dataset.baseUrl || link.getAttribute('href');
        const qty = parseInt(qval ? qval.textContent : '1', 10) || 1;
        const url = new URL(base + '/customizar', window.location.origin);
        url.searchParams.set('qty', String(qty));
        url.searchParams.set('parent_id', String(mainProductId));
        url.searchParams.set('return_to_parent', '1');
        
        window.location.href = url.toString();
        return;
      }
      
      // Para botões de combo, comportamento normal
      // Usar o href diretamente pois já contém unit e total_units se necessário
      const hrefOriginal = link.getAttribute('href');
      if (!hrefOriginal) return;
      const qty = parseInt(qval ? qval.textContent : '1',10)||1;
      const url = new URL(hrefOriginal, window.location.origin);
      url.searchParams.set('qty', String(qty));
      link.setAttribute('href', url.toString());
    };
    
    link.addEventListener('click', handleCustomizeClick);
  });

  // ===== CROSS-SELL LOGIC =====
  const mainForm = document.querySelector('form.footer');
  const crossSellItems = document.querySelectorAll('.cross-sell-item');
  
  log('=== INICIALIZANDO CROSS-SELL ===');
  log('Formulário encontrado:', mainForm ? 'SIM' : 'NÃO');
  log('Total de itens cross-sell:', crossSellItems.length);
  
  crossSellItems.forEach((item, index) => {
    const ring = item.querySelector('.ring');
    if (!ring) {
      log(`Item ${index}: RING NÃO ENCONTRADO`);
      return;
    }
    
    const productId = item.dataset.productId;
    log(`Item ${index}: ID=${productId}, ring=OK`);
    
    const toggleCrossSell = () => {
      // Verificar se está logado antes de permitir seleção de cross-sell
      if (!userLogged) {
        log('⚠️ Usuário não logado - abrindo modal de login');
        if (typeof window.openLoginModal === 'function') window.openLoginModal();
        return;
      }
      
      const wasSel = item.classList.contains('sel');
      log(`🔘 Toggle cross-sell ID=${productId}, estava selecionado=${wasSel}`);
      
      if (wasSel) {
        deselectCrossSellItem(item);
        log(`✓ Item desmarcado: ${productId}`);
      } else {
        selectCrossSellItem(item);
        log(`✓ Item marcado: ${productId}`);
      }
      
      // Verificar se realmente mudou
      const nowSel = item.classList.contains('sel');
      log(`  → Resultado: agora está selecionado=${nowSel}`);
    };
    
    // Handler unificado: flag 'handled' evita duplo disparo em mobile (touch + click)
    let handled = false;

    ring.addEventListener('touchstart', () => {
      handled = false;
    }, { passive: true });

    ring.addEventListener('touchmove', () => {
      handled = true; // arrastar não é toque intencional
    }, { passive: true });

    ring.addEventListener('touchend', (e) => {
      if (handled) return;
      handled = true;
      e.preventDefault();
      log(`👆 TOUCHEND no ring do item ID=${productId}`);
      toggleCrossSell();
      // Resetar após 300ms para não bloquear cliques futuros sem novo touchstart
      setTimeout(() => { handled = false; }, 300);
    }, { passive: false });

    ring.addEventListener('click', (e) => {
      if (handled) return; // já processado pelo touchend
      e.preventDefault();
      e.stopPropagation();
      log(`🖱️ CLICK no ring do item ID=${productId}`);
      toggleCrossSell();
    });

    // Também permitir click no item inteiro (mais fácil para mobile)
    item.addEventListener('click', (e) => {
      // Ignorar se clicou em link/botão
      if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || e.target.closest('a') || e.target.closest('button')) {
        log('Click em link/botão ignorado');
        return;
      }
      if (handled) return; // já processado pelo touchend
      log(`🖱️ CLICK no item ID=${productId}`);
      toggleCrossSell();
    });
  });

  // Sincronizar campos hidden inicial
  syncCrossSellHiddenFields();

  // ===== RESTAURAR CROSS-SELLS APÓS PERSONALIZAÇÃO =====
  (function restoreCrossSells() {
    const pendingCrossSells = sessionStorage.getItem('pendingCrossSells');
    const mainProductId = sessionStorage.getItem('pendingMainProduct');
    const pendingContext = sessionStorage.getItem('pendingProductContext');
    const urlParams = new URLSearchParams(window.location.search);
    const customizedCrossSellId = urlParams.get('customized_cross_sell');
    
    log('╔═══════════════════════════════════════════════════════════════');
    log('║ 🔄 RESTAURAR CROSS-SELLS');
    log('╠═══════════════════════════════════════════════════════════════');
    log('║ customizedCrossSellId:', customizedCrossSellId);
    log('║ pendingCrossSells:', pendingCrossSells);
    log('║ mainProductId:', mainProductId);
    log('║ currentProductId:', currentProductId);
    log('╚═══════════════════════════════════════════════════════════════');
    
    // Se voltamos de personalização (URL tem parâmetro customized_cross_sell)
    if (customizedCrossSellId) {
      log('🔙 VOLTAMOS DE PERSONALIZAÇÃO ===');
      log('Cross-sell personalizado:', customizedCrossSellId);
      
      // Reselecionar o item que foi personalizado
      const customizedItem = document.querySelector(`.cross-sell-item[data-product-id="${customizedCrossSellId}"]`);
      if (customizedItem && !customizedItem.classList.contains('sel')) {
        log('🎯 Selecionando item personalizado:', customizedCrossSellId);
        selectCrossSellItem(customizedItem);
      } else {
        log('⚠️ Item personalizado JÁ está selecionado ou NÃO ENCONTRADO');
      }
      
      // Restaurar outros cross-sells que estavam selecionados
      if (pendingCrossSells && mainProductId == currentProductId) {
        try {
          const idsToRestore = JSON.parse(pendingCrossSells);
          log('📋 IDs adicionais para restaurar:', idsToRestore);
          
          idsToRestore.forEach(id => {
            if (id !== customizedCrossSellId) { // Não duplicar o já selecionado
              const item = document.querySelector(`.cross-sell-item[data-product-id="${id}"]`);
              if (item && !item.classList.contains('sel')) {
                log(`  ➕ Restaurando item: ${id}`);
                selectCrossSellItem(item);
              } else {
                log(`  ⚠️ Item ${id} já selecionado ou não encontrado`);
              }
            }
          });
          
          log('🔄 SYNC FINAL após restauração...');
          syncCrossSellHiddenFields();
          log('✅ SYNC concluído');
          
        } catch (e) {
          log('❌ Erro ao restaurar cross-sells adicionais:', e);
        }
      } else {
        log('ℹ️ Nenhum cross-sell adicional para restaurar');
        log('🔄 SYNC FINAL após restauração do item personalizado...');
        syncCrossSellHiddenFields();
        log('✅ SYNC concluído');
      }
      
      // Limpar localStorage
      sessionStorage.removeItem('pendingCrossSells');
      sessionStorage.removeItem('pendingMainProduct');
      sessionStorage.removeItem('pendingProductContext');
      
      // Verificar personalização APENAS do item que acabou de ser personalizado
      // Aguardar um pouco para garantir que o item foi selecionado
      setTimeout(() => {
        if (customizedItem) {
          const customizeLink = customizedItem.querySelector('.choice-customize.cross-sell-customize');
          if (customizeLink) {
            customizeLink.textContent = 'Personalizado';
            customizeLink.classList.add('customized');
            customizedItem.setAttribute('data-is-customized', '1');
            log(`✅ Item ${customizedCrossSellId} marcado como personalizado`);
          }
        }
      }, 100);
      
      // Limpar URL (remover parâmetro customized_cross_sell)
      if (window.history && window.history.replaceState) {
        const cleanUrl = window.location.pathname;
        window.history.replaceState({}, '', cleanUrl);
      }
      
      log('✅ Cross-sell restaurado e sincronizado');
      return;
    }
    
    // Se estamos no produto principal e há cross-sells pendentes (fallback antigo)
    if (pendingCrossSells && mainProductId && mainProductId == currentProductId) {
      log('=== RESTAURANDO CROSS-SELLS (FALLBACK) ===');
      
      try {
        const idsToRestore = JSON.parse(pendingCrossSells);
        log('IDs para restaurar:', idsToRestore);
        
        idsToRestore.forEach(id => {
          const item = document.querySelector(`.cross-sell-item[data-product-id="${id}"]`);
          if (item && !item.classList.contains('sel')) {
            log(`Restaurando item: ${id}`);
            selectCrossSellItem(item);
          }
        });
        
        // Limpar localStorage
        sessionStorage.removeItem('pendingCrossSells');
        sessionStorage.removeItem('pendingMainProduct');
        sessionStorage.removeItem('pendingProductContext');
        
        log('✅ Cross-sells restaurados');
      } catch (e) {
        log('Erro ao restaurar cross-sells:', e);
        sessionStorage.removeItem('pendingCrossSells');
        sessionStorage.removeItem('pendingMainProduct');
        sessionStorage.removeItem('pendingProductContext');
      }
    }
  })();

  // ===== GARANTIR QUE ATTACH() SEJA CHAMADO (especialmente no mobile) =====
  if (mainForm) {
    log('🔧 Adicionando listener de submit via JavaScript (mobile fix)');
    
    // Adicionar listener via JavaScript (além do onsubmit inline)
    mainForm.addEventListener('submit', function(e) {
      log('');
      log('╔══════════════════════════════════════════════════════════════════');
      log('║ 🚀 SUBMIT DO FORMULÁRIO CAPTURADO');
      log('╠══════════════════════════════════════════════════════════════════');
      
      // GARANTIR sincronização final dos campos hidden
      log('║ Passo 1: Sync cross-sell fields...');
      syncCrossSellHiddenFields();
      
      // Verificar campos ANTES de chamar attach
      let crossSells = Array.from(mainForm.querySelectorAll('input[name="cross_sell[]"]')).map(i => i.value);
      log('║ Campos cross_sell[] ANTES attach:', crossSells.length, '→', crossSells);
      
      const visualItems = document.querySelectorAll('.cross-sell-item.sel');
      log('║ Itens visuais selecionados:', visualItems.length);
      
      // FALLBACK DE EMERGÊNCIA: Adicionar campos ANTES de attach
      if (crossSells.length === 0 && visualItems.length > 0) {
        log('║ ⚠️ ATIVANDO FALLBACK DE EMERGÊNCIA');
        visualItems.forEach(item => {
          const id = item.dataset.productId;
          if (id) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'cross_sell[]';
            input.value = id;
            input.setAttribute('data-emergency', '1');
            mainForm.insertBefore(input, mainForm.firstChild);
            log('║   ➕ Campo de emergência criado para ID:', id);
          }
        });
        
        crossSells = Array.from(mainForm.querySelectorAll('input[name="cross_sell[]"]')).map(i => i.value);
        log('║ Campos APÓS fallback:', crossSells.length, '→', crossSells);
      }
      
      // Chamar attach
      log('║ Passo 2: Chamando attach()...');
      const result = attach(e);
      log('║ attach() retornou:', result);
      
      if (!result) {
        log('║ ❌ attach() retornou FALSE - cancelando submit');
        log('╚══════════════════════════════════════════════════════════════════');
        e.preventDefault();
        return false;
      }
      
      // Verificar FormData final
      const formData = new FormData(mainForm);
      const finalCrossSells = formData.getAll('cross_sell[]');
      log('║ FormData final - cross_sell[]:', finalCrossSells.length, '→', finalCrossSells);
      log('║ ✅ Submetendo formulário...');
      log('╚══════════════════════════════════════════════════════════════════');
      
      return true;
    }, false);
    
    log('✅ Listener de submit adicionado');
  } else {
    log('❌ Form.footer não encontrado!');
  }

  // ===== TRACKING DE INTERAÇÕES PARA MACHINE LEARNING =====
  // (slug, currentProductId, customerId declarados no topo do script)

  // Gerar ou recuperar session_id para clientes anônimos
  let sessionId = localStorage.getItem('ml_session_id');
  if (!sessionId) {
    sessionId = Date.now().toString(36) + Math.random().toString(36).substr(2);
    localStorage.setItem('ml_session_id', sessionId);
  }
  
  function trackInteraction(productId, eventType) {
    // Não bloquear a UI; timeout de 3s para não deixar request pendurado
    const ctrl = new AbortController();
    const timer = setTimeout(() => ctrl.abort(), 3000);
    fetch(_pd.trackInteractionUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      signal: ctrl.signal,
      body: JSON.stringify({
        product_id: productId,
        event_type: eventType,
        customer_id: customerId,
        session_id: customerId ? null : sessionId
      })
    }).catch(() => {}).finally(() => clearTimeout(timer));
  }
  
  // Track "view" quando a página carregar
  if (currentProductId > 0) {
    trackInteraction(currentProductId, 'view');
  }

  // ===== DETECTAR ITENS DE COMBO/CROSS-SELL JÁ PERSONALIZADOS AO CARREGAR =====
  // Script roda no fim do <body> — DOM já está pronto; IIFE evita depender do evento
  (function checkCustomizations() {
    const comboItems = document.querySelectorAll('.combo .choice[data-customizable="1"]');
    
    comboItems.forEach(item => {
      const productId = item.dataset.productId;
      const customizeLink = item.querySelector('.choice-customize');
      
      if (!productId || !customizeLink) return;
      
      // Verificar se tem personalização salva via AJAX (timeout 5s)
      const ctrl1 = new AbortController();
      const timer1 = setTimeout(() => ctrl1.abort(), 5000);
      fetch(`${_pd.checkCustomizationUrl}?product_id=${productId}&parent_id=${currentProductId}`, {
        method: 'GET',
        credentials: 'same-origin',
        signal: ctrl1.signal
      })
      .then(res => res.json())
      .then(data => {
        if (data.has_customization && customizeLink) {
          // Se o item já estiver selecionado, mostrar como "Personalizado"
          if (item.classList.contains('sel')) {
            customizeLink.textContent = 'Personalizado';
            customizeLink.classList.remove('hidden');
            customizeLink.classList.add('customized');
            item.setAttribute('data-is-customized', '1');
          }
        }
      })
      .catch(() => {}).finally(() => clearTimeout(timer1));
    });
    
    // ===== DETECTAR ITENS CROSS-SELL JÁ PERSONALIZADOS AO CARREGAR =====
    const crossSellItems = document.querySelectorAll('.cross-sell-item[data-customizable="1"]');
    
    crossSellItems.forEach(item => {
      const productId = item.dataset.productId;
      const customizeLink = item.querySelector('.choice-customize.cross-sell-customize');
      
      if (!productId || !customizeLink) return;
      
      // Verificar se tem personalização salva via AJAX (timeout 5s)
      const ctrl2 = new AbortController();
      const timer2 = setTimeout(() => ctrl2.abort(), 5000);
      fetch(`${_pd.checkCustomizationUrl}?product_id=${productId}&parent_id=${currentProductId}`, {
        method: 'GET',
        credentials: 'same-origin',
        signal: ctrl2.signal
      })
      .then(res => res.json())
      .then(data => {
        if (data.has_customization && customizeLink) {
          // Se o item já estiver selecionado, mostrar como "Personalizado"
          if (item.classList.contains('sel')) {
            customizeLink.textContent = 'Personalizado';
            customizeLink.classList.remove('hidden');
            customizeLink.classList.add('customized');
            item.setAttribute('data-is-customized', '1');
          }
        }
      })
      .catch(() => {}).finally(() => clearTimeout(timer2));
    });
  })();
(function(){
  var t=document.querySelector('meta[name="csrf-token"]');
  if(!t||!t.content)return;
  var token=t.content;
  document.addEventListener('DOMContentLoaded',function(){
    document.querySelectorAll('form').forEach(function(f){
      if((f.getAttribute('method')||'').toUpperCase()==='POST'&&!f.querySelector('input[name="csrf_token"]')){
        var i=document.createElement('input');i.type='hidden';i.name='csrf_token';i.value=token;f.appendChild(i);
      }
    });
  });
  window._csrfToken=token;
})();
