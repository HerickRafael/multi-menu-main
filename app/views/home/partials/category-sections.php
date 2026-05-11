<?php if ($mostraNovidade): ?>
  <a id="novidades"></a>
  <h2 class="menu-group-title text-xl font-bold inline-block px-3 py-1 rounded-lg mb-2">Novidades</h2>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-3 mb-6">
    <?php foreach ($novidades as $p): ?>
      <?php if ($_partialCardExists) { include $_partialCard; } ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($mostraMaisPedidos): ?>
  <a id="mais-pedidos"></a>
  <h2 class="menu-group-title text-xl font-bold inline-block px-3 py-1 rounded-lg mb-2">🔥 Mais Pedidos</h2>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-3 mb-6">
    <?php foreach ($maisPedidos as $p): ?>
      <?php if ($_partialCardExists) { include $_partialCard; } ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<a id="topo"></a>

<?php foreach ($categories as $c): ?>
  <?php
    $items = array_values(array_filter($products, fn ($p) => (int)($p['category_id'] ?? 0) === (int)$c['id']));
    if (!$items) {
        continue;
    }
  ?>
  <a id="cat-<?= (int)$c['id'] ?>"></a>
  <h2 class="menu-group-title text-xl font-bold inline-block px-3 py-1 rounded-lg mb-2">
    <?= e($c['name'] ?? 'Categoria') ?>
  </h2>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-3 mb-6">
    <?php foreach ($items as $p): if ($_partialCardExists) { include $_partialCard; } endforeach; ?>
  </div>
<?php endforeach; ?>
