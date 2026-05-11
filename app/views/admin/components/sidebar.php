<?php
$sidebarData = is_array($sidebarData ?? null) ? $sidebarData : [];

$companySlug = (string)($sidebarData['companySlug'] ?? ($activeSlug ?? ($company['slug'] ?? '')));
$companyName = (string)($sidebarData['companyName'] ?? (trim((string)($company['name'] ?? '')) ?: 'Admin'));
$globalItems = is_array($sidebarData['globalItems'] ?? null) ? $sidebarData['globalItems'] : [];
$contextualItems = is_array($sidebarData['contextualItems'] ?? null) ? $sidebarData['contextualItems'] : [];
$contextLabel = (string)($sidebarData['contextLabel'] ?? 'Opções');
$sidebarBhStatus = is_array($sidebarData['sidebarBhStatus'] ?? null) ? $sidebarData['sidebarBhStatus'] : [
    'is_open' => false,
    'current_time' => '--:--',
    'today_hours' => 'Fechado hoje',
];
$sidebarHoursJson = (string)($sidebarData['sidebarHoursJson'] ?? '{}');
$dashboardUrl = (string)($sidebarData['dashboardUrl'] ?? base_url('admin/' . trim($companySlug, '/') . '/dashboard'));
$settingsUrl = (string)($sidebarData['settingsUrl'] ?? base_url('admin/' . trim($companySlug, '/') . '/settings'));
$logoutUrl = (string)($sidebarData['logoutUrl'] ?? base_url('admin/' . trim($companySlug, '/') . '/logout'));
$menuUrl = (string)($sidebarData['menuUrl'] ?? base_url($companySlug));
$sidebarDebugWarning = !empty($sidebarData['debug_warning']);
$sidebarDebugWarningLevel = (string)($sidebarData['debug_warning_level'] ?? 'warning');
$sidebarDebugWarningMessage = trim((string)($sidebarData['debug_warning_message'] ?? ''));
?>

<!-- Overlay para mobile -->
<script>
// Aplicar estado colapsado ANTES da renderização para evitar animação de fechamento
(function(){
  var c = localStorage.getItem('sidebar_collapsed') === 'true' && window.innerWidth >= 1024;
  if (c) document.documentElement.classList.add('sidebar-preload-collapsed');
})();
</script>
<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden transition-opacity duration-300" aria-hidden="true"></div>

<!-- Sidebar -->
<aside id="smart-sidebar" 
    class="fixed top-0 left-0 h-full flex flex-col bg-white border-r border-slate-200 z-40"
       data-collapsed="false">
    
    <!-- Header da Sidebar -->
    <div class="sidebar-header h-16 flex items-center justify-between px-4 border-b border-slate-100">
        <a href="<?= e($dashboardUrl) ?>" class="sidebar-logo flex items-center gap-3 overflow-hidden">
            <div class="w-9 h-9 rounded-xl overflow-hidden flex items-center justify-center flex-shrink-0">
                <img src="/assets/icons/admin/logo-multimenu.png" alt="Multi Menu" class="w-9 h-9" style="object-fit:cover;">
            </div>
            <span class="sidebar-text font-semibold text-slate-800 whitespace-nowrap transition-opacity duration-200">
                <?= e($companyName) ?>
            </span>
        </a>
        <button id="sidebar-toggle" 
                class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors"
                title="Alternar sidebar"
                aria-label="Alternar sidebar"
                aria-controls="smart-sidebar"
                aria-expanded="true">
            <svg class="w-5 h-5 sidebar-toggle-icon transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
            </svg>
        </button>
    </div>
    
    <!-- Navegação -->
    <nav class="flex-1 overflow-y-auto overflow-x-hidden py-4 sidebar-nav" role="navigation" aria-label="Navegação principal do admin">
        <!-- Itens Globais -->
        <div class="px-3 mb-6">
            <p class="sidebar-section-title text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2 px-3 transition-opacity duration-200">
                Menu Principal
            </p>
            <ul class="space-y-1">
                <?php foreach ($globalItems as $item): ?>
                <li>
                    <a href="<?= e($item['url']) ?>" 
                       class="sidebar-item group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 relative <?= !empty($item['is_active']) ? 'admin-sidebar-active' : (!empty($item['is_current_context']) ? 'admin-sidebar-context' : 'text-slate-600 admin-sidebar-item-hover') ?>"
                       title="<?= e($item['label']) ?>"
                       aria-current="<?= !empty($item['is_active']) ? 'page' : 'false' ?>">
                        <?php if (!empty($item['is_current_context']) && empty($item['is_active'])): ?>
                        <span class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 admin-sidebar-accent rounded-r-full"></span>
                        <?php endif; ?>
                        <span class="w-6 h-6 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <?= $item['icon'] ?>
                            </svg>
                        </span>
                        <span class="sidebar-text whitespace-nowrap transition-opacity duration-200"><?= e($item['label']) ?></span>
                        <?php if (!empty($item['badge'])): ?>
                        <span class="sidebar-text ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full animate-pulse"><?= e($item['badge']) ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- Itens Contextuais - Grupo Colapsável -->
        <div class="px-3 mb-6 <?= empty($contextualItems) ? 'hidden' : '' ?>" id="contextual-section">
            <button type="button" 
                    class="sidebar-group-toggle w-full flex items-center justify-between px-3 py-1.5 text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2 admin-sidebar-item-hover transition-colors"
                    aria-expanded="true"
                    aria-controls="contextual-items">
                <span class="sidebar-text transition-opacity duration-200"><?= e($contextLabel) ?></span>
                <svg class="sidebar-text w-4 h-4 transition-transform duration-200 group-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <ul class="space-y-1" id="contextual-items">
                <?php foreach ($contextualItems as $item): ?>
                <li>
                          <a href="<?= e($item['url']) ?>" 
                              class="sidebar-item group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors duration-150 relative <?= !empty($item['is_active']) ? 'admin-sidebar-contextual-active font-medium' : 'text-slate-600 admin-sidebar-item-hover' ?>"
                       title="<?= e($item['label']) ?>"
                       aria-current="<?= !empty($item['is_active']) ? 'page' : 'false' ?>">
                        <?php if (!empty($item['is_active'])): ?>
                        <span class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-5 admin-sidebar-accent rounded-r-full"></span>
                        <?php endif; ?>
                        <span class="w-6 h-6 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 transition-transform group-hover:scale-110 <?= !empty($item['is_active']) ? 'admin-primary-text' : '' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <?= $item['icon'] ?>
                            </svg>
                        </span>
                        <span class="sidebar-text whitespace-nowrap transition-opacity duration-200"><?= e($item['label']) ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php if ($sidebarDebugWarning): ?>
        <?php $isCritical = $sidebarDebugWarningLevel === 'critical'; ?>
        <div class="hidden lg:block px-3 mb-4">
            <div class="rounded-xl border px-3 py-2.5 <?= $isCritical ? 'border-red-400 bg-red-50 text-red-800 animate-pulse' : 'border-amber-300 bg-amber-50 text-amber-800' ?>">
                <p class="sidebar-text text-[11px] font-semibold uppercase tracking-wide flex items-center gap-1.5">
                    <?= $isCritical ? '<span class="inline-block w-2 h-2 rounded-full bg-red-500"></span> CRITICAL' : '<span class="inline-block w-2 h-2 rounded-full bg-amber-500"></span> WARNING' ?>
                </p>
                <?php if ($sidebarDebugWarningMessage !== ''): ?>
                <p class="sidebar-text mt-1 text-[11px] leading-4 <?= $isCritical ? 'text-red-700 font-medium' : 'text-amber-700' ?>">
                    <?= e($sidebarDebugWarningMessage) ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Bloco status da loja (desktop) -->
        <div class="hidden lg:block px-3 mb-4">
            <a href="<?= e($settingsUrl) ?>"
               id="sidebar-store-status-card"
               class="sidebar-store-status sidebar-item group block rounded-xl border border-slate-200 px-3 py-3 text-slate-600 transition-colors duration-200 hover:bg-slate-50 hover:text-slate-900"
               data-open="<?= $sidebarBhStatus['is_open'] ? '1' : '0' ?>"
               title="<?= $sidebarBhStatus['is_open'] ? 'Loja aberta' : 'Loja fechada' ?> — Horários">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="relative flex h-2.5 w-2.5 flex-shrink-0">
                            <span id="sidebar-store-dot-glow" class="absolute inline-flex h-full w-full rounded-full <?= $sidebarBhStatus['is_open'] ? 'bg-emerald-400' : 'bg-red-400' ?> opacity-60"></span>
                            <span id="sidebar-store-dot" class="relative inline-flex h-2.5 w-2.5 rounded-full <?= $sidebarBhStatus['is_open'] ? 'bg-emerald-500' : 'bg-red-500' ?>"></span>
                        </span>
                        <span class="sidebar-text truncate text-sm font-semibold text-slate-800">
                            Loja <span id="sidebar-store-open-state" class="<?= $sidebarBhStatus['is_open'] ? 'text-emerald-600' : 'text-red-600' ?>"><?= $sidebarBhStatus['is_open'] ? 'aberta' : 'fechada' ?></span>
                        </span>
                    </div>
                    <span class="sidebar-text inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 transition-colors group-hover:bg-slate-200">
                        Horários
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </span>
                </div>
                <p class="sidebar-text flex items-center gap-1.5 text-xs text-slate-500">
                    <svg class="h-3.5 w-3.5 flex-shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="M12 7v5l3 2" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                    <span id="sidebar-store-time-line" class="truncate"><?= e($sidebarBhStatus['current_time']) ?> · <?= e($sidebarBhStatus['today_hours']) ?></span>
                </p>
            </a>
        </div>
    </nav>
    
    <!-- Footer da Sidebar -->
    <div class="mt-auto border-t border-slate-100 p-3">
        <a href="<?= e($menuUrl) ?>" 
           target="_blank"
           class="sidebar-item group flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-all duration-200"
           title="Ver Cardápio">
            <span class="w-6 h-6 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                </svg>
            </span>
            <span class="sidebar-text whitespace-nowrap transition-opacity duration-200">Ver Cardápio</span>
        </a>
        <a href="<?= e($logoutUrl) ?>" 
           class="sidebar-item group flex items-center gap-3 px-3 py-2.5 rounded-xl text-red-600 hover:bg-red-50 transition-all duration-200"
           title="Sair">
            <span class="w-6 h-6 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                </svg>
            </span>
            <span class="sidebar-text whitespace-nowrap transition-opacity duration-200">Sair</span>
        </a>
    </div>
</aside>

<!-- Botão flutuante mobile para abrir sidebar -->
<button id="sidebar-mobile-toggle" 
        class="fixed bottom-4 left-4 z-50 lg:hidden w-12 h-12 rounded-full admin-gradient-bg text-white shadow-lg flex items-center justify-center hover:opacity-90 transition-all duration-200"
        aria-label="Abrir menu lateral"
        aria-controls="smart-sidebar"
        aria-expanded="false">
    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
    </svg>
</button>

<style>
/* Pré-carregar estado colapsado sem animação */
.sidebar-preload-collapsed #smart-sidebar {
    width: 72px !important;
    transition: none !important;
}
.sidebar-preload-collapsed .sidebar-main-content {
    margin-left: 72px !important;
    transition: none !important;
}
.sidebar-preload-collapsed #smart-sidebar .sidebar-text,
.sidebar-preload-collapsed #smart-sidebar .sidebar-section-title {
    opacity: 0 !important;
    max-width: 0 !important;
    overflow: hidden !important;
}

/* Transição do main content — só após carregamento */
.sidebar-main-content.transition-ready {
    transition: margin-left 0.3s ease-in-out;
}

/* Sidebar Styles */
#smart-sidebar {
    width: 260px;
    transition: width 0.3s ease-in-out;
}

.sidebar-text {
    max-width: 200px;
    transition: max-width 0.3s ease-in-out, opacity 0.2s ease-in-out;
}

#smart-sidebar[data-collapsed="true"] {
    width: 72px;
}

#smart-sidebar[data-collapsed="true"] .sidebar-text,
#smart-sidebar[data-collapsed="true"] .sidebar-section-title {
    opacity: 0;
    max-width: 0;
    overflow: hidden;
}

#smart-sidebar[data-collapsed="true"] .sidebar-store-status {
    display: none;
}

#smart-sidebar[data-collapsed="true"] .sidebar-toggle-icon {
    transform: rotate(180deg);
}

#smart-sidebar[data-collapsed="true"] .sidebar-header {
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: auto;
    padding: 12px 0;
    gap: 8px;
}

#smart-sidebar[data-collapsed="true"] .sidebar-logo {
    justify-content: center;
    gap: 0;
    overflow: visible;
    flex-shrink: 0;
    min-width: 36px;
    min-height: 36px;
}

#smart-sidebar[data-collapsed="true"] #sidebar-toggle {
    width: 36px;
    height: 28px;
}

/* Tooltip para modo colapsado */
#smart-sidebar[data-collapsed="true"] .sidebar-item {
    position: relative;
}

#smart-sidebar[data-collapsed="true"] .sidebar-item:hover::after,
#smart-sidebar[data-collapsed="true"] .sidebar-item:focus-visible::after {
    content: attr(title);
    position: absolute;
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    margin-left: 12px;
    padding: 6px 12px;
    background: #1e293b;
    color: white;
    font-size: 13px;
    font-weight: 500;
    border-radius: 8px;
    white-space: nowrap;
    z-index: 100;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

#smart-sidebar[data-collapsed="true"] .sidebar-item:hover::before,
#smart-sidebar[data-collapsed="true"] .sidebar-item:focus-visible::before {
    content: '';
    position: absolute;
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    margin-left: 4px;
    border: 6px solid transparent;
    border-right-color: #1e293b;
}

/* Collapsible group styles */
.sidebar-group-toggle[aria-expanded="false"] .group-chevron {
    transform: rotate(-90deg);
}

.sidebar-group-toggle[aria-expanded="false"] + ul {
    display: none;
}

.sidebar-group-toggle[aria-expanded="true"] + ul {
    display: block;
}

/* Hide collapsible section header when sidebar collapsed */
#smart-sidebar[data-collapsed="true"] .sidebar-group-toggle {
    display: none;
}

#smart-sidebar[data-collapsed="true"] #contextual-section {
    display: none;
}

/* Mobile styles */
@media (max-width: 1023px) {
    #smart-sidebar {
        transform: translateX(-100%);
        width: 280px !important;
    }
    
    #smart-sidebar.open {
        transform: translateX(0);
    }
    
    #smart-sidebar[data-collapsed="true"] {
        width: 280px !important;
    }
    
    #smart-sidebar .sidebar-text,
    #smart-sidebar .sidebar-section-title {
        opacity: 1 !important;
        width: auto !important;
    }
    
    #sidebar-toggle {
        display: none;
    }
}

/* Main content adjustment */
.sidebar-main-content {
    margin-left: 260px;
}

.sidebar-main-content.collapsed {
    margin-left: 72px;
}

@media (max-width: 1023px) {
    .sidebar-main-content,
    .sidebar-main-content.collapsed {
        margin-left: 0;
    }
}

/* Scrollbar styling */
.sidebar-nav::-webkit-scrollbar {
    width: 4px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 4px;
}

.sidebar-nav:hover::-webkit-scrollbar-thumb {
    background: #cbd5e1;
}
</style>

<script>
(function() {
    'use strict';
    
    const sidebar = document.getElementById('smart-sidebar');
    const toggle = document.getElementById('sidebar-toggle');
    const mobileToggle = document.getElementById('sidebar-mobile-toggle');
    const overlay = document.getElementById('sidebar-overlay');
    const storeCard = document.getElementById('sidebar-store-status-card');
    const storeStateEl = document.getElementById('sidebar-store-open-state');
    const storeTimeEl = document.getElementById('sidebar-store-time-line');
    const storeDotEl = document.getElementById('sidebar-store-dot');
    const storeDotGlowEl = document.getElementById('sidebar-store-dot-glow');
    const SIDEBAR_HOURS = <?= $sidebarHoursJson ?> || {};
    const STORAGE_KEY = 'sidebar_collapsed';

    function updateToggleAria(collapsed) {
        if (toggle) toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    }

    function updateMobileAria(open) {
        if (mobileToggle) mobileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (overlay) overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
    }

    function formatNowInSaoPaulo() {
        const parts = new Intl.DateTimeFormat('pt-BR', {
            timeZone: 'America/Sao_Paulo',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
            weekday: 'short'
        }).formatToParts(new Date());

        const map = {};
        parts.forEach((part) => {
            map[part.type] = part.value;
        });

        const weekdayMap = {
            'seg.': 1,
            'ter.': 2,
            'qua.': 3,
            'qui.': 4,
            'sex.': 5,
            'sab.': 6,
            'sáb.': 6,
            'dom.': 7
        };

        return {
            weekday: weekdayMap[(map.weekday || '').toLowerCase()] || 0,
            time: `${map.hour || '00'}:${map.minute || '00'}:${map.second || '00'}`,
            timeShort: `${map.hour || '00'}:${map.minute || '00'}`
        };
    }

    function normalizeTime(value) {
        if (!value || typeof value !== 'string') return null;
        const time = value.length === 5 ? `${value}:00` : value;
        return /^\d{2}:\d{2}:\d{2}$/.test(time) ? time : null;
    }

    function inRange(current, open, close) {
        if (open <= close) {
            return current >= open && current <= close;
        }
        return current >= open || current <= close;
    }

    function buildTodayHours(day) {
        const slots = [];
        const open1 = normalizeTime(day.open1 || '');
        const close1 = normalizeTime(day.close1 || '');
        const open2 = normalizeTime(day.open2 || '');
        const close2 = normalizeTime(day.close2 || '');

        if (open1 && close1) slots.push(`${open1.slice(0, 5)} - ${close1.slice(0, 5)}`);
        if (open2 && close2) slots.push(`${open2.slice(0, 5)} - ${close2.slice(0, 5)}`);
        if (slots.length === 0) return 'Horário não definido';
        return slots.join(' / ');
    }

    function computeStoreStatus() {
        const now = formatNowInSaoPaulo();
        const day = SIDEBAR_HOURS[String(now.weekday)] || SIDEBAR_HOURS[now.weekday] || null;
        const dayIsOpen = day ? Number(day.is_open) === 1 || day.is_open === true : false;

        if (!day || !dayIsOpen) {
            return {
                isOpen: false,
                timeShort: now.timeShort,
                todayHours: 'Fechado hoje'
            };
        }

        const open1 = normalizeTime(day.open1 || '');
        const close1 = normalizeTime(day.close1 || '');
        const open2 = normalizeTime(day.open2 || '');
        const close2 = normalizeTime(day.close2 || '');

        const isOpen = (
            (open1 && close1 && inRange(now.time, open1, close1)) ||
            (open2 && close2 && inRange(now.time, open2, close2))
        );

        return {
            isOpen,
            timeShort: now.timeShort,
            todayHours: buildTodayHours(day)
        };
    }

    function updateStoreStatusUi() {
        if (!storeCard || !storeStateEl || !storeTimeEl || !storeDotEl || !storeDotGlowEl) return;

        const status = computeStoreStatus();
        storeCard.dataset.open = status.isOpen ? '1' : '0';
        storeCard.title = status.isOpen ? 'Loja aberta — Horários' : 'Loja fechada — Horários';

        storeStateEl.textContent = status.isOpen ? 'aberta' : 'fechada';
        storeStateEl.classList.toggle('text-emerald-600', status.isOpen);
        storeStateEl.classList.toggle('text-red-600', !status.isOpen);

        storeDotEl.classList.toggle('bg-emerald-500', status.isOpen);
        storeDotEl.classList.toggle('bg-red-500', !status.isOpen);
        storeDotGlowEl.classList.toggle('bg-emerald-400', status.isOpen);
        storeDotGlowEl.classList.toggle('bg-red-400', !status.isOpen);

        storeTimeEl.textContent = `${status.timeShort} · ${status.todayHours}`;
    }
    
    // Carregar preferência salva
    function loadPreference() {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved === 'true' && window.innerWidth >= 1024) {
            sidebar.dataset.collapsed = 'true';
            updateMainContent(true);
            updateToggleAria(true);
        } else {
            updateToggleAria(false);
        }
    }
    
    // Salvar preferência
    function savePreference(collapsed) {
        localStorage.setItem(STORAGE_KEY, String(collapsed));
    }
    
    // Toggle sidebar (desktop)
    function toggleSidebar() {
        const isCollapsed = sidebar.dataset.collapsed === 'true';
        sidebar.dataset.collapsed = String(!isCollapsed);
        savePreference(!isCollapsed);
        updateMainContent(!isCollapsed);
        updateToggleAria(!isCollapsed);
    }
    
    // Toggle sidebar (mobile)
    function toggleMobileSidebar(open) {
        if (open) {
            sidebar.classList.add('open');
            overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            if (mobileToggle) mobileToggle.classList.add('hidden');
        } else {
            sidebar.classList.remove('open');
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
            if (mobileToggle) mobileToggle.classList.remove('hidden');
        }
        updateMobileAria(open);
    }
    
    // Atualizar margem do conteúdo principal
    function updateMainContent(collapsed) {
        const mainContent = document.querySelector('.sidebar-main-content');
        if (mainContent) {
            if (collapsed) {
                mainContent.classList.add('collapsed');
            } else {
                mainContent.classList.remove('collapsed');
            }
        }
    }
    
    // Event listeners
    if (toggle) {
        toggle.addEventListener('click', toggleSidebar);
    }
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', () => toggleMobileSidebar(true));
    }
    
    if (overlay) {
        overlay.addEventListener('click', () => toggleMobileSidebar(false));
    }
    
    // Fechar mobile sidebar com ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            toggleMobileSidebar(false);
        }
    });
    
    // Responsividade
    let wasDesktop = window.innerWidth >= 1024;
    let resizeTimer = null;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            const isDesktop = window.innerWidth >= 1024;
            if (isDesktop !== wasDesktop) {
                wasDesktop = isDesktop;
                if (isDesktop) {
                    toggleMobileSidebar(false);
                    loadPreference();
                }
            }
        }, 180);
    });
    
    // Collapsible group toggle
    const GROUP_STORAGE_KEY = 'sidebar_group_collapsed';
    const groupToggle = document.querySelector('.sidebar-group-toggle');
    
    function loadGroupState() {
        if (!groupToggle) return;
        const saved = localStorage.getItem(GROUP_STORAGE_KEY);
        if (saved === 'true') {
            groupToggle.setAttribute('aria-expanded', 'false');
        }
    }
    
    function toggleGroup() {
        if (!groupToggle) return;
        const isExpanded = groupToggle.getAttribute('aria-expanded') === 'true';
        groupToggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
        localStorage.setItem(GROUP_STORAGE_KEY, isExpanded ? 'true' : 'false');
    }
    
    if (groupToggle) {
        groupToggle.addEventListener('click', toggleGroup);
        loadGroupState();
    }
    
    // Inicialização — aplicar sem transição
    loadPreference();
    updateMobileAria(false);
    updateStoreStatusUi();
    window.setInterval(updateStoreStatusUi, 60000);
    
    // Habilitar transições somente após o estado inicial ser aplicado
    requestAnimationFrame(function() {
        requestAnimationFrame(function() {
            var mc = document.querySelector('.sidebar-main-content');
            if (mc) mc.classList.add('transition-ready');
            document.documentElement.classList.remove('sidebar-preload-collapsed');
        });
    });
})();
</script>
