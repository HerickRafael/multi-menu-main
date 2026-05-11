<?php
/**
 * Toolbar padronizado das sub-páginas de produtos (mobile)
 *
 * Variáveis opcionais:
 *   $toolbarSearch       = true|false  (mostrar campo de busca)
 *   $toolbarSearchId     = 'searchInput' (id do input)
 *   $toolbarPlaceholder  = 'Buscar...'
 *   $toolbarSearchValue  = '' (valor atual do campo)
 *   $toolbarSearchAction = null | '/url' (se definido, usa <form> em vez de JS filter)
 *   $toolbarSearchHidden = [] (campos hidden extras para form search)
 *   $toolbarNewUrl       = '/path'   (null = esconde botão)
 *   $toolbarNewLabel     = 'Novo'
 *   $toolbarNewOnclick   = null | 'fnName()' (se usar onclick em vez de link)
 */
$toolbarSearch       = $toolbarSearch ?? false;
$toolbarSearchId     = $toolbarSearchId ?? 'searchInput';
$toolbarPlaceholder  = $toolbarPlaceholder ?? 'Buscar...';
$toolbarSearchValue  = $toolbarSearchValue ?? '';
$toolbarSearchAction = $toolbarSearchAction ?? null;
$toolbarSearchHidden = $toolbarSearchHidden ?? [];
$toolbarNewUrl       = $toolbarNewUrl ?? null;
$toolbarNewLabel     = $toolbarNewLabel ?? 'Novo';
$toolbarNewOnclick   = $toolbarNewOnclick ?? null;
$hasToolbar = $toolbarSearch || $toolbarNewUrl || $toolbarNewOnclick;
?>
<?php if ($hasToolbar): ?>
<div style="display:flex; gap:10px; align-items:center; margin-bottom:16px;">
    <?php if ($toolbarSearch): ?>
        <?php if ($toolbarSearchAction): ?>
        <form method="get" action="<?= htmlspecialchars($toolbarSearchAction) ?>" style="flex:1; position:relative; display:flex;">
            <?php foreach ($toolbarSearchHidden as $hName => $hVal): ?>
            <input type="hidden" name="<?= htmlspecialchars($hName) ?>" value="<?= htmlspecialchars($hVal) ?>">
            <?php endforeach; ?>
            <div style="flex:1; position:relative;">
                <input type="text" name="q" id="<?= htmlspecialchars($toolbarSearchId) ?>" placeholder="<?= htmlspecialchars($toolbarPlaceholder) ?>"
                       value="<?= htmlspecialchars($toolbarSearchValue) ?>"
                       style="width:100%; padding:10px 14px 10px 38px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; background:#fff; color:#1e293b; box-sizing:border-box;">
                <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; pointer-events:none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                </span>
            </div>
        </form>
        <?php else: ?>
        <div style="flex:1; position:relative;">
            <input type="text" id="<?= htmlspecialchars($toolbarSearchId) ?>" placeholder="<?= htmlspecialchars($toolbarPlaceholder) ?>"
                   value="<?= htmlspecialchars($toolbarSearchValue) ?>"
                   style="width:100%; padding:10px 14px 10px 38px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; background:#fff; color:#1e293b; box-sizing:border-box;">
            <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; pointer-events:none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            </span>
        </div>
        <?php endif; ?>
    <?php else: ?>
    <div style="flex:1;"></div>
    <?php endif; ?>

    <?php if ($toolbarNewUrl || $toolbarNewOnclick): ?>
        <?php if ($toolbarNewOnclick): ?>
        <button type="button" onclick="<?= htmlspecialchars($toolbarNewOnclick) ?>"
                style="display:inline-flex; align-items:center; gap:6px; padding:10px 16px; border:none; border-radius:10px; font-size:13px; font-weight:600; color:#fff; background:var(--primary); cursor:pointer; white-space:nowrap; flex-shrink:0;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <?= htmlspecialchars($toolbarNewLabel) ?>
        </button>
        <?php else: ?>
        <a href="<?= htmlspecialchars($toolbarNewUrl) ?>"
           style="display:inline-flex; align-items:center; gap:6px; padding:10px 16px; border:none; border-radius:10px; font-size:13px; font-weight:600; color:#fff; background:var(--primary); text-decoration:none; white-space:nowrap; flex-shrink:0;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <?= htmlspecialchars($toolbarNewLabel) ?>
        </a>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php endif; ?>
