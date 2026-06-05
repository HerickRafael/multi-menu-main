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

    public static function findById(int $id): ?array
    {
        $st = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $st->execute([$id]);

        return $st->fetch() ?: null;
    }

    /** True se o e-mail já pertence a OUTRO usuário. */
    public static function emailTakenByOther(string $email, int $exceptId): bool
    {
        $st = db()->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $st->execute([$email, $exceptId]);

        return (bool) $st->fetchColumn();
    }

    /** Atualiza nome/e-mail e, opcionalmente, o hash de senha. */
    public static function updateProfile(int $id, string $name, string $email, ?string $passwordHash = null): void
    {
        if ($passwordHash !== null) {
            $st = db()->prepare('UPDATE users SET name = ?, email = ?, password_hash = ? WHERE id = ?');
            $st->execute([$name, $email, $passwordHash, $id]);
        } else {
            $st = db()->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
            $st->execute([$name, $email, $id]);
        }
    }
}
