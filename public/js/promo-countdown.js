/**
 * Promo Countdown Timer
 * 
 * Atualiza elementos com class="promo-countdown" que possuem data-end (Unix timestamp).
 * Exibe "Acaba em Xh Ym" ou "Só hoje!" ou "Últimos minutos!" conforme o tempo restante.
 * Quando o timer expira, esconde o card de promoção ou recarrega a página.
 */
(function() {
  'use strict';

  function updateCountdowns() {
    var els = document.querySelectorAll('.promo-countdown[data-end]');
    if (!els.length) return;

    var now = Math.floor(Date.now() / 1000);

    for (var i = 0; i < els.length; i++) {
      var el = els[i];
      var end = parseInt(el.getAttribute('data-end'), 10);
      if (!end) continue;

      var diff = end - now;
      var textEl = el.querySelector('.cd-text');
      if (!textEl) continue;

      if (diff <= 0) {
        textEl.textContent = 'Promoção encerrada!';
        el.style.opacity = '0.5';
        // Recarregar a página após 3s para atualizar preços
        if (!el.dataset.expired) {
          el.dataset.expired = '1';
          setTimeout(function() { location.reload(); }, 3000);
        }
      } else if (diff < 60) {
        textEl.textContent = 'Últimos segundos!';
      } else if (diff < 3600) {
        var m = Math.floor(diff / 60);
        textEl.textContent = 'Acaba em ' + m + 'min';
      } else if (diff < 86400) {
        var h = Math.floor(diff / 3600);
        var mm = Math.floor((diff % 3600) / 60);
        textEl.textContent = 'Acaba em ' + h + 'h' + (mm > 0 ? ' ' + mm + 'min' : '');
      } else {
        var d = Math.floor(diff / 86400);
        var hh = Math.floor((diff % 86400) / 3600);
        if (d === 1) {
          textEl.textContent = 'Só até amanhã!';
        } else {
          textEl.textContent = 'Acaba em ' + d + 'd ' + hh + 'h';
        }
      }
    }
  }

  // Executar imediatamente e a cada 30s
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      updateCountdowns();
      setInterval(updateCountdowns, 30000);
    });
  } else {
    updateCountdowns();
    setInterval(updateCountdowns, 30000);
  }
})();
