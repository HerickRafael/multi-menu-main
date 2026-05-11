#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Cria um registro na tabela super_admins.
 *
 * Uso: php scripts/create_super_admin.php --name="Nome" --email="a@b.com" --password="senha"
 */

$root = dirname(__DIR__);

require_once $root . '/vendor/autoload.php';

if (file_exists($root . '/.env')) {
    $lines = file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            [$name, $value] = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
            putenv(trim($name) . '=' . trim($value));
        }
    }
}

require_once $root . '/app/config/db.php';

function arg(string $long): ?string
{
    global $argv;
    $prefix = '--' . $long . '=';

    foreach ($argv as $a) {
        if (str_starts_with($a, $prefix)) {
            return substr($a, strlen($prefix));
        }
    }

    return null;
}

$name = arg('name');
$email = arg('email');
$password = arg('password');

if ($name === null || $email === null || $password === null) {
    fwrite(STDERR, "Uso: php scripts/create_super_admin.php --name=\"Nome\" --email=\"email@exemplo.com\" --password=\"senha\"\n");
    exit(1);
}

$name = trim($name);
$email = strtolower(trim($email));

if ($name === '' || strlen($name) > 150) {
    fwrite(STDERR, "Nome inválido.\n");
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Email inválido.\n");
    exit(1);
}

if (strlen($password) < 8) {
    fwrite(STDERR, "Senha deve ter no mínimo 8 caracteres.\n");
    exit(1);
}

$pdo = db();

$chk = $pdo->prepare('SELECT id FROM super_admins WHERE email = ? LIMIT 1');
$chk->execute([$email]);
if ($chk->fetchColumn()) {
    fwrite(STDERR, "Erro: já existe super admin com este email.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_BCRYPT);

$ins = $pdo->prepare(
    'INSERT INTO super_admins (name, email, password_hash, active, created_at) VALUES (?, ?, ?, 1, NOW())'
);
$ins->execute([$name, $email, $hash]);

$id = (int) $pdo->lastInsertId();

echo "Super admin criado com sucesso.\n";
echo "ID: {$id}\n";
echo "Email: {$email}\n";
