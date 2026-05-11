<section class="py-20 lg:py-24 gradient-feature relative">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center max-w-3xl mx-auto mb-12">
      <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-green-50 rounded-full text-green-700 text-sm font-semibold mb-4">
        🔒 Segurança
      </div>
      <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 leading-tight">
        Segurança de <span class="text-green-600">nível enterprise</span>
      </h2>
      <p class="mt-4 text-lg text-gray-500">
        <?= e($totalMiddlewares) ?> camadas de segurança OWASP. Seus dados e dos seus clientes protegidos.
      </p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <?php foreach ($securityFeatures as $sf): ?>
      <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm text-center scroll-reveal">
        <div class="text-2xl mb-2"><?= e($sf['emoji']) ?></div>
        <h4 class="font-bold text-gray-900 text-sm"><?= e($sf['title']) ?></h4>
        <p class="mt-1 text-gray-400 text-xs"><?= e($sf['desc']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


