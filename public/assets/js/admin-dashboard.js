// admin-dashboard.js — small behaviors for dashboard
(function(){
  'use strict';
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
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initCardLinks); else initCardLinks();
})();
