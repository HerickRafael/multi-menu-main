/**
 * admin-csrf.js — CSRF token injection
 *
 * - Lê token do <meta name="csrf-token">
 * - Injeta em formulários POST como <input type="hidden" name="csrf_token">
 * - Intercepta window.fetch para adicionar header X-CSRF-TOKEN em POSTs
 * - Expõe window._csrfToken para admin-common.js (postJson)
 */
(function () {
  var meta = document.querySelector('meta[name="csrf-token"]');
  if (!meta) return;
  var token = meta.getAttribute('content');
  if (!token) return;

  // Disponibiliza para admin-common.js (postJson usa window._csrfToken)
  window._csrfToken = token;

  function injectCsrf() {
    var forms = document.querySelectorAll('form');
    forms.forEach(function (form) {
      var method = (form.getAttribute('method') || '').toUpperCase();
      if (method === 'POST' && !form.querySelector('input[name="csrf_token"]')) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf_token';
        input.value = token;
        form.appendChild(input);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectCsrf);
  } else {
    injectCsrf();
  }

  // Interceptar fetch global para injetar header X-CSRF-TOKEN em POSTs
  if (window.fetch) {
    var origFetch = window.fetch;
    window.fetch = function (url, opts) {
      opts = opts || {};
      if (opts.method && opts.method.toUpperCase() === 'POST') {
        if (opts.headers instanceof Headers) {
          if (!opts.headers.has('X-CSRF-TOKEN')) {
            opts.headers.set('X-CSRF-TOKEN', token);
          }
        } else {
          opts.headers = opts.headers || {};
          if (!opts.headers['X-CSRF-TOKEN']) {
            opts.headers['X-CSRF-TOKEN'] = token;
          }
        }
      }
      return origFetch.call(this, url, opts);
    };
  }
})();
