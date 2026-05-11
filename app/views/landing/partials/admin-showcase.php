<section class="py-20 lg:py-28 bg-white relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
      <!-- Left: Info -->
      <div>
        <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-indigo-50 rounded-full text-indigo-600 text-sm font-semibold mb-4">
          💻 Painel Admin
        </div>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 leading-tight">
          Gerencie tudo do <span class="text-indigo-600">celular ou computador</span>
        </h2>
        <p class="mt-4 text-lg text-gray-500 leading-relaxed">
          Painel completo com <?= e($totalControllers) ?> telas, versão desktop e mobile (PWA), acesse de qualquer lugar.
        </p>

        <div class="mt-8 space-y-4">
          <?php foreach ($adminHighlights as $ah): ?>
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-lg flex-shrink-0"><?= e($ah['emoji']) ?></div>
            <div>
              <h4 class="font-semibold text-gray-900"><?= e($ah['title']) ?></h4>
              <p class="text-sm text-gray-500 mt-0.5"><?= e($ah['desc']) ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Right: Dashboard mockup -->
      <div class="relative scroll-reveal">
        <div class="bg-gray-900 rounded-2xl shadow-2xl overflow-hidden glow-sm">
          <!-- Titlebar -->
          <div class="bg-gray-800 px-4 py-3 flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-red-500"></div>
            <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
            <div class="w-3 h-3 rounded-full bg-green-500"></div>
            <span class="ml-3 text-xs text-gray-500 font-mono">admin.multimenu.com.br</span>
          </div>
          <!-- Dashboard Content -->
          <div class="p-6 bg-gradient-to-br from-gray-50 to-indigo-50/50">
            <!-- Stats Row -->
            <div class="grid grid-cols-4 gap-3 mb-5">
              <div class="bg-white rounded-xl p-3 shadow-sm border border-gray-100">
                <div class="text-xs text-gray-400 font-medium">Hoje</div>
                <div class="text-lg font-bold text-gray-900">R$ 3.240</div>
                <div class="text-[10px] text-green-500 font-semibold">▲ 12%</div>
              </div>
              <div class="bg-white rounded-xl p-3 shadow-sm border border-gray-100">
                <div class="text-xs text-gray-400 font-medium">Pedidos</div>
                <div class="text-lg font-bold text-gray-900">47</div>
                <div class="text-[10px] text-green-500 font-semibold">▲ 8%</div>
              </div>
              <div class="bg-white rounded-xl p-3 shadow-sm border border-gray-100">
                <div class="text-xs text-gray-400 font-medium">Ticket Médio</div>
                <div class="text-lg font-bold text-gray-900">R$ 68,90</div>
                <div class="text-[10px] text-green-500 font-semibold">▲ 5%</div>
              </div>
              <div class="bg-white rounded-xl p-3 shadow-sm border border-gray-100">
                <div class="text-xs text-gray-400 font-medium">Clientes</div>
                <div class="text-lg font-bold text-gray-900">312</div>
                <div class="text-[10px] text-indigo-500 font-semibold">+23 novos</div>
              </div>
            </div>
            <!-- Chart + Orders mockup -->
            <div class="grid grid-cols-5 gap-3">
              <div class="col-span-3 bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-3">
                  <span class="text-sm font-semibold text-gray-700">Faturamento Semanal</span>
                  <span class="text-[10px] px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded-full font-semibold">Esta Semana</span>
                </div>
                <!-- Chart bars -->
                <div class="flex items-end gap-1.5 h-24">
                  <div class="flex-1 bg-indigo-100 rounded-t" style="height:40%"></div>
                  <div class="flex-1 bg-indigo-200 rounded-t" style="height:55%"></div>
                  <div class="flex-1 bg-indigo-300 rounded-t" style="height:70%"></div>
                  <div class="flex-1 bg-indigo-200 rounded-t" style="height:45%"></div>
                  <div class="flex-1 bg-indigo-400 rounded-t" style="height:85%"></div>
                  <div class="flex-1 bg-indigo-500 rounded-t" style="height:95%"></div>
                  <div class="flex-1 bg-indigo-600 rounded-t" style="height:100%"></div>
                </div>
                <div class="flex justify-between mt-1.5">
                  <span class="text-[9px] text-gray-400">Seg</span>
                  <span class="text-[9px] text-gray-400">Ter</span>
                  <span class="text-[9px] text-gray-400">Qua</span>
                  <span class="text-[9px] text-gray-400">Qui</span>
                  <span class="text-[9px] text-gray-400">Sex</span>
                  <span class="text-[9px] text-gray-400">Sáb</span>
                  <span class="text-[9px] text-gray-400 font-bold text-indigo-600">Dom</span>
                </div>
              </div>
              <div class="col-span-2 bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                <span class="text-sm font-semibold text-gray-700">Pedidos Recentes</span>
                <div class="mt-3 space-y-2.5">
                  <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-yellow-400"></div>
                    <span class="text-xs text-gray-700 flex-1">#1247</span>
                    <span class="text-[10px] text-yellow-600 font-semibold bg-yellow-50 px-1.5 py-0.5 rounded">Preparando</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-blue-400"></div>
                    <span class="text-xs text-gray-700 flex-1">#1246</span>
                    <span class="text-[10px] text-blue-600 font-semibold bg-blue-50 px-1.5 py-0.5 rounded">Enviado</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-green-400"></div>
                    <span class="text-xs text-gray-700 flex-1">#1245</span>
                    <span class="text-[10px] text-green-600 font-semibold bg-green-50 px-1.5 py-0.5 rounded">Entregue</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-green-400"></div>
                    <span class="text-xs text-gray-700 flex-1">#1244</span>
                    <span class="text-[10px] text-green-600 font-semibold bg-green-50 px-1.5 py-0.5 rounded">Entregue</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>


