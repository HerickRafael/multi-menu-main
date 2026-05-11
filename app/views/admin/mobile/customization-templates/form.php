<?php
/**
 * Mobile View: Formulário de Grupo de Personalização (criar/editar)
 */
$isEdit = !empty($template);
$templateName = $template['name'] ?? '';
$templateType = $template['type'] ?? 'extra';
$templateMode = in_array($templateType, ['single', 'addon', 'choice'], true) ? 'choice' : 'extra';
$templateMinQty = $template['min_qty'] ?? 0;
$templateMaxQty = $template['max_qty'] ?? 1;
$templateActive = $template['active'] ?? 1;
$templateHideDuplicates = $template['hide_duplicates'] ?? 0;
$items = $template['items'] ?? [];

$ingredientsJs = json_encode(array_map(function($ing) {
    return [
        'id' => (int)$ing['id'],
        'name' => $ing['name'],
        'internal_name' => $ing['internal_name'] ?? '',
        'delta' => (float)($ing['delta'] ?? 0),
        'min_qty' => (int)($ing['min_qty'] ?? 0),
        'max_qty' => (int)($ing['max_qty'] ?? 1)
    ];
}, $ingredients ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>

<?php if ($error): ?>
<div style="background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:12px; margin-bottom:14px; font-size:13px;">
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<script>window.INGREDIENTS_DATA = <?= $ingredientsJs ?>;</script>

<form method="POST" action="/customization-templates<?= $isEdit ? '/' . (int)$template['id'] : '' ?>" id="templateForm">

    <!-- Nome -->
    <div style="margin-bottom:16px;">
        <label style="display:block; font-size:13px; font-weight:600; color:var(--text-primary,#1e293b); margin-bottom:6px;">Nome do grupo *</label>
        <input type="text" name="name" required value="<?= htmlspecialchars($templateName) ?>"
               placeholder="Ex: Adicionais, Molhos..."
               style="width:100%; padding:12px 14px; border:1.5px solid #e2e8f0; border-radius:12px; font-size:14px; background:var(--card-bg,#fff); color:var(--text-primary,#1e293b); box-sizing:border-box;">
    </div>

    <!-- Toggle Ativo -->
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px;">
        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px;">
            <input type="checkbox" name="active" value="1" <?= $templateActive ? 'checked' : '' ?>
                   style="width:18px; height:18px; accent-color:var(--admin-primary-color,#4361ee);">
            <span style="font-weight:600; color:var(--text-primary,#1e293b);">Grupo ativo</span>
        </label>
    </div>

    <!-- Toggle Ocultar Duplicados -->
    <div style="background:var(--card-bg,#fff); border-radius:12px; padding:14px; margin-bottom:16px; border:1px solid #e2e8f0;">
        <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer;">
            <input type="checkbox" name="hide_duplicates" value="1" <?= $templateHideDuplicates ? 'checked' : '' ?>
                   style="width:18px; height:18px; accent-color:var(--admin-primary-color,#4361ee); margin-top:2px; flex-shrink:0;">
            <div>
                <div style="font-size:13px; font-weight:600; color:var(--text-primary,#1e293b);">Ocultar ingredientes repetidos</div>
                <a href="/guide/customization-templates#form" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:6px;line-height:1;" title="Ajuda">?</a>
                <div style="font-size:11px; color:#64748b; margin-top:2px; line-height:1.4;">
                    Se o produto já tiver um ingrediente em outro grupo, ele será ocultado neste grupo na página do cliente.
                </div>
            </div>
        </label>
    </div>

    <!-- Modo de seleção -->
    <div style="margin-bottom:16px;">
        <label style="display:block; font-size:13px; font-weight:600; color:var(--text-primary,#1e293b); margin-bottom:6px;">Modo de seleção <a href="/guide/customization-templates#modes" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:4px;line-height:1;" title="Ajuda">?</a></label>
        <select name="type" id="modeSelect"
                style="width:100%; padding:12px 14px; border:1.5px solid #e2e8f0; border-radius:12px; font-size:14px; background:var(--card-bg,#fff); color:var(--text-primary,#1e293b); box-sizing:border-box;">
            <option value="extra" <?= $templateMode === 'extra' ? 'selected' : '' ?>>Adicionar ingredientes livremente</option>
            <option value="choice" <?= $templateMode === 'choice' ? 'selected' : '' ?>>Escolher ingrediente</option>
        </select>
    </div>

    <!-- Min/Max para modo choice -->
    <div id="choiceSettings" style="display:<?= $templateMode === 'choice' ? 'grid' : 'none' ?>; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:16px;">
        <div>
            <label style="display:block; font-size:12px; color:#64748b; margin-bottom:4px;">Seleções mínimas</label>
            <input type="number" name="min_qty" value="<?= $templateMode === 'choice' ? $templateMinQty : 0 ?>" min="0"
                   style="width:100%; padding:10px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; text-align:center; box-sizing:border-box;">
        </div>
        <div>
            <label style="display:block; font-size:12px; color:#64748b; margin-bottom:4px;">Seleções máximas</label>
            <input type="number" name="max_qty" value="<?= $templateMode === 'choice' ? $templateMaxQty : 1 ?>" min="1"
                   style="width:100%; padding:10px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; text-align:center; box-sizing:border-box;">
        </div>
    </div>

    <?php if ($isEdit && !empty($productsUsing)): ?>
    <!-- Produtos vinculados -->
    <div style="background:var(--card-bg,#fff); border-radius:12px; padding:14px; margin-bottom:16px; border:1px solid #e2e8f0;">
        <div style="font-size:13px; font-weight:600; color:var(--text-primary,#1e293b); margin-bottom:8px;">
            Usando em <?= count($productsUsing) ?> produto<?= count($productsUsing) > 1 ? 's' : '' ?>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px;">
            <?php foreach ($productsUsing as $prod): ?>
            <span style="display:inline-flex; background:#f1f5f9; color:#475569; padding:4px 10px; border-radius:8px; font-size:12px; font-weight:500;">
                <?= htmlspecialchars($prod['name']) ?>
            </span>
            <?php endforeach; ?>
        </div>
        <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer;">
            <input type="checkbox" name="sync_products" value="1" checked
                   style="width:18px; height:18px; accent-color:var(--admin-primary-color,#4361ee); margin-top:2px; flex-shrink:0;">
            <div>
                <div style="font-size:13px; font-weight:600; color:var(--text-primary,#1e293b);">Sincronizar com produtos</div>
                <a href="/guide/customization-templates#sync" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:6px;line-height:1;" title="Ajuda">?</a>
                <div style="font-size:11px; color:#64748b; margin-top:2px;">Aplicar alterações automaticamente nos produtos vinculados.</div>
            </div>
        </label>
    </div>
    <?php endif; ?>

    <!-- Itens -->
    <div style="background:var(--card-bg,#fff); border-radius:14px; border:1px solid #e2e8f0; overflow:hidden; margin-bottom:16px;">
        <div style="padding:12px 14px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-size:13px; font-weight:700; color:var(--text-primary,#1e293b);">
            Ingredientes
        </div>
        <div id="itemsContainer">
            <?php if (!empty($items)): foreach ($items as $ii => $item):
                $selId = isset($item['ingredient_id']) ? (int)$item['ingredient_id'] : 0;
                $itemLabel = $item['label'] ?? '';
                $def = !empty($item['is_default']);
                $minQ = isset($item['min_qty']) ? (int)$item['min_qty'] : 0;
                $maxQ = isset($item['max_qty']) ? (int)$item['max_qty'] : 1;
                $defQty = isset($item['default_qty']) ? (int)$item['default_qty'] : $minQ;
                $displayName = '';
                if ($selId) {
                    foreach ($ingredients as $ing) {
                        if ((int)$ing['id'] === $selId) { $displayName = $ing['name']; break; }
                    }
                }
                if (!$displayName) $displayName = $itemLabel;
            ?>
            <div class="tmpl-item" data-idx="<?= $ii ?>" style="padding:14px; border-bottom:1px solid #f1f5f9;">
                <!-- Ingrediente busca -->
                <div style="margin-bottom:10px; position:relative;">
                    <input type="text" class="ing-search" placeholder="Buscar ingrediente..."
                           value="<?= htmlspecialchars($displayName) ?>" autocomplete="off"
                           style="width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:13px; box-sizing:border-box; background:var(--card-bg,#fff); color:var(--text-primary,#1e293b);">
                    <input type="hidden" name="items[<?= $ii ?>][ingredient_id]" class="ing-id" value="<?= $selId ?>">
                    <input type="hidden" name="items[<?= $ii ?>][label]" class="ing-label" value="<?= htmlspecialchars($itemLabel) ?>">
                    <input type="hidden" name="items[<?= $ii ?>][delta]" class="ing-delta" value="<?= (float)($item['delta'] ?? 0) ?>">
                    <div class="ing-dropdown" style="display:none; position:absolute; top:100%; left:0; right:0; z-index:50; background:#fff; border:1px solid #e2e8f0; border-radius:10px; max-height:180px; overflow-y:auto; box-shadow:0 8px 20px rgba(0,0,0,.12);"></div>
                </div>
                <!-- Quantidades -->
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr auto; gap:8px; align-items:end;">
                    <div>
                        <label style="display:block; font-size:11px; color:#64748b; margin-bottom:3px;">Mín</label>
                        <input type="number" name="items[<?= $ii ?>][min_qty]" value="<?= $minQ ?>" min="0"
                               style="width:100%; padding:8px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; text-align:center; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; font-size:11px; color:#64748b; margin-bottom:3px;">Máx</label>
                        <input type="number" name="items[<?= $ii ?>][max_qty]" value="<?= $maxQ ?>" min="0"
                               style="width:100%; padding:8px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; text-align:center; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; font-size:11px; color:#64748b; margin-bottom:3px;">Qty Pad</label>
                        <input type="number" name="items[<?= $ii ?>][default_qty]" value="<?= $defQty ?>" min="0"
                               style="width:100%; padding:8px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; text-align:center; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; font-size:11px; color:#64748b; margin-bottom:3px;">Padrão</label>
                        <input type="hidden" name="items[<?= $ii ?>][is_default]" class="def-flag" value="<?= $def ? '1' : '0' ?>">
                        <button type="button" class="def-btn"
                                style="width:100%; padding:8px; border:1.5px solid <?= $def ? 'var(--admin-primary-color,#4361ee)' : '#e2e8f0' ?>; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; background:<?= $def ? 'var(--admin-primary-color,#4361ee)' : '#fff' ?>; color:<?= $def ? '#fff' : '#64748b' ?>;">
                            <?= $def ? 'Sim' : 'Não' ?>
                        </button>
                    </div>
                    <button type="button" class="remove-item-btn"
                            style="padding:8px; border:none; background:none; color:#ef4444; cursor:pointer; font-size:18px;" title="Remover">
                        ×
                    </button>
                </div>
            </div>
            <?php endforeach; else: ?>
            <!-- Item vazio -->
            <div class="tmpl-item" data-idx="0" style="padding:14px; border-bottom:1px solid #f1f5f9;">
                <div style="margin-bottom:10px; position:relative;">
                    <input type="text" class="ing-search" placeholder="Buscar ingrediente..." autocomplete="off"
                           style="width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:13px; box-sizing:border-box; background:var(--card-bg,#fff); color:var(--text-primary,#1e293b);">
                    <input type="hidden" name="items[0][ingredient_id]" class="ing-id" value="">
                    <input type="hidden" name="items[0][label]" class="ing-label" value="">
                    <input type="hidden" name="items[0][delta]" class="ing-delta" value="0">
                    <div class="ing-dropdown" style="display:none; position:absolute; top:100%; left:0; right:0; z-index:50; background:#fff; border:1px solid #e2e8f0; border-radius:10px; max-height:180px; overflow-y:auto; box-shadow:0 8px 20px rgba(0,0,0,.12);"></div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr auto; gap:8px; align-items:end;">
                    <div>
                        <label style="display:block; font-size:11px; color:#64748b; margin-bottom:3px;">Mín</label>
                        <input type="number" name="items[0][min_qty]" value="0" min="0"
                               style="width:100%; padding:8px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; text-align:center; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; font-size:11px; color:#64748b; margin-bottom:3px;">Máx</label>
                        <input type="number" name="items[0][max_qty]" value="10" min="0"
                               style="width:100%; padding:8px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; text-align:center; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; font-size:11px; color:#64748b; margin-bottom:3px;">Qty Pad</label>
                        <input type="number" name="items[0][default_qty]" value="1" min="0"
                               style="width:100%; padding:8px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; text-align:center; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; font-size:11px; color:#64748b; margin-bottom:3px;">Padrão</label>
                        <input type="hidden" name="items[0][is_default]" class="def-flag" value="0">
                        <button type="button" class="def-btn"
                                style="width:100%; padding:8px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; background:#fff; color:#64748b;">
                            Não
                        </button>
                    </div>
                    <button type="button" class="remove-item-btn"
                            style="padding:8px; border:none; background:none; color:#ef4444; cursor:pointer; font-size:18px;" title="Remover">
                        ×
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div style="padding:12px 14px; border-top:1px solid #e2e8f0;">
            <button type="button" id="addItemBtn"
                    style="width:100%; padding:10px; border:1.5px dashed #cbd5e1; border-radius:10px; font-size:13px; font-weight:600; color:#64748b; background:transparent; cursor:pointer;">
                + Adicionar Ingrediente
            </button>
        </div>
    </div>

    <!-- Botões submit -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
        <a href="/customization-templates"
           style="display:flex; align-items:center; justify-content:center; padding:12px; border:1.5px solid #e2e8f0; border-radius:12px; font-size:14px; font-weight:600; color:#64748b; text-decoration:none;">
            Cancelar
        </a>
        <button type="submit"
                style="padding:12px; border:none; border-radius:12px; font-size:14px; font-weight:600; color:#fff; background:var(--admin-primary-color,#4361ee); cursor:pointer;">
            <?= $isEdit ? 'Salvar' : 'Criar Grupo' ?>
        </button>
    </div>
</form>

<script>
(function() {
    const ingredients = window.INGREDIENTS_DATA || [];
    const container = document.getElementById('itemsContainer');
    const addBtn = document.getElementById('addItemBtn');
    const modeSelect = document.getElementById('modeSelect');
    const choiceSettings = document.getElementById('choiceSettings');

    // Toggle choice settings
    modeSelect.addEventListener('change', function() {
        choiceSettings.style.display = this.value === 'choice' ? 'grid' : 'none';
    });

    // Next index
    function nextIdx() {
        const items = container.querySelectorAll('.tmpl-item');
        const indices = Array.from(items).map(i => parseInt(i.dataset.idx) || 0);
        return indices.length ? Math.max(...indices) + 1 : 0;
    }

    // Escape HTML
    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    // Add item
    addBtn.addEventListener('click', function() {
        const idx = nextIdx();
        const html = `
        <div class="tmpl-item" data-idx="${idx}" style="padding:14px; border-bottom:1px solid #f1f5f9;">
            <div style="margin-bottom:10px; position:relative;">
                <input type="text" class="ing-search" placeholder="Buscar ingrediente..." autocomplete="off"
                       style="width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:13px; box-sizing:border-box; background:var(--card-bg,#fff); color:var(--text-primary,#1e293b);">
                <input type="hidden" name="items[${idx}][ingredient_id]" class="ing-id" value="">
                <input type="hidden" name="items[${idx}][label]" class="ing-label" value="">
                <input type="hidden" name="items[${idx}][delta]" class="ing-delta" value="0">
                <div class="ing-dropdown" style="display:none; position:absolute; top:100%; left:0; right:0; z-index:50; background:#fff; border:1px solid #e2e8f0; border-radius:10px; max-height:180px; overflow-y:auto; box-shadow:0 8px 20px rgba(0,0,0,.12);"></div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr auto; gap:8px; align-items:end;">
                <div>
                    <label style="display:block; font-size:11px; color:#64748b; margin-bottom:3px;">Mín</label>
                    <input type="number" name="items[${idx}][min_qty]" value="0" min="0"
                           style="width:100%; padding:8px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; text-align:center; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; font-size:11px; color:#64748b; margin-bottom:3px;">Máx</label>
                    <input type="number" name="items[${idx}][max_qty]" value="10" min="0"
                           style="width:100%; padding:8px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; text-align:center; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; font-size:11px; color:#64748b; margin-bottom:3px;">Qty Pad</label>
                    <input type="number" name="items[${idx}][default_qty]" value="1" min="0"
                           style="width:100%; padding:8px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; text-align:center; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; font-size:11px; color:#64748b; margin-bottom:3px;">Padrão</label>
                    <input type="hidden" name="items[${idx}][is_default]" class="def-flag" value="0">
                    <button type="button" class="def-btn"
                            style="width:100%; padding:8px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; background:#fff; color:#64748b;">
                        Não
                    </button>
                </div>
                <button type="button" class="remove-item-btn"
                        style="padding:8px; border:none; background:none; color:#ef4444; cursor:pointer; font-size:18px;" title="Remover">
                    ×
                </button>
            </div>
        </div>`;
        container.insertAdjacentHTML('beforeend', html);
        const newItem = container.lastElementChild;
        setupItem(newItem);
        newItem.querySelector('.ing-search').focus();
    });

    // Setup item events
    function setupItem(item) {
        // Remove
        item.querySelector('.remove-item-btn').addEventListener('click', function() { item.remove(); });

        // Default toggle
        const defBtn = item.querySelector('.def-btn');
        const defFlag = item.querySelector('.def-flag');
        defBtn.addEventListener('click', function() {
            const isOn = defFlag.value === '1';
            defFlag.value = isOn ? '0' : '1';
            defBtn.textContent = isOn ? 'Não' : 'Sim';
            defBtn.style.background = isOn ? '#fff' : 'var(--admin-primary-color,#4361ee)';
            defBtn.style.color = isOn ? '#64748b' : '#fff';
            defBtn.style.borderColor = isOn ? '#e2e8f0' : 'var(--admin-primary-color,#4361ee)';
        });

        // Ingredient search
        const search = item.querySelector('.ing-search');
        const dropdown = item.querySelector('.ing-dropdown');
        const idInput = item.querySelector('.ing-id');
        const labelInput = item.querySelector('.ing-label');
        const deltaInput = item.querySelector('.ing-delta');

        search.addEventListener('focus', function() { showDropdown(search.value); });
        search.addEventListener('input', function() { showDropdown(search.value); });

        function showDropdown(q) {
            q = (q || '').toLowerCase().trim();
            let filtered = ingredients;
            if (q) {
                filtered = ingredients.filter(function(ing) {
                    return ing.name.toLowerCase().indexOf(q) !== -1 ||
                           (ing.internal_name && ing.internal_name.toLowerCase().indexOf(q) !== -1);
                });
            }
            if (filtered.length === 0) {
                dropdown.innerHTML = '<div style="padding:10px 14px; color:#94a3b8; font-size:13px;">Nenhum encontrado</div>';
            } else {
                dropdown.innerHTML = filtered.slice(0, 10).map(function(ing) {
                    return '<div class="ing-option" data-id="'+ing.id+'" data-name="'+esc(ing.name)+'" data-delta="'+(ing.delta||0)+'" data-min="'+(ing.min_qty||0)+'" data-max="'+(ing.max_qty||10)+'" style="padding:10px 14px; cursor:pointer; font-size:13px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between;">' +
                        '<span style="font-weight:500; color:#1e293b;">'+esc(ing.name)+'</span>' +
                        (ing.delta ? '<span style="color:#10b981; font-size:12px;">+R$ '+parseFloat(ing.delta).toFixed(2)+'</span>' : '') +
                    '</div>';
                }).join('');
            }
            dropdown.style.display = 'block';
            dropdown.querySelectorAll('.ing-option').forEach(function(opt) {
                opt.addEventListener('click', function() { selectIng(opt); });
            });
        }

        function selectIng(opt) {
            search.value = opt.dataset.name;
            idInput.value = opt.dataset.id;
            labelInput.value = opt.dataset.name;
            deltaInput.value = opt.dataset.delta;
            dropdown.style.display = 'none';
        }

        document.addEventListener('click', function(e) {
            if (!item.contains(e.target)) dropdown.style.display = 'none';
        });
    }

    // Setup existing items
    container.querySelectorAll('.tmpl-item').forEach(function(item) { setupItem(item); });
})();
</script>
