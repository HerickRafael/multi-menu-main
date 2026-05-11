<?php
/**
 * View: Admin - Criar/Editar Cupom
 */

// Garantir que há uma empresa selecionada
if (empty($company) || empty($slug)) {
    echo '<p>Erro: dados da empresa não encontrados.</p>';
    return;
}

$coupon = $coupon ?? null;
$usage_stats = $usage_stats ?? null;
$isEdit = !empty($coupon['id']);

$pageTitle = $isEdit ? 'Editar Cupom' : 'Criar Novo Cupom';
ob_start();

// Configuração do header padronizado
$pageTitleHeader = $pageTitle;
$pageDescription = $isEdit ? 'Atualize as informações do cupom' : 'Crie um novo cupom de desconto';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58s1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41s-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>';
$breadcrumbs = [
    ['label' => 'Fidelidade & Descontos', 'url' => base_url('admin/' . $slug . '/loyalty-discount')],
    ['label' => $isEdit ? 'Editar Cupom' : 'Novo Cupom']
];
$actions = [];

// Usar $slug ao invés de $activeSlug nesta página
$activeSlug = $slug;
?>

<div class="mx-auto max-w-4xl p-4">

<?php include __DIR__ . '/../components/page-header.php'; ?>

<!-- ALERTA DE ERRO -->
<?php if (!empty($error)): ?>
  <div class="mb-6 rounded-xl border border-red-200 bg-red-50/90 p-4 text-sm text-red-800 shadow-sm">
    <div class="flex items-start gap-2">
      <svg class="h-5 w-5 shrink-0 text-red-600" viewBox="0 0 24 24" fill="none">
        <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <div><?= e($error) ?></div>
    </div>
  </div>
<?php endif; ?>

<!-- ESTATÍSTICAS DE USO (apenas em edição) -->
<?php if ($isEdit && $usage_stats): ?>
  <div class="mb-6 grid gap-4 md:grid-cols-2">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="mb-2 flex items-center gap-2">
        <svg class="h-5 w-5 text-blue-600" viewBox="0 0 24 24" fill="none">
          <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke="currentColor" stroke-width="1.5"/>
        </svg>
        <span class="text-sm font-medium text-slate-600">Clientes Únicos</span>
      </div>
      <div class="text-2xl font-bold text-slate-900"><?= (int)($usage_stats['unique_customers'] ?? 0) ?></div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="mb-2 flex items-center gap-2">
        <svg class="h-5 w-5 text-green-600" viewBox="0 0 24 24" fill="none">
          <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" stroke="currentColor" stroke-width="1.5"/>
        </svg>
        <span class="text-sm font-medium text-slate-600">Total de Usos</span>
      </div>
      <div class="text-2xl font-bold text-slate-900"><?= (int)($usage_stats['total_uses'] ?? 0) ?></div>
    </div>
  </div>
<?php endif; ?>

<!-- FORMULÁRIO -->
<form method="post" 
      action="<?= e(base_url('admin/' . $slug . ($isEdit ? '/coupons/' . (int)$coupon['id'] . '/update' : '/coupons/store'))) ?>"
      class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
  
  <?php if (function_exists('csrf_field')): ?>
    <?= csrf_field() ?>
  <?php elseif (function_exists('csrf_token')): ?>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <?php endif; ?>

  <div class="space-y-6">
    
    <!-- Código do Cupom -->
    <div>
      <label class="mb-2 block text-sm font-medium text-slate-700">
        Código do Cupom
        <span class="text-red-500">*</span>
        <a href="<?= e(base_url('admin/' . $slug . '/guide/coupons')) ?>#form" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a>
      </label>
      <input type="text" 
             name="code" 
             value="<?= e($coupon['coupon_code'] ?? '') ?>"
             placeholder="Ex: PRIMEIRACOMPRA10"
             maxlength="50"
             required
             class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-900 uppercase focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
             style="text-transform: uppercase;">
      <small class="mt-1 block text-xs text-slate-500">
        Deixe em branco para gerar automaticamente. Use apenas letras, números e hífen.
      </small>
    </div>

    <!-- Telefone do Cliente (Opcional) -->
    <div>
      <label class="mb-2 block text-sm font-medium text-slate-700">
        Telefone do Cliente (Opcional)
        <a href="<?= e(base_url('admin/' . $slug . '/guide/coupons')) ?>#types" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a>
      </label>
      <input type="text" 
             name="customer_phone" 
             value="<?= e($coupon['customer_phone'] ?? '') ?>"
             placeholder="Ex: 11999999999"
             maxlength="20"
             class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-900 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
      <small class="mt-1 block text-xs text-slate-500">
        Deixe em branco para cupom genérico ou informe o telefone para cupom individual.
      </small>
    </div>

    <!-- Grid de 2 colunas -->
    <div class="grid gap-6 md:grid-cols-2">
      
      <!-- Porcentagem de Desconto -->
      <div>
        <label class="mb-2 block text-sm font-medium text-slate-700">
          Desconto (%)
          <span class="text-red-500">*</span>
          <a href="<?= e(base_url('admin/' . $slug . '/guide/coupons')) ?>#form" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a>
        </label>
        <input type="number" 
               name="discount_percentage" 
               value="<?= e($coupon['discount_percentage'] ?? '') ?>"
               min="1"
               max="100"
               step="0.01"
               required
               class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-900 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
        <small class="mt-1 block text-xs text-slate-500">
          Entre 1% e 100%
        </small>
      </div>

      <!-- Limite de Uso -->
      <div>
        <label class="mb-2 block text-sm font-medium text-slate-700">
          Limite de Usos
          <span class="text-red-500">*</span>
          <a href="<?= e(base_url('admin/' . $slug . '/guide/coupons')) ?>#limits" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a>
        </label>
        <input type="number" 
               name="usage_limit" 
               value="<?= e($coupon['usage_limit'] ?? '1') ?>"
               min="1"
               max="1000"
               required
               class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-900 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
        <small class="mt-1 block text-xs text-slate-500">
          Quantas vezes o cupom pode ser usado no total
        </small>
      </div>

      <!-- Permitir múltiplos usos por cliente -->
      <div>
        <label class="mb-2 block text-sm font-medium text-slate-700">
          Uso por Cliente
          <a href="<?= e(base_url('admin/' . $slug . '/guide/coupons')) ?>#limits" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a>
        </label>
        <div class="flex items-center gap-3">
          <label class="relative inline-flex cursor-pointer items-center">
            <input type="checkbox" 
                   name="allow_multiple_uses_per_customer" 
                   value="1"
                   <?= !empty($coupon['allow_multiple_uses_per_customer']) && (int)$coupon['allow_multiple_uses_per_customer'] === 1 ? 'checked' : '' ?>
                   class="peer sr-only">
            <div class="peer h-6 w-11 rounded-full bg-slate-300 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-600 peer-checked:after:translate-x-full peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-400"></div>
          </label>
          <span class="text-sm text-slate-600">
            Permitir que o mesmo cliente use múltiplas vezes
          </span>
        </div>
        <small class="mt-1 block text-xs text-slate-500">
          Se desativado, cada cliente só poderá usar o cupom uma vez
        </small>
      </div>
    </div>

    <!-- Informações Atuais (apenas em edição) -->
    <?php if ($isEdit): ?>
      <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-4">
        <div class="mb-2 flex items-center gap-2">
          <svg class="h-5 w-5 text-blue-600" viewBox="0 0 24 24" fill="none">
            <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.5"/>
          </svg>
          <span class="text-sm font-semibold text-blue-900">Status Atual</span>
        </div>
        <div class="space-y-1 text-sm text-blue-900">
          <div><strong>Vezes usado:</strong> <?= (int)($coupon['times_used'] ?? 0) ?> de <?= (int)($coupon['usage_limit'] ?? 0) ?></div>
          <div><strong>Status:</strong> 
            <?php if ((int)($coupon['is_used'] ?? 0) === 1): ?>
              <span class="text-red-600">Esgotado</span>
            <?php else: ?>
              <span class="text-green-600">Disponível</span>
            <?php endif; ?>
          </div>
          <?php if (!empty($coupon['used_at'])): ?>
            <div><strong>Última utilização:</strong> <?= e(date('d/m/Y H:i', strtotime($coupon['used_at']))) ?></div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

  </div>

  <!-- Botões -->
  <div class="mt-6 flex items-center justify-end gap-3">
    <a href="<?= e(base_url('admin/' . $slug . '/loyalty-discount')) ?>"
       class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
      Cancelar
    </a>
    <button type="submit"
            class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-6 py-2 text-sm font-medium text-white shadow-sm hover:opacity-95 transition-opacity">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
        <path d="M20 7 9 18l-5-5" stroke="currentColor" stroke-width="1.5"/>
      </svg>
      <?= $isEdit ? 'Atualizar Cupom' : 'Criar Cupom' ?>
    </button>
  </div>

</form>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
