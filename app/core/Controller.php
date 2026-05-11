<?php

declare(strict_types=1);
class Controller
{
    protected function view(string $path, array $data = [])
    {
        // "public/home" → app/views/public/home.php
        $file = __DIR__ . '/../views/' . $path . '.php';

        if (!file_exists($file)) {
            echo "View não encontrada: $path";

            return;
        }
        extract($data);
        include $file;
    }

    /** Retorna conexão PDO usando a função db() definida em app/config/db.php */
    protected function db(): PDO
    {
        // Tentar carregar db.php se a função não existir
        if (!function_exists('db')) {
            $dbFile = __DIR__ . '/../config/db.php';
            if (file_exists($dbFile)) {
                require_once $dbFile;
            }
        }
        
        if (!function_exists('db')) {
            // Se ainda não existe, criar conexão diretamente
            error_log('Função db() não encontrada, criando conexão direta');
            static $pdo = null;
            
            if ($pdo) {
                return $pdo;
            }
            
            $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
            $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'multi_menu';
            $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
            $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            
            try {
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                return $pdo;
            } catch (PDOException $e) {
                error_log('Erro ao conectar ao banco: ' . $e->getMessage());
                throw new RuntimeException('Não foi possível conectar ao banco de dados: ' . $e->getMessage());
            }
        }
        
        $pdo = db(); // a função db() deve retornar um PDO (com cache estático)

        if (!$pdo instanceof PDO) {
            throw new RuntimeException('db() não retornou uma instância de PDO.');
        }

        return $pdo;
    }

    /** Protege rotas admin (inicia a sessão antes de verificar) */
    protected function requireAdmin(): void
    {
        Auth::start();       // garante que a sessão foi iniciada
        Auth::requireAdmin();
    }

    /**
     * ID da empresa corrente no contexto do admin.
     * - Para root: empresa escolhida via Auth::setActiveCompany()
     * - Para owner/staff: a própria company_id do usuário
     */
    protected function currentCompanyId(): ?int
    {
        return Auth::activeCompanyId();
    }

    /** Slug corrente do contexto (se definido via Auth::setActiveCompany) */
    protected function currentCompanySlug(): ?string
    {
        return Auth::activeCompanySlug();
    }

    /**
     * Garante que o contexto ativo bate com o slug da rota.
     * Útil em rotas /admin/{slug}/...
     */
    protected function ensureCompanyContext(int $companyId, string $slug): void
    {
        $activeId   = Auth::activeCompanyId();
        $activeSlug = Auth::activeCompanySlug();

        if ($activeId !== $companyId || $activeSlug !== $slug) {
            Auth::setActiveCompany($companyId, $slug);
        }
    }
}
