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
    <div class="form-header">
        <h1>Editar Loja</h1>
        <p><?= htmlspecialchars($store['name']); ?></p>
    </div>

    <!-- Form -->
    <div class="form-container">
        <form method="POST" action="<?= base_url('superadmin/stores/' . $store['id'] . '/update'); ?>" class="edit-form">
            <div class="form-section">
                <h2>Informações Básicas</h2>

                <div class="form-group">
                    <label for="name">Nome da Loja *</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="<?= htmlspecialchars($old['name'] ?? $store['name'] ?? ''); ?>" 
                        placeholder="Ex: Minha Loja"
                        class="form-control <?= isset($errors['name']) ? 'error' : ''; ?>"
                        required
                    >
                    <?php if (isset($errors['name'])): ?>
                        <span class="error-message"><?= htmlspecialchars($errors['name']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="slug">URL Amigável (Slug)</label>
                    <input 
                        type="text" 
                        id="slug" 
                        value="<?= htmlspecialchars($store['slug'] ?? ''); ?>" 
                        placeholder="Ex: minha-loja"
                        class="form-control"
                        disabled
                    >
                    <small>Gerada automaticamente. Contate o suporte para alterar.</small>
                </div>

                <div class="form-group">
                    <label for="whatsapp">WhatsApp</label>
                    <input 
                        type="tel" 
                        id="whatsapp" 
                        name="whatsapp" 
                        value="<?= htmlspecialchars($old['whatsapp'] ?? $store['whatsapp'] ?? ''); ?>" 
                        placeholder="Ex: 5551999999999"
                        class="form-control"
                    >
                    <small>Número com DDD e código de país (55)</small>
                </div>

                <div class="form-group">
                    <label for="address">Endereço</label>
                    <input 
                        type="text" 
                        id="address" 
                        name="address" 
                        value="<?= htmlspecialchars($old['address'] ?? $store['address'] ?? ''); ?>" 
                        placeholder="Ex: Rua Principal, 123"
                        class="form-control"
                    >
                </div>

                <div class="form-group">
                    <label for="min_order">Pedido Mínimo (R$)</label>
                    <input 
                        type="number" 
                        id="min_order" 
                        name="min_order" 
                        value="<?= htmlspecialchars($old['min_order'] ?? $store['min_order'] ?? ''); ?>" 
                        placeholder="Ex: 15.00"
                        step="0.01"
                        min="0"
                        class="form-control <?= isset($errors['min_order']) ? 'error' : ''; ?>"
                    >
                    <?php if (isset($errors['min_order'])): ?>
                        <span class="error-message"><?= htmlspecialchars($errors['min_order']); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="form-section">
                <h2>Informações Adicionais</h2>

                <div class="info-grid">
                    <div class="info-item">
                        <label>Status</label>
                        <p><span class="badge badge-<?= $store['status']; ?>"><?= ucfirst($store['status']); ?></span></p>
                    </div>
                    <div class="info-item">
                        <label>Ativa</label>
                        <p><?= $store['active'] ? '✓ Sim' : '✗ Não'; ?></p>
                    </div>
                    <div class="info-item">
                        <label>Criada em</label>
                        <p><?= date('d/m/Y H:i', strtotime($store['created_at'])); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Status Alterado em</label>
                        <p><?= date('d/m/Y H:i', strtotime($store['status_changed_at'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-large">
                    Salvar Alterações
                </button>
                <a href="<?= base_url('superadmin/stores/' . $store['id']); ?>" class="btn btn-secondary btn-large">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    <!-- Help Section -->
    <div class="help-section">
        <h3>Precisa de mais?</h3>
        <ul>
            <li>Para suspender ou bloquear a loja, acesse a página de detalhes</li>
            <li>Para alterar o slug, contate o suporte técnico</li>
            <li>Todas as alterações são registradas em auditoria</li>
        </ul>
    </div>
</div>

<style>
.form-header {
    margin-bottom: 2rem;
}

.form-header h1 {
    font-size: 1.875rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    color: #1f2937;
}

.form-header p {
    margin: 0;
    color: #6b7280;
    font-size: 0.875rem;
}

.form-container {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 2rem;
    margin-bottom: 2rem;
}

.form-section {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #e5e7eb;
}

.form-section:last-of-type {
    border-bottom: none;
}

.form-section h2 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 1.5rem 0;
    color: #1f2937;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #1f2937;
    font-size: 0.875rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-family: inherit;
    transition: all 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-control.error {
    border-color: #ef4444;
    background: rgba(239, 68, 68, 0.05);
}

.form-control:disabled {
    background: #f3f4f6;
    color: #6b7280;
    cursor: not-allowed;
}

.form-control small {
    display: block;
    color: #6b7280;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.error-message {
    display: block;
    color: #dc2626;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    background: #f9fafb;
    padding: 1.5rem;
    border-radius: 0.375rem;
}

.info-item label {
    display: block;
    font-size: 0.75rem;
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

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    justify-content: flex-end;
}

.btn-large {
    padding: 0.75rem 2rem;
}

.help-section {
    background: rgba(59, 130, 246, 0.05);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.help-section h3 {
    margin: 0 0 1rem 0;
    color: #1e40af;
    font-size: 0.875rem;
    font-weight: 600;
}

.help-section ul {
    margin: 0;
    padding-left: 1rem;
    color: #1e40af;
    font-size: 0.875rem;
}

.help-section li {
    margin-bottom: 0.5rem;
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

.flash-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: inherit;
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
