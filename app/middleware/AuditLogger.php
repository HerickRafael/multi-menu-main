<?php

namespace App\Middleware;

use PDO;
use Exception;

/**
 * Audit Logging Middleware
 * 
 * Comprehensive audit logging system for security and compliance:
 * - Event logging (authentication, authorization, data changes)
 * - User activity tracking
 * - Security event monitoring
 * - Compliance audit trails (GDPR, SOC 2, PCI-DSS)
 * - Retention policies
 * - Log integrity verification
 * - SIEM integration ready
 * 
 * OWASP Coverage:
 * - A09: Security Logging and Monitoring Failures
 * 
 * CWE Coverage:
 * - CWE-778: Insufficient Logging
 * - CWE-223: Omission of Security-relevant Information
 * - CWE-532: Insertion of Sensitive Information into Log
 * 
 * Compliance:
 * - GDPR Article 30 (Records of Processing)
 * - SOC 2 (Audit Trail Requirements)
 * - PCI-DSS 10.x (Logging and Monitoring)
 * 
 * @package App\Middleware
 * @author Multi-Menu Security Team
 * @version 1.0.0
 */
class AuditLogger
{
    /**
     * PDO database connection
     */
    private ?PDO $pdo;

    /**
     * Configuration options
     */
    private array $config;

    /**
     * Event categories
     */
    private const EVENT_CATEGORIES = [
        'authentication' => 'Authentication Events',
        'authorization' => 'Authorization Events',
        'data_access' => 'Data Access Events',
        'data_modification' => 'Data Modification Events',
        'security' => 'Security Events',
        'system' => 'System Events',
        'user_action' => 'User Actions',
        'compliance' => 'Compliance Events'
    ];

    /**
     * Event severity levels
     */
    private const SEVERITY_LEVELS = [
        'debug' => 0,
        'info' => 1,
        'notice' => 2,
        'warning' => 3,
        'error' => 4,
        'critical' => 5,
        'alert' => 6,
        'emergency' => 7
    ];

    /**
     * Sensitive fields that should be masked
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'passwd',
        'pwd',
        'secret',
        'token',
        'api_key',
        'access_token',
        'refresh_token',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
        'social_security'
    ];

    /**
     * Event buffer for batch inserts
     */
    private array $buffer = [];

    /**
     * Statistics counters
     */
    private array $stats = [
        'events_logged' => 0,
        'events_failed' => 0,
        'security_events' => 0,
        'compliance_events' => 0,
        'data_changes' => 0
    ];

    /**
     * Constructor
     * 
     * @param PDO|null $pdo Database connection
     * @param array $config Configuration options
     */
    public function __construct(?PDO $pdo = null, array $config = [])
    {
        $this->pdo = $pdo;
        $this->config = array_merge([
            // General
            'enabled' => true,
            'async' => false,                    // Async logging (future)
            'buffer_size' => 100,                // Buffer before flush
            
            // Filtering
            'min_severity' => 'info',            // Minimum severity to log
            'excluded_categories' => [],         // Categories to exclude
            'excluded_events' => [],             // Specific events to exclude
            
            // Data Protection
            'mask_sensitive' => true,            // Mask sensitive data
            'max_data_length' => 10000,          // Max data field length
            'include_stack_trace' => false,      // Include stack traces
            
            // Retention
            'retention_days' => 365,             // Keep logs for 1 year
            'archive_after_days' => 90,          // Archive after 90 days
            'auto_cleanup' => true,              // Auto-delete old logs
            
            // Security
            'verify_integrity' => true,          // Log integrity verification
            'hash_algorithm' => 'sha256',        // Hash algorithm
            
            // Performance
            'batch_insert' => true,              // Batch inserts
            'compression' => false,              // Compress old logs
            
            // SIEM Integration
            'siem_enabled' => false,             // Enable SIEM export
            'siem_format' => 'json',             // json, syslog, cef
            'siem_endpoint' => null,             // SIEM endpoint URL
            
            // Compliance
            'gdpr_mode' => true,                 // GDPR compliance
            'pci_mode' => false,                 // PCI-DSS compliance
            'soc2_mode' => false                 // SOC 2 compliance
        ], $config);
    }

    /**
     * Log an audit event
     * 
     * @param string $event Event name
     * @param string $category Event category
     * @param string $severity Severity level
     * @param array $data Event data
     * @param int|null $userId User ID
     * @return bool Success
     */
    public function log(
        string $event,
        string $category = 'system',
        string $severity = 'info',
        array $data = [],
        ?int $userId = null
    ): bool {
        if (!$this->config['enabled']) {
            return false;
        }

        // Check if event should be logged
        if (!$this->shouldLog($event, $category, $severity)) {
            return false;
        }

        try {
            // Prepare event data
            $eventData = $this->prepareEventData(
                $event,
                $category,
                $severity,
                $data,
                $userId
            );

            // Store event
            if ($this->config['batch_insert']) {
                $this->buffer[] = $eventData;
                
                if (count($this->buffer) >= $this->config['buffer_size']) {
                    $this->flush();
                }
            } else {
                $this->writeEvent($eventData);
            }

            $this->stats['events_logged']++;

            // Update category-specific counters
            if ($category === 'security') {
                $this->stats['security_events']++;
            }
            if ($category === 'compliance') {
                $this->stats['compliance_events']++;
            }
            if ($category === 'data_modification') {
                $this->stats['data_changes']++;
            }

            // SIEM integration
            if ($this->config['siem_enabled']) {
                $this->exportToSiem($eventData);
            }

            return true;

        } catch (Exception $e) {
            $this->stats['events_failed']++;
            error_log("Audit logging failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log authentication event
     * 
     * @param string $action Action (login, logout, failed_login, etc)
     * @param int|null $userId User ID
     * @param bool $success Success status
     * @param array $details Additional details
     * @return bool Success
     */
    public function logAuthentication(
        string $action,
        ?int $userId = null,
        bool $success = true,
        array $details = []
    ): bool {
        return $this->log(
            "auth.$action",
            'authentication',
            $success ? 'info' : 'warning',
            array_merge([
                'action' => $action,
                'success' => $success,
                'method' => $details['method'] ?? 'password'
            ], $details),
            $userId
        );
    }

    /**
     * Log authorization event
     * 
     * @param string $resource Resource being accessed
     * @param string $permission Permission checked
     * @param bool $granted Access granted
     * @param int|null $userId User ID
     * @param array $details Additional details
     * @return bool Success
     */
    public function logAuthorization(
        string $resource,
        string $permission,
        bool $granted,
        ?int $userId = null,
        array $details = []
    ): bool {
        return $this->log(
            "authz.$resource.$permission",
            'authorization',
            $granted ? 'info' : 'warning',
            array_merge([
                'resource' => $resource,
                'permission' => $permission,
                'granted' => $granted
            ], $details),
            $userId
        );
    }

    /**
     * Log data access event
     * 
     * @param string $resource Resource accessed
     * @param string $action Action performed
     * @param array $identifiers Resource identifiers
     * @param int|null $userId User ID
     * @return bool Success
     */
    public function logDataAccess(
        string $resource,
        string $action,
        array $identifiers = [],
        ?int $userId = null
    ): bool {
        return $this->log(
            "data_access.$resource.$action",
            'data_access',
            'info',
            [
                'resource' => $resource,
                'action' => $action,
                'identifiers' => $identifiers
            ],
            $userId
        );
    }

    /**
     * Log data modification event
     * 
     * @param string $resource Resource modified
     * @param string $action Action (create, update, delete)
     * @param mixed $recordId Record identifier
     * @param array $changes Changes made (before/after)
     * @param int|null $userId User ID
     * @return bool Success
     */
    public function logDataModification(
        string $resource,
        string $action,
        $recordId,
        array $changes = [],
        ?int $userId = null
    ): bool {
        return $this->log(
            "data_mod.$resource.$action",
            'data_modification',
            'notice',
            [
                'resource' => $resource,
                'action' => $action,
                'record_id' => $recordId,
                'changes' => $this->maskSensitiveData($changes)
            ],
            $userId
        );
    }

    /**
     * Log security event
     * 
     * @param string $event Event name
     * @param string $severity Severity level
     * @param array $details Event details
     * @param int|null $userId User ID
     * @return bool Success
     */
    public function logSecurityEvent(
        string $event,
        string $severity = 'warning',
        array $details = [],
        ?int $userId = null
    ): bool {
        return $this->log(
            "security.$event",
            'security',
            $severity,
            $details,
            $userId
        );
    }

    /**
     * Log compliance event
     * 
     * @param string $regulation Regulation (GDPR, PCI, SOC2)
     * @param string $event Event name
     * @param array $details Event details
     * @param int|null $userId User ID
     * @return bool Success
     */
    public function logCompliance(
        string $regulation,
        string $event,
        array $details = [],
        ?int $userId = null
    ): bool {
        return $this->log(
            "compliance.$regulation.$event",
            'compliance',
            'notice',
            array_merge(['regulation' => $regulation], $details),
            $userId
        );
    }

    /**
     * Log user action
     * 
     * @param string $action Action performed
     * @param array $details Action details
     * @param int|null $userId User ID
     * @return bool Success
     */
    public function logUserAction(
        string $action,
        array $details = [],
        ?int $userId = null
    ): bool {
        return $this->log(
            "user_action.$action",
            'user_action',
            'info',
            $details,
            $userId
        );
    }

    /**
     * Check if event should be logged
     * 
     * @param string $event Event name
     * @param string $category Category
     * @param string $severity Severity
     * @return bool Should log
     */
    private function shouldLog(string $event, string $category, string $severity): bool
    {
        // Check severity threshold
        $minLevel = self::SEVERITY_LEVELS[$this->config['min_severity']] ?? 0;
        $eventLevel = self::SEVERITY_LEVELS[$severity] ?? 0;
        
        if ($eventLevel < $minLevel) {
            return false;
        }

        // Check excluded categories
        if (in_array($category, $this->config['excluded_categories'])) {
            return false;
        }

        // Check excluded events
        if (in_array($event, $this->config['excluded_events'])) {
            return false;
        }

        return true;
    }

    /**
     * Prepare event data for storage
     * 
     * @param string $event Event name
     * @param string $category Category
     * @param string $severity Severity
     * @param array $data Event data
     * @param int|null $userId User ID
     * @return array Prepared data
     */
    private function prepareEventData(
        string $event,
        string $category,
        string $severity,
        array $data,
        ?int $userId
    ): array {
        // Mask sensitive data
        if ($this->config['mask_sensitive']) {
            $data = $this->maskSensitiveData($data);
        }

        // Limit data length
        $dataJson = json_encode($data);
        if (strlen($dataJson) > $this->config['max_data_length']) {
            $dataJson = substr($dataJson, 0, $this->config['max_data_length']) . '...';
            $data = json_decode($dataJson, true);
        }

        // Get request context
        $context = $this->getRequestContext();

        // Prepare event
        $eventData = [
            'event_name' => $event,
            'category' => $category,
            'severity' => $severity,
            'user_id' => $userId,
            'ip_address' => $context['ip'],
            'user_agent' => $context['user_agent'],
            'request_method' => $context['method'],
            'request_uri' => $context['uri'],
            'event_data' => json_encode($data),
            'timestamp' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Add integrity hash
        if ($this->config['verify_integrity']) {
            $eventData['integrity_hash'] = $this->calculateHash($eventData);
        }

        // Add stack trace if enabled
        if ($this->config['include_stack_trace']) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            $eventData['stack_trace'] = json_encode($trace);
        }

        return $eventData;
    }

    /**
     * Write event to database
     * 
     * @param array $eventData Event data
     * @return bool Success
     */
    private function writeEvent(array $eventData): bool
    {
        if (!$this->pdo) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO audit_logs (
                    event_name, category, severity, user_id,
                    ip_address, user_agent, request_method, request_uri,
                    event_data, integrity_hash, stack_trace,
                    timestamp, created_at
                ) VALUES (
                    :event_name, :category, :severity, :user_id,
                    :ip_address, :user_agent, :request_method, :request_uri,
                    :event_data, :integrity_hash, :stack_trace,
                    :timestamp, :created_at
                )
            ");

            return $stmt->execute([
                'event_name' => $eventData['event_name'],
                'category' => $eventData['category'],
                'severity' => $eventData['severity'],
                'user_id' => $eventData['user_id'],
                'ip_address' => $eventData['ip_address'],
                'user_agent' => $eventData['user_agent'],
                'request_method' => $eventData['request_method'],
                'request_uri' => $eventData['request_uri'],
                'event_data' => $eventData['event_data'],
                'integrity_hash' => $eventData['integrity_hash'] ?? null,
                'stack_trace' => $eventData['stack_trace'] ?? null,
                'timestamp' => $eventData['timestamp'],
                'created_at' => $eventData['created_at']
            ]);

        } catch (Exception $e) {
            error_log("Failed to write audit log: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Flush buffered events
     * 
     * @return bool Success
     */
    public function flush(): bool
    {
        if (empty($this->buffer)) {
            return true;
        }

        if (!$this->pdo) {
            $this->buffer = [];
            return false;
        }

        try {
            $this->pdo->beginTransaction();

            foreach ($this->buffer as $eventData) {
                $this->writeEvent($eventData);
            }

            $this->pdo->commit();
            $this->buffer = [];

            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Failed to flush audit logs: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mask sensitive data
     * 
     * @param array $data Data to mask
     * @return array Masked data
     */
    private function maskSensitiveData(array $data): array
    {
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);

            // Check if key is sensitive
            foreach (self::SENSITIVE_FIELDS as $sensitiveField) {
                if (str_contains($lowerKey, $sensitiveField)) {
                    $data[$key] = $this->maskValue($value);
                    break;
                }
            }

            // Recursively mask nested arrays
            if (is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value);
            }
        }

        return $data;
    }

    /**
     * Mask a value
     * 
     * @param mixed $value Value to mask
     * @return string Masked value
     */
    private function maskValue($value): string
    {
        if ($value === null) {
            return '[NULL]';
        }

        $str = (string)$value;
        $length = strlen($str);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        // Show first 2 and last 2 characters
        return substr($str, 0, 2) . str_repeat('*', $length - 4) . substr($str, -2);
    }

    /**
     * Get request context
     * 
     * @return array Context data
     */
    private function getRequestContext(): array
    {
        return [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'cli',
            'timestamp' => time()
        ];
    }

    /**
     * Calculate integrity hash
     * 
     * @param array $eventData Event data
     * @return string Hash
     */
    private function calculateHash(array $eventData): string
    {
        $hashData = [
            $eventData['event_name'],
            $eventData['category'],
            $eventData['severity'],
            $eventData['user_id'] ?? '',
            $eventData['event_data'],
            $eventData['timestamp']
        ];

        return hash($this->config['hash_algorithm'], implode('|', $hashData));
    }

    /**
     * Verify event integrity
     * 
     * @param array $event Event from database
     * @return bool Valid
     */
    public function verifyIntegrity(array $event): bool
    {
        if (!isset($event['integrity_hash'])) {
            return false;
        }

        $expectedHash = $this->calculateHash($event);
        return hash_equals($expectedHash, $event['integrity_hash']);
    }

    /**
     * Export to SIEM
     * 
     * @param array $eventData Event data
     */
    private function exportToSiem(array $eventData): void
    {
        if (!$this->config['siem_endpoint']) {
            return;
        }

        try {
            $payload = match($this->config['siem_format']) {
                'json' => json_encode($eventData),
                'syslog' => $this->formatSyslog($eventData),
                'cef' => $this->formatCef($eventData),
                default => json_encode($eventData)
            };

            // Send to SIEM (async recommended)
            // Implementation depends on SIEM system
            // Example: HTTP POST, syslog, etc.

        } catch (Exception $e) {
            error_log("SIEM export failed: " . $e->getMessage());
        }
    }

    /**
     * Format event as syslog
     * 
     * @param array $eventData Event data
     * @return string Syslog formatted string
     */
    private function formatSyslog(array $eventData): string
    {
        return sprintf(
            "<%d>%s %s %s: %s %s",
            16, // facility.severity
            date('M d H:i:s'),
            gethostname(),
            'audit',
            $eventData['event_name'],
            $eventData['event_data']
        );
    }

    /**
     * Format event as CEF (Common Event Format)
     * 
     * @param array $eventData Event data
     * @return string CEF formatted string
     */
    private function formatCef(array $eventData): string
    {
        return sprintf(
            "CEF:0|MultiMenu|AuditLog|1.0|%s|%s|%d|%s",
            $eventData['event_name'],
            $eventData['category'],
            self::SEVERITY_LEVELS[$eventData['severity']] ?? 0,
            $eventData['event_data']
        );
    }

    /**
     * Search audit logs
     * 
     * @param array $filters Search filters
     * @param int $limit Result limit
     * @param int $offset Result offset
     * @return array Results
     */
    public function search(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        if (!$this->pdo) {
            return [];
        }

        $where = ['1=1'];
        $params = [];

        if (isset($filters['user_id'])) {
            $where[] = 'user_id = :user_id';
            $params['user_id'] = $filters['user_id'];
        }

        if (isset($filters['category'])) {
            $where[] = 'category = :category';
            $params['category'] = $filters['category'];
        }

        if (isset($filters['severity'])) {
            $where[] = 'severity = :severity';
            $params['severity'] = $filters['severity'];
        }

        if (isset($filters['event_name'])) {
            $where[] = 'event_name LIKE :event_name';
            $params['event_name'] = '%' . $filters['event_name'] . '%';
        }

        if (isset($filters['date_from'])) {
            $where[] = 'timestamp >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (isset($filters['date_to'])) {
            $where[] = 'timestamp <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        $sql = sprintf(
            "SELECT * FROM audit_logs WHERE %s ORDER BY timestamp DESC LIMIT %d OFFSET %d",
            implode(' AND ', $where),
            $limit,
            $offset
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get statistics
     * 
     * @return array Statistics
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Destructor - flush buffer
     */
    public function __destruct()
    {
        $this->flush();
    }
}
