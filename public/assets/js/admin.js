// Centralized admin JavaScript for settings, ingredients and shared behaviors
(function(){
  'use strict';

  function digits(s){ return (s||'').replace(/\D+/g, ''); }
  function clamp(n,a,b){ return Math.max(a, Math.min(b, n)); }

  // ====== Global phone mask for all tel inputs ======
  function formatPhoneBR(value) {
    let d = digits(value);
    // Remove country code 55 if present
    if (d.startsWith('55') && d.length > 11) d = d.slice(2);
    d = d.slice(0, 11); // Max 11 digits for Brazilian mobile
    
    const ddd = d.slice(0, 2);
    const rest = d.slice(2);
    
    if (rest.length >= 9) return `(${ddd}) ${rest.slice(0,5)}-${rest.slice(5)}`;
    if (rest.length >= 8) return `(${ddd}) ${rest.slice(0,4)}-${rest.slice(4)}`;
    if (rest.length > 0) return `(${ddd}) ${rest}`;
    if (d.length >= 2) return `(${ddd}) `;
    if (d.length > 0) return `(${d}`;
    return '';
  }

  function initPhoneMasks() {
    document.querySelectorAll('input[type="tel"]').forEach(input => {
      if (input.dataset.phoneMaskInit) return; // Already initialized
      input.dataset.phoneMaskInit = 'true';
      
      // Apply mask on load if has value
      if (input.value) {
        input.value = formatPhoneBR(input.value);
      }
      
      input.addEventListener('input', function(e) {
        const cursorPos = e.target.selectionStart;
        const oldLength = e.target.value.length;
        e.target.value = formatPhoneBR(e.target.value);
        const newLength = e.target.value.length;
        // Adjust cursor position
        const newPos = Math.max(0, cursorPos + (newLength - oldLength));
        e.target.setSelectionRange(newPos, newPos);
      });
      
      input.addEventListener('keydown', function(e) {
        const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'];
        if (e.ctrlKey || e.metaKey) return; // Allow copy/paste
        if (!allowedKeys.includes(e.key) && (e.key < '0' || e.key > '9')) {
          e.preventDefault();
        }
      });
    });
  }

  // ====== WhatsApp mask + normalization for settings form ======
  function initWhats(){
    const inputWhats = document.getElementById('whats');
    if (!inputWhats) return;
    function toPretty(d){
      if (d.startsWith('55')) d = d.slice(2);
      d = d.slice(0, 13);
      const ddd = d.slice(0,2), rest = d.slice(2);
      if (rest.length >= 9) return `(${ddd}) ${rest.slice(0,5)}-${rest.slice(5)}`;
      if (rest.length >= 8) return `(${ddd}) ${rest.slice(0,4)}-${rest.slice(4)}`;
      if (rest.length > 0)  return `(${ddd}) ${rest}`;
      if (d.length >= 2)    return `(${ddd}) `;
      return d;
    }
    function onInput(){ let d = digits(inputWhats.value); inputWhats.value = toPretty(d); }
    function beforeSubmit(){
      let d = digits(inputWhats.value).slice(0,15);
      if (d.length <= 11 && !d.startsWith('55')) d = '55' + d;
      inputWhats.value = d;
    }
    inputWhats.addEventListener('input', onInput);
    onInput();
    const form = document.getElementById('settingsForm');
    if (form) form.addEventListener('submit', beforeSubmit);
  }

  // ====== Color text <-> color input linking ======
  function initColorSync(){
    document.querySelectorAll('input[data-color-for]').forEach((txt)=>{
      const key = txt.getAttribute('data-color-for');
      const color = document.querySelector(`input[type="color"][name="${key}"]`);
      if (!color) return;
      function norm(v){
        v = (v||'').trim().toUpperCase();
        if (!v) return '#000000';
        if (v[0] !== '#') v = '#'+v;
        if (!/^#([0-9A-F]{3}|[0-9A-F]{6})$/.test(v)) return color.value;
        if (v.length === 4) v = '#'+v[1]+v[1]+v[2]+v[2]+v[3]+v[3];
        return v;
      }
      txt.addEventListener('change', ()=>{ color.value = norm(txt.value); txt.value = color.value; });
      color.addEventListener('input', ()=>{ txt.value = color.value.toUpperCase(); });
    });
  }

  // ====== Image preview helper ======
  function initImagePreviews(){
    function validImage(file){ return /image\/(jpeg|png|webp)/.test(file.type) && file.size <= 5*1024*1024; }
    function previewFile(input, img){
      const f = input.files && input.files[0];
      if (!f) return;
      if (!validImage(f)) { alert('Formato inválido ou arquivo muito grande. Use JPG/PNG/WEBP até 5MB.'); input.value = ''; return; }
      const reader = new FileReader();
      reader.onload = e => { img.src = e.target.result; };
      reader.readAsDataURL(f);
    }

    const pairs = [
      ['logo-input','logo-preview'],
      ['banner-input','banner-preview'],
      ['image','image-preview']
    ];
    pairs.forEach(([inpId, imgId])=>{
      const input = document.getElementById(inpId);
      const img = document.getElementById(imgId);
      if (input && img){
        input.addEventListener('change', ()=> previewFile(input, img));
      }
    });
  }

  // ====== Toggle day / slot2 behavior and time input formatting ======
  function initToggleDays(){
    document.querySelectorAll('.toggle-day').forEach(chk=>{
      const day = chk.dataset.day;
      function toggle(){
        const enabled = chk.checked;
        document.querySelectorAll('[data-day="'+day+'"].time-input').forEach(i=>{
          i.disabled = !enabled; i.classList.toggle('bg-gray-100', !enabled);
        });
        document.querySelectorAll('.slot2[data-day="'+day+'"] input').forEach(i=>{ i.disabled = !enabled; });
      }
      chk.addEventListener('change', toggle); toggle();
    });

    document.querySelectorAll('.btn-slot2').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const day = btn.dataset.day;
        document.querySelectorAll('.slot2[data-day="'+day+'"]').forEach(el=>{
          el.style.display = (el.style.display==='none' || !el.style.display) ? 'block' : 'none';
        });
      });
    });

    // time formatting
    document.querySelectorAll('.time-input').forEach(inp=>{
      inp.addEventListener('input', ()=>{
        let v = (inp.value || '').replace(/\D+/g, '').slice(0,4);
        if (v.length >= 3) {
          let h = clamp(parseInt(v.slice(0,2)||'0',10), 0, 23).toString().padStart(2,'0');
          let m = clamp(parseInt(v.slice(2)||'0',10), 0, 59).toString().padStart(2,'0');
          inp.value = `${h}:${m}`;
        } else {
          inp.value = v;
        }
      });
    });
  }

  // ====== Money input formatting ======
  function initMoneyInputs(){
    function toMoneyBR(raw){
      let s = String(raw || '').replace(/\D+/g,'');
      if (!s) return '';
      if (s.length === 1) s = '0' + s;
      s = s.replace(/^0+(\d)/, '$1');
      const int = s.slice(0, -2) || '0';
      const dec = s.slice(-2);
      const intFmt = int.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      return intFmt + ',' + dec;
    }
    document.querySelectorAll('.money-input').forEach(inp=>{
      inp.addEventListener('input', ()=>{ const d = inp.value.replace(/\D+/g,''); inp.value = toMoneyBR(d); });
      inp.addEventListener('focus', ()=> inp.select());
    });
    
    // Decimal input - permite formato brasileiro com vírgula (ex: 1,5 ou 1,000)
    document.querySelectorAll('.decimal-input').forEach(inp=>{
      inp.addEventListener('input', (e)=>{
        let v = inp.value;
        // Permitir apenas números, vírgula e ponto
        v = v.replace(/[^\d.,]/g, '');
        // Substituir ponto por vírgula (formato brasileiro)
        v = v.replace(/\./g, ',');
        // Permitir apenas uma vírgula
        const parts = v.split(',');
        if (parts.length > 2) {
          v = parts[0] + ',' + parts.slice(1).join('');
        }
        inp.value = v;
      });
      inp.addEventListener('focus', ()=> inp.select());
    });
  }

  // ====== Unit select + custom unit behavior (ingredients form) ======
  function initUnitSelect(){
    const form = document.getElementById('ingredientForm');
    if (!form) return;
    const select = document.getElementById('unit_select');
    const custom = document.getElementById('unit_custom');
    const labelEl = document.getElementById('unit_label');
    const valueInput = document.getElementById('unit_value');
    if (!select || !labelEl || !valueInput) return;

    let labelMap = {};
    try { labelMap = JSON.parse(form.dataset.unitLabelMap || '{}'); } catch (e) { labelMap = {}; }

    function resolveLabel(){
      const sel = select?.value || '';
      if (sel === 'custom') {
        const customVal = (custom?.value || '').trim();
        return customVal !== '' ? customVal : 'unidade';
      }
      if (sel && Object.prototype.hasOwnProperty.call(labelMap, sel)) return labelMap[sel] || sel;
      return sel !== '' ? sel : 'unidade';
    }
    function syncUnit(){
      const isCustom = (select?.value === 'custom');
      if (custom) {
        custom.classList.toggle('hidden', !isCustom);
        isCustom ? custom.setAttribute('required','required') : custom.removeAttribute('required');
      }
      const u = resolveLabel();
      if (labelEl) labelEl.textContent = u;
      if (valueInput) valueInput.setAttribute('placeholder', ('Ex.: 1 ' + u).trim());
    }
    select.addEventListener('change', syncUnit);
    custom && custom.addEventListener('input', syncUnit);
    syncUnit();
  }

  function initCardLinks(){
    try{
      document.querySelectorAll('.card-link').forEach(function(card){
        const href = card.getAttribute('data-href');
        if(!href) return;
        card.addEventListener('click', function(e){
          const a = e.target.closest('a');
          if(a) return;
          window.location.href = href;
        });
        card.addEventListener('keydown', function(e){
          if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); window.location.href = href; }
        });
      });
    }catch(e){/* ignore */}
  }

  function initDeliverySearch(){
    try{
      // cities
      var cityForm  = document.querySelector('[data-js="city-search-form"]');
      var cityInput = document.querySelector('[data-js="city-search-input"]');
      var cityList  = document.querySelector('[data-js="city-list"]');
      var cityItems = cityList ? Array.prototype.slice.call(cityList.querySelectorAll('[data-js="city-item"]')) : [];
      var cityEmpty = document.querySelector('[data-js="city-empty"]');
      function filterCities(){ if(!cityList) return; var term=(cityInput&&cityInput.value?cityInput.value:'').toLowerCase().trim(); var visible=0; cityItems.forEach(function(item){ var haystack=(item.dataset&&item.dataset.cityName?item.dataset.cityName:'').toLowerCase(); var match = term==='' || haystack.indexOf(term)!==-1; item.style.display = match ? '' : 'none'; if(match) visible++; }); if(cityList) cityList.style.display = visible===0 ? 'none' : ''; if(cityEmpty) cityEmpty.classList.toggle('hidden', visible !== 0); }
      if(cityForm && cityInput && cityList){ cityForm.addEventListener('submit', function(ev){ ev.preventDefault(); filterCities(); }); cityInput.addEventListener('input', filterCities); filterCities(); }

      // zones
      var zoneForm  = document.querySelector('[data-js="zone-search-form"]');
      var zoneInput = document.querySelector('[data-js="zone-search-input"]');
      var zoneBody  = document.querySelector('[data-js="zone-body"]');
      var zoneRows  = zoneBody ? Array.prototype.slice.call(zoneBody.querySelectorAll('[data-js="zone-row"]')) : [];
      var zoneEmpty = document.querySelector('[data-js="zone-empty"]');
      function filterZones(){ if(!zoneBody) return; var term=(zoneInput&&zoneInput.value?zoneInput.value:'').toLowerCase().trim(); var visible=0; zoneRows.forEach(function(row){ var haystack=(row.dataset&&row.dataset.zoneSearch?row.dataset.zoneSearch:'').toLowerCase(); var match = term==='' || haystack.indexOf(term)!==-1; row.style.display = match ? '' : 'none'; if(match) visible++; }); if(zoneEmpty) zoneEmpty.classList.toggle('hidden', visible !== 0); }
      if(zoneForm && zoneInput && zoneBody){ zoneForm.addEventListener('submit', function(ev){ ev.preventDefault(); filterZones(); }); zoneInput.addEventListener('input', filterZones); filterZones(); }
    }catch(e){/* ignore */}
  }

  function initOrderForm(){
    try{
      const itemsBox = document.getElementById('items');
      const tpl = document.getElementById('tpl-row') ? document.getElementById('tpl-row').content : null;
      const form = document.getElementById('order-form');
      if (!itemsBox || !tpl || !form) return;

      function formatBR(v){ return 'R$ ' + (Number(v)||0).toFixed(2).replace('.', ','); }
      function getNumber(input){ const n = parseFloat(input?.value?.replace(',', '.') || '0'); return isFinite(n) ? n : 0; }

      function recalc(){
        let subtotal = 0;
        itemsBox.querySelectorAll('.product-select').forEach((sel, i) => {
          const opt = sel.options[sel.selectedIndex];
          const price = parseFloat(opt?.dataset?.price || '0');
          const qtyInput = itemsBox.querySelectorAll('.qty-input')[i];
          const q = Math.max(0, parseInt(qtyInput.value || '0', 10));
          subtotal += price * q;
        });
        const fee  = getNumber(document.querySelector('.fee-input'));
        const disc = getNumber(document.querySelector('.disc-input'));
        const total = Math.max(0, subtotal + fee - disc);
        const subtEl = document.getElementById('subtot-view');
        const totalEl = document.getElementById('total-view');
        if (subtEl) subtEl.textContent = formatBR(subtotal);
        if (totalEl) totalEl.textContent = formatBR(total);
      }

      function addRow(){
        const node = document.importNode(tpl, true);
        const row  = node.querySelector('div');
        const select = row.querySelector('.product-select');
        const qty    = row.querySelector('.qty-input');
        const show   = row.querySelector('.price-show');
        const btnDel = row.querySelector('.btn-del');
        function updateLine(){
          const opt = select.options[select.selectedIndex];
          const price = parseFloat(opt?.dataset?.price || '0');
          const q = Math.max(1, parseInt(qty.value || '1', 10));
          qty.value = q;
          if (show) show.value = formatBR(price * q);
          recalc();
        }
        select.addEventListener('change', updateLine);
        qty.addEventListener('input', updateLine);
        btnDel && btnDel.addEventListener('click', ()=>{ row.remove(); recalc(); });
        itemsBox.appendChild(row);
        updateLine();
      }

      const btnAdd = document.getElementById('btn-add-item');
      if (btnAdd) btnAdd.addEventListener('click', addRow);
      document.querySelectorAll('.fee-input, .disc-input').forEach(inp=>{ inp.addEventListener('input', recalc); });
      form.addEventListener('submit', (e)=>{
        const hasItem = Array.from(itemsBox.querySelectorAll('.product-select')).some(sel => sel.value && sel.value !== '');
        if (!hasItem) { e.preventDefault(); alert('Adicione pelo menos 1 item ao pedido.'); return false; }
      });
      addRow();
    }catch(e){/* ignore */}
  }

  function init(){
    initPhoneMasks();
    initWhats();
    initColorSync();
    initImagePreviews();
    initToggleDays();
    initMoneyInputs();
    initUnitSelect();
    initCardLinks();
    initDeliverySearch();
    initOrderForm();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();

})();