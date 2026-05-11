<?php

namespace App\Middleware;

/**
 * SQL Injection Prevention Middleware
 * 
 * Protege contra ataques de SQL Injection através de:
 * - Prepared Statements (PDO)
 * - Input Validation
 * - Query Builder seguro
 * - SQL Escaping
 * 
 * Implementação enterprise-level seguindo OWASP Top 10.
 * 
 * Uso:
 * 
 * // Prepared Statements (recomendado)
 * $users = SqlInjectionPrevention::query(
 *     "SELECT * FROM users WHERE email = :email AND status = :status",
 *     ['email' => $email, 'status' => 'active']
 * );
 * 
 * // Query Builder
 * $users = SqlInjectionPrevention::select('users')
 *     ->where('email', '=', $email)
 *     ->where('status', '=', 'active')
 *     ->get();
 * 
 * @link https://owasp.org/www-community/attacks/SQL_Injection
 */
class SqlInjectionPrevention
{
    /** @var \PDO Conexão PDO */
    private static ?\PDO $pdo = null;
    
    /** @var array Configuração padrão */
    private const DEFAULT_CONFIG = [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'database' => '',
        'username' => '',
        'password' => '',
        'charset' => 'utf8mb4',
        'options' => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,  // Prepared statements nativos
        ]
    ];
    
    /** @var array Estatísticas de queries */
    private static array $stats = [
        'queries_executed' => 0,
        'queries_prevented' => 0,
        'execution_time' => 0.0
    ];
    
    /** @var array Log de queries suspeitas */
    private static array $suspiciousQueries = [];
    
    /** @var array Padrões de SQL Injection */
    private const INJECTION_PATTERNS = [
        '/(\bUNION\b.*\bSELECT\b)/i',           // UNION SELECT attacks
        "/(\\bOR\\b\\s+['\"]?\\d+['\"]?\\s*=\\s*['\"]?\\d+['\"]?)/i",  // OR '1'='1', OR 1=1
        '/(;\\s*DROP\\b)/i',                    // ; DROP TABLE
        '/(;\\s*DELETE\\b)/i',                  // ; DELETE FROM
        '/(;\\s*UPDATE\\b)/i',                  // ; UPDATE
        '/(;\\s*INSERT\\b)/i',                  // ; INSERT
        '/(\bEXEC\\b\\s*\\()/i',                // EXEC(
        '/(\bEXECUTE\\b\\s*\\()/i',             // EXECUTE(
        '/(--[^\\r\\n]*$)/m',                   // SQL comments --
        '/(\/\*.*?\*\/)/s',                     // /* */ comments
        '/(\bxp_[a-z_]+)/i',                    // xp_cmdshell, etc
        '/(\bsp_[a-z_]+)/i',                    // sp_executesql, etc
    ];
    
    /**
     * Configura a conexão PDO
     * 
     * @param array $config Configuração do banco
     * @return void
     */
    public static function configure(array $config = []): void
    {
        $config = array_merge(self::DEFAULT_CONFIG, $config);
        
        try {
            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s;charset=%s',
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );
            
            self::$pdo = new \PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );
            
            // Logger removed
            
        } catch (\PDOException $e) {
            // Logger removed);
            throw $e;
        }
    }
    
    /**
     * Obtém a conexão PDO
     * 
     * @return \PDO
     * @throws \RuntimeException Se não configurado
     */
    public static function getPdo(): \PDO
    {
        if (self::$pdo === null) {
            throw new \RuntimeException("Database not configured. Call configure() first.");
        }
        
        return self::$pdo;
    }
    
    /**
     * Executa query com prepared statements (método principal)
     * 
     * @param string $sql Query SQL com placeholders
     * @param array $params Parâmetros nomeados ou posicionais
     * @return array Resultados da query
     * @throws \RuntimeException Se query for suspeita
     */
    public static function query(string $sql, array $params = []): array
    {
        $startTime = microtime(true);
        
        // Validar query
        if (self::isSuspiciousQuery($sql)) {
            self::$stats['queries_prevented']++;
            self::$suspiciousQueries[] = [
                'sql' => $sql,
                'params' => $params,
                'timestamp' => date('Y-m-d H:i:s'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ];
            
            // Logger removed
            
            throw new \RuntimeException("Suspicious SQL query detected");
        }
        
        try {
            $pdo = self::getPdo();
            $stmt = $pdo->prepare($sql);
            
            // Bind parameters
            foreach ($params as $key => $value) {
                if (is_int($key)) {
                    // Parâmetros posicionais (?)
                    $stmt->bindValue($key + 1, $value, self::getParamType($value));
                } else {
                    // Parâmetros nomeados (:name)
                    $key = str_starts_with($key, ':') ? $key : ':' . $key;
                    $stmt->bindValue($key, $value, self::getParamType($value));
                }
            }
            
            $stmt->execute();
            
            self::$stats['queries_executed']++;
            self::$stats['execution_time'] += (microtime(true) - $startTime);
            
            // Retornar resultados para SELECT
            if (stripos(trim($sql), 'SELECT') === 0) {
                return $stmt->fetchAll();
            }
            
            // Retornar linhas afetadas para INSERT/UPDATE/DELETE
            return ['affected_rows' => $stmt->rowCount()];
            
        } catch (\PDOException $e) {
            // Logger removed);
            throw $e;
        }
    }
    
    /**
     * Query Builder - SELECT
     * 
     * @param string $table Nome da tabela
     * @return QueryBuilder
     */
    public static function select(string $table): QueryBuilder
    {
        return new QueryBuilder($table, 'SELECT');
    }
    
    /**
     * Query Builder - INSERT
     * 
     * @param string $table Nome da tabela
     * @param array $data Dados a inserir
     * @return array Resultado
     */
    public static function insert(string $table, array $data): array
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ':' . $col, $columns);
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            self::escapeIdentifier($table),
            implode(', ', array_map([self::class, 'escapeIdentifier'], $columns)),
            implode(', ', $placeholders)
        );
        
        $result = self::query($sql, $data);
        
        return [
            'affected_rows' => $result['affected_rows'] ?? 0,
            'last_insert_id' => self::getPdo()->lastInsertId()
        ];
    }
    
    /**
     * Query Builder - UPDATE
     * 
     * @param string $table Nome da tabela
     * @param array $data Dados a atualizar
     * @param array $where Condições WHERE
     * @return array Resultado
     */
    public static function update(string $table, array $data, array $where): array
    {
        $setParts = [];
        $params = [];
        
        foreach ($data as $column => $value) {
            $setParts[] = self::escapeIdentifier($column) . ' = :set_' . $column;
            $params['set_' . $column] = $value;
        }
        
        $whereParts = [];
        foreach ($where as $column => $value) {
            $whereParts[] = self::escapeIdentifier($column) . ' = :where_' . $column;
            $params['where_' . $column] = $value;
        }
        
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            self::escapeIdentifier($table),
            implode(', ', $setParts),
            implode(' AND ', $whereParts)
        );
        
        return self::query($sql, $params);
    }
    
    /**
     * Query Builder - DELETE
     * 
     * @param string $table Nome da tabela
     * @param array $where Condições WHERE
     * @return array Resultado
     */
    public static function delete(string $table, array $where): array
    {
        $whereParts = [];
        $params = [];
        
        foreach ($where as $column => $value) {
            $whereParts[] = self::escapeIdentifier($column) . ' = :' . $column;
            $params[$column] = $value;
        }
        
        $sql = sprintf(
            "DELETE FROM %s WHERE %s",
            self::escapeIdentifier($table),
            implode(' AND ', $whereParts)
        );
        
        return self::query($sql, $params);
    }
    
    /**
     * Valida se uma query é suspeita de SQL Injection
     * 
     * @param string $sql Query SQL
     * @return bool True se suspeita
     */
    public static function isSuspiciousQuery(string $sql): bool
    {
        foreach (self::INJECTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $sql)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Valida input contra SQL Injection
     * 
     * @param mixed $input Input a validar
     * @return bool True se seguro
     */
    public static function validateInput($input): bool
    {
        if (is_array($input)) {
            foreach ($input as $value) {
                if (!self::validateInput($value)) {
                    return false;
                }
            }
            return true;
        }
        
        if (!is_string($input)) {
            return true; // Números, booleans são seguros
        }
        
        // Verificar padrões de injection
        foreach (self::INJECTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $input)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Escapa identificadores (tabelas, colunas)
     * 
     * @param string $identifier Identificador
     * @return string Identificador escapado
     */
    public static function escapeIdentifier(string $identifier): string
    {
        // Permitir * para SELECT *
        if ($identifier === '*') {
            return '*';
        }
        
        // Remover caracteres perigosos
        $identifier = preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
        
        // Adicionar backticks (MySQL) ou quotes (PostgreSQL)
        return '`' . $identifier . '`';
    }
    
    /**
     * Escapa string para uso direto (não recomendado - use prepared statements)
     * 
     * @param string $value Valor a escapar
     * @return string Valor escapado
     * @deprecated Use prepared statements com query()
     */
    public static function escapeString(string $value): string
    {
        return self::getPdo()->quote($value);
    }
    
    /**
     * Determina o tipo de parâmetro PDO
     * 
     * @param mixed $value Valor
     * @return int Tipo PDO
     */
    private static function getParamType($value): int
    {
        if (is_int($value)) {
            return \PDO::PARAM_INT;
        }
        
        if (is_bool($value)) {
            return \PDO::PARAM_BOOL;
        }
        
        if ($value === null) {
            return \PDO::PARAM_NULL;
        }
        
        return \PDO::PARAM_STR;
    }
    
    /**
     * Inicia transação
     * 
     * @return bool
     */
    public static function beginTransaction(): bool
    {
        return self::getPdo()->beginTransaction();
    }
    
    /**
     * Commit transação
     * 
     * @return bool
     */
    public static function commit(): bool
    {
        return self::getPdo()->commit();
    }
    
    /**
     * Rollback transação
     * 
     * @return bool
     */
    public static function rollback(): bool
    {
        return self::getPdo()->rollBack();
    }
    
    /**
     * Obtém estatísticas
     * 
     * @return array
     */
    public static function getStats(): array
    {
        return self::$stats;
    }
    
    /**
     * Obtém queries suspeitas bloqueadas
     * 
     * @return array
     */
    public static function getSuspiciousQueries(): array
    {
        return self::$suspiciousQueries;
    }
    
    /**
     * Reset state (útil para testes)
     * 
     * @return void
     */
    public static function reset(): void
    {
        self::$pdo = null;
        self::$stats = [
            'queries_executed' => 0,
            'queries_prevented' => 0,
            'execution_time' => 0.0
        ];
        self::$suspiciousQueries = [];
    }
}

/**
 * Query Builder - Construtor de queries seguro
 */
class QueryBuilder
{
    private string $table;
    private string $type;
    private array $select = ['*'];
    private array $where = [];
    private array $params = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $orderBy = [];
    
    public function __construct(string $table, string $type = 'SELECT')
    {
        $this->table = $table;
        $this->type = $type;
    }
    
    /**
     * Define colunas a selecionar
     * 
     * @param array $columns
     * @return self
     */
    public function columns(array $columns): self
    {
        $this->select = $columns;
        return $this;
    }
    
    /**
     * Adiciona condição WHERE
     * 
     * @param string $column Coluna
     * @param string $operator Operador (=, !=, >, <, etc)
     * @param mixed $value Valor
     * @return self
     */
    public function where(string $column, string $operator, $value): self
    {
        $paramName = 'where_' . count($this->where);
        $this->where[] = [
            'column' => $column,
            'operator' => $operator,
            'param' => $paramName
        ];
        $this->params[$paramName] = $value;
        
        return $this;
    }
    
    /**
     * Adiciona LIMIT
     * 
     * @param int $limit
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }
    
    /**
     * Adiciona OFFSET
     * 
     * @param int $offset
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }
    
    /**
     * Adiciona ORDER BY
     * 
     * @param string $column
     * @param string $direction ASC ou DESC
     * @return self
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];
        return $this;
    }
    
    /**
     * Executa a query e retorna resultados
     * 
     * @return array
     */
    public function get(): array
    {
        $sql = $this->buildSql();
        return SqlInjectionPrevention::query($sql, $this->params);
    }
    
    /**
     * Retorna primeiro resultado
     * 
     * @return array|null
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }
    
    /**
     * Conta registros
     * 
     * @return int
     */
    public function count(): int
    {
        $originalSelect = $this->select;
        $this->select = ['COUNT(*) as count'];
        $result = $this->first();
        $this->select = $originalSelect;
        
        return (int)($result['count'] ?? 0);
    }
    
    /**
     * Constrói SQL a partir do builder
     * 
     * @return string
     */
    private function buildSql(): string
    {
        $parts = [];
        
        // SELECT
        $parts[] = 'SELECT ' . implode(', ', array_map(
            [SqlInjectionPrevention::class, 'escapeIdentifier'],
            $this->select
        ));
        
        // FROM
        $parts[] = 'FROM ' . SqlInjectionPrevention::escapeIdentifier($this->table);
        
        // WHERE
        if (!empty($this->where)) {
            $whereClauses = [];
            foreach ($this->where as $condition) {
                $whereClauses[] = sprintf(
                    '%s %s :%s',
                    SqlInjectionPrevention::escapeIdentifier($condition['column']),
                    $condition['operator'],
                    $condition['param']
                );
            }
            $parts[] = 'WHERE ' . implode(' AND ', $whereClauses);
        }
        
        // ORDER BY
        if (!empty($this->orderBy)) {
            $orderClauses = [];
            foreach ($this->orderBy as $order) {
                $orderClauses[] = sprintf(
                    '%s %s',
                    SqlInjectionPrevention::escapeIdentifier($order['column']),
                    $order['direction']
                );
            }
            $parts[] = 'ORDER BY ' . implode(', ', $orderClauses);
        }
        
        // LIMIT
        if ($this->limit !== null) {
            $parts[] = 'LIMIT ' . (int)$this->limit;
        }
        
        // OFFSET
        if ($this->offset !== null) {
            $parts[] = 'OFFSET ' . (int)$this->offset;
        }
        
        return implode(' ', $parts);
    }
}
