<?php
// admin/dashboard/index.php — Dashboard (estilo moderno coeso)

// Helpers (caso a view seja renderizada isolada)

// Normalizações seguras
$company            = is_array($company ?? null) ? $company : [];
$categories         = is_array($categories ?? null) ? $categories : [];
$products           = is_array($products ?? null) ? $products : [];
$recentIngredients  = is_array($recentIngredients ?? null) ? $recentIngredients : [];
$recentOrders       = is_array($recentOrders ?? null) ? $recentOrders : []; // <— NOVO: lista de últimos pedidos
$ingredientsCount   = (int)($ingredientsCount ?? 0);
$ordersCount        = (int)($ordersCount ?? 0);

// Slugs/título com fallback
$activeSlug = (string)($activeSlug ?? ($company['slug'] ?? ''));
$slug       = rawurlencode($activeSlug);
$publicSlug = rawurlencode((string)($company['slug'] ?? ''));
$title      = 'Dashboard - ' . ($company['name'] ?? 'Empresa');

// Pequenos helpers
$price = function ($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); };

ob_start(); ?>

<div class="mx-auto max-w-6xl p-4">

<?php
// Configuração do header padronizado
$pageTitle = 'Dashboard';
$pageDescription = 'Visão geral do seu sistema';
$pageIcon = '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5V6a.5.5 0 0 1-1 0V4.5A.5.5 0 0 1 8 4M3.732 5.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 1 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707M2 10a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 10m9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5m.754-4.246a.39.39 0 0 0-.527-.02L7.547 9.31a.91.91 0 1 0 1.302 1.258l3.434-4.297a.39.39 0 0 0-.029-.518z"/><path fill-rule="evenodd" d="M0 10a8 8 0 1 1 15.547 2.661c-.442 1.253-1.845 1.602-2.932 1.25C11.309 13.488 9.475 13 8 13c-1.474 0-3.31.488-4.615.911-1.087.352-2.49.003-2.932-1.25A8 8 0 0 1 0 10m8-7a7 7 0 0 0-6.603 9.329c.203.575.923.876 1.68.63C4.397 12.533 6.358 12 8 12s3.604.532 4.923.96c.757.245 1.477-.056 1.68-.631A7 7 0 0 0 8 3"/></svg>';
$breadcrumbs = []; // Dashboard não precisa de breadcrumb, é a raiz
$actions = [];
include __DIR__ . '/../components/page-header.php';
?>

<!-- HERO / TOPO -->
<section class="relative mb-6 overflow-hidden rounded-3xl border border-slate-200 admin-gradient-bg text-white">
  <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-2xl"></div>
  <div class="absolute -bottom-16 -left-16 h-64 w-64 rounded-full bg-black/10 blur-3xl"></div>

  <div class="relative z-10 grid gap-4 p-5 md:grid-cols-[auto_1fr_auto] md:items-center md:p-7">
    <div class="inline-flex h-24 w-24 items-center justify-center rounded-2xl bg-white/10 p-0.5 ring-1 ring-white/30">
      <?php if (!empty($company['logo'])): ?>
        <img src="<?= e(base_url($company['logo'])) ?>" alt="Logo" 
             class="h-24 w-24 rounded-[0.9rem] object-cover ring-1 ring-black/10"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <div class="h-24 w-24 rounded-[0.9rem] ring-1 ring-black/10 bg-white/5 hidden items-center justify-center">
          <svg class="w-12 h-12 text-white/40" viewBox="0 0 24 24" fill="none">
            <path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      <?php else: ?>
        <div class="h-24 w-24 rounded-[0.9rem] ring-1 ring-black/10 bg-white/5 flex items-center justify-center">
          <svg class="w-12 h-12 text-white/40" viewBox="0 0 24 24" fill="none">
            <path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      <?php endif; ?>
    </div>

    <div class="text-white">
      <h1 class="text-2xl font-semibold leading-tight">
        <?= e($company['name'] ?? '—') ?>
      </h1>
      <p class="mt-0.5 text-sm text-white/80">
        Categorias: <?= (int)count($categories) ?> • Produtos: <?= (int)count($products) ?>
        <?php if (!empty($company['hours_text'])): ?> • Horário: <?= e($company['hours_text']) ?><?php endif; ?>
        <?php if (isset($company['min_order'])): ?> • Mín.: <?= $price($company['min_order']) ?><?php endif; ?>
      </p>
    </div>

    <div class="flex flex-wrap gap-2">
      <a href="<?= e(base_url('admin/' . $slug . '/financial')) ?>" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-sm text-white ring-1 ring-white/30 hover:bg-white/15">
        <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
          <path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73l.348.086z"/>
        </svg>
        Financeiro
      </a>
      <a href="<?= e(base_url('admin/' . $slug . '/api')) ?>" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-sm text-white ring-1 ring-white/30 hover:bg-white/15">
        <svg width="18" height="18" fill="currentColor" class="bi bi-code-slash" viewBox="0 0 16 16">
          <path d="M10.478 1.647a.5.5 0 1 0-.956-.294l-4 13a.5.5 0 0 0 .956.294zM4.854 4.146a.5.5 0 0 1 0 .708L1.707 8l3.147 3.146a.5.5 0 0 1-.708.708l-3.5-3.5a.5.5 0 0 1 0-.708l3.5-3.5a.5.5 0 0 1 .708 0zm6.292 0a.5.5 0 0 0 0 .708L14.293 8l-3.147 3.146a.5.5 0 0 0 .708.708l3.5-3.5a.5.5 0 0 0 0-.708l-3.5-3.5a.5.5 0 0 0-.708 0z"/>
        </svg>
        API
      </a>
      <a href="<?= e(base_url('admin/' . $slug . '/kds')) ?>" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-sm text-white ring-1 ring-white/30 hover:bg-white/15">
        <svg width="18" height="18" fill="currentColor" class="bi bi-display" viewBox="0 0 16 16">
          <path d="M0 1a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1z"/>
          <path d="M2 13.5a.5.5 0 0 1 .5-.5H6v-1H3.5a.5.5 0 0 1 0-1h9a.5.5 0 0 1 0 1H10v1h3.5a.5.5 0 0 1 0 1H2.5a.5.5 0 0 1-.5-.5"/>
        </svg>
        Abrir KDS
      </a>
      <a href="<?= e(base_url('admin/' . $slug . '/settings')) ?>" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-sm text-white ring-1 ring-white/30 hover:bg-white/15">
<svg width="16" height="16" fill="currentColor" class="bi bi-gear-fill" viewBox="0 0 16 16">
  <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z"/>
</svg>
        Configurações
      </a>
      <a href="<?= e(base_url($publicSlug)) ?>" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-sm text-white ring-1 ring-white/30 hover:bg-white/15">
        <svg width="20" height="20" fill="currentColor" class="bi bi-house" viewBox="0 0 16 16">
          <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V7.207l5-5z"/>
        </svg>
        Ver cardápio
      </a>
      <a href="<?= e(base_url('admin/' . $slug . '/logout')) ?>" class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-sm font-medium text-slate-900 shadow hover:opacity-95">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M15 7l5 5-5 5M20 12H9M15 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Sair
      </a>
    </div>
  </div>
</section>

<!-- PAUSA PROGRAMADA -->
<?php
// Carrega status da pausa programada
require_once __DIR__ . '/../../../services/ScheduledPauseService.php';
$pauseService = new ScheduledPauseService(db());
$pauseStatus = $pauseService->getPauseStatus((int)$company['id']);
$isPaused = $pauseStatus['is_paused'];
?>
<section id="scheduled-pause-section" class="mb-6 rounded-2xl border <?= $isPaused ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-white' ?> p-5 shadow-sm">
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex items-center gap-3">
      <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl <?= $isPaused ? 'bg-amber-200 text-amber-700' : 'bg-slate-100 text-slate-600' ?>">
        <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
          <path d="M6 3.5a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-1 0V4a.5.5 0 0 1 .5-.5zm4 0a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-1 0V4a.5.5 0 0 1 .5-.5z"/>
        </svg>
      </div>
      <div>
        <h3 class="font-semibold text-slate-900 flex items-center gap-2">
          Pausa Programada
          <?php if ($isPaused): ?>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-200 text-amber-800">
              ATIVO
            </span>
          <?php endif; ?>
        </h3>
        <p class="text-sm text-slate-600" id="pause-status-text">
          <?php if ($isPaused): ?>
            <?php if ($pauseStatus['pause_type'] === 'indefinite'): ?>
              Loja em pausa indefinida
            <?php else: ?>
              Retorna em: <strong id="pause-remaining"><?= e($pauseStatus['remaining_text'] ?? 'Calculando...') ?></strong>
            <?php endif; ?>
            <?php if (!empty($pauseStatus['pause_reason'])): ?>
              — <em><?= e($pauseStatus['pause_reason']) ?></em>
            <?php endif; ?>
          <?php else: ?>
            Pause temporariamente o recebimento de pedidos
          <?php endif; ?>
        </p>
      </div>
    </div>
    
    <div class="flex items-center gap-2 flex-wrap">
      <?php if ($isPaused): ?>
        <!-- Botões quando pausado -->
        <?php if ($pauseStatus['pause_type'] !== 'indefinite'): ?>
        <button type="button" onclick="extendPause()" 
                class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-amber-700 bg-amber-100 rounded-xl hover:bg-amber-200 transition">
          <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
          </svg>
          Estender
        </button>
        <?php endif; ?>
        <button type="button" onclick="disablePause()" 
                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition">
          <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M11.596 8.697l-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.692-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393"/>
          </svg>
          Retomar Atendimento
        </button>
      <?php else: ?>
        <!-- Botão para ativar pausa -->
        <button type="button" onclick="openPauseModal()" 
                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-xl hover:bg-amber-700 transition">
          <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M6 3.5a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-1 0V4a.5.5 0 0 1 .5-.5zm4 0a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-1 0V4a.5.5 0 0 1 .5-.5z"/>
          </svg>
          Pausar Loja
        </button>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Modal de Pausa Programada -->
<div id="pause-modal" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/50" onclick="closePauseModal()"></div>
  <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-4">
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
      <!-- Header -->
      <div class="bg-amber-50 px-6 py-4 border-b border-amber-100">
        <h3 class="text-lg font-semibold text-amber-900 flex items-center gap-2">
          <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M6 3.5a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-1 0V4a.5.5 0 0 1 .5-.5zm4 0a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-1 0V4a.5.5 0 0 1 .5-.5z"/>
          </svg>
          Pausar Recebimento de Pedidos
        </h3>
        <p class="text-sm text-amber-700 mt-1">Controle quando sua loja aceita novos pedidos</p>
      </div>
      
      <!-- Body -->
      <div class="p-6">
        <!-- Tipo de Pausa -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-slate-700 mb-2">Tipo de Pausa</label>
          <div class="grid grid-cols-3 gap-2">
            <button type="button" onclick="setPauseType('timed')" 
                    class="pause-type-btn active px-3 py-2 text-sm font-medium rounded-xl border-2 border-indigo-500 bg-indigo-50 text-indigo-700" data-type="timed">
              Temporizada
            </button>
            <button type="button" onclick="setPauseType('scheduled')" 
                    class="pause-type-btn px-3 py-2 text-sm font-medium rounded-xl border-2 border-slate-200 bg-white text-slate-600" data-type="scheduled">
              Até horário
            </button>
            <button type="button" onclick="setPauseType('indefinite')" 
                    class="pause-type-btn px-3 py-2 text-sm font-medium rounded-xl border-2 border-slate-200 bg-white text-slate-600" data-type="indefinite">
              Manual
            </button>
          </div>
        </div>
        
        <!-- Duração (para timed) -->
        <div id="pause-timed-options" class="mb-4">
          <label class="block text-sm font-medium text-slate-700 mb-2">Duração</label>
          <div class="grid grid-cols-4 gap-2">
            <button type="button" onclick="setDuration(15)" class="duration-btn px-3 py-2 text-sm font-medium rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50" data-minutes="15">15 min</button>
            <button type="button" onclick="setDuration(30)" class="duration-btn active px-3 py-2 text-sm font-medium rounded-xl border-2 border-indigo-500 bg-indigo-50 text-indigo-700" data-minutes="30">30 min</button>
            <button type="button" onclick="setDuration(60)" class="duration-btn px-3 py-2 text-sm font-medium rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50" data-minutes="60">1 hora</button>
            <button type="button" onclick="setDuration(120)" class="duration-btn px-3 py-2 text-sm font-medium rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50" data-minutes="120">2 horas</button>
          </div>
          <div class="mt-2">
            <input type="number" id="custom-minutes" min="1" max="1440" placeholder="Minutos personalizados" 
                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400"
                   onchange="setDuration(this.value)">
          </div>
        </div>
        
        <!-- Data/Hora (para scheduled) -->
        <div id="pause-scheduled-options" class="mb-4 hidden">
          <label class="block text-sm font-medium text-slate-700 mb-2">Retomar em</label>
          <input type="datetime-local" id="pause-until-datetime" 
                 class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
        </div>
        
        <!-- Info para indefinite -->
        <div id="pause-indefinite-info" class="mb-4 hidden">
          <div class="bg-slate-50 rounded-xl p-3 text-sm text-slate-600">
            <p>A loja permanecerá pausada até você retomar manualmente.</p>
          </div>
        </div>
        
        <!-- Motivo -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-slate-700 mb-2">Motivo (opcional)</label>
          <select id="pause-reason-select" onchange="updateReasonInput()" 
                  class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 mb-2">
            <option value="">Selecione ou digite abaixo...</option>
            <option value="Alta demanda no momento">Alta demanda no momento</option>
            <option value="Problemas técnicos temporários">Problemas técnicos temporários</option>
            <option value="Preparando pedidos em andamento">Preparando pedidos em andamento</option>
            <option value="Em manutenção">Em manutenção</option>
            <option value="Estoque limitado">Estoque limitado</option>
            <option value="Intervalo para descanso">Intervalo para descanso</option>
          </select>
          <input type="text" id="pause-reason-input" placeholder="Ou digite um motivo personalizado..." 
                 class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
        </div>
      </div>
      
      <!-- Footer -->
      <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
        <button type="button" onclick="closePauseModal()" 
                class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition">
          Cancelar
        </button>
        <button type="button" onclick="confirmPause()" 
                class="px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-xl hover:bg-amber-700 transition">
          Ativar Pausa
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal de Estender Pausa -->
<div id="extend-modal" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/50" onclick="closeExtendModal()"></div>
  <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-sm p-4">
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
      <div class="bg-amber-50 px-6 py-4 border-b border-amber-100">
        <h3 class="text-lg font-semibold text-amber-900">Estender Pausa</h3>
      </div>
      <div class="p-6">
        <label class="block text-sm font-medium text-slate-700 mb-2">Adicionar mais tempo</label>
        <div class="grid grid-cols-3 gap-2">
          <button type="button" onclick="confirmExtend(15)" class="px-3 py-2 text-sm font-medium rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50">+15 min</button>
          <button type="button" onclick="confirmExtend(30)" class="px-3 py-2 text-sm font-medium rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50">+30 min</button>
          <button type="button" onclick="confirmExtend(60)" class="px-3 py-2 text-sm font-medium rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50">+1 hora</button>
        </div>
      </div>
      <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
        <button type="button" onclick="closeExtendModal()" class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50">Cancelar</button>
      </div>
    </div>
  </div>
</div>

<script>
// Variáveis de estado
let pauseType = 'timed';
let pauseMinutes = 30;
const pauseBaseUrl = '<?= e(base_url('admin/' . $slug . '/pause')) ?>';

// Funções do Modal
function openPauseModal() {
  document.getElementById('pause-modal').classList.remove('hidden');
}

function closePauseModal() {
  document.getElementById('pause-modal').classList.add('hidden');
}

function setPauseType(type) {
  pauseType = type;
  document.querySelectorAll('.pause-type-btn').forEach(btn => {
    btn.classList.remove('active', 'border-indigo-500', 'bg-indigo-50', 'text-indigo-700');
    btn.classList.add('border-slate-200', 'bg-white', 'text-slate-600');
  });
  document.querySelector(`.pause-type-btn[data-type="${type}"]`).classList.remove('border-slate-200', 'bg-white', 'text-slate-600');
  document.querySelector(`.pause-type-btn[data-type="${type}"]`).classList.add('active', 'border-indigo-500', 'bg-indigo-50', 'text-indigo-700');
  
  // Mostrar/ocultar opções
  document.getElementById('pause-timed-options').classList.toggle('hidden', type !== 'timed');
  document.getElementById('pause-scheduled-options').classList.toggle('hidden', type !== 'scheduled');
  document.getElementById('pause-indefinite-info').classList.toggle('hidden', type !== 'indefinite');
}

function setDuration(minutes) {
  pauseMinutes = parseInt(minutes);
  document.querySelectorAll('.duration-btn').forEach(btn => {
    btn.classList.remove('active', 'border-indigo-500', 'bg-indigo-50', 'text-indigo-700', 'border-2');
    btn.classList.add('border', 'border-slate-200');
  });
  const activeBtn = document.querySelector(`.duration-btn[data-minutes="${minutes}"]`);
  if (activeBtn) {
    activeBtn.classList.remove('border', 'border-slate-200');
    activeBtn.classList.add('active', 'border-2', 'border-indigo-500', 'bg-indigo-50', 'text-indigo-700');
  }
}

function updateReasonInput() {
  const select = document.getElementById('pause-reason-select');
  const input = document.getElementById('pause-reason-input');
  if (select.value) {
    input.value = select.value;
  }
}

async function confirmPause() {
  const reason = document.getElementById('pause-reason-input').value || 
                 document.getElementById('pause-reason-select').value || 
                 'Estamos em pausa no momento';
  
  let payload = { type: pauseType, reason };
  
  if (pauseType === 'timed') {
    payload.minutes = pauseMinutes;
  } else if (pauseType === 'scheduled') {
    const datetime = document.getElementById('pause-until-datetime').value;
    if (!datetime) {
      alert('Por favor, selecione a data/hora de retorno');
      return;
    }
    payload.until = datetime.replace('T', ' ') + ':00';
  }
  
  try {
    const response = await fetch(`${pauseBaseUrl}/enable`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify(payload)
    });
    
    const data = await response.json();
    if (data.success) {
      location.reload();
    } else {
      alert(data.message || 'Erro ao ativar pausa');
    }
  } catch (error) {
    alert('Erro de conexão');
  }
}

async function disablePause() {
  if (!confirm('Deseja retomar o atendimento?')) return;
  
  try {
    const response = await fetch(`${pauseBaseUrl}/disable`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    });
    
    const data = await response.json();
    if (data.success) {
      location.reload();
    } else {
      alert(data.message || 'Erro ao desativar pausa');
    }
  } catch (error) {
    alert('Erro de conexão');
  }
}

function extendPause() {
  document.getElementById('extend-modal').classList.remove('hidden');
}

function closeExtendModal() {
  document.getElementById('extend-modal').classList.add('hidden');
}

async function confirmExtend(minutes) {
  try {
    const response = await fetch(`${pauseBaseUrl}/extend`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ minutes })
    });
    
    const data = await response.json();
    if (data.success) {
      location.reload();
    } else {
      alert(data.message || 'Erro ao estender pausa');
    }
  } catch (error) {
    alert('Erro de conexão');
  }
}

// Atualização automática do tempo restante
<?php if ($isPaused && $pauseStatus['pause_type'] !== 'indefinite'): ?>
setInterval(async () => {
  try {
    const response = await fetch(`${pauseBaseUrl}/status`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const data = await response.json();
    if (data.success && data.data.is_paused) {
      const remainingEl = document.getElementById('pause-remaining');
      if (remainingEl && data.data.remaining_text) {
        remainingEl.textContent = data.data.remaining_text;
      }
    } else if (!data.data.is_paused) {
      // Pausa expirou
      location.reload();
    }
  } catch (e) {}
}, 30000); // A cada 30 segundos
<?php endif; ?>
</script>

<!-- AÇÕES RÁPIDAS -->
<div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
  <a href="<?= e(base_url('admin/' . $slug . '/categories/create')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100">
      <svg width="20" height="20" fill="currentColor" class="bi bi-plus-circle-fill" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/></svg>
    </div>
    <div class="font-semibold text-slate-900">Nova categoria</div>
    <p class="text-sm text-slate-500">Organize seu cardápio por grupos.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/products/create')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
      <svg width="20" height="20" fill="currentColor" class="bi bi-plus-circle-fill" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/></svg>
    </div>
    <div class="font-semibold text-slate-900">Novo produto</div>
    <p class="text-sm text-slate-500">Cadastre simples ou combos.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/ingredients/create')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
      <svg width="20" height="20" fill="currentColor" class="bi bi-plus-circle-fill" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/></svg>
    </div>
    <div class="font-semibold text-slate-900">Novo ingrediente</div>
    <p class="text-sm text-slate-500">Vincule aos produtos.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/orders/create')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-600 ring-1 ring-sky-100">
      <svg width="20" height="20" fill="currentColor" class="bi bi-plus-circle-fill" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/></svg>
    </div>
    <div class="font-semibold text-slate-900">Novo pedido</div>
    <p class="text-sm text-slate-500">Registre um pedido manualmente.</p>
  </a>
</div>

<!-- GESTÃO FINANCEIRA -->
<div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
  <a href="<?= e(base_url('admin/' . $slug . '/financial')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-green-50 text-green-600 ring-1 ring-green-100">
      <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
        <path d="M0 3a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3zm2-1a1 1 0 0 0-1 1v1h14V3a1 1 0 0 0-1-1H2zm13 3H1v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V5z"/>
        <path d="M5 8h6v2H5z"/>
      </svg>
    </div>
    <div class="font-semibold text-slate-900">Dashboard Financeiro</div>
    <p class="text-sm text-slate-500">Lucros, vendas e métricas.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/expenses')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-600 ring-1 ring-red-100">
      <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
        <path d="M1.92.506a.5.5 0 0 1 .434.14L3 1.293l.646-.647a.5.5 0 0 1 .708 0L5 1.293l.646-.647a.5.5 0 0 1 .708 0L7 1.293l.646-.647a.5.5 0 0 1 .708 0L9 1.293l.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .801.13l.5 1A.5.5 0 0 1 15 2v12a.5.5 0 0 1-.053.224l-.5 1a.5.5 0 0 1-.8.13L13 14.707l-.646.647a.5.5 0 0 1-.708 0L11 14.707l-.646.647a.5.5 0 0 1-.708 0L9 14.707l-.646.647a.5.5 0 0 1-.708 0L7 14.707l-.646.647a.5.5 0 0 1-.708 0L5 14.707l-.646.647a.5.5 0 0 1-.708 0L3 14.707l-.646.647a.5.5 0 0 1-.801-.13l-.5-1A.5.5 0 0 1 1 14V2a.5.5 0 0 1 .053-.224l.5-1a.5.5 0 0 1 .367-.27zm.217 1.338L2 2.118v11.764l.137.274.51-.51a.5.5 0 0 1 .707 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.509.509.137-.274V2.118l-.137-.274-.51.51a.5.5 0 0 1-.707 0L12 1.707l-.646.647a.5.5 0 0 1-.708 0L10 1.707l-.646.647a.5.5 0 0 1-.708 0L8 1.707l-.646.647a.5.5 0 0 1-.708 0L6 1.707l-.646.647a.5.5 0 0 1-.708 0L4 1.707l-.646.647a.5.5 0 0 1-.708 0l-.509-.51z"/>
        <path d="M3 4.5a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5zm8-6a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5z"/>
      </svg>
    </div>
    <div class="font-semibold text-slate-900">Despesas</div>
    <p class="text-sm text-slate-500">Gerencie custos fixos e variáveis.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/product-costs')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600 ring-1 ring-purple-100">
      <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
        <path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73l.348.086z"/>
      </svg>
    </div>
    <div class="font-semibold text-slate-900">Custos de Produtos</div>
    <p class="text-sm text-slate-500">Margens e lucratividade.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/analytics')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
      <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
        <path d="M4 11H2v3h2zm5-4H7v7h2zm5-5h-2v12h2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM6 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1zm-5 4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1z"/>
      </svg>
    </div>
    <div class="font-semibold text-slate-900">Analytics</div>
    <p class="text-sm text-slate-500">Relatórios de vendas.</p>
  </a>
</div>

<!-- COLUNAS -->
<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">

<!-- Categorias -->
<div class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md card-link"
     data-href="<?= e(base_url('admin/' . $slug . '/categories')) ?>" role="button" tabindex="0">
  <div class="mb-3 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100">
        <svg width="20" height="20" fill="currentColor" class="bi bi-list-ul" viewBox="0 0 16 16">
          <path fill-rule="evenodd" d="M5 11.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m-3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2m0 4a1 1 0 1 0 0-2 1 1 0 0 0 0 2m0 4a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/>
        </svg>
      </span>
      <h2 class="font-semibold text-slate-900">Categorias</h2>
    </div>
    <span class="rounded-xl bg-slate-900 px-2.5 py-1 text-xs font-bold text-white"><?= (int)count($categories) ?></span>
  </div>

  <ul class="divide-y rounded-xl border border-slate-100 bg-white text-sm max-h-56 overflow-auto pr-1 thin-scroll">
    <?php foreach ($categories as $c): ?>
      <li class="flex items-center gap-3 px-3 py-2.5 hover:bg-slate-50">
        <a class="flex w-full items-center justify-between gap-3"
           href="<?= e(base_url('admin/' . $slug . '/categories/' . (int)($c['id'] ?? 0) . '/edit')) ?>">
          <div class="truncate font-medium text-slate-800"><?= e($c['name'] ?? '') ?></div>
          <span class="text-[11px] text-slate-500">#<?= (int)($c['id'] ?? 0) ?></span>
        </a>
      </li>
    <?php endforeach; ?>
    <?php if (!count($categories)): ?>
      <li class="px-3 py-3 text-slate-500">Nenhuma categoria ainda.</li>
    <?php endif; ?>
  </ul>
</div>

  <!-- Produtos (sem botão Editar; item inteiro clicável pro form) -->
  <div class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md card-link"
       data-href="<?= e(base_url('admin/' . $slug . '/products')) ?>" role="button" tabindex="0">
    <div class="mb-3 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
          <svg width="20" height="20" fill="currentColor" class="bi bi-bag" viewBox="0 0 16 16"><path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z"/></svg>
        </span>
        <h2 class="font-semibold text-slate-900">Produtos</h2>
      </div>
      <span class="rounded-xl bg-slate-900 px-2.5 py-1 text-xs font-bold text-white"><?= (int)count($products) ?></span>
    </div>

    <ul class="divide-y rounded-xl border border-slate-100 bg-white text-sm max-h-56 overflow-auto pr-1 thin-scroll">
      <?php $show = array_slice($products, 0, 8); ?>
      <?php foreach ($show as $p): $pid = (int)($p['id'] ?? 0); ?>
        <li class="flex items-center gap-3 px-3 py-2.5 hover:bg-slate-50">
          <a class="flex w-full items-center gap-3" href="<?= e(base_url('admin/' . $slug . '/products/' . $pid . '/edit')) ?>">
            <?php if (!empty($p['image'])): ?>
              <img src="<?= e(base_url($p['image'])) ?>" class="h-11 w-11 rounded-lg object-cover ring-1 ring-slate-200" alt="">
            <?php else: ?>
              <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-slate-100 text-slate-400 ring-1 ring-slate-200">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
            <?php endif; ?>
            <div class="min-w-0 flex-1">
              <div class="truncate font-medium text-slate-800"><?= e($p['name'] ?? '') ?></div>
              <div class="text-xs text-slate-500">
                <?php if (isset($p['promo_price']) && $p['promo_price'] !== '' && $p['promo_price'] !== null): ?>
                  <span class="line-through"><?= $price($p['price'] ?? 0) ?></span>
                  <strong class="ml-1 text-slate-800"><?= $price($p['promo_price']) ?></strong>
                <?php else: ?>
                  <?= $price($p['price'] ?? 0) ?>
                <?php endif; ?>
              </div>
            </div>
          </a>
        </li>
      <?php endforeach; ?>
      <?php if (!count($show)): ?>
        <li class="px-3 py-3 text-slate-500">Sem produtos ainda.</li>
      <?php endif; ?>
    </ul>
  </div>

<!-- Ingredientes -->
<div class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md card-link"
     data-href="<?= e(base_url('admin/' . $slug . '/ingredients')) ?>" role="button" tabindex="0">
  <div class="mb-3 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
        <svg width="20" height="20" fill="currentColor" class="bi bi-cup-straw" viewBox="0 0 16 16">
          <path d="M13.902.334a.5.5 0 0 1-.28.65l-2.254.902-.4 1.927c.376.095.715.215.972.367.228.135.56.396.56.82q0 .069-.011.132l-.962 9.068a1.28 1.28 0 0 1-.524.93c-.488.34-1.494.87-3.01.87s-2.522-.53-3.01-.87a1.28 1.28 0 0 1-.524-.93L3.51 5.132A1 1 0 0 1 3.5 5c0-.424.332-.685.56-.82.262-.154.607-.276.99-.372C5.824 3.614 6.867 3.5 8 3.5c.712 0 1.389.045 1.985.127l.464-2.215a.5.5 0 0 1 .303-.356l2.5-1a.5.5 0 0 1 .65.278"/>
        </svg>
      </span>
      <h2 class="font-semibold text-slate-900">Ingredientes</h2>
    </div>
    <span class="rounded-xl bg-slate-900 px-2.5 py-1 text-xs font-bold text-white"><?= (int)$ingredientsCount ?></span>
  </div>

  <ul class="divide-y rounded-xl border border-slate-100 bg-white text-sm max-h-56 overflow-auto pr-1 thin-scroll">
    <?php $ingsToShow = array_slice($recentIngredients, 0, 8); ?>
    <?php foreach ($ingsToShow as $ing): ?>
      <?php
        $iid = (int)($ing['id'] ?? 0);
        $pnRaw = $ing['product_names'] ?? null;

        if (is_string($pnRaw) && strpos($pnRaw, '||') !== false) {
            $pn = array_values(array_filter(array_map('trim', explode('||', $pnRaw))));
        } elseif (is_string($pnRaw) && $pnRaw !== '') {
            $pn = [$pnRaw];
        } elseif (is_array($pnRaw)) {
            $pn = $pnRaw;
        } else {
            $pn = [];
        }
        ?>
      <li class="flex items-center gap-3 px-3 py-2.5 hover:bg-slate-50">
        <a class="flex w-full items-center gap-3" href="<?= e(base_url('admin/' . $slug . '/ingredients/' . $iid . '/edit')) ?>">
          <?php $ingImage = trim((string)($ing['image_path'] ?? '')); ?>
          <?php if ($ingImage !== ''): ?>
            <img src="<?= e(base_url($ingImage)) ?>" alt=""
                 class="h-11 w-11 rounded-lg object-cover ring-1 ring-slate-200"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="h-11 w-11 rounded-lg bg-slate-100 text-slate-400 ring-1 ring-slate-200 hidden items-center justify-center">
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
          <?php else: ?>
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-slate-100 text-slate-400 ring-1 ring-slate-200">
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
          <?php endif; ?>
          <div class="min-w-0 flex-1">
            <div class="truncate font-medium text-slate-800"><?= e($ing['name'] ?? '') ?></div>
            <!-- product names removed from quick dashboard view -->
          </div>
        </a>
      </li>
    <?php endforeach; ?>
    <?php if (!count($ingsToShow)): ?>
      <li class="px-3 py-3 text-slate-500">Sem ingredientes cadastrados.</li>
    <?php endif; ?>
  </ul>
</div>

<!-- Pedidos (dashboard) — visual e status iguais ao admin/orders/index.php -->
<div class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md card-link"
     data-href="<?= e(base_url('admin/' . $slug . '/orders')) ?>" role="button" tabindex="0">

  <div class="mb-3 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-600 ring-1 ring-sky-100">
        <svg width="20" height="20" fill="currentColor" class="bi bi-cart4" viewBox="0 0 16 16">
          <path d="M0 2.5A.5.5 0 0 1 .5 2H2l.89 2H14.5a.5.5 0 0 1 .485.621l-1.5 6A.5.5 0 0 1 13 11H4L2 3H.5a.5.5 0 0 1-.5-.5z"/>
          <path d="M5 12a1 1 0 1 0 0 2 1 1 0 0 0 0-2m6 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2"/>
        </svg>
      </span>
      <h2 class="font-semibold text-slate-900">Pedidos</h2>
    </div>
    <span class="rounded-xl bg-slate-900 px-2.5 py-1 text-xs font-bold text-white"><?= (int)$ordersCount ?></span>
  </div>

  <?php
    // mesmo mapeamento/labels da página orders
    $statusLabels = [
        'pending'   => 'Pendente',
        'completed' => 'Concluído',
        'canceled'  => 'Cancelado',
        'paid'      => 'Concluído', // Pago agora exibe como Concluído
    ];
$ordersToShow = array_slice($recentOrders, 0, 8);
?>

  <ul class="divide-y rounded-xl border border-slate-100 bg-white text-sm max-h-56 overflow-auto pr-1 thin-scroll">
    <?php foreach ($ordersToShow as $o): 
      $oid = (int)($o['id'] ?? 0);
      $orderNum = (int)($o['order_number'] ?? $o['id'] ?? 0);
    ?>
      <?php
      $st    = (string)($o['status'] ?? 'pending');
        $label = $statusLabels[$st] ?? ucfirst($st);

        // classes do badge iguais ao admin/orders/index.php
        // Pago agora usa o mesmo estilo de Concluído
        $badge = match ($st) {
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'canceled'  => 'bg-rose-50 text-rose-700 ring-rose-200',
            default     => 'bg-amber-50 text-amber-700 ring-amber-200', // pending
        };

        // cor do pontinho
        $dot = match ($st) {
            'paid' => 'bg-emerald-500',
            'completed' => 'bg-emerald-500',
            'canceled'  => 'bg-rose-500',
            default     => 'bg-amber-500',
        };
        ?>
      <li>
        <a class="flex w-full items-center justify-between gap-3 px-3 py-2.5 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-200"
           href="<?= e(base_url('admin/' . $slug . '/orders/show?id=' . $oid)) ?>">

          <div class="min-w-0">
            <div class="truncate font-medium text-slate-800">
              #<?= $orderNum ?> · <?= e($o['customer_name'] ?? 'Cliente') ?>
            </div>
            <div class="text-xs text-slate-500">
              <?= e($o['created_at'] ?? '') ?>
            </div>
            <div class="mt-0.5 text-xs">
              <strong class="text-slate-800"><?= $price($o['total'] ?? 0) ?></strong>
            </div>
          </div>

          <!-- Badge de status igual ao da listagem de pedidos -->
          <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[12px] font-medium ring-1 <?= $badge ?>">
            <span class="h-1.5 w-1.5 rounded-full <?= $dot ?>"></span>
            <?= e($label) ?>
          </span>
        </a>
      </li>
    <?php endforeach; ?>
    <?php if (!count($ordersToShow)): ?>
      <li class="px-3 py-3 text-slate-500">Sem pedidos ainda.</li>
    <?php endif; ?>
  </ul>
</div>

</div>

<!-- Gestão operacional -->
<div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
  <a href="<?= e(base_url('admin/' . $slug . '/payment-methods')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600 ring-1 ring-purple-100">
<svg width="" height="20" fill="currentColor" class="bi bi-credit-card-2-back-fill" viewBox="0 0 16 16">
  <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v5H0zm11.5 1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zM0 11v1a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-1z"/>
</svg>
    </div>
    <div class="font-semibold text-slate-900">Métodos de pagamento</div>
    <p class="text-sm text-slate-500">Gerencie as opções exibidas no checkout.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/delivery-fees')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600 ring-1 ring-rose-100">
<svg width="20" height="20" fill="currentColor" class="bi bi-rocket-takeoff-fill" viewBox="0 0 16 16">
  <path d="M12.17 9.53c2.307-2.592 3.278-4.684 3.641-6.218.21-.887.214-1.58.16-2.065a3.6 3.6 0 0 0-.108-.563 2 2 0 0 0-.078-.23V.453c-.073-.164-.168-.234-.352-.295a2 2 0 0 0-.16-.045 4 4 0 0 0-.57-.093c-.49-.044-1.19-.03-2.08.188-1.536.374-3.618 1.343-6.161 3.604l-2.4.238h-.006a2.55 2.55 0 0 0-1.524.734L.15 7.17a.512.512 0 0 0 .433.868l1.896-.271c.28-.04.592.013.955.132.232.076.437.16.655.248l.203.083c.196.816.66 1.58 1.275 2.195.613.614 1.376 1.08 2.191 1.277l.082.202c.089.218.173.424.249.657.118.363.172.676.132.956l-.271 1.9a.512.512 0 0 0 .867.433l2.382-2.386c.41-.41.668-.949.732-1.526zm.11-3.699c-.797.8-1.93.961-2.528.362-.598-.6-.436-1.733.361-2.532.798-.799 1.93-.96 2.528-.361s.437 1.732-.36 2.531Z"/>
  <path d="M5.205 10.787a7.6 7.6 0 0 0 1.804 1.352c-1.118 1.007-4.929 2.028-5.054 1.903-.126-.127.737-4.189 1.839-5.18.346.69.837 1.35 1.411 1.925"/>
</svg>
    </div>
    <div class="font-semibold text-slate-900">Taxas de entrega</div>
    <p class="text-sm text-slate-500">Atualize cidades, bairros e valores.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/evolution')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-green-50 text-green-600 ring-1 ring-green-100">
<svg width="20" height="20" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
  <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.78-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.336-.445-.342-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/>
</svg>
    </div>
    <div class="font-semibold text-slate-900">Evolution API</div>
    <p class="text-sm text-slate-500">Gerencie instâncias WhatsApp e notificações.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/loyalty-discount')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
<svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
  <path d="M0 0h24v24H0V0z" fill="none"/>
  <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
</svg>
    </div>
    <div class="font-semibold text-slate-900">Desconto Fidelidade</div>
    <p class="text-sm text-slate-500">Configure taxa embutida e desconto.</p>
  </a>
</div>

<!-- Scrollbar fina + cursor de cartão -->
<style>
  .thin-scroll::-webkit-scrollbar{width:8px;height:8px}
  .thin-scroll::-webkit-scrollbar-thumb{background:#e5e7eb;border-radius:9999px}
  .thin-scroll::-webkit-scrollbar-track{background:transparent}
  .card-link{cursor:pointer}
</style>

<!-- JS: torna blocos clicáveis (e ignora cliques em links internos) -->
<script>
  document.querySelectorAll('.card-link').forEach(function(card){
    const href = card.getAttribute('data-href');
    if(!href) return;
    card.addEventListener('click', function(e){
      // Evita navegar se clicou num <a> interno
      const a = e.target.closest('a');
      if(a) return;
      window.location.href = href;
    });
    card.addEventListener('keydown', function(e){
      if(e.key === 'Enter' || e.key === ' '){
        e.preventDefault();
        window.location.href = href;
      }
    });
  });
</script>

<?php
// Close the main wrapper which was left open in some older templates
echo "</div>\n";
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
