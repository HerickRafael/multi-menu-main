<?php
/**
 * Script de Cron para Processamento de Engajamento de Clientes
 * 
 * Este script deve ser executado a cada 1-2 minutos via cron job.
 * 
 * Exemplo de configuração cron:
 * * * * * * /usr/bin/php /var/www/html/scripts/engagement_cron.php >> /var/log/engagement_cron.log 2>&1
 * 
 * O que este script faz:
 * 1. Busca todas as empresas com engajamento ativado
 * 2. Para cada empresa:
 *    - Identifica clientes novos que não fizeram pedido (Cenário 1)
 *    - Identifica clientes inativos há X dias (Cenário 2)
 *    - Adiciona mensagens à fila respeitando horário de funcionamento
 *    - Processa a fila de mensagens pendentes
 * 
 * @author Multi-Menu System
 * @version 1.0.0
 */

declare(strict_types=1);

// Configurações de erro para CLI
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Caminho base do projeto
define('BASE_PATH', dirname(__DIR__));

// Carregar bootstrap
require_once BASE_PATH . '/app/bootstrap.php';

// Carregar o serviço de engajamento
require_once BASE_PATH . '/app/services/CustomerEngagementService.php';

/**
 * Logger para o cron
 */
function cronLog(string $message, string $level = 'INFO'): void
{
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Log no arquivo
    $logFile = BASE_PATH . '/storage/logs/engagement_cron_' . date('Y-m-d') . '.log';
    file_put_contents($logFile, $formattedMessage, FILE_APPEND | LOCK_EX);
    
    // Também exibir no stdout para debug
    echo $formattedMessage;
}

/**
 * Chamada HTTP para Evolution API.
 */
function evolutionApiRequest(string $server, string $apiKey, string $path, string $method = 'GET', ?array $body = null): array
{
    $url = rtrim($server, '/') . '/' . ltrim($path, '/');

    $ch = curl_init($url);
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'apikey: ' . $apiKey,
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_CUSTOMREQUEST => $method,
    ]);

    if ($body !== null && strtoupper($method) !== 'GET') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['success' => false, 'error' => $curlError, 'http_code' => $httpCode, 'data' => null];
    }

    $decoded = null;
    if (is_string($response) && $response !== '') {
        $decoded = json_decode($response, true);
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        $msg = 'HTTP ' . $httpCode;
        if (is_array($decoded)) {
            $msg = (string)($decoded['message'] ?? $decoded['error'] ?? $msg);
        } elseif (is_string($response) && trim($response) !== '') {
            $msg = trim($response);
        }
        return ['success' => false, 'error' => $msg, 'http_code' => $httpCode, 'data' => $decoded];
    }

    return ['success' => true, 'error' => null, 'http_code' => $httpCode, 'data' => is_array($decoded) ? $decoded : []];
}

/**
 * Verifica se a empresa está no horário de expediente agora.
 */
function isWithinCompanyBusinessHours(PDO $pdo, int $companyId): bool
{
    $now = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
    $weekday = (int)$now->format('N');
    $currentTime = $now->format('H:i:s');

    $stmt = $pdo->prepare("SELECT is_open, open1, close1, open2, close2 FROM company_hours WHERE company_id = ? AND weekday = ? LIMIT 1");
    $stmt->execute([$companyId, $weekday]);
    $hours = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$hours || (int)$hours['is_open'] !== 1) {
        return false;
    }

    $isInRange = static function (string $time, string $start, string $end): bool {
        if ($start <= $end) {
            return $time >= $start && $time <= $end;
        }
        return $time >= $start || $time <= $end;
    };

    if (!empty($hours['open1']) && !empty($hours['close1'])) {
        if ($isInRange($currentTime, $hours['open1'], $hours['close1'])) {
            return true;
        }
    }

    if (!empty($hours['open2']) && !empty($hours['close2'])) {
        if ($isInRange($currentTime, $hours['open2'], $hours['close2'])) {
            return true;
        }
    }

    return false;
}

/**
 * Sincroniza automaticamente settings da Evolution por horário:
 * - em expediente: alwaysOnline=true, rejectCall=false
 * - fora de expediente: alwaysOnline=false, rejectCall=true
 */
function syncEvolutionSettingsByBusinessHours(PDO $pdo): void
{
    $rows = [];

    // Preferir instance_name do customer_engagement_config; fallback para última instance_identifier da evolution_instances.
    try {
        $sql = "SELECT
                    c.id AS company_id,
                    c.name AS company_name,
                    c.evolution_server_url,
                    c.evolution_api_key,
                    COALESCE(NULLIF(ec.instance_name, ''), NULLIF(last_ei.instance_identifier, '')) AS instance_name
                FROM companies c
                LEFT JOIN customer_engagement_config ec ON ec.company_id = c.id
                LEFT JOIN (
                    SELECT e1.company_id, e1.instance_identifier
                    FROM evolution_instances e1
                    INNER JOIN (
                        SELECT company_id, MAX(id) AS max_id
                        FROM evolution_instances
                        GROUP BY company_id
                    ) latest ON latest.max_id = e1.id
                ) last_ei ON last_ei.company_id = c.id
                WHERE c.active = 1
                  AND COALESCE(c.evolution_server_url, '') <> ''
                  AND COALESCE(c.evolution_api_key, '') <> ''
                  AND COALESCE(NULLIF(ec.instance_name, ''), NULLIF(last_ei.instance_identifier, '')) <> ''";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        // Fallback seguro para bases sem tabela evolution_instances.
        $sql = "SELECT DISTINCT
                    c.id AS company_id,
                    c.name AS company_name,
                    c.evolution_server_url,
                    c.evolution_api_key,
                    ec.instance_name
                FROM companies c
                INNER JOIN customer_engagement_config ec ON ec.company_id = c.id
                WHERE c.active = 1
                  AND COALESCE(c.evolution_server_url, '') <> ''
                  AND COALESCE(c.evolution_api_key, '') <> ''
                  AND COALESCE(ec.instance_name, '') <> ''";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($rows)) {
        cronLog('Sync Evolution horário: nenhuma empresa elegível encontrada');
        return;
    }

    $defaultSettings = [
        'rejectCall' => false,
        'msgCall' => '',
        'groupsIgnore' => false,
        'alwaysOnline' => false,
        'readMessages' => false,
        'readStatus' => false,
        'syncFullHistory' => false,
    ];

    $allowedKeys = array_keys($defaultSettings);

    foreach ($rows as $row) {
        $companyId = (int)$row['company_id'];
        $companyName = (string)$row['company_name'];
        $instanceName = (string)$row['instance_name'];

        try {
            $withinHours = isWithinCompanyBusinessHours($pdo, $companyId);
            $desiredAlwaysOnline = $withinHours;
            $desiredRejectCall = !$withinHours;

            $current = evolutionApiRequest(
                (string)$row['evolution_server_url'],
                (string)$row['evolution_api_key'],
                '/settings/find/' . rawurlencode($instanceName),
                'GET'
            );

            if (!$current['success']) {
                cronLog("Sync Evolution horário: falha ao buscar settings de {$companyName}/{$instanceName}: {$current['error']}", 'WARN');
                continue;
            }

            $rawSettings = is_array($current['data']) ? $current['data'] : [];

            $normalize = static function (string $key, $value) {
                if ($key === 'msgCall') {
                    return (string)($value ?? '');
                }

                if (is_bool($value)) {
                    return $value;
                }
                if (is_numeric($value)) {
                    return ((int)$value) === 1;
                }
                if (is_string($value)) {
                    $v = strtolower(trim($value));
                    if (in_array($v, ['1', 'true', 'yes', 'on'], true)) {
                        return true;
                    }
                    if (in_array($v, ['0', 'false', 'no', 'off', ''], true)) {
                        return false;
                    }
                }
                return (bool)$value;
            };

            $currentSettings = [];
            foreach ($allowedKeys as $key) {
                if (array_key_exists($key, $rawSettings)) {
                    $currentSettings[$key] = $normalize($key, $rawSettings[$key]);
                }
            }

            $alreadyApplied =
                isset($currentSettings['alwaysOnline'], $currentSettings['rejectCall']) &&
                (bool)$currentSettings['alwaysOnline'] === $desiredAlwaysOnline &&
                (bool)$currentSettings['rejectCall'] === $desiredRejectCall;

            if ($alreadyApplied) {
                continue;
            }

            $payload = array_merge($defaultSettings, $currentSettings, [
                'alwaysOnline' => $desiredAlwaysOnline,
                'rejectCall' => $desiredRejectCall,
            ]);

            $save = evolutionApiRequest(
                (string)$row['evolution_server_url'],
                (string)$row['evolution_api_key'],
                '/settings/set/' . rawurlencode($instanceName),
                'POST',
                $payload
            );

            if (!$save['success']) {
                cronLog("Sync Evolution horário: falha ao salvar settings de {$companyName}/{$instanceName}: {$save['error']}", 'WARN');
                continue;
            }

            $modeLabel = $withinHours ? 'expediente (alwaysOnline=ON)' : 'fora expediente (rejectCall=ON)';
            cronLog("Sync Evolution horário aplicado em {$companyName}/{$instanceName}: {$modeLabel}");
        } catch (Throwable $e) {
            cronLog("Sync Evolution horário: erro em {$companyName}/{$instanceName}: {$e->getMessage()}", 'WARN');
        }
    }
}

/**
 * Função principal do cron
 */
function runEngagementCron(): void
{
    $startTime = microtime(true);
    cronLog('=== Iniciando ciclo de engajamento ===');
    
    try {
        // Obter conexão com o banco
        $pdo = db();

        // 0) Sincronizar modo da instância Evolution com horário de expediente
        syncEvolutionSettingsByBusinessHours($pdo);
        
        // Buscar todas as empresas com engajamento ativado
        $sql = "SELECT 
                    ec.*,
                    c.name as company_name,
                    c.slug as company_slug,
                    c.evolution_server_url,
                    c.evolution_api_key
                FROM customer_engagement_config ec
                INNER JOIN companies c ON c.id = ec.company_id
                WHERE ec.enabled = 1
                AND c.active = 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($configs)) {
            cronLog('Nenhuma empresa com engajamento ativado encontrada');
            return;
        }
        
        cronLog(sprintf('Encontradas %d empresas com engajamento ativado', count($configs)));
        
        // Limite de tempo por empresa: impede que uma empresa monopolize o ciclo
        $maxSecondsPerCompany = 30;
        
        // Processar cada empresa
        foreach ($configs as $config) {
            $companyId = (int)$config['company_id'];
            $companyName = $config['company_name'];
            $companyStart = microtime(true);
            
            cronLog("Processando empresa: $companyName (ID: $companyId)");
            
            try {
                // Criar instância do serviço (apenas companyId)
                $service = new CustomerEngagementService($companyId);
                
                // Timeout: limita tempo por empresa para não monopolizar ciclo
                $service->setMaxExecutionSeconds($maxSecondsPerCompany);
                
                // Rodar ciclo de engajamento
                $result = $service->runEngagementCycle();
                
                // Log dos resultados
                $companyDuration = round(microtime(true) - $companyStart, 2);
                $stats = [
                    'scheduled' => $result['scheduled'] ?? 0,
                    'sent' => $result['sent'] ?? 0,
                    'errors' => count($result['errors'] ?? []),
                    'dlq_retried' => $result['dlq_retried'] ?? 0,
                    'dlq_count' => $result['dlq_count'] ?? 0,
                ];
                
                cronLog(sprintf(
                    '  → Agendadas: %d, Enviadas: %d, Erros: %d, DLQ: %d (retry: %d) (%.1fs)',
                    $stats['scheduled'],
                    $stats['sent'],
                    $stats['errors'],
                    $stats['dlq_count'],
                    $stats['dlq_retried'],
                    $companyDuration
                ));
                
                if (!empty($result['errors'])) {
                    foreach ($result['errors'] as $error) {
                        cronLog("  ⚠ Erro: $error", 'WARN');
                    }
                }
                
            } catch (Exception $e) {
                cronLog("Erro ao processar empresa $companyName: " . $e->getMessage(), 'ERROR');
                error_log("[Engagement Cron] Erro empresa $companyId: " . $e->getMessage());
            }
        }
        
    } catch (Exception $e) {
        cronLog('Erro fatal no cron: ' . $e->getMessage(), 'ERROR');
        error_log("[Engagement Cron] Erro fatal: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    }
    
    $duration = round(microtime(true) - $startTime, 2);
    cronLog("=== Ciclo finalizado em {$duration}s ===");
    
    // Heartbeat: registra execução no banco para monitoramento
    // Se este registro ficar desatualizado, alerta que cron parou
    try {
        $pdo = db();
        $metadata = json_encode([
            'companies_processed' => count($configs ?? []),
            'total_sent' => array_sum(array_column($configs ?? [], 'sent')),
        ], JSON_UNESCAPED_UNICODE);
        $stmt = $pdo->prepare("
            INSERT INTO system_heartbeat (service_name, last_run_at, duration_seconds, status, metadata)
            VALUES ('engagement_cron', NOW(), ?, 'ok', ?)
            ON DUPLICATE KEY UPDATE last_run_at = NOW(), duration_seconds = ?, status = 'ok', metadata = ?
        ");
        $stmt->execute([$duration, $metadata, $duration, $metadata]);
    } catch (\Throwable $e) {
        // Tabela/coluna pode não existir — silenciar
    }
}

/**
 * Verificar se o script já está rodando (lock)
 */
function acquireLock(): bool
{
    // Lock distribuído via MySQL advisory lock
    // Vantagens sobre file lock:
    // - Funciona em ambientes multi-servidor (mesmo MySQL)
    // - Liberado automaticamente se processo/conexão morrer
    // - Sem risco de lock file obsoleto (stale lock)
    try {
        $pdo = db();
        $stmt = $pdo->query("SELECT GET_LOCK('engagement_cron_lock', 0)");
        $result = (int)$stmt->fetchColumn();
        return $result === 1;
    } catch (\Throwable $e) {
        error_log('[Engagement Cron] Erro ao adquirir lock MySQL: ' . $e->getMessage());
        return false;
    }
}

/**
 * Liberar lock
 */
function releaseLock(): void
{
    try {
        $pdo = db();
        $pdo->query("SELECT RELEASE_LOCK('engagement_cron_lock')");
    } catch (\Throwable $e) {
        // Lock será liberado automaticamente ao encerrar a conexão
        error_log('[Engagement Cron] Erro ao liberar lock: ' . $e->getMessage());
    }
}

// ==================
// EXECUÇÃO PRINCIPAL
// ==================

// Verificar se é CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Este script só pode ser executado via CLI');
}

// Tentar adquirir lock
if (!acquireLock()) {
    cronLog('Outro processo de cron já está em execução. Abortando.', 'WARN');
    exit(0);
}

// Registrar handler para liberar lock ao finalizar
register_shutdown_function('releaseLock');

// Rodar o cron
try {
    runEngagementCron();
} catch (Throwable $e) {
    cronLog('Erro não tratado: ' . $e->getMessage(), 'ERROR');
    error_log("[Engagement Cron] Throwable: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    exit(1);
}

exit(0);
