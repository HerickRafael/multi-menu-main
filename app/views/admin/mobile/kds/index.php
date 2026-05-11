<?php
/**
 * KDS Mobile — Kitchen Display System touch-optimized
 * 
 * Usa tabs em vez de 3 colunas (layout mobile).
 * Polling + delta sync + chime idênticos ao desktop.
 *
 * @var array $company
 * @var array $u
 * @var array $initialSnapshot
 * @var array $kdsConfig
 * @var string $pageTitle
 * @var string $activeNav
 */

$initialSnapshot = is_array($initialSnapshot ?? null) ? $initialSnapshot : [];
$kdsConfig       = is_array($kdsConfig ?? null) ? $kdsConfig : [];
$initialJson = json_encode($initialSnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$configJson  = json_encode($kdsConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$getData = function($key, $default) use ($company) {
    return is_array($company) ? ($company[$key] ?? $default) : ($company->$key ?? $default);
};
$themeColor = $getData('menu_header_bg_color', $company['theme_color'] ?? '#4361ee');
?>

<style>
:root {
    --kds-primary: <?= htmlspecialchars($themeColor) ?>;
    --kds-pending: #f59e0b;
    --kds-paid: #3b82f6;
    --kds-completed: #10b981;
    --kds-canceled: #ef4444;
}

/* Tabs */
.kds-tabs {
    display: flex;
    background: #fff;
    border-radius: 1rem;
    padding: 0.25rem;
    margin: 0 0 0.75rem;
    gap: 0.25rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    position: sticky;
    top: 0;
    z-index: 20;
}

.kds-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.625rem 0.5rem;
    border-radius: 0.75rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #64748b;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    -webkit-tap-highlight-color: transparent;
}

.kds-tab.active {
    color: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.kds-tab[data-tab="pending"].active { background: var(--kds-pending); }
.kds-tab[data-tab="paid"].active    { background: var(--kds-paid); }
.kds-tab[data-tab="completed"].active { background: var(--kds-completed); }

.kds-tab-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.25rem;
    height: 1.25rem;
    padding: 0 0.375rem;
    border-radius: 9999px;
    font-size: 0.6875rem;
    font-weight: 700;
    background: #e2e8f0;
    color: #475569;
}
.kds-tab.active .kds-tab-badge {
    background: rgba(255,255,255,0.3);
    color: #fff;
}

/* Controls */
.kds-controls {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    flex-wrap: wrap;
}

.kds-range-btn {
    display: inline-flex;
    align-items: center;
    padding: 0.4375rem 0.75rem;
    border-radius: 0.625rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #475569;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
}

.kds-range-btn.active {
    background: var(--kds-primary);
    color: #fff;
    border-color: var(--kds-primary);
}

.kds-search {
    flex: 1;
    min-width: 0;
    padding: 0.4375rem 0.75rem;
    border-radius: 0.625rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    font-size: 0.8125rem;
    color: #1e293b;
}

.kds-search:focus {
    outline: none;
    border-color: var(--kds-primary);
    box-shadow: 0 0 0 2px rgba(67,97,238,0.15);
}

/* List Panel */
.kds-panel {
    display: none;
    flex-direction: column;
    gap: 0.75rem;
    min-height: 50vh;
}

.kds-panel.active {
    display: flex;
}

/* Card */
.kds-card {
    background: #fff;
    border-radius: 1rem;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border: 1px solid #e2e8f0;
    transition: border-color 0.2s;
}

.kds-card.sla-warning {
    border-color: var(--kds-pending);
}

.kds-card.sla-danger {
    border-color: var(--kds-canceled);
    animation: pulse-border 2s ease-in-out infinite;
}

@keyframes pulse-border {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0); }
    50% { box-shadow: 0 0 0 3px rgba(239,68,68,0.15); }
}

.kds-card-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.625rem;
}

.kds-order-num {
    font-size: 0.9375rem;
    font-weight: 700;
    color: #0f172a;
}

.kds-card-meta {
    font-size: 0.75rem;
    color: #64748b;
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    margin-bottom: 0.5rem;
}

.kds-card-meta strong { color: #0f172a; }

.kds-sla-tag {
    display: inline-flex;
    align-items: center;
    padding: 0.1875rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.6875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.kds-sla-tag.safe    { background: #dcfce7; color: #15803d; }
.kds-sla-tag.warning { background: #fef3c7; color: #d97706; }
.kds-sla-tag.danger  { background: #fee2e2; color: #dc2626; }

.kds-total {
    font-size: 0.9375rem;
    font-weight: 700;
    color: #0f172a;
    text-align: right;
}

/* Items */
.kds-items {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.625rem;
    padding: 0.625rem 0.75rem;
    margin: 0.5rem 0;
    list-style: none;
}

.kds-items li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.375rem 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.8125rem;
    color: #374151;
}

.kds-items li:last-child { border-bottom: none; }
.kds-items li strong { color: var(--kds-primary); font-weight: 700; }

.kds-notes {
    font-size: 0.75rem;
    color: #92400e;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 0.5rem;
    padding: 0.5rem 0.625rem;
    margin-top: 0.5rem;
    white-space: pre-line;
}

/* Actions */
.kds-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.75rem;
}

.kds-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.625rem 0.75rem;
    border-radius: 0.625rem;
    font-size: 0.8125rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    transition: opacity 0.15s;
}

.kds-btn:active { opacity: 0.85; transform: scale(0.98); }

.kds-btn-advance {
    background: var(--kds-primary);
    color: #fff;
}

.kds-btn-cancel {
    background: #fff;
    border: 1px solid #fca5a5;
    color: #dc2626;
}

.kds-btn-detail {
    background: #f1f5f9;
    color: #475569;
    flex: 0;
    padding: 0.625rem;
}

/* Empty state */
.kds-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
    text-align: center;
    color: #94a3b8;
    font-size: 0.875rem;
    gap: 0.5rem;
}

.kds-empty svg {
    width: 48px;
    height: 48px;
    stroke: #cbd5e1;
}

/* Notification toast */
.kds-toast {
    position: fixed;
    top: 0.75rem;
    left: 50%;
    transform: translateX(-50%);
    background: #0f172a;
    color: #fff;
    padding: 0.625rem 1rem;
    border-radius: 0.75rem;
    font-size: 0.8125rem;
    font-weight: 600;
    z-index: 200;
    animation: kds-slide-down 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    max-width: 90vw;
    text-align: center;
}

@keyframes kds-slide-down {
    from { transform: translateX(-50%) translateY(-100%); opacity: 0; }
    to   { transform: translateX(-50%) translateY(0); opacity: 1; }
}

/* Canceled toggle */
.kds-canceled-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.5rem;
    margin-top: 0.75rem;
    background: #fff;
    border: 1px solid #fca5a5;
    border-radius: 0.75rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #dc2626;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
}

.kds-canceled-cards {
    display: none;
    flex-direction: column;
    gap: 0.75rem;
    margin-top: 0.75rem;
}

.kds-canceled-cards.visible { display: flex; }

.kds-canceled-cards .kds-card { opacity: 0.65; }
</style>

<?php ob_start(); ?>

<!-- Tabs -->
<div class="kds-tabs" id="kds-tabs">
    <button class="kds-tab active" data-tab="pending">
        Recebidos <span class="kds-tab-badge" id="cnt-pending">0</span>
    </button>
    <button class="kds-tab" data-tab="paid">
        Preparando <span class="kds-tab-badge" id="cnt-paid">0</span>
    </button>
    <button class="kds-tab" data-tab="completed">
        Prontos <span class="kds-tab-badge" id="cnt-completed">0</span>
    </button>
</div>

<!-- Controls -->
<div class="kds-controls" id="kds-controls">
    <button class="kds-range-btn active" data-range="today">Hoje</button>
    <button class="kds-range-btn" data-range="yesterday">Ontem</button>
    <button class="kds-range-btn" data-range="all">Todos</button>
    <input type="search" class="kds-search" id="kds-search" placeholder="Buscar #, cliente…">
</div>

<!-- Panels -->
<div class="kds-panel active" id="panel-pending"></div>
<div class="kds-panel" id="panel-paid"></div>
<div class="kds-panel" id="panel-completed"></div>

<!-- Canceled toggle -->
<button class="kds-canceled-toggle" id="kds-canceled-btn" style="display:none;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="5" width="22" height="14" rx="7" ry="7"/><circle cx="8" cy="12" r="3"/></svg>
    Cancelados (<span id="cnt-canceled">0</span>)
</button>
<div class="kds-canceled-cards" id="kds-canceled-list"></div>

<script>
(function(){
  var CONFIG = <?= $configJson ?: '{}' ?>;
  var INITIAL = <?= $initialJson ?: '[]' ?>;

  var STATUS_FLOW = {
    pending:   { next: 'paid',      label: 'Iniciar preparo' },
    paid:      { next: 'completed', label: 'Marcar pronto' },
    completed: null,
    canceled:  null
  };
  var STATUS_LABELS = { pending:'Recebido', paid:'Preparando', completed:'Pronto', canceled:'Cancelado' };
  var LANE = { pending:'pending', paid:'paid', completed:'completed', canceled:'canceled' };

  /* ========= Chime (same as desktop, simplified) ========= */
  var DEFAULT_BELL = 'data:audio/wav;base64,UklGRjQrAABXQVZFZm10IBAAAAABAAEAIlYAAESsAAACABAAZGF0YRArAAAAAAA=';

  function KdsChime(bellUrl){
    this.AC = window.AudioContext || window.webkitAudioContext || null;
    this.bellUrl = (bellUrl && bellUrl.trim()) ? bellUrl.trim() : '';
    this.ctx = null;
    this.unlocked = false;
    this.pending = false;
    this.audioEl = null;
    this.audioFailed = false;
    this.loopId = null;

    var self = this;
    var unlock = function(){
      if (self.unlocked) return;
      self.unlocked = true;
      document.removeEventListener('touchstart', unlock);
      document.removeEventListener('pointerdown', unlock);
      if (self.pending) self.ring();
    };
    document.addEventListener('touchstart', unlock, {passive:true});
    document.addEventListener('pointerdown', unlock, {passive:true});

    document.addEventListener('visibilitychange', function(){
      if (document.hidden && self.pending) {
        self.startLoop();
      } else if (!document.hidden) {
        self.stopLoop();
      }
    });
  }

  KdsChime.prototype.ring = function(){
    if (!this.unlocked) { this.pending = true; return; }
    this.pending = false;
    this.playOnce();
  };

  KdsChime.prototype.startLoop = function(){
    if (this.loopId) return;
    var self = this;
    this.playOnce();
    this.loopId = setInterval(function(){ self.playOnce(); }, 5000);
  };

  KdsChime.prototype.stopLoop = function(){
    if (this.loopId) { clearInterval(this.loopId); this.loopId = null; }
  };

  KdsChime.prototype.playOnce = function(){
    if (this.bellUrl && !this.audioFailed) {
      if (!this.audioEl) {
        this.audioEl = new Audio();
        this.audioEl.preload = 'auto';
        this.audioEl.src = this.bellUrl;
        this.audioEl.volume = 0.8;
        var self = this;
        this.audioEl.addEventListener('error', function(){ self.audioFailed = true; }, {once:true});
      }
      try { this.audioEl.currentTime = 0; } catch(e){}
      var p = this.audioEl.play();
      if (p && p.catch) p.catch(function(){});
      if (!this.audioFailed) return;
    }
    // fallback Web Audio tone
    if (!this.AC) return;
    if (!this.ctx) {
      try { this.ctx = new this.AC(); } catch(e){ this.AC = null; return; }
    }
    try {
      var c = this.ctx, now = c.currentTime;
      if (c.state === 'suspended') c.resume();
      var osc = c.createOscillator(), g = c.createGain();
      osc.type = 'triangle'; osc.frequency.setValueAtTime(880, now);
      g.gain.setValueAtTime(0.0001, now);
      g.gain.exponentialRampToValueAtTime(0.32, now+0.02);
      g.gain.exponentialRampToValueAtTime(0.0001, now+0.7);
      osc.connect(g); g.connect(c.destination);
      osc.start(now); osc.stop(now+0.72);
    } catch(e){}
  };

  KdsChime.prototype.dispose = function(){
    this.stopLoop();
    if (this.ctx) try { this.ctx.close(); } catch(e){}
    if (this.audioEl) try { this.audioEl.pause(); } catch(e){}
  };

  /* ========= KDS Realtime engine ========= */
  function KdsEngine(cfg, initial){
    this.cfg = cfg || {};
    this.orders = {};
    this.tab = 'pending';
    this.range = 'today';
    this.search = '';
    this.syncToken = null;
    this.fetching = false;
    this.pollId = null;
    this.knownPending = {};
    this.chime = new KdsChime(cfg.bellUrl || '');

    var self = this;
    (initial || []).forEach(function(o){ self.ingest(o); });
    this.syncKnown();
  }

  KdsEngine.prototype.init = function(){
    this.bindUI();
    this.render();
    this.startPolling();
  };

  KdsEngine.prototype.bindUI = function(){
    var self = this;
    // Tabs
    var tabs = document.querySelectorAll('#kds-tabs .kds-tab');
    for (var i = 0; i < tabs.length; i++) {
      (function(tab){
        tab.addEventListener('click', function(){
          for (var j = 0; j < tabs.length; j++) tabs[j].classList.remove('active');
          tab.classList.add('active');
          self.tab = tab.dataset.tab;
          self.showPanel();
        });
      })(tabs[i]);
    }
    // Range
    var rangeBtns = document.querySelectorAll('#kds-controls .kds-range-btn');
    for (var i = 0; i < rangeBtns.length; i++) {
      (function(btn){
        btn.addEventListener('click', function(){
          for (var j = 0; j < rangeBtns.length; j++) rangeBtns[j].classList.remove('active');
          btn.classList.add('active');
          self.range = btn.dataset.range;
          self.render();
        });
      })(rangeBtns[i]);
    }
    // Search
    var searchEl = document.getElementById('kds-search');
    if (searchEl) {
      searchEl.addEventListener('input', function(){
        self.search = searchEl.value.trim().toLowerCase();
        self.render();
      });
    }
    // Canceled toggle
    var canceledBtn = document.getElementById('kds-canceled-btn');
    if (canceledBtn) {
      canceledBtn.addEventListener('click', function(){
        var list = document.getElementById('kds-canceled-list');
        list.classList.toggle('visible');
      });
    }
    // Delegate action clicks
    document.addEventListener('click', function(e){
      var btn = e.target.closest('[data-action]');
      if (!btn) return;
      e.preventDefault();
      self.handleAction(btn);
    });
  };

  KdsEngine.prototype.showPanel = function(){
    var panels = document.querySelectorAll('.kds-panel');
    for (var i = 0; i < panels.length; i++) panels[i].classList.remove('active');
    var p = document.getElementById('panel-' + this.tab);
    if (p) p.classList.add('active');
  };

  KdsEngine.prototype.startPolling = function(){
    var ms = Math.max(1500, Number(this.cfg.refreshMs) || 1500);
    var self = this;
    this.fetch();
    this.pollId = setInterval(function(){ self.fetch(); }, ms);

    document.addEventListener('visibilitychange', function(){
      if (document.hidden) {
        if (self.pollId) { clearInterval(self.pollId); self.pollId = null; }
      } else {
        self.fetch(true);
        self.pollId = setInterval(function(){ self.fetch(); }, ms);
      }
    });
  };

  KdsEngine.prototype.fetch = function(forceFull){
    if (!this.cfg.dataUrl || this.fetching) return;
    this.fetching = true;
    var url = this.cfg.dataUrl;
    if (!forceFull && this.syncToken) {
      url += (url.indexOf('?') >= 0 ? '&' : '?') + 'since=' + encodeURIComponent(this.syncToken);
    }
    var prevPending = {};
    for (var k in this.knownPending) prevPending[k] = true;

    var self = this;
    window.fetch(url, {credentials:'include', cache:'no-store'})
      .then(function(r){ return r.ok ? r.json() : Promise.reject(); })
      .then(function(data){
        var orders = Array.isArray(data.orders) ? data.orders : [];
        var removed = Array.isArray(data.removed_ids) ? data.removed_ids : [];
        var full = forceFull || !!data.full_refresh;
        if (full) self.orders = {};
        orders.forEach(function(o){ if (o) self.ingest(o); });
        removed.forEach(function(id){ delete self.orders[Number(id)]; });
        self.detectNew(prevPending);
        if (data.sync_token) self.syncToken = data.sync_token;
        else if (data.server_time) self.syncToken = data.server_time;
        self.render();
      })
      .catch(function(){})
      .then(function(){ self.fetching = false; });
  };

  KdsEngine.prototype.ingest = function(raw){
    if (!raw) return;
    var id = Number(raw.id || raw.order_id || 0);
    if (id <= 0) return;
    var prev = this.orders[id] || {};
    var o = {};
    o.id = id;
    o.order_number = raw.order_number || prev.order_number || id;
    o.status = raw.status || prev.status || 'pending';
    o.created_at = raw.created_at || prev.created_at || null;
    o.updated_at = raw.updated_at || prev.updated_at || null;
    o.status_changed_at = raw.status_changed_at || prev.status_changed_at || null;
    o.sla_deadline = raw.sla_deadline || prev.sla_deadline || null;
    o.customer_name = raw.customer_name != null ? raw.customer_name : (prev.customer_name || '');
    o.customer_phone = raw.customer_phone != null ? raw.customer_phone : (prev.customer_phone || '');
    o.customer_address = raw.customer_address != null ? raw.customer_address : (prev.customer_address || '');
    o.notes = raw.notes != null ? raw.notes : (prev.notes || '');
    o.total = Number(raw.total != null ? raw.total : (prev.total || 0));
    o.subtotal = Number(raw.subtotal != null ? raw.subtotal : (prev.subtotal || 0));
    o.delivery_fee = Number(raw.delivery_fee != null ? raw.delivery_fee : (prev.delivery_fee || 0));
    o.discount = Number(raw.discount != null ? raw.discount : (prev.discount || 0));
    o.items = Array.isArray(raw.items) ? raw.items : (prev.items || []);
    this.orders[id] = o;
  };

  KdsEngine.prototype.syncKnown = function(){
    this.knownPending = {};
    for (var id in this.orders) {
      if ((this.orders[id].status || 'pending') === 'pending') this.knownPending[id] = true;
    }
  };

  KdsEngine.prototype.detectNew = function(prev){
    var newCount = 0;
    this.knownPending = {};
    for (var id in this.orders) {
      if ((this.orders[id].status || 'pending') === 'pending') {
        this.knownPending[id] = true;
        if (!prev[id]) newCount++;
      }
    }
    if (newCount > 0) {
      this.chime.ring();
      this.toast(newCount + ' novo' + (newCount > 1 ? 's' : '') + ' pedido' + (newCount > 1 ? 's' : '') + '!');
    }
  };

  KdsEngine.prototype.toast = function(msg){
    var old = document.querySelector('.kds-toast');
    if (old) old.remove();
    var el = document.createElement('div');
    el.className = 'kds-toast';
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(function(){ if (el.parentNode) el.remove(); }, 4000);
  };

  KdsEngine.prototype.filter = function(o){
    if (this.search) {
      var h = ((o.customer_name || '') + ' ' + (o.customer_phone || '') + ' #' + o.id).toLowerCase();
      if (h.indexOf(this.search) < 0) return false;
    }
    if (this.range === 'today' || this.range === 'yesterday') {
      var ts = Date.parse(o.created_at || '');
      if (!ts) return false;
      var d = new Date(ts);
      var today = new Date(); today.setHours(0,0,0,0);
      if (this.range === 'today') {
        var tmrw = new Date(today); tmrw.setDate(tmrw.getDate()+1);
        return d >= today && d < tmrw;
      }
      var yest = new Date(today); yest.setDate(yest.getDate()-1);
      return d >= yest && d < today;
    }
    return true;
  };

  KdsEngine.prototype.render = function(){
    var groups = { pending:[], paid:[], completed:[], canceled:[] };
    var now = Date.now();
    var slaMs = (this.cfg.slaMinutes || 20) * 60000;
    var warnMs = Math.max(300000, slaMs / 3);

    for (var id in this.orders) {
      var o = this.orders[id];
      var lane = LANE[o.status] || 'pending';
      if (!groups[lane]) groups[lane] = [];
      groups[lane].push(o);
    }
    for (var s in groups) {
      groups[s].sort(function(a,b){
        return (Date.parse(a.created_at||'')||0) - (Date.parse(b.created_at||'')||0);
      });
    }

    var lanes = ['pending','paid','completed'];
    var self = this;
    lanes.forEach(function(lane){
      var panel = document.getElementById('panel-' + lane);
      var badge = document.getElementById('cnt-' + lane);
      if (!panel) return;

      var filtered = groups[lane].filter(function(o){ return self.filter(o); });
      if (badge) badge.textContent = filtered.length;

      if (!filtered.length) {
        panel.innerHTML = '<div class="kds-empty">' +
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
          '<span>Nenhum pedido</span></div>';
        return;
      }

      var html = '';
      filtered.forEach(function(o){
        html += self.cardHtml(o, now, warnMs);
      });
      panel.innerHTML = html;
    });

    // Canceled
    var canceledFiltered = groups.canceled.filter(function(o){ return self.filter(o); });
    var cntEl = document.getElementById('cnt-canceled');
    var cBtn = document.getElementById('kds-canceled-btn');
    var cList = document.getElementById('kds-canceled-list');
    if (cntEl) cntEl.textContent = canceledFiltered.length;
    if (cBtn) cBtn.style.display = canceledFiltered.length ? '' : 'none';
    if (cList && cList.classList.contains('visible')) {
      if (canceledFiltered.length) {
        var ch = '';
        canceledFiltered.forEach(function(o){ ch += self.canceledHtml(o); });
        cList.innerHTML = ch;
      } else {
        cList.innerHTML = '';
        cList.classList.remove('visible');
      }
    }
  };

  KdsEngine.prototype.cardHtml = function(o, now, warnMs){
    var created = o.created_at ? new Date(o.created_at) : null;
    var timeLabel = created ? created.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'}) : '--:--';
    var elapsed = created ? Math.max(0, Math.floor((now - created.getTime()) / 60000)) : 0;

    var slaDeadline = o.sla_deadline ? new Date(o.sla_deadline) : null;
    var slaClass = 'safe', slaLabel = 'No prazo';
    if (slaDeadline) {
      var remaining = slaDeadline.getTime() - now;
      if (remaining <= 0) { slaClass = 'danger'; slaLabel = 'Atrasado'; }
      else if (remaining < warnMs) { slaClass = 'warning'; slaLabel = 'Quase'; }
    }

    var cardClass = slaClass === 'danger' ? 'sla-danger' : (slaClass === 'warning' ? 'sla-warning' : '');

    var transition = STATUS_FLOW[o.status];
    var advBtn = '';
    if (transition && transition.next) {
      advBtn = '<button class="kds-btn kds-btn-advance" data-action="advance" data-id="' + o.id + '" data-status="' + transition.next + '">' + esc(transition.label) + '</button>';
    }
    var cancelBtn = '';
    if (o.status !== 'canceled' && o.status !== 'completed') {
      cancelBtn = '<button class="kds-btn kds-btn-cancel" data-action="cancel" data-id="' + o.id + '">Cancelar</button>';
    }

    var items = '';
    (o.items || []).forEach(function(it){
      items += '<li><span><strong>' + (it.qty||it.quantity||0) + 'x</strong> ' + esc(it.name||'') + '</span><span>' + cur(it.line_total||it.total||0) + '</span></li>';
    });
    if (!items) items = '<li>Nenhum item</li>';

    var addr = o.customer_address || '';
    var addrHtml = addr ? '<span>📍 ' + esc(addr) + '</span>' : '';

    return '<article class="kds-card ' + cardClass + '" data-order="' + o.id + '">' +
      '<div class="kds-card-head">' +
        '<div>' +
          '<div class="kds-order-num">#' + (o.order_number || o.id) + '</div>' +
          '<div class="kds-card-meta">' +
            '<span>⏱ ' + timeLabel + ' · ' + elapsed + ' min</span>' +
            (o.customer_name ? '<span>👤 <strong>' + esc(o.customer_name) + '</strong></span>' : '') +
            (o.customer_phone ? '<span>📱 ' + esc(o.customer_phone) + '</span>' : '') +
            addrHtml +
          '</div>' +
        '</div>' +
        '<div style="text-align:right">' +
          '<div class="kds-sla-tag ' + slaClass + '">' + slaLabel + '</div>' +
          '<div class="kds-total">' + cur(o.total) + '</div>' +
        '</div>' +
      '</div>' +
      '<ul class="kds-items">' + items + '</ul>' +
      (o.notes ? '<div class="kds-notes">' + esc(o.notes) + '</div>' : '') +
      '<div class="kds-actions">' + advBtn + cancelBtn +
        '<a class="kds-btn kds-btn-detail" href="' + (this.cfg.orderDetailBase||'/orders/show?id=') + o.id + '">' +
          '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke-linecap="round" stroke-linejoin="round"/><line x1="3" y1="6" x2="21" y2="6" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 10a4 4 0 01-8 0" stroke-linecap="round" stroke-linejoin="round"/></svg>
        '</a>' +
      '</div>' +
    '</article>';
  };

  KdsEngine.prototype.canceledHtml = function(o){
    return '<article class="kds-card" data-order="' + o.id + '">' +
      '<div class="kds-card-head"><div>' +
        '<div class="kds-order-num">#' + (o.order_number||o.id) + '</div>' +
        '<div class="kds-card-meta">' + (o.customer_name ? '<span>👤 ' + esc(o.customer_name) + '</span>' : '') + '</div>' +
      '</div><div style="text-align:right"><div class="kds-total" style="color:var(--kds-canceled)">' + cur(o.total) + '</div></div></div>' +
      '<div class="kds-actions"><a class="kds-btn kds-btn-detail" href="' + (this.cfg.orderDetailBase||'/orders/show?id=') + o.id + '">' +
        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke-linecap="round" stroke-linejoin="round"/><line x1="3" y1="6" x2="21" y2="6" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 10a4 4 0 01-8 0" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
      '</a></div></article>';
  };

  KdsEngine.prototype.handleAction = function(btn){
    var action = btn.dataset.action;
    var id = parseInt(btn.dataset.id, 10);
    if (!action || !id) return;
    if (action === 'advance') {
      this.updateStatus(id, btn.dataset.status);
    } else if (action === 'cancel') {
      if (!confirm('Cancelar este pedido?')) return;
      this.updateStatus(id, 'canceled');
    }
  };

  KdsEngine.prototype.updateStatus = function(id, status){
    if (!this.cfg.statusUrl) return;
    var self = this;
    window.fetch(this.cfg.statusUrl, {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      credentials: 'include',
      body: JSON.stringify({order_id: id, status: status})
    }).then(function(r){ return r.ok ? r.json() : Promise.reject(); })
      .then(function(resp){
        if (resp && resp.order) {
          self.ingest(resp.order);
          self.syncKnown();
          self.render();
        }
      })
      .catch(function(){ alert('Erro ao atualizar status.'); });
  };

  KdsEngine.prototype.cleanup = function(){
    if (this.pollId) { clearInterval(this.pollId); this.pollId = null; }
    if (this.chime) this.chime.dispose();
  };

  /* helpers */
  function esc(v){
    if (v == null) return '';
    return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function cur(v){
    try { return new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(v||0); }
    catch(e){ return 'R$ ' + Number(v||0).toFixed(2).replace('.',','); }
  }

  /* Boot */
  document.addEventListener('DOMContentLoaded', function(){
    window.__mobileKds = new KdsEngine(CONFIG, INITIAL);
    window.__mobileKds.init();
  });
  window.addEventListener('beforeunload', function(){
    if (window.__mobileKds) window.__mobileKds.cleanup();
  });
})();
</script>

<?php
$content = ob_get_clean();
$hideBottomNav = false;
include __DIR__ . '/../layout.php';
?>
