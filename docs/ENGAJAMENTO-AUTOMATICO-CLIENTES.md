# Engajamento Automatico de Clientes - Documento Completo

## 1. Objetivo
Este documento descreve o funcionamento completo do modulo de Engajamento Automatico de Clientes:
- arquitetura tecnica;
- configuracoes e endpoints;
- regras de negocio;
- fluxo de execucao por cron;
- estrutura de dados (config, fila, log);
- observabilidade e troubleshooting.

## 2. Visao Geral
O sistema dispara mensagens automaticas de WhatsApp para reengajar clientes em dois cenarios:
1. Cadastro sem pedido (scenario1)
2. Cliente inativo apos ultimo pedido (scenario2)

A orquestracao e feita por ciclo periodico no cron, com enfileiramento e processamento controlado.

## 3. Componentes

### 3.1 Servico principal
- Arquivo: app/services/CustomerEngagementService.php
- Responsabilidade:
  - carregar configuracao da empresa;
  - validar horario de funcionamento;
  - identificar clientes elegiveis por cenario;
  - agendar na fila;
  - processar fila e enviar mensagens;
  - registrar logs de sucesso/falha.

### 3.2 Servico de envio WhatsApp
- Arquivo: app/services/WhatsAppSendService.php
- Responsabilidade:
  - envio robusto para Evolution API;
  - retry e fallback;
  - rate limit;
  - integracao com takeover humano;
  - log tecnico de tentativas.

### 3.3 Controller de configuracao (Admin)
- Arquivo: app/controllers/AdminEvolutionInstanceController.php
- Endpoint principal:
  - GET/POST /admin/{slug}/evolution/instance/{instanceName}/customer-engagement
- Funcoes associadas:
  - customer_engagement
  - engagement_stats
  - saveEngagementConfig
  - getEngagementConfig
  - getActiveEngagementInstance
  - disableOtherEngagementInstances

### 3.4 Controller de configuracao (Mobile)
- Arquivo: app/controllers/MobileAdminSettingsController.php
- Endpoints relacionados:
  - GET/POST /settings/whatsapp/{name}/engagement
  - GET/POST /settings/whatsapp/{name}/out-of-hours
  - GET/POST /settings/whatsapp/{name}/scheduled-pause

### 3.5 Job de execucao
- Arquivo: scripts/engagement_cron.php
- Responsabilidade:
  - carregar empresas com engajamento habilitado;
  - executar runEngagementCycle por empresa;
  - escrever log em arquivo diario;
  - controlar lock para evitar concorrencia entre processos.

## 4. Endpoints e Rotas
Rotas principais em routes/web.php:
- POST /admin/{slug}/evolution/instance/{instanceName}/customer-engagement
- GET /admin/{slug}/evolution/instance/{instanceName}/customer-engagement
- GET /admin/{slug}/evolution/instance/{instanceName}/engagement-stats
- GET /settings/whatsapp/{name}/out-of-hours
- POST /settings/whatsapp/{name}/out-of-hours
- GET /settings/whatsapp/{name}/scheduled-pause
- POST /settings/whatsapp/{name}/scheduled-pause

## 5. Estrutura de Dados

### 5.1 customer_engagement_config
Migracao base: database/migrations/20260101_create_customer_engagement_system.sql

Finalidade:
- configuracao unica por empresa (UNIQUE company_id).

Campos principais:
- company_id
- instance_name
- enabled
- scenario1_enabled
- scenario1_delay_minutes
- scenario2_enabled
- scenario2_inactive_days
- min_hours_between_messages
- out_of_hours_enabled
- out_of_hours_message
- scheduled_pause_enabled
- scheduled_pause_message
- created_at
- updated_at

Observacao:
- os campos out_of_hours/scheduled_pause sao gravados pelos controllers de configuracao e usados no fluxo de auto-resposta do webhook.

### 5.2 customer_engagement_queue
Migracao base: database/migrations/20260101_create_customer_engagement_system.sql
Alteracao: database/migrations/20260406_add_lgpd_ga4_metadata.sql (coluna metadata)

Finalidade:
- fila de mensagens pendentes para envio.

Campos principais:
- company_id
- customer_id
- instance_name
- scenario_type (signup_no_order, inactive_customer)
- customer_phone
- customer_name
- metadata (JSON)
- scheduled_at
- status (pending, processing, sent, failed, cancelled)
- attempts
- last_attempt_at
- error_message
- created_at
- updated_at

Indices relevantes:
- idx_status_scheduled
- idx_company_status
- idx_customer_type

### 5.3 customer_engagement_log
Migracao base: database/migrations/20260101_create_customer_engagement_system.sql

Finalidade:
- auditoria de envios por cliente/cenario;
- base para anti-spam e regra de unicidade no scenario2.

Campos principais:
- company_id
- customer_id
- instance_name
- scenario_type
- customer_phone
- messages_count
- status (sent, failed)
- error_message
- created_at

### 5.4 Tabelas de suporte usadas no fluxo
- customers (dados do cliente, whatsapp_e164, lgpd_consent_at, created_at)
- orders (historico de pedidos para elegibilidade)
- company_hours (janela de funcionamento)
- companies (dados da empresa e credenciais Evolution)
- whatsapp_received_messages (interacao recente do cliente)
- whatsapp_human_takeover (bloqueio por atendimento humano)
- whatsapp_lid_mapping e whatsapp_contacts (descoberta de LID no scenario1)

## 6. Configuracao e Limites

### 6.1 Parametros de engajamento
- enabled: liga/desliga o sistema.
- scenario1_enabled: ativa cadastro sem pedido.
- scenario1_delay_minutes: intervalo para elegibilidade no scenario1.
- scenario2_enabled: ativa cliente inativo.
- scenario2_inactive_days: janela de inatividade.
- min_hours_between_messages: anti-spam entre disparos.

Validacoes no controller admin:
- scenario1_delay entre 5 e 60
- scenario2_days entre 7 e 90

### 6.2 Instancia ativa unica
Regra de exclusividade:
- quando enabled=true em uma instancia, pode haver conflito com outra instancia ativa da mesma empresa.
- suporte a force_switch para desativar as demais instancias automaticamente.

## 7. Fluxo Operacional Fim a Fim

### 7.1 Execucao do cron
scripts/engagement_cron.php:
1. adquire lock MySQL advisory (GET_LOCK)
2. busca empresas com customer_engagement_config.enabled=1 e companies.active=1
3. para cada empresa, instancia CustomerEngagementService(companyId)
4. define timeout por empresa (maxSecondsPerCompany=30s)
5. executa runEngagementCycle()
6. registra resultado no log diario (com duracao por empresa)
7. grava heartbeat no banco (system_heartbeat)
8. libera lock (RELEASE_LOCK) no shutdown

### 7.2 Orquestracao no runEngagementCycle
Metodo: runEngagementCycle

Passos:
1. carrega configuracao ativa (loadConfig)
2. identifica novos cadastros sem pedido (findNewCustomersWithoutOrders) — só dentro do horário
3. agenda cada cliente elegivel (scheduleSignupNoOrderMessage) — INSERT atomico
4. identifica clientes inativos (findInactiveCustomers) — só dentro do horário
5. agenda scenario2 (scheduleInactiveCustomerMessages) — INSERT atomico
6. processa fila pendente (processQueue) — rate-aware, com prioridade, só dentro do horário
7. retorna status consolidado (scheduled, sent, errors)

## 8. Regras de Negocio por Cenario

### 8.1 Scenario1 - Cadastro sem pedido
Metodo base: findNewCustomersWithoutOrders

Elegibilidade:
- cliente criado ha mais de scenario1_delay_minutes;
- cliente criado dentro de 24h;
- whatsapp_e164 preenchido;
- lgpd_consent_at preenchido;
- sem pedido associado no periodo;
- sem entrada previa para scenario1 na fila;
- sem envio recente no log dentro de min_hours_between_messages.

Agendamento:
- metodo scheduleSignupNoOrderMessage
- agendar para AGORA (envio imediato para preservar timing de conversao)
- INSERT atomico com WHERE NOT EXISTS (previne race condition)
- LID discovery opcional e nao-bloqueante (try-catch, timeout 3s)
- funciona APENAS dentro do horario de expediente (mensagens de madrugada sao antieticas)

Mensagens:
- geradas por generateSignupNoOrderMessages
- mensagem unica consolidada (anti-spam WhatsApp)
- tom conversacional com oferta de ajuda

### 8.2 Scenario2 - Cliente inativo
Metodo base: findInactiveCustomers

Elegibilidade:
- cliente com pelo menos um pedido;
- ultimo pedido anterior a scenario2_inactive_days;
- whatsapp_e164 preenchido;
- lgpd_consent_at preenchido;
- NAO pode existir log sent de inactive_customer apos o ultimo pedido.

Regra critica:
- disparo unico por ciclo de inatividade.
- para novo disparo, cliente precisa fazer novo pedido e voltar a ficar inativo.

Agendamento:
- metodo scheduleInactiveCustomerMessages
- cria item em queue com scheduled_at=NOW
- grava metadata com ultimo produto quando disponivel.

Mensagens:
- geradas por generateInactiveCustomerMessages
- podem incluir dica de fidelidade (LoyaltyProgram::getCustomerLoyalty).

## 9. Processamento da Fila
Metodo: processQueue

Regras:
1. TODOS os cenarios so processam dentro do horario de funcionamento
2. fora do horario: nenhum envio (mensagens de madrugada sao antieticas)
3. rate-aware: consulta WhatsAppSendService.getRateLimitStatus antes de definir batch
4. batch dinamico: min(max(10, ceil(pending*0.5)), 50, rateRemaining)
5. prioridade: signup_no_order primeiro, inactive_customer depois
6. timeout: para se exceder tempo alocado por empresa

Para cada item:
1. verifica timeout por empresa (isTimeBudgetExhausted)
2. marca status=processing
3. scenario1:
  - cancela se cliente ja fez pedido
  - cancela se houve interacao WhatsApp recente
4. bloqueia envio se houver human takeover ativo
5. gera mensagens do cenario (com tom diferente fora do horario)
6. envia via sendMessages
7. sucesso: status=sent + is_active=NULL + log sent
8. falha: incrementa attempts
  - se attempts >= 3: status=failed + is_active=NULL + DLQ (dead_letter_at) + log failed
  - senao volta para pending

## 10. Integracao com WhatsApp

### 10.1 Envio
Metodo: sendMessages
- chama WhatsAppSendService::send
- messageType=engagement
- maxAttempts=2 no envio individual
- checkTakeover=true
- enqueueOnFail=true
- delay humanizado com usleep entre mensagens

### 10.2 Deteccao de interacao recente
Metodo: customerHasRecentWhatsAppInteraction

Estrategia:
1. metodo primario: tabela local whatsapp_received_messages
2. fallback: consulta Evolution API em chat/findMessages/{instance}

Se houver mensagem do cliente no periodo entre cadastro e agora:
- item scenario1 e cancelado para evitar abordagem duplicada.

### 10.3 Takeover humano
Metodo: isHumanTakeoverActive

Regra:
- se existir registro active e nao expirado em whatsapp_human_takeover,
- o engajamento automatico nao envia mensagens para aquele telefone.

## 11. Horario de Funcionamento
Metodos:
- isWithinBusinessHours
- isTimeInRange
- isAtBusinessStart
- getNextBusinessOpenTime

Detalhes:
- timezone America/Sao_Paulo
- suporta dois periodos por dia (open1/close1 e open2/close2)
- suporta turno cruzando meia-noite
- scenario1 privilegia envio no inicio do expediente

## 12. Observabilidade

### 12.1 Logs do cron
Arquivo:
- storage/logs/engagement_cron_YYYY-MM-DD.log

Eventos:
- inicio/fim de ciclo
- total de empresas processadas
- agendadas/enviadas/erros por empresa

### 12.2 Logs de aplicacao
Prefixos frequentes:
- [CustomerEngagement]
- [Engagement Cron]

Pontos de log:
- cancelamento por interacao recente
- bloqueio por takeover
- falha de envio por mensagem
- descoberta de mapeamento LID

### 12.3 Estatisticas API
Endpoint:
- GET /admin/{slug}/evolution/instance/{instanceName}/engagement-stats

Retorno principal:
- total enviado ultimos 30 dias
- enviados por cenario
- total pendente na fila

## 13. Troubleshooting

### 13.1 Nao esta enviando nada
Checklist:
1. customer_engagement_config.enabled = 1
2. company active = 1
3. horario atual dentro de company_hours
4. cron rodando sem lock preso
5. instance_name valido e configurado

### 13.2 Scenario1 nao dispara
Checklist:
1. scenario1_enabled = 1
2. cliente com lgpd_consent_at preenchido
3. cliente dentro da janela (apos delay e antes de 24h)
4. cliente sem pedido
5. sem item previo em customer_engagement_queue
6. sem interacao recente no whatsapp_received_messages

### 13.3 Scenario2 repetindo ou nao disparando
Checklist:
1. scenario2_enabled = 1
2. scenario2_inactive_days correto
3. validar regra de unicidade no customer_engagement_log
4. confirmar ultimo pedido do cliente

### 13.4 Fila presa em pending/processing
Checklist:
1. verificar erros de API no log
2. verificar attempts e error_message
3. validar se takeover humano esta bloqueando
4. validar se cron esta executando continuamente

### 13.5 Falha de webhook/config
Checklist:
1. Evolution server URL e API key validos na companies
2. webhook de instancia configurado corretamente
3. endpoint de webhook recebendo eventos

## 14. Consultas uteis de operacao

### 14.1 Configuracao ativa por empresa
SELECT * FROM customer_engagement_config WHERE company_id = ?;

### 14.2 Pendencias de fila
SELECT id, customer_id, scenario_type, status, scheduled_at, attempts, error_message
FROM customer_engagement_queue
WHERE company_id = ?
ORDER BY scheduled_at ASC;

### 14.3 Historico de envios
SELECT id, customer_id, scenario_type, status, messages_count, created_at
FROM customer_engagement_log
WHERE company_id = ?
ORDER BY created_at DESC
LIMIT 100;

### 14.4 Pendencias por cenario
SELECT scenario_type, status, COUNT(*) as total
FROM customer_engagement_queue
WHERE company_id = ?
GROUP BY scenario_type, status;

## 15. Relacao com Modulos de WhatsApp
O modulo de Engajamento Automatico trabalha em conjunto com:
- resposta fora de expediente (out_of_hours)
- pausa programada (scheduled_pause)
- takeover humano
- fluxo de envio central (WhatsAppSendService)

Ou seja, o engajamento nao e um bloco isolado: ele compartilha as mesmas protecoes e controles operacionais do ecossistema de mensageria WhatsApp.

## 16. Referencias de Codigo
- app/services/CustomerEngagementService.php
- app/services/WhatsAppSendService.php
- scripts/engagement_cron.php
- scripts/run_engagement_cron.sh
- app/controllers/AdminEvolutionInstanceController.php
- app/controllers/MobileAdminSettingsController.php
- routes/web.php
- database/migrations/20260101_create_customer_engagement_system.sql
- database/migrations/20260406_add_lgpd_ga4_metadata.sql
- database/migrations/20260419_engagement_concurrency_fixes.sql
- database/migrations/20260419_engagement_advanced_fixes.sql
- database/migrations/20260419_engagement_final_fixes.sql

## 17. Melhorias de Concorrencia e Escalabilidade (v2)

### 17.1 Lock distribuido
- Mecanismo: MySQL GET_LOCK/RELEASE_LOCK
- Lock name: 'engagement_cron_lock'
- Timeout: 0 (non-blocking)
- Auto-release em crash de processo/conexao

### 17.2 Anti-race condition na fila
- INSERT atomico com WHERE NOT EXISTS
- UNIQUE KEY (company_id, customer_id, scenario_type, is_active)
- is_active = 1 (pending/processing), NULL (finalizado)
- NULLs nao conflitam em UNIQUE → permite multiplos finalizados

### 17.3 Rate-aware batch
- Consulta WhatsAppSendService.getRateLimitStatus antes de processar
- Batch limitado pelo menor entre: backlog/2, 50, rate remaining
- Previne estourar rate limits do WhatsApp (20/min, 200/hora)

### 17.4 Timeout por empresa
- maxSecondsPerCompany = 30s (configuravel)
- isTimeBudgetExhausted() verificado a cada item do batch
- Impede que empresa com volume alto monopolize o ciclo

### 17.5 Dead Letter Queue (DLQ)
- Itens com 3 falhas: status=failed + dead_letter_at=NOW()
- Classificacao de erros:
  - Permanentes (HTTP 400/401/403/404, invalid, missing_config): NAO reprocessados
  - Temporarios (timeout, HTTP 500/502, rate limit): retry apos 6h de cooldown
- retryDeadLetterQueue() roda a cada ciclo automaticamente
- Alerta automatico se DLQ > 20 itens
- Visivel no endpoint engagement-stats (campo dlq)
- Erros permanentes marcados com tag [permanent] no error_message

### 17.6 Heartbeat do cron
- Tabela: system_heartbeat
- Registra: service_name, last_run_at, duration_seconds, status
- Permite monitoramento externo (se last_run_at > 5min → alerta)

### 17.7 Metricas de conversao
- Metodo: getConversionStats(days)
- Mede: clientes que fizeram pedido em ate 7 dias apos receber engajamento
- Breakdown por cenario (signup_no_order, inactive_customer)
- Exposto no endpoint GET engagement-stats (campo conversion)

### 17.8 Protecao contra envio fora do horario
- NENHUM cenario dispara fora do horario de funcionamento
- processQueue() retorna imediatamente se fora do horario
- runEngagementCycle() so agenda e processa dentro do expediente
- Ao desativar o sistema, todos os itens pendentes da fila sao cancelados automaticamente

## 18. Hardening Final (v3)

### 18.1 A/B deterministico por hash
- Metodo: abs(crc32(customer_id)) % 100 < holdout_pct
- Garante que o mesmo cliente SEMPRE cai no mesmo grupo
- Elimina contaminacao de experimento (cliente ora holdout, ora treatment)
- Campo: customer_engagement_config.ab_holdout_pct (default 0 = desabilitado)

### 18.2 Classificacao de erros no envio
- sendMessages() retorna 3 valores: true, 'rate_limited', 'permanent_error', false
- Erros permanentes (HTTP 4xx exceto 429, config invalida): DLQ imediato, sem retry
- Erros temporarios (timeout, 5xx): incrementa attempts normalmente
- Rate limit (429): para batch inteiro (backoff)

### 18.3 Quota diaria por empresa
- Campo: customer_engagement_config.daily_quota (default 100)
- Verificado antes de montar batch no processQueue
- Log explicito QUOTA_EXCEEDED quando atingida
- Previne estourar limites sem deteccao silenciosa

### 18.4 Supervisor com crash alerting
- Script: scripts/run_engagement_cron.sh (modo supervisor)
- Crash log separado: storage/logs/engagement_crashes.log
- Conta total de crashes (nao reseta apos backoff)
- Grava heartbeat com status=critical e metadata de crashes no banco
- Permite monitoramento: SELECT * FROM system_heartbeat WHERE service_name = 'engagement_supervisor'

### 18.5 Conversao causal A/B
- Holdout registrado no log com is_holdout=1 e messages_count=0
- Mesma janela temporal (7 dias pos-decisao) para treatment e holdout
- Metrica de lift: (treatment_rate - holdout_rate) / holdout_rate * 100
- Flag is_significant: requer minimo 30 amostras por grupo
- getConversionStats() retorna: ab_comparison.lift e ab_comparison.is_significant

### 18.6 Feedback real de rate limit
- sendMessages detecta HTTP 429 e rate_limit_per_* do WhatsAppSendService
- Retorna 'rate_limited' → processQueue para batch inteiro
- Item devolvido para fila como pending (retry no proximo ciclo)
- Complementa rate-aware pre-check com feedback pos-envio
