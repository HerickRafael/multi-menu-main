<section id="funcionalidades" class="py-20 lg:py-28 bg-white relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center max-w-3xl mx-auto mb-16">
      <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-indigo-50 rounded-full text-indigo-600 text-sm font-semibold mb-4">
        ⚡ Funcionalidades
      </div>
      <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900 leading-tight">
        Tudo que seu restaurante precisa, <br class="hidden lg:block"><span class="text-indigo-600">em um só lugar</span>
      </h2>
      <p class="mt-6 text-lg text-gray-500">
        <?= e($totalFeatures) ?> funcionalidades prontas para usar. Zero complicação.
      </p>
    </div>

    <!-- Feature Tabs Navigation -->
    <div class="flex flex-wrap justify-center gap-3 mb-12" role="tablist" aria-label="Funcionalidades">
      <?php $tabIndex = 0; foreach ($featureTabs as $tabKey => $tab): ?>
      <button class="tab-btn flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold transition-all border border-gray-200 hover:border-indigo-200 <?= $tabIndex === 0 ? 'active' : 'bg-white text-gray-600' ?>"
              role="tab"
              id="tab-btn-<?= e($tabKey) ?>"
              aria-selected="<?= $tabIndex === 0 ? 'true' : 'false' ?>"
              aria-controls="tab-<?= e($tabKey) ?>"
              data-tab="<?= e($tabKey) ?>">
        <?= e($tab['icon']) ?>
        <?= e($tab['label']) ?>
      </button>
      <?php $tabIndex++; endforeach; ?>
    </div>

    <!-- Feature Tabs Content -->
    <?php $tabIndex = 0; foreach ($featureTabs as $tabKey => $tab): ?>
    <div class="tab-content <?= $tabIndex === 0 ? 'active' : '' ?>" id="tab-<?= e($tabKey) ?>" role="tabpanel" aria-labelledby="tab-btn-<?= e($tabKey) ?>">
      <div class="grid lg:grid-cols-3 gap-8">
        <?php foreach ($tab['items'] as $item): ?>
        <div class="group bg-white rounded-2xl p-6 lg:p-8 border border-gray-100 hover:border-indigo-200 hover:shadow-xl hover:shadow-indigo-50 transition-all duration-300">
          <!-- Mockup indicator -->
          <div class="flex items-center gap-2 mb-4">
            <?php if (($item['mockup'] ?? '') === 'phone'): ?>
            <span class="px-2.5 py-1 bg-indigo-50 text-indigo-600 text-[10px] font-bold rounded-full uppercase tracking-wider">📱 Mobile</span>
            <?php else: ?>
            <span class="px-2.5 py-1 bg-gray-100 text-gray-500 text-[10px] font-bold rounded-full uppercase tracking-wider">🖥️ Desktop</span>
            <?php endif; ?>
          </div>
          <h3 class="font-bold text-gray-900 text-xl group-hover:text-indigo-600 transition-colors"><?= e($item['title']) ?></h3>
          <p class="mt-3 text-gray-500 text-sm leading-relaxed"><?= e($item['description']) ?></p>
          <?php if (!empty($item['highlights'])): ?>
          <div class="mt-4 flex flex-wrap gap-2">
            <?php foreach ($item['highlights'] as $hl): ?>
            <span class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 rounded-lg text-xs text-gray-600 font-medium">
              <svg class="w-3 h-3 text-indigo-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
              <?= e($hl) ?>
            </span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php $tabIndex++; endforeach; ?>

    <!-- Quick feature grid below tabs -->
    <div class="mt-20 pt-16 border-t border-gray-100">
      <h3 class="text-center text-2xl font-extrabold text-gray-900 mb-10">Todas as funcionalidades em um olhar</h3>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
        <?php foreach ($features as $i => $feat): ?>
        <div class="scroll-reveal group bg-white rounded-2xl p-6 border border-gray-100 hover:border-indigo-200 hover:shadow-xl hover:shadow-indigo-50 transition-all duration-300">
          <div class="feature-icon <?= e($feat['iconBg']) ?> mb-4">
            <?= e($feat['icon']) ?>
          </div>
          <h3 class="font-bold text-gray-900 text-lg group-hover:text-indigo-600 transition-colors"><?= e($feat['title']) ?></h3>
          <p class="mt-2 text-gray-500 text-sm leading-relaxed"><?= e($feat['description']) ?></p>
          <?php if (!empty($feat['highlights'])): ?>
          <ul class="mt-3 space-y-1.5">
            <?php foreach ($feat['highlights'] as $hl): ?>
            <li class="flex items-center gap-2 text-xs text-gray-500">
              <svg class="w-3.5 h-3.5 text-indigo-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
              <?= e($hl) ?>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>


