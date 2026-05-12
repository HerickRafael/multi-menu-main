<?php
/**
 * Health Check Enterprise - Diagnóstico Completo de Sistema
 */

$start = microtime(true);

// Carregar Database enterprise
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/SessionManager.php';
require_once __DIR__ . '/../app/core/ErrorHandler.php';
require_once __DIR__ . '/../app/services/SmartCache.php';

ErrorHandler::register();

$response = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

// 1. Database com métricas enterprise
$dbStart = microtime(true);
try {
    $dbHealth = Database::healthCheck();
    $response['checks']['database'] = $dbHealth;
    
    if ($dbHealth['status'] !== 'healthy') {
        $response['status'] = $dbHealth['status'];
    }
} catch (Exception $e) {
    $response['checks']['database'] = [
        'status' => 'unhealthy',
        'error' => $e->getMessage(),
        'time' => round((microtime(true) - $dbStart) * 1000, 2) . ' ms'
    ];
    $response['status'] = 'unhealthy';
}

// 2. Session Enterprise
$sessionStart = microtime(true);
try {
    $sessionHealth = SessionManager::healthCheck();
    $response['checks']['session'] = $sessionHealth;
    
    if ($sessionHealth['status'] !== 'healthy') {
        $response['status'] = $sessionHealth['status'];
    }
} catch (Exception $e) {
    $response['checks']['session'] = [
        'status' => 'unhealthy',
        'error' => $e->getMessage(),
        'time' => round((microtime(true) - $sessionStart) * 1000, 2) . ' ms'
    ];
    $response['status'] = 'degraded';
}

// 3. Error Handler
$errorStart = microtime(true);
try {
    $errorHealth = ErrorHandler::healthCheck();
    $response['checks']['error_handler'] = $errorHealth;
    
    if ($errorHealth['status'] !== 'healthy') {
        $response['status'] = $errorHealth['status'];
    }
} catch (Exception $e) {
    $response['checks']['error_handler'] = [
        'status' => 'unhealthy',
        'error' => $e->getMessage(),
        'time' => round((microtime(true) - $errorStart) * 1000, 2) . ' ms'
    ];
    $response['status'] = 'degraded';
}

// 4. Smart Cache
$cacheStart = microtime(true);
try {
    $cacheHealth = SmartCache::healthCheck();
    $response['checks']['smart_cache'] = $cacheHealth;
    
    if ($cacheHealth['status'] === 'disabled') {
        $response['checks']['smart_cache']['status'] = 'healthy'; // Disabled não é unhealthy
    }
} catch (Exception $e) {
    $response['checks']['smart_cache'] = [
        'status' => 'unhealthy',
        'error' => $e->getMessage(),
        'time' => round((microtime(true) - $cacheStart) * 1000, 2) . ' ms'
    ];
}

// 5. Memória
$memoryUsage = memory_get_usage(true);
$memoryPeak = memory_get_peak_usage(true);
$response['checks']['memory'] = [
    'status' => 'healthy',
    'current_mb' => round($memoryUsage / 1024 / 1024, 2),
    'peak_mb' => round($memoryPeak / 1024 / 1024, 2),
    'limit' => ini_get('memory_limit')
];

// 6. PHP
$response['checks']['php'] = [
    'status' => 'healthy',
    'version' => PHP_VERSION,
    'opcache' => function_exists('opcache_get_status') ? 'enabled' : 'disabled',
    'extensions' => [
        'pdo' => extension_loaded('pdo'),
        'redis' => extension_loaded('redis'),
        'gd' => extension_loaded('gd'),
        'curl' => extension_loaded('curl')
    ]
];

// 6. Storage
$storagePath = __DIR__ . '/../storage/logs';
$response['checks']['storage'] = [
    'status' => is_writable($storagePath) ? 'healthy' : 'unhealthy',
    'writable' => is_writable($storagePath),
    'path' => basename(dirname($storagePath)) . '/' . basename($storagePath)
];

if (!is_writable($storagePath)) {
    $response['status'] = 'degraded';
}

// 7. Redis (opcional)
if (extension_loaded('redis')) {
    $redisStart = microtime(true);
    try {
        if (!class_exists('Redis')) {
            throw new RuntimeException('A classe Redis não está disponível. Certifique-se de que a extensão do Redis está instalada e habilitada.');
        }
        
        $redis = new Redis();
        $host = $_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST') ?: '127.0.0.1';
        $port = $_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT') ?: 6379;
        
        if ($redis->connect($host, (int)$port, 2)) {
            $redis->ping();
            $response['checks']['redis'] = [
                'status' => 'healthy',
                'time' => round((microtime(true) - $redisStart) * 1000, 2) . ' ms',
                'host' => $host . ':' . $port
            ];
            $redis->close();
        } else {
            $response['checks']['redis'] = [
                'status' => 'unhealthy',
                'error' => 'Connection failed'
            ];
        }
    } catch (Exception $e) {
        $response['checks']['redis'] = [
            'status' => 'unhealthy',
            'error' => $e->getMessage()
        ];
    }
}

// Total time
$response['total_time_ms'] = round((microtime(true) - $start) * 1000, 2);

// Set HTTP status code
http_response_code($response['status'] === 'healthy' ? 200 : 503);

header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

