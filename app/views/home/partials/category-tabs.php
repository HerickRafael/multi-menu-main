<?php $firstActiveAssigned = false; ?>
<div class="flex gap-2 overflow-x-auto flex-nowrap mb-3 pb-1" role="navigation" aria-label="Categorias do cardapio">
<?php if ($mostraNovidade): ?>
  <?php $isActive = !$firstActiveAssigned; if ($isActive) { $firstActiveAssigned = true; } ?>
  <a href="#novidades" class="category-tab shrink-0 px-4 py-1.5 rounded-full font-medium border<?= $isActive ? ' active' : '' ?>">Novidades</a>
<?php endif; ?>
<?php if ($mostraMaisPedidos): ?>
  <?php $isActive = !$firstActiveAssigned; if ($isActive) { $firstActiveAssigned = true; } ?>
  <a href="#mais-pedidos" class="category-tab shrink-0 px-4 py-1.5 rounded-full font-medium border<?= $isActive ? ' active' : '' ?>">🔥 Mais Pedidos</a>
<?php endif; ?>
<?php foreach ($categories as $c): ?>
  <?php
    $itemsForTab = array_values(array_filter($products, fn($p) => (int)($p['category_id'] ?? 0) === (int)$c['id']));
    if (!$itemsForTab) {
        continue;
    }
    $isActive = !$firstActiveAssigned;
    if ($isActive) {
        $firstActiveAssigned = true;
    }
  ?>
  <a href="#cat-<?= (int)$c['id'] ?>" class="category-tab shrink-0 px-4 py-1.5 rounded-full font-medium border<?= $isActive ? ' active' : '' ?>"><?= e($c['name'] ?? 'Categoria') ?></a>
<?php endforeach; ?>
</div>
