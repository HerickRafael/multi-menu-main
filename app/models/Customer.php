<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Helpers.php';

class Customer
{
    /**
     * Retorna um PDO. Ajuste para usar sua função global de conexão (ex.: db()) se já existir.
     * Você pode definir as credenciais via variáveis de ambiente ou constantes.
     *
     * Env/consts suportados:
     *  - DB_DSN  (ex: "mysql:host=localhost;dbname=multimenu;charset=utf8mb4")
     *  - DB_USER
     *  - DB_PASS
     */
    protected static function pdo(): PDO
    {
        // Se você já tem uma função global db() que retorna PDO, use-a:
        if (function_exists('db')) {
            $pdo = db();

            if ($pdo instanceof PDO) {
                return $pdo;
            }
        }

        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $dsn  = getenv('DB_DSN') ?: (defined('DB_DSN') ? DB_DSN : 'mysql:host=localhost;dbname=multimenu;charset=utf8mb4');
        $user = getenv('DB_USER') ?: (defined('DB_USER') ? DB_USER : 'root');
        $pass = getenv('DB_PASS') ?: (defined('DB_PASS') ? DB_PASS : '');

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        return $pdo;
    }

    /** Empresas */
    public static function findCompanyBySlug(string $slug): ?array
    {
        $sql = 'SELECT * FROM companies WHERE slug = :slug LIMIT 1';
        $st  = self::pdo()->prepare($sql);
        $st->execute([':slug' => $slug]);
        $row = $st->fetch();

        return $row ?: null;
    }

    /** Clientes */
    public static function findByCompanyAndE164(int $companyId, string $e164): ?array
    {
        $sql = 'SELECT * FROM customers WHERE company_id = :cid AND whatsapp_e164 = :e LIMIT 1';
        $st  = self::pdo()->prepare($sql);
        $st->execute([':cid' => $companyId, ':e' => $e164]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $sql = 'SELECT * FROM customers WHERE id = :id LIMIT 1';
        $st  = self::pdo()->prepare($sql);
        $st->execute([':id' => $id]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public static function insert(array $data): int
    {
        $sql = 'INSERT INTO customers (
                    company_id,
                    name,
                    whatsapp,
                    whatsapp_e164,
                    lgpd_consent_at,
                    lgpd_consent_ip,
                    created_at,
                    updated_at,
                    last_login_at
                )
                VALUES (
                    :company_id,
                    :name,
                    :whatsapp,
                    :e164,
                    :lgpd_consent_at,
                    :lgpd_consent_ip,
                    :created_at,
                    :updated_at,
                    :last_login_at
                )';
        $pdo = self::pdo();
        $st  = $pdo->prepare($sql);
        $st->execute([
            ':company_id'   => (int)$data['company_id'],
            ':name'         => $data['name'],
            ':whatsapp'     => $data['whatsapp'],
            ':e164'         => $data['whatsapp_e164'],
            ':lgpd_consent_at' => $data['lgpd_consent_at'] ?? null,
            ':lgpd_consent_ip' => $data['lgpd_consent_ip'] ?? null,
            ':created_at'   => $data['created_at'],
            ':updated_at'   => $data['updated_at'],
            ':last_login_at' => $data['last_login_at'],
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function updateById(int $id, array $data): void
    {
        $sql = 'UPDATE customers
                   SET name = :name,
                       whatsapp = :whatsapp,
                       whatsapp_e164 = :e164,
                       updated_at = :updated_at,
                       last_login_at = :last_login_at';

        $params = [
            ':name'          => $data['name'],
            ':whatsapp'      => $data['whatsapp'],
            ':e164'          => normalizePhone($data['whatsapp_e164'] ?? $data['whatsapp'] ?? ''),
            ':updated_at'    => $data['updated_at'],
            ':last_login_at' => $data['last_login_at'],
            ':id'            => $id,
        ];

        if (array_key_exists('lgpd_consent_at', $data)) {
            $sql .= ', lgpd_consent_at = :lgpd_consent_at';
            $params[':lgpd_consent_at'] = $data['lgpd_consent_at'];

            if (array_key_exists('lgpd_consent_ip', $data)) {
                $sql .= ', lgpd_consent_ip = :lgpd_consent_ip';
                $params[':lgpd_consent_ip'] = $data['lgpd_consent_ip'];
            }
        }

        $sql .= ' WHERE id = :id';

        $st  = self::pdo()->prepare($sql);
        $st->execute($params);
    }
}
