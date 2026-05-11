# SISTEMA MULTI MENU - DOCUMENTACAO TECNICA COMPLETA (RUNTIME ATIVO)

Versao: 1.0.0  
Data: 2026-05-09  
Escopo: codigo ativo em runtime (public/, app/, routes/, scripts/, docker*, database/migrations ativas)  
Fora de escopo principal: artefatos de legado/quarentena (ex.: storage/legacy-quarantine)

---

## 1. Objetivo

Este documento consolida a arquitetura tecnica real do Multi Menu com base em evidencias de codigo executavel.

Critios de qualidade adotados:
- Sem suposicoes nao verificadas em codigo.
- Cada area funcional aponta para artefatos de implementacao.
- Riscos sao classificados em Alto/Medio/Baixo.
- Foco em operacao real e regressao funcional.

---

## 2. Visao Geral do Sistema

O Multi Menu e uma plataforma de pedidos com:
- Frontend publico server-rendered (catalogo, produto, carrinho, checkout).
- Backoffice admin desktop e admin mobile.
- API autenticada para operacoes externas.
- Integracao com iFood (polling + webhook).
- Integracao com WhatsApp/Evolution (webhook + fila + retries).
- Cache e sessao com Redis (quando disponivel) + fallback controlado.
- Execucao em Docker Compose (dev/prod simples) e Docker Swarm com Traefik.

Evidencias principais:
- Entrada HTTP: public/index.php
- Bootstrapping: app/bootstrap.php
- Rotas: routes/web.php
- Dispatch de rotas: app/core/Router.php
- Controllers publicos: app/controllers/PublicHomeController.php, app/controllers/PublicProductController.php, app/controllers/PublicCartController.php
- Admin layouts: app/views/admin/layout.php, app/views/admin/mobile/layout.php

---

## 3. Arquitetura de Execucao

## 3.1 Pipeline de requisicao HTTP

Sequencia principal:
1. public/index.php carrega .env, bootstrap e middlewares globais.
2. Subdominio/host e resolvido para contexto (publico/admin/mobile).
3. SessionManager inicializa sessao com politicas de seguranca.
4. Security headers e rate limiting sao aplicados.
5. CSRF e validado com excecoes de rotas especificas.
6. Router localiza rota por metodo+path e despacha Controller@metodo.
7. Resposta renderizada (HTML/JSON).

Evidencias:
- public/index.php
- app/core/SessionManager.php
- app/core/Router.php
- app/middleware/*

## 3.2 Estrutura modular

Modulos em runtime:
- Publico: cardapio, produto, customizacao, carrinho, checkout.
- Admin Desktop: operacao completa (pedidos, produtos, categorias, frete, config, integracoes).
- Admin Mobile: operacao touch com navegacao otimizada.
- API: endpoints REST protegidos por ApiSecurity.
- Webhooks: iFood e Evolution/WhatsApp.
- Async Jobs: workers e crons para fila/polling/retry/engajamento.

Evidencias:
- routes/web.php
- app/controllers/ApiController.php
- app/controllers/WebhookIFoodController.php
- app/controllers/WebhookEvolutionController.php
- scripts/*.php

## 3.3 Convencoes de roteamento

- Nome de rota com guardrails para evitar anonimato em fluxo de negocio.
- Grupos por dominio funcional (public/admin/mobile/api/webhook).
- Dependencia de naming consistente para sidebar, permissao e navegao.

Evidencias:
- app/core/Router.php
- routes/web.php
- app/views/admin/layout.php

Risco:
- Medio: divergencia de naming afeta navegacao ativa, permissao e observabilidade.

---

## 4. Dominio Funcional

## 4.1 Catalogo e descoberta

Capacidades:
- Renderizacao por slug de empresa.
- Listagem de categorias e produtos ativos.
- Regras de disponibilidade (aberto/pausado/horario).
- Busca e pontos de reorder.

Evidencias:
- app/controllers/PublicHomeController.php
- app/models/Company.php
- app/models/Category.php
- app/models/Product.php
- app/views/home/home.php
- public/assets/home.js

Riscos:
- Medio: regras de horario distribuidas entre backend e apresentacao.

## 4.2 Produto, personalizacao e cross-sell

Capacidades:
- Produto simples/combos com grupos customizaveis.
- Grupos por tipo (single/addon/pool/qty) com min/max e defaults.
- Cross-sell por categoria disparadora com secoes de recomendacao.
- Persistencia de customizacao por unidade e por parent item.

Evidencias:
- app/controllers/PublicProductController.php
- app/models/ProductCustomization.php
- app/models/CrossSellGroup.php
- app/models/CustomizationTemplate.php
- app/views/product/product.php
- public/assets/product.js
- public/assets/customization.js

Riscos:
- Alto: alta complexidade de estado (frontend + sessao + backend) aumenta chance de regressao.
- Medio: regras de defaults e ocultacao de duplicados exigem testes de borda.

## 4.3 Carrinho e checkout

Capacidades:
- Carrinho com recalculo e hidratacao de itens customizados.
- Cupom, fidelidade, taxa de entrega, descontos e totais.
- Sincronizacao de estado cliente/servidor durante checkout.
- Criacao do pedido com validacoes de endereco/pagamento.

Evidencias:
- app/controllers/PublicCartController.php
- app/services/CartStorage.php
- app/models/Order.php
- app/models/PaymentMethod.php
- app/models/DeliveryCity.php
- app/models/DeliveryZone.php
- app/views/cart/cart.php
- app/views/public/checkout.php
- public/assets/cart.js
- public/assets/checkout.js

Riscos:
- Alto: acoplamento entre scripts client-side e regras backend de totalizacao.
- Alto: regressao pode causar divergencia de valor final ou validacao inconsistente.

---

## 5. Regras de Negocio Criticas

## 5.1 Prioridade de precificacao

Ordem funcional observada (resumo):
1. Preco base do produto/item.
2. Ajustes de personalizacao (delta por item, qty, defaults).
3. Promocoes/preco promocional quando ativo.
4. Taxas adicionais (incluindo taxa embutida quando aplicavel).
5. Descontos (fidelidade/cupom conforme regras habilitadas).
6. Frete por cidade/zona/politica vigente.
7. Total final para pagamento.

Evidencias:
- app/models/Product.php
- app/controllers/PublicCartController.php
- app/controllers/AdminDeliveryFeeController.php
- public/assets/checkout.js

Risco:
- Alto: qualquer mudanca fora dessa precedencia altera margem e UX.

## 5.2 Customizacao por tipos

Tipos detectados no runtime:
- single: selecao unica.
- addon: adicao opcional.
- pool: selecao com limite agregado.
- qty: controle de quantidade por item.

Regras principais:
- min/max por grupo e por item.
- itens default podem ser removiveis ou nao conforme regra.
- hide_duplicates pode impactar visibilidade e validacao.

Evidencias:
- app/models/ProductCustomization.php
- public/assets/customization.js
- public/assets/product.js

Risco:
- Medio: validacoes de borda em min/max e defaults.

## 5.3 Combos

Comportamentos relevantes:
- Grupo de itens do combo com customizacao por unidade.
- Necessidade de rastrear parent_item_id/product_id para consistencia.
- Hidratar/serializar corretamente para exibicao e pedido.

Evidencias:
- app/controllers/PublicProductController.php
- app/controllers/PublicCartController.php
- app/services/CartStorage.php

Risco:
- Alto: erro de associacao de unidade quebra composicao e preco.

---

## 6. Estado e Persistencia

## 6.1 Sessao e seguranca de sessao

Mecanismos:
- Fingerprint e renovacao de sessao.
- Timeout/inatividade.
- Endurecimento de cookie e politicas anti-sequestro.

Evidencias:
- app/core/SessionManager.php
- public/index.php

Risco:
- Medio: incompatibilidade de sessao entre dispositivos/navegadores pode afetar continuidade do carrinho.

## 6.2 Cart storage multicamada

Camadas:
- Redis (preferencial quando disponivel).
- Sessao PHP.
- Persistencia em banco (cart_sessions).

Apoios:
- Cookie mm_cart_sid para recuperar estado quando session_id muda.
- Chaves contextuais para itens customizados por parent:product:unit.

Evidencias:
- app/services/CartStorage.php
- app/controllers/PublicCartController.php

Risco:
- Alto: inconsistencia entre camadas pode gerar perda/duplicacao de itens.

## 6.3 Banco de dados

Caracteristicas:
- MySQL como fonte de verdade transacional.
- Migrations incrementais em database/migrations.
- Fallbacks de schema em pontos especificos do dominio de pedidos.

Evidencias:
- app/core/Database.php
- app/models/Order.php
- database/multi_menu.sql
- database/migrations/*

Risco:
- Medio: fallback de schema aumenta resiliencia, mas eleva complexidade e custo de manutencao.

---

## 7. Frontend Publico e UX Tecnico

## 7.1 Renderizacao e assets

Modelo:
- HTML server-rendered com dados injetados para JS.
- CSS/JS por pagina com arquivos especializados.
- Build/minificacao orientada por package.json.

Evidencias:
- app/views/public/layout.php
- app/views/home/home.php
- app/views/product/product.php
- app/views/cart/cart.php
- app/views/public/checkout.php
- public/assets/*.css
- public/assets/*.js
- package.json

Risco:
- Medio: acoplamento view + dados injetados + JS requer disciplina de contrato.

## 7.2 Mobile e PWA

Capacidades:
- Manifest e service workers para experiencia instalavel.
- Ajustes de safe-area e iOS.
- Admin mobile com layout dedicado e bottom nav.

Evidencias:
- public/manifest.webmanifest
- public/mobile-manifest.webmanifest
- public/sw.js
- public/mobile-sw.js
- app/views/admin/mobile/layout.php

Risco:
- Medio: divergencia de comportamento entre navegadores mobile e desktop.

---

## 8. Admin Desktop e Admin Mobile

## 8.1 Admin desktop

Capacidades:
- Sidebar por rota/naming + fallback resiliente.
- Tema por empresa (cores dinamicas).
- Notificacoes em tempo real (KDS/chime/notifications).

Evidencias:
- app/views/admin/layout.php
- app/services/SidebarService.php
- public/assets/js/order-notifications.min.js

Risco:
- Medio: dependencia de naming de rota para menu ativo e permissao contextual.

## 8.2 Admin mobile

Capacidades:
- Layout otimizado para toque e tela reduzida.
- Bottom navigation e fluxo rapido operacional.

Evidencias:
- app/views/admin/mobile/layout.php
- public/assets/css/mobile.css
- public/assets/js/mobile.js

Risco:
- Baixo/Medio: riscos de navegacao e acao acidental em telas pequenas.

---

## 9. API Externa

## 9.1 Seguranca da API

Controles:
- ApiSecurity no construtor do controlador.
- Suporte a bearer/api key.
- CORS, rate limit e logging.

Evidencias:
- app/controllers/ApiController.php
- app/middleware/ApiSecurity.php

Risco:
- Medio: configuracao fraca de secrets/chaves compromete superficie externa.

## 9.2 Endpoints principais

Detectados:
- Empresa, categorias, produtos, produto por id.
- Pedidos (lista/detalhe).
- Criacao de pedido por JSON.

Evidencias:
- app/controllers/ApiController.php
- routes/web.php

Risco:
- Medio: validaoes de payload e ownership de dados devem permanecer estritas.

---

## 10. Integracoes Externas

## 10.1 iFood

Arquitetura:
- OAuth client_credentials para token.
- Polling de eventos e acknowledge.
- Webhook alternativo com roteamento por merchantId.
- Conversao de pedido iFood para pedido local.

Evidencias:
- app/services/IFoodService.php
- app/controllers/WebhookIFoodController.php
- scripts/ifood_polling_cron.php
- docs/IFOOD-INTEGRATION.md

Riscos:
- Alto: perda/duplicidade de processamento se idempotencia falhar.
- Medio: token refresh e erros transitorios de API externa.

## 10.2 Evolution / WhatsApp

Arquitetura:
- Entrada webhook e automacoes por evento.
- Envio resiliente com retries e fallback.
- Worker de fila e cron de reprocessamento.

Evidencias:
- app/controllers/WebhookEvolutionController.php
- app/services/WhatsAppSendService.php
- scripts/webhook_queue_worker.php
- scripts/whatsapp_retry_cron.php
- docs/EVOLUTION-API.md

Riscos:
- Alto: backlog de fila e retry inadequado impactam comunicacao com cliente.
- Medio: variacoes de formato de numero e payload do provedor.

## 10.3 Web Push

Capacidades:
- Notificacoes para contexto admin/PWA.
- Chaves VAPID geradas por script.

Evidencias:
- app/services/WebPushService.php
- scripts/generate-vapid-keys.php

Risco:
- Baixo/Medio: expiracao/invalidez de inscricoes de push.

---

## 11. Infraestrutura e Deploy

## 11.1 Ambientes

Topologias:
- Docker Compose para execucao local e cenarios operacionais simples.
- Docker Swarm + Traefik para orquestracao e roteamento por host.

Evidencias:
- docker-compose.yml
- docker-compose.traefik.yml
- docker-stack.yml

## 11.2 Deploy e operacao

Pontos operacionais:
- Scripts de deploy/sync e atualizacao de stack.
- Dependencia de healthchecks e ordem de inicializacao.

Evidencias:
- deploy.sh
- deploy-stack.sh
- scripts/run_webhook_worker.sh
- scripts/run_ifood_polling.sh

Riscos:
- Alto: erro de sequencing em deploy pode degradar webhooks/workers sem falha aparente imediata.
- Medio: drift de configuracao entre compose e swarm.

---

## 12. Observabilidade, Logs e Diagnostico

Praticas detectadas:
- Logs de erro em webhooks e fluxos sensiveis.
- Presenca de logs DEBUG/fallback em areas criticas (carrinho/produto/checkout).
- Relatorios de regressao e scripts de validacao no repositorio.

Evidencias:
- app/controllers/PublicCartController.php
- app/controllers/WebhookIFoodController.php
- REGRESSION-REPORT.md
- validate-fixes.sh

Risco:
- Medio: ruido de logs pode ocultar sinais operacionais realmente criticos.

---

## 13. Matriz de Risco (Resumo Executivo)

| Dominio | Risco | Severidade | Impacto |
|---|---|---|---|
| Checkout e totalizacao | Divergencia frontend/backend em totais e validacoes | Alto | Cobranca incorreta, abandono, suporte elevado |
| Carrinho multicamada | Inconsistencia Redis/sessao/DB | Alto | Perda/duplicacao de itens |
| Combos/customizacao | Quebra de associacao por unidade/parent item | Alto | Pedido inconsistente e erro de preco |
| iFood eventos | Falha de idempotencia/ack | Alto | Pedido duplicado ou perdido |
| WhatsApp fila/retry | Backlog e falhas silenciosas | Alto | Queda de engajamento/confirmacao |
| Naming de rotas admin | Divergencia afeta sidebar/permissao | Medio | UX admin degradada e erros de navegacao |
| Sessao/fingerprint | Mudanca de contexto encerra fluxo | Medio | Friccao no checkout/carrinho |
| PWA mobile | Diferencas iOS/Android/browser | Medio | Comportamento inconsistente |
| Logs debug extensivos | Baixa relacao sinal/ruido | Medio | Dificulta RCA e incident response |

---

## 14. Fluxos que Nao Podem Quebrar

1. Home -> Produto -> Add carrinho -> Checkout -> Pedido criado com total correto.
2. Produto customizado (incluindo combo) com min/max/default valido ate persistencia do item.
3. Aplicacao/remocao de cupom com recalculo coerente no backend e frontend.
4. Calculo de frete por cidade/zona refletido no total final e no resumo do pedido.
5. Persistencia e recuperacao do carrinho apos mudanca de sessao (quando elegivel).
6. Recebimento de evento iFood PLACED e criacao do pedido local sem duplicacao.
7. Webhook Evolution + fila + retry para mensagens criticas sem perda silenciosa.
8. Admin recebendo notificacao de novos pedidos (KDS/notificacao) com navegacao correta.

---

## 15. Invariantes Operacionais

1. Nenhum pedido deve ser confirmado com total divergente entre backend persistido e resumo exibido.
2. Todo item de combo deve manter vinculo univoco com seu grupo/unidade de origem.
3. Evento externo processado deve ser idempotente por identificador do provedor.
4. Regras de disponibilidade (aberto/pausado/horario) nao podem ser ignoradas no backend.
5. CSRF e rate limit devem permanecer ativos para superficies aplicaveis.

---

## 16. Divida Tecnica Priorizada

## Prioridade Alta

1. Reduzir acoplamento de regras de totalizacao entre JS de checkout e backend.
2. Revisar e padronizar estrategia de estado do carrinho em multicamada.
3. Formalizar contrato de customizacao/combo com validacao unica de dominio.
4. Fortalecer trilha de idempotencia em webhooks/polling e retries.

## Prioridade Media

1. Reduzir logs debug em producao e padronizar niveis estruturados.
2. Unificar convencoes de route naming e cobertura de testes de navegacao admin.
3. Consolidar regras duplicadas de validacao de endereco/frete.

## Prioridade Baixa

1. Revisar consistencia de UX entre admin desktop e mobile para acoes criticas.
2. Melhorar runbooks operacionais com trilhas de rollback e health gates.

---

## 17. Estrategia de Teste de Regressao (Minimo Obrigatorio)

1. Jornada publica completa (desktop/mobile): home, produto simples, produto combo, carrinho, checkout, pedido.
2. Casos de customizacao de borda: min/max, defaults, pool e qty.
3. Cupom + fidelidade + frete em combinacoes validas e invalidas.
4. Recuperacao de carrinho apos expiracao/rotacao de sessao.
5. iFood: simulacao de eventos repetidos e fora de ordem (idempotencia).
6. Evolution: fila com falhas transitorias e confirmacao de retry.
7. Admin: notificacoes de novos pedidos e acesso a detalhes.

Artefatos existentes de apoio:
- playwright.config.js
- test-results/
- REGRESSION-REPORT.md
- validate-fixes.sh

---

## 18. Referencias por Dominio

Arquitetura base:
- public/index.php
- app/bootstrap.php
- routes/web.php
- app/core/Router.php

Dominio publico:
- app/controllers/PublicHomeController.php
- app/controllers/PublicProductController.php
- app/controllers/PublicCartController.php
- app/models/Product.php
- app/models/ProductCustomization.php
- app/services/CartStorage.php

Checkout/frete/pedido:
- app/models/Order.php
- app/controllers/AdminDeliveryFeeController.php
- app/models/DeliveryCity.php
- app/models/DeliveryZone.php
- public/assets/checkout.js

Admin:
- app/views/admin/layout.php
- app/views/admin/mobile/layout.php
- app/services/SidebarService.php

API e integracoes:
- app/controllers/ApiController.php
- app/services/IFoodService.php
- app/controllers/WebhookIFoodController.php
- app/controllers/WebhookEvolutionController.php
- app/services/WhatsAppSendService.php

Infra e operacao:
- docker-compose.yml
- docker-compose.traefik.yml
- docker-stack.yml
- deploy-stack.sh
- scripts/*.php

---

## 19. Limitacoes e Premissas Declaradas

1. Este documento cobre o runtime ativo identificado no repositorio atual.
2. Documentos antigos podem divergir da implementacao corrente; prevalece evidencia de codigo.
3. Areas marcadas como risco alto exigem validacao automatizada antes de mudancas estruturais.

---

## 20. Proximos Incrementos Recomendados

1. Adicionar anexos de sequencia (diagramas de fluxo) por dominio critico:
- Checkout totalizacao
- iFood eventos
- Evolution fila/retry

2. Criar matriz de rastreabilidade de testes:
- fluxo critico -> teste automatizado/manual -> status

3. Criar runbook de mudanca segura pre-deploy:
- checklist tecnico + rollback + verificacao de webhooks/workers

---

Fim do documento.
