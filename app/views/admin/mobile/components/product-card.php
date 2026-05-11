<?php
/**
 * Product Card Mobile - Card de produto para toggle rápido
 * 
 * @param object $product - Dados do produto
 * @param bool $showToggle - Mostrar toggle de disponibilidade
 */

$product = $product ?? null;
if (!$product) return;

$showToggle = $showToggle ?? true;
$isAvailable = ($product->is_available ?? 1) == 1;
$price = isset($product->price) ? 'R$ ' . number_format($product->price, 2, ',', '.') : 'R$ 0,00';
$image = $product->image ?? '/assets/images/placeholder.png';
$name = $product->name ?? 'Produto';
$category = $product->category_name ?? '';
?>

<div class="product-card" data-product-id="<?= $product->id ?? 0 ?>" data-available="<?= $isAvailable ? '1' : '0' ?>">
    <div class="product-card__image">
        <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($name) ?>" loading="lazy">
        <?php if (!$isAvailable): ?>
            <div class="product-card__unavailable">
                <span>Indisponível</span>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="product-card__content">
        <div class="product-card__header">
            <h3 class="product-card__name"><?= htmlspecialchars($name) ?></h3>
            <?php if ($category): ?>
                <span class="product-card__category"><?= htmlspecialchars($category) ?></span>
            <?php endif; ?>
        </div>
        
        <div class="product-card__footer">
            <span class="product-card__price"><?= $price ?></span>
            
            <?php if ($showToggle): ?>
                <label class="toggle-switch">
                    <input type="checkbox" 
                           class="toggle-availability" 
                           data-product="<?= $product->id ?>"
                           <?= $isAvailable ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            <?php endif; ?>
        </div>
    </div>
    
    <a href="/products/<?= $product->id ?>/edit" class="product-card__link" aria-label="Editar <?= htmlspecialchars($name) ?>"></a>
</div>
