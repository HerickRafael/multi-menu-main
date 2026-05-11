<?php
/**
 * Alertas padronizados de sucesso/erro (mobile)
 * Variáveis: $success, $error (já disponíveis no escopo da view)
 */
?>
<?php if (!empty($success)): ?>
<div style="background:#d1fae5; color:#065f46; padding:12px 16px; border-radius:12px; margin-bottom:14px; font-size:13px; display:flex; align-items:center; gap:8px;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div style="background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:12px; margin-bottom:14px; font-size:13px; display:flex; align-items:center; gap:8px;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>
