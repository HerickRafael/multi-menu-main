<section class="py-20 lg:py-28 bg-white relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center max-w-3xl mx-auto mb-16">
      <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-indigo-50 rounded-full text-indigo-600 text-sm font-semibold mb-4">
        📊 Comparativo
      </div>
      <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900 leading-tight">
        MultiMenu vs <span class="text-red-500">Concorrência</span>
      </h2>
      <p class="mt-6 text-lg text-gray-500">Veja porque restaurantes estão migrando para o MultiMenu.</p>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-gray-200 shadow-sm scroll-reveal">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-gray-50">
            <?php foreach ($competitors['headers'] as $hi => $header): ?>
            <th class="px-4 py-4 text-left font-semibold text-gray-700 whitespace-nowrap <?= $hi === 1 ? 'bg-indigo-50 text-indigo-700' : '' ?>">
              <?= e($header) ?>
            </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php foreach ($competitors['rows'] as $row): ?>
          <tr class="hover:bg-gray-50/50 transition-colors">
            <td class="px-4 py-3.5 font-medium text-gray-800 whitespace-nowrap"><?= e($row[0]) ?></td>
            <?php for ($ci = 1; $ci <= 5; $ci++): ?>
            <td class="px-4 py-3.5 <?= $ci === 1 ? 'bg-indigo-50/50' : '' ?>">
              <?php $val = $row[$ci] ?? 'none'; ?>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold compare-<?= e($val) ?>">
                <?php if ($val === 'full'): ?>
                  <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                <?php elseif ($val === 'partial'): ?>
                  <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                <?php else: ?>
                  <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                <?php endif; ?>
                <?= e($competitors['labels'][$val] ?? '') ?>
              </span>
            </td>
            <?php endfor; ?>
          </tr>
          <?php endforeach; ?>
          <!-- Cost row -->
          <tr class="bg-gray-50 font-semibold">
            <td class="px-4 py-4 text-gray-800">Custo</td>
            <?php $costKeys = ['MultiMenu', 'iFood', 'Rappi', 'Cardápio PDF', 'Planilha']; ?>
            <?php foreach ($costKeys as $ci => $key): ?>
            <td class="px-4 py-4 <?= $ci === 0 ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600' ?> text-xs">
              <?= e($competitors['costs'][$key] ?? '') ?>
            </td>
            <?php endforeach; ?>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</section>


