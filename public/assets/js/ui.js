// Enhanced UI
(function(){
  'use strict';
  
  function once(el, key){
    if (!el) return false;
    if (el.dataset[key]) return false;
    el.dataset[key] = '1';
    return true;
  }

  // Enhanced Search with Skeleton
  function initEnhancedSearch(){
    const form = document.querySelector('form[data-search-url]');
    if (!form || !once(form, 'search')) return;
    
    const input = form.querySelector('input[name="q"]');
    const results = document.getElementById('search-results');
    const url = form.dataset.searchUrl;
    
    if (!input || !results || !url) return;
    
    let searchTimeout;
    
    function showSearchSkeleton(){
      results.innerHTML = '<div class="mb-4"><div class="h-6 bg-gray-200 rounded w-48 mb-3 animate-pulse"></div><div class="grid gap-3"><div class="bg-white border rounded-2xl p-4 flex gap-3 animate-pulse"><div class="w-24 h-24 bg-gray-200 rounded-xl"></div><div class="flex-1"><div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div><div class="h-3 bg-gray-200 rounded w-1/2 mb-2"></div></div></div></div></div>';
    }
    
    function doSearch(){
      const term = input.value.trim();
      
      if (term === '') { 
        results.innerHTML = ''; 
        return; 
      }
      
      showSearchSkeleton();
      
      fetch(url + '?q=' + encodeURIComponent(term), { 
        headers: { 'X-Requested-With': 'XMLHttpRequest' } 
      })
      .then(function(res){ return res.text(); })
      .then(function(html){
        setTimeout(function(){
          results.innerHTML = html;
          initLazyLoading();
        }, 300);
      })
      .catch(function(e){
        console.error('Search error:', e);
        results.innerHTML = '<div class="p-4 text-red-600">Erro ao buscar produtos. Tente novamente.</div>';
      });
    }
    
    input.addEventListener('input', function(){
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(doSearch, 400);
    });
  }

  // Modal Functions
  function initModal(id, openSelectors, closeSelectors){
    const modal = document.getElementById(id);
    if (!modal || !once(modal, 'init')) return;
    
    function open(){ modal.classList.remove('hidden'); }
    function close(){ modal.classList.add('hidden'); }
    
    (openSelectors||[]).forEach(function(sel){
      document.querySelectorAll(sel).forEach(function(btn){ 
        btn.addEventListener('click', open); 
      });
    });
    
    (closeSelectors||[]).forEach(function(sel){
      document.querySelectorAll(sel).forEach(function(btn){ 
        btn.addEventListener('click', close); 
      });
    });
    
    modal.addEventListener('click', function(e){ 
      if (e.target===modal) close(); 
    });
    
    return { open: open, close: close };
  }

  function initHoursModal(){
    return initModal('hours-modal', ['#btn-hours', '#btn-hours-ico'], ['#hours-close']);
  }

  function initLoginModal(){
    const modal = document.getElementById('login-modal');
    if (!modal || !once(modal, 'init')) return null;
    
    const redirectInput = modal.querySelector('input[name="redirect_to"]');
    
    // Usar funções globais se disponíveis (definidas no partial login_modal.php)
    function open(){ 
      if (typeof window.openLoginModal === 'function') {
        window.openLoginModal();
      } else {
        if (redirectInput) redirectInput.value = window.location.pathname + window.location.search; 
        modal.classList.remove('hidden'); 
      }
    }
    function close(){ 
      if (typeof window.closeLoginModal === 'function') {
        window.closeLoginModal();
      } else {
        modal.classList.add('hidden'); 
      }
    }
    
    document.querySelectorAll('#btn-open-login').forEach(function(btn){ 
      btn.addEventListener('click', open); 
    });
    
    // Eventos de fechamento já são tratados pelo partial, mas manter fallback
    if (typeof window.closeLoginModal !== 'function') {
      document.querySelectorAll('#login-close').forEach(function(btn){ 
        btn.addEventListener('click', close); 
      });
      
      modal.addEventListener('click', function(e){ 
        if (e.target===modal) close(); 
      });
    }
    
    return { open: open, close: close };
  }

  function initCategoryTabs(){
    const tabs = Array.from(document.querySelectorAll('.category-tab'));
    if (!tabs.length || !once(tabs[0].closest('div') || tabs[0], 'tabs')) return;

    function activate(tab){
      if (!tab) return;
      tabs.forEach(function(t){ t.classList.remove('active'); });
      tab.classList.add('active');
    }
    
    tabs.forEach(function(t){ 
      t.addEventListener('click', function(){ activate(t); }); 
    });

    function onScroll(){
      let chosen = tabs[0];
      const offset = 80;
      tabs.forEach(function(t){
        const id = (t.getAttribute('href')||'').slice(1);
        const anchor = document.getElementById(id);
        const target = anchor && anchor.nextElementSibling || anchor;
        if (target && target.getBoundingClientRect().top - offset <= 0) {
          chosen = t;
        }
      });
      activate(chosen);
    }

    const initial = document.querySelector('.category-tab.active') || tabs[0];
    activate(initial);
    window.addEventListener('scroll', onScroll);
    onScroll();
  }

  // Lazy Loading Images
  function initLazyLoading(){
    const images = document.querySelectorAll('img.lazy-load[data-src]');
    
    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver(function(entries){
        entries.forEach(function(entry){
          if (entry.isIntersecting) {
            const img = entry.target;
            const src = img.dataset.src;
            const fallback = img.dataset.fallback;
            
            if (src) {
              img.src = src;
              img.onerror = function(){
                if (fallback) img.src = fallback;
              };
              img.classList.remove('lazy-load');
              observer.unobserve(img);
            }
          }
        });
      }, { rootMargin: '50px' });
      
      images.forEach(function(img){ observer.observe(img); });
    } else {
      // Fallback: carregar imediatamente
      images.forEach(function(img){
        const src = img.dataset.src;
        const fallback = img.dataset.fallback;
        if (src) {
          img.src = src;
          img.onerror = function(){
            if (fallback) img.src = fallback;
          };
          img.classList.remove('lazy-load');
        }
      });
    }
  }
  
  // Phone mask for login modal
  function initPhoneMask(){
    const phoneInput = document.getElementById('login-whatsapp');
    if (!phoneInput || !once(phoneInput, 'mask')) return;
    
    const applyPhoneMask = (value) => {
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
    
    phoneInput.addEventListener('input', (e) => {
      e.target.value = applyPhoneMask(e.target.value);
    });
    
    phoneInput.addEventListener('keydown', (e) => {
      const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'];
      if (!allowedKeys.includes(e.key) && (e.key < '0' || e.key > '9')) {
        e.preventDefault();
      }
    });
  }
  
  // Interceptar cliques em Sacola e Perfil se não estiver logado
  function initFooterMenuProtection(){
    const btnCart = document.getElementById('btn-cart');
    const btnProfile = document.getElementById('btn-profile');
    
    if (!btnCart && !btnProfile) return;
    
    const isLogged = window.__IS_CUSTOMER || (window.__LOGIN_CONFIG && window.__LOGIN_CONFIG.userLogged) || false;
    
    if (isLogged) return; // Se está logado, deixa funcionar normalmente
    
    // Função para abrir modal de login (usar função global se disponível)
    const openLoginModal = function(){
      if (typeof window.openLoginModal === 'function') {
        window.openLoginModal();
      } else {
        const modal = document.getElementById('login-modal');
        if (modal) {
          modal.classList.remove('hidden');
        }
      }
    };
    
    // Interceptar clique na Sacola
    if (btnCart) {
      btnCart.addEventListener('click', function(e){
        if (!isLogged) {
          e.preventDefault();
          openLoginModal();
        }
      });
    }
    
    // Interceptar clique no Perfil
    if (btnProfile) {
      btnProfile.addEventListener('click', function(e){
        if (!isLogged) {
          e.preventDefault();
          openLoginModal();
        }
      });
    }
  }

  // Initialize everything
  function init(){
    document.body.classList.add('js-loading');
    
    initHoursModal();
    initLoginModal();
    initCategoryTabs();
    initEnhancedSearch();
    initLazyLoading();
    initPhoneMask();
    initFooterMenuProtection();
    
    // Copy functionality
    document.querySelectorAll('[data-action="copy"]').forEach(function(el){
      el.addEventListener('click', function(){
        const target = el.dataset.target;
        const copyEl = target ? document.querySelector(target) : el;
        if (!copyEl) return;
        
        const text = copyEl.innerText || copyEl.value || copyEl.textContent || '';
        
        if (navigator.clipboard) {
          navigator.clipboard.writeText(text);
        } else {
          const tmp = document.createElement('textarea'); 
          tmp.value = text; 
          document.body.appendChild(tmp); 
          tmp.select(); 
          document.execCommand('copy'); 
          tmp.remove();
        }
      });
    });
    
    window.addEventListener('load', function(){
      setTimeout(function(){
        document.body.classList.remove('js-loading');
        document.body.classList.add('js-loaded');
      }, 100);
    });
    
    // Force login if needed
    try {
      const login = initLoginModal();
      if (window.__FORCE_LOGIN && login && typeof login.open === 'function') {
        login.open();
      }
    } catch(e){}
  }

  // Run when ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();