// admin-products.js — Enhanced version with unified autocomplete system
(function(){
  'use strict';

  // Referência para função de typeahead de ingredientes (definida posteriormente)
  let setupIngredientTypeahead = null;

  // Utils
  function formatMoney(v){ const n=isNaN(v)?0:Number(v); return n.toLocaleString('pt-BR',{style:'currency',currency:'BRL'}); }
  function brToFloat(v){ if(v==null)return 0; const raw=String(v).trim(); return raw.includes(',')?parseFloat(raw.replace(/\./g,'').replace(',','.'))||0:parseFloat(raw)||0; }
  function toggleBlock(el,on){ if(!el) return; el.classList.toggle('hidden',!on); el.setAttribute('aria-hidden',String(!on)); }
  function ensureMinMax(scope){ if(!scope) return; scope.querySelectorAll('input[name$="[min]"]').forEach(minEl=>{ const wrap=minEl.closest('.cust-group')||minEl.closest('.group-card')||scope; const maxEl=wrap.querySelector('input[name$="[max]"]'); if(!maxEl) return; const min=Number(minEl.value||0), max=Number(maxEl.value||0); if(max && max<min) maxEl.value=min; }); }
  
  // Função para remover acentos
  function removeAccents(str) {
    return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  }

  // Debounce function
  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  // Get company slug from URL
  function getCompanySlug() {
    const match = window.location.pathname.match(/\/admin\/([^\/]+)\//);
    return match ? match[1] : null;
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
  }

  // ===== SISTEMA UNIFICADO DE AUTOCOMPLETE =====
  function createAutocomplete(config) {
    const {
      wrapperSelector,        // ex: '.product-autocomplete-wrapper'
      inputSelector,          // ex: '.product-search-input'
      suggestionsSelector,    // ex: '.product-suggestions'
      hiddenInputSelector,    // ex: '.product-id-input'
      itemClass,              // ex: 'product-suggestion-item'
      noResultsClass,         // ex: 'product-suggestion-item' (para estilo)
      search,                 // async function(query) => items[]
      renderItem,             // function(item) => HTMLElement
      extractItemData,        // function(element) => data object
      onSelect,               // function(input, data, wrapper)
      getSelectedIds,         // function(input) => [ids] de itens já selecionados
      minQueryLength = 2,
      debounceMs = 200,
      showOnFocus = false,
      checkBeforeFocus = null // function(input) => boolean
    } = config;

    let activeInput = null;
    let selectedIndex = -1;

    function getWrapper(input) {
      return input.closest(wrapperSelector);
    }

    function getSuggestionsDiv(input) {
      return getWrapper(input)?.querySelector(suggestionsSelector);
    }

    function showSuggestions(input, items) {
      const suggestionsDiv = getSuggestionsDiv(input);
      if (!suggestionsDiv) return;

      suggestionsDiv.innerHTML = '';
      selectedIndex = -1;

      // Filtrar itens já selecionados
      const selectedIds = getSelectedIds ? getSelectedIds(input) : [];
      const filteredItems = items.filter(item => {
        const itemId = (item.id || item.ingredient_id || '').toString();
        return !selectedIds.includes(itemId);
      });

      if (filteredItems.length === 0) {
        const noResultsEl = document.createElement('div');
        noResultsEl.className = noResultsClass || itemClass;
        noResultsEl.style.color = items.length === 0 ? '#6b7280' : '#f59e0b';
        noResultsEl.textContent = items.length === 0 
          ? 'Nenhum resultado encontrado' 
          : 'Todos os itens já foram selecionados';
        suggestionsDiv.appendChild(noResultsEl);
      } else {
        filteredItems.forEach(item => {
          const el = renderItem(item);
          el.classList.add(itemClass);
          suggestionsDiv.appendChild(el);
        });
      }

      suggestionsDiv.classList.remove('hidden');
    }

    function hideSuggestions(input) {
      const suggestionsDiv = getSuggestionsDiv(input);
      suggestionsDiv?.classList.add('hidden');
      activeInput = null;
      selectedIndex = -1;
    }

    function navigate(input, direction) {
      const suggestionsDiv = getSuggestionsDiv(input);
      const items = suggestionsDiv?.querySelectorAll(`.${itemClass}[data-id]`);
      if (!items?.length) return;

      items.forEach(item => item.classList.remove('highlighted'));

      if (direction === 'down') {
        selectedIndex = (selectedIndex + 1) % items.length;
      } else if (direction === 'up') {
        selectedIndex = selectedIndex <= 0 ? items.length - 1 : selectedIndex - 1;
      }

      const highlightedItem = items[selectedIndex];
      if (highlightedItem) {
        highlightedItem.classList.add('highlighted');
        highlightedItem.scrollIntoView({ block: 'nearest' });
      }
    }

    function selectHighlighted(input) {
      const suggestionsDiv = getSuggestionsDiv(input);
      const highlightedItem = suggestionsDiv?.querySelector(`.${itemClass}.highlighted`);
      if (highlightedItem && extractItemData) {
        const data = extractItemData(highlightedItem);
        onSelect(input, data, getWrapper(input));
        hideSuggestions(input);
        return true;
      }
      return false;
    }

    const debouncedSearch = debounce(async (input, query) => {
      if (query.length < minQueryLength) {
        if (!showOnFocus) hideSuggestions(input);
        return;
      }
      const items = await search(query);
      if (input === activeInput) {
        showSuggestions(input, items);
      }
    }, debounceMs);

    function setup(input) {
      if (input.dataset.unifiedAutocompleteSetup) return;
      input.dataset.unifiedAutocompleteSetup = 'true';

      input.addEventListener('input', (e) => {
        const query = e.target.value.trim();
        activeInput = input;

        if (query.length === 0) {
          const wrapper = getWrapper(input);
          const hiddenInput = wrapper?.querySelector(hiddenInputSelector);
          if (hiddenInput) hiddenInput.value = '';
          input.dataset.selectedId = '';
          hideSuggestions(input);
        } else {
          debouncedSearch(input, query);
        }
      });

      input.addEventListener('focus', async () => {
        activeInput = input;
        
        // Verificação opcional antes de mostrar (ex: se já tem seleção)
        if (checkBeforeFocus && !checkBeforeFocus(input)) return;

        const query = input.value.trim();
        if (showOnFocus || query.length >= minQueryLength) {
          const items = await search(query);
          showSuggestions(input, items);
        }
      });

      input.addEventListener('blur', () => {
        setTimeout(() => {
          if (activeInput === input) {
            hideSuggestions(input);
          }
        }, 150);
      });

      input.addEventListener('keydown', (e) => {
        const suggestionsDiv = getSuggestionsDiv(input);
        if (suggestionsDiv?.classList.contains('hidden')) return;

        switch (e.key) {
          case 'ArrowDown':
            e.preventDefault();
            navigate(input, 'down');
            break;
          case 'ArrowUp':
            e.preventDefault();
            navigate(input, 'up');
            break;
          case 'Enter':
            e.preventDefault();
            selectHighlighted(input);
            break;
          case 'Escape':
            e.preventDefault();
            hideSuggestions(input);
            break;
        }
      });

      // Event delegation para cliques
      const wrapper = getWrapper(input);
      const suggestionsDiv = wrapper?.querySelector(suggestionsSelector);

      suggestionsDiv?.addEventListener('mousedown', (e) => e.preventDefault());
      suggestionsDiv?.addEventListener('click', (e) => {
        const item = e.target.closest(`.${itemClass}[data-id]`);
        if (item && extractItemData) {
          const data = extractItemData(item);
          onSelect(input, data, wrapper);
          hideSuggestions(input);
        }
      });
    }

    return { setup, hideSuggestions, activeInput: () => activeInput };
  }

  // ===== Toggle Switch Helper (para combo groups) =====
  function setupToggleSwitch(label) {
    const checkbox = label.querySelector('.combo-group-custom-switch');
    const track = label.querySelector('.combo-toggle-track');
    const thumb = label.querySelector('.combo-toggle-thumb');
    
    if (!checkbox || !track || !thumb) return;
    
    label.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      checkbox.checked = !checkbox.checked;
      
      // Disparar evento change para manter compatibilidade com código existente
      checkbox.dispatchEvent(new Event('change', { bubbles: true }));
      
      // Atualizar visual do toggle
      if (checkbox.checked) {
        track.classList.remove('bg-slate-300');
        track.classList.add('admin-primary-bg');
        thumb.style.transform = 'translateX(16px)';
      } else {
        track.classList.remove('admin-primary-bg');
        track.classList.add('bg-slate-300');
        thumb.style.transform = 'translateX(0px)';
      }
    });
  }

  // ===== contador descrição & preview imagem =====
  const descField=document.getElementById('description');
  const descCounter=document.getElementById('description-counter');
  function syncDescCounter(){ if(!descField || !descCounter) return; const size=descField.value.trim().length; descCounter.textContent=`${size} caractere${size===1?'':'s'}`; }
  descField?.addEventListener('input', syncDescCounter);
  syncDescCounter();

  // ===== Preview de imagem =====
  const imageInput = document.getElementById('image');
  const imagePreview = document.getElementById('image-preview-img');
  const imagePlaceholder = document.getElementById('image-preview-placeholder');
  
  if (imageInput) {
    imageInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(event) {
          // Atualizar a imagem
          if (imagePreview) {
            imagePreview.src = event.target.result;
            imagePreview.style.display = 'block';
            imagePreview.classList.remove('hidden');
            
            // Adicionar label se não existir
            const previewContainer = imagePreview.parentElement;
            let label = previewContainer.querySelector('.text-xs.text-slate-500');
            if (!label) {
              label = document.createElement('span');
              label.className = 'text-xs text-slate-500';
              label.textContent = 'Pré-visualização';
              previewContainer.insertBefore(label, imagePreview);
            }
          }
          
          // Ocultar placeholder
          if (imagePlaceholder) {
            imagePlaceholder.style.display = 'none';
            imagePlaceholder.classList.add('hidden');
          }
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // ===== Toggle switches principais =====
  // Toggle "Usar grupos de opções"
  const groupsToggleLabel = document.getElementById('groups-toggle-label');
  if (groupsToggleLabel) {
    const checkbox = groupsToggleLabel.querySelector('#groups-toggle');
    const track = groupsToggleLabel.querySelector('.groups-toggle-track');
    const thumb = groupsToggleLabel.querySelector('.groups-toggle-thumb');
    
    groupsToggleLabel.addEventListener('click', function(e) {
      e.preventDefault();
      checkbox.checked = !checkbox.checked;
      
      // Disparar evento change
      checkbox.dispatchEvent(new Event('change', { bubbles: true }));
      
      // Atualizar visual
      if (checkbox.checked) {
        track.classList.remove('bg-slate-300');
        track.classList.add('admin-primary-bg');
        thumb.style.transform = 'translateX(16px)';
      } else {
        track.classList.remove('admin-primary-bg');
        track.classList.add('bg-slate-300');
        thumb.style.transform = 'translateX(0px)';
      }
    });
  }

  // Toggle "Produto ativo"
  const activeToggleLabel = document.getElementById('active-toggle-label');
  if (activeToggleLabel) {
    const checkbox = activeToggleLabel.querySelector('#active');
    const track = activeToggleLabel.querySelector('.active-toggle-track');
    const thumb = activeToggleLabel.querySelector('.active-toggle-thumb');
    
    activeToggleLabel.addEventListener('click', function(e) {
      e.preventDefault();
      checkbox.checked = !checkbox.checked;
      
      // Atualizar visual
      if (checkbox.checked) {
        track.classList.remove('bg-slate-300');
        track.classList.add('admin-primary-bg');
        thumb.style.transform = 'translateX(16px)';
      } else {
        track.classList.remove('admin-primary-bg');
        track.classList.add('bg-slate-300');
        thumb.style.transform = 'translateX(0px)';
      }
    });
  }

  // ===== Visibilidade de Combo =====
  const groupsToggle=document.getElementById('groups-toggle');
  const hiddenUse=document.getElementById('use_groups_hidden');
  const groupsWrap=document.getElementById('groups-wrap');
  function syncGroupsVisibility(){ if(groupsToggle){ toggleBlock(groupsWrap,!!groupsToggle.checked); groupsToggle.setAttribute('aria-expanded', groupsToggle.checked?'true':'false'); } }
  groupsToggle?.addEventListener('change', e=>{ if(hiddenUse) hiddenUse.value=e.target.checked?'1':'0'; syncGroupsVisibility(); });
  syncGroupsVisibility();

  // ===== AUTOCOMPLETE DE PRODUTOS (usando sistema unificado) =====
  let searchCache = new Map();

  async function searchProducts(query, limit = 20) {
    const slug = getCompanySlug();
    if (!slug) return [];

    const cacheKey = `product_${query}_${limit}`;
    if (searchCache.has(cacheKey)) {
      return searchCache.get(cacheKey);
    }

    try {
      const response = await fetch(`/admin/${slug}/products/simple-search?search=${encodeURIComponent(query)}&limit=${limit}`);
      if (!response.ok) throw new Error('Erro na busca');
      
      const data = await response.json();
      if (!data.success) throw new Error(data.error || 'Erro na busca');
      
      const products = data.products || [];
      searchCache.set(cacheKey, products);
      setTimeout(() => searchCache.delete(cacheKey), 5 * 60 * 1000);
      
      return products;
    } catch (error) {
      console.error('Erro ao buscar produtos:', error);
      return [];
    }
  }

  // Configuração do autocomplete de produtos
  const productAutocomplete = createAutocomplete({
    wrapperSelector: '.product-autocomplete-wrapper',
    inputSelector: '.product-search-input',
    suggestionsSelector: '.product-suggestions',
    hiddenInputSelector: '.product-id-input',
    itemClass: 'product-suggestion-item',
    minQueryLength: 2,
    debounceMs: 300,

    search: async (query) => searchProducts(query),

    renderItem: (product) => {
      const item = document.createElement('div');
      item.dataset.id = product.id;
      item.dataset.productPrice = product.price;
      item.dataset.productCustomize = product.allow_customize ? '1' : '0';
      item.dataset.productIngredients = product.ingredient_count;
      
      item.innerHTML = `
        <div class="product-suggestion-name">${escapeHtml(product.name)}</div>
        <div class="product-suggestion-details">
          SKU: ${escapeHtml(product.sku || '')} | 
          <span class="product-suggestion-price">${product.price_formatted}</span>
          ${product.allow_customize && product.ingredient_count > 2 ? ' | Personalizável' : ''}
        </div>
      `;
      return item;
    },

    extractItemData: (el) => ({
      id: el.dataset.id,
      name: el.querySelector('.product-suggestion-name')?.textContent || '',
      price: el.dataset.productPrice,
      allow_customize: el.dataset.productCustomize === '1',
      ingredient_count: parseInt(el.dataset.productIngredients) || 0
    }),

    onSelect: (input, data, wrapper) => {
      const hiddenInput = wrapper.querySelector('.product-id-input');
      const priceInput = input.closest('.item-row')?.querySelector('.price-override-input');
      
      input.value = data.name;
      input.dataset.selectedId = data.id;
      input.dataset.selectedPrice = data.price;
      input.dataset.selectedCustomize = data.allow_customize ? '1' : '0';
      input.dataset.selectedIngredients = data.ingredient_count;
      
      if (hiddenInput) hiddenInput.value = data.id;
      
      searchCache.clear();
      
      if (priceInput) {
        const originalPrice = parseFloat(data.price) || 0;
        priceInput.dataset.originalPrice = originalPrice;
        priceInput.value = originalPrice.toFixed(2);
        priceInput.classList.remove('is-customized');
      }
      
      const row = input.closest('.item-row');
      if (row) {
        syncCustomizationControls(row);
        updateGroupFooter(row.closest('.group-card'));
      }
    },

    getSelectedIds: (input) => {
      const currentRow = input.closest('.item-row');
      const groupCard = currentRow?.closest('.group-card');
      const ids = [];
      groupCard?.querySelectorAll('.item-row').forEach(row => {
        if (row !== currentRow) {
          const id = row.querySelector('.product-id-input')?.value;
          if (id) ids.push(id);
        }
      });
      return ids;
    }
  });

  function setupProductAutocomplete(input) {
    productAutocomplete.setup(input);
  }

  function updatePriceCustomization(priceInput) {
    const originalPrice = parseFloat(priceInput.dataset.originalPrice) || 0;
    const currentPrice = parseFloat(priceInput.value) || 0;
    priceInput.classList.toggle('is-customized', Math.abs(currentPrice - originalPrice) > 0.01);
  }

  // Setup para campos de preço customizado
  function setupPriceOverride(priceInput) {
    if (priceInput.dataset.priceSetup) return;
    priceInput.dataset.priceSetup = 'true';
    
    priceInput.addEventListener('input', () => updatePriceCustomization(priceInput));
    priceInput.addEventListener('blur', () => {
      priceInput.value = (parseFloat(priceInput.value) || 0).toFixed(2);
      updatePriceCustomization(priceInput);
    });
  }

  // ===== COMBO wiring =====
  const gContainer=document.getElementById('groups-container'),
        addGroupBtn=document.getElementById('add-group'),
        tplGroup=document.getElementById('tpl-group'),
        tplItem=document.getElementById('tpl-item');
  const typeSelect=document.getElementById('type');
  const customizationCard=document.getElementById('customization-card');

  function updateItemPrice(row) {
    const priceInput = row.querySelector('.price-override-input');
    return parseFloat(priceInput?.dataset.originalPrice) || 0;
  }

  function setDefaultFlag(row,on){
    const flag=row.querySelector('.combo-default-flag');
    const btn=row.querySelector('.combo-default-btn');
    const oldBtn=row.querySelector('.combo-default-toggle'); // compatibilidade
    if(flag) flag.value=on?'1':'0';
    if(btn) {
      const activeClass = (btn.dataset.activeClass || '').split(' ').filter(Boolean);
      const inactiveClass = (btn.dataset.inactiveClass || '').split(' ').filter(Boolean);
      if(on) {
        btn.classList.remove(...inactiveClass);
        btn.classList.add(...activeClass);
      } else {
        btn.classList.remove(...activeClass);
        btn.classList.add(...inactiveClass);
      }
      btn.textContent = on ? 'Sim' : 'Não';
    }
    if(oldBtn) oldBtn.classList.toggle('is-active',!!on);
    
    // Ajustar quantidade automaticamente
    // Se marcar padrão e qty=0, muda para 1. Se já tiver valor > 0, mantém.
    // Se desmarcar padrão, muda para 0.
    const qtyInput = row.querySelector('.default-qty-input');
    if (qtyInput) {
      const currentQty = parseInt(qtyInput.value) || 0;
      if (on) {
        // Marcou como padrão: se estava 0, coloca 1
        if (currentQty === 0) {
          qtyInput.value = '1';
        }
        // Se já tinha valor > 0, mantém
      } else {
        // Desmarcou padrão: volta para 0
        qtyInput.value = '0';
      }
    }
  }

  function setCustomFlag(row,on){
    // Atualizar o campo hidden que será enviado ao servidor
    const flag=row.querySelector('.combo-item-customizable');
    const btn=row.querySelector('.combo-custom-toggle');
    if(flag) flag.value=on?'1':'0';
    if(btn) btn.classList.toggle('is-active',!!on);
  }

  function syncCustomizationControls(row){
    const searchInput = row.querySelector('.product-search-input');
    const allow = searchInput?.dataset.selectedCustomize === '1';
    const count = Number(searchInput?.dataset.selectedIngredients || '0');
    const can = allow && count > 2;
    const btn = row.querySelector('.combo-custom-toggle');
    const wrapper = row.querySelector('.combo-custom-wrapper');
    const group = row.closest('.group-card');
    const groupEnabled = group?.dataset.customGroup === '1';
    const typeIsCombo = typeSelect?.value === 'combo';
    
    if (btn) { btn.classList.toggle('hidden', !can); }
    if (!can) { 
      setCustomFlag(row, false); 
    } else if (groupEnabled && typeIsCombo) {
      // Se o grupo está habilitado para personalização e o produto permite, habilitar automaticamente
      setCustomFlag(row, true);
    }
    if (wrapper) {
      const shouldShow = can && groupEnabled && typeIsCombo;
      wrapper.classList.toggle('hidden', !shouldShow);
    }
  }

  function updateGroupFooter(groupEl){
    let sum = 0;
    groupEl?.querySelectorAll('.item-row').forEach(r => {
      const flag = r.querySelector('.combo-default-flag');
      if (flag?.value === '1') {
        const priceInput = r.querySelector('.price-override-input');
        const price = parseFloat(priceInput?.value) || 0;
        sum += price;
      }
    });
    const footer = groupEl?.querySelector('.group-base-price');
    if (footer) footer.textContent = `Preço base: ${formatMoney(sum)}`;
  }

  function wireItemRow(row){
    const searchInput = row.querySelector('.product-search-input');
    const priceInput = row.querySelector('.price-override-input');
    const defaultBtn = row.querySelector('.combo-default-btn');
    const defaultBtnOld = row.querySelector('.combo-default-toggle'); // compatibilidade
    const customBtn = row.querySelector('.combo-custom-toggle');
    
    if (searchInput) {
      setupProductAutocomplete(searchInput);
      syncCustomizationControls(row);
    }
    
    if (priceInput) {
      setupPriceOverride(priceInput);
    }
    
    // Novo botão Sim/Não
    if (defaultBtn) {
      defaultBtn.addEventListener('click', () => {
        const group = row.closest('.group-card');
        const wasActive = defaultBtn.classList.contains('admin-primary-bg');
        group?.querySelectorAll('.item-row').forEach(r => setDefaultFlag(r, false));
        if (!wasActive) { setDefaultFlag(row, true); }
        else { setDefaultFlag(row, false); }
        updateGroupFooter(group);
      });
    }
    
    // Compatibilidade com botão antigo
    if (defaultBtnOld && !defaultBtn) {
      defaultBtnOld.addEventListener('click', () => {
        const group = row.closest('.group-card');
        const wasActive = defaultBtnOld.classList.contains('is-active');
        group?.querySelectorAll('.item-row').forEach(r => setDefaultFlag(r, false));
        if (!wasActive) { setDefaultFlag(row, true); }
        else { setDefaultFlag(row, false); }
        updateGroupFooter(group);
      });
    }
    
    if (customBtn) {
      customBtn.addEventListener('click', () => {
        const active = customBtn.classList.contains('is-active');
        setCustomFlag(row, !active);
      });
    }
    
    const initDefault = row.querySelector('.combo-default-flag');
    if (initDefault) { setDefaultFlag(row, initDefault.value === '1'); }
    const initCustom = row.querySelector('.combo-item-customizable');
    if (initCustom) { setCustomFlag(row, initCustom.value === '1'); }
  }

  function refreshGroupCustomBox(groupEl){
    if(!groupEl) return;
    const info=groupEl.querySelector('.combo-group-customizable');
    const isComboType=typeSelect?.value==='combo';
    if(info){
      info.classList.toggle('hidden', !isComboType);
      const switchEl=info.querySelector('.combo-group-custom-switch');
      if(switchEl){
        switchEl.disabled=!isComboType;
        switchEl.checked=isComboType && groupEl.dataset.customGroup==='1';
      }
    }
    groupEl.querySelectorAll('.item-row').forEach(r=>syncCustomizationControls(r));
  }
  function refreshGroupCustomBoxes(){ document.querySelectorAll('.group-card').forEach(refreshGroupCustomBox); }
  function setGroupCustomState(groupEl, enabled){
    if(!groupEl) return;
    groupEl.dataset.customGroup = enabled ? '1' : '0';
    const switchEl=groupEl.querySelector('.combo-group-custom-switch');
    if(switchEl){ 
      switchEl.checked = !!enabled; 
      
      // Atualizar visual do toggle
      const label = groupEl.querySelector('.combo-group-custom-label');
      if (label) {
        const track = label.querySelector('.combo-toggle-track');
        const thumb = label.querySelector('.combo-toggle-thumb');
        if (track && thumb) {
          if (enabled) {
            track.classList.remove('bg-slate-300');
            track.classList.add('admin-primary-bg');
            thumb.style.transform = 'translateX(16px)';
          } else {
            track.classList.remove('admin-primary-bg');
            track.classList.add('bg-slate-300');
            thumb.style.transform = 'translateX(0px)';
          }
        }
      }
    }
    groupEl.querySelectorAll('.item-row').forEach(row=>{
      const searchInput = row.querySelector('.product-search-input');
      const allow = searchInput?.dataset.selectedCustomize === '1';
      const count = Number(searchInput?.dataset.selectedIngredients || '0');
      if(enabled && allow && count>2){ setCustomFlag(row,true); }
      if(!enabled){ setCustomFlag(row,false); }
      syncCustomizationControls(row);
    });
  }
  function wireGroupCard(groupEl){
    if(!groupEl) return;
    groupEl.querySelectorAll('.item-row').forEach(wireItemRow);
    updateGroupFooter(groupEl);
    if(!groupEl.dataset.comboGroupWired){
      // Setup toggle switch visual
      const label = groupEl.querySelector('.combo-group-custom-label');
      if (label) {
        setupToggleSwitch(label);
      }
      
      const switchEl=groupEl.querySelector('.combo-group-custom-switch');
      if(switchEl){
        switchEl.addEventListener('change', ()=>{
          setGroupCustomState(groupEl, !!switchEl.checked);
        });
      }
      groupEl.dataset.comboGroupWired='1';
    }
    refreshGroupCustomBox(groupEl);
  }
  document.querySelectorAll('.group-card').forEach(wireGroupCard);

  let gIndex=gContainer?Array.from(gContainer.children).length:0;
  function addGroup(){
    const gi=gIndex++;
    const html=tplGroup.innerHTML.replaceAll('__GI__',gi);
    const wrap=document.createElement('div'); wrap.innerHTML=html.trim();
    const el=wrap.firstElementChild;
    gContainer.appendChild(el);
    wireGroupCard(el);
    refreshComboGroupOrder();
    return el;
  }
  function nextItemIndex(groupEl){ const idxs=Array.from(groupEl.querySelectorAll('.item-row')).map(r=>Number(r.dataset.itemIndex||0)); return idxs.length?Math.max(...idxs)+1:0; }
  function addItem(groupEl){
    const gi=Number(groupEl.dataset.index);
    const ii=nextItemIndex(groupEl);
    const html=tplItem.innerHTML.replaceAll('__GI__',gi).replaceAll('__II__',ii);
    const wrap=document.createElement('div'); wrap.innerHTML=html.trim();
    const row=wrap.firstElementChild;
    const footer=groupEl.querySelector('.group-base-price')?.parentElement;
    (footer?groupEl.insertBefore(row,footer):groupEl.appendChild(row));
    row.dataset.itemIndex=ii;
    wireItemRow(row);
    updateGroupFooter(groupEl);
    if(groupEl.dataset.customGroup==='1'){ setGroupCustomState(groupEl,true); }
    return row;
  }
  addGroupBtn?.addEventListener('click', ()=>{ const group=addGroup(); refreshGroupCustomBox(group); });
  gContainer?.addEventListener('click', ev=>{ 
    const t=ev.target; 
    if(t.classList.contains('add-item')){ 
      const group=t.closest('.group-card'); 
      const row=addItem(group); 
      if(group?.dataset.customGroup==='1'){ 
        setGroupCustomState(group,true); 
      } else if(row){ 
        syncCustomizationControls(row); 
      }
      
      // Atualizar helpers de modo fixo se necessário
      const priceModeSelect = document.getElementById('price_mode');
      const typeSelect = document.getElementById('type');
      if (priceModeSelect && typeSelect && 
          priceModeSelect.value === 'fixed' && typeSelect.value === 'combo') {
        setTimeout(showFixedModeHelpers, 100);
      }
    } 
    if(t.classList.contains('remove-group')){ 
      t.closest('.group-card')?.remove(); 
      refreshComboGroupOrder(); 
      // Limpar cache quando grupo é removido
      searchCache.clear();
    } 
    if(t.classList.contains('remove-item')){ 
      const g=t.closest('.group-card'); 
      t.closest('.item-row')?.remove(); 
      if(g) updateGroupFooter(g); 
      // Limpar cache quando item é removido para atualizar filtros
      searchCache.clear();
    } 
  });

  // ===== DRAG & DROP — COMBO =====
  let comboDragging=null, comboGhost=null;
  function getDragAfterElement(container,y,selector){
    const siblings=Array.from(container.querySelectorAll(selector)).filter(el=>el!==comboDragging);
    let closest={offset:Number.NEGATIVE_INFINITY,element:null};
    for(const child of siblings){
      const box=child.getBoundingClientRect(); const offset=y-(box.top+box.height/2);
      if(offset<0 && offset>closest.offset){ closest={offset,element:child}; }
    }
    return closest.element;
  }
  function refreshComboGroupOrder(){
    gContainer?.querySelectorAll('.group-card').forEach((g,idx)=>{
      g.dataset.index=idx;
      const inp=g.querySelector('.combo-order-input'); if(inp) inp.value=String(idx);
    });
  }
  gContainer?.addEventListener('dragstart', e=>{
    const handle=e.target.closest('.combo-drag-handle'); if(!handle){ e.preventDefault(); return; }
    const card=handle.closest('.group-card'); if(!card){ e.preventDefault(); return; }
    comboDragging=card; card.classList.add('dragging');
    if(e.dataTransfer){
      e.dataTransfer.effectAllowed='move'; e.dataTransfer.setData('text/plain','');
      const rect=card.getBoundingClientRect();
      const ghost=card.cloneNode(true);
      ghost.classList.add('combo-drag-ghost'); ghost.style.width=`${rect.width}px`; ghost.style.height=`${rect.height}px`;
      ghost.style.position='fixed'; ghost.style.top='-9999px'; ghost.style.left='-9999px'; ghost.style.opacity='0.85'; ghost.style.pointerEvents='none';
      document.body.appendChild(ghost); comboGhost=ghost;
      const offsetX=(e.clientX-rect.left)||rect.width/2, offsetY=(e.clientY-rect.top)||rect.height/2;
      e.dataTransfer.setDragImage(ghost, offsetX, offsetY);
    }
  });
  gContainer?.addEventListener('dragend', ()=>{
    if(comboDragging){ comboDragging.classList.remove('dragging'); comboDragging=null; refreshComboGroupOrder(); }
    if(comboGhost){ comboGhost.remove(); comboGhost=null; }
  });
  gContainer?.addEventListener('dragover', e=>{
    if(!comboDragging) return; e.preventDefault();
    const after=getDragAfterElement(gContainer, e.clientY, '.group-card');
    if(!after){ gContainer.appendChild(comboDragging); }
    else if(after!==comboDragging){ gContainer.insertBefore(comboDragging, after); }
  });
  gContainer?.addEventListener('drop', e=>{ if(!comboDragging) return; e.preventDefault(); refreshComboGroupOrder(); });

  // ===== DRAG & DROP — ITEMS DO COMBO =====
  let comboItemDragging = null;
  let comboItemSourceGroup = null;
  let canDragComboItem = false;

  function getComboItemAfterElement(container, y) {
    const siblings = Array.from(container.querySelectorAll('.item-row')).filter(el => el !== comboItemDragging);
    let closest = { offset: Number.NEGATIVE_INFINITY, element: null };
    for (const child of siblings) {
      const box = child.getBoundingClientRect();
      const offset = y - (box.top + box.height / 2);
      if (offset < 0 && offset > closest.offset) {
        closest = { offset, element: child };
      }
    }
    return closest.element;
  }

  function refreshComboItemOrder(groupEl) {
    if (!groupEl) return;
    groupEl.querySelectorAll('.item-row').forEach((item, idx) => {
      item.dataset.itemIndex = idx;
      const orderInput = item.querySelector('.combo-item-order');
      if (orderInput) orderInput.value = String(idx);
    });
  }

  // Mousedown no handle habilita o drag
  document.addEventListener('mousedown', e => {
    const handle = e.target.closest('.combo-item-drag-handle');
    if (handle) {
      canDragComboItem = true;
      const item = handle.closest('.item-row');
      if (item) item.style.cursor = 'grabbing';
    }
  });
  
  document.addEventListener('mouseup', () => {
    canDragComboItem = false;
    document.querySelectorAll('.item-row').forEach(item => {
      item.style.cursor = '';
    });
  });

  gContainer?.addEventListener('dragstart', e => {
    const item = e.target.closest('.item-row');
    if (!item) return;
    
    if (!canDragComboItem) {
      e.preventDefault();
      return;
    }
    
    e.stopPropagation();
    comboItemDragging = item;
    comboItemSourceGroup = item.closest('.group-card');
    item.classList.add('dragging');
    
    if (e.dataTransfer) {
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', 'combo-item');
    }
  }, true);

  document.addEventListener('dragend', e => {
    if (!comboItemDragging) return;
    comboItemDragging.classList.remove('dragging');
    comboItemDragging.style.cursor = '';
    if (comboItemSourceGroup) {
      refreshComboItemOrder(comboItemSourceGroup);
    }
    comboItemDragging = null;
    comboItemSourceGroup = null;
    canDragComboItem = false;
  });

  gContainer?.addEventListener('dragover', e => {
    if (!comboItemDragging) return;
    e.preventDefault();
    
    const targetGroup = e.target.closest('.group-card');
    if (!targetGroup || targetGroup !== comboItemSourceGroup) return;
    
    const after = getComboItemAfterElement(targetGroup, e.clientY);
    const footer = targetGroup.querySelector('.flex.items-center.justify-between');
    
    if (!after) {
      if (footer) {
        targetGroup.insertBefore(comboItemDragging, footer);
      } else {
        targetGroup.appendChild(comboItemDragging);
      }
    } else if (after !== comboItemDragging) {
      targetGroup.insertBefore(comboItemDragging, after);
    }
  });

  gContainer?.addEventListener('drop', e => {
    if (!comboItemDragging) return;
    e.preventDefault();
    if (comboItemSourceGroup) {
      refreshComboItemOrder(comboItemSourceGroup);
    }
  });

  // ===== PERSONALIZAÇÃO =====
  const custToggle=document.getElementById('customization-enabled');
  const custHidden=document.getElementById('customization-enabled-hidden');
  const custWrap=document.getElementById('customization-wrap');
  const custCont=document.getElementById('cust-groups-container');
  const custAddGrp=document.getElementById('cust-add-group');
  const tplCustGrp=document.getElementById('tpl-cust-group');
  const tplCustItm=document.getElementById('tpl-cust-item');

  function refreshCustGroupOrder(){
    custCont?.querySelectorAll('.cust-group').forEach((g,idx)=>{
      const order=g.querySelector('.cust-order-input'); if(order) order.value=String(idx);
    });
  }
  function updateCustItem(itemEl){
    if(!itemEl) return;
    const groupEl=itemEl.closest('.cust-group');
    const mode=groupEl?.dataset.mode==='choice'?'choice':'extra';
    const limits=itemEl.querySelector('.cust-limits');
    const minInput=itemEl.querySelector('.cust-min-input');
    const maxInput=itemEl.querySelector('.cust-max-input');
    const qtyWrap=itemEl.querySelector('.cust-default-qty-wrap');
    const qtyInput=itemEl.querySelector('.cust-default-qty');
    const toggleWrap=itemEl.querySelector('.cust-default-toggle-wrap');
    const checkbox=itemEl.querySelector('.cust-default-toggle');
    const flag=itemEl.querySelector('.cust-default-flag');

    let min=Number(minInput?.value ?? 0), max=Number(maxInput?.value ?? min);
    if(Number.isNaN(min)||min<0) min=0;
    if(Number.isNaN(max)||max<min) max=min;
    if(minInput){ minInput.value=String(min); }
    if(maxInput){ maxInput.value=String(max); }
    if(limits){ limits.dataset.min=String(min); limits.dataset.max=String(max); }
    
    if(mode==='choice'){
      // Quantidade padrão pode ser qualquer valor (sem limite de max), mínimo 0
      if(qtyInput){ qtyInput.min='0'; qtyInput.removeAttribute('max'); }
      // Mostrar checkbox e sempre mostrar qtd padrão (cada item pode ter sua qtd padrão)
      if(toggleWrap) toggleWrap.classList.remove('hidden');
      if(qtyWrap) qtyWrap.classList.remove('hidden');
      const isChecked = checkbox?.checked ?? false;
      if(flag) flag.value = isChecked ? '1' : '0';
    }else{
      // Modo extra: quantidade padrão pode ser qualquer valor (sem limite de max)
      if(qtyInput){ qtyInput.min='0'; qtyInput.removeAttribute('max'); }
      // Mostrar checkbox e qtd padrão em ambos os modos
      if(toggleWrap) toggleWrap.classList.remove('hidden');
      if(qtyWrap) qtyWrap.classList.remove('hidden');
      // Manter flag sincronizado com checkbox
      const isChecked = checkbox?.checked ?? false;
      if(flag) flag.value = isChecked ? '1' : '0';
    }
  }
  function applyCustMode(groupEl){
    const select=groupEl.querySelector('.cust-mode-select');
    const choiceWrap=groupEl.querySelector('.cust-choice-settings');
    const poolWrap=groupEl.querySelector('.cust-pool-settings');
    const addItemBtn=groupEl.querySelector('.cust-add-item');
    const addChoiceBtn=groupEl.querySelector('.cust-add-choice');
    const val=select?.value||'extra';
    const mode=val==='choice'?'choice':(val==='pool'?'pool':'extra');
    groupEl.dataset.mode=mode;
    toggleBlock(choiceWrap, mode==='choice');
    toggleBlock(poolWrap, mode==='pool');
    if(addItemBtn) addItemBtn.textContent = mode==='choice' ? '+ Opção' : '+ Ingrediente';
    if(addChoiceBtn) addChoiceBtn.classList.toggle('hidden', mode==='choice'||mode==='pool');
    groupEl.querySelectorAll('.cust-item').forEach(updateCustItem);
  }
  function wireCustItem(itemEl){
    if(!itemEl) return;
    const flag=itemEl.querySelector('.cust-default-flag');
    const qtyInput=itemEl.querySelector('.cust-default-qty');
    const checkbox=itemEl.querySelector('.cust-default-toggle');
    const toggleBtn=itemEl.querySelector('.cust-default-btn');
    const groupEl = itemEl.closest('.cust-group');
    
    // NÃO sincronizar checkbox com qty - o checkbox deve refletir o valor do flag (is_default)
    // O flag já vem com o valor correto do banco
    if(checkbox && flag){
      checkbox.checked = flag.value === '1';
    }
    
    // Sincronizar visual do botão com o checkbox
    const updateToggleBtnVisual = () => {
      if(toggleBtn && checkbox){
        if(checkbox.checked){
          toggleBtn.textContent = 'Sim';
          toggleBtn.classList.remove('bg-white', 'border-slate-300', 'text-slate-600', 'hover:bg-slate-50');
          toggleBtn.classList.add('admin-primary-bg', 'border-transparent', 'text-white');
        } else {
          toggleBtn.textContent = 'Não';
          toggleBtn.classList.remove('admin-primary-bg', 'border-transparent', 'text-white');
          toggleBtn.classList.add('bg-white', 'border-slate-300', 'text-slate-600', 'hover:bg-slate-50');
        }
      }
    };
    updateToggleBtnVisual();
    
    // Função para contar quantos itens estão marcados como padrão no grupo
    const countDefaultsInGroup = () => {
      if(!groupEl) return 0;
      let count = 0;
      groupEl.querySelectorAll('.cust-item .cust-default-toggle').forEach(cb => {
        if(cb.checked) count++;
      });
      return count;
    };
    
    // Função para desmarcar outros itens quando atingir o limite
    const enforceMaxDefaults = () => {
      if(!groupEl) return;
      const maxInput = groupEl.querySelector('.cust-choice-max');
      const maxSel = maxInput ? parseInt(maxInput.value || '1', 10) : 1;
      const currentCount = countDefaultsInGroup();
      
      if(currentCount > maxSel){
        // Desmarcar outros itens (o mais recente fica, os anteriores são desmarcados)
        const items = groupEl.querySelectorAll('.cust-item');
        let remaining = currentCount - maxSel;
        items.forEach(item => {
          if(remaining <= 0) return;
          if(item === itemEl) return; // Não desmarcar o item atual
          const otherCb = item.querySelector('.cust-default-toggle');
          const otherFlag = item.querySelector('.cust-default-flag');
          const otherBtn = item.querySelector('.cust-default-btn');
          if(otherCb && otherCb.checked){
            otherCb.checked = false;
            if(otherFlag) otherFlag.value = '0';
            if(otherBtn){
              otherBtn.textContent = 'Não';
              otherBtn.classList.remove('admin-primary-bg', 'border-transparent', 'text-white');
              otherBtn.classList.add('bg-white', 'border-slate-300', 'text-slate-600', 'hover:bg-slate-50');
            }
            remaining--;
          }
        });
      }
    };
    
    // Clique no botão toggle
    if(toggleBtn && !toggleBtn.dataset.wired){
      toggleBtn.addEventListener('click', ()=>{
        if(checkbox){
          checkbox.checked = !checkbox.checked;
          updateToggleBtnVisual();
          if(checkbox.checked){
            // Se marcou como Sim, verificar se precisa desmarcar outros
            // Apenas no modo "choice" - no modo "extra" permite múltiplos padrões
            const mode = groupEl?.dataset.mode === 'choice' ? 'choice' : 'extra';
            if(mode === 'choice'){
              enforceMaxDefaults();
            }
          }
          updateCustItem(itemEl);
        }
      });
      toggleBtn.dataset.wired='1';
    }
    
    // Referências para min/max inputs
    const minInput = itemEl.querySelector('.cust-min-input');
    const maxInput = itemEl.querySelector('.cust-max-input');
    
    // Atualizar flag quando quantidade muda (modo extra) e sincronizar max
    if(qtyInput && !qtyInput.dataset.wired){
      qtyInput.addEventListener('input', ()=>{
        const groupEl = itemEl.closest('.cust-group');
        const mode = groupEl?.dataset.mode === 'choice' ? 'choice' : 'extra';
        const qty = Number(qtyInput.value || 0);
        
        // Sincronizar max: se qtd padrão > max, aumentar max
        if(maxInput){
          const currentMax = Number(maxInput.value || 0);
          if(qty > currentMax){
            maxInput.value = qty;
          }
        }
        
        if(mode === 'extra'){
          if(flag){ flag.value = qty > 0 ? '1' : '0'; }
        }
      });
      qtyInput.dataset.wired='1';
    }
    
    // Sincronizar max quando diminuir: não deixar menor que qtd padrão
    if(maxInput && !maxInput.dataset.wiredQty){
      maxInput.addEventListener('input', ()=>{
        const qty = Number(qtyInput?.value || 0);
        const newMax = Number(maxInput.value || 0);
        // Se max ficar menor que qtd padrão, ajustar qtd padrão para baixo
        if(qtyInput && newMax < qty){
          qtyInput.value = newMax;
          // Atualizar flag se modo extra
          const groupEl = itemEl.closest('.cust-group');
          const mode = groupEl?.dataset.mode === 'choice' ? 'choice' : 'extra';
          if(mode === 'extra' && flag){
            flag.value = newMax > 0 ? '1' : '0';
          }
        }
      });
      maxInput.dataset.wiredQty='1';
    }
    
    // Atualizar quando checkbox muda (modo choice)
    if(checkbox && !checkbox.dataset.wired){
      checkbox.addEventListener('change', ()=>{
        updateToggleBtnVisual();
        updateCustItem(itemEl);
      });
      checkbox.dataset.wired='1';
    }
    
    // Inicializar typeahead para ingredientes
    const typeaheadInput = itemEl.querySelector('.ingredient-typeahead-input');
    if (typeaheadInput && setupIngredientTypeahead) {
      setupIngredientTypeahead(typeaheadInput);
    }
    updateCustItem(itemEl);
  }
  function wireCustGroup(groupEl){
    if(!groupEl) return;
    const select=groupEl.querySelector('.cust-mode-select');
    const modeFromVal = (v) => v==='choice'?'choice':(v==='pool'?'pool':'extra');
    if(select && !groupEl.dataset.mode){ groupEl.dataset.mode = modeFromVal(select.value); }
    else if(select){ select.value = groupEl.dataset.mode==='choice'?'choice':(groupEl.dataset.mode==='pool'?'pool':'extra'); }
    if(select && !select.dataset.wired){
      select.addEventListener('change', ()=>{
        groupEl.dataset.mode = modeFromVal(select.value);
        applyCustMode(groupEl);
      });
      select.dataset.wired='1';
    }
    groupEl.querySelectorAll('.cust-item').forEach(wireCustItem);
    applyCustMode(groupEl);
  }
  function nextCustGroupIndex(){
    const idxs=Array.from(custCont.querySelectorAll('.cust-group')).map(g=>Number(g.dataset.index||0));
    return idxs.length?Math.max(...idxs)+1:0;
  }
  function nextCustItemIndex(groupEl){
    const idxs=Array.from(groupEl.querySelectorAll('.cust-item')).map(r=>Number(r.dataset.itemIndex||0));
    return idxs.length?Math.max(...idxs)+1:0;
  }
  function addCustGroup(){
    const gi=nextCustGroupIndex();
    const html=tplCustGrp.innerHTML.replaceAll('__CGI__',gi);
    const wrap=document.createElement('div'); wrap.innerHTML=html.trim();
    const node=wrap.firstElementChild;
    custCont.appendChild(node);
    wireCustGroup(node);
    refreshCustGroupOrder();
    return node;
  }
  function addCustItem(groupEl){
    const gi=Number(groupEl.dataset.index);
    const ii=nextCustItemIndex(groupEl);
    const html=tplCustItm.innerHTML.replaceAll('__CGI__',gi).replaceAll('__CII__',ii);
    const wrap=document.createElement('div'); wrap.innerHTML=html.trim();
    const row=wrap.firstElementChild;
    const footer=Array.from(groupEl.children).find(el=>el.matches('.flex.border-t, .border-t'));
    (footer?groupEl.insertBefore(row,footer):groupEl.appendChild(row));
    row.dataset.itemIndex=ii;
    wireCustItem(row); applyCustMode(groupEl);
    return row;
  }
  custAddGrp?.addEventListener('click', addCustGroup);
  custCont?.addEventListener('click', e=>{
    const t=e.target;
    if(t.classList.contains('cust-add-item')){ addCustItem(t.closest('.cust-group')); }
    else if(t.classList.contains('cust-add-choice')){ const g=t.closest('.cust-group'); const sel=g?.querySelector('.cust-mode-select'); if(sel){ sel.value='choice'; } applyCustMode(g); addCustItem(g); }
    else if(t.classList.contains('cust-remove-group')){ t.closest('.cust-group')?.remove(); refreshCustGroupOrder(); }
    else if(t.classList.contains('cust-remove-item')){ t.closest('.cust-item')?.remove(); }
  });

  // DRAG & DROP — PERSONALIZAÇÃO
  let custDragging=null, custGhost=null;
  function getCustAfterElement(container,y){
    const siblings=Array.from(container.querySelectorAll('.cust-group')).filter(el=>el!==custDragging);
    let closest={offset:Number.NEGATIVE_INFINITY,element:null};
    for(const child of siblings){
      const box=child.getBoundingClientRect(); const offset=y-(box.top+box.height/2);
      if(offset<0 && offset>closest.offset){ closest={offset,element:child}; }
    }
    return closest.element;
  }
  custCont?.addEventListener('dragstart', e=>{
    const handle=e.target.closest('.cust-drag-handle'); if(!handle){ e.preventDefault(); return; }
    const group=handle.closest('.cust-group'); if(!group){ e.preventDefault(); return; }
    custDragging=group; group.classList.add('dragging');
    if(e.dataTransfer){
      e.dataTransfer.effectAllowed='move'; e.dataTransfer.setData('text/plain','');
      const rect=group.getBoundingClientRect();
      const ghost=group.cloneNode(true);
      ghost.classList.add('cust-drag-ghost'); ghost.style.width=`${rect.width}px`; ghost.style.height=`${rect.height}px`;
      ghost.style.position='fixed'; ghost.style.top='-9999px'; ghost.style.left='-9999px'; ghost.style.opacity='0.85'; ghost.style.pointerEvents='none';
      document.body.appendChild(ghost); custGhost=ghost;
      const offsetX=(e.clientX-rect.left)||rect.width/2, offsetY=(e.clientY-rect.top)||rect.height/2;
      e.dataTransfer.setDragImage(ghost, offsetX, offsetY);
    }
  });
  custCont?.addEventListener('dragend', ()=>{
    if(custDragging){ custDragging.classList.remove('dragging'); custDragging=null; refreshCustGroupOrder(); }
    if(custGhost){ custGhost.remove(); custGhost=null; }
  });
  custCont?.addEventListener('dragover', e=>{
    if(!custDragging) return; e.preventDefault();
    const after=getCustAfterElement(custCont, e.clientY);
    if(!after){ custCont.appendChild(custDragging); }
    else if(after!==custDragging){ custCont.insertBefore(custDragging, after); }
  });
  custCont?.addEventListener('drop', e=>{ if(!custDragging) return; e.preventDefault(); refreshCustGroupOrder(); });

  // ===== DRAG & DROP — INGREDIENTES (items dentro de grupos) =====
  let custItemDragging = null;
  let custItemGhost = null;
  let custItemSourceGroup = null;

  function getCustItemAfterElement(container, y) {
    const siblings = Array.from(container.querySelectorAll('.cust-item')).filter(el => el !== custItemDragging);
    let closest = { offset: Number.NEGATIVE_INFINITY, element: null };
    for (const child of siblings) {
      const box = child.getBoundingClientRect();
      const offset = y - (box.top + box.height / 2);
      if (offset < 0 && offset > closest.offset) {
        closest = { offset, element: child };
      }
    }
    return closest.element;
  }

  function refreshCustItemOrder(groupEl) {
    if (!groupEl) return;
    groupEl.querySelectorAll('.cust-item').forEach((item, idx) => {
      item.dataset.itemIndex = idx;
      const orderInput = item.querySelector('.cust-item-order');
      if (orderInput) orderInput.value = String(idx);
    });
  }

  // ===== DRAG & DROP — INGREDIENTES (items dentro de grupos) =====
  // Controlar se o drag pode iniciar baseado no handle
  let canDragItem = false;
  
  // Mousedown no handle habilita o drag
  document.addEventListener('mousedown', e => {
    const handle = e.target.closest('.cust-item-drag-handle');
    if (handle) {
      canDragItem = true;
      // Adicionar classe visual de arrastar
      const item = handle.closest('.cust-item');
      if (item) item.style.cursor = 'grabbing';
    }
  });
  
  document.addEventListener('mouseup', () => {
    canDragItem = false;
    // Remover cursor de todos os items
    document.querySelectorAll('.cust-item').forEach(item => {
      item.style.cursor = '';
    });
  });

  // Usar delegação de eventos no container principal
  custCont?.addEventListener('dragstart', e => {
    const item = e.target.closest('.cust-item');
    if (!item) return;
    
    // Verificar se o drag foi iniciado pelo handle (via mousedown)
    if (!canDragItem) {
      e.preventDefault();
      return;
    }
    
    e.stopPropagation(); // Evitar que o drag de grupo seja acionado
    custItemDragging = item;
    custItemSourceGroup = item.closest('.cust-group');
    item.classList.add('dragging');
    
    if (e.dataTransfer) {
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', 'item');
    }
  }, true); // Usar capture para pegar antes do drag de grupo

  document.addEventListener('dragend', e => {
    if (!custItemDragging) return;
    custItemDragging.classList.remove('dragging');
    custItemDragging.style.cursor = '';
    if (custItemSourceGroup) {
      refreshCustItemOrder(custItemSourceGroup);
    }
    custItemDragging = null;
    custItemSourceGroup = null;
    canDragItem = false;
    if (custItemGhost) { custItemGhost.remove(); custItemGhost = null; }
  });

  custCont?.addEventListener('dragover', e => {
    if (!custItemDragging) return;
    e.preventDefault();
    
    // Encontrar o grupo onde o mouse está
    const targetGroup = e.target.closest('.cust-group');
    if (!targetGroup || targetGroup !== custItemSourceGroup) return; // Só permitir dentro do mesmo grupo
    
    const after = getCustItemAfterElement(targetGroup, e.clientY);
    const itemsContainer = targetGroup; // Items ficam dentro do grupo
    
    if (!after) {
      // Inserir antes do footer (botões + Ingrediente)
      const footer = targetGroup.querySelector('.flex.items-center.justify-between');
      if (footer) {
        targetGroup.insertBefore(custItemDragging, footer);
      } else {
        targetGroup.appendChild(custItemDragging);
      }
    } else if (after !== custItemDragging) {
      targetGroup.insertBefore(custItemDragging, after);
    }
  });

  custCont?.addEventListener('drop', e => {
    if (!custItemDragging) return;
    e.preventDefault();
    if (custItemSourceGroup) {
      refreshCustItemOrder(custItemSourceGroup);
    }
  });

  // ===== Toggle "Permitir personalização de itens" =====
  const customizationToggleLabel = document.getElementById('customization-toggle-label');
  if (customizationToggleLabel) {
    const checkbox = customizationToggleLabel.querySelector('#customization-enabled');
    const track = customizationToggleLabel.querySelector('.customization-toggle-track');
    const thumb = customizationToggleLabel.querySelector('.customization-toggle-thumb');
    
    customizationToggleLabel.addEventListener('click', function(e) {
      e.preventDefault();
      
      // Não permitir toggle se estiver desabilitado
      if (checkbox.disabled) return;
      
      checkbox.checked = !checkbox.checked;
      
      // Disparar evento change
      checkbox.dispatchEvent(new Event('change', { bubbles: true }));
      
      // Atualizar visual
      if (checkbox.checked) {
        track.classList.remove('bg-slate-300');
        track.classList.add('admin-primary-bg');
        thumb.style.transform = 'translateX(16px)';
      } else {
        track.classList.remove('admin-primary-bg');
        track.classList.add('bg-slate-300');
        thumb.style.transform = 'translateX(0px)';
      }
    });
  }

  // ===== toggle Personalização =====
  function syncCust(){ const on=!!custToggle?.checked; if(custHidden) custHidden.value=on?'1':'0'; toggleBlock(custWrap,on); }
  custToggle?.addEventListener('change', syncCust); syncCust();

  function syncProductTypeSections(){
    const isCombo=typeSelect?.value==='combo';
    const comboGroupsCard = document.getElementById('combo-groups-card');
    
    // Mostrar bloco de Grupos apenas para tipo Combo
    if(comboGroupsCard){ toggleBlock(comboGroupsCard, isCombo); }
    
    // Mostrar bloco de Personalização apenas para tipo Simples
    if(customizationCard){ toggleBlock(customizationCard, !isCombo); }
    if(custToggle){
      custToggle.disabled=!!isCombo;
      if(isCombo){
        if(custToggle.checked){ 
          custToggle.checked=false;
          
          // Atualizar visual do toggle
          const label = document.getElementById('customization-toggle-label');
          if (label) {
            const track = label.querySelector('.customization-toggle-track');
            const thumb = label.querySelector('.customization-toggle-thumb');
            if (track && thumb) {
              track.classList.remove('admin-primary-bg');
              track.classList.add('bg-slate-300');
              thumb.style.transform = 'translateX(0px)';
            }
          }
        }
        if(custHidden) custHidden.value='0';
        syncCust();
      }
    }
    refreshGroupCustomBoxes();
  }
  typeSelect?.addEventListener('change', syncProductTypeSections);
  syncProductTypeSections();

  // ===== CONTROLE DE MODO DE PREÇO =====
  function setupPriceModeControl() {
    const priceModeSelect = document.getElementById('price_mode');
    const priceInput = document.getElementById('price');
    const promoInput = document.getElementById('promo_price');
    const typeSelect = document.getElementById('type');
    
    if (!priceModeSelect || !priceInput) return;
    
    function calculateDefaultItemsPrice() {
      let total = 0;
      
      // Buscar todos os itens marcados como "Acompanhamento padrão"
      const defaultButtons = document.querySelectorAll('.combo-default-toggle.is-active');
      
      defaultButtons.forEach(button => {
        const itemRow = button.closest('.item-row');
        const priceOverrideInput = itemRow.querySelector('.price-override-input');
        
        if (priceOverrideInput && priceOverrideInput.value) {
          const itemPrice = parseFloat(priceOverrideInput.value) || 0;
          total += itemPrice;
        }
      });
      
      return total;
    }
    
    function updatePriceModeState() {
      const priceMode = priceModeSelect.value;
      const productType = typeSelect ? typeSelect.value : 'simple';

      // Elementos dos campos promocionais
      const promoFixedField = document.getElementById('promo-fixed-field');
      const promoSumFields = document.getElementById('promo-sum-fields');
      const promoPercentageInput = document.getElementById('promo_percentage');
      const promoPreview = document.getElementById('promo-preview');

      if (priceMode === 'sum' && productType === 'combo') {
        // Modo "Somar itens" - calcular preço automaticamente
        const calculatedPrice = calculateDefaultItemsPrice();
        priceInput.value = calculatedPrice.toFixed(2);
        priceInput.readOnly = true;
        priceInput.classList.add('bg-gray-100', 'cursor-not-allowed');
        priceInput.title = 'Preço calculado automaticamente pela soma dos itens padrão';

        // Mostrar campos de porcentagem, esconder campo fixo
        if (promoFixedField) promoFixedField.classList.add('hidden');
        if (promoSumFields) promoSumFields.classList.remove('hidden');

        // Converter valor atual de promo_price para porcentagem se necessário
        if (promoInput && promoPercentageInput && promoInput.value) {
          const promoValue = parseFloat(promoInput.value);
          if (promoValue <= 100) {
            // Assumir que já é porcentagem
            promoPercentageInput.value = promoValue;
          } else {
            // Converter de valor absoluto para porcentagem
            const basePrice = parseFloat(priceInput.value) || 0;
            if (basePrice > 0) {
              const percentage = ((basePrice - promoValue) / basePrice * 100);
              promoPercentageInput.value = Math.max(0, Math.min(100, percentage.toFixed(1)));
            }
          }
          promoInput.value = promoPercentageInput.value; // Sincronizar
        }

        // Em modo 'sum' permita que price_override dos itens sejam editáveis (upgrades)
        document.querySelectorAll('.price-override-input').forEach(i => {
          i.readOnly = false;
          i.classList.remove('bg-gray-100', 'cursor-not-allowed');
        });

        // Esconder indicadores de ajuda no modo somar
        hideFixedModeHelpers();
      } else {
        // Modo "Fixo" - preço manual
        priceInput.readOnly = false;
        priceInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
        priceInput.title = '';

        // Mostrar campo fixo, esconder campos de porcentagem
        if (promoFixedField) promoFixedField.classList.remove('hidden');
        if (promoSumFields) promoSumFields.classList.add('hidden');

        // No modo fixo, permitir edição dos preços para upgrades
        document.querySelectorAll('.price-override-input').forEach(i => {
          i.readOnly = false;
          i.classList.remove('bg-gray-100', 'cursor-not-allowed');
        });

        // Mostrar indicadores de ajuda para modo fixo
        showFixedModeHelpers();
      }

      // Atualizar preview de desconto
      updatePromoPreview();
    }

    // Função para atualizar preview do desconto
    function updatePromoPreview() {
      const promoPreview = document.getElementById('promo-preview');
      const previewBase = document.getElementById('preview-base');
      const previewDiscount = document.getElementById('preview-discount');
      const previewDiscounted = document.getElementById('preview-discounted');
      const promoPercentageInput = document.getElementById('promo_percentage');
      const priceModeSelect = document.getElementById('price_mode');

      if (!promoPreview || !previewBase || !previewDiscounted || !promoPercentageInput) return;

      const priceMode = priceModeSelect.value;
      const basePrice = parseFloat(priceInput.value) || 0;
      const discountPercent = parseFloat(promoPercentageInput.value) || 0;

      if (priceMode === 'sum' && discountPercent > 0 && basePrice > 0) {
        const discountAmount = basePrice * (discountPercent / 100);
        const discountedPrice = basePrice - discountAmount;
        
        previewBase.textContent = formatMoney(basePrice);
        if (previewDiscount) {
          previewDiscount.textContent = '-' + formatMoney(discountAmount);
        }
        previewDiscounted.textContent = formatMoney(discountedPrice);
        promoPreview.classList.remove('hidden');

        // Sincronizar com o campo hidden promo_price
        if (promoInput) {
          promoInput.value = discountPercent;
        }
      } else {
        promoPreview.classList.add('hidden');
      }
    }
    
    // Event listeners
    priceModeSelect.addEventListener('change', updatePriceModeState);
    if (typeSelect) {
      typeSelect.addEventListener('change', updatePriceModeState);
    }

    // Event listeners para campos de porcentagem
    const promoPercentageInput = document.getElementById('promo_percentage');
    if (promoPercentageInput) {
      promoPercentageInput.addEventListener('input', function() {
        updatePromoPreview();
      });
    }

    // Event listener para campo de preço base (para atualizar preview)
    if (priceInput) {
      priceInput.addEventListener('input', function() {
        updatePromoPreview();
      });
    }
    
    // Monitorar mudanças nos itens padrão para recalcular
    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('combo-default-btn') || e.target.classList.contains('combo-default-toggle')) {
        // Aguardar a atualização do estado do botão
        setTimeout(updatePriceModeState, 100);
      }
    });
    
    // Monitorar mudanças nos preços dos itens para recalcular
    document.addEventListener('input', function(e) {
      if (e.target.classList.contains('price-override-input')) {
        const priceMode = priceModeSelect.value;
        const productType = typeSelect ? typeSelect.value : 'simple';

        if (priceMode === 'sum' && productType === 'combo') {
          setTimeout(updatePriceModeState, 100);
        }
      }
    });
    
    // Estado inicial
    updatePriceModeState();
  }

  // Função para mostrar/esconder indicadores de ajuda no modo fixo
  function showFixedModeHelpers() {
    // Adicionar indicador "!" ao lado dos labels "Preço base" nos itens dos grupos de combo
    document.querySelectorAll('.item-row .price-override-input').forEach(input => {
      const container = input.closest('div');
      const priceLabel = container.querySelector('label');
      
      if (priceLabel && priceLabel.textContent.trim() === 'Preço base') {
        // Remover indicador existente se houver
        const existingHelper = priceLabel.querySelector('.price-helper');
        if (existingHelper) {
          existingHelper.remove();
        }

        // Criar novo indicador
        const helper = document.createElement('span');
        helper.className = 'price-helper ml-1 relative inline-block cursor-help text-orange-500';
        helper.innerHTML = `
          <span class="text-sm font-bold">!</span>
          <div class="price-helper-tooltip absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-800 text-white text-xs rounded-lg shadow-lg opacity-0 invisible transition-opacity duration-200 w-64 z-50">
            <div class="text-center">
              <strong>Modo Fixo - Upgrades:</strong><br>
              Para upgrades do combo, adicione apenas o valor adicional que o cliente deve pagar.<br>
              <em>Exemplo: Item custa R$ 15, combo inclui R$ 10, coloque R$ 5</em>
            </div>
            <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
          </div>
        `;

        // Eventos de hover para mostrar/esconder tooltip
        helper.addEventListener('mouseenter', () => {
          const tooltip = helper.querySelector('.price-helper-tooltip');
          tooltip.classList.remove('opacity-0', 'invisible');
          tooltip.classList.add('opacity-100', 'visible');
        });

        helper.addEventListener('mouseleave', () => {
          const tooltip = helper.querySelector('.price-helper-tooltip');
          tooltip.classList.remove('opacity-100', 'visible');
          tooltip.classList.add('opacity-0', 'invisible');
        });

        priceLabel.appendChild(helper);
      }
    });
  }

  // Função para esconder indicadores de ajuda
  function hideFixedModeHelpers() {
    document.querySelectorAll('.price-helper').forEach(helper => {
      helper.remove();
    });
  }

  // ===== validação & normalização no submit =====
  document.getElementById('product-form')?.addEventListener('submit', (e)=>{
    const name=document.getElementById('name');
    if(!name.value.trim()){ e.preventDefault(); alert('Informe o nome do produto.'); name.focus(); return; }

    const priceEl = document.getElementById('price'); if (priceEl) { priceEl.value = String(brToFloat(priceEl.value || '0')); }
    const promoEl = document.getElementById('promo_price');
    const priceModeEl = document.getElementById('price_mode');
    const priceModeVal = priceModeEl ? priceModeEl.value : 'fixed';

    if (promoEl) {
      const raw = promoEl.value == null ? '' : String(promoEl.value).trim();

      if (priceModeVal === 'sum') {
        // Promo é porcentagem: aceitar '15' ou '15%'
        let r = raw.replace(',', '.');
        if (r.endsWith('%')) r = r.slice(0, -1).trim();
        if (r === '') {
          promoEl.value = '';
        } else {
          const pct = parseFloat(r);
          if (Number.isNaN(pct) || pct <= 0 || pct >= 100) {
            e.preventDefault();
            alert('Em modo "Somar itens", o preço promocional deve ser uma porcentagem válida entre 0 e 100 (ex: 15).');
            promoEl.focus();
            return;
          }
          // armazenar como número (porcentagem)
          promoEl.value = String(pct);
        }
      } else {
        // Modo fixo: promo em valor monetário
        promoEl.value = raw === '' ? '' : String(brToFloat(raw));
        const price = parseFloat((priceEl?.value || '0'));
        const promoRaw = promoEl.value ?? '';
        const promo = promoRaw === '' ? null : parseFloat(promoRaw || '0');
        if (promoEl && promo !== null && !Number.isNaN(promo)) {
          if (price <= 0 || promo <= 0) { promoEl.value = ''; }
          else if (promo >= price) { e.preventDefault(); alert('O preço promocional deve ser menor que o preço base.'); promoEl.focus(); return; }
        }
      }
    }

    if(groupsToggle && groupsToggle.checked){
      const gs=gContainer.querySelectorAll('.group-card');
      if(!gs.length){ e.preventDefault(); alert('Adicione pelo menos um grupo de opções do combo.'); return; }
      for(const g of gs){
        const gname=g.querySelector('input[name^="groups"][name$="[name]"]'); const items=g.querySelectorAll('.item-row');
        ensureMinMax(g);
        const minEl=g.querySelector('input[name$="[min]"]'), maxEl=g.querySelector('input[name$="[max]"]');
        const min=Number(minEl?.value||0), max=Number(maxEl?.value||0);
        if(max && max<min){ e.preventDefault(); alert('No grupo "'+(gname.value||'')+'", o máximo não pode ser menor que o mínimo.'); maxEl.focus(); return; }
        if(!gname.value.trim() || !items.length){ e.preventDefault(); alert('Cada grupo do combo precisa de nome e ao menos um item.'); gname.focus(); return; }
        for(const it of items){ 
          const hiddenInput = it.querySelector('.product-id-input'); 
          if(!hiddenInput.value){ e.preventDefault(); alert('Selecione um produto simples para cada item do combo.'); 
            const searchInput = it.querySelector('.product-search-input');
            searchInput?.focus(); 
            return; 
          } 
        }
      }
    }

    if(custToggle && custToggle.checked){
      const cgs=custCont.querySelectorAll('.cust-group');
      if(!cgs.length){ e.preventDefault(); alert('Adicione pelo menos um grupo de personalização.'); return; }
      for(const cg of cgs){
        const nameEl=cg.querySelector('input[name^="customization"][name$="[name]"]'); const items=cg.querySelectorAll('.cust-item');
        if(!nameEl.value.trim()){ e.preventDefault(); alert('Cada grupo de personalização precisa de um nome.'); nameEl.focus(); return; }
        if(!items.length){ e.preventDefault(); alert('Adicione pelo menos um ingrediente no grupo "'+(nameEl.value||'')+'".'); return; }
        for(const it of items){
          const hiddenInput=it.querySelector('.ingredient-id-hidden'); 
          if(!hiddenInput || !hiddenInput.value){ 
            e.preventDefault(); 
            alert('Selecione um ingrediente em cada item do grupo "'+(nameEl.value||'')+'".'); 
            const typeaheadInput = it.querySelector('.ingredient-typeahead-input');
            typeaheadInput?.focus(); 
            return; 
          }
        }
      }
    }
  });

  // ===== TYPEAHEAD DE INGREDIENTES (usando sistema unificado) =====
  function getIngredientsData() {
    return window.__INGREDIENTS_DATA__ || [];
  }

  function searchIngredients(query) {
    const ingredientsData = getIngredientsData();
    if (!query || query.length < 1) return ingredientsData.slice(0, 20);
    
    const normalizedQuery = removeAccents(query.toLowerCase());
    const terms = normalizedQuery.split(/\s+/).filter(t => t.length > 0);
    
    return ingredientsData.filter(ing => {
      const name = removeAccents((ing.name || '').toLowerCase());
      const internalName = removeAccents((ing.internal_name || '').toLowerCase());
      const combined = name + ' ' + internalName;
      return terms.every(term => combined.includes(term));
    }).slice(0, 20);
  }

  function clearIngredientSelection(input) {
    const wrapper = input.closest('.ingredient-typeahead-wrapper');
    const hiddenInput = wrapper?.querySelector('.ingredient-id-hidden');
    const clearBtn = wrapper?.querySelector('.ingredient-clear-btn');
    
    if (input) input.value = '';
    if (hiddenInput) hiddenInput.value = '';
    if (input) input.dataset.selectedId = '';
    if (clearBtn) clearBtn.remove();
    
    input?.focus();
  }

  // Configuração do autocomplete de ingredientes
  const ingredientAutocomplete = createAutocomplete({
    wrapperSelector: '.ingredient-typeahead-wrapper',
    inputSelector: '.ingredient-typeahead-input',
    suggestionsSelector: '.ingredient-suggestions',
    hiddenInputSelector: '.ingredient-id-hidden',
    itemClass: 'ingredient-suggestion-item',
    noResultsClass: 'ingredient-no-results',
    minQueryLength: 0,
    debounceMs: 150,
    showOnFocus: true,

    search: (query) => searchIngredients(query),

    renderItem: (ing) => {
      const item = document.createElement('div');
      item.dataset.id = ing.id;
      item.dataset.ingredientName = ing.name;
      item.dataset.ingredientInternalName = ing.internal_name || '';
      item.dataset.ingredientMin = ing.min_qty;
      item.dataset.ingredientMax = ing.max_qty;
      
      let displayName = escapeHtml(ing.name);
      if (ing.internal_name) {
        displayName += ` <span style="color:#64748b">(${escapeHtml(ing.internal_name)})</span>`;
      }
      
      let details = '';
      if (ing.price && ing.price > 0) {
        details = `R$ ${ing.price.toFixed(2).replace('.', ',')}`;
      }
      
      item.innerHTML = `
        <div class="ingredient-suggestion-name">${displayName}</div>
        ${details ? `<div class="ingredient-suggestion-details">${details}</div>` : ''}
      `;
      return item;
    },

    extractItemData: (el) => ({
      id: el.dataset.id,
      name: el.dataset.ingredientName,
      internal_name: el.dataset.ingredientInternalName,
      min_qty: parseInt(el.dataset.ingredientMin) || 0,
      max_qty: parseInt(el.dataset.ingredientMax) || 1
    }),

    onSelect: (input, data, wrapper) => {
      const hiddenInput = wrapper.querySelector('.ingredient-id-hidden');
      
      let displayName = data.name;
      if (data.internal_name) {
        displayName += ` (${data.internal_name})`;
      }
      
      input.value = displayName;
      if (hiddenInput) hiddenInput.value = data.id;
      input.dataset.selectedId = data.id;
      
      // Adicionar botão de limpar se não existir
      let clearBtn = wrapper.querySelector('.ingredient-clear-btn');
      if (!clearBtn) {
        clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'ingredient-clear-btn';
        clearBtn.title = 'Limpar';
        clearBtn.textContent = '✕';
        clearBtn.addEventListener('click', () => clearIngredientSelection(input));
        wrapper.appendChild(clearBtn);
      }
    },

    getSelectedIds: (input) => {
      const currentItem = input.closest('.cust-item');
      const custGroup = currentItem?.closest('.cust-group');
      const ids = [];
      custGroup?.querySelectorAll('.cust-item').forEach(item => {
        if (item !== currentItem) {
          const id = item.querySelector('.ingredient-id-hidden')?.value;
          if (id) ids.push(id);
        }
      });
      return ids;
    }
  });

  setupIngredientTypeahead = function(input) {
    if (input.dataset.typeaheadSetup) return;
    input.dataset.typeaheadSetup = 'true';
    
    ingredientAutocomplete.setup(input);
    
    // Limpar seleção quando usuário digita
    input.addEventListener('input', () => {
      const wrapper = input.closest('.ingredient-typeahead-wrapper');
      const hiddenInput = wrapper?.querySelector('.ingredient-id-hidden');
      if (hiddenInput?.value) {
        hiddenInput.value = '';
        input.dataset.selectedId = '';
        const clearBtn = wrapper?.querySelector('.ingredient-clear-btn');
        if (clearBtn) clearBtn.remove();
      }
    });
    
    // Setup clear button se já existir
    const wrapper = input.closest('.ingredient-typeahead-wrapper');
    const clearBtn = wrapper?.querySelector('.ingredient-clear-btn');
    if (clearBtn) {
      clearBtn.addEventListener('click', () => clearIngredientSelection(input));
    }
  };

  // Inicializar typeahead para ingredientes existentes
  document.querySelectorAll('.ingredient-typeahead-input').forEach(setupIngredientTypeahead);

  // Inicializações
  document.querySelectorAll('.cust-group').forEach(wireCustGroup);
  document.querySelectorAll('.product-search-input').forEach(setupProductAutocomplete);
  document.querySelectorAll('.price-override-input').forEach(setupPriceOverride);
  refreshCustGroupOrder();
  refreshComboGroupOrder();
  
  // Controle do modo de preço
  setupPriceModeControl();

  // Fechar sugestões quando clica fora (otimizado)
  document.addEventListener('click', (e) => {
    if (e.target.closest('.product-autocomplete-wrapper, .ingredient-typeahead-wrapper')) return;
    document.querySelectorAll('.product-suggestions:not(.hidden), .ingredient-suggestions:not(.hidden)').forEach(div => div.classList.add('hidden'));
  });

  // ===== EXPORTAR FUNÇÕES GLOBAIS =====
  // Para uso pelo modal de copiar grupo de personalização
  window.addCustGroup = addCustGroup;
  window.addCustItem = addCustItem;
  window.wireCustGroup = wireCustGroup;
  window.applyCustMode = applyCustMode;
  
  // Função auxiliar para adicionar item com dados pré-preenchidos
  window.addCustItemWithData = function(groupEl, itemData) {
    const row = addCustItem(groupEl);
    if (!row) return null;
    
    // Preencher dados
    const hiddenInput = row.querySelector('.ingredient-id-hidden');
    const typeaheadInput = row.querySelector('.ingredient-typeahead-input');
    const minQtyInput = row.querySelector('.cust-min-input');
    const maxQtyInput = row.querySelector('.cust-max-input');
    const defaultQtyInput = row.querySelector('.cust-default-qty');
    const defaultFlag = row.querySelector('.cust-default-flag');
    const defaultBtn = row.querySelector('.cust-default-btn');
    
    // Preencher ingredient_id e nome visível
    if (hiddenInput && itemData.ingredient_id) {
      hiddenInput.value = itemData.ingredient_id;
    }
    
    // Preencher o campo de texto com o nome do ingrediente
    if (typeaheadInput) {
      // Usar ingredient_name retornado pela API, ou label, ou string vazia
      const displayName = itemData.ingredient_name || itemData.label || '';
      typeaheadInput.value = displayName;
    }
    
    // Preencher quantidades
    if (minQtyInput) minQtyInput.value = itemData.min_qty ?? 0;
    if (maxQtyInput) maxQtyInput.value = itemData.max_qty ?? 1;
    if (defaultQtyInput) defaultQtyInput.value = itemData.default_qty ?? 0;
    
    // Preencher padrão
    if (defaultFlag) {
      defaultFlag.value = itemData.is_default ? '1' : '0';
    }
    if (defaultBtn && itemData.is_default) {
      defaultBtn.textContent = 'Sim';
      defaultBtn.classList.remove('bg-white', 'border-slate-300', 'text-slate-600');
      defaultBtn.classList.add('admin-primary-bg', 'border-transparent', 'text-white');
    }
    
    return row;
  };

})();