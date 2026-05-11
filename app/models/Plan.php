<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class Plan
{
    public static function all(bool $onlyActive = false): array
    {
        $sql = 'SELECT * FROM plans';
        if ($onlyActive) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY price_monthly ASC, name ASC';

        $st = db()->query($sql);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(int $id): ?array
    {
        $st = db()->prepare('SELECT * FROM plans WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByCode(string $code): ?array
    {
        $st = db()->prepare('SELECT * FROM plans WHERE code = ? LIMIT 1');
        $st->execute([trim($code)]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function upsertByCode(array $data): int
    {
        $code = trim((string)($data['code'] ?? ''));
        if ($code === '') {
            throw new InvalidArgumentException('code e obrigatorio');
        }

        $name = trim((string)($data['name'] ?? $code));
        $description = isset($data['description']) ? (string)$data['description'] : null;
        $priceMonthly = (float)($data['price_monthly'] ?? 0);
        $priceYearly = (float)($data['price_yearly'] ?? 0);
        $currency = strtoupper(trim((string)($data['currency'] ?? 'BRL')));
        $limitsJson = $data['limits_json'] ?? null;
        $isActive = (int)($data['is_active'] ?? 1) === 1 ? 1 : 0;

        $limitsPayload = null;
        if ($limitsJson !== null) {
            $limitsPayload = is_string($limitsJson) ? $limitsJson : json_encode($limitsJson, JSON_UNESCAPED_UNICODE);
        }

        $sql = 'INSERT INTO plans (code, name, description, price_monthly, price_yearly, currency, limits_json, is_active)
                VALUES (:code, :name, :description, :price_monthly, :price_yearly, :currency, :limits_json, :is_active)
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    description = VALUES(description),
                    price_monthly = VALUES(price_monthly),
                    price_yearly = VALUES(price_yearly),
                    currency = VALUES(currency),
                    limits_json = VALUES(limits_json),
                    is_active = VALUES(is_active),
                    updated_at = NOW()';

        $st = db()->prepare($sql);
        $st->execute([
            'code' => $code,
            'name' => $name,
            'description' => $description,
            'price_monthly' => number_format($priceMonthly, 2, '.', ''),
            'price_yearly' => number_format($priceYearly, 2, '.', ''),
            'currency' => $currency,
            'limits_json' => $limitsPayload,
            'is_active' => $isActive,
        ]);

        $found = self::findByCode($code);
        return (int)($found['id'] ?? 0);
    }
}
