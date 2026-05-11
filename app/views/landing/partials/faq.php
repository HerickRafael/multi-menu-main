<section class="py-20 lg:py-28 bg-white relative">
  <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
      <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-indigo-50 rounded-full text-indigo-600 text-sm font-semibold mb-4">
        ❓ FAQ
      </div>
      <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900">Perguntas Frequentes</h2>
    </div>

    <div class="space-y-4">
      <?php foreach ($faq as $q): ?>
      <details class="group bg-gray-50 rounded-2xl border border-gray-100" role="group">
        <summary class="flex items-center justify-between p-5 cursor-pointer list-none" aria-expanded="false">
          <span class="font-semibold text-gray-900 text-sm pr-8"><?= e($q['question']) ?></span>
          <svg class="w-5 h-5 text-gray-400 flex-shrink-0 group-open:rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </summary>
        <div class="px-5 pb-5 text-sm text-gray-500 leading-relaxed border-t border-gray-100 pt-4">
          <?= e($q['answer']) ?>
        </div>
      </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>


