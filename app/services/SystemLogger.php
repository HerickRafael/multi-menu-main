<?php

declare(strict_types=1);

/**
 * SystemLogger
 * 
 * Centralized logging service for all system events.
 * Logs are persisted to system_logs table for audit, debugging and compliance.
 */
class SystemLogger
{
    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_CRITICAL = 'critical';

    public const CATEGORY_AUTH = 'auth';
    public const CATEGORY_API = 'api';
    public const CATEGORY_PAYMENT = 'payment';
    public const CATEGORY_DELIVERY = 'delivery';
    public const CATEGORY_WEBHOOK = 'webhook';
    public const CATEGORY_ORDER = 'order';
    public const CATEGORY_SYSTEM = 'system';
    public const CATEGORY_PERFORMANCE = 'performance';
    public const CATEGORY_DATABASE = 'database';
    public const CATEGORY_CACHE = 'cache';

    private static ?self $instance = null;

    private function __construct()
    {
        // Singleton
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Log a debug message (most verbose)
     */
    public function debug(string $category, string $message, array $context = []): void
    {
        $this->log(self::LEVEL_DEBUG, $category, $message, $context);
    }

    /**
     * Log an info message
     */
    public function info(string $category, string $message, array $context = []): void
    {
        $this->log(self::LEVEL_INFO, $category, $message, $context);
    }

    /**
     * Log a warning message
     */
    public function warning(string $category, string $message, array $context = []): void
    {
        $this->log(self::LEVEL_WARNING, $category, $message, $context);
    }

    /**
     * Log an error message
     */
    public function error(string $category, string $message, array $context = []): void
    {
        $this->log(self::LEVEL_ERROR, $category, $message, $context);
    }

    /**
     * Log a critical error (requires immediate attention)
     */
    public function critical(string $category, string $message, array $context = []): void
    {
        $this->log(self::LEVEL_CRITICAL, $category, $message, $context);
    }

    /**
     * Log an exception
     */
    public function logException(\Throwable $exception, string $category = self::CATEGORY_SYSTEM, array $context = []): void
    {
        $context['exception_class'] = get_class($exception);
        $context['exception_code'] = $exception->getCode();
        $context['exception_file'] = $exception->getFile();
        $context['exception_line'] = $exception->getLine();

        $this->error($category, $exception->getMessage(), $context);
    }

    /**
     * Internal method to persist log to database
     */
    private function log(string $level, string $category, string $message, array $context = []): void
    {
        try {
            $db = Database::getInstance();

            // Enrich context with request info
            $enrichedContext = array_merge($context, [
                'ip_address' => $this->getClientIp(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
                'request_path' => $_SERVER['REQUEST_URI'] ?? '',
                'timestamp' => date('c'),
            ]);

            // Add user info if authenticated
            $userId = null;
            if (isset($_SESSION['user_id'])) {
                $userId = (int)$_SESSION['user_id'];
                $enrichedContext['user_id'] = $userId;
            } elseif (isset($_SESSION['super_admin_id'])) {
                $enrichedContext['super_admin_id'] = (int)$_SESSION['super_admin_id'];
            }

            $companyId = isset($_SESSION['company_id']) ? (int)$_SESSION['company_id'] : null;
            $orderId = $context['order_id'] ?? null;

            $stmt = $db->prepare(
                'INSERT INTO system_logs (level, module, message, context_json, company_id, order_id, source, logged_at, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );

            $stmt->execute([
                $level,
                $category,
                $message,
                json_encode($enrichedContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $companyId,
                $orderId,
                'app',
            ]);

            // Also log to file for backup
            $this->logToFile($level, $category, $message, $enrichedContext);
        } catch (\Throwable $e) {
            // Fallback to file logging if database fails
            error_log(sprintf(
                '[%s] %s - %s: %s',
                date('Y-m-d H:i:s'),
                $level,
                $category,
                $message
            ));
        }
    }

    /**
     * Log to file as backup
     */
    private function logToFile(string $level, string $category, string $message, array $context): void
    {
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $logFile = $logDir . '/' . date('Y-m-d') . '.log';
        $logEntry = sprintf(
            "[%s] [%s] [%s] %s\n",
            date('H:i:s'),
            strtoupper($level),
            $category,
            $message
        );

        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Get client IP address (considering proxy headers)
     */
    private function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Handle multiple IPs, take the first one
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return 'unknown';
    }
}
