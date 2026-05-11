<?php
/**
 * Configurações da Loja Mobile
 * Abas: Dados, API, Cores, Imagens (igual desktop)
 */
$colorValues = $colorValues ?? [];
$colorDefaults = $colorDefaults ?? [];
$activeTab = $activeTab ?? 'dados';

$colorLabels = [
    'menu_header_text_color'       => 'Texto do cabeçalho',
    'menu_header_button_color'     => 'Botões/ícones do cabeçalho',
    'menu_header_bg_color'         => 'Fundo do cabeçalho',
    'menu_logo_border_color'       => 'Borda da logo',
    'menu_group_title_bg_color'    => 'Fundo do título dos grupos',
    'menu_group_title_text_color'  => 'Texto do título dos grupos',
    'menu_welcome_bg_color'        => 'Fundo boas-vindas',
    'menu_welcome_text_color'      => 'Texto boas-vindas',
];

ob_start();
?>

<style>
/* Tabs - estilo grid card */
.store-tabs {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    margin-bottom: 16px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
}

.store-tab {
    padding: 12px 4px;
    border: none;
    background: none;
    font-size: 10px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    color: #6b7280;
    border-bottom: 2px solid transparent;
    line-height: 1.1;
    text-align: center;
}

.store-tab.active {
    background: var(--admin-primary-color, #4361ee);
    color: #fff;
    font-weight: 600;
    border-bottom-color: var(--admin-primary-color, #4361ee);
}

.store-tab:not(.active) {
    background: none;
    color: #6b7280;
}

.store-tab svg {
    width: 18px;
    height: 18px;
}

.section-panel {
    display: none;
}

.section-panel.active {
    display: block;
}

/* Card */
.settings-card {
    background: white;
    border-radius: 16px;
    padding: 16px;
    margin-bottom: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
}

.settings-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.settings-card-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.settings-card-icon.purple { background: var(--admin-primary-soft, #ede9fe); color: var(--admin-primary-color, #7c3aed); }
.settings-card-icon.green { background: #d1fae5; color: #059669; }
.settings-card-icon.blue { background: #dbeafe; color: #2563eb; }
.settings-card-icon.yellow { background: #fef3c7; color: #d97706; }

.settings-card-title {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
}

.settings-card-desc {
    font-size: 13px;
    color: #6b7280;
    margin-top: 2px;
}

/* Form */
.form-group {
    margin-bottom: 14px;
}

.form-label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
}

.form-input, .form-select {
    width: 100%;
    padding: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    font-size: 16px;
    background: white;
    color: #1f2937;
}

.form-input:focus, .form-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(91, 33, 182, 0.1);
}

.form-hint {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 4px;
}

.input-with-prefix {
    display: flex;
    align-items: center;
}

.input-with-prefix .prefix {
    padding: 12px;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-right: none;
    border-radius: 12px 0 0 12px;
    color: #6b7280;
    font-size: 14px;
}

.input-with-prefix input {
    border-radius: 0 12px 12px 0;
    flex: 1;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

/* Buttons */
.btn-primary {
    width: 100%;
    padding: 14px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 16px;
}

.btn-primary:active {
    transform: scale(0.98);
}

.btn-reset {
    width: 100%;
    padding: 12px;
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fcd34d;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    margin-top: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

/* Flash */
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

/* Color picker */
.color-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 12px;
    margin-bottom: 10px;
}

.color-preview {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    flex-shrink: 0;
    cursor: pointer;
    position: relative;
}

.color-preview input[type="color"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}

.color-info {
    flex: 1;
}

.color-name {
    font-size: 13px;
    font-weight: 500;
    color: #374151;
}

.color-value {
    font-size: 12px;
    font-family: monospace;
    color: #6b7280;
    text-transform: uppercase;
}

/* Image upload */
.image-upload-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: #f9fafb;
    border: 2px dashed #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 120px;
}

.image-upload-box:active {
    border-color: var(--primary);
    background: #ede9fe;
}

.image-upload-box img {
    max-width: 100%;
    max-height: 100px;
    border-radius: 8px;
    object-fit: contain;
}

.image-upload-box .placeholder {
    color: #9ca3af;
    text-align: center;
}

.image-upload-box .placeholder svg {
    margin-bottom: 8px;
}

/* API key */
.api-key-input {
    position: relative;
}

.api-key-input input {
    padding-right: 44px;
}

.api-key-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 4px;
}
</style>

<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="flash-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="flash-error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- Tabs -->
<div class="store-tabs">
    <button class="store-tab <?= $activeTab === 'dados' ? 'active' : '' ?>" onclick="switchStoreTab('dados')" data-tab="dados">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
        Dados
    </button>
    <button class="store-tab <?= $activeTab === 'api' ? 'active' : '' ?>" onclick="switchStoreTab('api')" data-tab="api">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M3 12h18M12 3v18"/>
        </svg>
        API
    </button>
    <button class="store-tab <?= $activeTab === 'cores' ? 'active' : '' ?>" onclick="switchStoreTab('cores')" data-tab="cores">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4z"/>
            <circle cx="7" cy="17" r="1"/>
        </svg>
        Cores
    </button>
    <button class="store-tab <?= $activeTab === 'imagens' ? 'active' : '' ?>" onclick="switchStoreTab('imagens')" data-tab="imagens">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
            <circle cx="8.5" cy="8.5" r="1.5"/>
            <path d="M21 15l-5-5L5 21"/>
        </svg>
        Imagens
    </button>
</div>

<form method="POST" action="/settings/store" enctype="multipart/form-data">
    <input type="hidden" name="active_tab" id="activeTabInput" value="<?= htmlspecialchars($activeTab) ?>">

    <!-- PAINEL 1: DADOS -->
    <div class="section-panel <?= $activeTab === 'dados' ? 'active' : '' ?>" id="panel-dados">
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="settings-card-icon purple">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2z"/>
                        <path d="M12 11a3 3 0 100-6 3 3 0 000 6zM17 21v-2a4 4 0 00-4-4H11a4 4 0 00-4 4v2"/>
                    </svg>
                </div>
                <div>
                    <div class="settings-card-title">Informações do Comércio</div>
                    <div class="settings-card-desc">Dados básicos da sua loja</div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nome do comércio *</label>
                <input type="text" name="name" class="form-input" required
                       value="<?= htmlspecialchars($company['name'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">WhatsApp</label>
                <input type="tel" name="whatsapp" class="form-input" inputmode="tel"
                       value="<?= htmlspecialchars($company['whatsapp'] ?? '') ?>"
                       placeholder="(51) 99999-9999">
            </div>
            
            <div class="form-group">
                <label class="form-label">Endereço</label>
                <input type="text" name="address" class="form-input"
                       value="<?= htmlspecialchars($company['address'] ?? '') ?>"
                       placeholder="Rua, número - Bairro">
            </div>
            
            <div class="form-group">
                <label class="form-label">Pedido mínimo <a href="/guide/company-settings#data" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:4px;line-height:1;" title="Ajuda">?</a></label>
                <div class="input-with-prefix">
                    <span class="prefix">R$</span>
                    <input type="number" name="min_order" class="form-input" step="0.01" inputmode="decimal"
                           value="<?= htmlspecialchars($company['min_order'] ?? '') ?>"
                           placeholder="0,00">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tempo mín. (min) <a href="/guide/company-settings#data" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:4px;line-height:1;" title="Ajuda">?</a></label>
                    <input type="number" name="avg_delivery_min_from" class="form-input" inputmode="numeric"
                           value="<?= htmlspecialchars($company['avg_delivery_min_from'] ?? '') ?>"
                           placeholder="40">
                </div>
                <div class="form-group">
                    <label class="form-label">Tempo máx. (min)</label>
                    <input type="number" name="avg_delivery_min_to" class="form-input" inputmode="numeric"
                           value="<?= htmlspecialchars($company['avg_delivery_min_to'] ?? '') ?>"
                           placeholder="60">
                </div>
            </div>
        </div>
    </div>

    <!-- PAINEL 2: API -->
    <div class="section-panel <?= $activeTab === 'api' ? 'active' : '' ?>" id="panel-api">
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="settings-card-icon green">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M3 12h18M12 3v18"/>
                    </svg>
                </div>
                <div>
                    <div class="settings-card-title">Evolution API</div>
                    <div class="settings-card-desc">Integração com WhatsApp</div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">SERVER_URL</label>
                <input type="url" name="evolution_server_url" class="form-input"
                       value="<?= htmlspecialchars($company['evolution_server_url'] ?? '') ?>"
                       placeholder="https://api.evolution.com">
                <div class="form-hint">URL do servidor Evolution API</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">AUTHENTICATION_API_KEY</label>
                <div class="api-key-input">
                    <input type="password" name="evolution_api_key" id="apiKeyInput" class="form-input"
                           value="<?= htmlspecialchars($company['evolution_api_key'] ?? '') ?>"
                           placeholder="Sua chave de API">
                    <button type="button" class="api-key-toggle" onclick="toggleApiKey()">
                        <svg id="eyeOpen" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg id="eyeClosed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="display:none">
                            <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/>
                            <path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>
                <div class="form-hint">Chave usada no header 'apikey'</div>
            </div>

            <div class="form-group">
                <label class="form-label">Google Analytics 4 — Measurement ID</label>
                <input type="text" name="ga_measurement_id" value="<?= e($company['ga_measurement_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX" class="form-input">
                <div class="form-hint">ID de medição do GA4 (ex: G-AB12CD34EF). Vazio = desativado.</div>
            </div>
        </div>
    </div>

    <!-- PAINEL 3: CORES -->
    <div class="section-panel <?= $activeTab === 'cores' ? 'active' : '' ?>" id="panel-cores">
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="settings-card-icon yellow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4z"/>
                        <circle cx="7" cy="17" r="1"/>
                    </svg>
                </div>
                <div>
                    <div class="settings-card-title">Cores do Cardápio</div>
                    <div class="settings-card-desc">Personalize a aparência</div>
                </div>
            </div>
            
            <?php foreach ($colorLabels as $key => $label): ?>
                <div class="color-item">
                    <div class="color-preview" style="background-color: <?= htmlspecialchars($colorValues[$key] ?? '#FFFFFF') ?>">
                        <input type="color" name="<?= htmlspecialchars($key) ?>" 
                               value="<?= htmlspecialchars($colorValues[$key] ?? '#FFFFFF') ?>"
                               onchange="updateColorPreview(this)">
                    </div>
                    <div class="color-info">
                        <div class="color-name"><?= htmlspecialchars($label) ?></div>
                        <div class="color-value" data-for="<?= htmlspecialchars($key) ?>">
                            <?= htmlspecialchars($colorValues[$key] ?? '#FFFFFF') ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <button type="button" class="btn-reset" onclick="resetColors()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                    <path d="M21 3v5h-5"/>
                    <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                    <path d="M3 21v-5h5"/>
                </svg>
                Restaurar cores padrão
            </button>
        </div>
    </div>

    <!-- PAINEL 4: IMAGENS -->
    <div class="section-panel <?= $activeTab === 'imagens' ? 'active' : '' ?>" id="panel-imagens">
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="settings-card-icon blue">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <path d="M21 15l-5-5L5 21"/>
                    </svg>
                </div>
                <div>
                    <div class="settings-card-title">Identidade Visual</div>
                    <div class="settings-card-desc">Logo e banner</div>
                </div>
            </div>
            
            <!-- Logo -->
            <div class="form-group">
                <label class="form-label">Logo (quadrado)</label>
                <div class="image-upload-box" onclick="document.getElementById('logoInput').click()">
                    <?php if (!empty($company['logo'])): ?>
                        <img src="/<?= htmlspecialchars($company['logo']) ?>" alt="Logo" id="logoPreview">
                    <?php else: ?>
                        <div class="placeholder" id="logoPlaceholder">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="3" width="18" height="18" rx="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <path d="M21 15l-5-5L5 21"/>
                            </svg>
                            <div>Toque para selecionar</div>
                        </div>
                        <img src="" alt="Logo" id="logoPreview" style="display: none;">
                    <?php endif; ?>
                </div>
                <input type="file" name="logo" id="logoInput" accept="image/*" style="display: none;" onchange="previewImage(this, 'logoPreview', 'logoPlaceholder')">
                <div class="form-hint">Recomendado: 512×512px. JPG, PNG ou WEBP.</div>
            </div>
            
            <!-- Banner -->
            <div class="form-group">
                <label class="form-label">Banner (largura)</label>
                <div class="image-upload-box" onclick="document.getElementById('bannerInput').click()">
                    <?php if (!empty($company['banner'])): ?>
                        <img src="/<?= htmlspecialchars($company['banner']) ?>" alt="Banner" id="bannerPreview">
                    <?php else: ?>
                        <div class="placeholder" id="bannerPlaceholder">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="2" y="6" width="20" height="12" rx="2"/>
                                <path d="M6 10l4 4 3-3 5 5"/>
                            </svg>
                            <div>Toque para selecionar</div>
                        </div>
                        <img src="" alt="Banner" id="bannerPreview" style="display: none;">
                    <?php endif; ?>
                </div>
                <input type="file" name="banner" id="bannerInput" accept="image/*" style="display: none;" onchange="previewImage(this, 'bannerPreview', 'bannerPlaceholder')">
                <div class="form-hint">Recomendado: 1600×400px. JPG, PNG ou WEBP.</div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn-primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20 7L9 18l-5-5"/>
        </svg>
        Salvar Alterações
    </button>
</form>

<script>
// Cores padrão para reset
const colorDefaults = <?= json_encode($colorDefaults) ?>;

function switchStoreTab(tabName) {
    document.querySelectorAll('.store-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.section-panel').forEach(panel => panel.classList.remove('active'));
    
    document.querySelector('.store-tab[data-tab="' + tabName + '"]').classList.add('active');
    document.getElementById('panel-' + tabName).classList.add('active');
    document.getElementById('activeTabInput').value = tabName;
}

function toggleApiKey() {
    const input = document.getElementById('apiKeyInput');
    const eyeOpen = document.getElementById('eyeOpen');
    const eyeClosed = document.getElementById('eyeClosed');
    
    if (input.type === 'password') {
        input.type = 'text';
        eyeOpen.style.display = 'none';
        eyeClosed.style.display = 'block';
    } else {
        input.type = 'password';
        eyeOpen.style.display = 'block';
        eyeClosed.style.display = 'none';
    }
}

function updateColorPreview(input) {
    input.parentElement.style.backgroundColor = input.value;
    const key = input.name;
    const valueEl = document.querySelector('.color-value[data-for="' + key + '"]');
    if (valueEl) {
        valueEl.textContent = input.value.toUpperCase();
    }
}

function resetColors() {
    if (!confirm('Restaurar todas as cores para o padrão?')) return;
    
    for (const [key, value] of Object.entries(colorDefaults)) {
        const input = document.querySelector('input[name="' + key + '"]');
        if (input) {
            input.value = value;
            input.parentElement.style.backgroundColor = value;
            const valueEl = document.querySelector('.color-value[data-for="' + key + '"]');
            if (valueEl) {
                valueEl.textContent = value;
            }
        }
    }
}

function previewImage(input, previewId, placeholderId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            const placeholder = document.getElementById(placeholderId);
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
