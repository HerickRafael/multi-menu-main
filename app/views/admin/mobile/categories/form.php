<?php
/**
 * Formulário de Categoria Mobile
 */
$isEdit = !empty($category);
ob_start();
?>

<form method="POST" action="<?= $isEdit ? "/categories/{$category['id']}" : '/categories' ?>" 
      enctype="multipart/form-data" class="mobile-form">
    
    <!-- Imagem -->
    <div class="form-section">
        <div class="image-upload-area" onclick="document.getElementById('imageInput').click()">
            <?php if ($isEdit && !empty($category['image'])): ?>
                <img src="/<?= htmlspecialchars($category['image']) ?>" alt="Categoria" id="imagePreview">
            <?php else: ?>
                <div class="image-placeholder" id="imagePlaceholder">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    <span>Toque para adicionar imagem</span>
                </div>
                <img src="" alt="Preview" id="imagePreview" style="display: none;">
            <?php endif; ?>
        </div>
        <input type="file" name="image" id="imageInput" accept="image/*" style="display: none;" onchange="previewImage(this)">
    </div>
    
    <!-- Dados -->
    <div class="form-section">
        <div class="form-group">
            <label class="form-label">Nome da Categoria *</label>
            <input type="text" name="name" class="form-input" required
                   value="<?= htmlspecialchars($category['name'] ?? '') ?>"
                   placeholder="Ex: Hambúrgueres">
        </div>
        
        <div class="form-group">
            <label class="form-label">Descrição</label>
            <textarea name="description" class="form-input" rows="2"
                      placeholder="Descrição opcional..."><?= htmlspecialchars($category['description'] ?? '') ?></textarea>
        </div>
        
        <div class="form-group">
            <label class="form-label">Ordem de exibição</label>
            <input type="number" name="sort_order" class="form-input" inputmode="numeric"
                   value="<?= (int)($category['sort_order'] ?? 0) ?>"
                   placeholder="0">
            <span class="form-hint">Menor número aparece primeiro</span>
        </div>
        
        <div class="form-group">
            <label class="toggle-switch">
                <input type="checkbox" name="active" value="1" <?= ($category['active'] ?? 1) ? 'checked' : '' ?>>
                <span class="toggle-slider"></span>
                <span class="toggle-label">Categoria ativa</span>
            </label>
        </div>
    </div>
    
    <!-- Botões -->
    <div class="form-actions">
        <button type="submit" class="btn-primary btn-block">
            <?= $isEdit ? 'Salvar Alterações' : 'Criar Categoria' ?>
        </button>
        
        <?php if ($isEdit): ?>
            <button type="button" class="btn-danger btn-block mt-sm" onclick="confirmDelete()">
                Excluir Categoria
            </button>
        <?php endif; ?>
    </div>
</form>

<?php if ($isEdit): ?>
<form id="deleteForm" method="POST" action="/categories/<?= $category['id'] ?>/delete" style="display: none;"></form>
<?php endif; ?>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            const placeholder = document.getElementById('imagePlaceholder');
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function confirmDelete() {
    if (confirm('Tem certeza que deseja excluir esta categoria?')) {
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
