<section class="py-20 lg:py-28 gradient-feature relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center max-w-3xl mx-auto mb-16">
      <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-amber-50 rounded-full text-amber-700 text-sm font-semibold mb-4">
        🏆 Cases de Sucesso
      </div>
      <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900 leading-tight">
        Resultados <span class="text-indigo-600">reais</span> de quem usa
      </h2>
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
      <?php foreach ($cases as $case): ?>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden scroll-reveal">
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-5">
          <h3 class="font-bold text-white text-lg"><?= e($case['name']) ?></h3>
          <p class="text-indigo-200 text-sm"><?= e($case['city']) ?> — Plano <?= e($case['plan']) ?></p>
        </div>
        <!-- Metrics -->
        <div class="grid grid-cols-3 divide-x divide-gray-100 border-b border-gray-100">
          <?php foreach ($case['metrics'] as $m): ?>
          <div class="px-4 py-4 text-center">
            <div class="text-lg font-extrabold text-indigo-600"><?= e($m['value']) ?></div>
            <div class="text-[10px] text-gray-400 mt-0.5 leading-tight"><?= e($m['label']) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <!-- Quote -->
        <div class="px-6 py-5">
          <p class="text-sm text-gray-600 italic leading-relaxed">&ldquo;<?= e($case['quote']) ?>&rdquo;</p>
          <p class="mt-3 text-xs text-gray-400 font-semibold"><?= e($case['owner']) ?>, <?= e($case['name']) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


