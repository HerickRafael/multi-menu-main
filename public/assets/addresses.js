(function () {
  'use strict';

  function getPageConfig() {
    var cfg = document.getElementById('page-config');
    if (!cfg) {
      return {};
    }
    try {
      return JSON.parse(cfg.textContent || '{}');
    } catch (e) {
      return {};
    }
  }

  function initAddressList(config) {
    var forms = document.querySelectorAll('.js-confirm-delete-form');
    if (!forms.length) {
      return;
    }

    var message = (config && config.deleteConfirmMessage) ? String(config.deleteConfirmMessage) : 'Excluir este endereco?';

    forms.forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!window.confirm(message)) {
          event.preventDefault();
        }
      });
    });
  }

  function initAddressForm(config) {
    if (!document.getElementById('address-form')) {
      return;
    }

    var data = {
      cities: Array.isArray(config.cities) ? config.cities : [],
      zonesByCity: (config.zonesByCity && typeof config.zonesByCity === 'object') ? config.zonesByCity : {}
    };

    var slug = String(config.slug || '');
    var selectedZoneId = parseInt(config.selectedZoneId || 0, 10) || 0;

    var citySelect = document.getElementById('city-select');
    var zoneSelect = document.getElementById('zone-select');
    var cityNameInput = document.getElementById('city-name');
    var zoneNameInput = document.getElementById('zone-name');

    function syncCityName() {
      if (!citySelect || !cityNameInput) {
        return;
      }
      var opt = citySelect.options[citySelect.selectedIndex];
      cityNameInput.value = opt ? opt.textContent : '';
    }

    function syncZoneName() {
      if (!zoneSelect || !zoneNameInput) {
        return;
      }
      var opt = zoneSelect.options[zoneSelect.selectedIndex];
      zoneNameInput.value = opt ? opt.textContent : '';
    }

    function populateZones(cityId) {
      if (!zoneSelect) {
        return [];
      }

      if (!cityId) {
        zoneSelect.innerHTML = '<option value="">Escolha a cidade primeiro</option>';
        zoneSelect.disabled = true;
        return [];
      }

      var zones = data.zonesByCity[cityId] || data.zonesByCity[String(cityId)] || [];

      zoneSelect.innerHTML = '';

      var placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.textContent = 'Selecione o bairro';
      zoneSelect.appendChild(placeholder);

      if (!zones.length) {
        var noZone = document.createElement('option');
        noZone.value = '';
        noZone.textContent = 'Nenhum bairro disponivel';
        zoneSelect.appendChild(noZone);
        zoneSelect.disabled = true;
        return zones;
      }

      zones.forEach(function (zone) {
        var option = document.createElement('option');
        option.value = zone.id;
        option.textContent = zone.name;
        option.dataset.cityName = zone.city_name || '';
        option.dataset.zoneName = zone.name || '';
        zoneSelect.appendChild(option);
      });

      zoneSelect.disabled = false;
      return zones;
    }

    if (citySelect) {
      citySelect.addEventListener('change', function () {
        var cityId = parseInt(citySelect.value, 10) || 0;
        syncCityName();
        populateZones(cityId);
        if (zoneSelect) {
          zoneSelect.value = '';
        }
        syncZoneName();
      });
    }

    if (zoneSelect) {
      zoneSelect.addEventListener('change', syncZoneName);
    }

    if (citySelect && citySelect.value) {
      var initialCityId = parseInt(citySelect.value, 10) || 0;
      if (initialCityId > 0) {
        populateZones(initialCityId);
        if (selectedZoneId > 0 && zoneSelect) {
          zoneSelect.value = String(selectedZoneId);
          syncZoneName();
        }
      }
    }

    var labelInput = document.getElementById('label-input');
    var presetButtons = document.querySelectorAll('.label-preset[data-label]');

    function updatePresetActiveState(value) {
      presetButtons.forEach(function (button) {
        var label = String(button.dataset.label || '');
        button.classList.toggle('active', label === value);
      });
    }

    function setLabel(label) {
      if (!labelInput) {
        return;
      }
      labelInput.value = label;
      labelInput.focus();
      updatePresetActiveState(label);
    }

    presetButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        setLabel(String(button.dataset.label || ''));
      });
    });

    if (labelInput) {
      updatePresetActiveState(labelInput.value);
      labelInput.addEventListener('input', function () {
        updatePresetActiveState(labelInput.value);
      });
    }

    var phoneInput = document.getElementById('phone-input');
    if (phoneInput) {
      var applyPhoneMask = function (value) {
        var digits = String(value || '').replace(/\D/g, '');
        var limited = digits.substring(0, 11);
        var formatted = '';
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

      phoneInput.addEventListener('input', function (event) {
        event.target.value = applyPhoneMask(event.target.value);
      });
    }

    var numberInput = document.querySelector('input[name="number"]');
    if (numberInput) {
      numberInput.addEventListener('input', function (event) {
        event.target.value = event.target.value.replace(/[^\d]/g, '');
      });
    }

    var streetInput = document.getElementById('addr-street');
    var acList = document.getElementById('addr-street-ac-list');
    if (!streetInput || !acList) {
      return;
    }

    var acTimer = null;
    var acAbort = null;
    var acIndex = -1;
    var selectedFromList = false;

    function getCityName() {
      if (!citySelect) {
        return '';
      }
      var opt = citySelect.options[citySelect.selectedIndex];
      return opt && opt.value ? String(opt.textContent || '').trim() : '';
    }

    function getNeighborhoodName() {
      if (!zoneSelect) {
        return '';
      }
      var opt = zoneSelect.options[zoneSelect.selectedIndex];
      return opt && opt.value ? String(opt.dataset.zoneName || opt.textContent || '').trim() : '';
    }

    function closeList() {
      acList.innerHTML = '';
      acList.classList.remove('active');
      acIndex = -1;
    }

    function trackPopularity(streetId) {
      if (!streetId || streetId <= 0) {
        return;
      }
      fetch('/' + encodeURIComponent(slug) + '/street-autocomplete/popularity', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ street_id: streetId })
      }).catch(function () {});
    }

    function learnStreet(city, neighborhood, street) {
      if (!street || street.length < 10) {
        return;
      }
      fetch('/' + encodeURIComponent(slug) + '/street-autocomplete/learn', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ city: city, neighborhood: neighborhood, street: street })
      }).catch(function () {});
    }

    function fetchStreets(query) {
      var city = getCityName();
      var neighborhood = getNeighborhoodName();

      if (!city || !neighborhood || query.length < 2) {
        closeList();
        return;
      }

      if (acAbort) {
        try {
          acAbort.abort();
        } catch (e) {}
      }
      acAbort = new AbortController();

      acList.innerHTML = '<div class="street-ac-loading">Buscando...</div>';
      acList.classList.add('active');

      var url = '/' + encodeURIComponent(slug) + '/street-autocomplete'
        + '?q=' + encodeURIComponent(query)
        + '&city=' + encodeURIComponent(city)
        + '&neighborhood=' + encodeURIComponent(neighborhood);

      fetch(url, { signal: acAbort.signal, cache: 'no-store' })
        .then(function (response) {
          return response.json();
        })
        .then(function (json) {
          var results = json.results || [];
          acList.innerHTML = '';

          if (!results.length) {
            acList.innerHTML = '<div class="street-ac-loading">Nenhuma rua encontrada</div>';
            acList.classList.add('active');
            return;
          }

          acIndex = -1;
          results.forEach(function (item) {
            var row = document.createElement('div');
            row.className = 'street-ac-item';
            row.textContent = item.street;
            row.addEventListener('mousedown', function (event) {
              event.preventDefault();
              streetInput.value = item.street;
              selectedFromList = true;
              closeList();
              if (item.id) {
                trackPopularity(item.id);
              }
              var numberField = document.querySelector('input[name="number"]');
              if (numberField) {
                numberField.focus();
              }
            });
            acList.appendChild(row);
          });
          acList.classList.add('active');
        })
        .catch(function (error) {
          if (error.name !== 'AbortError') {
            closeList();
          }
        });
    }

    streetInput.addEventListener('input', function () {
      selectedFromList = false;
      var value = String(streetInput.value || '').trim();
      if (value.length < 2) {
        closeList();
        return;
      }
      clearTimeout(acTimer);
      acTimer = setTimeout(function () {
        fetchStreets(value);
      }, 350);
    });

    streetInput.addEventListener('keydown', function (event) {
      var items = acList.querySelectorAll('.street-ac-item');
      if (!items.length) {
        return;
      }

      if (event.key === 'ArrowDown') {
        event.preventDefault();
        acIndex = Math.min(acIndex + 1, items.length - 1);
        items.forEach(function (item, index) {
          item.classList.toggle('active', index === acIndex);
        });
        items[acIndex].scrollIntoView({ block: 'nearest' });
      } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        acIndex = Math.max(acIndex - 1, 0);
        items.forEach(function (item, index) {
          item.classList.toggle('active', index === acIndex);
        });
        items[acIndex].scrollIntoView({ block: 'nearest' });
      } else if (event.key === 'Enter' && acIndex >= 0) {
        event.preventDefault();
        items[acIndex].dispatchEvent(new Event('mousedown'));
      } else if (event.key === 'Escape') {
        closeList();
      }
    });

    streetInput.addEventListener('blur', function () {
      setTimeout(function () {
        closeList();
        if (!selectedFromList) {
          var value = String(streetInput.value || '').trim();
          if (value.length >= 10) {
            learnStreet(getCityName(), getNeighborhoodName(), value);
          }
        }
      }, 200);
    });

    document.addEventListener('click', function (event) {
      if (!streetInput.contains(event.target) && !acList.contains(event.target)) {
        closeList();
      }
    });
  }

  var config = getPageConfig();
  initAddressList(config);
  initAddressForm(config);
})();
