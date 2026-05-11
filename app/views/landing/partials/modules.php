<section id="modulos" class="py-20 lg:py-28 gradient-dark text-white relative overflow-hidden">
  <div class="blob-1 top-0 -left-40 opacity-20"></div>
  <div class="blob-2 bottom-0 right-0 opacity-10"></div>
  <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImciIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PGNpcmNsZSBjeD0iMzAiIGN5PSIzMCIgcj0iMS4yIiBmaWxsPSJyZ2JhKDI1NSwyNTUsMjU1LDAuMDUpIi8+PC9wYXR0ZXJuPjwvZGVmcz48cmVjdCBmaWxsPSJ1cmwoI2cpIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIi8+PC9zdmc+')] opacity-60"></div>

  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center max-w-3xl mx-auto mb-16">
      <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-white/10 rounded-full text-indigo-300 text-sm font-semibold mb-4 border border-white/10">
        🧱 Módulos
      </div>
      <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-tight">
        <?= e($totalModules) ?> módulos profissionais <br class="hidden lg:block"><span class="text-amber-400">prontos para usar</span>
      </h2>
      <p class="mt-6 text-lg text-indigo-200/80">
        Cada módulo foi projetado para resolver um problema real de restaurantes.
      </p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
      <?php foreach ($modules as $mod): ?>
      <div class="module-card bg-white/5 backdrop-blur-sm rounded-2xl p-5 border border-white/10 hover:bg-white/10 hover:border-indigo-400/30 scroll-reveal">
        <div class="text-3xl mb-3"><?= e($mod['emoji']) ?></div>
        <h3 class="font-bold text-white text-base"><?= e($mod['name']) ?></h3>
        <p class="mt-1.5 text-indigo-200/60 text-sm leading-relaxed"><?= e($mod['desc']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Tech stack badges -->
    <div class="mt-16 text-center">
      <p class="text-sm text-indigo-300/60 font-medium uppercase tracking-wider mb-6">Stack Tecnológico</p>
      <div class="flex flex-wrap justify-center gap-3">
        <?php foreach ($techStack as $tech): ?>
        <span class="px-4 py-2 bg-white/5 border border-white/10 rounded-full text-sm text-indigo-200/80 font-medium"><?= e($tech) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>


