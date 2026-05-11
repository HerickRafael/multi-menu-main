<?php
/**
 * Configuração da Instância WhatsApp - Mobile
 * Página de detalhes e configurações igual à versão desktop
 */
$instanceName = $instanceName ?? '';
$instanceData = $instanceData ?? [];
$slug = $_SERVER['MOBILE_SLUG'] ?? 'wollburger';

// Preparar dados
$status = $instanceData['status'] ?? 'disconnected';
$isConnected = $status === 'connected';
$statusClass = $status === 'connected' ? 'connected' : ($status === 'pending' ? 'pending' : 'disconnected');
$statusText = $status === 'connected' ? 'Conectado' : ($status === 'pending' ? 'Reconectando' : 'Desconectado');
$avatarLetters = strtoupper(substr($instanceData['profileName'] ?? $instanceName ?? 'WA', 0, 2));
$number = $instanceData['number'] ?? '';
$formattedNumber = $number ? preg_replace('/(\d{2})(\d{2})(\d{4,5})(\d{4})/', '+$1 ($2) $3-$4', $number) : 'Número não disponível';

ob_start();
?>

<style>
:root {
    --wa-green: #25D366;
    --wa-dark: #128C7E;
}

/* Instance Header */
.instance-header {
    background: linear-gradient(135deg, var(--wa-green) 0%, var(--wa-dark) 100%);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 16px;
    color: white;
}

.instance-header-content {
    display: flex;
    align-items: center;
    gap: 16px;
}

.instance-header-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 600;
    flex-shrink: 0;
    overflow: hidden;
}

.instance-header-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.instance-header-info {
    flex: 1;
    min-width: 0;
}

.instance-header-name {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.instance-header-number {
    font-size: 14px;
    opacity: 0.9;
}

/* Status Badge */
.status-badge-large {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 24px;
    font-size: 14px;
    font-weight: 600;
    margin-top: 12px;
}

.status-badge-large.connected {
    background: rgba(255,255,255,0.2);
    color: white;
}

.status-badge-large.pending {
    background: #fef3c7;
    color: #92400e;
}

.status-badge-large.disconnected {
    background: #fee2e2;
    color: #991b1b;
}

.status-dot-large {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.status-badge-large.connected .status-dot-large { background: white; }
.status-badge-large.pending .status-dot-large { background: #f59e0b; animation: pulse 1.5s infinite; }
.status-badge-large.disconnected .status-dot-large { background: #ef4444; }

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 16px;
}

.stat-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px 12px;
    text-align: center;
}

.stat-icon {
    width: 36px;
    height: 36px;
    margin: 0 auto 8px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-icon.blue { background: #dbeafe; color: #2563eb; }
.stat-icon.green { background: #d1fae5; color: #059669; }
.stat-icon.purple { background: var(--admin-primary-soft, #ede9fe); color: var(--admin-primary-color, #7c3aed); }

.stat-label {
    font-size: 11px;
    color: #6b7280;
    margin-bottom: 4px;
}

.stat-value {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
}

/* Connection Banner */
.connection-banner {
    background: #fef3c7;
    border: 1px solid #fcd34d;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.connection-banner-text {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    color: #92400e;
}

.connection-banner-text svg {
    flex-shrink: 0;
    color: #f59e0b;
}

.connection-banner-actions {
    display: flex;
    gap: 8px;
}

.btn-qr {
    flex: 1;
    padding: 12px;
    background: #f59e0b;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.btn-qr:active {
    background: #d97706;
}

/* Action Buttons */
.action-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 16px;
}

.action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 16px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    cursor: pointer;
}

.action-btn:active {
    background: #f9fafb;
    transform: scale(0.98);
}

.action-btn svg {
    width: 24px;
    height: 24px;
    color: var(--wa-green);
}

.action-btn.danger svg {
    color: #dc2626;
}

.action-btn.danger {
    color: #dc2626;
}

/* Settings Card */
.settings-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    margin-bottom: 16px;
    overflow: hidden;
}

.settings-card-header {
    padding: 16px;
    border-bottom: 1px solid #f3f4f6;
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 10px;
}

.settings-card-header svg {
    color: var(--wa-green);
}

.settings-card-body {
    padding: 16px;
}

/* Info List */
.info-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f3f4f6;
    font-size: 14px;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    color: #6b7280;
}

.info-value {
    color: #1f2937;
    font-weight: 500;
    text-align: right;
    max-width: 60%;
    word-break: break-word;
}

/* Token Box */
.token-box {
    background: #f9fafb;
    border-radius: 10px;
    padding: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.token-text {
    flex: 1;
    font-family: monospace;
    font-size: 12px;
    color: #6b7280;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.token-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: white;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
}

.token-btn:active {
    background: #e5e7eb;
}

/* Toggle Switch */
.toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #f3f4f6;
}

.toggle-row:last-child {
    border-bottom: none;
}

.toggle-info {
    flex: 1;
}

.toggle-label {
    font-size: 14px;
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 2px;
}

.toggle-desc {
    font-size: 12px;
    color: #6b7280;
}

.toggle-switch {
    position: relative;
    width: 50px;
    height: 28px;
    flex-shrink: 0;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background: #e5e7eb;
    border-radius: 28px;
    transition: 0.3s;
}

.toggle-slider:before {
    content: "";
    position: absolute;
    width: 22px;
    height: 22px;
    left: 3px;
    bottom: 3px;
    background: white;
    border-radius: 50%;
    transition: 0.3s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.toggle-switch input:checked + .toggle-slider {
    background: var(--wa-green);
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(22px);
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
    padding-bottom: calc(20px + 80px);
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
    min-height: 260px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
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

/* Toast */
#toast {
    position: fixed;
    bottom: 100px;
    left: 16px;
    right: 16px;
    z-index: 200;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.3s;
}
</style>

<!-- Instance Header -->
<div class="instance-header">
    <div class="instance-header-content">
        <div class="instance-header-avatar">
            <?php if (!empty($instanceData['profilePicUrl'])): ?>
                <img src="<?= htmlspecialchars($instanceData['profilePicUrl']) ?>" alt="" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <span style="display:none"><?= htmlspecialchars($avatarLetters) ?></span>
            <?php else: ?>
                <?= htmlspecialchars($avatarLetters) ?>
            <?php endif; ?>
        </div>
        <div class="instance-header-info">
            <div class="instance-header-name"><?= htmlspecialchars($instanceData['profileName'] ?: $instanceName) ?></div>
            <div class="instance-header-number"><?= htmlspecialchars($formattedNumber) ?></div>
        </div>
    </div>
    <span class="status-badge-large <?= $statusClass ?>">
        <span class="status-dot-large"></span>
        <?= $statusText ?>
    </span>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                <circle cx="8.5" cy="7" r="4"/>
            </svg>
        </div>
        <div class="stat-label">Contatos</div>
        <div class="stat-value"><?= number_format($instanceData['contacts'] ?? 0) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
            </svg>
        </div>
        <div class="stat-label">Chats</div>
        <div class="stat-value"><?= number_format($instanceData['chats'] ?? 0) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v6a2 2 0 01-2 2h-3l-4 4z"/>
            </svg>
        </div>
        <div class="stat-label">Mensagens</div>
        <div class="stat-value"><?= number_format($instanceData['messages'] ?? 0) ?></div>
    </div>
</div>

<?php if (!$isConnected): ?>
<!-- Connection Banner (when disconnected) -->
<div class="connection-banner">
    <div class="connection-banner-text">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 8v4M12 16h.01"/>
        </svg>
        <span>Para conectar, escaneie o QR code com seu WhatsApp</span>
    </div>
    <div class="connection-banner-actions">
        <button class="btn-qr" onclick="showQrCode()">Obter QR Code</button>
    </div>
</div>
<?php endif; ?>

<!-- Action Buttons -->
<div class="action-buttons">
    <button class="action-btn" onclick="showQrCode()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="3" width="7" height="7"/>
            <rect x="14" y="3" width="7" height="7"/>
            <rect x="14" y="14" width="7" height="7"/>
            <rect x="3" y="14" width="7" height="7"/>
        </svg>
        QR Code
    </button>
    <button class="action-btn" onclick="restartInstance()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M23 4v6h-6M1 20v-6h6"/>
            <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
        </svg>
        Reiniciar
    </button>
    <button class="action-btn" onclick="disconnectInstance()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M18.36 6.64a9 9 0 11-12.73 0"/>
            <line x1="12" y1="2" x2="12" y2="12"/>
        </svg>
        Desconectar
    </button>
    <button class="action-btn danger" onclick="deleteInstance()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
        </svg>
        Excluir
    </button>
</div>

<!-- Instance Info -->
<div class="settings-card">
    <div class="settings-card-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 16v-4M12 8h.01"/>
        </svg>
        Informações da Instância
    </div>
    <div class="settings-card-body">
        <ul class="info-list">
            <li class="info-item">
                <span class="info-label">Cliente</span>
                <span class="info-value"><?= htmlspecialchars($instanceData['clientName'] ?: $instanceName) ?></span>
            </li>
            <li class="info-item">
                <span class="info-label">Integração</span>
                <span class="info-value"><?= htmlspecialchars($instanceData['integration'] ?? 'WHATSAPP-BAILEYS') ?></span>
            </li>
            <li class="info-item">
                <span class="info-label">Criado em</span>
                <span class="info-value"><?= !empty($instanceData['createdAt']) ? date('d/m/Y H:i', strtotime($instanceData['createdAt'])) : 'N/A' ?></span>
            </li>
            <li class="info-item">
                <span class="info-label">Última atualização</span>
                <span class="info-value"><?= !empty($instanceData['updatedAt']) ? date('d/m/Y H:i', strtotime($instanceData['updatedAt'])) : 'N/A' ?></span>
            </li>
        </ul>
    </div>
</div>

<!-- Token -->
<div class="settings-card">
    <div class="settings-card-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0110 0v4"/>
        </svg>
        Token da Instância
    </div>
    <div class="settings-card-body">
        <div class="token-box">
            <span class="token-text" id="tokenText" data-visible="0"><?= str_repeat('•', 32) ?></span>
            <button class="token-btn" onclick="toggleToken()" title="Mostrar/Ocultar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
            </button>
            <button class="token-btn" onclick="copyToken()" title="Copiar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                    <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<!-- Configurações Avançadas (Evolution API) -->
<div class="settings-card">
    <div class="settings-card-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="3"/>
            <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/>
        </svg>
        Configurações Avançadas
    </div>
    <div class="settings-card-body">
        <div class="toggle-row" id="toggleRejectCallsRow">
            <div class="toggle-info">
                <div class="toggle-label">Rejeitar chamadas <a href="/guide/whatsapp#settings" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:4px;line-height:1;" title="Ajuda">?</a></div>
                <div class="toggle-desc">Recusar automaticamente chamadas recebidas</div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="toggleRejectCalls" onchange="saveApiSetting('rejectCall', this.checked)">
                <span class="toggle-slider"></span>
            </label>
        </div>
        
        <!-- Campo de mensagem ao rejeitar chamada -->
        <div id="rejectCallMessageContainer" class="hidden" style="padding: 12px; background: #f9fafb; border-radius: 10px; margin-bottom: 12px;">
            <label style="font-size: 13px; font-weight: 500; color: #374151; display: block; margin-bottom: 8px;">Mensagem ao rejeitar</label>
            <textarea id="rejectCallMessage" placeholder="Mensagem enviada quando uma chamada for rejeitada..." style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; resize: none;" rows="2"></textarea>
            <button onclick="saveRejectCallMessage()" style="margin-top: 8px; padding: 8px 16px; background: var(--wa-green); color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 500;">Salvar Mensagem</button>
        </div>
        
        <div class="toggle-row">
            <div class="toggle-info">
                <div class="toggle-label">Ler mensagens</div>
                <div class="toggle-desc">Marcar como lidas automaticamente</div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="toggleReadMessages" onchange="saveApiSetting('readMessages', this.checked)">
                <span class="toggle-slider"></span>
            </label>
        </div>
        
        <div class="toggle-row">
            <div class="toggle-info">
                <div class="toggle-label">Sempre online</div>
                <div class="toggle-desc">Manter status online constantemente</div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="toggleAlwaysOnline" onchange="saveApiSetting('alwaysOnline', this.checked)">
                <span class="toggle-slider"></span>
            </label>
        </div>
        
        <div class="toggle-row">
            <div class="toggle-info">
                <div class="toggle-label">Ignorar grupos</div>
                <div class="toggle-desc">Não processar mensagens de grupos</div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="toggleGroupsIgnore" onchange="saveApiSetting('groupsIgnore', this.checked)">
                <span class="toggle-slider"></span>
            </label>
        </div>
        
        <div class="toggle-row">
            <div class="toggle-info">
                <div class="toggle-label">Visualizar status</div>
                <div class="toggle-desc">Marcar status como visualizado</div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="toggleReadStatus" onchange="saveApiSetting('readStatus', this.checked)">
                <span class="toggle-slider"></span>
            </label>
        </div>
        
        <div class="toggle-row" style="border-bottom: none;">
            <div class="toggle-info">
                <div class="toggle-label">Sincronizar histórico</div>
                <div class="toggle-desc">Sincronizar histórico completo</div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="toggleSyncFullHistory" onchange="saveApiSetting('syncFullHistory', this.checked)">
                <span class="toggle-slider"></span>
            </label>
        </div>
    </div>
</div>

<!-- Notificação de Pedido -->
<div class="settings-card">
    <div class="settings-card-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 01-3.46 0"/>
        </svg>
        Notificação de Pedido
    </div>
    <div class="settings-card-body">
        <div class="toggle-row">
            <div class="toggle-info">
                <div class="toggle-label">Notificar novos pedidos <a href="/guide/whatsapp#notifications" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:4px;line-height:1;" title="Ajuda">?</a></div>
                <div class="toggle-desc">Enviar mensagem para números cadastrados</div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="toggleOrderNotification" onchange="toggleOrderNotificationConfig()">
                <span class="toggle-slider"></span>
            </label>
        </div>
        
        <!-- Container de números (oculto por padrão) -->
        <div id="orderNotificationConfig" class="hidden" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #f3f4f6;">
            <!-- Aviso -->
            <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 10px; padding: 12px; margin-bottom: 16px;">
                <div style="display: flex; align-items: flex-start; gap: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="1.5" style="flex-shrink: 0; margin-top: 2px;">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 8v4M12 16h.01"/>
                    </svg>
                    <div>
                        <div style="font-size: 13px; font-weight: 600; color: #92400e;">Grupos em manutenção</div>
                        <div style="font-size: 12px; color: #a16207; margin-top: 2px;">Notificações via números individuais.</div>
                    </div>
                </div>
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="font-size: 13px; font-weight: 500; color: #374151; display: block; margin-bottom: 8px;">Número Principal</label>
                <input type="tel" id="orderNotificationNumber1" placeholder="(51) 99999-9999" 
                    style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;" 
                    maxlength="15" oninput="applyPhoneMask(this)">
                <p id="number1Status" class="hidden" style="font-size: 12px; margin-top: 4px;"></p>
            </div>
            
            <div>
                <label style="font-size: 13px; font-weight: 500; color: #374151; display: block; margin-bottom: 8px;">Número Secundário (opcional)</label>
                <input type="tel" id="orderNotificationNumber2" placeholder="(51) 99999-9999" 
                    style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;" 
                    maxlength="15" oninput="applyPhoneMask(this)">
                <p id="number2Status" class="hidden" style="font-size: 12px; margin-top: 4px;"></p>
            </div>
        </div>
    </div>
</div>

<!-- Engajamento Automático de Clientes -->
<div class="settings-card">
    <div class="settings-card-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
        </svg>
        Engajamento de Clientes
        <span style="margin-left: auto; background: #d1fae5; color: #065f46; font-size: 10px; font-weight: 600; padding: 3px 8px; border-radius: 12px;">Novo</span>
    </div>
    <div class="settings-card-body">
        <div class="toggle-row">
            <div class="toggle-info">
                <div class="toggle-label">Ativar engajamento automático</div>
                <a href="/guide/whatsapp#engagement" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:6px;line-height:1;" title="Ajuda">?</a>
                <div class="toggle-desc">Mensagens automáticas para recuperar clientes</div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="toggleEngagement" onchange="toggleEngagementConfig()">
                <span class="toggle-slider"></span>
            </label>
        </div>
        
        <!-- Container de configurações (oculto por padrão) -->
        <div id="engagementConfig" class="hidden" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #f3f4f6;">
            
            <!-- Cenário 1 -->
            <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; margin-bottom: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 32px; height: 32px; background: #dbeafe; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.5">
                                <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                <circle cx="8.5" cy="7" r="4"/>
                                <line x1="20" y1="8" x2="20" y2="14"/>
                                <line x1="23" y1="11" x2="17" y2="11"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size: 13px; font-weight: 600; color: #1f2937;">Cadastro sem pedido</div>
                            <div style="font-size: 11px; color: #6b7280;">Cliente que não finalizou o primeiro pedido</div>
                        </div>
                    </div>
                    <label class="toggle-switch" style="width: 44px; height: 24px;">
                        <input type="checkbox" id="toggleScenario1" checked onchange="saveEngagementConfig()">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div id="scenario1Config" style="padding-left: 42px;">
                    <label style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 4px;">Tempo de espera</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="number" id="scenario1Delay" value="10" min="5" max="60" 
                            style="width: 70px; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; text-align: center;"
                            onchange="saveEngagementConfig()">
                        <span style="font-size: 12px; color: #6b7280;">minutos após cadastro</span>
                    </div>
                </div>
            </div>
            
            <!-- Cenário 2 -->
            <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; margin-bottom: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 32px; height: 32px; background: #fef3c7; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="1.5">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size: 13px; font-weight: 600; color: #1f2937;">Cliente inativo</div>
                            <div style="font-size: 11px; color: #6b7280;">Cliente que não faz pedidos há tempo</div>
                        </div>
                    </div>
                    <label class="toggle-switch" style="width: 44px; height: 24px;">
                        <input type="checkbox" id="toggleScenario2" checked onchange="saveEngagementConfig()">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div id="scenario2Config" style="padding-left: 42px;">
                    <label style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 4px;">Período de inatividade</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="number" id="scenario2Days" value="15" min="7" max="90" 
                            style="width: 70px; padding: 8px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; text-align: center;"
                            onchange="saveEngagementConfig()">
                        <span style="font-size: 12px; color: #6b7280;">dias sem pedidos</span>
                    </div>
                </div>
            </div>
            
            <!-- Info -->
            <div style="background: #d1fae5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 12px;">
                <div style="display: flex; align-items: flex-start; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.5" style="flex-shrink: 0; margin-top: 2px;">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4M12 8h.01"/>
                    </svg>
                    <div style="font-size: 11px; color: #065f46; line-height: 1.5;">
                        Mensagens são enviadas apenas no horário de funcionamento. Cada cliente recebe no máximo 1 mensagem por cenário a cada 30 dias.
                    </div>
                </div>
            </div>
            
            <!-- Estatísticas -->
            <div id="engagementStats" class="hidden" style="margin-top: 12px; background: white; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px;">
                <div style="font-size: 11px; font-weight: 500; color: #6b7280; margin-bottom: 8px;">Últimos 30 dias</div>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; text-align: center;">
                    <div>
                        <div id="statsTotalSent" style="font-size: 18px; font-weight: 700; color: #1f2937;">0</div>
                        <div style="font-size: 10px; color: #6b7280;">Enviadas</div>
                    </div>
                    <div>
                        <div id="statsScenario1" style="font-size: 18px; font-weight: 700; color: #2563eb;">0</div>
                        <div style="font-size: 10px; color: #6b7280;">Cenário 1</div>
                    </div>
                    <div>
                        <div id="statsScenario2" style="font-size: 18px; font-weight: 700; color: #d97706;">0</div>
                        <div style="font-size: 10px; color: #6b7280;">Cenário 2</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resposta Fora do Expediente -->
<div class="settings-card">
    <div class="settings-card-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/>
        </svg>
        Resposta Fora do Expediente
    </div>
    <div class="settings-card-body">
        <div class="toggle-row">
            <div class="toggle-info">
                <div class="toggle-label">Ativar resposta automática</div>
                <div class="toggle-desc">Responder quando cliente enviar mensagem fora do horário de funcionamento</div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="toggleOutOfHours" onchange="toggleOutOfHoursConfig()">
                <span class="toggle-slider"></span>
            </label>
        </div>
        
        <!-- Container de configurações (oculto por padrão) -->
        <div id="outOfHoursConfig" class="hidden" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #f3f4f6;">
            
            <!-- Mensagem personalizada -->
            <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; margin-bottom: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <label style="font-size: 13px; font-weight: 600; color: #1f2937;">Mensagem personalizada</label>
                    <button type="button" onclick="useDefaultOutOfHoursMessage()" style="font-size: 11px; color: #9333ea; background: none; border: none; font-weight: 500; cursor: pointer;">
                        Usar padrão
                    </button>
                </div>
                <textarea id="outOfHoursMessage" 
                    style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px; resize: none; min-height: 80px;"
                    placeholder="Deixe em branco para usar a mensagem padrão. Use {saudacao}, {dia}, {hora}"
                ></textarea>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 8px;">
                    <span style="font-size: 10px; color: #9ca3af;">Variáveis: {saudacao}, {dia}, {hora}</span>
                    <button type="button" onclick="saveOutOfHoursMessage()" style="padding: 6px 12px; font-size: 11px; font-weight: 600; background: #9333ea; color: white; border: none; border-radius: 8px; cursor: pointer;">
                        Salvar
                    </button>
                </div>
            </div>
            
            <!-- Exemplo -->
            <div style="background: #f3e8ff; border: 1px solid #e9d5ff; border-radius: 10px; padding: 12px; margin-bottom: 12px;">
                <p style="font-size: 11px; color: #7c3aed; margin-bottom: 4px; font-weight: 500;">Exemplo da mensagem padrão:</p>
                <p style="font-size: 11px; color: #6b21a8; font-style: italic; line-height: 1.4;">"Boa noite! 😊 Obrigado por entrar em contato! No momento estamos fora do horário de atendimento. Voltamos amanhã às 19:00. Assim que abrirmos, retornaremos sua mensagem! 🙌"</p>
            </div>
            
            <!-- Info -->
            <div style="background: #f3e8ff; border: 1px solid #e9d5ff; border-radius: 10px; padding: 12px;">
                <div style="display: flex; align-items: flex-start; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9333ea" stroke-width="1.5" style="flex-shrink: 0; margin-top: 2px;">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4M12 8h.01"/>
                    </svg>
                    <div style="font-size: 11px; color: #7c3aed; line-height: 1.5;">
                        Detecta automaticamente quando a loja está fechada. Cooldown de 30 minutos entre respostas para o mesmo cliente.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resposta em Pausa Programada -->
<div class="settings-card">
    <div class="settings-card-header">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="1.5">
            <path d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Resposta em Pausa Programada
    </div>
    <div class="settings-card-body">
        <div class="toggle-row">
            <div class="toggle-info">
                <div class="toggle-label">Ativar resposta automática</div>
                <div class="toggle-desc">Responder automaticamente quando a loja estiver em pausa programada</div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="toggleScheduledPause" onchange="toggleScheduledPauseConfig()">
                <span class="toggle-slider"></span>
            </label>
        </div>
        
        <!-- Container de configurações (oculto por padrão) -->
        <div id="scheduledPauseConfig" class="hidden" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #f3f4f6;">
            
            <!-- Mensagem personalizada -->
            <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; margin-bottom: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <label style="font-size: 13px; font-weight: 600; color: #1f2937;">Mensagem personalizada</label>
                    <button type="button" onclick="useDefaultScheduledPauseMessage()" style="font-size: 11px; color: #ea580c; background: none; border: none; font-weight: 500; cursor: pointer;">
                        Usar padrão
                    </button>
                </div>
                <textarea id="scheduledPauseMessage" 
                    style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px; resize: none; min-height: 80px;"
                    placeholder="Deixe em branco para usar a mensagem padrão. Use {motivo} para o motivo da pausa e {tempo_restante} para o tempo restante."
                ></textarea>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 8px;">
                    <span style="font-size: 10px; color: #9ca3af;">Variáveis: {motivo}, {tempo_restante}</span>
                    <button type="button" onclick="saveScheduledPauseMessage()" style="padding: 6px 12px; font-size: 11px; font-weight: 600; background: #ea580c; color: white; border: none; border-radius: 8px; cursor: pointer;">
                        Salvar
                    </button>
                </div>
            </div>
            
            <!-- Exemplo -->
            <div style="background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px; padding: 12px; margin-bottom: 12px;">
                <p style="font-size: 11px; color: #c2410c; margin-bottom: 4px; font-weight: 500;">Exemplo da mensagem padrão:</p>
                <p style="font-size: 11px; color: #9a3412; font-style: italic; line-height: 1.4;">"Olá! 👋 A loja está temporariamente em pausa. Voltaremos em aproximadamente 30 minutos. Aguardamos seu retorno! 🙏"</p>
            </div>
            
            <!-- Info -->
            <div style="background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px; padding: 12px;">
                <div style="display: flex; align-items: flex-start; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="1.5" style="flex-shrink: 0; margin-top: 2px;">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4M12 8h.01"/>
                    </svg>
                    <div style="font-size: 11px; color: #c2410c; line-height: 1.5;">
                        <p style="font-weight: 500; margin-bottom: 4px;">Como funciona:</p>
                        <ul style="list-style: disc; padding-left: 16px; margin: 0;">
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

<!-- Modal QR Code -->
<div class="modal-overlay" id="qrModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Conectar WhatsApp</h2>
            <button class="modal-close" onclick="closeQrModal()">&times;</button>
        </div>
        <div class="qr-container">
            <div class="qr-box" id="qrContent">
                <div class="loading-spinner"></div>
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
<div id="toast"></div>

<script>
const instanceName = '<?= htmlspecialchars($instanceName) ?>';
const instanceToken = '<?= htmlspecialchars($instanceData['token'] ?? '') ?>';
let qrRefreshInterval = null;
let currentApiSettings = {};
let verifyTimeout1 = null;
let verifyTimeout2 = null;

// Toast
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const bgColor = type === 'error' ? '#fee2e2' : (type === 'warning' ? '#fef3c7' : '#d1fae5');
    const textColor = type === 'error' ? '#991b1b' : (type === 'warning' ? '#92400e' : '#065f46');
    
    toast.innerHTML = '<div style="background: ' + bgColor + '; color: ' + textColor + '; padding: 12px 16px; border-radius: 12px; font-size: 14px; text-align: center;">' + message + '</div>';
    toast.style.opacity = '1';
    
    setTimeout(function() {
        toast.style.opacity = '0';
    }, 3000);
}

// Token
function toggleToken() {
    const el = document.getElementById('tokenText');
    if (el.dataset.visible === '0') {
        el.textContent = instanceToken || 'N/A';
        el.dataset.visible = '1';
    } else {
        el.textContent = '••••••••••••••••••••••••••••••••';
        el.dataset.visible = '0';
    }
}

function copyToken() {
    navigator.clipboard.writeText(instanceToken).then(function() {
        showToast('Token copiado!');
    });
}

// ================== API Settings ==================
async function loadApiSettings() {
    try {
        const response = await fetch('/settings/whatsapp/' + encodeURIComponent(instanceName) + '/api-settings', {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success && data.data) {
            currentApiSettings = data.data;
            
            // Atualizar toggles
            const toggles = {
                'toggleRejectCalls': 'rejectCall',
                'toggleReadMessages': 'readMessages',
                'toggleAlwaysOnline': 'alwaysOnline',
                'toggleGroupsIgnore': 'groupsIgnore',
                'toggleReadStatus': 'readStatus',
                'toggleSyncFullHistory': 'syncFullHistory'
            };
            
            for (const [toggleId, settingKey] of Object.entries(toggles)) {
                const toggle = document.getElementById(toggleId);
                if (toggle) {
                    toggle.checked = !!data.data[settingKey];
                }
            }
            
            // Mostrar campo de mensagem se rejeitar chamadas está ativo
            if (data.data.rejectCall) {
                document.getElementById('rejectCallMessageContainer').classList.remove('hidden');
            }
            if (data.data.msgCall) {
                document.getElementById('rejectCallMessage').value = data.data.msgCall;
            }
        }
    } catch (e) {
        console.error('Erro ao carregar configurações:', e);
    }
}

async function saveApiSetting(settingKey, value) {
    try {
        // Atualizar objeto local
        currentApiSettings[settingKey] = value;
        
        const response = await fetch('/settings/whatsapp/' + encodeURIComponent(instanceName) + '/api-settings', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest' 
            },
            body: JSON.stringify(currentApiSettings)
        });
        const data = await response.json();
        
        if (data.success) {
            // Toggle especial para rejeitar chamadas - mostrar/ocultar campo de mensagem
            if (settingKey === 'rejectCall') {
                const container = document.getElementById('rejectCallMessageContainer');
                if (value) {
                    container.classList.remove('hidden');
                } else {
                    container.classList.add('hidden');
                }
            }
        } else {
            showToast(data.error || 'Erro ao salvar', 'error');
        }
    } catch (e) {
        showToast('Erro de conexão', 'error');
    }
}

async function saveRejectCallMessage() {
    const message = document.getElementById('rejectCallMessage').value.trim();
    currentApiSettings.msgCall = message;
    
    try {
        const response = await fetch('/settings/whatsapp/' + encodeURIComponent(instanceName) + '/api-settings', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest' 
            },
            body: JSON.stringify(currentApiSettings)
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('Mensagem salva!');
        } else {
            showToast(data.error || 'Erro ao salvar', 'error');
        }
    } catch (e) {
        showToast('Erro de conexão', 'error');
    }
}

// ================== Order Notification ==================
async function loadOrderNotificationConfig() {
    try {
        const response = await fetch('/settings/whatsapp/' + encodeURIComponent(instanceName) + '/order-notification', {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success && data.data) {
            const toggle = document.getElementById('toggleOrderNotification');
            const config = document.getElementById('orderNotificationConfig');
            
            if (toggle && data.data.enabled) {
                toggle.checked = true;
                config.classList.remove('hidden');
            }
            
            if (data.data.primary_number) {
                document.getElementById('orderNotificationNumber1').value = formatPhoneForDisplay(data.data.primary_number);
                // Verificar automaticamente após carregar
                setTimeout(() => verifyWhatsAppNumber('orderNotificationNumber1', 'number1Status'), 500);
            }
            if (data.data.secondary_number) {
                document.getElementById('orderNotificationNumber2').value = formatPhoneForDisplay(data.data.secondary_number);
                // Verificar automaticamente após carregar
                setTimeout(() => verifyWhatsAppNumber('orderNotificationNumber2', 'number2Status'), 800);
            }
        }
    } catch (e) {
        console.error('Erro ao carregar configuração de notificação:', e);
    }
}

function toggleOrderNotificationConfig() {
    const toggle = document.getElementById('toggleOrderNotification');
    const config = document.getElementById('orderNotificationConfig');
    
    if (toggle.checked) {
        config.classList.remove('hidden');
    } else {
        config.classList.add('hidden');
    }
    
    saveOrderNotificationConfig();
}

async function saveOrderNotificationConfig() {
    const toggle = document.getElementById('toggleOrderNotification');
    const number1 = unmaskPhone(document.getElementById('orderNotificationNumber1').value);
    const number2 = unmaskPhone(document.getElementById('orderNotificationNumber2').value);
    
    try {
        const response = await fetch('/settings/whatsapp/' + encodeURIComponent(instanceName) + '/order-notification', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest' 
            },
            body: JSON.stringify({
                enabled: toggle.checked,
                primary_number: number1 ? '55' + number1 : '',
                secondary_number: number2 ? '55' + number2 : '',
                force_switch: true
            })
        });
        const data = await response.json();
        
        if (!data.success) {
            showToast(data.error || 'Erro ao salvar', 'error');
        }
    } catch (e) {
        showToast('Erro de conexão', 'error');
    }
}

// Máscara de telefone
function applyPhoneMask(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 11) value = value.substring(0, 11);
    
    if (value.length > 0) value = '(' + value;
    if (value.length > 3) value = value.substring(0, 3) + ') ' + value.substring(3);
    if (value.length > 10) value = value.substring(0, 10) + '-' + value.substring(10);
    
    input.value = value;
    
    // Verificação automática com debounce
    const inputId = input.id;
    const statusId = inputId === 'orderNotificationNumber1' ? 'number1Status' : 'number2Status';
    
    if (inputId === 'orderNotificationNumber1') {
        clearTimeout(verifyTimeout1);
        verifyTimeout1 = setTimeout(() => verifyWhatsAppNumber(inputId, statusId), 1500);
    } else {
        clearTimeout(verifyTimeout2);
        verifyTimeout2 = setTimeout(() => verifyWhatsAppNumber(inputId, statusId), 1500);
    }
}

function unmaskPhone(phone) {
    return phone.replace(/\D/g, '');
}

function formatPhoneForDisplay(phone) {
    let digits = String(phone).replace(/\D/g, '');
    if (digits.startsWith('55') && digits.length >= 12) {
        digits = digits.substring(2);
    }
    if (digits.length === 11) {
        return '(' + digits.substring(0, 2) + ') ' + digits.substring(2, 7) + '-' + digits.substring(7);
    } else if (digits.length === 10) {
        return '(' + digits.substring(0, 2) + ') ' + digits.substring(2, 6) + '-' + digits.substring(6);
    }
    return phone;
}

async function verifyWhatsAppNumber(inputId, statusId) {
    const input = document.getElementById(inputId);
    const status = document.getElementById(statusId);
    const number = unmaskPhone(input.value);
    
    if (number.length < 10) {
        status.classList.add('hidden');
        return;
    }
    
    status.textContent = 'Verificando...';
    status.className = '';
    status.style.color = '#6b7280';
    status.classList.remove('hidden');
    
    try {
        const response = await fetch('/settings/whatsapp/' + encodeURIComponent(instanceName) + '/validate-number', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest' 
            },
            body: JSON.stringify({ number: '55' + number })
        });
        const data = await response.json();
        
        if (data.success && data.exists) {
            status.textContent = '✓ Número válido no WhatsApp';
            status.style.color = '#059669';
            saveOrderNotificationConfig();
        } else if (data.checked === false) {
            status.textContent = '⚠ Não foi possível verificar';
            status.style.color = '#d97706';
            saveOrderNotificationConfig();
        } else {
            status.textContent = '✗ Número não existe no WhatsApp';
            status.style.color = '#dc2626';
        }
    } catch (e) {
        status.textContent = 'Erro ao verificar';
        status.style.color = '#dc2626';
    }
}

// ================== Engagement ==================
async function loadEngagementConfig() {
    try {
        const response = await fetch('/settings/whatsapp/' + encodeURIComponent(instanceName) + '/engagement', {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success && data.data) {
            const toggle = document.getElementById('toggleEngagement');
            const config = document.getElementById('engagementConfig');
            
            if (toggle && data.data.enabled) {
                toggle.checked = true;
                config.classList.remove('hidden');
            }
            
            document.getElementById('toggleScenario1').checked = data.data.scenario1_enabled !== false;
            document.getElementById('scenario1Delay').value = data.data.scenario1_delay || 10;
            document.getElementById('toggleScenario2').checked = data.data.scenario2_enabled !== false;
            document.getElementById('scenario2Days').value = data.data.scenario2_days || 15;
            
            // Estatísticas
            if (data.data.stats && data.data.stats.total > 0) {
                document.getElementById('engagementStats').classList.remove('hidden');
                document.getElementById('statsTotalSent').textContent = data.data.stats.total;
                document.getElementById('statsScenario1').textContent = data.data.stats.scenario1;
                document.getElementById('statsScenario2').textContent = data.data.stats.scenario2;
            }
        }
    } catch (e) {
        console.error('Erro ao carregar configuração de engajamento:', e);
    }
}

function toggleEngagementConfig() {
    const toggle = document.getElementById('toggleEngagement');
    const config = document.getElementById('engagementConfig');
    
    if (toggle.checked) {
        config.classList.remove('hidden');
    } else {
        config.classList.add('hidden');
    }
    
    saveEngagementConfig();
}

// ================== Fora do Expediente ==================
function toggleOutOfHoursConfig() {
    const toggle = document.getElementById('toggleOutOfHours');
    const config = document.getElementById('outOfHoursConfig');
    
    if (toggle.checked) {
        config.classList.remove('hidden');
    } else {
        config.classList.add('hidden');
    }
    
    saveOutOfHoursConfig();
}

async function saveOutOfHoursConfig() {
    const toggle = document.getElementById('toggleOutOfHours');
    const messageTextarea = document.getElementById('outOfHoursMessage');
    
    try {
        const response = await fetch('/settings/whatsapp/' + encodeURIComponent(instanceName) + '/out-of-hours', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest' 
            },
            body: JSON.stringify({
                enabled: toggle.checked,
                message: messageTextarea ? messageTextarea.value.trim() : ''
            })
        });
        const data = await response.json();
        
        if (!data.success) {
            showToast(data.error || 'Erro ao salvar', 'error');
        }
    } catch (e) {
        showToast('Erro de conexão', 'error');
    }
}

async function saveOutOfHoursMessage() {
    await saveOutOfHoursConfig();
    showToast('Mensagem salva!', 'success');
}

function useDefaultOutOfHoursMessage() {
    const textarea = document.getElementById('outOfHoursMessage');
    if (textarea) {
        textarea.value = '';
        saveOutOfHoursMessage();
    }
}

async function loadOutOfHoursConfig() {
    try {
        const response = await fetch('/settings/whatsapp/' + encodeURIComponent(instanceName) + '/out-of-hours', {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success && data.data) {
            const toggle = document.getElementById('toggleOutOfHours');
            const config = document.getElementById('outOfHoursConfig');
            const messageTextarea = document.getElementById('outOfHoursMessage');
            
            if (toggle) {
                toggle.checked = data.data.enabled || false;
                if (toggle.checked && config) {
                    config.classList.remove('hidden');
                }
            }
            
            if (messageTextarea && data.data.message) {
                messageTextarea.value = data.data.message;
            }
        }
    } catch (e) {
        console.error('Erro ao carregar configuração de fora do expediente:', e);
    }
}

async function saveEngagementConfig() {
    const toggle = document.getElementById('toggleEngagement');
    
    try {
        const response = await fetch('/settings/whatsapp/' + encodeURIComponent(instanceName) + '/engagement', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest' 
            },
            body: JSON.stringify({
                enabled: toggle.checked,
                scenario1_enabled: document.getElementById('toggleScenario1').checked,
                scenario1_delay: parseInt(document.getElementById('scenario1Delay').value) || 10,
                scenario2_enabled: document.getElementById('toggleScenario2').checked,
                scenario2_days: parseInt(document.getElementById('scenario2Days').value) || 15
            })
        });
        const data = await response.json();
        
        if (!data.success) {
            showToast(data.error || 'Erro ao salvar', 'error');
        }
    } catch (e) {
        showToast('Erro de conexão', 'error');
    }
}

// ================== Pausa Programada ==================
function toggleScheduledPauseConfig() {
    const toggle = document.getElementById('toggleScheduledPause');
    const config = document.getElementById('scheduledPauseConfig');
    
    if (toggle.checked) {
        config.classList.remove('hidden');
    } else {
        config.classList.add('hidden');
    }
    
    saveScheduledPauseConfig();
}

async function saveScheduledPauseConfig() {
    const toggle = document.getElementById('toggleScheduledPause');
    const messageTextarea = document.getElementById('scheduledPauseMessage');
    
    try {
        const response = await fetch('/settings/whatsapp/' + encodeURIComponent(instanceName) + '/scheduled-pause', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest' 
            },
            body: JSON.stringify({
                enabled: toggle.checked,
                message: messageTextarea ? messageTextarea.value.trim() : ''
            })
        });
        const data = await response.json();
        
        if (!data.success) {
            showToast(data.error || 'Erro ao salvar', 'error');
        }
    } catch (e) {
        showToast('Erro de conexão', 'error');
    }
}

async function saveScheduledPauseMessage() {
    await saveScheduledPauseConfig();
    showToast('Mensagem salva!', 'success');
}

function useDefaultScheduledPauseMessage() {
    const textarea = document.getElementById('scheduledPauseMessage');
    if (textarea) {
        textarea.value = '';
        saveScheduledPauseMessage();
    }
}

async function loadScheduledPauseConfig() {
    try {
        const response = await fetch('/settings/whatsapp/' + encodeURIComponent(instanceName) + '/scheduled-pause', {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success && data.data) {
            const toggle = document.getElementById('toggleScheduledPause');
            const config = document.getElementById('scheduledPauseConfig');
            const messageTextarea = document.getElementById('scheduledPauseMessage');
            
            if (toggle) {
                toggle.checked = data.data.enabled || false;
                if (toggle.checked && config) {
                    config.classList.remove('hidden');
                }
            }
            
            if (messageTextarea && data.data.message) {
                messageTextarea.value = data.data.message;
            }
        }
    } catch (e) {
        console.error('Erro ao carregar configuração de pausa programada:', e);
    }
}

// ================== QR Code Modal ==================
function showQrCode() {
    document.getElementById('qrModal').classList.add('active');
    loadQrCode();
}

function closeQrModal() {
    document.getElementById('qrModal').classList.remove('active');
    if (qrRefreshInterval) {
        clearInterval(qrRefreshInterval);
        qrRefreshInterval = null;
    }
}

async function loadQrCode() {
    const content = document.getElementById('qrContent');
    content.innerHTML = '<div class="loading-spinner"></div><p style="color: #6b7280; margin-top: 12px;">Carregando QR Code...</p>';
    
    try {
        const response = await fetch('/settings/whatsapp/' + encodeURIComponent(instanceName) + '/qrcode', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.status === 'connected') {
            content.innerHTML = '<div style="padding: 40px;">' +
                '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.5" style="margin: 0 auto 12px; display: block;">' +
                    '<path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>' +
                    '<polyline points="22 4 12 14.01 9 11.01"/>' +
                '</svg>' +
                '<p style="font-weight: 600; color: #059669; text-align: center;">Já está conectado!</p>' +
            '</div>';
            setTimeout(function() { location.reload(); }, 2000);
        } else if (data.qrcode) {
            content.innerHTML = '<img src="' + data.qrcode + '" alt="QR Code" style="max-width: 220px;">';
            
            if (qrRefreshInterval) clearInterval(qrRefreshInterval);
            qrRefreshInterval = setInterval(loadQrCode, 15000);
        } else {
            content.innerHTML = '<p style="color: #dc2626; padding: 40px; text-align: center;">Erro ao carregar QR Code.<br>Tente novamente.</p>';
        }
    } catch (e) {
        content.innerHTML = '<p style="color: #dc2626; padding: 40px; text-align: center;">Erro de conexão</p>';
    }
}

// ================== Instance Actions ==================
async function restartInstance() {
    showToast('Reiniciando...');
    
    try {
        await fetch('/settings/whatsapp/' + encodeURIComponent(instanceName) + '/restart', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        setTimeout(function() {
            showQrCode();
        }, 1000);
    } catch (e) {
        showToast('Erro ao reiniciar', 'error');
    }
}

async function disconnectInstance() {
    if (!confirm('Desconectar esta instância?')) return;
    
    try {
        const response = await fetch('/settings/whatsapp/' + encodeURIComponent(instanceName) + '/disconnect', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('Desconectado');
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            showToast(data.error || 'Erro', 'error');
        }
    } catch (e) {
        showToast('Erro de conexão', 'error');
    }
}

async function deleteInstance() {
    if (!confirm('Excluir a instância "' + instanceName + '"?\nEsta ação não pode ser desfeita.')) return;
    
    try {
        const response = await fetch('/settings/whatsapp/' + encodeURIComponent(instanceName) + '/delete', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('Instância excluída');
            setTimeout(function() { window.location.href = '/settings/whatsapp'; }, 1000);
        } else {
            showToast(data.error || 'Erro', 'error');
        }
    } catch (e) {
        showToast('Erro de conexão', 'error');
    }
}

// Fechar modal ao clicar fora
document.getElementById('qrModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeQrModal();
    }
});

// ================== Inicialização ==================
document.addEventListener('DOMContentLoaded', function() {
    // Carregar todas as configurações
    loadApiSettings();
    loadOrderNotificationConfig();
    loadEngagementConfig();
    loadOutOfHoursConfig();
    loadScheduledPauseConfig();
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
