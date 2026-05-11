<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/**
 * Model para Categorias de Despesas
 */
class ExpenseCategory
{
    /**
     * Categorias padrão do sistema
     */
    public static array $defaultCategories = [
        // Custos Fixos
        ['name' => 'Aluguel', 'type' => 'fixed', 'icon' => 'home', 'color' => '#EF4444'],
        ['name' => 'Energia Elétrica', 'type' => 'fixed', 'icon' => 'bolt', 'color' => '#F59E0B'],
        ['name' => 'Água', 'type' => 'fixed', 'icon' => 'droplet', 'color' => '#3B82F6'],
        ['name' => 'Internet', 'type' => 'fixed', 'icon' => 'wifi', 'color' => '#8B5CF6'],
        ['name' => 'Gás', 'type' => 'fixed', 'icon' => 'flame', 'color' => '#F97316'],
        ['name' => 'Salários', 'type' => 'fixed', 'icon' => 'users', 'color' => '#10B981'],
        ['name' => 'Contabilidade', 'type' => 'fixed', 'icon' => 'calculator', 'color' => '#6366F1'],
        ['name' => 'Assinaturas/Software', 'type' => 'fixed', 'icon' => 'credit-card', 'color' => '#EC4899'],
        ['name' => 'Seguro', 'type' => 'fixed', 'icon' => 'shield', 'color' => '#14B8A6'],
        ['name' => 'Outras Despesas Fixas', 'type' => 'fixed', 'icon' => 'folder', 'color' => '#6B7280'],
        
        // Custos Variáveis
        ['name' => 'Compra de Ingredientes', 'type' => 'variable', 'icon' => 'shopping-cart', 'color' => '#22C55E'],
        ['name' => 'Compra de Embalagens', 'type' => 'variable', 'icon' => 'package', 'color' => '#A855F7'],
        ['name' => 'Marketing/Publicidade', 'type' => 'variable', 'icon' => 'megaphone', 'color' => '#F43F5E'],
        ['name' => 'Manutenção/Reparos', 'type' => 'variable', 'icon' => 'wrench', 'color' => '#64748B'],
        ['name' => 'Material de Limpeza', 'type' => 'variable', 'icon' => 'sparkles', 'color' => '#06B6D4'],
        ['name' => 'Taxas Bancárias', 'type' => 'variable', 'icon' => 'building', 'color' => '#78716C'],
        ['name' => 'Imprevistos', 'type' => 'variable', 'icon' => 'alert-triangle', 'color' => '#DC2626'],
        ['name' => 'Outras Despesas Variáveis', 'type' => 'variable', 'icon' => 'folder', 'color' => '#9CA3AF'],
    ];

    /**
     * Lista todas as categorias de uma empresa
     */
    public static function listByCompany(int $companyId, ?string $type = null): array
    {
        $pdo = db();
        $sql = 'SELECT * FROM expense_categories WHERE company_id = ? AND active = 1';
        $params = [$companyId];

        if ($type !== null) {
            $sql .= ' AND type = ?';
            $params[] = $type;
        }

        $sql .= ' ORDER BY type, name';
        $st = $pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Busca categoria por ID
     */
    public static function find(int $id): ?array
    {
        $pdo = db();
        $st = $pdo->prepare('SELECT * FROM expense_categories WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Busca categoria por ID e empresa
     */
    public static function findForCompany(int $companyId, int $id): ?array
    {
        $pdo = db();
        $st = $pdo->prepare('SELECT * FROM expense_categories WHERE id = ? AND company_id = ?');
        $st->execute([$id, $companyId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Cria nova categoria
     */
    public static function create(array $data): int
    {
        $pdo = db();
        $st = $pdo->prepare('
            INSERT INTO expense_categories 
            (company_id, name, type, description, color, icon, is_system)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $st->execute([
            $data['company_id'],
            $data['name'],
            $data['type'] ?? 'fixed',
            $data['description'] ?? null,
            $data['color'] ?? '#3B82F6',
            $data['icon'] ?? 'currency-dollar',
            $data['is_system'] ?? 0,
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Atualiza categoria
     */
    public static function update(int $id, array $data): void
    {
        $pdo = db();
        $st = $pdo->prepare('
            UPDATE expense_categories 
            SET name = ?, type = ?, description = ?, color = ?, icon = ?
            WHERE id = ?
        ');
        $st->execute([
            $data['name'],
            $data['type'],
            $data['description'] ?? null,
            $data['color'] ?? '#3B82F6',
            $data['icon'] ?? 'currency-dollar',
            $id,
        ]);
    }

    /**
     * Desativa categoria (soft delete)
     */
    public static function delete(int $id): void
    {
        $pdo = db();
        $st = $pdo->prepare('UPDATE expense_categories SET active = 0 WHERE id = ?');
        $st->execute([$id]);
    }

    /**
     * Inicializa categorias padrão para uma empresa
     */
    public static function initializeDefaults(int $companyId): void
    {
        $pdo = db();
        
        // Verificar se já tem categorias
        $st = $pdo->prepare('SELECT COUNT(*) FROM expense_categories WHERE company_id = ?');
        $st->execute([$companyId]);
        
        if ((int)$st->fetchColumn() > 0) {
            return; // Já tem categorias, não inicializar
        }

        foreach (self::$defaultCategories as $cat) {
            self::create([
                'company_id' => $companyId,
                'name' => $cat['name'],
                'type' => $cat['type'],
                'icon' => $cat['icon'],
                'color' => $cat['color'],
                'is_system' => 1,
            ]);
        }
    }

    /**
     * Alias para initializeDefaults que retorna contagem
     */
    public static function seedDefaults(int $companyId): int
    {
        $pdo = db();
        
        // Verificar se já tem categorias
        $st = $pdo->prepare('SELECT COUNT(*) FROM expense_categories WHERE company_id = ?');
        $st->execute([$companyId]);
        
        if ((int)$st->fetchColumn() > 0) {
            return 0; // Já tem categorias
        }

        $count = 0;
        foreach (self::$defaultCategories as $cat) {
            self::create([
                'company_id' => $companyId,
                'name' => $cat['name'],
                'type' => $cat['type'],
                'icon' => $cat['icon'],
                'color' => $cat['color'],
                'is_system' => 1,
            ]);
            $count++;
        }
        
        return $count;
    }

    /**
     * Conta despesas por categoria
     */
    public static function countExpensesByCategory(int $companyId, ?string $month = null): array
    {
        $pdo = db();
        $sql = '
            SELECT 
                ec.id,
                ec.name,
                ec.type,
                ec.color,
                ec.icon,
                COUNT(e.id) as expense_count,
                COALESCE(SUM(e.amount), 0) as total_amount
            FROM expense_categories ec
            LEFT JOIN expenses e ON e.category_id = ec.id
        ';
        
        $params = [$companyId];
        
        if ($month !== null) {
            $sql .= ' AND e.reference_month = ?';
            $params[] = $month;
        }
        
        $sql .= '
            WHERE ec.company_id = ? AND ec.active = 1
            GROUP BY ec.id
            ORDER BY total_amount DESC
        ';
        
        // Reordenar params
        $params = $month !== null ? [$month, $companyId] : [$companyId];
        
        $st = $pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
