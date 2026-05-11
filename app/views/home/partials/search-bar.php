<form method="get" action="<?= e(base_url(rawurlencode((string)$company['slug']).'/buscar')) ?>" class="mb-4" data-search-url="<?= e(base_url(rawurlencode((string)$company['slug']).'/buscar')) ?>">
  <input type="text" name="q" value="<?= e($q) ?>" placeholder="Digite para buscar um item" class="w-full border rounded-xl px-3 py-2" />
</form>

<div id="search-results" class="mb-4">
  <?php if ($q !== ''): ?>
    <h2 class="text-xl font-bold mb-2">Resultado da busca</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-3">
      <?php if (!$searchResults): ?>
        <div class="p-4 border bg-white rounded-xl sm:col-span-2">Nada encontrado para <strong><?= e($q) ?></strong>.</div>
      <?php endif; ?>
      <?php foreach ($searchResults as $p): if ($_partialCardExists) { include $_partialCard; } endforeach; ?>
    </div>
  <?php endif; ?>
</div>
