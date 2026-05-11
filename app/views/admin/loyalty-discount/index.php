<?php
/**
 * View: Admin - Desconto Fidelidade
 * Gerenciamento da taxa embutida para desconto de entrega
 */

// Garantir que há uma empresa selecionada
if (empty($company) || empty($slug)) {
    echo '<p>Erro: dados da empresa não encontrados.</p>';
    return;
}

$pageTitle = 'Desconto Fidelidade';
ob_start();

// Configuração do header padronizado
$pageDescription = '';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>';
$breadcrumbs = [
    ['label' => 'Desconto Fidelidade']
];
$actions = [
    ['label' => 'Dashboard', 'url' => base_url('admin/' . $slug . '/dashboard'), 'icon' => '<svg class="h-4 w-4" fill="currentColor" viewBox="0 0 16 16"><path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/></svg>']
];
?>

<style>
/* Reutilização exata dos estilos do orders/create */
.emb-category-tabs {
    display: flex;
    overflow-x: auto;
    gap: 8px;
    padding-bottom: 12px;
    margin-bottom: 16px;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}
.emb-category-tabs::-webkit-scrollbar { display: none; }
.emb-category-tabs .category-tab {
    flex-shrink: 0;
    padding: 8px 16px;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    font-size: 13px;
    color: #6b7280;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
}
.emb-category-tabs .category-tab:hover { background: #e5e7eb; }
.emb-category-tabs .category-tab.active {
    background: var(--admin-primary-color);
    border-color: var(--admin-primary-color);
    color: white;
}
.emb-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 12px;
    width: 100%;
}
.emb-product-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.2s;
    box-sizing: border-box;
    overflow: hidden;
}
.emb-product-item:hover {
    border-color: #cbd5e1;
    background: #f1f5f9;
}
.emb-product-item.selected {
    background: rgba(91, 33, 182, 0.05);
    border-color: var(--admin-primary-color);
}
.emb-product-item .product-image {
    width: 52px;
    height: 52px;
    min-width: 52px;
    border-radius: 10px;
    object-fit: cover;
    background: #e2e8f0;
    flex-shrink: 0;
}
.emb-product-item .product-info {
    flex: 1;
    min-width: 0;
    overflow: hidden;
}
.emb-product-item .product-name {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}
.emb-product-item .product-price {
    font-size: 13px;
    font-weight: 700;
    color: var(--admin-primary-color);
}
.emb-check-indicator {
    width: 30px;
    height: 30px;
    min-width: 30px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: #fff;
    border: 1.5px solid #d1d5db;
    transition: all 0.15s;
}
.emb-product-item.selected .emb-check-indicator {
    background: var(--admin-primary-color);
    border-color: var(--admin-primary-color);
}
</style>

<div class="mx-auto max-w-6xl p-4">

<?php include __DIR__ . '/../components/page-header.php'; ?>

<!-- ALERTA DE ERRO -->
<?php if (!empty($error)): ?>
  <div class="mb-4 rounded-xl border border-red-200 bg-red-50/90 p-3 text-sm text-red-800 shadow-sm">
    <?= e($error) ?>
  </div>
<?php endif; ?>

<!-- ALERTA DE SUCESSO -->
<?php if (!empty($success)): ?>
  <div class="mb-4 rounded-xl border border-green-200 bg-green-50/90 p-3 text-sm text-green-800 shadow-sm">
    <?= e($success) ?>
  </div>
<?php endif; ?>

<!-- TOP BAR: NAVEGAÇÃO ENTRE SEÇÕES -->
<div class="mb-6 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-sm">
  <div class="grid grid-cols-3 gap-1.5">
    <button type="button" 
            class="section-btn flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition-colors bg-slate-50 text-slate-700 hover:bg-slate-100"
            data-section="cupons"
            onclick="switchSection('cupons')">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
        <path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58s1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41s-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/>
      </svg>
      Cupons
    </button>
    <button type="button" 
            class="section-btn active flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition-colors admin-gradient-bg text-white"
            data-section="taxa-embutida"
            onclick="switchSection('taxa-embutida')">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
      </svg>
      Taxa Embutida
    </button>
    <button type="button" 
            class="section-btn flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition-colors bg-slate-50 text-slate-700 hover:bg-slate-100"
            data-section="cadastro-completo"
            onclick="switchSection('cadastro-completo')">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
      </svg>
      Cadastro Completo
    </button>
  </div>
</div>

<!-- SEÇÃO 1: CUPONS (oculta por padrão) -->
<div id="section-cupons" class="section-content hidden">
  <div class="space-y-6">
    
    <!-- Dashboard de Cupons -->
    <div class="grid gap-4 md:grid-cols-4">
      <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-2 flex items-center justify-between">
          <span class="text-sm font-medium text-slate-600">Total de Cupons</span>
          <svg class="h-5 w-5 text-indigo-600" viewBox="0 0 24 24" fill="currentColor">
            <path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58s1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41s-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/>
          </svg>
        </div>
        <div class="text-2xl font-bold text-slate-900"><?= e($cupons_stats['total']) ?></div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-2 flex items-center justify-between">
          <span class="text-sm font-medium text-slate-600">Ativos</span>
          <svg class="h-5 w-5 text-green-600" viewBox="0 0 24 24" fill="none">
            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.5"/>
          </svg>
        </div>
        <div class="text-2xl font-bold text-green-600"><?= e($cupons_stats['active']) ?></div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-2 flex items-center justify-between">
          <span class="text-sm font-medium text-slate-600">Usados</span>
          <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none">
            <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="1.5"/>
          </svg>
        </div>
        <div class="text-2xl font-bold text-slate-600"><?= e($cupons_stats['used']) ?></div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-2 flex items-center justify-between">
          <span class="text-sm font-medium text-slate-600">Total de Usos</span>
          <svg class="h-5 w-5 text-purple-600" viewBox="0 0 24 24" fill="none">
            <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke="currentColor" stroke-width="1.5"/>
          </svg>
        </div>
        <div class="text-2xl font-bold text-purple-600"><?= e($cupons_stats['totalUsage']) ?></div>
      </div>
    </div>

    <!-- Botão Criar Cupom -->
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-bold text-slate-900">Gerenciar Cupons</h3>
      <a href="<?= e(base_url('admin/' . $slug . '/coupons/create')) ?>"
         class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow-sm hover:opacity-95 transition-opacity">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M12 4v16m8-8H4"/>
        </svg>
        Criar Novo Cupom
      </a>
    </div>

    <!-- Tabela de Cupons -->
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Código</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Cliente</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Desconto</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Uso</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Status</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Criado em</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <?php if (empty($cupons)): ?>
              <tr>
                <td colspan="7" class="px-6 py-12 text-center">
                  <div class="flex flex-col items-center justify-center text-slate-400">
                    <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"></path>
                    </svg>
                    <p class="text-lg font-medium text-slate-600 mb-1">Nenhum cupom cadastrado ainda</p>
                    <p class="text-sm text-slate-500">Clique em "Criar Novo Cupom" para começar</p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($cupons as $cupom): ?>
                <tr class="border-b border-slate-200 hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3">
                    <span class="font-mono font-semibold text-green-700 bg-green-50 px-2 py-1 rounded text-sm">
                      <?= e($cupom['coupon_code']) ?>
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                      <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                      </svg>
                      <span class="text-sm text-slate-700"><?= e(format_phone_br($cupom['customer_phone']) ?: 'N/A') ?></span>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <span class="text-sm font-semibold text-emerald-700"><?= e($cupom['discount_percentage']) ?>%</span>
                  </td>
                  <td class="px-4 py-3">
                    <span class="text-sm text-slate-600"><?= e($cupom['times_used']) ?>/<?= e($cupom['usage_limit']) ?></span>
                  </td>
                  <td class="px-4 py-3">
                    <?php 
                      $timesUsed = (int)($cupom['times_used'] ?? 0);
                      $usageLimit = (int)($cupom['usage_limit'] ?? 1);
                      $isUsed = (int)$cupom['is_used'] === 1;
                      
                      // Cupom usado = is_used=1 OU times_used >= usage_limit
                      $isFullyUsed = $isUsed || ($usageLimit > 0 && $timesUsed >= $usageLimit);
                    ?>
                    <?php if ($isFullyUsed): ?>
                      <span class="px-2 py-1 text-xs font-medium rounded-full bg-slate-100 text-slate-600">Usado</span>
                    <?php else: ?>
                      <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">Ativo</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-4 py-3">
                    <span class="text-sm text-slate-500">
                      <?php 
                        $date = new DateTime($cupom['created_at']);
                        echo $date->format('d/m/Y');
                      ?>
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex items-center justify-end gap-2">
                      <?php if (empty($cupom['customer_phone']) && (int)$cupom['times_used'] > 0): ?>
                        <button onclick="viewCouponHistory('<?= e($cupom['coupon_code']) ?>')"
                                class="text-purple-600 hover:text-purple-800 transition-colors" 
                                title="Ver histórico de uso">
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                          </svg>
                        </button>
                      <?php endif; ?>
                      <a href="<?= e(base_url('admin/' . $slug . '/coupons/' . $cupom['id'] . '/edit')) ?>"
                         class="text-blue-600 hover:text-blue-800 transition-colors" 
                         title="Editar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                      </a>
                      <form method="POST" action="<?= e(base_url('admin/' . $slug . '/coupons/' . $cupom['id'] . '/delete')) ?>" 
                            onsubmit="return confirm('Tem certeza que deseja excluir este cupom?')" 
                            style="display: inline;">
                        <button type="submit" class="text-red-600 hover:text-red-800 transition-colors" title="Excluir">
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                          </svg>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- FORMULÁRIO PRINCIPAL -->
<form method="POST" action="<?= e(base_url('admin/' . $slug . '/loyalty-discount')) ?>">

<!-- SEÇÃO 2: TAXA EMBUTIDA (visível por padrão) -->
<div id="section-taxa-embutida" class="section-content">
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="mb-6 flex items-center gap-2 text-xl font-bold text-slate-900">
      <svg class="h-6 w-6 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
      </svg>
      Taxa Embutida para Desconto de Entrega
    </h3>

    <!-- Como funciona -->
    <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50/50 p-4">
      <div class="flex items-start gap-2">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none">
          <path d="M12 16v-4m0-4h.01M22 12c0 5.523-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2s10 4.477 10 10z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <div class="text-sm text-blue-900">
          <p class="mb-2 font-semibold">Como funciona:</p>
          <ul class="ml-4 list-disc space-y-1 text-sm leading-relaxed">
            <li>O valor definido será <strong>adicionado automaticamente</strong> ao preço de todos os produtos do cardápio</li>
            <li>No checkout, esse valor acumulado se transforma em <strong>desconto na taxa de entrega</strong></li>
            <li>O cliente vê um preço ligeiramente maior nos produtos, mas ganha desconto no frete</li>
            <li>Exemplo: Taxa de R$1,00 + 2 produtos = R$2,00 de desconto na entrega</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Formulário Taxa Embutida -->
    <div class="grid gap-6 md:grid-cols-2">
      <div class="space-y-4">
        <label class="block">
          <span class="mb-2 block text-sm font-medium text-slate-700">Valor a embutir por produto (R$) <a href="<?= e(base_url('admin/' . $slug . '/guide/loyalty-discount#embedded')) ?>" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></span>
          <input type="number" 
                 name="embedded_delivery_fee" 
                 id="embedded_delivery_fee"
                 value="<?= e($company['embedded_delivery_fee'] ?? '0.00') ?>" 
                 step="0.01" 
                 min="0" 
                 max="10"
                 class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-900 focus:ring-2 focus:ring-indigo-400"
                 placeholder="0.00">
          <small class="mt-1 block text-xs text-slate-500">Digite 0 para desativar. Recomendado: entre R$0,50 e R$2,00</small>
        </label>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="mb-2 text-sm font-semibold text-slate-700">Status atual:</div>
          <div class="flex items-center gap-2">
            <?php 
            $currentFee = (float)($company['embedded_delivery_fee'] ?? 0);
            $isActive = $currentFee > 0;
            ?>
            <span id="status-indicator" class="inline-flex h-2 w-2 rounded-full <?= $isActive ? 'bg-green-500' : 'bg-slate-300' ?>"></span>
            <span id="status-text" class="text-sm <?= $isActive ? 'text-green-700 font-medium' : 'text-slate-600' ?>">
              <?= $isActive ? 'Ativo - R$ ' . number_format($currentFee, 2, ',', '.') . ' por produto' : 'Desativado' ?>
            </span>
          </div>
        </div>
      </div>

      <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-4">
        <div class="mb-2 flex items-center gap-2">
          <svg class="h-5 w-5 text-amber-600" viewBox="0 0 24 24" fill="none">
            <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span class="text-sm font-semibold text-amber-900">Simulação de exemplo</span>
        </div>
        <div id="simulation" class="space-y-2 text-sm text-amber-900">
          <div class="flex justify-between">
            <span>Produto 1 (R$16,00):</span>
            <span id="sim-product1" class="font-medium">R$<?= number_format(16 + $currentFee, 2, ',', '.') ?></span>
          </div>
          <div class="flex justify-between">
            <span>Produto 2 (R$10,00):</span>
            <span id="sim-product2" class="font-medium">R$<?= number_format(10 + $currentFee, 2, ',', '.') ?></span>
          </div>
          <div class="border-t border-amber-300 pt-2">
            <div class="flex justify-between">
              <span>Subtotal:</span>
              <span id="sim-subtotal" class="font-medium">R$<?= number_format(26 + ($currentFee * 2), 2, ',', '.') ?></span>
            </div>
            <div class="flex justify-between text-green-700">
              <span>Entrega (R$9,00 - desconto):</span>
              <span id="sim-delivery" class="font-medium">R$<?= number_format(max(0, 9 - ($currentFee * 2)), 2, ',', '.') ?></span>
            </div>
            <div class="mt-2 flex justify-between border-t border-amber-300 pt-2 font-bold">
              <span>Total:</span>
              <span id="sim-total">R$<?= number_format(26 + ($currentFee * 2) + max(0, 9 - ($currentFee * 2)), 2, ',', '.') ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Seletor de Produtos Participantes -->
    <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5" id="product-selector-section">
      <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
        <h4 class="flex items-center gap-2 text-base font-semibold text-slate-800">
          <svg class="h-5 w-5" style="color:var(--admin-primary-color)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <path d="M16 10a4 4 0 0 1-8 0"/>
          </svg>
          Produtos Participantes
          <span class="text-xs font-normal text-slate-500" id="products-count-label">
            <?php
            $totalProds = count($allProducts ?? []);
            $enabledProds = 0;
            foreach (($allProducts ?? []) as $p) {
              if ((int)($p['embedded_fee_enabled'] ?? 1) === 1) $enabledProds++;
            }
            echo "({$enabledProds} de {$totalProds})";
            ?>
          </span>
        </h4>
        <div class="flex items-center gap-2">
          <button type="button" onclick="toggleAllProducts(true)" style="border-color:var(--admin-primary-color);background:rgba(91,33,182,0.08);color:var(--admin-primary-color)" class="rounded-full border px-3 py-1 text-xs font-medium transition-colors hover:opacity-80">
            Selecionar todos
          </button>
          <button type="button" onclick="toggleAllProducts(false)" class="rounded-full border border-slate-300 bg-white px-3 py-1 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50">
            Desmarcar todos
          </button>
        </div>
      </div>

      <?php
      // Agrupar produtos por categoria
      $productsByCategory = [];
      $uncategorized = [];
      $catMap = [];
      foreach (($categories ?? []) as $cat) {
        $catMap[(int)$cat['id']] = $cat['name'];
        $productsByCategory[(int)$cat['id']] = [];
      }
      foreach (($allProducts ?? []) as $prod) {
        $catId = (int)($prod['category_id'] ?? 0);
        if ($catId && isset($productsByCategory[$catId])) {
          $productsByCategory[$catId][] = $prod;
        } else {
          $uncategorized[] = $prod;
        }
      }
      ?>

      <!-- Category Tabs -->
      <div class="emb-category-tabs" id="emb-category-tabs">
        <div class="category-tab active" data-emb-cat="all" onclick="filterEmbProducts('all', this)">Todos</div>
        <?php foreach ($productsByCategory as $catId => $catProducts): ?>
          <?php if (empty($catProducts)) continue; ?>
          <div class="category-tab" data-emb-cat="<?= $catId ?>" onclick="filterEmbProducts(<?= $catId ?>, this)"><?= e($catMap[$catId] ?? 'Categoria') ?></div>
        <?php endforeach; ?>
        <?php if (!empty($uncategorized)): ?>
          <div class="category-tab" data-emb-cat="0" onclick="filterEmbProducts(0, this)">Sem categoria</div>
        <?php endif; ?>
      </div>

      <!-- Selecionar/Desmarcar categoria visível -->
      <div class="mb-3 flex items-center gap-2" id="emb-cat-actions" style="display:none;">
        <button type="button" onclick="toggleVisibleCategory(true)" class="rounded-lg border border-green-200 bg-green-50 px-3 py-1 text-xs font-medium text-green-700 hover:bg-green-100 transition-colors">
          <svg class="inline h-3 w-3 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>Selecionar categoria
        </button>
        <button type="button" onclick="toggleVisibleCategory(false)" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100 transition-colors">
          Desmarcar categoria
        </button>
      </div>

      <!-- Hidden marker: seletor de produtos foi renderizado -->
      <input type="hidden" name="embedded_fee_products_present" value="1">

      <!-- Products Grid -->
      <div class="emb-products-grid" id="emb-products-grid">
        <?php foreach (($allProducts ?? []) as $prod):
          $isEnabled = (int)($prod['embedded_fee_enabled'] ?? 1) === 1;
          $catId = (int)($prod['category_id'] ?? 0);
          $pp = (float)$prod['price'];
        ?>
        <label class="emb-product-item<?= $isEnabled ? ' selected' : '' ?>" data-emb-category="<?= $catId ?>">
          <input type="checkbox"
                 name="embedded_fee_products[]"
                 value="<?= (int)$prod['id'] ?>"
                 class="product-checkbox"
                 data-category="<?= $catId ?>"
                 <?= $isEnabled ? 'checked' : '' ?>
                 onchange="onProductToggle(this)"
                 style="display:none;">
          <?php if (!empty($prod['image'])): ?>
            <img src="/<?= e($prod['image']) ?>" class="product-image" alt="" loading="lazy">
          <?php else: ?>
            <div class="product-image" style="display:flex;align-items:center;justify-content:center;">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
            </div>
          <?php endif; ?>
          <div class="product-info">
            <div class="product-name"><?= e($prod['name']) ?></div>
            <div class="product-price">R$ <?= number_format($pp, 2, ',', '.') ?></div>
          </div>
          <div class="emb-check-indicator">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="<?= $isEnabled ? '#fff' : 'transparent' ?>" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
          </div>
        </label>
        <?php endforeach; ?>

        <?php if (empty($allProducts)): ?>
          <div style="grid-column:1/-1;border:2px dashed #e2e8f0;border-radius:12px;padding:32px 20px;text-align:center;background:#f8fafc;">
            <svg style="width:40px;height:40px;margin:0 auto 8px;color:#94a3b8;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
            <p style="font-size:14px;color:#64748b;margin:0;">Nenhum produto cadastrado</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<!-- SEÇÃO 3: CADASTRO COMPLETO (oculta por padrão) -->
<div id="section-cadastro-completo" class="section-content hidden">
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="mb-6 flex items-center gap-2 text-xl font-bold text-slate-900">
      <svg class="h-6 w-6 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
      </svg>
      Desconto por Cadastro Completo
    </h3>

    <!-- Como funciona -->
    <div class="mb-6 rounded-xl border border-green-200 bg-green-50/50 p-4">
      <div class="flex items-start gap-2">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-green-600" viewBox="0 0 24 24" fill="none">
          <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <div class="text-sm text-green-900">
          <p class="mb-2 font-semibold">Como funciona:</p>
          <ul class="ml-4 list-disc space-y-1 text-sm leading-relaxed">
            <li>Quando o cliente preencher <strong>CPF e Data de Nascimento</strong> pela primeira vez no perfil</li>
            <li>Ele recebe automaticamente um <strong>desconto permanente</strong> em todos os pedidos futuros</li>
            <li>O desconto é aplicado no valor total do pedido</li>
            <li>Incentiva o cadastro completo e fideliza os clientes</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Formulário Cadastro Completo -->
    <div class="grid gap-6 md:grid-cols-2">
      <div class="space-y-4">
        <label class="flex items-center gap-3 cursor-pointer">
          <input type="checkbox" 
                 name="loyalty_active" 
                 id="loyalty_active"
                 <?= !empty($loyalty_active) ? 'checked' : '' ?>
                 class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-2 focus:ring-indigo-400">
          <span class="text-sm font-medium text-slate-700">Ativar desconto por cadastro completo <a href="<?= e(base_url('admin/' . $slug . '/guide/loyalty-discount#signup')) ?>" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></span>
        </label>

        <label class="block">
          <span class="mb-2 block text-sm font-medium text-slate-700">Porcentagem de desconto (%) <a href="<?= e(base_url('admin/' . $slug . '/guide/loyalty-discount#signup')) ?>" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></span>
          <input type="number" 
                 name="loyalty_discount" 
                 id="loyalty_discount"
                 value="<?= e($loyalty_discount ?? '0.00') ?>" 
                 step="0.01" 
                 min="0" 
                 max="100"
                 class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-900 focus:ring-2 focus:ring-indigo-400"
                 placeholder="0.00">
          <small class="mt-1 block text-xs text-slate-500">Digite 0 para desativar. Recomendado: entre 5% e 15%</small>
        </label>

        <label class="block">
          <span class="mb-2 block text-sm font-medium text-slate-700">Mensagem de boas-vindas (opcional)</span>
          <textarea name="loyalty_message" 
                    id="loyalty_message"
                    rows="5"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:ring-2 focus:ring-indigo-400 resize-y"
                    placeholder="Ex: Obrigado por completar seu cadastro! Aproveite seu desconto especial."><?= e($loyalty_message ?? '') ?></textarea>
          <small class="mt-1 block text-xs text-slate-500">Mensagem exibida ao cliente quando ele ganhar o desconto</small>
        </label>

        <label class="block">
          <span class="mb-2 block text-sm font-medium text-slate-700">Prefixo do cupom <a href="<?= e(base_url('admin/' . $slug . '/guide/loyalty-discount#signup')) ?>" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></span>
          <input type="text" 
                 name="coupon_prefix" 
                 id="coupon_prefix"
                 value="<?= e($coupon_prefix ?? 'WOLL') ?>" 
                 maxlength="10"
                 class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-900 focus:ring-2 focus:ring-indigo-400 uppercase"
                 placeholder="WOLL"
                 style="text-transform: uppercase;">
          <small class="mt-1 block text-xs text-slate-500">Prefixo usado no código do cupom. Ex: <strong>WOLL</strong>123ABC</small>
        </label>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="mb-2 text-sm font-semibold text-slate-700">Status atual:</div>
          <div class="flex items-center gap-2">
            <?php $isLoyaltyActive = !empty($loyalty_active) && (float)$loyalty_discount > 0; ?>
            <span class="inline-flex h-2 w-2 rounded-full <?= $isLoyaltyActive ? 'bg-green-500' : 'bg-slate-300' ?>"></span>
            <span class="text-sm <?= $isLoyaltyActive ? 'text-green-700 font-medium' : 'text-slate-600' ?>">
              <?= $isLoyaltyActive ? 'Ativo - ' . number_format((float)$loyalty_discount, 0) . '% de desconto' : 'Desativado' ?>
            </span>
          </div>
        </div>
      </div>

      <div class="rounded-xl border border-purple-200 bg-purple-50/50 p-4">
        <div class="mb-2 flex items-center gap-2">
          <svg class="h-5 w-5 text-purple-600" viewBox="0 0 24 24" fill="none">
            <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" fill="currentColor"/>
          </svg>
          <span class="text-sm font-semibold text-purple-900">Exemplo de aplicação</span>
        </div>
        <div class="space-y-2 text-sm text-purple-900">
          <div class="rounded-lg border border-purple-200 bg-white p-3">
            <div class="mb-2 font-semibold">Cenário:</div>
            <div class="space-y-1 text-xs">
              <div>✅ Cliente preenche CPF e Data de Nascimento</div>
              <div>✅ Sistema ativa desconto de <strong id="example-discount"><?= number_format((float)$loyalty_discount, 0) ?>%</strong></div>
            </div>
          </div>
          <div class="rounded-lg border border-purple-200 bg-white p-3">
            <div class="mb-2 text-xs font-semibold">Pedido de R$ 50,00:</div>
            <div class="flex justify-between text-xs">
              <span>Subtotal:</span>
              <span>R$ 50,00</span>
            </div>
            <div class="flex justify-between text-xs text-green-600 font-medium">
              <span>Desconto (<span id="example-percent"><?= number_format((float)$loyalty_discount, 0) ?>%</span>):</span>
              <span id="example-discount-value">-R$ <?= number_format(50 * ((float)$loyalty_discount / 100), 2, ',', '.') ?></span>
            </div>
            <div class="mt-2 flex justify-between border-t border-purple-200 pt-2 font-bold text-sm">
              <span>Total final:</span>
              <span id="example-total">R$ <?= number_format(50 - (50 * ((float)$loyalty_discount / 100)), 2, ',', '.') ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

  <!-- BOTÃO SALVAR -->
  <div class="mt-6 flex justify-end">
    <button type="submit"
            class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:opacity-95 transition-opacity">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M20 7 9 18l-5-5"/>
      </svg>
      Salvar Configurações
    </button>
  </div>

</form>

</div>

<script>
// Função para trocar entre seções
function switchSection(sectionName) {
  // Ocultar todas as seções
  document.querySelectorAll('.section-content').forEach(section => {
    section.classList.add('hidden');
  });
  
  // Remover active de todos os botões
  document.querySelectorAll('.section-btn').forEach(btn => {
    btn.classList.remove('admin-gradient-bg', 'text-white');
    btn.classList.add('bg-slate-50', 'text-slate-700');
  });
  
  // Mostrar seção selecionada
  document.getElementById('section-' + sectionName).classList.remove('hidden');
  
  // Ativar botão selecionado
  const activeBtn = document.querySelector(`[data-section="${sectionName}"]`);
  activeBtn.classList.remove('bg-slate-50', 'text-slate-700');
  activeBtn.classList.add('admin-gradient-bg', 'text-white');
}

// Atualizar simulação de taxa embutida em tempo real
const feeInput = document.getElementById('embedded_delivery_fee');
const statusIndicator = document.getElementById('status-indicator');
const statusText = document.getElementById('status-text');

feeInput?.addEventListener('input', function() {
  const fee = parseFloat(this.value) || 0;
  const isActive = fee > 0;
  
  // Atualizar status
  if (isActive) {
    statusIndicator.classList.remove('bg-slate-300');
    statusIndicator.classList.add('bg-green-500');
    statusText.classList.remove('text-slate-600');
    statusText.classList.add('text-green-700', 'font-medium');
    statusText.textContent = `Ativo - R$ ${fee.toFixed(2).replace('.', ',')} por produto`;
  } else {
    statusIndicator.classList.remove('bg-green-500');
    statusIndicator.classList.add('bg-slate-300');
    statusText.classList.remove('text-green-700', 'font-medium');
    statusText.classList.add('text-slate-600');
    statusText.textContent = 'Desativado';
  }
  
  // Atualizar simulação
  const product1 = 16 + fee;
  const product2 = 10 + fee;
  const subtotal = product1 + product2;
  const delivery = Math.max(0, 9 - (fee * 2));
  const total = subtotal + delivery;
  
  document.getElementById('sim-product1').textContent = `R$ ${product1.toFixed(2).replace('.', ',')}`;
  document.getElementById('sim-product2').textContent = `R$ ${product2.toFixed(2).replace('.', ',')}`;
  document.getElementById('sim-subtotal').textContent = `R$ ${subtotal.toFixed(2).replace('.', ',')}`;
  document.getElementById('sim-delivery').textContent = `R$ ${delivery.toFixed(2).replace('.', ',')}`;
  document.getElementById('sim-total').textContent = `R$ ${total.toFixed(2).replace('.', ',')}`;
});

// Atualizar exemplo de desconto fidelidade em tempo real
const loyaltyDiscountInput = document.getElementById('loyalty_discount');

loyaltyDiscountInput?.addEventListener('input', function() {
  const discount = parseFloat(this.value) || 0;
  const subtotal = 50;
  const discountValue = subtotal * (discount / 100);
  const total = subtotal - discountValue;
  
  document.getElementById('example-discount').textContent = discount.toFixed(0);
  document.getElementById('example-percent').textContent = discount.toFixed(0);
  document.getElementById('example-discount-value').textContent = `-R$ ${discountValue.toFixed(2).replace('.', ',')}`;
  document.getElementById('example-total').textContent = `R$ ${total.toFixed(2).replace('.', ',')}`;
});

// Inicializar ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
  // Verificar se há parâmetro na URL para abrir aba específica
  const urlParams = new URLSearchParams(window.location.search);
  const section = urlParams.get('section');
  
  if (section === 'cupons') {
    // Abrir aba de cupons se especificado na URL
    switchSection('cupons');
  }
});

// Função para visualizar histórico de uso do cupom
function viewCouponHistory(couponCode) {
  // Criar modal
  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/50';
  modal.innerHTML = `
    <div class="w-full max-w-2xl mx-4 rounded-2xl bg-white shadow-xl">
      <div class="flex items-center justify-between border-b border-slate-200 p-6">
        <div>
          <h3 class="text-lg font-semibold text-slate-900">Histórico de Uso</h3>
          <p class="text-sm text-slate-600">Cupom: <span class="font-mono font-semibold">${couponCode}</span></p>
        </div>
        <button onclick="this.closest('.fixed').remove()" class="text-slate-400 hover:text-slate-600">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <div class="p-6">
        <div id="history-content" class="space-y-3">
          <div class="text-center py-8">
            <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-purple-600 border-r-transparent"></div>
            <p class="mt-2 text-sm text-slate-600">Carregando histórico...</p>
          </div>
        </div>
      </div>
    </div>
  `;
  
  document.body.appendChild(modal);
  
  // Buscar histórico via AJAX
  fetch(`<?= base_url('admin/' . $slug . '/coupons/history') ?>?code=${encodeURIComponent(couponCode)}`, {
    method: 'GET',
    credentials: 'same-origin',
    headers: {
      'Accept': 'application/json'
    }
  })
    .then(response => {
      if (!response.ok) {
        throw new Error('Erro na requisição');
      }
      return response.json();
    })
    .then(data => {
      const content = document.getElementById('history-content');
      if (data.success && data.history.length > 0) {
        content.innerHTML = data.history.map(item => `
          <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-full bg-purple-100">
                <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
              </div>
              <div>
                <div class="font-medium text-slate-900">${item.customer_phone}</div>
                <div class="text-sm text-slate-500">Pedido #${item.order_id}</div>
              </div>
            </div>
            <div class="text-right">
              <div class="text-sm font-medium text-slate-900">${item.used_at}</div>
              <div class="text-xs text-slate-500">${item.time_ago}</div>
            </div>
          </div>
        `).join('');
      } else {
        content.innerHTML = `
          <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
            <p class="mt-2 text-sm text-slate-600">Nenhum uso registrado</p>
          </div>
        `;
      }
    })
    .catch(error => {
      console.error('Erro ao carregar histórico:', error);
      document.getElementById('history-content').innerHTML = `
        <div class="text-center py-8">
          <svg class="mx-auto h-12 w-12 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <p class="mt-2 text-sm text-red-600">Erro ao carregar histórico</p>
          <p class="mt-1 text-xs text-slate-500">${error.message}</p>
        </div>
      `;
    });
}

// === Seletor de Produtos para Taxa Embutida ===
let currentEmbCategory = 'all';

function filterEmbProducts(catId, btn) {
  currentEmbCategory = catId;
  // Update tab active class
  document.querySelectorAll('#emb-category-tabs .category-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');

  // Show/hide products
  document.querySelectorAll('.emb-product-item').forEach(item => {
    const itemCat = item.dataset.embCategory;
    item.style.display = (catId === 'all' || String(itemCat) === String(catId)) ? '' : 'none';
  });

  // Show category actions when a specific category is selected
  const catActions = document.getElementById('emb-cat-actions');
  if (catActions) {
    catActions.style.display = (catId === 'all') ? 'none' : 'flex';
  }
}

function toggleVisibleCategory(checked) {
  document.querySelectorAll('.emb-product-item').forEach(item => {
    if (item.style.display !== 'none') {
      const cb = item.querySelector('.product-checkbox');
      if (cb) {
        cb.checked = checked;
        item.classList.toggle('selected', checked);
        updateCheckSvg(item, checked);
      }
    }
  });
  updateProductCount();
}

function onProductToggle(cb) {
  const item = cb.closest('.emb-product-item');
  item.classList.toggle('selected', cb.checked);
  updateCheckSvg(item, cb.checked);
  updateProductCount();
}

function updateCheckSvg(item, checked) {
  const svg = item.querySelector('.emb-check-indicator svg');
  if (svg) svg.setAttribute('stroke', checked ? '#fff' : 'transparent');
}

function toggleAllProducts(checked) {
  document.querySelectorAll('.product-checkbox').forEach(cb => {
    cb.checked = checked;
    const item = cb.closest('.emb-product-item');
    if (item) {
      item.classList.toggle('selected', checked);
      updateCheckSvg(item, checked);
    }
  });
  updateProductCount();
}

function updateProductCount() {
  const total = document.querySelectorAll('.product-checkbox').length;
  const checked = document.querySelectorAll('.product-checkbox:checked').length;
  const label = document.getElementById('products-count-label');
  if (label) label.textContent = `(${checked} de ${total})`;
}
</script>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
