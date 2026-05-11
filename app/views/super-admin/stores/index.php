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
    <div class="page-header">
        <h1>Gestão de Lojas</h1>
        <p>Controle operacional de todas as lojas da plataforma</p>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <input type="text" name="q" placeholder="Buscar por nome ou slug..." value="<?= htmlspecialchars($filters['search']); ?>" class="filter-input">
            </div>
            
            <div class="filter-group">
                <select name="status" class="filter-select">
                    <option value="">Todos os status</option>
                    <option value="active" <?= $filters['status'] === 'active' ? 'selected' : ''; ?>>Ativas</option>
                    <option value="suspended" <?= $filters['status'] === 'suspended' ? 'selected' : ''; ?>>Suspensas</option>
                    <option value="maintenance" <?= $filters['status'] === 'maintenance' ? 'selected' : ''; ?>>Manutenção</option>
                    <option value="blocked" <?= $filters['status'] === 'blocked' ? 'selected' : ''; ?>>Bloqueadas</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="<?= base_url('superadmin/stores'); ?>" class="btn btn-secondary">Limpar</a>
        </form>
    </div>

    <!-- Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Loja</th>
                    <th>Status</th>
                    <th>Pedidos Hoje</th>
                    <th>Receita Hoje</th>
                    <th>Pedidos Ativos</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($companies)): ?>
                    <tr>
                        <td colspan="6" class="text-center">Nenhuma loja encontrada</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($companies as $company): ?>
                        <tr>
                            <td>
                                <div class="store-name">
                                    <?php if ($company['logo']): ?>
                                        <img src="<?= htmlspecialchars($company['logo']); ?>" alt="Logo" class="store-logo">
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= htmlspecialchars($company['name']); ?></strong>
                                        <small><?= htmlspecialchars($company['slug']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-<?= $company['status'] ?? 'active'; ?>">
                                    <?= ucfirst($company['status'] ?? 'active'); ?>
                                </span>
                            </td>
                            <td><?= $company['metrics']['total_orders_today'] ?? 0; ?></td>
                            <td>R$ <?= number_format($company['metrics']['total_revenue_today'] ?? 0, 2, ',', '.'); ?></td>
                            <td><?= $company['metrics']['active_orders_now'] ?? 0; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="<?= base_url('superadmin/stores/' . $company['id']); ?>" class="btn btn-sm btn-outline" title="Ver Detalhes">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </a>
                                    <a href="<?= base_url('superadmin/stores/' . $company['id'] . '/edit'); ?>" class="btn btn-sm btn-outline" title="Editar">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['pages'] > 1): ?>
        <div class="pagination">
            <?php if ($pagination['page'] > 1): ?>
                <a href="<?= base_url('superadmin/stores?page=1' . ($filters['search'] ? '&q=' . urlencode($filters['search']) : '') . ($filters['status'] ? '&status=' . urlencode($filters['status']) : '')); ?>" class="btn btn-sm">Primeira</a>
                <a href="<?= base_url('superadmin/stores?page=' . ($pagination['page'] - 1) . ($filters['search'] ? '&q=' . urlencode($filters['search']) : '') . ($filters['status'] ? '&status=' . urlencode($filters['status']) : '')); ?>" class="btn btn-sm">Anterior</a>
            <?php endif; ?>

            <span class="pagination-info">
                Página <?= $pagination['page']; ?> de <?= $pagination['pages']; ?>
                (<?= $pagination['total']; ?> lojas)
            </span>

            <?php if ($pagination['page'] < $pagination['pages']): ?>
                <a href="<?= base_url('superadmin/stores?page=' . ($pagination['page'] + 1) . ($filters['search'] ? '&q=' . urlencode($filters['search']) : '') . ($filters['status'] ? '&status=' . urlencode($filters['status']) : '')); ?>" class="btn btn-sm">Próxima</a>
                <a href="<?= base_url('superadmin/stores?page=' . $pagination['pages'] . ($filters['search'] ? '&q=' . urlencode($filters['search']) : '') . ($filters['status'] ? '&status=' . urlencode($filters['status']) : '')); ?>" class="btn btn-sm">Última</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.page-header {
    margin-bottom: 2rem;
}

.page-header h1 {
    font-size: 1.875rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #1f2937;
}

.page-header p {
    color: #6b7280;
    font-size: 0.875rem;
}

.filters-bar {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 2rem;
}

.filters-form {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-input, .filter-select {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-family: inherit;
}

.filter-input:focus, .filter-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.table-container {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
    margin-bottom: 2rem;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.data-table thead {
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.data-table th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    text-transform: uppercase;
    font-size: 0.75rem;
}

.data-table td {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
}

.data-table tbody tr:hover {
    background: #f9fafb;
}

.store-name {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.store-logo {
    width: 2rem;
    height: 2rem;
    border-radius: 0.25rem;
    object-fit: cover;
    background: #f3f4f6;
}

.store-name small {
    display: block;
    color: #9ca3af;
    font-size: 0.75rem;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-active {
    background: rgba(34, 197, 94, 0.1);
    color: #166534;
}

.badge-suspended {
    background: rgba(251, 146, 60, 0.1);
    color: #92400e;
}

.badge-maintenance {
    background: rgba(59, 130, 246, 0.1);
    color: #1e40af;
}

.badge-blocked {
    background: rgba(239, 68, 68, 0.1);
    color: #991b1b;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-secondary {
    background: #e5e7eb;
    color: #1f2937;
}

.btn-secondary:hover {
    background: #d1d5db;
}

.btn-outline {
    background: transparent;
    border: 1px solid #d1d5db;
    color: #374151;
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.pagination {
    display: flex;
    align-items: center;
    gap: 1rem;
    justify-content: center;
    padding: 2rem 0;
}

.pagination-info {
    font-size: 0.875rem;
    color: #6b7280;
}

.text-center {
    text-align: center;
    color: #9ca3af;
    padding: 2rem;
}

.flash-message {
    margin-bottom: 1rem;
    padding: 1rem;
    border-radius: 0.375rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    animation: slideDown 0.3s ease;
}

.flash-success {
    background-color: rgba(34, 197, 94, 0.1);
    border: 1px solid #86efac;
    color: #166534;
}

.flash-error {
    background-color: rgba(239, 68, 68, 0.1);
    border: 1px solid #fca5a5;
    color: #991b1b;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-1rem);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
