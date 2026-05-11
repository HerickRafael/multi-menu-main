(function () {
  'use strict';

  var configEl = document.getElementById('home-page-config');
  var config = {};
  if (configEl && configEl.textContent) {
    try {
      config = JSON.parse(configEl.textContent);
    } catch (err) {
      config = {};
    }
  }

  var colors = (config && config.colors) || {};
  var root = document.querySelector('.menu-root') || document.documentElement;

  var varsMap = {
    '--menu-header-text': colors.headerTextColor,
    '--menu-header-button': colors.headerButtonColor,
    '--menu-header-bg': colors.headerBgColor,
    '--menu-logo-border': colors.logoBorderColor,
    '--menu-group-bg': colors.groupBgColor,
    '--menu-group-text': colors.groupTextColor,
    '--menu-welcome-bg': colors.welcomeBgColor,
    '--menu-welcome-text': colors.welcomeTextColor
  };

  Object.keys(varsMap).forEach(function (key) {
    if (varsMap[key]) {
      root.style.setProperty(key, varsMap[key]);
    }
  });

  document.querySelectorAll('[data-logo-img]').forEach(function (img) {
    img.addEventListener('error', function () {
      img.style.display = 'none';
      var fallback = img.parentElement ? img.parentElement.querySelector('[data-logo-fallback]') : null;
      if (fallback) {
        fallback.style.display = 'flex';
      }
    });
  });

  document.querySelectorAll('.js-logout-form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      var msg = form.getAttribute('data-confirm-message') || 'Sair?';
      if (!window.confirm(msg)) {
        event.preventDefault();
      }
    });
  });
})();
