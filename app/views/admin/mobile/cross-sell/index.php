<?php
/**
 * Mobile View: Cross-Sell Groups (estilo desktop)
 */
require_once __DIR__ . '/../components/icons.php';
$categoriesJson = json_encode($categories ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>

<?php $activeProductNav = 'crosssell'; require __DIR__ . '/../components/products-nav.php'; ?>

<?php require __DIR__ . '/../components/page-alerts.php'; ?>

<?php if (empty($groups)): ?>
<div style="text-align:center; padding:50px 20px; color:#94a3b8;">
    <div style="margin-bottom:12px;">
        <?= productIcon('tag', 48, '1.5') ?>
    </div>
    <div style="font-size:15px; font-weight:600; margin-bottom:4px;">Nenhuma regra configurada</div>
    <div style="font-size:13px; margin-bottom:16px;">Crie regras de cross-sell para aumentar suas vendas.</div>
    <button type="button" onclick="openCsModal()"
            style="display:inline-flex; align-items:center; gap:6px; padding:10px 16px; border:none; border-radius:10px; font-size:13px; font-weight:600; color:#fff; background:var(--primary); cursor:pointer;">
        <?= productIcon('plus', 15, '2.5') ?>
        Criar regra
    </button>
</div>
<?php else: ?>

<div class="table-list">
    <?php foreach ($groups as $group): ?>
    <div class="table-list-item">
        <div class="table-list-header">
            <div class="table-list-title">
                <span class="table-list-icon"><?= productIcon('folder', 16) ?></span>
                <span class="table-list-name"><?= htmlspecialchars($group['trigger_category_name'] ?? 'Categoria') ?></span>
            </div>
            <span class="<?= !empty($group['active']) ? 'badge-active' : 'badge-inactive' ?>">
                <?= !empty($group['active']) ? 'Ativo' : 'Inativo' ?>
            </span>
        </div>

        <div class="table-list-tags">
            <?php foreach ($group['recommendations'] as $rec): ?>
            <span class="table-list-tag" style="background:#ecfdf5; color:#047857; border:1px solid #a7f3d0;">
                <?= htmlspecialchars($rec['category_name'] ?? '') ?>
                <span style="color:#86efac;">·</span>
                <span style="font-style:italic;"><?= htmlspecialchars($rec['section_title'] ?? '') ?></span>
            </span>
            <?php endforeach; ?>
        </div>

        <div class="table-list-actions">
            <button type="button" class="action-btn cs-edit-btn" data-group='<?= htmlspecialchars(json_encode($group), ENT_QUOTES, 'UTF-8') ?>'>
                <?= productIcon('edit', 14) ?> Editar
            </button>
            <button type="button" class="action-btn cs-toggle-btn" data-id="<?= (int)$group['id'] ?>">
                <?php if (!empty($group['active'])): ?>
                <?= productIcon('eye-off', 14) ?> Desativar
                <?php else: ?>
                <?= productIcon('eye', 14) ?> Ativar
                <?php endif; ?>
            </button>
            <button type="button" class="action-btn action-btn-danger cs-delete-btn" data-id="<?= (int)$group['id'] ?>">
                <?= productIcon('trash', 14) ?> Excluir
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal -->
<div id="csModal" style="display:none; position:fixed; inset:0; z-index:1000; background:rgba(0,0,0,.5); padding:16px; overflow-y:auto;">
    <div style="background:var(--card-bg,#fff); border-radius:16px; max-width:500px; margin:20px auto; box-shadow:0 10px 40px rgba(0,0,0,.2); overflow:hidden;">
        <!-- Header -->
        <div style="padding:16px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
            <div style="font-size:16px; font-weight:700; color:var(--text-primary,#1e293b);" id="csModalTitle">Nova Regra</div>
            <button type="button" id="csModalClose" style="background:none; border:none; font-size:22px; color:#94a3b8; cursor:pointer; padding:4px;">×</button>
        </div>

        <form method="POST" action="/cross-sell/save" id="csForm" style="padding:16px;">
            <!-- Categoria Disparadora -->
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:13px; font-weight:600; color:var(--text-primary,#1e293b); margin-bottom:6px;">Categoria Disparadora * <a href="/guide/cross-sell#form" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;" title="Ajuda">?</a></label>
                <select name="trigger_category_id" id="csTrigger" required
                        style="width:100%; padding:12px 14px; border:1.5px solid #e2e8f0; border-radius:12px; font-size:14px; background:var(--card-bg,#fff); color:var(--text-primary,#1e293b); box-sizing:border-box;">
                    <option value="">Selecione...</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="font-size:11px; color:#94a3b8; margin-top:4px;">Quando o cliente comprar desta categoria</div>
            </div>

            <!-- Categorias Recomendadas -->
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:13px; font-weight:600; color:var(--text-primary,#1e293b); margin-bottom:8px;">Categorias Recomendadas <a href="/guide/cross-sell#form" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;" title="Ajuda">?</a></label>
                <div id="csRecommendations" style="max-height:280px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:12px; background:#f8fafc;">
                    <?php foreach ($categories as $cat): ?>
                    <div style="padding:12px; border-bottom:1px solid #e2e8f0;" data-cat-id="<?= (int)$cat['id'] ?>">
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                            <input type="checkbox" class="cs-cat-check" data-cat="<?= (int)$cat['id'] ?>" name="recommended_categories[<?= (int)$cat['id'] ?>][selected]" value="1"
                                   style="width:18px; height:18px; accent-color:var(--admin-primary-color,#4361ee); flex-shrink:0;">
                            <span style="font-size:14px; font-weight:500; color:var(--text-primary,#1e293b);"><?= htmlspecialchars($cat['name']) ?></span>
                        </label>
                        <div class="cs-title-wrap" data-for="<?= (int)$cat['id'] ?>" style="display:none; margin-top:8px; margin-left:28px;">
                            <input type="text" name="recommended_categories[<?= (int)$cat['id'] ?>][title]" class="cs-title-input"
                                   placeholder="Ex: Que tal uma <?= htmlspecialchars(strtolower($cat['name'])) ?>?"
                                   maxlength="100"
                                   style="width:100%; padding:8px 12px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:13px; box-sizing:border-box;">
                            <div style="font-size:10px; color:#94a3b8; margin-top:2px;">Título que aparecerá no site</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Botões -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; padding-top:8px; border-top:1px solid #e2e8f0;">
                <button type="button" id="csModalCancel"
                        style="padding:12px; border:1.5px solid #e2e8f0; border-radius:12px; font-size:14px; font-weight:600; color:#64748b; background:transparent; cursor:pointer;">
                    Cancelar
                </button>
                <button type="submit"
                        style="padding:12px; border:none; border-radius:12px; font-size:14px; font-weight:600; color:#fff; background:var(--admin-primary-color,#4361ee); cursor:pointer;">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    var modal = document.getElementById('csModal');
    var modalTitle = document.getElementById('csModalTitle');
    var trigger = document.getElementById('csTrigger');

    function openModal() { modal.style.display = 'block'; }
    function closeModal() { modal.style.display = 'none'; }

    // Expose globally for toolbar button
    window.openCsModal = function() {
        resetForm();
        modalTitle.textContent = 'Nova Regra de Cross-Sell';
        openModal();
    };

    function resetForm() {
        trigger.value = '';
        document.querySelectorAll('.cs-cat-check').forEach(function(cb) {
            cb.checked = false;
            toggleTitle(cb.dataset.cat);
        });
    }

    function toggleTitle(catId) {
        var cb = document.querySelector('.cs-cat-check[data-cat="'+catId+'"]');
        var wrap = document.querySelector('.cs-title-wrap[data-for="'+catId+'"]');
        if (cb && wrap) {
            wrap.style.display = cb.checked ? 'block' : 'none';
            if (!cb.checked) {
                var inp = wrap.querySelector('.cs-title-input');
                if (inp) inp.value = '';
            }
        }
    }

    // Toggle title on checkbox change
    document.querySelectorAll('.cs-cat-check').forEach(function(cb) {
        cb.addEventListener('change', function() { toggleTitle(cb.dataset.cat); });
    });

    // Edit buttons
    document.querySelectorAll('.cs-edit-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var group = JSON.parse(btn.dataset.group);
            resetForm();
            modalTitle.textContent = 'Editar Regra de Cross-Sell';
            trigger.value = group.trigger_category_id;
            (group.recommendations || []).forEach(function(rec) {
                var cb = document.querySelector('.cs-cat-check[data-cat="'+rec.category_id+'"]');
                if (cb) {
                    cb.checked = true;
                    toggleTitle(rec.category_id);
                    var inp = document.querySelector('.cs-title-wrap[data-for="'+rec.category_id+'"] .cs-title-input');
                    if (inp) inp.value = rec.section_title || '';
                }
            });
            openModal();
        });
    });

    // Close
    document.getElementById('csModalClose').addEventListener('click', closeModal);
    document.getElementById('csModalCancel').addEventListener('click', closeModal);
    modal.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });

    // Toggle active
    document.querySelectorAll('.cs-toggle-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Alterar status deste grupo?')) return;
            var id = btn.dataset.id;
            fetch('/cross-sell/' + id + '/toggle', { method: 'POST' })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.success) location.reload();
                    else alert(d.error || 'Erro ao alterar status');
                })
                .catch(function() { alert('Erro de conexão'); });
        });
    });

    // Delete
    document.querySelectorAll('.cs-delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Excluir este grupo? Esta ação não pode ser desfeita.')) return;
            var id = btn.dataset.id;
            fetch('/cross-sell/' + id + '/delete', { method: 'POST' })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.success) location.reload();
                    else alert(d.error || 'Erro ao excluir');
                })
                .catch(function() { alert('Erro de conexão'); });
        });
    });

    // Form validation
    document.getElementById('csForm').addEventListener('submit', function(e) {
        if (!trigger.value) {
            e.preventDefault();
            alert('Selecione a categoria disparadora');
            return;
        }
        var checked = document.querySelectorAll('.cs-cat-check:checked');
        if (checked.length === 0) {
            e.preventDefault();
            alert('Selecione pelo menos uma categoria para recomendar');
            return;
        }
        var missing = false;
        checked.forEach(function(cb) {
            var inp = document.querySelector('.cs-title-wrap[data-for="'+cb.dataset.cat+'"] .cs-title-input');
            if (!inp || !inp.value.trim()) missing = true;
        });
        if (missing) {
            e.preventDefault();
            alert('Preencha o título para todas as categorias selecionadas');
        }
    });
})();
</script>

<!-- FAB Nova Regra -->
<button type="button" onclick="openCsModal()" class="fab">
    <?= productIcon('plus', 24, '1.5') ?>
</button>
