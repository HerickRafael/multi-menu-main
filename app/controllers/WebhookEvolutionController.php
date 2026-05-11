<?php
/**
 * WebhookEvolutionController
 * 
 * Controller para receber webhooks da Evolution API
 * Processa mensagens recebidas para verificação de engajamento
 * Envia resposta automática fora do expediente
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/WhatsAppSendService.php';

class WebhookEvolutionController
{
    private PDO $db;
    private WhatsAppSendService $sendService;
    
    // Cache para evitar múltiplas respostas ao mesmo cliente em pouco tempo
    private const RESPONSE_COOLDOWN_MINUTES = 30;
    
    // Tempo (minutos) sem atividade humana para liberar automação
    private const HUMAN_TAKEOVER_EXPIRY_MINUTES = 30;
    
    public function __construct()
    {
        $this->db = db();
        $this->sendService = new WhatsAppSendService($this->db);
    }
    
    /**
     * Recebe webhook de mensagens da Evolution API
     * Endpoint: POST /webhook/evolution/{instanceName}
     */
    public function messages(array $params): void
    {
        \App\Middleware\WebhookGate::requireInboundWebhookSecret('WEBHOOK_EVOLUTION_SECRET');

        $instanceName = $params['instanceName'] ?? '';

        $rawInput = file_get_contents('php://input');
        error_log('[Webhook Evolution] Recebido instância=' . $instanceName . ' bytes=' . strlen($rawInput));
        
        // Parse do payload
        $payload = json_decode($rawInput, true);
        
        if (!$payload) {
            error_log("[Webhook Evolution] Payload inválido ou vazio");
            $this->jsonResponse(['status' => 'error', 'message' => 'Invalid payload'], 400);
            return;
        }
        
        // Identificar tipo de evento (aceitar variações entre versões da Evolution)
        $event = (string)($payload['event'] ?? '');
        $normalizedEvent = strtolower(str_replace(['_', '-'], '.', trim($event)));
        
        // GUARD: Bloquear MESSAGES_SET — dump de histórico ao reconectar.
        if (in_array($normalizedEvent, ['messages.set', 'message.set'], true)) {
            error_log("[Webhook Evolution] MESSAGES_SET recebido — ignorando dump de histórico");
            $this->jsonResponse(['status' => 'ok']);
            return;
        }
        
        $hasMessageShape = isset($payload['data']['key']) && isset($payload['data']['message']);
        $isMessageUpsert = in_array($normalizedEvent, ['messages.upsert', 'message.upsert'], true) || ($event === '' && $hasMessageShape);

        if (!$isMessageUpsert) {
            error_log("[Webhook Evolution] Evento ignorado: '{$event}' (normalizado: '{$normalizedEvent}')");
            $this->jsonResponse(['status' => 'ok']);
            return;
        }
        
        $data = $payload['data'] ?? [];
        $key = $data['key'] ?? [];
        $fromMeRaw = $key['fromMe'] ?? false;
        $fromMe = $this->toBool($fromMeRaw);
        
        // GUARD: fromMe — apenas takeover, sem processamento.
        if ($fromMe) {
            $this->handleHumanMessage($instanceName, $payload);
            $this->jsonResponse(['status' => 'ok']);
            return;
        }
        
        // IDEMPOTÊNCIA INSERT-first: banco decide, não PHP.
        $messageId = $key['id'] ?? '';
        if (!empty($messageId) && !str_starts_with($messageId, 'noid_')) {
            if (!$this->claimMessageForProcessing($instanceName, $messageId)) {
                error_log("[Webhook Evolution] IDEMPOTÊNCIA: message_id={$messageId} já reservado — ignorando duplicata");
                $this->jsonResponse(['status' => 'ok']);
                return;
            }
        }
        
        // =====================================================================
        // ARQUITETURA ASSÍNCRONA: Recepção ≠ Processamento
        // 
        // O webhook APENAS salva na fila e responde 200.
        // Processamento pesado (LID resolution, envio de msg, queries) 
        // roda em worker separado. Isso garante:
        //   1. Resposta < 50ms para Evolution (sem retry)
        //   2. Apache não trava com processamento pesado
        //   3. Sem dependência de flush/buffer do Traefik
        //   4. Se o PHP crashar, a mensagem está salva na fila
        // =====================================================================
        $queued = $this->enqueueWebhook($instanceName, $messageId, $rawInput);
        
        if ($queued) {
            error_log("[Webhook Evolution] Mensagem enfileirada: {$messageId} para {$instanceName}");
        } else {
            // NÃO processar inline — reintroduz o bug original (timeout + retry).
            // Se enqueue falhou, o claim de idempotência já foi feito.
            // A mensagem será perdida, mas é melhor que travar o Apache.
            error_log("[Webhook Evolution] CRITICAL: enqueue falhou para {$messageId} — mensagem PERDIDA (banco indisponível?)");
        }
        
        $this->jsonResponse(['status' => 'ok']);
    }
    
    /**
     * Worker: Processa mensagens da fila em loop contínuo.
     * Roda por ~55s (cron a cada minuto), polling a cada 2s quando fila vazia.
     * Drena toda a fila disponível (sem batch fixo).
     * Endpoint: POST /webhook/evolution-worker
     */
    public function processQueue(array $params = []): void
    {
        \App\Middleware\WebhookGate::requireHeaderSecret('WEBHOOK_INTERNAL_SECRET', 'HTTP_X_WEBHOOK_INTERNAL');

        $processed = 0;
        $errors = 0;
        $skipped = 0;
        $startTime = microtime(true);
        $maxRunSeconds = 55;
        $pollIntervalUs = 2_000_000; // 2s entre polls quando fila vazia
        
        // BACKPRESSURE ADAPTATIVO COM HISTERESE:
        // - Base: 200 por ciclo (modo normal)
        // - Throttle ON: p95 > 8s OU error_rate > 30% OU queue growth > 1.5x
        // - Throttle OFF: p95 < 3s E error_rate < 15% E queue estável
        // - Decision cooldown: uma vez throttled, mantém por ≥10s
        $maxPerCycle = 200;
        $maxPerInstance = 50;          // Anti-starvation: cap por instância/ciclo
        $maxInstanceTimeSeconds = 8.0; // Fairness por tempo de CPU por instância/ciclo
        $errorCooldownUs = 1_000_000; // 1s após erro retryable
        $processingTimes = [];         // Latência de processamento por item
        
        // HISTERESE: thresholds separados para ON e OFF (evita oscillation)
        $isThrottled = false;           // Estado atual de throttle
        $throttleOnAt = 0.0;            // Timestamp de quando throttle foi ativado
        $throttleMinHoldSeconds = 10.0; // Cooldown mínimo: não reavalia por 10s
        // ON thresholds (ativar throttle)
        $onErrorRate = 0.30;      // 30%
        $onP95Latency = 8.0;      // 8s
        // OFF thresholds (desativar throttle) — significativamente menores
        $offErrorRate = 0.15;     // 15%
        $offP95Latency = 3.0;     // 3s
        
        try {
            $this->ensureQueueTable();
            
            // COLD START WARM-UP: carregar métricas recentes do banco.
            // Evita decisões cegas nos primeiros segundos do ciclo.
            // Sem isso, p95/backpressure fica cego até acumular 10+ amostras in-memory.
            $warmupStmt = $this->db->query("
                SELECT TIMESTAMPDIFF(SECOND, started_at, completed_at) as secs
                FROM whatsapp_webhook_queue
                WHERE status = 'done' AND started_at IS NOT NULL AND completed_at IS NOT NULL
                AND completed_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                ORDER BY completed_at DESC
                LIMIT 50
            ");
            foreach ($warmupStmt->fetchAll(\PDO::FETCH_COLUMN) as $sec) {
                $processingTimes[] = max(0.0, (float)$sec);
            }
            if (!empty($processingTimes)) {
                error_log("[Webhook Worker] WARM_UP: carregou " . count($processingTimes) . " amostras recentes (p95=" . round($this->calculatePercentile($processingTimes, 95), 1) . "s)");
            }
            
            // ADVISORY LOCK PER-INSTANCE: permite workers paralelos por tenant.
            // Busca instâncias com itens pendentes e tenta lock para cada uma.
            // Se outro worker já processa essa instância, pula para a próxima.
            $instanceStmt = $this->db->query("
                SELECT DISTINCT instance_name 
                FROM whatsapp_webhook_queue 
                WHERE status = 'pending'
            ");
            $instances = $instanceStmt->fetchAll(\PDO::FETCH_COLUMN);
            
            if (empty($instances)) {
                // Nada pendente — mas vamos ficar em loop polling
                $instances = ['__poll__'];
            }
            
            $acquiredLocks = [];
            $lockedInstanceNames = [];
            foreach ($instances as $inst) {
                if ($inst === '__poll__') {
                    $lockName = 'wh_worker_global';
                } else {
                    $lockName = 'wh_worker_' . $inst;
                }
                $lockStmt = $this->db->prepare("SELECT GET_LOCK(?, 0) as acquired");
                $lockStmt->execute([$lockName]);
                $lockResult = $lockStmt->fetch(\PDO::FETCH_ASSOC);
                if ($lockResult && (int)$lockResult['acquired'] === 1) {
                    $acquiredLocks[] = $lockName;
                    if ($inst !== '__poll__') {
                        $lockedInstanceNames[] = $inst;
                    }
                }
            }
            
            if (empty($acquiredLocks)) {
                $this->jsonResponse([
                    'status' => 'skipped',
                    'reason' => 'all_instances_locked'
                ]);
                return;
            }
            
            // STUCK RECOVERY COM HEARTBEAT:
            // Só considera stuck se heartbeat_at parou de atualizar > 3min.
            // Se worker crashou: heartbeat para → item volta para pending.
            // Se worker está lento: heartbeat continua → item NÃO volta (sem duplicação).
            $this->db->exec("
                UPDATE whatsapp_webhook_queue 
                SET status = 'pending' 
                WHERE status = 'processing' 
                AND heartbeat_at < DATE_SUB(NOW(), INTERVAL 3 MINUTE)
            ");
            
            // Snapshot para métrica de queue growth com suavização (janela 60s)
            $initialPending = (int)$this->db->query(
                "SELECT COUNT(*) FROM whatsapp_webhook_queue WHERE status = 'pending'"
            )->fetchColumn();
            $lastGrowthCheck = $startTime;
            $growthSamples = [
                ['ts' => $startTime, 'pending' => $initialPending]
            ];
            
            // ROUND-ROBIN: contadores e orçamento por instância
            $instanceIndex = 0;
            $instanceCounts = [];
            $instanceTimeSpent = [];
            
            while ($processed + $skipped < $maxPerCycle) {
                // Timeout
                if ((microtime(true) - $startTime) > $maxRunSeconds) {
                    break;
                }
                
                // BACKPRESSURE COM HISTERESE + DECISION COOLDOWN
                // Sistema dinâmico com feedback — evita oscillation via:
                //   1. Thresholds separados para ON/OFF (histerese)
                //   2. Cooldown mínimo de 10s após ativar throttle
                //   3. Log estruturado de cada transição de estado
                $totalAttempted = $processed + $errors + $skipped;
                if ($totalAttempted >= 10) {
                    // Coletar sinais atuais
                    $errorRate = ($errors + $skipped) / $totalAttempted;
                    $p95Latency = (count($processingTimes) >= 10)
                        ? $this->calculatePercentile($processingTimes, 95)
                        : null;
                    $queueGrowing = false;
                    
                    // Signal: Queue growth (janela 60s com baseline 30s e média móvel)
                    $now = microtime(true);
                    if ($now - $lastGrowthCheck > 10) {
                        $currentPending = (int)$this->db->query(
                            "SELECT COUNT(*) FROM whatsapp_webhook_queue WHERE status = 'pending'"
                        )->fetchColumn();
                        $lastGrowthCheck = $now;

                        $growthSamples[] = ['ts' => $now, 'pending' => $currentPending];
                        $growthSamples = array_values(array_filter(
                            $growthSamples,
                            static fn(array $sample): bool => ($now - (float)$sample['ts']) <= 60
                        ));

                        $baselineSample = $growthSamples[0];
                        $windowSeconds = $now - (float)$baselineSample['ts'];
                        $windowAverage = array_sum(array_column($growthSamples, 'pending')) / max(count($growthSamples), 1);

                        if (
                            $windowSeconds >= 30
                            && (int)$baselineSample['pending'] > 0
                            && $currentPending > ((int)$baselineSample['pending'] * 1.5)
                            && $currentPending > ($windowAverage * 1.3)
                            && $currentPending > 20
                        ) {
                            $queueGrowing = true;
                        }
                    }
                    
                    // HISTERESE: decisão baseada no estado atual
                    $nowTs = microtime(true);
                    $bpReasons = [];
                    
                    if (!$isThrottled) {
                        // === MODO NORMAL → avaliar se deve ATIVAR throttle ===
                        $shouldActivate = false;
                        
                        if ($errorRate > $onErrorRate) {
                            $shouldActivate = true;
                            $bpReasons[] = 'error_rate=' . round($errorRate, 2) . '>=' . $onErrorRate;
                        }
                        if ($p95Latency !== null && $p95Latency > $onP95Latency) {
                            $shouldActivate = true;
                            $bpReasons[] = 'p95_latency=' . round($p95Latency, 1) . 's>=' . $onP95Latency . 's';
                        }
                        if ($queueGrowing) {
                            $shouldActivate = true;
                            $bpReasons[] = "queue_growth=" . (int)($baselineSample['pending'] ?? 0) . "->" . ($currentPending ?? '?');
                        }
                        
                        if ($shouldActivate) {
                            $isThrottled = true;
                            $throttleOnAt = $nowTs;
                            $maxPerCycle = 50;
                            $maxPerInstance = 15;
                            $maxInstanceTimeSeconds = 3.0;
                            
                            error_log("[Webhook Worker] SCHEDULER_DECISION: " . json_encode([
                                'decision' => 'throttle_on',
                                'transition' => 'normal→throttled',
                                'reasons' => $bpReasons,
                                'signals' => [
                                    'error_rate' => round($errorRate, 3),
                                    'p95_latency' => $p95Latency !== null ? round($p95Latency, 1) : null,
                                    'queue_growing' => $queueGrowing,
                                    'pending' => $currentPending ?? $initialPending,
                                ],
                                'thresholds' => [
                                    'on' => ['error_rate' => $onErrorRate, 'p95' => $onP95Latency],
                                    'off' => ['error_rate' => $offErrorRate, 'p95' => $offP95Latency],
                                ],
                                'caps' => ['max_cycle' => 50, 'max_instance' => 15, 'max_time_s' => 3.0],
                            ], JSON_UNESCAPED_SLASHES));
                        }
                    } else {
                        // === MODO THROTTLED → avaliar se pode DESATIVAR ===
                        // Decision cooldown: manter throttle por mínimo $throttleMinHoldSeconds
                        $holdElapsed = $nowTs - $throttleOnAt;
                        
                        if ($holdElapsed >= $throttleMinHoldSeconds) {
                            // Verificar se TODOS os sinais estão abaixo dos thresholds OFF
                            $canRelease = true;
                            $releaseReasons = [];
                            
                            if ($errorRate > $offErrorRate) {
                                $canRelease = false;
                                $releaseReasons[] = 'error_rate=' . round($errorRate, 2) . '>' . $offErrorRate;
                            }
                            if ($p95Latency !== null && $p95Latency > $offP95Latency) {
                                $canRelease = false;
                                $releaseReasons[] = 'p95=' . round($p95Latency, 1) . 's>' . $offP95Latency . 's';
                            }
                            if ($queueGrowing) {
                                $canRelease = false;
                                $releaseReasons[] = 'queue_still_growing';
                            }
                            
                            if ($canRelease) {
                                $isThrottled = false;
                                $maxPerCycle = 200;
                                $maxPerInstance = 50;
                                $maxInstanceTimeSeconds = 8.0;
                                
                                error_log("[Webhook Worker] SCHEDULER_DECISION: " . json_encode([
                                    'decision' => 'throttle_off',
                                    'transition' => 'throttled→normal',
                                    'held_seconds' => round($holdElapsed, 1),
                                    'signals' => [
                                        'error_rate' => round($errorRate, 3),
                                        'p95_latency' => $p95Latency !== null ? round($p95Latency, 1) : null,
                                        'queue_growing' => false,
                                        'pending' => $currentPending ?? $initialPending,
                                    ],
                                ], JSON_UNESCAPED_SLASHES));
                            }
                            // Se não pode liberar, continua throttled silenciosamente
                        }
                    }
                    
                    // Se throttled e já passou do cap, parar
                    if ($isThrottled && ($processed + $skipped >= $maxPerCycle)) {
                        break;
                    }
                }
                
                // ROUND-ROBIN: selecionar próxima instância que não estourou cap
                if (empty($lockedInstanceNames)) {
                    usleep($pollIntervalUs);
                    $newInsts = $this->db->query(
                        "SELECT DISTINCT instance_name FROM whatsapp_webhook_queue WHERE status = 'pending'"
                    )->fetchAll(\PDO::FETCH_COLUMN);
                    foreach ($newInsts as $newInst) {
                        if (!in_array($newInst, $lockedInstanceNames, true)) {
                            $lk = 'wh_worker_' . $newInst;
                            $ls = $this->db->prepare("SELECT GET_LOCK(?, 0) as acquired");
                            $ls->execute([$lk]);
                            $lr = $ls->fetch(\PDO::FETCH_ASSOC);
                            if ($lr && (int)$lr['acquired'] === 1) {
                                $acquiredLocks[] = $lk;
                                $lockedInstanceNames[] = $newInst;
                            }
                        }
                    }
                    continue;
                }
                
                $tried = 0;
                $currentInstance = null;
                while ($tried < count($lockedInstanceNames)) {
                    $candidate = $lockedInstanceNames[$instanceIndex % count($lockedInstanceNames)];
                    $instanceIndex++;
                    $tried++;
                    $countUnderCap = ($instanceCounts[$candidate] ?? 0) < $maxPerInstance;
                    $timeUnderCap = ($instanceTimeSpent[$candidate] ?? 0.0) < $maxInstanceTimeSeconds;
                    if ($countUnderCap && $timeUnderCap) {
                        $currentInstance = $candidate;
                        break;
                    }
                }
                
                if ($currentInstance === null) {
                    break; // Todas instâncias no cap
                }
                
                // Pegar próximo item DESTA instância (por prioridade)
                $this->db->beginTransaction();
                
                $stmt = $this->db->prepare("
                    SELECT id, instance_name, payload, attempts 
                    FROM whatsapp_webhook_queue 
                    WHERE status = 'pending' AND instance_name = ?
                    ORDER BY GREATEST(priority - FLOOR(TIMESTAMPDIFF(MINUTE, created_at, NOW()) / 10), CEIL(priority / 3)) ASC, created_at ASC
                    LIMIT 1
                    FOR UPDATE SKIP LOCKED
                ");
                $stmt->execute([$currentInstance]);
                $item = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if (!$item) {
                    $this->db->commit();
                    // Instância esgotada — remover da rotação
                    $lockedInstanceNames = array_values(
                        array_filter($lockedInstanceNames, fn($i) => $i !== $currentInstance)
                    );
                    $instanceIndex = 0;
                    continue;
                }
                
                $instanceCounts[$currentInstance] = ($instanceCounts[$currentInstance] ?? 0) + 1;
                
                // Marcar como processando + heartbeat inicial
                $updateStmt = $this->db->prepare("
                    UPDATE whatsapp_webhook_queue 
                    SET status = 'processing', 
                        attempts = attempts + 1,
                        started_at = NOW(),
                        heartbeat_at = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([$item['id']]);
                $this->db->commit();
                
                // Processar fora da transação (com tracking de latência)
                $itemStart = microtime(true);
                try {
                    $payload = json_decode($item['payload'], true);
                    
                    if (!$payload) {
                        throw new \RuntimeException('Payload JSON inválido na fila');
                    }
                    
                    // HEARTBEAT: atualizar antes do processamento pesado
                    $this->updateHeartbeat($item['id']);
                    
                    $this->processMessageUpsert($item['instance_name'], $payload, (int)$item['id']);
                    
                    // Sucesso
                    $doneStmt = $this->db->prepare("
                        UPDATE whatsapp_webhook_queue 
                        SET status = 'done', completed_at = NOW(), heartbeat_at = NOW()
                        WHERE id = ?
                    ");
                    $doneStmt->execute([$item['id']]);
                    $processed++;
                    $processingTimes[] = microtime(true) - $itemStart;
                    
                } catch (\Throwable $e) {
                    $msg = $e->getMessage();
                    $currentAttempts = ($item['attempts'] ?? 0) + 1;
                    $maxRetries = 3;
                    $isRetryable = $this->isRetryableError($e);
                    
                    if (!$isRetryable) {
                        $failStmt = $this->db->prepare("
                            UPDATE whatsapp_webhook_queue 
                            SET status = 'dlq', last_error = ?, heartbeat_at = NOW()
                            WHERE id = ?
                        ");
                        $failStmt->execute([substr("[NON-RETRYABLE] {$msg}", 0, 500), $item['id']]);
                        error_log("[Webhook Worker] DLQ(non-retryable) #{$item['id']}: {$msg}");
                        $skipped++;
                    } elseif ($currentAttempts >= $maxRetries) {
                        $failStmt = $this->db->prepare("
                            UPDATE whatsapp_webhook_queue 
                            SET status = 'dlq', last_error = ?, heartbeat_at = NOW()
                            WHERE id = ?
                        ");
                        $failStmt->execute([substr("[MAX_RETRIES:{$currentAttempts}] {$msg}", 0, 500), $item['id']]);
                        error_log("[Webhook Worker] DLQ(max-retries) #{$item['id']} ({$currentAttempts}x): {$msg}");
                        $errors++;
                    } else {
                        $failStmt = $this->db->prepare("
                            UPDATE whatsapp_webhook_queue 
                            SET status = 'pending', last_error = ?, heartbeat_at = NOW()
                            WHERE id = ?
                        ");
                        $failStmt->execute([substr("[RETRY {$currentAttempts}/{$maxRetries}] {$msg}", 0, 500), $item['id']]);
                        error_log("[Webhook Worker] RETRY #{$item['id']} ({$currentAttempts}/{$maxRetries}): {$msg}");
                        $errors++;
                        // COOLDOWN após erro retryable — evita bombardear API/DB instável
                        usleep($errorCooldownUs);
                    }
                    $processingTimes[] = microtime(true) - $itemStart;
                }
                $instanceTimeSpent[$currentInstance] = ($instanceTimeSpent[$currentInstance] ?? 0.0) + (microtime(true) - $itemStart);
            }
            
            // ARQUIVAR ANTES DE DELETAR: preserva evidência para debug
            $this->archiveAndCleanup();
            
            // Liberar todos os advisory locks adquiridos
            foreach ($acquiredLocks as $lockName) {
                $this->db->prepare("SELECT RELEASE_LOCK(?)")->execute([$lockName]);
            }
            
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            // Liberar locks em erro fatal
            foreach ($acquiredLocks ?? [] as $lockName) {
                try { $this->db->prepare("SELECT RELEASE_LOCK(?)")->execute([$lockName]); } catch (\Throwable $ignored) {}
            }
            error_log("[Webhook Worker] Erro fatal: " . $e->getMessage());
        }
        
        $elapsed = round((microtime(true) - $startTime) * 1000);
        
        if ($processed > 0 || $errors > 0 || $skipped > 0) {
            // STRUCTURED CYCLE SUMMARY: visão completa de cada ciclo para análise
            error_log("[Webhook Worker] CYCLE_SUMMARY: " . json_encode([
                'processed' => $processed,
                'retries' => $errors,
                'dlq' => $skipped,
                'elapsed_ms' => $elapsed,
                'instances' => $instanceCounts ?? [],
                'instance_time_s' => array_map(fn($t) => round($t, 2), $instanceTimeSpent ?? []),
                'p95_runtime' => !empty($processingTimes) ? round($this->calculatePercentile($processingTimes, 95), 2) : null,
                'warmup_samples' => count($processingTimes) - ($processed + $errors + $skipped),
            ], JSON_UNESCAPED_SLASHES));
        }
        
        $this->jsonResponse([
            'status' => 'ok',
            'processed' => $processed,
            'errors' => $errors,
            'skipped' => $skipped,
            'elapsed_ms' => $elapsed
        ]);
    }
    
    /**
     * Atualiza heartbeat de um item em processamento.
     * Indica que o worker ainda está vivo e trabalhando neste item.
     */
    private function updateHeartbeat(int $itemId): void
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE whatsapp_webhook_queue SET heartbeat_at = NOW() WHERE id = ?
            ");
            $stmt->execute([$itemId]);
        } catch (\Throwable $e) {
            // Silenciar — heartbeat é best-effort
        }
    }

    /**
     * Calcula percentil simples para série numérica.
     */
    private function calculatePercentile(array $values, int $percentile): float
    {
        if (empty($values)) {
            return 0.0;
        }

        sort($values);
        $p = max(1, min(99, $percentile));
        $index = (int)ceil((count($values) * $p) / 100) - 1;
        $index = max(0, min($index, count($values) - 1));
        return (float)$values[$index];
    }
    
    /**
     * Arquiva itens finalizados antes de deletar.
    * Preserva evidência para debug em tabela leve.
     */
    private function archiveAndCleanup(): void
    {
        // Garantir tabela de arquivo existe
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS whatsapp_webhook_queue_archive (
                id BIGINT PRIMARY KEY,
                instance_name VARCHAR(100) NOT NULL,
                message_id VARCHAR(100) NOT NULL DEFAULT '',
                status VARCHAR(20) NOT NULL,
                priority TINYINT UNSIGNED NOT NULL DEFAULT 1,
                attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
                last_error VARCHAR(500) NULL,
                payload_hash CHAR(32) NULL,
                payload_event VARCHAR(80) NULL,
                payload_remote_jid VARCHAR(120) NULL,
                payload_message_type VARCHAR(80) NULL,
                payload_sender VARCHAR(120) NULL,
                created_at DATETIME NOT NULL,
                started_at DATETIME NULL,
                completed_at DATETIME NULL,
                archived_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_archived_instance (instance_name, archived_at),
                INDEX idx_archived_status (status, archived_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Migration: adicionar colunas novas à tabela de arquivo existente
        try { $this->db->exec("ALTER TABLE whatsapp_webhook_queue_archive ADD COLUMN priority TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER status"); } catch (\Throwable $e) {}
        try { $this->db->exec("ALTER TABLE whatsapp_webhook_queue_archive ADD COLUMN payload_hash CHAR(32) NULL AFTER last_error"); } catch (\Throwable $e) {}
        try { $this->db->exec("ALTER TABLE whatsapp_webhook_queue_archive ADD COLUMN payload_event VARCHAR(80) NULL AFTER payload_hash"); } catch (\Throwable $e) {}
        try { $this->db->exec("ALTER TABLE whatsapp_webhook_queue_archive ADD COLUMN payload_remote_jid VARCHAR(120) NULL AFTER payload_event"); } catch (\Throwable $e) {}
        try { $this->db->exec("ALTER TABLE whatsapp_webhook_queue_archive ADD COLUMN payload_message_type VARCHAR(80) NULL AFTER payload_remote_jid"); } catch (\Throwable $e) {}
        try { $this->db->exec("ALTER TABLE whatsapp_webhook_queue_archive ADD COLUMN payload_sender VARCHAR(120) NULL AFTER payload_message_type"); } catch (\Throwable $e) {}
        
        // 1. Arquivar done > 48h (sem payload — hash para rastreabilidade)
        $this->db->exec("
            INSERT IGNORE INTO whatsapp_webhook_queue_archive 
                (id, instance_name, message_id, status, priority, attempts, last_error, payload_hash, created_at, started_at, completed_at)
            SELECT id, instance_name, message_id, status, priority, attempts, last_error, MD5(payload), created_at, started_at, completed_at
            FROM whatsapp_webhook_queue 
            WHERE status = 'done' AND completed_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)
        ");
        $this->db->exec("
            DELETE FROM whatsapp_webhook_queue 
            WHERE status = 'done' AND completed_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)
        ");
        
        // 2. Arquivar + deletar expired > 7d
        $this->db->exec("
            INSERT IGNORE INTO whatsapp_webhook_queue_archive 
                (id, instance_name, message_id, status, priority, attempts, last_error, payload_hash, created_at, started_at, completed_at)
            SELECT id, instance_name, message_id, status, priority, attempts, last_error, MD5(payload), created_at, started_at, completed_at
            FROM whatsapp_webhook_queue 
            WHERE status = 'expired' AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $this->db->exec("
            DELETE FROM whatsapp_webhook_queue 
            WHERE status = 'expired' AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        
        // 3. Arquivar + deletar dlq > 30d (COM payload estruturado para debug)
        $this->db->exec("
            INSERT IGNORE INTO whatsapp_webhook_queue_archive 
                (id, instance_name, message_id, status, priority, attempts, last_error, payload_hash, payload_event, payload_remote_jid, payload_message_type, payload_sender, created_at, started_at, completed_at)
            SELECT
                id,
                instance_name,
                message_id,
                status,
                priority,
                attempts,
                last_error,
                MD5(payload),
                CASE WHEN JSON_VALID(payload) THEN JSON_UNQUOTE(JSON_EXTRACT(payload, '$.event')) ELSE NULL END,
                CASE WHEN JSON_VALID(payload) THEN JSON_UNQUOTE(JSON_EXTRACT(payload, '$.data.key.remoteJid')) ELSE NULL END,
                CASE WHEN JSON_VALID(payload) THEN JSON_UNQUOTE(JSON_EXTRACT(payload, '$.data.messageType')) ELSE NULL END,
                CASE WHEN JSON_VALID(payload) THEN JSON_UNQUOTE(JSON_EXTRACT(payload, '$.sender')) ELSE NULL END,
                created_at,
                started_at,
                completed_at
            FROM whatsapp_webhook_queue 
            WHERE status = 'dlq' AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $this->db->exec("
            DELETE FROM whatsapp_webhook_queue 
            WHERE status = 'dlq' AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // 4. Pendentes > 24h → expired
        $this->db->exec("
            UPDATE whatsapp_webhook_queue SET status = 'expired' 
            WHERE status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        
        // 5. Limpar arquivo velho > 90d (o arquivo também não é eterno)
        $this->db->exec("
            DELETE FROM whatsapp_webhook_queue_archive 
            WHERE archived_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
        ");
    }
    
    /**
     * DLQ: Reprocessar itens da Dead Letter Queue.
     * Endpoint: POST /webhook/evolution-dlq-retry
     * 
     * Segurança:
     *   - dry_run: true → mostra o que SERIA movido sem mover
     *   - error_type: filtrar por tipo de erro (ex: "MAX_RETRIES", "NON-RETRYABLE")
     *   - max: limite de itens (default 10, teto 50)
     *   - id: reprocessar item específico
     *   - all: true para reprocessar todos (com max)
     */
    public function dlqRetry(array $params = []): void
    {
        \App\Middleware\WebhookGate::requireHeaderSecret('WEBHOOK_INTERNAL_SECRET', 'HTTP_X_WEBHOOK_INTERNAL');

        try {
            $this->ensureQueueTable();
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            
            $specificId = $input['id'] ?? null;
            $all = $input['all'] ?? false;
            $dryRun = $input['dry_run'] ?? false;
            $errorType = $input['error_type'] ?? null; // ex: "MAX_RETRIES", "NON-RETRYABLE"
            $max = min((int)($input['max'] ?? 10), 50); // Default 10, teto 50 (não 200)
            
            // Rate limit: máximo 3 operações de retry por hora
            $rateLimitStmt = $this->db->query("
                SELECT COUNT(*) as cnt 
                FROM whatsapp_webhook_queue 
                WHERE status = 'pending' 
                AND last_error LIKE '[DLQ-RETRY%'
                AND started_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            // Usar uma abordagem mais simples: contar DLQ retries recentes via log
            // (não poluir a query principal)
            
            if ($specificId) {
                if ($dryRun) {
                    $stmt = $this->db->prepare("
                        SELECT id, instance_name, message_id, last_error, attempts, created_at 
                        FROM whatsapp_webhook_queue WHERE id = ? AND status = 'dlq'
                    ");
                    $stmt->execute([$specificId]);
                    $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $this->jsonResponse(['status' => 'ok', 'dry_run' => true, 'would_retry' => $items]);
                    return;
                }
                $stmt = $this->db->prepare("
                    UPDATE whatsapp_webhook_queue 
                    SET status = 'pending', attempts = 0, priority = 9, last_error = CONCAT('[DLQ-RETRY] ', COALESCE(last_error, '')), started_at = NULL
                    WHERE id = ? AND status = 'dlq'
                ");
                $stmt->execute([$specificId]);
                $moved = $stmt->rowCount();
            } elseif ($all) {
                // Construir query com filtro opcional por tipo de erro
                $where = "status = 'dlq'";
                $bindParams = [];
                
                if ($errorType) {
                    $where .= " AND last_error LIKE ?";
                    $bindParams[] = "%{$errorType}%";
                }
                
                if ($dryRun) {
                    $stmt = $this->db->prepare("
                        SELECT id, instance_name, message_id, last_error, attempts, created_at 
                        FROM whatsapp_webhook_queue WHERE {$where} ORDER BY created_at ASC LIMIT {$max}
                    ");
                    $stmt->execute($bindParams);
                    $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $this->jsonResponse(['status' => 'ok', 'dry_run' => true, 'would_retry' => $items, 'count' => count($items)]);
                    return;
                }
                
                $stmt = $this->db->prepare("
                    UPDATE whatsapp_webhook_queue 
                    SET status = 'pending', attempts = 0, priority = 9, last_error = CONCAT('[DLQ-RETRY] ', COALESCE(last_error, '')), started_at = NULL
                    WHERE {$where}
                    ORDER BY created_at ASC
                    LIMIT {$max}
                ");
                $stmt->execute($bindParams);
                $moved = $stmt->rowCount();
            } else {
                $this->jsonResponse(['status' => 'error', 'message' => 'Specify id or all:true. Use dry_run:true to preview.'], 400);
                return;
            }
            
            error_log("[Webhook DLQ] Reprocessamento: {$moved} itens movidos de DLQ para pending" . ($errorType ? " (filtro: {$errorType})" : ''));
            
            $this->jsonResponse([
                'status' => 'ok',
                'moved_to_pending' => $moved,
            ]);
            
        } catch (\Throwable $e) {
            error_log("[Webhook DLQ] Erro: " . $e->getMessage());
            $this->jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Observabilidade: métricas da fila de webhooks.
     * Endpoint: GET /webhook/evolution-queue-stats
     */
    public function queueStats(array $params = []): void
    {
        \App\Middleware\WebhookGate::requireHeaderSecret('WEBHOOK_INTERNAL_SECRET', 'HTTP_X_WEBHOOK_INTERNAL');

        try {
            $this->ensureQueueTable();
            
            // Contagens por status
            $stmt = $this->db->query("
                SELECT status, COUNT(*) as count 
                FROM whatsapp_webhook_queue 
                GROUP BY status
            ");
            $statusCounts = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $statusCounts[$row['status']] = (int)$row['count'];
            }
            
            // Tempo médio de processamento (últimas 100 mensagens done)
            $avgStmt = $this->db->query("
                SELECT 
                    ROUND(AVG(TIMESTAMPDIFF(SECOND, created_at, completed_at)), 1) as avg_seconds,
                    ROUND(MAX(TIMESTAMPDIFF(SECOND, created_at, completed_at)), 1) as max_seconds,
                    ROUND(MIN(TIMESTAMPDIFF(SECOND, created_at, completed_at)), 1) as min_seconds
                FROM (
                    SELECT created_at, completed_at 
                    FROM whatsapp_webhook_queue 
                    WHERE status = 'done' AND completed_at IS NOT NULL
                    ORDER BY completed_at DESC 
                    LIMIT 100
                ) recent
            ");
            $timing = $avgStmt->fetch(\PDO::FETCH_ASSOC);

            // p95 de latência (últimas 100 done)
            $p95Stmt = $this->db->query("
                SELECT ROUND(MAX(diff_seconds), 1) as p95_seconds
                FROM (
                    SELECT
                        TIMESTAMPDIFF(SECOND, created_at, completed_at) as diff_seconds,
                        ROW_NUMBER() OVER (ORDER BY TIMESTAMPDIFF(SECOND, created_at, completed_at)) as rn,
                        COUNT(*) OVER () as total
                    FROM (
                        SELECT created_at, completed_at
                        FROM whatsapp_webhook_queue
                        WHERE status = 'done' AND completed_at IS NOT NULL
                        ORDER BY completed_at DESC
                        LIMIT 100
                    ) recent_done
                ) ranked
                WHERE rn >= CEIL(total * 0.95)
            ");
            $p95Seconds = (float)($p95Stmt->fetchColumn() ?: 0);
            
            // p95 por tipo de evento: identifica qual tipo de mensagem puxa latência
            // Inclui p95 REAL por tipo (não só avg/max) para diagnóstico preciso
            $latencyByEvent = [];
            try {
                // Buscar latências brutas por tipo (última hora, max 500 itens)
                $eventRawStmt = $this->db->query("
                    SELECT
                        COALESCE(
                            CASE WHEN JSON_VALID(payload)
                                 THEN JSON_UNQUOTE(JSON_EXTRACT(payload, '$.event'))
                                 ELSE NULL END,
                            'unknown'
                        ) as event_type,
                        TIMESTAMPDIFF(SECOND, started_at, completed_at) as latency_secs
                    FROM whatsapp_webhook_queue
                    WHERE status = 'done'
                      AND started_at IS NOT NULL AND completed_at IS NOT NULL
                      AND completed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                    ORDER BY completed_at DESC
                    LIMIT 500
                ");
                // Agrupar latências por tipo em PHP para calcular p95
                $byType = [];
                foreach ($eventRawStmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                    $type = $row['event_type'];
                    $byType[$type][] = max(0, (int)$row['latency_secs']);
                }
                foreach ($byType as $type => $latencies) {
                    $count = count($latencies);
                    sort($latencies);
                    $avg = round(array_sum($latencies) / $count, 1);
                    $max = $latencies[$count - 1];
                    $p95Idx = max(0, (int)ceil($count * 0.95) - 1);
                    $p95 = $latencies[min($p95Idx, $count - 1)];
                    $latencyByEvent[] = [
                        'event_type' => $type,
                        'total' => $count,
                        'avg_secs' => $avg,
                        'p95_secs' => $p95,
                        'max_secs' => $max,
                    ];
                }
                // Ordenar por volume desc
                usort($latencyByEvent, fn($a, $b) => $b['total'] <=> $a['total']);
                $latencyByEvent = array_slice($latencyByEvent, 0, 10);
            } catch (\Throwable $e) {
                // JSON_VALID pode não estar disponível em versões antigas
            }
            
            // Erros recentes (últimas 10 falhas — DLQ + failed)
            $errorStmt = $this->db->query("
                SELECT instance_name, message_id, status, last_error, attempts, created_at
                FROM whatsapp_webhook_queue 
                WHERE status IN ('failed', 'dlq')
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $recentErrors = $errorStmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Itens antigos pendentes (> 5 minutos = potencial problema)
            $staleStmt = $this->db->query("
                SELECT COUNT(*) as count 
                FROM whatsapp_webhook_queue 
                WHERE status = 'pending' 
                AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ");
            $staleCount = (int)$staleStmt->fetchColumn();
            
            // Throughput: processadas na última hora
            $throughputStmt = $this->db->query("
                SELECT COUNT(*) as count 
                FROM whatsapp_webhook_queue 
                WHERE status = 'done' 
                AND completed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $hourlyThroughput = (int)$throughputStmt->fetchColumn();
            
            $this->jsonResponse([
                'status' => 'ok',
                'queue' => [
                    'pending' => $statusCounts['pending'] ?? 0,
                    'processing' => $statusCounts['processing'] ?? 0,
                    'done' => $statusCounts['done'] ?? 0,
                    'failed' => $statusCounts['failed'] ?? 0,
                    'expired' => $statusCounts['expired'] ?? 0,
                    'dlq' => $statusCounts['dlq'] ?? 0,
                ],
                'timing' => [
                    'avg_seconds' => (float)($timing['avg_seconds'] ?? 0),
                    'p95_seconds' => $p95Seconds,
                    'max_seconds' => (float)($timing['max_seconds'] ?? 0),
                    'min_seconds' => (float)($timing['min_seconds'] ?? 0),
                ],
                'health' => [
                    'stale_pending' => $staleCount,
                    'hourly_throughput' => $hourlyThroughput,
                    'healthy' => $staleCount === 0,
                ],
                'latency_by_event' => $latencyByEvent,
                'recent_errors' => $recentErrors,
            ]);
            
        } catch (\Throwable $e) {
            error_log("[Webhook Stats] Erro: " . $e->getMessage());
            $this->jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Classifica se um erro é retryable (temporário) ou non-retryable (permanente).
     * 
     * Non-retryable: dados corrompidos, validação, lógica.
     * Retryable: rede, timeout, banco temporário, API externa.
     */
    private function isRetryableError(\Throwable $e): bool
    {
        $msg = strtolower($e->getMessage());
        
        // NON-RETRYABLE: erros de dados/lógica que retry não resolve
        $permanentPatterns = [
            'payload json inválido',    // dados corrompidos
            'invalid json',             // dados corrompidos
            'json_decode',              // dados corrompidos
            'undefined index',          // estrutura inesperada
            'undefined array key',      // estrutura inesperada
            'cannot access offset',     // tipo errado
            'must be of type',          // tipo errado
            'argument #',               // tipo errado
            'division by zero',         // lógica
        ];
        
        foreach ($permanentPatterns as $pattern) {
            if (str_contains($msg, $pattern)) {
                return false;
            }
        }
        
        // RETRYABLE: erros temporários
        $retryablePatterns = [
            'connection',           // falha de rede/DB
            'timeout',              // timeout
            'timed out',            // timeout
            'gone away',            // MySQL desconectou
            'lost connection',      // MySQL desconectou
            'deadlock',             // deadlock MySQL
            'lock wait timeout',    // lock timeout MySQL
            'too many connections', // MySQL sobrecarregado
            'curl',                 // falha na API Evolution
            'could not resolve',    // DNS
            'refused',              // connection refused
            '500',                  // server error externo
            '502',                  // bad gateway
            '503',                  // service unavailable
            '504',                  // gateway timeout
        ];
        
        foreach ($retryablePatterns as $pattern) {
            if (str_contains($msg, $pattern)) {
                return true;
            }
        }
        
        // Default: assume retryable (fail-open — melhor tentar de novo que perder)
        return true;
    }

    /**
     * Converte diferentes representações para bool com segurança.
     */
    private function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return ((int)$value) === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }
        }

        return (bool)$value;
    }
    
    /**
     * Detecta mensagem enviada por humano (atendente) e ativa takeover.
     * 
     * Quando fromMe=true, pode ser:
     *   - Echo automático: nosso sistema enviou via API e o webhook retornou a msg
     *   - Resposta humana real: atendente digitou manualmente no WhatsApp
     * 
     * Diferenciação (2 camadas):
     *   1. PRIMÁRIA: correlação exata por message_id — se o ID da mensagem
     *      existe no whatsapp_send_log, é garantidamente um echo do nosso envio.
     *   2. FALLBACK: janela temporal de 60s — caso o message_id não esteja
     *      registrado (envio via OrderNotification/Engagement que não usam send_log).
     */
    private function handleHumanMessage(string $instanceName, array $payload): void
    {
        $data = $payload['data'] ?? [];
        $key = $data['key'] ?? [];
        $remoteJid = $key['remoteJid'] ?? '';
        $messageId = $key['id'] ?? '';
        
        if (empty($remoteJid) || strpos($remoteJid, '@g.us') !== false) {
            return; // Ignorar se vazio ou grupo
        }
        
        $companyId = $this->getCompanyByInstance($instanceName);
        
        if (!$companyId) {
            return;
        }
        
        // Contatos LID: resolver para número real antes de processar takeover.
        // Sem resolução, não conseguimos determinar para quem ativar o takeover.
        // Isso é necessário para detectar quando o atendente inicia uma conversa via
        // contato LID: sem isso, a resposta de fora-do-expediente dispara incorretamente.
        if (strpos($remoteJid, '@lid') !== false) {
            $lid = preg_replace('/@.*$/', '', $remoteJid);
            $phone = $this->getPhoneByLid($lid, $instanceName);
            if (empty($phone)) {
                error_log("[Webhook Evolution] fromMe LID {$lid} não resolvido — takeover não ativado");
                return;
            }
            if ($this->isAutomatedEcho($companyId, $phone, $messageId)) {
                error_log("[Webhook Evolution] fromMe LID {$lid} para {$phone} é ECHO AUTOMÁTICO — ignorando");
                return;
            }
            error_log("[Webhook Evolution] Mensagem HUMANA detectada via LID {$lid} → {$phone} — ativando takeover");
            $this->activateHumanTakeover($companyId, $instanceName, $phone, $remoteJid);
            return;
        }
        
        $phone = normalizePhone(preg_replace('/@.*$/', '', $remoteJid));
        
        // Verificar se é echo de envio automático
        if ($this->isAutomatedEcho($companyId, $phone, $messageId)) {
            error_log("[Webhook Evolution] fromMe para {$phone} é ECHO AUTOMÁTICO — ignorando (não é humano)");
            return;
        }
        
        error_log("[Webhook Evolution] Mensagem HUMANA detectada para {$phone} — ativando takeover");
        $this->activateHumanTakeover($companyId, $instanceName, $phone, $remoteJid);
    }
    
    /**
     * Processa evento de mensagem recebida
     */
    private function processMessageUpsert(string $instanceName, array $payload, ?int $queueItemId = null): void
    {
        $data = $payload['data'] ?? [];
        $key = $data['key'] ?? [];
        
        $remoteJid = $key['remoteJid'] ?? '';
        $messageId = $key['id'] ?? '';
        
        // Capturar o sender do payload principal (número real do remetente)
        $senderJid = $payload['sender'] ?? '';
        
        if (empty($remoteJid)) {
            return;
        }

        // Alguns payloads chegam sem key.id em variações da Evolution.
        // Não devemos descartar a mensagem por isso.
        if (empty($messageId)) {
            $messageId = 'noid_' . md5($remoteJid . '|' . (string)($data['messageTimestamp'] ?? time()));
            error_log("[Webhook Evolution] key.id ausente — gerando id sintético {$messageId}");
        }
        
        // Ignorar grupos
        if (strpos($remoteJid, '@g.us') !== false) {
            return;
        }
        
        // Extrair telefone do remoteJid
        // Formato normal: 5551999999999@s.whatsapp.net -> 5551999999999
        // Formato LID: 208125575577645@lid -> usar sender como número real
        $phone = '';
        $replyJid = $remoteJid; // JID para responder
        $isLid = false;
        
        // Capturar participant do key (pode conter o número real em mensagens LID)
        $participantJid = $key['participant'] ?? '';
        
        if (strpos($remoteJid, '@lid') !== false) {
            // Formato LID - precisa resolver para número real
            $isLid = true;
            $lid = preg_replace('/@.*$/', '', $remoteJid);
            
            // Capturar pushName para busca alternativa
            $pushName = $data['pushName'] ?? $payload['pushName'] ?? '';
            
            error_log("[Webhook Evolution] Processando LID {$lid}, senderJid={$senderJid}, participantJid={$participantJid}");
            
            // IMPORTANTE: O campo sender da Evolution API pode retornar incorretamente
            // o número da própria instância (ownerJid) ao invés do remetente real.
            // Precisamos verificar e buscar o número real de forma alternativa.
            
            // 0. NOVO: Tentar usar senderJid se for número válido e não for o número da instância
            // O sender pode conter o número real do remetente em formato @s.whatsapp.net
            if (!empty($senderJid) && strpos($senderJid, '@s.whatsapp.net') !== false) {
                $senderPhone = normalizePhone(preg_replace('/@.*$/', '', $senderJid));
                error_log("[Webhook Evolution] Verificando sender: {$senderPhone}");
                // Verificar se não é o número da própria instância
                $isOwner = $this->isInstanceOwnerNumber($senderPhone, $instanceName);
                error_log("[Webhook Evolution] isInstanceOwnerNumber({$senderPhone}) = " . ($isOwner ? 'true' : 'false'));
                if (!$isOwner) {
                    $phone = $senderPhone;
                    $replyJid = $senderJid;
                    // Salvar mapeamento para uso futuro
                    $this->saveLidMapping($lid, $phone, $instanceName);
                    error_log("[Webhook Evolution] LID {$lid} -> usando sender: {$phone}");
                }
            }
            
            // 0b. NOVO: Tentar usar participant se for número válido
            if (empty($phone) && !empty($participantJid) && strpos($participantJid, '@s.whatsapp.net') !== false) {
                $participantPhone = normalizePhone(preg_replace('/@.*$/', '', $participantJid));
                if (!$this->isInstanceOwnerNumber($participantPhone, $instanceName)) {
                    $phone = $participantPhone;
                    $replyJid = $participantJid;
                    $this->saveLidMapping($lid, $phone, $instanceName);
                    error_log("[Webhook Evolution] LID {$lid} -> usando participant: {$phone}");
                }
            }
            
            // 1. Tentar buscar no mapeamento local
            if (empty($phone)) {
                $mappedPhone = $this->getPhoneByLid($lid, $instanceName);
                if ($mappedPhone) {
                    $phone = $mappedPhone;
                    $replyJid = $mappedPhone . '@s.whatsapp.net';
                    error_log("[Webhook Evolution] LID {$lid} -> usando mapeamento existente: {$phone}");
                }
            }
            
            // 2. REMOVIDO: pushName NÃO deve ser usado para resolver LIDs.
            // PushNames não são identificadores únicos — múltiplos clientes podem ter
            // o mesmo nome (ex: dois "Paulo"), e a tabela whatsapp_pushname_mapping
            // sobrescreve o mapeamento a cada mensagem, causando resolução para o
            // cliente ERRADO. É preferível não enviar do que enviar para a pessoa errada.
            if (empty($phone) && !empty($pushName)) {
                error_log("[Webhook Evolution] LID {$lid} -> pushName '{$pushName}' disponível mas NÃO usado para resolução (não confiável)");
            }
            
            // 3. REMOVIDO: profilePic matching também é inseguro.
            // Contatos sem foto de perfil → mesmo resultado → falso positivo.
            // Foto padrão do WhatsApp → match errado entre contatos distintos.
            // Mensagem enviada para cliente errado é PIOR que não enviar.
            
            // Fallback: LID fica pendente. Responder via LID diretamente.
            // A Evolution API v2+ suporta responder via LID.
            // Mensagens pendentes serão enviadas quando um evento futuro
            // trouxer sender/participant com o número real (saveLidMapping).
            if (empty($phone)) {
                $phone = $lid;
                $replyJid = $remoteJid;
                error_log("[Webhook Evolution] LID {$lid} -> sem evidência determinística, mantendo pendente");
            }
        } else {
            // Formato normal @s.whatsapp.net — identidade determinística pelo remoteJid
            $phone = normalizePhone(preg_replace('/@.*$/', '', $remoteJid));
            $replyJid = $remoteJid;
            
            // Salvar mapeamento pushName -> phone para informação (NÃO para resolução LID)
            $pushName = $data['pushName'] ?? $payload['pushName'] ?? '';
            if (!empty($pushName) && !empty($phone)) {
                $this->savePushNameMapping($pushName, $phone, $instanceName);
            }
            
            // BINDING POSTERIOR: Se este phone tem LIDs pendentes (não resolvidos),
            // vincular o LID ao phone agora que temos identidade determinística.
            // Cenário: cliente mandou msg via LID (sem número), depois mandou via @s.whatsapp.net.
            // O pushName é igual → podemos vincular com segurança.
            if (!empty($pushName)) {
                $this->attemptLidBindingByIdentity($phone, $pushName, $instanceName);
            }
        }
        
        // Extrair texto da mensagem com unwrapping robusto de estruturas encapsuladas
        // (CRÍTICO: mensagens ephemeral/viewOnce vêm aninhadas e precisam ser unwrapped)
        $messageText = $this->extractMessageText($data['message'] ?? []);
        
        // Timestamp da mensagem
        $messageTimestamp = $data['messageTimestamp'] ?? time();
        
        // Buscar company_id pela instância
        $companyId = $this->getCompanyByInstance($instanceName);
        
        if (!$companyId) {
            error_log("[Webhook Evolution] Company não encontrada para instância: {$instanceName}");
            return;
        }

        // Self-healing: processar uma pequena janela de retries pendentes
        // a cada mensagem recebida para não depender apenas do cron externo.
        try {
            $retryResult = $this->sendService->processDueFailedQueue($companyId, 3);
            if (($retryResult['processed'] ?? 0) > 0) {
                error_log("[Webhook Evolution] Retry inline: processadas={$retryResult['processed']}, enviadas={$retryResult['sent']}, falhas={$retryResult['failed']}, abandonadas={$retryResult['abandoned']}");
            }
        } catch (\Throwable $e) {
            error_log("[Webhook Evolution] Erro no retry inline: " . $e->getMessage());
        }
        
        // HUMAN TAKEOVER CHECK — se humano está atendendo, não enviar automação
        if ($this->isHumanTakeoverActive($companyId, $phone)) {
            error_log("[Webhook Evolution] Takeover humano ATIVO para {$phone} — automação bloqueada");
            // Salvar mensagem normalmente, mas não responder automaticamente
            $this->saveReceivedMessage($companyId, $instanceName, $remoteJid, $phone, $messageId, $messageText, $messageTimestamp);
            error_log("[Webhook Evolution] Mensagem salva (sem auto-resposta): {$phone} - " . substr($messageText, 0, 30));
            return;
        }
        
        // RESPONDER PRIMEIRO — princípio: resposta imediata antes de qualquer processamento
        // Isso garante que a resposta é enviada mesmo se o salvamento no banco falhar
        // HEARTBEAT GRANULAR: atualizar antes/depois de chamada externa (WhatsApp API)
        if ($queueItemId) { $this->updateHeartbeat($queueItemId); }
        try {
            $this->checkAndSendOutOfHoursResponse($companyId, $instanceName, $phone, $replyJid);
        } catch (\Throwable $e) {
            error_log("[Webhook Evolution] Erro ao processar resposta automática: " . $e->getMessage());
        }
        if ($queueItemId) { $this->updateHeartbeat($queueItemId); }
        
        // Salvar mensagem recebida (após resposta para não atrasar o envio)
        $this->saveReceivedMessage($companyId, $instanceName, $remoteJid, $phone, $messageId, $messageText, $messageTimestamp);
        
        error_log("[Webhook Evolution] Mensagem processada: {$phone} - " . substr($messageText, 0, 30));
    }
    
    /**
     * Extrai texto da mensagem com unwrapping robusto de estruturas encapsuladas.
     * 
     * A Evolution API v2.x pode enviar mensagens em múltiplos formatos:
     * - Texto simples: message.conversation
     * - Texto com link: message.extendedTextMessage.text
     * - Ephemeral (temporárias): message.ephemeralMessage.message.conversation
     * - ViewOnce (visualização única): message.viewOnceMessage.message.imageMessage.caption
     * - Botões/Listas: message.buttonsResponseMessage / listResponseMessage
     * 
     * Sem unwrapping, mensagens encapsuladas ficam invisíveis ao sistema,
     * causando falha na resposta da primeira interação.
     */
    private function extractMessageText(array $msg): string
    {
        // Log da estrutura para debug (apenas chaves de primeiro nível)
        $topKeys = array_keys($msg);
        error_log("[Webhook Evolution] Estrutura da mensagem recebida: " . implode(', ', $topKeys));
        
        // === UNWRAP mensagens encapsuladas (CRÍTICO para primeira interação) ===
        
        // ephemeralMessage = mensagens temporárias/que desaparecem
        if (isset($msg['ephemeralMessage']['message']) && is_array($msg['ephemeralMessage']['message'])) {
            error_log("[Webhook Evolution] Unwrapping ephemeralMessage");
            $msg = $msg['ephemeralMessage']['message'];
        }
        
        // viewOnceMessage = fotos/vídeos de visualização única (v1)
        if (isset($msg['viewOnceMessage']['message']) && is_array($msg['viewOnceMessage']['message'])) {
            error_log("[Webhook Evolution] Unwrapping viewOnceMessage");
            $msg = $msg['viewOnceMessage']['message'];
        }
        
        // viewOnceMessageV2 = visualização única (v2)
        if (isset($msg['viewOnceMessageV2']['message']) && is_array($msg['viewOnceMessageV2']['message'])) {
            error_log("[Webhook Evolution] Unwrapping viewOnceMessageV2");
            $msg = $msg['viewOnceMessageV2']['message'];
        }
        
        // viewOnceMessageV2Extension
        if (isset($msg['viewOnceMessageV2Extension']['message']) && is_array($msg['viewOnceMessageV2Extension']['message'])) {
            error_log("[Webhook Evolution] Unwrapping viewOnceMessageV2Extension");
            $msg = $msg['viewOnceMessageV2Extension']['message'];
        }
        
        // documentWithCaptionMessage = documento com legenda
        if (isset($msg['documentWithCaptionMessage']['message']) && is_array($msg['documentWithCaptionMessage']['message'])) {
            error_log("[Webhook Evolution] Unwrapping documentWithCaptionMessage");
            $msg = $msg['documentWithCaptionMessage']['message'];
        }
        
        // === EXTRAIR texto de múltiplos formatos ===
        
        $text = $msg['conversation']                                            // texto simples
            ?? $msg['extendedTextMessage']['text']                              // texto com link/menção
            ?? $msg['imageMessage']['caption']                                  // legenda de imagem
            ?? $msg['videoMessage']['caption']                                  // legenda de vídeo
            ?? $msg['documentMessage']['caption']                               // legenda de documento
            ?? $msg['buttonsResponseMessage']['selectedButtonId']               // resposta de botão
            ?? $msg['listResponseMessage']['title']                             // resposta de lista
            ?? $msg['listResponseMessage']['singleSelectReply']['selectedRowId'] // seleção de lista (v2)
            ?? $msg['templateButtonReplyMessage']['selectedId']                 // resposta de template
            ?? null;
        
        if ($text !== null) {
            return (string) $text;
        }
        
        // Mídia sem texto (áudio, sticker, contato, localização) — fluxo continua normalmente
        foreach (['audioMessage', 'stickerMessage', 'contactMessage', 'locationMessage'] as $mediaType) {
            if (isset($msg[$mediaType])) {
                error_log("[Webhook Evolution] Mensagem de mídia sem texto: {$mediaType}");
                return '';
            }
        }
        
        error_log("[Webhook Evolution] Tipo de mensagem não reconhecido. Chaves: " . implode(', ', array_keys($msg)));
        return '';
    }
    
    /**
     * Verifica se está fora do expediente e envia resposta automática
     */
    private function checkAndSendOutOfHoursResponse(int $companyId, string $instanceName, string $phone, string $remoteJid): void
    {
        error_log("[Webhook Evolution] Verificando resposta fora do expediente para company_id={$companyId}, instance={$instanceName}, phone={$phone}, remoteJid={$remoteJid}");
        
        // Verificar se a funcionalidade está habilitada
        $config = $this->getEngagementConfig($companyId, $instanceName);
        if (!$config) {
            error_log("[Webhook Evolution] Configuração de engajamento não encontrada para company_id={$companyId}, instance={$instanceName}");
            return;
        }
        
        // Verificar pausa programada PRIMEIRO (tem prioridade sobre horário de funcionamento)
        $pauseStatus = $this->getScheduledPauseStatus($companyId);
        if ($pauseStatus['is_paused']) {
            error_log("[Webhook Evolution] Loja está em PAUSA PROGRAMADA - verificando se deve enviar mensagem");
            
            // Verificar se mensagem de pausa está habilitada
            if (empty($config['scheduled_pause_enabled'])) {
                error_log("[Webhook Evolution] Mensagem de pausa programada está DESABILITADA para company_id={$companyId}");
                return;
            }
            
            // Verificar cooldown (separado por tipo — pausa programada não é bloqueada por cooldown de fora de expediente)
            if ($this->hasRecentOutOfHoursResponse($companyId, $phone, 'scheduled_pause')) {
                error_log("[Webhook Evolution] Cooldown ativo para {$phone} - não enviando resposta de pausa");
                return;
            }
            
            // Montar mensagem de pausa programada
            $customMessage = $config['scheduled_pause_message'] ?? null;
            $message = $this->buildScheduledPauseMessage($companyId, $pauseStatus, $customMessage);
            
            // Enviar mensagem
            $this->sendClosedStoreMessage($companyId, $instanceName, $phone, $remoteJid, $message, 'scheduled_pause');
            return;
        }
        
        if (empty($config['out_of_hours_enabled'])) {
            error_log("[Webhook Evolution] Resposta fora do expediente está DESABILITADA para company_id={$companyId}");
            return;
        }
        
        // Verificar se está dentro do horário de funcionamento
        if ($this->isWithinBusinessHours($companyId)) {
            error_log("[Webhook Evolution] Loja está ABERTA - não enviando resposta automática");
            return;
        }
        
        error_log("[Webhook Evolution] Loja está FECHADA - verificando cooldown");
        
        // Verificar cooldown - não enviar se já enviou recentemente para este número
        if ($this->hasRecentOutOfHoursResponse($companyId, $phone)) {
            error_log("[Webhook Evolution] Cooldown ativo para {$phone} - não enviando resposta");
            return;
        }
        
        // Verificar se o atendente foi quem iniciou esta conversa.
        // Se houve atividade humana (fromMe manual) nos últimos 30 minutos para este
        // número, o dono iniciou o contato — suprimir a auto-resposta de fora do expediente.
        if ($this->wasConversationInitiatedByAttendant($companyId, $phone)) {
            error_log("[Webhook Evolution] Atendente iniciou conversa recentemente — suprimindo fora-do-expediente para {$phone}");
            return;
        }
        
        // Buscar próximo horário de abertura
        $nextOpenInfo = $this->getNextBusinessOpenInfo($companyId);
        
        if (!$nextOpenInfo) {
            error_log("[Webhook Evolution] Não foi possível determinar próximo horário de abertura");
            return;
        }
        
        error_log("[Webhook Evolution] Próxima abertura: {$nextOpenInfo['day_name']} às {$nextOpenInfo['time']}");
        
        // Montar mensagem de resposta (usa mensagem personalizada se existir)
        $customMessage = $config['out_of_hours_message'] ?? null;
        $message = $this->buildOutOfHoursMessage($companyId, $nextOpenInfo, $customMessage);
        
        // Enviar mensagem - usar número puro para contatos normais e JID apenas para LID
        error_log("[Webhook Evolution] Tentando enviar resposta para {$remoteJid}...");
        
        // Verificar se é um LID não resolvido (phone igual ao LID)
        $isUnresolvedLid = strpos($remoteJid, '@lid') !== false && $phone === preg_replace('/@.*$/', '', $remoteJid);
        
        if ($isUnresolvedLid) {
            // Para LIDs não resolvidos, não conseguimos enviar diretamente
            // Salvar como mensagem pendente para enviar quando tivermos o número
            error_log("[Webhook Evolution] LID não resolvido {$remoteJid} - salvando mensagem pendente");
            $this->savePendingOutOfHoursMessage($companyId, $instanceName, $remoteJid, $message);
            // Não registrar cooldown pois não foi enviado
            return;
        }
        
        // SEGURANÇA: Para LIDs resolvidos, verificar se o mapping é HIGH confidence
        // LOW confidence (profilePic) NUNCA deve disparar auto-resposta
        if (strpos($remoteJid, '@lid') !== false) {
            $lidNum = preg_replace('/@.*$/', '', $remoteJid);
            $confStmt = $this->db->prepare("
                SELECT confidence FROM whatsapp_lid_mapping
                WHERE lid = ? AND instance_name = ?
                ORDER BY FIELD(confidence, 'HIGH', 'LOW') LIMIT 1
            ");
            $confStmt->execute([$lidNum, $instanceName]);
            $confRow = $confStmt->fetch(PDO::FETCH_ASSOC);
            if (!$confRow || $confRow['confidence'] !== 'HIGH') {
                error_log("[Webhook Evolution] LID {$lidNum} resolvido mas confidence=" . ($confRow['confidence'] ?? 'null') . " — salvando como pendente em vez de enviar");
                $this->savePendingOutOfHoursMessage($companyId, $instanceName, $remoteJid, $message);
                return;
            }
        }

        $target = (strpos($remoteJid, '@lid') !== false) ? $remoteJid : $phone;
        $success = $this->sendWhatsAppMessage($companyId, $instanceName, $target, $message);
        
        // Registrar cooldown SEMPRE (sucesso ou falha) para evitar spam de tentativas
        $this->logOutOfHoursResponse($companyId, $phone);
        
        if ($success) {
            error_log("[Webhook Evolution] Resposta fora do expediente enviada com SUCESSO para {$remoteJid}");
        } else {
            error_log("[Webhook Evolution] FALHA ao enviar resposta fora do expediente para {$remoteJid} - cooldown registrado para evitar spam");
        }
    }
    
    /**
     * Busca configuração de engajamento da empresa
     * Busca por company_id OU instance_name para garantir compatibilidade
     */
    private function getEngagementConfig(int $companyId, ?string $instanceName = null): ?array
    {
        // Primeiro, tentar buscar pela instância específica se fornecida
        if (!empty($instanceName)) {
            $stmt = $this->db->prepare("
                SELECT * FROM customer_engagement_config 
                WHERE company_id = ? AND instance_name = ? 
                LIMIT 1
            ");
            $stmt->execute([$companyId, $instanceName]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                return $result;
            }
        }
        
        // Fallback: buscar por company_id
        $stmt = $this->db->prepare("
            SELECT * FROM customer_engagement_config 
            WHERE company_id = ? 
            LIMIT 1
        ");
        $stmt->execute([$companyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Verifica status da pausa programada
     */
    private function getScheduledPauseStatus(int $companyId): array
    {
        $stmt = $this->db->prepare("
            SELECT pause_enabled, pause_until, pause_reason, pause_type
            FROM companies 
            WHERE id = ?
        ");
        $stmt->execute([$companyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row || empty($row['pause_enabled'])) {
            return ['is_paused' => false];
        }
        
        $pauseType = $row['pause_type'] ?? 'timed';
        
        // Pausa indefinida - sempre ativa enquanto pause_enabled=1
        if ($pauseType === 'indefinite') {
            return [
                'is_paused' => true,
                'pause_reason' => $row['pause_reason'],
                'pause_type' => $pauseType,
                'pause_until' => null,
                'remaining_text' => null
            ];
        }
        
        // Pausa temporizada - verifica se não expirou
        if ($row['pause_until']) {
            $now = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
            $until = new \DateTime($row['pause_until'], new \DateTimeZone('America/Sao_Paulo'));
            
            if ($now < $until) {
                $diff = $now->diff($until);
                $remainingText = $this->formatPauseRemainingTime($diff);
                
                return [
                    'is_paused' => true,
                    'pause_reason' => $row['pause_reason'],
                    'pause_type' => $pauseType,
                    'pause_until' => $row['pause_until'],
                    'remaining_text' => $remainingText
                ];
            }
        }
        
        return ['is_paused' => false];
    }
    
    /**
     * Formata o tempo restante da pausa
     */
    private function formatPauseRemainingTime(\DateInterval $diff): string
    {
        if ($diff->d > 0) {
            return $diff->d . ' dia(s) e ' . $diff->h . ' hora(s)';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hora(s) e ' . $diff->i . ' minuto(s)';
        } else {
            return $diff->i . ' minuto(s)';
        }
    }
    
    /**
     * Monta mensagem de pausa programada
     */
    private function buildScheduledPauseMessage(int $companyId, array $pauseStatus, ?string $customMessage): string
    {
        // Se há mensagem personalizada, usar ela
        if (!empty($customMessage)) {
            // Substituir variáveis na mensagem personalizada
            $message = str_replace(
                ['{motivo}', '{tempo_restante}', '{MOTIVO}', '{TEMPO_RESTANTE}'],
                [
                    $pauseStatus['pause_reason'] ?? 'Estamos em pausa',
                    $pauseStatus['remaining_text'] ?? 'em breve',
                    $pauseStatus['pause_reason'] ?? 'Estamos em pausa',
                    $pauseStatus['remaining_text'] ?? 'em breve'
                ],
                $customMessage
            );
            return $message;
        }
        
        // Buscar nome da empresa
        $stmt = $this->db->prepare("SELECT name FROM companies WHERE id = ?");
        $stmt->execute([$companyId]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        $companyName = $company['name'] ?? 'Estabelecimento';
        
        // Montar mensagem padrão baseada no tipo de pausa
        $pauseType = $pauseStatus['pause_type'] ?? 'timed';
        $pauseReason = $pauseStatus['pause_reason'] ?? '';
        
        if ($pauseType === 'indefinite') {
            $message = "Olá! 👋\n\n";
            $message .= "*{$companyName}* está temporariamente em pausa";
            if (!empty($pauseReason)) {
                $message .= ": {$pauseReason}";
            }
            $message .= ".\n\n";
            $message .= "Voltaremos a atender em breve! 🙏\n";
            $message .= "Acompanhe nossas redes sociais para novidades.";
        } else {
            $message = "Olá! 👋\n\n";
            $message .= "*{$companyName}* está temporariamente em pausa";
            if (!empty($pauseReason)) {
                $message .= ": {$pauseReason}";
            }
            $message .= ".\n\n";
            if (!empty($pauseStatus['remaining_text'])) {
                $message .= "⏰ Voltaremos em aproximadamente *{$pauseStatus['remaining_text']}*.\n\n";
            }
            $message .= "Aguardamos seu retorno! 🙏";
        }
        
        return $message;
    }
    
    /**
     * Método auxiliar para enviar mensagem de loja fechada (usado por pausa e fora do horário)
     */
    private function sendClosedStoreMessage(int $companyId, string $instanceName, string $phone, string $remoteJid, string $message, string $responseType = 'out_of_hours'): void
    {
        error_log("[Webhook Evolution] Tentando enviar mensagem de loja fechada para {$remoteJid}...");
        
        // Verificar se é um LID não resolvido
        $isUnresolvedLid = strpos($remoteJid, '@lid') !== false && $phone === preg_replace('/@.*$/', '', $remoteJid);
        
        if ($isUnresolvedLid) {
            error_log("[Webhook Evolution] LID não resolvido {$remoteJid} - salvando mensagem pendente");
            $this->savePendingOutOfHoursMessage($companyId, $instanceName, $remoteJid, $message);
            return;
        }
        
        // SEGURANÇA: Para LIDs resolvidos, verificar se o mapping é HIGH confidence
        if (strpos($remoteJid, '@lid') !== false) {
            $lidNum = preg_replace('/@.*$/', '', $remoteJid);
            $confStmt = $this->db->prepare("
                SELECT confidence FROM whatsapp_lid_mapping
                WHERE lid = ? AND instance_name = ?
                ORDER BY FIELD(confidence, 'HIGH', 'LOW') LIMIT 1
            ");
            $confStmt->execute([$lidNum, $instanceName]);
            $confRow = $confStmt->fetch(PDO::FETCH_ASSOC);
            if (!$confRow || $confRow['confidence'] !== 'HIGH') {
                error_log("[Webhook Evolution] Loja fechada: LID {$lidNum} confidence=" . ($confRow['confidence'] ?? 'null') . " — salvando como pendente");
                $this->savePendingOutOfHoursMessage($companyId, $instanceName, $remoteJid, $message);
                return;
            }
        }

        $target = (strpos($remoteJid, '@lid') !== false) ? $remoteJid : $phone;
        $success = $this->sendWhatsAppMessage($companyId, $instanceName, $target, $message);
        
        // Registrar cooldown SEMPRE (sucesso ou falha) para evitar spam de tentativas
        $this->logOutOfHoursResponse($companyId, $phone, $responseType);
        
        if ($success) {
            error_log("[Webhook Evolution] Mensagem de loja fechada enviada com SUCESSO para {$remoteJid} (tipo: {$responseType})");
        } else {
            error_log("[Webhook Evolution] FALHA ao enviar mensagem de loja fechada para {$remoteJid} - cooldown registrado para evitar spam");
        }
    }
    
    /**
     * Verifica se está dentro do horário de funcionamento
     */
    private function isWithinBusinessHours(int $companyId): bool
    {
        $stmt = $this->db->prepare("
            SELECT weekday, is_open, open1, close1, open2, close2 
            FROM company_hours 
            WHERE company_id = ? 
            ORDER BY weekday
        ");
        $stmt->execute([$companyId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $hours = [];
        foreach ($rows as $row) {
            $hours[(int)$row['weekday']] = $row;
        }
        
        // Usar timezone de Brasília
        $brasiliaTime = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
        $weekday = (int)$brasiliaTime->format('N');
        $currentTime = $brasiliaTime->format('H:i:s');
        
        if (!isset($hours[$weekday]) || empty($hours[$weekday]['is_open'])) {
            return false;
        }
        
        $day = $hours[$weekday];

        $isTimeInRange = static function (string $current, string $open, string $close): bool {
            if ($open <= $close) {
                return ($current >= $open && $current <= $close);
            }

            // Ex.: 18:00 -> 02:00
            return ($current >= $open || $current <= $close);
        };
        
        // Verificar primeiro período
        if (!empty($day['open1']) && !empty($day['close1'])) {
            if ($isTimeInRange($currentTime, $day['open1'], $day['close1'])) {
                return true;
            }
        }
        
        // Verificar segundo período (se houver)
        if (!empty($day['open2']) && !empty($day['close2'])) {
            if ($isTimeInRange($currentTime, $day['open2'], $day['close2'])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Busca informações do próximo horário de abertura
     * Retorna informações sobre quando a loja abre, ou um fallback genérico
     */
    private function getNextBusinessOpenInfo(int $companyId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT weekday, open1 
            FROM company_hours 
            WHERE company_id = ? AND is_open = 1 AND open1 IS NOT NULL
            ORDER BY weekday
        ");
        $stmt->execute([$companyId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Se não há nenhum dia com horário configurado, retornar mensagem genérica
        if (empty($rows)) {
            error_log("[Webhook Evolution] Nenhum horário de funcionamento configurado para company_id={$companyId}");
            return [
                'is_today' => false,
                'day_name' => 'em breve',
                'time' => ''
            ];
        }
        
        $hours = [];
        foreach ($rows as $row) {
            $hours[(int)$row['weekday']] = $row['open1'];
        }
        
        $brasiliaTime = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
        $currentWeekday = (int)$brasiliaTime->format('N');
        $currentTime = $brasiliaTime->format('H:i:s');
        
        $daysOfWeek = [
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
            7 => 'Domingo'
        ];
        
        // Verificar próximos 7 dias
        for ($offset = 0; $offset <= 7; $offset++) {
            $checkWeekday = (($currentWeekday - 1 + $offset) % 7) + 1;
            
            if (!isset($hours[$checkWeekday])) {
                continue;
            }
            
            $openTime = $hours[$checkWeekday];
            
            // Se for hoje, verificar se o horário de abertura ainda não passou
            if ($offset === 0) {
                if ($currentTime < $openTime) {
                    return [
                        'is_today' => true,
                        'day_name' => 'hoje',
                        'time' => substr($openTime, 0, 5) // HH:MM
                    ];
                }
                continue;
            }
            
            // Próximo dia com expediente
            $isToday = false;
            $dayName = '';
            
            if ($offset === 1) {
                $dayName = 'amanhã';
            } else {
                $dayName = $daysOfWeek[$checkWeekday];
            }
            
            return [
                'is_today' => false,
                'day_name' => $dayName,
                'time' => substr($openTime, 0, 5) // HH:MM
            ];
        }
        
        return null;
    }
    
    /**
     * Monta a mensagem de fora do expediente
     * Se $customMessage for fornecida, usa ela com substituição de variáveis
     * Variáveis suportadas: {saudacao}, {dia}, {hora}
     */
    private function buildOutOfHoursMessage(int $companyId, array $nextOpenInfo, ?string $customMessage = null): string
    {
        // Buscar nome da empresa
        $stmt = $this->db->prepare("SELECT name FROM companies WHERE id = ?");
        $stmt->execute([$companyId]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        $companyName = $company['name'] ?? 'nossa loja';
        
        // Saudação baseada no horário
        $brasiliaTime = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
        $hour = (int)$brasiliaTime->format('H');
        
        if ($hour >= 5 && $hour < 12) {
            $greeting = 'Bom dia';
        } elseif ($hour >= 12 && $hour < 18) {
            $greeting = 'Boa tarde';
        } else {
            $greeting = 'Boa noite';
        }
        
        // Se tem mensagem personalizada, usar ela
        if (!empty($customMessage)) {
            $dayName = $nextOpenInfo['is_today'] ? 'hoje' : $nextOpenInfo['day_name'];
            
            // Substituir variáveis
            $message = str_replace('{saudacao}', $greeting, $customMessage);
            $message = str_replace('{dia}', $dayName, $message);
            $message = str_replace('{hora}', $nextOpenInfo['time'], $message);
            
            return $message;
        }
        
        // Mensagem padrão
        if ($nextOpenInfo['is_today']) {
            $message = "{$greeting}! 😊\n\n";
            $message .= "Obrigado por entrar em contato!\n\n";
            $message .= "No momento estamos *fora do horário de atendimento*.\n\n";
            $message .= "🕐 Voltamos *hoje às {$nextOpenInfo['time']}*.\n\n";
            $message .= "Assim que abrirmos, retornaremos sua mensagem! 🙌";
        } elseif (empty($nextOpenInfo['time'])) {
            // Quando não tem horário específico configurado
            $message = "{$greeting}! 😊\n\n";
            $message .= "Obrigado por entrar em contato!\n\n";
            $message .= "No momento estamos *fora do horário de atendimento*.\n\n";
            $message .= "Assim que abrirmos, retornaremos sua mensagem! 🙌";
        } else {
            $message = "{$greeting}! 😊\n\n";
            $message .= "Obrigado por entrar em contato!\n\n";
            $message .= "No momento estamos *fora do horário de atendimento*.\n\n";
            $message .= "🕐 Voltamos *{$nextOpenInfo['day_name']} às {$nextOpenInfo['time']}*.\n\n";
            $message .= "Assim que abrirmos, retornaremos sua mensagem! 🙌";
        }
        
        return $message;
    }
    
    /**
     * Verifica se já enviou resposta de fora do expediente recentemente
     * @param string $responseType Tipo de resposta para cooldown independente ('out_of_hours' ou 'scheduled_pause')
     */
    private function hasRecentOutOfHoursResponse(int $companyId, string $phone, string $responseType = 'out_of_hours'): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM out_of_hours_responses 
                WHERE company_id = ? 
                    AND phone = ? 
                    AND response_type = ?
                    AND sent_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
                LIMIT 1
            ");
            $stmt->execute([$companyId, $phone, $responseType, self::RESPONSE_COOLDOWN_MINUTES]);
            return (bool) $stmt->fetch();
        } catch (\PDOException $e) {
            // Tabela pode não existir ainda (será criada no primeiro envio bem-sucedido)
            error_log("[Webhook Evolution] Erro ao verificar cooldown (tabela pode não existir): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra envio de resposta fora do expediente
     * @param string $responseType Tipo de resposta ('out_of_hours' ou 'scheduled_pause')
     */
    private function logOutOfHoursResponse(int $companyId, string $phone, string $responseType = 'out_of_hours'): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO out_of_hours_responses (company_id, phone, response_type)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$companyId, $phone, $responseType]);
        } catch (PDOException $e) {
            error_log("[Webhook Evolution] Erro ao registrar resposta: " . $e->getMessage());
        }
    }
    
    /**
     * Envia mensagem via WhatsApp usando o serviço centralizado.
     * Delegate para WhatsAppSendService::send() que inclui retry, log, message_id e fallback.
     *
     * @param string $target Número puro (ex: 5551999999999) para contatos normais
     *                       ou JID completo para LID (ex: 2081255...@lid)
     */
    private function sendWhatsAppMessage(int $companyId, string $instanceName, string $target, string $message, string $messageType = 'auto_response'): bool
    {
        $result = $this->sendService->send($companyId, $instanceName, $target, $message, $messageType, [
            'checkTakeover' => false // Takeover já verificado no processMessageUpsert
        ]);
        return $result['success'];
    }
    
    // =========================================================================
    // HUMAN TAKEOVER — Prioridade do atendimento humano sobre automação
    // =========================================================================
    
    /**
     * Verifica se uma mensagem fromMe é echo automático ou interação humana.
     * 
     * Duas camadas de detecção (prioridade decrescente):
     * 
     * 1. CORRELAÇÃO POR MESSAGE_ID (determinística):
     *    Compara o message_id do webhook com os message_ids registrados
     *    no whatsapp_send_log. Match exato = echo garantido.
     * 
     * 2. FALLBACK TEMPORAL (heurístico):
     *    Se message_id não encontrado (envio via serviços que não usam send_log),
     *    verifica se houve envio automático para este phone nos últimos 60s.
     */
    private function isAutomatedEcho(int $companyId, string $phone, string $messageId): bool
    {
        // === CAMADA 1: Correlação exata por message_id ===
        if (!empty($messageId)) {
            try {
                $stmt = $this->db->prepare("
                    SELECT id FROM whatsapp_send_log 
                    WHERE sent_message_id = ? 
                        AND company_id = ?
                    LIMIT 1
                ");
                $stmt->execute([$messageId, $companyId]);
                if ($stmt->fetch()) {
                    error_log("[Webhook Evolution] Echo detectado via message_id={$messageId} (correlação exata)");
                    return true;
                }
            } catch (\Throwable $e) {
                // Tabela ou coluna pode não existir ainda
            }
        }
        
        // === CAMADA 2: Fallback temporal (60s) para envios sem send_log ===
        $windowSeconds = 60;
        
        // 2a. whatsapp_send_log (auto-respostas, retry, etc.)
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM whatsapp_send_log 
                WHERE company_id = ? 
                    AND phone = ? 
                    AND status = 'success'
                    AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
                LIMIT 1
            ");
            $stmt->execute([$companyId, $phone, $windowSeconds]);
            if ($stmt->fetch()) {
                error_log("[Webhook Evolution] Echo detectado via whatsapp_send_log temporal para {$phone}");
                return true;
            }
        } catch (\Throwable $e) {
            // Tabela pode não existir
        }
        
        // 2b. out_of_hours_responses (resposta fora do horário)
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM out_of_hours_responses 
                WHERE company_id = ? 
                    AND phone = ? 
                    AND sent_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
                LIMIT 1
            ");
            $stmt->execute([$companyId, $phone, $windowSeconds]);
            if ($stmt->fetch()) {
                error_log("[Webhook Evolution] Echo detectado via out_of_hours_responses para {$phone}");
                return true;
            }
        } catch (\Throwable $e) {
            // Tabela pode não existir
        }
        
        // 2c. customer_engagement_log (engajamento automático)
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM customer_engagement_log 
                WHERE company_id = ? 
                    AND customer_phone = ? 
                    AND status = 'sent'
                    AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
                LIMIT 1
            ");
            $stmt->execute([$companyId, $phone, $windowSeconds]);
            if ($stmt->fetch()) {
                error_log("[Webhook Evolution] Echo detectado via customer_engagement_log para {$phone}");
                return true;
            }
        } catch (\Throwable $e) {
            // Tabela pode não existir
        }
        
        // Nenhuma fonte reconhece esta mensagem — é interação humana
        return false;
    }
    
    /**
     * Ativa modo de atendimento humano para uma conversa.
     * Quando ativado: bloqueia auto-respostas, pausa programada, engajamento.
     * Cancela retries pendentes na fila de fallback.
     */
    private function activateHumanTakeover(int $companyId, string $instanceName, string $phone, string $remoteJid): void
    {
        try {
            // Criar tabela se não existir (idempotente)
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS whatsapp_human_takeover (
                    id BIGINT AUTO_INCREMENT PRIMARY KEY,
                    company_id INT NOT NULL,
                    instance_name VARCHAR(100) NOT NULL,
                    phone VARCHAR(30) NOT NULL,
                    remote_jid VARCHAR(100) NOT NULL,
                    activated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    last_human_activity_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    expires_at DATETIME NOT NULL,
                    status ENUM('active','expired','released') NOT NULL DEFAULT 'active',
                    released_at DATETIME NULL,
                    UNIQUE KEY unique_active_conversation (company_id, phone, status),
                    INDEX idx_active_lookup (company_id, phone, status, expires_at),
                    INDEX idx_expiration (status, expires_at),
                    INDEX idx_instance (instance_name)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            $expiryMinutes = self::HUMAN_TAKEOVER_EXPIRY_MINUTES;
            
            // Verificar se já existe um takeover ativo para este phone
            $stmt = $this->db->prepare("
                SELECT id FROM whatsapp_human_takeover 
                WHERE company_id = ? AND phone = ? AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$companyId, $phone]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Atualizar: renovar expiração a cada mensagem humana
                $stmt = $this->db->prepare("
                    UPDATE whatsapp_human_takeover 
                    SET last_human_activity_at = NOW(),
                        expires_at = DATE_ADD(NOW(), INTERVAL ? MINUTE)
                    WHERE id = ?
                ");
                $stmt->execute([$expiryMinutes, $existing['id']]);
                error_log("[Webhook Evolution] Takeover renovado para {$phone} (+{$expiryMinutes}min)");
            } else {
                // Expirar takeovers antigos deste phone antes de criar novo
                $this->db->prepare("
                    UPDATE whatsapp_human_takeover 
                    SET status = 'expired' 
                    WHERE company_id = ? AND phone = ? AND status = 'active'
                ")->execute([$companyId, $phone]);
                
                // Criar novo takeover
                $stmt = $this->db->prepare("
                    INSERT INTO whatsapp_human_takeover 
                        (company_id, instance_name, phone, remote_jid, expires_at)
                    VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))
                ");
                $stmt->execute([$companyId, $instanceName, $phone, $remoteJid, $expiryMinutes]);
                error_log("[Webhook Evolution] Takeover ATIVADO para {$phone} (expira em {$expiryMinutes}min)");
            }
            
            // Cancelar retries pendentes na fila de fallback para este número
            $this->cancelPendingRetriesForPhone($companyId, $phone);
            
        } catch (\Throwable $e) {
            // Takeover não pode impedir o fluxo
            error_log("[Webhook Evolution] Erro ao ativar takeover: " . $e->getMessage());
        }
    }
    
    /**
     * Verifica se existe atendimento humano ativo para este cliente.
     * Também expira automaticamente takeovers vencidos.
     */
    private function isHumanTakeoverActive(int $companyId, string $phone): bool
    {
        try {
            // Expirar takeovers vencidos em batch (cleanup oportunístico)
            $this->db->exec("
                UPDATE whatsapp_human_takeover 
                SET status = 'expired' 
                WHERE status = 'active' AND expires_at < NOW()
            ");
            
            // Verificar se existe takeover ativo para este phone
            $stmt = $this->db->prepare("
                SELECT id FROM whatsapp_human_takeover 
                WHERE company_id = ? 
                    AND phone = ? 
                    AND status = 'active' 
                    AND expires_at > NOW()
                LIMIT 1
            ");
            $stmt->execute([$companyId, $phone]);
            return (bool) $stmt->fetch();
        } catch (\Throwable $e) {
            // Se tabela não existe ainda, não há takeover ativo
            error_log("[Webhook Evolution] Erro ao verificar takeover (tabela pode não existir): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se o atendente (dono) iniciou ou participou desta conversa recentemente.
     *
     * Quando o atendente envia uma mensagem manual (fromMe=true, não echo automático),
     * o sistema ativa um Human Takeover. Este método verifica se houve atividade humana
     * registrada nos últimos HUMAN_TAKEOVER_EXPIRY_MINUTES, mesmo que o takeover já
     * tenha expirado ou sido liberado (cobrindo race conditions de timing de webhook).
     *
     * Isso evita que a mensagem de "fora do expediente" seja enviada quando o dono foi
     * quem iniciou a conversa com o cliente.
     */
    private function wasConversationInitiatedByAttendant(int $companyId, string $phone): bool
    {
        try {
            $windowMinutes = self::HUMAN_TAKEOVER_EXPIRY_MINUTES;
            $stmt = $this->db->prepare("
                SELECT id FROM whatsapp_human_takeover
                WHERE company_id = ?
                  AND phone = ?
                  AND last_human_activity_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
                LIMIT 1
            ");
            $stmt->execute([$companyId, $phone, $windowMinutes]);
            return (bool) $stmt->fetch();
        } catch (\Throwable $e) {
            // Tabela ainda não existe — sem histórico de takeover, não bloquear
            return false;
        }
    }
    
    /**
     * Cancela retries pendentes na fila de fallback quando humano assume
     */
    private function cancelPendingRetriesForPhone(int $companyId, string $phone): void
    {
        try {
            // Cancelar na whatsapp_failed_queue
            $stmt = $this->db->prepare("
                UPDATE whatsapp_failed_queue 
                SET status = 'abandoned',
                    last_error = 'Cancelado: atendimento humano ativado',
                    updated_at = NOW()
                WHERE company_id = ? 
                    AND remote_jid LIKE CONCAT(?, '%')
                    AND status IN ('pending', 'retrying')
            ");
            $stmt->execute([$companyId, $phone]);
            $cancelled = $stmt->rowCount();
            
            if ($cancelled > 0) {
                error_log("[Webhook Evolution] {$cancelled} retry(s) cancelado(s) na fila de fallback por takeover humano para {$phone}");
            }
            
            // Cancelar mensagens pendentes de LID também
            $stmt2 = $this->db->prepare("
                UPDATE pending_out_of_hours_messages 
                SET status = 'cancelled'
                WHERE company_id = ? 
                    AND status = 'pending'
                    AND lid IN (
                        SELECT REPLACE(remote_jid, '@lid', '') 
                        FROM whatsapp_human_takeover 
                        WHERE company_id = ? AND phone = ? AND status = 'active'
                    )
            ");
            $stmt2->execute([$companyId, $companyId, $phone]);
        } catch (\Throwable $e) {
            // Tabelas podem não existir ainda
            error_log("[Webhook Evolution] Erro ao cancelar retries pendentes: " . $e->getMessage());
        }
    }
    
    /**
     * Busca company_id pelo nome da instância
     */
    private function getCompanyByInstance(string $instanceName): ?int
    {
        // Buscar na tabela de engajamento
        $stmt = $this->db->prepare("
            SELECT company_id FROM customer_engagement_config 
            WHERE instance_name = ? 
            LIMIT 1
        ");
        $stmt->execute([$instanceName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return (int) $result['company_id'];
        }
        
        // Fallback: buscar na tabela de instâncias
        $stmt = $this->db->prepare("
            SELECT company_id FROM evolution_instances 
            WHERE instance_identifier = ? 
            LIMIT 1
        ");
        $stmt->execute([$instanceName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? (int) $result['company_id'] : null;
    }
    
    /**
     * Salva mensagem recebida no banco
     */
    private function saveReceivedMessage(
        int $companyId, 
        string $instanceName, 
        string $remoteJid, 
        string $phone, 
        string $messageId, 
        string $messageText, 
        int $messageTimestamp
    ): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_received_messages 
                (company_id, instance_name, remote_jid, phone, message_id, message_text, message_timestamp)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    message_text = VALUES(message_text),
                    received_at = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([
                $companyId,
                $instanceName,
                $remoteJid,
                $phone,
                $messageId,
                $messageText,
                $messageTimestamp
            ]);
        } catch (PDOException $e) {
            error_log("[Webhook Evolution] Erro ao salvar mensagem: " . $e->getMessage());
        }
    }
    
    /**
     * Busca telefone pelo pushName (nome do contato) na tabela de clientes
     * Útil para resolver LIDs quando o nome é conhecido
     */
    private function getPhoneByPushName(int $companyId, string $pushName, ?string $instanceName = null): ?string
    {
        // Buscar APENAS no mapeamento exato pushName -> phone (previamente salvo quando 
        // o mesmo contato mandou mensagem via @s.whatsapp.net, que tem o número real).
        // REMOVIDO: busca fuzzy por LIKE/SOUNDEX na tabela customers — resolvia para 
        // clientes errados com nomes similares (ex: dois "João" resultava em mensagem 
        // enviada para o cliente errado).
        try {
            // Prioridade 1: buscar com instance_name específico
            if (!empty($instanceName)) {
                $stmt = $this->db->prepare("
                    SELECT phone FROM whatsapp_pushname_mapping 
                    WHERE push_name = ? AND instance_name = ?
                    ORDER BY updated_at DESC
                    LIMIT 1
                ");
                $stmt->execute([$pushName, $instanceName]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result && !empty($result['phone'])) {
                    error_log("[Webhook Evolution] pushName '{$pushName}' -> encontrado no mapeamento (instance={$instanceName}): {$result['phone']}");
                    return $result['phone'];
                }
            }
            
            // Prioridade 2: buscar sem filtro de instância (pode ser de outra instância)
            $stmt = $this->db->prepare("
                SELECT phone FROM whatsapp_pushname_mapping 
                WHERE push_name = ?
                ORDER BY updated_at DESC
                LIMIT 1
            ");
            $stmt->execute([$pushName]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && !empty($result['phone'])) {
                error_log("[Webhook Evolution] pushName '{$pushName}' -> encontrado no mapeamento (global): {$result['phone']}");
                return $result['phone'];
            }
        } catch (PDOException $e) {
            // Tabela pode não existir ainda
            error_log("[Webhook Evolution] Erro ao buscar pushName mapping: " . $e->getMessage());
        }
        
        // Não retorna nada se não houver mapeamento exato — melhor não responder
        // do que responder para o cliente errado
        error_log("[Webhook Evolution] pushName '{$pushName}' -> nenhum mapeamento encontrado, não resolvendo");
        return null;
    }
    
    /**
     * Garante schema normalizado contacts + lid_mapping.
     * Idempotente — usa CREATE TABLE IF NOT EXISTS + ALTER com try/catch.
     */
    private function ensureLidSchema(): void
    {
        static $done = false;
        if ($done) return;

        // 1. Tabela de contatos canônicos
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS whatsapp_contacts (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                phone VARCHAR(20) NOT NULL,
                instance_name VARCHAR(100) NOT NULL,
                company_id INT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_phone_inst_co (phone, instance_name, company_id),
                INDEX idx_company (company_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 2. Evoluir lid_mapping existente (add colunas se ausentes)
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS whatsapp_lid_mapping (
                id INT AUTO_INCREMENT PRIMARY KEY,
                lid VARCHAR(50) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                instance_name VARCHAR(100) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_lid (lid, instance_name),
                INDEX idx_phone (phone)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $addCol = function(string $sql): void {
            try { $this->db->exec($sql); } catch (\Throwable $e) { /* já existe */ }
        };
        $addCol("ALTER TABLE whatsapp_lid_mapping ADD COLUMN contact_id BIGINT NULL AFTER lid");
        $addCol("ALTER TABLE whatsapp_lid_mapping ADD COLUMN company_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER instance_name");
        $addCol("ALTER TABLE whatsapp_lid_mapping ADD COLUMN confidence ENUM('HIGH','LOW') NOT NULL DEFAULT 'HIGH' AFTER company_id");
        $addCol("ALTER TABLE whatsapp_lid_mapping ADD COLUMN source ENUM('webhook','manual') NOT NULL DEFAULT 'webhook' AFTER confidence");
        $addCol("ALTER TABLE whatsapp_lid_mapping ADD COLUMN updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        $addCol("ALTER TABLE whatsapp_lid_mapping ADD INDEX idx_contact_id (contact_id)");
        $addCol("ALTER TABLE whatsapp_lid_mapping ADD INDEX idx_company_id (company_id)");
        $addCol("ALTER TABLE whatsapp_lid_mapping ADD INDEX idx_confidence (confidence)");

        // 3. Migração one-time: backfill contacts + contact_id de registros legados
        try {
            // Garantir collation correta nas tabelas existentes
            $addCol("ALTER TABLE whatsapp_contacts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $addCol("ALTER TABLE whatsapp_lid_mapping CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            $pending = (int) $this->db->query(
                "SELECT COUNT(*) FROM whatsapp_lid_mapping WHERE contact_id IS NULL AND phone != ''"
            )->fetchColumn();
            if ($pending > 0) {
                // Criar contatos a partir de dados existentes
                $this->db->exec("
                    INSERT IGNORE INTO whatsapp_contacts (phone, instance_name, company_id, created_at)
                    SELECT m.phone, m.instance_name,
                           COALESCE((SELECT cec.company_id FROM customer_engagement_config cec WHERE cec.instance_name = m.instance_name LIMIT 1), 0),
                           m.created_at
                    FROM whatsapp_lid_mapping m
                    WHERE m.contact_id IS NULL AND m.phone != ''
                ");
                // Linkar contact_id
                $this->db->exec("
                    UPDATE whatsapp_lid_mapping m
                    JOIN whatsapp_contacts c ON c.phone = m.phone AND c.instance_name = m.instance_name COLLATE utf8mb4_unicode_ci
                    SET m.contact_id = c.id,
                        m.company_id = c.company_id
                    WHERE m.contact_id IS NULL
                ");
                error_log("[Webhook Evolution] LID schema migrado: {$pending} registros legados vinculados a whatsapp_contacts");
            }
        } catch (\Throwable $e) {
            error_log("[Webhook Evolution] Erro na migração LID schema: " . $e->getMessage());
        }

        $done = true;
    }

    /**
     * Busca telefone pelo LID no mapeamento local.
     * Escopo ESTRITO: instance_name obrigatório, sem fallback cross-instance.
     * SEGURANÇA: Retorna SOMENTE mapeamentos HIGH confidence.
     *            LOW confidence NUNCA é retornado — evita envio para pessoa errada.
     */
    private function getPhoneByLid(string $lid, string $instanceName): ?string
    {
        $this->ensureLidSchema();

        // SOMENTE HIGH confidence — LOW nunca dispara envio automático
        $stmt = $this->db->prepare("
            SELECT c.phone
            FROM whatsapp_lid_mapping m
            JOIN whatsapp_contacts c ON c.id = m.contact_id
            WHERE m.lid = ? AND m.instance_name = ? AND m.confidence = 'HIGH'
            LIMIT 1
        ");
        $stmt->execute([$lid, $instanceName]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $row['phone'];
        }

        // Log se existe LOW mas não HIGH — para diagnóstico
        $stmtLow = $this->db->prepare("
            SELECT c.phone FROM whatsapp_lid_mapping m
            JOIN whatsapp_contacts c ON c.id = m.contact_id
            WHERE m.lid = ? AND m.instance_name = ? AND m.confidence = 'LOW'
            LIMIT 1
        ");
        $stmtLow->execute([$lid, $instanceName]);
        if ($stmtLow->fetch()) {
            error_log("[Webhook Evolution] LID {$lid} tem mapeamento LOW mas não HIGH — não será usado para envio automático");
        }

        // Fallback legado: registros ainda sem contact_id (migração parcial)
        // Estes são pre-confidence e tratados como HIGH (vieram de sender/participant)
        $stmt = $this->db->prepare("
            SELECT phone FROM whatsapp_lid_mapping
            WHERE lid = ? AND instance_name = ? AND contact_id IS NULL AND phone != ''
            LIMIT 1
        ");
        $stmt->execute([$lid, $instanceName]);
        $legacy = $stmt->fetch(PDO::FETCH_ASSOC);

        return $legacy ? $legacy['phone'] : null;
    }
    
    /**
     * Busca telefone pelo LID na Evolution API e salva mapeamento
     * Usa a profilePicUrl para correlacionar LID com número real
     */
    private function lookupPhoneByLid(string $lid, string $instanceName): ?string
    {
        // Buscar configuração da Evolution API
        $stmt = $this->db->prepare("
            SELECT c.evolution_server_url, c.evolution_api_key 
            FROM companies c
            JOIN customer_engagement_config cec ON cec.company_id = c.id
            WHERE cec.instance_name = ?
            LIMIT 1
        ");
        $stmt->execute([$instanceName]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$config || empty($config['evolution_server_url'])) {
            return null;
        }
        
        $server = rtrim($config['evolution_server_url'], '/');
        $apiKey = $config['evolution_api_key'];
        
        // 1. Buscar o chat do LID para pegar a profilePicUrl
        $url = $server . '/chat/findChats/' . $instanceName;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'apikey: ' . $apiKey
            ],
            CURLOPT_TIMEOUT => 5,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['where' => ['remoteJid' => $lid . '@lid']])
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $lidChat = json_decode($response, true);
        
        if (empty($lidChat) || !is_array($lidChat) || empty($lidChat[0]['profilePicUrl'])) {
            error_log("[Webhook Evolution] LID {$lid} não tem profilePicUrl");
            return null;
        }
        
        // Extrair o ID da imagem da URL (parte única que identifica o usuário)
        $lidPicUrl = $lidChat[0]['profilePicUrl'];
        preg_match('/\/([0-9_]+n\.jpg)/', $lidPicUrl, $matches);
        $picId = $matches[1] ?? null;
        
        if (!$picId) {
            error_log("[Webhook Evolution] Não foi possível extrair ID da foto do LID {$lid}");
            return null;
        }
        
        error_log("[Webhook Evolution] Buscando phone para LID {$lid} com picId: {$picId}");
        
        // 2. Buscar todos os chats com @s.whatsapp.net e comparar profilePicUrl
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'apikey: ' . $apiKey
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([])
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $allChats = json_decode($response, true);
        
        if (!is_array($allChats)) {
            return null;
        }
        
        // Procurar chat com @s.whatsapp.net que tenha a mesma foto de perfil
        foreach ($allChats as $chat) {
            $remoteJid = $chat['remoteJid'] ?? '';
            $chatPicUrl = $chat['profilePicUrl'] ?? '';
            
            // Só considerar chats com número real
            if (strpos($remoteJid, '@s.whatsapp.net') === false) {
                continue;
            }
            
            // Verificar se a foto é a mesma
            if (!empty($chatPicUrl) && strpos($chatPicUrl, $picId) !== false) {
                $phone = normalizePhone(preg_replace('/@.*$/', '', $remoteJid));
                
                // IMPORTANTE: Ignorar se for o número da própria instância
                if ($this->isInstanceOwnerNumber($phone, $instanceName)) {
                    error_log("[Webhook Evolution] Ignorando correspondência de foto - é o número da instância: {$phone}");
                    continue;
                }
                
                error_log("[Webhook Evolution] Encontrado mapeamento via foto: LID {$lid} -> {$phone} (LOW confidence)");
                
                // Salvar mapeamento com LOW confidence (profilePic é heurístico)
                $this->saveLidMapping($lid, $phone, $instanceName, 'LOW', 'manual');
                
                return $phone;
            }
        }
        
        error_log("[Webhook Evolution] Não encontrou phone para LID {$lid} via foto de perfil");
        return null;
    }
    
    /**
     * Verifica se um número pertence à própria instância/empresa
     * Isso é necessário porque a Evolution API às vezes retorna o ownerJid incorretamente
     */
    private function isInstanceOwnerNumber(string $phone, string $instanceName): bool
    {
        // Normalizar ambos para comparação consistente
        $cleanPhone = normalizePhone($phone);
        
        // Buscar o número do WhatsApp da empresa associada a esta instância
        $stmt = $this->db->prepare("
            SELECT c.whatsapp 
            FROM companies c
            JOIN customer_engagement_config cec ON cec.company_id = c.id
            WHERE cec.instance_name = ?
            LIMIT 1
        ");
        $stmt->execute([$instanceName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || empty($result['whatsapp'])) {
            return false;
        }
        
        $ownerPhone = normalizePhone($result['whatsapp']);
        
        // Comparação direta após normalização
        if ($cleanPhone === $ownerPhone) {
            return true;
        }
        
        // Verificar variação sem 9 (formato antigo brasileiro)
        if (strlen($cleanPhone) >= 12 && strlen($ownerPhone) >= 12) {
            // Remove DDI + DDD (primeiros 4 dígitos) e compara
            $phoneCore = substr($cleanPhone, 4);
            $ownerCore = substr($ownerPhone, 4);
            
            // Se um tem 9 inicial e outro não, comparar o core sem 9
            if (strlen($phoneCore) === 9 && substr($phoneCore, 0, 1) === '9') {
                $phoneCore = substr($phoneCore, 1);
            }
            if (strlen($ownerCore) === 9 && substr($ownerCore, 0, 1) === '9') {
                $ownerCore = substr($ownerCore, 1);
            }
            
            if ($phoneCore === $ownerCore) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Salva mapeamento LID -> Phone via modelo normalizado contacts + lid_mapping.
     *
     * @param string $confidence 'HIGH' = evidência determinística (sender/participant JID)
     *                           'LOW'  = heurístico (profilePic, engagement discovery)
     * @param string $source     'webhook' = veio do processamento de webhook
     *                           'manual'  = profilePic lookup, engagement, admin
     */
    private function saveLidMapping(string $lid, string $phone, string $instanceName, string $confidence = 'HIGH', string $source = 'webhook'): void
    {
        // Guard: não mapear para o número da própria instância (ownerJid bug)
        if ($this->isInstanceOwnerNumber($phone, $instanceName)) {
            error_log("[Webhook Evolution] REJEITADO: Não salvando mapeamento LID {$lid} -> {$phone} (é o número da própria instância)");
            return;
        }

        $this->ensureLidSchema();

        try {
            // Resolver company_id
            $companyId = $this->getCompanyByInstance($instanceName) ?? 0;

            // 1. Upsert contato canônico
            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_contacts (phone, instance_name, company_id)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)
            ");
            $stmt->execute([$phone, $instanceName, $companyId]);
            $contactId = (int) $this->db->lastInsertId();

            // 2. Upsert LID mapping
            //    Se o registro já existe com HIGH e a nova evidência é LOW, não rebaixar.
            //    Se o existente é LOW e o novo é HIGH, promover.
            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_lid_mapping
                    (lid, contact_id, phone, instance_name, company_id, confidence, source)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    contact_id = VALUES(contact_id),
                    phone      = VALUES(phone),
                    company_id = VALUES(company_id),
                    confidence = IF(VALUES(confidence) = 'HIGH' OR confidence = 'LOW', VALUES(confidence), confidence),
                    source     = IF(VALUES(confidence) = 'HIGH' OR confidence = 'LOW', VALUES(source), source)
            ");
            $stmt->execute([$lid, $contactId, $phone, $instanceName, $companyId, $confidence, $source]);

            error_log("[Webhook Evolution] LID_MAP: {$lid} -> {$phone} (contact_id={$contactId}, confidence={$confidence}, source={$source}, company={$companyId})");

            // SEGURANÇA: Só processar pendentes se evidência é HIGH (determinística).
            // LOW confidence (profilePic/heurístico) NUNCA deve disparar envio.
            if ($confidence === 'HIGH') {
                $this->processPendingMessagesForLid($lid, $phone, $instanceName);
            } else {
                error_log("[Webhook Evolution] LID_MAP LOW: {$lid} -> pendentes NÃO processados (confidence={$confidence}, requer HIGH)");
            }
        } catch (PDOException $e) {
            error_log("[Webhook Evolution] Erro ao salvar mapeamento LID: " . $e->getMessage());
        }
    }
    
    /**
     * Salva mapeamento pushName -> Phone para uso futuro em LIDs
     */
    private function savePushNameMapping(string $pushName, string $phone, string $instanceName): void
    {
        if (empty($pushName) || empty($phone)) {
            return;
        }
        
        try {
            // Criar tabela se não existir
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS whatsapp_pushname_mapping (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    push_name VARCHAR(255) NOT NULL,
                    phone VARCHAR(20) NOT NULL,
                    instance_name VARCHAR(100) NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_pushname_instance (push_name(191), instance_name),
                    INDEX idx_phone (phone)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_pushname_mapping (push_name, phone, instance_name)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE phone = VALUES(phone), updated_at = NOW()
            ");
            $stmt->execute([$pushName, $phone, $instanceName]);
        } catch (PDOException $e) {
            // Silenciar erros - não é crítico
            error_log("[Webhook Evolution] Erro ao salvar mapeamento pushName: " . $e->getMessage());
        }
    }
    
    /**
     * Salva mensagem pendente para LID não resolvido
     * Será enviada quando o mapeamento for criado (ex: quando o cliente fizer um pedido)
     */
    private function savePendingOutOfHoursMessage(int $companyId, string $instanceName, string $remoteJid, string $message): void
    {
        try {
            // Criar tabela se não existir
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS pending_out_of_hours_messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    company_id INT NOT NULL,
                    instance_name VARCHAR(100) NOT NULL,
                    lid VARCHAR(50) NOT NULL,
                    message TEXT NOT NULL,
                    status ENUM('pending', 'sent', 'expired', 'failed') DEFAULT 'pending',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    sent_at DATETIME NULL,
                    INDEX idx_lid_status (lid, status),
                    INDEX idx_company_status (company_id, status),
                    INDEX idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // Extrair LID puro do remoteJid
            $lid = preg_replace('/@.*$/', '', $remoteJid);
            
            // PROTEÇÃO ANTI-FLOOD:
            // 1. Dedup por conteúdo (mesmo LID + mesmo conteúdo = ignora)
            // 2. Limite máximo por LID (max 3 pendentes)
            // 3. TTL implícito (30min para dedup, 24h para processamento)
            
            // Check 1: Mensagem idêntica recente para este LID
            $contentHash = md5($message);
            $stmt = $this->db->prepare("
                SELECT id FROM pending_out_of_hours_messages 
                WHERE company_id = ? AND lid = ? AND status = 'pending'
                AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                AND MD5(message) = ?
                LIMIT 1
            ");
            $stmt->execute([$companyId, $lid, $contentHash]);
            if ($stmt->fetch()) {
                error_log("[Webhook Evolution] Mensagem pendente duplicada para LID {$lid} — ignorando");
                return;
            }
            
            // Check 2: Limite máximo de pendentes por LID (evita acúmulo por spam/retry)
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as cnt FROM pending_out_of_hours_messages 
                WHERE lid = ? AND status = 'pending'
            ");
            $stmt->execute([$lid]);
            $count = (int)($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);
            if ($count >= 3) {
                error_log("[Webhook Evolution] Limite de pendentes atingido para LID {$lid} ({$count}/3) — ignorando");
                return;
            }
            
            // Check 3: Expirar pendentes antigos (TTL de 24h) — cleanup oportunístico
            $this->db->exec("
                UPDATE pending_out_of_hours_messages 
                SET status = 'expired' 
                WHERE status = 'pending' 
                AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            
            $stmt = $this->db->prepare("
                INSERT INTO pending_out_of_hours_messages (company_id, instance_name, lid, message)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$companyId, $instanceName, $lid, $message]);
            
            error_log("[Webhook Evolution] Mensagem pendente salva para LID {$lid} - será enviada quando o mapeamento for criado");
        } catch (PDOException $e) {
            error_log("[Webhook Evolution] Erro ao salvar mensagem pendente: " . $e->getMessage());
        }
    }
    
    /**
     * Processa mensagens pendentes para um LID que acabou de ter mapeamento criado
     * Chamado quando saveLidMapping é executado com sucesso
     */
    public function processPendingMessagesForLid(string $lid, string $phone, string $instanceName): void
    {
        try {
            // SEGURANÇA: Re-validar que o mapeamento atual é HIGH confidence
            // Protege contra race condition: LOW gravado entre o caller e este método
            $checkStmt = $this->db->prepare("
                SELECT m.confidence FROM whatsapp_lid_mapping m
                WHERE m.lid = ? AND m.instance_name = ?
                ORDER BY FIELD(m.confidence, 'HIGH', 'LOW') LIMIT 1
            ");
            $checkStmt->execute([$lid, $instanceName]);
            $currentMapping = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if (!$currentMapping || $currentMapping['confidence'] !== 'HIGH') {
                error_log("[Webhook Evolution] BLOQUEADO: pendentes para LID {$lid} não enviados — mapeamento atual não é HIGH (" . ($currentMapping['confidence'] ?? 'null') . ")");
                return;
            }

            // Buscar mensagens pendentes ESCOPO instance_name (evita cross-tenant)
            $stmt = $this->db->prepare("
                SELECT id, company_id, message 
                FROM pending_out_of_hours_messages 
                WHERE lid = ? AND instance_name = ? AND status = 'pending'
                AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute([$lid, $instanceName]);
            $pendingMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($pendingMessages)) {
                return;
            }
            
            error_log("[Webhook Evolution] Encontradas " . count($pendingMessages) . " mensagens pendentes para LID {$lid} (confidence=HIGH verificado)");
            
            foreach ($pendingMessages as $pending) {
                $success = $this->sendWhatsAppMessage($pending['company_id'], $instanceName, $phone, $pending['message'], 'pending_lid');
                
                // Atualizar status
                $newStatus = $success ? 'sent' : 'failed';
                $updateStmt = $this->db->prepare("
                    UPDATE pending_out_of_hours_messages 
                    SET status = ?, sent_at = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([$newStatus, $pending['id']]);
                
                if ($success) {
                    // Registrar cooldown
                    $this->logOutOfHoursResponse($pending['company_id'], $phone);
                    error_log("[Webhook Evolution] Mensagem pendente enviada com sucesso para {$phone}");
                } else {
                    error_log("[Webhook Evolution] Falha ao enviar mensagem pendente para {$phone}");
                }
            }
        } catch (PDOException $e) {
            error_log("[Webhook Evolution] Erro ao processar mensagens pendentes: " . $e->getMessage());
        }
    }
    
    /**
     * IDEMPOTÊNCIA INSERT-FIRST: Tenta reservar o message_id via INSERT IGNORE.
     * Retorna true = reservado com sucesso (pode processar).
     * Retorna false = já existia (duplicata, ignorar).
     *
     * Thread-safe: o UNIQUE constraint do banco garante que apenas 1 thread
     * consegue inserir. Todas as outras recebem rowCount=0.
     * Usa tabela dedicada (leve, sem texto) para o lock ser rápido.
     */
    private function claimMessageForProcessing(string $instanceName, string $messageId): bool
    {
        try {
            // Tabela dedicada para lock de idempotência (criada uma vez)
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS whatsapp_message_lock (
                    instance_name VARCHAR(100) NOT NULL,
                    message_id VARCHAR(100) NOT NULL,
                    claimed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (instance_name, message_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO whatsapp_message_lock (instance_name, message_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$instanceName, $messageId]);
            
            // rowCount=1 → inseriu (somos os primeiros). rowCount=0 → já existia.
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            // Em caso de erro, permitir processamento (fail-open para não perder mensagens)
            error_log("[Webhook Evolution] Erro no claim de idempotência: " . $e->getMessage());
            return true;
        }
    }
    
    /**
     * Enfileira payload do webhook para processamento assíncrono.
     * INSERT puro — zero processamento. Retorna true se enfileirou.
     */
    private function enqueueWebhook(string $instanceName, string $messageId, string $rawPayload): bool
    {
        try {
            $this->ensureQueueTable();
            
            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_webhook_queue 
                    (instance_name, message_id, payload, status, priority)
                VALUES (?, ?, ?, 'pending', 1)
            ");
            $stmt->execute([$instanceName, $messageId, $rawPayload]);
            return true;
        } catch (\Throwable $e) {
            error_log("[Webhook Evolution] Erro ao enfileirar webhook: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cria tabela de fila se não existir (idempotente, cached em static var).
     */
    private function ensureQueueTable(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS whatsapp_webhook_queue (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                instance_name VARCHAR(100) NOT NULL,
                message_id VARCHAR(100) NOT NULL DEFAULT '',
                payload MEDIUMTEXT NOT NULL,
                status ENUM('pending','processing','done','failed','expired','dlq') NOT NULL DEFAULT 'pending',
                attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
                last_error VARCHAR(500) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                started_at DATETIME NULL,
                completed_at DATETIME NULL,
                heartbeat_at DATETIME NULL,
                INDEX idx_status_created (status, created_at),
                INDEX idx_instance_message (instance_name, message_id),
                INDEX idx_completed (completed_at),
                INDEX idx_heartbeat (status, heartbeat_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Migration: coluna priority para priorização de tarefas
        try {
            $this->db->exec("ALTER TABLE whatsapp_webhook_queue ADD COLUMN priority TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER status");
            $this->db->exec("ALTER TABLE whatsapp_webhook_queue ADD INDEX idx_status_priority (status, priority, created_at)");
        } catch (\Throwable $e) {
            // Coluna já existe — ignorar
        }
        
        $ensured = true;
    }
    
    /**
     * BINDING POSTERIOR DE LID: Quando um cliente envia mensagem via @s.whatsapp.net,
     * verifica se existem LIDs pendentes (não resolvidos) que podem pertencer a este phone.
     * 
     * Estratégia CONSERVADORA (sem profilePic — evita falso positivo):
     * O binding só acontece quando existe evidência DETERMINÍSTICA:
     * - O sender/participant do payload LID original continha este phone
     *   (já resolvido na hora pelo processMessageUpsert → saveLidMapping)
     * 
     * Para LIDs sem evidência direta, mantém pendente.
     * O binding automático só ocorre se a Evolution enviar um evento futuro
     * que contenha o número real junto com o LID (sender, participant).
     * 
     * profilePic matching foi REMOVIDO:
     * - 2 contatos sem foto → mesmo resultado → falso positivo
     * - Foto padrão do WhatsApp → match errado
     * - Mensagem enviada para cliente errado = pior que não enviar
     */
    private function attemptLidBindingByIdentity(string $phone, string $pushName, string $instanceName): void
    {
        // Binding posterior conservador: NÃO tenta adivinhar.
        // LIDs são resolvidos apenas por evidência determinística:
        // 1. sender JID no payload (já tratado em processMessageUpsert)
        // 2. participant JID no payload (já tratado em processMessageUpsert)
        // 3. Mapeamento existente no banco (getPhoneByLid)
        //
        // Se nenhuma dessas fontes resolveu, o LID fica pendente.
        // Mensagens pendentes serão enviadas quando um evento futuro
        // trouxer evidência real (saveLidMapping → processPendingMessagesForLid).
        //
        // NÃO usar: pushName (não único), profilePic (falso positivo sem foto).
        return;
    }
    
    /**
     * Responde em JSON
     */
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
