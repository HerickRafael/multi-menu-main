<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class DeliveryZone
{
    /**
     * Lista zonas da empresa com o nome da cidade.
     * Se $search for informado, filtra por cidade OU bairro (case-insensitive).
     */
    public static function allByCompany(int $companyId, ?string $search = null): array
    {
        $sql = 'SELECT dz.*, dc.name AS city_name
              FROM delivery_zones dz
              JOIN delivery_cities dc ON dc.id = dz.city_id
             WHERE dz.company_id = ?';
        $params = [$companyId];

        if ($search !== null && $search !== '') {
            $sql .= ' AND (LOWER(dc.name) LIKE LOWER(?) OR LOWER(dz.neighborhood) LIKE LOWER(?))';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' ORDER BY dc.name, dz.neighborhood';

        $st = db()->prepare($sql);
        $st->execute($params);

        return $st->fetchAll() ?: [];
    }

    /** Cria uma zona (bairro) vinculada à cidade */
    public static function create(array $data): int
    {
        $st = db()->prepare(
            'INSERT INTO delivery_zones (company_id, city_id, neighborhood, fee)
       VALUES (?, ?, ?, ?)'
        );
        $st->execute([
          (int)$data['company_id'],
          (int)$data['city_id'],
          $data['neighborhood'],
          $data['fee'],
        ]);

        return (int)db()->lastInsertId();
    }

    /** Busca uma zona específica da empresa (inclui city_name) */
    public static function findForCompany(int $id, int $companyId): ?array
    {
        $st = db()->prepare(
            'SELECT dz.*, dc.name AS city_name
         FROM delivery_zones dz
         JOIN delivery_cities dc ON dc.id = dz.city_id
        WHERE dz.id = ? AND dz.company_id = ?'
        );
        $st->execute([$id, $companyId]);
        $row = $st->fetch();

        return $row ?: null;
    }

    /** Verifica duplicidade de bairro na mesma cidade */
    public static function existsForCity(int $companyId, int $cityId, string $neighborhood): bool
    {
        $st = db()->prepare(
            'SELECT 1
         FROM delivery_zones
        WHERE company_id = ?
          AND city_id = ?
          AND LOWER(neighborhood) = LOWER(?)
        LIMIT 1'
        );
        $st->execute([$companyId, $cityId, $neighborhood]);

        return (bool)$st->fetchColumn();
    }

    /** Verifica duplicidade ignorando um ID (para edição) */
    public static function existsForCityExcept(int $companyId, int $cityId, string $neighborhood, int $ignoreId): bool
    {
        $st = db()->prepare(
            'SELECT 1
         FROM delivery_zones
        WHERE company_id = ?
          AND city_id = ?
          AND LOWER(neighborhood) = LOWER(?)
          AND id <> ?
        LIMIT 1'
        );
        $st->execute([$companyId, $cityId, $neighborhood, $ignoreId]);

        return (bool)$st->fetchColumn();
    }

    /** Atualiza uma zona */
    public static function update(int $id, int $companyId, array $data): void
    {
        $st = db()->prepare(
            'UPDATE delivery_zones
          SET city_id = ?, neighborhood = ?, fee = ?
        WHERE id = ? AND company_id = ?'
        );
        $st->execute([
          (int)$data['city_id'],
          $data['neighborhood'],
          $data['fee'],
          $id,
          $companyId,
        ]);
    }

    /** Ajusta todas as taxas por um delta (garante mínimo 0) */
    public static function adjustFees(int $companyId, float $delta): void
    {
        $st = db()->prepare('UPDATE delivery_zones SET fee = GREATEST(0, fee + ?) WHERE company_id = ?');
        $st->execute([$delta, $companyId]);
    }

    /** Exclui zona */
    public static function delete(int $id, int $companyId): void
    {
        $st = db()->prepare('DELETE FROM delivery_zones WHERE id = ? AND company_id = ?');
        $st->execute([$id, $companyId]);
    }
}
