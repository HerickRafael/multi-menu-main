<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/**
 * Model para Configurações Financeiras da Empresa
 */
class FinancialSettings
{
    /**
     * Busca configurações de uma empresa
     */
    public static function findByCompany(int $companyId): ?array
    {
        $pdo = db();
        $st = $pdo->prepare('SELECT * FROM financial_settings WHERE company_id = ?');
        $st->execute([$companyId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Alias para findOrCreate (compatibilidade)
     */
    public static function get(int $companyId): array
    {
        return self::findOrCreate($companyId);
    }

    /**
     * Busca ou cria configurações padrão
     */
    public static function findOrCreate(int $companyId): array
    {
        $existing = self::findByCompany($companyId);
        
        if ($existing) {
            return $existing;
        }

        // Criar configurações padrão
        $pdo = db();
        $st = $pdo->prepare('
            INSERT INTO financial_settings (company_id, target_profit_margin)
            VALUES (?, 30.00)
        ');
        $st->execute([$companyId]);

        return self::findByCompany($companyId) ?? [
            'company_id' => $companyId,
            'default_tax_percentage' => 0,
            'ifood_fee_percentage' => 0,
            'rappi_fee_percentage' => 0,
            'ubereats_fee_percentage' => 0,
            'own_delivery_fee_percentage' => 0,
            'hourly_labor_cost' => 0,
            'target_profit_margin' => 30,
            'monthly_revenue_goal' => 0,
            'monthly_profit_goal' => 0,
        ];
    }

    /**
     * Salva configurações
     */
    public static function save(int $companyId, array $data): void
    {
        $pdo = db();
        
        $existing = self::findByCompany($companyId);

        if ($existing) {
            $st = $pdo->prepare('
                UPDATE financial_settings SET
                    default_tax_percentage = ?,
                    tax_regime = ?,
                    ifood_fee_percentage = ?,
                    rappi_fee_percentage = ?,
                    ubereats_fee_percentage = ?,
                    own_delivery_fee_percentage = ?,
                    hourly_labor_cost = ?,
                    target_profit_margin = ?,
                    monthly_revenue_goal = ?,
                    monthly_profit_goal = ?
                WHERE company_id = ?
            ');
            $st->execute([
                $data['default_tax_percentage'] ?? 0,
                $data['tax_regime'] ?? null,
                $data['ifood_fee_percentage'] ?? 0,
                $data['rappi_fee_percentage'] ?? 0,
                $data['ubereats_fee_percentage'] ?? 0,
                $data['own_delivery_fee_percentage'] ?? 0,
                $data['hourly_labor_cost'] ?? 0,
                $data['target_profit_margin'] ?? 30,
                $data['monthly_revenue_goal'] ?? 0,
                $data['monthly_profit_goal'] ?? 0,
                $companyId,
            ]);
        } else {
            $st = $pdo->prepare('
                INSERT INTO financial_settings 
                (company_id, default_tax_percentage, tax_regime, ifood_fee_percentage, rappi_fee_percentage,
                 ubereats_fee_percentage, own_delivery_fee_percentage, hourly_labor_cost, target_profit_margin,
                 monthly_revenue_goal, monthly_profit_goal)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $st->execute([
                $companyId,
                $data['default_tax_percentage'] ?? 0,
                $data['tax_regime'] ?? null,
                $data['ifood_fee_percentage'] ?? 0,
                $data['rappi_fee_percentage'] ?? 0,
                $data['ubereats_fee_percentage'] ?? 0,
                $data['own_delivery_fee_percentage'] ?? 0,
                $data['hourly_labor_cost'] ?? 0,
                $data['target_profit_margin'] ?? 30,
                $data['monthly_revenue_goal'] ?? 0,
                $data['monthly_profit_goal'] ?? 0,
            ]);
        }
    }
}
