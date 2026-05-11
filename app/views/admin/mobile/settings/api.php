<?php
/**
 * Gerenciamento de API - Mobile
 * Tokens JWT, API Keys, Endpoints e Estatísticas
 * 
 * @var array $company
 * @var array $user
 * @var array $apiData
 * @var array $stats
 * @var array $endpoints
 * @var string $baseUrl
 */

$pageTitle = 'API';
$backUrl = '/settings';
$activeNav = 'settings';
$showBackButton = true;

// Normalização
$apiData = is_array($apiData ?? null) ? $apiData : ['tokens' => [], 'api_keys' => []];
$stats = is_array($stats ?? null) ? $stats : ['requests_today' => 0, 'total_requests' => 0, 'top_endpoints' => []];
$endpoints = is_array($endpoints ?? null) ? $endpoints : [];
$baseUrl = (string)($baseUrl ?? '');

// Helpers
$formatDate = function($date) {
    return $date ? date('d/m/Y H:i', strtotime($date)) : '--';
};

$formatScopes = function($scopes) {
    if (is_string($scopes)) {
        $scopes = json_decode($scopes, true) ?? [];
    }
    return is_array($scopes) ? implode(', ', $scopes) : '';
};

ob_start();
?>

<style>
/* API Mobile Page */
.api-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 20px;
}

.api-stat-card {
    background: white;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    padding: 14px 10px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.api-stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
}

.api-stat-icon.blue { background: #dbeafe; color: #2563eb; }
.api-stat-icon.green { background: #d1fae5; color: #059669; }
.api-stat-icon.purple { background: #ede9fe; color: #7c3aed; }

.api-stat-value {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1;
}

.api-stat-label {
    font-size: 11px;
    color: #64748b;
    margin-top: 4px;
}

/* Tabs */
.api-tabs {
    display: flex;
    background: white;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    padding: 4px;
    margin-bottom: 16px;
    gap: 4px;
}

.api-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 8px;
    border-radius: 10px;
    border: none;
    background: transparent;
    color: #64748b;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.api-tab.active {
    background: var(--primary);
    color: white;
    box-shadow: 0 2px 4px rgba(91,33,182,0.2);
}

.api-tab svg { width: 16px; height: 16px; }

/* Panel */
.api-panel { display: none; }
.api-panel.active { display: block; }

/* Card */
.api-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    padding: 16px;
    margin-bottom: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.api-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
}

.api-card-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.api-card-icon.blue { background: #dbeafe; color: #2563eb; }
.api-card-icon.green { background: #d1fae5; color: #059669; }
.api-card-icon.purple { background: #ede9fe; color: #7c3aed; }
.api-card-icon.amber { background: #fef3c7; color: #d97706; }
.api-card-icon.slate { background: #f1f5f9; color: #475569; }

.api-card-title {
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
}

/* Token/Key list item */
.api-item {
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: 14px;
    margin-bottom: 10px;
}

.api-item-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.api-item-label {
    font-size: 12px;
    font-weight: 600;
    color: #475569;
}

.api-item-mono {
    font-family: 'Courier New', monospace;
    font-size: 11px;
    color: #334155;
    background: #e2e8f0;
    padding: 6px 10px;
    border-radius: 8px;
    word-break: break-all;
    line-height: 1.4;
    margin: 6px 0;
}

.api-item-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
    font-size: 12px;
    color: #64748b;
}

.api-item-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Badges */
.badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.badge.active { background: #d1fae5; color: #065f46; }
.badge.expired { background: #fef3c7; color: #92400e; }
.badge.revoked { background: #fee2e2; color: #991b1b; }
.badge.method-get { background: #d1fae5; color: #065f46; }
.badge.method-post { background: #dbeafe; color: #1e40af; }

/* Buttons */
.btn-generate {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 12px;
    border-radius: 12px;
    border: none;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
}

.btn-generate:active { opacity: 0.85; }

.btn-generate.blue {
    background: #2563eb;
    color: white;
}

.btn-generate.green {
    background: #059669;
    color: white;
}

.btn-revoke {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 6px 14px;
    border-radius: 8px;
    border: 1px solid #fca5a5;
    background: #fef2f2;
    color: #dc2626;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-revoke:active { background: #fee2e2; }

.btn-copy {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 6px 14px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: white;
    color: #475569;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
}

.btn-copy:active { background: #f1f5f9; }

/* Empty state */
.api-empty {
    text-align: center;
    padding: 30px 16px;
    color: #94a3b8;
}

.api-empty svg {
    margin: 0 auto 10px;
    color: #cbd5e1;
}

.api-empty p { font-size: 14px; margin: 4px 0; }
.api-empty .sub { font-size: 12px; color: #94a3b8; }

/* Endpoint list */
.endpoint-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
}

.endpoint-item:last-child { border-bottom: none; }

.endpoint-path {
    font-family: 'Courier New', monospace;
    font-size: 13px;
    color: #334155;
    flex: 1;
}

.endpoint-desc {
    font-size: 12px;
    color: #94a3b8;
}

/* Info grid */
.info-grid {
    display: grid;
    gap: 12px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
}

.info-row:last-child { border-bottom: none; }

.info-label {
    font-size: 13px;
    color: #64748b;
}

.info-value {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
}

/* Modal */
.api-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    align-items: flex-end;
    justify-content: center;
}

.api-modal-overlay.show {
    display: flex;
}

.api-modal {
    background: white;
    border-radius: 20px 20px 0 0;
    width: 100%;
    max-width: 500px;
    max-height: 85vh;
    overflow-y: auto;
    padding: 20px;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
}

.api-modal-handle {
    width: 40px;
    height: 4px;
    background: #d1d5db;
    border-radius: 4px;
    margin: 0 auto 16px;
}

.api-modal h3 {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 16px;
}

.form-group {
    margin-bottom: 14px;
}

.form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

.form-select, .form-input {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    font-size: 14px;
    color: #1e293b;
    background: white;
    -webkit-appearance: none;
}

.form-select:focus, .form-input:focus {
    outline: none;
    border-color: var(--primary, #7c3aed);
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    color: #374151;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    border-radius: 4px;
    accent-color: var(--primary, #7c3aed);
}

.modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.modal-btn {
    flex: 1;
    padding: 12px;
    border-radius: 12px;
    border: none;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
}

.modal-btn:active { opacity: 0.85; }

.modal-btn.cancel {
    background: #f1f5f9;
    color: #475569;
}

.modal-btn.primary {
    background: #2563eb;
    color: white;
}

.modal-btn.success {
    background: #059669;
    color: white;
}

/* Result display */
.result-box {
    background: #f1f5f9;
    border-radius: 12px;
    padding: 14px;
    margin: 12px 0;
}

.result-box code {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    color: #1e293b;
    word-break: break-all;
    line-height: 1.5;
}

/* Toast */
.api-toast {
    position: fixed;
    bottom: 80px;
    left: 50%;
    transform: translateX(-50%);
    background: #1e293b;
    color: white;
    padding: 10px 20px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 500;
    z-index: 10000;
    display: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.api-toast.show {
    display: block;
    animation: fadeInUp 0.3s ease;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateX(-50%) translateY(10px); }
    to { opacity: 1; transform: translateX(-50%) translateY(0); }
}

/* Top endpoints */
.top-endpoint {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
}

.top-endpoint:last-child { border-bottom: none; }

.top-endpoint code {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    color: #334155;
}

.top-endpoint .count {
    background: #f1f5f9;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
}
</style>

<?php if (!empty($_SESSION['flash_success'])): ?>
    <div style="background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; padding: 12px 16px; border-radius: 12px; margin-bottom: 16px; font-size: 14px;">
        ✓ <?= htmlspecialchars($_SESSION['flash_success']) ?>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 16px; border-radius: 12px; margin-bottom: 16px; font-size: 14px;">
        ✕ <?= htmlspecialchars($_SESSION['flash_error']) ?>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- Estatísticas -->
<div class="api-stats">
    <div class="api-stat-card">
        <div class="api-stat-icon blue">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </div>
        <div class="api-stat-value"><?= number_format($stats['requests_today']) ?></div>
        <div class="api-stat-label">Req. Hoje</div>
    </div>
    <div class="api-stat-card">
        <div class="api-stat-icon green">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
        </div>
        <div class="api-stat-value"><?= number_format($stats['total_requests']) ?></div>
        <div class="api-stat-label">Total Req.</div>
    </div>
    <div class="api-stat-card">
        <div class="api-stat-icon purple">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
        </div>
        <div class="api-stat-value"><?= count($apiData['tokens']) ?></div>
        <div class="api-stat-label">Tokens</div>
    </div>
</div>

<!-- Tabs -->
<div class="api-tabs">
    <button class="api-tab active" onclick="switchApiTab('tokens')" data-tab="tokens">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
        </svg>
        Tokens
    </button>
    <button class="api-tab" onclick="switchApiTab('keys')" data-tab="keys">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0110 0v4"/>
        </svg>
        API Keys
    </button>
    <button class="api-tab" onclick="switchApiTab('docs')" data-tab="docs">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
            <rect x="9" y="3" width="6" height="4" rx="1"/>
        </svg>
        Docs
    </button>
</div>

<!-- PAINEL 1: JWT TOKENS -->
<div class="api-panel active" id="panel-tokens">

    <button type="button" class="btn-generate blue" onclick="openModal('tokenModal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M12 5v14M5 12h14"/>
        </svg>
        Gerar JWT Token
    </button>

    <div style="margin-top: 14px;">
        <?php if (empty($apiData['tokens'])): ?>
            <div class="api-empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                <p>Nenhum token JWT</p>
                <p class="sub">Gere seu primeiro token para usar a API</p>
            </div>
        <?php else: ?>
            <?php foreach ($apiData['tokens'] as $token): 
                $isExpired = $token['expires_at'] && strtotime($token['expires_at']) < time();
                $statusClass = $isExpired ? 'expired' : 'active';
                $statusText = $isExpired ? 'Expirado' : 'Ativo';
            ?>
                <div class="api-item">
                    <div class="api-item-header">
                        <span class="api-item-label">JWT Token</span>
                        <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                    </div>
                    <div class="api-item-mono"><?= htmlspecialchars($token['jwt_raw'] ?? '—') ?></div>
                    <div class="api-item-meta">
                        <span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 5v14m-7-7h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <?= $formatDate($token['created_at']) ?>
                        </span>
                        <span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 5v14m-7-7h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <?= $token['expires_at'] ? $formatDate($token['expires_at']) : 'Nunca' ?>
                        </span>
                        <span>📋 <?= htmlspecialchars($formatScopes($token['scopes'])) ?></span>
                    </div>
                    <div style="display: flex; gap: 8px; margin-top: 10px;">
                        <button type="button" class="btn-copy" onclick="copyText('<?= htmlspecialchars(addslashes($token['jwt_raw'] ?? ''), ENT_QUOTES) ?>')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 5v14m-7-7h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Copiar
                        </button>
                        <?php if (!$isExpired): ?>
                            <form method="POST" action="/settings/api/revoke-token" style="margin:0;" onsubmit="return confirm('Revogar este token?')">
                                <input type="hidden" name="token_id" value="<?= (int)$token['id'] ?>">
                                <button type="submit" class="btn-revoke">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Revogar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- PAINEL 2: API KEYS -->
<div class="api-panel" id="panel-keys">

    <button type="button" class="btn-generate green" onclick="openModal('keyModal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M12 5v14M5 12h14"/>
        </svg>
        Gerar API Key
    </button>

    <div style="margin-top: 14px;">
        <?php if (empty($apiData['api_keys'])): ?>
            <div class="api-empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0110 0v4"/>
                </svg>
                <p>Nenhuma API Key</p>
                <p class="sub">Gere sua primeira chave para acessar a API</p>
            </div>
        <?php else: ?>
            <?php foreach ($apiData['api_keys'] as $key): 
                $isActive = (bool)($key['is_active'] ?? true);
                $isExpired = $key['expires_at'] && strtotime($key['expires_at']) < time();
                if (!$isActive) {
                    $statusClass = 'revoked'; $statusText = 'Revogado';
                } elseif ($isExpired) {
                    $statusClass = 'expired'; $statusText = 'Expirado';
                } else {
                    $statusClass = 'active'; $statusText = 'Ativo';
                }
            ?>
                <div class="api-item">
                    <div class="api-item-header">
                        <span class="api-item-label"><?= htmlspecialchars($key['name'] ?? 'API Key') ?></span>
                        <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                    </div>
                    <div class="api-item-mono"><?= htmlspecialchars(substr($key['key_hash'] ?? '', 0, 20)) ?>...</div>
                    <div class="api-item-meta">
                        <span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 5v14m-7-7h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <?= $formatDate($key['created_at']) ?>
                        </span>
                        <span>📋 <?= htmlspecialchars($formatScopes($key['scopes'])) ?></span>
                    </div>
                    <?php if ($isActive && !$isExpired): ?>
                        <div style="margin-top: 10px;">
                            <form method="POST" action="/settings/api/revoke-key" style="margin:0;" onsubmit="return confirm('Revogar esta API Key?')">
                                <input type="hidden" name="key_id" value="<?= (int)$key['id'] ?>">
                                <button type="submit" class="btn-revoke">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Revogar
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- PAINEL 3: DOCUMENTAÇÃO -->
<div class="api-panel" id="panel-docs">

    <!-- Endpoints Disponíveis -->
    <div class="api-card">
        <div class="api-card-header">
            <div class="api-card-icon blue">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="api-card-title">Endpoints Disponíveis</div>
        </div>
        <?php foreach ($endpoints as $ep): ?>
            <div class="endpoint-item">
                <span class="badge method-<?= strtolower($ep['method']) ?>"><?= htmlspecialchars($ep['method']) ?></span>
                <div style="flex: 1; min-width: 0;">
                    <div class="endpoint-path"><?= htmlspecialchars($ep['path']) ?></div>
                    <div class="endpoint-desc"><?= htmlspecialchars($ep['description'] ?? '') ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Endpoints Mais Usados -->
    <?php if (!empty($stats['top_endpoints'])): ?>
    <div class="api-card">
        <div class="api-card-header">
            <div class="api-card-icon amber">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div class="api-card-title">Endpoints Mais Usados</div>
        </div>
        <?php foreach ($stats['top_endpoints'] as $ep): ?>
            <div class="top-endpoint">
                <code><?= htmlspecialchars($ep['endpoint']) ?></code>
                <span class="count"><?= number_format($ep['count']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Informações da API -->
    <div class="api-card">
        <div class="api-card-header">
            <div class="api-card-icon slate">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 16v-4M12 8h.01"/>
                </svg>
            </div>
            <div class="api-card-title">Informações da API</div>
        </div>
        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">Base URL</span>
                <span class="info-value" style="font-family: monospace; font-size: 12px;"><?= htmlspecialchars($baseUrl) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Rate Limit</span>
                <span class="info-value">1000 req/min</span>
            </div>
            <div class="info-row">
                <span class="info-label">Formato</span>
                <span class="info-value">JSON</span>
            </div>
            <div class="info-row">
                <span class="info-label">Autenticação</span>
                <span class="info-value">JWT + API Key</span>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Gerar Token -->
<div class="api-modal-overlay" id="tokenModal">
    <div class="api-modal">
        <div class="api-modal-handle"></div>
        <h3>🔑 Gerar JWT Token</h3>
        <form method="POST" action="/settings/api/generate-token">
            <div class="form-group">
                <label class="form-label">Expiração</label>
                <select name="expires_in" class="form-select">
                    <option value="3600">1 hora</option>
                    <option value="86400" selected>24 horas</option>
                    <option value="604800">7 dias</option>
                    <option value="2592000">30 dias</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Permissões</label>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="scopes[]" value="read" checked> Leitura (read)
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="scopes[]" value="write" checked> Escrita (write)
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="scopes[]" value="admin"> Administração (admin)
                    </label>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="modal-btn cancel" onclick="closeModal('tokenModal')">Cancelar</button>
                <button type="submit" class="modal-btn primary">Gerar Token</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Gerar API Key -->
<div class="api-modal-overlay" id="keyModal">
    <div class="api-modal">
        <div class="api-modal-handle"></div>
        <h3>🗝️ Gerar API Key</h3>
        <form method="POST" action="/settings/api/generate-key">
            <div class="form-group">
                <label class="form-label">Nome</label>
                <input type="text" name="name" class="form-input" placeholder="Ex: App Mobile" required>
            </div>
            <div class="form-group">
                <label class="form-label">Permissões</label>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="scopes[]" value="read" checked> Leitura (read)
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="scopes[]" value="write"> Escrita (write)
                    </label>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="modal-btn cancel" onclick="closeModal('keyModal')">Cancelar</button>
                <button type="submit" class="modal-btn success">Gerar Chave</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Resultado (token/key gerado) -->
<div class="api-modal-overlay" id="resultModal">
    <div class="api-modal">
        <div class="api-modal-handle"></div>
        <h3 id="resultTitle">Resultado</h3>
        <p style="font-size: 13px; color: #64748b; margin-bottom: 12px;">Copie e guarde em um lugar seguro. Este valor não será exibido novamente.</p>
        <div class="result-box">
            <code id="resultContent"></code>
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-btn primary" onclick="copyResultAndClose()" style="flex: 2;">
                📋 Copiar e Fechar
            </button>
            <button type="button" class="modal-btn cancel" onclick="closeModal('resultModal')">Fechar</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="api-toast" id="apiToast"></div>

<?php
// Check if we have a generated token/key to display
$generatedToken = $_SESSION['generated_token'] ?? null;
$generatedKey = $_SESSION['generated_key'] ?? null;
unset($_SESSION['generated_token'], $_SESSION['generated_key']);
?>

<script>
// Tab switching
function switchApiTab(tabName) {
    document.querySelectorAll('.api-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.api-panel').forEach(p => p.classList.remove('active'));
    document.querySelector('.api-tab[data-tab="' + tabName + '"]').classList.add('active');
    document.getElementById('panel-' + tabName).classList.add('active');
}

// Modals
function openModal(id) {
    document.getElementById(id).classList.add('show');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

// Close modal on overlay click
document.querySelectorAll('.api-modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('show');
    });
});

// Toast
function showToast(msg) {
    const t = document.getElementById('apiToast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

// Copy text
function copyText(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('✓ Copiado!');
    }).catch(() => {
        // Fallback
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        showToast('✓ Copiado!');
    });
}

function copyResultAndClose() {
    const text = document.getElementById('resultContent').textContent;
    copyText(text);
    setTimeout(() => closeModal('resultModal'), 500);
}

// Show generated result if available
<?php if ($generatedToken): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('resultTitle').textContent = '🔑 JWT Token Gerado';
    document.getElementById('resultContent').textContent = <?= json_encode($generatedToken) ?>;
    openModal('resultModal');
});
<?php elseif ($generatedKey): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('resultTitle').textContent = '🗝️ API Key Gerada';
    document.getElementById('resultContent').textContent = <?= json_encode($generatedKey) ?>;
    openModal('resultModal');
});
<?php endif; ?>
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
