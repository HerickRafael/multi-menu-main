<?php
// admin/orders/show.php — Detalhe do pedido (versão moderna)

$orderNumber = $order['order_number'] ?? $order['id'] ?? 0;
$title = 'Pedido #' . $orderNumber;
$o     = $order ?? [];
$slug  = rawurlencode((string)($activeSlug ?? ($company['slug'] ?? '')));

// variáveis de pagamento (passadas pelo controller)
$pmType         = $paymentMethodType ?? null;
$pmMeta         = $paymentMethodMeta ?? null;
$pmInstructions = $paymentMethodInstructions ?? null;

// Resolver ícone da bandeira de cartão
$pmBrandIcon  = null;
$pmTypeLabel  = null;
if ($pmType) {
    $typeIconMap = [
        'credit'  => 'credit.svg',
        'debit'   => 'debit.svg',
        'cash'    => 'cash.svg',
        'pix'     => 'pix.svg',
        'voucher' => 'voucher.svg',
    ];
    $typeLabelMap = [
        'credit'  => 'Crédito',
        'debit'   => 'Débito',
        'cash'    => 'Dinheiro',
        'pix'     => 'Transação instantânea',
        'voucher' => 'Vale',
    ];
    $brandMapping = [
        'visa'       => 'visa.svg',
        'mastercard' => 'mastercard.svg',
        'master'     => 'mastercard.svg',
        'elo'        => 'elo.svg',
        'hipercard'  => 'hipercard.svg',
        'hiper'      => 'hipercard.svg',
        'diners'     => 'diners.svg',
        'amex'       => 'others.svg',
    ];
    $pmTypeLabel = $typeLabelMap[$pmType] ?? null;

    // 1) Preferir meta['icon'] (caminho do SVG da bandeira salvo no cadastro)
    $metaIconRaw = trim((string)($pmMeta['icon'] ?? ''));
    if ($metaIconRaw !== '') {
        // normalizar: remover 'assets/card-brands/' se presente, para montar via base_url
        $pmBrandIcon = preg_replace('#^/?assets/card-brands/#', '', $metaIconRaw);
    } else {
        // 2) Tentar pelo brand no meta
        $brand = strtolower(trim((string)($pmMeta['brand'] ?? '')));
        if ($brand && isset($brandMapping[$brand])) {
            $pmBrandIcon = $brandMapping[$brand];
        } else {
            // 3) Tentar inferir pelo nome do método de pagamento
            $nameNorm = strtolower(preg_replace('/\s+/', '', $paymentMethodName ?? ''));
            $pmBrandIcon = null;
            foreach ($brandMapping as $key => $svg) {
                if (str_contains($nameNorm, $key)) {
                    $pmBrandIcon = $svg;
                    break;
                }
            }
            // 4) Fallback: ícone genérico do tipo
            if (!$pmBrandIcon) {
                $pmBrandIcon = $typeIconMap[$pmType] ?? 'others.svg';
            }
        }
    }
}

// Parsear notes: separar observações do usuário de linhas de pagamento/troco
$userNotes         = '';
$notesInstructions = ''; // instruções de pagamento embutidas nas notas (pedidos do site)
if (!empty($o['notes'])) {
    $rawLines  = preg_split('/\r?\n/', $o['notes']);
    $userLines = [];
    foreach ($rawLines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        // Linha de pagamento: "Pagamento: X — instruções"
        if (preg_match('/^Pagamento:\s*.+?(?:\s+[—\-]\s+(.+))?$/u', $line, $pmatch)) {
            if (!empty($pmatch[1]) && empty($notesInstructions)) {
                $notesInstructions = trim($pmatch[1]);
            }
            continue; // não mostrar nas observações (está na seção de pagamento)
        }
        // Linha de troco: "Troco para: ..."
        if (preg_match('/^Troco para:/i', $line)) {
            continue; // exibido na seção de pagamento
        }
        // Prefixo "Observações: ..."
        if (preg_match('/^Observações:\s*(.+)$/si', $line, $omatch)) {
            $userLines[] = trim($omatch[1]);
            continue;
        }
        $userLines[] = $line;
    }
    $userNotes = implode("\n", $userLines);
}
// Preferir instruções da tabela (mais atualizadas) sobre as embutidas nas notas
$displayInstructions = !empty($pmInstructions) ? $pmInstructions : $notesInstructions;

// labels e cores de status
$statusLabels = [
  'pending'   => 'Novo',
  'paid'      => 'Saiu para Entrega',
  'completed' => 'Concluído',
  'canceled'  => 'Cancelado',
];
$st = (string)($o['status'] ?? 'pending');
$orderSource = $o['source'] ?? 'manual';
$canEdit = $st === 'pending' && $orderSource !== 'ifood';

// Progresso do pedido
$progressSteps = [
  ['key' => 'pending',   'label' => 'Novo'],
  ['key' => 'paid',      'label' => "Saiu p/<br>Entrega"],
  ['key' => 'completed', 'label' => 'Concluído'],
];
$progressStepOrder = ['pending' => 0, 'paid' => 1, 'completed' => 2];
$currentStepIdx = $progressStepOrder[$st] ?? -1;
$totalProgressSteps = count($progressSteps);

$orderEvents = $orderEvents ?? [];
$historyEvents = array_values(array_filter($orderEvents, static function ($event) {
    return in_array($event['event_type'] ?? '', ['order.created', 'order.updated', 'order.status_changed', 'order.canceled'], true);
}));

// util: montar link do WhatsApp se houver telefone
$wa = null;

if (!empty($o['customer_phone'])) {
    $digits = preg_replace('/\D+/', '', (string)$o['customer_phone']);

    if ($digits) {
        // Garante código do país 55 (Brasil)
        if (!str_starts_with($digits, '55')) {
            $digits = '55' . $digits;
        }
        $waText = rawurlencode('Olá! Sobre o pedido #'.(int)$orderNumber.'.');
        $wa = "https://wa.me/{$digits}?text={$waText}";
    }
}

ob_start(); ?>
<div class="admin-print-only">
  <?php
    $companyName    = trim((string)($company['name'] ?? ''));
$companyAddress = trim((string)($company['address'] ?? ''));
$companyContact = trim((string)($company['whatsapp'] ?? ($company['phone'] ?? '')));
$createdAt      = trim((string)($o['created_at'] ?? ''));
$subtotal       = (float)($o['subtotal'] ?? 0);
$deliveryFee    = (float)($o['delivery_fee'] ?? 0);
$discountValue  = (float)($o['discount'] ?? 0) + (float)($o['loyalty_discount'] ?? 0);
$totalValue     = (float)($o['total'] ?? 0);
$printTitle     = $companyName !== ''
  ? (function_exists('mb_strtoupper') ? mb_strtoupper($companyName, 'UTF-8') : strtoupper($companyName))
  : 'PEDIDO';
$printStatus    = $statusLabels[$st] ?? ucfirst($st);
$items          = is_array($o['items'] ?? null) ? $o['items'] : [];
$discountLabel  = $discountValue > 0
  ? '-R$ ' . number_format($discountValue, 2, ',', '.')
  : 'R$ 0,00';
?>
  <div class="receipt">
    <div class="receipt-header">
      <h1><?= e($printTitle) ?></h1>
      <?php if ($companyAddress !== ''): ?>
        <p class="receipt-text"><?= e($companyAddress) ?></p>
      <?php endif; ?>
      <?php if ($companyContact !== ''): ?>
        <p class="receipt-text">Contato: <?= e($companyContact) ?></p>
      <?php endif; ?>
    </div>
    <hr>
    <div class="receipt-section">
      <div class="receipt-row"><span>Pedido</span><span>#<?= (int)$orderNumber ?></span></div>
      <?php if ($createdAt !== ''): ?>
        <div class="receipt-row"><span>Data</span><span><?= e($createdAt) ?></span></div>
      <?php endif; ?>
      <div class="receipt-row"><span>Status</span><span><?= e($printStatus) ?></span></div>
    </div>
    <hr>
    <div class="receipt-section">
      <div class="receipt-label">Cliente</div>
      <div class="receipt-text"><?= e($o['customer_name'] ?? '-') ?></div>
      <?php if (!empty($o['customer_phone'])): ?>
        <div class="receipt-text">Tel: <?= e(format_phone_br($o['customer_phone'])) ?></div>
      <?php endif; ?>
      <?php if (!empty($o['customer_address'])): ?>
        <div class="receipt-text receipt-pre"><?= e($o['customer_address']) ?></div>
      <?php endif; ?>
      <?php if (!empty($o['notes'])): ?>
        <div class="receipt-text receipt-pre">Obs.: <?= e($o['notes']) ?></div>
      <?php endif; ?>
    </div>
    <hr>
    <div class="receipt-section">
      <div class="receipt-label">Itens</div>
      <table class="receipt-table">
        <?php foreach ($items as $it): ?>
          <tr>
            <td colspan="3" class="receipt-item-name"><?= e($it['product_name'] ?: ($it['notes'] ?: '-')) ?></td>
          </tr>
          <tr class="receipt-item-row">
            <td class="qty"><?= (int)($it['quantity'] ?? 0) ?>x</td>
            <td class="price">R$ <?= number_format((float)($it['unit_price'] ?? 0), 2, ',', '.') ?></td>
            <td class="total">R$ <?= number_format((float)($it['line_total'] ?? 0), 2, ',', '.') ?></td>
          </tr>
          <?php 
          // Combo
          $comboDataPrint = null;
          $componentCustomizationsPrint = [];
          if (!empty($it['combo_data'])) {
              $comboDataPrint = is_string($it['combo_data']) ? json_decode($it['combo_data'], true) : $it['combo_data'];
              // Extrair component_customizations se disponível
              if (is_array($comboDataPrint) && !empty($comboDataPrint['component_customizations'])) {
                  $componentCustomizationsPrint = $comboDataPrint['component_customizations'];
              }
          }
          if ($comboDataPrint && !empty($comboDataPrint['selected_items'])): 
          ?>
            <tr>
              <td colspan="3" class="receipt-note" style="padding-left: 1em;">
                <strong>Opções:</strong>
                <?php 
                $comboLines = [];
                foreach ($comboDataPrint['selected_items'] as $comboItem) {
                    $comboName = $comboItem['simple_name'] ?? $comboItem['name'] ?? '';
                    $simpleId = $comboItem['simple_id'] ?? 0;
                    $comboQty = isset($comboItem['qty']) ? (int)$comboItem['qty'] : (isset($comboItem['default_qty']) ? (int)$comboItem['default_qty'] : 1);
                    if ($comboQty <= 0) $comboQty = 1;
                    
                    // Verificar se há unit_customizations
                    $hasUnitCust = $simpleId > 0 && 
                        !empty($componentCustomizationsPrint[$simpleId]['unit_customizations']) &&
                        is_array($componentCustomizationsPrint[$simpleId]['unit_customizations']);
                    
                    if ($hasUnitCust && $comboQty > 1) {
                        foreach ($componentCustomizationsPrint[$simpleId]['unit_customizations'] as $unitNum => $unitCust) {
                            $unitLine = "{$comboName} ({$unitNum}º)";
                            // Adicionar customizações da unidade
                            if (!empty($unitCust['groups'])) {
                                $unitExtras = [];
                                foreach ($unitCust['groups'] as $cg) {
                                    if (!empty($cg['items'])) {
                                        foreach ($cg['items'] as $ci) {
                                            $ciName = $ci['name'] ?? '';
                                            $ciDelta = isset($ci['delta_qty']) ? (int)$ci['delta_qty'] : null;
                                            $ciDefaultQty = isset($ci['default_qty']) ? (int)$ci['default_qty'] : null;
                                            $ciQty = isset($ci['qty']) ? (int)$ci['qty'] : null;
                                            $ciRemoved = !empty($ci['removed']) || ($ciDefaultQty !== null && $ciDefaultQty > 0 && ($ciQty === 0 || $ciQty === null));
                                            
                                            if ($ciDelta === null && $ciDefaultQty !== null && $ciQty !== null) {
                                                $ciDelta = $ciQty - $ciDefaultQty;
                                            }
                                            
                                            if ($ciRemoved && $ciName) {
                                                $unitExtras[] = "Sem {$ciName}";
                                            } elseif ($ciDelta !== null && $ciDelta > 0 && $ciName) {
                                                $unitExtras[] = "+{$ciDelta}x {$ciName}";
                                            } elseif ($ciDelta !== null && $ciDelta < 0 && $ciName) {
                                                $unitExtras[] = "Sem {$ciName}";
                                            }
                                        }
                                    }
                                }
                                if ($unitExtras) {
                                    $unitLine .= ': ' . implode(', ', $unitExtras);
                                }
                            }
                            $comboLines[] = $unitLine;
                        }
                    } else {
                        $displayQty = $comboQty > 1 ? "{$comboQty}x " : "";
                        $comboLines[] = $displayQty . $comboName;
                    }
                }
                echo e(implode('; ', array_filter($comboLines)));
                ?>
              </td>
            </tr>
          <?php endif; ?>
          
          <?php 
          // Personalização
          $customDataPrint = null;
          if (!empty($it['customization_data'])) {
              $customDataPrint = is_string($it['customization_data']) ? json_decode($it['customization_data'], true) : $it['customization_data'];
          }
          if ($customDataPrint && !empty($customDataPrint['groups'])): 
              $customItemsPrint = [];
              foreach ($customDataPrint['groups'] as $group) {
                  $gTypePrint = $group['type'] ?? 'extra';
                  if (!empty($group['items'])) {
                      foreach ($group['items'] as $customItem) {
                          $itemName = $customItem['name'] ?? '';
                          $qty = isset($customItem['qty']) ? (int)$customItem['qty'] : 1;
                          $deltaQty = $customItem['delta_qty'] ?? null;
                          
                          // Pool (açaí): mostrar itens com qty > 0
                          if ($gTypePrint === 'pool') {
                              if ($itemName && $qty > 0) {
                                  $customItemsPrint[] = $qty > 1 ? "{$qty}x {$itemName}" : $itemName;
                              }
                              continue;
                          }
                          
                          if ($itemName && ($deltaQty !== 0 || in_array($gTypePrint, ['addon', 'single', 'choice']))) {
                              if ($deltaQty !== null && $deltaQty > 0) {
                                  $customItemsPrint[] = "+{$deltaQty}x {$itemName}";
                              } elseif ($deltaQty !== null && $deltaQty < 0) {
                                  $customItemsPrint[] = "Sem {$itemName}";
                              } elseif ($qty > 0 && in_array($gTypePrint, ['single', 'addon', 'choice'])) {
                                  $customItemsPrint[] = $qty > 1 ? "{$qty}x {$itemName}" : $itemName;
                              } elseif ($qty > 1) {
                                  $customItemsPrint[] = "{$qty}x {$itemName}";
                              } else {
                                  $customItemsPrint[] = $itemName;
                              }
                          }
                      }
                  }
              }
              if ($customItemsPrint):
          ?>
            <tr>
              <td colspan="3" class="receipt-note" style="padding-left: 1em;">
                <strong>Personalização:</strong> <?= e(implode(', ', $customItemsPrint)) ?>
              </td>
            </tr>
          <?php endif; endif; ?>
          
          <?php if (!empty($it['notes']) && !empty($it['product_name'])): ?>
            <tr>
              <td colspan="3" class="receipt-note receipt-pre"><?= e($it['notes']) ?></td>
            </tr>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if (empty($items)): ?>
          <tr>
            <td colspan="3" class="receipt-text">Sem itens neste pedido.</td>
          </tr>
        <?php endif; ?>
      </table>
    </div>
    <hr>
    <div class="receipt-section">
      <div class="receipt-row"><span>Subtotal</span><span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span></div>
      <div class="receipt-row"><span>Entrega</span><span>R$ <?= number_format($deliveryFee, 2, ',', '.') ?></span></div>
      <div class="receipt-row"><span>Desconto</span><span><?= $discountLabel ?></span></div>
      <div class="receipt-total"><span>Total</span><span>R$ <?= number_format($totalValue, 2, ',', '.') ?></span></div>
    </div>
    <hr>
    <div class="receipt-footer">
      <div>Nº do pedido: #<?= (int)$orderNumber ?></div>
      <div>Obrigado pela preferência!</div>
    </div>
  </div>
</div>
<?php
// Configuração do header padronizado
$pageTitle = 'Pedido #' . (int)$orderNumber;
$pageDescription = isset($statusLabels[$st]) ? $statusLabels[$st] : ucfirst($st);
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M5 7h14M7 12h10M9 17h8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
$breadcrumbs = [
    ['label' => 'Pedidos', 'url' => base_url('admin/' . $slug . '/orders')],
    ['label' => 'Detalhes #' . (int)$orderNumber]
];

$orderId = (int)($o['id'] ?? 0);
$actions = [];
if ($wa) {
    $actions[] = [
        'label' => 'WhatsApp',
        'url' => $wa,
        'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M7 20l1.5-4.5a7 7 0 1 1 2.5 2.5L7 20z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    ];
}
if ($canEdit) {
    $actions[] = [
        'label' => 'Editar',
        'url' => base_url('admin/' . $slug . '/orders/' . $orderId . '/edit'),
        'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 20h4l10-10-4-4L4 16v4Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    ];
}
$actions[] = [
    'label' => 'Imprimir',
    'url' => base_url('admin/' . $activeSlug . '/orders/print?id=' . $orderId),
    'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M7 9V4h10v5M7 14H5a2 2 0 0 1-2-2v-1a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2h-2m-10 0h10v6H7v-6Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
];
?>
<div class="mx-auto max-w-6xl p-4 admin-screen-only">
  <?php include __DIR__ . '/../components/page-header.php'; ?>

  <!-- PROGRESSO + AÇÕES -->
  <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <p class="mb-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Progresso do Pedido</p>

    <?php if ($st === 'canceled'): ?>
      <div class="mb-5 flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
          <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
          Cancelado
        </span>
        <span class="text-xs text-slate-400">Este pedido foi cancelado.</span>
      </div>
    <?php else: ?>
      <div class="relative flex items-start justify-between mb-6">
        <!-- trilho fundo -->
        <div class="absolute inset-x-2.5 top-2.5 h-px bg-slate-200"></div>
        <!-- trilho progresso -->
        <?php if ($currentStepIdx > 0): ?>
          <div class="absolute left-2.5 top-2.5 h-px" style="width:calc((100% - 20px) * <?= $currentStepIdx / ($totalProgressSteps - 1) ?>); background-color:var(--admin-primary-color,#6366f1);"></div>
        <?php endif; ?>
        <!-- etapas -->
        <?php foreach ($progressSteps as $step):
          $sIdx  = $progressStepOrder[$step['key']];
          $sDone = $currentStepIdx > $sIdx;
          $sAct  = $currentStepIdx === $sIdx;
        ?>
          <div class="relative z-10 flex flex-col items-center gap-2">
            <div class="flex h-5 w-5 items-center justify-center rounded-full border-2 bg-white transition-colors"
                 style="<?= $sDone ? 'border-color:var(--admin-primary-color,#6366f1);background-color:var(--admin-primary-color,#6366f1);' : ($sAct ? 'border-color:var(--admin-primary-color,#6366f1);' : 'border-color:#cbd5e1;') ?>">
              <?php if ($sDone): ?>
                <svg class="h-2.5 w-2.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 7 9 18l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <?php elseif ($sAct): ?>
                <div class="h-2 w-2 rounded-full" style="background-color:var(--admin-primary-color,#6366f1);"></div>
              <?php endif; ?>
            </div>
            <span class="max-w-[72px] text-center text-[11px] font-medium leading-tight <?= $sAct ? 'text-slate-900' : ($sDone ? 'text-slate-500' : 'text-slate-400') ?>">
              <?= $step['label'] ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Botões de ação rápida -->
    <?php if ($orderSource !== 'ifood' && in_array($st, ['pending', 'paid'], true)): ?>
      <div class="flex flex-wrap items-center gap-2 mb-4">
        <?php if ($st === 'pending'): ?>
          <form method="post" action="<?= e(base_url('admin/' . $slug . '/orders/setStatus')) ?>">
            <?php if (function_exists('csrf_field')): echo csrf_field(); elseif (function_exists('csrf_token')): ?><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><?php endif; ?>
            <input type="hidden" name="id" value="<?= (int)($o['id'] ?? 0) ?>">
            <input type="hidden" name="status" value="paid">
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90" style="background-color:var(--admin-primary-color,#6366f1);">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h5l2 4v4h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
              Saiu para Entrega
            </button>
          </form>
        <?php endif; ?>
        <form method="post" action="<?= e(base_url('admin/' . $slug . '/orders/setStatus')) ?>">
          <?php if (function_exists('csrf_field')): echo csrf_field(); elseif (function_exists('csrf_token')): ?><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><?php endif; ?>
          <input type="hidden" name="id" value="<?= (int)($o['id'] ?? 0) ?>">
          <input type="hidden" name="status" value="completed">
          <button type="submit"
            class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium shadow-sm transition <?= $st === 'paid' ? 'text-white hover:opacity-90' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' ?>"
            <?= $st === 'paid' ? 'style="background-color:var(--admin-primary-color,#6366f1);"' : '' ?>>
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 7 9 18l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Concluído
          </button>
        </form>
        <?php if ($canEdit): ?>
          <a href="<?= e(base_url('admin/' . $slug . '/orders/' . $orderId . '/edit')) ?>"
             class="ml-auto inline-flex items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 transition">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 20h4l10-10-4-4L4 16v4Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Editar Pedido
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Bloco de mudança de status -->
    <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
      <form method="post" action="<?= e(base_url('admin/' . $slug . '/orders/setStatus')) ?>" class="flex flex-wrap items-center gap-2">
        <?php if (function_exists('csrf_field')): echo csrf_field(); elseif (function_exists('csrf_token')): ?><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><?php endif; ?>
        <input type="hidden" name="id" value="<?= (int)($o['id'] ?? 0) ?>">
        <span class="shrink-0 text-xs text-slate-500">Mudar status:</span>
        <select name="status" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-800 focus:outline-none focus:ring-1">
          <?php foreach ($statusLabels as $k => $label): ?>
            <option value="<?= e($k) ?>" <?= $st === $k ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 transition">
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7 9 18l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Aplicar
        </button>
      </form>
      <div class="ml-auto flex flex-wrap items-center gap-3 text-xs">
        <?= status_pill($st, $statusLabels[$st] ?? ucfirst($st)) ?>
        <?php if ($orderSource === 'ifood'): ?>
          <span class="inline-flex items-center gap-1 rounded-lg bg-red-100 px-2 py-1 font-semibold text-red-700">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
            iFood
          </span>
        <?php elseif ($orderSource === 'website'): ?>
          <span class="inline-flex items-center gap-1 rounded-lg bg-blue-100 px-2 py-1 font-semibold text-blue-700">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            Site
          </span>
        <?php elseif ($orderSource === 'whatsapp'): ?>
          <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-2 py-1 font-semibold text-emerald-700">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7 20l1.5-4.5a7 7 0 1 1 2.5 2.5L7 20z" stroke-linecap="round" stroke-linejoin="round"/></svg>
            WhatsApp
          </span>
        <?php else: ?>
          <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2 py-1 font-medium text-slate-600">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
            Manual
          </span>
        <?php endif; ?>
        <?php if (!empty($o['created_at'])): ?>
          <span class="text-slate-400"><?= e($o['created_at']) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- CARDS: Cliente & Resumo -->
  <div class="mb-6 grid gap-4 md:grid-cols-2">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
      <h2 class="mb-2 text-sm font-medium text-slate-700">Cliente</h2>
      <div class="text-lg font-semibold text-slate-900"><?= e($o['customer_name'] ?? '-') ?></div>
      <div class="text-slate-700"><?= e(format_phone_br($o['customer_phone'] ?? '')) ?: '-' ?></div>
      <?php if (!empty($o['customer_address'])): ?>
        <div class="mt-1 text-sm text-slate-700"><?= nl2br(e($o['customer_address'])) ?></div>
      <?php elseif (($o['source'] ?? '') !== 'ifood'): ?>
        <div class="mt-1 text-xs text-slate-400 italic">Endereço não informado</div>
      <?php endif; ?>
      <?php if (!empty($userNotes) || !empty($displayInstructions)): ?>
        <div class="mt-3 rounded-xl bg-slate-50 p-3 text-sm">
          <div class="mb-1 text-xs font-medium text-slate-500">Observações</div>
          <?php if (!empty($userNotes)): ?>
            <div class="text-slate-800"><?= nl2br(e($userNotes)) ?></div>
          <?php endif; ?>
          <?php if (!empty($displayInstructions)): ?>
            <div class="mt-1 flex items-start gap-1 text-xs text-amber-700">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mt-0.5 h-3.5 w-3.5 shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <span><?= e($displayInstructions) ?></span>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
      <h2 class="mb-2 text-sm font-medium text-slate-700">Resumo</h2>
      <dl class="space-y-1 text-slate-800">
        <div class="flex justify-between"><dt>Subtotal</dt><dd>R$ <?= number_format((float)($o['subtotal'] ?? 0), 2, ',', '.') ?></dd></div>
        <div class="flex justify-between"><dt>Entrega</dt><dd>R$ <?= number_format((float)($o['delivery_fee'] ?? 0), 2, ',', '.') ?></dd></div>
        <?php $totalDiscount = (float)($o['discount'] ?? 0) + (float)($o['loyalty_discount'] ?? 0); ?>
        <div class="flex justify-between"><dt>Desconto</dt><dd><?= $totalDiscount > 0 ? '-R$ ' . number_format($totalDiscount, 2, ',', '.') : 'R$ 0,00' ?></dd></div>
      </dl>
      <div class="mt-2 flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
        <div class="text-sm text-slate-600">Total</div>
        <div class="text-xl font-semibold text-slate-900">R$ <?= number_format((float)($o['total'] ?? 0), 2, ',', '.') ?></div>
      </div>
      <?php if (!empty($paymentMethodName)): ?>
        <div class="mt-2 flex items-center gap-2">
          <?php if ($pmBrandIcon): ?>
            <img src="<?= e(base_url('assets/card-brands/' . $pmBrandIcon)) ?>"
                 alt="<?= e($pmTypeLabel ?? $paymentMethodName) ?>"
                 onerror="this.style.display='none'"
                 style="width:32px;height:20px;object-fit:contain;flex-shrink:0;">
          <?php else: ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0 text-slate-500"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          <?php endif; ?>
          <div class="min-w-0">
            <?php if (!empty($pmTypeLabel)): ?>
              <div class="text-xs text-slate-500 leading-none mb-0.5"><?= e($pmTypeLabel) ?></div>
            <?php endif; ?>
            <div class="text-sm font-medium text-slate-800"><?= e($paymentMethodName) ?></div>
          </div>
        </div>
        <?php
          // Exibir troco se existir nas observações do pedido
          $trocoMatch = [];
          if (!empty($o['notes']) && preg_match('/Troco para: R\$ ([\d.,]+)(?:\s+\(Troco: R\$ ([\d.,]+)\))?/i', $o['notes'], $trocoMatch)):
            $trocoParaValor = $trocoMatch[1] ?? '';
            $trocoValor = $trocoMatch[2] ?? '';
        ?>
        <div class="mt-1 pl-1 text-sm text-slate-600 flex items-center gap-1">
          <span>💰</span>
          <span>Troco para: R$ <?= e($trocoParaValor) ?></span>
          <?php if (!empty($trocoValor)): ?>
            <span class="text-slate-400 text-xs">(Troco: R$ <?= e($trocoValor) ?>)</span>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      <?php elseif (($o['source'] ?? '') !== 'ifood' && !empty($o['payment_method_id'])): ?>
        <div class="mt-2 text-xs text-slate-400">Pagamento: ID #<?= (int)$o['payment_method_id'] ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- INFO IFOOD (se pedido for do iFood) -->
  <?php if (($o['source'] ?? '') === 'ifood' && !empty($ifoodData)): ?>
  <?php
    // Parse JSON fields once
    $ifoodPayments = is_string($ifoodData['payments'] ?? null) ? json_decode($ifoodData['payments'], true) : ($ifoodData['payments'] ?? []);
    $ifoodBenefits = is_string($ifoodData['benefits'] ?? null) ? json_decode($ifoodData['benefits'], true) : ($ifoodData['benefits'] ?? []);
    $ifoodRawData = is_string($ifoodData['raw_data'] ?? null) ? json_decode($ifoodData['raw_data'], true) : ($ifoodData['raw_data'] ?? []);
    $ifoodDelivery = $ifoodRawData['delivery'] ?? [];
    $deliveryObservations = $ifoodDelivery['observations'] ?? '';
  ?>
  <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-3">
      <svg class="h-5 w-5 text-red-600" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
      </svg>
      <h2 class="text-sm font-semibold text-red-800">Informações do iFood</h2>
    </div>
    <div class="grid gap-3 sm:grid-cols-2 text-sm">
      <div>
        <span class="text-red-700 font-medium">ID iFood:</span>
        <span class="text-red-900 ml-1 font-mono text-xs"><?= e($o['ifood_order_id'] ?? '-') ?></span>
      </div>
      <?php if (!empty($ifoodData['ifood_display_id'])): ?>
      <div>
        <span class="text-red-700 font-medium">Código:</span>
        <span class="text-red-900 ml-1 font-semibold">#<?= e($ifoodData['ifood_display_id']) ?></span>
      </div>
      <?php endif; ?>
      <?php if (!empty($ifoodData['status'])): ?>
      <div>
        <span class="text-red-700 font-medium">Status iFood:</span>
        <span class="text-red-900 ml-1"><?= e(ucfirst($ifoodData['status'])) ?></span>
      </div>
      <?php endif; ?>
      <?php if (!empty($ifoodData['order_type'])): ?>
      <div>
        <span class="text-red-700 font-medium">Tipo:</span>
        <span class="text-red-900 ml-1"><?= $ifoodData['order_type'] === 'DELIVERY' ? 'Entrega' : ($ifoodData['order_type'] === 'TAKEOUT' ? 'Retirada' : e($ifoodData['order_type'])) ?></span>
      </div>
      <?php endif; ?>

      <?php // Pedido Agendado ?>
      <?php if (!empty($ifoodData['scheduled_datetime'])): ?>
      <div>
        <span class="text-red-700 font-medium">📅 Agendado para:</span>
        <span class="text-red-900 ml-1 font-semibold"><?= e(date('d/m/Y H:i', strtotime($ifoodData['scheduled_datetime']))) ?></span>
      </div>
      <?php endif; ?>
      <?php if (($ifoodData['order_timing'] ?? '') === 'SCHEDULED'): ?>
      <div>
        <span class="text-red-700 font-medium">Timing:</span>
        <span class="inline-flex items-center rounded bg-amber-200 px-2 py-0.5 text-xs font-semibold text-amber-900">AGENDADO</span>
      </div>
      <?php endif; ?>

      <?php // CPF/CNPJ do cliente ?>
      <?php if (!empty($ifoodData['customer_document'])): ?>
      <div>
        <span class="text-red-700 font-medium">CPF/CNPJ:</span>
        <span class="text-red-900 ml-1"><?= e($ifoodData['customer_document']) ?></span>
      </div>
      <?php endif; ?>

      <?php // Código de retirada (pickup code) ?>
      <?php if (!empty($ifoodData['pickup_code'])): ?>
      <div>
        <span class="text-red-700 font-medium">🔑 Código de Retirada:</span>
        <span class="text-red-900 ml-1 font-mono text-lg font-bold bg-white rounded px-2 py-0.5"><?= e($ifoodData['pickup_code']) ?></span>
      </div>
      <?php endif; ?>

      <?php // Entregue por ?>
      <?php if (!empty($ifoodData['delivered_by'])): ?>
      <div>
        <span class="text-red-700 font-medium">Entrega por:</span>
        <span class="text-red-900 ml-1"><?= $ifoodData['delivered_by'] === 'IFOOD' ? 'iFood' : ($ifoodData['delivered_by'] === 'MERCHANT' ? 'Loja' : e($ifoodData['delivered_by'])) ?></span>
      </div>
      <?php endif; ?>

      <?php if (!empty($ifoodData['delivery_address'])): ?>
      <div class="sm:col-span-2">
        <span class="text-red-700 font-medium">Endereço iFood:</span>
        <?php 
          $addr = is_string($ifoodData['delivery_address']) ? json_decode($ifoodData['delivery_address'], true) : $ifoodData['delivery_address'];
          if (is_array($addr)):
        ?>
        <div class="text-red-900 mt-1">
          <?= e(($addr['streetName'] ?? '') . ', ' . ($addr['streetNumber'] ?? '')) ?>
          <?php if (!empty($addr['complement'])): ?> - <?= e($addr['complement']) ?><?php endif; ?>
          <br>
          <?= e(($addr['neighborhood'] ?? '') . ' - ' . ($addr['city'] ?? '') . '/' . ($addr['state'] ?? '')) ?>
          <?php if (!empty($addr['postalCode'])): ?><br>CEP: <?= e($addr['postalCode']) ?><?php endif; ?>
          <?php if (!empty($addr['reference'])): ?><br><em>Ref: <?= e($addr['reference']) ?></em><?php endif; ?>
        </div>
        <?php else: ?>
        <span class="text-red-900 ml-1"><?= e($ifoodData['delivery_address']) ?></span>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php // Observações de entrega ?>
      <?php if (!empty($deliveryObservations)): ?>
      <div class="sm:col-span-2">
        <span class="text-red-700 font-medium">📝 Obs. Entrega:</span>
        <div class="text-red-900 mt-1 bg-white rounded-lg p-2"><?= nl2br(e($deliveryObservations)) ?></div>
      </div>
      <?php endif; ?>

      <?php if (!empty($ifoodData['created_at'])): ?>
      <div>
        <span class="text-red-700 font-medium">Recebido em:</span>
        <span class="text-red-900 ml-1"><?= e($ifoodData['created_at']) ?></span>
      </div>
      <?php endif; ?>

      <?php // Motivo de cancelamento ?>
      <?php if (!empty($ifoodData['cancellation_reason'])): ?>
      <div class="sm:col-span-2">
        <span class="text-red-700 font-medium">❌ Motivo Cancelamento:</span>
        <span class="text-red-900 ml-1"><?= e($ifoodData['cancellation_reason']) ?></span>
      </div>
      <?php endif; ?>
    </div>

    <?php // Pagamentos ?>
    <?php if (!empty($ifoodPayments['methods'])): ?>
    <div class="mt-4 border-t border-red-200 pt-3">
      <div class="text-xs font-semibold uppercase tracking-wider text-red-700 mb-2">Pagamento</div>
      <div class="space-y-2">
        <?php foreach ($ifoodPayments['methods'] as $pm): ?>
          <?php
            $methodName = $pm['method'] ?? '';
            $methodType = $pm['type'] ?? '';
            $brand = $pm['card']['brand'] ?? $pm['brand'] ?? '';
            $pmValue = (float)($pm['value'] ?? 0);
            $prepaid = ($pm['prepaid'] ?? false) || $methodType === 'ONLINE';
            $changeFor = (float)($pm['cash']['changeFor'] ?? 0);
            
            // Formatar nome amigável
            $methodLabels = [
                'CREDIT' => 'Crédito', 'DEBIT' => 'Débito',
                'MEAL_VOUCHER' => 'Vale Refeição', 'FOOD_VOUCHER' => 'Vale Alimentação',
                'PIX' => 'PIX', 'CASH' => 'Dinheiro',
            ];
            $methodDisplay = $methodLabels[strtoupper($methodName)] ?? $methodName;
            if ($brand) $methodDisplay .= ' (' . e($brand) . ')';
          ?>
          <div class="flex items-center justify-between text-sm">
            <div>
              <span class="text-red-900 font-medium"><?= e($methodDisplay) ?></span>
              <?php if ($prepaid): ?>
                <span class="ml-1 inline-flex items-center rounded bg-green-200 px-1.5 py-0.5 text-xs font-medium text-green-800">Pago Online</span>
              <?php else: ?>
                <span class="ml-1 inline-flex items-center rounded bg-yellow-200 px-1.5 py-0.5 text-xs font-medium text-yellow-800">Pagar na Entrega</span>
              <?php endif; ?>
            </div>
            <span class="text-red-900 font-semibold">R$ <?= number_format($pmValue, 2, ',', '.') ?></span>
          </div>
          <?php if (strtoupper($methodName) === 'CASH' && $changeFor > 0): ?>
            <div class="flex items-center justify-between text-sm pl-4">
              <span class="text-red-700">💰 Troco para:</span>
              <span class="text-red-900 font-semibold">R$ <?= number_format($changeFor, 2, ',', '.') ?></span>
            </div>
            <div class="flex items-center justify-between text-sm pl-4">
              <span class="text-red-700">Troco:</span>
              <span class="text-red-900 font-semibold">R$ <?= number_format($changeFor - $pmValue, 2, ',', '.') ?></span>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php // Benefícios/Cupons ?>
    <?php if (!empty($ifoodBenefits)): ?>
    <div class="mt-4 border-t border-red-200 pt-3">
      <div class="text-xs font-semibold uppercase tracking-wider text-red-700 mb-2">Cupons / Benefícios</div>
      <div class="space-y-1.5">
        <?php foreach ($ifoodBenefits as $benefit): ?>
          <?php
            $benefitValue = (float)($benefit['value'] ?? 0);
            $target = $benefit['target'] ?? '';
            $sponsorName = $benefit['sponsorshipValues']['IFOOD'] ?? null;
            $merchantSponsor = $benefit['sponsorshipValues']['MERCHANT'] ?? null;
            
            $targetLabels = ['DELIVERY_FEE' => 'Frete', 'ITEM' => 'Item', 'CART' => 'Carrinho'];
            $targetDisplay = $targetLabels[$target] ?? $target;
          ?>
          <div class="flex items-center justify-between text-sm">
            <div>
              <span class="text-red-900 font-medium">🎫 Desconto <?= e($targetDisplay) ?></span>
              <?php if ($sponsorName !== null && $merchantSponsor !== null): ?>
                <span class="ml-1 text-xs text-red-600">(iFood: R$ <?= number_format((float)$sponsorName, 2, ',', '.') ?> | Loja: R$ <?= number_format((float)$merchantSponsor, 2, ',', '.') ?>)</span>
              <?php elseif ($sponsorName !== null): ?>
                <span class="ml-1 text-xs text-red-600">(Pago pelo iFood)</span>
              <?php elseif ($merchantSponsor !== null): ?>
                <span class="ml-1 text-xs text-red-600">(Pago pela Loja)</span>
              <?php endif; ?>
            </div>
            <span class="text-green-700 font-semibold">- R$ <?= number_format($benefitValue, 2, ',', '.') ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <?php elseif (($o['source'] ?? '') === 'ifood'): ?>
  <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 shadow-sm">
    <div class="flex items-center gap-2">
      <svg class="h-5 w-5 text-red-600" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
      </svg>
      <h2 class="text-sm font-semibold text-red-800">Pedido do iFood</h2>
      <?php if (!empty($o['ifood_order_id'])): ?>
      <span class="ml-auto text-xs font-mono text-red-700"><?= e($o['ifood_order_id']) ?></span>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ITENS -->
  <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-4 py-3">
      <h2 class="text-sm font-medium text-slate-700">Itens do Pedido</h2>
    </div>
    <div class="divide-y divide-slate-100">
      <?php foreach (($o['items'] ?? []) as $it): ?>
        <div class="p-4 hover:bg-slate-50/60 transition-colors">
          <!-- Nome e Quantidade -->
          <div class="flex items-start justify-between gap-4 mb-3">
            <div class="flex-1">
              <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                  <?= (int)($it['quantity'] ?? 0) ?>x
                </span>
                <h3 class="text-base font-semibold text-slate-900"><?= e($it['product_name'] ?: ($it['notes'] ?: '-')) ?></h3>
              </div>
            </div>
            <div class="text-right">
              <?php
                $itQty        = (int)($it['quantity'] ?? 1);
                $itUnitPrice  = (float)($it['unit_price'] ?? 0);
                $itLineTotal  = (float)($it['line_total'] ?? ($itUnitPrice * $itQty));
                $itCustRaw    = $it['customization_data'] ?? null;
                $itCustArr    = is_string($itCustRaw) ? json_decode($itCustRaw, true) : $itCustRaw;
                $itTotalDelta = isset($itCustArr['total_delta']) ? (float)$itCustArr['total_delta'] : 0.0;
                $itBaseUnit   = $itUnitPrice - $itTotalDelta;
              ?>
              <?php if ($itTotalDelta > 0.009): ?>
                <div class="text-xs text-slate-400 mb-0.5">
                  R$ <?= number_format($itBaseUnit, 2, ',', '.') ?>
                  <span class="text-emerald-600 font-medium">+R$ <?= number_format($itTotalDelta, 2, ',', '.') ?></span>
                </div>
              <?php endif; ?>
              <div class="text-lg font-bold text-slate-900">R$ <?= number_format($itLineTotal, 2, ',', '.') ?></div>
            </div>
          </div>
          
          <?php 
          // Decodificar dados de combo
          $comboData = null;
          $componentCusts = [];
          if (!empty($it['combo_data'])) {
              $comboData = is_string($it['combo_data']) ? json_decode($it['combo_data'], true) : $it['combo_data'];
              // Extrair component_customizations se disponível
              if (is_array($comboData) && !empty($comboData['component_customizations'])) {
                  $componentCusts = $comboData['component_customizations'];
              }
          }
          
          // Decodificar dados de personalização
          $customData = null;
          if (!empty($it['customization_data'])) {
              $customData = is_string($it['customization_data']) ? json_decode($it['customization_data'], true) : $it['customization_data'];
          }
          ?>
          
          <!-- Opções do Combo -->
          <?php if ($comboData && !empty($comboData['selected_items'])): ?>
            <div class="mt-3 border-t border-slate-100 pt-3">
              <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                Opções do Combo
              </div>
              <div class="space-y-1.5">
                <?php foreach ($comboData['selected_items'] as $idx => $comboItem): ?>
                  <?php 
                    $itemName = $comboItem['simple_name'] ?? $comboItem['name'] ?? '';
                    $simpleId = $comboItem['simple_id'] ?? 0;
                    $itemQty = isset($comboItem['qty']) ? (int)$comboItem['qty'] : (isset($comboItem['default_qty']) ? (int)$comboItem['default_qty'] : 1);
                    if ($itemQty <= 0) $itemQty = 1;
                    
                    // Verificar se há unit_customizations
                    $hasUnitCusts = $simpleId > 0 && 
                        !empty($componentCusts[$simpleId]['unit_customizations']) &&
                        is_array($componentCusts[$simpleId]['unit_customizations']);
                    
                    $delta = (float)($comboItem['delta'] ?? $comboItem['delta_price'] ?? 0);
                  ?>
                  <?php if ($itemName): ?>
                    <?php if ($hasUnitCusts && $itemQty > 1): ?>
                      <?php foreach ($componentCusts[$simpleId]['unit_customizations'] as $unitNum => $unitCust): ?>
                        <div class="flex items-center justify-between text-sm">
                          <span class="font-medium text-slate-700">
                            <?= e($itemName) ?> (<?= $unitNum ?>º)
                          </span>
                          <span class="text-slate-400">
                            <?= abs($delta) > 0.009 ? '+ R$ ' . number_format($delta, 2, ',', '.') : 'Incluso' ?>
                          </span>
                        </div>
                        <?php 
                        // Mostrar customizações desta unidade
                        if (!empty($unitCust['groups'])):
                          foreach ($unitCust['groups'] as $ug):
                            if (!empty($ug['items'])):
                              foreach ($ug['items'] as $ui):
                                $uiName = $ui['name'] ?? '';
                                $uiDelta = isset($ui['delta_qty']) ? (int)$ui['delta_qty'] : null;
                                $uiDefaultQty = isset($ui['default_qty']) ? (int)$ui['default_qty'] : null;
                                $uiQty = isset($ui['qty']) ? (int)$ui['qty'] : null;
                                $uiPrice = (float)($ui['price'] ?? 0);
                                $uiRemoved = !empty($ui['removed']) || ($uiDefaultQty !== null && $uiDefaultQty > 0 && ($uiQty === 0 || $uiQty === null));
                                
                                if ($uiDelta === null && $uiDefaultQty !== null && $uiQty !== null) {
                                    $uiDelta = $uiQty - $uiDefaultQty;
                                }
                                
                                $showUnitItem = false;
                                $unitItemText = '';
                                $unitItemStatus = '';
                                
                                if ($uiRemoved && $uiName) {
                                    $showUnitItem = true;
                                    $unitItemText = "Sem {$uiName}";
                                    $unitItemStatus = 'Removido';
                                } elseif ($uiDelta !== null && $uiDelta > 0 && $uiName) {
                                    $showUnitItem = true;
                                    $unitItemText = "+{$uiDelta}x {$uiName}";
                                    $unitItemStatus = $uiPrice > 0 ? '+ R$ ' . number_format($uiPrice, 2, ',', '.') : 'Extra';
                                } elseif ($uiDelta !== null && $uiDelta < 0 && $uiName) {
                                    $showUnitItem = true;
                                    $unitItemText = "Sem {$uiName}";
                                    $unitItemStatus = 'Removido';
                                }
                                
                                if ($showUnitItem):
                        ?>
                          <div class="flex items-center justify-between text-sm pl-4">
                            <span class="text-slate-600"><?= e($unitItemText) ?></span>
                            <span class="text-xs text-slate-400"><?= e($unitItemStatus) ?></span>
                          </div>
                        <?php 
                                endif;
                              endforeach;
                            endif;
                          endforeach;
                        endif;
                        ?>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <?php 
                        $displayName = $itemQty > 1 ? "{$itemQty}x {$itemName}" : $itemName;
                      ?>
                      <div class="flex items-center justify-between text-sm">
                        <span class="<?= $idx % 2 == 0 ? 'font-medium text-slate-700' : 'text-slate-600' ?>">
                          <?= e($displayName) ?>
                        </span>
                        <span class="text-slate-400">
                          <?= abs($delta) > 0.009 ? '+ R$ ' . number_format($delta, 2, ',', '.') : 'Incluso' ?>
                        </span>
                      </div>
                    <?php endif; ?>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
          
          <!-- Personalização -->
          <?php if ($customData && !empty($customData['groups'])): ?>
            <?php 
            $customItems = [];
            foreach ($customData['groups'] as $group) {
                // Verificar se é um grupo de seleção/escolha
                $groupType = $group['type'] ?? 'extra';
                $isChoiceGroup = in_array($groupType, ['single', 'addon', 'choice']);
                $isPoolGroup = $groupType === 'pool';
                $groupName = $group['name'] ?? '';
                
                if (!empty($group['items'])) {
                    foreach ($group['items'] as $customItem) {
                        $itemName = $customItem['name'] ?? '';
                        // Manter qty como null se não existir para detectar remoções corretamente
                        $qty = isset($customItem['qty']) ? (int)$customItem['qty'] : null;
                        $deltaQty = isset($customItem['delta_qty']) ? (int)$customItem['delta_qty'] : null;
                        $defaultQty = isset($customItem['default_qty']) ? (int)$customItem['default_qty'] : null;
                        $price = (float)($customItem['price'] ?? 0);
                        $isSelected = !empty($customItem['selected']) || ($qty !== null && $qty > 0);
                        // Remoção: item marcado como removido OU tem default_qty > 0 e qty é 0 ou null
                        $isRemoved = !empty($customItem['removed']) || ($defaultQty !== null && $defaultQty > 0 && ($qty === 0 || $qty === null));
                        
                        // Verificar se é remoção
                        if ($isRemoved && $itemName) {
                            $customItems[] = [
                                'text' => "Sem " . $itemName,
                                'status' => 'Removido'
                            ];
                            continue;
                        }
                        
                        $effectiveQty = $qty ?? 0;
                        
                        // Calcular delta se não existir
                        if ($deltaQty === null && $defaultQty !== null && $qty !== null) {
                            $deltaQty = $qty - $defaultQty;
                        }
                        
                        // Para grupos pool (açaí): mostrar todos os itens selecionados com quantidade
                        if ($isPoolGroup && $itemName && $effectiveQty > 0) {
                            $displayText = $effectiveQty > 1 ? "{$effectiveQty}x {$itemName}" : $itemName;
                            $paidQty = (int)($customItem['paid_qty'] ?? 0);
                            $paidPrice = (float)($customItem['unit_price'] ?? $price);
                            $status = ($paidQty > 0 && $paidPrice > 0.009)
                                ? '+ R$ ' . number_format($paidQty * $paidPrice, 2, ',', '.')
                                : 'Incluso';
                            $customItems[] = [
                                'text' => $displayText,
                                'status' => $status
                            ];
                            continue;
                        }
                        
                        // Para grupos de escolha: mostrar o item selecionado
                        if ($isChoiceGroup && $isSelected && $effectiveQty > 0 && $itemName) {
                            $displayText = $itemName;
                            $status = $price > 0.009 ? '+ R$ ' . number_format($price, 2, ',', '.') : 'Incluso';
                            
                            $customItems[] = [
                                'text' => $displayText,
                                'status' => $status
                            ];
                            continue;
                        }
                        
                        // Para outros tipos: mostrar modificações
                        if ($itemName && $deltaQty !== null && $deltaQty !== 0) {
                            $displayText = '';
                            $status = '';
                            
                            if ($deltaQty > 0) {
                                $displayText = '+' . ($deltaQty > 1 ? "{$deltaQty}x " : "") . $itemName;
                                $status = $price > 0 ? '+ R$ ' . number_format($price, 2, ',', '.') : 'Extra';
                            } elseif ($deltaQty < 0) {
                                $displayText = "Sem " . $itemName;
                                $status = 'Removido';
                            }
                            
                            if ($displayText) {
                                $customItems[] = [
                                    'text' => $displayText,
                                    'status' => $status
                                ];
                            }
                        } elseif (!$isChoiceGroup && $itemName && $price > 0.009 && $effectiveQty > 0) {
                            // Itens com preço extra
                            $displayText = ($effectiveQty > 1 ? "{$effectiveQty}x " : "") . $itemName;
                            $customItems[] = [
                                'text' => $displayText,
                                'status' => '+ R$ ' . number_format($price, 2, ',', '.')
                            ];
                        }
                    }
                }
            }
            ?>
            <?php if (!empty($customItems)): ?>
              <div class="mt-3 border-t border-slate-100 pt-3">
                <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                  Personalize os ingredientes
                </div>
                <div class="space-y-1.5">
                  <?php foreach ($customItems as $idx => $custom): ?>
                    <div class="flex items-center justify-between text-sm">
                      <span class="<?= $idx % 2 == 0 ? 'font-medium text-slate-700' : 'text-slate-600' ?>">
                        <?= e($custom['text']) ?>
                      </span>
                      <span class="text-slate-400">
                        <?= e($custom['status']) ?>
                      </span>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          <?php endif; ?>
          
          <!-- Personalizações iFood (formato array plano) -->
          <?php if ($customData && !isset($customData['groups']) && is_array($customData) && !empty($customData[0]['name'])): ?>
            <div class="mt-3 border-t border-slate-100 pt-3">
              <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                Personalizações
              </div>
              <div class="space-y-1.5">
                <?php 
                $currentGroup = '';
                foreach ($customData as $idx => $opt): 
                  $groupName = $opt['group'] ?? '';
                  if ($groupName && $groupName !== $currentGroup):
                    $currentGroup = $groupName;
                ?>
                  <div class="text-xs font-medium text-slate-400 uppercase mt-2"><?= e($groupName) ?></div>
                <?php endif; ?>
                  <div class="flex items-center justify-between text-sm">
                    <span class="<?= $idx % 2 == 0 ? 'font-medium text-slate-700' : 'text-slate-600' ?>">
                      <?= (int)($opt['quantity'] ?? 1) ?>x <?= e($opt['name'] ?? '') ?>
                    </span>
                    <span class="text-slate-400">
                      <?= (float)($opt['price'] ?? 0) > 0.009 ? '+ R$ ' . number_format((float)$opt['price'], 2, ',', '.') : 'Incluso' ?>
                    </span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
          <?php 
            // Se notes foi usado como nome do item (product_name vazio), não exibir duplicado como obs
            $showItemNotes = !empty($it['notes']) && !empty($it['product_name']);
          ?>
          <?php if ($showItemNotes): ?>
            <div class="mt-3 border-t border-slate-100 pt-3">
              <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                Observações
              </div>
              <p class="text-sm text-slate-600"><?= nl2br(e($it['notes'])) ?></p>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <?php if (empty($o['items'])): ?>
        <div class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-slate-300" viewBox="0 0 24 24" fill="none">
            <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" stroke="currentColor" stroke-width="1.5"/>
          </svg>
          <p class="mt-2 text-sm text-slate-500">Sem itens neste pedido.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($historyEvents)): ?>
    <?php
      $eventLabelMap = [
        'order.created' => 'Pedido criado',
        'order.updated' => 'Pedido atualizado',
        'order.status_changed' => 'Status alterado',
        'order.canceled' => 'Pedido cancelado',
      ];
      $formatMoney = static function ($value) {
          return 'R$ ' . number_format((float)$value, 2, ',', '.');
      };
      $formatStatus = static function ($status) use ($statusLabels) {
          $status = (string)$status;
          return $statusLabels[$status] ?? ucfirst($status);
      };
    ?>
    <div class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-200 px-4 py-3">
        <h2 class="text-sm font-medium text-slate-700">Histórico</h2>
      </div>
      <div class="divide-y divide-slate-100">
        <?php foreach ($historyEvents as $event): ?>
          <?php
            $payload = $event['payload'] ?? [];
            $meta = $payload['meta'] ?? [];
            $statusChange = $meta['status_change'] ?? null;
            $updateMeta = $meta['order_update'] ?? [];
            $before = $updateMeta['before'] ?? null;
            $after = $updateMeta['after'] ?? null;
            $label = $eventLabelMap[$event['event_type'] ?? ''] ?? ($event['event_type'] ?? 'Evento');
            $createdAt = $event['created_at'] ?? '';
            $createdAtLabel = $createdAt ? date('d/m/Y H:i', strtotime($createdAt)) : '';
          ?>
          <div class="p-4 text-sm text-slate-700">
            <div class="flex items-center justify-between gap-2">
              <div class="font-semibold text-slate-900"><?= e($label) ?></div>
              <?php if ($createdAtLabel): ?>
                <div class="text-xs text-slate-500"><?= e($createdAtLabel) ?></div>
              <?php endif; ?>
            </div>
            <?php if (!empty($statusChange)): ?>
              <div class="mt-1 text-slate-600">Status: <?= e($formatStatus($statusChange['from'] ?? '')) ?> → <?= e($formatStatus($statusChange['to'] ?? '')) ?></div>
            <?php elseif (($event['event_type'] ?? '') === 'order.status_changed'): ?>
              <div class="mt-1 text-slate-600">Status: <?= e($formatStatus($event['status'] ?? '')) ?></div>
            <?php endif; ?>
            <?php if (($event['event_type'] ?? '') === 'order.updated' && $before && $after): ?>
              <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <div>
                  <div class="text-xs uppercase text-slate-500">Antes</div>
                  <div class="text-slate-700">Subtotal: <?= e($formatMoney($before['subtotal'] ?? 0)) ?></div>
                  <div class="text-slate-700">Entrega: <?= e($formatMoney($before['delivery_fee'] ?? 0)) ?></div>
                  <?php $beforeDiscount = (float)($before['discount'] ?? 0) + (float)($before['loyalty_discount'] ?? 0); ?>
                  <div class="text-slate-700">Desconto: <?= e($beforeDiscount > 0 ? '- ' . $formatMoney($beforeDiscount) : $formatMoney(0)) ?></div>
                  <div class="text-slate-900 font-semibold">Total: <?= e($formatMoney($before['total'] ?? 0)) ?></div>
                </div>
                <div>
                  <div class="text-xs uppercase text-slate-500">Depois</div>
                  <div class="text-slate-700">Subtotal: <?= e($formatMoney($after['subtotal'] ?? 0)) ?></div>
                  <div class="text-slate-700">Entrega: <?= e($formatMoney($after['delivery_fee'] ?? 0)) ?></div>
                  <?php $afterDiscount = (float)($after['discount'] ?? 0) + (float)($after['loyalty_discount'] ?? 0); ?>
                  <div class="text-slate-700">Desconto: <?= e($afterDiscount > 0 ? '- ' . $formatMoney($afterDiscount) : $formatMoney(0)) ?></div>
                  <div class="text-slate-900 font-semibold">Total: <?= e($formatMoney($after['total'] ?? 0)) ?></div>
                </div>
              </div>
              <?php if (!empty($before['items']) || !empty($after['items'])): ?>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                  <div>
                    <div class="text-xs uppercase text-slate-500">Itens antes</div>
                    <?php if (!empty($before['items'])): ?>
                      <ul class="mt-1 space-y-1 text-slate-600">
                        <?php foreach ($before['items'] as $it): ?>
                          <?php
                            $qty = (int)($it['quantity'] ?? 0);
                            $name = (string)($it['name'] ?? '');
                            $line = (float)($it['line_total'] ?? (($it['unit_price'] ?? 0) * $qty));
                          ?>
                          <li><?= e($qty . 'x ' . $name) ?> — <?= e($formatMoney($line)) ?></li>
                        <?php endforeach; ?>
                      </ul>
                    <?php else: ?>
                      <div class="mt-1 text-slate-400 text-xs">Sem itens</div>
                    <?php endif; ?>
                  </div>
                  <div>
                    <div class="text-xs uppercase text-slate-500">Itens depois</div>
                    <?php if (!empty($after['items'])): ?>
                      <ul class="mt-1 space-y-1 text-slate-600">
                        <?php foreach ($after['items'] as $it): ?>
                          <?php
                            $qty = (int)($it['quantity'] ?? 0);
                            $name = (string)($it['name'] ?? '');
                            $line = (float)($it['line_total'] ?? (($it['unit_price'] ?? 0) * $qty));
                          ?>
                          <li><?= e($qty . 'x ' . $name) ?> — <?= e($formatMoney($line)) ?></li>
                        <?php endforeach; ?>
                      </ul>
                    <?php else: ?>
                      <div class="mt-1 text-slate-400 text-xs">Sem itens</div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- DANGER ZONE -->
  <div class="mt-6 flex justify-end">
    <form method="post"
          action="<?= e(base_url('admin/' . $slug . '/orders/' . (int)($o['id'] ?? 0) . '/del')) ?>"
          onsubmit="return confirm('Excluir pedido?');">
      <?php if (function_exists('csrf_field')): ?>
        <?= csrf_field() ?>
      <?php elseif (function_exists('csrf_token')): ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <?php endif; ?>
      <button class="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50 transition">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 7h12M9 7v11m6-11v11M8 7l1-2h6l1 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        Excluir pedido
      </button>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
