<section id="integracoes" class="py-20 lg:py-28 bg-white relative">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center max-w-3xl mx-auto mb-16">
      <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-indigo-50 rounded-full text-indigo-600 text-sm font-semibold mb-4">
        🔗 Integrações
      </div>
      <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900 leading-tight">
        Conecte-se ao <span class="text-indigo-600">ecossistema</span> que já usa
      </h2>
      <p class="mt-6 text-lg text-gray-500">
        Integrações nativas que funcionam de verdade, sem gambiarras.
      </p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
      <?php foreach ($integrations as $int): ?>
      <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm hover:shadow-xl hover:border-indigo-100 transition-all scroll-reveal">
        <div class="text-5xl mb-4"><?= e($int['emoji']) ?></div>
        <h3 class="font-bold text-gray-900 text-xl mb-1"><?= e($int['name']) ?></h3>
        <p class="text-indigo-500 text-xs font-semibold mb-3"><?= e($int['subtitle']) ?></p>
        <p class="text-gray-500 text-sm leading-relaxed mb-4"><?= e($int['description']) ?></p>
        <div class="flex flex-wrap gap-2">
          <?php foreach ($int['badges'] as $badge): ?>
          <span class="px-3 py-1 bg-indigo-50 text-indigo-600 text-xs font-semibold rounded-full"><?= e($badge) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


