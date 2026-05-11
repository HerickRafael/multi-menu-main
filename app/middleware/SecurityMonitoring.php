<?php

namespace App\Middleware;

use PDO;
use Exception;

/**
 * Security Monitoring Middleware
 * 
 * Provides comprehensive security monitoring and threat detection:
 * - Real-time threat detection
 * - Anomaly detection (behavioral analysis)
 * - Security metrics and dashboards
 * - Alert system (email, webhook, SMS)
 * - Incident response automation
 * - Performance monitoring
 * - Threat intelligence integration
 * - Security score calculation
 * 
 * OWASP Coverage:
 * - A09: Security Logging and Monitoring Failures
 * - A05: Security Misconfiguration
 * - A06: Vulnerable and Outdated Components
 * 
 * CWE Coverage:
 * - CWE-778: Insufficient Logging
 * - CWE-223: Omission of Security-relevant Information
 * - CWE-755: Improper Handling of Exceptional Conditions
 * 
 * Compliance:
 * - SOC 2 CC7.2 (Monitoring Activities)
 * - PCI-DSS 10.6 (Review Logs)
 * - NIST CSF DE.CM (Security Continuous Monitoring)
 * 
 * @package App\Middleware
 * @author Multi-Menu Security Team
 * @version 1.0.0
 */
class SecurityMonitoring
{
    /**
     * Database connection
     */
    private PDO $db;

    /**
     * Configuration
     */
    private array $config;

    /**
     * Alert handlers
     */
    private array $alertHandlers = [];

    /**
     * Threat patterns
     */
    private array $threatPatterns = [];

    /**
     * Statistics
     */
    private array $stats = [
        'threats_detected' => 0,
        'anomalies_detected' => 0,
        'alerts_sent' => 0,
        'incidents_created' => 0
    ];

    /**
     * Threat severity levels
     */
    private const SEVERITY_INFO = 1;
    private const SEVERITY_LOW = 2;
    private const SEVERITY_MEDIUM = 3;
    private const SEVERITY_HIGH = 4;
    private const SEVERITY_CRITICAL = 5;

    /**
     * Anomaly detection thresholds
     */
    private const THRESHOLD_FAILED_LOGIN = 5;          // 5 failed logins in 10 min
    private const THRESHOLD_API_REQUESTS = 100;        // 100 requests per minute
    private const THRESHOLD_DATA_ACCESS = 50;          // 50 records in 5 min
    private const THRESHOLD_SUSPICIOUS_ACTIVITY = 10;  // 10 suspicious events in 1 hour

    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     * @param array $config Configuration
     */
    public function __construct(PDO $db, array $config = [])
    {
        $this->db = $db;
        $this->config = array_merge([
            // Monitoring
            'real_time_monitoring' => true,
            'anomaly_detection' => true,
            'threat_detection' => true,
            
            // Alerts
            'enable_alerts' => true,
            'alert_channels' => ['email', 'webhook'],
            'alert_email' => 'security@multi-menu.com',
            'alert_webhook' => null,
            
            // Thresholds
            'failed_login_threshold' => self::THRESHOLD_FAILED_LOGIN,
            'api_request_threshold' => self::THRESHOLD_API_REQUESTS,
            'data_access_threshold' => self::THRESHOLD_DATA_ACCESS,
            
            // Incident Response
            'auto_block_threats' => false,
            'auto_create_incidents' => true,
            'escalation_enabled' => true,
            
            // Performance
            'metrics_interval' => 60,        // seconds
            'cleanup_days' => 90,
            
            // Advanced
            'threat_intelligence' => false,
            'machine_learning' => false,
            'correlation_engine' => true
        ], $config);

        $this->initializeThreatPatterns();
    }

    /**
     * Monitor security event
     * 
     * @param string $eventType Event type
     * @param array $data Event data
     * @return array Monitoring result
     */
    public function monitor(string $eventType, array $data): array
    {
        $result = [
            'threat_detected' => false,
            'anomaly_detected' => false,
            'severity' => self::SEVERITY_INFO,
            'actions' => [],
            'alerts' => []
        ];

        try {
            // Real-time threat detection
            if ($this->config['threat_detection']) {
                $threat = $this->detectThreat($eventType, $data);
                if ($threat) {
                    $result['threat_detected'] = true;
                    $result['severity'] = $threat['severity'];
                    $result['threat'] = $threat;
                    $this->stats['threats_detected']++;
                }
            }

            // Anomaly detection
            if ($this->config['anomaly_detection']) {
                $anomaly = $this->detectAnomaly($eventType, $data);
                if ($anomaly) {
                    $result['anomaly_detected'] = true;
                    $result['anomaly'] = $anomaly;
                    $this->stats['anomalies_detected']++;
                }
            }

            // Record security event
            $eventId = $this->recordEvent($eventType, $data, $result);
            $result['event_id'] = $eventId;

            // Handle alerts
            if ($result['threat_detected'] || $result['anomaly_detected']) {
                $this->handleAlert($eventType, $data, $result);
            }

            // Auto-response
            if ($result['severity'] >= self::SEVERITY_HIGH) {
                $result['actions'] = $this->autoRespond($eventType, $data, $result);
            }

        } catch (Exception $e) {
            error_log('Security monitoring error: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Detect security threat
     * 
     * @param string $eventType Event type
     * @param array $data Event data
     * @return array|null Threat details or null
     */
    private function detectThreat(string $eventType, array $data): ?array
    {
        // Check against known threat patterns
        foreach ($this->threatPatterns as $pattern) {
            if ($pattern['event_type'] === $eventType || $pattern['event_type'] === '*') {
                if ($this->matchPattern($pattern, $data)) {
                    return [
                        'type' => $pattern['threat_type'],
                        'severity' => $pattern['severity'],
                        'description' => $pattern['description'],
                        'matched_pattern' => $pattern['name']
                    ];
                }
            }
        }

        // SQL Injection detection
        if (isset($data['query']) || isset($data['input'])) {
            $input = $data['query'] ?? $data['input'] ?? '';
            if ($this->detectSqlInjection($input)) {
                return [
                    'type' => 'sql_injection',
                    'severity' => self::SEVERITY_CRITICAL,
                    'description' => 'SQL injection attempt detected'
                ];
            }
        }

        // XSS detection
        if (isset($data['input']) || isset($data['content'])) {
            $input = $data['input'] ?? $data['content'] ?? '';
            if ($this->detectXss($input)) {
                return [
                    'type' => 'xss',
                    'severity' => self::SEVERITY_HIGH,
                    'description' => 'XSS attack attempt detected'
                ];
            }
        }

        // Brute force detection
        if ($eventType === 'authentication_failure') {
            if ($this->detectBruteForce($data)) {
                return [
                    'type' => 'brute_force',
                    'severity' => self::SEVERITY_HIGH,
                    'description' => 'Brute force attack detected'
                ];
            }
        }

        return null;
    }

    /**
     * Detect anomaly
     * 
     * @param string $eventType Event type
     * @param array $data Event data
     * @return array|null Anomaly details or null
     */
    private function detectAnomaly(string $eventType, array $data): ?array
    {
        $userId = $data['user_id'] ?? null;
        $ipAddress = $data['ip_address'] ?? null;

        // Unusual time access
        $hour = (int)date('G');
        if ($hour >= 2 && $hour <= 5) {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM security_events
                WHERE (user_id = ? OR ip_address = ?)
                  AND HOUR(created_at) BETWEEN 2 AND 5
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute([$userId, $ipAddress]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] < 3) {
                return [
                    'type' => 'unusual_time_access',
                    'severity' => self::SEVERITY_MEDIUM,
                    'description' => 'Access during unusual hours (2-5 AM)'
                ];
            }
        }

        // Geographic anomaly
        if ($ipAddress && $userId) {
            $location = $this->getIpLocation($ipAddress);
            if ($this->detectGeographicAnomaly($userId, $location)) {
                return [
                    'type' => 'geographic_anomaly',
                    'severity' => self::SEVERITY_HIGH,
                    'description' => 'Access from unusual geographic location'
                ];
            }
        }

        // Velocity anomaly (rapid actions)
        if ($userId) {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM security_events
                WHERE user_id = ?
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 30) {
                return [
                    'type' => 'velocity_anomaly',
                    'severity' => self::SEVERITY_MEDIUM,
                    'description' => 'Unusually high activity rate detected'
                ];
            }
        }

        // Data access pattern anomaly
        if ($eventType === 'data_access' && $userId) {
            if ($this->detectDataAccessAnomaly($userId, $data)) {
                return [
                    'type' => 'data_access_anomaly',
                    'severity' => self::SEVERITY_HIGH,
                    'description' => 'Unusual data access pattern detected'
                ];
            }
        }

        return null;
    }

    /**
     * Detect SQL injection
     * 
     * @param string $input Input string
     * @return bool Is SQL injection
     */
    private function detectSqlInjection(string $input): bool
    {
        $patterns = [
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bor\b\s+\d+\s*=\s*\d+)/i',
            '/(\band\b\s+\d+\s*=\s*\d+)/i',
            '/(;|\-\-|\/\*|\*\/|xp_|sp_)/i',
            '/(\bdrop\b|\bdelete\b|\binsert\b|\bupdate\b).*(\btable\b|\bfrom\b)/i',
            '/(\bexec\b|\bexecute\b)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect XSS
     * 
     * @param string $input Input string
     * @return bool Is XSS
     */
    private function detectXss(string $input): bool
    {
        $patterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=\s*["\']?[^"\']*["\']?/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect brute force attack
     * 
     * @param array $data Event data
     * @return bool Is brute force
     */
    private function detectBruteForce(array $data): bool
    {
        $identifier = $data['username'] ?? $data['email'] ?? $data['ip_address'] ?? null;
        
        if (!$identifier) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*) as failed_attempts
            FROM security_events
            WHERE event_type = 'authentication_failure'
              AND (event_data->>'$.username' = ? 
                   OR event_data->>'$.email' = ?
                   OR ip_address = ?)
              AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
        ");
        $stmt->execute([$identifier, $identifier, $identifier]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['failed_attempts'] >= $this->config['failed_login_threshold'];
    }

    /**
     * Detect geographic anomaly
     * 
     * @param int $userId User ID
     * @param array $location Location data
     * @return bool Is anomaly
     */
    private function detectGeographicAnomaly(int $userId, array $location): bool
    {
        // Get user's typical locations
        $stmt = $this->db->prepare("
            SELECT DISTINCT country, city
            FROM security_events
            WHERE user_id = ?
              AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            LIMIT 10
        ");
        $stmt->execute([$userId]);
        $typicalLocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if current location is typical
        foreach ($typicalLocations as $typical) {
            if ($typical['country'] === $location['country']) {
                return false;
            }
        }

        return count($typicalLocations) > 0;
    }

    /**
     * Detect data access anomaly
     * 
     * @param int $userId User ID
     * @param array $data Event data
     * @return bool Is anomaly
     */
    private function detectDataAccessAnomaly(int $userId, array $data): bool
    {
        // Check for mass data access
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as access_count
            FROM security_events
            WHERE user_id = ?
              AND event_type = 'data_access'
              AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['access_count'] > $this->config['data_access_threshold'];
    }

    /**
     * Get IP location
     * 
     * @param string $ipAddress IP address
     * @return array Location data
     */
    private function getIpLocation(string $ipAddress): array
    {
        // Simplified - in production, use GeoIP database
        return [
            'country' => 'Unknown',
            'city' => 'Unknown',
            'latitude' => null,
            'longitude' => null
        ];
    }

    /**
     * Match threat pattern
     * 
     * @param array $pattern Pattern
     * @param array $data Event data
     * @return bool Matches
     */
    private function matchPattern(array $pattern, array $data): bool
    {
        foreach ($pattern['conditions'] as $field => $condition) {
            if (!isset($data[$field])) {
                return false;
            }

            $value = $data[$field];

            if (is_array($condition)) {
                if (isset($condition['regex']) && !preg_match($condition['regex'], $value)) {
                    return false;
                }
                if (isset($condition['equals']) && $value !== $condition['equals']) {
                    return false;
                }
                if (isset($condition['contains']) && strpos($value, $condition['contains']) === false) {
                    return false;
                }
            } else {
                if ($value !== $condition) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Record security event
     * 
     * @param string $eventType Event type
     * @param array $data Event data
     * @param array $result Monitoring result
     * @return int Event ID
     */
    private function recordEvent(string $eventType, array $data, array $result): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO security_events (
                event_type,
                severity,
                threat_detected,
                anomaly_detected,
                user_id,
                ip_address,
                user_agent,
                country,
                city,
                event_data,
                threat_data,
                anomaly_data,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $eventType,
            $result['severity'],
            $result['threat_detected'] ? 1 : 0,
            $result['anomaly_detected'] ? 1 : 0,
            $data['user_id'] ?? null,
            $data['ip_address'] ?? null,
            $data['user_agent'] ?? null,
            $data['country'] ?? null,
            $data['city'] ?? null,
            json_encode($data),
            isset($result['threat']) ? json_encode($result['threat']) : null,
            isset($result['anomaly']) ? json_encode($result['anomaly']) : null
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Handle alert
     * 
     * @param string $eventType Event type
     * @param array $data Event data
     * @param array $result Monitoring result
     */
    private function handleAlert(string $eventType, array $data, array $result): void
    {
        if (!$this->config['enable_alerts']) {
            return;
        }

        $alert = [
            'event_type' => $eventType,
            'severity' => $result['severity'],
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => $data,
            'result' => $result
        ];

        // Send via configured channels
        foreach ($this->config['alert_channels'] as $channel) {
            try {
                $this->sendAlert($channel, $alert);
                $this->stats['alerts_sent']++;
            } catch (Exception $e) {
                error_log("Failed to send alert via {$channel}: " . $e->getMessage());
            }
        }

        // Create incident if needed
        if ($this->config['auto_create_incidents'] && $result['severity'] >= self::SEVERITY_HIGH) {
            $this->createIncident($alert);
        }
    }

    /**
     * Send alert
     * 
     * @param string $channel Channel
     * @param array $alert Alert data
     */
    private function sendAlert(string $channel, array $alert): void
    {
        switch ($channel) {
            case 'email':
                $this->sendEmailAlert($alert);
                break;
            case 'webhook':
                $this->sendWebhookAlert($alert);
                break;
            case 'sms':
                $this->sendSmsAlert($alert);
                break;
        }
    }

    /**
     * Send email alert
     * 
     * @param array $alert Alert data
     */
    private function sendEmailAlert(array $alert): void
    {
        $to = $this->config['alert_email'];
        $subject = "Security Alert: {$alert['event_type']} (Severity {$alert['severity']})";
        
        $message = "Security Alert Detected\n\n";
        $message .= "Event Type: {$alert['event_type']}\n";
        $message .= "Severity: {$alert['severity']}\n";
        $message .= "Timestamp: {$alert['timestamp']}\n\n";
        $message .= "Details:\n" . print_r($alert['result'], true);

        mail($to, $subject, $message);
    }

    /**
     * Send webhook alert
     * 
     * @param array $alert Alert data
     */
    private function sendWebhookAlert(array $alert): void
    {
        if (!$this->config['alert_webhook']) {
            return;
        }

        $ch = curl_init($this->config['alert_webhook']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($alert));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Send SMS alert
     * 
     * @param array $alert Alert data
     */
    private function sendSmsAlert(array $alert): void
    {
        // Implement SMS provider integration (Twilio, etc.)
    }

    /**
     * Create incident
     * 
     * @param array $alert Alert data
     * @return int Incident ID
     */
    private function createIncident(array $alert): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO security_incidents (
                incident_type,
                severity,
                status,
                title,
                description,
                alert_data,
                assigned_to,
                created_at
            ) VALUES (?, ?, 'open', ?, ?, ?, NULL, NOW())
        ");

        $title = "Security Incident: {$alert['event_type']}";
        $description = json_encode($alert['result']);

        $stmt->execute([
            $alert['event_type'],
            $alert['severity'],
            $title,
            $description,
            json_encode($alert)
        ]);

        $this->stats['incidents_created']++;

        return (int)$this->db->lastInsertId();
    }

    /**
     * Auto-respond to threat
     * 
     * @param string $eventType Event type
     * @param array $data Event data
     * @param array $result Monitoring result
     * @return array Actions taken
     */
    private function autoRespond(string $eventType, array $data, array $result): array
    {
        $actions = [];

        // Block IP if configured
        if ($this->config['auto_block_threats'] && isset($data['ip_address'])) {
            $this->blockIp($data['ip_address']);
            $actions[] = 'ip_blocked';
        }

        // Terminate session
        if (isset($data['session_id'])) {
            $this->terminateSession($data['session_id']);
            $actions[] = 'session_terminated';
        }

        // Lock user account
        if (isset($data['user_id']) && $result['severity'] === self::SEVERITY_CRITICAL) {
            $this->lockUserAccount($data['user_id']);
            $actions[] = 'account_locked';
        }

        return $actions;
    }

    /**
     * Block IP address
     * 
     * @param string $ipAddress IP address
     */
    private function blockIp(string $ipAddress): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO blocked_ips (ip_address, reason, blocked_until, created_at)
            VALUES (?, 'Auto-blocked by security monitoring', DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW())
            ON DUPLICATE KEY UPDATE blocked_until = DATE_ADD(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$ipAddress]);
    }

    /**
     * Terminate session
     * 
     * @param string $sessionId Session ID
     */
    private function terminateSession(string $sessionId): void
    {
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE session_id = ?");
        $stmt->execute([$sessionId]);
    }

    /**
     * Lock user account
     * 
     * @param int $userId User ID
     */
    private function lockUserAccount(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE users SET account_locked = 1, locked_at = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }

    /**
     * Get security metrics
     * 
     * @param string $period Period (hour, day, week, month)
     * @return array Metrics
     */
    public function getMetrics(string $period = 'day'): array
    {
        $interval = match($period) {
            'hour' => 'INTERVAL 1 HOUR',
            'day' => 'INTERVAL 1 DAY',
            'week' => 'INTERVAL 7 DAY',
            'month' => 'INTERVAL 30 DAY',
            default => 'INTERVAL 1 DAY'
        };

        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_events,
                SUM(threat_detected) as threats_detected,
                SUM(anomaly_detected) as anomalies_detected,
                AVG(severity) as avg_severity,
                MAX(severity) as max_severity
            FROM security_events
            WHERE created_at >= DATE_SUB(NOW(), {$interval})
        ");

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Calculate security score
     * 
     * @return array Security score
     */
    public function calculateSecurityScore(): array
    {
        $score = 100;
        $factors = [];

        // Recent threats (-20 points per critical threat)
        $stmt = $this->db->query("
            SELECT COUNT(*) as count
            FROM security_events
            WHERE threat_detected = 1
              AND severity = 5
              AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $criticalThreats = $stmt->fetchColumn();
        $score -= ($criticalThreats * 20);
        $factors['critical_threats'] = $criticalThreats;

        // Failed authentications (-5 points per 10 failures)
        $stmt = $this->db->query("
            SELECT COUNT(*) as count
            FROM security_events
            WHERE event_type = 'authentication_failure'
              AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $failedLogins = $stmt->fetchColumn();
        $score -= (floor($failedLogins / 10) * 5);
        $factors['failed_logins'] = $failedLogins;

        // Active incidents (-15 points per incident)
        $stmt = $this->db->query("SELECT COUNT(*) FROM security_incidents WHERE status = 'open'");
        $activeIncidents = $stmt->fetchColumn();
        $score -= ($activeIncidents * 15);
        $factors['active_incidents'] = $activeIncidents;

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'grade' => match(true) {
                $score >= 90 => 'A',
                $score >= 80 => 'B',
                $score >= 70 => 'C',
                $score >= 60 => 'D',
                default => 'F'
            },
            'factors' => $factors,
            'status' => match(true) {
                $score >= 90 => 'excellent',
                $score >= 75 => 'good',
                $score >= 60 => 'fair',
                $score >= 40 => 'poor',
                default => 'critical'
            }
        ];
    }

    /**
     * Initialize threat patterns
     */
    private function initializeThreatPatterns(): void
    {
        $this->threatPatterns = [
            [
                'name' => 'multiple_failed_logins',
                'event_type' => 'authentication_failure',
                'threat_type' => 'brute_force',
                'severity' => self::SEVERITY_HIGH,
                'description' => 'Multiple failed login attempts',
                'conditions' => []
            ],
            [
                'name' => 'admin_access_attempt',
                'event_type' => 'authorization_failure',
                'threat_type' => 'privilege_escalation',
                'severity' => self::SEVERITY_HIGH,
                'description' => 'Unauthorized admin access attempt',
                'conditions' => ['resource' => ['contains' => 'admin']]
            ],
            [
                'name' => 'suspicious_file_upload',
                'event_type' => 'file_upload',
                'threat_type' => 'malware',
                'severity' => self::SEVERITY_CRITICAL,
                'description' => 'Suspicious file upload detected',
                'conditions' => ['mime_type' => ['regex' => '/(executable|script)/i']]
            ]
        ];
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
}
