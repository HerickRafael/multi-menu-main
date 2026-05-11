<?php
/**
 * Configurações WhatsApp/Evolution Mobile
 * Versão mobile completa baseada na versão admin
 */
$hasConfig = $hasConfig ?? false;
$instances = $instances ?? [];
$selectedInstance = $_GET['instance'] ?? null;
$slug = $_SERVER['MOBILE_SLUG'] ?? 'wollburger';

ob_start();
?>

<style>
/* Base styles */
:root {
    --wa-green: #25D366;
    --wa-dark: #128C7E;
    --wa-light: #dcf8c6;
}

/* Header WhatsApp */
.wa-header {
    background: linear-gradient(135deg, var(--wa-green) 0%, var(--wa-dark) 100%);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 16px;
    color: white;
}

.wa-header-icon {
    width: 48px;
    height: 48px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
}

.wa-header h1 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 4px;
}

.wa-header p {
    font-size: 13px;
    opacity: 0.9;
}

/* Action Bar */
.action-bar {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

/* Search and Filter Bar */
.search-filter-bar {
    display: flex;
    gap: 10px;
    margin-bottom: 16px;
}

.search-box {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 8px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 0 12px;
}

.search-box svg {
    color: #9ca3af;
    flex-shrink: 0;
}

.search-box input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 12px 0;
    font-size: 14px;
    outline: none;
}

.search-box input::placeholder {
    color: #9ca3af;
}

.filter-dropdown {
    position: relative;
}

.filter-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 12px 14px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    font-size: 13px;
    color: #374151;
    cursor: pointer;
    white-space: nowrap;
}

.filter-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 4px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    min-width: 140px;
    z-index: 50;
    display: none;
    overflow: hidden;
}

.filter-menu.active {
    display: block;
}

.filter-menu button {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 12px 14px;
    border: none;
    background: transparent;
    text-align: left;
    font-size: 13px;
    color: #374151;
    cursor: pointer;
}

.filter-menu button:hover {
    background: #f9fafb;
}

.filter-menu .status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.filter-menu .status-dot.connected { background: #10b981; }
.filter-menu .status-dot.pending { background: #f59e0b; }
.filter-menu .status-dot.disconnected { background: #ef4444; }

.action-btn-bar {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 12px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    background: white;
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    cursor: pointer;
}

.action-btn-bar:active {
    transform: scale(0.98);
    background: #f9fafb;
}

.action-btn-bar.primary {
    background: var(--wa-green);
    color: white;
    border: none;
}

/* Cards de Instância */
.instance-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    margin-bottom: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    cursor: pointer;
    transition: all 0.2s ease;
}

.instance-card:active {
    transform: scale(0.98);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Botão de configurações no header do card */
.instance-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    border-bottom: 1px solid #f3f4f6;
}

.instance-settings-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    cursor: pointer;
}

.instance-settings-btn:active {
    background: #f3f4f6;
}

.instance-card-name {
    font-size: 15px;
    font-weight: 600;
    color: #1f2937;
}

.instance-card-body {
    padding: 16px;
}

.instance-card-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.instance-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--wa-green);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
    flex-shrink: 0;
    overflow: hidden;
}

.instance-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.instance-info {
    flex: 1;
    min-width: 0;
}

.instance-profile-name {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.instance-number {
    font-size: 12px;
    color: #6b7280;
}

/* Status badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.connected {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.pending {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.disconnected {
    background: #fee2e2;
    color: #991b1b;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-badge.connected .status-dot { background: #10b981; }
.status-badge.pending .status-dot { background: #f59e0b; animation: pulse 1.5s infinite; }
.status-badge.disconnected .status-dot { background: #ef4444; }

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Instance ID box */
.instance-id-box {
    background: #f9fafb;
    border-radius: 8px;
    padding: 10px 12px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Instance stats */
.instance-stats {
    display: flex;
    gap: 16px;
    margin-bottom: 12px;
    padding: 10px 0;
    border-top: 1px solid #f3f4f6;
    border-bottom: 1px solid #f3f4f6;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #6b7280;
    font-size: 13px;
}

.stat-item svg {
    opacity: 0.6;
}

.stat-item span {
    font-weight: 500;
}

.instance-id-text {
    flex: 1;
    font-family: monospace;
    font-size: 11px;
    color: #6b7280;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.instance-id-btn {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: none;
    background: white;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

/* Card footer actions */
.instance-card-footer {
    display: flex;
    border-top: 1px solid #f3f4f6;
}

.card-action {
    flex: 1;
    padding: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 500;
    color: #6b7280;
    background: none;
    border: none;
    cursor: pointer;
    border-right: 1px solid #f3f4f6;
}

.card-action:last-child {
    border-right: none;
}

.card-action:active {
    background: #f9fafb;
}

.card-action.danger {
    color: #dc2626;
}

.card-action.primary {
    color: var(--wa-green);
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 48px 24px;
    color: #9ca3af;
}

.empty-state svg {
    margin: 0 auto 16px;
    color: #d1d5db;
}

.empty-state h3 {
    font-size: 16px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.empty-state p {
    font-size: 13px;
    margin-bottom: 16px;
}

/* Config alert */
.config-alert {
    background: #fef3c7;
    border: 1px solid #fcd34d;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
}

.config-alert h4 {
    font-weight: 600;
    color: #92400e;
    margin-bottom: 6px;
}

.config-alert p {
    font-size: 13px;
    color: #78350f;
}

.config-alert a {
    color: #92400e;
    font-weight: 600;
    text-decoration: underline;
}

/* Modal */
.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 100;
    background: rgba(0,0,0,0.6);
    display: none;
    align-items: flex-end;
}

.modal-overlay.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 20px 20px 0 0;
    width: 100%;
    max-height: 85vh;
    overflow-y: auto;
    padding: 20px;
    padding-bottom: calc(20px + 80px); /* Espaço extra para o rodapé */
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.modal-header h2 {
    font-size: 18px;
    font-weight: 600;
}

.modal-close {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: #f3f4f6;
    font-size: 20px;
    cursor: pointer;
}

/* Form */
.form-group {
    margin-bottom: 16px;
}

.form-label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
}

.form-input {
    width: 100%;
    padding: 14px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    font-size: 16px;
    background: white;
}

.form-input:focus {
    outline: none;
    border-color: var(--wa-green);
    box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.15);
}

.form-hint {
    font-size: 11px;
    color: #9ca3af;
    margin-top: 4px;
}

.btn-submit {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    background: var(--wa-green);
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-submit:active {
    transform: scale(0.98);
}

/* QR Code Modal */
.qr-container {
    text-align: center;
    padding: 20px 0;
}

.qr-box {
    background: white;
    border: 2px dashed var(--wa-green);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 16px;
}

.qr-box img {
    max-width: 220px;
    margin: 0 auto;
}

.qr-instructions {
    background: #f9fafb;
    border-radius: 12px;
    padding: 16px;
    text-align: left;
    font-size: 13px;
    color: #6b7280;
    line-height: 1.6;
}

.qr-instructions strong {
    color: #374151;
}

/* Loading */
.loading-spinner {
    width: 24px;
    height: 24px;
    border: 2px solid #e5e7eb;
    border-top-color: var(--wa-green);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Skeleton loading */
.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: 8px;
}

@keyframes skeleton-loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* Flash messages */
.flash-success {
    background: #d1fae5;
    color: #065f46;
    padding: 12px 16px;
    border-radius: 12px;
    margin-bottom: 16px;
    font-size: 14px;
}

.flash-error {
    background: #fef2f2;
    color: #991b1b;
    padding: 12px 16px;
    border-radius: 12px;
    margin-bottom: 16px;
    font-size: 14px;
}

/* Refreshing indicator */
.refreshing-indicator {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--wa-green);
    animation: progress 1s ease-in-out infinite;
    display: none;
    z-index: 200;
}

.refreshing-indicator.active {
    display: block;
}

@keyframes progress {
    0% { transform: scaleX(0); transform-origin: left; }
    50% { transform: scaleX(1); transform-origin: left; }
    50.1% { transform-origin: right; }
    100% { transform: scaleX(0); transform-origin: right; }
}
</style>

<div class="refreshing-indicator" id="refreshIndicator"></div>

<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="flash-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="flash-error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- Header -->
<div class="wa-header">
    <div class="wa-header-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
    </div>
    <h1>Instâncias WhatsApp</h1>
    <p>Gerencie suas conexões WhatsApp Business</p>
</div>

<?php if (!$hasConfig): ?>
    <div class="config-alert">
        <h4>⚠️ API não configurada</h4>
        <p>Configure a Evolution API nas <a href="/settings/store?tab=api">configurações da loja</a> para gerenciar suas instâncias WhatsApp.</p>
    </div>
<?php else: ?>

<!-- Action Bar -->
<div class="action-bar">
    <button type="button" class="action-btn-bar" onclick="syncInstances()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M23 4v6h-6M1 20v-6h6"/>
            <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
        </svg>
        <span id="syncBtnText">Sincronizar</span>
    </button>
    <button type="button" class="action-btn-bar" onclick="refreshInstances()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <polyline points="23 4 23 10 17 10"/>
            <path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/>
        </svg>
        Atualizar
    </button>
    <button type="button" class="action-btn-bar primary" onclick="openNewModal()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M12 5v14M5 12h14"/>
        </svg>
        Nova
    </button>
</div>

<!-- Busca e Filtros -->
<div class="search-filter-bar">
    <div class="search-box">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input type="text" id="searchInput" placeholder="Buscar instância..." oninput="filterInstances()">
    </div>
    <div class="filter-dropdown">
        <button type="button" class="filter-btn" onclick="toggleFilterMenu()">
            <span id="filterLabel">Todos</span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </button>
        <div class="filter-menu" id="filterMenu">
            <button data-status="all" onclick="setFilter('all', 'Todos')">Todos</button>
            <button data-status="connected" onclick="setFilter('connected', 'Conectado')">
                <span class="status-dot connected"></span> Conectado
            </button>
            <button data-status="pending" onclick="setFilter('pending', 'Pendente')">
                <span class="status-dot pending"></span> Pendente
            </button>
            <button data-status="disconnected" onclick="setFilter('disconnected', 'Desconectado')">
                <span class="status-dot disconnected"></span> Desconectado
            </button>
        </div>
    </div>
</div>

<!-- Lista de Instâncias -->
<div id="instancesList">
    <!-- Skeleton loading -->
    <div id="skeletonCards">
        <div class="instance-card">
            <div class="instance-card-header">
                <div class="skeleton" style="width: 120px; height: 18px;"></div>
                <div class="skeleton" style="width: 80px; height: 24px; border-radius: 20px;"></div>
            </div>
            <div class="instance-card-body">
                <div class="instance-card-profile">
                    <div class="skeleton" style="width: 48px; height: 48px; border-radius: 50%;"></div>
                    <div>
                        <div class="skeleton" style="width: 100px; height: 14px; margin-bottom: 6px;"></div>
                        <div class="skeleton" style="width: 140px; height: 12px;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="instance-card">
            <div class="instance-card-header">
                <div class="skeleton" style="width: 100px; height: 18px;"></div>
                <div class="skeleton" style="width: 70px; height: 24px; border-radius: 20px;"></div>
            </div>
            <div class="instance-card-body">
                <div class="instance-card-profile">
                    <div class="skeleton" style="width: 48px; height: 48px; border-radius: 50%;"></div>
                    <div>
                        <div class="skeleton" style="width: 80px; height: 14px; margin-bottom: 6px;"></div>
                        <div class="skeleton" style="width: 120px; height: 12px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cards reais serão inseridos via JS -->
    <div id="realCards"></div>
</div>

<?php endif; ?>

<!-- Modal Nova Instância -->
<div class="modal-overlay" id="newModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Nova Instância</h2>
            <button class="modal-close" onclick="closeNewModal()">&times;</button>
        </div>
        <form id="formNewInstance" onsubmit="createInstance(event)">
            <div class="form-group">
                <label class="form-label">Nome da instância *</label>
                <input type="text" name="name" class="form-input" required
                       placeholder="Ex: vendas_whatsapp" pattern="[a-zA-Z0-9_-]+">
                <div class="form-hint">Apenas letras, números, _ e -</div>
            </div>
            <button type="submit" class="btn-submit" id="createBtn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Criar Instância
            </button>
        </form>
    </div>
</div>

<!-- Modal QR Code -->
<div class="modal-overlay" id="qrModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Conectar WhatsApp</h2>
            <button class="modal-close" onclick="closeQrModal()">&times;</button>
        </div>
        <div class="qr-container">
            <div class="qr-box" id="qrContent">
                <div class="loading-spinner" style="margin: 40px auto;"></div>
                <p style="color: #6b7280; margin-top: 12px;">Carregando QR Code...</p>
            </div>
            <div class="qr-instructions">
                <strong>Como conectar:</strong><br>
                1. Abra o WhatsApp no celular<br>
                2. Toque em ⋮ → Dispositivos conectados<br>
                3. Toque em "Conectar dispositivo"<br>
                4. Escaneie o QR Code acima
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" style="position: fixed; bottom: 80px; left: 16px; right: 16px; z-index: 200; pointer-events: none;"></div>

<script>
const companySlug = '<?= htmlspecialchars($slug) ?>';
let instances = <?= json_encode($instances ?? []) ?>;
let currentQrInstance = null;
let qrRefreshInterval = null;
let currentFilter = 'all';
let searchQuery = '';

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($hasConfig): ?>
    refreshInstances();
    <?php endif; ?>
    
    // Fechar menu de filtro ao clicar fora
    document.addEventListener('click', function(e) {
        const filterMenu = document.getElementById('filterMenu');
        if (!e.target.closest('.filter-dropdown')) {
            filterMenu.classList.remove('active');
        }
    });
});

// Filtrar instâncias
function filterInstances() {
    searchQuery = document.getElementById('searchInput').value.toLowerCase();
    applyFilters();
}

function toggleFilterMenu() {
    document.getElementById('filterMenu').classList.toggle('active');
}

function setFilter(status, label) {
    currentFilter = status;
    document.getElementById('filterLabel').textContent = label;
    document.getElementById('filterMenu').classList.remove('active');
    applyFilters();
}

function applyFilters() {
    const cards = document.querySelectorAll('.instance-card');
    cards.forEach(function(card) {
        const name = (card.dataset.name || '').toLowerCase();
        const status = card.dataset.status || '';
        
        const matchesSearch = searchQuery === '' || name.includes(searchQuery);
        const matchesFilter = currentFilter === 'all' || status === currentFilter;
        
        card.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
    });
}

// Toast
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const bgColor = type === 'error' ? '#fee2e2' : '#d1fae5';
    const textColor = type === 'error' ? '#991b1b' : '#065f46';
    
    toast.innerHTML = '<div style="background: ' + bgColor + '; color: ' + textColor + '; padding: 12px 16px; border-radius: 12px; font-size: 14px; text-align: center;">' + message + '</div>';
    toast.style.opacity = '1';
    
    setTimeout(function() {
        toast.style.opacity = '0';
    }, 3000);
}

// Refresh indicator
function showRefreshing(show) {
    document.getElementById('refreshIndicator').classList.toggle('active', show);
}

// Sincronizar da API
async function syncInstances() {
    const btn = document.getElementById('syncBtnText');
    btn.innerHTML = '<span class="loading-spinner" style="width:14px;height:14px;"></span>';
    showRefreshing(true);
    
    try {
        const response = await fetch('/settings/whatsapp/sync', { 
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        btn.textContent = 'Sincronizar';
        showRefreshing(false);
        
        if (data.success) {
            showToast(data.message || 'Sincronizado!');
            refreshInstances();
        } else {
            showToast(data.error || 'Erro ao sincronizar', 'error');
        }
    } catch (e) {
        btn.textContent = 'Sincronizar';
        showRefreshing(false);
        showToast('Erro de conexão', 'error');
    }
}

// Atualizar lista
async function refreshInstances() {
    showRefreshing(true);
    
    try {
        const response = await fetch('/settings/whatsapp/instances', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.instances) {
            instances = data.instances;
            renderInstances();
        }
    } catch (e) {
        console.error('Erro ao atualizar:', e);
    } finally {
        showRefreshing(false);
    }
}

// Renderizar instâncias
function renderInstances() {
    const skeleton = document.getElementById('skeletonCards');
    const container = document.getElementById('realCards');
    
    skeleton.style.display = 'none';
    container.innerHTML = '';
    
    if (instances.length === 0) {
        container.innerHTML = '<div class="empty-state">' +
            '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>' +
            '<h3>Nenhuma instância encontrada</h3>' +
            '<p>Clique em "Sincronizar" para buscar da API<br>ou "Nova" para criar uma</p>' +
        '</div>';
        return;
    }
    
    instances.forEach(function(inst) {
        container.appendChild(createInstanceCard(inst));
    });
}

// Criar card de instância
function createInstanceCard(inst) {
    const card = document.createElement('div');
    card.className = 'instance-card';
    card.dataset.name = inst.name;
    card.dataset.status = inst.status;
    
    // Clique no card abre configurações da instância
    card.addEventListener('click', function(e) {
        // Não navegar se clicou em botão de ação
        if (e.target.closest('.card-action') || e.target.closest('.instance-id-btn') || e.target.closest('.instance-settings-btn')) {
            return;
        }
        goToInstanceSettings(inst.name);
    });
    
    const statusClass = inst.status === 'connected' ? 'connected' : 
                        inst.status === 'pending' ? 'pending' : 'disconnected';
    const statusText = inst.status === 'connected' ? 'Conectado' : 
                       inst.status === 'pending' ? 'Reconectando' : 'Desconectado';
    
    const avatarLetters = (inst.profile_name || inst.label || inst.name || 'WA').substring(0, 2).toUpperCase();
    const avatarHtml = inst.profile_picture 
        ? '<img src="' + inst.profile_picture + '" alt="" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">' +
          '<span style="display:none">' + avatarLetters + '</span>'
        : avatarLetters;
    
    const numberText = inst.formatted_number || inst.number || (inst.status === 'connected' ? 'Número não disponível' : 
                       inst.status === 'pending' ? 'Aguardando conexão' : 'Desconectado');
    
    const chatsCount = inst.chats || 0;
    const messagesCount = inst.messages || 0;
    
    card.innerHTML = '<div class="instance-card-header">' +
            '<span class="instance-card-name">' + (inst.label || inst.name) + '</span>' +
            '<div style="display:flex;align-items:center;gap:8px;">' +
                '<span class="status-badge ' + statusClass + '">' +
                    '<span class="status-dot"></span>' +
                    statusText +
                '</span>' +
                '<button class="instance-settings-btn" onclick="event.stopPropagation(); goToInstanceSettings(\'' + inst.name + '\')" title="Configurações">' +
                    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>' +
                '</button>' +
            '</div>' +
        '</div>' +
        '<div class="instance-card-body">' +
            '<div class="instance-card-profile">' +
                '<div class="instance-avatar">' + avatarHtml + '</div>' +
                '<div class="instance-info">' +
                    '<div class="instance-profile-name">' + (inst.profile_name || 'Contato') + '</div>' +
                    '<div class="instance-number">' + numberText + '</div>' +
                '</div>' +
            '</div>' +
            '<div class="instance-stats">' +
                '<div class="stat-item">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>' +
                    '<span>' + chatsCount + '</span>' +
                '</div>' +
                '<div class="stat-item">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>' +
                    '<span>' + messagesCount + '</span>' +
                '</div>' +
            '</div>' +
            '<div class="instance-id-box">' +
                '<span class="instance-id-text" data-visible="0">' + '*'.repeat(inst.name.length) + '</span>' +
                '<button class="instance-id-btn" onclick="toggleInstanceId(this, \'' + inst.name + '\')" title="Mostrar/Ocultar">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>' +
                '</button>' +
                '<button class="instance-id-btn" onclick="copyInstanceId(\'' + inst.name + '\')" title="Copiar">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>' +
                '</button>' +
            '</div>' +
        '</div>' +
        '<div class="instance-card-footer">' +
            '<button class="card-action primary" onclick="showQrCode(\'' + inst.name + '\')">' +
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>' +
                'QR Code' +
            '</button>' +
            '<button class="card-action" onclick="disconnectInstance(\'' + inst.name + '\')">' +
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18.36 6.64a9 9 0 11-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>' +
                'Desconectar' +
            '</button>' +
            '<button class="card-action danger" onclick="deleteInstance(\'' + inst.name + '\', \'' + (inst.id || '') + '\')">' +
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>' +
                'Excluir' +
            '</button>' +
        '</div>';
    
    return card;
}

// Funções auxiliares
function toggleInstanceId(btn, name) {
    event.stopPropagation();
    const textEl = btn.parentElement.querySelector('.instance-id-text');
    if (textEl.dataset.visible === '0') {
        textEl.textContent = name;
        textEl.dataset.visible = '1';
    } else {
        textEl.textContent = '*'.repeat(name.length);
        textEl.dataset.visible = '0';
    }
}

function copyInstanceId(name) {
    event.stopPropagation();
    navigator.clipboard.writeText(name).then(function() {
        showToast('ID copiado!');
    });
}

// Navegar para página de configurações da instância
function goToInstanceSettings(instanceName) {
    window.location.href = '/settings/whatsapp/instance/' + encodeURIComponent(instanceName);
}

// Modal Nova Instância
function openNewModal() {
    document.getElementById('newModal').classList.add('active');
}

function closeNewModal() {
    document.getElementById('newModal').classList.remove('active');
}

async function createInstance(e) {
    e.preventDefault();
    
    const form = document.getElementById('formNewInstance');
    const btn = document.getElementById('createBtn');
    const name = form.name.value.trim();
    
    if (!name) return;
    
    btn.innerHTML = '<span class="loading-spinner" style="width:18px;height:18px;"></span> Criando...';
    btn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('name', name);
        
        const response = await fetch('/settings/whatsapp/create', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        const data = await response.json();
        
        if (data.success || data.ok) {
            showToast('Instância criada! Escaneie o QR Code.');
            closeNewModal();
            form.reset();
            refreshInstances();
            setTimeout(function() { showQrCode(name); }, 1000);
        } else {
            showToast(data.error || 'Erro ao criar instância', 'error');
        }
    } catch (e) {
        showToast('Erro de conexão', 'error');
    } finally {
        btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        btn.disabled = false;
    }
}

// Modal QR Code
function showQrCode(name) {
    currentQrInstance = name;
    document.getElementById('qrModal').classList.add('active');
    loadQrCode(name);
}

function closeQrModal() {
    document.getElementById('qrModal').classList.remove('active');
    currentQrInstance = null;
    if (qrRefreshInterval) {
        clearInterval(qrRefreshInterval);
        qrRefreshInterval = null;
    }
}

async function loadQrCode(name) {
    const content = document.getElementById('qrContent');
    content.innerHTML = '<div class="loading-spinner" style="margin: 40px auto;"></div><p style="color: #6b7280; margin-top: 12px;">Carregando QR Code...</p>';
    
    try {
        const response = await fetch('/settings/whatsapp/' + encodeURIComponent(name) + '/qrcode', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.status === 'connected') {
            content.innerHTML = '<div style="padding: 40px;">' +
                '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.5" style="margin: 0 auto 12px;">' +
                    '<path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>' +
                    '<polyline points="22 4 12 14.01 9 11.01"/>' +
                '</svg>' +
                '<p style="font-weight: 600; color: #059669;">Já está conectado!</p>' +
            '</div>';
            refreshInstances();
        } else if (data.qrcode) {
            content.innerHTML = '<img src="' + data.qrcode + '" alt="QR Code" style="max-width: 220px;">';
            
            // Atualizar a cada 15s
            if (qrRefreshInterval) clearInterval(qrRefreshInterval);
            qrRefreshInterval = setInterval(function() {
                if (currentQrInstance === name) loadQrCode(name);
            }, 15000);
        } else {
            content.innerHTML = '<p style="color: #dc2626; padding: 40px;">Erro ao carregar QR Code.<br>Tente novamente.</p>';
        }
    } catch (e) {
        content.innerHTML = '<p style="color: #dc2626; padding: 40px;">Erro de conexão</p>';
    }
}

// Desconectar
async function disconnectInstance(name) {
    if (!confirm('Desconectar esta instância?')) return;
    
    showRefreshing(true);
    
    try {
        const response = await fetch('/settings/whatsapp/' + encodeURIComponent(name) + '/disconnect', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('Desconectado');
            refreshInstances();
        } else {
            showToast(data.error || 'Erro ao desconectar', 'error');
        }
    } catch (e) {
        showToast('Erro de conexão', 'error');
    } finally {
        showRefreshing(false);
    }
}

// Excluir
async function deleteInstance(name, id) {
    if (!confirm('Excluir a instância "' + name + '"?\nEsta ação não pode ser desfeita.')) return;
    
    showRefreshing(true);
    
    try {
        const formData = new FormData();
        formData.append('name', name);
        if (id) formData.append('id', id);
        
        const response = await fetch('/settings/whatsapp/' + encodeURIComponent(name) + '/delete', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('Instância excluída');
            refreshInstances();
        } else {
            showToast(data.error || 'Erro ao excluir', 'error');
        }
    } catch (e) {
        showToast('Erro de conexão', 'error');
    } finally {
        showRefreshing(false);
    }
}

// Fechar modais ao clicar fora
document.querySelectorAll('.modal-overlay').forEach(function(modal) {
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('active');
            if (modal.id === 'qrModal') {
                currentQrInstance = null;
                if (qrRefreshInterval) {
                    clearInterval(qrRefreshInterval);
                    qrRefreshInterval = null;
                }
            }
        }
    });
});

// Auto-refresh a cada 30s se tiver instâncias
<?php if ($hasConfig): ?>
setInterval(function() {
    if (!document.hidden && !document.querySelector('.modal-overlay.active')) {
        refreshInstances();
    }
}, 30000);
<?php endif; ?>
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
