<?php $hideTopbar = false; ?>
<?php include __DIR__ . '/../layout.php'; ?>

<div class="super-admin-content">
    <!-- Flash Message -->
    <?php if (!empty($flash)): ?>
        <div class="flash-message flash-<?= htmlspecialchars($flash['type']); ?>">
            <span><?= htmlspecialchars($flash['message']); ?></span>
            <button type="button" class="flash-close" onclick="this.parentElement.style.display='none';">×</button>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="detail-header">
        <div class="detail-header-content">
            <?php if ($store['logo']): ?>
                <img src="<?= htmlspecialchars($store['logo']); ?>" alt="Logo" class="detail-logo">
            <?php endif; ?>
            <div>
                <h1><?= htmlspecialchars($store['name']); ?></h1>
                <p class="slug"><?= htmlspecialchars($store['slug']); ?></p>
            </div>
        </div>
        <div class="detail-header-actions">
            <span class="badge badge-<?= $store['status']; ?>">
                <?= ucfirst($store['status']); ?>
            </span>
            <a href="<?= base_url('superadmin/stores/' . $store['id'] . '/edit'); ?>" class="btn btn-primary">
                Editar
            </a>
            <a href="<?= base_url('superadmin/stores'); ?>" class="btn btn-secondary">
                Voltar
            </a>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="metrics-grid">
        <div class="metric-card">
            <p class="metric-label">Pedidos Hoje</p>
            <p class="metric-value"><?= $store['summary']['total_today'] ?? 0; ?></p>
        </div>
        <div class="metric-card">
            <p class="metric-label">Pedidos Ativos</p>
            <p class="metric-value"><?= $store['summary']['active_orders'] ?? 0; ?></p>
        </div>
        <div class="metric-card">
            <p class="metric-label">Receita Hoje</p>
            <p class="metric-value">R$ <?= number_format($store['summary']['revenue_today'] ?? 0, 2, ',', '.'); ?></p>
        </div>
        <div class="metric-card">
            <p class="metric-label">Tempo Médio Prep</p>
            <p class="metric-value"><?= $store['summary']['avg_prep_time'] ?? 0; ?>min</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <div class="tabs-header">
            <button class="tab-btn active" data-tab="info">Informações</button>
            <button class="tab-btn" data-tab="status">Status & Histórico</button>
            <button class="tab-btn" data-tab="resources">Recursos</button>
            <button class="tab-btn" data-tab="actions">Ações Operacionais</button>
        </div>

        <!-- Tab: Informações -->
        <div class="tab-content active" id="tab-info">
            <div class="info-grid">
                <div class="info-item">
                    <label>Nome</label>
                    <p><?= htmlspecialchars($store['name']); ?></p>
                </div>
                <div class="info-item">
                    <label>Slug</label>
                    <p><?= htmlspecialchars($store['slug']); ?></p>
                </div>
                <div class="info-item">
                    <label>WhatsApp</label>
                    <p><?= htmlspecialchars($store['whatsapp'] ?? 'Não informado'); ?></p>
                </div>
                <div class="info-item">
                    <label>Endereço</label>
                    <p><?= htmlspecialchars($store['address'] ?? 'Não informado'); ?></p>
                </div>
                <div class="info-item">
                    <label>Criada em</label>
                    <p><?= date('d/m/Y H:i', strtotime($store['created_at'])); ?></p>
                </div>
                <div class="info-item">
                    <label>Status Ativa</label>
                    <p><strong><?= $store['active'] ? 'Sim' : 'Não'; ?></strong></p>
                </div>
            </div>
        </div>

        <!-- Tab: Status & Histórico -->
        <div class="tab-content" id="tab-status">
            <div class="status-section">
                <h3>Status Atual</h3>
                <div class="status-info">
                    <p><strong>Status:</strong> <span class="badge badge-<?= $store['status']; ?>"><?= ucfirst($store['status']); ?></span></p>
                    <p><strong>Motivo:</strong> <?= htmlspecialchars($store['status_reason'] ?? 'Nenhum'); ?></p>
                    <p><strong>Alterado em:</strong> <?= date('d/m/Y H:i:s', strtotime($store['status_changed_at'])); ?></p>
                </div>
            </div>

            <div class="status-section">
                <h3>Histórico de Mudanças</h3>
                <div class="history-list">
                    <?php if (empty($store['history'])): ?>
                        <p class="empty">Nenhum histórico registrado</p>
                    <?php else: ?>
                        <?php foreach ($store['history'] as $change): ?>
                            <div class="history-item">
                                <div class="history-badge badge badge-<?= htmlspecialchars($change['status']); ?>">
                                    <?= ucfirst($change['status']); ?>
                                </div>
                                <div class="history-detail">
                                    <p class="history-reason"><?= htmlspecialchars($change['reason'] ?? 'Sem motivo'); ?></p>
                                    <p class="history-date"><?= date('d/m/Y H:i:s', strtotime($change['changed_at'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tab: Recursos -->
        <div class="tab-content" id="tab-resources">
            <div class="resources-list">
                <h3>Recursos Habilitados</h3>
                <?php if (empty($store['resources'])): ?>
                    <p class="empty">Nenhum recurso encontrado</p>
                <?php else: ?>
                    <div class="resource-items">
                        <?php foreach ($store['resources'] as $resource): ?>
                            <div class="resource-item">
                                <div class="resource-info">
                                    <p class="resource-name"><?= htmlspecialchars($resource['resource_name']); ?></p>
                                    <p class="resource-status">
                                        <strong>Status:</strong>
                                        <span class="badge <?= $resource['enabled'] ? 'badge-active' : 'badge-inactive'; ?>">
                                            <?= $resource['enabled'] ? 'Ativado' : 'Desativado'; ?>
                                        </span>
                                    </p>
                                </div>
                                <p class="resource-date">
                                    Ativado em: <?= date('d/m/Y', strtotime($resource['enabled_at'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab: Ações Operacionais -->
        <div class="tab-content" id="tab-actions">
            <div class="actions-section">
                <h3>Gerenciar Status</h3>
                <div class="actions-grid">
                    <?php if ($store['status'] !== 'active'): ?>
                        <form method="POST" action="<?= base_url('superadmin/stores/' . $store['id'] . '/activate'); ?>" class="action-form">
                            <button type="submit" class="btn btn-success" onclick="return confirm('Ativar esta loja?');">
                                Ativar Loja
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($store['status'] !== 'suspended'): ?>
                        <button type="button" class="btn btn-warning" onclick="showSuspendModal()">
                            Suspender
                        </button>
                    <?php endif; ?>

                    <?php if ($store['status'] !== 'maintenance'): ?>
                        <button type="button" class="btn btn-info" onclick="showMaintenanceModal()">
                            Manutenção
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Suspend Modal -->
<div id="suspendModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Suspender Loja</h2>
            <button type="button" class="modal-close" onclick="closeSuspendModal()">×</button>
        </div>
        <form method="POST" action="<?= base_url('superadmin/stores/' . $store['id'] . '/suspend'); ?>">
            <div class="form-group">
                <label>Motivo da Suspensão</label>
                <textarea name="reason" required placeholder="Descreva o motivo..." class="form-control"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeSuspendModal()">Cancelar</button>
                <button type="submit" class="btn btn-danger">Suspender</button>
            </div>
        </form>
    </div>
</div>

<!-- Maintenance Modal -->
<div id="maintenanceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Manutenção</h2>
            <button type="button" class="modal-close" onclick="closeMaintenanceModal()">×</button>
        </div>
        <form method="POST" action="<?= base_url('superadmin/stores/' . $store['id'] . '/maintenance'); ?>">
            <div class="form-group">
                <label>Motivo da Manutenção</label>
                <textarea name="reason" required placeholder="Descreva a manutenção..." class="form-control"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeMaintenanceModal()">Cancelar</button>
                <button type="submit" class="btn btn-info">Confirmar Manutenção</button>
            </div>
        </form>
    </div>
</div>

<style>
.detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.detail-header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.detail-logo {
    width: 4rem;
    height: 4rem;
    border-radius: 0.5rem;
    object-fit: cover;
    background: #f3f4f6;
}

.detail-header-content h1 {
    margin: 0 0 0.25rem 0;
    font-size: 1.5rem;
    color: #1f2937;
}

.slug {
    margin: 0;
    color: #6b7280;
    font-size: 0.875rem;
}

.detail-header-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.metric-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1rem;
    text-align: center;
}

.metric-label {
    margin: 0 0 0.5rem 0;
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #6b7280;
    font-weight: 600;
}

.metric-value {
    margin: 0;
    font-size: 1.875rem;
    font-weight: 700;
    color: #1f2937;
}

.tabs {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
}

.tabs-header {
    display: flex;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
}

.tab-btn {
    flex: 1;
    padding: 1rem;
    border: none;
    background: transparent;
    cursor: pointer;
    font-weight: 500;
    color: #6b7280;
    border-bottom: 2px solid transparent;
    transition: all 0.2s ease;
    text-align: center;
}

.tab-btn:hover {
    color: #1f2937;
}

.tab-btn.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
}

.tab-content {
    display: none;
    padding: 2rem;
}

.tab-content.active {
    display: block;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.info-item label {
    display: block;
    font-size: 0.875rem;
    text-transform: uppercase;
    color: #6b7280;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.info-item p {
    margin: 0;
    color: #1f2937;
    font-size: 0.875rem;
}

.status-section {
    margin-bottom: 2rem;
}

.status-section h3 {
    margin: 0 0 1rem 0;
    color: #1f2937;
}

.status-info p {
    margin: 0.5rem 0;
}

.history-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.history-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 0.375rem;
    align-items: flex-start;
}

.history-badge {
    flex-shrink: 0;
}

.history-reason {
    margin: 0 0 0.25rem 0;
    color: #1f2937;
    font-weight: 500;
}

.history-date {
    margin: 0;
    color: #9ca3af;
    font-size: 0.75rem;
}

.empty {
    color: #9ca3af;
    font-style: italic;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.resource-items {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.resource-item {
    padding: 1rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
}

.resource-name {
    margin: 0 0 0.5rem 0;
    font-weight: 600;
    color: #1f2937;
}

.resource-status {
    margin: 0 0 0.5rem 0;
}

.resource-date {
    margin: 0;
    font-size: 0.75rem;
    color: #6b7280;
}

.badge-inactive {
    background: rgba(107, 114, 128, 0.1);
    color: #374151;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 0.5rem;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
    margin: 0;
    color: #1f2937;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
}

.modal-footer {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    padding: 1rem;
    border-top: 1px solid #e5e7eb;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #1f2937;
}

.form-control {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-family: inherit;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover {
    background: #059669;
}

.btn-warning {
    background: #f59e0b;
    color: white;
}

.btn-warning:hover {
    background: #d97706;
}

.btn-info {
    background: #0ea5e9;
    color: white;
}

.btn-info:hover {
    background: #0284c7;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}
</style>

<script>
function showSuspendModal() {
    document.getElementById('suspendModal').classList.add('active');
}

function closeSuspendModal() {
    document.getElementById('suspendModal').classList.remove('active');
}

function showMaintenanceModal() {
    document.getElementById('maintenanceModal').classList.add('active');
}

function closeMaintenanceModal() {
    document.getElementById('maintenanceModal').classList.remove('active');
}

// Tab switching
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tabId = this.getAttribute('data-tab');
        
        // Remove active from all tabs and buttons
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        
        // Add active to current tab and button
        document.getElementById('tab-' + tabId).classList.add('active');
        this.classList.add('active');
    });
});
</script>
