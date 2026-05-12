<?php
/**
 * CustomerEngagementService
 * 
 * Serviço responsável pelo disparo automático de mensagens via WhatsApp
 * para engajamento de clientes em dois cenários:
 * 1. Cliente cadastrou mas não fez pedido (após 10 min)
 * 2. Cliente inativo há mais de 15 dias
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/WhatsAppSendService.php';
require_once __DIR__ . '/../models/LoyaltyProgram.php';

class CustomerEngagementService
{
    private PDO $db;
    private int $companyId;
    private ?array $config = null;
    private ?array $hours = null;
    private ?array $company = null;
    private int $maxExecutionSeconds = 0;
    private float $executionStartTime = 0;

    /**
     * Verifica se um horário está dentro de um intervalo, incluindo turnos que cruzam meia-noite.
     */
    private function isTimeInRange(string $currentTime, string $openTime, string $closeTime): bool
    {
        if ($openTime <= $closeTime) {
            return ($currentTime >= $openTime && $currentTime <= $closeTime);
        }

        // Ex.: 18:00 -> 02:00
        return ($currentTime >= $openTime || $currentTime <= $closeTime);
    }
    
    public function __construct(int $companyId)
    {
        $this->db = db();
        $this->companyId = $companyId;
        $this->executionStartTime = microtime(true);
    }
    
    /**
     * Define limite de tempo para execução do ciclo desta empresa
     */
    public function setMaxExecutionSeconds(int $seconds): void
    {
        $this->maxExecutionSeconds = $seconds;
    }
    
    /**
     * Verifica se excedeu o tempo máximo de execução por empresa
     */
    private function isTimeBudgetExhausted(): bool
    {
        if ($this->maxExecutionSeconds <= 0) {
            return false;
        }
        return (microtime(true) - $this->executionStartTime) >= $this->maxExecutionSeconds;
    }
    
    /**
     * Retorna a configuração atual (para uso externo)
     */
    public function getConfig(): array
    {
        $config = $this->loadConfig();
        return $config ?: [
            'enabled' => false,
            'instance_name' => '',
            'scenario1_enabled' => true,
            'scenario1_delay_minutes' => 10,
            'scenario2_enabled' => true,
            'scenario2_inactive_days' => 15,
            'min_hours_between_messages' => 24
        ];
    }
    
    /**
     * Carrega configuração do sistema de engajamento.
     * @param bool $forceReload Força releitura do banco (ignora cache em memória)
     */
    private function loadConfig(bool $forceReload = false): ?array
    {
        if (!$forceReload && $this->config !== null) {
            return $this->config;
        }
        
        $stmt = $this->db->prepare("
            SELECT * FROM customer_engagement_config
            WHERE company_id = ? AND enabled = 1
            LIMIT 1
        ");
        $stmt->execute([$this->companyId]);
        $this->config = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        
        return $this->config;
    }

    /**
     * HARD BLOCK: verifica se o sistema está habilitado relendo do banco.
     * Deve ser chamado antes de qualquer operação crítica (envio, agendamento).
     * Defesa em profundidade — mesmo que loadConfig tenha passado antes.
     */
    private function isEngagementEnabled(): bool
    {
        $stmt = $this->db->prepare("
            SELECT enabled FROM customer_engagement_config
            WHERE company_id = ? LIMIT 1
        ");
        $stmt->execute([$this->companyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && (int)$row['enabled'] === 1;
    }
    
    /**
     * Carrega dados da empresa
     */
    private function loadCompany(): ?array
    {
        if ($this->company !== null) {
            return $this->company;
        }
        
        $stmt = $this->db->prepare("
            SELECT * FROM companies WHERE id = ? LIMIT 1
        ");
        $stmt->execute([$this->companyId]);
        $this->company = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        
        return $this->company;
    }
    
    /**
     * Carrega horários de funcionamento da empresa
     */
    private function loadBusinessHours(): array
    {
        if ($this->hours !== null) {
            return $this->hours;
        }
        
        $stmt = $this->db->prepare("
            SELECT weekday, is_open, open1, close1, open2, close2 
            FROM company_hours 
            WHERE company_id = ? 
            ORDER BY weekday
        ");
        $stmt->execute([$this->companyId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->hours = [];
        foreach ($rows as $row) {
            $this->hours[(int)$row['weekday']] = $row;
        }
        
        return $this->hours;
    }
    
    /**
     * Verifica se está dentro do horário de funcionamento (usando horário de Brasília)
     */
    public function isWithinBusinessHours(): bool
    {
        $hours = $this->loadBusinessHours();
        
        // Usar timezone de Brasília
        $brasiliaTime = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
        
        // Dia da semana (1=Segunda ... 7=Domingo)
        $weekday = (int)$brasiliaTime->format('N');
        $currentTime = $brasiliaTime->format('H:i:s');
        
        if (!isset($hours[$weekday]) || empty($hours[$weekday]['is_open'])) {
            return false;
        }
        
        $day = $hours[$weekday];
        
        // Verificar primeiro período
        if (!empty($day['open1']) && !empty($day['close1'])) {
            if ($this->isTimeInRange($currentTime, $day['open1'], $day['close1'])) {
                return true;
            }
        }
        
        // Verificar segundo período (se houver)
        if (!empty($day['open2']) && !empty($day['close2'])) {
            if ($this->isTimeInRange($currentTime, $day['open2'], $day['close2'])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Calcula o próximo horário de abertura do expediente
     * Retorna a data/hora do próximo início de expediente
     * 
     * @param bool $forceNextDay Se true, pula o dia atual e retorna próximo dia
     * @return \DateTime|null Próximo horário de abertura ou null se não houver
     */
    public function getNextBusinessOpenTime(bool $forceNextDay = false): ?\DateTime
    {
        $hours = $this->loadBusinessHours();
        $brasiliaTime = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
        
        // Se forceNextDay, começar do dia seguinte
        $startOffset = $forceNextDay ? 1 : 0;
        
        // Verificar os próximos 7 dias
        for ($dayOffset = $startOffset; $dayOffset <= 7; $dayOffset++) {
            $checkDate = clone $brasiliaTime;
            if ($dayOffset > 0) {
                $checkDate->modify("+{$dayOffset} days");
            }
            
            $weekday = (int)$checkDate->format('N');
            
            if (!isset($hours[$weekday]) || empty($hours[$weekday]['is_open'])) {
                continue;
            }
            
            $day = $hours[$weekday];
            $openTime = $day['open1'] ?? null;
            
            if (empty($openTime)) {
                continue;
            }
            
            // Criar datetime para o horário de abertura
            $openDateTime = new \DateTime(
                $checkDate->format('Y-m-d') . ' ' . $openTime,
                new \DateTimeZone('America/Sao_Paulo')
            );
            
            // Se for hoje e não forceNextDay, verificar se o horário de abertura já passou
            if ($dayOffset === 0 && $openDateTime <= $brasiliaTime) {
                // Horário de abertura de hoje já passou, buscar próximo dia
                continue;
            }
            
            // Adicionar um pequeno offset (5-15 min) para não disparar exatamente na abertura
            $openDateTime->modify('+' . rand(5, 15) . ' minutes');
            
            return $openDateTime;
        }
        
        return null;
    }
    
    /**
     * Verifica se estamos no início do expediente (primeiras 2 horas após abertura)
     * 
     * @return bool True se estamos nas primeiras 2 horas do expediente (19:00-21:00)
     */
    public function isAtBusinessStart(): bool
    {
        $hours = $this->loadBusinessHours();
        $brasiliaTime = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
        $weekday = (int)$brasiliaTime->format('N');
        
        if (!isset($hours[$weekday]) || empty($hours[$weekday]['is_open'])) {
            return false;
        }
        
        $day = $hours[$weekday];
        $openTime = $day['open1'] ?? null;
        
        if (empty($openTime)) {
            return false;
        }
        
        $currentTime = $brasiliaTime->format('H:i:s');
        
        // Calcular 2 horas após abertura (até 21:00 se abre às 19:00)
        $openDateTime = new \DateTime($openTime);
        $openDateTime->modify('+2 hours');
        $twoHoursAfterOpen = $openDateTime->format('H:i:s');
        
        // Verificar se estamos entre abertura e 2 horas depois (exclusivo)
        return ($currentTime >= $openTime && $currentTime < $twoHoursAfterOpen);
    }
    
    /**
     * Retorna saudação dinâmica baseada no horário de Brasília
     */
    public function getDynamicGreeting(): string
    {
        // Usar timezone de Brasília
        $brasiliaTime = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
        $hour = (int)$brasiliaTime->format('H');
        
        if ($hour >= 5 && $hour < 12) {
            return 'Bom dia';
        } elseif ($hour >= 12 && $hour < 18) {
            return 'Boa tarde';
        } else {
            return 'Boa noite';
        }
    }
    
    /**
     * Verifica se pode enviar mensagem para cliente (controle de spam)
     * 
     * Para cenário 'inactive_customer':
     * - Só pode enviar se NÃO enviou mensagem APÓS o último pedido do cliente
     * - Isso garante disparo único por ciclo de inatividade
     * 
     * Para cenário 'signup_no_order':
     * - Usa o controle normal de min_hours_between_messages
     */
    private function canSendToCustomer(int $customerId, string $scenarioType): bool
    {
        $config = $this->loadConfig();
        if (!$config) {
            return false;
        }
        
        // Para cliente inativo, verificar se já recebeu mensagem APÓS o último pedido
        if ($scenarioType === 'inactive_customer') {
            // Buscar data do último pedido do cliente
            $stmt = $this->db->prepare("
                SELECT c.whatsapp_e164, c.whatsapp, MAX(o.created_at) as last_order_at
                FROM customers c
                LEFT JOIN orders o ON (o.customer_phone = c.whatsapp_e164 OR o.customer_phone = c.whatsapp) 
                    AND o.company_id = c.company_id
                WHERE c.id = ? AND c.company_id = ?
                GROUP BY c.id
            ");
            $stmt->execute([$customerId, $this->companyId]);
            $customerData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customerData || !$customerData['last_order_at']) {
                // Sem pedidos, não é elegível para cenário 2
                return false;
            }
            
            // Verificar se já enviou mensagem de inatividade APÓS o último pedido
            $stmt = $this->db->prepare("
                SELECT id FROM customer_engagement_log 
                WHERE customer_id = ? 
                    AND scenario_type = 'inactive_customer' 
                    AND status = 'sent'
                    AND created_at > ?
                LIMIT 1
            ");
            $stmt->execute([$customerId, $customerData['last_order_at']]);
            
            if ($stmt->fetch()) {
                // Já recebeu mensagem de inatividade após o último pedido
                // Só pode enviar novamente se fizer NOVO pedido e ficar inativo de novo
                return false;
            }
        } else {
            // Para outros cenários, usar controle de tempo normal
            $minHours = $config['min_hours_between_messages'] ?? 24;
            
            $stmt = $this->db->prepare("
                SELECT created_at 
                FROM customer_engagement_log 
                WHERE customer_id = ? AND scenario_type = ? AND status = 'sent'
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$customerId, $scenarioType]);
            $lastSent = $stmt->fetchColumn();
            
            if ($lastSent) {
                $lastSentTime = strtotime($lastSent);
                $hoursSince = (time() - $lastSentTime) / 3600;
                
                if ($hoursSince < $minHours) {
                    return false;
                }
            }
        }
        
        // Verificar se já está na fila
        $stmt = $this->db->prepare("
            SELECT id FROM customer_engagement_queue 
            WHERE customer_id = ? AND scenario_type = ? AND status IN ('pending', 'processing')
            LIMIT 1
        ");
        $stmt->execute([$customerId, $scenarioType]);
        if ($stmt->fetch()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Adiciona cliente na fila de mensagens (Cenário 1: Cadastro sem pedido)
     * 
     * REGRA: As mensagens são agendadas para o INÍCIO do próximo expediente,
     * não para o momento atual. Isso garante que o cliente receba a mensagem
     * no começo do dia, quando está mais propenso a responder.
     */
    public function scheduleSignupNoOrderMessage(int $customerId, string $phone, string $name = ''): bool
    {
        $config = $this->loadConfig();
        if (!$config || empty($config['scenario1_enabled'])) {
            return false;
        }
        
        if (!$this->canSendToCustomer($customerId, 'signup_no_order')) {
            return false;
        }
        
        // LID discovery é opcional e não-bloqueante — envio usa phone number direto
        try {
            $this->discoverAndSaveLidMapping($phone, $config['instance_name']);
        } catch (\Throwable $e) {
            error_log("[CustomerEngagement] LID discovery falhou; envio nao afetado: " . $e->getMessage());
        }
        
        // Scenario1 (lead quente): agendar para AGORA
        // Envio imediato preserva timing de conversão — processQueue envia apenas dentro do horário
        $scheduledAt = date('Y-m-d H:i:s');
        
        // INSERT atômico: evita race condition com WHERE NOT EXISTS
        $stmt = $this->db->prepare("
            INSERT INTO customer_engagement_queue 
            (company_id, customer_id, instance_name, scenario_type, customer_phone, customer_name, scheduled_at, status)
            SELECT ?, ?, ?, 'signup_no_order', ?, ?, ?, 'pending'
            FROM DUAL
            WHERE NOT EXISTS (
                SELECT 1 FROM customer_engagement_queue 
                WHERE customer_id = ? AND company_id = ? AND scenario_type = 'signup_no_order' AND status IN ('pending', 'processing')
            )
        ");
        
        $stmt->execute([
            $this->companyId,
            $customerId,
            $config['instance_name'],
            $phone,
            $name,
            $scheduledAt,
            $customerId,
            $this->companyId
        ]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Cancela mensagem pendente se cliente fez pedido
     */
    public function cancelPendingMessages(int $customerId, ?string $scenarioType = null): int
    {
        $sql = "UPDATE customer_engagement_queue SET status = 'cancelled', is_active = NULL WHERE customer_id = ? AND status = 'pending'";
        $params = [$customerId];
        
        if ($scenarioType) {
            $sql .= " AND scenario_type = ?";
            $params[] = $scenarioType;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Busca clientes inativos para enviar mensagem (Cenário 2)
     * 
     * REGRA IMPORTANTE:
     * - O disparo de inatividade acontece APENAS UMA VEZ após 15 dias do último pedido
     * - Após o disparo, o cliente só recebe nova mensagem se fizer um NOVO pedido
     *   e depois ficar novamente 15 dias sem pedir
     * - Isso evita múltiplos disparos para o mesmo período de inatividade
     */
    public function findInactiveCustomers(): array
    {
        $config = $this->loadConfig();
        if (!$config || empty($config['scenario2_enabled'])) {
            return [];
        }
        
        $inactiveDays = $config['scenario2_inactive_days'] ?? 15;
        
        // Buscar clientes que:
        // 1. Fizeram pelo menos um pedido
        // 2. Último pedido foi há mais de X dias
        // 3. NÃO receberam mensagem de inatividade APÓS o último pedido
        //    (isso garante disparo único por ciclo de inatividade)
        //
        // Usamos subconsulta para garantir compatibilidade com MySQL
        $stmt = $this->db->prepare("
            SELECT sub.* FROM (
                SELECT c.id, c.name, c.whatsapp_e164, c.whatsapp,
                       MAX(o.created_at) as last_order_at,
                       (
                           SELECT p.name FROM orders o2
                           INNER JOIN order_items oi ON oi.order_id = o2.id
                           INNER JOIN products p ON p.id = oi.product_id
                           WHERE (o2.customer_phone = c.whatsapp_e164 OR o2.customer_phone = c.whatsapp)
                               AND o2.company_id = c.company_id
                           ORDER BY o2.created_at DESC
                           LIMIT 1
                       ) as last_product_name
                FROM customers c
                INNER JOIN orders o ON (o.customer_phone = c.whatsapp_e164 OR o.customer_phone = c.whatsapp) 
                    AND o.company_id = c.company_id
                WHERE c.company_id = ?
                    AND c.whatsapp_e164 IS NOT NULL
                    AND c.whatsapp_e164 != ''
                    AND c.lgpd_consent_at IS NOT NULL
                GROUP BY c.id, c.name, c.whatsapp_e164, c.whatsapp
                HAVING MAX(o.created_at) < DATE_SUB(NOW(), INTERVAL ? DAY)
            ) sub
            WHERE NOT EXISTS (
                SELECT 1 FROM customer_engagement_log cel 
                WHERE cel.customer_id = sub.id 
                    AND cel.scenario_type = 'inactive_customer' 
                    AND cel.status = 'sent'
                    AND cel.created_at > sub.last_order_at
            )
            LIMIT 50
        ");
        $stmt->execute([$this->companyId, $inactiveDays]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Agenda mensagens para clientes inativos
     */
    public function scheduleInactiveCustomerMessages(): int
    {
        $customers = $this->findInactiveCustomers();
        $config = $this->loadConfig();
        
        if (!$config) {
            return 0;
        }
        
        $count = 0;
        foreach ($customers as $customer) {
            if (!$this->canSendToCustomer((int)$customer['id'], 'inactive_customer')) {
                continue;
            }
            
            $phone = $customer['whatsapp_e164'] ?: $customer['whatsapp'];
            
            $metadata = !empty($customer['last_product_name'])
                ? json_encode(['last_product' => $customer['last_product_name']])
                : null;
            
            // INSERT atômico: evita race condition com WHERE NOT EXISTS
            $stmt = $this->db->prepare("
                INSERT INTO customer_engagement_queue 
                (company_id, customer_id, instance_name, scenario_type, customer_phone, customer_name, metadata, scheduled_at, status)
                SELECT ?, ?, ?, 'inactive_customer', ?, ?, ?, NOW(), 'pending'
                FROM DUAL
                WHERE NOT EXISTS (
                    SELECT 1 FROM customer_engagement_queue 
                    WHERE customer_id = ? AND company_id = ? AND scenario_type = 'inactive_customer' AND status IN ('pending', 'processing')
                )
            ");
            
            $stmt->execute([
                $this->companyId,
                $customer['id'],
                $config['instance_name'],
                $phone,
                $customer['name'] ?? '',
                $metadata,
                $customer['id'],
                $this->companyId
            ]);
            
            if ($stmt->rowCount() > 0) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Gera mensagens para o cenário 1 (cadastro sem pedido)
     */
    private function generateSignupNoOrderMessages(string $customerName, bool $outsideHours = false): array
    {
        $greeting = $this->getDynamicGreeting();
        $firstName = explode(' ', trim($customerName))[0];
        
        // Mensagem única consolidada — evita parecer automação agressiva no WhatsApp
        // Nota: este método só é chamado dentro do horário de funcionamento
        return [
            "{$greeting}, {$firstName}! 😊 Vi que você se cadastrou aqui, mas não finalizou seu pedido ainda. Posso te ajudar com alguma coisa? Qualquer dúvida é só falar!"
        ];
    }
    
    /**
     * Gera mensagens para o cenário 2 (cliente inativo)
     */
    private function generateInactiveCustomerMessages(string $customerName, string $lastProduct = '', string $loyaltyHint = ''): array
    {
        $greeting = $this->getDynamicGreeting();
        $firstName = explode(' ', trim($customerName))[0];
        
        $company = $this->loadCompany();
        $companyName = $company['name'] ?? 'nosso estabelecimento';
        
        $messages = [
            "{$greeting}, {$firstName}! 😊"
        ];
        
        if (!empty($lastProduct)) {
            $messages[] = "Faz um tempinho que você não pede aqui no *{$companyName}*. Seu último pedido foi *{$lastProduct}* — que tal repetir? 😋";
        } else {
            $messages[] = "Sentimos sua falta aqui no *{$companyName}*! 😊 Que tal dar uma olhada no nosso cardápio?";
        }
        
        if (!empty($loyaltyHint)) {
            $messages[] = $loyaltyHint;
        } else {
            $messages[] = "Estamos com novidades esperando por você! É só chamar que te ajudo. 🙌";
        }
        
        return $messages;
    }
    
    /**
     * Processa fila de mensagens pendentes
     */
    public function processQueue(): array
    {
        $results = ['processed' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];
        
        // HARD BLOCK: verificar enabled direto no banco antes de qualquer coisa
        if (!$this->isEngagementEnabled()) {
            $results['skipped_reason'] = 'Sistema desabilitado (hard block)';
            return $results;
        }
        
        $config = $this->loadConfig(true); // forceReload — pegar estado mais recente
        if (!$config) {
            $results['skipped_reason'] = 'Sistema desabilitado ou não configurado';
            return $results;
        }
        
        // CORE BLOCK: horário de funcionamento ANTES de qualquer cenário
        if (!$this->isWithinBusinessHours()) {
            $results['skipped_reason'] = 'Fora do horário de funcionamento';
            return $results;
        }
        
        // Quota diária: limitar total de envios por dia por empresa (padrão 100)
        $dailyQuota = (int)($config['daily_quota'] ?? 100);
        $sentToday = $this->getSentTodayCount();
        $quotaRemaining = max(0, $dailyQuota - $sentToday);
        
        if ($quotaRemaining <= 0) {
            $results['skipped_reason'] = "Quota diária atingida ({$sentToday}/{$dailyQuota})";
            error_log("[CustomerEngagement] QUOTA_EXCEEDED company={$this->companyId} sent_today={$sentToday} quota={$dailyQuota}");
            return $results;
        }
        
        // Rate-aware: consultar capacidade restante do WhatsApp antes de definir batch
        $rateRemaining = PHP_INT_MAX;
        try {
            $sendService = WhatsAppSendService::getInstance();
            $rateStatus = $sendService->getRateLimitStatus($this->companyId);
            $rateRemaining = min(
                $rateStatus['per_minute']['remaining'] ?? 20,
                $rateStatus['per_hour']['remaining'] ?? 200
            );
        } catch (\Throwable $e) {
            // fail-open: sem info de rate, usar batch padrão
        }
        
        // Batch dinâmico: base 10, escala até 50, mas limitado pela capacidade de rate
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) FROM customer_engagement_queue 
            WHERE company_id = ? AND status = 'pending' AND scheduled_at <= NOW() AND attempts < 3
        ");
        $countStmt->execute([$this->companyId]);
        $pendingCount = (int)$countStmt->fetchColumn();
        $batchSize = (int)min(max(10, (int)ceil($pendingCount * 0.5)), 50, $rateRemaining, $quotaRemaining);
        
        // Prioridade: signup_no_order primeiro (leads quentes), depois inactive_customer
        $stmt = $this->db->prepare("
            SELECT q.*
            FROM customer_engagement_queue q
            WHERE q.company_id = ? 
                AND q.status = 'pending' 
                AND q.scheduled_at <= NOW()
                AND q.attempts < 3
            ORDER BY FIELD(q.scenario_type, 'signup_no_order', 'inactive_customer'), q.scheduled_at ASC
            LIMIT " . $batchSize . "
        ");
        $stmt->execute([$this->companyId]);
        $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($queue as $item) {
            // IDEMPOTÊNCIA: re-verificar status do item antes de processar (pode ter sido cancelado por outro processo)
            $checkStmt = $this->db->prepare("SELECT status FROM customer_engagement_queue WHERE id = ? LIMIT 1");
            $checkStmt->execute([(int)$item['id']]);
            $currentStatus = $checkStmt->fetchColumn();
            if ($currentStatus !== 'pending') {
                $results['skipped']++;
                continue;
            }
            
            // Timeout: parar se excedeu tempo alocado para esta empresa
            if ($this->isTimeBudgetExhausted()) {
                $results['skipped_reason'] = 'Tempo limite por empresa atingido';
                break;
            }
            
            $results['processed']++;
            
            // Marcar como processando
            $this->updateQueueStatus((int)$item['id'], 'processing');
            
            // Verificar se cliente fez pedido (apenas para signup_no_order)
            if ($item['scenario_type'] === 'signup_no_order') {
                if ($this->customerHasOrder((int)$item['customer_id'])) {
                    $this->updateQueueStatus((int)$item['id'], 'cancelled');
                    $results['skipped']++;
                    continue;
                }
                
                // Verificar se cliente teve interação via WhatsApp após o cadastro
                // Se houve conversa no WhatsApp (ex: tirou dúvida e finalizou por lá), não enviar mensagem
                if ($this->customerHasRecentWhatsAppInteraction(
                    $item['customer_phone'],
                    $config['instance_name'],
                    (int)$item['customer_id']
                )) {
                    error_log("[CustomerEngagement] Interacao WhatsApp recente detectada - cancelando envio automatico");
                    $this->updateQueueStatus((int)$item['id'], 'cancelled', null, 'Interação WhatsApp detectada');
                    $results['skipped']++;
                    continue;
                }
            }
            
            // Gerar mensagens
            $lastProduct = '';
            $loyaltyHint = '';
            if ($item['scenario_type'] === 'inactive_customer' && !empty($item['metadata'])) {
                $meta = json_decode($item['metadata'], true);
                $lastProduct = $meta['last_product'] ?? '';
            }

            // Buscar progresso de fidelidade (se próximo de completar ciclo)
            if ($item['scenario_type'] === 'inactive_customer' && !empty($item['customer_id'])) {
                $loyalty = LoyaltyProgram::getCustomerLoyalty($this->db, (int)$item['customer_id'], (int)$item['company_id']);
                if ($loyalty && $loyalty['remaining'] > 0 && $loyalty['remaining'] <= 3) {
                    $rewardDesc = $loyalty['program']['reward_description'] ?? 'uma recompensa';
                    $loyaltyHint = "🎯 Você está a apenas *{$loyalty['remaining']} pedido(s)* de ganhar *{$rewardDesc}*!";
                }
            }

            $messages = $item['scenario_type'] === 'signup_no_order'
                ? $this->generateSignupNoOrderMessages($item['customer_name'] ?? '')
                : $this->generateInactiveCustomerMessages($item['customer_name'] ?? '', $lastProduct, $loyaltyHint);
            
            // HUMAN TAKEOVER CHECK — não enviar engajamento se humano está atendendo
            if ($this->isHumanTakeoverActive($item['company_id'], $item['customer_phone'])) {
                error_log("[CustomerEngagement] Takeover humano ativo - engajamento cancelado");
                $this->updateQueueStatus((int)$item['id'], 'cancelled', null, 'Atendimento humano ativo');
                $results['skipped']++;
                continue;
            }
            
            // A/B HOLDOUT — determinístico por hash do customer_id
            // hash garante que o mesmo cliente SEMPRE cai no mesmo grupo (sem contaminar experimento)
            $holdoutPct = (int)($config['ab_holdout_pct'] ?? 0);
            if ($holdoutPct > 0 && (abs(crc32((string)$item['customer_id'])) % 100) < $holdoutPct) {
                $item['is_holdout'] = 1;
                $this->updateQueueStatus((int)$item['id'], 'sent', null, 'Holdout A/B');
                $this->logMessageSent($item, 'sent', 'holdout', 0);
                $results['sent']++;
                continue;
            }
            
            // Enviar mensagens
            $sendResult = $this->sendMessages(
                $config['instance_name'],
                $item['customer_phone'],
                $messages
            );
            
            if ($sendResult === true) {
                $this->updateQueueStatus((int)$item['id'], 'sent');
                $this->logMessageSent($item, 'sent', null, count($messages));
                $results['sent']++;
            } elseif ($sendResult === 'rate_limited') {
                // Rate limit real (429): parar batch, devolver item para fila
                $this->updateQueueStatus((int)$item['id'], 'pending', null, 'Rate limit atingido');
                $results['skipped_reason'] = 'Rate limit atingido — batch interrompido';
                break;
            } elseif ($sendResult === 'permanent_error') {
                // Erro permanente (4xx, config inválida): DLQ imediato, sem retry
                $this->updateQueueStatus((int)$item['id'], 'failed', 3, '[permanent] Erro não-retentável');
                $this->logMessageSent($item, 'failed', '[permanent] Erro não-retentável', count($messages));
                $this->moveToDeadLetter((int)$item['id']);
                $results['failed']++;
            } else {
                $attempts = (int)$item['attempts'] + 1;
                $newStatus = $attempts >= 3 ? 'failed' : 'pending';
                $this->updateQueueStatus((int)$item['id'], $newStatus, $attempts, 'Falha no envio via API');
                
                if ($newStatus === 'failed') {
                    $this->logMessageSent($item, 'failed', 'Falha após 3 tentativas', count($messages));
                    $this->moveToDeadLetter((int)$item['id']);
                    $results['failed']++;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Verifica se existe atendimento humano ativo para este cliente.
     * Se humano está atendendo, engajamento automático deve ser bloqueado.
     */
    private function isHumanTakeoverActive(int $companyId, string $phone): bool
    {
        try {
            $normalizedPhone = normalizePhone($phone);
            $stmt = $this->db->prepare("
                SELECT id FROM whatsapp_human_takeover 
                WHERE company_id = ? 
                    AND phone = ? 
                    AND status = 'active' 
                    AND expires_at > NOW()
                LIMIT 1
            ");
            $stmt->execute([$companyId, $normalizedPhone]);
            return (bool) $stmt->fetch();
        } catch (\Throwable $e) {
            // Tabela pode não existir ainda — sem takeover ativo
            return false;
        }
    }
    
    /**
     * Verifica se cliente já tem pedido
     */
    private function customerHasOrder(int $customerId): bool
    {
        // Buscar cliente
        $stmt = $this->db->prepare("SELECT whatsapp, whatsapp_e164 FROM customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            return false;
        }
        
        // Verificar por telefone
        $stmt = $this->db->prepare("
            SELECT id FROM orders 
            WHERE company_id = ? 
                AND (customer_phone = ? OR customer_phone = ?)
            LIMIT 1
        ");
        $stmt->execute([
            $this->companyId, 
            $customer['whatsapp_e164'], 
            $customer['whatsapp']
        ]);
        
        return (bool)$stmt->fetch();
    }
    
    /**
     * Verifica se cliente teve interação via WhatsApp no período entre cadastro e agora
     * Utiliza a Evolution API para buscar mensagens do chat
     * 
     * @param string $phone Telefone do cliente em formato E.164
     * @param string $instanceName Nome da instância Evolution
     * @param int $customerId ID do cliente (para buscar data de cadastro)
     * @return bool True se houve interação no período, False caso contrário
     */
    private function customerHasRecentWhatsAppInteraction(string $phone, string $instanceName, int $customerId): bool
    {
        try {
            // Buscar data de cadastro do cliente para usar como referência
            $stmt = $this->db->prepare("SELECT created_at FROM customers WHERE id = ?");
            $stmt->execute([$customerId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customer) {
                return false;
            }
            
            // Período de verificação: 2 minutos ANTES do cadastro até agora
            // (cliente pode mandar mensagem poucos segundos antes de completar cadastro)
            $customerCreatedAt = strtotime($customer['created_at']) - 120; // 2 minutos antes
            $now = time();
            
            // Normalizar telefone e gerar variações (com e sem 9º dígito)
            $cleanPhone = normalizePhone($phone);
            
            $phoneVariations = [$cleanPhone];
            
            // Variação do 9º dígito para números brasileiros
            if (strlen($cleanPhone) === 13 && substr($cleanPhone, 0, 2) === '55') {
                $ddd = substr($cleanPhone, 2, 2);
                $ninthDigit = substr($cleanPhone, 4, 1);
                if ($ninthDigit === '9') {
                    $phoneWithout9 = '55' . $ddd . substr($cleanPhone, 5);
                    $phoneVariations[] = $phoneWithout9;
                }
            }
            elseif (strlen($cleanPhone) === 12 && substr($cleanPhone, 0, 2) === '55') {
                $ddd = substr($cleanPhone, 2, 2);
                $phoneWith9 = '55' . $ddd . '9' . substr($cleanPhone, 4);
                $phoneVariations[] = $phoneWith9;
            }
            
            // MÉTODO 1: Verificar na tabela local (webhook) - mais confiável
            $placeholders = implode(',', array_fill(0, count($phoneVariations), '?'));
            $stmt = $this->db->prepare("
                SELECT id, phone, message_timestamp, message_text 
                FROM whatsapp_received_messages 
                WHERE company_id = ? 
                  AND instance_name = ?
                  AND phone IN ({$placeholders})
                  AND message_timestamp >= ?
                  AND message_timestamp <= ?
                ORDER BY message_timestamp DESC
                LIMIT 1
            ");
            
            $params = array_merge(
                [$this->companyId, $instanceName],
                $phoneVariations,
                [$customerCreatedAt, $now]
            );
            $stmt->execute($params);
            $localMessage = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($localMessage) {
                error_log("[CustomerEngagement] Interacao WhatsApp recebida (webhook). " .
                    "Cadastro: " . date('Y-m-d H:i:s', $customerCreatedAt + 120) . 
                    ", Mensagem: " . date('Y-m-d H:i:s', $localMessage['message_timestamp']));
                return true;
            }
            
            // MÉTODO 2: Fallback - verificar na Evolution API (caso webhook não tenha recebido)
            return $this->checkEvolutionApiForInteraction($phone, $instanceName, $phoneVariations, $customerCreatedAt, $now);
            
        } catch (Exception $e) {
            error_log("[CustomerEngagement] Erro ao verificar interação WhatsApp: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica interação na Evolution API (fallback)
     */
    private function checkEvolutionApiForInteraction(string $phone, string $instanceName, array $phoneVariations, int $customerCreatedAt, int $now): bool
    {
        // Buscar dados da empresa para Evolution API
        $stmt = $this->db->prepare("SELECT evolution_server_url, evolution_api_key FROM companies WHERE id = ?");
        $stmt->execute([$this->companyId]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$company || empty($company['evolution_server_url']) || empty($company['evolution_api_key'])) {
            error_log("[CustomerEngagement] Sem interacao (webhook vazio, API nao configurada).");
            return false;
        }
        
        $server = rtrim($company['evolution_server_url'], '/');
        $apiKey = $company['evolution_api_key'];
        
        foreach ($phoneVariations as $phoneVariation) {
            $remoteJid = $phoneVariation . '@s.whatsapp.net';
            
            error_log("[CustomerEngagement] Verificando interacao via API.");
            
            $url = $server . '/chat/findMessages/' . $instanceName;
            
            $payload = [
                'where' => [
                    'key' => [
                        'remoteJid' => $remoteJid
                    ]
                ],
                'page' => 1,
                'offset' => 30
            ];
            
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
                CURLOPT_POSTFIELDS => json_encode($payload)
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError || $httpCode < 200 || $httpCode >= 300) {
                error_log("[CustomerEngagement] Erro ao buscar mensagens: HTTP {$httpCode}, cURL: {$curlError}");
                continue;
            }
            
            $data = json_decode($response, true);
            
            if (isset($data['messages']['records']) && is_array($data['messages']['records'])) {
                foreach ($data['messages']['records'] as $message) {
                    $fromMe = isset($message['key']['fromMe']) ? $message['key']['fromMe'] : true;
                    
                    if ($fromMe) {
                        continue;
                    }
                    
                    $messageTimestamp = $message['messageTimestamp'] ?? 0;
                    
                    if ($messageTimestamp >= $customerCreatedAt && $messageTimestamp <= $now) {
                        error_log("[CustomerEngagement] Interacao WhatsApp recebida (API). " .
                            "Cadastro: " . date('Y-m-d H:i:s', $customerCreatedAt + 120) . 
                            ", Mensagem: " . date('Y-m-d H:i:s', $messageTimestamp));
                        return true;
                    }
                }
            }
        }
        
        error_log("[CustomerEngagement] Sem interacao WhatsApp no periodo. Pode enviar mensagem.");
        return false;
    }
    
    /**
     * Atualiza status de item na fila
     */
    private function updateQueueStatus(int $id, string $status, ?int $attempts = null, ?string $error = null): void
    {
        $sql = "UPDATE customer_engagement_queue SET status = ?, updated_at = NOW()";
        $params = [$status];
        
        // is_active: NULL quando finalizado (sent/failed/cancelled) → libera UNIQUE para novo ciclo
        if (in_array($status, ['sent', 'failed', 'cancelled'], true)) {
            $sql .= ", is_active = NULL";
        }
        
        if ($attempts !== null) {
            $sql .= ", attempts = ?, last_attempt_at = NOW()";
            $params[] = $attempts;
        }
        
        if ($error !== null) {
            $sql .= ", error_message = ?";
            $params[] = $error;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }
    
    /**
     * Registra log de mensagem enviada
     */
    private function logMessageSent(array $queueItem, string $status, ?string $error = null, int $messagesCount = 3): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO customer_engagement_log 
            (company_id, customer_id, instance_name, scenario_type, customer_phone, messages_count, status, error_message, is_holdout)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $queueItem['company_id'],
            $queueItem['customer_id'],
            $queueItem['instance_name'],
            $queueItem['scenario_type'],
            $queueItem['customer_phone'],
            $messagesCount,
            $status,
            $error,
            $queueItem['is_holdout'] ?? 0
        ]);
    }
    
    /**
     * Envia múltiplas mensagens via WhatsApp Evolution API
     * 
     * @return bool|string True em sucesso, 'rate_limited' se 429, 'permanent_error' se 4xx, false se falha genérica
     */
    private function sendMessages(string $instanceName, string $phone, array $messages)
    {
        // DOUBLE CHECK: defesa em profundidade — re-verificar ANTES de cada envio
        if (!$this->isEngagementEnabled()) {
            error_log("[CustomerEngagement] HARD BLOCK no sendMessages: sistema desabilitado para company={$this->companyId}");
            return 'permanent_error';
        }
        if (!$this->isWithinBusinessHours()) {
            error_log("[CustomerEngagement] HARD BLOCK no sendMessages: fora do horário para company={$this->companyId}");
            return 'permanent_error';
        }
        
        $sendService = WhatsAppSendService::getInstance();
        
        foreach ($messages as $index => $message) {
            // Delay humanizado entre mensagens (1-2.5s)
            if ($index > 0) {
                usleep(rand(1000000, 2500000));
            }
            
            $result = $sendService->send(
                $this->companyId,
                $instanceName,
                $phone,
                $message,
                'engagement',
                [
                    'maxAttempts' => 2,
                    'checkTakeover' => true,
                    'enqueueOnFail' => true,
                    'timeout' => 15,
                    'delay' => 0 // delay já controlado pelo usleep acima
                ]
            );
            
            if (!$result['success']) {
                $httpCode = (int)($result['http_code'] ?? 0);
                $error = $result['error'] ?? '';
                
                // Detectar rate limit real (429 da API ou rate limit interno)
                $isRateLimited = $httpCode === 429 || str_starts_with($error, 'rate_limit_per_');
                if ($isRateLimited) {
                    error_log("[CustomerEngagement] Rate limit atingido ao enviar mensagem.");
                    return 'rate_limited';
                }
                
                // Erro permanente: 4xx (config inválida, número errado) — não adianta retentar
                $isPermanent = ($httpCode >= 400 && $httpCode < 500)
                    || in_array($error, ['missing_whatsapp_config', 'invalid_provider_config', 'human_takeover_active'], true);
                if ($isPermanent) {
                    error_log("[CustomerEngagement] Erro permanente no envio (HTTP {$httpCode}).");
                    return 'permanent_error';
                }
                
                error_log("[CustomerEngagement] Erro temporario ao enviar mensagem {$index}.");
                return false;
            }
            
            error_log("[CustomerEngagement] Mensagem {$index} enviada (msgId={$result['message_id']})");
        }
        
        return true;
    }
    
    /**
     * Busca clientes que se cadastraram recentemente e não têm pedidos
     * para adicionar à fila automaticamente
     */
    public function findNewCustomersWithoutOrders(): array
    {
        $config = $this->loadConfig();
        if (!$config || empty($config['scenario1_enabled'])) {
            return [];
        }
        
        $delayMinutes = $config['scenario1_delay_minutes'] ?? 10;
        $minHours = $config['min_hours_between_messages'] ?? 24;
        
        // Buscar clientes que:
        // 1. Se cadastraram há mais de X minutos (delay configurado)
        // 2. Não têm nenhum pedido
        // 3. Não estão na fila ainda (qualquer status)
        // 4. Não receberam esta mensagem recentemente
        // Usa NOT EXISTS para evitar re-adição de clientes com entradas canceladas
        $stmt = $this->db->prepare("
            SELECT c.id, c.name, c.whatsapp_e164, c.whatsapp, c.created_at
            FROM customers c
            WHERE c.company_id = ?
                AND c.created_at <= DATE_SUB(NOW(), INTERVAL ? MINUTE)
                AND c.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                AND c.whatsapp_e164 IS NOT NULL
                AND c.whatsapp_e164 != ''
                AND c.lgpd_consent_at IS NOT NULL
                AND NOT EXISTS (
                    SELECT 1 FROM orders o 
                    WHERE (o.customer_phone = c.whatsapp_e164 OR o.customer_phone = c.whatsapp) 
                        AND o.company_id = c.company_id
                )
                AND NOT EXISTS (
                    SELECT 1 FROM customer_engagement_queue q 
                    WHERE q.customer_id = c.id 
                        AND q.scenario_type = 'signup_no_order'
                )
                AND NOT EXISTS (
                    SELECT 1 FROM customer_engagement_log l 
                    WHERE l.customer_id = c.id 
                        AND l.scenario_type = 'signup_no_order' 
                        AND l.created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
                )
            LIMIT 20
        ");
        $stmt->execute([$this->companyId, $delayMinutes, $minHours]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Método principal para rodar o ciclo completo de engajamento
     */
    public function runEngagementCycle(): array
    {
        $results = [
            'company_id' => $this->companyId,
            'timestamp' => date('Y-m-d H:i:s'),
            'within_hours' => $this->isWithinBusinessHours(),
            'scheduled' => 0,
            'sent' => 0,
            'errors' => []
        ];
        
        // HARD BLOCK 1: verificar enabled direto no banco (sem cache)
        if (!$this->isEngagementEnabled()) {
            $results['status'] = 'disabled_hard_block';
            return $results;
        }
        
        $config = $this->loadConfig(true); // forceReload para pegar estado mais recente
        if (!$config) {
            $results['status'] = 'disabled_or_not_configured';
            return $results;
        }
        
        // CORE BLOCK: horário de funcionamento antes de qualquer cenário
        if (!$results['within_hours']) {
            $results['status'] = 'outside_business_hours';
            return $results;
        }
        
        if ($results['within_hours']) {
            // Scenario1 (cadastro sem pedido)
            $newCustomers = $this->findNewCustomersWithoutOrders();
            foreach ($newCustomers as $customer) {
                $phone = $customer['whatsapp_e164'] ?: $customer['whatsapp'];
                if ($this->scheduleSignupNoOrderMessage((int)$customer['id'], $phone, $customer['name'] ?? '')) {
                    $results['scheduled']++;
                }
            }
            
            // Scenario2 (cliente inativo)
            $results['scheduled'] += $this->scheduleInactiveCustomerMessages();
        }
        
        // Processar fila (processQueue gerencia horário por cenário internamente)
        $queueResults = $this->processQueue();
        $results['sent'] = $queueResults['sent'] ?? 0;
        
        if (!empty($queueResults['skipped_reason'])) {
            $results['errors'][] = $queueResults['skipped_reason'];
        }
        
        // DLQ ativo: retry automático de itens na dead letter (após cooldown de 6h)
        $dlqResults = $this->retryDeadLetterQueue();
        $results['dlq_retried'] = $dlqResults['retried'];
        $results['dlq_count'] = $dlqResults['dlq_count'];
        if ($dlqResults['alert']) {
            $results['errors'][] = $dlqResults['alert_message'];
        }
        
        $results['status'] = 'completed';
        
        return $results;
    }
    
    /**
     * Descobre o LID associado a um número de telefone e salva o mapeamento
     * Usa a profilePicUrl para correlacionar phone <-> LID
     */
    private function discoverAndSaveLidMapping(string $phone, string $instanceName): void
    {
        try {
            $company = $this->loadCompany();
            if (!$company || empty($company['evolution_server_url']) || empty($company['evolution_api_key'])) {
                return;
            }
            
            $server = rtrim($company['evolution_server_url'], '/');
            $apiKey = $company['evolution_api_key'];
            
            // Normalizar telefone
            $cleanPhone = normalizePhone($phone);
            
            // Gerar variações do número brasileiro (9º dígito)
            $phoneVariations = [$cleanPhone];
            if (strlen($cleanPhone) === 13 && substr($cleanPhone, 0, 2) === '55' && substr($cleanPhone, 4, 1) === '9') {
                $phoneVariations[] = '55' . substr($cleanPhone, 2, 2) . substr($cleanPhone, 5);
            } elseif (strlen($cleanPhone) === 12 && substr($cleanPhone, 0, 2) === '55') {
                $phoneVariations[] = '55' . substr($cleanPhone, 2, 2) . '9' . substr($cleanPhone, 4);
            }
            
            // 1. Buscar contato pelo número para pegar a profilePicUrl
            $profilePicId = null;
            
            foreach ($phoneVariations as $phoneVar) {
                $url = $server . '/chat/findContacts/' . $instanceName;
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'Content-Type: application/json',
                        'apikey: ' . $apiKey
                    ],
                    CURLOPT_TIMEOUT => 3,
                    CURLOPT_CONNECTTIMEOUT => 2,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode(['where' => ['remoteJid' => $phoneVar . '@s.whatsapp.net']])
                ]);
                
                $response = curl_exec($ch);
                curl_close($ch);
                
                $contacts = json_decode($response, true);
                
                if (!empty($contacts) && is_array($contacts) && !empty($contacts[0]['profilePicUrl'])) {
                    // Extrair ID único da foto
                    preg_match('/\/([0-9_]+n\.jpg)/', $contacts[0]['profilePicUrl'], $matches);
                    $profilePicId = $matches[1] ?? null;
                    if ($profilePicId) {
                        break;
                    }
                }
            }
            
            if (!$profilePicId) {
                error_log("[CustomerEngagement] Nao encontrou profilePicUrl.");
                return;
            }
            
            // 2. Buscar todos os contatos e encontrar o LID com mesma foto
            $url = $server . '/chat/findContacts/' . $instanceName;
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'apikey: ' . $apiKey
                ],
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([])
            ]);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $allContacts = json_decode($response, true);
            
            if (!is_array($allContacts)) {
                return;
            }
            
            // Procurar contato @lid com mesma foto
            foreach ($allContacts as $contact) {
                $remoteJid = $contact['remoteJid'] ?? '';
                $picUrl = $contact['profilePicUrl'] ?? '';
                
                if (strpos($remoteJid, '@lid') !== false && !empty($picUrl) && strpos($picUrl, $profilePicId) !== false) {
                    $lid = preg_replace('/@.*$/', '', $remoteJid);
                    
                    // Salvar mapeamento
                    $this->saveLidMapping($lid, $cleanPhone, $instanceName);
                    error_log("[CustomerEngagement] Mapeamento LID descoberto.");
                    return;
                }
            }
            
            error_log("[CustomerEngagement] Nao encontrou LID.");
            
        } catch (Exception $e) {
            error_log("[CustomerEngagement] Erro ao descobrir LID: " . $e->getMessage());
        }
    }
    
    /**
     * Verifica se um número pertence à própria instância/empresa
     */
    private function isInstanceOwnerNumber(string $phone, string $instanceName): bool
    {
        $company = $this->loadCompany();
        if (!$company || empty($company['whatsapp'])) {
            return false;
        }
        
        $cleanPhone = normalizePhone($phone);
        $ownerPhone = normalizePhone($company['whatsapp']);
        
        // Verificar se é o mesmo número (com ou sem 9 inicial)
        if ($cleanPhone === $ownerPhone) {
            return true;
        }
        
        // Verificar variação sem 9 (formato antigo brasileiro)
        if (strlen($cleanPhone) >= 12 && strlen($ownerPhone) >= 12) {
            $phoneCore = substr($cleanPhone, 4);
            $ownerCore = substr($ownerPhone, 4);
            
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
     * Usa confidence=LOW porque a descoberta é baseada em profilePic (heurístico).
     */
    private function saveLidMapping(string $lid, string $phone, string $instanceName): void
    {
        // Guard: não mapear para o número da própria instância
        if ($this->isInstanceOwnerNumber($phone, $instanceName)) {
            error_log("[CustomerEngagement] REJEITADO: Nao salvando mapeamento LID (numero da instancia).");
            return;
        }
        
        try {
            // Garantir schema
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

            // 1. Upsert contato canônico
            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_contacts (phone, instance_name, company_id)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)
            ");
            $stmt->execute([$phone, $instanceName, $this->companyId]);
            $contactId = (int) $this->db->lastInsertId();

            // 2. Upsert LID mapping (confidence=LOW, source=manual para profilePic discovery)
            //    Não rebaixa HIGH existente para LOW
            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_lid_mapping
                    (lid, contact_id, phone, instance_name, company_id, confidence, source)
                VALUES (?, ?, ?, ?, ?, 'LOW', 'manual')
                ON DUPLICATE KEY UPDATE
                    contact_id = IF(confidence = 'LOW', VALUES(contact_id), contact_id),
                    phone      = IF(confidence = 'LOW', VALUES(phone), phone),
                    company_id = IF(confidence = 'LOW', VALUES(company_id), company_id)
            ");
            $stmt->execute([$lid, $contactId, $phone, $instanceName, $this->companyId]);

            error_log("[CustomerEngagement] LID_MAP registrado (contact_id={$contactId}, confidence=LOW, source=manual, company={$this->companyId}).");
        } catch (PDOException $e) {
            error_log("[CustomerEngagement] Erro ao salvar mapeamento LID: " . $e->getMessage());
        }
    }
    
    /**
     * Move item para dead letter queue (DLQ)
     * Itens que falharam 3x são marcados com dead_letter_at para análise posterior.
     * Não reprocessados automaticamente — requerem intervenção manual ou retry explícito.
     */
    /**
     * Conta total de mensagens enviadas hoje por esta empresa
     */
    private function getSentTodayCount(): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM customer_engagement_log 
                WHERE company_id = ? 
                    AND status = 'sent' 
                    AND (is_holdout IS NULL OR is_holdout = 0)
                    AND DATE(created_at) = CURDATE()
            ");
            $stmt->execute([$this->companyId]);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }
    
    /**
     * DLQ ativo: retry automático de itens na dead letter queue
     * Classifica erros: permanentes (número inválido, config) não retentam.
     * Temporários (timeout, 429, 500) retentam após cooldown.
     */
    public function retryDeadLetterQueue(int $minHoursOld = 6, int $limit = 10): array
    {
        $result = ['retried' => 0, 'dlq_count' => 0, 'dlq_permanent' => 0, 'alert' => false];
        
        try {
            // Contar total na DLQ
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM customer_engagement_queue 
                WHERE company_id = ? AND dead_letter_at IS NOT NULL AND status = 'failed'
            ");
            $stmt->execute([$this->companyId]);
            $result['dlq_count'] = (int)$stmt->fetchColumn();
            
            // Contar erros permanentes (não retentáveis)
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM customer_engagement_queue 
                WHERE company_id = ? AND dead_letter_at IS NOT NULL AND status = 'failed'
                AND (error_message LIKE '%invalid%' 
                    OR error_message LIKE '%not found%'
                    OR error_message LIKE '%missing%config%'
                    OR error_message LIKE '%invalid_provider%'
                    OR error_message LIKE '%HTTP 400%'
                    OR error_message LIKE '%HTTP 401%'
                    OR error_message LIKE '%HTTP 403%'
                    OR error_message LIKE '%HTTP 404%'
                    OR error_message LIKE '%[permanent]%')
            ");
            $stmt->execute([$this->companyId]);
            $result['dlq_permanent'] = (int)$stmt->fetchColumn();
            
            // Alerta se DLQ acumula demais (>20 itens retentáveis)
            $retryable = $result['dlq_count'] - $result['dlq_permanent'];
            if ($result['dlq_count'] > 20) {
                $result['alert'] = true;
                $result['alert_message'] = "DLQ: {$result['dlq_count']} total ({$result['dlq_permanent']} permanentes, {$retryable} retentáveis) para empresa {$this->companyId}";
                error_log("[CustomerEngagement] ALERTA DLQ: {$result['alert_message']}");
            }
            
            // Reativar apenas erros TEMPORÁRIOS (excluir permanentes)
            $stmt = $this->db->prepare("
                UPDATE customer_engagement_queue 
                SET status = 'pending', 
                    attempts = 0, 
                    dead_letter_at = NULL,
                    error_message = CONCAT(COALESCE(error_message,''), ' [DLQ retry]')
                WHERE company_id = ? 
                    AND dead_letter_at IS NOT NULL 
                    AND status = 'failed'
                    AND dead_letter_at <= DATE_SUB(NOW(), INTERVAL ? HOUR)
                    AND (error_message IS NULL 
                        OR (error_message NOT LIKE '%invalid%'
                            AND error_message NOT LIKE '%not found%'
                            AND error_message NOT LIKE '%missing%config%'
                            AND error_message NOT LIKE '%invalid_provider%'
                            AND error_message NOT LIKE '%HTTP 400%'
                            AND error_message NOT LIKE '%HTTP 401%'
                            AND error_message NOT LIKE '%HTTP 403%'
                            AND error_message NOT LIKE '%HTTP 404%'
                            AND error_message NOT LIKE '%[permanent]%'))
                LIMIT ?
            ");
            $stmt->execute([$this->companyId, $minHoursOld, $limit]);
            $result['retried'] = $stmt->rowCount();
            
        } catch (\Throwable $e) {
            error_log("[CustomerEngagement] DLQ retry erro: " . $e->getMessage());
        }
        
        return $result;
    }
    
    private function moveToDeadLetter(int $queueId): void
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE customer_engagement_queue 
                SET dead_letter_at = NOW() 
                WHERE id = ? AND status = 'failed'
            ");
            $stmt->execute([$queueId]);
        } catch (\Throwable $e) {
            // Coluna pode não existir — silenciar
            error_log("[CustomerEngagement] DLQ: Erro ao marcar item {$queueId}: " . $e->getMessage());
        }
    }
    
    /**
     * Métricas de conversão: clientes que fizeram pedido após receber engajamento
     * 
     * @param int $days Período de análise em dias (padrão 30)
     * @return array Estatísticas de conversão por cenário
     */
    public function getConversionStats(int $days = 30): array
    {
        $stats = [
            'period_days' => $days,
            'signup_no_order' => ['sent' => 0, 'converted' => 0, 'rate' => 0.0],
            'inactive_customer' => ['sent' => 0, 'converted' => 0, 'rate' => 0.0],
            'ab_comparison' => [
                'treatment' => ['sent' => 0, 'converted' => 0, 'rate' => 0.0],
                'holdout' => ['sent' => 0, 'converted' => 0, 'rate' => 0.0],
                'lift' => null,
                'is_significant' => false,
            ],
        ];
        
        try {
            // Para cada cenário: contar quantos receberam msg E depois fizeram pedido
            foreach (['signup_no_order', 'inactive_customer'] as $scenario) {
                // Total enviados no período (excluir holdout para métrica principal)
                $stmt = $this->db->prepare("
                    SELECT COUNT(DISTINCT customer_id) 
                    FROM customer_engagement_log 
                    WHERE company_id = ? 
                        AND scenario_type = ? 
                        AND status = 'sent' 
                        AND (is_holdout IS NULL OR is_holdout = 0)
                        AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ");
                $stmt->execute([$this->companyId, $scenario, $days]);
                $sent = (int)$stmt->fetchColumn();
                $stats[$scenario]['sent'] = $sent;
                
                if ($sent === 0) continue;
                
                // Convertidos: clientes que fizeram pedido APÓS receber a mensagem
                // Janela de 7 dias após o timestamp do log (mesma janela para holdout)
                $stmt = $this->db->prepare("
                    SELECT COUNT(DISTINCT cel.customer_id)
                    FROM customer_engagement_log cel
                    INNER JOIN customers c ON c.id = cel.customer_id
                    WHERE cel.company_id = ?
                        AND cel.scenario_type = ?
                        AND cel.status = 'sent'
                        AND (cel.is_holdout IS NULL OR cel.is_holdout = 0)
                        AND cel.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                        AND EXISTS (
                            SELECT 1 FROM orders o
                            WHERE o.company_id = cel.company_id
                                AND (o.customer_phone = c.whatsapp_e164 OR o.customer_phone = c.whatsapp)
                                AND o.created_at > cel.created_at
                                AND o.created_at <= DATE_ADD(cel.created_at, INTERVAL 7 DAY)
                        )
                ");
                $stmt->execute([$this->companyId, $scenario, $days]);
                $converted = (int)$stmt->fetchColumn();
                
                $stats[$scenario]['converted'] = $converted;
                $stats[$scenario]['rate'] = round(($converted / $sent) * 100, 1);
            }
            
            // A/B comparison: treatment vs holdout com MESMA janela temporal (causal)
            // Ambos os grupos usam created_at como "momento de decisão" — holdout tem o mesmo timestamp
            foreach (['treatment' => 0, 'holdout' => 1] as $group => $holdoutVal) {
                $condition = $holdoutVal === 0 
                    ? "(cel.is_holdout IS NULL OR cel.is_holdout = 0)" 
                    : "cel.is_holdout = 1";
                
                $stmt = $this->db->prepare("
                    SELECT COUNT(DISTINCT cel.customer_id)
                    FROM customer_engagement_log cel
                    WHERE cel.company_id = ?
                        AND cel.status = 'sent'
                        AND {$condition}
                        AND cel.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ");
                $stmt->execute([$this->companyId, $days]);
                $groupSent = (int)$stmt->fetchColumn();
                $stats['ab_comparison'][$group]['sent'] = $groupSent;
                
                if ($groupSent === 0) continue;
                
                // Mesma janela de 7 dias pós-"decisão" — garante comparação justa
                $stmt = $this->db->prepare("
                    SELECT COUNT(DISTINCT cel.customer_id)
                    FROM customer_engagement_log cel
                    INNER JOIN customers c ON c.id = cel.customer_id
                    WHERE cel.company_id = ?
                        AND cel.status = 'sent'
                        AND {$condition}
                        AND cel.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                        AND EXISTS (
                            SELECT 1 FROM orders o
                            WHERE o.company_id = cel.company_id
                                AND (o.customer_phone = c.whatsapp_e164 OR o.customer_phone = c.whatsapp)
                                AND o.created_at > cel.created_at
                                AND o.created_at <= DATE_ADD(cel.created_at, INTERVAL 7 DAY)
                        )
                ");
                $stmt->execute([$this->companyId, $days]);
                $groupConverted = (int)$stmt->fetchColumn();
                
                $stats['ab_comparison'][$group]['converted'] = $groupConverted;
                $stats['ab_comparison'][$group]['rate'] = round(($groupConverted / $groupSent) * 100, 1);
            }
            
            // Calcular lift e significância estatística simplificada
            $tRate = $stats['ab_comparison']['treatment']['rate'];
            $hRate = $stats['ab_comparison']['holdout']['rate'];
            $tN = $stats['ab_comparison']['treatment']['sent'];
            $hN = $stats['ab_comparison']['holdout']['sent'];
            
            if ($hRate > 0) {
                $stats['ab_comparison']['lift'] = round((($tRate - $hRate) / $hRate) * 100, 1);
            } elseif ($tRate > 0 && $hN > 0) {
                $stats['ab_comparison']['lift'] = 100.0; // holdout 0% → lift é 100%
            }
            
            // Significância: mínimo 30 por grupo para resultado confiável
            $stats['ab_comparison']['is_significant'] = ($tN >= 30 && $hN >= 30);
            
        } catch (\Throwable $e) {
            error_log("[CustomerEngagement] Erro ao calcular conversão: " . $e->getMessage());
        }
        
        return $stats;
    }
}
