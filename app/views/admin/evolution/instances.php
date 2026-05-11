<?php
// admin/evolution/instances.php — Instâncias Evolution API (design unificado)

$title = 'Instâncias Evolution - ' . ($company['name'] ?? 'Empresa');
$slug = rawurlencode((string)($activeSlug ?? ($company['slug'] ?? '')));
$backUrl = $slug ? base_url('admin/' . $slug . '/dashboard') : base_url('admin');

// helper de escape

ob_start();

// Configuração do header padronizado
$pageTitle = 'Instâncias Evolution';
$pageDescription = 'Gerencie suas conexões WhatsApp Business';
$pageIcon = '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 16 16"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg>';
$breadcrumbs = [
    ['label' => 'Instâncias Evolution']
];
$actions = [
    ['label' => 'Nova', 'onclick' => "document.getElementById('newInstanceBtn').click()", 'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>', 'primary' => true]
];
$extraHeaderContent = '<button id="refreshBtn" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"><svg class="refresh-icon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/><path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/></svg>Atualizar</button><div id="autoSyncIndicator" class="hidden items-center gap-1.5 rounded-lg bg-green-50 px-2 py-1 text-xs text-green-700"><div class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></div>Sincronizando...</div>';
?>

<!-- Sistemas centralizados carregados via layout principal -->

<div class="mx-auto max-w-6xl p-4">

<?php include __DIR__ . '/../components/page-header.php'; ?>

  <!-- Botão oculto para trigger do modal -->
  <button id="newInstanceBtn" class="hidden">Nova Instância</button>

  <!-- FILTROS E BUSCA -->
  <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <!-- Search -->
      <div class="relative flex-1 max-w-md">
        <input id="search" type="text" placeholder="Buscar instâncias..." 
               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 pr-10 text-sm placeholder:text-slate-500 focus:border-slate-300 focus:outline-none focus:ring-4 focus:ring-slate-100" />
        <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="11" cy="11" r="7" /><path d="m21 21-4.3-4.3"/>
        </svg>
      </div>
      <!-- Status Filter -->
      <div class="relative">
        <button id="statusBtn" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
          Status
          <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="m7.247 4.86-4.796 5.481c-.566.647-.106 1.659.753 1.659h9.592a1 1 0 0 0 .753-1.659l-4.796-5.48a1 1 0 0 0-1.506 0z"/>
          </svg>
        </button>
        <!-- dropdown -->
        <div id="statusMenu" class="hidden absolute right-0 mt-2 w-48 rounded-xl border border-slate-200 bg-white p-1 shadow-lg z-10">
          <button data-status="all" class="w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">Todos</button>
          <button data-status="connected" class="w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">Conectado</button>
          <button data-status="pending" class="w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">Pendente</button>
          <button data-status="disconnected" class="w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">Desconectado</button>
        </div>
      </div>
    </div>
  </div>

  <!-- GRID DE INSTÂNCIAS -->
  <section id="cards" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <!-- Skeleton loading cards -->
    <div id="skeletonCards" class="contents">
      <!-- Skeleton Card 1 - sempre visível -->
      <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm animate-pulse">
        <div class="flex items-center justify-between mb-4">
          <div class="h-5 bg-slate-200 rounded w-32"></div>
          <div class="h-8 w-8 bg-slate-200 rounded-lg"></div>
        </div>
        
        <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
          <div class="flex items-center gap-2">
            <div class="flex-1 h-4 bg-slate-200 rounded"></div>
            <div class="h-6 w-6 bg-slate-200 rounded"></div>
            <div class="h-6 w-6 bg-slate-200 rounded"></div>
          </div>
        </div>
        
        <div class="flex items-start justify-between gap-4 mb-4">
          <div class="flex items-center gap-3">
            <div class="h-9 w-9 bg-slate-200 rounded-full"></div>
            <div>
              <div class="h-4 bg-slate-200 rounded w-24 mb-1"></div>
              <div class="h-3 bg-slate-200 rounded w-32"></div>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <div class="flex items-center gap-1.5">
              <div class="h-4 w-4 bg-slate-200 rounded"></div>
              <div class="h-4 bg-slate-200 rounded w-8"></div>
            </div>
            <div class="flex items-center gap-1.5">
              <div class="h-4 w-4 bg-slate-200 rounded"></div>
              <div class="h-4 bg-slate-200 rounded w-8"></div>
            </div>
          </div>
        </div>
        
        <div class="flex items-center justify-between">
          <div class="h-6 bg-slate-200 rounded-full w-20"></div>
          <div class="h-8 bg-slate-200 rounded-xl w-16"></div>
        </div>
      </article>
      
      <!-- Skeleton Card 2 - visível em telas sm+ -->
      <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm animate-pulse hidden sm:block">
        <div class="flex items-center justify-between mb-4">
          <div class="h-5 bg-slate-200 rounded w-28"></div>
          <div class="h-8 w-8 bg-slate-200 rounded-lg"></div>
        </div>
        
        <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
          <div class="flex items-center gap-2">
            <div class="flex-1 h-4 bg-slate-200 rounded"></div>
            <div class="h-6 w-6 bg-slate-200 rounded"></div>
            <div class="h-6 w-6 bg-slate-200 rounded"></div>
          </div>
        </div>
        
        <div class="flex items-start justify-between gap-4 mb-4">
          <div class="flex items-center gap-3">
            <div class="h-9 w-9 bg-slate-200 rounded-full"></div>
            <div>
              <div class="h-4 bg-slate-200 rounded w-20 mb-1"></div>
              <div class="h-3 bg-slate-200 rounded w-28"></div>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <div class="flex items-center gap-1.5">
              <div class="h-4 w-4 bg-slate-200 rounded"></div>
              <div class="h-4 bg-slate-200 rounded w-6"></div>
            </div>
            <div class="flex items-center gap-1.5">
              <div class="h-4 w-4 bg-slate-200 rounded"></div>
              <div class="h-4 bg-slate-200 rounded w-10"></div>
            </div>
          </div>
        </div>
        
        <div class="flex items-center justify-between">
          <div class="h-6 bg-slate-200 rounded-full w-24"></div>
          <div class="h-8 bg-slate-200 rounded-xl w-16"></div>
        </div>
      </article>
      
      <!-- Skeleton Card 3 - visível em telas lg+ -->
      <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm animate-pulse hidden lg:block">
        <div class="flex items-center justify-between mb-4">
          <div class="h-5 bg-slate-200 rounded w-36"></div>
          <div class="h-8 w-8 bg-slate-200 rounded-lg"></div>
        </div>
        
        <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
          <div class="flex items-center gap-2">
            <div class="flex-1 h-4 bg-slate-200 rounded"></div>
            <div class="h-6 w-6 bg-slate-200 rounded"></div>
            <div class="h-6 w-6 bg-slate-200 rounded"></div>
          </div>
        </div>
        
        <div class="flex items-start justify-between gap-4 mb-4">
          <div class="flex items-center gap-3">
            <div class="h-9 w-9 bg-slate-200 rounded-full"></div>
            <div>
              <div class="h-4 bg-slate-200 rounded w-26 mb-1"></div>
              <div class="h-3 bg-slate-200 rounded w-36"></div>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <div class="flex items-center gap-1.5">
              <div class="h-4 w-4 bg-slate-200 rounded"></div>
              <div class="h-4 bg-slate-200 rounded w-7"></div>
            </div>
            <div class="flex items-center gap-1.5">
              <div class="h-4 w-4 bg-slate-200 rounded"></div>
              <div class="h-4 bg-slate-200 rounded w-9"></div>
            </div>
          </div>
        </div>
        
        <div class="flex items-center justify-between">
          <div class="h-6 bg-slate-200 rounded-full w-22"></div>
          <div class="h-8 bg-slate-200 rounded-xl w-16"></div>
        </div>
      </article>
      
      <!-- Skeleton Cards 4-6 - visíveis apenas em telas lg+ para preencher mais espaço -->
      <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm animate-pulse hidden lg:block">
        <div class="flex items-center justify-between mb-4">
          <div class="h-5 bg-slate-200 rounded w-30"></div>
          <div class="h-8 w-8 bg-slate-200 rounded-lg"></div>
        </div>
        
        <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
          <div class="flex items-center gap-2">
            <div class="flex-1 h-4 bg-slate-200 rounded"></div>
            <div class="h-6 w-6 bg-slate-200 rounded"></div>
            <div class="h-6 w-6 bg-slate-200 rounded"></div>
          </div>
        </div>
        
        <div class="flex items-start justify-between gap-4 mb-4">
          <div class="flex items-center gap-3">
            <div class="h-9 w-9 bg-slate-200 rounded-full"></div>
            <div>
              <div class="h-4 bg-slate-200 rounded w-22 mb-1"></div>
              <div class="h-3 bg-slate-200 rounded w-30"></div>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <div class="flex items-center gap-1.5">
              <div class="h-4 w-4 bg-slate-200 rounded"></div>
              <div class="h-4 bg-slate-200 rounded w-6"></div>
            </div>
            <div class="flex items-center gap-1.5">
              <div class="h-4 w-4 bg-slate-200 rounded"></div>
              <div class="h-4 bg-slate-200 rounded w-8"></div>
            </div>
          </div>
        </div>
        
        <div class="flex items-center justify-between">
          <div class="h-6 bg-slate-200 rounded-full w-18"></div>
          <div class="h-8 bg-slate-200 rounded-xl w-16"></div>
        </div>
      </article>
      
      <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm animate-pulse hidden lg:block">
        <div class="flex items-center justify-between mb-4">
          <div class="h-5 bg-slate-200 rounded w-34"></div>
          <div class="h-8 w-8 bg-slate-200 rounded-lg"></div>
        </div>
        
        <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
          <div class="flex items-center gap-2">
            <div class="flex-1 h-4 bg-slate-200 rounded"></div>
            <div class="h-6 w-6 bg-slate-200 rounded"></div>
            <div class="h-6 w-6 bg-slate-200 rounded"></div>
          </div>
        </div>
        
        <div class="flex items-start justify-between gap-4 mb-4">
          <div class="flex items-center gap-3">
            <div class="h-9 w-9 bg-slate-200 rounded-full"></div>
            <div>
              <div class="h-4 bg-slate-200 rounded w-24 mb-1"></div>
              <div class="h-3 bg-slate-200 rounded w-34"></div>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <div class="flex items-center gap-1.5">
              <div class="h-4 w-4 bg-slate-200 rounded"></div>
              <div class="h-4 bg-slate-200 rounded w-9"></div>
            </div>
            <div class="flex items-center gap-1.5">
              <div class="h-4 w-4 bg-slate-200 rounded"></div>
              <div class="h-4 bg-slate-200 rounded w-7"></div>
            </div>
          </div>
        </div>
        
        <div class="flex items-center justify-between">
          <div class="h-6 bg-slate-200 rounded-full w-26"></div>
          <div class="h-8 bg-slate-200 rounded-xl w-16"></div>
        </div>
      </article>
      
      <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm animate-pulse hidden lg:block">
        <div class="flex items-center justify-between mb-4">
          <div class="h-5 bg-slate-200 rounded w-28"></div>
          <div class="h-8 w-8 bg-slate-200 rounded-lg"></div>
        </div>
        
        <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
          <div class="flex items-center gap-2">
            <div class="flex-1 h-4 bg-slate-200 rounded"></div>
            <div class="h-6 w-6 bg-slate-200 rounded"></div>
            <div class="h-6 w-6 bg-slate-200 rounded"></div>
          </div>
        </div>
        
        <div class="flex items-start justify-between gap-4 mb-4">
          <div class="flex items-center gap-3">
            <div class="h-9 w-9 bg-slate-200 rounded-full"></div>
            <div>
              <div class="h-4 bg-slate-200 rounded w-26 mb-1"></div>
              <div class="h-3 bg-slate-200 rounded w-32"></div>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <div class="flex items-center gap-1.5">
              <div class="h-4 w-4 bg-slate-200 rounded"></div>
              <div class="h-4 bg-slate-200 rounded w-8"></div>
            </div>
            <div class="flex items-center gap-1.5">
              <div class="h-4 w-4 bg-slate-200 rounded"></div>
              <div class="h-4 bg-slate-200 rounded w-6"></div>
            </div>
          </div>
        </div>
        
        <div class="flex items-center justify-between">
          <div class="h-6 bg-slate-200 rounded-full w-20"></div>
          <div class="h-8 bg-slate-200 rounded-xl w-16"></div>
        </div>
      </article>
    </div>
    
    <!-- Cards reais serão inseridos via JavaScript -->
  </section>

</div>

<!-- Modal New Instance -->
<div id="modalNewInstance" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/60" onclick="document.getElementById('modalNewInstance').classList.add('hidden')"></div>
  <div class="relative z-10 mx-auto max-w-2xl px-4 py-10 pointer-events-none">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl pointer-events-auto">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-900">Nova instância</h2>
        <button id="modalClose" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">
          <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/>
          </svg>
        </button>
      </div>
      <form id="formNewInstance" method="post" action="<?= e(base_url('admin/' . $slug . '/evolution/create')) ?>">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nome da instância <a href="/admin/<?= $slug ?>/guide/whatsapp#instances" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></label>
            <input name="name" type="text" required 
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm placeholder:text-slate-500 focus:border-slate-300 focus:outline-none focus:ring-4 focus:ring-slate-100" 
                   placeholder="Ex: vendas_whatsapp" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Número (opcional)</label>
            <input name="number" type="text" 
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm placeholder:text-slate-500 focus:border-slate-300 focus:outline-none focus:ring-4 focus:ring-slate-100" 
                   placeholder="Ex: 5511999887766" />
          </div>
        </div>
        <div class="mt-6 flex justify-end gap-3">
          <button type="button" id="modalCancelBtn" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Cancelar
          </button>
          <button type="submit" class="admin-gradient-bg rounded-xl px-4 py-2.5 text-sm font-medium text-white shadow hover:opacity-95">
            Criar instância
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Toast notification -->
<div id="toast" class="pointer-events-none fixed inset-x-0 bottom-6 z-50 grid place-items-center opacity-0 transition-opacity"></div>

<script>
  // Dados das instâncias já processados pelo controller
  const instances = <?= json_encode($processedInstances ?? []) ?>;
  const companySlug = '<?= e($slug) ?>';

  const cardsEl = document.getElementById('cards');

  function iconSettings() {
    return `<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115z"/></svg>`;
  }

  function iconCopy() {
    return `<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1z"/><path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0z"/></svg>`;
  }

  function iconEye() {
    return `<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/></svg>`;
  }

  function iconUser() {
    return `<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/></svg>`;
  }

  function iconChat() {
    return `<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M2.678 11.894a1 1 0 0 1 .287.801 11 11 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8 8 0 0 0 8 14c3.996 0 7-2.807 7-6s-3.004-6-7-6-7 2.808-7 6c0 1.468.617 2.83 1.678 3.894m-.493 3.905a22 22 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a10 10 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9 9 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105"/></svg>`;
  }

  function iconCheck() {
    return `<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0"/></svg>`;
  }

  // Reutilizar função de status do admin-common.js
  function getStatusChip(status) {
    if (window.AdminCommon && window.AdminCommon.createStatusPill) {
      return window.AdminCommon.createStatusPill(status);
    } else {
      // Status já vem da API /instance/connectionState (estado real)
      const statusMap = {
        'connected': 'status-connected',
        'pending': 'status-pending',
        'disconnected': 'status-disconnected'
      };
      
      const statusClass = statusMap[status] || 'status-pending';
      const displayText = status === 'connected' ? 'Conectado' : 
                         status === 'pending' ? 'Reconectando' : 'Desconectado';
      
      return `<span class="status-pill ${statusClass}"><span class="status-dot"></span>${displayText}</span>`;
    }
  }

  function renderCard(data) {
    const card = document.createElement('article');
    card.className = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md';
    card.dataset.name = (data.instance_name + ' ' + data.contact_name + ' ' + data.handle + ' ' + data.phone).toLowerCase();
    card.dataset.status = data.status;

    card.innerHTML = `
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-slate-900">${data.instance_name}</h3>
        <a href="<?= e(base_url('admin/')) ?>${companySlug}/evolution/instance/${encodeURIComponent(data.instance_name)}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50" title="Configurações">${iconSettings()}</a>
      </div>

      <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
        <div class="flex items-center gap-2">
          <span class="flex-1 select-all overflow-hidden text-ellipsis whitespace-nowrap font-mono text-slate-700" data-instance-id="${data.instance_identifier}" data-visible="0">${data.instance_identifier.replace(/./g, '*')}</span>
          <button class="inline-flex h-6 w-6 items-center justify-center rounded text-slate-500 hover:bg-slate-200" title="Copiar UUID" data-action="copy">${iconCopy()}</button>
          <button class="inline-flex h-6 w-6 items-center justify-center rounded text-slate-500 hover:bg-slate-200" title="Mostrar/Ocultar" data-action="reveal">${iconEye()}</button>
        </div>
      </div>

      <div class="flex items-start justify-between gap-4 mb-4">
        <div class="flex items-center gap-3">
          ${data.profile_pic_url ? 
            `<div class="h-9 w-9 rounded-full overflow-hidden bg-slate-100 ring-2 ring-slate-200">
              <img src="${data.profile_pic_url}" alt="Profile" class="h-full w-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
              <div class="h-full w-full grid place-items-center rounded-full text-xs font-bold text-white ${data.avatar.color}" style="display:none">${data.avatar.letters}</div>
            </div>` : 
            `<div class="grid h-9 w-9 place-items-center rounded-full text-xs font-bold text-white ${data.avatar.color}">${data.avatar.letters}</div>`
          }
          <div>
            <div class="font-medium text-slate-900">${data.contact_name || 'Contato'}</div>
            <div class="text-xs text-slate-500">
              ${data.show_phone && data.instance_phone ? 
                data.instance_phone : 
                (data.status === 'connected' ? 'Número não disponível' : 
                 data.status === 'pending' ? 'Conecte via QR Code' : 
                 'Desconectado')}
            </div>
          </div>
        </div>
        <div class="flex items-center gap-4 text-sm">
          <div class="flex items-center gap-1.5 text-slate-600">
            <span class="text-slate-400">${iconUser()}</span>
            <span class="font-medium">${data.users}</span>
          </div>
          <div class="flex items-center gap-1.5 text-slate-600">
            <span class="text-slate-400">${iconChat()}</span>
            <span class="font-medium">${data.messages}</span>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-between">
        ${getStatusChip(data.status)}
        <button class="rounded-xl bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700" data-action="delete">Delete</button>
      </div>
    `;

    // actions
    card.querySelector('[data-action="copy"]').addEventListener('click', () => {
      const real = card.querySelector('[data-instance-id]').dataset.instanceId;
      if (window.AdminCommon && window.AdminCommon.copyToClipboard) {
        window.AdminCommon.copyToClipboard(real, 'UUID da instância copiado!');
      } else {
        // Fallback
        navigator.clipboard?.writeText(real);
        toast('UUID da instância copiado.');
      }
    });

    card.querySelector('[data-action="reveal"]').addEventListener('click', () => {
      const span = card.querySelector('[data-instance-id]');
      if (span.dataset.visible === '1') {
        span.textContent = span.dataset.instanceId.replace(/./g, '*');
        span.dataset.visible = '0';
      } else {
        span.textContent = span.dataset.instanceId;
        span.dataset.visible = '1';
      }
    });

    card.querySelector('[data-action="delete"]').addEventListener('click', () => {
      if (confirm(`Excluir a instância "${data.instance_name}"?`)) {
        // Usar instanceName para deletar na API Evolution
        const deleteData = new URLSearchParams();
        deleteData.append('instanceName', data.instanceName || data.instance_name);
        if (data.id) {
          deleteData.append('instanceId', data.id);
        }
        
        fetch(`<?= e(base_url('admin/' . $slug . '/evolution/delete')) ?>`, {
          method: 'POST',
          headers: { 
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          },
          body: deleteData.toString()
        }).then(response => {
          if (response.ok) {
            return response.json();
          } else {
            return response.text().then(text => {
              throw new Error(`HTTP ${response.status}: ${text}`);
            });
          }
        }).then(result => {
          if (result.ok) {
            card.remove();
            if (result.warning) {
              toast(result.warning, 'warning');
            } else {
              toast(result.message || 'Instância excluída com sucesso.');
            }
          } else {
            throw new Error(result.error || 'Erro desconhecido');
          }
        }).catch(error => {
          console.error('Erro ao excluir instância:', error);
          toast('Erro ao excluir instância: ' + error.message);
        });
      }
    });

    return card;
  }

  let previousStatuses = new Map(); // Para rastrear mudanças de status

  function showSkeletonLoading() {
    // Usar SkeletonSystem centralizado se disponível
    if (window.SkeletonSystem) {
      return window.SkeletonSystem.showSkeletonLoading({ 
        main: { skeleton: 'skeletonCards', content: 'realCards' } 
      });
    }
    // Fallback
    const skeletonCards = document.getElementById('skeletonCards');
    if (skeletonCards) {
      skeletonCards.style.display = 'contents';
    }
  }

  function hideSkeletonLoading() {
    // Usar SkeletonSystem centralizado se disponível
    if (window.SkeletonSystem && window.skeletonLoaderInstance) {
      window.SkeletonSystem.hideSkeletonLoading(window.skeletonLoaderInstance);
      return;
    }
    // Fallback
    const skeletonCards = document.getElementById('skeletonCards');
    if (skeletonCards) {
      skeletonCards.style.display = 'none';
    }
  }

  function mount() {
    const currentStatuses = new Map();
    
    // Esconder skeleton loading
    hideSkeletonLoading();
    
    // Limpar cards existentes (exceto skeleton) - incluindo mensagens vazias
    const realCards = cardsEl.querySelectorAll('article:not(#skeletonCards article)');
    realCards.forEach(card => card.remove());
    
    // Remover mensagens vazias anteriores
    const emptyMessages = cardsEl.querySelectorAll('.empty-instances-message');
    emptyMessages.forEach(msg => msg.remove());
    
    // Se não há instâncias, mostrar mensagem para carregar
    if (instances.length === 0) {
      const emptyMessage = document.createElement('div');
      emptyMessage.className = 'col-span-full flex flex-col items-center justify-center py-16 text-center empty-instances-message';
      emptyMessage.innerHTML = `
        <div class="text-6xl mb-4">📱</div>
        <h3 class="text-xl font-semibold text-slate-900 mb-2">Instâncias Evolution API</h3>
        <p class="text-slate-600 mb-6">Clique no botão "Atualizar" acima para carregar as instâncias da API</p>
        <button onclick="document.getElementById('refreshBtn').click()" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
          <svg class="refresh-icon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
            <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
          </svg>
          Carregar Instâncias
        </button>
      `;
      cardsEl.appendChild(emptyMessage);
      return;
    }
    
    instances.forEach((x, index) => {
      const card = renderCard(x);
      
      // Smooth reveal animation para cards novos
      card.style.opacity = '0';
      card.style.transform = 'translateY(20px)';
      card.style.transition = 'all 0.4s ease-in-out';
      
      cardsEl.appendChild(card);
      
      // Trigger smooth reveal com staggered delay
      setTimeout(() => {
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
      }, index * 100 + 50);
      
      // Verificar se houve mudança de status
      const previousStatus = previousStatuses.get(x.id);
      if (previousStatus && previousStatus !== x.status) {
        // Destacar card brevemente se o status mudou
        setTimeout(() => {
          card.classList.add('ring-2', 'ring-blue-200', 'bg-blue-50');
          setTimeout(() => {
            card.classList.remove('ring-2', 'ring-blue-200', 'bg-blue-50');
          }, 3000);
        }, index * 100 + 200);
        
        // Toast para mudanças importantes
        if (x.status === 'connected' && previousStatus !== 'connected') {
          toast(`${x.instance_name} conectou!`, 'success');
        } else if (x.status === 'disconnected' && previousStatus === 'connected') {
          toast(`${x.instance_name} desconectou!`, 'error');
        }
      }
      
      currentStatuses.set(x.id, x.status);
    });
    
    // Atualizar mapa de status
    previousStatuses = currentStatuses;
  }

  // Usar ToastSystem centralizado
  function toast(msg, type = 'success') {
    if (window.ToastSystem) {
      return window.ToastSystem.toast(msg, type);
    } else if (window.AdminCommon && window.AdminCommon.showToast) {
      window.AdminCommon.showToast(msg, type);
    } else {
      // Fallback básico
      console.log(`Toast: ${msg} (${type})`);
    }
  }

  // Inicialização com skeleton loading
  function initializePage() {
    // Mostrar skeleton loading imediatamente
    showSkeletonLoading();
    
    // Carregar dados após um delay mínimo
    setTimeout(() => {
      refreshData(false);
    }, 100);
  }

  // Carregar dados automaticamente ao abrir a página
  initializePage();
  
  // Search functionality usando função centralizada
  if (window.AdminCommon && window.AdminCommon.setupLiveSearch) {
    window.AdminCommon.setupLiveSearch('#search', 'article', (item, query) => {
      const searchText = item.dataset.name || '';
      return query === '' || searchText.includes(query);
    });
  } else {
    // Fallback
    const searchInput = document.getElementById('search');
    searchInput.addEventListener('input', (e) => {
      const query = e.target.value.toLowerCase();
      const cards = cardsEl.querySelectorAll('article');
      
      cards.forEach(card => {
        const searchText = card.dataset.name || '';
        if (searchText.includes(query)) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    });
  }

  // Status filter
  const statusBtn = document.getElementById('statusBtn');
  const statusMenu = document.getElementById('statusMenu');
  
  statusBtn.addEventListener('click', () => {
    statusMenu.classList.toggle('hidden');
  });

  document.addEventListener('click', (e) => {
    if (!statusBtn.contains(e.target) && !statusMenu.contains(e.target)) {
      statusMenu.classList.add('hidden');
    }
  });

  statusMenu.addEventListener('click', (e) => {
    if (e.target.hasAttribute('data-status')) {
      const status = e.target.getAttribute('data-status');
      const cards = cardsEl.querySelectorAll('article');
      
      cards.forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
      
      statusMenu.classList.add('hidden');
    }
  });

  // Função de refresh reutilizável
  async function refreshData(isAutomatic = false) {
    const refreshBtn = document.getElementById('refreshBtn');
    const autoIndicator = document.getElementById('autoSyncIndicator');
    
    let removeLoading = () => {};
    
    // Mostrar skeleton loading apenas se não há instâncias carregadas ou se é o primeiro carregamento
    const shouldShowSkeleton = instances.length === 0 && !isAutomatic;
    if (shouldShowSkeleton) {
      showSkeletonLoading();
    }
    
    if (!isAutomatic) {
      // Usar função centralizada para loading
      if (window.AdminCommon && window.AdminCommon.setButtonLoading) {
        removeLoading = window.AdminCommon.setButtonLoading(refreshBtn, 'Atualizando');
      } else {
        // Fallback
        const originalHtml = refreshBtn.innerHTML;
        refreshBtn.innerHTML = '<svg class="h-4 w-4 loading-refresh-icon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"></path><path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"></path></svg> Atualizando';
        refreshBtn.disabled = true;
        removeLoading = () => {
          refreshBtn.innerHTML = originalHtml;
          refreshBtn.disabled = false;
        };
      }
    } else {
      // Usar função centralizada para indicador automático
      if (window.AdminCommon && window.AdminCommon.toggleAutoIndicator) {
        window.AdminCommon.toggleAutoIndicator(autoIndicator, true);
      } else {
        // Fallback
        autoIndicator.classList.remove('hidden');
        autoIndicator.classList.add('flex');
      }
    }
    
    try {
      // Usar função centralizada para fetch
      let data;
      if (window.AdminCommon && window.AdminCommon.getJson) {
        data = await window.AdminCommon.getJson('<?= e(base_url('admin/' . $slug . '/evolution/instances/data')) ?>');
      } else {
        // Fallback
        const response = await fetch('<?= e(base_url('admin/' . $slug . '/evolution/instances/data')) ?>');
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        data = await response.json();
      }
      
      if (data.instances) {
        // Se estava mostrando skeleton, garantir delay mínimo para melhor UX
        const processData = () => {
          instances.length = 0;
          instances.push(...data.instances);
          mount();
        };
        
        if (shouldShowSkeleton) {
          // Delay mínimo de 800ms para skeleton loading
          setTimeout(processData, 800);
        } else {
          processData();
        }
      }
    } catch (error) {
      console.error('Erro ao atualizar dados:', error);
      if (!isAutomatic) {
        toast('Erro ao atualizar dados.', 'error');
      }
    } finally {
      if (!isAutomatic) {
        removeLoading();
      } else {
        setTimeout(() => {
          if (window.AdminCommon && window.AdminCommon.toggleAutoIndicator) {
            window.AdminCommon.toggleAutoIndicator(autoIndicator, false);
          } else {
            // Fallback
            autoIndicator.classList.add('hidden');
            autoIndicator.classList.remove('flex');
          }
        }, 1000);
      }
    }
  }

  // Refresh manual
  document.getElementById('refreshBtn').addEventListener('click', () => refreshData(false));

  // Auto-refresh removido - apenas quando necessário por mudança de status
  // let autoRefreshInterval = setInterval(() => refreshData(true), 30000);

  // Carregar dados automaticamente quando página ficar visível (mas sem auto-refresh por tempo)
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
      // Refresh apenas quando volta para a página, não por tempo
      refreshData(true);
    }
  });

  // Sistema de monitoramento inteligente de mudanças de status
  // Verifica mudanças apenas quando há atividade do usuário ou eventos específicos
  let lastStatusCheck = new Map();
  
  function checkStatusChanges() {
    // Fazer uma verificação rápida dos status sem refresh completo
    instances.forEach(instance => {
      const currentStatus = instance.status;
      const lastStatus = lastStatusCheck.get(instance.id);
      
      if (lastStatus && lastStatus !== currentStatus) {
        // Houve mudança de status - fazer refresh completo
        refreshData(true);
        return;
      }
      
      lastStatusCheck.set(instance.id, currentStatus);
    });
  }

  // Verificar mudanças de status quando há interação do usuário
  document.addEventListener('click', () => {
    setTimeout(checkStatusChanges, 1000); // Verificar 1 segundo após clique
  });

  // Verificar mudanças quando há foco na janela (usuário voltou)
  window.addEventListener('focus', () => {
    setTimeout(() => refreshData(true), 500);
  });

  // Modal functionality
  const modal = document.getElementById('modalNewInstance');
  const newBtn = document.getElementById('newInstanceBtn');
  const closeBtn = document.getElementById('modalClose');
  const cancelBtn = document.getElementById('modalCancelBtn');

  newBtn.addEventListener('click', () => {
    modal.classList.remove('hidden');
  });

  [closeBtn, cancelBtn].forEach(btn => {
    btn.addEventListener('click', () => {
      modal.classList.add('hidden');
    });
  });

  modal.addEventListener('click', (e) => {
    if (e.target === modal) {
      modal.classList.add('hidden');
    }
  });

  // Form submission usando função centralizada
  const form = document.getElementById('formNewInstance');
  
  if (window.AdminCommon && window.AdminCommon.submitFormAjax) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      
      window.AdminCommon.submitFormAjax(
        form, 
        null, // usar action do próprio form
        (result) => {
          // Callback de sucesso
          if (result.ok) {
            toast('Instância criada com sucesso!');
            modal.classList.add('hidden');
            form.reset();
            
            // Atualizar lista de instâncias
            refreshData(false);
          }
        },
        (error) => {
          // Callback de erro personalizado
          console.error('Erro ao criar instância:', error);
          toast('Erro ao criar instância. Tente novamente.', 'error');
        }
      );
    });
  } else {
    // Fallback - código original
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const submitBtn = form.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      
      // Mostrar loading
      submitBtn.innerHTML = '<svg class="h-4 w-4 loading-refresh-icon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"></path><path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"></path></svg> Criando';
      submitBtn.disabled = true;
      
      try {
        const formData = new FormData(form);
        const response = await fetch(form.action, {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        
        const result = await response.json();
        
        if (result.error) {
          toast(result.error, 'error');
        } else if (result.ok) {
          toast('Instância criada com sucesso!');
          modal.classList.add('hidden');
          form.reset();
          
          // Atualizar lista de instâncias
          refreshData(false);
        }
      } catch (error) {
        console.error('Erro ao criar instância:', error);
        toast('Erro ao criar instância. Tente novamente.', 'error');
      } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
      }
    });
  }
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layout.php'; ?>