(() => {
  const configElement = document.getElementById('order-processing-config');
  const config = configElement ? JSON.parse(configElement.textContent || '{}') : {};

  const DEBUG = false;
  const log = (...args) => DEBUG && console.log(...args);
  const warn = (...args) => DEBUG && console.warn(...args);
  const err = (...args) => DEBUG && console.error(...args);

  const companyWhatsApp = config.companyWhatsApp || '';
  const companyName = config.companyName || 'Restaurante';
  const confirmUrl = config.confirmUrl || '#';
  const checkoutUrl = config.checkoutUrl || '#';
  const successUrl = config.successUrl || checkoutUrl;
  const csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';

  let orderData = null;
  let orderSummary = null;

  function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
    return null;
  }

  function deleteCookie(name) {
    document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
  }

  function getStoredData(key) {
    try { const value = sessionStorage.getItem(key); if (value) return value; } catch (error) { warn('sessionStorage indisponível:', error); }
    const cookieValue = getCookie(key); if (cookieValue) return cookieValue;
    try { const value = localStorage.getItem(key); if (value) return value; } catch (error) { warn('localStorage indisponível:', error); }
    return null;
  }

  function clearStoredData() {
    try { sessionStorage.removeItem('checkoutFormData'); sessionStorage.removeItem('orderSummary'); } catch (error) {}
    deleteCookie('checkoutFormData');
    deleteCookie('orderSummary');
    try { localStorage.removeItem('checkoutFormData'); localStorage.removeItem('orderSummary'); } catch (error) {}
  }

  const animationDone = new Promise((resolve) => setTimeout(resolve, 3500));
  const checkoutData = getStoredData('checkoutFormData');
  const summaryData = getStoredData('orderSummary');

  log('checkoutData presente?', checkoutData ? 'SIM' : 'NÃO');
  log('summaryData presente?', summaryData ? 'SIM' : 'NÃO');

  let fetchPromise;

  if (checkoutData) {
    try {
      const parsed = JSON.parse(checkoutData);
      orderData = parsed.data;

      if (summaryData) {
        try { orderSummary = JSON.parse(summaryData); } catch (error) { warn('Erro ao parsear orderSummary:', error); }
      }

      fetchPromise = fetch(confirmUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-CSRF-Token': csrfToken },
      })
        .then((response) => {
          if (!response.ok) throw new Error('HTTP ' + response.status);
          return response.json();
        })
        .then((json) => {
          if (!json.success) throw new Error(json.message || 'Confirmação falhou');
          log('Servidor confirmou, order_id:', json.order_id);
        })
        .catch((error) => {
          err('Erro na confirmação (não bloqueia):', error);
        });
    } catch (error) {
      err('Erro ao parsear dados locais:', error);
      clearStoredData();
      window.location.href = checkoutUrl;
      fetchPromise = new Promise(() => {});
    }
  } else {
    warn('Nenhum dado de pedido encontrado no storage');
    clearStoredData();
    window.location.href = checkoutUrl + '?erro=sem_dados';
    fetchPromise = new Promise(() => {});
  }

  function buildWhatsAppMessage(data, summary) {
    if (!data) {
      warn('data é null — tentando recuperar...');
      const raw = getStoredData('checkoutFormData');
      if (raw) {
        try { data = JSON.parse(raw).data; } catch (error) {}
      }
      if (!data) return '';
    }

    const name = data['address[name]'] || '';
    const street = data['address[street]'] || '';
    const number = data['address[number]'] || '';
    const complement = data['address[complement]'] || '';
    const neighborhood = data['address[neighborhood]'] || '';
    const city = data['address[city]'] || '';
    const notes = data['order[notes]'] || '';

    let message = `*Olá ${companyName}!* eu sou ${name}\n`;
    message += `Acabei de fazer um pedido pelo cardápio online.\n\n`;
    message += `*ENDEREÇO DE ENTREGA*\n`;
    message += `${street}, ${number}`;
    if (complement) message += ` - ${complement}`;
    message += `\n`;
    if (neighborhood) message += `Bairro: ${neighborhood}\n`;
    if (city) message += `Cidade: ${city}\n`;
    message += `•  - - - - - - - - - - - - - -\n\n`;

    if (summary && summary.items && summary.items.length > 0) {
      message += `*ITENS*\n\n`;

      summary.items.forEach((item) => {
        const spacing = Math.max(1, 45 - item.product.length - item.price.length);
        message += `${item.product}${' '.repeat(spacing)}${item.price}\n`;

        if (item.details && item.details.length > 0) {
          item.details.forEach((detail) => {
            const detailPrice = detail.price && detail.price.trim() !== '' ? detail.price : '';
            if (detailPrice) {
              const detailSpacing = Math.max(1, 43 - detail.name.length - detailPrice.length);
              message += `  ${detail.name}${' '.repeat(detailSpacing)}${detailPrice}\n`;
            } else {
              message += `  ${detail.name}\n`;
            }
          });
        }
      });

      message += `•  - - - - - - - - - - - - - -\n\n`;

      if (summary.subtotal) {
        const subLabel = 'Subtotal:';
        message += `${subLabel}${' '.repeat(Math.max(1, 58 - subLabel.length - summary.subtotal.length))}${summary.subtotal}\n`;
      }
      const deliveryText = summary.deliveryOriginal || summary.delivery;
      if (deliveryText) {
        const delLabel = 'Taxa Entrega:';
        message += `${delLabel}${' '.repeat(Math.max(1, 58 - delLabel.length - deliveryText.length))}${deliveryText}\n`;
      }
      const loyaltyText = summary.loyaltyDiscountTotal || summary.loyaltyDiscount;
      if (loyaltyText) {
        const discLabel = 'Desconto Fidelidade:';
        message += `${discLabel}${' '.repeat(Math.max(1, 58 - discLabel.length - loyaltyText.length))}${loyaltyText}\n`;
      }
      message += `\n`;
      if (summary.total) {
        const totalLabel = '*TOTAL:';
        const totalValue = summary.total + '*';
        message += `${totalLabel}${' '.repeat(Math.max(1, 58 - totalLabel.length - totalValue.length))}${totalValue}\n`;
      }
      message += `\n•  - - - - - - - - - - - - - -\n\n`;
    }

    if (summary && summary.paymentMethod) {
      if (summary.paymentType === 'pix') {
        message += `*PAGAMENTO*\nPix - mandar o comprovante após pagamento\n\n`;
      } else {
        message += `*PAGAMENTO*\n${summary.paymentMethod}\n\n`;
      }
    }

    if (notes) message += `*OBSERVAÇÕES*\n${notes}\n\n`;

    message += `_Aguardando confirmação do pedido._`;
    return message;
  }

  const steps = [
    { id: 'step-1', delay: 500 },
    { id: 'step-2', delay: 1500 },
    { id: 'step-3', delay: 2500 },
  ];

  steps.forEach(({ id, delay }) => {
    setTimeout(() => {
      const step = document.getElementById(id);
      if (step) {
        step.classList.add('completed');
        step.classList.remove('active');
      }

      const currentIndex = steps.findIndex((stepData) => stepData.id === id);
      const nextStepData = steps[currentIndex + 1];
      const nextStep = nextStepData ? document.getElementById(nextStepData.id) : null;
      if (nextStep) nextStep.classList.add('active');
    }, delay);
  });

  Promise.all([animationDone, fetchPromise]).then(() => {
    const checkmark = document.getElementById('checkmark');
    const title = document.getElementById('title');
    const subtitle = document.getElementById('subtitle');

    if (checkmark) checkmark.classList.add('show');
    if (title) title.textContent = 'Pedido confirmado!';
    if (subtitle) subtitle.textContent = 'Abrindo WhatsApp...';

    setTimeout(() => {
      if (!orderData) {
        const raw = getStoredData('checkoutFormData');
        if (raw) {
          try { orderData = JSON.parse(raw).data; } catch (error) {}
        }
      }
      if (!orderSummary) {
        const raw = getStoredData('orderSummary');
        if (raw) {
          try { orderSummary = JSON.parse(raw); } catch (error) {}
        }
      }

      if (companyWhatsApp) {
        const message = buildWhatsAppMessage(orderData, orderSummary);
        if (!message) {
          clearStoredData();
          window.location.href = checkoutUrl + '?erro=sem_dados';
          return;
        }
        const whatsappUrl = `https://wa.me/${companyWhatsApp}?text=${encodeURIComponent(message)}`;
        clearStoredData();
        window.location.href = whatsappUrl;
      } else {
        clearStoredData();
        window.location.href = successUrl;
      }
    }, 1000);
  });
})();
