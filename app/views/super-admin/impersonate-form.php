<?php
// app/views/super-admin/impersonate-form.php

$company = $company ?? [];
$errors = $_SESSION['superadmin_errors'] ?? [];
$old = $_SESSION['superadmin_old_input'] ?? [];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar como Loja - Super Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .card-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .card-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        .card-body {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .company-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #667eea;
        }
        .company-info h3 {
            font-size: 13px;
            color: #999;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-weight: 600;
        }
        .company-info p {
            font-size: 16px;
            color: #333;
            font-weight: 600;
            word-break: break-word;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            margin-top: 5px;
            border-left: 3px solid #721c24;
        }

        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        .warning-box strong { display: block; margin-bottom: 8px; }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }
        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #764ba2;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #e8eaf6;
            color: #667eea;
        }
        .btn-secondary:hover {
            background: #d1c4f9;
        }

        .info-section {
            background: #e8f4f8;
            border-left: 4px solid #0c5460;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 13px;
            color: #0c5460;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h1>🔓 Entrar como Loja</h1>
            <p>Acesso de suporte ou debugging</p>
        </div>

        <div class="card-body">
            <!-- Informações da Loja -->
            <div class="company-info">
                <h3>Loja Selecionada</h3>
                <p><?php echo htmlspecialchars($company['name'] ?? 'Unknown'); ?></p>
                <p style="font-size: 13px; color: #666; margin-top: 5px; font-weight: normal;">
                    Slug: <code style="background: white; padding: 2px 6px; border-radius: 3px;">
                        <?php echo htmlspecialchars($company['slug'] ?? 'N/A'); ?>
                    </code>
                </p>
            </div>

            <!-- Aviso de Segurança -->
            <div class="warning-box">
                <strong>⚠️ Aviso de Segurança</strong>
                Você está prestes a entrar como administrador desta loja. Esta ação será auditada e registrada completamente.
                Use apenas para suporte legítimo e debugging.
            </div>

            <!-- Formulário -->
            <form method="POST" action="<?php echo base_url("/superadmin/impersonate/{$company['id']}/start"); ?>">
                <div class="form-group">
                    <label for="role">Qual role você quer?</label>
                    <select name="role" id="role" required>
                        <option value="owner">Dono de Loja (Owner)</option>
                        <option value="staff">Funcionário (Staff)</option>
                    </select>
                    <?php if (!empty($errors['role'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['role']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="reason">Motivo da impersonação</label>
                    <textarea name="reason" id="reason" placeholder="Ex: Debugging de relatório de vendas, suporte ao cliente sobre pedido #123" required><?php echo htmlspecialchars($old['reason'] ?? ''); ?></textarea>
                    <?php if (!empty($errors['reason'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['reason']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <a href="<?php echo base_url("/superadmin/stores/{$company['id']}"); ?>" class="btn btn-secondary">
                        ← Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Entrar como Loja
                    </button>
                </div>
            </form>

            <!-- Informações -->
            <div class="info-section">
                <strong>ℹ️ O que acontecerá:</strong>
                <ul style="margin-left: 20px; margin-top: 8px;">
                    <li>Você será redirecionado para o dashboard da loja</li>
                    <li>Verá a plataforma como um <?php echo $old['role'] ?? 'dono'; ?> de loja</li>
                    <li>Todas as suas ações serão rastreadas em auditoria</li>
                    <li>Você poderá sair da impersonação a qualquer momento</li>
                </ul>
            </div>
        </div>
    </div>

    <?php 
    unset($_SESSION['superadmin_errors']);
    unset($_SESSION['superadmin_old_input']);
    ?>
</body>
</html>
