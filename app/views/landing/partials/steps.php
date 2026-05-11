<section id="demo" class="py-20 lg:py-28 gradient-feature relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center max-w-3xl mx-auto mb-16">
      <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-indigo-50 rounded-full text-indigo-600 text-sm font-semibold mb-4">
        🚀 Como Funciona
      </div>
      <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900 leading-tight">
        Do zero ao online em <span class="text-indigo-600">3 passos</span>
      </h2>
    </div>

    <div class="max-w-2xl mx-auto">
      <?php foreach ($steps as $i => $step): ?>
      <div class="feature-step relative pl-16 pb-12 scroll-reveal">
        <div class="absolute left-0 top-0 w-14 h-14 rounded-2xl bg-indigo-600 text-white font-bold text-xl flex items-center justify-center shadow-lg shadow-indigo-200"><?= $i + 1 ?></div>
        <h3 class="font-bold text-gray-900 text-xl pt-3"><?= e($step['title']) ?></h3>
        <p class="mt-2 text-gray-500 leading-relaxed"><?= e($step['description']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


