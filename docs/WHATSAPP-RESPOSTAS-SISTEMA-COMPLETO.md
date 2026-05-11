# Sistema de Respostas WhatsApp - Funcionamento Completo

## 1. Objetivo
Este documento descreve o fluxo completo do sistema de respostas WhatsApp no Multi Menu:
- recepcao de eventos da Evolution API;
- fila assíncrona e worker de processamento;
- regras de automacao (fora de expediente, pausa programada, takeover humano);
- envio robusto de mensagens com retry/fallback/log;
- configuracao administrativa por instancia;
- operacao, monitoramento e troubleshooting.

## 2. Arquitetura Geral

### 2.1 Componentes principais
- Webhook de entrada: `WebhookEvolutionController@messages`
- Worker de processamento: `WebhookEvolutionController@processQueue`
- Servico central de envio: `WhatsAppSendService`
- Configuracao de instancia/webhook: `AdminEvolutionController`, `AdminEvolutionInstanceController`, `MobileAdminSettingsController`
- Gestao de pausa programada: `AdminPauseController`, `ScheduledPauseService`
- Notificacao de pedidos: `OrderNotificationService`

### 2.2 Principio arquitetural
A entrada do webhook e desacoplada do processamento:
1. endpoint recebe o payload;
2. valida e enfileira;
3. responde HTTP 200 rapidamente;
4. worker processa em separado.

Beneficios:
- reduz timeout/retry da Evolution;
- evita bloquear PHP/Apache com processamento pesado;
- melhora resiliencia (mensagem fica persistida na fila).

## 3. Endpoints Principais

### 3.1 Webhooks Evolution
- `POST /webhook/evolution/{instanceName}`: recepcao de mensagens
- `POST /webhook/evolution-worker`: processamento da fila
- `GET /webhook/evolution-queue-stats`: estatisticas da fila
- `POST /webhook/evolution-dlq-retry`: reprocessamento de falhas

### 3.2 Configuracao administrativa
- `POST /admin/{slug}/evolution/configure_webhook/{instanceName}` (controller admin equivalente)
- `POST/GET /admin/{slug}/evolution/instance/{instanceName}/customer-engagement`
- `POST/GET /admin/{slug}/evolution/instance/{instanceName}/order-notification`

### 3.3 Pausa programada
- `GET /admin/{slug}/pause/status`
- `POST /admin/{slug}/pause/enable`
- `POST /admin/{slug}/pause/disable`
- `POST /admin/{slug}/pause/extend`

Tambem existem rotas mobile equivalentes para configuracao/pausa.

## 4. Fluxo de Entrada (Webhook)

### 4.1 Recepcao do evento
`messages()` executa:
1. le payload bruto;
2. normaliza tipo de evento;
3. ignora `MESSAGES_SET` (dump historico ao reconectar);
4. aceita `MESSAGES_UPSERT` (com variacoes de nome);
5. se `fromMe=true`, trata como potencial takeover humano;
6. aplica idempotencia por `message_id`;
7. enfileira e responde `{"status":"ok"}`.

### 4.2 Guards e filtros
- Eventos nao suportados sao ignorados com `status=ok`.
- Duplicatas por `message_id` nao sao reprocessadas.
- Mensagens `fromMe` nao disparam automacao; entram no fluxo de takeover humano.

## 5. Fluxo do Worker (Fila Assincrona)

### 5.1 Comportamento
`processQueue()`:
- roda em janela aproximada de 55s;
- faz polling quando fila esta vazia;
- usa locks por instancia (evita concorrencia indevida);
- recupera itens travados por heartbeat expirado;
- aplica backpressure adaptativo e fairness por instancia.

### 5.2 Processamento de cada mensagem
Para cada item:
1. parse da mensagem;
2. extracao de texto com unwrapping robusto (`ephemeral`, `viewOnce`, etc.);
3. resolve `company_id` por `instanceName`;
4. executa pequeno retry inline da fila de falhas de envio;
5. verifica takeover humano ativo;
6. executa regra de resposta automatica (pausa/fora de horario);
7. salva mensagem recebida.

## 6. Regras de Negocio de Resposta Automatica

### 6.1 Prioridade de regras
Ordem aplicada no processamento:
1. Takeover humano ativo bloqueia automacoes
2. Pausa programada (se ativa) tem prioridade sobre horario de funcionamento
3. Fora de expediente (quando loja fechada)

### 6.2 Fora de expediente
- Dependencia de `customer_engagement_config.out_of_hours_enabled`.
- Se loja estiver aberta (`company_hours`), nao envia resposta.
- Se fechada, monta mensagem personalizada/padrao com proxima abertura.
- Aplica cooldown anti-spam.

### 6.3 Pausa programada
- Dependencia de `customer_engagement_config.scheduled_pause_enabled`.
- Estado de pausa vem de `companies` (`pause_enabled`, `pause_until`, `pause_reason`, `pause_type`).
- Tipos suportados:
  - `timed` (X minutos)
  - `scheduled` (ate data/hora)
  - `indefinite` (indefinida)
- Monta mensagem personalizada/padrao com motivo e tempo restante.
- Aplica cooldown proprio (separado do fora de expediente).

### 6.4 Cooldown anti-spam
- Cooldown padrao: 30 minutos.
- Persistido em `out_of_hours_responses`.
- Implementacao atual separa por tipo de resposta usando `response_type`:
  - `out_of_hours`
  - `scheduled_pause`

Resultado pratico:
- envio de pausa programada nao fica bloqueado por cooldown anterior de fora de expediente;
- envio de fora de expediente nao usa cooldown de pausa programada.

## 7. LID, Resolucao e Confianca

### 7.1 Quando chega `@lid`
- Se LID nao resolvido para numero real: nao envia direto; salva pendente.
- Se LID resolvido com confianca `LOW`: nao envia auto-resposta.
- Somente LID com confianca `HIGH` e elegivel para envio automatico.

### 7.2 Mensagens pendentes
Mensagens de auto-resposta para LID sem resolucao/confianca adequada sao guardadas para envio posterior, evitando envio incorreto.

## 8. Human Takeover (Atendimento Humano)

### 8.1 Conceito
Quando humano assume a conversa, automacoes sao bloqueadas temporariamente para evitar conflito bot x atendente.

### 8.2 Ativacao
- Mensagens `fromMe` passam por deteccao de echo automatico vs interacao humana.
- Se identificado humano, ativa takeover para o telefone.

### 8.3 Deteccao de echo automatico
2 camadas:
1. Correlacao deterministica por `sent_message_id` em `whatsapp_send_log`.
2. Fallback temporal (janela curta) em logs de envio automatico.

### 8.4 Expiracao e renovacao
- Expiracao padrao: 30 minutos sem atividade humana.
- Nova mensagem humana renova prazo.
- Rotina expira takeovers vencidos automaticamente.

### 8.5 Efeitos do takeover
- bloqueia auto-resposta fora de expediente/pausa;
- cancela retries pendentes na `whatsapp_failed_queue` para o telefone;
- pode cancelar pendencias relacionadas a LID daquele contato.

## 9. Envio de Mensagens (WhatsAppSendService)

### 9.1 Objetivo
Centralizar qualquer envio WhatsApp com:
- log persistente por tentativa;
- retry com backoff exponencial;
- fallback de formato de destino;
- fila de falhas para reprocessamento;
- validacoes de takeover e rate limit.

### 9.2 Regras importantes
- `messageType=notification` nao sofre bloqueio de takeover/rate-limit do mesmo modo das automacoes.
- Rate limits por empresa:
  - por minuto: 20
  - por hora: 200
- Retry imediato configuravel (padrao ate 3 tentativas).
- Fallback alterna entre numero puro e JID quando necessario.

### 9.3 Observabilidade de envio
Cada tentativa grava em `whatsapp_send_log`:
- company/instancia/destino;
- tipo de mensagem;
- status e tentativa;
- HTTP/cURL/body resumido;
- `sent_message_id` quando disponivel;
- duracao da chamada.

### 9.4 Fila de falhas
Falhas podem ser enfileiradas em `whatsapp_failed_queue`:
- status inicial `pending`;
- reprocessamento com backoff (10, 30, 60 min...);
- transicoes para `sent` ou `abandoned` apos max retries.

### 9.5 Auto-recuperacao
`processDueFailedQueue()` roda em cenarios de self-healing, inclusive inline no worker, reduzindo dependencia exclusiva de cron externo.

## 10. Configuracao por Instancia

### 10.1 Webhook Evolution
A configuracao correta do webhook usa:
- endpoint Evolution: `/webhook/set/{instanceName}`
- URL destino: `https://{slug}.online/webhook/evolution/{instanceName}`
- evento: `MESSAGES_UPSERT`

### 10.2 Config de engajamento
`customer_engagement_config` guarda por empresa/instancia:
- habilitacao geral;
- cenarios de engajamento;
- fora de expediente (enable + mensagem custom);
- pausa programada (enable + mensagem custom).

### 10.3 Config de notificacao de pedido
`instance_configs` com `config_key=order_notification` guarda:
- habilitado/desabilitado;
- numeros principal/secundario;
- campos de mensagem.

Regra operacional: apenas uma instancia ativa para notificacao por empresa (com suporte a `force_switch`).

## 11. Pausa Programada (Operacao)

### 11.1 API de pausa
`AdminPauseController` + `ScheduledPauseService` permitem:
- ativar pausa temporizada;
- ativar pausa ate data/hora;
- ativar pausa indefinida;
- desativar pausa;
- estender pausa ativa.

### 11.2 Timezone
Calculos de pausa e horario usam `America/Sao_Paulo`.

### 11.3 Integracao com auto-resposta
Se `is_paused=true`, o sistema responde com mensagem de pausa antes de avaliar horario de funcionamento.

## 12. Tabelas e Dados Relevantes

### 12.1 Entrada e processamento
- `whatsapp_webhook_queue`: fila dos webhooks recebidos
- `customer_engagement_config`: configuracoes de automacao
- `company_hours`: horario de funcionamento
- `companies`: estado de pausa programada

### 12.2 Controle de envio e anti-spam
- `out_of_hours_responses`: historico/cooldown de respostas automaticas
- `whatsapp_send_log`: log detalhado de tentativas de envio
- `whatsapp_failed_queue`: fila de retry/falhas de envio
- `whatsapp_human_takeover`: estado de takeover humano

### 12.3 Suporte a LID
- `whatsapp_lid_mapping`: mapeamento LID -> numero com confianca
- `pending_out_of_hours_messages`: mensagens pendentes para LID nao resolvido

## 13. Monitoramento e Operacao

### 13.1 Logs chave
Monitorar logs por prefixos:
- `[Webhook Evolution]`
- `[Webhook Worker]`
- `[WhatsAppSend]`
- `[Out of Hours Webhook]`
- `[Order Notification]`

### 13.2 Sinais de saude
- webhook responde rapido com `{"status":"ok"}`;
- fila reduz ao longo do tempo (sem crescimento continuo);
- envios com `status=success` no `whatsapp_send_log`;
- baixa taxa de `abandoned` na `whatsapp_failed_queue`.

## 14. Troubleshooting (Guia Rapido)

### 14.1 Mensagem nao chega fora de expediente
Checklist:
1. webhook configurado na Evolution (`webhook/find/{instance}` / `webhook/set/{instance}`)
2. URL correta incluindo nome da instancia
3. `instance_name` alinhado entre Evolution e banco
4. `out_of_hours_enabled=1` na config
5. empresa realmente fora de horario
6. takeover humano nao ativo
7. cooldown nao ativo para `out_of_hours`
8. logs de envio (`whatsapp_send_log`) e fila de falha

### 14.2 Pausa programada nao envia
Checklist:
1. `pause_enabled=1` e pausa ainda valida
2. `scheduled_pause_enabled=1`
3. takeover humano nao ativo
4. cooldown de `scheduled_pause` nao ativo
5. LID resolvido com confianca `HIGH` (quando aplicavel)

### 14.3 Cooldown parece "travado"
- Cooldown existe para evitar spam.
- Mesmo apos expirar, o sistema so envia quando houver nova mensagem recebida do cliente.
- Cooldown agora e por tipo (`out_of_hours` vs `scheduled_pause`).

### 14.4 Mudanca feita no codigo mas nao refletiu
Em ambientes sem bind mount de codigo:
- editar workspace nao basta;
- e necessario copiar para o container (ou fazer redeploy da imagem/stack).

## 15. Boas Praticas Operacionais
- Padronizar naming de instancia entre painel Evolution e banco.
- Validar webhook apos criar/recriar instancia.
- Evitar alteracoes manuais de schema em producao sem migracao versionada.
- Manter cron/worker ativos para drenagem de fila e retries.
- Auditar periodicamente `whatsapp_send_log` e `whatsapp_failed_queue`.

## 16. Fluxo Resumido (Fim a Fim)
1. Cliente envia mensagem para WhatsApp.
2. Evolution envia webhook para `/webhook/evolution/{instanceName}`.
3. Sistema valida, filtra, faz idempotencia e enfileira.
4. Worker consome fila e resolve contexto (empresa/cliente).
5. Se takeover humano ativo: bloqueia automacao.
6. Se pausa ativa: avalia cooldown de `scheduled_pause` e responde.
7. Senao, se fora de horario: avalia cooldown de `out_of_hours` e responde.
8. Envio passa por `WhatsAppSendService` (retry/fallback/log/fila de falhas).
9. Mensagem recebida e dados operacionais ficam persistidos para auditoria.

---
Documento tecnico-operacional atualizado para o estado atual do sistema e regras validadas em producao.
