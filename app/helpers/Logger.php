<?php
// app/helpers/Logger.php
// Sistema de logging centralizado

declare(strict_types=1);

class Logger
{
    private static $logFile = __DIR__ . '/../../debug_requests.log';
    
    /**
     * Log de nível DEBUG
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log('DEBUG', $message, $context);
    }
    
    /**
     * Log de nível INFO
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }
    
    /**
     * Log de nível WARNING
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }
    
    /**
     * Log de nível ERROR
     */
    public static function error(string $message, $exception = null, array $context = []): void
    {
        if ($exception instanceof Exception || $exception instanceof Throwable) {
            $context['exception'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        }
        
        self::log('ERROR', $message, $context);
    }
    
    /**
     * Método principal de logging
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
        
        // Escrever no arquivo de log
        error_log($logMessage, 3, self::$logFile);
        
        // Também enviar para o error_log padrão do PHP em modo desenvolvimento
        if (defined('APP_ENV') && APP_ENV === 'development') {
            error_log("[{$level}] {$message}{$contextStr}");
        }
    }
    
    /**
     * Limpar arquivo de log
     */
    public static function clear(): void
    {
        if (file_exists(self::$logFile)) {
            file_put_contents(self::$logFile, '');
        }
    }
    
    /**
     * Definir caminho do arquivo de log
     */
    public static function setLogFile(string $path): void
    {
        self::$logFile = $path;
    }
}
