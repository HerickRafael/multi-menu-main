<?php
$sidebarData = is_array($sidebarData ?? null) ? $sidebarData : [];

$companySlug = (string)($sidebarData['companySlug'] ?? ($activeSlug ?? ($company['slug'] ?? '')));
$companyName = (string)($sidebarData['companyName'] ?? (trim((string)($company['name'] ?? '')) ?: 'Admin'));
$globalItems = is_array($sidebarData['globalItems'] ?? null) ? $sidebarData['globalItems'] : [];
$secondaryItems = is_array($sidebarData['secondaryItems'] ?? null) ? $sidebarData['secondaryItems'] : [];
$contextLabel = (string)($sidebarData['contextLabel'] ?? 'Mais opções');
$dashboardUrl = (string)($sidebarData['dashboardUrl'] ?? base_url('admin/' . trim($companySlug, '/') . '/dashboard'));
$logoutUrl = (string)($sidebarData['logoutUrl'] ?? base_url('admin/' . trim($companySlug, '/') . '/logout'));
$menuUrl = (string)($sidebarData['menuUrl'] ?? base_url($companySlug));
$sidebarDebugWarning = !empty($sidebarData['debug_warning']);
$sidebarDebugWarningLevel = (string)($sidebarData['debug_warning_level'] ?? 'warning');
$sidebarDebugWarningMessage = trim((string)($sidebarData['debug_warning_message'] ?? ''));
?>

<script>
(function(){
  var c = localStorage.getItem('sidebar_collapsed') === 'true' && window.innerWidth >= 1024;
  if (c) document.documentElement.classList.add('sidebar-preload-collapsed');
})();
</script>

<div id="sidebar-overlay" class="fixed inset-0 bg-black/40 z-30 hidden lg:hidden transition-opacity duration-300" aria-hidden="true"></div>

<aside id="smart-sidebar"
       class="fixed left-0 flex flex-col z-40"
       style="top:48px;height:calc(100vh - 48px);background:#efeff0;border-right:none;"
       data-collapsed="false">

    <nav class="flex-1 overflow-y-auto overflow-x-hidden px-1 py-1.5 sidebar-nav space-y-3" role="navigation" aria-label="Navegação principal do admin">
        <!-- Main items (no header label) -->
        <div class="space-y-1">
            <?php foreach ($globalItems as $item): ?>
                <a href="<?= e($item['url']) ?>"
                   class="sidebar-item group flex h-9 items-center rounded-md px-3 text-sm font-medium transition-colors <?= !empty($item['is_active']) ? 'admin-sidebar-active' : 'text-zinc-700 admin-sidebar-item-hover' ?>"
                   title="<?= e($item['label']) ?>"
                   aria-current="<?= !empty($item['is_active']) ? 'page' : 'false' ?>"
                   style="<?= !empty($item['is_active']) ? 'box-shadow: inset 2px 0 0 var(--admin-primary-color);' : '' ?>">
                    <span class="flex h-4 w-4 shrink-0 items-center justify-center">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <?= $item['icon'] ?>
                        </svg>
                    </span>
                    <span class="sidebar-text ml-3 whitespace-nowrap transition-opacity duration-200"><?= e($item['label']) ?></span>
                    <?php if (!empty($item['badge'])): ?>
                    <span class="sidebar-text ml-auto rounded-full bg-red-500 px-2 py-0.5 text-xs font-bold text-white animate-pulse"><?= e($item['badge']) ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Secondary group: collapsible "Mais opções" -->
        <?php if (!empty($secondaryItems)): ?>
        <div class="space-y-1" id="secondary-section">
            <button type="button"
                    class="sidebar-group-toggle flex w-full items-center justify-between rounded-md px-3 py-1 text-[11px] font-semibold tracking-wide text-zinc-500 hover:bg-zinc-200/60 sidebar-text transition-opacity"
                    aria-expanded="true"
                    aria-controls="secondary-items">
                <span><?= e($contextLabel) ?></span>
                <svg class="h-3.5 w-3.5 transition-transform group-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div id="secondary-items" class="space-y-1">
                <?php foreach ($secondaryItems as $item): ?>
                    <a href="<?= e($item['url']) ?>"
                       class="sidebar-item group flex h-9 items-center rounded-md px-3 text-sm font-medium transition-colors <?= !empty($item['is_active']) ? 'admin-sidebar-active' : 'text-zinc-700 admin-sidebar-item-hover' ?>"
                       title="<?= e($item['label']) ?>"
                       aria-current="<?= !empty($item['is_active']) ? 'page' : 'false' ?>"
                       style="<?= !empty($item['is_active']) ? 'box-shadow: inset 2px 0 0 var(--admin-primary-color);' : '' ?>">
                        <span class="flex h-4 w-4 shrink-0 items-center justify-center">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <?= $item['icon'] ?>
                            </svg>
                        </span>
                        <span class="sidebar-text ml-3 whitespace-nowrap transition-opacity duration-200"><?= e($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($sidebarDebugWarning): ?>
            <?php $isCritical = $sidebarDebugWarningLevel === 'critical'; ?>
            <div class="hidden lg:block px-2">
                <div class="rounded-lg border px-3 py-2 <?= $isCritical ? 'border-red-400 bg-red-50 text-red-800 animate-pulse' : 'border-amber-300 bg-amber-50 text-amber-800' ?>">
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
    </nav>

    <!-- Footer da Sidebar -->
    <div class="px-1 py-2 space-y-1">
        <a href="<?= e($menuUrl) ?>"
           target="_blank"
           rel="noreferrer"
           class="sidebar-item flex h-9 items-center rounded-md px-3 text-sm text-zinc-700 hover:bg-zinc-200/70 transition-colors"
           title="Ver Cardápio">
            <span class="flex h-4 w-4 shrink-0 items-center justify-center">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                </svg>
            </span>
            <span class="sidebar-text ml-3 whitespace-nowrap transition-opacity duration-200">Ver Cardápio</span>
        </a>
        <a href="<?= e($logoutUrl) ?>"
           class="sidebar-item flex h-9 items-center rounded-md px-3 text-sm text-red-600 hover:bg-red-50 transition-colors"
           title="Sair">
            <span class="flex h-4 w-4 shrink-0 items-center justify-center">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                </svg>
            </span>
            <span class="sidebar-text ml-3 whitespace-nowrap transition-opacity duration-200">Sair</span>
        </a>
    </div>
</aside>

<style>
/* Pré-carregar estado colapsado sem animação */
.sidebar-preload-collapsed #smart-sidebar {
    width: 56px !important;
    transition: none !important;
}
.sidebar-preload-collapsed #smart-sidebar .sidebar-text {
    opacity: 0 !important;
    max-width: 0 !important;
    overflow: hidden !important;
}

/* Sidebar geometry — matches StoreDashboardLayout (60 expanded / 14 collapsed) */
#smart-sidebar {
    width: 240px;
    transition: width 0.3s ease-in-out;
}

.sidebar-text {
    max-width: 200px;
    transition: max-width 0.3s ease-in-out, opacity 0.2s ease-in-out;
}

#smart-sidebar[data-collapsed="true"] {
    width: 56px;
}

#smart-sidebar[data-collapsed="true"] .sidebar-text {
    opacity: 0;
    max-width: 0;
    overflow: hidden;
    margin-left: 0;
}

#smart-sidebar[data-collapsed="true"] .sidebar-item {
    justify-content: center;
    padding-left: 0;
    padding-right: 0;
}

#smart-sidebar[data-collapsed="true"] #secondary-section .sidebar-group-toggle {
    display: none;
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
    pointer-events: none;
}

/* Collapsible group state */
.sidebar-group-toggle[aria-expanded="false"] .group-chevron {
    transform: rotate(-90deg);
}
.sidebar-group-toggle[aria-expanded="false"] + #secondary-items {
    display: none;
}

/* Mobile styles (drawer) */
@media (max-width: 1023px) {
    #smart-sidebar {
        transform: translateX(-100%);
        width: 272px !important;
        transition: transform 0.3s ease-in-out;
    }
    #smart-sidebar.open { transform: translateX(0); }
    #smart-sidebar[data-collapsed="true"] { width: 272px !important; }
    #smart-sidebar .sidebar-text { opacity: 1 !important; max-width: 200px !important; margin-left: 0.75rem; }
    #smart-sidebar .sidebar-item { justify-content: flex-start; padding-left: 0.75rem; padding-right: 0.75rem; }
}

/* Scrollbar */
.sidebar-nav::-webkit-scrollbar { width: 4px; }
.sidebar-nav::-webkit-scrollbar-track { background: transparent; }
.sidebar-nav::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }
.sidebar-nav:hover::-webkit-scrollbar-thumb { background: #cbd5e1; }
</style>

<script>
(function() {
    'use strict';

    const sidebar = document.getElementById('smart-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const STORAGE_KEY = 'sidebar_collapsed';
    const GROUP_STORAGE_KEY = 'sidebar_secondary_collapsed';

    function updateMainContent(collapsed) {
        const mainContent = document.getElementById('admin-main-content');
        if (!mainContent) return;
        if (collapsed) mainContent.classList.add('collapsed');
        else mainContent.classList.remove('collapsed');
    }

    function setMobileOpen(open) {
        if (!sidebar) return;
        if (open) {
            sidebar.classList.add('open');
            if (overlay) overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    function loadPreference() {
        if (!sidebar) return;
        const saved = localStorage.getItem(STORAGE_KEY);
        const collapsed = saved === 'true' && window.innerWidth >= 1024;
        sidebar.dataset.collapsed = String(collapsed);
        updateMainContent(collapsed);
    }

    function toggleCollapsed() {
        if (!sidebar) return;
        if (window.innerWidth < 1024) {
            setMobileOpen(!sidebar.classList.contains('open'));
            return;
        }
        const collapsed = sidebar.dataset.collapsed === 'true';
        sidebar.dataset.collapsed = String(!collapsed);
        localStorage.setItem(STORAGE_KEY, String(!collapsed));
        updateMainContent(!collapsed);
    }

    // Wire topbar hamburger (#topbar-sidebar-toggle) to toggle
    const topbarToggle = document.getElementById('topbar-sidebar-toggle');
    if (topbarToggle) {
        topbarToggle.addEventListener('click', toggleCollapsed);
    }

    // Mobile overlay click → close
    if (overlay) overlay.addEventListener('click', () => setMobileOpen(false));

    // ESC closes mobile drawer
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
            setMobileOpen(false);
        }
    });

    // Window resize: reset state appropriately
    let wasDesktop = window.innerWidth >= 1024;
    let resizeTimer = null;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            const isDesktop = window.innerWidth >= 1024;
            if (isDesktop !== wasDesktop) {
                wasDesktop = isDesktop;
                if (isDesktop) {
                    setMobileOpen(false);
                    loadPreference();
                }
            }
        }, 180);
    });

    // Collapsible "Mais opções" group
    const groupToggle = sidebar ? sidebar.querySelector('.sidebar-group-toggle') : null;
    if (groupToggle) {
        const saved = localStorage.getItem(GROUP_STORAGE_KEY);
        if (saved === 'true') groupToggle.setAttribute('aria-expanded', 'false');
        groupToggle.addEventListener('click', () => {
            const isExpanded = groupToggle.getAttribute('aria-expanded') === 'true';
            groupToggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
            localStorage.setItem(GROUP_STORAGE_KEY, isExpanded ? 'true' : 'false');
        });
    }

    loadPreference();

    // Enable transitions after initial state is applied
    requestAnimationFrame(function() {
        requestAnimationFrame(function() {
            document.documentElement.classList.remove('sidebar-preload-collapsed');
        });
    });
})();
</script>
