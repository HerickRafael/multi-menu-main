<?php
// app/models/ProductNativeIngredient.php
// Modelo para gerenciar ingredientes nativos (composição) do produto

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class ProductNativeIngredient
{
    /**
     * Lista ingredientes nativos de um produto
     */
    public static function listByProduct(int $productId): array
    {
        $pdo = db();
        
        $sql = "SELECT pni.*, i.name as ingredient_name, i.image_path as ingredient_image
                FROM product_native_ingredients pni
                JOIN ingredients i ON i.id = pni.ingredient_id
                WHERE pni.product_id = ?
                ORDER BY pni.sort_order, i.name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$productId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Sincroniza ingredientes nativos de um produto
     * 
     * @param int $productId ID do produto
     * @param array $ingredients Array de ingredientes [['ingredient_id' => X, 'quantity' => Y, 'can_remove' => Z], ...]
     */
    public static function sync(int $productId, array $ingredients): bool
    {
        $pdo = db();
        
        try {
            $pdo->beginTransaction();
            
            // Remover todos os ingredientes atuais
            $stmt = $pdo->prepare('DELETE FROM product_native_ingredients WHERE product_id = ?');
            $stmt->execute([$productId]);
            
            // Inserir os novos ingredientes
            if (!empty($ingredients)) {
                $sql = "INSERT INTO product_native_ingredients 
                        (product_id, ingredient_id, quantity, can_remove, sort_order) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                
                $sortOrder = 0;
                foreach ($ingredients as $ing) {
                    $ingredientId = (int)($ing['ingredient_id'] ?? 0);
                    if ($ingredientId <= 0) continue;
                    
                    $quantity = isset($ing['quantity']) ? (float)$ing['quantity'] : 1.0;
                    $canRemove = isset($ing['can_remove']) ? (int)$ing['can_remove'] : 1;
                    
                    $stmt->execute([
                        $productId,
                        $ingredientId,
                        $quantity,
                        $canRemove,
                        $sortOrder++
                    ]);
                }
            }
            
            $pdo->commit();
            return true;
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log("Erro ao sincronizar ingredientes nativos: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Adiciona um ingrediente nativo ao produto
     */
    public static function add(int $productId, int $ingredientId, float $quantity = 1.0, ?string $notes = null): bool
    {
        $pdo = db();
        
        try {
            // Verificar se já existe
            $check = $pdo->prepare('SELECT id FROM product_native_ingredients WHERE product_id = ? AND ingredient_id = ?');
            $check->execute([$productId, $ingredientId]);
            
            if ($check->fetch()) {
                // Atualizar
                $stmt = $pdo->prepare('UPDATE product_native_ingredients SET quantity = ?, notes = ? WHERE product_id = ? AND ingredient_id = ?');
                return $stmt->execute([$quantity, $notes, $productId, $ingredientId]);
            }
            
            // Inserir
            $stmt = $pdo->prepare('INSERT INTO product_native_ingredients (product_id, ingredient_id, quantity, notes) VALUES (?, ?, ?, ?)');
            return $stmt->execute([$productId, $ingredientId, $quantity, $notes]);
            
        } catch (\Exception $e) {
            error_log("Erro ao adicionar ingrediente nativo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove um ingrediente nativo do produto
     */
    public static function remove(int $productId, int $ingredientId): bool
    {
        $pdo = db();
        
        $stmt = $pdo->prepare('DELETE FROM product_native_ingredients WHERE product_id = ? AND ingredient_id = ?');
        return $stmt->execute([$productId, $ingredientId]);
    }
    
    /**
     * Cria a tabela se não existir
     */
    public static function createTableIfNotExists(): void
    {
        $pdo = db();
        
        $sql = "CREATE TABLE IF NOT EXISTS `product_native_ingredients` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT UNSIGNED NOT NULL,
            `ingredient_id` INT UNSIGNED NOT NULL,
            `quantity` DECIMAL(10,2) DEFAULT 1.00,
            `can_remove` TINYINT(1) DEFAULT 1 COMMENT 'Cliente pode remover este ingrediente',
            `sort_order` INT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            UNIQUE KEY `uk_product_ingredient` (`product_id`, `ingredient_id`),
            INDEX `idx_product_id` (`product_id`),
            INDEX `idx_ingredient_id` (`ingredient_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        
        // Adicionar coluna can_remove se não existir (para tabelas existentes)
        try {
            $pdo->exec("ALTER TABLE `product_native_ingredients` ADD COLUMN IF NOT EXISTS `can_remove` TINYINT(1) DEFAULT 1 AFTER `quantity`");
        } catch (\Exception $e) {
            // Ignorar erro se a coluna já existir
        }
    }
}
