<?php
/**
 * Configurações de Fidelidade Mobile
 * Abas: Cupons, Taxa Embutida, Cadastro Completo
 * Mesmo conteúdo do desktop, adaptado para mobile
 */
$cupons = $cupons ?? [];
$cupons_stats = $cupons_stats ?? ['total' => 0, 'active' => 0, 'used' => 0, 'totalUsage' => 0];
$embedded_delivery_fee = $embedded_delivery_fee ?? '0.00';
$loyalty_active = $loyalty_active ?? 0;
$loyalty_discount = $loyalty_discount ?? '0.00';
$loyalty_message = $loyalty_message ?? '';
$coupon_prefix = $coupon_prefix ?? 'WOLL';
$currentFee = (float)$embedded_delivery_fee;

ob_start();
?>

<style>
/* Tabs - estilo grid card */
.loyalty-tabs {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    margin-bottom: 16px;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
}

.loyalty-tab {
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

.loyalty-tab.active {
    background: var(--admin-primary-color, #4361ee);
    color: #fff;
    font-weight: 600;
    border-bottom-color: var(--admin-primary-color, #4361ee);
}

.loyalty-tab:not(.active) {
    background: none;
    color: #6b7280;
}

.loyalty-tab svg {
    width: 18px;
    height: 18px;
}

.section-panel {
    display: none;
}

.section-panel.active {
    display: block;
}

/* Cards */
.loyalty-card {
    background: white;
    border-radius: 16px;
    padding: 16px;
    margin-bottom: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
}

.loyalty-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.loyalty-card-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.loyalty-card-icon.purple { background: var(--admin-primary-soft, #ede9fe); color: var(--admin-primary-color, #7c3aed); }
.loyalty-card-icon.green { background: #d1fae5; color: #059669; }
.loyalty-card-icon.blue { background: #dbeafe; color: #2563eb; }
.loyalty-card-icon.yellow { background: #fef3c7; color: #d97706; }
.loyalty-card-icon.red { background: #fee2e2; color: #dc2626; }

.loyalty-card-title {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
}

.loyalty-card-desc {
    font-size: 13px;
    color: #6b7280;
    margin-top: 2px;
}

/* Stats grid */
.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 16px;
}

.stat-card {
    background: #f8fafc;
    border-radius: 12px;
    padding: 14px;
    border: 1px solid #e2e8f0;
}

.stat-card-label {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.stat-card-label svg {
    width: 14px;
    height: 14px;
}

.stat-card-value {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
}

.stat-card-value.green { color: #059669; }
.stat-card-value.slate { color: #64748b; }
.stat-card-value.purple { color: var(--admin-primary-color, #7c3aed); }

/* Coupon list */
.coupon-item {
    background: #f8fafc;
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 10px;
    border: 1px solid #e2e8f0;
}

.coupon-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.coupon-code {
    font-family: monospace;
    font-size: 14px;
    font-weight: 700;
    color: #059669;
    background: #ecfdf5;
    padding: 4px 10px;
    border-radius: 8px;
}

.coupon-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 20px;
}

.coupon-badge.active {
    background: #dcfce7;
    color: #15803d;
}

.coupon-badge.used {
    background: #f1f5f9;
    color: #64748b;
}

.coupon-details {
    display: flex;
    gap: 16px;
    font-size: 12px;
    color: #64748b;
}

.coupon-detail {
    display: flex;
    align-items: center;
    gap: 4px;
}

.coupon-detail svg {
    width: 14px;
    height: 14px;
}

.coupon-actions {
    display: flex;
    gap: 8px;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #e2e8f0;
}

.coupon-actions a,
.coupon-actions button {
    flex: 1;
    padding: 8px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    border: 1px solid #e2e8f0;
    background: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.coupon-actions a svg,
.coupon-actions button svg {
    width: 14px;
    height: 14px;
}

.coupon-actions .btn-edit { color: #2563eb; }
.coupon-actions .btn-delete { color: #dc2626; border-color: #fecaca; }

/* Form elements */
.form-group {
    margin-bottom: 16px;
}

.form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

.form-input {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    font-size: 14px;
    color: #1f2937;
    background: white;
    box-sizing: border-box;
    transition: border-color 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(91, 33, 182, 0.1);
}

.form-hint {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 4px;
}

.form-textarea {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    font-size: 14px;
    color: #1f2937;
    background: white;
    box-sizing: border-box;
    resize: vertical;
    min-height: 80px;
    font-family: inherit;
}

.form-textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(91, 33, 182, 0.1);
}

/* Toggle switch */
.toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 0;
}

.toggle-label {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
}

.toggle-desc {
    font-size: 12px;
    color: #6b7280;
    margin-top: 2px;
}

.toggle-switch {
    position: relative;
    width: 48px;
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
    background: #d1d5db;
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
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}

.toggle-switch input:checked + .toggle-slider {
    background: var(--primary);
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(20px);
}

/* Info box */
.info-box {
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 16px;
}

.info-box.blue { background: #eff6ff; border: 1px solid #bfdbfe; }
.info-box.green { background: #f0fdf4; border: 1px solid #bbf7d0; }
.info-box.yellow { background: #fefce8; border: 1px solid #fde68a; }
.info-box.purple { background: #faf5ff; border: 1px solid #e9d5ff; }

.info-box-title {
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.info-box.blue .info-box-title { color: #1e40af; }
.info-box.green .info-box-title { color: #166534; }
.info-box.yellow .info-box-title { color: #92400e; }
.info-box.purple .info-box-title { color: #6b21a8; }

.info-box ul {
    padding-left: 16px;
    margin: 0;
}

.info-box li {
    font-size: 12px;
    margin-bottom: 4px;
    line-height: 1.5;
}

.info-box.blue li { color: #1e3a5f; }
.info-box.green li { color: #14532d; }
.info-box.yellow li { color: #78350f; }
.info-box.purple li { color: #581c87; }

/* Status indicator */
.status-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 16px;
}

.status-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.status-dot.active { background: #22c55e; }
.status-dot.inactive { background: #d1d5db; }

.status-text {
    font-size: 13px;
    color: #374151;
}

/* Simulation box */
.simulation-box {
    background: #fefce8;
    border: 1px solid #fde68a;
    border-radius: 12px;
    padding: 14px;
    margin-top: 16px;
}

.simulation-title {
    font-size: 13px;
    font-weight: 600;
    color: #92400e;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.sim-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: #78350f;
    padding: 4px 0;
}

.sim-row.total {
    border-top: 1px solid #fde68a;
    margin-top: 6px;
    padding-top: 8px;
    font-weight: 700;
}

.sim-row.green { color: #15803d; }

/* Save button */
.btn-save {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 12px;
    background: var(--primary);
    color: white;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 16px;
    transition: opacity 0.2s;
}

.btn-save:active {
    opacity: 0.9;
}

.btn-save svg {
    width: 20px;
    height: 20px;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 32px 16px;
}

.empty-state svg {
    width: 48px;
    height: 48px;
    color: #cbd5e1;
    margin-bottom: 12px;
}

.empty-state-title {
    font-size: 15px;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 4px;
}

.empty-state-desc {
    font-size: 13px;
    color: #94a3b8;
}

/* Create coupon button */
.btn-create-coupon {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
    padding: 12px;
    border: 2px dashed var(--primary);
    border-radius: 12px;
    background: transparent;
    color: var(--primary);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: 16px;
    transition: background 0.2s;
}

.btn-create-coupon:active {
    background: rgba(91, 33, 182, 0.05);
}

.btn-create-coupon svg {
    width: 18px;
    height: 18px;
}

/* Example box */
.example-box {
    background: #faf5ff;
    border: 1px solid #e9d5ff;
    border-radius: 12px;
    padding: 14px;
    margin-top: 16px;
}

.example-title {
    font-size: 13px;
    font-weight: 600;
    color: #6b21a8;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.example-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: #581c87;
    padding: 3px 0;
}

.example-row.discount { color: #15803d; font-weight: 500; }

.example-row.total {
    border-top: 1px solid #e9d5ff;
    margin-top: 6px;
    padding-top: 8px;
    font-weight: 700;
}

/* Create coupon modal */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: flex-end;
    justify-content: center;
}

.modal-overlay.show {
    display: flex;
}

.modal-content {
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

.modal-handle {
    width: 40px;
    height: 4px;
    background: #d1d5db;
    border-radius: 2px;
    margin: 0 auto 16px;
}

.modal-title {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 16px;
}
</style>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Tabs de navegação -->
<div class="loyalty-tabs">
    <button type="button" class="loyalty-tab active" data-tab="cupons" onclick="switchLoyaltyTab('cupons')">
        <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58s1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41s-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/>
        </svg>
        Cupons
    </button>
    <button type="button" class="loyalty-tab" data-tab="taxa" onclick="switchLoyaltyTab('taxa')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
        </svg>
        Taxa
    </button>
    <button type="button" class="loyalty-tab" data-tab="cadastro" onclick="switchLoyaltyTab('cadastro')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        Cadastro
    </button>
</div>

<!-- ========== SEÇÃO 1: CUPONS ========== -->
<div id="tab-cupons" class="section-panel active">
    
    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-label">
                <svg viewBox="0 0 24 24" fill="currentColor" style="color:#6366f1"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58s1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41s-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>
                Total
            </div>
            <div class="stat-card-value"><?= (int)$cupons_stats['total'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-card-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.5"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Ativos
            </div>
            <div class="stat-card-value green"><?= (int)$cupons_stats['active'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-card-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.5"><path d="M5 13l4 4L19 7"/></svg>
                Usados
            </div>
            <div class="stat-card-value slate"><?= (int)$cupons_stats['used'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-card-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--primary,#7c3aed)" stroke-width="1.5"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Usos
            </div>
            <div class="stat-card-value purple"><?= (int)$cupons_stats['totalUsage'] ?></div>
        </div>
    </div>

    <!-- Botão Criar Cupom -->
    <button type="button" class="btn-create-coupon" onclick="openCreateCouponModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M12 4v16m8-8H4"/>
        </svg>
        Criar Novo Cupom
    </button>

    <!-- Lista de Cupons -->
    <div class="loyalty-card">
        <div class="loyalty-card-header">
            <div class="loyalty-card-icon purple">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58s1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41s-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/>
                </svg>
            </div>
            <div>
                <div class="loyalty-card-title">Cupons de Fidelidade</div>
                <div class="loyalty-card-desc">Gerencie os cupons dos clientes</div>
            </div>
        </div>

        <?php if (empty($cupons)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    <path d="M10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/>
                </svg>
                <div class="empty-state-title">Nenhum cupom cadastrado</div>
                <div class="empty-state-desc">Toque em "Criar Novo Cupom" para começar</div>
            </div>
        <?php else: ?>
            <?php foreach ($cupons as $cupom): 
                $timesUsed = (int)($cupom['times_used'] ?? 0);
                $usageLimit = (int)($cupom['usage_limit'] ?? 1);
                $isUsed = (int)($cupom['is_used'] ?? 0) === 1;
                $isFullyUsed = $isUsed || ($usageLimit > 0 && $timesUsed >= $usageLimit);
                $date = new DateTime($cupom['created_at']);
            ?>
                <div class="coupon-item">
                    <div class="coupon-header">
                        <span class="coupon-code"><?= htmlspecialchars($cupom['coupon_code']) ?></span>
                        <?php if ($isFullyUsed): ?>
                            <span class="coupon-badge used">Usado</span>
                        <?php else: ?>
                            <span class="coupon-badge active">Ativo</span>
                        <?php endif; ?>
                    </div>
                    <div class="coupon-details">
                        <div class="coupon-detail">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <?= htmlspecialchars(!empty($cupom['customer_phone']) ? $cupom['customer_phone'] : 'Genérico') ?>
                        </div>
                        <div class="coupon-detail">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <?= htmlspecialchars($cupom['discount_percentage']) ?>%
                        </div>
                        <div class="coupon-detail">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <?= $timesUsed ?>/<?= $usageLimit ?>
                        </div>
                    </div>
                    <div class="coupon-details" style="margin-top: 6px;">
                        <div class="coupon-detail">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6" stroke-linecap="round" stroke-linejoin="round"/><line x1="8" y1="2" x2="8" y2="6" stroke-linecap="round" stroke-linejoin="round"/><line x1="3" y1="10" x2="21" y2="10" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <?= $date->format('d/m/Y') ?>
                        </div>
                    </div>
                    <div class="coupon-actions">
                        <a href="/settings/loyalty/coupon/<?= (int)$cupom['id'] ?>/edit" class="btn-edit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" stroke-linecap="round" stroke-linejoin="round"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Editar
                        </a>
                        <form method="POST" action="/settings/loyalty/coupon/<?= (int)$cupom['id'] ?>/delete" onsubmit="return confirm('Excluir este cupom?')" style="flex:1;display:flex;">
                            <button type="submit" class="btn-delete" style="flex:1;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Excluir
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ========== SEÇÃO 2: TAXA EMBUTIDA ========== -->
<div id="tab-taxa" class="section-panel">
    <form method="POST" action="/settings/loyalty">

        <div class="loyalty-card">
            <div class="loyalty-card-header">
                <div class="loyalty-card-icon green">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div>
                    <div class="loyalty-card-title">Taxa Embutida</div>
                    <div class="loyalty-card-desc">Desconto de entrega via preço dos produtos</div>
                </div>
            </div>

            <!-- Info -->
            <div class="info-box blue">
                <div class="info-box-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="8" x2="12.01" y2="8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Como funciona
                </div>
                <ul>
                    <li>O valor é <strong>adicionado ao preço</strong> de todos os produtos</li>
                    <li>No checkout, vira <strong>desconto na taxa de entrega</strong></li>
                    <li>Ex: Taxa de R$1,00 + 2 produtos = R$2,00 de desconto na entrega</li>
                </ul>
            </div>

            <!-- Input -->
            <div class="form-group">
                <label class="form-label">Valor a embutir por produto (R$) <a href="/guide/loyalty-discount#embedded" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;" title="Ajuda">?</a></label>
                <input type="number" name="embedded_delivery_fee" id="embedded_delivery_fee"
                       value="<?= htmlspecialchars($embedded_delivery_fee) ?>"
                       step="0.01" min="0" max="10"
                       class="form-input" placeholder="0.00">
                <div class="form-hint">Digite 0 para desativar. Recomendado: entre R$0,50 e R$2,00</div>
            </div>

            <!-- Status -->
            <div class="status-box">
                <div style="font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">Status atual</div>
                <div class="status-row">
                    <span id="fee-status-dot" class="status-dot <?= $currentFee > 0 ? 'active' : 'inactive' ?>"></span>
                    <span id="fee-status-text" class="status-text">
                        <?= $currentFee > 0 ? 'Ativo — R$ ' . number_format($currentFee, 2, ',', '.') . ' por produto' : 'Desativado' ?>
                    </span>
                </div>
            </div>

            <!-- Simulação -->
            <div class="simulation-box">
                <div class="simulation-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="8" x2="12.01" y2="8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Simulação de exemplo
                </div>
                <div class="sim-row">
                    <span>Produto 1 (R$16,00):</span>
                    <span id="sim-p1">R$ <?= number_format(16 + $currentFee, 2, ',', '.') ?></span>
                </div>
                <div class="sim-row">
                    <span>Produto 2 (R$10,00):</span>
                    <span id="sim-p2">R$ <?= number_format(10 + $currentFee, 2, ',', '.') ?></span>
                </div>
                <div class="sim-row">
                    <span>Subtotal:</span>
                    <span id="sim-sub">R$ <?= number_format(26 + ($currentFee * 2), 2, ',', '.') ?></span>
                </div>
                <div class="sim-row green">
                    <span>Entrega (R$9,00 - desc):</span>
                    <span id="sim-del">R$ <?= number_format(max(0, 9 - ($currentFee * 2)), 2, ',', '.') ?></span>
                </div>
                <div class="sim-row total">
                    <span>Total:</span>
                    <span id="sim-total">R$ <?= number_format(26 + ($currentFee * 2) + max(0, 9 - ($currentFee * 2)), 2, ',', '.') ?></span>
                </div>
            </div>
        </div>

        <!-- Desconto por Cadastro Completo (dentro do mesmo form) -->
        <input type="hidden" name="_section" value="taxa">
        
        <button type="submit" class="btn-save">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M20 7L9 18l-5-5"/>
            </svg>
            Salvar Taxa Embutida
        </button>
    </form>
</div>

<!-- ========== SEÇÃO 3: CADASTRO COMPLETO ========== -->
<div id="tab-cadastro" class="section-panel">
    <form method="POST" action="/settings/loyalty">

        <div class="loyalty-card">
            <div class="loyalty-card-header">
                <div class="loyalty-card-icon yellow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div>
                    <div class="loyalty-card-title">Cadastro Completo</div>
                    <div class="loyalty-card-desc">Desconto para clientes com dados completos</div>
                </div>
            </div>

            <!-- Info -->
            <div class="info-box green">
                <div class="info-box-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 11-5.93-9.14" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 4L12 14.01l-3-3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Como funciona
                </div>
                <ul>
                    <li>Cliente preenche <strong>CPF e Data de Nascimento</strong> no perfil</li>
                    <li>Recebe automaticamente um <strong>desconto permanente</strong></li>
                    <li>O desconto é aplicado no valor total do pedido</li>
                    <li>Incentiva o cadastro completo e fideliza clientes</li>
                </ul>
            </div>

            <!-- Toggle Ativar -->
            <div class="toggle-row">
                <div>
                    <div class="toggle-label">Ativar desconto <a href="/guide/loyalty-discount#signup" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:4px;line-height:1;" title="Ajuda">?</a></div>
                    <div class="toggle-desc">Desconto por cadastro completo</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="loyalty_active" id="loyalty_active" <?= !empty($loyalty_active) ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <!-- Porcentagem -->
            <div class="form-group">
                <label class="form-label">Porcentagem de desconto (%) <a href="/guide/loyalty-discount#signup" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;" title="Ajuda">?</a></label>
                <input type="number" name="loyalty_discount" id="loyalty_discount"
                       value="<?= htmlspecialchars($loyalty_discount) ?>"
                       step="0.01" min="0" max="100"
                       class="form-input" placeholder="0.00">
                <div class="form-hint">Recomendado: entre 5% e 15%</div>
            </div>

            <!-- Mensagem -->
            <div class="form-group">
                <label class="form-label">Mensagem de boas-vindas</label>
                <textarea name="loyalty_message" id="loyalty_message"
                          class="form-textarea"
                          placeholder="Ex: Obrigado por completar seu cadastro! Aproveite seu desconto especial."><?= htmlspecialchars($loyalty_message) ?></textarea>
                <div class="form-hint">Mensagem exibida quando o cliente ganhar o desconto</div>
            </div>

            <!-- Prefixo -->
            <div class="form-group">
                <label class="form-label">Prefixo do cupom <a href="/guide/loyalty-discount#signup" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;" title="Ajuda">?</a></label>
                <input type="text" name="coupon_prefix" id="coupon_prefix"
                       value="<?= htmlspecialchars($coupon_prefix) ?>"
                       maxlength="10"
                       class="form-input"
                       placeholder="WOLL"
                       style="text-transform: uppercase;">
                <div class="form-hint">Prefixo usado no código. Ex: <strong><?= htmlspecialchars($coupon_prefix) ?></strong>123ABC</div>
            </div>

            <!-- Status -->
            <div class="status-box">
                <div style="font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">Status atual</div>
                <div class="status-row">
                    <?php $isLoyaltyActive = !empty($loyalty_active) && (float)$loyalty_discount > 0; ?>
                    <span class="status-dot <?= $isLoyaltyActive ? 'active' : 'inactive' ?>"></span>
                    <span class="status-text">
                        <?= $isLoyaltyActive ? 'Ativo — ' . number_format((float)$loyalty_discount, 0) . '% de desconto' : 'Desativado' ?>
                    </span>
                </div>
            </div>

            <!-- Exemplo -->
            <div class="example-box">
                <div class="example-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="8" x2="12.01" y2="8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Exemplo de aplicação
                </div>
                <div class="example-row">
                    <span>Subtotal:</span>
                    <span>R$ 50,00</span>
                </div>
                <div class="example-row discount">
                    <span>Desconto (<span id="ex-pct"><?= number_format((float)$loyalty_discount, 0) ?></span>%):</span>
                    <span id="ex-val">-R$ <?= number_format(50 * ((float)$loyalty_discount / 100), 2, ',', '.') ?></span>
                </div>
                <div class="example-row total">
                    <span>Total final:</span>
                    <span id="ex-total">R$ <?= number_format(50 - (50 * ((float)$loyalty_discount / 100)), 2, ',', '.') ?></span>
                </div>
            </div>
        </div>

        <input type="hidden" name="_section" value="cadastro">

        <button type="submit" class="btn-save">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M20 7L9 18l-5-5"/>
            </svg>
            Salvar Cadastro Completo
        </button>
    </form>
</div>

<!-- Modal Criar Cupom -->
<div id="createCouponModal" class="modal-overlay" onclick="if(event.target===this)closeCreateCouponModal()">
    <div class="modal-content">
        <div class="modal-handle"></div>
        <div class="modal-title">Criar Novo Cupom</div>
        
        <div class="form-group">
            <label class="form-label">Código do cupom (opcional)</label>
            <input type="text" id="modal-coupon-code" class="form-input" placeholder="Deixe vazio para gerar automaticamente" style="text-transform:uppercase;">
            <div class="form-hint">Se não preencher, será gerado automaticamente</div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Telefone do cliente (opcional)</label>
            <input type="text" id="modal-coupon-phone" class="form-input" placeholder="(00) 00000-0000">
            <div class="form-hint">Deixe vazio para cupom genérico (qualquer cliente pode usar)</div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Desconto (%)</label>
            <input type="number" id="modal-coupon-discount" class="form-input" value="10" min="1" max="100" step="1">
        </div>
        
        <div class="form-group">
            <label class="form-label">Limite de usos</label>
            <input type="number" id="modal-coupon-limit" class="form-input" value="1" min="0">
            <div class="form-hint">0 = ilimitado</div>
        </div>
        
        <div id="modal-coupon-error" style="display:none;padding:10px;border-radius:10px;background:#fef2f2;color:#dc2626;font-size:13px;margin-bottom:12px;"></div>
        <div id="modal-coupon-success" style="display:none;padding:10px;border-radius:10px;background:#f0fdf4;color:#15803d;font-size:13px;margin-bottom:12px;"></div>
        
        <button type="button" class="btn-save" onclick="createCoupon()" id="btn-create-coupon-submit">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 5v14m-7-7h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Criar Cupom
        </button>
        
        <button type="button" onclick="closeCreateCouponModal()" style="width:100%;padding:12px;border:1px solid #e5e7eb;border-radius:12px;background:white;color:#6b7280;font-size:14px;font-weight:600;cursor:pointer;margin-top:8px;">
            Cancelar
        </button>
    </div>
</div>

<script>
// Tab switching
function switchLoyaltyTab(tabName) {
    document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.loyalty-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + tabName).classList.add('active');
    document.querySelector('[data-tab="' + tabName + '"]').classList.add('active');
}

// Taxa embutida - simulação em tempo real
const feeInput = document.getElementById('embedded_delivery_fee');
if (feeInput) {
    feeInput.addEventListener('input', function() {
        const fee = parseFloat(this.value) || 0;
        const isActive = fee > 0;
        
        const dot = document.getElementById('fee-status-dot');
        const txt = document.getElementById('fee-status-text');
        
        dot.className = 'status-dot ' + (isActive ? 'active' : 'inactive');
        txt.textContent = isActive 
            ? 'Ativo — R$ ' + fee.toFixed(2).replace('.', ',') + ' por produto'
            : 'Desativado';
        
        const p1 = 16 + fee, p2 = 10 + fee;
        const sub = p1 + p2;
        const del = Math.max(0, 9 - (fee * 2));
        const total = sub + del;
        
        const fmt = (v) => 'R$ ' + v.toFixed(2).replace('.', ',');
        document.getElementById('sim-p1').textContent = fmt(p1);
        document.getElementById('sim-p2').textContent = fmt(p2);
        document.getElementById('sim-sub').textContent = fmt(sub);
        document.getElementById('sim-del').textContent = fmt(del);
        document.getElementById('sim-total').textContent = fmt(total);
    });
}

// Desconto cadastro - simulação em tempo real
const ldInput = document.getElementById('loyalty_discount');
if (ldInput) {
    ldInput.addEventListener('input', function() {
        const d = parseFloat(this.value) || 0;
        const dv = 50 * (d / 100);
        const total = 50 - dv;
        const fmt = (v) => 'R$ ' + v.toFixed(2).replace('.', ',');
        
        document.getElementById('ex-pct').textContent = d.toFixed(0);
        document.getElementById('ex-val').textContent = '-' + fmt(dv);
        document.getElementById('ex-total').textContent = fmt(total);
    });
}

// Modal criar cupom
function openCreateCouponModal() {
    document.getElementById('createCouponModal').classList.add('show');
    document.getElementById('modal-coupon-error').style.display = 'none';
    document.getElementById('modal-coupon-success').style.display = 'none';
}

function closeCreateCouponModal() {
    document.getElementById('createCouponModal').classList.remove('show');
}

// Phone mask
const phoneField = document.getElementById('modal-coupon-phone');
if (phoneField) {
    phoneField.addEventListener('input', function() {
        let v = this.value.replace(/\D/g, '').substring(0, 11);
        let f = '';
        if (v.length > 0) f = '(' + v.substring(0, 2);
        if (v.length > 2) f += ') ' + v.substring(2, 7);
        if (v.length > 7) f += '-' + v.substring(7, 11);
        this.value = f;
    });
}

// Criar cupom via AJAX
async function createCoupon() {
    const errEl = document.getElementById('modal-coupon-error');
    const sucEl = document.getElementById('modal-coupon-success');
    const btn = document.getElementById('btn-create-coupon-submit');
    
    errEl.style.display = 'none';
    sucEl.style.display = 'none';
    
    const code = document.getElementById('modal-coupon-code').value.trim();
    const phone = document.getElementById('modal-coupon-phone').value.replace(/\D/g, '');
    const discount = parseFloat(document.getElementById('modal-coupon-discount').value) || 0;
    const limit = parseInt(document.getElementById('modal-coupon-limit').value) || 0;
    
    if (discount < 1 || discount > 100) {
        errEl.textContent = 'Desconto deve estar entre 1% e 100%';
        errEl.style.display = 'block';
        return;
    }
    
    btn.disabled = true;
    btn.style.opacity = '0.6';
    
    try {
        const response = await fetch('/settings/loyalty/create-coupon', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code, phone, discount, limit })
        });
        
        const result = await response.json();
        
        if (result.success) {
            sucEl.textContent = '✓ Cupom ' + result.coupon_code + ' criado com sucesso!';
            sucEl.style.display = 'block';
            
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            errEl.textContent = result.message || 'Erro ao criar cupom';
            errEl.style.display = 'block';
        }
    } catch (err) {
        errEl.textContent = 'Erro de conexão. Tente novamente.';
        errEl.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.style.opacity = '1';
    }
}

// Check URL params for tab
const urlParams = new URLSearchParams(window.location.search);
const tab = urlParams.get('tab');
if (tab && ['cupons', 'taxa', 'cadastro'].includes(tab)) {
    switchLoyaltyTab(tab);
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
