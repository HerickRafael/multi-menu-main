<?php
/**
 * Configurações de Pagamentos Mobile
 * Design igual ao desktop, adaptado para toque
 */
$methods = $methods ?? [];
$editMethod = $editMethod ?? null;
$brandIcons = $brandIcons ?? [];
$paymentTypes = $paymentTypes ?? [
    'pix' => 'Pix',
    'credit' => 'Crédito',
    'debit' => 'Débito',
    'cash' => 'Dinheiro',
    'voucher' => 'Vale-refeição',
    'others' => 'Outros'
];

// Agrupar métodos por tipo
$groupedMethods = [];
foreach ($methods as $m) {
    $type = $m['type'] ?? 'others';
    if (!isset($groupedMethods[$type])) {
        $groupedMethods[$type] = [];
    }
    $groupedMethods[$type][] = $m;
}

ob_start();
?>

<style>
/* Tabs - estilo grid card */
.payment-tabs {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    margin-bottom: 16px;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
}

.payment-tab {
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

.payment-tab.active {
    background: var(--admin-primary-color, #4361ee);
    color: #fff;
    font-weight: 600;
    border-bottom-color: var(--admin-primary-color, #4361ee);
}

.payment-tab:not(.active) {
    background: none;
    color: #6b7280;
}

.section-panel {
    display: none;
}

.section-panel.active {
    display: block;
}

/* Cards */
.payment-card {
    background: white;
    border-radius: 16px;
    padding: 16px;
    margin-bottom: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
}

.payment-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.payment-card-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.payment-card-icon.purple { background: var(--admin-primary-soft, #ede9fe); color: var(--admin-primary-color, #7c3aed); }
.payment-card-icon.green { background: #d1fae5; color: #059669; }
.payment-card-icon.blue { background: #dbeafe; color: #2563eb; }

.payment-card-title {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
}

/* Form inputs */
.form-group {
    margin-bottom: 12px;
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
    -webkit-appearance: none;
}

.form-input:focus, .form-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(91, 33, 182, 0.1);
}

.form-select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 20px;
    padding-right: 40px;
}

.form-hint {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 4px;
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
}

.btn-primary:active {
    transform: scale(0.98);
}

.btn-secondary {
    width: 100%;
    padding: 12px;
    background: white;
    color: #374151;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    margin-top: 8px;
    text-decoration: none;
    display: block;
    text-align: center;
}

/* Method list item */
.method-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px;
    background: white;
    border-radius: 12px;
    margin-bottom: 8px;
    border: 1px solid #e5e7eb;
}

.method-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.method-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}

.method-icon img {
    max-width: 28px;
    max-height: 28px;
    object-fit: contain;
}

.method-text {
    flex: 1;
}

.method-name {
    font-size: 15px;
    font-weight: 600;
    color: #1f2937;
}

.method-type {
    font-size: 12px;
    color: #6b7280;
    margin-top: 2px;
}

.method-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Toggle Switch */
.toggle-track {
    position: relative;
    width: 44px;
    height: 26px;
    background: #d1d5db;
    border-radius: 26px;
    transition: background 0.2s;
    flex-shrink: 0;
    cursor: pointer;
}

.toggle-track.active {
    background: var(--primary);
}

.toggle-thumb {
    position: absolute;
    top: 3px;
    left: 3px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    transition: transform 0.2s;
}

.toggle-track.active .toggle-thumb {
    transform: translateX(18px);
}

.toggle-checkbox {
    display: none;
}

/* Action buttons */
.action-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.action-btn.edit { color: #6b7280; }
.action-btn.delete { color: #dc2626; background: #fef2f2; border-color: #fecaca; }

/* Empty state */
.empty-state {
    text-align: center;
    padding: 32px 16px;
    color: #9ca3af;
}

.empty-state svg {
    margin: 0 auto 12px;
    color: #d1d5db;
}

.empty-state p {
    font-size: 14px;
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

/* Pix fields */
.pix-fields {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 12px;
    padding: 12px;
    margin-bottom: 12px;
}

.pix-fields-title {
    font-size: 13px;
    font-weight: 600;
    color: #059669;
    margin-bottom: 12px;
}

/* Icon selector */
.icon-selector {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    margin-top: 8px;
}

.icon-option {
    aspect-ratio: 1;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.icon-option.selected {
    border-color: var(--primary);
    background: #ede9fe;
}

.icon-option img {
    max-width: 32px;
    max-height: 32px;
    object-fit: contain;
}

.icon-option input {
    display: none;
}

/* Type group header */
.type-header {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 8px 4px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.type-header::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e5e7eb;
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

<!-- Tabs de navegação -->
<div class="payment-tabs">
    <button class="payment-tab active" onclick="switchPaymentTab('lista')" data-tab="lista">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="16" rx="2"/>
            <line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        Métodos
    </button>
    <button class="payment-tab" onclick="switchPaymentTab('novo')" data-tab="novo">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 6v12M6 12h12"/>
        </svg>
        <?= $editMethod ? 'Editar' : 'Novo' ?>
    </button>
</div>

<!-- PAINEL 1: LISTA DE MÉTODOS -->
<div class="section-panel active" id="panel-lista">
    
    <?php if (empty($methods)): ?>
        <div class="empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="4" width="18" height="16" rx="2"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <p>Nenhum método de pagamento cadastrado</p>
        </div>
    <?php else: ?>
        <?php 
        $typeOrder = ['pix', 'credit', 'debit', 'cash', 'voucher', 'others'];
        foreach ($typeOrder as $type):
            if (!isset($groupedMethods[$type])) continue;
            $typeLabel = $paymentTypes[$type] ?? ucfirst($type);
        ?>
            <div class="type-header"><?= htmlspecialchars($typeLabel) ?></div>
            
            <?php foreach ($groupedMethods[$type] as $method): ?>
                <?php 
                $meta = is_array($method['meta'] ?? null) ? $method['meta'] : [];
                $icon = $meta['icon'] ?? '';
                if ($type === 'pix' && empty($icon)) {
                    $icon = '/assets/card-brands/pix.svg';
                }
                if ($type === 'cash' && empty($icon)) {
                    $icon = '/assets/card-brands/cash.svg';
                }
                ?>
                <div class="method-item">
                    <div class="method-info">
                        <div class="method-icon">
                            <?php if ($icon): ?>
                                <img src="<?= htmlspecialchars($icon) ?>" alt="">
                            <?php else: ?>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5">
                                    <rect x="3" y="4" width="18" height="16" rx="2"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="method-text">
                            <div class="method-name"><?= htmlspecialchars($method['name']) ?></div>
                            <?php if ($type === 'pix' && !empty($meta['px_key'])): ?>
                                <div class="method-type">Chave: <?= htmlspecialchars(substr($meta['px_key'], 0, 20)) ?>...</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="method-actions">
                        <label class="toggle-track <?= $method['active'] ? 'active' : '' ?>" onclick="toggleMethod(<?= (int)$method['id'] ?>, this)">
                            <span class="toggle-thumb"></span>
                        </label>
                        <a href="/settings/payments?edit=<?= (int)$method['id'] ?>" class="action-btn edit">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </a>
                        <form method="POST" action="/settings/payments/<?= (int)$method['id'] ?>/delete" style="margin: 0;"
                              onsubmit="return confirm('Excluir este método?')">
                            <button type="submit" class="action-btn delete">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- PAINEL 2: NOVO/EDITAR MÉTODO -->
<div class="section-panel" id="panel-novo">
    
    <div class="payment-card">
        <div class="payment-card-header">
            <div class="payment-card-icon purple">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 6v12M6 12h12"/>
                </svg>
            </div>
            <div class="payment-card-title"><?= $editMethod ? 'Editar Método' : 'Novo Método' ?></div>
        </div>
        
        <form method="POST" action="/settings/payments/save">
            <?php if ($editMethod): ?>
                <input type="hidden" name="id" value="<?= (int)$editMethod['id'] ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label class="form-label">Tipo * <a href="/guide/payment-methods#form" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;" title="Ajuda">?</a></label>
                <select name="type" id="payment-type" class="form-select" required onchange="onTypeChange()">
                    <?php foreach ($paymentTypes as $typeKey => $typeLabel): ?>
                        <option value="<?= htmlspecialchars($typeKey) ?>" <?= ($editMethod && $editMethod['type'] === $typeKey) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($typeLabel) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" id="name-field">
                <label class="form-label">Nome * <a href="/guide/payment-methods#form" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;" title="Ajuda">?</a></label>
                <input type="text" name="name" class="form-input" 
                       value="<?= htmlspecialchars($editMethod['name'] ?? '') ?>"
                       placeholder="Ex: Visa, Mastercard...">
            </div>
            
            <!-- Campos Pix -->
            <div class="pix-fields" id="pix-fields" style="display: none;">
                <div class="pix-fields-title">🔑 Dados do Pix <a href="/guide/payment-methods#pix" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:4px;line-height:1;" title="Ajuda">?</a></div>
                
                <div class="form-group">
                    <label class="form-label">Tipo da chave</label>
                    <select name="pix_key_type" class="form-select">
                        <option value="">Selecione...</option>
                        <option value="cpf" <?= ($editMethod && ($editMethod['meta']['px_key_type'] ?? '') === 'cpf') ? 'selected' : '' ?>>CPF</option>
                        <option value="cnpj" <?= ($editMethod && ($editMethod['meta']['px_key_type'] ?? '') === 'cnpj') ? 'selected' : '' ?>>CNPJ</option>
                        <option value="email" <?= ($editMethod && ($editMethod['meta']['px_key_type'] ?? '') === 'email') ? 'selected' : '' ?>>E-mail</option>
                        <option value="telefone" <?= ($editMethod && ($editMethod['meta']['px_key_type'] ?? '') === 'telefone') ? 'selected' : '' ?>>Telefone</option>
                        <option value="aleatoria" <?= ($editMethod && ($editMethod['meta']['px_key_type'] ?? '') === 'aleatoria') ? 'selected' : '' ?>>Chave aleatória</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Chave Pix</label>
                    <input type="text" name="pix_key" class="form-input" 
                           value="<?= htmlspecialchars($editMethod['meta']['px_key'] ?? '') ?>"
                           placeholder="Digite a chave Pix">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nome do titular</label>
                    <input type="text" name="pix_holder_name" class="form-input" 
                           value="<?= htmlspecialchars($editMethod['meta']['px_holder_name'] ?? '') ?>"
                           placeholder="Nome que aparece no Pix">
                </div>
            </div>
            
            <!-- Seletor de ícone (exceto Pix e Dinheiro) -->
            <div class="form-group" id="icon-field">
                <label class="form-label">Ícone/Bandeira</label>
                <div class="icon-selector">
                    <?php foreach ($brandIcons as $brandIcon): ?>
                        <?php 
                        $isSelected = $editMethod && ($editMethod['meta']['icon'] ?? '') === $brandIcon['path'];
                        ?>
                        <label class="icon-option <?= $isSelected ? 'selected' : '' ?>">
                            <input type="radio" name="icon" value="<?= htmlspecialchars($brandIcon['path']) ?>" <?= $isSelected ? 'checked' : '' ?>>
                            <img src="<?= htmlspecialchars($brandIcon['path']) ?>" alt="<?= htmlspecialchars($brandIcon['name']) ?>">
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" name="active" value="1" <?= ($editMethod && $editMethod['active']) || !$editMethod ? 'checked' : '' ?>>
                    Ativo
                </label>
            </div>
            
            <button type="submit" class="btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M20 7L9 18l-5-5"/>
                </svg>
                <?= $editMethod ? 'Atualizar' : 'Cadastrar' ?>
            </button>
            
            <?php if ($editMethod): ?>
                <a href="/settings/payments" class="btn-secondary">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
function switchPaymentTab(tabName) {
    document.querySelectorAll('.payment-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    document.querySelectorAll('.section-panel').forEach(panel => {
        panel.classList.remove('active');
    });
    
    document.querySelector('.payment-tab[data-tab="' + tabName + '"]').classList.add('active');
    document.getElementById('panel-' + tabName).classList.add('active');
}

function onTypeChange() {
    const type = document.getElementById('payment-type').value;
    const pixFields = document.getElementById('pix-fields');
    const nameField = document.getElementById('name-field');
    const iconField = document.getElementById('icon-field');
    
    // PIX: mostrar campos pix, esconder nome e ícone
    if (type === 'pix') {
        pixFields.style.display = 'block';
        nameField.style.display = 'none';
        iconField.style.display = 'none';
    }
    // Dinheiro: esconder campos extras
    else if (type === 'cash') {
        pixFields.style.display = 'none';
        nameField.style.display = 'none';
        iconField.style.display = 'none';
    }
    // Outros tipos
    else {
        pixFields.style.display = 'none';
        nameField.style.display = 'block';
        iconField.style.display = 'block';
    }
}

function toggleMethod(id, el) {
    fetch('/settings/payments/' + id + '/toggle', {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (data.active) {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        }
    })
    .catch(e => console.error(e));
}

// Selecionar ícone
document.querySelectorAll('.icon-option').forEach(opt => {
    opt.addEventListener('click', function() {
        document.querySelectorAll('.icon-option').forEach(o => o.classList.remove('selected'));
        this.classList.add('selected');
        this.querySelector('input').checked = true;
    });
});

// Inicializar
<?php if ($editMethod): ?>
    switchPaymentTab('novo');
<?php endif; ?>
onTypeChange();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
