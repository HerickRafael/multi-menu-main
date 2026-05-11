<?php
/**
 * whatsapp_retry_cron.php
 * 
 * Reprocessa mensagens WhatsApp que falharam em todas as tentativas imediatas.
 * Lê da tabela whatsapp_failed_queue e tenta reenviar com backoff crescente.
 * 
 * Rodar via cron a cada 5 minutos:
 *   * /5 * * * * php /var/www/html/scripts/whatsapp_retry_cron.php >> /var/www/html/storage/logs/whatsapp_retry.log 2>&1
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/core/Helpers.php';
require_once __DIR__ . '/../app/services/WhatsAppSendService.php';

$startTime = microtime(true);
$processedCount = 0;
$sentCount = 0;
$failedCount = 0;
$abandonedCount = 0;

echo "[" . date('Y-m-d H:i:s') . "] Iniciando reprocessamento de mensagens WhatsApp falhas...\n";

try {
    $db = db();
} catch (\Throwable $e) {
    echo "ERRO: Falha ao conectar ao banco: " . $e->getMessage() . "\n";
    exit(1);
}

// Buscar mensagens pendentes cujo next_retry_at já passou
try {
    $stmt = $db->prepare("
        SELECT fq.id, fq.company_id, fq.instance_name, fq.remote_jid, fq.message, 
               fq.message_type, fq.retry_count, fq.max_retries,
               c.evolution_server_url, c.evolution_api_key
        FROM whatsapp_failed_queue fq
        JOIN companies c ON c.id = fq.company_id
        WHERE fq.status = 'pending'
          AND fq.next_retry_at <= NOW()
          AND fq.retry_count < fq.max_retries
        ORDER BY fq.next_retry_at ASC
        LIMIT 50
    ");
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    echo "ERRO: Falha ao buscar fila: " . $e->getMessage() . "\n";
    // Tabela pode não existir ainda
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        echo "Tabela whatsapp_failed_queue não existe. Rode a migration primeiro.\n";
    }
    exit(1);
}

if (empty($messages)) {
    echo "Nenhuma mensagem para reprocessar.\n";
    exit(0);
}

echo "Encontradas " . count($messages) . " mensagens para reprocessar.\n";

// Marcar como 'retrying' para evitar duplo processamento
$ids = array_column($messages, 'id');
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$db->prepare("UPDATE whatsapp_failed_queue SET status = 'retrying' WHERE id IN ({$placeholders})")->execute($ids);

foreach ($messages as $msg) {
    $processedCount++;
    $msgId = $msg['id'];
    $targetNumber = normalizePhone(preg_replace('/@.*$/', '', $msg['remote_jid']));
    
    echo "  [{$msgId}] Reenviando para {$targetNumber} (tentativa " . ($msg['retry_count'] + 1) . "/" . $msg['max_retries'] . ")... ";
    
    // HUMAN TAKEOVER CHECK — se humano está atendendo, não reenviar automação
    // Exceção: notificações de pedido (message_type='notification') sempre passam
    if ($msg['message_type'] !== 'notification') {
        // ENGAGEMENT HARD BLOCK — mensagens de engagement só reenviam se sistema ativo E dentro do horário
        if ($msg['message_type'] === 'engagement') {
            try {
                $engStmt = $db->prepare("SELECT enabled FROM customer_engagement_config WHERE company_id = ? LIMIT 1");
                $engStmt->execute([$msg['company_id']]);
                $engCfg = $engStmt->fetch(PDO::FETCH_ASSOC);
                if (!$engCfg || (int)$engCfg['enabled'] !== 1) {
                    echo "SKIP (engajamento desativado)\n";
                    $db->prepare("
                        UPDATE whatsapp_failed_queue 
                        SET status = 'abandoned', last_error = 'Cancelado: engagement desabilitado', updated_at = NOW()
                        WHERE id = ?
                    ")->execute([$msgId]);
                    $abandonedCount++;
                    continue;
                }
                // Verificar horário de funcionamento
                $brasiliaTime = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
                $weekday = (int)$brasiliaTime->format('N');
                $currentTime = $brasiliaTime->format('H:i:s');
                $hoursStmt = $db->prepare("SELECT is_open, open1, close1, open2, close2 FROM company_hours WHERE company_id = ? AND weekday = ?");
                $hoursStmt->execute([$msg['company_id'], $weekday]);
                $hours = $hoursStmt->fetch(PDO::FETCH_ASSOC);
                $withinHours = false;
                if ($hours && (int)$hours['is_open']) {
                    if (!empty($hours['open1']) && !empty($hours['close1'])) {
                        $open1 = $hours['open1']; $close1 = $hours['close1'];
                        if ($open1 <= $close1) { $withinHours = ($currentTime >= $open1 && $currentTime <= $close1); }
                        else { $withinHours = ($currentTime >= $open1 || $currentTime <= $close1); }
                    }
                    if (!$withinHours && !empty($hours['open2']) && !empty($hours['close2'])) {
                        $open2 = $hours['open2']; $close2 = $hours['close2'];
                        if ($open2 <= $close2) { $withinHours = ($currentTime >= $open2 && $currentTime <= $close2); }
                        else { $withinHours = ($currentTime >= $open2 || $currentTime <= $close2); }
                    }
                }
                if (!$withinHours) {
                    echo "SKIP (fora do horário de funcionamento)\n";
                    $db->prepare("UPDATE whatsapp_failed_queue SET status = 'pending', last_error = 'Adiado: fora do horário', updated_at = NOW() WHERE id = ?")->execute([$msgId]);
                    $processedCount--;
                    continue;
                }
            } catch (\Throwable $e) {
                error_log("[WhatsApp Retry] Erro verificando engagement para {$msgId}: " . $e->getMessage());
            }
        }
        
        try {
            $takeoverStmt = $db->prepare("
                SELECT id FROM whatsapp_human_takeover 
                WHERE company_id = ? AND phone = ? AND status = 'active' AND expires_at > NOW()
                LIMIT 1
            ");
            $takeoverStmt->execute([$msg['company_id'], $targetNumber]);
            if ($takeoverStmt->fetch()) {
                echo "SKIP (atendimento humano ativo)\n";
                $db->prepare("
                    UPDATE whatsapp_failed_queue 
                    SET status = 'abandoned', last_error = 'Cancelado: atendimento humano ativo', updated_at = NOW()
                    WHERE id = ?
                ")->execute([$msgId]);
                $abandonedCount++;
                continue;
            }
        } catch (\Throwable $e) {
            // Tabela pode não existir — prossegue normalmente
        }
    }
    
    if (empty($msg['evolution_server_url']) || empty($msg['evolution_api_key'])) {
        echo "SKIP (config Evolution ausente)\n";
        $db->prepare("UPDATE whatsapp_failed_queue SET status = 'abandoned', updated_at = NOW() WHERE id = ?")->execute([$msgId]);
        $abandonedCount++;
        continue;
    }
    
    // Montar e enviar request
    $server = rtrim($msg['evolution_server_url'], '/');
    $url = "{$server}/message/sendText/{$msg['instance_name']}";
    
    $isLid = strpos($msg['remote_jid'], '@lid') !== false;
    $payload = json_encode([
        'number' => $isLid ? $msg['remote_jid'] : $targetNumber,
        'text' => $msg['message'],
        'delay' => 1200
    ]);
    
    $startReq = microtime(true);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'apikey: ' . $msg['evolution_api_key']
        ],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    $durationMs = (int)((microtime(true) - $startReq) * 1000);
    
    $success = !$curlError && $httpCode >= 200 && $httpCode < 300;
    
    // Log no whatsapp_send_log (com message_id)
    $sentMessageId = null;
    if ($success) {
        $decoded = json_decode($response, true);
        $sentMessageId = $decoded['key']['id'] ?? null;
    }
    try {
        $logStmt = $db->prepare("
            INSERT INTO whatsapp_send_log 
                (company_id, instance_name, remote_jid, phone, message_type, message_preview,
                 attempt, status, http_code, curl_error, api_response, sent_message_id, duration_ms)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $logStmt->execute([
            $msg['company_id'],
            $msg['instance_name'],
            $msg['remote_jid'],
            $targetNumber,
            $msg['message_type'] . '_retry',
            mb_substr($msg['message'], 0, 200),
            $msg['retry_count'] + 1,
            $success ? 'success' : 'failed',
            $httpCode ?: null,
            $curlError ?: null,
            $response ? mb_substr($response, 0, 2000) : null,
            $sentMessageId,
            $durationMs
        ]);
    } catch (\Throwable $e) {
        // Log não impede o fluxo
    }
    
    if ($success) {
        echo "OK ({$durationMs}ms)\n";
        $db->prepare("UPDATE whatsapp_failed_queue SET status = 'sent', updated_at = NOW() WHERE id = ?")->execute([$msgId]);
        $sentCount++;
    } else {
        $newRetryCount = $msg['retry_count'] + 1;
        echo "FALHA (HTTP {$httpCode}, {$durationMs}ms)\n";
        
        if ($newRetryCount >= $msg['max_retries']) {
            // Esgotou todas as tentativas
            echo "    → Máximo de retries atingido. Status: abandoned.\n";
            $db->prepare("
                UPDATE whatsapp_failed_queue 
                SET status = 'abandoned', retry_count = ?, last_error = ?, last_http_code = ?, updated_at = NOW()
                WHERE id = ?
            ")->execute([$newRetryCount, mb_substr(($curlError ?: $response) ?: 'Unknown', 0, 500), $httpCode ?: null, $msgId]);
            $abandonedCount++;
        } else {
            // Agendar próxima tentativa com backoff crescente: 10min, 30min, 60min
            $backoffMinutes = [10, 30, 60][$newRetryCount - 1] ?? 60;
            echo "    → Próximo retry em {$backoffMinutes}min.\n";
            $db->prepare("
                UPDATE whatsapp_failed_queue 
                SET status = 'pending', retry_count = ?, last_error = ?, last_http_code = ?,
                    next_retry_at = DATE_ADD(NOW(), INTERVAL ? MINUTE), updated_at = NOW()
                WHERE id = ?
            ")->execute([$newRetryCount, mb_substr(($curlError ?: $response) ?: 'Unknown', 0, 500), $httpCode ?: null, $backoffMinutes, $msgId]);
            $failedCount++;
        }
    }
    
    // Rate limit entre mensagens (anti-ban)
    usleep(500000); // 500ms entre cada envio
}

$elapsed = round(microtime(true) - $startTime, 2);
echo "\n[" . date('Y-m-d H:i:s') . "] Concluído em {$elapsed}s — Processadas: {$processedCount}, Enviadas: {$sentCount}, Falharam: {$failedCount}, Abandonadas: {$abandonedCount}\n";

// Limpeza periódica de logs antigos (30 dias de retenção)
try {
    $cleanupResult = WhatsAppSendService::getInstance()->cleanupOldLogs(30);
    $totalCleaned = array_sum($cleanupResult);
    if ($totalCleaned > 0) {
        echo "[Cleanup] Removidos: send_log={$cleanupResult['send_log_deleted']}, failed_queue={$cleanupResult['failed_queue_deleted']}, takeover={$cleanupResult['takeover_deleted']}\n";
    }
} catch (\Throwable $e) {
    echo "[Cleanup] Erro: " . $e->getMessage() . "\n";
}
