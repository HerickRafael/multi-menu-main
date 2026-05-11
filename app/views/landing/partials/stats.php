<section class="py-16 bg-white relative">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
      <?php foreach ($stats as $stat): ?>
      <div class="text-center scroll-reveal">
        <div class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-indigo-600">
          <span class="counter" data-target="<?= e((string)$stat['value']) ?>"><?= e((string)$stat['value']) ?></span><?= e($stat['suffix']) ?>
        </div>
        <div class="mt-2 text-sm sm:text-base text-gray-500 font-medium"><?= e($stat['label']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Trusted logos -->
    <div class="mt-14 text-center">
      <p class="text-sm text-gray-400 font-medium uppercase tracking-wider mb-6">Confiado por restaurantes de todos os portes</p>
      <div class="flex flex-wrap items-center justify-center gap-8 lg:gap-12 opacity-40">
        <div class="text-2xl font-bold text-gray-400 tracking-tight">Burger<span class="text-gray-300">House</span></div>
        <div class="text-2xl font-bold text-gray-400 tracking-tight">Pizza<span class="text-gray-300">Express</span></div>
        <div class="text-2xl font-bold text-gray-400 tracking-tight">Açaí<span class="text-gray-300">Mania</span></div>
        <div class="text-2xl font-bold text-gray-400 tracking-tight">Sushi<span class="text-gray-300">Premium</span></div>
        <div class="text-2xl font-bold text-gray-400 tracking-tight">Padaria<span class="text-gray-300">Nova</span></div>
      </div>
    </div>
  </div>
</section>


