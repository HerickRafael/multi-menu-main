<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
class User
{
    public static function findByEmail(string $email): ?array
    {
        $st = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);

        return $st->fetch() ?: null;
    }
}
