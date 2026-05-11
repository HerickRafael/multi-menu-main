<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/**
 * Model para Despesas (Fixas e Variáveis)
 */
class Expense
{
    /**
     * Lista despesas de uma empresa
     */
    public static function listByCompany(
        int $companyId,
        ?string $month = null,
        ?string $type = null,
        ?int $categoryId = null,
        int $limit = 100,
        int $offset = 0
    ): array {
        $pdo = db();
        $sql = '
            SELECT e.*, ec.name as category_name, ec.type as category_type, ec.color as category_color, ec.icon as category_icon
            FROM expenses e
            LEFT JOIN expense_categories ec ON ec.id = e.category_id
            WHERE e.company_id = ?
        ';
        $params = [$companyId];

        if ($month !== null) {
            $sql .= ' AND e.reference_month = ?';
            $params[] = $month;
        }

        if ($type !== null) {
            $sql .= ' AND ec.type = ?';
            $params[] = $type;
        }

        if ($categoryId !== null) {
            $sql .= ' AND e.category_id = ?';
            $params[] = $categoryId;
        }

        $sql .= ' ORDER BY e.expense_date DESC, e.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $st = $pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Busca despesa por ID
     */
    public static function find(int $id): ?array
    {
        $pdo = db();
        $st = $pdo->prepare('
            SELECT e.*, ec.name as category_name, ec.type as category_type
            FROM expenses e
            LEFT JOIN expense_categories ec ON ec.id = e.category_id
            WHERE e.id = ?
        ');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Busca despesa por ID e empresa
     */
    public static function findForCompany(int $companyId, int $id): ?array
    {
        $pdo = db();
        $st = $pdo->prepare('
            SELECT e.*, ec.name as category_name, ec.type as category_type
            FROM expenses e
            LEFT JOIN expense_categories ec ON ec.id = e.category_id
            WHERE e.id = ? AND e.company_id = ?
        ');
        $st->execute([$id, $companyId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Cria nova despesa
     */
    public static function create(array $data): int
    {
        $pdo = db();
        
        // Calcular reference_month a partir da expense_date
        $expenseDate = $data['expense_date'];
        $referenceMonth = date('Y-m', strtotime($expenseDate));

        $st = $pdo->prepare('
            INSERT INTO expenses 
            (company_id, category_id, description, amount, expense_date, reference_month, 
             is_recurring, recurrence_type, payment_method, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $st->execute([
            $data['company_id'],
            $data['category_id'] ?? null,
            $data['description'],
            $data['amount'],
            $expenseDate,
            $referenceMonth,
            $data['is_recurring'] ?? 0,
            $data['recurrence_type'] ?? null,
            $data['payment_method'] ?? null,
            $data['notes'] ?? null,
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Atualiza despesa
     */
    public static function update(int $id, array $data): void
    {
        $pdo = db();
        
        $expenseDate = $data['expense_date'];
        $referenceMonth = date('Y-m', strtotime($expenseDate));

        $st = $pdo->prepare('
            UPDATE expenses 
            SET category_id = ?, description = ?, amount = ?, expense_date = ?, 
                reference_month = ?, is_recurring = ?, recurrence_type = ?, 
                payment_method = ?, notes = ?
            WHERE id = ?
        ');
        $st->execute([
            $data['category_id'] ?? null,
            $data['description'],
            $data['amount'],
            $expenseDate,
            $referenceMonth,
            $data['is_recurring'] ?? 0,
            $data['recurrence_type'] ?? null,
            $data['payment_method'] ?? null,
            $data['notes'] ?? null,
            $id,
        ]);
    }

    /**
     * Deleta despesa
     */
    public static function delete(int $id): void
    {
        $pdo = db();
        $st = $pdo->prepare('DELETE FROM expenses WHERE id = ?');
        $st->execute([$id]);
    }

    /**
     * Total de despesas por mês
     */
    public static function getTotalByMonth(int $companyId, string $month): array
    {
        $pdo = db();
        $st = $pdo->prepare('
            SELECT 
                COALESCE(SUM(CASE WHEN ec.type = "fixed" THEN e.amount ELSE 0 END), 0) as fixed_total,
                COALESCE(SUM(CASE WHEN ec.type = "variable" THEN e.amount ELSE 0 END), 0) as variable_total,
                COALESCE(SUM(e.amount), 0) as total,
                COUNT(e.id) as count
            FROM expenses e
            LEFT JOIN expense_categories ec ON ec.id = e.category_id
            WHERE e.company_id = ? AND e.reference_month = ?
        ');
        $st->execute([$companyId, $month]);

        return $st->fetch(PDO::FETCH_ASSOC) ?: [
            'fixed_total' => 0,
            'variable_total' => 0,
            'total' => 0,
            'count' => 0,
        ];
    }

    /**
     * Alias para getTotalByMonth (compatibilidade com controllers)
     */
    public static function getMonthlySummary(int $companyId, string $month): array
    {
        return self::getTotalByMonth($companyId, $month);
    }

    /**
     * Totais mensais dos últimos N meses
     */
    public static function getMonthlyTotals(int $companyId, int $months = 12): array
    {
        $pdo = db();
        $st = $pdo->prepare('
            SELECT 
                e.reference_month,
                COALESCE(SUM(CASE WHEN ec.type = "fixed" THEN e.amount ELSE 0 END), 0) as fixed_total,
                COALESCE(SUM(CASE WHEN ec.type = "variable" THEN e.amount ELSE 0 END), 0) as variable_total,
                COALESCE(SUM(e.amount), 0) as total
            FROM expenses e
            LEFT JOIN expense_categories ec ON ec.id = e.category_id
            WHERE e.company_id = ? 
              AND e.reference_month >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL ? MONTH), "%Y-%m")
            GROUP BY e.reference_month
            ORDER BY e.reference_month ASC
        ');
        $st->execute([$companyId, $months]);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Despesas agrupadas por categoria em um mês
     */
    public static function getByCategory(int $companyId, string $month): array
    {
        $pdo = db();
        $st = $pdo->prepare('
            SELECT 
                ec.id as category_id,
                ec.name as category_name,
                ec.type as category_type,
                ec.color as category_color,
                ec.icon as category_icon,
                COALESCE(SUM(e.amount), 0) as total,
                COUNT(e.id) as count
            FROM expense_categories ec
            LEFT JOIN expenses e ON e.category_id = ec.id AND e.reference_month = ?
            WHERE ec.company_id = ? AND ec.active = 1
            GROUP BY ec.id
            ORDER BY total DESC
        ');
        $st->execute([$month, $companyId]);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Conta despesas de uma empresa
     */
    public static function countByCompany(int $companyId, ?string $month = null): int
    {
        $pdo = db();
        $sql = 'SELECT COUNT(*) FROM expenses WHERE company_id = ?';
        $params = [$companyId];

        if ($month !== null) {
            $sql .= ' AND reference_month = ?';
            $params[] = $month;
        }

        $st = $pdo->prepare($sql);
        $st->execute($params);

        return (int)$st->fetchColumn();
    }

    /**
     * Gera despesas recorrentes para um mês
     */
    public static function generateRecurring(int $companyId, string $targetMonth): int
    {
        $pdo = db();
        
        // Buscar despesas recorrentes do mês anterior
        $previousMonth = date('Y-m', strtotime($targetMonth . '-01 -1 month'));
        
        $st = $pdo->prepare('
            SELECT * FROM expenses 
            WHERE company_id = ? 
              AND reference_month = ? 
              AND is_recurring = 1
        ');
        $st->execute([$companyId, $previousMonth]);
        $recurring = $st->fetchAll(PDO::FETCH_ASSOC);

        $created = 0;
        foreach ($recurring as $expense) {
            // Verificar se já existe no mês alvo
            $checkSt = $pdo->prepare('
                SELECT id FROM expenses 
                WHERE company_id = ? 
                  AND category_id = ? 
                  AND description = ? 
                  AND reference_month = ?
            ');
            $checkSt->execute([
                $companyId,
                $expense['category_id'],
                $expense['description'],
                $targetMonth,
            ]);

            if ($checkSt->fetch()) {
                continue; // Já existe
            }

            // Criar nova despesa
            $newDate = $targetMonth . '-' . date('d', strtotime($expense['expense_date']));
            // Ajustar se o dia não existe no mês (ex: 31 em fevereiro)
            if (!checkdate((int)date('m', strtotime($newDate)), (int)date('d', strtotime($expense['expense_date'])), (int)date('Y', strtotime($newDate)))) {
                $newDate = date('Y-m-t', strtotime($targetMonth . '-01')); // Último dia do mês
            }

            self::create([
                'company_id' => $companyId,
                'category_id' => $expense['category_id'],
                'description' => $expense['description'],
                'amount' => $expense['amount'],
                'expense_date' => $newDate,
                'is_recurring' => 1,
                'recurrence_type' => $expense['recurrence_type'],
                'payment_method' => $expense['payment_method'],
                'notes' => $expense['notes'],
            ]);
            $created++;
        }

        return $created;
    }
}
