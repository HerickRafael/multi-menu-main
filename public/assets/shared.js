/**
 * shared.js — Comportamentos reutilizados em múltiplas páginas.
 *
 * Carregado via <script defer> antes do JS específico de cada página.
 * Não depende de nenhum outro módulo.
 */

/* global window */

/**
 * Formulários com [data-confirm]: exibe window.confirm() antes de submeter.
 * Cancelar impede o envio. Usado em order, profile e outras páginas com
 * ações destrutivas (cancelar pedido, excluir conta, etc.).
 *
 * Uso no HTML:
 *   <form method="POST" action="..." data-confirm="Deseja cancelar este pedido?">
 */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      var message = form.getAttribute('data-confirm') || 'Tem certeza?';
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });
});
