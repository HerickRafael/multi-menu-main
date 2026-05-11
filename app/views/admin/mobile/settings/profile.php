<?php
/**
 * Perfil do Usuário Mobile
 */
ob_start();
?>

<form method="POST" action="/settings/profile" class="mobile-form">
    
    <div class="form-section">
        <div class="profile-avatar-section">
            <div class="user-avatar-xl">
                <?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Nome</label>
            <input type="text" name="name" class="form-input" required
                   value="<?= htmlspecialchars($user['name'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-input" required inputmode="email"
                   value="<?= htmlspecialchars($user['email'] ?? '') ?>">
        </div>
    </div>
    
    <div class="form-section">
        <h3 class="form-section-title">Alterar Senha</h3>
        <p class="form-hint">Deixe em branco para manter a senha atual</p>
        
        <div class="form-group">
            <label class="form-label">Senha Atual</label>
            <input type="password" name="current_password" class="form-input"
                   autocomplete="current-password">
        </div>
        
        <div class="form-group">
            <label class="form-label">Nova Senha</label>
            <input type="password" name="new_password" class="form-input" minlength="6"
                   autocomplete="new-password">
            <span class="form-hint">Mínimo 6 caracteres</span>
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn-primary btn-block">Salvar Alterações</button>
    </div>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
