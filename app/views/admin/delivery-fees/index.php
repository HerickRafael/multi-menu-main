<?php
// admin/delivery-fees/index.php

$company      = is_array($company ?? null) ? $company : [];
$cities       = is_array($cities ?? null) ? $cities : [];
$zones        = is_array($zones ?? null) ? $zones : [];
$cityErrors   = is_array($cityErrors ?? null) ? $cityErrors : [];
$zoneErrors   = is_array($zoneErrors ?? null) ? $zoneErrors : [];
$optionErrors = is_array($optionErrors ?? null) ? $optionErrors : [];
$bulkErrors   = is_array($bulkErrors ?? null) ? $bulkErrors : [];
$oldCity      = is_array($oldCity ?? null) ? $oldCity : ['name' => ''];
$oldZone      = is_array($oldZone ?? null) ? $oldZone : ['city_id' => '', 'neighborhood' => '', 'fee' => ''];
$optionValues = is_array($optionValues ?? null) ? $optionValues : [];
$bulkValue    = isset($bulkValue) ? (string)$bulkValue : '';
$citySearch   = isset($citySearch) ? trim((string)$citySearch) : '';
$zoneSearch   = isset($zoneSearch) ? trim((string)$zoneSearch) : '';
$editCityId   = isset($editCityId) ? (int)$editCityId : 0;
$editZoneId   = isset($editZoneId) ? (int)$editZoneId : 0;
$flash        = is_array($flash ?? null) ? $flash : [];

$title        = 'Taxas de entrega - ' . ($company['name'] ?? '');
$slug         = rawurlencode((string)($company['slug'] ?? ''));

// Contagem de bairros por cidade
$zoneCountByCity = [];

foreach ($zones as $zone) {
    $cityId = (int)($zone['city_id'] ?? 0);

    if (!isset($zoneCountByCity[$cityId])) {
        $zoneCountByCity[$cityId] = 0;
    }
    $zoneCountByCity[$cityId]++;
}

$basePath   = base_url('admin/' . $slug . '/delivery-fees');
$queryState = [];

if ($citySearch !== '') {
    $queryState['city_search'] = $citySearch;
}

if ($zoneSearch !== '') {
    $queryState['zone_search'] = $zoneSearch;
}

if (!function_exists('delivery_query_suffix')) {
    function delivery_query_suffix(array $current, array $overrides = [], array $remove = []): string
    {
        foreach ($remove as $key) {
            unset($current[$key]);
        }

        foreach ($overrides as $key => $value) {
            if ($value === null || $value === '') {
                unset($current[$key]);
            } else {
                $current[$key] = $value;
            }
        }

        if (!$current) {
            return '';
        }

        return '?' . http_build_query($current);
    }
}

ob_start();
?>

<div class="mx-auto max-w-6xl p-4">

<?php
// Configuração do header padronizado
$pageTitle = 'Taxas de entrega';
$pageDescription = 'Cadastre primeiro as cidades atendidas e, depois, os bairros vinculados a cada uma.';
$pageIcon = '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 16 16"><path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5v7A1.5 1.5 0 0 1 10.5 12H10a2 2 0 1 1-4 0H4a2 2 0 1 1-3.874-.5A1.5 1.5 0 0 1 0 10.5zm1.5-.5a.5.5 0 0 0-.5.5v5.473A2 2 0 0 1 3.874 11H6V3h4.5a.5.5 0 0 0 .5-.5V3h.086a1.5 1.5 0 0 1 1.3.75l1.528 2.75a1.5 1.5 0 0 1 .186.725V9.5A1.5 1.5 0 0 1 12.5 11H12a2 2 0 1 1-4 0H6v1h4.5a.5.5 0 0 0 .5-.5V9h1.5a.5.5 0 0 0 .5-.5v-.525a.5.5 0 0 0-.062-.242l-1.528-2.75A.5.5 0 0 0 11.438 5H11V3.5A1.5 1.5 0 0 0 9.5 2z"/></svg>';
$breadcrumbs = [
    ['label' => 'Taxas de entrega']
];
$actions = [
    ['label' => 'Dashboard', 'url' => base_url('admin/' . $slug . '/dashboard'), 'icon' => '<svg class="h-4 w-4" fill="currentColor" viewBox="0 0 16 16"><path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/></svg>']
];
include __DIR__ . '/../components/page-header.php';
?>

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
            class="section-btn active flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition-colors admin-gradient-bg text-white"
            data-section="ajustes"
            onclick="switchSection('ajustes')">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
      </svg>
      Ajustes Rápidos
    </button>
    <button type="button" 
            class="section-btn flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition-colors bg-slate-50 text-slate-700 hover:bg-slate-100"
            data-section="cidades"
            onclick="switchSection('cidades')">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
      </svg>
      Cidades
    </button>
    <button type="button" 
            class="section-btn flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition-colors bg-slate-50 text-slate-700 hover:bg-slate-100"
            data-section="bairros"
            onclick="switchSection('bairros')">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
        <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>
      Bairros
    </button>
  </div>
</div>

<!-- SEÇÃO 1: AJUSTES RÁPIDOS -->
<div id="section-ajustes" class="section-content">
  <div class="space-y-6">

  <?php if ($bulkErrors): ?>
    <div class="rounded-xl border border-red-200 bg-red-50/90 p-3 text-sm text-red-800 shadow-sm">
      <?php foreach ($bulkErrors as $error): ?>
        <div><?= e($error) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($optionErrors): ?>
    <div class="rounded-xl border border-red-200 bg-red-50/90 p-3 text-sm text-red-800 shadow-sm">
      <?php foreach ($optionErrors as $error): ?>
        <div><?= e($error) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Grid de ajustes rápidos -->
  <div class="grid gap-6 lg:grid-cols-2">
    
    <!-- 1. Ajuste em lote de todas as taxas -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/zones/adjust')) ?>">
        <?php if (function_exists('csrf_field')): ?>
          <?= csrf_field() ?>
        <?php elseif (function_exists('csrf_token')): ?>
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php endif; ?>
        
        <div class="mb-4 flex items-center gap-3">
          <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M4 12h16M12 4v16"/>
            </svg>
          </span>
          <h3 class="text-lg font-semibold text-slate-900">Ajuste em Lote</h3>
        </div>
        
        <label class="mb-4 grid gap-2">
          <span class="text-sm font-medium text-slate-700">Valor do ajuste (R$) <a href="<?= e(base_url('admin/' . $slug . '/guide/delivery-fees#ajustes')) ?>" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></span>
          <input type="number" 
                 step="0.01" 
                 name="delta" 
                 value="<?= e($bulkValue) ?>" 
                 placeholder="Ex.: 2.00 ou -1.50" 
                 class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-900 focus:ring-2 focus:ring-indigo-400">
          <small class="text-xs text-slate-500">Aumente (valor positivo) ou diminua (valor negativo) todas as taxas de uma vez.</small>
        </label>
        
        <button type="submit" 
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl admin-gradient-bg px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:opacity-95 transition-opacity">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
            <path d="M20 7 9 18l-5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Aplicar Ajuste
        </button>
      </form>
    </div>

    <!-- 2. Adicional após as 18h -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/options')) ?>">
        <?php if (function_exists('csrf_field')): ?>
          <?= csrf_field() ?>
        <?php elseif (function_exists('csrf_token')): ?>
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php endif; ?>
        <input type="hidden" name="free_delivery" value="<?= (int)($optionValues['free_delivery'] ?? 0) ?>">
        
        <div class="mb-4 flex items-center gap-3">
          <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <circle cx="12" cy="12" r="10"/>
              <path d="M12 6v6l4 2"/>
            </svg>
          </span>
          <h3 class="text-lg font-semibold text-slate-900">Taxa Após 18h</h3>
        </div>
        
        <label class="mb-4 grid gap-2">
          <span class="text-sm font-medium text-slate-700">Adicional (R$) <a href="<?= e(base_url('admin/' . $slug . '/guide/delivery-fees#ajustes')) ?>" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></span>
          <input type="number" 
                 step="0.01" 
                 min="0" 
                 name="after_hours_fee" 
                 value="<?= e($optionValues['after_hours_fee'] ?? '0.00') ?>" 
                 class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-900 focus:ring-2 focus:ring-indigo-400">
          <small class="text-xs text-slate-500">Valor somado automaticamente às entregas após as 18h.</small>
        </label>
        
        <button type="submit" 
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl admin-gradient-bg px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:opacity-95 transition-opacity">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
            <path d="M20 7 9 18l-5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Salvar Adicional
        </button>
      </form>
    </div>

    <!-- 3. Taxa gratuita (toggle) -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="mb-4 flex items-center gap-3">
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </span>
        <div class="flex-1">
          <h3 class="text-lg font-semibold text-slate-900">Taxa Gratuita <a href="<?= e(base_url('admin/' . $slug . '/guide/delivery-fees#ajustes')) ?>" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></h3>
        </div>
        
        <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/options')) ?>" class="inline">
          <?php if (function_exists('csrf_field')): ?>
            <?= csrf_field() ?>
          <?php elseif (function_exists('csrf_token')): ?>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <?php endif; ?>
          <input type="hidden" name="after_hours_fee" value="<?= e($optionValues['after_hours_fee'] ?? '0.00') ?>">
          <input type="hidden" name="free_delivery" value="<?= (int)($optionValues['free_delivery'] ?? 0) ? 0 : 1 ?>">
          
          <button type="submit" 
                  class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 <?= (int)($optionValues['free_delivery'] ?? 0) ? 'bg-emerald-600' : 'bg-slate-300' ?>">
            <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow-sm transition-transform <?= (int)($optionValues['free_delivery'] ?? 0) ? 'translate-x-6' : 'translate-x-1' ?>"></span>
          </button>
        </form>
      </div>
      
      <p class="text-sm text-slate-600 mb-3">
        <?php if ((int)($optionValues['free_delivery'] ?? 0)): ?>
          <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
              <circle cx="12" cy="12" r="10"/>
            </svg>
            Ativado
          </span>
          <span class="ml-2 text-slate-600">Todas as entregas estão gratuitas.</span>
        <?php else: ?>
          <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
              <circle cx="12" cy="12" r="10"/>
            </svg>
            Desativado
          </span>
          <span class="ml-2 text-slate-600">Taxas de entrega aplicadas normalmente.</span>
        <?php endif; ?>
      </p>
      
      <?php if ((int)($optionValues['free_delivery'] ?? 0)): ?>
      <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-800">
        <strong>⚠️ Atenção:</strong> O frete grátis promocional foi desativado automaticamente.
      </div>
      <?php endif; ?>
    </div>

    <!-- 4. Frete grátis a partir de um valor -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/free-shipping')) ?>">
        <?php if (function_exists('csrf_field')): ?>
          <?= csrf_field() ?>
        <?php elseif (function_exists('csrf_token')): ?>
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php endif; ?>
        
        <div class="mb-4 flex items-center gap-3">
          <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-green-100 text-green-700">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 16 16">
              <path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-3.998-.085A1.5 1.5 0 0 1 0 10.5zm1.294 7.456A2 2 0 0 1 4.732 11h5.536a2 2 0 0 1 .732-.732V3.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .294.456M12 10a2 2 0 0 1 1.732 1h.768a.5.5 0 0 0 .5-.5V8.35a.5.5 0 0 0-.11-.312l-1.48-1.85A.5.5 0 0 0 13.02 6H12zm-9 1a1 1 0 1 0 0 2 1 1 0 0 0 0-2m9 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2"/>
            </svg>
          </span>
          <h3 class="text-lg font-semibold text-slate-900">Frete Grátis Promocional</h3>
        </div>
        
        <label class="mb-4 grid gap-2">
          <span class="text-sm font-medium text-slate-700">Valor mínimo do pedido (R$) <a href="<?= e(base_url('admin/' . $slug . '/guide/delivery-fees#ajustes')) ?>" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></span>
          <input type="number" 
                 step="0.01" 
                 min="0" 
                 name="delivery_free_min_value" 
                 value="<?= e($company['delivery_free_min_value'] ?? '0.00') ?>" 
                 class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-900 focus:ring-2 focus:ring-indigo-400"
                 placeholder="Ex: 50.00">
          <small class="text-xs text-slate-500">Ao atingir este valor, o frete é grátis. Deixe em 0.00 para desativar.</small>
        </label>
        
        <button type="submit" 
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl admin-gradient-bg px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:opacity-95 transition-opacity">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
            <path d="M20 7 9 18l-5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Salvar Promoção
        </button>
        
        <?php if (isset($company['delivery_free_min_value']) && (float)$company['delivery_free_min_value'] > 0): ?>
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-sm text-emerald-800">
          <strong>✓ Ativo:</strong> Frete grátis em pedidos acima de R$ <?= e(number_format((float)$company['delivery_free_min_value'], 2, ',', '.')) ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($company['delivery_free_min_value']) && (float)$company['delivery_free_min_value'] > 0): ?>
        <div class="mt-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-800">
          <strong>⚠️ Atenção:</strong> A taxa gratuita para todos foi desativada automaticamente.
        </div>
        <?php endif; ?>
      </form>
    </div>
  </div>
  </div>
</div>

<!-- SEÇÃO 2: CIDADES -->
<div id="section-cidades" class="section-content hidden">
<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4">
      <h2 class="text-lg font-semibold text-slate-800">1. Cadastrar cidades atendidas</h2>
      <p class="text-sm text-slate-500">As taxas de bairro ficam vinculadas a uma das cidades abaixo.</p>
    </div>

    <?php if ($cityErrors): ?>
      <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <strong class="font-semibold">Corrija os campos da cidade:</strong>
        <ul class="mt-2 list-disc space-y-1 pl-4">
          <?php foreach ($cityErrors as $error): ?>
            <li><?= e($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php
      $cityFormAction = $editCityId && !empty($oldCity['id'])
        ? base_url('admin/' . $slug . '/delivery-fees/cities/' . (int)$oldCity['id'])
        : base_url('admin/' . $slug . '/delivery-fees/cities');
?>
    <form method="post" action="<?= e($cityFormAction) ?>" class="grid gap-4">
      <?php if (function_exists('csrf_field')): ?>
        <?= csrf_field() ?>
      <?php elseif (function_exists('csrf_token')): ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <?php endif; ?>

      <div class="grid gap-2">
        <label for="city-name" class="text-sm font-medium text-slate-700">Nome da cidade <span class="text-red-500">*</span></label>
        <input type="text" id="city-name" name="name" value="<?= e($oldCity['name'] ?? '') ?>" required
               maxlength="120"
               class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
               placeholder="Ex.: São Paulo">
      </div>

      <div class="flex items-center justify-end gap-3">
        <?php if ($editCityId && !empty($oldCity['id'])): ?>
          <a href="<?= e($basePath . delivery_query_suffix($queryState, [], ['edit_city'])) ?>"
             class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
            Cancelar
          </a>
        <?php endif; ?>

        <button type="submit" class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          <?= $editCityId && !empty($oldCity['id']) ? 'Atualizar cidade' : 'Salvar cidade' ?>
        </button>
      </div>
    </form>

    <div class="mt-6">
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Cidades cadastradas</h3>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">Total: <?= count($cities) ?></span>
      </div>

      <form method="get" action="<?= e($basePath) ?>" class="mb-3 flex items-center gap-2 text-sm" data-js="city-search-form">
        <input type="search" name="city_search" value="<?= e($citySearch) ?>" placeholder="Buscar cidade..."
               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
               data-js="city-search-input">
        <?php if ($zoneSearch !== ''): ?>
          <input type="hidden" name="zone_search" value="<?= e($zoneSearch) ?>">
        <?php endif; ?>
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-700 shadow-sm hover:bg-slate-50">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="m11 4-7 8h8l-1 8 7-8h-8l1-8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
          Buscar
        </button>
        <?php if ($citySearch !== ''): ?>
          <a href="<?= e($basePath . delivery_query_suffix($queryState, ['city_search' => null])) ?>"
             class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-500 hover:bg-slate-50">Limpar</a>
        <?php endif; ?>
      </form>

      <?php if (!$cities): ?>
        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
          Nenhuma cidade cadastrada ainda.
        </div>
      <?php else: ?>
        <ul class="space-y-2" data-js="city-list">
          <?php foreach ($cities as $city): ?>
            <li class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                data-js="city-item"
                data-city-name="<?= e(strtolower($city['name'] ?? '')) ?>">
              <div>
                <div class="font-medium text-slate-800"><?= e($city['name'] ?? '') ?></div>
                <div class="text-xs text-slate-500">
                  <?= (int)($zoneCountByCity[(int)($city['id'] ?? 0)] ?? 0) ?> bairro(s) cadastrados
                </div>
              </div>
              <div class="flex items-center gap-2">
                <a href="<?= e($basePath . delivery_query_suffix($queryState, ['edit_city' => (int)($city['id'] ?? 0)], ['edit_zone'])) ?>"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                  <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M5 16.5 16.5 5 19 7.5 7.5 19H5v-2.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  Editar
                </a>
                <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/cities/' . (int)($city['id'] ?? 0) . '/del')) ?>"
                      onsubmit="return confirm('Remover esta cidade? Bairros vinculados também serão excluídos.');">
                  <?php if (function_exists('csrf_field')): ?>
                    <?= csrf_field() ?>
                  <?php elseif (function_exists('csrf_token')): ?>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <?php endif; ?>
                  <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-700 shadow-sm hover:bg-red-50">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 7h12M9 7v11m6-11v11M8 7l1-2h6l1 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    Excluir
                  </button>
                </form>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="hidden rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500"
             data-js="city-empty">
          Nenhuma cidade encontrada para a busca atual.
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>

<!-- SEÇÃO 3: BAIRROS -->
<div id="section-bairros" class="section-content hidden">
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4">
      <h2 class="text-lg font-semibold text-slate-800">2. Cadastrar bairros e taxas</h2>
      <p class="text-sm text-slate-500">Selecione a cidade e informe o bairro com a taxa correspondente.</p>
    </div>

    <?php if ($zoneErrors): ?>
      <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <strong class="font-semibold">Corrija os campos do bairro:</strong>
        <ul class="mt-2 list-disc space-y-1 pl-4">
          <?php foreach ($zoneErrors as $error): ?>
            <li><?= e($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php
  $zoneFormAction = $editZoneId && !empty($oldZone['id'])
    ? base_url('admin/' . $slug . '/delivery-fees/zones/' . (int)$oldZone['id'])
    : base_url('admin/' . $slug . '/delivery-fees/zones');
?>
    <form method="post" action="<?= e($zoneFormAction) ?>" class="grid gap-4">
      <?php if (function_exists('csrf_field')): ?>
        <?= csrf_field() ?>
      <?php elseif (function_exists('csrf_token')): ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <?php endif; ?>

      <div class="grid gap-2">
        <label for="zone-city" class="text-sm font-medium text-slate-700">Cidade <span class="text-red-500">*</span></label>
        <select id="zone-city" name="city_id" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200" <?= $cities ? '' : 'disabled' ?> required>
          <option value="">Selecione uma cidade</option>
          <?php foreach ($cities as $city): ?>
            <option value="<?= (int)($city['id'] ?? 0) ?>" <?= ((string)($oldZone['city_id'] ?? '') === (string)($city['id'] ?? '')) ? 'selected' : '' ?>><?= e($city['name'] ?? '') ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (!$cities): ?>
          <span class="text-xs text-amber-600">Cadastre ao menos uma cidade antes de registrar bairros.</span>
        <?php endif; ?>
      </div>

      <div class="grid gap-2">
        <label for="zone-neighborhood" class="text-sm font-medium text-slate-700">Bairro <span class="text-red-500">*</span></label>
        <input type="text" id="zone-neighborhood" name="neighborhood" value="<?= e($oldZone['neighborhood'] ?? '') ?>" required maxlength="120"
               class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
               placeholder="Ex.: Centro">
      </div>

      <div class="grid gap-2">
        <label for="zone-fee" class="text-sm font-medium text-slate-700">Taxa de entrega (R$) <span class="text-red-500">*</span></label>
        <input type="number" min="0" step="0.01" inputmode="decimal" id="zone-fee" name="fee" value="<?= e($oldZone['fee'] ?? '') ?>" required
               class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
               placeholder="Ex.: 8,00">
      </div>

      <div class="flex items-center justify-end gap-3">
        <?php if ($editZoneId && !empty($oldZone['id'])): ?>
          <a href="<?= e($basePath . delivery_query_suffix($queryState, [], ['edit_zone'])) ?>"
             class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
            Cancelar
          </a>
        <?php endif; ?>
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95" <?= $cities ? '' : 'disabled' ?>>
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          <?= $editZoneId && !empty($oldZone['id']) ? 'Atualizar taxa' : 'Salvar taxa' ?>
        </button>
      </div>
    </form>

    <div class="mt-6">
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Bairros cadastrados</h3>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">Total: <?= count($zones) ?></span>
      </div>

      <form method="get" action="<?= e($basePath) ?>" class="mb-3 flex items-center gap-2 text-sm" data-js="zone-search-form">
        <?php if ($citySearch !== ''): ?>
          <input type="hidden" name="city_search" value="<?= e($citySearch) ?>">
        <?php endif; ?>
        <input type="search" name="zone_search" value="<?= e($zoneSearch) ?>" placeholder="Buscar por bairro ou cidade..."
               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
               data-js="zone-search-input">
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-700 shadow-sm hover:bg-slate-50">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="m11 4-7 8h8l-1 8 7-8h-8l1-8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
          Buscar
        </button>
        <?php if ($zoneSearch !== ''): ?>
          <a href="<?= e($basePath . delivery_query_suffix($queryState, ['zone_search' => null])) ?>"
             class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-500 hover:bg-slate-50">Limpar</a>
        <?php endif; ?>
      </form>

      <?php if (!$zones): ?>
        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
          Nenhuma taxa cadastrada ainda.
        </div>
      <?php else: ?>
        <div class="max-h-[520px] overflow-auto rounded-xl border border-slate-200" data-js="zone-table-wrapper">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-600">
              <tr>
                <th class="p-3">Cidade</th>
                <th class="p-3">Bairro</th>
                <th class="p-3">Taxa</th>
                <th class="p-3 text-right">Ações</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100" data-js="zone-body">
              <?php foreach ($zones as $zone): ?>
                <tr class="hover:bg-slate-50/70"
                    data-js="zone-row"
                    data-zone-search="<?= e(strtolower(($zone['city_name'] ?? '') . ' ' . ($zone['neighborhood'] ?? ''))) ?>">
                  <td class="p-3 align-middle font-medium text-slate-800"><?= e($zone['city_name'] ?? '') ?></td>
                  <td class="p-3 align-middle text-slate-700"><?= e($zone['neighborhood'] ?? '') ?></td>
                  <td class="p-3 align-middle text-slate-700">R$ <?= number_format((float)($zone['fee'] ?? 0), 2, ',', '.') ?></td>
                  <td class="p-3 align-middle">
                    <div class="flex justify-end gap-2">
                      <a href="<?= e($basePath . delivery_query_suffix($queryState, ['edit_zone' => (int)($zone['id'] ?? 0)], ['edit_city'])) ?>"
                         class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M5 16.5 16.5 5 19 7.5 7.5 19H5v-2.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Editar
                      </a>
                      <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/zones/' . (int)($zone['id'] ?? 0) . '/del')) ?>"
                            onsubmit="return confirm('Remover esta taxa de entrega?');">
                        <?php if (function_exists('csrf_field')): ?>
                          <?= csrf_field() ?>
                        <?php elseif (function_exists('csrf_token')): ?>
                          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <?php endif; ?>
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-700 shadow-sm hover:bg-red-50">
                          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 7h12M9 7v11m6-11v11M8 7l1-2h6l1 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                          Excluir
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <tr class="hidden" data-js="zone-empty">
                <td colspan="4" class="p-4 text-center text-sm text-slate-500">Nenhum bairro encontrado para a busca atual.</td>
              </tr>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>
</div>

<!-- delivery-fees search behaviors migrated to public/assets/js/admin.js -->

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

// Auto-navegar para a aba correta baseado nos parâmetros da URL
(function() {
  const urlParams = new URLSearchParams(window.location.search);
  
  if (urlParams.has('edit_zone') || urlParams.has('zone_search')) {
    // Se está editando zona ou buscando bairro, ir para aba de bairros
    switchSection('bairros');
  } else if (urlParams.has('edit_city') || urlParams.has('city_search')) {
    // Se está editando cidade ou buscando cidade, ir para aba de cidades
    switchSection('cidades');
  }
})();
</script>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
