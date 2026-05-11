<?php
// admin/settings/index.php — Configurações (com toolbar fixa)

$title = 'Configurações - ' . ($company['name'] ?? '');
$slug  = rawurlencode((string)($company['slug'] ?? ''));
$days  = [1 => 'Segunda',2 => 'Terça',3 => 'Quarta',4 => 'Quinta',5 => 'Sexta',6 => 'Sábado',7 => 'Domingo'];

// helper de escape (se ainda não existir)

// Normalização de cores (se ainda não existir)
if (!function_exists('settings_color_value')) {
    function settings_color_value($value, $default)
    {
        $value = trim((string)$value);

        if ($value === '') {
            return strtoupper($default);
        }

        if ($value[0] !== '#') {
            $value = '#'.$value;
        }

        if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
            return strtoupper($default);
        }

        if (strlen($value) === 4) {
            $value = '#'.$value[1].$value[1].$value[2].$value[2].$value[3].$value[3];
        }

        return strtoupper($value);
    }
}

$colorDefaults = [
  'menu_header_text_color'       => '#FFFFFF',
  'menu_header_button_color'     => '#FACC15',
  'menu_header_bg_color'         => '#5B21B6',
  'menu_logo_border_color'       => '#7C3AED',
  'menu_group_title_bg_color'    => '#FACC15',
  'menu_group_title_text_color'  => '#000000',
  'menu_welcome_bg_color'        => '#6B21A8',
  'menu_welcome_text_color'      => '#FFFFFF',
];

$colorValues = [];

foreach ($colorDefaults as $key => $default) {
    $colorValues[$key] = settings_color_value($company[$key] ?? '', $default);
}

// Horários vindos do controller (pode estar vazio)
$hours = $hours ?? [];

// Verificar status do horário de funcionamento
require_once __DIR__ . '/../../../helpers/business_hours_helper.php';
$bhStatus = check_business_hours_status($hours);

ob_start(); ?>

<div class="mx-auto max-w-6xl p-4">

<?php
// Configuração do header padronizado
$pageTitle = 'Configurações';
$pageDescription = '';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>';
$breadcrumbs = [
    ['label' => 'Configurações']
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
  <div class="grid grid-cols-5 gap-1.5">
    <button type="button" 
            class="section-btn active flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors admin-gradient-bg text-white"
            data-section="dados"
            onclick="switchSection('dados')">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
      Dados
    </button>
    <button type="button" 
            class="section-btn flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors bg-slate-50 text-slate-700 hover:bg-slate-100"
            data-section="api"
            onclick="switchSection('api')">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M3 12h18M12 3v18"/>
      </svg>
      API
    </button>
    <button type="button" 
            class="section-btn flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors bg-slate-50 text-slate-700 hover:bg-slate-100"
            data-section="aparencia"
            onclick="switchSection('aparencia')">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM7 21h10a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
      </svg>
      Cores
    </button>
    <button type="button" 
            class="section-btn flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors bg-slate-50 text-slate-700 hover:bg-slate-100"
            data-section="imagens"
            onclick="switchSection('imagens')">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
      Imagens
    </button>
    <button type="button" 
            class="section-btn flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors bg-slate-50 text-slate-700 hover:bg-slate-100"
            data-section="horarios"
            onclick="switchSection('horarios')">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <circle cx="12" cy="12" r="10"/>
        <path d="M12 6v6l4 2"/>
      </svg>
      Horários
    </button>
  </div>
</div>

<form id="settingsForm" method="post" enctype="multipart/form-data"
      action="<?= e(base_url('admin/' . $slug . '/settings')) ?>"
      class="space-y-6">

  <?php if (function_exists('csrf_field')): ?>
    <?= csrf_field() ?>
  <?php elseif (function_exists('csrf_token')): ?>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <?php endif; ?>

  <!-- SEÇÃO 1: DADOS PRINCIPAIS -->
  <div id="section-dados" class="section-content">
  <fieldset class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 7h12M6 12h10M6 17h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      Informações do comércio
    </legend>

    <div class="grid gap-3 md:grid-cols-2">
      <label class="grid gap-1">
        <span class="text-sm text-slate-700">Nome do comércio</span>
        <input name="name" value="<?= e($company['name'] ?? '') ?>" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
      </label>

      <label class="grid gap-1">
        <span class="text-sm text-slate-700">WhatsApp</span>
        <input id="whats" name="whatsapp" value="<?= e($company['whatsapp'] ?? '') ?>"
               class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400"
               inputmode="numeric" placeholder="(51) 92001-7687">
      </label>
    </div>

    <label class="mt-3 grid gap-1">
      <span class="text-sm text-slate-700">Endereço (opcional)</span>
      <input name="address" value="<?= e($company['address'] ?? '') ?>" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
    </label>

    <div class="mt-3 grid gap-3 md:grid-cols-3">
      <label class="grid gap-1">
        <span class="text-sm text-slate-700">Pedido mínimo (R$)</span>
        <a href="/admin/<?= rawurlencode($company['slug']) ?>/guide/company-settings#data" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a>
        <input name="min_order" type="number" step="0.01" value="<?= e($company['min_order'] ?? '') ?>" class="rounded-xl border border-slate-300 bg-white px-3 py-2 focus:ring-2 focus:ring-indigo-400">
      </label>

      <label class="grid gap-1">
        <span class="text-sm text-slate-700">Tempo médio (de) – min</span>
        <a href="/admin/<?= rawurlencode($company['slug']) ?>/guide/company-settings#data" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a>
        <input name="avg_delivery_min_from" type="number" min="1" step="1"
               value="<?= e($company['avg_delivery_min_from'] ?? '') ?>" class="rounded-xl border border-slate-300 bg-white px-3 py-2 focus:ring-2 focus:ring-indigo-400" placeholder="40">
      </label>

      <label class="grid gap-1">
        <span class="text-sm text-slate-700">Tempo médio (até) – min</span>
        <input name="avg_delivery_min_to" type="number" min="1" step="1"
               value="<?= e($company['avg_delivery_min_to'] ?? '') ?>" class="rounded-xl border border-slate-300 bg-white px-3 py-2 focus:ring-2 focus:ring-indigo-400" placeholder="60">
      </label>
    </label>

  </fieldset>

  <!-- TEXTOS POR DIA DA SEMANA -->
  <fieldset class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M3 12h18M12 3v18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      Textos por Dia da Semana
    </legend>
    <p class="mb-4 text-sm text-slate-600">Configure um texto de destaque diferente para cada dia. O sistema mudará automaticamente conforme o dia.</p>

    <div class="grid gap-3">
      <!-- CARD: Texto único para todos os dias -->
      <div class="highlight-day-card rounded-2xl border border-blue-300 bg-blue-50/30 overflow-hidden hover:shadow-sm transition-shadow" data-day="all">
        <div class="flex items-center justify-between p-4 cursor-pointer single-text-header">
          <div class="flex items-center gap-3 flex-1">
            <label class="inline-flex cursor-pointer items-center single-text-toggle-label">
              <input type="checkbox" id="use-single-text" class="highlight-toggle-day hidden" data-day="all">
              <span class="highlight-toggle-track w-10 h-6 bg-slate-300 rounded-full relative transition-colors">
                <span class="highlight-toggle-thumb absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform transition-transform"></span>
              </span>
            </label>
            <div class="flex-1">
              <div class="font-semibold text-blue-900 flex items-center gap-2">

                Aplicar texto único em todos os dias
              </div>
              <div class="text-sm text-slate-500 highlight-text-summary">
                Atalho para usar o mesmo texto em todos os dias
              </div>
            </div>
          </div>
          <svg class="h-5 w-5 text-slate-400 highlight-chevron transition-transform" viewBox="0 0 24 24" fill="none">
            <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>

        <!-- Detalhes expandíveis -->
        <div class="highlight-day-details hidden border-t border-blue-200 bg-white/50 p-4">
          <label class="grid gap-1.5">
            <span class="text-xs font-medium text-slate-700">Texto único para aplicar em todos os dias</span>
            <textarea 
              id="single-text-input"
              rows="3"
              class="highlight-text-input rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-blue-400 resize-none" 
              placeholder="Ex.: Peça online e retire sem fila! 🍔"></textarea>
            <small class="text-xs text-slate-500">Digite o texto e clique no botão abaixo para aplicar em todos os 7 dias</small>
          </label>
          <button type="button" id="apply-single-text" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors w-full justify-center">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
              <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Aplicar em todos os dias da semana
          </button>
        </div>
      </div>

      <?php 
      $dayKeys = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
      $dayLabels = $dayLabels ?? [
          'monday'    => 'Segunda-feira',
          'tuesday'   => 'Terça-feira',
          'wednesday' => 'Quarta-feira',
          'thursday'  => 'Quinta-feira',
          'friday'    => 'Sexta-feira',
          'saturday'  => 'Sábado',
          'sunday'    => 'Domingo'
      ];
      $dailyHighlightTexts = $dailyHighlightTexts ?? [];
      $enabledDays = $enabledDays ?? [];

      foreach ($dayKeys as $day) {
        $label = $dayLabels[$day] ?? ucfirst($day);
        $value = $dailyHighlightTexts[$day] ?? '';
        $fieldName = "highlight_text_" . $day;
        $isEnabled = in_array($day, $enabledDays, true);
        $hasText = !empty($value);
      ?>
        <!-- Card do dia (resumo) -->
        <div class="highlight-day-card rounded-2xl border border-slate-200 bg-white overflow-hidden hover:shadow-sm transition-shadow" data-day="<?= $day ?>">
          <div class="flex items-center justify-between p-4 cursor-pointer day-header">
            <div class="flex items-center gap-3 flex-1">
              <label class="inline-flex cursor-pointer items-center" onclick="event.stopPropagation()">
                <input type="checkbox" name="highlight_enabled[<?= $day ?>]" <?= $isEnabled ? 'checked' : '' ?> 
                       class="highlight-toggle-day hidden"
                       data-day="<?= $day ?>">
                <span class="highlight-toggle-track w-10 h-6 <?= $isEnabled ? 'admin-primary-bg' : 'bg-slate-300' ?> rounded-full relative transition-colors">
                  <span class="highlight-toggle-thumb absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform transition-transform" style="transform: translateX(<?= $isEnabled ? '16px' : '0px' ?>)"></span>
                </span>
              </label>
              <div class="flex-1">
                <div class="font-semibold text-slate-900"><?= e($label) ?></div>
                <div class="text-sm <?= $hasText ? 'text-slate-600' : 'text-slate-400' ?> highlight-text-summary">
                  <?= $hasText ? substr(e($value), 0, 50) . (strlen($value) > 50 ? '...' : '') : 'Sem texto' ?>
                </div>
              </div>
            </div>
            <svg class="h-5 w-5 text-slate-400 highlight-chevron transition-transform" viewBox="0 0 24 24" fill="none">
              <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>

          <!-- Detalhes expandíveis -->
          <div class="highlight-day-details hidden border-t border-slate-200 bg-slate-50/50 p-4">
            <label class="grid gap-1.5">
              <span class="text-xs font-medium text-slate-700">Texto de destaque</span>
              <textarea 
                name="<?= $fieldName ?>" 
                rows="3"
                class="highlight-text-input rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-indigo-400 resize-none" 
                placeholder="Digite o texto de destaque para <?= e($label) ?>..."
                data-day="<?= $day ?>"><?= e($value) ?></textarea>
              <small class="text-xs text-slate-500">Suporta emojis e quebras de linha</small>
            </label>
          </div>
        </div>
      <?php } ?>
    </div>
  </fieldset>
  </div>

  <!-- SEÇÃO 2: EVOLUTION API -->
  <div id="section-api" class="section-content hidden">
  <fieldset class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M3 12h18M12 3v18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      Evolution API
    </legend>

    <p class="mb-3 text-sm text-slate-600">Configurações para conectar com a Evolution API (veja a documentação para SERVER_URL e API KEY).</p>

    <label class="grid gap-1">
      <span class="text-sm text-slate-700">SERVER_URL</span>
      <input name="evolution_server_url" value="<?= e($company['evolution_server_url'] ?? '') ?>" class="rounded-xl border border-slate-300 bg-white px-3 py-2">
    </label>

    <label class="mt-3 grid gap-1">
      <span class="text-sm text-slate-700">AUTHENTICATION_API_KEY</span>
      <div class="relative">
        <input type="password" 
               id="evolution-api-key-input"
               name="evolution_api_key" 
               value="<?= e($company['evolution_api_key'] ?? '') ?>" 
               class="rounded-xl border border-slate-300 bg-white px-3 py-2 pr-10 w-full">
        <button type="button" 
                onclick="togglePasswordVisibility('evolution-api-key-input', this)"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
          <svg class="h-5 w-5 eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
          <svg class="h-5 w-5 eye-closed hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
            <line x1="1" y1="1" x2="23" y2="23"/>
          </svg>
        </button>
      </div>
      <small class="text-xs text-slate-500">Chave usada no header 'Authentication-Api-Key'.</small>
    </label>

    <label class="mt-4 grid gap-1">
      <span class="text-sm font-medium">Google Analytics 4 — Measurement ID</span>
      <input name="ga_measurement_id" value="<?= e($company['ga_measurement_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
      <small class="text-xs text-slate-500">ID de medição do GA4 (ex: G-AB12CD34EF). Deixe vazio para desativar.</small>
    </label>
  </fieldset>
  </div>

  <!-- SEÇÃO 4: APARÊNCIA DO CARDÁPIO -->
  <div id="section-aparencia" class="section-content hidden">
  <fieldset class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M5 7h14M5 12h10M5 17h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      Aparência do cardápio
    </legend>
    <p class="mb-4 text-sm text-slate-600">Personalize as cores exibidas no cardápio on-line.</p>

    <div class="grid gap-4 md:grid-cols-2">
      <?php
      $labels = [
        'menu_header_text_color'      => 'Texto do cabeçalho',
        'menu_header_button_color'    => 'Botões/ícones do cabeçalho',
        'menu_header_bg_color'        => 'Fundo do cabeçalho',
        'menu_logo_border_color'      => 'Borda da logo',
        'menu_group_title_bg_color'   => 'Fundo do título dos grupos',
        'menu_group_title_text_color' => 'Texto do título dos grupos',
        'menu_welcome_bg_color'       => 'Fundo da mensagem de boas-vindas',
        'menu_welcome_text_color'     => 'Texto da mensagem de boas-vindas',
      ];

foreach ($labels as $key => $lab): ?>
        <div class="rounded-xl border border-slate-200 bg-white p-4 hover:border-indigo-300 hover:shadow-md transition-all">
          <label class="grid gap-2">
            <span class="text-sm font-medium text-slate-900"><?= e($lab) ?></span>
            <div class="flex items-center gap-3">
              <!-- Preview -->
              <div class="h-12 w-12 rounded-xl border-2 border-slate-300 shadow-inner flex-shrink-0"
                   style="background-color: <?= e($colorValues[$key]) ?>">
              </div>
              
              <!-- Color Picker (escondido visualmente, mas clicável sobre o preview) -->
              <div class="relative -ml-[60px] h-12 w-12 flex-shrink-0">
                <input type="color" 
                       name="<?= e($key) ?>" 
                       value="<?= e($colorValues[$key]) ?>" 
                       class="color-picker h-12 w-12 cursor-pointer opacity-0 absolute inset-0"
                       id="picker-<?= e($key) ?>">
              </div>
              
              <!-- Text Input -->
              <input type="text" 
                     value="<?= e($colorValues[$key]) ?>" 
                     data-color-for="<?= e($key) ?>"
                     maxlength="7"
                     class="color-text-input flex-1 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-center text-sm font-mono font-semibold uppercase tracking-wider text-slate-800 focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400"
                     placeholder="#000000">
            </div>
          </label>
        </div>
      <?php endforeach; ?>
    </div>
    
    <!-- Reset Button -->
    <div class="mt-6 flex justify-end">
      <button type="button" 
              onclick="resetColors()"
              class="inline-flex items-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-medium text-amber-700 hover:bg-amber-100 transition-colors">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8M21 3v5h-5M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16M3 21v-5h5"/>
        </svg>
        Restaurar cores padrão
      </button>
    </div>
  </fieldset>
  </div>

  <!-- SEÇÃO 5: LOGO & BANNER -->
  <div id="section-imagens" class="section-content hidden">
  <fieldset class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Identidade visual
    </legend>

    <div class="grid gap-4 md:grid-cols-2">
      <!-- Logo -->
      <div class="grid items-start gap-3 md:grid-cols-[1fr_auto]">
        <div class="grid gap-2">
          <label for="logo-input" class="text-sm text-slate-700">Logo (quadrado) – jpg/png/webp</label>
          <label class="inline-flex w-fit cursor-pointer items-center gap-2 rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
            <input type="file" name="logo" id="logo-input" accept=".jpg,.jpeg,.png,.webp" class="hidden">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            Selecionar arquivo
          </label>
          <small class="text-xs text-slate-500">Recomendado: 512×512px. Máx. 5 MB.</small>
        </div>

        <div class="flex flex-col items-center gap-2">
          <?php if (!empty($company['logo'])): ?>
            <span class="text-xs text-slate-500">Pré-visualização</span>
            <img id="logo-preview-img"
                 src="<?= e(base_url($company['logo'])) ?>"
                 class="h-20 w-20 rounded-xl border border-slate-200 object-cover shadow-sm"
                 alt="Pré-visualização da logo"
                 onerror="this.style.display='none'; document.getElementById('logo-preview-placeholder').style.display='flex';">
          <?php else: ?>
            <img id="logo-preview-img" class="h-20 w-20 rounded-xl border border-slate-200 object-cover shadow-sm hidden" alt="Pré-visualização da logo">
          <?php endif; ?>
          <div id="logo-preview-placeholder" class="<?= !empty($company['logo']) ? 'hidden' : 'flex' ?> h-20 w-20 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 shadow-sm">
            <svg class="h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none">
              <path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
        </div>
      </div>

      <!-- Banner -->
      <div class="grid items-start gap-3 md:grid-cols-[1fr_auto]">
        <div class="grid gap-2">
          <label for="banner-input" class="text-sm text-slate-700">Banner (largura) – jpg/png/webp</label>
          <label class="inline-flex w-fit cursor-pointer items-center gap-2 rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
            <input type="file" name="banner" id="banner-input" accept=".jpg,.jpeg,.png,.webp" class="hidden">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            Selecionar arquivo
          </label>
          <small class="text-xs text-slate-500">Recomendado: 1600×400px. Máx. 5 MB.</small>
        </div>

        <div class="flex flex-col items-center gap-2">
          <?php if (!empty($company['banner'])): ?>
            <span class="text-xs text-slate-500">Pré-visualização</span>
            <img id="banner-preview-img"
                 src="<?= e(base_url($company['banner'])) ?>"
                 class="h-20 w-32 rounded-xl border border-slate-200 object-cover shadow-sm"
                 alt="Pré-visualização do banner"
                 onerror="this.style.display='none'; document.getElementById('banner-preview-placeholder').style.display='flex';">
          <?php else: ?>
            <img id="banner-preview-img" class="h-20 w-32 rounded-xl border border-slate-200 object-cover shadow-sm hidden" alt="Pré-visualização do banner">
          <?php endif; ?>
          <div id="banner-preview-placeholder" class="<?= !empty($company['banner']) ? 'hidden' : 'flex' ?> h-20 w-32 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 shadow-sm">
            <svg class="h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none">
              <path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
        </div>
      </div>
    </div>
  </fieldset>
  </div>

  <!-- SEÇÃO 6: HORÁRIOS -->
  <div id="section-horarios" class="section-content hidden">
  <fieldset class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 6h12v12H6z M12 8v5l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Horários de funcionamento
    </legend>

    <!-- Status atual do horário -->
    <div class="mb-4 flex items-center gap-3 rounded-xl border px-4 py-3 <?= $bhStatus['is_open'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' ?>">
      <span class="relative flex h-3 w-3">
        <span class="absolute inline-flex h-full w-full rounded-full <?= $bhStatus['is_open'] ? 'bg-green-400' : 'bg-red-400' ?> opacity-75 animate-ping"></span>
        <span class="relative inline-flex h-3 w-3 rounded-full <?= $bhStatus['is_open'] ? 'bg-green-500' : 'bg-red-500' ?>"></span>
      </span>
      <div class="flex-1">
        <span class="text-sm font-semibold <?= $bhStatus['is_open'] ? 'text-green-800' : 'text-red-800' ?>">
          <?= $bhStatus['is_open'] ? 'Aberto agora' : 'Fechado agora' ?>
        </span>
        <span class="ml-2 text-sm <?= $bhStatus['is_open'] ? 'text-green-600' : 'text-red-600' ?>">
          — Agora: <?= e($bhStatus['current_time']) ?> · Hoje: <?= e($bhStatus['today_hours']) ?>
        </span>
      </div>
      <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium <?= $bhStatus['is_open'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
        <?= e($bhStatus['label']) ?>
      </span>
    </div>

    <p class="mb-4 text-sm text-slate-600">Ative os dias e defina até dois intervalos por dia.</p>

    <div class="grid gap-3">
      <?php foreach ($days as $d => $label):
          $row    = $hours[$d] ?? ['is_open' => 0,'open1' => null,'close1' => null,'open2' => null,'close2' => null];
          $isOpen = !empty($row['is_open']);
          $hasOpen1 = !empty($row['open1']) && !empty($row['close1']);
          $hasOpen2 = !empty($row['open2']) && !empty($row['close2']);
          $timeDisplay = '';
          if ($hasOpen1) {
              $timeDisplay = substr((string)$row['open1'], 0, 5) . ' - ' . substr((string)$row['close1'], 0, 5);
          }
          if ($hasOpen2) {
              $timeDisplay .= ' / ' . substr((string)$row['open2'], 0, 5) . ' - ' . substr((string)$row['close2'], 0, 5);
          }
          if (!$hasOpen1 && !$hasOpen2) {
              $timeDisplay = 'Horário não definido';
          }
          ?>
        <!-- Card do dia (resumo) -->
        <div class="day-card rounded-2xl border border-slate-200 bg-white overflow-hidden hover:shadow-sm transition-shadow" data-day="<?= $d ?>">
          <div class="flex items-center justify-between p-4 cursor-pointer day-header">
            <div class="flex items-center gap-3 flex-1">
              <label class="inline-flex cursor-pointer items-center" onclick="event.stopPropagation()">
                <input type="checkbox" name="is_open[<?= $d ?>]" <?= $isOpen ? 'checked' : '' ?> 
                       class="toggle-day hidden"
                       data-day="<?= $d ?>">
                <span class="day-toggle-track w-10 h-6 <?= $isOpen ? 'admin-primary-bg' : 'bg-slate-300' ?> rounded-full relative transition-colors">
                  <span class="day-toggle-thumb absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform transition-transform" style="transform: translateX(<?= $isOpen ? '16px' : '0px' ?>)"></span>
                </span>
              </label>
              <div class="flex-1">
                <div class="font-semibold text-slate-900"><?= e($label) ?></div>
                <div class="text-sm <?= $isOpen ? 'text-slate-600' : 'text-slate-400' ?> time-summary">
                  <?= $isOpen ? e($timeDisplay) : 'Fechado' ?>
                </div>
              </div>
            </div>
            <svg class="h-5 w-5 text-slate-400 chevron transition-transform" viewBox="0 0 24 24" fill="none">
              <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>

          <!-- Detalhes expandíveis -->
          <div class="day-details hidden border-t border-slate-200 bg-slate-50/50 p-4">
            <div class="space-y-3">
              <!-- Primeiro horário -->
              <div class="grid grid-cols-2 gap-3">
                <label class="grid gap-1.5">
                  <span class="text-xs font-medium text-slate-700">Início</span>
                  <input type="time" name="open1[<?= $d ?>]" 
                         value="<?= e(substr((string)$row['open1'], 0, 5)) ?>"
                         class="time-input rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-900 text-center text-base font-medium focus:ring-2 focus:ring-indigo-400"
                         data-day="<?= $d ?>" data-slot="1">
                </label>
                <label class="grid gap-1.5">
                  <span class="text-xs font-medium text-slate-700">Término</span>
                  <input type="time" name="close1[<?= $d ?>]" 
                         value="<?= e(substr((string)$row['close1'], 0, 5)) ?>"
                         class="time-input rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-900 text-center text-base font-medium focus:ring-2 focus:ring-indigo-400"
                         data-day="<?= $d ?>" data-slot="1">
                </label>
              </div>

              <!-- Segundo horário (se existir) -->
              <div class="slot2-container <?= $hasOpen2 ? '' : 'hidden' ?>" data-day="<?= $d ?>">
                <div class="flex items-center gap-2 mb-2">
                  <div class="h-px bg-slate-300 flex-1"></div>
                  <span class="text-xs text-slate-500">Segundo horário</span>
                  <div class="h-px bg-slate-300 flex-1"></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <label class="grid gap-1.5">
                    <span class="text-xs font-medium text-slate-700">Início</span>
                    <input type="time" name="open2[<?= $d ?>]" 
                           value="<?= e(substr((string)$row['open2'], 0, 5)) ?>"
                           class="time-input rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-900 text-center text-base font-medium focus:ring-2 focus:ring-indigo-400"
                           data-day="<?= $d ?>" data-slot="2">
                  </label>
                  <label class="grid gap-1.5">
                    <span class="text-xs font-medium text-slate-700">Término</span>
                    <input type="time" name="close2[<?= $d ?>]" 
                           value="<?= e(substr((string)$row['close2'], 0, 5)) ?>"
                           class="time-input rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-900 text-center text-base font-medium focus:ring-2 focus:ring-indigo-400"
                           data-day="<?= $d ?>" data-slot="2">
                  </label>
                </div>
                <button type="button" class="btn-remove-slot2 mt-2 w-full text-center text-sm text-red-600 hover:text-red-700 font-medium py-1" data-day="<?= $d ?>">
                  Remover segundo horário
                </button>
              </div>

              <!-- Botão adicionar segundo horário -->
              <button type="button" class="btn-add-slot2 <?= $hasOpen2 ? 'hidden' : '' ?> w-full inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 font-medium" data-day="<?= $d ?>">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                  <path d="M12 6v12M6 12h12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                Adicionar segundo horário
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </fieldset>
  </div>

  <!-- Botão Salvar -->
  <div class="flex justify-end gap-3">
    <a href="<?= e(base_url('admin/' . $slug . '/dashboard')) ?>"
       class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm hover:bg-slate-50 transition-colors">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Cancelar
    </a>
    <button type="submit"
            class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:opacity-95 transition-opacity">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M20 7 9 18l-5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Salvar Configurações
    </button>
  </div>

  <script>
  // ===== Função de navegação entre seções =====
  function switchSection(sectionName) {
    // Esconder todas as seções
    document.querySelectorAll('.section-content').forEach(section => {
      section.classList.add('hidden');
    });
    
    // Remover classe ativa de todos os botões
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

  // ===== Função para alternar visibilidade da senha =====
  function togglePasswordVisibility(inputId, button) {
    const input = document.getElementById(inputId);
    const eyeOpen = button.querySelector('.eye-open');
    const eyeClosed = button.querySelector('.eye-closed');
    
    if (input.type === 'password') {
      input.type = 'text';
      eyeOpen.classList.add('hidden');
      eyeClosed.classList.remove('hidden');
    } else {
      input.type = 'password';
      eyeOpen.classList.remove('hidden');
      eyeClosed.classList.add('hidden');
    }
  }

  // ===== Preview de imagens (Logo e Banner) =====
  const logoInput = document.getElementById('logo-input');
  const logoPreviewImg = document.getElementById('logo-preview-img');
  const logoPreviewPlaceholder = document.getElementById('logo-preview-placeholder');
  
  if (logoInput) {
    logoInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(event) {
          logoPreviewImg.src = event.target.result;
          logoPreviewImg.style.display = 'block';
          logoPreviewImg.classList.remove('hidden');
          logoPreviewPlaceholder.style.display = 'none';
          logoPreviewPlaceholder.classList.add('hidden');
        };
        reader.readAsDataURL(file);
      }
    });
  }

  const bannerInput = document.getElementById('banner-input');
  const bannerPreviewImg = document.getElementById('banner-preview-img');
  const bannerPreviewPlaceholder = document.getElementById('banner-preview-placeholder');
  
  if (bannerInput) {
    bannerInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(event) {
          bannerPreviewImg.src = event.target.result;
          bannerPreviewImg.style.display = 'block';
          bannerPreviewImg.classList.remove('hidden');
          bannerPreviewPlaceholder.style.display = 'none';
          bannerPreviewPlaceholder.classList.add('hidden');
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // Toggle expandir/recolher dia
  document.querySelectorAll('.day-header').forEach(header => {
    header.addEventListener('click', function() {
      const card = this.closest('.day-card');
      const details = card.querySelector('.day-details');
      const chevron = card.querySelector('.chevron');
      const isExpanded = !details.classList.contains('hidden');
      
      if (isExpanded) {
        details.classList.add('hidden');
        chevron.style.transform = 'rotate(0deg)';
      } else {
        details.classList.remove('hidden');
        chevron.style.transform = 'rotate(90deg)';
      }
    });
  });

  // Toggle checkbox abrir/fechar
  document.querySelectorAll('.toggle-day').forEach(checkbox => {
    const label = checkbox.closest('label');
    const track = label.querySelector('.day-toggle-track');
    const thumb = label.querySelector('.day-toggle-thumb');
    
    // Clicar no label/toggle
    label.addEventListener('click', function(e) {
      e.stopPropagation();
      checkbox.checked = !checkbox.checked;
      
      const day = checkbox.dataset.day;
      const card = checkbox.closest('.day-card');
      const summary = card.querySelector('.time-summary');
      const isOpen = checkbox.checked;
      
      // Atualizar visual do toggle
      if (isOpen) {
        track.classList.remove('bg-slate-300');
        track.classList.add('admin-primary-bg');
        thumb.style.transform = 'translateX(16px)';
        updateTimeSummary(day);
        summary.classList.remove('text-slate-400');
        summary.classList.add('text-slate-600');
      } else {
        track.classList.remove('admin-primary-bg');
        track.classList.add('bg-slate-300');
        thumb.style.transform = 'translateX(0px)';
        summary.textContent = 'Fechado';
        summary.classList.remove('text-slate-600');
        summary.classList.add('text-slate-400');
      }
    });
  });

  // Adicionar segundo horário
  document.querySelectorAll('.btn-add-slot2').forEach(btn => {
    btn.addEventListener('click', function() {
      const day = this.dataset.day;
      const card = this.closest('.day-card');
      const container = card.querySelector('.slot2-container[data-day="' + day + '"]');
      
      container.classList.remove('hidden');
      this.classList.add('hidden');
    });
  });

  // Remover segundo horário
  document.querySelectorAll('.btn-remove-slot2').forEach(btn => {
    btn.addEventListener('click', function() {
      const day = this.dataset.day;
      const card = this.closest('.day-card');
      const container = this.closest('.slot2-container');
      const addBtn = card.querySelector('.btn-add-slot2[data-day="' + day + '"]');
      
      // Limpar valores
      container.querySelectorAll('input[type="time"]').forEach(input => input.value = '');
      
      container.classList.add('hidden');
      addBtn.classList.remove('hidden');
      updateTimeSummary(day);
    });
  });

  // Atualizar resumo quando alterar horários
  document.querySelectorAll('.time-input').forEach(input => {
    input.addEventListener('change', function() {
      const day = this.dataset.day;
      updateTimeSummary(day);
    });
  });

  function updateTimeSummary(day) {
    const card = document.querySelector('.day-card[data-day="' + day + '"]');
    const summary = card.querySelector('.time-summary');
    const checkbox = card.querySelector('.toggle-day');
    
    if (!checkbox.checked) {
      summary.textContent = 'Fechado';
      return;
    }
    
    const open1 = card.querySelector('input[name="open1[' + day + ']"]').value;
    const close1 = card.querySelector('input[name="close1[' + day + ']"]').value;
    const open2 = card.querySelector('input[name="open2[' + day + ']"]').value;
    const close2 = card.querySelector('input[name="close2[' + day + ']"]').value;
    
    let text = '';
    if (open1 && close1) {
      text = open1 + ' - ' + close1;
    }
    if (open2 && close2) {
      text += (text ? ' / ' : '') + open2 + ' - ' + close2;
    }
    
    summary.textContent = text || 'Horário não definido';
  }

  // ===== Sincronização Color Pickers =====
  document.addEventListener('DOMContentLoaded', function() {
    // Sincronizar color picker -> text input
    document.querySelectorAll('.color-picker').forEach(picker => {
      picker.addEventListener('input', function() {
        const key = this.name;
        const textInput = document.querySelector('.color-text-input[data-color-for="' + key + '"]');
        const preview = this.closest('.rounded-xl').querySelector('.shadow-inner');
        
        if (textInput) {
          textInput.value = this.value.toUpperCase();
        }
        if (preview) {
          preview.style.backgroundColor = this.value;
        }
      });
    });

    // Sincronizar text input -> color picker
    document.querySelectorAll('.color-text-input').forEach(textInput => {
      textInput.addEventListener('input', function() {
        const key = this.dataset.colorFor;
        const picker = document.getElementById('picker-' + key);
        const preview = this.closest('.rounded-xl').querySelector('.shadow-inner');
        
        // Auto-uppercase
        this.value = this.value.toUpperCase();
        
        // Validar e atualizar se for hex válido
        if (/^#[0-9A-F]{6}$/i.test(this.value)) {
          if (picker) {
            picker.value = this.value;
          }
          if (preview) {
            preview.style.backgroundColor = this.value;
          }
        }
      });
    });
  });

  // Função para resetar cores
  function resetColors() {
    if (!confirm('Tem certeza que deseja restaurar as cores padrão? Esta ação não pode ser desfeita.')) {
      return;
    }
    
    const defaults = {
      'menu_header_text_color': '#FFFFFF',
      'menu_header_button_color': '#FFFFFF',
      'menu_header_bg_color': '#1E293B',
      'menu_logo_border_color': '#E2E8F0',
      'menu_group_title_bg_color': '#F1F5F9',
      'menu_group_title_text_color': '#1E293B',
      'menu_welcome_bg_color': '#FEF3C7',
      'menu_welcome_text_color': '#92400E'
    };
    
    Object.entries(defaults).forEach(([key, value]) => {
      const picker = document.getElementById('picker-' + key);
      const textInput = document.querySelector('.color-text-input[data-color-for="' + key + '"]');
      const preview = picker?.closest('.rounded-xl').querySelector('.shadow-inner');
      
      if (picker) picker.value = value;
      if (textInput) textInput.value = value;
      if (preview) preview.style.backgroundColor = value;
    });
  }

  // ===== TEXTOS POR DIA DA SEMANA =====
  // Toggle expandir/recolher dia
  document.querySelectorAll('.highlight-day-card .day-header').forEach(header => {
    header.addEventListener('click', function() {
      const card = this.closest('.highlight-day-card');
      const details = card.querySelector('.highlight-day-details');
      const chevron = card.querySelector('.highlight-chevron');
      const isExpanded = !details.classList.contains('hidden');
      
      if (isExpanded) {
        details.classList.add('hidden');
        chevron.style.transform = 'rotate(0deg)';
      } else {
        details.classList.remove('hidden');
        chevron.style.transform = 'rotate(90deg)';
      }
    });
  });

  // Toggle checkbox abrir/fechar
  document.querySelectorAll('.highlight-toggle-day:not(#use-single-text)').forEach(checkbox => {
    const label = checkbox.closest('label');
    const track = label.querySelector('.highlight-toggle-track');
    const thumb = label.querySelector('.highlight-toggle-thumb');
    
    label.addEventListener('click', function(e) {
      e.stopPropagation();
      checkbox.checked = !checkbox.checked;
      
      const day = checkbox.dataset.day;
      const card = checkbox.closest('.highlight-day-card');
      const summary = card.querySelector('.highlight-text-summary');
      const isEnabled = checkbox.checked;
      
      // Atualizar visual do toggle
      if (isEnabled) {
        // DESATIVAR O MODO "TEXTO ÚNICO" QUANDO ATIVAR UM DIA INDIVIDUAL
        disableSingleTextMode();
        
        track.classList.remove('bg-slate-300');
        track.classList.add('admin-primary-bg');
        thumb.style.transform = 'translateX(16px)';
        updateHighlightTextSummary(day);
      } else {
        track.classList.remove('admin-primary-bg');
        track.classList.add('bg-slate-300');
        thumb.style.transform = 'translateX(0px)';
        // NÃO mudar o resumo - manter o texto visível
        updateHighlightTextSummary(day);
      }
    });
  });

  // Atualizar resumo quando alterar texto
  document.querySelectorAll('.highlight-text-input').forEach(input => {
    input.addEventListener('change', function() {
      const day = this.dataset.day;
      updateHighlightTextSummary(day);
    });

    input.addEventListener('keyup', function() {
      const day = this.dataset.day;
      updateHighlightTextSummary(day);
    });
  });

  function updateHighlightTextSummary(day) {
    const card = document.querySelector('.highlight-day-card[data-day="' + day + '"]');
    const summary = card.querySelector('.highlight-text-summary');
    const checkbox = card.querySelector('.highlight-toggle-day');
    
    // Card especial "todos os dias" não tem resumo dinâmico
    if (day === 'all') return;
    
    const textarea = card.querySelector('textarea[name="highlight_text_' + day + '"]');
    const text = textarea.value.trim();
    
    // Sempre mostrar o texto se existir, independente do toggle
    if (text) {
      const preview = text.substring(0, 50) + (text.length > 50 ? '...' : '');
      summary.textContent = preview;
      summary.classList.remove('text-slate-400');
      summary.classList.add('text-slate-600');
    } else {
      summary.textContent = 'Sem texto';
      summary.classList.remove('text-slate-600');
      summary.classList.add('text-slate-400');
    }
  }

  // ===== CARD "TEXTO ÚNICO" - Toggle e Expand/Collapse =====
  const singleTextCard = document.querySelector('.highlight-day-card[data-day="all"]');
  if (singleTextCard) {
    // Toggle
    const singleToggle = singleTextCard.querySelector('#use-single-text');
    const singleTrack = singleTextCard.querySelector('.highlight-toggle-track');
    const singleThumb = singleTextCard.querySelector('.highlight-toggle-thumb');
    const singleToggleLabel = singleTextCard.querySelector('.single-text-toggle-label');
    
    // Impedir que o clique no toggle/label propague para o header
    singleToggleLabel?.addEventListener('click', function(e) {
      e.stopPropagation();
    });
    
    singleToggle?.addEventListener('change', function(e) {
      e.stopPropagation(); // Impedir propagação
      if (this.checked) {
        singleTrack.classList.remove('bg-slate-300');
        singleTrack.classList.add('bg-blue-600');
        singleThumb.style.transform = 'translateX(16px)';
        
        // DESATIVAR TODOS OS DIAS INDIVIDUAIS
        disableAllIndividualDays();
      } else {
        singleTrack.classList.add('bg-slate-300');
        singleTrack.classList.remove('bg-blue-600');
        singleThumb.style.transform = 'translateX(0px)';
      }
    });

    // Expand/Collapse do header
    const singleHeader = singleTextCard.querySelector('.single-text-header');
    const singleDetails = singleTextCard.querySelector('.highlight-day-details');
    const singleChevron = singleTextCard.querySelector('.highlight-chevron');
    
    singleHeader?.addEventListener('click', function(e) {
      // Não fazer nada se clicou no toggle
      if (e.target.closest('.single-text-toggle-label')) {
        return;
      }
      
      const isHidden = singleDetails.classList.contains('hidden');
      
      if (isHidden) {
        singleDetails.classList.remove('hidden');
        singleChevron.style.transform = 'rotate(90deg)';
      } else {
        singleDetails.classList.add('hidden');
        singleChevron.style.transform = 'rotate(0deg)';
      }
    });
  }

  // ===== FUNÇÃO: Desativar todos os dias individuais =====
  function disableAllIndividualDays() {
    const dayKeys = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    
    dayKeys.forEach(day => {
      const card = document.querySelector('.highlight-day-card[data-day="' + day + '"]');
      if (!card) return;
      
      const checkbox = card.querySelector('.highlight-toggle-day');
      const track = card.querySelector('.highlight-toggle-track');
      const thumb = card.querySelector('.highlight-toggle-thumb');
      
      // Desativar toggle
      checkbox.checked = false;
      track.classList.add('bg-slate-300');
      track.classList.remove('admin-primary-bg');
      thumb.style.transform = 'translateX(0px)';
      
      // Atualizar resumo
      updateHighlightTextSummary(day);
    });
  }

  // ===== FUNÇÃO: Desativar o card "Texto Único" =====
  function disableSingleTextMode() {
    const singleToggle = document.querySelector('#use-single-text');
    const singleTrack = document.querySelector('.highlight-day-card[data-day="all"] .highlight-toggle-track');
    const singleThumb = document.querySelector('.highlight-day-card[data-day="all"] .highlight-toggle-thumb');
    
    if (singleToggle && singleToggle.checked) {
      singleToggle.checked = false;
      singleTrack.classList.add('bg-slate-300');
      singleTrack.classList.remove('bg-blue-600');
      singleThumb.style.transform = 'translateX(0px)';
    }
  }

  document.getElementById('apply-single-text')?.addEventListener('click', function() {
    const singleText = document.getElementById('single-text-input').value.trim();
    
    if (!singleText) {
      alert('Digite um texto antes de aplicar!');
      return;
    }

    // Aplicar o texto em todos os dias
    const dayKeys = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    
    dayKeys.forEach(day => {
      const card = document.querySelector('.highlight-day-card[data-day="' + day + '"]');
      const textarea = card.querySelector('textarea[name="highlight_text_' + day + '"]');
      const checkbox = card.querySelector('.highlight-toggle-day');
      const track = card.querySelector('.highlight-toggle-track');
      const thumb = card.querySelector('.highlight-toggle-thumb');
      
      // Aplicar texto
      textarea.value = singleText;
      
      // Ativar toggle
      checkbox.checked = true;
      track.classList.remove('bg-slate-300');
      track.classList.add('admin-primary-bg');
      thumb.style.transform = 'translateX(16px)';
      
      // Atualizar resumo
      updateHighlightTextSummary(day);
    });

    alert('✅ Texto aplicado em todos os dias da semana!');
  });

  // ===== Inicializar ao carregar a página =====
  document.addEventListener('DOMContentLoaded', function() {
    // Inicializar resumos dos textos por dia
    document.querySelectorAll('.highlight-day-card').forEach(card => {
      const day = card.dataset.day;
      updateHighlightTextSummary(day);
    });

    // Verificar se há parâmetro na URL para abrir seção específica
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get('section');
    
    if (section && document.getElementById('section-' + section)) {
      // Abrir seção especificada na URL
      switchSection(section);
    }
  });
  </script>

  </div>

  </form>

  <?php
  $content = ob_get_clean();
include __DIR__ . '/../layout.php';
