<?php
require_once __DIR__ . '/../components/icons.php';
$activeProductNav = 'ingredients'; require __DIR__ . '/../components/products-nav.php';
?>

<?php
$toolbarSearch = true;
$toolbarSearchId = 'searchInput';
$toolbarPlaceholder = 'Buscar ingrediente...';
$toolbarSearchValue = $q ?? '';
require __DIR__ . '/../components/page-toolbar.php';
?>

<?php require __DIR__ . '/../components/page-alerts.php'; ?>

<!-- Lista de Ingredientes -->
<div class="products-list">
    <?php if (empty($items)): ?>
        <div style="text-align:center; padding:50px 20px; color:#94a3b8;">
            <div style="margin-bottom:12px;">
                <?= productIcon('layers', 48, '1.5') ?>
            </div>
            <div style="font-size:15px; font-weight:600; margin-bottom:4px;">Nenhum ingrediente</div>
            <div style="font-size:13px; margin-bottom:16px;">Adicione ingredientes para controlar custos</div>
            <a href="/ingredients/create"
               style="display:inline-flex; align-items:center; gap:6px; padding:10px 16px; border:none; border-radius:10px; font-size:13px; font-weight:600; color:#fff; background:var(--primary); text-decoration:none;">
                <?= productIcon('plus', 15, '2.5') ?>
                Novo Ingrediente
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($items as $item):
            $margin = 0;
            if ((float)($item['sale_price'] ?? 0) > 0) {
                $margin = (((float)$item['sale_price'] - (float)$item['cost']) / (float)$item['sale_price']) * 100;
            }
            $mColor = $margin >= 50 ? '#10b981' : ($margin >= 30 ? '#f59e0b' : '#ef4444');
        ?>
            <div class="product-card ingredient-card<?= empty($item['active']) ? ' ingredient-inactive' : '' ?>" data-name="<?= strtolower(htmlspecialchars($item['name'])) ?>" data-id="<?= (int)$item['id'] ?>">
                <div class="product-card-image">
                    <?php if (!empty($item['image_path'])): ?>
                        <img src="/<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                    <?php else: ?>
                        <div class="product-placeholder">
                            <?= productIcon('layers', 24, '1.5') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="product-card-content">
                    <div class="product-name"><?= htmlspecialchars($item['name']) ?></div>
                    <?php if (!empty($item['internal_name'])): ?>
                        <span class="product-category"><?= htmlspecialchars($item['internal_name']) ?></span>
                    <?php endif; ?>
                    <div class="product-price-row">
                        <span class="price-current" style="color:#ef4444;">R$ <?= number_format((float)($item['cost'] ?? 0), 2, ',', '.') ?></span>
                        <span style="font-size:12px; color:#94a3b8;">→</span>
                        <span class="price-current" style="color:#10b981;">R$ <?= number_format((float)($item['sale_price'] ?? 0), 2, ',', '.') ?></span>
                        <span style="font-size:11px; font-weight:600; color:<?= $mColor ?>; margin-left:2px;"><?= number_format($margin, 0) ?>%</span>
                    </div>
                </div>

                <div class="product-card-actions">
                    <button type="button" class="btn-icon-sm btn-toggle-ingredient" data-id="<?= (int)$item['id'] ?>" data-active="<?= (int)($item['active'] ?? 1) ?>" title="<?= empty($item['active']) ? 'Ativar' : 'Ocultar' ?>">
                        <?= productIcon(empty($item['active']) ? 'eye-off' : 'eye', 20) ?>
                    </button>
                    <a href="/ingredients/<?= $item['id'] ?>/edit" class="btn-icon-sm" title="Editar">
                        <?= productIcon('edit', 20) ?>
                    </a>
                    <form method="POST" action="/ingredients/<?= $item['id'] ?>/delete" onsubmit="return confirm('Excluir ingrediente?')" style="margin:0;">
                        <button type="submit" class="btn-icon-sm" title="Excluir" style="color:#ef4444;">
                            <?= productIcon('trash', 20) ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
.ingredient-inactive { opacity: 0.45; }
.ingredient-inactive .product-name { text-decoration: line-through; }
.btn-toggle-ingredient { transition: color .2s; }
.btn-toggle-ingredient[data-active="0"] { color: #94a3b8; }
.btn-toggle-ingredient[data-active="1"] { color: #10b981; }
</style>

<script>
(function() {
    var input = document.getElementById('searchInput');
    if (input) {
        input.addEventListener('input', function() {
            var q = this.value.toLowerCase();
            var cards = document.querySelectorAll('.ingredient-card');
            cards.forEach(function(c) {
                c.style.display = c.dataset.name.indexOf(q) !== -1 ? '' : 'none';
            });
        });
    }

    // Eye icons SVG paths
    var eyeOnSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    var eyeOffSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

    document.querySelectorAll('.btn-toggle-ingredient').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            var self = this;
            fetch('/ingredients/' + id + '/toggle', { method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'} })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.success) return;
                    self.dataset.active = data.active;
                    self.title = data.active ? 'Ocultar' : 'Ativar';
                    self.innerHTML = data.active ? eyeOnSvg : eyeOffSvg;
                    var card = self.closest('.ingredient-card');
                    if (card) {
                        card.classList.toggle('ingredient-inactive', !data.active);
                    }
                });
        });
    });
})();
</script>

<!-- FAB Novo Ingrediente -->
<a href="/ingredients/create" class="fab">
    <?= productIcon('plus', 24, '1.5') ?>
</a>
