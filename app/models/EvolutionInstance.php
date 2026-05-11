<?php
// app/models/EvolutionInstance.php
// ATENÇÃO: O STATUS DE CONEXÃO deve ser obtido via API Evolution usando:
// GET /instance/connectionState/{instanceName}
// NUNCA usar status local do banco pois pode estar desatualizado!
require_once __DIR__ . '/../config/db.php';

class EvolutionInstance
{
    public static function allForCompany(int $companyId): array
    {
        $st = db()->prepare('SELECT * FROM evolution_instances WHERE company_id = ? ORDER BY created_at DESC');
        $st->execute([$companyId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(int $companyId, array $data): int
    {
        // Nota: status, qr_code e connected_at foram removidos da tabela
        // O status deve ser obtido diretamente da API Evolution
        $st = db()->prepare('INSERT INTO evolution_instances (company_id, label, number, instance_identifier, created_at) VALUES (?, ?, ?, ?, NOW())');
        $st->execute([
            $companyId,
            $data['label'] ?? null,
            $data['number'] ?? null,
            $data['instance_identifier'] ?? null,
        ]);

        return (int) db()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $st = db()->prepare('SELECT * FROM evolution_instances WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function update(int $id, array $data): void
    {
        $fields = [];
        $vals = [];

        // Apenas campos que ainda existem na tabela
        foreach (['label','number','instance_identifier'] as $k) {
            if (array_key_exists($k, $data)) {
                $fields[] = "$k = ?";
                $vals[] = $data[$k];
            }
        }

        if (!$fields) return;

        $vals[] = $id;
        $sql = 'UPDATE evolution_instances SET ' . implode(', ', $fields) . ' WHERE id = ?';
        db()->prepare($sql)->execute($vals);
    }

    public static function delete(int $id): void
    {
        db()->prepare('DELETE FROM evolution_instances WHERE id = ?')->execute([$id]);
    }
}
