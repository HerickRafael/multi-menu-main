<section class="py-20 lg:py-28 bg-white relative">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center max-w-3xl mx-auto mb-16">
      <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-indigo-50 rounded-full text-indigo-600 text-sm font-semibold mb-4">
        📈 Evolução
      </div>
      <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900 leading-tight">
        Plataforma em <span class="text-indigo-600">constante evolução</span>
      </h2>
      <p class="mt-6 text-lg text-gray-500">Novas funcionalidades a cada trimestre.</p>
    </div>

    <div class="relative pl-8">
      <div class="timeline-line"></div>
      <?php foreach ($timeline as $ti => $t): ?>
      <div class="relative pl-10 pb-10 last:pb-0 scroll-reveal">
        <div class="timeline-dot <?= $t['done'] ? 'done' : '' ?>"></div>
        <div class="flex items-center gap-3 mb-2">
          <span class="px-3 py-1 rounded-full text-xs font-bold <?= $t['done'] ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500' ?>"><?= e($t['quarter']) ?></span>
          <h3 class="font-bold text-gray-900"><?= e($t['title']) ?></h3>
          <?php if (!$t['done']): ?>
          <span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-[10px] font-bold rounded-full">Em breve</span>
          <?php endif; ?>
        </div>
        <div class="flex flex-wrap gap-2">
          <?php foreach ($t['items'] as $item): ?>
          <span class="px-3 py-1.5 rounded-lg text-xs font-medium <?= $t['done'] ? 'bg-indigo-50 text-indigo-600' : 'bg-gray-50 text-gray-400' ?>"><?= e($item) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


