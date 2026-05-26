# `app/services/IFood/` — Integração modular com a API iFood

Arquitetura introduzida na **Fase 0 (Fundações)**. Cada feature da
integração iFood (estoque, reviews, requestDriver, shipping…) é construída
sobre os blocos descritos aqui.

> **Existe outra classe** — `app/services/IFoodService.php` (1.299 linhas) — que
> contém o pipeline de polling/webhook/order já em produção. Por ora ela continua
> funcionando como está; futuras features novas devem usar os blocos deste módulo
> ao invés de reproduzir o cURL inline.

## Visão geral

```
                 ┌─────────────────────────┐
                 │  routes → controller    │  (admin UI dispara ação)
                 └────────────┬────────────┘
                              │ enqueue
                              ▼
                ┌──────────────────────────────┐
                │   queue_jobs (job_type =      │
                │     'ifood.<domain>.<op>')   │
                └────────────┬─────────────────┘
                             │ cron a cada 1 min
                             ▼
            ┌──────────────────────────────────┐
            │ scripts/ifood_worker.php          │
            │   ↳ IFoodJobWorker                │
            │       ↳ IFoodJobDispatcher        │
            │            ↳ Handler concreto     │
            │                ↳ IFoodClient      │
            │                      ↳ IFoodApiLogger
            └──────────────────────────────────┘
                             │
                             ▼
                  API do iFood (sandbox ou produção)
```

## Componentes

| Arquivo | O que faz |
|---|---|
| `IFoodEndpoints.php` | URLs por env + módulo (auth/order/catalog/review/shipping…). Não toca em HTTP. |
| `IFoodApiLogger.php` | Persiste cada chamada em `ifood_api_logs` com sanitização de tokens/PII. Best-effort: log não interrompe. |
| `IFoodResponse.php` | DTO imutável devolvido pelo client. Tem `ok`, `status`, `body`, `error`, `latencyMs`, `attempts`, `responseHeaders`. |
| `IFoodClient.php` | Cliente HTTP central. Retry exponencial (3 tentativas, 1s/2s/4s + jitter), respeita `Retry-After`, logging automático. Stateless por job. |
| `IFoodJobHandler.php` | Interface — todo handler implementa `handle(array $job, array $payload): void`. |
| `IFoodRetryableException.php` | Sinaliza falha transitória — worker reagenda. Qualquer outra exceção → dead-letter. |
| `IFoodJobDispatcher.php` | Registry: `register('ifood.x.y', HandlerClass::class)` + `dispatch($job, $payload)`. |
| `IFoodJobWorker.php` | Loop de consumo de `queue_jobs`. Reserva atômica via `SELECT FOR UPDATE SKIP LOCKED`. Suporta `run($maxJobs)` (cron) e `loop($durationSec)` (daemon). |

## Como adicionar uma feature nova

> **Estoque já está implementado** (Fase 3 — ver `Handlers/StockSyncHandler.php`
> + `StockSyncDispatcher.php`). O exemplo abaixo é didático; a implementação
> real lê o estado do banco em vez de confiar no payload do job (mais robusta
> a múltiplas mudanças coalescidas).

1. **Criar handler** em `app/services/IFood/Handlers/<Feature>Handler.php`
   implementando `IFoodJobHandler`. Dentro dele, use `IFoodClient` para a
   chamada concreta:

   ```php
   final class StockSyncHandler implements IFoodJobHandler {
       public function __construct(private \PDO $db, private IFoodApiLogger $logger) {}

       public function handle(array $job, array $payload): void {
           $companyId = (int)$job['company_id'];
           $env = (string)($payload['environment'] ?? 'production');
           $token = $this->getTokenFor($companyId);  // delegar ao IFoodService legado
           $merchantId = (string)$payload['merchant_id'];
           $itemId = (string)$payload['item_id'];
           $available = (bool)($payload['available'] ?? false);

           $client = new IFoodClient($companyId, $env, $token, $this->logger, $job['id']);
           $res = $client->put(
               IFoodEndpoints::catalogItemStatus($env, $merchantId, $itemId),
               IFoodEndpoints::MODULE_CATALOG,
               ['status' => $available ? 'AVAILABLE' : 'UNAVAILABLE']
           );

           if (!$res->ok) {
               // 5xx/429/timeout → reagenda
               if ($res->status === null || $res->status >= 500 || $res->status === 429) {
                   throw new IFoodRetryableException($res->error ?? 'transient');
               }
               // 4xx → permanente, vai pra dead
               throw new \RuntimeException($res->error ?? 'permanent');
           }
       }
   }
   ```

2. **Criar bootstrap** em `app/services/IFood/Handlers/bootstrap.php` (carregado
   automaticamente pelo `scripts/ifood_worker.php`):

   ```php
   require_once __DIR__ . '/StockSyncHandler.php';
   /** @var IFoodJobDispatcher $dispatcher */
   $dispatcher->register('ifood.stock.sync', new StockSyncHandler(db(), new IFoodApiLogger(db())));
   ```

3. **Enfileirar** quando o admin alterar estoque no painel:

   ```php
   QueueJob::enqueue(
       jobType: 'ifood.stock.sync',
       companyId: $companyId,
       payload: ['merchant_id' => $m, 'item_id' => $i, 'available' => $a, 'environment' => $env],
       maxAttempts: 5
   );
   ```

4. O cron de 1 min processa o job, `ifood_api_logs` registra a chamada, retries
   acontecem automaticamente até `max_attempts` ou sucesso.

## Cron sugerido

```cron
# /etc/cron.d/multi-menu-ifood

# Worker — consome queue_jobs (todos os handlers iFood) a cada 1 min
* * * * * www-data php /var/www/html/scripts/ifood_worker.php --max=100 --verbose >> /var/www/html/storage/logs/ifood_worker.log 2>&1

# Reconciliação de estoque — detecta drift entre products.active e o último
# estado sincronizado, re-enfileira ifood.stock.sync. Roda a cada 15 min.
*/15 * * * * www-data php /var/www/html/scripts/ifood_stock_reconcile_cron.php >> /var/www/html/storage/logs/ifood_stock_reconcile.log 2>&1
```

Para deploy com supervisor (alternativa daemon):

```ini
[program:ifood_worker]
command=php /var/www/html/scripts/ifood_worker.php --loop=55 --verbose
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/www/html/storage/logs/ifood_worker.log
```

## Retenção de logs

Em produção, agendar a limpeza para evitar inflar `ifood_api_logs`:

```php
(new IFoodApiLogger(db()))->purgeOlderThan(30); // remove > 30 dias
```

## Ambientes

A coluna `ifood_integrations.environment` (`sandbox`/`production`) é a fonte
de verdade. `IFoodEndpoints::baseUrl($env)` resolve a URL. Hoje o iFood usa
o mesmo host para os dois (a separação é por credenciais), mas mantemos o
switch para futuro-proofing.

`sandbox_merchant_id` é guardado em campo separado para o admin poder
alternar entre os ambientes sem perder configuração.

## Fase 3 — Stock Sync (status AVAILABLE/UNAVAILABLE)

Sincroniza `products.active` ↔ catálogo do iFood via endpoint
`PUT /catalog/v2.0/.../items/{id}/status`.

**Gatilhos de enfileiramento:**
- `ProductService::toggle()` — admin toggle no painel.
- `ProductService::save()` — quando `active` muda entre versões.
- `POST /admin/{slug}/ifood/stock/sync` — manual (per produto ou tudo).
- `scripts/ifood_stock_reconcile_cron.php` — periódico, detecta drift.

**Coalescing:** múltiplas chamadas a `StockSyncDispatcher::syncProduct()`
dentro da janela de debounce (default 2s) são fundidas em 1 job via
`queue_jobs.dedup_key`. O worker nulla o `dedup_key` ao reservar, permitindo
que novas mudanças durante o processamento agendem outro job.

**Estado:** snapshot em `ifood_stock_sync_state` — usado para detectar drift
e evitar PUTs desnecessários (no-op quando local == último-remoto-conhecido).

**Reconciliação:** o cron `ifood_stock_reconcile_cron.php` faz LEFT JOIN
entre `ifood_product_mapping` e `ifood_stock_sync_state` e re-enfileira em
3 casos: (1) nunca sincronizado, (2) snapshot divergente, (3) falhas
consecutivas > 0. Cap por company via `CAP_PER_COMPANY` (default 200).

**Endpoints admin:**
- `POST /admin/{slug}/ifood/stock/sync` — body `{"product_id": N}` opcional.
- `GET /admin/{slug}/ifood/stock/state?env=production` — snapshot pra dashboard.

## Fase 4 — Driver Request (pedidos iFood nativos)

Solicita um entregador iFood para um pedido criado na plataforma (endpoint
`POST /order/v1.0/orders/{id}/requestDriver` via `IFoodEndpoints::orderAction`).

**Pré-requisitos validados em runtime:**
- Integração iFood ativa
- Pedido existe em `ifood_orders` (mesmo company)
- Pedido em status `CONFIRMED`, `READY_TO_PICKUP` ou `DISPATCHED`
- `delivered_by = 'MERCHANT'` (se for IFOOD a logística já é dele)

**Estados em `ifood_order_drivers`:**

| Estado | Significado |
|---|---|
| `PENDING` | job criado, ainda não chamou a API |
| `REQUESTED` | iFood respondeu 2xx ou 409; aguardando atribuição |
| `NO_DRIVER` | iFood respondeu 422; retry pelo worker com backoff exponencial |
| `ASSIGNED` | entregador atribuído (a popular via webhook na Fase 4B) |
| `COMPLETED` | entrega concluída |
| `CANCELLED` | cancelado (ou pedido virou IFOOD-entregue) |
| `FAILED` | 4xx não-422 permanente; job morto |

**Coalescing:** `DriverRequestDispatcher::requestForOrder()` usa
`dedup_key = ifood.driver:{company}:{env}:{ifood_order_id}`. Múltiplas
chamadas para o mesmo pedido enquanto há job pending são fundidas.

**Endpoints admin:**
- `POST /admin/{slug}/ifood/orders/{ifood_order_id}/request-driver`
- `GET /admin/{slug}/ifood/orders/{ifood_order_id}/driver-state`

## Fase 4B — Driver Lifecycle (webhook + cancel + auto-resubmit)

Fecha o ciclo de vida do entregador: eventos do iFood populam o estado real,
admin pode cancelar, cron re-enfileira pedidos travados.

**Webhook integration (`IFoodService::handleDriverEvent`):**

| fullCode recebido | Efeito em `ifood_order_drivers` |
|---|---|
| `DISPATCHED`, `ASSIGN_DRIVER`, `DRIVER_ASSIGNED` | `request_status=ASSIGNED` + `assigned_at` + popula `driver_id/name/phone/vehicle_type` do `metadata.driver` |
| `GOING_TO_ORIGIN`, `ARRIVED_AT_ORIGIN` | `ASSIGNED` (mantém) |
| `PICKED_UP`, `GOING_TO_DESTINATION`, `GOING_TO_CONSUMER` | `picked_up_at` populado |
| `CONCLUDED`, `DELIVERED`, `ARRIVED_AT_DESTINATION`, `ARRIVED_AT_CONSUMER` | `request_status=COMPLETED` + `delivered_at` |
| `CANCELLED`, `DRIVER_CANCELLED` | `request_status=CANCELLED` + `cancelled_at` |
| fullCode desconhecido **com** `metadata.driver` | atualiza driver info, mantém status |
| Pedido inexistente localmente | no-op silencioso |

Tolerante a vários formatos do payload: lê `metadata.driver`,
`metadata.delivery.driver` e `metadata.logistics.driver`.

**Cancel driver:**
- Handler `ifood.driver.cancel` → POST `/order/v1.0/orders/{id}/cancelDriver`
- Dispatcher: `DriverRequestDispatcher::cancelForOrder()` com dedup_key
  `ifood.driver-cancel:{company}:{env}:{orderId}`
- Endpoint: `POST /admin/{slug}/ifood/orders/{ifood_order_id}/cancel-driver`
- Skipa silenciosamente quando estado é `COMPLETED`/`CANCELLED`/`FAILED`
- `404` do iFood tratado como sucesso idempotente

**Auto-resubmit cron (`scripts/ifood_driver_resubmit_cron.php`):**

Re-enfileira `ifood.driver.request` para:
1. `NO_DRIVER` cujo `updated_at < NOW - NO_DRIVER_RETRY_MIN` (default 5 min)
2. `REQUESTED` há mais de `REQUESTED_TIMEOUT_MIN` (default 15 min) sem
   `assigned_at` — assignment perdido ou nunca chegou

Skipa pedidos com `retries >= MAX_RETRIES` (default 10) e estados terminais
(`CONCLUDED`/`CANCELLED`/`FAILED`).

Cron sugerido:
```cron
*/5 * * * * www-data php /var/www/html/scripts/ifood_driver_resubmit_cron.php >> /var/www/html/storage/logs/ifood_driver_resubmit.log 2>&1
```

Tuning via env: `NO_DRIVER_RETRY_MIN`, `REQUESTED_TIMEOUT_MIN`, `MAX_RETRIES`, `CAP_PER_COMPANY`.

## Fase 5 — Shipping Orders (HUB logístico)

Cria pedidos próprios (NÃO-iFood) na logística do iFood via
`POST /shipping/v1.0/orders`. Transforma o sistema em hub que aceita
pedidos do checkout local OU de sistemas externos e dispara entrega iFood.

**Identidade e idempotência:** chave externa `external_reference`
(UUID gerado ou order_number do caller). `UNIQUE KEY (company, env,
external_reference)` impede duplicação no banco, e a mesma chave vai no
body do request iFood (`externalReference`) para o servidor deduplicar.

**Estados em `ifood_shipping_orders`:**

| Status | Significa |
|---|---|
| `PENDING` | row criada, job ainda não rodou |
| `SUBMITTED` | iFood aceitou o POST (2xx); aguarda confirmação interna |
| `ACCEPTED` | iFood confirmou (via polling) |
| `CONFIRMED` | entregador atribuído |
| `PICKED_UP` | entregador retirou |
| `DELIVERED` | entrega concluída |
| `CANCELLED` | cancelado (nosso lado ou iFood) |
| `REJECTED` | iFood recusou (400/422/409 sem id) |
| `FAILED` | erro permanente irrecuperável |

**Dispatcher (`ShippingDispatcher`):**

```php
// Criar shipping order — dispatcher insere row + enfileira job
$result = ShippingDispatcher::createShippingOrder(
    $companyId,
    $iFoodPayload,                    // customer/items/pickup/delivery/payment/dimensions/weight
    ['external_reference' => 'ORDER-123', 'order_id' => 42]
);
// → ['ok' => true, 'external_reference' => '...', 'enqueued' => true, 'already_existed' => false]

// Cancelar
$cancel = ShippingDispatcher::cancelShippingOrder($companyId, 'ORDER-123', 'cliente desistiu');
```

**Handler create (`ifood.shipping.create`):**

- Lê `request_payload` da row (não confia no payload do job → idempotente em retry)
- 2xx → SUBMITTED + `ifood_shipping_id` + `next_poll_at` = NOW + 30s
- 409 com id no body → SUBMITTED idempotente (retry após sucesso parcial)
- 409 sem id, 400, 422 → REJECTED + dead
- 401/429/5xx/rede → IFoodRetryableException
- Outros 4xx → FAILED + dead

**Handler cancel (`ifood.shipping.cancel`):**

- 2xx ou 404 → CANCELLED (404 = idempotente, já não existe no iFood)
- Row sem `ifood_shipping_id` → CANCELLED só local (não chegou a chamar a API)
- 401/429/5xx → retryable

**Polling cron (`ifood_shipping_poll_cron.php`):**

Sincroniza status via GET `/shipping/v1.0/orders/{id}`. Recalcula
`next_poll_at` por etapa: SUBMITTED=30s, ACCEPTED=60s, CONFIRMED=90s,
PICKED_UP=120s. Estados terminais zeram `next_poll_at`. 404 do iFood
→ marca FAILED para parar de polar.

Cron sugerido (a cada 1 min):
```cron
* * * * * www-data php /var/www/html/scripts/ifood_shipping_poll_cron.php >> /var/www/html/storage/logs/ifood_shipping_poll.log 2>&1
```

**Endpoints admin:**
- `POST /admin/{slug}/ifood/shipping/orders` — body JSON `{external_reference?, order_id?, payload}`
- `GET  /admin/{slug}/ifood/shipping/orders` — lista (filtros: `status`, `env`)
- `GET  /admin/{slug}/ifood/shipping/orders/{ext}` — estado de um pedido
- `POST /admin/{slug}/ifood/shipping/orders/{ext}/cancel` — body `reason` opcional

**Como o sistema lida com os problemas clássicos:**

| Problema | Mitigação |
|---|---|
| **Timeout** | Worker re-tenta com backoff exponencial; row preserva `request_payload` |
| **Duplicidade** | UNIQUE KEY + `INSERT IGNORE` no dispatcher + `external_reference` no body |
| **Race condition** | UNIQUE KEY garante 1 row por (company, env, ref); dedup_key impede 2 jobs |
| **Webhook duplicado** | (Fase 5B) — usar `ifood_events_log.event_id` para dedup |
| **Retry exponencial** | Built-in no `IFoodJobWorker` (1m, 2m, 4m, 8m...) |

## Fase 5B — Shipping Orders (quote + webhook + auto-cancel)

**Quote síncrono (`POST /shipping/v1.0/quote`):**

```php
$result = ShippingDispatcher::quoteShippingOrder($companyId, [
    'pickup' => [...], 'delivery' => [...], 'items' => [...]
]);
// → ['ok' => true, 'quote' => {...iFood response...}, 'http_status' => 200, 'message' => null]
```

Endpoint admin: `POST /admin/{slug}/ifood/shipping/quote` (body `{payload: {...}}`).
Não persiste — caller usa o resultado pra decidir se aceita o frete e
então chama `POST /admin/.../ifood/shipping/orders` com o payload completo.

**Webhook integration (`IFoodService::handleShippingEvent`):**

Mesma stream de eventos que pedidos nativos, mas o `orderId` casa com
`ifood_shipping_id`. Lookup automático após o handler de driver — se não
encontra em `ifood_orders`, tenta `ifood_shipping_orders`.

| fullCode → status local | Timestamp |
|---|---|
| `DISPATCHED`, `ASSIGN_DRIVER`, `DRIVER_ASSIGNED`, `GOING_TO_ORIGIN`, `ARRIVED_AT_ORIGIN` → `CONFIRMED` | `accepted_at` populado se nulo |
| `PICKED_UP`, `GOING_TO_DESTINATION`, `GOING_TO_CONSUMER` → `PICKED_UP` | `picked_up_at` |
| `CONCLUDED`, `DELIVERED`, `ARRIVED_AT_DESTINATION/CONSUMER` → `DELIVERED` | `delivered_at` + `next_poll_at=NULL` |
| `CANCELLED`, `DRIVER_CANCELLED` → `CANCELLED` | `cancelled_at` + `next_poll_at=NULL` |

**Anti-downgrade:** se a row já está em DELIVERED/CANCELLED/REJECTED/FAILED,
eventos atrasados (ex: CONFIRMED chegando depois de DELIVERED) são ignorados.

**Auto-cancel cron (`scripts/ifood_shipping_autocancel_cron.php`):**

Detecta shipping orders travados em estados intermediários por tempo demais
e enfileira `ifood.shipping.cancel`. Thresholds por estado (env-overridable):

```cron
*/30 * * * * www-data php /var/www/html/scripts/ifood_shipping_autocancel_cron.php >> /var/www/html/storage/logs/ifood_shipping_autocancel.log 2>&1
```

| Env | Default | Significa |
|---|---|---|
| `SUBMITTED_TIMEOUT_HOURS` | 1 | submetido mas iFood não confirmou |
| `ACCEPTED_TIMEOUT_HOURS`  | 2 | confirmado mas sem entregador |
| `CONFIRMED_TIMEOUT_HOURS` | 2 | entregador atribuído sem retirar |
| `PICKED_UP_TIMEOUT_HOURS` | 3 | pegou mas não entregou |
| `MAX_RETRIES`             | 5 | acima disso, deixa pra operador olhar |
| `CAP_PER_COMPANY`         | 50 | máx por execução |

Rows com `retries >= MAX_RETRIES` são puladas — provável causa estrutural.

## Fase 6 — Central Logística (dashboard agregado)

Camada de leitura sobre todas as tabelas iFood — sem novas tabelas, sem
novas crons. Agrega `ifood_orders` + `ifood_order_drivers` +
`ifood_shipping_orders` + `ifood_stock_sync_state` + `queue_jobs` em visão
operacional única.

**Service:** `app/services/IFood/LogisticsDashboardService.php`

```php
$service = new LogisticsDashboardService(db());
$dashboard = $service->dashboard($companyId);
// → ['summary', 'active', 'metrics_24h', 'alerts', 'queue_health', 'sla', 'company_id', 'generated_at']
```

Cache em memória por instância (mesma request reusa). Para cache
cross-request usar Redis/filesystem por cima.

**Endpoints (todos GET):**

| Rota | Conteúdo |
|---|---|
| `/admin/{slug}/ifood/logistics/dashboard` | Tudo numa chamada (recomendado pra polling de UI) |
| `/admin/{slug}/ifood/logistics/summary`   | Counters: pedidos ativos, drivers, shipping, drift |
| `/admin/{slug}/ifood/logistics/active?limit=30` | Lista de pedidos/shipping em curso com SLA flag |
| `/admin/{slug}/ifood/logistics/metrics`   | Métricas 24h: taxas, tempos médios |
| `/admin/{slug}/ifood/logistics/alerts`    | Lista priorizada de alertas críticos |

**Tipos de alerta gerados:**

| Type | Level | Condição |
|---|---|---|
| `kitchen_sla_breach`         | warning  | pedido CONFIRMED há > `IFOOD_SLA_KITCHEN_MIN` (default 25) |
| `delivery_sla_breach`        | critical | pedido DISPATCHED há > `IFOOD_SLA_DELIVERY_MIN` (default 45) |
| `no_driver_prolonged`        | warning  | driver NO_DRIVER há > `IFOOD_SLA_NO_DRIVER_MIN` (default 10) |
| `shipping_stuck_submitted`   | warning  | shipping SUBMITTED há > `IFOOD_SLA_SHIPPING_SUB_MIN` (default 60) |
| `shipping_failures_24h`      | critical | shipping em FAILED/REJECTED nas últimas 24h |
| `stock_drift`                | info     | produtos com `desired_status ≠ last_synced_status` |

**Métricas calculadas (janela = 24h):**

- `orders_received` / `orders_completed` / `orders_cancelled`
- `cancellation_rate` (0.0–1.0)
- `avg_kitchen_minutes`   = avg(`ready_at - confirmed_at`)
- `avg_pickup_minutes`    = avg(`dispatched_at - ready_at`)
- `avg_delivery_minutes`  = avg(`concluded_at - dispatched_at`)
- `driver_acceptance_rate` = `ASSIGNED+COMPLETED / total_requested`
- `shipping_success_rate`  = `DELIVERED / total_submitted`
- `no_driver_events`

**SLA env-overridáveis:** `IFOOD_SLA_KITCHEN_MIN`, `IFOOD_SLA_PICKUP_MIN`,
`IFOOD_SLA_DELIVERY_MIN`, `IFOOD_SLA_NO_DRIVER_MIN`, `IFOOD_SLA_SHIPPING_SUB_MIN`.

**Fora do escopo (limitações conscientes):**
- Mapa em tempo real — requer lat/lng dos entregadores (não temos)
- Heatmap histórico — requer agregação consolidada (não há tabela; faria sense uma materialized view)
- Websocket push — infra do projeto é PHP request-response; polling do front a cada 5-10s atende

## Fase 7 — DLQ Observability

Camada de visibilidade e remediação da Dead-Letter Queue + saúde da API
iFood. Sem novas tabelas — agrega `queue_jobs` + `ifood_api_logs`.

**Service:** `app/services/IFood/DLQObservabilityService.php`

```php
$dlq = new DLQObservabilityService(db());

$dlq->health();                              // tudo consolidado
$dlq->queueStats();                          // counters por status + janelas
$dlq->apiHealth();                           // taxa de erro, by_module, by_status
$dlq->latencyStats();                        // avg/p50/p95/p99/max (1h)
$dlq->topErrors(10);                         // top mensagens de erro (24h)
$dlq->deadJobs($companyId, 'ifood.x', 50);   // lista paginada
$dlq->retryDeadJob($jobId);                  // dead → retrying
$dlq->retryDeadJobsByType('ifood.x', $cid);  // bulk retry
$dlq->alertingViolations();                  // o que está violando thresholds AGORA
```

**Endpoints admin:**

| Método | Rota | Conteúdo |
|---|---|---|
| GET  | `/admin/{slug}/ifood/observability/health` | tudo |
| GET  | `/admin/{slug}/ifood/observability/api-health` | só api + latência |
| GET  | `/admin/{slug}/ifood/observability/dead-jobs?job_type=...&limit=50&offset=0&all=1` | lista (use `all=1` pra cross-company) |
| POST | `/admin/{slug}/ifood/observability/dead-jobs/{id}/retry` | retry individual (body `reset_attempts=1`) |
| POST | `/admin/{slug}/ifood/observability/dead-jobs/retry-all` | bulk (JSON `{job_type, all_companies?, limit?}`) |

**Cron de alertas (`scripts/ifood_dlq_alerts_cron.php`):**

Avalia thresholds a cada 5 min. Destinos:
1. `error_log` (sempre)
2. Slack webhook se `IFOOD_DLQ_SLACK_WEBHOOK` configurado

Deduplicação: 1 alerta por `type` a cada `IFOOD_DLQ_ALERT_COOLDOWN_MIN`
(default 15 min). Estado guardado em `ifood_api_logs` com `module='dlq_alert'`.

```cron
*/5 * * * * www-data php /var/www/html/scripts/ifood_dlq_alerts_cron.php >> /var/www/html/storage/logs/ifood_dlq_alerts.log 2>&1
```

**Thresholds (env-overridáveis):**

| Env | Default | O que dispara |
|---|---|---|
| `IFOOD_DLQ_DEAD_1H`              | 5     | jobs `dead` na última 1h |
| `IFOOD_DLQ_API_ERROR_RATE`       | 0.10  | taxa de erro da API (4xx+5xx+net) — mín 20 calls |
| `IFOOD_DLQ_LATENCY_P95`          | 5000  | p95 em ms — mín 20 calls |
| `IFOOD_DLQ_ALERT_COOLDOWN_MIN`   | 15    | cooldown entre alertas do mesmo type |
| `IFOOD_DLQ_SLACK_WEBHOOK`        | (none)| URL do webhook Slack |

**Cron de cleanup (`scripts/ifood_jobs_cleanup_cron.php`):**

Purga registros antigos para manter as tabelas saudáveis. Suporta `--dry-run`.

```cron
0 3 * * * www-data php /var/www/html/scripts/ifood_jobs_cleanup_cron.php >> /var/www/html/storage/logs/ifood_jobs_cleanup.log 2>&1
```

| Env | Default | Aplica em |
|---|---|---|
| `IFOOD_JOBS_RETENTION_DONE_DAYS` | 7  | `queue_jobs status='done'` |
| `IFOOD_JOBS_RETENTION_DEAD_DAYS` | 30 | `queue_jobs status='dead'` (maior — pra revisão) |
| `IFOOD_LOGS_RETENTION_DAYS`      | 30 | `ifood_api_logs` (reusa `IFoodApiLogger::purgeOlderThan`) |

**Cron completo recomendado (todos do roadmap iFood):**

```cron
# Worker (todos os handlers iFood)
* * * * * php /scripts/ifood_worker.php --max=100

# Polling de eventos iFood
* * * * * php /scripts/ifood_polling_cron.php

# Reconciliação de estoque
*/15 * * * * php /scripts/ifood_stock_reconcile_cron.php

# Auto-resubmit driver
*/5 * * * * php /scripts/ifood_driver_resubmit_cron.php

# Polling status shipping
* * * * * php /scripts/ifood_shipping_poll_cron.php

# Auto-cancel shipping travado
*/30 * * * * php /scripts/ifood_shipping_autocancel_cron.php

# DLQ alerts
*/5 * * * * php /scripts/ifood_dlq_alerts_cron.php

# Cleanup diário
0 3 * * * php /scripts/ifood_jobs_cleanup_cron.php
```

## Fase 8 — Widget iFood

Botão/iframe que o merchant embarca no próprio site para mandar visitantes
direto ao cardápio iFood, com helper de tracking de pedidos. Não substitui
o cardápio do site — complementa.

**Service:** `app/services/IFood/IFoodWidgetService.php`

```php
$service = new IFoodWidgetService(db(), $cacheDir);

$service->getConfig($companyId);                  // full (admin)
$service->getPublicConfig($companyId);            // sanitizada (público)
$service->saveConfig($companyId, $payload);       // upsert + bump cache_version
$service->buildMerchantUrl($config);              // URL do merchant no iFood
$service->buildTrackingUrlFromDisplayId($id);     // pedido.ifood.com.br/r/{id}
$service->trackingUrlForOrder($cid, $ref);        // lookup por display_id/UUID/orders.id
$service->renderJsSnippet($companyId);            // snippet auto-contido (cacheado)
```

**Admin endpoints:**

| Método | Rota | Conteúdo |
|---|---|---|
| GET  | `/admin/{slug}/ifood/widget/config` | Config completa |
| POST | `/admin/{slug}/ifood/widget/config` | Salva (JSON body, whitelist + validação) |
| GET  | `/admin/{slug}/ifood/widget/preview` | HTML preview com o snippet injetado |

**Public endpoints (sem auth):**

| Método | Rota | Para |
|---|---|---|
| GET | `/api/{slug}/ifood-widget/config.json` | Config sanitizada + URLs |
| GET | `/api/{slug}/ifood-widget/ifood.js` | Snippet JS auto-contido (cache 1h) |
| GET | `/api/{slug}/ifood-widget/track/{ref}` | URL de tracking pra um pedido |

**Como usar no site público:**

```html
<!doctype html>
<html>
<body data-store-slug="wollburger">
  <!-- Opcional: slot para widget_type=embedded -->
  <div data-ifood-widget-slot></div>

  <script src="https://meusite.com/api/wollburger/ifood-widget/ifood.js"></script>
  <script>
    // Helper exposto pela snippet — abre tracking em nova aba
    document.getElementById('btn-rastrear').onclick = () =>
      window.iFoodWidget.track('ABC1234');
  </script>
</body>
</html>
```

**Tipos de widget:**

| `widget_type` | Comportamento |
|---|---|
| `button` (default) | Botão flutuante (position configurável) que abre `merchant_url` em nova aba |
| `embedded` | Procura `[data-ifood-widget-slot]` e injeta iframe inline; fallback pra link se iframe falhar |
| `tracking_only` | Não renderiza UI; só expõe `window.iFoodWidget.track()` |

**Cache do snippet:**

- Filesystem (`storage/cache/ifood_widget/`), key = `{company_id}_v{cache_version}`
- `cache_version` é bumpado a cada `saveConfig` → próximo GET regenera
- Browser/edge cacheia 1h via `Cache-Control: public, max-age=3600`

**CORS:**

- Default: `Access-Control-Allow-Origin: *` (widget público faz sentido global)
- Se admin setar `allowed_origins` (CSV), só esses domínios recebem o header

**Validação no saveConfig:**

- Whitelist de campos (defensive)
- `widget_type`/`theme`/`position` validados contra enum
- `merchant_url`/`fallback_url` passam por `FILTER_VALIDATE_URL`
- Campos desconhecidos são silenciosamente ignorados

**Limitações conscientes:**

- Não tenta replicar o cardápio iFood — usa URL pública do merchant
- Não negocia OAuth com iFood — widget é só link/iframe estático
- Tracking depende de `ifood_display_id` ter sido populado via webhook/polling
  (Fases 1-2 fazem isso automaticamente)
