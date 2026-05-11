<?php
// admin/evolution/instance_config.php — Configuração da Instância Evolution (design unificado)

$title = 'Configuração Evolution - ' . ($company['name'] ?? 'Empresa');
$activeSlug = $slug ?? ($company['slug'] ?? '');
$backUrl = base_url('admin/' . rawurlencode($activeSlug) . '/evolution');

// Verificar status do horário de funcionamento
require_once __DIR__ . '/../../../helpers/business_hours_helper.php';
$bhStatus = check_business_hours_status($hours ?? []);

// Configuração do header padronizado
$pageTitle = 'Configuração da Instância';
$pageDescription = e($instanceName) . ' — Gerencie sua conexão WhatsApp';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$breadcrumbs = [
    ['label' => 'Evolution', 'url' => base_url('admin/' . $activeSlug . '/evolution')],
    ['label' => 'Configuração']
];

ob_start();

// Preparar conteúdo extra para o header (botão atualizar)
ob_start();
?>
<div class="flex items-center gap-2">
  <!-- Progress indicator -->
  <div id="loadingProgress" class="hidden items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-xs text-blue-700">
    <div class="h-1.5 w-1.5 rounded-full bg-blue-500 animate-pulse"></div>
    <span id="loadingText">Carregando...</span>
  </div>
  
  <button id="btnRefresh" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
    <svg class="refresh-icon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
      <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
      <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
    </svg>
    Atualizar
  </button>
</div>
<?php
$extraHeaderContent = ob_get_clean();
$actions = [];
?>

<style>
/* Estilos específicos para o campo de mensagem de rejeição */
#rejectCallMessageContainer {
  transform: translateY(-10px);
  opacity: 0;
  max-height: 0;
  overflow: hidden;
}

#rejectCallMessageContainer:not(.hidden) {
  transform: translateY(0);
  opacity: 1;
  max-height: 200px;
  transition: all 0.3s ease-in-out;
}

#rejectCallMessage:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

#saveRejectMessage:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>

<!-- Sistemas centralizados carregados via layout principal -->

<div class="mx-auto max-w-6xl p-4">

  <?php include __DIR__ . '/../components/page-header.php'; ?>

  <!-- INSTANCE HEADER CARD -->
  <section class="mb-6 rounded-2xl bg-white border border-slate-200 shadow-sm">

    <!-- Banner de status do horário de funcionamento -->
    <div class="flex items-center gap-3 rounded-t-2xl border-b px-5 py-3 <?= $bhStatus['is_open'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' ?>">
      <span class="relative flex h-3 w-3">
        <span class="absolute inline-flex h-full w-full rounded-full <?= $bhStatus['is_open'] ? 'bg-green-400' : 'bg-red-400' ?> opacity-75 animate-ping"></span>
        <span class="relative inline-flex h-3 w-3 rounded-full <?= $bhStatus['is_open'] ? 'bg-green-500' : 'bg-red-500' ?>"></span>
      </span>
      <div class="flex items-center gap-2 flex-1">
        <svg class="h-4 w-4 <?= $bhStatus['is_open'] ? 'text-green-600' : 'text-red-600' ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span class="text-sm font-semibold <?= $bhStatus['is_open'] ? 'text-green-800' : 'text-red-800' ?>">
          <?= $bhStatus['is_open'] ? 'Loja aberta' : 'Loja fechada' ?>
        </span>
        <span class="text-sm <?= $bhStatus['is_open'] ? 'text-green-600' : 'text-red-600' ?>">
          — <?= e($bhStatus['current_time']) ?> · <?= e($bhStatus['today_hours']) ?>
        </span>
      </div>
      <a href="<?= e(base_url('admin/' . $activeSlug . '/settings')) ?>" 
         class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium <?= $bhStatus['is_open'] ? 'text-green-700 hover:bg-green-100' : 'text-red-700 hover:bg-red-100' ?> transition-colors"
         title="Editar horários">
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3"/></svg>
        Horários
      </a>
    </div>
    <!-- Skeleton loading para header - inicialmente visível -->
    <div id="headerSkeleton" class="p-6">
      <div class="animate-pulse">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="h-12 w-12 rounded-xl bg-slate-200"></div>
            <div>
              <div class="h-6 bg-slate-200 rounded w-32 mb-2"></div>
              <div class="h-4 bg-slate-200 rounded w-48"></div>
            </div>
          </div>
          <div class="h-6 bg-slate-200 rounded-full w-20"></div>
        </div>
        
        <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
          <div class="flex items-center gap-2">
            <div class="flex-1 h-4 bg-slate-200 rounded"></div>
            <div class="h-6 w-6 bg-slate-200 rounded"></div>
            <div class="h-6 w-6 bg-slate-200 rounded"></div>
          </div>
        </div>
        
        <div class="flex items-center justify-end gap-2 pt-2">
          <div class="h-8 w-8 bg-slate-200 rounded-lg"></div>
          <div class="h-8 bg-slate-200 rounded-lg w-20"></div>
          <div class="h-8 bg-slate-200 rounded-lg w-24"></div>
        </div>
      </div>
    </div>
    
    <!-- Conteúdo real - inicialmente oculto para evitar flash -->
    <div id="headerContent" class="p-6 hidden">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="h-12 w-12 rounded-xl admin-gradient-bg grid place-items-center text-white">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
          </div>
          <div>
            <h2 class="text-xl font-semibold text-slate-900"><?= e($instanceName) ?></h2>
            <p class="text-sm text-slate-600">Instância WhatsApp Evolution</p>
          </div>
        </div>
        <?php 
          // Status vem normalizado do controller: 'connected', 'disconnected', 'pending'
          $status = $instanceData['connectionStatus'] ?? 'unknown';
          $isConnected = in_array($status, ['open', 'connected']);
          
          // Mapear status para UI (aceita tanto valores raw quanto normalizados)
          $statusClassMap = [
            'open' => 'status-connected',
            'connected' => 'status-connected',
            'connecting' => 'status-connecting',
            'pending' => 'status-connecting',
            'close' => 'status-disconnected',
            'disconnected' => 'status-disconnected'
          ];
          $statusTextMap = [
            'open' => 'Conectado',
            'connected' => 'Conectado',
            'connecting' => 'Reconectando',
            'pending' => 'Reconectando',
            'close' => 'Desconectado',
            'disconnected' => 'Desconectado'
          ];

          $statusClass = $statusClassMap[$status] ?? 'status-pending';
          $statusText = $statusTextMap[$status] ?? 'Verificando...';
        ?>
        <span id="statusPill" class="status-pill <?= $statusClass ?>">
          <span class="status-dot"></span>
          <?= $statusText ?>
        </span>
      </div>

      <!-- Token -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-slate-700 mb-2">Token da Instância</label>
        <div class="relative">
          <input id="tokenInput" type="password" value="<?= e($instanceData['token'] ?? 'N/A') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 pr-24 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" readonly />
          <div class="absolute inset-y-0 right-2 flex items-center gap-1">
            <button id="toggleMask" class="p-2 rounded-lg hover:bg-slate-100" title="Mostrar/ocultar">
              <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="8" x2="12.01" y2="8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <button id="copyToken" class="p-2 rounded-lg hover:bg-slate-100" title="Copiar">
              <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Connection Banner - Only show when disconnected -->
      <?php if (!$isConnected): ?>
      <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-800 p-4 flex items-center gap-4">
        <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <p class="text-sm flex-1">Para conectar, escaneie o QR code ou use um código de pareamento</p>
        <div class="flex items-center gap-2">
          <button id="btnQr" class="px-4 py-2 rounded-lg text-sm font-medium bg-amber-500 hover:bg-amber-600 text-white">Conectar WhatsApp</button>
          <button id="btnRefreshState" class="p-2 rounded-lg hover:bg-amber-100" title="Recarregar estado">
            <svg class="refresh-icon w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
              <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
              <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
            </svg>
          </button>
          <button id="btnRestart" class="px-3 py-2 rounded-lg text-sm font-medium bg-slate-100 hover:bg-slate-200 text-slate-700">REINICIAR</button>
          <button id="btnDisconnect" class="px-3 py-2 rounded-lg text-sm font-medium bg-red-100 hover:bg-red-200 text-red-700">DESCONECTAR</button>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- Action buttons for connected instances -->
      <?php if ($isConnected): ?>
      <div class="flex items-center justify-end gap-2 pt-2">
        <button id="btnRefreshState" class="p-2 rounded-lg hover:bg-slate-100 text-slate-500" title="Recarregar estado">
          <svg class="refresh-icon w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
            <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
            <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
          </svg>
        </button>
        <button id="btnRestart" class="px-3 py-2 rounded-lg text-sm font-medium bg-slate-100 hover:bg-slate-200 text-slate-700">REINICIAR</button>
        <button id="btnDisconnect" class="px-3 py-2 rounded-lg text-sm font-medium bg-red-100 hover:bg-red-200 text-red-700">DESCONECTAR</button>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- STATISTICS CARDS -->
  <section class="grid gap-6 md:grid-cols-3">
    <!-- Skeleton loading para todos os cards de estatísticas -->
    <div id="statsSkeleton" class="contents">
      <!-- Skeleton Card 1 - Contatos -->
      <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
          <div class="size-12 rounded-lg skeleton-enhanced"></div>
          <div class="flex-1 space-y-2">
            <div class="h-5 rounded skeleton-enhanced" style="width: 4.5rem;"></div>
            <div class="h-9 rounded skeleton-enhanced" style="width: 3rem; animation-delay: 0.2s;"></div>
          </div>
        </div>
      </div>
      
      <!-- Skeleton Card 2 - Chats -->
      <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
          <div class="size-12 rounded-lg skeleton-enhanced" style="animation-delay: 0.3s;"></div>
          <div class="flex-1 space-y-2">
            <div class="h-5 rounded skeleton-enhanced" style="width: 3rem; animation-delay: 0.4s;"></div>
            <div class="h-9 rounded skeleton-enhanced" style="width: 3.5rem; animation-delay: 0.5s;"></div>
          </div>
        </div>
      </div>
      
      <!-- Skeleton Card 3 - Mensagens -->
      <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
          <div class="size-12 rounded-lg skeleton-enhanced" style="animation-delay: 0.6s;"></div>
          <div class="flex-1 space-y-2">
            <div class="h-5 rounded skeleton-enhanced" style="width: 5rem; animation-delay: 0.7s;"></div>
            <div class="h-9 rounded skeleton-enhanced" style="width: 4rem; animation-delay: 0.8s;"></div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Cards reais de estatísticas -->
    <div id="statsContent" class="contents hidden">
      <!-- Card 1 - Contatos -->
      <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6 stat-card" style="opacity: 0; transform: translateY(20px);">
        <div class="flex items-center gap-4">
          <div class="size-12 rounded-lg bg-blue-100 flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="8" x2="12.01" y2="8" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-slate-900">Contatos</h3>
            <p class="text-3xl font-bold text-slate-900 stat-value" data-stat="contacts"><?= number_format($instanceData['_count']['Contact'] ?? 0) ?></p>
          </div>
        </div>
      </div>
      
      <!-- Card 2 - Chats -->
      <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6 stat-card" style="opacity: 0; transform: translateY(20px);">
        <div class="flex items-center gap-4">
          <div class="size-12 rounded-lg bg-green-100 flex items-center justify-center">
            <svg class="w-6 h-6 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 11-5.93-9.14" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 4L12 14.01l-3-3" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-slate-900">Chats</h3>
            <p class="text-3xl font-bold text-slate-900 stat-value" data-stat="chats"><?= number_format($instanceData['_count']['Chat'] ?? 0) ?></p>
          </div>
        </div>
      </div>
      
      <!-- Card 3 - Mensagens -->
      <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6 stat-card" style="opacity: 0; transform: translateY(20px);">
        <div class="flex items-center gap-4">
          <div class="size-12 rounded-lg bg-purple-100 flex items-center justify-center">
            <svg class="w-6 h-6 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke-linecap="round" stroke-linejoin="round"/><line x1="3" y1="6" x2="21" y2="6" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 10a4 4 0 01-8 0" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-slate-900">Mensagens</h3>
            <p class="text-3xl font-bold text-slate-900 stat-value" data-stat="messages"><?= number_format($instanceData['_count']['Message'] ?? 0) ?></p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- INSTANCE INFO SECTION -->
  <section class="mt-6 rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
    <!-- Skeleton loading para seção de informações -->
    <div id="infoSkeleton" class="grid gap-8 lg:grid-cols-2">
      <div class="animate-pulse">
        <div class="h-6 bg-slate-200 rounded w-40 mb-4"></div>
        <div class="space-y-3">
          <div class="flex justify-between py-2 border-b border-slate-100">
            <div class="h-4 bg-slate-200 rounded w-16"></div>
            <div class="h-4 bg-slate-200 rounded w-24"></div>
          </div>
          <div class="flex justify-between py-2 border-b border-slate-100">
            <div class="h-4 bg-slate-200 rounded w-20"></div>
            <div class="h-4 bg-slate-200 rounded w-32"></div>
          </div>
          <div class="flex justify-between py-2 border-b border-slate-100">
            <div class="h-4 bg-slate-200 rounded w-18"></div>
            <div class="h-4 bg-slate-200 rounded w-28"></div>
          </div>
          <div class="flex justify-between py-2">
            <div class="h-4 bg-slate-200 rounded w-24"></div>
            <div class="h-4 bg-slate-200 rounded w-28"></div>
          </div>
        </div>
      </div>
      
      <div class="animate-pulse">
        <div class="h-6 bg-slate-200 rounded w-28 mb-4"></div>
        <div class="space-y-4">
          <div class="h-4 bg-slate-200 rounded w-32 mb-4"></div>
          <div class="space-y-3">
            <div class="h-12 bg-slate-100 rounded-lg"></div>
            <div class="h-12 bg-slate-100 rounded-lg"></div>
            <div class="h-12 bg-slate-100 rounded-lg"></div>
            <div class="h-12 bg-slate-100 rounded-lg"></div>
            <div class="h-12 bg-slate-100 rounded-lg"></div>
            <div class="h-12 bg-slate-100 rounded-lg"></div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Conteúdo real -->
    <div id="infoContent" class="grid gap-8 lg:grid-cols-2 hidden">
      <div>
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Informações da Instância</h3>
        <dl class="space-y-3">
          <div class="flex justify-between py-2 border-b border-slate-100">
            <dt class="text-sm text-slate-600">Cliente:</dt>
            <dd class="text-sm font-medium text-slate-900"><?= e($instanceData['clientName'] ?? 'Evolution') ?></dd>
          </div>
          <div class="flex justify-between py-2 border-b border-slate-100">
            <dt class="text-sm text-slate-600">Integração:</dt>
            <dd class="text-sm font-medium text-slate-900"><?= e($instanceData['integration'] ?? 'WHATSAPP-BAILEYS') ?></dd>
          </div>
          <div class="flex justify-between py-2 border-b border-slate-100">
            <dt class="text-sm text-slate-600">Criado em:</dt>
            <dd class="text-sm font-medium text-slate-900"><?= isset($instanceData['createdAt']) ? date('d/m/Y H:i', strtotime($instanceData['createdAt'])) : 'N/A' ?></dd>
          </div>
          <div class="flex justify-between py-2">
            <dt class="text-sm text-slate-600">Última atualização:</dt>
            <dd class="text-sm font-medium text-slate-900"><?= isset($instanceData['updatedAt']) ? date('d/m/Y H:i', strtotime($instanceData['updatedAt'])) : 'N/A' ?></dd>
          </div>
        </dl>
      </div>

      <div>
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Configurações</h3>
        
        <!-- Skeleton loading para configurações -->
        <div id="settingsSkeletonLoader" class="space-y-4">
          <div class="animate-pulse">
            <!-- Skeleton para aviso da API (se houver) -->
            <div class="mb-4 p-3 bg-slate-100 border border-slate-200 rounded-lg">
              <div class="flex items-center gap-2">
                <div class="w-5 h-5 bg-slate-300 rounded"></div>
                <div class="h-4 bg-slate-300 rounded w-32"></div>
              </div>
              <div class="h-3 bg-slate-200 rounded w-80 mt-1"></div>
            </div>
            <!-- Skeleton para toggles -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <div class="h-4 bg-slate-300 rounded w-24 mb-1"></div>
                  <div class="h-3 bg-slate-200 rounded w-40"></div>
                </div>
                <div class="h-6 w-11 bg-slate-200 rounded-full"></div>
              </div>
              <div class="flex items-center justify-between">
                <div>
                  <div class="h-4 bg-slate-300 rounded w-28 mb-1"></div>
                  <div class="h-3 bg-slate-200 rounded w-44"></div>
                </div>
                <div class="h-6 w-11 bg-slate-200 rounded-full"></div>
              </div>
              <div class="flex items-center justify-between">
                <div>
                  <div class="h-4 bg-slate-300 rounded w-26 mb-1"></div>
                  <div class="h-3 bg-slate-200 rounded w-36"></div>
                </div>
                <div class="h-6 w-11 bg-slate-200 rounded-full"></div>
              </div>
              <div class="flex items-center justify-between">
                <div>
                  <div class="h-4 bg-slate-300 rounded w-30 mb-1"></div>
                  <div class="h-3 bg-slate-200 rounded w-38"></div>
                </div>
                <div class="h-6 w-11 bg-slate-200 rounded-full"></div>
              </div>
              <div class="flex items-center justify-between">
                <div>
                  <div class="h-4 bg-slate-300 rounded w-32 mb-1"></div>
                  <div class="h-3 bg-slate-200 rounded w-48"></div>
                </div>
                <div class="h-6 w-11 bg-slate-200 rounded-full"></div>
              </div>
              <div class="flex items-center justify-between">
                <div>
                  <div class="h-4 bg-slate-300 rounded w-34 mb-1"></div>
                  <div class="h-3 bg-slate-200 rounded w-52"></div>
                </div>
                <div class="h-6 w-11 bg-slate-200 rounded-full"></div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Conteúdo real das configurações -->
        <div id="settingsContent" class="hidden">
          <!-- Configurações de Comportamento -->
          <?php if (empty($company['evolution_server_url']) || empty($company['evolution_api_key'])): ?>
          <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
              </svg>
              <span class="text-sm font-medium text-amber-800">Evolution API não configurada</span>
            </div>
            <p class="text-xs text-amber-700 mt-1">Configure o servidor e chave da API nas configurações da empresa para usar essas funcionalidades.</p>
          </div>
          <?php endif; ?>
          
          <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-900">Ler mensagens</p>
              <p class="text-xs text-slate-500">Marcar mensagens como lidas automaticamente</p>
            </div>
            <div class="flex items-center gap-2">
              <span id="statusReadMessages" class="text-xs text-slate-400 hidden">Carregando...</span>
              <button id="toggleReadMessages" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-200 transition-colors duration-200 ease-in-out hover:bg-slate-300" data-enabled="false" data-loading="false">
                <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
              </button>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-900">Ignorar grupos</p>
              <p class="text-xs text-slate-500">Não processar mensagens de grupos</p>
            </div>
            <div class="flex items-center gap-2">
              <span id="statusGroupsIgnore" class="text-xs text-slate-400 hidden">Carregando...</span>
              <button id="toggleGroupsIgnore" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-200 transition-colors duration-200 ease-in-out hover:bg-slate-300" data-enabled="false" data-loading="false">
                <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
              </button>
            </div>
          </div>
          
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-900">Visualizar status</p>
              <p class="text-xs text-slate-500">Marcar status como visualizado automaticamente</p>
            </div>
            <div class="flex items-center gap-2">
              <span id="statusReadStatus" class="text-xs text-slate-400 hidden">Carregando...</span>
              <button id="toggleReadStatus" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-200 transition-colors duration-200 ease-in-out hover:bg-slate-300" data-enabled="false" data-loading="false">
                <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
              </button>
            </div>
          </div>
          
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-900">Sincronizar histórico</p>
              <p class="text-xs text-slate-500">Sincronizar histórico completo do WhatsApp</p>
            </div>
            <div class="flex items-center gap-2">
              <span id="statusSyncFullHistory" class="text-xs text-slate-400 hidden">Carregando...</span>
              <button id="toggleSyncFullHistory" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-200 transition-colors duration-200 ease-in-out hover:bg-slate-300" data-enabled="false" data-loading="false">
                <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
              </button>
            </div>
          </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- NOTIFICAÇÃO DE PEDIDO -->
  <section class="mt-6 mb-6 rounded-2xl bg-white border border-slate-200 shadow-sm">
    <div class="p-6">
      <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        Notificação de Pedido
        <a href="/admin/<?= rawurlencode($activeSlug) ?>/guide/whatsapp#notifications" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a>
      </h3>
      
      <!-- Skeleton loading para notificação de pedido -->
      <div id="orderNotificationSkeleton" class="space-y-4">
        <div class="animate-pulse">
          <div class="flex items-center justify-between mb-4">
            <div>
              <div class="h-4 bg-slate-300 rounded w-40 mb-1"></div>
              <div class="h-3 bg-slate-200 rounded w-60"></div>
            </div>
            <div class="h-6 w-11 bg-slate-200 rounded-full"></div>
          </div>
          
          <div class="border border-slate-200 rounded-xl p-4 bg-slate-50">
            <div class="h-4 bg-slate-300 rounded w-32 mb-3"></div>
            <div class="h-10 bg-slate-200 rounded w-full mb-3"></div>
            <div class="h-8 bg-slate-200 rounded w-28"></div>
          </div>
        </div>
      </div>

      <!-- Conteúdo real da notificação de pedido -->
      <div id="orderNotificationContent" class="hidden">
        <div class="flex items-center justify-between mb-4">
          <div>
            <p class="text-sm font-medium text-slate-900">Notificar novos pedidos</p>
            <p class="text-xs text-slate-500">Enviar mensagem para números WhatsApp quando houver novo pedido</p>
          </div>
          <div class="flex items-center gap-2">
            <span id="statusOrderNotification" class="text-xs text-slate-400 hidden">Carregando...</span>
            <button id="toggleOrderNotification" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-200 transition-colors duration-200 ease-in-out hover:bg-slate-300" data-enabled="false" data-loading="false">
              <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
            </button>
          </div>
        </div>

        <!-- Container para configuração de números - inicialmente oculto -->
        <div id="orderNotificationGroupContainer" class="hidden border border-slate-200 rounded-xl p-4 pb-5 bg-slate-50">
          <!-- Aviso sobre grupos em manutenção -->
          <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
            <div class="flex items-start gap-2">
              <svg class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
              </svg>
              <div>
                <p class="text-sm font-medium text-amber-800">Notificação via grupos em manutenção</p>
                <p class="text-xs text-amber-700 mt-1">No momento, as notificações estão sendo enviadas para números individuais. Os grupos WhatsApp estarão disponíveis em breve.</p>
              </div>
            </div>
          </div>
          
          <div class="mb-4">
            <label for="orderNotificationNumber1" class="block text-sm font-medium text-slate-700 mb-2">
              Número Principal para Notificações
            </label>
            <input type="tel" id="orderNotificationNumber1" placeholder="(51) 99999-9999" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" maxlength="15">
            <p class="text-xs text-slate-500 mt-1">Digite o número com DDD (Ex: 51 99999-9999)</p>
            <p id="number1Status" class="text-xs mt-1 hidden"></p>
          </div>
          
          <div>
            <label for="orderNotificationNumber2" class="block text-sm font-medium text-slate-700 mb-2">
              Número Secundário (opcional)
            </label>
            <input type="tel" id="orderNotificationNumber2" placeholder="(51) 99999-9999" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" maxlength="15">
            <p class="text-xs text-slate-500 mt-1">Número adicional para receber cópia das notificações</p>
            <p id="number2Status" class="text-xs mt-1 hidden"></p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ENGAJAMENTO DE CLIENTES -->
  <section class="mt-6 mb-6 rounded-2xl bg-white border border-slate-200 shadow-sm">
    <div class="p-6">
      <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
        </svg>
        Engajamento Automático de Clientes
        <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-emerald-100 text-emerald-700 rounded-full">Novo</span>
      </h3>
      
      <!-- Skeleton loading -->
      <div id="engagementSkeleton" class="space-y-4">
        <div class="animate-pulse">
          <div class="flex items-center justify-between mb-4">
            <div>
              <div class="h-4 bg-slate-300 rounded w-48 mb-1"></div>
              <div class="h-3 bg-slate-200 rounded w-72"></div>
            </div>
            <div class="h-6 w-11 bg-slate-200 rounded-full"></div>
          </div>
          <div class="border border-slate-200 rounded-xl p-4 bg-slate-50">
            <div class="h-4 bg-slate-300 rounded w-40 mb-3"></div>
            <div class="h-10 bg-slate-200 rounded w-full mb-3"></div>
          </div>
        </div>
      </div>

      <!-- Conteúdo real -->
      <div id="engagementContent" class="hidden">
        <!-- Toggle principal -->
        <div class="flex items-center justify-between mb-4">
          <div>
            <p class="text-sm font-medium text-slate-900">Ativar engajamento automático</p>
            <a href="/admin/<?= rawurlencode($activeSlug) ?>/guide/whatsapp#engagement" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a>
            <p class="text-xs text-slate-500">Enviar mensagens automáticas para recuperar clientes</p>
          </div>
          <div class="flex items-center gap-2">
            <span id="statusEngagement" class="text-xs text-slate-400 hidden">Carregando...</span>
            <button id="toggleEngagement" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-200 transition-colors duration-200 ease-in-out hover:bg-slate-300" data-enabled="false" data-loading="false">
              <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
            </button>
          </div>
        </div>

        <!-- Container de configurações (aparece quando ativado) -->
        <div id="engagementConfigContainer" class="hidden space-y-4">
          <!-- Cenário 1: Cadastro sem pedido -->
          <div class="border border-slate-200 rounded-xl p-4 bg-slate-50">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                  <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                  </svg>
                </div>
                <div>
                  <p class="text-sm font-medium text-slate-900">Cenário 1: Cadastro sem pedido</p>
                  <p class="text-xs text-slate-500">Cliente se cadastra mas não finaliza o primeiro pedido</p>
                </div>
              </div>
              <button id="toggleScenario1" class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-emerald-500 transition-colors duration-200 ease-in-out" data-enabled="true">
                <span class="pointer-events-none inline-block h-4 w-4 translate-x-4 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
              </button>
            </div>
            <div id="scenario1Config" class="mt-3 pl-11">
              <label class="block text-xs font-medium text-slate-600 mb-1">Tempo de espera (minutos)</label>
              <div class="flex items-center gap-2">
                <input type="number" id="scenario1Delay" value="10" min="5" max="60" class="w-20 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                <span class="text-xs text-slate-500">após o cadastro</span>
              </div>
              <p class="text-xs text-slate-400 mt-1">Recomendado: 10 minutos</p>
            </div>
          </div>

          <!-- Cenário 2: Cliente inativo -->
          <div class="border border-slate-200 rounded-xl p-4 bg-slate-50">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                  <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                  </svg>
                </div>
                <div>
                  <p class="text-sm font-medium text-slate-900">Cenário 2: Cliente inativo</p>
                  <p class="text-xs text-slate-500">Cliente que não faz pedidos há algum tempo</p>
                </div>
              </div>
              <button id="toggleScenario2" class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-emerald-500 transition-colors duration-200 ease-in-out" data-enabled="true">
                <span class="pointer-events-none inline-block h-4 w-4 translate-x-4 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
              </button>
            </div>
            <div id="scenario2Config" class="mt-3 pl-11">
              <label class="block text-xs font-medium text-slate-600 mb-1">Período de inatividade (dias)</label>
              <div class="flex items-center gap-2">
                <input type="number" id="scenario2Days" value="15" min="7" max="90" class="w-20 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                <span class="text-xs text-slate-500">sem pedidos</span>
              </div>
              <p class="text-xs text-slate-400 mt-1">Recomendado: 15 dias</p>
            </div>
          </div>

          <!-- Informações sobre funcionamento -->
          <div class="border border-emerald-200 rounded-xl p-4 bg-emerald-50">
            <div class="flex items-start gap-3">
              <svg class="w-5 h-5 text-emerald-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
              </svg>
              <div class="text-xs text-emerald-800">
                <p class="font-medium mb-1">Como funciona:</p>
                <ul class="list-disc list-inside space-y-0.5 text-emerald-700">
                  <li>As mensagens são enviadas apenas no horário de funcionamento</li>
                  <li>Cada cliente recebe no máximo 1 mensagem por cenário a cada 30 dias</li>
                  <li>As mensagens são humanizadas e divididas em 3 partes</li>
                  <li>O sistema usa saudações dinâmicas (Bom dia/tarde/noite)</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Estatísticas (resumido) -->
          <div id="engagementStats" class="hidden border border-slate-200 rounded-xl p-4 bg-white">
            <p class="text-xs font-medium text-slate-600 mb-2">Últimos 30 dias</p>
            <div class="grid grid-cols-3 gap-4 text-center">
              <div>
                <p class="text-lg font-bold text-slate-900" id="statsTotalSent">0</p>
                <p class="text-xs text-slate-500">Mensagens enviadas</p>
              </div>
              <div>
                <p class="text-lg font-bold text-blue-600" id="statsScenario1">0</p>
                <p class="text-xs text-slate-500">Cenário 1</p>
              </div>
              <div>
                <p class="text-lg font-bold text-amber-600" id="statsScenario2">0</p>
                <p class="text-xs text-slate-500">Cenário 2</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- AUTOMACAO POR EXPEDIENTE -->
  <section class="mt-6 mb-6 rounded-2xl bg-white border border-slate-200 shadow-sm">
    <div class="p-6">
      <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m6 2.25A9 9 0 1 1 3 12a9 9 0 0 1 18 0Z" />
        </svg>
        Automacao por Expediente
      </h3>

      <div id="businessHoursSkeleton" class="space-y-4">
        <div class="animate-pulse">
          <div class="flex items-center justify-between mb-4">
            <div>
              <div class="h-4 bg-slate-300 rounded w-52 mb-1"></div>
              <div class="h-3 bg-slate-200 rounded w-80"></div>
            </div>
            <div class="h-6 w-11 bg-slate-200 rounded-full"></div>
          </div>
          <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 space-y-3">
            <div class="h-3 bg-slate-300 rounded w-40"></div>
            <div class="h-10 bg-slate-200 rounded w-full"></div>
            <div class="h-10 bg-slate-200 rounded w-full"></div>
          </div>
        </div>
      </div>

      <div id="businessHoursContent" class="hidden">

      <div class="flex items-center justify-between mb-4">
        <div>
          <p class="text-sm font-medium text-slate-900">Ativar controle automatico de presenca e chamadas</p>
          <p class="text-xs text-slate-500">Dentro do horario: sempre online ligado e rejeitar chamadas desligado. Fora do horario: sempre online desligado e rejeitar chamadas ligado.</p>
        </div>
        <button id="toggleBusinessHoursAutomation" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-200 transition-colors duration-200 ease-in-out hover:bg-slate-300" data-enabled="false" data-loading="false">
          <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
        </button>
      </div>

      <div id="businessHoursAutomationConfigContainer" class="hidden space-y-3">
        <div class="border border-emerald-200 rounded-xl p-4 bg-emerald-50">
          <p class="text-xs font-medium text-emerald-800 mb-1">Comportamento automatico ativo</p>
          <p id="businessHoursAutomationState" class="text-xs text-emerald-700">
            Carregando estado do expediente...
          </p>
        </div>

        <div class="border border-slate-200 rounded-xl p-4 bg-white space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-900">Rejeitar chamadas <a href="/admin/<?= rawurlencode($activeSlug) ?>/guide/whatsapp#settings" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></p>
              <p class="text-xs text-slate-500">Recusar automaticamente chamadas recebidas</p>
            </div>
            <div class="flex items-center gap-2">
              <span id="statusRejectCalls" class="text-xs text-slate-400 hidden">Carregando...</span>
              <button id="toggleRejectCalls" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-200 transition-colors duration-200 ease-in-out hover:bg-slate-300" data-enabled="false" data-loading="false" data-locked="false">
                <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
              </button>
            </div>
          </div>

          <div id="rejectCallMessageContainer" class="transition-all duration-300 ease-in-out">
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
              <label for="rejectCallMessage" class="block text-sm font-medium text-slate-700 mb-2">
                Mensagem ao rejeitar chamada
              </label>
              <textarea 
                id="rejectCallMessage" 
                class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                rows="3"
                placeholder="Digite a mensagem que será enviada quando uma chamada for rejeitada automaticamente..."
              ></textarea>
              <p id="rejectCallMessageHint" class="mt-2 text-xs text-slate-500">Esta mensagem sera enviada quando a chamada for rejeitada automaticamente.</p>
              <div class="flex justify-end mt-2">
                <button id="saveRejectMessage" class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                  Salvar Mensagem
                </button>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-900">Sempre online</p>
              <p class="text-xs text-slate-500">Manter status online constantemente</p>
            </div>
            <div class="flex items-center gap-2">
              <span id="statusAlwaysOnline" class="text-xs text-slate-400 hidden">Carregando...</span>
              <button id="toggleAlwaysOnline" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-200 transition-colors duration-200 ease-in-out hover:bg-slate-300" data-enabled="false" data-loading="false" data-locked="false">
                <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
              </button>
            </div>
          </div>
        </div>
      </div>
      </div>
    </div>
  </section>

  <!-- RESPOSTA FORA DO EXPEDIENTE -->
  <section class="mt-6 mb-6 rounded-2xl bg-white border border-slate-200 shadow-sm">
    <div class="p-6">
      <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
        </svg>
        Resposta Fora do Expediente
      </h3>

      <div id="outOfHoursSkeleton" class="space-y-4">
        <div class="animate-pulse">
          <div class="flex items-center justify-between mb-4">
            <div>
              <div class="h-4 bg-slate-300 rounded w-44 mb-1"></div>
              <div class="h-3 bg-slate-200 rounded w-72"></div>
            </div>
            <div class="h-6 w-11 bg-slate-200 rounded-full"></div>
          </div>
          <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 space-y-3">
            <div class="h-3 bg-slate-300 rounded w-32"></div>
            <div class="h-24 bg-slate-200 rounded w-full"></div>
          </div>
        </div>
      </div>

      <div id="outOfHoursContent" class="hidden">
      
      <!-- Toggle principal -->
      <div class="flex items-center justify-between mb-4">
        <div>
          <p class="text-sm font-medium text-slate-900">Ativar resposta automática</p>
          <p class="text-xs text-slate-500">Responder automaticamente quando cliente enviar mensagem fora do horário de funcionamento</p>
        </div>
        <button id="toggleOutOfHours" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-200 transition-colors duration-200 ease-in-out hover:bg-slate-300" data-enabled="false" data-loading="false">
          <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
        </button>
      </div>

      <!-- Container de configurações (aparece quando ativado) -->
      <div id="outOfHoursConfigContainer" class="hidden space-y-4">
        <!-- Área de edição de mensagem personalizada -->
        <div class="border border-slate-200 rounded-xl p-4 bg-slate-50">
          <div class="flex items-center justify-between mb-3">
            <label class="text-sm font-medium text-slate-700">Mensagem personalizada</label>
            <button type="button" id="btnUseDefaultMessage" class="text-xs text-purple-600 hover:text-purple-800 font-medium">
              Usar mensagem padrão
            </button>
          </div>
          <textarea id="outOfHoursMessage" 
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 resize-none"
            rows="4"
            placeholder="Deixe em branco para usar a mensagem padrão. Use {saudacao} para Bom dia/Boa tarde/Boa noite, {dia} para dia da semana, {hora} para horário de abertura."
          ></textarea>
          <div class="flex items-center justify-between mt-2">
            <p class="text-xs text-slate-400">Variáveis: {saudacao}, {dia}, {hora}</p>
            <button type="button" id="btnSaveOutOfHoursMessage" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-purple-600 hover:bg-purple-700 text-white transition-colors">
              Salvar Mensagem
            </button>
          </div>
        </div>
        
        <!-- Exemplo da mensagem padrão -->
        <div class="bg-purple-50 border border-purple-200 rounded-xl p-4">
          <p class="text-xs text-purple-700 mb-1 font-medium">Exemplo da mensagem padrão:</p>
          <p class="text-xs text-purple-600 italic">"Boa noite! 😊 Obrigado por entrar em contato! No momento estamos fora do horário de atendimento. Voltamos amanhã às 19:00. Assim que abrirmos, retornaremos sua mensagem! 🙌"</p>
        </div>

        <!-- Informações -->
        <div class="border border-purple-200 rounded-xl p-4 bg-purple-50">
          <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-purple-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
            </svg>
            <div class="text-xs text-purple-800">
              <p class="font-medium mb-1">Como funciona:</p>
              <ul class="list-disc list-inside space-y-0.5 text-purple-700">
                <li>Detecta automaticamente quando a loja está fechada</li>
                <li>Usa o horário de funcionamento configurado na loja</li>
                <li>Cooldown de 30 minutos entre respostas para o mesmo cliente</li>
                <li>A mensagem é personalizada com saudação e horário de reabertura</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
      </div>
    </div>
  </section>

  <!-- RESPOSTA PAUSA PROGRAMADA -->
  <section class="mt-6 mb-6 rounded-2xl bg-white border border-slate-200 shadow-sm">
    <div class="p-6">
      <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        Resposta em Pausa Programada
      </h3>

      <div id="scheduledPauseSkeleton" class="space-y-4">
        <div class="animate-pulse">
          <div class="flex items-center justify-between mb-4">
            <div>
              <div class="h-4 bg-slate-300 rounded w-48 mb-1"></div>
              <div class="h-3 bg-slate-200 rounded w-72"></div>
            </div>
            <div class="h-6 w-11 bg-slate-200 rounded-full"></div>
          </div>
          <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 space-y-3">
            <div class="h-3 bg-slate-300 rounded w-32"></div>
            <div class="h-24 bg-slate-200 rounded w-full"></div>
          </div>
        </div>
      </div>

      <div id="scheduledPauseContent" class="hidden">
      
      <!-- Toggle principal -->
      <div class="flex items-center justify-between mb-4">
        <div>
          <p class="text-sm font-medium text-slate-900">Ativar resposta automática</p>
          <p class="text-xs text-slate-500">Responder automaticamente quando a loja estiver em pausa programada</p>
        </div>
        <button id="toggleScheduledPause" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-200 transition-colors duration-200 ease-in-out hover:bg-slate-300" data-enabled="false" data-loading="false">
          <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
        </button>
      </div>

      <!-- Container de configurações (aparece quando ativado) -->
      <div id="scheduledPauseConfigContainer" class="hidden space-y-4">
        <!-- Área de edição de mensagem personalizada -->
        <div class="border border-slate-200 rounded-xl p-4 bg-slate-50">
          <div class="flex items-center justify-between mb-3">
            <label class="text-sm font-medium text-slate-700">Mensagem personalizada</label>
            <button type="button" id="btnUseDefaultPauseMessage" class="text-xs text-orange-600 hover:text-orange-800 font-medium">
              Usar mensagem padrão
            </button>
          </div>
          <textarea id="scheduledPauseMessage" 
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 resize-none"
            rows="4"
            placeholder="Deixe em branco para usar a mensagem padrão. Use {motivo} para o motivo da pausa e {tempo_restante} para o tempo restante."
          ></textarea>
          <div class="flex items-center justify-between mt-2">
            <p class="text-xs text-slate-400">Variáveis: {motivo}, {tempo_restante}</p>
            <button type="button" id="btnSaveScheduledPauseMessage" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-orange-600 hover:bg-orange-700 text-white transition-colors">
              Salvar Mensagem
            </button>
          </div>
        </div>
        
        <!-- Exemplo da mensagem padrão -->
        <div class="bg-orange-50 border border-orange-200 rounded-xl p-4">
          <p class="text-xs text-orange-700 mb-1 font-medium">Exemplo da mensagem padrão:</p>
          <p class="text-xs text-orange-600 italic">"Olá! 👋 [Nome da loja] está temporariamente em pausa. Voltaremos em aproximadamente 30 minutos. Aguardamos seu retorno! 🙏"</p>
        </div>

        <!-- Informações -->
        <div class="border border-orange-200 rounded-xl p-4 bg-orange-50">
          <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-orange-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
            </svg>
            <div class="text-xs text-orange-800">
              <p class="font-medium mb-1">Como funciona:</p>
              <ul class="list-disc list-inside space-y-0.5 text-orange-700">
                <li>Detecta quando a loja está em Pausa Programada</li>
                <li>Tem prioridade sobre a mensagem "Fora do Expediente"</li>
                <li>Mostra o motivo da pausa se configurado</li>
                <li>Informa o tempo restante da pausa (se temporizada)</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
      </div>
    </div>
  </section>

</div>

  <!-- QR CODE / PAIRING CODE MODAL -->
  <div id="qrModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50" id="qrModalBg"></div>
    <div class="relative w-[520px] max-w-[92vw] rounded-2xl bg-white shadow-xl border border-slate-200 p-6">
      <div class="flex items-center justify-between mb-4">
        <h4 class="text-lg font-semibold text-slate-900">Conectar WhatsApp</h4>
        <button class="p-2 rounded-lg hover:bg-slate-100 text-slate-500" id="closeQr" aria-label="Fechar">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
      </div>

      <!-- Tabs de seleção de método -->
      <div class="flex border-b border-slate-200 mb-4">
        <button id="tabQrCode" class="flex-1 py-2.5 text-sm font-medium text-center border-b-2 border-amber-500 text-amber-600 transition-colors" data-tab="qr">
          <svg class="inline w-4 h-4 mr-1 -mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="3" height="3"/><line x1="21" y1="14" x2="21" y2="21"/><line x1="14" y1="21" x2="21" y2="21"/></svg>
          QR Code
        </button>
        <button id="tabPairingCode" class="flex-1 py-2.5 text-sm font-medium text-center border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition-colors" data-tab="pairing">
          <svg class="inline w-4 h-4 mr-1 -mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 12h.01M10 12h.01M14 12h.01M18 12h.01"/></svg>
          Código de Pareamento
        </button>
      </div>

      <!-- Conteúdo da aba QR Code -->
      <div id="panelQrCode" class="tab-panel">
        <div class="bg-slate-50 rounded-xl p-8 border border-slate-200" id="qrContainer">
          <div class="text-center">
            <svg class="mx-auto mb-4 w-8 h-8 loading-refresh-icon text-indigo-600" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
              <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"></path>
              <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"></path>
            </svg>
            <p class="text-sm text-slate-600">Clique em "Atualizar QR" para gerar</p>
          </div>
        </div>
        <!-- Countdown timer -->
        <div id="qrCountdown" class="hidden mt-3 text-center">
          <div class="inline-flex items-center gap-2 text-xs text-slate-500">
            <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <circle cx="12" cy="12" r="10" stroke-width="1.5" stroke-dasharray="60" stroke-dashoffset="20"/>
            </svg>
            <span>Próxima atualização em <span id="qrCountdownSeconds" class="font-medium text-amber-600">20</span>s</span>
          </div>
        </div>
        <div class="mt-4 flex justify-end gap-3">
          <button id="refreshQrBtn" class="px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
              <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
              <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
            </svg>
            Atualizar QR
          </button>
          <button id="closeQr2" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700">Fechar</button>
        </div>
      </div>

      <!-- Conteúdo da aba Código de Pareamento -->
      <div id="panelPairingCode" class="tab-panel hidden">
        <div class="bg-slate-50 rounded-xl p-6 border border-slate-200">
          <p class="text-sm text-slate-600 mb-4">
            Informe o número do WhatsApp que será conectado. Um código de 8 dígitos será gerado para você digitar no celular.
          </p>
          <div class="flex gap-3">
            <div class="flex-1">
              <label for="pairingPhoneInput" class="block text-xs font-medium text-slate-500 mb-1">Número com DDD</label>
              <input id="pairingPhoneInput" type="tel" placeholder="5551999999999" 
                     class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                     maxlength="15" />
            </div>
            <div class="flex items-end">
              <button id="btnRequestPairing" class="px-4 py-2.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium whitespace-nowrap">
                Gerar Código
              </button>
            </div>
          </div>
        </div>

        <!-- Resultado do pairing code -->
        <div id="pairingResult" class="hidden mt-4">
          <div class="bg-green-50 rounded-xl p-6 border border-green-200 text-center">
            <p class="text-xs text-green-600 font-medium mb-2">SEU CÓDIGO DE PAREAMENTO</p>
            <p id="pairingCodeDisplay" class="text-4xl font-mono font-bold tracking-[0.3em] text-green-700 mb-3">----‑----</p>
            <div class="text-sm text-slate-600 space-y-1">
              <p>No celular, abra o <strong>WhatsApp</strong></p>
              <p>Vá em <strong>Configurações → Dispositivos conectados → Conectar dispositivo</strong></p>
              <p>Toque em <strong>"Conectar com número de telefone"</strong></p>
              <p>Digite o código acima</p>
            </div>
          </div>
        </div>

        <!-- Polling status -->
        <div id="pairingPolling" class="hidden mt-3 text-center">
          <div class="inline-flex items-center gap-2 text-xs text-slate-500">
            <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <circle cx="12" cy="12" r="10" stroke-width="1.5" stroke-dasharray="60" stroke-dashoffset="20"/>
            </svg>
            <span>Aguardando conexão... <span id="pairingCountdownSeconds" class="font-medium text-amber-600">60</span>s</span>
          </div>
        </div>

        <div class="mt-4 flex justify-end gap-3">
          <button id="closePairing" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700">Fechar</button>
        </div>
      </div>

    </div>
  </div>

  <!-- Toast container -->
  <div id="toasts" class="fixed bottom-4 right-4 space-y-2 z-[200]"></div>

  <!-- Editor de Template de Mensagem -->
  <script src="<?= base_url('assets/js/order-message-editor.js') ?>"></script>

  <script>
    // Cache busting - forçar reload do JavaScript
    console.log('Evolution Instance Config JS - v2.1 - <?= date("Y-m-d H:i:s") ?>');
    
    const el = (id) => document.getElementById(id);
    const instanceName = '<?= htmlspecialchars($instanceName) ?>';
    const baseUrl = '<?= base_url('admin/' . rawurlencode($company['slug']) . '/evolution/instance/') ?>';
    const isStoreOpenNow = <?= !empty($bhStatus['is_open']) ? 'true' : 'false' ?>;
    let businessHoursAutomationEnabled = null;

    console.log('Configuração inicial:');
    console.log('- instanceName:', instanceName);
    console.log('- baseUrl:', baseUrl);
    console.log('- URL de grupos:', baseUrl + instanceName + '/groups');
    console.log('- Loja aberta agora:', isStoreOpenNow);

    function clearBusinessHoursStatusIndicators() {
      ['statusRejectCalls', 'statusAlwaysOnline'].forEach((id) => {
        const statusEl = el(id);
        if (!statusEl) return;
        if (statusEl.textContent === 'Ajustado pelo horario') {
          statusEl.classList.add('hidden');
        }
      });
    }

    function setBusinessHoursManagedLock(locked) {
      ['toggleRejectCalls', 'toggleAlwaysOnline'].forEach((toggleId) => {
        const toggle = el(toggleId);
        if (!toggle) return;

        toggle.dataset.locked = locked ? 'true' : 'false';
        if (locked) {
          toggle.classList.add('opacity-70', 'cursor-not-allowed');
          toggle.classList.remove('cursor-pointer');
        } else {
          toggle.classList.remove('opacity-70', 'cursor-not-allowed');
          toggle.classList.add('cursor-pointer');
        }
      });
    }

    function updateBusinessHoursAutomationStateText() {
      const stateEl = el('businessHoursAutomationState');
      if (!stateEl) return;

      if (businessHoursAutomationEnabled !== true) {
        stateEl.textContent = 'Automacao desativada: voce pode ajustar "Sempre online" e "Rejeitar chamadas" manualmente.';
        return;
      }

      if (isStoreOpenNow) {
        stateEl.textContent = 'Loja aberta agora: sempre online fica ligado e rejeitar chamadas fica desligado.';
      } else {
        stateEl.textContent = 'Loja fechada agora: sempre online fica desligado e rejeitar chamadas fica ligado.';
      }
    }

    async function enforceBusinessHoursSettings(settings = {}, options = { showToast: true }) {
      if (businessHoursAutomationEnabled !== true) {
        return;
      }

      const targetRejectCall = !isStoreOpenNow;
      const targetAlwaysOnline = isStoreOpenNow;

      const shouldUpdateRejectCall = settings.rejectCall !== targetRejectCall;
      const shouldUpdateAlwaysOnline = settings.alwaysOnline !== targetAlwaysOnline;

      if (!shouldUpdateRejectCall && !shouldUpdateAlwaysOnline) {
        return;
      }

      try {
        const payload = {
          rejectCall: targetRejectCall,
          alwaysOnline: targetAlwaysOnline
        };

        const response = await fetch(baseUrl + instanceName + '/settings', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify(payload)
        });

        if (!response.ok) {
          throw new Error('Falha ao aplicar regras de horario');
        }

        const rejectToggle = el('toggleRejectCalls');
        const alwaysOnlineToggle = el('toggleAlwaysOnline');
        if (rejectToggle) {
          rejectToggle.dataset.enabled = targetRejectCall.toString();
          updateToggleState(rejectToggle, targetRejectCall);
        }
        if (alwaysOnlineToggle) {
          alwaysOnlineToggle.dataset.enabled = targetAlwaysOnline.toString();
          updateToggleState(alwaysOnlineToggle, targetAlwaysOnline);
        }

        const rejectStatus = el('statusRejectCalls');
        const onlineStatus = el('statusAlwaysOnline');
        if (rejectStatus) {
          rejectStatus.className = 'text-xs text-emerald-600';
          rejectStatus.textContent = 'Ajustado pelo horario';
          rejectStatus.classList.remove('hidden');
        }
        if (onlineStatus) {
          onlineStatus.className = 'text-xs text-emerald-600';
          onlineStatus.textContent = 'Ajustado pelo horario';
          onlineStatus.classList.remove('hidden');
        }

        if (options.showToast) {
          if (isStoreOpenNow) {
            toast('Loja aberta: rejeitar chamadas desligado e sempre online ligado.', 'success');
          } else {
            toast('Loja fechada: rejeitar chamadas ligado e sempre online desligado.', 'success');
          }
        }
      } catch (error) {
        console.error('Erro ao aplicar regra de horario aberto:', error);
      }
    }

    // Sistema de toast profissional - reutilizar admin-common.js
    function toast(message, type = 'info') {
      if (window.AdminCommon && window.AdminCommon.showToast) {
        // Mapear tipos para compatibilidade
        const typeMap = { 'ok': 'success', 'warn': 'warning' };
        window.AdminCommon.showToast(message, typeMap[type] || type);
      } else {
        // Fallback aprimorado com animações suaves
        const wrap = document.createElement('div');
        const base = 'pointer-events-auto px-4 py-3 rounded-xl text-sm shadow-lg border transform transition-all duration-300 ease-in-out';
        const palette = {
          info:    'bg-blue-50 border-blue-200 text-blue-800',
          success: 'bg-green-50 border-green-200 text-green-800',
          ok:      'bg-green-50 border-green-200 text-green-800',
          warn:    'bg-amber-50 border-amber-200 text-amber-800',
          warning: 'bg-amber-50 border-amber-200 text-amber-800',
          error:   'bg-red-50 border-red-200 text-red-800'
        }
        
        wrap.className = base + ' ' + (palette[type] || palette.info);
        wrap.textContent = message;
        wrap.style.transform = 'translateX(100%)';
        wrap.style.opacity = '0';
        wrap.style.whiteSpace = 'nowrap';
        wrap.style.minWidth = 'max-content';
        
        const toastsEl = el('toasts');
        if (toastsEl) {
          toastsEl.appendChild(wrap);
          
          // Animação de entrada
          requestAnimationFrame(() => {
            wrap.style.transform = 'translateX(0)';
            wrap.style.opacity = '1';
          });
          
          // Animação de saída
          setTimeout(() => {
            wrap.style.transform = 'translateX(100%)';
            wrap.style.opacity = '0';
            setTimeout(() => wrap.remove(), 300);
          }, 4700);
        }
      }
    }
    
    // Micro-interações para melhor feedback visual
    const MicroInteractions = {
      // Efeito pulse para elementos carregando
      pulse(element) {
        if (!element) return;
        element.classList.add('animate-pulse');
        return () => element.classList.remove('animate-pulse');
      },
      
      // Bounce effect para feedbacks positivos
      bounce(element) {
        if (!element) return;
        element.style.transform = 'scale(1.05)';
        element.style.transition = 'transform 0.15s ease-out';
        setTimeout(() => {
          element.style.transform = 'scale(1)';
        }, 150);
      },
      
      // Shake effect para erros
      shake(element) {
        if (!element) return;
        element.style.animation = 'shake 0.5s ease-in-out';
        setTimeout(() => {
          element.style.animation = '';
        }, 500);
      }
    };
    
    // CSS já carregado via skeleton.css - não precisa adicionar inline
    
    // Sistema de estados visuais profissional (usando SkeletonSystem centralizado)
    const VisualStates = window.SkeletonSystem ? window.SkeletonSystem.VisualStates : {
      // Fallbacks básicos caso SkeletonSystem não esteja carregado
      applyLoadingState(element) {
        if (!element) return;
        element.classList.add('skeleton-basic');
        return () => element.classList.remove('skeleton-basic');
      },
      
      revealWithAnimation(element, animation = 'fadeInScale') {
        if (!element) return;
        element.classList.add('animate-' + animation.replace(/([A-Z])/g, '-$1').toLowerCase());
      },
      
      enhanceButtons() {
        document.querySelectorAll('button').forEach(button => {
          if (button.hasAttribute('data-enhanced')) return;
          button.setAttribute('data-enhanced', 'true');
          
          button.addEventListener('click', () => {
            if (!button.disabled && MicroInteractions) {
              MicroInteractions.bounce(button);
            }
          });
        });
      }
    };

    // Sidebar mobile
    el('btnOpenSidebar')?.addEventListener('click', () => {
      const sb = document.getElementById('sidebar');
      sb?.classList.toggle('hidden');
    });

    // Token copy + mask
    el('copyToken').addEventListener('click', async () => {
      const input = el('tokenInput');
      if (window.AdminCommon && window.AdminCommon.copyToClipboard) {
        window.AdminCommon.copyToClipboard(input.value, 'Token copiado para a área de transferência!');
      } else {
        // Fallback
        try { 
          await navigator.clipboard.writeText(input.value); 
          toast('Token copiado para a área de transferência', 'ok'); 
        } catch { 
          toast('Não foi possível copiar', 'error'); 
        }
      }
    });

    el('toggleMask').addEventListener('click', () => {
      const input = el('tokenInput');
      input.type = input.type === 'password' ? 'text' : 'password';
    });

    // QR modal with auto-refresh
    const modal = el('qrModal');
    let qrRefreshInterval = null;
    let qrCountdownInterval = null;
    let countdownSeconds = 20;
    
    // Função para buscar QR Code
    async function fetchQRCode(showLoading = true) {
      if (showLoading) {
        el('qrContainer').innerHTML = `
          <div class="text-center text-slate-400">
            <svg class="mx-auto mb-2 w-8 h-8 loading-refresh-icon" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
              <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"></path>
              <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"></path>
            </svg>
            Gerando QR Code
          </div>
        `;
      }
      
      try {
        const response = await fetch(baseUrl + instanceName + '/qr_code');
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        if (result.success && result.qr) {
          el('qrContainer').innerHTML = `
            <div class="text-center">
              <img src="${result.qr}" class="max-w-full rounded-lg mx-auto mb-3" alt="QR Code" />
              <p class="text-sm text-slate-400">Escaneie este código com seu WhatsApp</p>
              <p class="text-xs text-slate-500 mt-1">WhatsApp > Menu (⋮) > Dispositivos conectados > Conectar dispositivo</p>
            </div>
          `;
          // Mostrar countdown e iniciar
          el('qrCountdown').classList.remove('hidden');
          startCountdown();
          return true;
        } else if (result.connected) {
          // Instância já conectada!
          el('qrContainer').innerHTML = `
            <div class="text-center text-green-500">
              <svg class="mx-auto mb-2 w-16 h-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <p class="font-semibold text-lg">Conectado com sucesso!</p>
              <p class="text-sm text-slate-500 mt-1">A página será atualizada...</p>
            </div>
          `;
          stopQRRefresh();
          setTimeout(() => location.reload(), 2000);
          return false;
        } else {
          el('qrContainer').innerHTML = `
            <div class="text-center text-red-400">
              <svg class="mx-auto mb-2 w-12 h-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"/>
                <path d="m15 9-6 6"/>
                <path d="m9 9 6 6"/>
              </svg>
              ${result.error || 'Erro ao carregar QR Code'}
            </div>
          `;
          return false;
        }
      } catch (error) {
        el('qrContainer').innerHTML = `
          <div class="text-center text-red-400">
            <svg class="mx-auto mb-2 w-12 h-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <circle cx="12" cy="12" r="10"/>
              <path d="m15 9-6 6"/>
              <path d="m9 9 6 6"/>
            </svg>
            Erro de conexão
          </div>
        `;
        return false;
      }
    }
    
    // Countdown visual
    function startCountdown() {
      countdownSeconds = 20;
      el('qrCountdownSeconds').textContent = countdownSeconds;
      
      if (qrCountdownInterval) clearInterval(qrCountdownInterval);
      
      qrCountdownInterval = setInterval(() => {
        countdownSeconds--;
        el('qrCountdownSeconds').textContent = countdownSeconds;
        
        if (countdownSeconds <= 0) {
          clearInterval(qrCountdownInterval);
        }
      }, 1000);
    }
    
    // Iniciar auto-refresh do QR
    function startQRRefresh() {
      stopQRRefresh(); // Limpar intervalos existentes
      
      // Atualizar a cada 20 segundos
      qrRefreshInterval = setInterval(async () => {
        const success = await fetchQRCode(false); // Não mostrar loading
        if (!success) {
          stopQRRefresh();
        }
      }, 20000);
    }
    
    // Parar auto-refresh
    function stopQRRefresh() {
      if (qrRefreshInterval) {
        clearInterval(qrRefreshInterval);
        qrRefreshInterval = null;
      }
      if (qrCountdownInterval) {
        clearInterval(qrCountdownInterval);
        qrCountdownInterval = null;
      }
      el('qrCountdown')?.classList.add('hidden');
    }
    
    // Evento do botão Conectar WhatsApp - abre modal na aba QR
    el('btnQr')?.addEventListener('click', async () => {
      modal.classList.remove('hidden');
      switchTab('qr');
      const success = await fetchQRCode(true);
      if (success) {
        startQRRefresh();
      }
    });
    
    // Botão de refresh manual
    el('refreshQrBtn')?.addEventListener('click', async () => {
      stopQRRefresh();
      const success = await fetchQRCode(true);
      if (success) {
        startQRRefresh();
        toast('QR Code atualizado!', 'ok');
      }
    });
    
    // Fechar modal - parar tudo
    function closeQRModal() {
      modal.classList.add('hidden');
      stopQRRefresh();
      stopPairingPolling();
    }

    el('closeQr').addEventListener('click', closeQRModal);
    el('closeQr2').addEventListener('click', closeQRModal);
    el('closePairing')?.addEventListener('click', closeQRModal);
    el('qrModalBg').addEventListener('click', closeQRModal);

    // === SISTEMA DE ABAS ===
    function switchTab(tab) {
      const tabQr = el('tabQrCode');
      const tabPairing = el('tabPairingCode');
      const panelQr = el('panelQrCode');
      const panelPairing = el('panelPairingCode');

      if (tab === 'qr') {
        tabQr.classList.add('border-amber-500', 'text-amber-600');
        tabQr.classList.remove('border-transparent', 'text-slate-500');
        tabPairing.classList.remove('border-amber-500', 'text-amber-600');
        tabPairing.classList.add('border-transparent', 'text-slate-500');
        panelQr.classList.remove('hidden');
        panelPairing.classList.add('hidden');
        stopPairingPolling();
      } else {
        tabPairing.classList.add('border-amber-500', 'text-amber-600');
        tabPairing.classList.remove('border-transparent', 'text-slate-500');
        tabQr.classList.remove('border-amber-500', 'text-amber-600');
        tabQr.classList.add('border-transparent', 'text-slate-500');
        panelPairing.classList.remove('hidden');
        panelQr.classList.add('hidden');
        stopQRRefresh();
      }
    }

    el('tabQrCode')?.addEventListener('click', () => {
      switchTab('qr');
    });

    el('tabPairingCode')?.addEventListener('click', () => {
      switchTab('pairing');
    });

    // === PAIRING CODE ===
    let pairingPollingInterval = null;
    let pairingCountdownInterval = null;

    function stopPairingPolling() {
      if (pairingPollingInterval) { clearInterval(pairingPollingInterval); pairingPollingInterval = null; }
      if (pairingCountdownInterval) { clearInterval(pairingCountdownInterval); pairingCountdownInterval = null; }
      el('pairingPolling')?.classList.add('hidden');
    }

    function startPairingPolling() {
      stopPairingPolling();
      let seconds = 60;
      el('pairingPolling')?.classList.remove('hidden');
      el('pairingCountdownSeconds').textContent = seconds;

      pairingCountdownInterval = setInterval(() => {
        seconds--;
        el('pairingCountdownSeconds').textContent = seconds;
        if (seconds <= 0) { stopPairingPolling(); }
      }, 1000);

      // Verificar connection_state a cada 3s
      pairingPollingInterval = setInterval(async () => {
        try {
          const res = await fetch(baseUrl + instanceName + '/connection_state');
          const data = await res.json();
          const state = data?.data?.instance?.state ?? data?.data?.state ?? null;
          if (state === 'open') {
            stopPairingPolling();
            el('pairingResult').innerHTML = `
              <div class="bg-green-50 rounded-xl p-6 border border-green-200 text-center">
                <svg class="mx-auto mb-2 w-16 h-16 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="font-semibold text-lg text-green-700">Conectado com sucesso!</p>
                <p class="text-sm text-slate-500 mt-1">A página será atualizada...</p>
              </div>
            `;
            el('pairingResult').classList.remove('hidden');
            setTimeout(() => location.reload(), 2000);
          }
        } catch(e) { /* silêncio */ }
      }, 3000);
    }

    el('btnRequestPairing')?.addEventListener('click', async () => {
      const phone = el('pairingPhoneInput').value.trim();
      if (!phone || phone.replace(/\D/g, '').length < 10) {
        toast('Informe um número válido com DDD (ex: 5551999999999)', 'warn');
        return;
      }

      const btn = el('btnRequestPairing');
      btn.disabled = true;
      btn.textContent = 'Gerando...';

      try {
        const res = await fetch(baseUrl + instanceName + '/pairing_code', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ number: phone.replace(/\D/g, '') })
        });

        const result = await res.json();

        if (result.connected) {
          toast('Instância já está conectada!', 'ok');
          setTimeout(() => location.reload(), 1500);
          return;
        }

        if (result.success && result.pairingCode) {
          el('pairingCodeDisplay').textContent = result.pairingCode;
          el('pairingResult').classList.remove('hidden');
          toast('Código gerado! Digite no WhatsApp.', 'ok');
          startPairingPolling();
        } else {
          toast(result.error || 'Erro ao gerar código', 'error');
          el('pairingResult').classList.add('hidden');
        }
      } catch (e) {
        toast('Erro de conexão ao gerar código', 'error');
      } finally {
        btn.disabled = false;
        btn.textContent = 'Gerar Código';
      }
    });

    // Enter no campo de telefone dispara gerar código
    el('pairingPhoneInput')?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        el('btnRequestPairing')?.click();
      }
    });

    // Actions
    el('btnRestart').addEventListener('click', async () => {
      if (!confirm('Deseja realmente reiniciar esta instância?')) return;
      
      try {
        el('btnRestart').disabled = true;
        el('btnRestart').textContent = 'REINICIANDO...';
        
        const response = await fetch(baseUrl + instanceName + '/restart', {method: 'POST'});
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
          toast(result.message || 'Instância reiniciada com sucesso', 'ok');
          setTimeout(() => location.reload(), 2500);
        } else {
          toast(result.error || 'Erro ao reiniciar', 'error');
        }
      } catch (error) {
        toast('Erro de conexão', 'error');
      } finally {
        el('btnRestart').disabled = false;
        el('btnRestart').textContent = 'REINICIAR';
      }
    });

    el('btnRefreshState').addEventListener('click', refreshStats);

    el('btnDisconnect').addEventListener('click', async () => {
      if (!confirm('⚠️ ATENÇÃO: Deseja realmente desconectar esta instância?\n\nIsso irá deslogar o WhatsApp e você precisará escanear o QR Code novamente.')) return;
      
      try {
        el('btnDisconnect').disabled = true;
        el('btnDisconnect').textContent = 'DESCONECTANDO...';
        
        const response = await fetch(baseUrl + instanceName + '/disconnect', {method: 'POST'});
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
          toast(result.message || 'Instância desconectada com sucesso', 'warn');
          setTimeout(() => location.reload(), 2000);
        } else {
          toast(result.error || 'Erro ao desconectar', 'error');
        }
      } catch (error) {
        toast('Erro de conexão', 'error');
      } finally {
        el('btnDisconnect').disabled = false;
        el('btnDisconnect').textContent = 'DESCONECTAR';
      }
    });

    // Refresh stats com loading profissional
    async function refreshStats(showToast = true) {
      const refreshBtn = el('btnRefresh');
      
      // Verificar se o botão existe
      if (!refreshBtn) {
        console.error('Botão btnRefresh não encontrado!');
        if (showToast) {
          toast('Erro: Botão de atualizar não encontrado', 'error');
        }
        return;
      }
      
      console.log('Botão encontrado:', refreshBtn);
      
      // Usar loading system do admin-common.js se disponível
      let removeLoading = () => {};
      
      // Sistema de loading simplificado - sem AdminCommon.js
      const originalHtml = refreshBtn.innerHTML;
      const originalDisabled = refreshBtn.disabled;
      
      // Aplicar loading state
      refreshBtn.innerHTML = `
        <svg class="h-4 w-4 loading-refresh-icon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"></path>
          <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"></path>
        </svg>
        Atualizando
      `;
      refreshBtn.disabled = true;
      refreshBtn.classList.add('opacity-75');
      
      // Função para remover loading
      removeLoading = () => {
        try {
          if (refreshBtn && originalHtml) {
            refreshBtn.innerHTML = originalHtml;
            refreshBtn.disabled = originalDisabled;
            refreshBtn.classList.remove('opacity-75');
            console.log('Loading state removido com sucesso');
          }
        } catch (error) {
          console.error('Erro ao remover loading state:', error);
        }
      };
      
      // Timeout de segurança para garantir que o loading seja removido
      const safetyTimeout = setTimeout(() => {
        console.warn('Timeout de segurança ativado - removendo loading state');
        removeLoading();
      }, 10000); // 10 segundos
      
      try {
        console.log('Iniciando refresh stats para:', baseUrl + instanceName + '/stats');
        
        const response = await fetch(baseUrl + instanceName + '/stats', {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        
        console.log('Response status:', response.status, response.statusText);
        
        if (!response.ok) {
          console.error('Response not ok:', response.status, response.statusText);
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        console.log('Result:', result);
        
        if (result.success) {
          const stats = result.data;
          console.log('Stats recebidas com sucesso:', stats);
          
          // Atualizar apenas os contadores - sem toasts
          try {
            document.querySelectorAll('[data-stat]').forEach(el => {
              const stat = el.dataset.stat;
              if (stats[stat] !== undefined) {
                el.textContent = new Intl.NumberFormat('pt-BR').format(stats[stat]);
              }
            });
            
            console.log('Contadores atualizados com sucesso');
          } catch (error) {
            console.error('Erro ao atualizar contadores:', error);
          }
          
          // Sem toast de sucesso
        } else {
          console.error('API retornou erro:', result.error);
          // Sem toast de erro
        }
      } catch (error) {
        console.error('Erro ao atualizar estatísticas:', error);
        // Sem toast de erro
      } finally {
        // Limpar timeout de segurança
        clearTimeout(safetyTimeout);
        
        // Remover loading state
        console.log('Executando removeLoading no finally...');
        removeLoading();
        console.log('removeLoading executado com sucesso');
      }
    }

    // Toggle switches functionality
    function setupToggleSwitch(elementId, settingKey) {
      const toggle = el(elementId);
      if (!toggle) return;

      const statusElementId = elementId.replace('toggle', 'status');
      const statusEl = el(statusElementId);

      toggle.addEventListener('click', async () => {
        // Evitar cliques múltiplos durante operação
        if (toggle.dataset.loading === 'true') {
          return;
        }

        if (toggle.dataset.locked === 'true') {
          toast('Este controle esta sendo gerenciado pela Automacao por Expediente.', 'warn');
          return;
        }

        const currentState = toggle.dataset.enabled === 'true';
        const newState = !currentState;

        // Regra de negócio: com loja aberta, rejeitar chamadas deve ficar OFF e sempre online ON
        if (businessHoursAutomationEnabled === true && settingKey === 'rejectCall') {
          if (isStoreOpenNow && newState === true) {
            toast('Com a loja aberta, "Rejeitar chamadas" fica desativado automaticamente.', 'warn');
            return;
          }
          if (!isStoreOpenNow && newState === false) {
            toast('Com a loja fechada, "Rejeitar chamadas" fica ativado automaticamente.', 'warn');
            return;
          }
        }

        if (businessHoursAutomationEnabled === true && settingKey === 'alwaysOnline') {
          if (isStoreOpenNow && newState === false) {
            toast('Com a loja aberta, "Sempre online" fica ativado automaticamente.', 'warn');
            return;
          }
          if (!isStoreOpenNow && newState === true) {
            toast('Com a loja fechada, "Sempre online" fica desativado automaticamente.', 'warn');
            return;
          }
        }
        
        // Mostrar loading
        toggle.dataset.loading = 'true';
        toggle.style.opacity = '0.6';
        if (statusEl) {
          statusEl.classList.remove('hidden');
          statusEl.textContent = 'Salvando...';
          statusEl.className = 'text-xs text-blue-500';
        }
        
        try {
          // Salvar via endpoint local para padronizar payload e evitar Bad Request da API direta
          const saveResponse = await fetch(baseUrl + instanceName + '/settings', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ [settingKey]: newState })
          });
          
          if (saveResponse.ok) {
            // Atualizar estado visual
            toggle.dataset.enabled = newState.toString();
            updateToggleState(toggle, newState);
            
            const settingNames = {
              'rejectCall': 'Rejeitar chamadas',
              'readMessages': 'Ler mensagens',
              'alwaysOnline': 'Sempre online',
              'groupsIgnore': 'Ignorar grupos',
              'readStatus': 'Visualizar status',
              'syncFullHistory': 'Sincronizar histórico'
            };
            
            // Feedback visual de sucesso
            if (statusEl) {
              statusEl.classList.add('hidden');
            }
          } else {
            let errorMessage = 'Erro desconhecido';
            try {
              const errorData = await saveResponse.json();
              errorMessage = errorData?.error || errorData?.message || errorMessage;
            } catch (_) {
              const errorText = await saveResponse.text();
              if (errorText) errorMessage = errorText;
            }
            throw new Error(errorMessage);
          }
        } catch (error) {
          console.error('Erro ao salvar configuração:', error);
          
          // Feedback visual de erro
          if (statusEl) {
            statusEl.textContent = 'Erro';
            statusEl.className = 'text-xs text-red-500';
            setTimeout(() => {
              statusEl.classList.add('hidden');
            }, 3000);
          }
          
          toast('Erro ao salvar configuração: ' + error.message, 'error');
        } finally {
          // Remover loading
          toggle.dataset.loading = 'false';
          toggle.style.opacity = '1';
        }
      });
    }

    function updateToggleState(toggle, enabled) {
      const thumb = toggle.querySelector('span');
      if (enabled) {
        toggle.classList.remove('bg-slate-200');
        toggle.classList.add('admin-gradient-bg');
        thumb.classList.remove('translate-x-0');
        thumb.classList.add('translate-x-5');
      } else {
        toggle.classList.add('bg-slate-200');
        toggle.classList.remove('admin-gradient-bg');
        thumb.classList.add('translate-x-0');
        thumb.classList.remove('translate-x-5');
      }
      
      // Lógica específica para o toggle "Rejeitar chamadas"
      if (toggle.id === 'toggleRejectCalls') {
        const messageContainer = el('rejectCallMessageContainer');
        const messageHint = el('rejectCallMessageHint');
        if (messageContainer) {
          if (enabled) {
            messageContainer.classList.remove('opacity-60');
            messageContainer.style.maxHeight = '200px';
            messageContainer.style.opacity = '1';
            messageContainer.style.transform = 'translateY(0)';
            if (messageHint) {
              messageHint.textContent = 'Esta mensagem sera enviada quando a chamada for rejeitada automaticamente.';
            }
          } else {
            messageContainer.classList.add('opacity-60');
            messageContainer.style.maxHeight = '200px';
            messageContainer.style.opacity = '1';
            messageContainer.style.transform = 'translateY(0)';
            if (messageHint) {
              messageHint.textContent = 'Rejeitar chamadas esta desativado no momento, mas voce pode deixar a mensagem pronta.';
            }
          }
        }
      }
    }

    // Função para salvar mensagem de rejeição
    async function saveRejectCallMessage() {
      const messageInput = el('rejectCallMessage');
      const saveButton = el('saveRejectMessage');
      
      if (!messageInput || !saveButton) return;
      
      const message = messageInput.value.trim();
      
      // Mostrar loading no botão
      const originalText = saveButton.textContent;
      saveButton.textContent = 'Salvando...';
      saveButton.disabled = true;
      
      try {
        // Salvar via endpoint local para manter payload estrito
        const saveResponse = await fetch(baseUrl + instanceName + '/settings', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({ msgCall: message })
        });
        
        if (saveResponse.ok) {
          toast('Mensagem de rejeição salva com sucesso!', 'success');
        } else {
          let errorMessage = 'Erro desconhecido';
          try {
            const errorData = await saveResponse.json();
            errorMessage = errorData?.error || errorData?.message || errorMessage;
          } catch (_) {
            const errorText = await saveResponse.text();
            if (errorText) errorMessage = errorText;
          }
          throw new Error(errorMessage);
        }
        
      } catch (error) {
        console.error('Erro ao salvar mensagem:', error);
        toast('Erro ao salvar mensagem: ' + error.message, 'error');
      } finally {
        saveButton.textContent = originalText;
        saveButton.disabled = false;
      }
    }

    // === FUNÇÕES PARA MÁSCARA DE TELEFONE ===
    
    // Aplicar máscara de telefone brasileiro: (XX) XXXXX-XXXX
    function applyPhoneMask(input) {
      let value = input.value.replace(/\D/g, '');
      
      // Limitar a 11 dígitos (DDD + 9 dígitos)
      if (value.length > 11) {
        value = value.substring(0, 11);
      }
      
      // Aplicar máscara
      if (value.length > 0) {
        value = '(' + value;
      }
      if (value.length > 3) {
        value = value.substring(0, 3) + ') ' + value.substring(3);
      }
      if (value.length > 10) {
        value = value.substring(0, 10) + '-' + value.substring(10);
      }
      
      input.value = value;
    }
    
    // Remover máscara e retornar apenas números
    function unmaskPhone(phone) {
      return phone.replace(/\D/g, '');
    }
    
    // Adicionar código do país 55 ao número
    function addCountryCode(phone) {
      const digits = unmaskPhone(phone);
      if (digits.length >= 10 && digits.length <= 11) {
        return '55' + digits;
      }
      return digits;
    }
    
    // Remover código do país 55 para exibição
    function removeCountryCode(phone) {
      const digits = String(phone).replace(/\D/g, '');
      if (digits.startsWith('55') && digits.length >= 12) {
        return digits.substring(2);
      }
      return digits;
    }
    
    // Formatar número para exibição com máscara
    function formatPhoneForDisplay(phone) {
      const digits = removeCountryCode(phone);
      if (digits.length === 11) {
        return '(' + digits.substring(0, 2) + ') ' + digits.substring(2, 7) + '-' + digits.substring(7);
      } else if (digits.length === 10) {
        return '(' + digits.substring(0, 2) + ') ' + digits.substring(2, 6) + '-' + digits.substring(6);
      }
      return phone;
    }

    // === FUNÇÕES PARA NOTIFICAÇÃO DE PEDIDO ===
    
    // Carregar grupos da instância
    async function loadInstanceGroups() {
      try {
        console.log('Iniciando busca de grupos para instância:', instanceName);
        
        const url = baseUrl + instanceName + '/groups';
        console.log('URL da requisição:', url);
        
        const response = await fetch(url, {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        
        console.log('Response status:', response.status, response.statusText);
        
        if (!response.ok) {
          // Se a resposta não for ok, vamos ver se é um problema de roteamento
          const text = await response.text();
          console.log('Response text:', text.substring(0, 200));
          throw new Error(`HTTP ${response.status}: ${response.statusText} - ${text.substring(0, 100)}`);
        }
        
        const result = await response.json();
        console.log('Resultado da API de grupos:', result);
        
        if (result.success) {
          const groups = result.data || [];
          console.log(`${groups.length} grupos encontrados`);
          return groups;
        } else {
          throw new Error(result.error || 'Erro desconhecido da API');
        }
      } catch (error) {
        console.error('Erro ao carregar grupos:', error);
        // Não retornar dados fictícios - deixar que o erro seja tratado
        throw error;
      }
    }

    // Configurar seletor de grupos
    async function setupGroupSelector() {
      const groupSelect = el('orderNotificationGroup');
      if (!groupSelect) {
        console.error('Elemento orderNotificationGroup não encontrado');
        return;
      }
      
      // Mostrar loading
      groupSelect.innerHTML = '<option value="">🔄 Carregando grupos...</option>';
      groupSelect.disabled = true;
      
      try {
        const groups = await loadInstanceGroups();
        
        // Limpar e adicionar opções
        groupSelect.innerHTML = '<option value="">Selecione um grupo</option>';
        
        if (groups.length > 0) {
          groups.forEach((group, index) => {
            const option = document.createElement('option');
            option.value = group.id;
            option.textContent = `${group.subject} (${group.participants} participantes)`;
            groupSelect.appendChild(option);
            
            console.log(`Grupo ${index + 1}:`, {
              id: group.id,
              subject: group.subject,
              participants: group.participants
            });
          });
          
          toast(`${groups.length} grupos encontrados`, 'success');
        } else {
          groupSelect.innerHTML = '<option value="">❌ Nenhum grupo encontrado</option>';
          toast('Nenhum grupo encontrado nesta instância', 'warn');
        }
        
      } catch (error) {
        console.error('Erro ao configurar seletor de grupos:', error);
        groupSelect.innerHTML = '<option value="">❌ Erro ao carregar grupos</option>';
        toast('Erro ao carregar grupos: ' + error.message, 'error');
      } finally {
        groupSelect.disabled = false;
      }
    }

    // Verificar se outra instância já tem notificação ativada
    async function checkNotificationConflict() {
      try {
        console.log('🔍 Verificando conflito de notificação...');
        
        // Usar endpoint GET específico para verificar conflito
        const response = await fetch(baseUrl + instanceName + '/check-notification-conflict', {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin' // Importante: enviar cookies de sessão
        });
        
        const result = await response.json();
        console.log('📋 Resultado da verificação:', result);
        
        if (result.success && result.has_conflict) {
          console.log('⚠️ Conflito detectado com instância:', result.active_instance);
          return {
            hasConflict: true,
            activeInstance: result.active_instance
          };
        }
        
        console.log('✅ Sem conflito');
        return { hasConflict: false };
      } catch (error) {
        console.error('❌ Erro ao verificar conflito:', error);
        return { hasConflict: false };
      }
    }

    // Mostrar modal de confirmação de troca de instância
    function showInstanceSwitchModal(activeInstance, onConfirm) {
      // Criar overlay
      const overlay = document.createElement('div');
      overlay.id = 'instanceSwitchOverlay';
      overlay.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/50';
      overlay.innerHTML = `
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-6 max-w-md mx-4">
          <div class="flex items-center gap-3 mb-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
              <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-slate-900">Notificação já ativa</h3>
            </div>
          </div>
          
          <p class="text-sm text-slate-600 mb-4">
            A instância <strong class="text-slate-900">${activeInstance}</strong> já está configurada para receber notificações de pedidos.
          </p>
          
          <p class="text-sm text-slate-600 mb-6">
            Deseja desativar as notificações em <strong>${activeInstance}</strong> e ativar nesta instância?
          </p>
          
          <div class="flex gap-3 justify-end">
            <button id="cancelSwitch" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
              Cancelar
            </button>
            <button id="confirmSwitch" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors">
              Sim, mudar para esta instância
            </button>
          </div>
        </div>
      `;
      
      document.body.appendChild(overlay);
      
      // Handlers dos botões
      document.getElementById('cancelSwitch').addEventListener('click', () => {
        overlay.remove();
        // Reverter toggle para estado anterior
        const toggle = el('toggleOrderNotification');
        if (toggle) {
          toggle.dataset.enabled = 'false';
          updateToggleState(toggle, false);
        }
      });
      
      document.getElementById('confirmSwitch').addEventListener('click', () => {
        overlay.remove();
        onConfirm();
      });
      
      // Fechar ao clicar fora - apenas fechar o modal
      overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
          console.log('❌ Modal fechado pelo usuário');
          overlay.remove();
          // Toggle já está no estado correto (não ativado), não precisa resetar
        }
      });
    }

    // Ativar notificação com force switch
    async function activateNotificationWithSwitch() {
      const container = el('orderNotificationGroupContainer');
      
      try {
        // Salvar com force_switch
        const response = await fetch(baseUrl + instanceName + '/order-notification', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            enabled: true,
            primary_number: '',
            secondary_number: '',
            force_switch: true
          })
        });
        
        const result = await response.json();
        
        if (result.success) {
          toast('Notificações ativadas nesta instância!', 'success');
          
          // Mostrar container
          if (container) {
            container.classList.remove('hidden');
            await setupGroupSelector();
            setTimeout(() => {
              container.style.maxHeight = 'none';
              container.style.opacity = '1';
            }, 10);
          }
        } else {
          toast(result.error || 'Erro ao ativar notificações', 'error');
        }
      } catch (error) {
        console.error('Erro:', error);
        toast('Erro ao ativar notificações', 'error');
      }
    }

    // Configurar toggle de notificação de pedido
    function setupOrderNotificationToggle() {
      const toggle = el('toggleOrderNotification');
      const container = el('orderNotificationGroupContainer');
      
      if (!toggle || !container) return;
      
      toggle.addEventListener('click', async () => {
        const currentState = toggle.dataset.enabled === 'true';
        const newState = !currentState;
        
        // Se está ativando, verificar conflito ANTES de mudar o toggle
        if (newState) {
          console.log('🔄 Tentando ativar notificações...');
          
          const conflict = await checkNotificationConflict();
          console.log('📋 Resultado conflito:', conflict);
          
          if (conflict.hasConflict) {
            console.log('⚠️ Conflito detectado! Mostrando modal para instância:', conflict.activeInstance);
            // NÃO atualizar o toggle ainda - aguardar confirmação
            showInstanceSwitchModal(conflict.activeInstance, async () => {
              // Usuário confirmou - agora sim ativar
              console.log('✅ Usuário confirmou switch');
              updateToggleState(toggle, true);
              toggle.dataset.enabled = 'true';
              await activateNotificationWithSwitch();
            });
            return;
          }
          
          // Sem conflito, ativar normalmente
          console.log('✅ Sem conflito, ativando...');
          updateToggleState(toggle, newState);
          toggle.dataset.enabled = newState.toString();
          
          container.classList.remove('hidden');
          toast('Carregando grupos da instância...', 'info');
          await setupGroupSelector();
          
          setTimeout(() => {
            container.style.maxHeight = 'none';
            container.style.opacity = '1';
          }, 10);
          
          // Salvar estado ativado imediatamente (mesmo sem alterar números)
          await autoSaveNotificationConfig();
          toast('Notificações ativadas!', 'success');
        } else {
          // Desativando
          updateToggleState(toggle, newState);
          toggle.dataset.enabled = newState.toString();
          
          container.style.maxHeight = '0';
          container.style.opacity = '0';
          setTimeout(() => {
            container.classList.add('hidden');
          }, 300);
          
          // Salvar estado desativado
          await autoSaveNotificationConfig();
        }
      });
    }

    // Salvar configuração automaticamente (após verificação de número ou ao ativar/desativar)
    async function autoSaveNotificationConfig() {
      const toggle = el('toggleOrderNotification');
      const number1Input = el('orderNotificationNumber1');
      const number2Input = el('orderNotificationNumber2');
      const number1Status = el('number1Status');
      const number2Status = el('number2Status');
      
      if (!toggle || !number1Input || !number2Input) return;
      
      const isEnabled = toggle.dataset.enabled === 'true';
      const number1Raw = unmaskPhone(number1Input.value.trim());
      const number2Raw = unmaskPhone(number2Input.value.trim());
      
      // Só bloquear se número estiver EXPLICITAMENTE marcado como inválido (vermelho)
      // Se o status não tem classe vermelha, permite salvar (número pode estar válido ou não verificado)
      if (number1Raw && number1Status && number1Status.classList.contains('text-red-600')) {
        console.log('⚠️ Número principal marcado como inválido, não salvando');
        return;
      }
      
      // Só bloquear secundário se preenchido E inválido
      if (number2Raw && number2Status && number2Status.classList.contains('text-red-600')) {
        console.log('⚠️ Número secundário marcado como inválido, não salvando');
        return;
      }
      
      // Adicionar código do país 55 aos números
      const number1 = number1Raw ? addCountryCode(number1Raw) : '';
      const number2 = number2Raw ? addCountryCode(number2Raw) : '';
      
      // Obter campos selecionados da mensagem
      const messageFields = window.OrderMessageEditor ? window.OrderMessageEditor.getSelectedFields() : null;
      
      console.log('💾 Salvando configuração automaticamente:', {
        enabled: isEnabled,
        primary_number: number1,
        secondary_number: number2
      });
      
      try {
        const response = await fetch(baseUrl + instanceName + '/order-notification', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            enabled: isEnabled,
            primary_number: number1,
            secondary_number: number2,
            message_fields: messageFields,
            force_switch: true // Já passou pela verificação de conflito
          })
        });
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
          console.log('✅ Configuração salva automaticamente');
        } else {
          console.error('Erro ao salvar:', result.error);
        }
        
      } catch (error) {
        console.error('Erro ao salvar configuração:', error);
      }
    }

    // Salvar configuração de notificação de pedido (mantido para compatibilidade)
    async function saveOrderNotificationConfig() {
      const toggle = el('toggleOrderNotification');
      const number1Input = el('orderNotificationNumber1');
      const number2Input = el('orderNotificationNumber2');
      const saveButton = el('saveOrderNotification');
      const number1Status = el('number1Status');
      const number2Status = el('number2Status');
      
      if (!toggle || !number1Input || !number2Input) return;
      
      const isEnabled = toggle.dataset.enabled === 'true';
      const number1Raw = unmaskPhone(number1Input.value.trim());
      const number2Raw = unmaskPhone(number2Input.value.trim());
      
      if (isEnabled && !number1Raw) {
        toast('Por favor, digite pelo menos o número principal para receber as notificações', 'error');
        number1Input.focus();
        return;
      }
      
      // Validar formato dos números (10-11 dígitos após remover máscara)
      if (isEnabled && number1Raw && (number1Raw.length < 10 || number1Raw.length > 11)) {
        toast('Número principal inválido. Digite DDD + número (10 ou 11 dígitos)', 'error');
        number1Input.focus();
        return;
      }
      
      if (isEnabled && number2Raw && (number2Raw.length < 10 || number2Raw.length > 11)) {
        toast('Número secundário inválido. Digite DDD + número (10 ou 11 dígitos)', 'error');
        number2Input.focus();
        return;
      }
      
      // Verificar se o número principal foi validado como inexistente
      if (isEnabled && number1Raw && number1Status && !number1Status.classList.contains('hidden')) {
        if (number1Status.classList.contains('text-red-600') && number1Status.textContent.includes('não existe')) {
          toast('O número principal não existe no WhatsApp. Por favor, verifique e corrija.', 'error');
          number1Input.focus();
          return;
        }
      }
      
      // Verificar se o número secundário foi validado como inexistente
      if (isEnabled && number2Raw && number2Status && !number2Status.classList.contains('hidden')) {
        if (number2Status.classList.contains('text-red-600') && number2Status.textContent.includes('não existe')) {
          toast('O número secundário não existe no WhatsApp. Por favor, verifique e corrija.', 'error');
          number2Input.focus();
          return;
        }
      }
      
      // Adicionar código do país 55 aos números
      const number1 = number1Raw ? addCountryCode(number1Raw) : '';
      const number2 = number2Raw ? addCountryCode(number2Raw) : '';
      
      // Mostrar loading no botão
      const originalText = saveButton.textContent;
      saveButton.textContent = 'Salvando...';
      saveButton.disabled = true;
      
      // Obter campos selecionados da mensagem
      const messageFields = window.OrderMessageEditor ? window.OrderMessageEditor.getSelectedFields() : null;
      
      console.log('💾 Salvando configuração:', {
        enabled: isEnabled,
        primary_number: number1,
        secondary_number: number2,
        message_fields: messageFields
      });
      
      try {
        const response = await fetch(baseUrl + instanceName + '/order-notification', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            enabled: isEnabled,
            primary_number: number1,
            secondary_number: number2,
            message_fields: messageFields
          })
        });
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
          toast('Configuração de notificação salva com sucesso!', 'success');
          
          // Mostrar resumo dos números configurados (formatados)
          const numbers = [];
          if (number1Raw) numbers.push(formatPhoneForDisplay(number1Raw));
          if (number2Raw) numbers.push(formatPhoneForDisplay(number2Raw));
          
          if (isEnabled && numbers.length > 0) {
            setTimeout(() => {
              toast(`Notificações ativas para: ${numbers.join(', ')}`, 'info');
            }, 1000);
          }
        } else {
          throw new Error(result.error || 'Erro desconhecido');
        }
        
      } catch (error) {
        console.error('Erro ao salvar configuração:', error);
        toast('Erro ao salvar configuração: ' + error.message, 'error');
      } finally {
        saveButton.textContent = originalText;
        saveButton.disabled = false;
      }
    }

    // Carregar configuração de notificação de pedido
    async function loadOrderNotificationConfig() {
      try {
        const response = await fetch(baseUrl + instanceName + '/order-notification', {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
          const config = result.data;
          const toggle = el('toggleOrderNotification');
          const number1Input = el('orderNotificationNumber1');
          const number2Input = el('orderNotificationNumber2');
          const container = el('orderNotificationGroupContainer');
          
          if (toggle) {
            toggle.dataset.enabled = config.enabled.toString();
            updateToggleState(toggle, config.enabled);
            
            if (config.enabled && container) {
              container.classList.remove('hidden');
              setTimeout(() => {
                container.style.maxHeight = 'none';
                container.style.opacity = '1';
              }, 10);
            }
          }
          
          // Preencher os números salvos (removendo código 55 e aplicando máscara)
          if (number1Input && config.primary_number) {
            number1Input.value = formatPhoneForDisplay(config.primary_number);
            // Verificar automaticamente após carregar o número
            setTimeout(() => {
              verifyWhatsAppNumber('orderNotificationNumber1', 'number1Status');
            }, 500);
          }
          
          if (number2Input && config.secondary_number) {
            number2Input.value = formatPhoneForDisplay(config.secondary_number);
            // Verificar automaticamente após carregar o número
            setTimeout(() => {
              verifyWhatsAppNumber('orderNotificationNumber2', 'number2Status');
            }, 800);
          }
          
          // Carregar campos da mensagem no editor
          if (window.OrderMessageEditor) {
            console.log('📥 Carregando campos no editor:', config.message_fields);
            window.OrderMessageEditor.loadFieldsConfig(config);
          } else {
            console.warn('⚠️ OrderMessageEditor não está disponível');
          }
          
          // Log para debug
          console.log('✅ Configuração de notificação carregada:', config);
        }
        
      } catch (error) {
        console.error('Erro ao carregar configuração de notificação:', error);
      }
    }

    // === FIM DAS FUNÇÕES DE NOTIFICAÇÃO DE PEDIDO ===

    // Inicializar máscaras de telefone
    const phone1Input = el('orderNotificationNumber1');
    const phone2Input = el('orderNotificationNumber2');
    
    if (phone1Input) {
      phone1Input.addEventListener('input', function() {
        applyPhoneMask(this);
      });
    }
    
    if (phone2Input) {
      phone2Input.addEventListener('input', function() {
        applyPhoneMask(this);
      });
    }

    // Debounce para verificação automática
    let verifyTimeout1 = null;
    let verifyTimeout2 = null;
    
    // Função para verificar se número existe no WhatsApp (automática)
    async function verifyWhatsAppNumber(inputId, statusId) {
      const input = el(inputId);
      const statusEl = el(statusId);
      
      if (!input || !statusEl) return;
      
      const numberRaw = unmaskPhone(input.value.trim());
      
      // Limpar status se número estiver vazio ou incompleto
      if (!numberRaw || numberRaw.length < 10) {
        statusEl.classList.add('hidden');
        input.classList.remove('border-green-500', 'border-red-500');
        return;
      }
      
      // Adicionar código do país
      const number = addCountryCode(numberRaw);
      
      // Mostrar loading
      statusEl.textContent = 'Verificando...';
      statusEl.className = 'text-xs mt-1 text-slate-500';
      statusEl.classList.remove('hidden');
      
      try {
        const response = await fetch(baseUrl + instanceName + '/validate-whatsapp', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({ number: number })
        });
        
        const result = await response.json();
        
        if (result.success && result.exists) {
          if (result.checked) {
            statusEl.textContent = '✓ Número válido no WhatsApp!';
            statusEl.className = 'text-xs mt-1 text-green-600 font-medium';
            input.classList.remove('border-red-500');
            input.classList.add('border-green-500');
            
            // Salvar automaticamente após verificação válida
            await autoSaveNotificationConfig();
          } else {
            statusEl.textContent = '⚠ Não foi possível verificar (Evolution desconectada)';
            statusEl.className = 'text-xs mt-1 text-amber-600';
            input.classList.remove('border-green-500', 'border-red-500');
            
            // Salvar mesmo sem verificação (Evolution offline)
            await autoSaveNotificationConfig();
          }
        } else {
          statusEl.textContent = '✗ Este número não existe no WhatsApp';
          statusEl.className = 'text-xs mt-1 text-red-600 font-medium';
          input.classList.remove('border-green-500');
          input.classList.add('border-red-500');
        }
        
      } catch (error) {
        console.error('Erro ao verificar número:', error);
        statusEl.textContent = 'Erro ao verificar número';
        statusEl.className = 'text-xs mt-1 text-red-600';
      }
    }
    
    // Configurar verificação automática com debounce
    if (phone1Input) {
      phone1Input.addEventListener('input', function() {
        clearTimeout(verifyTimeout1);
        const numberRaw = unmaskPhone(this.value.trim());
        // Só verifica se tiver 10 ou 11 dígitos (número completo)
        if (numberRaw.length >= 10 && numberRaw.length <= 11) {
          verifyTimeout1 = setTimeout(() => {
            verifyWhatsAppNumber('orderNotificationNumber1', 'number1Status');
          }, 800); // Aguarda 800ms após parar de digitar
        }
      });
    }
    
    if (phone2Input) {
      phone2Input.addEventListener('input', function() {
        clearTimeout(verifyTimeout2);
        const numberRaw = unmaskPhone(this.value.trim());
        // Só verifica se tiver 10 ou 11 dígitos (número completo)
        if (numberRaw.length >= 10 && numberRaw.length <= 11) {
          verifyTimeout2 = setTimeout(() => {
            verifyWhatsAppNumber('orderNotificationNumber2', 'number2Status');
          }, 800); // Aguarda 800ms após parar de digitar
        }
      });
      
      // Salvar ao perder foco (inclusive quando campo vazio - exclusão)
      phone2Input.addEventListener('blur', function() {
        const numberRaw = unmaskPhone(this.value.trim());
        // Se campo está vazio ou tem número válido, salvar
        if (numberRaw.length === 0 || (numberRaw.length >= 10 && numberRaw.length <= 11)) {
          // Limpar status se vazio
          const statusEl = el('number2Status');
          if (numberRaw.length === 0 && statusEl) {
            statusEl.textContent = '';
            statusEl.classList.add('hidden');
            this.classList.remove('border-green-500', 'border-red-500');
          }
          autoSaveNotificationConfig();
        }
      });
    }
    
    // Salvar número principal ao perder foco também
    if (phone1Input) {
      phone1Input.addEventListener('blur', function() {
        const numberRaw = unmaskPhone(this.value.trim());
        // Se tem número válido, salvar
        if (numberRaw.length >= 10 && numberRaw.length <= 11) {
          autoSaveNotificationConfig();
        }
      });
    }

    // Inicializar toggles (usando nomes corretos da Evolution API v2)
    // POST usa camelCase, GET retorna underscore
    setupToggleSwitch('toggleRejectCalls', 'rejectCall');
    setupToggleSwitch('toggleReadMessages', 'readMessages');
    setupToggleSwitch('toggleAlwaysOnline', 'alwaysOnline');
    setupToggleSwitch('toggleGroupsIgnore', 'groupsIgnore');
    setupToggleSwitch('toggleReadStatus', 'readStatus');
    setupToggleSwitch('toggleSyncFullHistory', 'syncFullHistory');
    
    // Configurar botão de salvar mensagem
    const saveMessageBtn = el('saveRejectMessage');
    if (saveMessageBtn) {
      saveMessageBtn.addEventListener('click', saveRejectCallMessage);
    }

    // Configurar bloco de notificação de pedido
    setupOrderNotificationToggle();

    // Carregar configurações atuais dos toggles
    async function loadInstanceSettings() {
      // Mostrar indicadores de carregamento
      ['statusRejectCalls', 'statusReadMessages', 'statusAlwaysOnline', 'statusGroupsIgnore', 'statusReadStatus', 'statusSyncFullHistory'].forEach(id => {
        const statusEl = el(id);
        if (statusEl) {
          statusEl.classList.remove('hidden');
          statusEl.textContent = 'Carregando...';
          statusEl.className = 'text-xs text-slate-400';
        }
      });

      try {
        // Tentar endpoint local primeiro, fallback para API direta
        let result;
        
        try {
          const response = await fetch(`<?= base_url('admin/' . $slug . '/evolution/instance/' . $instanceName . '/settings') ?>`, {
            method: 'GET',
            headers: {
              'Accept': 'application/json'
            }
          });
          
          if (response.ok) {
            result = await response.json();
          } else {
            throw new Error('Endpoint local não disponível');
          }
        } catch (localError) {
          console.log('Endpoint local falhou, usando API direta:', localError.message);
          
          // Fallback: chamar Evolution API diretamente
          const evolutionApiUrl = '<?= e($company['evolution_server_url'] ?? '') ?>';
          const apiKey = '<?= e($company['evolution_api_key'] ?? '') ?>';
          
          if (!evolutionApiUrl || !apiKey) {
            throw new Error('Configuração da Evolution API não encontrada');
          }
          
          const directResponse = await fetch(`${evolutionApiUrl}/settings/find/<?= e($instanceName) ?>`, {
            method: 'GET',
            headers: {
              'Accept': 'application/json',
              'apikey': apiKey
            }
          });
          
          if (directResponse.ok) {
            const directData = await directResponse.json();
            result = { success: true, data: directData };
          } else {
            throw new Error(`Evolution API error: ${directResponse.status}`);
          }
        }
        
        if (result.success) {
          // A resposta pode ter diferentes estruturas dependendo da versão da API
          const settings = result.data || {};
          
          console.log('Configurações carregadas:', settings);
          
          // Mapear configurações para toggles (API usa camelCase tanto no GET quanto no POST)
          const toggleMappings = [
            { settingKey: 'rejectCall', toggleId: 'toggleRejectCalls', statusId: 'statusRejectCalls' },
            { settingKey: 'readMessages', toggleId: 'toggleReadMessages', statusId: 'statusReadMessages' },
            { settingKey: 'alwaysOnline', toggleId: 'toggleAlwaysOnline', statusId: 'statusAlwaysOnline' },
            { settingKey: 'groupsIgnore', toggleId: 'toggleGroupsIgnore', statusId: 'statusGroupsIgnore' },
            { settingKey: 'readStatus', toggleId: 'toggleReadStatus', statusId: 'statusReadStatus' },
            { settingKey: 'syncFullHistory', toggleId: 'toggleSyncFullHistory', statusId: 'statusSyncFullHistory' }
          ];
          
          toggleMappings.forEach(mapping => {
            const toggle = el(mapping.toggleId);
            const statusEl = el(mapping.statusId);
            
            console.log(`Debug ${mapping.settingKey}:`, {
              elementFound: !!toggle,
              statusElementFound: !!statusEl,
              settingValue: settings[mapping.settingKey],
              isEnabled: settings[mapping.settingKey] === true
            });
            
            if (toggle && statusEl) {
              const isEnabled = settings[mapping.settingKey] === true;
              
              toggle.dataset.enabled = isEnabled.toString();
              updateToggleState(toggle, isEnabled);
              
              // Esconder status
              statusEl.classList.add('hidden');
            }
          });

          // Aplicar regra automática conforme horário da loja após carregar estados atuais
          await enforceBusinessHoursSettings(settings, { showToast: false });
          
          // Carregar mensagem de rejeição de chamada
          const rejectCallMessage = el('rejectCallMessage');
          if (rejectCallMessage && settings.msgCall !== undefined) {
            rejectCallMessage.value = settings.msgCall || '';
          }
        } else {
          console.log('Erro ao buscar configurações:', result.error);
          hideLoadingIndicators();
          toast('Erro ao carregar configurações da instância', 'warn');
        }
      } catch (error) {
        console.log('Erro ao carregar configurações:', error);
        hideLoadingIndicators();
        // Não mostrar toast para erro de configurações opcionais
      }
    }

    function hideLoadingIndicators() {
      ['statusRejectCalls', 'statusReadMessages', 'statusAlwaysOnline', 'statusGroupsIgnore', 'statusReadStatus', 'statusSyncFullHistory'].forEach(id => {
        const statusEl = el(id);
        if (statusEl) {
          statusEl.classList.add('hidden');
        }
      });
    }

    // Sistema de skeleton loading usando SkeletonSystem centralizado
    const SkeletonLoader = window.SkeletonSystem ? window.SkeletonSystem.createSkeletonLoader({
      header: { skeleton: 'headerSkeleton', content: 'headerContent' },
      stats: { skeleton: 'statsSkeleton', content: 'statsContent' },
      info: { skeleton: 'infoSkeleton', content: 'infoContent' },
      settings: { skeleton: 'settingsSkeletonLoader', content: 'settingsContent' }
    }) : {
      // Fallback básico caso SkeletonSystem não esteja carregado
      elements: {
        header: { skeleton: 'headerSkeleton', content: 'headerContent' },
        stats: { skeleton: 'statsSkeleton', content: 'statsContent' },
        info: { skeleton: 'infoSkeleton', content: 'infoContent' },
        settings: { skeleton: 'settingsSkeletonLoader', content: 'settingsContent' }
      },
      
      show() {
        // Fallback: apenas esconder conteúdo real
        Object.values(this.elements).forEach(({ content }) => {
          const contentEl = el(content);
          if (contentEl) contentEl.classList.add('hidden');
        });
      },
      
      // Esconder skeleton com smooth reveal
      hide() {
        const staggerDelay = 150; // Delay entre animações
        let currentDelay = 0;
        
        // Header reveal
        setTimeout(() => {
          this.revealElement(this.elements.header.skeleton, this.elements.header.content);
        }, currentDelay);
        currentDelay += staggerDelay;
        
        // Stats reveal com animação especial para cards
        setTimeout(() => {
          this.revealStatsCards();
        }, currentDelay);
        currentDelay += staggerDelay;
        
        // Info reveal
        setTimeout(() => {
          this.revealElement(this.elements.info.skeleton, this.elements.info.content, 'grid');
        }, currentDelay);
        currentDelay += staggerDelay;
        
        // Settings reveal
        setTimeout(() => {
          this.revealElement(this.elements.settings.skeleton, this.elements.settings.content);
        }, currentDelay);
      },
      
      // Revelar elemento com animações suaves
      revealElement(skeletonId, contentId, displayType = 'block') {
        const skeleton = el(skeletonId);
        const content = el(contentId);
        
        if (!skeleton || !content) return;
        
        // Esconder skeleton
        if (displayType === 'contents') {
          skeleton.style.display = 'none';
        } else {
          skeleton.classList.add('hidden');
        }
        
        // Mostrar conteúdo real
        if (displayType === 'contents') {
          content.classList.remove('hidden');
          content.style.display = displayType;
        } else {
          content.style.display = displayType;
        }
        
        this.smoothRevealElement(content);
      },
      
      // Revelar cards de estatísticas com animação especial
      revealStatsCards() {
        const skeleton = el(this.elements.stats.skeleton);
        const content = el(this.elements.stats.content);
        
        if (!skeleton || !content) return;
        
        // Esconder skeleton
        skeleton.style.display = 'none';
        
        // Mostrar conteúdo
        content.classList.remove('hidden');
        content.style.display = 'contents';
        
        // Animar cada card individualmente
        const cards = content.querySelectorAll('.stat-card');
        cards.forEach((card, index) => {
          setTimeout(() => {
            card.style.transition = 'all 0.6s cubic-bezier(0.16, 1, 0.3, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
            
            // Micro-bounce no final
            setTimeout(() => {
              card.style.transform = 'translateY(-2px)';
              setTimeout(() => {
                card.style.transform = 'translateY(0)';
              }, 150);
            }, 400);
          }, index * 150); // 150ms delay entre cada card
        });
      },
      
      // Animação suave de revelação com efeitos profissionais
      smoothRevealElement(element, delay = 0) {
        setTimeout(() => {
          // Usar sistema de animações profissional
          VisualStates.revealWithAnimation(element, 'slideInFromBottom');
          
          // Fallback para browsers que não suportam animation
          element.style.opacity = '0';
          element.style.transform = 'translateY(20px)';
          element.style.transition = 'all 0.5s cubic-bezier(0.16, 1, 0.3, 1)';
          
          // Trigger reflow
          element.offsetHeight;
          
          element.style.opacity = '1';
          element.style.transform = 'translateY(0)';
        }, delay);
      }
    };
    
    // Funções de compatibilidade (reutilizando interface existente)
    function showSkeletonLoading() {
      // Skeleton já visível por padrão, apenas garantir conteúdo oculto
      SkeletonLoader.show();
    }
    
    function hideSkeletonLoading() {
      SkeletonLoader.hide();
    }

    // Sistema de inicialização profissional com indicadores de progresso
    const PageLoader = {
      minLoadingTime: 1200, // 1.2 segundos mínimo para melhor percepção
      maxLoadingTime: 6000,  // 6 segundos máximo antes de forçar reveal
      
      // Indicador de progresso
      showProgress(text = 'Carregando...') {
        const progressEl = el('loadingProgress');
        const textEl = el('loadingText');
        if (progressEl && textEl) {
          textEl.textContent = text;
          progressEl.classList.remove('hidden');
          progressEl.classList.add('flex');
        }
      },
      
      hideProgress() {
        const progressEl = el('loadingProgress');
        if (progressEl) {
          progressEl.classList.add('hidden');
          progressEl.classList.remove('flex');
        }
      },
      
      async initialize() {
        const startTime = Date.now();
        
        // Mostrar progress indicator (skeleton já visível)
        this.showProgress('Iniciando...');
        
        try {
          // Fase 1: Carregamento de dados principais
          this.showProgress('Carregando dados da instância...');
          
          const loadingPromises = [
            this.loadStatsWithProgress(),
            this.loadSettingsWithProgress(),
            this.ensureMinimumLoadingTime(startTime)
          ];
          
          // Aguardar todos os carregamentos
          await Promise.race([
            Promise.all(loadingPromises),
            this.createTimeoutPromise()
          ]);
          
          // Fase 2: Finalizando
          this.showProgress('Finalizando...');
          await new Promise(resolve => setTimeout(resolve, 200));
          
          // Smooth reveal
          this.showProgress('Pronto!');
          setTimeout(() => {
            SkeletonLoader.hide();
            this.hideProgress();
          }, 300);
          
        } catch (error) {
          console.error('Erro durante inicialização:', error);
          this.showProgress('Erro - tentando continuar...');
          
          setTimeout(() => {
            SkeletonLoader.hide();
            this.hideProgress();
          }, 500);
        }
      },
      
      async loadStatsWithProgress() {
        return new Promise((resolve) => {
          try {
            // Pequeno delay para mostrar progresso
            setTimeout(() => {
              refreshStats(false);
              resolve();
            }, 300);
          } catch (error) {
            console.warn('Erro ao carregar estatísticas:', error);
            resolve(); // Não bloquear por erro de stats
          }
        });
      },
      
      async loadSettingsWithProgress() {
        return new Promise((resolve) => {
          try {
            // Delay escalonado para visual melhor
            setTimeout(() => {
              loadInstanceSettings();
              
              // Carregar também o bloco de notificação de pedido
              setTimeout(() => {
                const orderNotificationSkeleton = el('orderNotificationSkeleton');
                const orderNotificationContent = el('orderNotificationContent');
                
                console.log('Elementos de notificação encontrados:', {
                  skeleton: !!orderNotificationSkeleton,
                  content: !!orderNotificationContent
                });
                
                if (orderNotificationSkeleton && orderNotificationContent) {
                  orderNotificationSkeleton.classList.add('hidden');
                  orderNotificationContent.classList.remove('hidden');
                  
                  // Carregar configurações salvas
                  console.log('Carregando configurações de notificação...');
                  loadOrderNotificationConfig();
                }
              }, 300);
              
              resolve();
            }, 500);
          } catch (error) {
            console.warn('Erro ao carregar configurações:', error);
            resolve(); // Não bloquear por erro de settings
          }
        });
      },
      
      async ensureMinimumLoadingTime(startTime) {
        const elapsed = Date.now() - startTime;
        const remainingTime = Math.max(0, this.minLoadingTime - elapsed);
        
        if (remainingTime > 0) {
          await new Promise(resolve => setTimeout(resolve, remainingTime));
        }
      },
      
      createTimeoutPromise() {
        return new Promise((resolve) => {
          setTimeout(resolve, this.maxLoadingTime);
        });
      }
    };

    // Inicialização completa da página com UX profissional
    function initializeProfessionalUX() {
      // 1. Aplicar melhorias visuais
      VisualStates.enhanceButtons();
      
      // 2. Inicializar carregamento
      PageLoader.initialize().then(() => {
        // 3. Aplicar animações finais após carregamento
        setTimeout(() => {
          // Reveal final com stagger para todos os elementos principais
          const mainElements = document.querySelectorAll('section, .rounded-2xl');
          mainElements.forEach((el, index) => {
            if (el.offsetParent !== null) { // Apenas elementos visíveis
              VisualStates.revealWithAnimation(el, 'fadeInScale');
            }
          });
        }, 500);
      });
    }
    
    // Inicialização com múltiplos pontos de entrada
    document.addEventListener('DOMContentLoaded', () => {
      setTimeout(initializeProfessionalUX, 50);
    });
    
    // Fallback para DOM já carregado
    if (document.readyState === 'loading') {
      // DOMContentLoaded irá disparar
    } else {
      // DOM já pronto - inicializar imediatamente
      setTimeout(initializeProfessionalUX, 50);
    }
    
    // Refresh button - apenas um event listener
    el('btnRefresh')?.addEventListener('click', () => refreshStats(true));

    // =====================================================
    // CUSTOMER ENGAGEMENT - Sistema de Engajamento de Clientes
    // =====================================================
    
    const CustomerEngagement = {
      initialized: false,
      config: {
        enabled: false,
        scenario1_enabled: true,
        scenario1_delay: 10,
        scenario2_enabled: true,
        scenario2_days: 15,
        business_hours_automation_enabled: false
      },

      revealSection(sectionName) {
        const skeleton = el(sectionName + 'Skeleton');
        const content = el(sectionName + 'Content');

        if (skeleton) {
          skeleton.classList.add('hidden');
        }
        if (content) {
          content.classList.remove('hidden');
        }
      },

      revealLoadedContent() {
        this.revealSection('engagement');
        this.revealSection('businessHours');
        this.revealSection('outOfHours');
        this.revealSection('scheduledPause');
      },
      
      // Inicializar o sistema
      async init() {
        if (this.initialized) return;
        this.initialized = true;
        
        console.log('📱 Inicializando Customer Engagement...');
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Carregar configuração
        await this.loadConfig();

        this.revealLoadedContent();
        
        console.log('✅ Customer Engagement inicializado');
      },
      
      // Configurar event listeners
      setupEventListeners() {
        // Toggle principal
        const toggleMain = el('toggleEngagement');
        if (toggleMain) {
          toggleMain.addEventListener('click', () => this.handleMainToggle());
        }
        
        // Toggle Cenário 1
        const toggle1 = el('toggleScenario1');
        if (toggle1) {
          toggle1.addEventListener('click', () => this.handleScenarioToggle(1));
        }
        
        // Toggle Cenário 2
        const toggle2 = el('toggleScenario2');
        if (toggle2) {
          toggle2.addEventListener('click', () => this.handleScenarioToggle(2));
        }
        
        // Toggle Fora do Expediente
        const toggleOutOfHours = el('toggleOutOfHours');
        if (toggleOutOfHours) {
          toggleOutOfHours.addEventListener('click', () => this.handleOutOfHoursToggle());
        }
        
        // Toggle Pausa Programada
        const toggleScheduledPause = el('toggleScheduledPause');
        if (toggleScheduledPause) {
          toggleScheduledPause.addEventListener('click', () => this.handleScheduledPauseToggle());
        }

        // Toggle Automacao por Expediente
        const toggleBusinessHoursAutomation = el('toggleBusinessHoursAutomation');
        if (toggleBusinessHoursAutomation) {
          toggleBusinessHoursAutomation.addEventListener('click', () => this.handleBusinessHoursAutomationToggle());
        }
        
        // Inputs de configuração (salvar automaticamente ao mudar)
        const delay1Input = el('scenario1Delay');
        const days2Input = el('scenario2Days');
        
        if (delay1Input) {
          delay1Input.addEventListener('change', () => this.handleConfigChange());
        }
        
        if (days2Input) {
          days2Input.addEventListener('change', () => this.handleConfigChange());
        }
        
        // Botão para salvar mensagem fora do expediente
        const btnSaveMessage = el('btnSaveOutOfHoursMessage');
        if (btnSaveMessage) {
          btnSaveMessage.addEventListener('click', () => this.handleSaveOutOfHoursMessage());
        }
        
        // Botão para usar mensagem padrão
        const btnDefault = el('btnUseDefaultMessage');
        if (btnDefault) {
          btnDefault.addEventListener('click', () => {
            const textarea = el('outOfHoursMessage');
            if (textarea) {
              textarea.value = '';
              this.config.out_of_hours_message = '';
              this.handleSaveOutOfHoursMessage();
            }
          });
        }
        
        // Botão para salvar mensagem de pausa programada
        const btnSavePauseMessage = el('btnSaveScheduledPauseMessage');
        if (btnSavePauseMessage) {
          btnSavePauseMessage.addEventListener('click', () => this.handleSaveScheduledPauseMessage());
        }
        
        // Botão para usar mensagem padrão de pausa
        const btnDefaultPause = el('btnUseDefaultPauseMessage');
        if (btnDefaultPause) {
          btnDefaultPause.addEventListener('click', () => {
            const textarea = el('scheduledPauseMessage');
            if (textarea) {
              textarea.value = '';
              this.config.scheduled_pause_message = '';
              this.handleSaveScheduledPauseMessage();
            }
          });
        }
      },
      
      // Carregar configuração do servidor
      async loadConfig() {
        try {
          const response = await fetch(baseUrl + instanceName + '/customer-engagement', {
            method: 'GET',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          });
          
          if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
          }
          
          const result = await response.json();
          
          if (result.success && result.data) {
            const businessHoursAutomationRaw = result.data.business_hours_automation_enabled;

            this.config = {
              enabled: result.data.enabled || false,
              scenario1_enabled: result.data.scenario1_enabled !== false,
              scenario1_delay: result.data.scenario1_delay || 10,
              scenario2_enabled: result.data.scenario2_enabled !== false,
              scenario2_days: result.data.scenario2_days || 15,
              out_of_hours_enabled: result.data.out_of_hours_enabled !== false,
              out_of_hours_message: result.data.out_of_hours_message || '',
              scheduled_pause_enabled: result.data.scheduled_pause_enabled !== false,
              scheduled_pause_message: result.data.scheduled_pause_message || '',
              business_hours_automation_enabled: (businessHoursAutomationRaw === true || businessHoursAutomationRaw === 1 || businessHoursAutomationRaw === '1')
            };
            
            this.updateUI();
            
            // Carregar estatísticas se estiver ativado
            if (this.config.enabled) {
              this.loadStats();
            }
          }
          
          console.log('📊 Configuração de engajamento carregada:', this.config);
          
        } catch (error) {
          console.error('Erro ao carregar configuração de engajamento:', error);
        } finally {
          this.revealLoadedContent();
        }
      },
      
      // Atualizar a UI com base na configuração
      updateUI() {
        // Toggle principal
        const toggleMain = el('toggleEngagement');
        if (toggleMain) {
          toggleMain.dataset.enabled = this.config.enabled.toString();
          this.updateToggleVisual(toggleMain, this.config.enabled);
        }
        
        // Container de configurações
        const configContainer = el('engagementConfigContainer');
        if (configContainer) {
          if (this.config.enabled) {
            configContainer.classList.remove('hidden');
          } else {
            configContainer.classList.add('hidden');
          }
        }
        
        // Toggle Cenário 1
        const toggle1 = el('toggleScenario1');
        if (toggle1) {
          toggle1.dataset.enabled = this.config.scenario1_enabled.toString();
          this.updateSmallToggleVisual(toggle1, this.config.scenario1_enabled);
        }
        
        // Toggle Cenário 2
        const toggle2 = el('toggleScenario2');
        if (toggle2) {
          toggle2.dataset.enabled = this.config.scenario2_enabled.toString();
          this.updateSmallToggleVisual(toggle2, this.config.scenario2_enabled);
        }
        
        // Toggle Fora do Expediente (agora usa toggle grande, seção independente)
        const toggleOutOfHours = el('toggleOutOfHours');
        if (toggleOutOfHours) {
          toggleOutOfHours.dataset.enabled = this.config.out_of_hours_enabled.toString();
          this.updateToggleVisual(toggleOutOfHours, this.config.out_of_hours_enabled);
        }
        
        // Container de configurações Fora do Expediente
        const outOfHoursContainer = el('outOfHoursConfigContainer');
        if (outOfHoursContainer) {
          if (this.config.out_of_hours_enabled) {
            outOfHoursContainer.classList.remove('hidden');
          } else {
            outOfHoursContainer.classList.add('hidden');
          }
        }
        
        // Inputs
        const delay1Input = el('scenario1Delay');
        if (delay1Input) {
          delay1Input.value = this.config.scenario1_delay;
        }
        
        const days2Input = el('scenario2Days');
        if (days2Input) {
          days2Input.value = this.config.scenario2_days;
        }
        
        // Input de mensagem fora do expediente
        const outOfHoursMessage = el('outOfHoursMessage');
        if (outOfHoursMessage) {
          outOfHoursMessage.value = this.config.out_of_hours_message || '';
        }
        
        // Toggle Pausa Programada
        const toggleScheduledPause = el('toggleScheduledPause');
        if (toggleScheduledPause) {
          toggleScheduledPause.dataset.enabled = this.config.scheduled_pause_enabled.toString();
          this.updateToggleVisual(toggleScheduledPause, this.config.scheduled_pause_enabled);
        }
        
        // Container de configurações Pausa Programada
        const scheduledPauseContainer = el('scheduledPauseConfigContainer');
        if (scheduledPauseContainer) {
          if (this.config.scheduled_pause_enabled) {
            scheduledPauseContainer.classList.remove('hidden');
          } else {
            scheduledPauseContainer.classList.add('hidden');
          }
        }
        
        // Input de mensagem pausa programada
        const scheduledPauseMessage = el('scheduledPauseMessage');
        if (scheduledPauseMessage) {
          scheduledPauseMessage.value = this.config.scheduled_pause_message || '';
        }

        // Toggle Automacao por Expediente
        const toggleBusinessHoursAutomation = el('toggleBusinessHoursAutomation');
        if (toggleBusinessHoursAutomation) {
          toggleBusinessHoursAutomation.dataset.enabled = this.config.business_hours_automation_enabled.toString();
          this.updateToggleVisual(toggleBusinessHoursAutomation, this.config.business_hours_automation_enabled);
        }

        // Container de configuracoes da automacao por expediente
        const businessHoursContainer = el('businessHoursAutomationConfigContainer');
        if (businessHoursContainer) {
          if (this.config.business_hours_automation_enabled === true) {
            businessHoursContainer.classList.remove('hidden');
          } else {
            businessHoursContainer.classList.add('hidden');
          }
        }

        businessHoursAutomationEnabled = this.config.business_hours_automation_enabled === true;
        updateBusinessHoursAutomationStateText();

        setBusinessHoursManagedLock(businessHoursAutomationEnabled);

        if (businessHoursAutomationEnabled) {
          const rejectToggle = el('toggleRejectCalls');
          const alwaysOnlineToggle = el('toggleAlwaysOnline');
          const currentSettings = {
            rejectCall: rejectToggle ? (rejectToggle.dataset.enabled === 'true') : undefined,
            alwaysOnline: alwaysOnlineToggle ? (alwaysOnlineToggle.dataset.enabled === 'true') : undefined
          };
          enforceBusinessHoursSettings(currentSettings, { showToast: false });
        } else {
          clearBusinessHoursStatusIndicators();
        }
        
        // Configurações de cenário (ocultar se desativado)
        const scenario1Config = el('scenario1Config');
        if (scenario1Config) {
          scenario1Config.style.opacity = this.config.scenario1_enabled ? '1' : '0.5';
          scenario1Config.style.pointerEvents = this.config.scenario1_enabled ? 'auto' : 'none';
        }
        
        const scenario2Config = el('scenario2Config');
        if (scenario2Config) {
          scenario2Config.style.opacity = this.config.scenario2_enabled ? '1' : '0.5';
          scenario2Config.style.pointerEvents = this.config.scenario2_enabled ? 'auto' : 'none';
        }
      },
      
      // Atualizar visual do toggle grande
      updateToggleVisual(toggle, enabled) {
        const span = toggle.querySelector('span');
        if (enabled) {
          toggle.classList.remove('bg-slate-200', 'hover:bg-slate-300');
          toggle.classList.add('bg-emerald-500', 'hover:bg-emerald-600');
          if (span) span.classList.add('translate-x-5');
        } else {
          toggle.classList.remove('bg-emerald-500', 'hover:bg-emerald-600');
          toggle.classList.add('bg-slate-200', 'hover:bg-slate-300');
          if (span) span.classList.remove('translate-x-5');
        }
      },
      
      // Atualizar visual do toggle pequeno
      updateSmallToggleVisual(toggle, enabled) {
        const span = toggle.querySelector('span');
        if (enabled) {
          toggle.classList.remove('bg-slate-200');
          toggle.classList.add('bg-emerald-500');
          if (span) span.classList.add('translate-x-4');
          if (span) span.classList.remove('translate-x-0');
        } else {
          toggle.classList.remove('bg-emerald-500');
          toggle.classList.add('bg-slate-200');
          if (span) span.classList.remove('translate-x-4');
          if (span) span.classList.add('translate-x-0');
        }
      },
      
      // Handler do toggle principal
      async handleMainToggle() {
        const toggle = el('toggleEngagement');
        if (!toggle || toggle.dataset.loading === 'true') return;
        
        const newEnabled = toggle.dataset.enabled !== 'true';
        
        // Mostrar loading
        toggle.dataset.loading = 'true';
        const statusEl = el('statusEngagement');
        if (statusEl) {
          statusEl.textContent = 'Salvando...';
          statusEl.classList.remove('hidden');
        }
        
        try {
          // Salvar configuração
          this.config.enabled = newEnabled;
          await this.saveConfig();
          
          // Atualizar UI
          this.updateUI();
          
          toast(newEnabled ? 'Engajamento ativado com sucesso!' : 'Engajamento desativado', newEnabled ? 'success' : 'info');
          
          // Carregar estatísticas se ativou
          if (newEnabled) {
            this.loadStats();
          }
          
        } catch (error) {
          console.error('Erro ao toggle engajamento:', error);
          toast('Erro ao salvar configuração: ' + error.message, 'error');
          this.config.enabled = !newEnabled; // Reverter
        } finally {
          toggle.dataset.loading = 'false';
          if (statusEl) statusEl.classList.add('hidden');
        }
      },
      
      // Handler dos toggles de cenário
      async handleScenarioToggle(scenario) {
        const toggleId = scenario === 1 ? 'toggleScenario1' : 'toggleScenario2';
        const toggle = el(toggleId);
        if (!toggle) return;
        
        const newEnabled = toggle.dataset.enabled !== 'true';
        
        if (scenario === 1) {
          this.config.scenario1_enabled = newEnabled;
        } else {
          this.config.scenario2_enabled = newEnabled;
        }
        
        try {
          await this.saveConfig();
          this.updateUI();
          toast(`Cenário ${scenario} ${newEnabled ? 'ativado' : 'desativado'}`, 'info');
        } catch (error) {
          console.error('Erro ao toggle cenário:', error);
          toast('Erro ao salvar: ' + error.message, 'error');
          // Reverter
          if (scenario === 1) {
            this.config.scenario1_enabled = !newEnabled;
          } else {
            this.config.scenario2_enabled = !newEnabled;
          }
        }
      },
      
      // Handler do toggle de fora do expediente
      async handleOutOfHoursToggle() {
        const toggle = el('toggleOutOfHours');
        if (!toggle) return;
        
        const newEnabled = toggle.dataset.enabled !== 'true';
        this.config.out_of_hours_enabled = newEnabled;
        
        try {
          await this.saveConfig();
          this.updateUI();
          toast(`Resposta fora do expediente ${newEnabled ? 'ativada' : 'desativada'}`, 'info');
        } catch (error) {
          console.error('Erro ao toggle fora do expediente:', error);
          toast('Erro ao salvar: ' + error.message, 'error');
          this.config.out_of_hours_enabled = !newEnabled;
        }
      },
      
      // Handler para salvar mensagem fora do expediente
      async handleSaveOutOfHoursMessage() {
        const textarea = el('outOfHoursMessage');
        if (!textarea) return;
        
        this.config.out_of_hours_message = textarea.value.trim();
        
        try {
          await this.saveConfig();
          toast('Mensagem fora do expediente atualizada!', 'success');
        } catch (error) {
          console.error('Erro ao salvar mensagem:', error);
          toast('Erro ao salvar: ' + error.message, 'error');
        }
      },
      
      // Handler para toggle pausa programada
      async handleScheduledPauseToggle() {
        const toggle = el('toggleScheduledPause');
        if (!toggle) return;
        
        const newEnabled = toggle.dataset.enabled !== 'true';
        this.config.scheduled_pause_enabled = newEnabled;
        
        try {
          await this.saveConfig();
          this.updateUI();
          toast(`Resposta em pausa programada ${newEnabled ? 'ativada' : 'desativada'}`, 'info');
        } catch (error) {
          console.error('Erro ao toggle pausa programada:', error);
          toast('Erro ao salvar: ' + error.message, 'error');
          this.config.scheduled_pause_enabled = !newEnabled;
        }
      },

      // Handler para toggle da automacao por expediente
      async handleBusinessHoursAutomationToggle() {
        const toggle = el('toggleBusinessHoursAutomation');
        if (!toggle) return;

        const newEnabled = toggle.dataset.enabled !== 'true';
        this.config.business_hours_automation_enabled = newEnabled;

        try {
          await this.saveConfig();
          this.updateUI();

          if (newEnabled) {
            const rejectToggle = el('toggleRejectCalls');
            const alwaysOnlineToggle = el('toggleAlwaysOnline');
            const currentSettings = {
              rejectCall: rejectToggle ? (rejectToggle.dataset.enabled === 'true') : undefined,
              alwaysOnline: alwaysOnlineToggle ? (alwaysOnlineToggle.dataset.enabled === 'true') : undefined
            };
            await enforceBusinessHoursSettings(currentSettings, { showToast: true });
          } else {
            clearBusinessHoursStatusIndicators();
          }

          toast(`Automacao por expediente ${newEnabled ? 'ativada' : 'desativada'}`, 'info');
        } catch (error) {
          console.error('Erro ao toggle automacao por expediente:', error);
          toast('Erro ao salvar: ' + error.message, 'error');
          this.config.business_hours_automation_enabled = !newEnabled;
          this.updateUI();
        }
      },
      
      // Handler para salvar mensagem pausa programada
      async handleSaveScheduledPauseMessage() {
        const textarea = el('scheduledPauseMessage');
        if (!textarea) return;
        
        this.config.scheduled_pause_message = textarea.value.trim();
        
        try {
          await this.saveConfig();
          toast('Mensagem de pausa programada atualizada!', 'success');
        } catch (error) {
          console.error('Erro ao salvar mensagem:', error);
          toast('Erro ao salvar: ' + error.message, 'error');
        }
      },
      
      // Handler de mudança de configuração
      async handleConfigChange() {
        const delay1Input = el('scenario1Delay');
        const days2Input = el('scenario2Days');
        
        if (delay1Input) {
          let val = parseInt(delay1Input.value) || 10;
          val = Math.max(5, Math.min(60, val));
          delay1Input.value = val;
          this.config.scenario1_delay = val;
        }
        
        if (days2Input) {
          let val = parseInt(days2Input.value) || 15;
          val = Math.max(7, Math.min(90, val));
          days2Input.value = val;
          this.config.scenario2_days = val;
        }
        
        try {
          await this.saveConfig();
          toast('Configuração atualizada', 'success');
        } catch (error) {
          console.error('Erro ao salvar configuração:', error);
          toast('Erro ao salvar: ' + error.message, 'error');
        }
      },
      
      // Salvar configuração no servidor
      async saveConfig() {
        const response = await fetch(baseUrl + instanceName + '/customer-engagement', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            enabled: this.config.enabled,
            scenario1_enabled: this.config.scenario1_enabled,
            scenario1_delay: this.config.scenario1_delay,
            scenario2_enabled: this.config.scenario2_enabled,
            scenario2_days: this.config.scenario2_days,
            out_of_hours_enabled: this.config.out_of_hours_enabled,
            out_of_hours_message: this.config.out_of_hours_message || '',
            scheduled_pause_enabled: this.config.scheduled_pause_enabled,
            scheduled_pause_message: this.config.scheduled_pause_message || '',
            business_hours_automation_enabled: this.config.business_hours_automation_enabled
          })
        });
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
          // Verificar conflito
          if (result.conflict) {
            const confirm = window.confirm(
              `${result.error}\n\nDeseja desativar a instância "${result.active_instance}" e ativar nesta?`
            );
            
            if (confirm) {
              // Reenviar com force_switch
              return this.saveConfigWithForce();
            } else {
              throw new Error('Operação cancelada');
            }
          }
          throw new Error(result.error || 'Erro ao salvar');
        }
        
        return result;
      },
      
      // Salvar com force switch
      async saveConfigWithForce() {
        const response = await fetch(baseUrl + instanceName + '/customer-engagement', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            enabled: this.config.enabled,
            scenario1_enabled: this.config.scenario1_enabled,
            scenario1_delay: this.config.scenario1_delay,
            scenario2_enabled: this.config.scenario2_enabled,
            scenario2_days: this.config.scenario2_days,
            out_of_hours_enabled: this.config.out_of_hours_enabled,
            out_of_hours_message: this.config.out_of_hours_message || '',
            scheduled_pause_enabled: this.config.scheduled_pause_enabled,
            scheduled_pause_message: this.config.scheduled_pause_message || '',
            business_hours_automation_enabled: this.config.business_hours_automation_enabled,
            force_switch: true
          })
        });
        
        const result = await response.json();
        
        if (!result.success) {
          throw new Error(result.error || 'Erro ao salvar');
        }
        
        return result;
      },
      
      // Carregar estatísticas
      async loadStats() {
        try {
          const response = await fetch(baseUrl + instanceName + '/engagement-stats', {
            method: 'GET',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          });
          
          if (!response.ok) return;
          
          const result = await response.json();
          
          if (result.success && result.data) {
            const statsContainer = el('engagementStats');
            if (statsContainer) {
              statsContainer.classList.remove('hidden');
              
              const totalSent = el('statsTotalSent');
              const scenario1 = el('statsScenario1');
              const scenario2 = el('statsScenario2');
              
              if (totalSent) totalSent.textContent = result.data.messages?.total_sent || 0;
              if (scenario1) scenario1.textContent = result.data.messages?.scenario1_sent || 0;
              if (scenario2) scenario2.textContent = result.data.messages?.scenario2_sent || 0;
            }
          }
        } catch (error) {
          console.error('Erro ao carregar estatísticas de engajamento:', error);
        }
      }
    };
    
    // Inicializar Customer Engagement após carregamento da página
    setTimeout(() => {
      CustomerEngagement.init();
    }, 1000);
    
    // Auto refresh removido temporariamente para evitar conflitos
  </script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layout.php'; ?>
