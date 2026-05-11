<?php

declare(strict_types=1);

/**
 * LoyaltyProgram Model - Programa de Fidelidade Progressiva
 *
 * Gerencia programas de fidelidade por empresa e tracking de progresso do cliente.
 */
class LoyaltyProgram
{
    /**
     * Busca o programa ativo de uma empresa
     */
    public static function getActiveByCompany(PDO $db, int $companyId): ?array
    {
        $stmt = $db->prepare('
            SELECT * FROM loyalty_programs 
            WHERE company_id = ? AND is_active = 1 
            LIMIT 1
        ');
        $stmt->execute([$companyId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Busca o progresso de um cliente em um programa
     */
    public static function getProgress(PDO $db, int $customerId, int $programId): ?array
    {
        $stmt = $db->prepare('
            SELECT * FROM customer_loyalty_progress 
            WHERE customer_id = ? AND program_id = ?
        ');
        $stmt->execute([$customerId, $programId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Busca programa + progresso combinados para exibição
     */
    public static function getCustomerLoyalty(PDO $db, int $customerId, int $companyId): ?array
    {
        $program = self::getActiveByCompany($db, $companyId);
        if (!$program) {
            return null;
        }

        $progress = self::getProgress($db, $customerId, (int)$program['id']);
        $currentCount = $progress ? (int)$progress['current_count'] : 0;
        $requiredOrders = (int)$program['required_orders'];
        $remaining = max(0, $requiredOrders - $currentCount);

        return [
            'program'         => $program,
            'current_count'   => $currentCount,
            'required_orders' => $requiredOrders,
            'remaining'       => $remaining,
            'percentage'      => $requiredOrders > 0 ? min(100, round(($currentCount / $requiredOrders) * 100)) : 0,
            'times_completed' => $progress ? (int)$progress['times_completed'] : 0,
        ];
    }

    /**
     * Incrementa o progresso do cliente após pedido finalizado.
     * Se atingir o required_orders, gera cupom e reseta o contador.
     *
     * @return array|null Cupom gerado (se completou ciclo) ou null
     */
    public static function incrementProgress(PDO $db, int $customerId, int $companyId, string $customerPhone): ?array
    {
        $program = self::getActiveByCompany($db, $companyId);
        if (!$program) {
            return null;
        }

        $programId = (int)$program['id'];
        $required = (int)$program['required_orders'];

        // Upsert: criar progresso se não existe, ou incrementar
        $db->prepare('
            INSERT INTO customer_loyalty_progress (customer_id, program_id, current_count)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE current_count = current_count + 1, updated_at = NOW()
        ')->execute([$customerId, $programId]);

        // Buscar progresso atualizado
        $progress = self::getProgress($db, $customerId, $programId);
        if (!$progress) {
            return null;
        }

        $currentCount = (int)$progress['current_count'];

        // Verificar se completou o ciclo
        if ($currentCount >= $required) {
            return self::completeCycle($db, $program, $customerId, $companyId, $customerPhone, $programId);
        }

        return null;
    }

    /**
     * Completa um ciclo de fidelidade: gera cupom e reseta contador
     */
    private static function completeCycle(
        PDO $db,
        array $program,
        int $customerId,
        int $companyId,
        string $customerPhone,
        int $programId
    ): array {
        // Gerar código do cupom
        $couponCode = 'FIEL' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        // Determinar desconto baseado no tipo de recompensa
        // Nota: customer_loyalty_coupons suporta apenas discount_percentage
        $discountPercentage = 0;
        $description = $program['reward_description'];

        switch ($program['reward_type']) {
            case 'discount_percentage':
                $discountPercentage = min(100, (float)$program['reward_value']);
                break;
            case 'discount_fixed':
                // Desconto fixo: armazenar o valor como "percentual" (convenção)
                // O checkout deve aplicar como valor fixo quando reward_type é verificado no programa
                $discountPercentage = min(100, (float)$program['reward_value']);
                break;
            case 'free_delivery':
                // Frete grátis: usar 100% para sinalizar desconto total no frete
                $discountPercentage = 100;
                $description = 'Frete grátis — Programa de Fidelidade';
                break;
            case 'free_item':
                // Item grátis: usar 100% para sinalizar desconto total em 1 item
                $discountPercentage = 100;
                break;
        }

        // Inserir cupom na tabela existente customer_loyalty_coupons
        $db->prepare('
            INSERT INTO customer_loyalty_coupons 
            (company_id, customer_phone, coupon_code, discount_percentage, is_used, usage_limit)
            VALUES (?, ?, ?, ?, 0, 1)
        ')->execute([
            $companyId,
            $customerPhone,
            $couponCode,
            $discountPercentage,
        ]);

        // Resetar contador e incrementar times_completed
        $db->prepare('
            UPDATE customer_loyalty_progress 
            SET current_count = 0, times_completed = times_completed + 1, last_rewarded_at = NOW(), updated_at = NOW()
            WHERE customer_id = ? AND program_id = ?
        ')->execute([$customerId, $programId]);

        return [
            'coupon_code'   => $couponCode,
            'reward_type'   => $program['reward_type'],
            'reward_value'  => $program['reward_value'],
            'description'   => $description,
        ];
    }

    /**
     * CRUD: criar programa
     */
    public static function create(PDO $db, array $data): int
    {
        $stmt = $db->prepare('
            INSERT INTO loyalty_programs (company_id, name, required_orders, reward_type, reward_value, reward_product_id, reward_description, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['company_id'],
            $data['name'],
            $data['required_orders'],
            $data['reward_type'],
            $data['reward_value'] ?? null,
            $data['reward_product_id'] ?? null,
            $data['reward_description'],
            $data['is_active'] ?? 1,
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * CRUD: atualizar programa
     */
    public static function update(PDO $db, int $id, array $data): bool
    {
        $stmt = $db->prepare('
            UPDATE loyalty_programs 
            SET name = ?, required_orders = ?, reward_type = ?, reward_value = ?, 
                reward_product_id = ?, reward_description = ?, is_active = ?
            WHERE id = ?
        ');
        return $stmt->execute([
            $data['name'],
            $data['required_orders'],
            $data['reward_type'],
            $data['reward_value'] ?? null,
            $data['reward_product_id'] ?? null,
            $data['reward_description'],
            $data['is_active'] ?? 1,
            $id,
        ]);
    }

    /**
     * CRUD: buscar por ID
     */
    public static function findById(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare('SELECT * FROM loyalty_programs WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Dashboard: estatísticas de progresso por empresa
     */
    public static function getDashboardStats(PDO $db, int $programId): array
    {
        $stmt = $db->prepare('
            SELECT 
                COUNT(*) AS total_participants,
                SUM(CASE WHEN current_count > 0 THEN 1 ELSE 0 END) AS active_participants,
                AVG(current_count) AS avg_progress,
                SUM(times_completed) AS total_completions
            FROM customer_loyalty_progress
            WHERE program_id = ?
        ');
        $stmt->execute([$programId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_participants' => 0,
            'active_participants' => 0,
            'avg_progress' => 0,
            'total_completions' => 0,
        ];
    }
}
