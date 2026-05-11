<?php
/**
 * iFood Config - Mobile
 */
$activeNav = 'settings';
ob_start();
?>

<style>
.ifood-page { padding: 1rem; padding-bottom: 6rem; }
.ifood-back { display: inline-flex; align-items: center; gap: 0.5rem; color: var(--text-secondary, #64748b); font-size: 0.875rem; text-decoration: none; margin-bottom: 1rem; }
.ifood-title { font-size: 1.25rem; font-weight: 700; color: var(--text-primary, #1e293b); margin-bottom: 0.25rem; }
.ifood-subtitle { font-size: 0.8125rem; color: var(--text-secondary, #64748b); margin-bottom: 1.25rem; }

.ifood-status-badge { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.375rem 0.875rem; border-radius: 9999px; font-size: 0.8125rem; font-weight: 500; margin-bottom: 1rem; }
.ifood-status-badge.active { background: #dcfce7; color: #15803d; }
.ifood-status-badge.active .dot { width: 0.5rem; height: 0.5rem; border-radius: 50%; background: #22c55e; animation: pulse 2s infinite; }
.ifood-status-badge.inactive { background: #f1f5f9; color: #64748b; }
.ifood-status-badge.inactive .dot { width: 0.5rem; height: 0.5rem; border-radius: 50%; background: #94a3b8; }

@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

.ifood-alert { border-radius: 0.75rem; padding: 0.875rem; margin-bottom: 1rem; display: flex; align-items: flex-start; gap: 0.75rem; font-size: 0.8125rem; }
.ifood-alert.warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
.ifood-alert.info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
.ifood-alert.success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
.ifood-alert.error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.ifood-alert svg { flex-shrink: 0; width: 1.25rem; height: 1.25rem; margin-top: 0.125rem; }

.ifood-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; overflow: hidden; margin-bottom: 1rem; }
.ifood-card-header { padding: 1rem; border-bottom: 1px solid #f1f5f9; font-weight: 600; font-size: 0.9375rem; color: var(--text-primary, #1e293b); }
.ifood-card-body { padding: 1rem; }

.ifood-field { margin-bottom: 1rem; }
.ifood-field label { display: block; font-size: 0.8125rem; font-weight: 500; color: var(--text-primary, #1e293b); margin-bottom: 0.375rem; }
.ifood-field label .req { color: #ef4444; }
.ifood-field input, .ifood-field select { width: 100%; padding: 0.625rem 0.875rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.875rem; background: #fff; color: var(--text-primary, #1e293b); -webkit-appearance: none; }
.ifood-field input:focus, .ifood-field select:focus { outline: none; border-color: var(--primary, #4361ee); box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1); }
.ifood-field .hint { font-size: 0.75rem; color: var(--text-secondary, #64748b); margin-top: 0.25rem; }
.ifood-field .pass-wrap { position: relative; }
.ifood-field .pass-wrap input { padding-right: 2.75rem; }
.ifood-field .pass-toggle { position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; padding: 0; cursor: pointer; }

.ifood-check { display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem 0; cursor: pointer; }
.ifood-check input[type="checkbox"] { width: 1.25rem; height: 1.25rem; margin-top: 0.125rem; accent-color: var(--primary, #4361ee); flex-shrink: 0; }
.ifood-check-label { font-weight: 500; color: var(--text-primary, #1e293b); font-size: 0.875rem; }
.ifood-check-desc { font-size: 0.75rem; color: var(--text-secondary, #64748b); }

.ifood-actions { display: flex; gap: 0.75rem; padding: 1rem; border-top: 1px solid #f1f5f9; }
.ifood-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 0.75rem; font-size: 0.875rem; font-weight: 500; border: none; cursor: pointer; flex: 1; text-decoration: none; }
.ifood-btn-primary { background: var(--primary, #4361ee); color: #fff; }
.ifood-btn-outline { background: #fff; color: var(--text-primary, #1e293b); border: 1px solid #e2e8f0; }
.ifood-btn svg { width: 1rem; height: 1rem; }

.ifood-quick-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-top: 1rem; }
.ifood-quick-card { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; padding: 1rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 0.75rem; text-decoration: none; color: var(--text-primary, #1e293b); text-align: center; }
.ifood-quick-card .icon-wrap { width: 2.75rem; height: 2.75rem; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; }
.ifood-quick-card h4 { font-size: 0.8125rem; font-weight: 600; margin: 0; }
.ifood-quick-card p { font-size: 0.6875rem; color: var(--text-secondary, #64748b); margin: 0; }

#connectionResult { display: none; margin-top: 1rem; }
#connectionResult.show { display: block; }

.ifood-merchant-online { display: inline-flex; align-items: center; gap: 0.375rem; background: #dcfce7; color: #15803d; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
.ifood-merchant-offline { display: inline-flex; align-items: center; gap: 0.375rem; background: #fef2f2; color: #dc2626; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
</style>

<div class="ifood-page">
    <a href="/settings" class="ifood-back">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Configurações
    </a>

    <h1 class="ifood-title">Integração iFood</h1>
    <p class="ifood-subtitle">Configure para receber pedidos automaticamente</p>

    <!-- Status -->
    <?php if ($config && $config['is_active']): ?>
        <div class="ifood-status-badge active"><span class="dot"></span> Integração Ativa</div>
    <?php else: ?>
        <div class="ifood-status-badge inactive"><span class="dot"></span> Integração Inativa</div>
    <?php endif; ?>

    <!-- Flash messages -->
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="ifood-alert success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 11-5.93-9.14" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 4L12 14.01l-3-3" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span><?= htmlspecialchars($_SESSION['flash_success']) ?></span>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="ifood-alert error">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span><?= htmlspecialchars($_SESSION['flash_error']) ?></span>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Last error -->
    <?php if ($config && !empty($config['last_error'])): ?>
        <div class="ifood-alert warning">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <div><strong>Último erro:</strong><br><?= htmlspecialchars($config['last_error']) ?></div>
        </div>
    <?php endif; ?>

    <!-- Info -->
    <div class="ifood-alert info">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="9" x2="12" y2="13" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="17" x2="12.01" y2="17" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <div>
            <strong>Como configurar:</strong>
            <ol style="margin: 0.5rem 0 0; padding-left: 1.25rem; line-height: 1.6;">
                <li>Acesse <a href="https://developer.ifood.com.br" target="_blank" style="text-decoration: underline;">developer.ifood.com.br</a></li>
                <li>Crie um app e obtenha Client ID/Secret</li>
                <li>Preencha abaixo e salve</li>
                <li>Clique em "Testar Conexão"</li>
            </ol>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="/ifood/config/save" id="ifoodConfigForm">
        <!-- Credentials -->
        <div class="ifood-card">
            <div class="ifood-card-header">Credenciais da API</div>
            <div class="ifood-card-body">
                <div class="ifood-field">
                    <label>Client ID <span class="req">*</span> <a href="/guide/ifood#credentials" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;" title="Ajuda">?</a></label>
                    <input type="text" name="client_id" value="<?= htmlspecialchars($config['client_id'] ?? '') ?>" placeholder="Ex: 3c587f8f-fb22-...">
                    <div class="hint">Obtido no painel de desenvolvedores</div>
                </div>
                <div class="ifood-field">
                    <label>Client Secret <span class="req">*</span></label>
                    <div class="pass-wrap">
                        <input type="password" name="client_secret" id="clientSecret" placeholder="<?= !empty($config['client_secret']) ? '••••••••••' : 'Cole seu Client Secret' ?>">
                        <button type="button" class="pass-toggle" onclick="togglePass()">
                            <svg id="eyeIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                    <div class="hint">Deixe em branco para manter o atual</div>
                </div>
            </div>
        </div>

        <!-- Merchant -->
        <div class="ifood-card">
            <div class="ifood-card-header">Loja</div>
            <div class="ifood-card-body">
                <div class="ifood-field">
                    <label>Merchant ID</label>
                    <?php if (!empty($merchants)): ?>
                        <select name="merchant_id">
                            <option value="">Selecione uma loja...</option>
                            <?php foreach ($merchants as $merchant): ?>
                                <option value="<?= htmlspecialchars($merchant['id']) ?>" <?= ($config['merchant_id'] ?? '') === $merchant['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($merchant['name'] ?? $merchant['id']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="text" name="merchant_id" value="<?= htmlspecialchars($config['merchant_id'] ?? '') ?>" placeholder="ID da sua loja no iFood">
                    <?php endif; ?>
                    <div class="hint">Após testar, as lojas aparecerão aqui</div>
                </div>
                <?php
                $isOnline = false;
                $statusMessage = '';
                if ($merchantStatus && is_array($merchantStatus)) {
                    foreach ($merchantStatus as $s) {
                        if (isset($s['available']) && $s['available']) {
                            $isOnline = true;
                            $statusMessage = $s['message']['title'] ?? 'Online';
                            break;
                        }
                    }
                    if (!$isOnline && !empty($merchantStatus[0]['message']['title'])) {
                        $statusMessage = $merchantStatus[0]['message']['title'];
                    }
                }
                ?>
                <?php if ($merchantStatus): ?>
                    <div style="margin-top: 0.5rem;">
                        <?php if ($isOnline): ?>
                            <span class="ifood-merchant-online"><span class="dot" style="width:6px;height:6px;border-radius:50%;background:#22c55e;"></span> <?= htmlspecialchars($statusMessage ?: 'Online') ?></span>
                        <?php else: ?>
                            <span class="ifood-merchant-offline"><span class="dot" style="width:6px;height:6px;border-radius:50%;background:#dc2626;"></span> <?= htmlspecialchars($statusMessage ?: 'Offline') ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Options -->
        <div class="ifood-card">
            <div class="ifood-card-header">Opções</div>
            <div class="ifood-card-body" style="padding-top: 0.5rem; padding-bottom: 0.5rem;">
                <label class="ifood-check">
                    <input type="checkbox" name="is_active" value="1" <?= ($config['is_active'] ?? false) ? 'checked' : '' ?>>
                    <div>
                        <div class="ifood-check-label">Integração Ativa</div>
                        <div class="ifood-check-desc">Ative para receber pedidos</div>
                    </div>
                </label>
                <label class="ifood-check">
                    <input type="checkbox" name="auto_confirm" value="1" <?= ($config['auto_confirm'] ?? false) ? 'checked' : '' ?>>
                    <div>
                        <div class="ifood-check-label">Confirmar Automaticamente <a href="/guide/ifood#credentials" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:4px;line-height:1;" title="Ajuda">?</a></div>
                        <div class="ifood-check-desc">Confirma pedidos sem intervenção manual</div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Webhook -->
        <div class="ifood-card">
            <div class="ifood-card-header">Webhook</div>
            <div class="ifood-card-body">
                <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 0.5rem;">URL para configurar no portal iFood:</p>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <code style="flex: 1; font-size: 0.75rem; background: #f1f5f9; padding: 0.5rem; border-radius: 0.5rem; word-break: break-all; user-select: all;"><?= htmlspecialchars(base_url('webhook/ifood')) ?></code>
                    <button type="button" onclick="copyWebhookUrl()" class="ifood-btn ifood-btn-outline" style="padding: 0.4rem 0.75rem; font-size: 0.75rem; white-space: nowrap;">Copiar</button>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div style="display: flex; gap: 0.75rem; margin-bottom: 1rem;">
            <button type="button" class="ifood-btn ifood-btn-outline" onclick="testConnection()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 12.55a11 11 0 0114.08 0" stroke-linecap="round" stroke-linejoin="round"/><path d="M1.42 9a16 16 0 0121.16 0" stroke-linecap="round" stroke-linejoin="round"/><path d="M8.53 16.11a6 6 0 016.95 0" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="20" x2="12.01" y2="20" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Testar
            </button>
            <button type="submit" class="ifood-btn ifood-btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round"/><polyline points="17 21 17 13 7 13 7 21" stroke-linecap="round" stroke-linejoin="round"/><polyline points="7 3 7 8 15 8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Salvar
            </button>
        </div>
    </form>

    <!-- Test Result -->
    <div id="connectionResult">
        <div id="connectionResultContent" class="ifood-alert"></div>
    </div>

    <!-- Quick Actions -->
    <?php if ($config && $config['is_active']): ?>
    <div class="ifood-quick-actions">
        <a href="/ifood/orders" class="ifood-quick-card">
            <div class="icon-wrap" style="background: #fee2e2; color: #dc2626;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="19" y1="12" x2="5" y2="12" stroke-linecap="round" stroke-linejoin="round"/><polyline points="12 19 5 12 12 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <h4>Ver Pedidos</h4>
            <p>Gerenciar pedidos iFood</p>
        </a>
        <button type="button" onclick="pollOrders()" class="ifood-quick-card" style="border: 1px solid #e2e8f0; cursor: pointer;">
            <div class="icon-wrap" style="background: #dbeafe; color: #2563eb;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="19" y1="12" x2="5" y2="12" stroke-linecap="round" stroke-linejoin="round"/><polyline points="12 19 5 12 12 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <h4>Buscar Pedidos</h4>
            <p>Sincronizar manualmente</p>
        </button>
    </div>
    <?php endif; ?>
</div>

<script>
function copyWebhookUrl() {
    var url = '<?= htmlspecialchars(base_url('webhook/ifood'), ENT_QUOTES) ?>';
    navigator.clipboard.writeText(url).then(function() {
        var btn = event.currentTarget;
        btn.textContent = 'Copiado!';
        setTimeout(function() { btn.textContent = 'Copiar'; }, 2000);
    });
}

function togglePass() {
    var inp = document.getElementById('clientSecret');
    inp.type = inp.type === 'password' ? 'text' : 'password';
}

function testConnection() {
    var rd = document.getElementById('connectionResult');
    var rc = document.getElementById('connectionResultContent');
    rd.style.display = 'block';
    rc.className = 'ifood-alert info';
    rc.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="9" x2="12" y2="13" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="17" x2="12.01" y2="17" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    fetch('/ifood/test-connection', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            var html = '<strong>Conexão OK!</strong>';
            if (data.expires_at) html += '<br>Token válido até: ' + data.expires_at;
            if (data.merchants && data.merchants.length > 0) {
                html += '<br>Lojas: ';
                data.merchants.forEach(function(m) { html += '<br>• ' + (m.name || m.id); });
            }
            rc.className = 'ifood-alert success';
            rc.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 11-5.93-9.14" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 4L12 14.01l-3-3" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        } else {
            rc.className = 'ifood-alert error';
            rc.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        }
    })
    .catch(function(e) {
        rc.className = 'ifood-alert error';
        rc.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    });
}

function pollOrders() {
    var rd = document.getElementById('connectionResult');
    var rc = document.getElementById('connectionResultContent');
    rd.style.display = 'block';
    rc.className = 'ifood-alert info';
    rc.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="9" x2="12" y2="13" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="17" x2="12.01" y2="17" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    fetch('/ifood/poll', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success && data.events > 0) {
            rc.className = 'ifood-alert success';
            rc.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 11-5.93-9.14" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 4L12 14.01l-3-3" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        } else {
            rc.className = 'ifood-alert info';
            rc.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="9" x2="12" y2="13" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="17" x2="12.01" y2="17" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        }
    })
    .catch(function(e) {
        rc.className = 'ifood-alert error';
        rc.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
