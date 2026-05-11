<?php
/**
 * WhatsAppSendService
 * 
 * Serviço CENTRALIZADO para envio de mensagens via Evolution API.
 * Todos os envios de WhatsApp devem passar por aqui para garantir:
 * 
 * 1. Log persistente de cada tentativa (whatsapp_send_log)
 * 2. Captura de message_id para correlação com echo (anti falso-takeover)
 * 3. Retry com backoff exponencial (configurável)
 * 4. Fila de fallback para reprocessamento (whatsapp_failed_queue)
 * 5. Verificação de human takeover antes do envio
 * 
 * Usado por: WebhookEvolutionController, OrderNotificationService, CustomerEngagementService
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/whatsapp/WhatsAppProviderInterface.php';
require_once __DIR__ . '/whatsapp/EvolutionV2Provider.php';
require_once __DIR__ . '/whatsapp/EvoGoProvider.php';

class WhatsAppSendService
{
    private PDO $db;
    
    // Singleton para reutilizar conexão DB
    private static ?self $instance = null;
    
    // Rate limiting: limites por empresa
    private const RATE_LIMIT_PER_MINUTE = 20;
    private const RATE_LIMIT_PER_HOUR = 200;

    /** @var array<string,bool>|null */
    private ?array $companiesColumnsCache = null;
    
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Envia uma mensagem de texto via Evolution API.
     * Inclui retry, log persistente, captura de message_id, e fallback.
     * 
     * @param int    $companyId     ID da empresa
     * @param string $instanceName  Nome da instância Evolution
     * @param string $number        Número do destinatário (com código país) OU remoteJid completo
     * @param string $message       Texto da mensagem
     * @param string $messageType   Tipo para categorização: auto_response, engagement, notification, pending_lid
     * @param array  $options       Opções adicionais:
     *                              - 'maxAttempts' (int, default 3): tentativas imediatas
     *                              - 'delay' (int, default 1200): delay em ms no envio (anti-spam Evolution)
     *                              - 'checkTakeover' (bool, default true): verificar human takeover
     *                              - 'enqueueOnFail' (bool, default true): salvar na fila de fallback
     *                              - 'timeout' (int, default 15): timeout HTTP em segundos
     *                              - 'checkRateLimit' (bool, default true): verificar rate limit global
     * @return array ['success' => bool, 'message_id' => ?string, 'http_code' => int, 'error' => ?string]
     */
    public function send(
        int $companyId,
        string $instanceName,
        string $number,
        string $message,
        string $messageType = 'auto_response',
        array $options = []
    ): array {
        $maxAttempts = $options['maxAttempts'] ?? 3;
        $delay = array_key_exists('delay', $options)
            ? (int)$options['delay']
            : (($messageType === 'auto_response' || $messageType === 'pending_lid') ? 0 : 1200);
        $checkTakeover = $options['checkTakeover'] ?? true;
        $enqueueOnFail = $options['enqueueOnFail'] ?? true;
        $timeout = $options['timeout'] ?? 15;
        $checkRateLimit = $options['checkRateLimit'] ?? true;
        
        $result = ['success' => false, 'message_id' => null, 'http_code' => 0, 'error' => null];

        // Allow override from caller: $options['allow_send'] or $options['context']['allow_send']
        $allowSend = null;
        if (array_key_exists('allow_send', $options)) {
            $allowSend = (bool)$options['allow_send'];
        } elseif (isset($options['context']) && is_array($options['context']) && array_key_exists('allow_send', $options['context'])) {
            $allowSend = (bool)$options['context']['allow_send'];
        }

        // Minimal, centralized enforcement for engagement messages (fail-closed).
        if ($messageType === 'engagement') {
            if ($allowSend === false) {
                $result['error'] = 'engagement_blocked_by_caller';
                return $result;
            }
            if ($allowSend !== true) {
                // fallback protection: if caller didn't explicitly allow, check safety
                if (!$this->isEngagementAllowed($companyId)) {
                    $result['error'] = 'engagement_blocked';
                    return $result;
                }
            }
        }

        // Normalizar: se vier com @, extrair o número/jid
        $isJid = strpos($number, '@') !== false;
        $isLid = strpos($number, '@lid') !== false;
        $phone = preg_replace('/@.*$/', '', $number);
        
        // Normalizar phone (exceto LIDs que não são números reais)
        if (!$isLid) {
            $phone = normalizePhone($phone);
        }
        
        $remoteJid = $isJid ? $number : $phone . '@s.whatsapp.net';
        
        // Human takeover check (exceto notificações de pedido)
        if ($checkTakeover && $messageType !== 'notification') {
            if ($this->isHumanTakeoverActive($companyId, $phone)) {
                error_log("[WhatsAppSend] Takeover humano ativo para {$phone} — envio bloqueado (type={$messageType})");
                $result['error'] = 'human_takeover_active';
                return $result;
            }
        }
        
        // Rate limit global (notificações de pedido sempre passam)
        if ($checkRateLimit && $messageType !== 'notification') {
            $rateLimitError = $this->checkRateLimit($companyId);
            if ($rateLimitError !== null) {
                error_log("[WhatsAppSend] Rate limit atingido para company_id={$companyId}: {$rateLimitError}");
                $result['error'] = $rateLimitError;
                
                // Enfileirar para reprocessamento futuro se habilitado
                if ($enqueueOnFail) {
                    $this->enqueueFailedMessage(
                        $companyId, $instanceName, $remoteJid, $message, $messageType,
                        429, 'rate_limit_exceeded', null, 0
                    );
                }
                return $result;
            }
        }
        
        // Buscar config do provedor WhatsApp da empresa
        $company = $this->getCompanyWhatsAppConfig($companyId);
        if (!$company) {
            error_log("[WhatsAppSend] Config WhatsApp não encontrada para company_id={$companyId}");
            $result['error'] = 'missing_whatsapp_config';
            return $result;
        }

        $providerKey = $this->resolveProviderKey($company, $options['provider'] ?? null);
        $provider = $this->createProviderClient($company, $providerKey);

        if ($provider === null) {
            error_log("[WhatsAppSend] Config do provider '{$providerKey}' inválida para company_id={$companyId}");
            $result['error'] = 'invalid_provider_config';
            return $result;
        }
        
        // Payload principal (Evolution v2): número puro para contatos normais, JID para LID.
        $payloadNumber = $isLid ? $remoteJid : $phone;
        
        $lastHttpCode = 0;
        $lastCurlError = '';
        $lastResponse = '';
        
        // Retry com backoff exponencial
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if ($attempt > 1) {
                $backoffSeconds = (int) pow(2, $attempt - 1);
                error_log("[WhatsAppSend] Retry #{$attempt} para {$phone} em {$backoffSeconds}s...");
                sleep($backoffSeconds);
            }
            
            $startTime = microtime(true);
            $sendResult = $provider->sendText($instanceName, $payloadNumber, $message, $delay, $timeout);
            $response = $sendResult['response'] ?? null;
            $httpCode = (int) ($sendResult['http_code'] ?? 0);
            $curlError = (string) ($sendResult['curl_error'] ?? '');
            
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $httpSuccess = !$curlError && $httpCode >= 200 && $httpCode < 300;
            $responseHasError = $this->hasApiResponseError($response);
            $success = $httpSuccess && !$responseHasError;

            if ($httpSuccess && $responseHasError) {
                error_log("[WhatsAppSend] HTTP {$httpCode} com erro de aplicação no body: " . $this->summarizeApiResponseError($response));
            }
            
            // Extrair message_id se sucesso
            $sentMessageId = null;
            if ($success) {
                $sentMessageId = $this->extractMessageId($response);
                if (empty($sentMessageId)) {
                    error_log("[WhatsAppSend] Aviso: envio com HTTP {$httpCode} sem message_id retornado para {$phone}");
                }
            }
            
            // Log persistente
            $this->logSendAttempt(
                $companyId, $instanceName, $remoteJid, $phone,
                $messageType, $message, $attempt,
                $success ? 'success' : ($attempt < $maxAttempts ? 'pending_retry' : 'failed'),
                $httpCode, $curlError, $response, $durationMs, $sentMessageId
            );
            
            if ($success) {
                error_log("[WhatsAppSend] Enviado com sucesso (tentativa #{$attempt}) msgId={$sentMessageId} para {$phone}");
                $result['success'] = true;
                $result['message_id'] = $sentMessageId;
                $result['http_code'] = $httpCode;
                return $result;
            }

            // Fallback imediato de formato (algumas versões da Evolution aceitam apenas um dos formatos)
            $fallbackPayloadNumber = ($payloadNumber === $phone) ? $remoteJid : $phone;

            if (!empty($fallbackPayloadNumber) && $fallbackPayloadNumber !== $payloadNumber) {
                $fallbackStartTime = microtime(true);
                $fallbackSendResult = $provider->sendText($instanceName, $fallbackPayloadNumber, $message, $delay, $timeout);
                $fallbackResponse = $fallbackSendResult['response'] ?? null;
                $fallbackHttpCode = (int) ($fallbackSendResult['http_code'] ?? 0);
                $fallbackCurlError = (string) ($fallbackSendResult['curl_error'] ?? '');

                $fallbackDurationMs = (int) ((microtime(true) - $fallbackStartTime) * 1000);
                $fallbackHttpSuccess = !$fallbackCurlError && $fallbackHttpCode >= 200 && $fallbackHttpCode < 300;
                $fallbackResponseHasError = $this->hasApiResponseError($fallbackResponse);
                $fallbackSuccess = $fallbackHttpSuccess && !$fallbackResponseHasError;

                if ($fallbackHttpSuccess && $fallbackResponseHasError) {
                    error_log("[WhatsAppSend] Fallback HTTP {$fallbackHttpCode} com erro de aplicação no body: " . $this->summarizeApiResponseError($fallbackResponse));
                }

                $fallbackMessageId = $fallbackSuccess ? $this->extractMessageId($fallbackResponse) : null;

                if ($fallbackSuccess && empty($fallbackMessageId)) {
                    error_log("[WhatsAppSend] Aviso: fallback com HTTP {$fallbackHttpCode} sem message_id retornado para {$phone}");
                }

                $this->logSendAttempt(
                    $companyId,
                    $instanceName,
                    $remoteJid,
                    $phone,
                    $messageType,
                    $message,
                    $attempt,
                    $fallbackSuccess ? 'success' : ($attempt < $maxAttempts ? 'pending_retry' : 'failed'),
                    $fallbackHttpCode,
                    $fallbackCurlError,
                    $fallbackResponse,
                    $fallbackDurationMs,
                    $fallbackMessageId
                );

                if ($fallbackSuccess) {
                    error_log("[WhatsAppSend] Enviado com sucesso no fallback de formato (tentativa #{$attempt}) msgId={$fallbackMessageId} para {$phone}");
                    $result['success'] = true;
                    $result['message_id'] = $fallbackMessageId;
                    $result['http_code'] = $fallbackHttpCode;
                    return $result;
                }

                $lastHttpCode = $fallbackHttpCode;
                $lastCurlError = $fallbackCurlError;
                $lastResponse = $fallbackResponse;
                $fallbackBodySummary = $this->summarizeApiResponseError($fallbackResponse);
                error_log("[WhatsAppSend] Fallback de formato falhou: HTTP {$fallbackHttpCode}, cURL: {$fallbackCurlError}, body: {$fallbackBodySummary}");
            }
            
            $lastHttpCode = $httpCode;
            $lastCurlError = $curlError;
            $lastResponse = $response;

            $bodySummary = $this->summarizeApiResponseError($response);
            error_log("[WhatsAppSend] Tentativa #{$attempt} falhou: HTTP {$httpCode}, cURL: {$curlError}, body: {$bodySummary}");
            
            // Não fazer retry para erros de client (4xx exceto 429)
            if ($httpCode >= 400 && $httpCode < 500 && $httpCode !== 429) {
                error_log("[WhatsAppSend] Erro de client ({$httpCode}) — abortando retry");
                break;
            }
        }
        
        // Falhou — salvar na fila de fallback se configurado
        if ($enqueueOnFail) {
            error_log("[WhatsAppSend] Todas tentativas falharam para {$phone}. Salvando na fila de fallback.");
            $this->enqueueFailedMessage(
                $companyId, $instanceName, $remoteJid, $message, $messageType,
                $lastHttpCode, $lastCurlError, $lastResponse, $maxAttempts
            );
        }
        
        $result['http_code'] = $lastHttpCode;
        $result['error'] = $lastCurlError ?: "HTTP {$lastHttpCode}";
        return $result;
    }
    
    /**
     * Envia mensagem simples sem retry nem fila (fire-and-forget com log).
     * Ideal para notificações de pedido que precisam de baixa latência.
     * 
     * @return array ['success' => bool, 'message_id' => ?string, 'http_code' => int]
     */
    public function sendOnce(
        int $companyId,
        string $instanceName,
        string $number,
        string $message,
        string $messageType = 'notification',
        array $options = []
    ): array {
        return $this->send($companyId, $instanceName, $number, $message, $messageType, array_merge($options, [
            'maxAttempts' => 1,
            'enqueueOnFail' => false,
            'checkTakeover' => false, // Notificações sempre passam
            'timeout' => $options['timeout'] ?? 10,
            'delay' => $options['delay'] ?? 0
        ]));
    }
    
    /**
     * Extrai message_id da resposta da Evolution API.
     * A Evolution API v2 retorna: {"key":{"remoteJid":"...","fromMe":true,"id":"BAABC123..."}}
     */
    private function extractMessageId(?string $apiResponse): ?string
    {
        if (empty($apiResponse)) {
            return null;
        }
        $decoded = json_decode($apiResponse, true);
        return $decoded['key']['id'] ?? null;
    }

    /**
     * Detecta erro de aplicação no body da API mesmo com HTTP 2xx.
     */
    private function hasApiResponseError(?string $apiResponse): bool
    {
        if (empty($apiResponse)) {
            return false;
        }

        $decoded = json_decode($apiResponse, true);
        if (!is_array($decoded)) {
            return false;
        }

        if (array_key_exists('success', $decoded) && $decoded['success'] === false) {
            return true;
        }

        $status = strtolower((string) ($decoded['status'] ?? ''));
        if (in_array($status, ['error', 'failed', 'failure'], true)) {
            return true;
        }

        if (!empty($decoded['error'])) {
            return true;
        }

        return false;
    }

    /**
     * Resumo compacto do erro retornado no body da API.
     */
    private function summarizeApiResponseError(?string $apiResponse): string
    {
        if (empty($apiResponse)) {
            return 'body vazio';
        }

        $decoded = json_decode($apiResponse, true);
        if (!is_array($decoded)) {
            return mb_substr($apiResponse, 0, 200);
        }

        $parts = [];

        if (isset($decoded['status'])) {
            $parts[] = 'status=' . (string) $decoded['status'];
        }
        if (isset($decoded['error'])) {
            $parts[] = 'error=' . (is_scalar($decoded['error']) ? (string) $decoded['error'] : json_encode($decoded['error']));
        }
        if (isset($decoded['message']) && is_scalar($decoded['message'])) {
            $parts[] = 'message=' . (string) $decoded['message'];
        }

        // Alguns providers retornam mensagem dentro de "response.message" (array/string).
        if (isset($decoded['response']['message'])) {
            $nestedMessage = $decoded['response']['message'];
            if (is_array($nestedMessage)) {
                $parts[] = 'response.message=' . implode('; ', array_map('strval', $nestedMessage));
            } elseif (is_scalar($nestedMessage)) {
                $parts[] = 'response.message=' . (string) $nestedMessage;
            }
        }

        if (empty($parts)) {
            return mb_substr($apiResponse, 0, 200);
        }

        return implode(' | ', $parts);
    }
    
    /**
     * Busca config WhatsApp de uma empresa (com cache em memória).
     */
    private array $companyConfigCache = [];

    private function getCompanyWhatsAppConfig(int $companyId): ?array
    {
        if (isset($this->companyConfigCache[$companyId])) {
            return $this->companyConfigCache[$companyId];
        }

        $columns = $this->getCompaniesColumns();
        $selectColumns = ['evolution_server_url', 'evolution_api_key'];

        if (!empty($columns['whatsapp_provider'])) {
            $selectColumns[] = 'whatsapp_provider';
        }
        if (!empty($columns['evogo_server_url'])) {
            $selectColumns[] = 'evogo_server_url';
        }
        if (!empty($columns['evogo_api_key'])) {
            $selectColumns[] = 'evogo_api_key';
        }

        $stmt = $this->db->prepare(
            'SELECT ' . implode(', ', $selectColumns) . ' FROM companies WHERE id = ?'
        );
        $stmt->execute([$companyId]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$company) {
            return null;
        }

        // Requisito mínimo para manter compatibilidade com o provedor padrão.
        if (empty($company['evolution_server_url']) || empty($company['evolution_api_key'])) {
            return null;
        }

        $this->companyConfigCache[$companyId] = $company;
        return $company;
    }

    /**
     * Retorna colunas existentes na tabela companies para manter compatibilidade
     * com ambientes onde a migração de schema ainda não foi aplicada.
     *
     * @return array<string,bool>
     */
    private function getCompaniesColumns(): array
    {
        if ($this->companiesColumnsCache !== null) {
            return $this->companiesColumnsCache;
        }

        $this->companiesColumnsCache = [];

        try {
            $stmt = $this->db->query('SHOW COLUMNS FROM companies');
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
                if (!empty($column['Field'])) {
                    $this->companiesColumnsCache[$column['Field']] = true;
                }
            }
        } catch (\Throwable $e) {
            error_log('[WhatsAppSend] Falha ao mapear colunas de companies: ' . $e->getMessage());
        }

        return $this->companiesColumnsCache;
    }

    /**
     * Resolve provider desejado usando override por opção e fallback seguro.
     */
    private function resolveProviderKey(array $company, ?string $providerOverride = null): string
    {
        $provider = strtolower(trim((string) ($providerOverride ?? ($company['whatsapp_provider'] ?? 'evolution'))));
        return $provider !== '' ? $provider : 'evolution';
    }

    /**
     * Instancia cliente do provider configurado.
     */
    private function createProviderClient(array $company, string $providerKey): ?WhatsAppProviderInterface
    {
        if ($providerKey === 'evogo') {
            $evogoServer = trim((string) ($company['evogo_server_url'] ?? ''));
            $evogoApiKey = trim((string) ($company['evogo_api_key'] ?? ''));

            if ($evogoServer !== '' && $evogoApiKey !== '') {
                return new EvoGoProvider($evogoServer, $evogoApiKey);
            }

            error_log('[WhatsAppSend] Provider evogo sem configuração completa. Fallback para evolution.');
        }

        $evolutionServer = trim((string) ($company['evolution_server_url'] ?? ''));
        $evolutionApiKey = trim((string) ($company['evolution_api_key'] ?? ''));
        if ($evolutionServer === '' || $evolutionApiKey === '') {
            return null;
        }

        return new EvolutionV2Provider($evolutionServer, $evolutionApiKey);
    }

    /**
     * Verifica se human takeover está ativo para este phone.
     */
    private function isHumanTakeoverActive(int $companyId, string $phone): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM whatsapp_human_takeover 
                WHERE company_id = ? AND phone = ? AND status = 'active' AND expires_at > NOW()
                LIMIT 1
            ");
            $stmt->execute([$companyId, $phone]);
            return (bool) $stmt->fetch();
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Verifica de forma concisa e encapsulada se o envio de 'engagement' é permitido.
     * Implementado como verificação mínima (enabled + horário) para proteger contra envios
     * indevidos quando o caller não forneceu contexto explícito.
     *
     * Fail-closed: retorna false em caso de erro.
     */
    private function isEngagementAllowed(int $companyId): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT enabled FROM customer_engagement_config WHERE company_id = ? LIMIT 1");
            $stmt->execute([$companyId]);
            $cfg = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$cfg || (int)$cfg['enabled'] !== 1) {
                return false;
            }

            $brasiliaTime = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
            $weekday = (int)$brasiliaTime->format('N');
            $currentTime = $brasiliaTime->format('H:i:s');

            $hoursStmt = $this->db->prepare("SELECT is_open, open1, close1, open2, close2 FROM company_hours WHERE company_id = ? AND weekday = ?");
            $hoursStmt->execute([$companyId, $weekday]);
            $hours = $hoursStmt->fetch(PDO::FETCH_ASSOC);
            if (!$hours || (int)$hours['is_open'] !== 1) {
                return false;
            }

            // check two intervals
            if (!empty($hours['open1']) && !empty($hours['close1'])) {
                $o = $hours['open1']; $c = $hours['close1'];
                if ($o <= $c) {
                    if ($currentTime >= $o && $currentTime <= $c) {
                        return true;
                    }
                } else {
                    if ($currentTime >= $o || $currentTime <= $c) {
                        return true;
                    }
                }
            }
            if (!empty($hours['open2']) && !empty($hours['close2'])) {
                $o = $hours['open2']; $c = $hours['close2'];
                if ($o <= $c) {
                    if ($currentTime >= $o && $currentTime <= $c) {
                        return true;
                    }
                } else {
                    if ($currentTime >= $o || $currentTime <= $c) {
                        return true;
                    }
                }
            }

            return false;
        } catch (\Throwable $e) {
            error_log("[WhatsAppSend] isEngagementAllowed failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log persistente de cada tentativa de envio.
     */
    private function logSendAttempt(
        int $companyId, string $instanceName, string $remoteJid, string $phone,
        string $messageType, string $message, int $attempt, string $status,
        int $httpCode, string $curlError, ?string $apiResponse, int $durationMs,
        ?string $sentMessageId = null
    ): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_send_log 
                    (company_id, instance_name, remote_jid, phone, message_type, message_preview,
                     attempt, status, http_code, curl_error, api_response, sent_message_id, duration_ms)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId,
                $instanceName,
                $remoteJid,
                $phone,
                $messageType,
                mb_substr($message, 0, 200),
                $attempt,
                $status,
                $httpCode ?: null,
                $curlError ?: null,
                $apiResponse ? mb_substr($apiResponse, 0, 2000) : null,
                $sentMessageId,
                $durationMs
            ]);
        } catch (\Throwable $e) {
            error_log("[WhatsAppSend] Falha ao gravar send_log: " . $e->getMessage());
        }
    }
    
    /**
     * Salva na fila de fallback para reprocessamento.
     */
    private function enqueueFailedMessage(
        int $companyId, string $instanceName, string $remoteJid, string $message,
        string $messageType, int $lastHttpCode, string $lastError, ?string $lastResponse, int $attemptsDone
    ): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_failed_queue 
                    (company_id, instance_name, remote_jid, message, message_type,
                     last_error, last_http_code, attempts_total, status, next_retry_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 5 MINUTE))
            ");
            $stmt->execute([
                $companyId,
                $instanceName,
                $remoteJid,
                $message,
                $messageType,
                mb_substr(($lastError ?: $lastResponse) ?: 'Unknown error', 0, 500),
                $lastHttpCode ?: null,
                $attemptsDone
            ]);
        } catch (\Throwable $e) {
            error_log("[WhatsAppSend] CRÍTICO — falha ao salvar na fila: " . $e->getMessage());
            error_log("[WhatsAppSend] Mensagem perdida: company={$companyId}, jid={$remoteJid}");
        }
    }
    
    /**
     * Limpeza periódica de logs antigos.
     * Chamado pelo cron de retry. Remove registros com mais de 30 dias.
     */
    public function cleanupOldLogs(int $retentionDays = 30): array
    {
        $result = ['send_log_deleted' => 0, 'failed_queue_deleted' => 0, 'takeover_deleted' => 0];
        
        try {
            // whatsapp_send_log — manter últimos N dias
            $stmt = $this->db->prepare("
                DELETE FROM whatsapp_send_log 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$retentionDays]);
            $result['send_log_deleted'] = $stmt->rowCount();
        } catch (\Throwable $e) {
            error_log("[WhatsAppSend] Cleanup send_log: " . $e->getMessage());
        }
        
        try {
            // whatsapp_failed_queue — limpar resolvidos (sent/abandoned) com mais de N dias
            $stmt = $this->db->prepare("
                DELETE FROM whatsapp_failed_queue 
                WHERE status IN ('sent', 'abandoned') 
                    AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$retentionDays]);
            $result['failed_queue_deleted'] = $stmt->rowCount();
        } catch (\Throwable $e) {
            error_log("[WhatsAppSend] Cleanup failed_queue: " . $e->getMessage());
        }
        
        try {
            // whatsapp_human_takeover — limpar expired/released com mais de N dias
            $stmt = $this->db->prepare("
                DELETE FROM whatsapp_human_takeover 
                WHERE status IN ('expired', 'released') 
                    AND activated_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$retentionDays]);
            $result['takeover_deleted'] = $stmt->rowCount();
        } catch (\Throwable $e) {
            error_log("[WhatsAppSend] Cleanup takeover: " . $e->getMessage());
        }
        
        return $result;
    }

    /**
     * Reprocessa mensagens pendentes da whatsapp_failed_queue para uma empresa.
     *
     * Este método existe para auto-recuperação quando o cron de retry está
     * indisponível temporariamente.
     */
    public function processDueFailedQueue(int $companyId, int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        $result = ['processed' => 0, 'sent' => 0, 'failed' => 0, 'abandoned' => 0];

        try {
            $stmt = $this->db->prepare("\n                SELECT id, company_id, instance_name, remote_jid, message, message_type,\n                       retry_count, max_retries\n                FROM whatsapp_failed_queue\n                WHERE company_id = ?\n                  AND status = 'pending'\n                  AND next_retry_at <= NOW()\n                ORDER BY next_retry_at ASC\n                LIMIT {$limit}\n            ");
            $stmt->execute([$companyId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                return $result;
            }

            $ids = array_map(static fn(array $row): int => (int)$row['id'], $rows);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $lockStmt = $this->db->prepare("UPDATE whatsapp_failed_queue SET status = 'retrying' WHERE id IN ({$placeholders})");
            $lockStmt->execute($ids);

            foreach ($rows as $row) {
                $result['processed']++;

                $queueId = (int)$row['id'];
                $retryCount = (int)$row['retry_count'];
                $maxRetries = (int)$row['max_retries'];

                $sendResult = $this->send(
                    (int)$row['company_id'],
                    (string)$row['instance_name'],
                    (string)$row['remote_jid'],
                    (string)$row['message'],
                    (string)$row['message_type'],
                    [
                        'maxAttempts' => 1,
                        'enqueueOnFail' => false,
                        'checkRateLimit' => false,
                        'checkTakeover' => ((string)$row['message_type']) !== 'notification',
                        'delay' => 0,
                    ]
                );

                if (!empty($sendResult['success'])) {
                    $okStmt = $this->db->prepare("\n                        UPDATE whatsapp_failed_queue\n                        SET status = 'sent', updated_at = NOW()\n                        WHERE id = ?\n                    ");
                    $okStmt->execute([$queueId]);
                    $result['sent']++;
                    continue;
                }

                $newRetryCount = $retryCount + 1;
                $lastError = mb_substr((string)($sendResult['error'] ?? 'retry_failed'), 0, 500);
                $lastHttpCode = isset($sendResult['http_code']) ? (int)$sendResult['http_code'] : null;

                if ($newRetryCount >= $maxRetries) {
                    $failStmt = $this->db->prepare("\n                        UPDATE whatsapp_failed_queue\n                        SET status = 'abandoned', retry_count = ?, last_error = ?, last_http_code = ?, updated_at = NOW()\n                        WHERE id = ?\n                    ");
                    $failStmt->execute([$newRetryCount, $lastError, $lastHttpCode ?: null, $queueId]);
                    $result['abandoned']++;
                } else {
                    $backoffMinutes = [10, 30, 60][$newRetryCount - 1] ?? 60;
                    $retryStmt = $this->db->prepare("\n                        UPDATE whatsapp_failed_queue\n                        SET status = 'pending', retry_count = ?, last_error = ?, last_http_code = ?,\n                            next_retry_at = DATE_ADD(NOW(), INTERVAL ? MINUTE), updated_at = NOW()\n                        WHERE id = ?\n                    ");
                    $retryStmt->execute([$newRetryCount, $lastError, $lastHttpCode ?: null, $backoffMinutes, $queueId]);
                    $result['failed']++;
                }
            }
        } catch (\Throwable $e) {
            error_log('[WhatsAppSend] Erro ao processar fila pendente inline: ' . $e->getMessage());
        }

        return $result;
    }
    
    // ========================================================================
    // Rate Limiting
    // ========================================================================
    
    /**
     * Verifica rate limit por empresa.
     * Conta chamadas recentes no whatsapp_send_log para evitar flood na Evolution API.
     * 
     * @return string|null Mensagem de erro se excedeu, null se dentro dos limites
     */
    private function checkRateLimit(int $companyId): ?string
    {
        try {
            // Per-minute check (proteção contra burst)
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM whatsapp_send_log 
                WHERE company_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
            ");
            $stmt->execute([$companyId]);
            $perMinute = (int) $stmt->fetchColumn();
            
            if ($perMinute >= self::RATE_LIMIT_PER_MINUTE) {
                return "rate_limit_per_minute ({$perMinute}/" . self::RATE_LIMIT_PER_MINUTE . ")";
            }
            
            // Per-hour check (proteção contra volume sustentado)
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM whatsapp_send_log 
                WHERE company_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute([$companyId]);
            $perHour = (int) $stmt->fetchColumn();
            
            if ($perHour >= self::RATE_LIMIT_PER_HOUR) {
                return "rate_limit_per_hour ({$perHour}/" . self::RATE_LIMIT_PER_HOUR . ")";
            }
            
            return null; // dentro dos limites
        } catch (\Throwable $e) {
            // Fail open — se não conseguir verificar, permite envio
            error_log("[WhatsAppSend] Erro ao verificar rate limit: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Retorna uso atual do rate limit para uma empresa.
     * Útil para dashboards e debug.
     */
    public function getRateLimitStatus(int $companyId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM whatsapp_send_log 
                WHERE company_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
            ");
            $stmt->execute([$companyId]);
            $perMinute = (int) $stmt->fetchColumn();
            
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM whatsapp_send_log 
                WHERE company_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute([$companyId]);
            $perHour = (int) $stmt->fetchColumn();
            
            return [
                'per_minute' => ['used' => $perMinute, 'limit' => self::RATE_LIMIT_PER_MINUTE, 'remaining' => max(0, self::RATE_LIMIT_PER_MINUTE - $perMinute)],
                'per_hour'   => ['used' => $perHour,   'limit' => self::RATE_LIMIT_PER_HOUR, 'remaining' => max(0, self::RATE_LIMIT_PER_HOUR - $perHour)],
            ];
        } catch (\Throwable $e) {
            return [
                'per_minute' => ['used' => 0, 'limit' => self::RATE_LIMIT_PER_MINUTE, 'remaining' => self::RATE_LIMIT_PER_MINUTE, 'error' => $e->getMessage()],
                'per_hour'   => ['used' => 0, 'limit' => self::RATE_LIMIT_PER_HOUR, 'remaining' => self::RATE_LIMIT_PER_HOUR, 'error' => $e->getMessage()],
            ];
        }
    }
    
    // ========================================================================
    // Monitoramento Centralizado
    // ========================================================================
    
    /**
     * Estatísticas de envio por empresa e período.
     * Agrupa por message_type e status com métricas de performance.
     * 
     * @param int    $companyId  ID da empresa (0 = todas)
     * @param string $period     Período: '1h', '24h', '7d', '30d'
     * @return array Breakdown por tipo e status
     */
    public function getStats(int $companyId = 0, string $period = '24h'): array
    {
        $intervals = ['1h' => '1 HOUR', '24h' => '24 HOUR', '7d' => '7 DAY', '30d' => '30 DAY'];
        $interval = $intervals[$period] ?? '24 HOUR';
        
        try {
            // Breakdown por tipo e status
            $companyFilter = $companyId > 0 ? 'AND company_id = ?' : '';
            $params = $companyId > 0 ? [$companyId] : [];
            
            $stmt = $this->db->prepare("
                SELECT 
                    message_type,
                    status,
                    COUNT(*) as total,
                    ROUND(AVG(duration_ms)) as avg_duration_ms,
                    MAX(duration_ms) as max_duration_ms,
                    MIN(created_at) as first_at,
                    MAX(created_at) as last_at
                FROM whatsapp_send_log 
                WHERE created_at > DATE_SUB(NOW(), INTERVAL {$interval})
                    {$companyFilter}
                GROUP BY message_type, status
                ORDER BY message_type, status
            ");
            $stmt->execute($params);
            $breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Totais agregados
            $totalSent = 0;
            $totalSuccess = 0;
            $totalFailed = 0;
            foreach ($breakdown as $row) {
                $totalSent += (int) $row['total'];
                if ($row['status'] === 'success') {
                    $totalSuccess += (int) $row['total'];
                } elseif ($row['status'] === 'failed') {
                    $totalFailed += (int) $row['total'];
                }
            }
            
            // Fila de pendentes
            $queueStmt = $this->db->prepare("
                SELECT status, COUNT(*) as total 
                FROM whatsapp_failed_queue 
                WHERE 1=1 {$companyFilter}
                GROUP BY status
            ");
            $queueStmt->execute($params);
            $queueStatus = [];
            foreach ($queueStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $queueStatus[$row['status']] = (int) $row['total'];
            }
            
            return [
                'period' => $period,
                'company_id' => $companyId ?: 'all',
                'totals' => [
                    'attempts' => $totalSent,
                    'success' => $totalSuccess,
                    'failed' => $totalFailed,
                    'success_rate' => $totalSent > 0 ? round(($totalSuccess / $totalSent) * 100, 1) : 0,
                ],
                'breakdown' => $breakdown,
                'queue' => $queueStatus,
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage(), 'period' => $period, 'company_id' => $companyId ?: 'all'];
        }
    }
    
    /**
     * Health check rápido do sistema de envio.
     * Retorna status geral baseado na taxa de sucesso recente e fila de pendentes.
     * 
     * @param int $companyId ID da empresa (0 = sistema inteiro)
     * @return array ['status' => 'healthy'|'degraded'|'critical', ...]
     */
    public function getHealthStatus(int $companyId = 0): array
    {
        try {
            $companyFilter = $companyId > 0 ? 'AND company_id = ?' : '';
            $params = $companyId > 0 ? [$companyId] : [];
            
            // Taxa de sucesso nos últimos 15 minutos
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count,
                    ROUND(AVG(duration_ms)) as avg_duration_ms
                FROM whatsapp_send_log 
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
                    {$companyFilter}
            ");
            $stmt->execute($params);
            $recent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $total = (int) ($recent['total'] ?? 0);
            $successCount = (int) ($recent['success_count'] ?? 0);
            $failedCount = (int) ($recent['failed_count'] ?? 0);
            $avgDuration = (int) ($recent['avg_duration_ms'] ?? 0);
            $successRate = $total > 0 ? round(($successCount / $total) * 100, 1) : 100;
            
            // Pendentes na fila de fallback
            $queueStmt = $this->db->prepare("
                SELECT COUNT(*) FROM whatsapp_failed_queue 
                WHERE status = 'pending' {$companyFilter}
            ");
            $queueStmt->execute($params);
            $pendingQueue = (int) $queueStmt->fetchColumn();
            
            // Takeovers ativos
            $takeoverStmt = $this->db->prepare("
                SELECT COUNT(*) FROM whatsapp_human_takeover 
                WHERE status = 'active' AND expires_at > NOW() {$companyFilter}
            ");
            $takeoverStmt->execute($params);
            $activeTakeovers = (int) $takeoverStmt->fetchColumn();
            
            // Determinar status geral
            $status = 'healthy';
            $issues = [];
            
            if ($total > 5 && $successRate < 50) {
                $status = 'critical';
                $issues[] = "Taxa de sucesso crítica: {$successRate}%";
            } elseif ($total > 5 && $successRate < 80) {
                $status = 'degraded';
                $issues[] = "Taxa de sucesso degradada: {$successRate}%";
            }
            
            if ($pendingQueue > 20) {
                $status = $status === 'critical' ? 'critical' : 'degraded';
                $issues[] = "Fila de pendentes elevada: {$pendingQueue}";
            }
            
            if ($avgDuration > 10000) {
                $status = $status === 'critical' ? 'critical' : 'degraded';
                $issues[] = "Latência alta: {$avgDuration}ms";
            }
            
            return [
                'status' => $status,
                'checked_at' => date('Y-m-d H:i:s'),
                'company_id' => $companyId ?: 'all',
                'last_15min' => [
                    'total_attempts' => $total,
                    'success' => $successCount,
                    'failed' => $failedCount,
                    'success_rate' => $successRate,
                    'avg_duration_ms' => $avgDuration,
                ],
                'pending_queue' => $pendingQueue,
                'active_takeovers' => $activeTakeovers,
                'issues' => $issues,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'unknown',
                'error' => $e->getMessage(),
                'checked_at' => date('Y-m-d H:i:s'),
            ];
        }
    }
}
