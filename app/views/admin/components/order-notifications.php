<?php
/**
 * order-notifications.php — Componente de notificações de pedidos
 *
 * Inclui: container de toasts + tema visual para os scripts de notificação.
 * URLs e config de polling são fornecidos via window.APP_CONFIG (emitido pelo layout).
 *
 * Variáveis esperadas do escopo pai:
 *   $adminPrimaryColor    string
 *   $adminPrimaryGradient string
 */
?>
<div class="admin-order-toasts" id="admin-order-toasts" aria-live="polite"></div>

<script>
window.ADMIN_THEME = {
  primaryColor:    '<?= e($adminPrimaryColor) ?>',
  primaryGradient: '<?= e($adminPrimaryGradient) ?>'
};
</script>
