/**
 * Editor de Template de Mensagem de Pedido
 * Gerencia personalização de campos e preview da mensagem
 */

(function() {
  'use strict';

  // Configuração padrão de campos
  const defaultFields = {
    company_name: true,
    order_number: true,
    order_status: true,
    order_date: true,
    customer_name: true,
    customer_phone: false,
    customer_address: false,
    delivery_type: true,
    payment_method: true,
    payment_change: false,
    subtotal: true,
    delivery_fee: false,
    total: true,
    items_list: true,
    item_quantity: true,
    item_price: true,
    item_subtotal: true,
    item_customization: false,
    item_observations: false,
    order_notes: false,
    estimated_time: false,
    system_source: true
  };

  // Dados de exemplo para preview
  const sampleData = {
    company_name: 'WOLLBURGER',
    order_number: '1234',
    order_status: 'NOVO PEDIDO',
    order_date: '16/10/2025 14:30',
    customer_name: 'João Silva',
    customer_phone: '(11) 98765-4321',
    customer_address: 'Rua das Flores, 123 - Centro',
    delivery_type: 'Entrega',
    payment_method: 'Cartão de Crédito',
    payment_change: 'R$ 50,00',
    subtotal: 45.00,
    delivery_fee: 5.00,
    total: 54.50,
    estimated_time: '30-40 minutos',
    order_notes: 'Sem cebola, por favor',
    items: [
      {
        quantity: 1,
        name: 'Woll Smash',
        price: 25.50,
        subtotal: 25.50,
        customization: 'Ingredientes: 6x Bled Costela 90 (carne) (+R$ 37,50), 2x Queijo Cheddar (+R$ 1,00) | Pão Brioche: Incluso | Maionese: Incluso | Cebola: Incluso',
        observations: ''
      },
      {
        quantity: 1,
        name: 'Batata Frita G',
        price: 12.00,
        subtotal: 12.00,
        customization: '',
        observations: ''
      },
      {
        quantity: 2,
        name: 'Coca-Cola 2L',
        price: 8.50,
        subtotal: 17.00,
        customization: '',
        observations: 'Bem gelada'
      }
    ]
  };

  /**
   * Gera preview da mensagem baseado nos campos selecionados
   */
  function generateMessagePreview() {
    const fields = getSelectedFields();
    let message = '';

    // Cabeçalho
    if (fields.company_name) {
      message += `🍔 *${sampleData.company_name}*\n`;
    }
    if (fields.order_status) {
      message += `🔔 *${sampleData.order_status}!*\n`;
    }
    message += '━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n';

    // Informações do Pedido
    if (fields.order_number) {
      message += `📋 *Pedido:* #${sampleData.order_number}\n`;
    }
    if (fields.customer_name) {
      message += `👤 *Cliente:* ${sampleData.customer_name}\n`;
    }
    if (fields.customer_phone) {
      message += `📱 *Telefone:* ${sampleData.customer_phone}\n`;
    }
    if (fields.customer_address) {
      message += `📍 *Endereço:* ${sampleData.customer_address}\n`;
    }
    if (fields.delivery_type) {
      message += `🚗 *Tipo:* ${sampleData.delivery_type}\n`;
    }

    // Pagamento
    if (fields.payment_method) {
      message += `💳 *Pagamento:* ${sampleData.payment_method}\n`;
    }
    if (fields.payment_change && sampleData.payment_change) {
      message += `💵 *Troco para:* ${sampleData.payment_change}\n`;
    }

    // Valores
    if (fields.subtotal) {
      message += `💵 *Subtotal:* R$ ${formatCurrency(sampleData.subtotal)}\n`;
    }
    if (fields.delivery_fee && sampleData.delivery_fee > 0) {
      message += `🚚 *Taxa de Entrega:* R$ ${formatCurrency(sampleData.delivery_fee)}\n`;
    }
    if (fields.total) {
      message += `💰 *Total:* R$ ${formatCurrency(sampleData.total)}\n`;
    }

    message += '\n';

    // Itens do Pedido
    if (fields.items_list && sampleData.items.length > 0) {
      message += '🛒 *ITENS:*\n';

      sampleData.items.forEach(item => {
        if (fields.item_quantity) {
          message += `• ${item.quantity}x ${item.name}\n`;
        } else {
          message += `• ${item.name}\n`;
        }

        if (fields.item_price) {
          message += `  💵 Unit: R$ ${formatCurrency(item.price)}`;
        }
        if (fields.item_subtotal) {
          if (fields.item_price) message += ' | ';
          message += `Total: R$ ${formatCurrency(item.subtotal)}`;
        }
        if (fields.item_price || fields.item_subtotal) {
          message += '\n';
        }

        if (fields.item_customization && item.customization) {
          message += `  ⚙️ ${item.customization}\n`;
        }
        if (fields.item_observations && item.observations) {
          message += `  📝 ${item.observations}\n`;
        }
      });

      message += '\n';
    }

    // Informações Extras
    if (fields.order_notes && sampleData.order_notes) {
      message += `📝 *Observações:* ${sampleData.order_notes}\n`;
    }
    if (fields.estimated_time) {
      message += `⏱️ *Tempo estimado:* ${sampleData.estimated_time}\n`;
    }
    if (fields.order_date) {
      message += `⏰ ${sampleData.order_date}\n`;
    }
    if (fields.system_source) {
      message += '📱 Sistema Automático\n';
    }

    message += '\n✨ *Preparar pedido!* 🚀';

    return message;
  }

  /**
   * Formata valor monetário
   */
  function formatCurrency(value) {
    return value.toFixed(2).replace('.', ',');
  }

  /**
   * Obtém campos selecionados
   */
  function getSelectedFields() {
    const fields = {};
    document.querySelectorAll('.order-field-toggle').forEach(checkbox => {
      fields[checkbox.dataset.field] = checkbox.checked;
    });
    return fields;
  }

  /**
   * Atualiza preview da mensagem
   */
  function updatePreview() {
    const preview = document.getElementById('messagePreview');
    if (preview) {
      preview.textContent = generateMessagePreview();
    }
  }

  /**
   * Configura event listeners
   */
  function setupEventListeners() {
    // Atualizar preview quando campos mudarem
    document.querySelectorAll('.order-field-toggle').forEach(checkbox => {
      checkbox.addEventListener('change', updatePreview);
    });

    // Selecionar todos
    const selectAllBtn = document.getElementById('selectAllFields');
    if (selectAllBtn) {
      selectAllBtn.addEventListener('click', () => {
        document.querySelectorAll('.order-field-toggle').forEach(checkbox => {
          checkbox.checked = true;
        });
        updatePreview();
      });
    }

    // Desmarcar todos
    const deselectAllBtn = document.getElementById('deselectAllFields');
    if (deselectAllBtn) {
      deselectAllBtn.addEventListener('click', () => {
        document.querySelectorAll('.order-field-toggle').forEach(checkbox => {
          checkbox.checked = false;
        });
        updatePreview();
      });
    }

    // Restaurar padrão
    const resetBtn = document.getElementById('resetDefaultFields');
    if (resetBtn) {
      resetBtn.addEventListener('click', () => {
        document.querySelectorAll('.order-field-toggle').forEach(checkbox => {
          const field = checkbox.dataset.field;
          checkbox.checked = defaultFields[field] || false;
        });
        updatePreview();
      });
    }
  }

  /**
   * Salva configuração dos campos
   */
  function saveFieldsConfig() {
    return getSelectedFields();
  }

  /**
   * Carrega configuração dos campos
   */
  function loadFieldsConfig(config) {
    if (!config || !config.message_fields) {
      // Usar configuração padrão
      document.querySelectorAll('.order-field-toggle').forEach(checkbox => {
        const field = checkbox.dataset.field;
        checkbox.checked = defaultFields[field] || false;
      });
    } else {
      // Carregar configuração salva
      document.querySelectorAll('.order-field-toggle').forEach(checkbox => {
        const field = checkbox.dataset.field;
        checkbox.checked = config.message_fields[field] || false;
      });
    }
    updatePreview();
  }

  /**
   * Inicializa o editor
   */
  function init() {
    setupEventListeners();
    updatePreview();
  }

  // Expor funções globalmente
  window.OrderMessageEditor = {
    init: init,
    updatePreview: updatePreview,
    saveFieldsConfig: saveFieldsConfig,
    loadFieldsConfig: loadFieldsConfig,
    getSelectedFields: getSelectedFields
  };

  // Inicializar quando o DOM estiver pronto
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
