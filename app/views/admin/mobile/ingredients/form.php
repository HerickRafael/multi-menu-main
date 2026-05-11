<?php
    $isEdit = !empty($ingredient);
    $action = $isEdit ? "/ingredients/{$ingredient['id']}" : '/ingredients';
?>

<?php if ($error): ?>
<div style="background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:12px; margin-bottom:14px; font-size:13px; display:flex; align-items:center; gap:8px;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<form method="POST" action="<?= $action ?>" enctype="multipart/form-data"
      style="display:flex; flex-direction:column; gap:16px;">

    <!-- Imagem -->
    <div style="background:var(--card-bg,#fff); border-radius:14px; padding:16px; box-shadow:0 1px 4px rgba(0,0,0,.06);">
        <label style="font-size:13px; font-weight:600; color:var(--text-primary,#1e293b); margin-bottom:8px; display:flex; align-items:center; gap:6px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg> Imagem <a href="/guide/ingredients#form" class="section-help-btn" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;">?</a></label>
        <?php if ($isEdit && !empty($ingredient['image_path'])): ?>
        <div style="margin-bottom:10px; text-align:center;">
            <img src="/<?= htmlspecialchars($ingredient['image_path']) ?>" alt=""
                 style="width:80px; height:80px; border-radius:12px; object-fit:cover;">
        </div>
        <?php endif; ?>
        <input type="file" name="image" accept="image/*"
               style="width:100%; font-size:13px; color:#64748b; box-sizing:border-box;">
    </div>

    <!-- Nome -->
    <div style="background:var(--card-bg,#fff); border-radius:14px; padding:16px; box-shadow:0 1px 4px rgba(0,0,0,.06);">
        <label style="font-size:13px; font-weight:600; color:var(--text-primary,#1e293b); margin-bottom:8px; display:flex; align-items:center; gap:6px;">Nome * <a href="/guide/ingredients#form" class="section-help-btn" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;">?</a></label>
        <input type="text" name="name" required placeholder="Ex: Queijo Mussarela"
               value="<?= htmlspecialchars($isEdit ? ($ingredient['name'] ?? '') : ($_POST['name'] ?? '')) ?>"
               style="width:100%; padding:12px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; background:var(--card-bg,#fff); color:var(--text-primary,#1e293b); box-sizing:border-box;">
    </div>

    <!-- Nome Interno -->
    <div style="background:var(--card-bg,#fff); border-radius:14px; padding:16px; box-shadow:0 1px 4px rgba(0,0,0,.06);">
        <label style="font-size:13px; font-weight:600; color:var(--text-primary,#1e293b); margin-bottom:8px; display:flex; align-items:center; gap:6px;">Nome Interno <a href="/guide/ingredients#form" class="section-help-btn" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;">?</a></label>
        <input type="text" name="internal_name" placeholder="Nome para uso interno (opcional)"
               value="<?= htmlspecialchars($isEdit ? ($ingredient['internal_name'] ?? '') : ($_POST['internal_name'] ?? '')) ?>"
               style="width:100%; padding:12px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; background:var(--card-bg,#fff); color:var(--text-primary,#1e293b); box-sizing:border-box;">
        <div style="font-size:11px; color:#94a3b8; margin-top:4px;">Usado apenas internamente</div>
    </div>

    <!-- Custo + Venda -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div style="background:var(--card-bg,#fff); border-radius:14px; padding:16px; box-shadow:0 1px 4px rgba(0,0,0,.06);">
            <label style="font-size:13px; font-weight:600; color:var(--text-primary,#1e293b); margin-bottom:8px; display:flex; align-items:center; gap:6px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg> Custo <a href="/guide/ingredients#pricing" class="section-help-btn" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;">?</a></label>
            <div style="position:relative;">
                <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:13px; color:#94a3b8;">R$</span>
                <input type="text" name="cost" required placeholder="0,00" inputmode="decimal"
                       value="<?= $isEdit ? number_format((float)($ingredient['cost'] ?? 0), 2, ',', '.') : htmlspecialchars($_POST['cost'] ?? '') ?>"
                       style="width:100%; padding:12px 14px 12px 36px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; background:var(--card-bg,#fff); color:var(--text-primary,#1e293b); box-sizing:border-box;">
            </div>
        </div>
        <div style="background:var(--card-bg,#fff); border-radius:14px; padding:16px; box-shadow:0 1px 4px rgba(0,0,0,.06);">
            <label style="font-size:13px; font-weight:600; color:var(--text-primary,#1e293b); margin-bottom:8px; display:flex; align-items:center; gap:6px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg> Venda</label>
            <div style="position:relative;">
                <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:13px; color:#94a3b8;">R$</span>
                <input type="text" name="sale_price" required placeholder="0,00" inputmode="decimal"
                       value="<?= $isEdit ? number_format((float)($ingredient['sale_price'] ?? 0), 2, ',', '.') : htmlspecialchars($_POST['sale_price'] ?? '') ?>"
                       style="width:100%; padding:12px 14px 12px 36px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; background:var(--card-bg,#fff); color:var(--text-primary,#1e293b); box-sizing:border-box;">
            </div>
        </div>
    </div>

    <!-- Unidade + Valor -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div style="background:var(--card-bg,#fff); border-radius:14px; padding:16px; box-shadow:0 1px 4px rgba(0,0,0,.06);">
            <label style="font-size:13px; font-weight:600; color:var(--text-primary,#1e293b); margin-bottom:8px; display:flex; align-items:center; gap:6px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg> Unidade <a href="/guide/ingredients#units" class="section-help-btn" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;">?</a></label>
            <select name="unit" id="unitSelect" required
                    onchange="document.getElementById('customUnit').style.display = this.value==='custom' ? 'block' : 'none'"
                    style="width:100%; padding:12px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; background:var(--card-bg,#fff); color:var(--text-primary,#1e293b); box-sizing:border-box; -webkit-appearance:none;">
                <?php
                    $units = ['g' => 'Gramas (g)', 'kg' => 'Quilos (kg)', 'ml' => 'Mililitros (ml)', 'l' => 'Litros (l)', 'un' => 'Unidade (un)', 'fatia' => 'Fatia', 'colher' => 'Colher', 'custom' => 'Personalizada...'];
                    $currentUnit = $isEdit ? ($ingredient['unit'] ?? '') : ($_POST['unit'] ?? '');
                    $isCustom = !empty($currentUnit) && !array_key_exists($currentUnit, $units);
                    foreach ($units as $val => $label):
                        $sel = ($currentUnit === $val || ($isCustom && $val === 'custom')) ? 'selected' : '';
                ?>
                <option value="<?= $val ?>" <?= $sel ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="customUnit" name="custom_unit" placeholder="Nome da unidade"
                   value="<?= $isCustom ? htmlspecialchars($currentUnit) : '' ?>"
                   style="display:<?= $isCustom ? 'block' : 'none' ?>; width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:13px; margin-top:8px; background:var(--card-bg,#fff); color:var(--text-primary,#1e293b); box-sizing:border-box;">
        </div>
        <div style="background:var(--card-bg,#fff); border-radius:14px; padding:16px; box-shadow:0 1px 4px rgba(0,0,0,.06);">
            <label style="font-size:13px; font-weight:600; color:var(--text-primary,#1e293b); margin-bottom:8px; display:flex; align-items:center; gap:6px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/></svg> Valor Unitário</label>
            <input type="text" name="unit_value" required placeholder="Ex: 100" inputmode="decimal"
                   value="<?= $isEdit ? htmlspecialchars($ingredient['unit_value'] ?? '') : htmlspecialchars($_POST['unit_value'] ?? '') ?>"
                   style="width:100%; padding:12px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; background:var(--card-bg,#fff); color:var(--text-primary,#1e293b); box-sizing:border-box;">
            <div style="font-size:11px; color:#94a3b8; margin-top:4px;">Qtd por unidade (ex: 100g)</div>
        </div>
    </div>

    <!-- Margem Preview -->
    <div id="marginPreview" style="background:linear-gradient(135deg,#ecfdf5,#d1fae5); border-radius:14px; padding:14px 16px; display:flex; align-items:center; justify-content:space-between;">
        <span style="font-size:13px; font-weight:600; color:#065f46; display:flex; align-items:center; gap:6px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg> Margem</span>
        <span id="marginValue" style="font-size:18px; font-weight:700; color:#10b981;">--%</span>
    </div>

    <!-- Submit -->
    <button type="submit"
            style="width:100%; padding:16px; background:var(--admin-primary-color,#4361ee); color:#fff; border:none; border-radius:14px; font-size:15px; font-weight:700; cursor:pointer;">
        <?= $isEdit ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Salvar' : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M12 5v14m-7-7h14"/></svg> Criar' ?>
    </button>
</form>

<script>
(function() {
    var costEl = document.querySelector('[name="cost"]');
    var saleEl = document.querySelector('[name="sale_price"]');
    var marginEl = document.getElementById('marginValue');
    function parseBR(v) {
        if (!v) return 0;
        return parseFloat(v.replace(/\./g,'').replace(',','.')) || 0;
    }
    function updateMargin() {
        var c = parseBR(costEl.value), s = parseBR(saleEl.value);
        if (s > 0) {
            var m = ((s - c) / s * 100).toFixed(0);
            marginEl.textContent = m + '%';
            marginEl.style.color = m >= 50 ? '#10b981' : (m >= 30 ? '#f59e0b' : '#ef4444');
        } else {
            marginEl.textContent = '--%';
        }
    }
    costEl.addEventListener('input', updateMargin);
    saleEl.addEventListener('input', updateMargin);
    updateMargin();

    // Handle custom unit
    var form = costEl.closest('form');
    form.addEventListener('submit', function() {
        var sel = document.getElementById('unitSelect');
        var custom = document.getElementById('customUnit');
        if (sel.value === 'custom' && custom.value.trim()) {
            sel.disabled = true;
            var h = document.createElement('input');
            h.type = 'hidden'; h.name = 'unit'; h.value = custom.value.trim();
            form.appendChild(h);
        }
    });
})();
</script>

<!-- Spacer -->
<div style="height:90px;"></div>
