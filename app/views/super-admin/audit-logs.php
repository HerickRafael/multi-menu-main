<?php
// app/views/super-admin/audit-logs.php

$flash = $_SESSION['superadmin_flash'] ?? null;
$logs = $logs ?? [];
$pagination = $pagination ?? [];
$filters = $filters ?? [];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoria - Super Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 28px; font-weight: 600; }
        .header-actions a {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-block;
            margin-left: 10px;
        }
        .header-actions a:hover { background: rgba(255,255,255,0.3); }
        
        .content { padding: 30px; }

        .flash {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .flash.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .flash.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .filter-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
        }
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        .filter-actions button {
            padding: 8px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        .filter-actions button:hover { background: #764ba2; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #333;
            border-bottom: 2px solid #ddd;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        tbody tr:hover { background: #f8f9fa; }

        .action-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .action-badge.create { background: #d1ecf1; color: #0c5460; }
        .action-badge.update { background: #fff3cd; color: #856404; }
        .action-badge.delete { background: #f8d7da; color: #721c24; }
        .action-badge.suspend { background: #f8d7da; color: #721c24; }
        .action-badge.activate { background: #d4edda; color: #155724; }
        .action-badge.maintenance { background: #fff3cd; color: #856404; }
        .action-badge.impersonate_start { background: #d1ecf1; color: #0c5460; }
        .action-badge.impersonate_end { background: #d4edda; color: #155724; }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #667eea;
            font-size: 14px;
            transition: all 0.3s;
        }
        .pagination a:hover { background: #667eea; color: white; }
        .pagination .current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .pagination .disabled {
            color: #ccc;
            cursor: not-allowed;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>📋 Auditoria de Ações</h1>
                <p style="font-size: 14px; opacity: 0.9; margin-top: 5px;">Histórico completo de todas as ações do super admin</p>
            </div>
            <div class="header-actions">
                <a href="<?php echo base_url('/superadmin'); ?>">← Voltar ao Dashboard</a>
            </div>
        </div>

        <div class="content">
            <?php if ($flash): ?>
                <div class="flash <?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
                <?php unset($_SESSION['superadmin_flash']); ?>
            <?php endif; ?>

            <!-- Filtros -->
            <form method="GET" class="filters">
                <div class="filter-group">
                    <label>Ação</label>
                    <select name="action">
                        <option value="">Todas</option>
                        <option value="create" <?php echo ($filters['action'] ?? null) === 'create' ? 'selected' : ''; ?>>Criar</option>
                        <option value="update" <?php echo ($filters['action'] ?? null) === 'update' ? 'selected' : ''; ?>>Atualizar</option>
                        <option value="delete" <?php echo ($filters['action'] ?? null) === 'delete' ? 'selected' : ''; ?>>Deletar</option>
                        <option value="suspend" <?php echo ($filters['action'] ?? null) === 'suspend' ? 'selected' : ''; ?>>Suspender</option>
                        <option value="activate" <?php echo ($filters['action'] ?? null) === 'activate' ? 'selected' : ''; ?>>Ativar</option>
                        <option value="maintenance" <?php echo ($filters['action'] ?? null) === 'maintenance' ? 'selected' : ''; ?>>Manutenção</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Módulo</label>
                    <select name="module">
                        <option value="">Todos</option>
                        <option value="stores" <?php echo ($filters['module'] ?? null) === 'stores' ? 'selected' : ''; ?>>Lojas</option>
                        <option value="users" <?php echo ($filters['module'] ?? null) === 'users' ? 'selected' : ''; ?>>Usuários</option>
                        <option value="orders" <?php echo ($filters['module'] ?? null) === 'orders' ? 'selected' : ''; ?>>Pedidos</option>
                        <option value="impersonations" <?php echo ($filters['module'] ?? null) === 'impersonations' ? 'selected' : ''; ?>>Impersonações</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Data Inicial</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>">
                </div>

                <div class="filter-group">
                    <label>Data Final</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>">
                </div>

                <div class="filter-actions">
                    <button type="submit">Filtrar</button>
                    <a href="<?php echo base_url('/superadmin/audit-logs'); ?>" style="padding: 8px 20px; background: #999; color: white; border-radius: 6px; text-decoration: none; display: inline-block;">Limpar</a>
                </div>
            </form>

            <!-- Tabela de Logs -->
            <?php if (empty($logs)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 11H5m14 0a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2m14 0V9a2 2 0 0 0-2-2M5 11V9a2 2 0 0 1 2-2m0 0V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2M7 7h10"/>
                    </svg>
                    <h3 style="color: #333; margin-bottom: 10px;">Nenhum log encontrado</h3>
                    <p>Não há registros de auditoria para os filtros selecionados.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Admin</th>
                            <th>Ação</th>
                            <th>Módulo</th>
                            <th>Entidade</th>
                            <th>Loja</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td style="font-size: 13px; color: #999;">
                                    <?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?>
                                </td>
                                <td style="font-weight: 600;">
                                    #<?php echo $log['super_admin_id']; ?>
                                </td>
                                <td>
                                    <span class="action-badge <?php echo htmlspecialchars($log['action']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($log['action']))); ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="background: #e8eaf6; color: #3f51b5; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                        <?php echo ucfirst(htmlspecialchars($log['module'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo ucfirst(htmlspecialchars($log['entity_type'])); ?> #<?php echo $log['entity_id'] ?? 'N/A'; ?>
                                </td>
                                <td>
                                    #<?php echo $log['company_id'] ?? 'N/A'; ?>
                                </td>
                                <td style="color: #666;">
                                    <?php echo htmlspecialchars($log['description']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Paginação -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="pagination">
                        <?php if ($pagination['has_prev']): ?>
                            <a href="<?php echo base_url("/superadmin/audit-logs?page=1"); ?>">Primeira</a>
                            <a href="<?php echo base_url("/superadmin/audit-logs?page=" . ($pagination['page'] - 1)); ?>">← Anterior</a>
                        <?php else: ?>
                            <span class="disabled">Primeira</span>
                            <span class="disabled">← Anterior</span>
                        <?php endif; ?>

                        <span>Página <?php echo $pagination['page']; ?> de <?php echo $pagination['total_pages']; ?></span>

                        <?php if ($pagination['has_next']): ?>
                            <a href="<?php echo base_url("/superadmin/audit-logs?page=" . ($pagination['page'] + 1)); ?>">Próxima →</a>
                            <a href="<?php echo base_url("/superadmin/audit-logs?page=" . $pagination['total_pages']); ?>">Última</a>
                        <?php else: ?>
                            <span class="disabled">Próxima →</span>
                            <span class="disabled">Última</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
