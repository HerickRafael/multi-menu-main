<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class SuperAdmin
{
    public static function findByEmail(string $email): ?array
    {
        $st = db()->prepare('SELECT * FROM super_admins WHERE email = ? LIMIT 1');
        $st->execute([strtolower(trim($email))]);

        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function findActiveById(int $id): ?array
    {
        $st = db()->prepare('SELECT * FROM super_admins WHERE id = ? AND active = 1 LIMIT 1');
        $st->execute([$id]);

        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function touchLastLogin(int $id): void
    {
        $st = db()->prepare('UPDATE super_admins SET last_login_at = NOW() WHERE id = ?');
        $st->execute([$id]);
    }
}
