<div id="hours-modal" class="fixed inset-0 bg-black/50 hidden z-50" role="dialog" aria-modal="true" aria-labelledby="hours-modal-title">
  <div class="bg-white max-w-md mx-auto mt-16 rounded-2xl overflow-hidden shadow-xl">
    <div class="p-4">
      <div class="flex items-center mb-1">
        <div id="hours-modal-title" class="font-semibold flex items-center gap-2">
          <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-yellow-400 text-black" aria-hidden="true">i</span>
          Horarios de Funcionamento
        </div>
        <button id="hours-close" class="ml-auto px-3 py-1.5 rounded-xl border" aria-label="Fechar horarios de funcionamento">Fechar</button>
      </div>

      <?php if (!empty($company['avg_delivery_min_from']) && !empty($company['avg_delivery_min_to'])): ?>
        <div class="text-sm text-gray-600">
          Tempo medio delivery: <?= (int)$company['avg_delivery_min_from'] ?> - <?= (int)$company['avg_delivery_min_to'] ?> minutos
        </div>
      <?php endif; ?>
    </div>

    <div class="px-4 pb-4">
      <table class="w-full border-collapse">
        <tbody>
        <?php
          $names = [1 => 'Segunda',2 => 'Terca',3 => 'Quarta',4 => 'Quinta',5 => 'Sexta',6 => 'Sabado',7 => 'Domingo'];
          foreach ($names as $d => $nm):
            $r = $hours[$d] ?? null;
            $txt = 'Fechado';
            if ($r && !empty($r['is_open']) && !empty($r['open1']) && !empty($r['close1'])) {
                $txt = substr((string)$r['open1'], 0, 5) . ' - ' . substr((string)$r['close1'], 0, 5);
                if (!empty($r['open2']) && !empty($r['close2'])) {
                    $txt .= ' / ' . substr((string)$r['open2'], 0, 5) . ' - ' . substr((string)$r['close2'], 0, 5);
                }
            }
            $rowClass = ((int)date('N') === $d) ? 'border-t border-b bg-yellow-50' : 'border-b';
        ?>
          <tr class="<?= $rowClass ?> border-gray-300">
            <td class="py-2 font-medium <?= ((int)date('N') === $d) ? 'text-black' : 'text-gray-700' ?>"><?= $nm ?></td>
            <td class="py-2 text-right"><?= e($txt) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
