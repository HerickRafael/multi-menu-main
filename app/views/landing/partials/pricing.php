<section id="precos" class="py-20 lg:py-28 gradient-dark text-white relative overflow-hidden">
  <div class="blob-1 top-0 right-0 opacity-20"></div>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
    <div class="text-center max-w-3xl mx-auto mb-16">
      <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-white/10 rounded-full text-indigo-300 text-sm font-semibold mb-4 border border-white/10">
        💰 Preços
      </div>
      <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-tight">
        Planos que cabem no seu <span class="text-amber-400">bolso</span>
      </h2>
      <p class="mt-6 text-lg text-indigo-200/80">
        Comece gratuitamente. Escale quando quiser. Sem taxa por pedido.
      </p>
    </div>

    <div class="grid md:grid-cols-3 gap-6 lg:gap-8 max-w-5xl mx-auto items-start">
      <?php foreach ($pricing as $key => $plan): ?>
      <div class="<?= !empty($plan['popular']) ? 'pricing-popular' : '' ?>">
        <?php if (!empty($plan['popular'])): ?>
        <div class="bg-amber-400 text-gray-900 text-xs font-bold text-center py-2 rounded-t-2xl uppercase tracking-wider">
          ⭐ Mais Popular
        </div>
        <?php endif; ?>
        <div class="bg-white/5 backdrop-blur-sm <?= !empty($plan['popular']) ? 'rounded-b-2xl border-2 border-amber-400/50' : 'rounded-2xl border border-white/10' ?> p-8">
          <h3 class="text-xl font-bold text-white"><?= e($plan['name']) ?></h3>
          <div class="mt-4 flex items-baseline gap-1">
            <span class="text-sm text-indigo-300">R$</span>
            <span class="text-5xl font-extrabold text-white"><?= e((string)$plan['price']) ?></span>
            <span class="text-indigo-300"><?= e($plan['period']) ?></span>
          </div>

          <a href="https://wa.me/<?= e($whatsappNumber) ?>?text=<?= rawurlencode('Olá! Quero contratar o plano ' . $plan['name'] . ' do MultiMenu.') ?>" 
             target="_blank" rel="noopener noreferrer"
             class="mt-6 block w-full py-3.5 text-center font-bold text-sm rounded-xl transition-all <?= !empty($plan['popular']) ? 'bg-amber-400 hover:bg-amber-300 text-gray-900 shadow-xl shadow-amber-400/20' : 'bg-white/10 hover:bg-white/20 text-white border border-white/20' ?>">
            Começar Agora
          </a>

          <ul class="mt-8 space-y-3">
            <?php foreach ($plan['features'] as $feature): ?>
            <li class="flex items-start gap-3 text-sm">
              <svg class="w-4 h-4 text-green-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
              <span class="text-indigo-100/80"><?= e($feature) ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Money back guarantee -->
    <div class="mt-12 text-center">
      <div class="inline-flex items-center gap-3 px-6 py-3 bg-white/5 rounded-full border border-white/10">
        <svg class="w-6 h-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <span class="text-indigo-100/80 text-sm">Garantia de 7 dias — se não gostar, devolvemos 100% do valor.</span>
      </div>
    </div>
  </div>
</section>


