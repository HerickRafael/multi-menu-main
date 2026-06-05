<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/SmartCache.php';

class Category
{
    public static function listByCompany(int $companyId): array
    {
        $key = "categories:company:{$companyId}:active";
        
        return SmartCache::remember($key, function() use ($companyId) {
            $st = db()->prepare('SELECT * FROM categories WHERE company_id = ? AND active = 1 ORDER BY sort_order, name');
            $st->execute([$companyId]);
            return $st->fetchAll();
        }, 300); // 5 minutos
    }
    public static function allByCompany(int $companyId): array
    {
        $key = "categories:company:{$companyId}:all";
        
        return SmartCache::remember($key, function() use ($companyId) {
            $st = db()->prepare('SELECT * FROM categories WHERE company_id = ? ORDER BY sort_order, name');
            $st->execute([$companyId]);
            return $st->fetchAll();
        }, 300);
    }
    
    public static function find(int $id): ?array
    {
        $key = "categories:id:{$id}";
        
        return SmartCache::remember($key, function() use ($id) {
            $st = db()->prepare('SELECT * FROM categories WHERE id = ?');
            $st->execute([$id]);
            return $st->fetch() ?: null;
        }, 300);
    }
    
    public static function create(array $data): int
    {
        // Capturar a conexão localmente: lastInsertId() precisa ser lido no
        // mesmo handle do INSERT (chamar db() de novo pode retornar 0).
        $pdo = db();
        $st = $pdo->prepare('INSERT INTO categories (company_id, name, sort_order, active) VALUES (?,?,?,?)');
        $st->execute([$data['company_id'], $data['name'], (int)($data['sort_order'] ?? 0), (int)($data['active'] ?? 1)]);

        $id = (int)$pdo->lastInsertId();
        
        // Invalidar cache da empresa
        SmartCache::forgetByPattern("categories:company:{$data['company_id']}:*");
        
        return $id;
    }
    
    public static function update(int $id, array $data): void
    {
        $st = db()->prepare('UPDATE categories SET name=?, sort_order=?, active=? WHERE id=?');
        $st->execute([$data['name'], (int)($data['sort_order'] ?? 0), (int)($data['active'] ?? 1), $id]);
        
        // Invalidar cache
        SmartCache::forget("categories:id:{$id}");
        
        // Se tiver company_id, invalidar cache da empresa
        if (isset($data['company_id'])) {
            SmartCache::forgetByPattern("categories:company:{$data['company_id']}:*");
        }
    }
    
    public static function delete(int $id): void
    {
        // Buscar category para pegar company_id
        $category = self::find($id);
        
        $st = db()->prepare('DELETE FROM categories WHERE id=?');
        $st->execute([$id]);
        
        // Invalidar cache
        SmartCache::forget("categories:id:{$id}");
        
        if ($category && isset($category['company_id'])) {
            SmartCache::forgetByPattern("categories:company:{$category['company_id']}:*");
        }
    }
}
