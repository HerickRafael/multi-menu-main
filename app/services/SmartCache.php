<?php

/**
 * Smart Cache - Cache Inteligente com Redis
 * 
 * Decide automaticamente quando usar cache baseado em:
 * - Tempo de execução da query
 * - Histórico de performance
 * - Threshold configurável
 * 
 * Funcionalidades:
 * - Detecção automática de queries lentas
 * - Cache adaptativo (aprende com o tempo)
 * - Bypass automático para queries rápidas
 * - Métricas e estatísticas
 */
class SmartCache
{
    private static $redis = null;
    private static $enabled = false;
    private static $threshold = 50; // ms - queries acima disso usam cache
    private static $stats = [
        'queries_total' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
        'cache_bypassed' => 0,
        'slow_queries_detected' => 0,
        'total_time_saved_ms' => 0
    ];
    
    // Histórico de performance por chave (aprendizado)
    private static $performance_history = [];
    
    /**
     * Inicializar SmartCache
     */
    public static function init(): void
    {
        if (self::$enabled) {
            return;
        }
        
        // Tentar conectar ao Redis
        if (extension_loaded('redis')) {
            try {
                if (!class_exists('Redis')) {
                    throw new RuntimeException('A classe Redis não está disponível. Certifique-se de que a extensão do Redis está instalada e habilitada.');
                }
                
                self::$redis = new Redis();
                $host = getenv('REDIS_HOST') ?: ($_ENV['REDIS_HOST'] ?? 'redis_redis');
                $port = getenv('REDIS_PORT') ?: ($_ENV['REDIS_PORT'] ?? 6379);
                $password = getenv('REDIS_PASSWORD') ?: ($_ENV['REDIS_PASSWORD'] ?? '');
                
                error_log("[SmartCache] Tentando conectar ao Redis em {$host}:{$port}");
                
                self::$redis->connect($host, (int)$port, 2.0);
                
                if (!empty($password)) {
                    self::$redis->auth($password);
                }
                
                self::$redis->select(2); // Database 2 para SmartCache
                self::$enabled = true;
                
                // Carregar histórico de performance
                self::loadPerformanceHistory();
                
            } catch (Exception $e) {
                error_log('[SmartCache] Falha na conexão com Redis: ' . $e->getMessage());
                self::$enabled = false;
            }
        }
    }
    
    /**
     * Executar query com cache inteligente
     * 
     * @param string $key Chave única do cache
     * @param callable $callback Função que executa a query
     * @param int $ttl TTL em segundos (padrão: 300 = 5 minutos)
     * @return mixed Resultado da query
     */
    public static function remember(string $key, callable $callback, int $ttl = 300)
    {
        self::init();
        self::$stats['queries_total']++;
        
        // Se Redis não disponível, executar direto
        if (!self::$enabled) {
            return $callback();
        }
        
        // Verificar se essa query costuma ser lenta (aprendizado)
        $shouldCache = self::shouldUseCache($key);
        
        // Se histórico indica que é rápida, fazer bypass do cache
        if (!$shouldCache) {
            self::$stats['cache_bypassed']++;
            return $callback();
        }
        
        // Tentar pegar do cache
        try {
            $cached = self::$redis->get($key);
            
            if ($cached !== false) {
                // Cache HIT
                self::$stats['cache_hits']++;
                
                // Registrar que cache foi útil
                self::recordCacheHit($key);
                
                return unserialize($cached);
            }
        } catch (Exception $e) {
            error_log('[SmartCache] Erro ao ler cache: ' . $e->getMessage());
        }
        
        // Cache MISS - executar query e medir tempo
        self::$stats['cache_misses']++;
        $startTime = microtime(true);
        
        $result = $callback();
        
        $executionTime = (microtime(true) - $startTime) * 1000; // em ms
        
        // Registrar performance
        self::recordQueryPerformance($key, $executionTime);
        
        // Se query foi lenta, salvar no cache
        if ($executionTime > self::$threshold) {
            self::$stats['slow_queries_detected']++;
            
            try {
                self::$redis->setex($key, $ttl, serialize($result));
                
                // Calcular tempo economizado em futuras requisições
                self::$stats['total_time_saved_ms'] += $executionTime;
                
            } catch (Exception $e) {
                error_log('[SmartCache] Erro ao escrever cache: ' . $e->getMessage());
            }
        }
        
        return $result;
    }
    
    /**
     * Verificar se deve usar cache baseado em histórico
     * 
     * @param string $key Chave do cache
     * @return bool True se deve usar cache
     */
    private static function shouldUseCache(string $key): bool
    {
        // Se não tem histórico, assume que deve tentar cache
        if (!isset(self::$performance_history[$key])) {
            return true;
        }
        
        $history = self::$performance_history[$key];
        
        // Se média de execução é menor que threshold, não precisa cache
        if ($history['avg_time'] < self::$threshold) {
            return false;
        }
        
        // Se já teve cache hits, continuar usando cache
        if ($history['cache_hits'] > 0) {
            return true;
        }
        
        return true;
    }
    
    /**
     * Registrar performance de uma query
     * 
     * @param string $key Chave
     * @param float $time Tempo em ms
     */
    private static function recordQueryPerformance(string $key, float $time): void
    {
        if (!isset(self::$performance_history[$key])) {
            self::$performance_history[$key] = [
                'executions' => 0,
                'total_time' => 0,
                'avg_time' => 0,
                'cache_hits' => 0,
                'last_execution' => 0
            ];
        }
        
        $history = &self::$performance_history[$key];
        $history['executions']++;
        $history['total_time'] += $time;
        $history['avg_time'] = $history['total_time'] / $history['executions'];
        $history['last_execution'] = $time;
        
        // Salvar histórico periodicamente (a cada 10 queries)
        if (self::$stats['queries_total'] % 10 === 0) {
            self::savePerformanceHistory();
        }
    }
    
    /**
     * Registrar cache hit
     * 
     * @param string $key Chave
     */
    private static function recordCacheHit(string $key): void
    {
        if (isset(self::$performance_history[$key])) {
            self::$performance_history[$key]['cache_hits']++;
        }
    }
    
    /**
     * Carregar histórico de performance do Redis
     */
    private static function loadPerformanceHistory(): void
    {
        if (!self::$enabled) {
            return;
        }
        
        try {
            $history = self::$redis->get('smart_cache:performance_history');
            if ($history !== false) {
                self::$performance_history = unserialize($history);
            }
        } catch (Exception $e) {
            error_log('[SmartCache] Error loading performance history: ' . $e->getMessage());
        }
    }
    
    /**
     * Salvar histórico de performance no Redis
     */
    private static function savePerformanceHistory(): void
    {
        if (!self::$enabled) {
            return;
        }
        
        try {
            // Manter apenas últimos 100 registros (evitar crescimento infinito)
            if (count(self::$performance_history) > 100) {
                // Ordenar por último uso e manter os 100 mais recentes
                uasort(self::$performance_history, function($a, $b) {
                    return $b['executions'] - $a['executions'];
                });
                self::$performance_history = array_slice(self::$performance_history, 0, 100, true);
            }
            
            self::$redis->setex(
                'smart_cache:performance_history',
                86400, // 24 horas
                serialize(self::$performance_history)
            );
        } catch (Exception $e) {
            error_log('[SmartCache] Error saving performance history: ' . $e->getMessage());
        }
    }
    
    /**
     * Invalidar cache por chave
     * 
     * @param string $key Chave do cache
     */
    public static function forget(string $key): void
    {
        if (!self::$enabled) {
            return;
        }
        
        try {
            self::$redis->del($key);
        } catch (Exception $e) {
            error_log('[SmartCache] Error deleting cache: ' . $e->getMessage());
        }
    }

    /**
     * Obter valor diretamente do cache (sem callback)
     * 
     * @param string $key Chave do cache
     * @return mixed|null Valor ou null se não encontrado
     */
    public static function get(string $key)
    {
        self::init();
        
        if (!self::$enabled) {
            return null;
        }
        
        try {
            $cached = self::$redis->get($key);
            if ($cached !== false) {
                return unserialize($cached);
            }
        } catch (Exception $e) {
            error_log('[SmartCache] Error reading cache: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Definir valor diretamente no cache
     * 
     * @param string $key Chave do cache
     * @param mixed $value Valor a armazenar
     * @param int $ttl TTL em segundos (padrão: 300)
     */
    public static function set(string $key, $value, int $ttl = 300): void
    {
        self::init();
        
        if (!self::$enabled) {
            return;
        }
        
        try {
            self::$redis->setex($key, $ttl, serialize($value));
        } catch (Exception $e) {
            error_log('[SmartCache] Error writing cache: ' . $e->getMessage());
        }
    }
    
    /**
     * Invalidar cache por padrão (ex: "products:*")
     * 
     * @param string $pattern Padrão de chaves
     */
    public static function forgetByPattern(string $pattern): void
    {
        if (!self::$enabled) {
            return;
        }
        
        try {
            $keys = self::$redis->keys($pattern);
            if (!empty($keys)) {
                self::$redis->del($keys);
            }
        } catch (Exception $e) {
            error_log('[SmartCache] Error deleting cache by pattern: ' . $e->getMessage());
        }
    }
    
    /**
     * Limpar todo o cache
     */
    public static function flush(): void
    {
        if (!self::$enabled) {
            return;
        }
        
        try {
            self::$redis->flushDB();
            self::$performance_history = [];
        } catch (Exception $e) {
            error_log('[SmartCache] Error flushing cache: ' . $e->getMessage());
        }
    }
    
    /**
     * Configurar threshold (tempo mínimo para usar cache)
     * 
     * @param int $milliseconds Threshold em millisegundos
     */
    public static function setThreshold(int $milliseconds): void
    {
        self::$threshold = $milliseconds;
    }
    
    /**
     * Obter estatísticas
     * 
     * @return array Estatísticas
     */
    public static function getStats(): array
    {
        $hitRate = self::$stats['queries_total'] > 0
            ? round((self::$stats['cache_hits'] / self::$stats['queries_total']) * 100, 2)
            : 0;
        
        return array_merge(self::$stats, [
            'enabled' => self::$enabled,
            'threshold_ms' => self::$threshold,
            'hit_rate_percent' => $hitRate,
            'performance_history_size' => count(self::$performance_history)
        ]);
    }
    
    /**
     * Obter histórico de performance
     * 
     * @return array Histórico
     */
    public static function getPerformanceHistory(): array
    {
        return self::$performance_history;
    }
    
    /**
     * Health check
     * 
     * @return array Status
     */
    public static function healthCheck(): array
    {
        self::init();
        
        $status = 'healthy';
        $message = 'SmartCache operational';
        
        if (!self::$enabled) {
            $status = 'disabled';
            $message = 'Redis not available';
        }
        
        return [
            'status' => $status,
            'message' => $message,
            'redis_available' => self::$enabled,
            'stats' => self::getStats()
        ];
    }
}
