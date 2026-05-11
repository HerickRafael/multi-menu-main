<?php
// admin/kds/index.php — Kitchen Display System (SSE + polling fallback)

$title = 'KDS - ' . ($company['name'] ?? 'Empresa');
$slug  = rawurlencode((string)($activeSlug ?? ($company['slug'] ?? '')));

$initialSnapshot = is_array($initialSnapshot ?? null) ? $initialSnapshot : [];
$kdsConfig       = is_array($kdsConfig ?? null) ? $kdsConfig : [];
$hasCanceled     = !empty($hasCanceled);

$initialJson = json_encode($initialSnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$configJson  = json_encode($kdsConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<style>
  /* Global KDS Styles - Consistent with Admin UI */
  * { box-sizing: border-box; }
  
  body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    background: #f8fafc;
    margin: 0;
    padding: 0;
    min-height: 100vh;
  }

  .kds-main-container {
    background: #f8fafc;
    min-height: 100vh;
    padding: 1rem;
    max-width: 1536px;
    margin: 0 auto;
  }

  /* Header Styles - Following Admin Pattern */
  .kds-header {
    background: #ffffff;
    border-radius: 1.5rem;
    padding: 1.5rem 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
  }

  .kds-header-icon {
    display: inline-flex;
    height: 2.5rem;
    width: 2.5rem;
    align-items: center;
    justify-content: center;
    border-radius: 1.5rem;
    background-image: var(--admin-primary-gradient);
    background-color: var(--admin-primary-color);
    color: #ffffff;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  }

  .kds-header h1 {
    background-image: var(--admin-primary-gradient);
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
  }

  .kds-header p {
    color: #64748b;
    margin: 0.25rem 0 0 0;
    font-size: 0.875rem;
  }

  .kds-header-actions {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  /* Controls Section - Admin Style */
  .kds-controls {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 1.5rem;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  }

  .kds-range-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    cursor: pointer;
    transition: all 0.15s ease;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  }

  .kds-range-btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
  }

  .kds-range-btn.kds-btn-primary {
    background-image: var(--admin-primary-gradient);
    background-color: var(--admin-primary-color);
    color: #ffffff;
    border-color: var(--admin-primary-color);
  }

  .kds-range-btn.kds-btn-primary:hover {
    opacity: 0.95;
  }

  .kds-search {
    margin-left: auto;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    padding: 0.5rem 0.75rem;
    color: #1f2937;
    font-weight: 500;
    min-width: 200px;
    transition: all 0.15s ease;
  }

  .kds-search::placeholder {
    color: #9ca3af;
  }

  .kds-search:focus {
    outline: none;
    border-color: var(--admin-primary-color);
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
  }

  /* Columns Grid */
  .kds-columns { 
    display: grid; 
    gap: 1.5rem; 
    grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
  }

  @media (min-width: 768px) { 
    .kds-columns { grid-template-columns: repeat(2, 1fr); } 
  }
  
  @media (min-width: 1200px) { 
    .kds-columns { grid-template-columns: repeat(3, 1fr); } 
  }

  @media (min-width: 1600px) { 
    .kds-columns { grid-template-columns: repeat(3, 1fr); } 
  }

  /* Column Styles - Admin Card Style */
  .kds-column {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 1.5rem;
    padding: 1.5rem;
    min-height: 600px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  }

  .kds-column[data-status="pending"] {
    border-left: 4px solid #f59e0b;
  }

  .kds-column[data-status="paid"] {
    border-left: 4px solid #3b82f6;
  }

  .kds-column[data-status="completed"] {
    border-left: 4px solid #10b981;
  }

  .kds-column-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .kds-column-header h2 {
    font-size: 0.875rem;
    font-weight: 600;
    color: #0f172a;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  .kds-column-count {
    background-color: var(--admin-primary-soft);
    color: var(--admin-primary-color);
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
  }

  /* Order List */
  .kds-list {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    overflow-y: auto;
    max-height: calc(100vh - 300px);
    padding-right: 0.5rem;
  }

  .kds-list::-webkit-scrollbar {
    width: 6px;
  }

  .kds-list::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
  }

  .kds-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
  }

  .kds-list::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
  }

  /* Order Cards - Admin Style */
  .kds-card {
    background: #ffffff;
    border-radius: 1.25rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
    position: relative;
  }

  .kds-card[data-status="paid"]::before {
    background: #3b82f6;
  }

  .kds-card[data-status="completed"]::before {
    background: #10b981;
  }

  .kds-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  }

  .kds-card.kds-alert-warning {
    border-color: #f59e0b;
    box-shadow: 0 0 0 1px #f59e0b20;
  }

  .kds-card.kds-alert-danger {
    border-color: #ef4444;
    box-shadow: 0 0 0 1px #ef444420;
    animation: pulse-danger 2s ease-in-out infinite;
  }

  @keyframes pulse-danger {
    0%, 100% { 
      box-shadow: 0 0 0 1px #ef444420, 0 1px 3px 0 rgba(0, 0, 0, 0.1); 
    }
    50% { 
      box-shadow: 0 0 0 4px #ef444420, 0 4px 6px -1px rgba(0, 0, 0, 0.1); 
    }
  }

  /* Card Header */
  .kds-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
  }

  .kds-card-header h3 {
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.5rem 0;
  }

  .kds-meta {
    font-size: 0.875rem;
    color: #64748b;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .kds-meta strong {
    color: #0f172a;
    font-weight: 600;
  }

  /* Status Badge - Admin Style */
  .kds-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    margin-bottom: 0.5rem;
  }

  .kds-badge[data-status="pending"] {
    background-color: #fef3c7;
    color: #92400e;
  }

  .kds-badge[data-status="paid"] {
    background-color: #dbeafe;
    color: #1e40af;
  }

  .kds-badge[data-status="completed"] {
    background-color: #dcfce7;
    color: #166534;
  }

  /* SLA Tags */
  .kds-tag {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
  }

  .kds-tag.sla-safe {
    background: #dcfce7;
    color: #15803d;
  }

  .kds-tag.sla-warning {
    background: #fef3c7;
    color: #d97706;
  }

  .kds-tag.sla-now {
    background: #fee2e2;
    color: #dc2626;
    animation: pulse-sla 1s ease-in-out infinite;
  }

  @keyframes pulse-sla {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
  }

  /* Items List */
  .kds-items {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;

</style>
<!-- Inline quick-hide for admin toasts: ensures toasts are hidden immediately on parse -->
<style id="__kds_hide_admin_toasts_inline">#admin-order-toasts{display:none !important;}</style>
<style>
    padding: 1rem;
    margin: 1rem 0;
    list-style: none;
  }

  .kds-items li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.875rem;
    color: #374151;
  }

  .kds-items li:last-child {
    border-bottom: none;
  }

  .kds-items li strong {
    color: var(--admin-primary-color);
    font-weight: 700;
  }

  /* Action Buttons - Admin Style */
  .kds-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 1rem;
  }

  .kds-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.15s ease;
    border: 1px solid transparent;
  }

  .kds-btn-primary {
    background-image: var(--admin-primary-gradient);
    background-color: var(--admin-primary-color);
    color: #ffffff;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  }

  .kds-btn-primary:hover {
    opacity: 0.95;
  }

  .kds-btn-success {
    background: #10b981;
    color: #ffffff;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  }

  .kds-btn-success:hover {
    background: #059669;
  }

  .kds-btn-ghost {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #374151;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  }

  .kds-btn-ghost:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
  }

  .kds-btn-danger {
    background: #ffffff;
    border: 1px solid #fca5a5;
    color: #dc2626;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  }

  .kds-btn-danger:hover {
    background: #fef2f2;
    border-color: #f87171;
  }

  /* Total Price */
  .kds-total {
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    text-align: right;
    margin-top: 0.25rem;
  }

  /* Empty State */
  .kds-empty {
    background: #ffffff;
    border: 2px dashed #e2e8f0;
    border-radius: 1rem;
    padding: 2rem 1rem;
    text-align: center;
    color: #64748b;
    font-size: 0.875rem;
    font-weight: 500;
  }

  /* Canceled Section */
  #kds-canceled {
    margin-top: 2rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 1.5rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  }

  /* Responsive Design */
  @media (max-width: 768px) {
    .kds-main-container {
      padding: 1rem;
    }

    .kds-header {
      padding: 1rem 1.5rem;
    }

    .kds-header h1 {
      font-size: 1.25rem;
    }

    .kds-controls {
      padding: 1rem;
    }

    .kds-columns {
      grid-template-columns: 1fr;
      gap: 1rem;
    }

    .kds-column {
      min-height: 400px;
    }

    .kds-card {
      padding: 1rem;
    }

    .kds-search {
      min-width: 150px;
      margin-left: 0;
      margin-top: 0.5rem;
      width: 100%;
    }

    .kds-actions {
      gap: 0.25rem;
    }

    .kds-btn {
      padding: 0.375rem 0.75rem;
      font-size: 0.8rem;
    }
  }

  /* Notification Styles */
  .kds-notification {
    position: fixed;
    top: 1rem;
    right: 1rem;
    background: #ffffff;
    color: #0f172a;
    padding: 1rem 1.25rem;
    border-radius: 0.75rem;
    font-weight: 600;
    font-size: 0.875rem;
    z-index: 1000;
    animation: slideIn 0.3s ease;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    min-width: 260px;
    max-width: 320px;
  }

  @keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
  }

  /* Focus and accessibility improvements */
  .kds-btn:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
  }

  .kds-btn:active {
    transform: translateY(1px);
  }

  /* Print Styles */
  @media print {
    .kds-main-container {
      background: white !important;
    }
    
    .kds-card {
      background: white !important;
      border: 1px solid #000 !important;
      break-inside: avoid;
    }
    
    .kds-btn {
      display: none !important;
    }
  }
</style>

<?php ob_start(); ?>
<div class="kds-main-container" id="kds-app" data-slug="<?= e($slug) ?>">
  <header class="kds-header">
    <div class="flex items-center gap-3">
      <span class="kds-header-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path d="M0 1a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1z"/>
          <path d="M2 13.5a.5.5 0 0 1 .5-.5H6v-1H3.5a.5.5 0 0 1 0-1h9a.5.5 0 0 1 0 1H10v1h3.5a.5.5 0 0 1 0 1H2.5a.5.5 0 0 1-.5-.5"/>
        </svg>
      </span>
      <div>
        <h1>KDS · <?= e($company['name'] ?? '') ?></h1>
        <p>Sistema de exibição da cozinha em tempo real</p>
      </div>
    </div>
    <div class="kds-header-actions">
      <button id="kds-refresh" class="kds-btn kds-btn-ghost">
        <svg class="refresh-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
          <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
        </svg>
        Atualizar
      </button>
      <a href="<?= e(base_url('admin/' . $slug . '/dashboard')) ?>" class="kds-btn kds-btn-ghost">
        <svg class="refresh-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path d="M7.293 1.5a1 1 0 0 1 1.414 0L11 3.793V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v3.293l2.354 2.353a.5.5 0 0 1-.708.708L8 2.207l-5 5V13.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 1 0 1h-4A1.5 1.5 0 0 1 2 13.5V8.207l-.646.647a.5.5 0 1 1-.708-.708z"/>
          <path d="M11.886 9.46c.18-.613 1.048-.613 1.229 0l.043.148a.64.64 0 0 0 .921.382l.136-.074c.561-.306 1.175.308.87.869l-.075.136a.64.64 0 0 0 .382.92l.149.045c.612.18.612 1.048 0 1.229l-.15.043a.64.64 0 0 0-.38.921l.074.136c.305.561-.309 1.175-.87.87l-.136-.075a.64.64 0 0 0-.92.382l-.045.149c-.18.612-1.048.612-1.229 0l-.043-.15a.64.64 0 0 0-.921-.38l-.136.074c-.561.305-1.175-.309-.87-.87l.075-.136a.64.64 0 0 0-.382-.92l-.148-.044c-.613-.181-.613-1.049 0-1.23l.148-.043a.64.64 0 0 0 .382-.921l-.074-.136c-.306-.561.308-1.175.869-.87l.136.075a.64.64 0 0 0 .92-.382zM14 12.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0"/>
        </svg>
        Dashboard
      </a>
    </div>
  </header>

  <section class="kds-controls" id="kds-range-buttons">
    <button type="button" class="kds-range-btn" data-range="today">Hoje</button>
    <button type="button" class="kds-range-btn" data-range="yesterday">Ontem</button>
    <button type="button" class="kds-range-btn" data-range="all">Todos</button>
    <div class="relative">
      <input type="search" id="kds-search" class="kds-search" placeholder="Buscar por cliente, telefone ou #">
    </div>
  </section>

  <section class="kds-columns" id="kds-columns"></section>

  <section id="kds-canceled" class="<?= $hasCanceled ? '' : 'hidden' ?>">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Pedidos Cancelados</h2>
      <button id="toggle-canceled" data-visible="0" class="kds-btn kds-btn-danger <?= $hasCanceled ? '' : 'cursor-not-allowed opacity-50' ?>" <?= $hasCanceled ? '' : 'disabled' ?>>
        <?= $hasCanceled ? 'Mostrar cancelados' : 'Sem cancelados' ?>
      </button>
    </div>
    <div id="kds-canceled-count" class="text-sm text-slate-400 mb-3"></div>
    <div id="kds-canceled-list" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3"></div>
  </section>
</div>

<script>
(function(){
  const CONFIG = <?= $configJson ?: '{}' ?>;
  const INITIAL_ORDERS = <?= $initialJson ?: '[]' ?>;
  const STATUS_FLOW = {
    pending:   { next: 'paid',       label: 'Iniciar preparo' },
    paid:      { next: 'completed',  label: 'Marcar como pronto' },
    completed: null,
    canceled:  null,
  };
  const STATUS_LABELS = {
    pending:   'Recebido',
    paid:      'Preparando',
    completed: 'Pronto',
    canceled:  'Cancelado'
  };
  const LANE_BY_STATUS = {
    pending:   'pending',
    paid:      'paid',
    completed: 'completed',
    canceled:  'canceled'
  };

  // Pequeno beep (dados inline) usado como fallback da sineta.
  const DEFAULT_BELL_URI = 'data:audio/wav;base64,' +
  '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';

  class KdsChime {
    constructor(fallbackUri){
      // Preferir arquivo de áudio quando for fornecido (diferente do DEFAULT)
      this.AudioContext = window.AudioContext || window.webkitAudioContext || null;
      this.fallbackUri = this.prepareUri((typeof fallbackUri === 'string' && fallbackUri.trim()) ? fallbackUri.trim() : DEFAULT_BELL_URI);
      this.preferFallback = this.fallbackUri && this.fallbackUri !== DEFAULT_BELL_URI;

      this.context = null;
      this.unlocked = false;
      this.pendingRing = false;
      this.lastPlayedAt = 0;
      this.minimumGapMs = 450;
      this.loopInterval = null;
      this.loopTimeout = null;
      this.loopContinuous = false;

      this.unlockEvents = ['pointerdown', 'touchstart', 'keydown'];
      this.handleUnlockEvent = this.handleUnlockEvent.bind(this);
      this.handleVisibility   = this.handleVisibility.bind(this);

      this.audioEl = null;
      this.audioFailed = false;

      this.bindUnlockListeners();
    }

    prepareUri(value){
      if (typeof value !== 'string') return '';
      const raw = value.trim();
      if (!raw) return '';
      if (/^(data:|blob:)/i.test(raw)) return raw;
      if (/^https?:/i.test(raw)) return raw;
      if (/^\/\//.test(raw)) return window.location.protocol + raw;
      if (raw[0] === '/') return window.location.origin + raw;
      try { return new URL(raw, window.location.href).toString(); } catch { return raw; }
    }

    bindUnlockListeners(){
      this.unlockEvents.forEach(evt => {
        document.addEventListener(evt, this.handleUnlockEvent, {passive: true});
      });
      document.addEventListener('visibilitychange', this.handleVisibility);
    }

    removeUnlockListeners(){
      this.unlockEvents.forEach(evt => {
        document.removeEventListener(evt, this.handleUnlockEvent);
      });
      document.removeEventListener('visibilitychange', this.handleVisibility);
    }

    handleVisibility(){
      if (document.hidden) {
        if (!this.loopContinuous) {
          this.startContinuousLoop();
        }
      } else {
        if (this.loopContinuous || this.loopTimeout) {
          this.stopLoops();
          this.playVisiblePattern();
        }
      }
    }

    handleUnlockEvent(){
      if (this.unlocked) return;
      this.unlocked = true;

      // Só cria o contexto já no unlock se a preferência não for por arquivo
      if (!this.preferFallback) {
        this.ensureContext();
      }

      this.removeUnlockListeners();
      if (this.pendingRing) {
        this.startAlarm();
      }
    }

    ring(){
      if (!this.unlocked) {
        this.pendingRing = true;
        return;
      }
      const now = Date.now();
      if (now - this.lastPlayedAt < this.minimumGapMs) return;
      this.startAlarm();
    }

    startAlarm(){
      if (document.hidden) {
        this.startContinuousLoop();
      } else {
        this.playVisiblePattern();
      }
    }

    playVisiblePattern(){
      this.stopLoops();
      this.pendingRing = true;
      this.playOnce();
      this.loopTimeout = setTimeout(() => {
        this.loopTimeout = null;
        if (document.hidden) {
          this.startContinuousLoop();
          return;
        }
        this.playOnce();
      }, 5000);
    }

    startContinuousLoop(){
      this.stopLoops();
      this.loopContinuous = true;
      this.pendingRing = true;
      this.playOnce();
      this.loopInterval = setInterval(() => {
        this.playOnce();
      }, 5000);
    }

    playOnce(){
      let played = false;

      if (this.preferFallback && !this.audioFailed) {
        played = this.playFallback();
        if (!played && this.AudioContext) {
          this.ensureContext();
          if (this.context) played = this.playWithContext();
        }
      } else {
        this.ensureContext();
        if (this.context) played = this.playWithContext();
        if (!played) played = this.playFallback();
      }

      if (played) {
        this.pendingRing = false;
        this.lastPlayedAt = Date.now();
      }
      return played;
    }

    ensureContext(){
      if (this.context || !this.AudioContext) return;
      try {
        this.context = new this.AudioContext();
        if (this.context && this.context.state === 'suspended') {
          this.context.resume().catch(()=>{});
        }
      } catch {
        this.context = null;
        this.AudioContext = null;
      }
    }

    playWithContext(){
      if (!this.context) return false;
      try {
        const ctx = this.context;
        if (ctx.state === 'suspended') ctx.resume().catch(()=>{});
        const now = ctx.currentTime;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(880, now);
        gain.gain.setValueAtTime(0.0001, now);
        gain.gain.exponentialRampToValueAtTime(0.32, now + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.7);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(now);
        osc.stop(now + 0.72);
        return true;
      } catch {
        this.context = null;
        return false;
      }
    }

    playFallback(){
      if (!this.fallbackUri) return false;

      const fallbackToTone = () => {
        this.audioFailed = true;
        this.preferFallback = false;
        return this.fallbackToTone();
      };

      try {
        if (!this.audioEl) {
          this.audioEl = new Audio();
          this.audioEl.preload = 'auto';
          this.audioEl.src = this.fallbackUri;
          this.audioEl.volume = 0.8;
          this.audioEl.addEventListener('error', () => { fallbackToTone(); }, { once: true });
        }
        try { this.audioEl.currentTime = 0; } catch {}
        const p = this.audioEl.play();
        if (p && typeof p.catch === 'function') {
          p.catch(() => { fallbackToTone(); });
        }
        this.audioFailed = false;
        return true;
      } catch {
        return fallbackToTone();
      }
    }

    fallbackToTone(){
      if (!this.AudioContext) return false;
      if (this.audioEl) {
        try { this.audioEl.pause(); } catch {}
      }
      this.audioEl = null;
      this.ensureContext();
      if (!this.context) return false;
      return this.playWithContext();
    }

    dispose(){
      this.removeUnlockListeners();
      this.stopLoops();
      if (this.context && typeof this.context.close === 'function') {
        try { this.context.close(); } catch {}
      }
      this.context = null;
      if (this.audioEl) {
        try {
          this.audioEl.pause();
          this.audioEl.currentTime = 0;
        } catch {}
      }
      this.audioEl = null;
    }

    clearLoopTimers(){
      if (this.loopInterval) {
        clearInterval(this.loopInterval);
        this.loopInterval = null;
      }
      if (this.loopTimeout) {
        clearTimeout(this.loopTimeout);
        this.loopTimeout = null;
      }
      this.loopContinuous = false;
    }

    stopLoops(){
      this.clearLoopTimers();
      this.pendingRing = false;
    }
  }

  class KdsRealtime {
    constructor(config, initial){
      this.config = config || {};
      this.state = {
        orders: new Map(),
        range: 'today',
        search: '',
      };
      this.columnsEl = document.getElementById('kds-columns');
      this.columnRefs = new Map();
      this.canceledSection = document.getElementById('kds-canceled');
      this.canceledList = document.getElementById('kds-canceled-list');
      this.canceledCount = document.getElementById('kds-canceled-count');
      this.toggleCanceledBtn = document.getElementById('toggle-canceled');
      this.rangeButtons = Array.from(document.querySelectorAll('#kds-range-buttons [data-range]'));
      this.searchInput = document.getElementById('kds-search');
      this.refreshBtn = document.getElementById('kds-refresh');
      this.pollTimer = null;
      this.pollInterval = this.resolveInterval();
      this.lastSyncToken = null;
      this.isFetching = false;
      this.renderRequested = false;
      const fallbackBell = (typeof this.config.bellUrl === 'string' && this.config.bellUrl.trim())
        ? this.config.bellUrl.trim()
        : DEFAULT_BELL_URI;
      this.chime = new KdsChime(fallbackBell);
      this.knownPending = new Set();
      (initial || []).forEach(order => this.ingestOrder(order));
      this.updateSyncTokenFromState();
      this.syncKnownPending();
    }

    init(){
      this.renderColumnsSkeleton();
      this.scheduleRender();
      this.bindUi();
      this.startPolling();
    }

    resolveInterval(){
      const raw = Number(this.config.refreshMs || 0);
      const minInterval = 1500;
      if (!Number.isFinite(raw) || raw < minInterval) {
        return minInterval;
      }
      return raw;
    }

    bindUi(){
      this.rangeButtons.forEach(btn=>{
        btn.addEventListener('click', ()=>{
          this.rangeButtons.forEach(b=>b.classList.remove('kds-btn-primary'));
          btn.classList.add('kds-btn-primary');
          this.state.range = btn.dataset.range || 'today';
          this.scheduleRender();
          this.fetchData();
        });
      });
      if (this.rangeButtons.length) {
        this.rangeButtons[0].classList.add('kds-btn-primary');
      }
      if (this.searchInput) {
        this.searchInput.addEventListener('input', () => {
          this.state.search = this.searchInput.value.trim().toLowerCase();
          this.scheduleRender();
        });
      }
      if (this.refreshBtn) {
        this.refreshBtn.addEventListener('click', () => this.fetchData({forceFull: true}));
      }
      if (this.toggleCanceledBtn) {
        this.toggleCanceledBtn.addEventListener('click', () => {
          if (this.toggleCanceledBtn.disabled) return;
          const visible = this.toggleCanceledBtn.dataset.visible === '1';
          this.toggleCanceledBtn.dataset.visible = visible ? '0' : '1';
          this.updateCanceledVisibility(!visible);
        });
      }
    }

    startPolling(){
      this.pollInterval = this.resolveInterval();
      this.fetchData();
      if (this.pollTimer) clearInterval(this.pollTimer);
      this.pollTimer = setInterval(() => this.fetchData(), this.pollInterval);

      // Pausar polling quando aba está em background para economizar CPU
      document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
          this.pausePolling();
        } else {
          this.resumePolling();
        }
      });
    }

    pausePolling(){
      if (this.pollTimer) {
        clearInterval(this.pollTimer);
        this.pollTimer = null;
      }
    }

    resumePolling(){
      if (!this.pollTimer) {
        this.fetchData({ forceFull: true }); // Refresh completo ao voltar
        this.pollTimer = setInterval(() => this.fetchData(), this.pollInterval);
      }
    }

    fetchData(options = {}){
      if (!this.config.dataUrl) return;
      if (this.isFetching) return;
      const forceFull = options && options.forceFull === true;
      const previousPending = this.knownPending ? new Set(this.knownPending) : new Set();
      let endpoint = this.config.dataUrl;
      if (!forceFull && this.lastSyncToken) {
        const separator = endpoint.includes('?') ? '&' : '?';
        endpoint = `${endpoint}${separator}since=${encodeURIComponent(this.lastSyncToken)}`;
      }
      this.isFetching = true;
      fetch(endpoint, {credentials:'include', cache:'no-store'})
        .then(r => r.ok ? r.json() : Promise.reject(new Error('fetch_failed')))
        .then(data => {
          const orders = Array.isArray(data.orders) ? data.orders : [];
          const removedIds = Array.isArray(data.removed_ids) ? data.removed_ids : [];
          const fullRefresh = forceFull || !!data.full_refresh;
          const next = fullRefresh ? new Map() : new Map(this.state.orders);
          orders.forEach(order => {
            if (!order) return;
            const idKey = this.orderKey(order.id ?? order.order_id ?? 0);
            if (idKey <= 0) return;
            const existing = next.get(idKey) || null;
            const normalized = this.normalizeOrder(order, existing);
            if (normalized.id > 0) {
              next.set(normalized.id, normalized);
            }
          });
          removedIds.forEach(id => {
            const key = this.orderKey(id);
            if (key > 0) {
              next.delete(key);
            }
          });
          this.state.orders = next;
          this.detectNewPending(previousPending, next);
          const syncHint = (typeof data.sync_token === 'string' && data.sync_token.trim())
            ? data.sync_token.trim()
            : (typeof data.server_time === 'string' && data.server_time.trim() ? data.server_time.trim() : null);
          this.updateSyncTokenFromState(syncHint);
          this.scheduleRender();
        })
        .catch(()=>{})
        .finally(() => {
          this.isFetching = false;
        });
    }

    orderKey(value){
      const num = Number(value);
      return Number.isFinite(num) ? Math.trunc(num) : 0;
    }

    computeLatestToken(){
      let latest = 0;
      this.state.orders.forEach(order => {
        ['status_changed_at', 'updated_at', 'created_at'].forEach(field => {
          const value = order[field];
          if (!value) return;
          const ts = Date.parse(value);
          if (Number.isFinite(ts) && ts > latest) {
            latest = ts;
          }
        });
      });
      return latest ? new Date(latest).toISOString() : null;
    }

    updateSyncTokenFromState(token){
      let candidate = this.lastSyncToken || null;
      if (typeof token === 'string' && token.trim()) {
        candidate = token.trim();
      }
      const computed = this.computeLatestToken();
      if (computed) {
        if (!candidate) {
          candidate = computed;
        } else {
          const candidateTs = Date.parse(candidate);
          const computedTs = Date.parse(computed);
          const candidateValid = Number.isFinite(candidateTs);
          const computedValid = Number.isFinite(computedTs);
          if (!candidateValid && computedValid) {
            candidate = computed;
          } else if (candidateValid && computedValid && computedTs > candidateTs) {
            candidate = computed;
          }
        }
      }
      if (candidate) {
        this.lastSyncToken = candidate;
      }
    }

    collectPendingIds(source){
      const result = new Set();
      if (!source) return result;
      if (source instanceof Map) {
        source.forEach((order, id) => {
          const status = (order && order.status ? String(order.status) : '').toLowerCase();
          const lane = LANE_BY_STATUS[status] || (status || 'pending');
          const key = this.orderKey(id);
          if (lane === 'pending' && key > 0) {
            result.add(key);
          }
        });
        return result;
      }
      if (Array.isArray(source)) {
        source.forEach(order => {
          if (!order) return;
          const status = (order.status ? String(order.status) : '').toLowerCase();
          const lane = LANE_BY_STATUS[status] || (status || 'pending');
          if (lane !== 'pending') return;
          const idKey = this.orderKey(order.id ?? order.order_id ?? 0);
          if (idKey > 0) {
            result.add(idKey);
          }
        });
      }
      return result;
    }

    syncKnownPending(){
      const orders = this.state && this.state.orders ? this.state.orders : null;
      this.knownPending = this.collectPendingIds(orders);
    }

    detectNewPending(previousSet, nextMap){
      const nextSet = this.collectPendingIds(nextMap);
      this.knownPending = nextSet;
      if (!nextSet || nextSet.size === 0) return;
      let hasNew = false;
      let newCount = 0;
      nextSet.forEach(id => {
        if (!previousSet || !previousSet.has(id)) {
          hasNew = true;
          newCount++;
        }
      });
      if (hasNew && this.chime) {
        this.chime.ring();
        this.showNotification(`${newCount} novo${newCount === 1 ? '' : 's'} pedido${newCount === 1 ? '' : 's'} recebido${newCount === 1 ? '' : 's'}!`);
      }
    }

    showNotification(message) {
      // Remove notificação anterior se existir
      const existingNotification = document.querySelector('.kds-notification');
      if (existingNotification) {
        existingNotification.remove();
      }

      // Criar nova notificação
      const notification = document.createElement('div');
      notification.className = 'kds-notification';
      notification.textContent = message;
      document.body.appendChild(notification);

      // Remover após 5 segundos
      setTimeout(() => {
        if (notification.parentNode) {
          notification.remove();
        }
      }, 5000);
    }

    renderColumnsSkeleton(){
      this.columnsEl.innerHTML = '';
      this.columnRefs.clear();
      const columns = Array.isArray(this.config.columns) && this.config.columns.length
        ? this.config.columns
        : [
            {id:'pending', label:'Recebidos'},
            {id:'paid', label:'Preparando'},
            {id:'completed', label:'Prontos'}
          ];
      columns.forEach(col => {
        const section = document.createElement('section');
        section.className = 'kds-column';
        section.dataset.status = col.id;
        section.innerHTML = `
          <div class="kds-column-header">
            <h2>${col.label}</h2>
            <span class="kds-column-count" data-count="0">0 pedidos</span>
          </div>
          <div class="kds-list" id="kds-list-${col.id}"></div>
        `;
        const listEl = section.querySelector('.kds-list');
        const countEl = section.querySelector('.kds-column-count');
        this.columnRefs.set(col.id, {section, list: listEl, count: countEl});
        this.columnsEl.appendChild(section);
      });
    }

    scheduleRender(){
      if (this.renderRequested) return;
      this.renderRequested = true;
      const run = () => {
        this.renderRequested = false;
        this.renderAll();
      };
      if (window.requestAnimationFrame) {
        window.requestAnimationFrame(run);
      } else {
        setTimeout(run, 80);
      }
    }

    ingestOrder(raw){
      if (!raw) return;
      const idKey = this.orderKey(raw.id ?? raw.order_id ?? 0);
      if (idKey <= 0) return;
      const existing = this.state.orders.get(idKey) || null;
      const normalized = this.normalizeOrder(raw, existing);
      if (normalized.id > 0) {
        this.state.orders.set(normalized.id, normalized);
        this.syncKnownPending();
      }
    }

    normalizeOrder(order, previous = null){
      const result = previous ? {...previous} : {};

      const idKey = this.orderKey(order.id ?? result.id ?? 0);
      if (idKey > 0) {
        result.id = idKey;
      } else if (previous) {
        const prevKey = this.orderKey(previous.id ?? 0);
        result.id = prevKey > 0 ? prevKey : 0;
      } else {
        result.id = 0;
      }
      result.status = order.status || result.status || 'pending';
      result.created_at = order.created_at || result.created_at || null;
      result.updated_at = order.updated_at || result.updated_at || result.created_at || null;
      result.status_changed_at = order.status_changed_at || result.status_changed_at || result.updated_at || result.created_at || null;
      result.sla_deadline = order.sla_deadline || result.sla_deadline || null;

      result.customer_name = order.customer_name ?? result.customer_name ?? '';
      result.customer_phone = order.customer_phone ?? result.customer_phone ?? '';
      result.customer_address = order.customer_address ?? result.customer_address ?? '';
      result.notes = order.notes ?? result.notes ?? '';

      const numeric = (value, fallback) => {
        if (value === undefined || value === null || value === '') {
          return Number(fallback || 0);
        }
        const num = Number(value);
        return Number.isFinite(num) ? num : Number(fallback || 0);
      };

      result.total = numeric(order.total, result.total);
      result.subtotal = numeric(order.subtotal, result.subtotal);
      result.delivery_fee = numeric(order.delivery_fee, result.delivery_fee);
      result.discount = numeric(order.discount, result.discount);

      if (Array.isArray(order.items)) {
        result.items = order.items;
      } else if (!Array.isArray(result.items)) {
        result.items = [];
      }

      return result;
    }

    renderAll(){
      const groups = {
        pending: [],
        paid: [],
        completed: [],
        canceled: []
      };
      this.state.orders.forEach(order => {
        const status = order.status || 'pending';
        const lane = LANE_BY_STATUS[status] || 'pending';
        if (!groups[lane]) groups[lane] = [];
        groups[lane].push(order);
      });
      Object.keys(groups).forEach(status => {
        groups[status].sort((a,b)=>{
          const aTime = Date.parse(a.created_at || '') || 0;
          const bTime = Date.parse(b.created_at || '') || 0;
          return aTime - bTime;
        });
      });

      const filters = {
        range: this.state.range,
        search: this.state.search,
      };

      const now = Date.now();
      const slaMs = (minutes)=> minutes * 60000;
      const warningThreshold = slaMs(Math.max(5, (this.config.slaMinutes || 20) / 3));

      const applyFilters = (order) => {
        if (filters.search) {
          const haystack = `${order.customer_name || ''} ${order.customer_phone || ''} #${order.id}`.toLowerCase();
          if (!haystack.includes(filters.search)) return false;
        }
        if (filters.range === 'today' || filters.range === 'yesterday') {
          const created = Date.parse(order.created_at || '') || 0;
          if (!created) return false;
          const date = new Date(created);
          const today = new Date(); today.setHours(0,0,0,0);
          if (filters.range === 'today') {
            const tomorrow = new Date(today); tomorrow.setDate(tomorrow.getDate()+1);
            return date >= today && date < tomorrow;
          }
          if (filters.range === 'yesterday') {
            const yesterday = new Date(today); yesterday.setDate(yesterday.getDate()-1);
            return date >= yesterday && date < today;
          }
        }
        return true;
      };

      Object.entries(groups).forEach(([status, orders]) => {
        if (status === 'canceled') return;
        const refs = this.columnRefs.get(status);
        if (!refs || !refs.list || !refs.count) return;
        const container = refs.list;
        const header = refs.count;
        const filtered = orders.filter(applyFilters);
        header.textContent = `${filtered.length} pedido${filtered.length === 1 ? '' : 's'}`;
        if (!filtered.length) {
          container.innerHTML = '<div class="kds-empty">Nenhum pedido encontrado.</div>';
          return;
        }
        container.innerHTML = filtered.map(order => this.renderCard(order, now, warningThreshold)).join('');
      });

      const canceledOrders = (groups['canceled'] || []).filter(applyFilters);
      if (this.toggleCanceledBtn) {
        this.toggleCanceledBtn.dataset.count = canceledOrders.length;
        this.toggleCanceledBtn.textContent = canceledOrders.length
          ? (this.toggleCanceledBtn.dataset.visible === '1' ? `Ocultar cancelados (${canceledOrders.length})` : `Mostrar cancelados (${canceledOrders.length})`)
          : 'Sem cancelados';
        if (canceledOrders.length === 0) {
          this.toggleCanceledBtn.classList.add('cursor-not-allowed','text-slate-400');
          this.toggleCanceledBtn.disabled = true;
        } else {
          this.toggleCanceledBtn.classList.remove('cursor-not-allowed','text-slate-400');
          this.toggleCanceledBtn.disabled = false;
        }
      }

      if (this.toggleCanceledBtn && this.toggleCanceledBtn.dataset.visible === '1' && canceledOrders.length) {
        this.canceledList.innerHTML = canceledOrders.map(order => this.renderCanceled(order)).join('');
        if (this.canceledCount) {
          this.canceledCount.textContent = `${canceledOrders.length} pedido${canceledOrders.length === 1 ? '' : 's'}`;
        }
        this.canceledSection.classList.remove('hidden');
      } else {
        this.canceledSection.classList.add('hidden');
      }
    }

    renderCard(order, now, warningThreshold){
      const createdAt = order.created_at ? new Date(order.created_at) : null;
      const createdLabel = createdAt ? createdAt.toLocaleTimeString('pt-BR', {hour:'2-digit', minute:'2-digit'}) : '--:--';
      const elapsedMs = createdAt ? now - createdAt.getTime() : 0;
      const elapsedMinutes = Math.max(0, Math.floor(elapsedMs / 60000));
      const slaDeadline = order.sla_deadline ? new Date(order.sla_deadline) : null;
      let slaClass = 'sla-safe';
      let slaLabel = 'Dentro do SLA';
      if (slaDeadline) {
        const remaining = slaDeadline.getTime() - now;
        if (remaining <= 0) {
          slaClass = 'sla-now';
          slaLabel = 'Atrasado';
        } else if (remaining < warningThreshold) {
          slaClass = 'sla-warning';
          slaLabel = 'Próximo do limite';
        }
      }
      const transition = STATUS_FLOW[order.status];
      const advanceBtn = (transition && transition.next)
        ? `<button class="kds-btn kds-btn-primary" data-action="advance" data-id="${order.id}" data-status="${transition.next}">
             ${this.escape(transition.label)}
           </button>`
        : '';
      const cancelBtn = order.status !== 'canceled' && order.status !== 'completed'
        ? `<button class="kds-btn kds-btn-danger" data-action="cancel" data-id="${order.id}">
             Cancelar
           </button>`
        : '';
      const address = order.customer_address || order.address || '';
      const addressHtml = address ? `<span>Entrega: ${this.escape(address).replace(/\n/g, '<br>')}</span>` : '';
      const items = (order.items || []).map(item => `
        <li>
          <span><strong>${item.qty || item.quantity || 0}x</strong> ${this.escape(item.name ?? '')}</span>
          <span class="font-medium">${this.formatCurrency(item.line_total || item.total || 0)}</span>
        </li>`).join('');

      return `
        <article class="kds-card ${slaClass === 'sla-now' ? 'kds-alert-danger' : (slaClass === 'sla-warning' ? 'kds-alert-warning' : '')}" data-order="${order.id}" data-status="${order.status}">
          <div class="kds-card-header">
            <div>
              <h3>Pedido #${order.id}</h3>
              <div class="kds-meta">
                <span>Iniciado às <strong>${createdLabel}</strong> · ${elapsedMinutes} min atrás</span>
                ${order.customer_name ? `<span>Cliente: <strong>${this.escape(order.customer_name)}</strong></span>` : ''}
                ${order.customer_phone ? `<span>Telefone: ${this.escape(order.customer_phone)}</span>` : ''}
                ${addressHtml}
              </div>
            </div>
            <div class="text-right">
              <div class="kds-badge" data-status="${order.status}">
                ${STATUS_LABELS[order.status] || order.status}
              </div>
              <div class="kds-tag ${slaClass}">${slaLabel}</div>
              <div class="kds-total">${this.formatCurrency(order.total)}</div>
            </div>
          </div>
          <ul class="kds-items">${items || '<li>Nenhum item registrado.</li>'}</ul>
          ${order.notes ? `<div class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-xl p-3 whitespace-pre-line">${this.escape(order.notes)}</div>` : ''}
          <div class="kds-actions">
            ${advanceBtn}
            ${cancelBtn}
            <a class="kds-btn kds-btn-ghost" href="${this.orderDetailUrl(order.id)}" target="_blank">
              Detalhes
            </a>
          </div>
        </article>`;
    }

    renderCanceled(order){
      const address = order.customer_address || order.address || '';
      const addressHtml = address ? `<span>Entrega: ${this.escape(address).replace(/\n/g,'<br>')}</span>` : '';
      return `
        <article class="kds-card" data-order="${order.id}" style="opacity: 0.7;">
          <div class="kds-card-header">
            <div>
              <h3>Pedido #${order.id}</h3>
              <div class="kds-meta">
                ${order.customer_name ? `<span>Cliente: <strong>${this.escape(order.customer_name)}</strong></span>` : ''}
                ${order.customer_phone ? `<span>Telefone: ${this.escape(order.customer_phone)}</span>` : ''}
                ${addressHtml}
              </div>
            </div>
            <div class="text-right">
              <div class="kds-badge" style="background: #fee2e2; color: #dc2626;">
                Cancelado
              </div>
              <div class="kds-total" style="color: #dc2626;">${this.formatCurrency(order.total)}</div>
            </div>
          </div>
          <div class="kds-actions">
            <a class="kds-btn kds-btn-ghost" href="${this.orderDetailUrl(order.id)}" target="_blank">
              Ver detalhes
            </a>
          </div>
        </article>`;
    }

    updateCanceledVisibility(show){
      if (!this.toggleCanceledBtn) return;
      if (show) {
        this.toggleCanceledBtn.dataset.visible = '1';
        this.scheduleRender();
      } else {
        this.toggleCanceledBtn.dataset.visible = '0';
        this.canceledSection.classList.add('hidden');
        this.toggleCanceledBtn.textContent = this.toggleCanceledBtn.dataset.count > 0 ? `Mostrar cancelados (${this.toggleCanceledBtn.dataset.count})` : 'Sem cancelados';
      }
    }

    orderDetailUrl(id){
      if (this.config.orderDetailBase) {
        return this.config.orderDetailBase + id;
      }
      return `${window.location.origin}${window.location.pathname.replace(/\/kds.*/, '')}/orders/show?id=${id}`;
    }

    formatCurrency(value){
      try {
        return new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(value || 0);
      } catch {
        return 'R$ ' + Number(value || 0).toFixed(2).replace('.', ',');
      }
    }

    escape(value){
      if (value === undefined || value === null) return '';
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    handleAction(target){
      const action = target.dataset.action;
      const orderId = parseInt(target.dataset.id, 10);
      if (!action || !orderId) return;
      if (action === 'advance') {
        const status = target.dataset.status;
        this.updateStatus(orderId, status);
      }
      if (action === 'cancel') {
        if (!confirm('Cancelar este pedido?')) return;
        this.updateStatus(orderId, 'canceled');
      }
    }

    updateStatus(orderId, status){
      if (!this.config.statusUrl) return;
      fetch(this.config.statusUrl, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        credentials: 'include',
        body: JSON.stringify({order_id: orderId, status})
      }).then(r=>r.ok?r.json():Promise.reject())
        .then(resp => {
          if (resp && resp.order) {
            this.ingestOrder(resp.order);
            this.updateSyncTokenFromState();
            this.scheduleRender();
          }
        }).catch(()=>{
          alert('Não foi possível atualizar o status.');
        });
    }

    cleanup(){
      if (this.pollTimer) {
        clearInterval(this.pollTimer);
        this.pollTimer = null;
      }
      if (this.chime && typeof this.chime.dispose === 'function') {
        this.chime.dispose();
      }
      this.chime = null;
      // Restore global admin toasts if they were suppressed on the KDS page
      try {
        if (window.__kds_toast_disabled && window.__original_showToast_for_kds) {
          try { window.showToast = window.__original_showToast_for_kds; } catch(e) { window.showToast = window.__original_showToast_for_kds; }
        }
      } catch (e) {}
      // Remove visual hide style if injected
      try {
        const s = document.getElementById('__kds_hide_admin_toasts');
        if (s && s.parentNode) s.parentNode.removeChild(s);
      } catch (e) {}
      try {
        const si = document.getElementById('__kds_hide_admin_toasts_inline');
        if (si && si.parentNode) si.parentNode.removeChild(si);
      } catch (e) {}
    }
  }

  document.addEventListener('click', (evt) => {
    const btn = evt.target.closest('[data-action]');
    if (!btn) return;
    if (!window.__kdsInstance) return;
    evt.preventDefault();
    window.__kdsInstance.handleAction(btn);
  });

  window.addEventListener('DOMContentLoaded', () => {
    window.__kdsInstance = new KdsRealtime(CONFIG || {}, INITIAL_ORDERS || []);
    window.__kdsInstance.init();
    // Hide global admin toasts visually while KDS is active
    try {
      if (!document.getElementById('__kds_hide_admin_toasts')) {
        const s = document.createElement('style');
        s.id = '__kds_hide_admin_toasts';
        s.textContent = '#admin-order-toasts { display: none !important; }';
        document.head.appendChild(s);
      }
    } catch (e) {}
    document.querySelectorAll('[data-kds-nav]').forEach(link => {
      if (link.dataset.cleanupBound) return;
      link.dataset.cleanupBound = '1';
      link.addEventListener('click', (evt) => {
        const href = link.getAttribute('href');
        if (!href) return;
        evt.preventDefault();
        window.__kdsInstance?.cleanup();
        setTimeout(() => { window.location.href = href; }, 30);
      });
    });
  });

  window.addEventListener('beforeunload', () => {
    window.__kdsInstance?.cleanup();
  });

  window.addEventListener('pagehide', () => {
    window.__kdsInstance?.cleanup();
  });

  window.addEventListener('popstate', () => {
    window.__kdsInstance?.cleanup();
  });
})();
</script>
<script>
  // Disable the global admin popup toasts on the KDS page only.
  // We replace window.showToast with a noop as soon as it becomes available.
  (function disableAdminToastsOnKds(){
    if (window.__kds_disable_admin_toasts_done) return;
    window.__kds_disable_admin_toasts_done = false;

    const replaceOnce = () => {
      try {
        if (window.showToast && !window.__kds_toast_disabled) {
          window.__kds_toast_disabled = true;
          // Keep a reference to original in case debugging is needed
          try { window.__original_showToast_for_kds = window.showToast; } catch(e){}
          window.showToast = function(){ /* suppressed on KDS */ };
          window.__kds_disable_admin_toasts_done = true;
          return true;
        }
      } catch (e) {}
      return false;
    };

    if (!replaceOnce()) {
      const interval = setInterval(() => {
        if (replaceOnce()) {
          clearInterval(interval);
        }
      }, 120);
      // stop trying after a short timeout
      setTimeout(() => clearInterval(interval), 3000);
    }
  })();
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
