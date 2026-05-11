<?php
/**
 * Lista de Produtos Mobile
 */
require_once __DIR__ . '/../components/icons.php';
ob_start();

// Extrai cores da configuração do sistema
$getData = function($key, $default) use ($company) {
    if (is_array($company)) {
        return $company[$key] ?? $default;
    }
    return $company->$key ?? $default;
};

// Cores do sistema configuradas em settings
$headerBgColor      = $getData('menu_header_bg_color', $company['theme_color'] ?? '#4361ee');
$headerButtonColor  = $getData('menu_header_button_color', '#F59E0B');

// Gera uma versão mais escura da cor primária para hover/active
$primaryColorRgb = hexToRgb($headerBgColor);
$darkerPrimary = rgbToHex(
    max(0, $primaryColorRgb[0] - 40),
    max(0, $primaryColorRgb[1] - 40),
    max(0, $primaryColorRgb[2] - 40)
);

// Funções auxiliares para manipulação de cores
function hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    return sscanf($hex, "%02x%02x%02x");
}

function rgbToHex($r, $g, $b) {
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}
?>

<style>
:root {
    --primary-color: <?= htmlspecialchars($headerBgColor) ?>;
    --primary-color-dark: <?= htmlspecialchars($darkerPrimary) ?>;
}

.status-filter {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    overflow-x: auto;
    padding-bottom: 4px;
}

.status-filter::-webkit-scrollbar {
    height: 4px;
}

.status-filter::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 4px;
}

.status-filter::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 4px;
}

.filter-chip {
    padding: 8px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    background: white;
    color: #6b7280;
    text-decoration: none;
    white-space: nowrap;
    transition: all 0.2s;
}

.filter-chip:active {
    background: #f3f4f6;
}

.filter-chip.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}
</style>

<?php $activeProductNav = 'products'; require __DIR__ . '/../components/products-nav.php'; ?>

<?php
$toolbarSearch = true;
$toolbarSearchAction = '/products';
$toolbarSearchHidden = [];
if ($status !== 'all') $toolbarSearchHidden['status'] = $status;
if ($categoryId) $toolbarSearchHidden['category'] = (string)$categoryId;
$toolbarPlaceholder = 'Buscar produto...';
$toolbarSearchValue = $search ?? '';
require __DIR__ . '/../components/page-toolbar.php';
?>

<!-- Status Filter -->
<div class="status-filter">
    <a href="/products?status=all<?= $categoryId ? "&category={$categoryId}" : '' ?>" class="filter-chip <?= $status === 'all' ? 'active' : '' ?>">
        Todos (<?= $stats['total'] ?? 0 ?>)
    </a>
    <a href="/products?status=active<?= $categoryId ? "&category={$categoryId}" : '' ?>" class="filter-chip <?= $status === 'active' ? 'active' : '' ?>">
        Ativos (<?= $stats['active'] ?? 0 ?>)
    </a>
    <a href="/products?status=inactive<?= $categoryId ? "&category={$categoryId}" : '' ?>" class="filter-chip <?= $status === 'inactive' ? 'active' : '' ?>">
        Inativos (<?= $stats['inactive'] ?? 0 ?>)
    </a>
</div>

<!-- Categoria Filter -->
<?php if (!empty($categories)): ?>
<div class="category-chips">
    <a href="/products?status=<?= $status ?>" class="chip <?= !$categoryId ? 'active' : '' ?>">Todas</a>
    <?php foreach ($categories as $cat): ?>
        <a href="/products?status=<?= $status ?>&category=<?= $cat['id'] ?>" 
           class="chip <?= $categoryId == $cat['id'] ? 'active' : '' ?>">
            <?= htmlspecialchars($cat['name']) ?>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Lista de Produtos -->
<div class="products-list">
    <?php if (empty($products)): ?>
        <div style="text-align:center; padding:50px 20px; color:#94a3b8;">
            <div style="margin-bottom:12px;">
                <?= productIcon('package', 48, '1.5') ?>
            </div>
            <div style="font-size:15px; font-weight:600; margin-bottom:4px;">Nenhum produto</div>
            <div style="font-size:13px; margin-bottom:16px;">Adicione seu primeiro produto</div>
            <a href="/products/create"
               style="display:inline-flex; align-items:center; gap:6px; padding:10px 16px; border:none; border-radius:10px; font-size:13px; font-weight:600; color:#fff; background:var(--primary); text-decoration:none;">
                <?= productIcon('plus', 15, '2.5') ?>
                Novo Produto
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($products as $product): ?>
            <div class="product-card<?= !$product['active'] ? ' inactive' : '' ?>" data-id="<?= $product['id'] ?>">
                <div class="product-card-image">
                    <?php if (!empty($product['image'])): ?>
                        <img src="/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <?php else: ?>
                        <div class="product-placeholder">
                            <?= productIcon('image', 32, '1.5') ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!$product['active']): ?>
                        <span class="product-badge inactive" data-badge="status">Inativo</span>
                    <?php elseif (!empty($product['promo_price'])): ?>
                        <span class="product-badge promo" data-badge="promo">Promoção</span>
                    <?php endif; ?>
                </div>
                
                <div class="product-card-content">
                    <div class="product-info">
                        <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                        <?php if (!empty($product['category_name'])): ?>
                            <span class="product-category"><?= htmlspecialchars($product['category_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-price-row">
                        <?php if (!empty($product['promo_price'])): ?>
                            <span class="price-old">R$ <?= number_format((float)$product['price'], 2, ',', '.') ?></span>
                            <span class="price-current promo">R$ <?= number_format((float)$product['promo_price'], 2, ',', '.') ?></span>
                        <?php else: ?>
                            <span class="price-current">R$ <?= number_format((float)$product['price'], 2, ',', '.') ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="product-card-actions">
                        <button type="button" class="btn-icon-sm toggle-btn" 
                            onclick="toggleProductInstant(this, <?= $product['id'] ?>)" 
                            data-active="<?= $product['active'] ? '1' : '0' ?>"
                            data-icon-active="<?= htmlspecialchars(productIcon('eye', 20), ENT_QUOTES) ?>"
                            data-icon-inactive="<?= htmlspecialchars(productIcon('eye-off', 20), ENT_QUOTES) ?>"
                            title="<?= $product['active'] ? 'Desativar' : 'Ativar' ?>">
                        <span class="toggle-icon">
                        <?php if ($product['active']): ?>
                            <?= productIcon('eye', 20) ?>
                        <?php else: ?>
                            <?= productIcon('eye-off', 20) ?>
                        <?php endif; ?>
                        </span>
                    </button>
                    <a href="/products/<?= $product['id'] ?>/edit" class="btn-icon-sm" title="Editar">
                        <?= productIcon('edit', 20) ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- FAB Novo Produto -->
<a href="/products/create" class="fab">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <line x1="12" y1="5" x2="12" y2="19"/>
        <line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
</a>

<style>
.product-card.inactive { opacity: 0.6; filter: grayscale(0.3); }
</style>
<script>
function toggleProductInstant(btn, id) {
    if (btn.disabled) return;
    btn.disabled = true;
    const wasActive = btn.getAttribute('data-active') === '1';
    const iconActive = btn.getAttribute('data-icon-active') || '';
    const iconInactive = btn.getAttribute('data-icon-inactive') || '';
    fetch(`/products/${id}/toggle`, {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            MobileApp.toast(data.message || 'Erro ao atualizar', 'error');
            return;
        }

        const nextActive = data.active === 1 || data.active === '1' || data.active === true;
        const effectiveActive = typeof data.active === 'undefined' ? !wasActive : nextActive;
        const card = btn.closest('.product-card');
        // Badge de status
        let badge = card ? card.querySelector('.product-badge[data-badge="status"]') : null;
        if (!effectiveActive) {
            if (card) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'product-badge inactive';
                    badge.setAttribute('data-badge', 'status');
                    const imageWrap = card.querySelector('.product-card-image');
                    if (imageWrap) imageWrap.appendChild(badge);
                }
                if (badge) {
                    badge.textContent = 'Inativo';
                    badge.classList.add('inactive');
                }
                card.classList.add('inactive');
            }
        } else if (card) {
            if (badge) badge.remove();
            card.classList.remove('inactive');
        }

        // Atualiza ícone
        const iconSpan = btn.querySelector('.toggle-icon');
        if (iconSpan) {
            iconSpan.innerHTML = effectiveActive ? iconActive : iconInactive;
        }

        // Atualiza estado
        btn.setAttribute('data-active', effectiveActive ? '1' : '0');
        btn.title = effectiveActive ? 'Desativar' : 'Ativar';
        MobileApp.toast(data.message, 'success');
    })
    .catch(e => {
        MobileApp.toast('Erro ao atualizar', 'error');
    })
    .finally(() => {
        btn.disabled = false;
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
