<?php
/**
 * Formulário de Cliente Mobile
 */
$isEdit = !empty($customer);
ob_start();
?>

<form method="POST" action="<?= $isEdit ? "/customers/{$customer['id']}" : '/customers' ?>" class="mobile-form">
    
    <div class="form-section">
        <div class="form-group">
            <label class="form-label">Nome</label>
            <input type="text" name="name" class="form-input"
                   value="<?= htmlspecialchars($customer['name'] ?? '') ?>"
                   placeholder="Nome do cliente">
        </div>
        
        <div class="form-group">
            <label class="form-label">WhatsApp *</label>
            <div class="input-with-prefix">
                <span class="prefix">+55</span>
                <input type="tel" name="whatsapp" class="form-input" required
                       inputmode="tel" maxlength="15"
                       value="<?= htmlspecialchars($customer['whatsapp'] ?? '') ?>"
                       placeholder="11999999999">
            </div>
            <span class="form-hint">Apenas números, com DDD</span>
        </div>
        
        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-input" inputmode="email"
                   value="<?= htmlspecialchars($customer['email'] ?? '') ?>"
                   placeholder="email@exemplo.com">
        </div>
        
        <div class="form-group">
            <label class="form-label">Observações</label>
            <textarea name="notes" class="form-input" rows="3"
                      placeholder="Anotações sobre o cliente..."><?= htmlspecialchars($customer['notes'] ?? '') ?></textarea>
        </div>
    </div>
    
    <!-- Botões -->
    <div class="form-actions">
        <button type="submit" class="btn-primary btn-block">
            <?= $isEdit ? 'Salvar Alterações' : 'Cadastrar Cliente' ?>
        </button>
        
        <?php if ($isEdit): ?>
            <button type="button" class="btn-danger btn-block mt-sm" onclick="confirmDelete()">
                Excluir Cliente
            </button>
        <?php endif; ?>
    </div>
</form>

<?php if ($isEdit): ?>
<form id="deleteForm" method="POST" action="/customers/<?= $customer['id'] ?>/delete" style="display: none;"></form>

<script>
function confirmDelete() {
    if (confirm('Tem certeza que deseja excluir este cliente?')) {
        document.getElementById('deleteForm').submit();
    }
}
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
