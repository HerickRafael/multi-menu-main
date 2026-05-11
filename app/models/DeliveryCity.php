<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class DeliveryCity
{
    /**
     * Lista cidades da empresa.
     * Se $search for informado, filtra por nome (case-insensitive).
     */
    public static function allByCompany(int $companyId, ?string $search = null): array
    {
        $sql = 'SELECT * FROM delivery_cities WHERE company_id = ?';
        $params = [$companyId];

        if ($search !== null && $search !== '') {
            $sql .= ' AND LOWER(name) LIKE LOWER(?)';
            $params[] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY name';

        $st = db()->prepare($sql);
        $st->execute($params);

        return $st->fetchAll() ?: [];
    }

    /** Verifica existência por nome (case-insensitive) */
    public static function existsByName(int $companyId, string $name): bool
    {
        $st = db()->prepare('SELECT 1 FROM delivery_cities WHERE company_id = ? AND LOWER(name) = LOWER(?) LIMIT 1');
        $st->execute([$companyId, $name]);

        return (bool)$st->fetchColumn();
    }

    /** Verifica existência por nome, ignorando um ID específico (edição) */
    public static function existsByNameExcept(int $companyId, string $name, int $ignoreId): bool
    {
        $st = db()->prepare('SELECT 1 FROM delivery_cities WHERE company_id = ? AND LOWER(name) = LOWER(?) AND id <> ? LIMIT 1');
        $st->execute([$companyId, $name, $ignoreId]);

        return (bool)$st->fetchColumn();
    }

    /** Busca uma cidade específica da empresa */
    public static function findForCompany(int $id, int $companyId): ?array
    {
        $st = db()->prepare('SELECT * FROM delivery_cities WHERE id = ? AND company_id = ?');
        $st->execute([$id, $companyId]);
        $row = $st->fetch();

        return $row ?: null;
    }

    /** Cria cidade */
    public static function create(array $data): int
    {
        $st = db()->prepare('INSERT INTO delivery_cities (company_id, name) VALUES (?, ?)');
        $st->execute([(int)$data['company_id'], $data['name']]);

        return (int)db()->lastInsertId();
    }

    /** Atualiza cidade */
    public static function update(int $id, int $companyId, string $name): void
    {
        $st = db()->prepare('UPDATE delivery_cities SET name = ? WHERE id = ? AND company_id = ?');
        $st->execute([$name, $id, $companyId]);
    }

    /** Exclui cidade */
    public static function delete(int $id, int $companyId): void
    {
        $st = db()->prepare('DELETE FROM delivery_cities WHERE id = ? AND company_id = ?');
        $st->execute([$id, $companyId]);
    }
}
