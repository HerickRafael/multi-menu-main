<?php
// scripts/apply_migration.php
// Uso: php apply_migration.php /path/to/migration.sql
require_once __DIR__ . '/../app/config/db.php';

if ($argc < 2) {
    echo "Uso: php apply_migration.php /path/to/migration.sql\n";
    exit(1);
}

$path = $argv[1];
if (!is_file($path)) {
    echo "Arquivo não encontrado: $path\n";
    exit(2);
}

$sql = file_get_contents($path);
if ($sql === false) {
    echo "Falha ao ler o arquivo SQL.\n";
    exit(3);
}

// Divide por ';' — simples, adequado para migrações sem delimitadores complexos
$parts = preg_split('/;\s*\n/', $sql);
if (!$parts) $parts = [$sql];

$pdo = null;
try {
    $pdo = db();
} catch (Throwable $e) {
    echo "ERROR: não foi possível conectar ao DB: " . $e->getMessage() . "\n";
    exit(4);
}

$allOk = true;
foreach ($parts as $i => $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '') continue;
    try {
        $pdo->exec($stmt);
        echo "OK: statement $i executado\n";
    } catch (Throwable $e) {
        echo "ERRO no statement $i: " . $e->getMessage() . "\n";
        $allOk = false;
        // Não aborta imediatamente; registra e continua para reportar
    }
}

if ($allOk) {
    echo "Migration aplicada com sucesso (sem transação devido a DDL).\n";
    exit(0);
} else {
    echo "Houveram erros ao executar alguns statements. Ver mensagens acima.\n";
    exit(5);
}
