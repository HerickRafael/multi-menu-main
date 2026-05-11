<section id="depoimentos" class="py-20 lg:py-28 bg-white relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center max-w-3xl mx-auto mb-16">
      <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-amber-50 rounded-full text-amber-700 text-sm font-semibold mb-4">
        ⭐ Depoimentos
      </div>
      <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900 leading-tight">
        Quem usa, <span class="text-indigo-600">recomenda</span>
      </h2>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
      <?php foreach ($testimonials as $t): ?>
      <div class="testimonial-card rounded-2xl p-6 border border-gray-100 shadow-sm scroll-reveal">
        <div class="flex items-center gap-1 mb-4">
          <?php for ($s = 0; $s < 5; $s++): ?>
          <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
          <?php endfor; ?>
        </div>
        <p class="text-gray-600 text-sm leading-relaxed italic mb-4">&ldquo;<?= e($t['text']) ?>&rdquo;</p>
        <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
          <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white font-bold text-sm"><?= e(mb_substr($t['name'], 0, 1)) ?></div>
          <div>
            <div class="font-semibold text-gray-900 text-sm"><?= e($t['name']) ?></div>
            <div class="text-xs text-gray-400"><?= e($t['role']) ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

