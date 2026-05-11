<section class="py-20 lg:py-28 gradient-feature relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center max-w-3xl mx-auto mb-16">
      <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-green-50 rounded-full text-green-700 text-sm font-semibold mb-4">
        💰 Calculadora de Economia
      </div>
      <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900 leading-tight">
        Quanto você <span class="text-green-600">economiza</span> com o MultiMenu?
      </h2>
    </div>

    <div class="max-w-2xl mx-auto scroll-reveal">
      <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 lg:p-10">
        <!-- Faturamento slider -->
        <div class="mb-8">
          <div class="flex items-center justify-between mb-2">
            <label class="text-sm font-semibold text-gray-700">Faturamento mensal no marketplace</label>
            <div class="flex items-center gap-2">
              <span id="roi-max-label" class="text-xs text-indigo-400 font-semibold hidden" aria-live="polite">Máximo</span>
              <span id="roi-faturamento-val" class="text-lg font-bold text-indigo-600">R$ <?= number_format($roiDefaults['faturamentoMensal'], 0, ',', '.') ?></span>
            </div>
          </div>
          <input type="range" id="roi-faturamento" min="5000" max="200000" step="1000" value="<?= e((string)$roiDefaults['faturamentoMensal']) ?>"
                 class="w-full h-2 bg-indigo-100 rounded-lg appearance-none cursor-pointer accent-indigo-600">
          <div class="flex justify-between text-xs text-gray-400 mt-1">
            <span>R$ 5.000</span>
            <span>R$ 200.000</span>
          </div>
        </div>

        <!-- Taxa slider -->
        <div class="mb-8">
          <div class="flex items-center justify-between mb-2">
            <label class="text-sm font-semibold text-gray-700">Taxa do marketplace (%)</label>
            <span id="roi-taxa-val" class="text-lg font-bold text-red-500"><?= e((string)$roiDefaults['taxaMarketplace']) ?>%</span>
          </div>
          <input type="range" id="roi-taxa" min="10" max="35" step="1" value="<?= e((string)$roiDefaults['taxaMarketplace']) ?>"
                 class="w-full h-2 bg-red-100 rounded-lg appearance-none cursor-pointer accent-red-500">
          <div class="flex justify-between text-xs text-gray-400 mt-1">
            <span>10%</span>
            <span>35%</span>
          </div>
        </div>

        <!-- Resultado -->
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-6 border border-green-100">
          <div class="grid grid-cols-3 gap-4 text-center">
            <div>
              <div class="text-xs text-gray-500 font-medium mb-1">Gasto no Marketplace</div>
              <div id="roi-marketplace" class="text-xl font-extrabold text-red-500">R$ 8.100</div>
              <div class="text-[10px] text-gray-400">por mês</div>
            </div>
            <div>
              <div class="text-xs text-gray-500 font-medium mb-1">Custo MultiMenu</div>
              <div id="roi-multimenu" class="text-xl font-extrabold text-indigo-600">R$ 197</div>
              <div class="text-[10px] text-gray-400">plano fixo</div>
            </div>
            <div>
              <div class="text-xs text-gray-500 font-medium mb-1">Economia Mensal</div>
              <div id="roi-economia" class="text-2xl font-extrabold text-green-600">R$ 7.903</div>
              <div class="text-[10px] text-gray-400">no seu bolso</div>
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-green-200 text-center">
            <span class="text-sm text-gray-600">Economia anual estimada: </span>
            <span id="roi-anual" class="text-lg font-extrabold text-green-700">R$ 94.836</span>
          </div>
        </div>

        <a href="#precos" class="mt-6 block w-full py-3.5 text-center bg-green-600 hover:bg-green-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-green-200 transition-all">
          Começar a Economizar Agora →
        </a>
      </div>
    </div>
  </div>
</section>


