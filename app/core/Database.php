<?php

declare(strict_types=1);

/**
 * Database Connection Manager - Enterprise Edition
 * 
 * Provides:
 * - Connection pooling with persistent connections
 * - Automatic reconnection on failures
 * - Query performance metrics
 * - Health checks
 * - Timeout configuration
 * - Connection validation
 * 
 * @package App\Core
 * @version 2.0.0
 */
class Database
{
    private static ?PDO $instance = null;
    private static int $reconnectAttempts = 0;
    private static array $stats = [
        'queries' => 0,
        'errors' => 0,
        'reconnections' => 0,
        'total_query_time' => 0.0,
        'avg_query_time' => 0.0,
        'slowest_query' => 0.0,
        'fastest_query' => PHP_FLOAT_MAX
    ];
    
    private const MAX_RECONNECT_ATTEMPTS = 3;
    private const RECONNECT_DELAY = 1; // segundos
    private const SLOW_QUERY_THRESHOLD = 1.0; // 1 segundo
    
    /**
     * Get database instance with automatic reconnection
     * 
     * @return PDO Database connection
     * @throws RuntimeException If connection fails after retries
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null || !self::isConnectionAlive()) {
            self::connect();
        }
        
        return self::$instance;
    }
    
    /**
     * Establish database connection with retry logic
     * 
     * @throws RuntimeException If connection fails
     */
    private static function connect(): void
    {
        $config = [
            'host' => $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1',
            'port' => $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: 3306,
            'dbname' => $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'multi_menu',
            'user' => $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root',
            'pass' => $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '',
            'charset' => 'utf8mb4',
        ];
        
        $dsn = sprintf(
            "mysql:host=%s;port=%d;dbname=%s;charset=%s",
            $config['host'],
            (int)$config['port'],
            $config['dbname'],
            $config['charset']
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false, // Previne SQL injection
            PDO::ATTR_PERSISTENT => true, // Connection pooling
            PDO::ATTR_TIMEOUT => 5, // 5 segundos timeout
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '-03:00'",
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
        ];
        
        try {
            self::$instance = new PDO($dsn, $config['user'], $config['pass'], $options);
            self::$reconnectAttempts = 0;
            
            // Log successful connection
            if (self::$stats['reconnections'] > 0) {
                error_log("[Database] Reconnected successfully after " . self::$stats['reconnections'] . " attempts");
            }
        } catch (PDOException $e) {
            self::handleConnectionError($e);
        }
    }
    
    /**
     * Handle connection errors with retry logic
     * 
     * @param PDOException $e The exception that occurred
     * @throws RuntimeException If max retries exceeded
     */
    private static function handleConnectionError(PDOException $e): void
    {
        self::$reconnectAttempts++;
        self::$stats['errors']++;
        
        $errorContext = [
            'attempt' => self::$reconnectAttempts,
            'max_attempts' => self::MAX_RECONNECT_ATTEMPTS,
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ];
        
        if (self::$reconnectAttempts >= self::MAX_RECONNECT_ATTEMPTS) {
            error_log("[Database] CRITICAL: Failed to connect after " . self::MAX_RECONNECT_ATTEMPTS . " attempts: " . json_encode($errorContext));
            throw new RuntimeException("Database connection failed: " . $e->getMessage());
        }
        
        error_log("[Database] Connection failed (attempt " . self::$reconnectAttempts . "/" . self::MAX_RECONNECT_ATTEMPTS . "): " . $e->getMessage());
        sleep(self::RECONNECT_DELAY);
        
        self::connect(); // Recursive retry
    }
    
    /**
     * Check if connection is alive
     * 
     * @return bool True if connection is active
     */
    private static function isConnectionAlive(): bool
    {
        if (self::$instance === null) {
            return false;
        }
        
        try {
            self::$instance->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            error_log("[Database] Connection lost, will reconnect: " . $e->getMessage());
            self::$stats['reconnections']++;
            return false;
        }
    }
    
    /**
     * Execute query with automatic metrics tracking
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Query parameters
     * @return array Query results
     * @throws PDOException If query fails
     */
    public static function query(string $sql, array $params = []): array
    {
        $start = microtime(true);
        
        try {
            $pdo = self::getInstance();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetchAll();
            
            self::updateStats($start, $sql);
            
            return $result;
        } catch (PDOException $e) {
            self::$stats['errors']++;
            error_log("[Database] Query error: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }
    
    /**
     * Execute query and return single row
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array|null Single row or null
     */
    public static function queryOne(string $sql, array $params = []): ?array
    {
        $result = self::query($sql, $params);
        return $result[0] ?? null;
    }
    
    /**
     * Execute INSERT/UPDATE/DELETE query
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return int Number of affected rows
     */
    public static function execute(string $sql, array $params = []): int
    {
        $start = microtime(true);
        
        try {
            $pdo = self::getInstance();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $affected = $stmt->rowCount();
            
            self::updateStats($start, $sql);
            
            return $affected;
        } catch (PDOException $e) {
            self::$stats['errors']++;
            error_log("[Database] Execute error: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }
    
    /**
     * Get last insert ID
     * 
     * @return string Last inserted ID
     */
    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }
    
    /**
     * Begin transaction
     * 
     * @return bool Success
     */
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }
    
    /**
     * Commit transaction
     * 
     * @return bool Success
     */
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }
    
    /**
     * Rollback transaction
     * 
     * @return bool Success
     */
    public static function rollBack(): bool
    {
        return self::getInstance()->rollBack();
    }
    
    /**
     * Check if in transaction
     * 
     * @return bool True if in transaction
     */
    public static function inTransaction(): bool
    {
        return self::getInstance()->inTransaction();
    }
    
    /**
     * Update query statistics
     * 
     * @param float $start Query start time
     * @param string $sql SQL query
     */
    private static function updateStats(float $start, string $sql): void
    {
        $duration = microtime(true) - $start;
        
        self::$stats['queries']++;
        self::$stats['total_query_time'] += $duration;
        self::$stats['avg_query_time'] = self::$stats['total_query_time'] / self::$stats['queries'];
        
        if ($duration > self::$stats['slowest_query']) {
            self::$stats['slowest_query'] = $duration;
        }
        
        if ($duration < self::$stats['fastest_query']) {
            self::$stats['fastest_query'] = $duration;
        }
        
        // Log slow queries
        if ($duration > self::SLOW_QUERY_THRESHOLD) {
            error_log(sprintf(
                "[Database] SLOW QUERY (%.3fs): %s",
                $duration,
                substr($sql, 0, 200)
            ));
        }
    }
    
    /**
     * Get performance statistics
     * 
     * @return array Statistics
     */
    public static function getStats(): array
    {
        return array_merge(self::$stats, [
            'avg_query_time_ms' => round(self::$stats['avg_query_time'] * 1000, 2),
            'slowest_query_ms' => round(self::$stats['slowest_query'] * 1000, 2),
            'fastest_query_ms' => round(self::$stats['fastest_query'] * 1000, 2),
            'total_query_time_s' => round(self::$stats['total_query_time'], 3),
            'error_rate' => self::$stats['queries'] > 0 
                ? round((self::$stats['errors'] / self::$stats['queries']) * 100, 2) 
                : 0
        ]);
    }
    
    /**
     * Health check
     * 
     * @return array Health status
     */
    public static function healthCheck(): array
    {
        try {
            $start = microtime(true);
            self::getInstance()->query('SELECT 1');
            $duration = (microtime(true) - $start) * 1000;
            
            $stats = self::getStats();
            
            $status = 'healthy';
            if ($duration > 100) $status = 'degraded';
            if ($duration > 500) $status = 'unhealthy';
            
            return [
                'status' => $status,
                'response_time_ms' => round($duration, 2),
                'queries_executed' => $stats['queries'],
                'total_errors' => $stats['errors'],
                'error_rate' => $stats['error_rate'] . '%',
                'reconnections' => $stats['reconnections'],
                'avg_query_time_ms' => $stats['avg_query_time_ms'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'queries_executed' => self::$stats['queries'],
                'total_errors' => self::$stats['errors'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    /**
     * Reset statistics (useful for testing)
     */
    public static function resetStats(): void
    {
        self::$stats = [
            'queries' => 0,
            'errors' => 0,
            'reconnections' => 0,
            'total_query_time' => 0.0,
            'avg_query_time' => 0.0,
            'slowest_query' => 0.0,
            'fastest_query' => PHP_FLOAT_MAX
        ];
    }
    
    /**
     * Close connection (useful for long-running scripts)
     */
    public static function disconnect(): void
    {
        self::$instance = null;
        self::$reconnectAttempts = 0;
    }
}
