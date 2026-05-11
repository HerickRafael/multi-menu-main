<?php

namespace App\Middleware;

/**
 * Rate Limiter Middleware
 * 
 * Protege o sistema contra abuso limitando número de requisições por período.
 * Implementação enterprise-level com múltiplos adaptadores (arquivo/Redis).
 * 
 * Uso:
 * 
 * // Verificar rate limit
 * if (!RateLimiter::check()) {
 *     http_response_code(429);
 *     echo json_encode(['error' => 'Too many requests']);
 *     exit;
 * }
 * 
 * // Obter informações do rate limit
 * $info = RateLimiter::getInfo();
 * header("X-RateLimit-Limit: {$info['limit']}");
 * header("X-RateLimit-Remaining: {$info['remaining']}");
 * header("X-RateLimit-Reset: {$info['reset']}");
 */
class RateLimiter
{
    /** @var int Máximo de requisições permitidas */
    private const MAX_REQUESTS = 60;
    
    /** @var int Janela de tempo em segundos */
    private const TIME_WINDOW = 60;
    
    /** @var string Diretório para armazenar dados de rate limiting */
    private const STORAGE_DIR = __DIR__ . '/../../storage/rate_limits';
    
    /** @var string|null Adaptador a ser usado (file|redis) */
    private static ?string $adapter = null;
    
    /** @var \Redis|null Instância do Redis quando disponível */
    private static ?\Redis $redis = null;
    
    /** @var int|null Limite atual usado na última verificação */
    private static ?int $currentLimit = null;

    /**
     * Verifica se a requisição atual está dentro do rate limit
     * 
     * @param string|null $identifier Identificador único (se null, usa IP + User Agent)
     * @param int|null $maxRequests Máximo de requisições (se null, usa padrão)
     * @param int|null $timeWindow Janela de tempo em segundos (se null, usa padrão)
     * @return bool True se permitido, False se excedeu limite
     */
    public static function check(
        ?string $identifier = null, 
        ?int $maxRequests = null, 
        ?int $timeWindow = null
    ): bool {
        $identifier = $identifier ?? self::getIdentifier();
        $maxRequests = $maxRequests ?? self::MAX_REQUESTS;
        $timeWindow = $timeWindow ?? self::TIME_WINDOW;
        
        self::$currentLimit = $maxRequests;
        
        $adapter = self::getAdapter();
        
        if ($adapter === 'redis') {
            return self::checkWithRedis($identifier, $maxRequests, $timeWindow);
        }
        
        return self::checkWithFile($identifier, $maxRequests, $timeWindow);
    }
    
    /**
     * Obtém informações sobre o rate limit atual
     * 
     * @param string|null $identifier Identificador único
     * @return array ['limit' => int, 'remaining' => int, 'reset' => int]
     */
    public static function getInfo(?string $identifier = null): array
    {
        $identifier = $identifier ?? self::getIdentifier();
        $adapter = self::getAdapter();
        
        if ($adapter === 'redis') {
            return self::getInfoWithRedis($identifier);
        }
        
        return self::getInfoWithFile($identifier);
    }
    
    /**
     * Limpa todos os dados de rate limiting
     * 
     * @return bool True se limpeza foi bem-sucedida
     */
    public static function clear(): bool
    {
        $adapter = self::getAdapter();
        
        if ($adapter === 'redis' && self::$redis) {
            $keys = self::$redis->keys('rate_limit:*');
            if ($keys) {
                self::$redis->del($keys);
            }
            return true;
        }
        
        // Limpar arquivos
        if (!is_dir(self::STORAGE_DIR)) {
            return true;
        }
        
        $files = glob(self::STORAGE_DIR . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }
    
    /**
     * Gera identificador único baseado em IP e User Agent
     * 
     * @return string Hash SHA256 do IP + User Agent
     */
    public static function getIdentifier(): string
    {
        $ip = self::getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        return hash('sha256', $ip . ':' . $userAgent);
    }
    
    /**
     * Obtém o IP real do cliente (considerando proxies)
     * 
     * @return string IP do cliente
     */
    private static function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',    // Cloudflare
            'HTTP_X_REAL_IP',            // Nginx proxy
            'HTTP_X_FORWARDED_FOR',      // Proxy padrão
            'REMOTE_ADDR'                // Direto
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Se for lista de IPs (proxy chain), pegar o primeiro
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validar IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Determina qual adaptador usar (Redis ou File)
     * 
     * @return string 'redis' ou 'file'
     */
    private static function getAdapter(): string
    {
        if (self::$adapter !== null) {
            return self::$adapter;
        }
        
        // Tentar usar Redis se disponível
        if (extension_loaded('redis')) {
            try {
                self::$redis = new \Redis();
                $connected = @self::$redis->connect('127.0.0.1', 6379, 1);
                
                if ($connected && self::$redis->ping()) {
                    self::$adapter = 'redis';
                    // Logger removed
                    return 'redis';
                }
            } catch (\Exception $e) {
                // Logger removed
            }
        }
        
        // Fallback para file-based
        self::$adapter = 'file';
        self::ensureStorageDir();
        
        return 'file';
    }
    
    /**
     * Verifica rate limit usando Redis
     */
    private static function checkWithRedis(
        string $identifier, 
        int $maxRequests, 
        int $timeWindow
    ): bool {
        $key = "rate_limit:{$identifier}";
        
        try {
            // Incrementar contador
            $current = self::$redis->incr($key);
            
            // Se é primeira requisição, definir expiração
            if ($current === 1) {
                self::$redis->expire($key, $timeWindow);
            }
            
            // Verificar se excedeu limite
            if ($current > $maxRequests) {
                // Logger removed
                
                return false;
            }
            
            return true;
            
        } catch (\Exception $e) {
            // Logger removed
            // Em caso de erro, permitir requisição (fail open)
            return true;
        }
    }
    
    /**
     * Verifica rate limit usando arquivo
     */
    private static function checkWithFile(
        string $identifier, 
        int $maxRequests, 
        int $timeWindow
    ): bool {
        $filename = self::STORAGE_DIR . '/' . $identifier . '.json';
        $now = time();
        
        // Ler dados atuais
        $data = self::readRateLimitFile($filename);
        
        // Limpar requisições antigas
        $data['requests'] = array_filter($data['requests'], function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        // Verificar limite
        if (count($data['requests']) >= $maxRequests) {
            // Logger removed
            
            return false;
        }
        
        // Adicionar requisição atual
        $data['requests'][] = $now;
        $data['last_request'] = $now;
        $data['limit'] = $maxRequests;  // Armazenar limite usado
        
        // Salvar
        self::writeRateLimitFile($filename, $data);
        
        return true;
    }
    
    /**
     * Obtém informações com Redis
     */
    private static function getInfoWithRedis(string $identifier): array
    {
        $key = "rate_limit:{$identifier}";
        $limit = self::$currentLimit ?? self::MAX_REQUESTS;
        
        try {
            $current = (int) self::$redis->get($key);
            $ttl = self::$redis->ttl($key);
            
            return [
                'limit' => $limit,
                'remaining' => max(0, $limit - $current),
                'reset' => time() + max(0, $ttl),
                'used' => $current
            ];
        } catch (\Exception $e) {
            return [
                'limit' => $limit,
                'remaining' => $limit,
                'reset' => time() + self::TIME_WINDOW,
                'used' => 0
            ];
        }
    }
    
    /**
     * Obtém informações com arquivo
     */
    private static function getInfoWithFile(string $identifier): array
    {
        $filename = self::STORAGE_DIR . '/' . $identifier . '.json';
        $data = self::readRateLimitFile($filename);
        $now = time();
        
        // Limpar requisições antigas
        $data['requests'] = array_filter($data['requests'], function($timestamp) use ($now) {
            return ($now - $timestamp) < self::TIME_WINDOW;
        });
        
        $used = count($data['requests']);
        $oldestRequest = !empty($data['requests']) ? min($data['requests']) : $now;
        $reset = $oldestRequest + self::TIME_WINDOW;
        
        // Obter limite usado na última chamada check()
        // Se não houver dados, usar o padrão
        $limit = $data['limit'] ?? self::MAX_REQUESTS;
        
        return [
            'limit' => $limit,
            'remaining' => max(0, $limit - $used),
            'reset' => $reset,
            'used' => $used
        ];
    }
    
    /**
     * Garante que o diretório de storage existe
     */
    private static function ensureStorageDir(): void
    {
        if (!is_dir(self::STORAGE_DIR)) {
            mkdir(self::STORAGE_DIR, 0755, true);
        }
    }
    
    /**
     * Lê arquivo de rate limit
     */
    private static function readRateLimitFile(string $filename): array
    {
        $default = [
            'requests' => [],
            'last_request' => null
        ];
        
        if (!file_exists($filename)) {
            return $default;
        }
        
        $content = @file_get_contents($filename);
        if ($content === false) {
            return $default;
        }
        
        $data = json_decode($content, true);
        return is_array($data) ? $data : $default;
    }
    
    /**
     * Escreve arquivo de rate limit
     */
    private static function writeRateLimitFile(string $filename, array $data): bool
    {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        return @file_put_contents($filename, $json, LOCK_EX) !== false;
    }
    
    /**
     * Configura manualmente o adaptador
     * 
     * @param string $adapter 'redis' ou 'file'
     */
    public static function setAdapter(string $adapter): void
    {
        if (!in_array($adapter, ['redis', 'file'])) {
            throw new \InvalidArgumentException("Invalid adapter: {$adapter}");
        }
        
        self::$adapter = $adapter;
    }
    
    /**
     * Reseta o adaptador (para testes)
     */
    public static function resetAdapter(): void
    {
        self::$adapter = null;
        self::$redis = null;
    }
}
