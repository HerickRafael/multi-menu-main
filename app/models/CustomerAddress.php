<?php

declare(strict_types=1);
// app/models/CustomerAddress.php

class CustomerAddress
{
    /**
     * Obtém conexão PDO de forma segura
     */
    private static function getDb(): PDO
    {
        static $connection = null;
        
        // Se já temos uma conexão estática, retorna ela
        if ($connection instanceof PDO) {
            return $connection;
        }
        
        // Tentar usar a função global db() se existir
        if (function_exists('db')) {
            try {
                $pdo = db();
                if ($pdo instanceof PDO) {
                    $connection = $pdo;
                    return $connection;
                }
            } catch (Throwable $e) {
                error_log("Erro ao usar função db(): " . $e->getMessage());
            }
        }
        
        // Fallback: criar conexão diretamente
        try {
            $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
            $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'multi_menu';
            $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
            $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            
            $connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            
            return $connection;
        } catch (PDOException $e) {
            error_log("ERRO CRÍTICO ao conectar ao banco em CustomerAddress: " . $e->getMessage());
            throw new RuntimeException("Não foi possível conectar ao banco de dados: " . $e->getMessage());
        }
    }

    /**
     * Busca todos os endereços de um cliente para uma empresa específica
     */
    public static function getByCustomer(int $customerId, int $companyId): array
    {
        $stmt = self::getDb()->prepare('
            SELECT * FROM customer_addresses 
            WHERE customer_id = ? AND company_id = ?
            ORDER BY is_default DESC, created_at DESC
        ');
        $stmt->execute([$customerId, $companyId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Busca o endereço padrão de um cliente
     */
    public static function getDefault(int $customerId, int $companyId): ?array
    {
        $stmt = self::getDb()->prepare('
            SELECT * FROM customer_addresses 
            WHERE customer_id = ? AND company_id = ? AND is_default = 1
            LIMIT 1
        ');
        $stmt->execute([$customerId, $companyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ?: null;
    }

    /**
     * Define um endereço como padrão e remove o padrão dos outros
     */
    public static function setAsDefault(int $addressId, int $customerId, int $companyId): bool
    {
        $db = self::getDb();
        
        try {
            $db->beginTransaction();
            
            // Remove o padrão de todos os endereços do cliente
            $stmt = $db->prepare('
                UPDATE customer_addresses 
                SET is_default = 0 
                WHERE customer_id = ? AND company_id = ?
            ');
            $stmt->execute([$customerId, $companyId]);
            
            // Define o novo endereço como padrão
            $stmt = $db->prepare('
                UPDATE customer_addresses 
                SET is_default = 1 
                WHERE id = ? AND customer_id = ?
            ');
            $stmt->execute([$addressId, $customerId]);
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    /**
     * Cria um novo endereço e define como padrão se for o primeiro
     */
    public static function createAddress(array $data): int|false
    {
        $customerId = (int)($data['customer_id'] ?? 0);
        $companyId = (int)($data['company_id'] ?? 0);

        if ($customerId <= 0 || $companyId <= 0) {
            return false;
        }

        $db = self::getDb();

        // Verifica se é o primeiro endereço do cliente
        $stmt = $db->prepare('
            SELECT COUNT(*) as total 
            FROM customer_addresses 
            WHERE customer_id = ? AND company_id = ?
        ');
        $stmt->execute([$customerId, $companyId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $existingCount = (int)($result['total'] ?? 0);

        // Se for o primeiro, define como padrão
        $isDefault = $existingCount === 0 ? 1 : 0;

        try {
            $stmt = $db->prepare('
                INSERT INTO customer_addresses (
                    customer_id, company_id, label, name, phone,
                    city_id, zone_id, city, neighborhood, street,
                    number, complement, reference, is_default
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            
            $stmt->execute([
                $customerId,
                $companyId,
                $data['label'] ?? null,
                $data['name'] ?? '',
                $data['phone'] ?? '',
                $data['city_id'] ?? null,
                $data['zone_id'] ?? null,
                $data['city'] ?? null,
                $data['neighborhood'] ?? null,
                $data['street'] ?? '',
                $data['number'] ?? '',
                $data['complement'] ?? null,
                $data['reference'] ?? null,
                $isDefault
            ]);

            return (int)$db->lastInsertId();
        } catch (Exception $e) {
            error_log("Erro ao criar endereço: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Atualiza um endereço
     */
    public static function updateAddress(int $addressId, array $data, int $customerId): bool
    {
        // Garante que só pode atualizar endereço do próprio cliente
        $stmt = self::getDb()->prepare('
            SELECT id FROM customer_addresses 
            WHERE id = ? AND customer_id = ?
            LIMIT 1
        ');
        $stmt->execute([$addressId, $customerId]);
        
        if (!$stmt->fetch()) {
            return false;
        }

        $fields = [];
        $values = [];

        $allowedFields = ['label', 'name', 'phone', 'city_id', 'zone_id', 'city', 
                         'neighborhood', 'street', 'number', 'complement', 'reference'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $addressId;
        $values[] = $customerId;

        try {
            $stmt = self::getDb()->prepare('
                UPDATE customer_addresses 
                SET ' . implode(', ', $fields) . '
                WHERE id = ? AND customer_id = ?
            ');
            $stmt->execute($values);
            
            return true;
        } catch (Exception $e) {
            error_log("Erro ao atualizar endereço: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deleta um endereço
     */
    public static function deleteAddress(int $addressId, int $customerId): bool
    {
        $db = self::getDb();
        
        // Busca o endereço
        $stmt = $db->prepare('
            SELECT id, customer_id, company_id, is_default 
            FROM customer_addresses 
            WHERE id = ? AND customer_id = ?
            LIMIT 1
        ');
        $stmt->execute([$addressId, $customerId]);
        $address = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$address) {
            return false;
        }

        try {
            $db->beginTransaction();
            
            // Deleta o endereço
            $stmt = $db->prepare('DELETE FROM customer_addresses WHERE id = ?');
            $stmt->execute([$addressId]);

            // Se estava marcado como padrão, marca outro como padrão
            if ((int)$address['is_default'] === 1) {
                $stmt = $db->prepare('
                    SELECT id FROM customer_addresses 
                    WHERE customer_id = ? AND company_id = ?
                    ORDER BY created_at DESC
                    LIMIT 1
                ');
                $stmt->execute([$customerId, (int)$address['company_id']]);
                $newDefault = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($newDefault) {
                    $stmt = $db->prepare('
                        UPDATE customer_addresses 
                        SET is_default = 1 
                        WHERE id = ?
                    ');
                    $stmt->execute([(int)$newDefault['id']]);
                }
            }

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Erro ao deletar endereço: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca um endereço específico com validação de cliente
     */
    public static function getAddress(int $addressId, int $customerId): ?array
    {
        $stmt = self::getDb()->prepare('
            SELECT * FROM customer_addresses 
            WHERE id = ? AND customer_id = ?
            LIMIT 1
        ');
        $stmt->execute([$addressId, $customerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ?: null;
    }

    /**
     * Formata o endereço completo para exibição
     */
    public static function formatAddress(array $address): string
    {
        $parts = [];
        
        $parts[] = $address['street'] . ', ' . $address['number'];
        
        if (!empty($address['complement'])) {
            $parts[] = $address['complement'];
        }
        
        $parts[] = $address['neighborhood'];
        
        if (!empty($address['city'])) {
            $parts[] = $address['city'];
        }

        return implode(' - ', $parts);
    }
}
