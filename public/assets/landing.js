(function () {
  'use strict';

  var configEl = document.getElementById('landing-page-config');
  var pageConfig = {};
  if (configEl && configEl.textContent) {
    try {
      pageConfig = JSON.parse(configEl.textContent);
    } catch (err) {
      pageConfig = {};
    }
  }

  var roiDefaults = pageConfig.roiDefaults || {};
  var planMultiMenu = parseInt(roiDefaults.planoMultiMenu, 10);
  if (!Number.isFinite(planMultiMenu)) {
    planMultiMenu = 197;
  }

  var navbar = document.getElementById('navbar');
  if (navbar) {
    window.addEventListener('scroll', function () {
      navbar.classList.toggle('scrolled', window.scrollY > 50);
    }, { passive: true });
  }

  var mobileBtn = document.getElementById('mobile-menu-btn');
  var mobileMenu = document.getElementById('mobile-menu');
  if (mobileBtn && mobileMenu) {
    mobileBtn.addEventListener('click', function () {
      mobileMenu.classList.toggle('hidden');
    });
    mobileMenu.querySelectorAll('a').forEach(function (anchor) {
      anchor.addEventListener('click', function () {
        mobileMenu.classList.add('hidden');
      });
    });
  }

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
  document.querySelectorAll('.scroll-reveal').forEach(function (el) {
    observer.observe(el);
  });

  var counterObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        var el = entry.target;
        var target = parseFloat(el.dataset.target);
        var isDecimal = target % 1 !== 0;
        var duration = 2000;
        var start = performance.now();

        function update(now) {
          var elapsed = now - start;
          var progress = Math.min(elapsed / duration, 1);
          var eased = 1 - Math.pow(1 - progress, 3);
          var current = target * eased;
          el.textContent = isDecimal ? current.toFixed(1) : Math.floor(current).toLocaleString('pt-BR');
          if (progress < 1) {
            requestAnimationFrame(update);
          }
        }

        requestAnimationFrame(update);
        counterObserver.unobserve(el);
      }
    });
  }, { threshold: 0.5 });
  document.querySelectorAll('.counter[data-target]').forEach(function (el) {
    counterObserver.observe(el);
  });

  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (event) {
      var target = document.querySelector(this.getAttribute('href'));
      if (target) {
        event.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  document.querySelectorAll('.tab-btn[data-tab]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.tab-btn[data-tab]').forEach(function (item) {
        item.classList.remove('active');
        item.setAttribute('aria-selected', 'false');
      });
      document.querySelectorAll('.tab-content').forEach(function (content) {
        content.classList.remove('active');
      });
      btn.classList.add('active');
      btn.setAttribute('aria-selected', 'true');
      var panel = document.getElementById('tab-' + btn.dataset.tab);
      if (panel) {
        panel.classList.add('active');
      }
    });
  });

  var roiFat = document.getElementById('roi-faturamento');
  var roiTaxa = document.getElementById('roi-taxa');
  if (roiFat && roiTaxa) {
    var fmt = function (value) {
      return 'R$ ' + Math.round(value).toLocaleString('pt-BR');
    };

    var calcROI = function () {
      var faturamento = parseInt(roiFat.value, 10);
      var taxa = parseInt(roiTaxa.value, 10);
      var marketplace = faturamento * taxa / 100;
      var economia = marketplace - planMultiMenu;

      document.getElementById('roi-faturamento-val').textContent = fmt(faturamento);
      document.getElementById('roi-taxa-val').textContent = taxa + '%';
      document.getElementById('roi-marketplace').textContent = fmt(marketplace);
      document.getElementById('roi-multimenu').textContent = fmt(planMultiMenu);
      document.getElementById('roi-economia').textContent = fmt(Math.max(0, economia));
      document.getElementById('roi-anual').textContent = fmt(Math.max(0, economia * 12));

      var maxLabel = document.getElementById('roi-max-label');
      if (maxLabel) {
        maxLabel.classList.toggle('hidden', faturamento < parseInt(roiFat.max, 10));
      }
    };

    roiFat.addEventListener('input', calcROI);
    roiTaxa.addEventListener('input', calcROI);
    calcROI();
  }

  document.querySelectorAll('details[role="group"]').forEach(function (details) {
    details.addEventListener('toggle', function () {
      var summary = details.querySelector('summary');
      if (summary) {
        summary.setAttribute('aria-expanded', details.open ? 'true' : 'false');
      }
    });
  });
})();
