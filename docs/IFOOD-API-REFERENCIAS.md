# Referência Completa da API iFood

> Documentação baseada em: https://developer.ifood.com.br/pt-BR/docs/references  
> Todas as APIs (exceto Authentication) exigem autenticação via token OAuth.

---

## Índice

1. [Authentication (Autenticação)](#1-authentication-autenticação)
2. [Merchant (Estabelecimento)](#2-merchant-estabelecimento)
3. [Events (Eventos)](#3-events-eventos)
4. [Order (Pedidos)](#4-order-pedidos)
5. [Logistics (Logística)](#5-logistics-logística)
6. [Shipping (Envio / Entrega Externa)](#6-shipping-envio--entrega-externa)
7. [Catalog (Catálogo)](#7-catalog-catálogo)
8. [Financial (Financeiro)](#8-financial-financeiro)
9. [Review (Avaliações)](#9-review-avaliações)
10. [Picking (Separação de Pedidos)](#10-picking-separação-de-pedidos)
11. [Item (Integração de Produtos Mercado)](#11-item-integração-de-produtos-mercado)

---

## 1. Authentication (Autenticação)

**Versão:** v1.0  
**Descrição:** API responsável por gerar e gerenciar tokens de acesso OAuth 2.0. É o primeiro passo para usar qualquer outra API do iFood. Implementa um fluxo de autorização em duas etapas: primeiro solicita um código de usuário, depois troca esse código por um token de acesso.

### 1.1 OAuth

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `POST` | `/oauth/userCode` | **Requests a user code** | Solicita um código de usuário (user code). Esse código é exibido ao lojista para que ele autorize a aplicação no painel do iFood. Você envia seu `clientId` e recebe um código temporário + uma URL de verificação. O lojista acessa essa URL e insere o código para autorizar. |
| `POST` | `/oauth/token` | **Requests an access token** | Troca credenciais por um token de acesso (access token). Pode ser usado de duas formas: (1) com `grantType=authorization_code` + o `authorizationCode` + `authorizationCodeVerifier` obtidos após o lojista autorizar, ou (2) com `grantType=refresh_token` + `refreshToken` para renovar um token expirado. O token retornado é do tipo Bearer e deve ser enviado no header `Authorization` de todas as chamadas subsequentes. |

**Fluxo resumido:**
1. Sua aplicação chama `POST /oauth/userCode` com `clientId` → recebe `userCode` + `verificationUrl`
2. O lojista entra na URL e insere o código para autorizar
3. Sua aplicação chama `POST /oauth/token` com `grantType=authorization_code` → recebe `accessToken` + `refreshToken`
4. Use o `accessToken` como Bearer token em todas as APIs
5. Quando expirar, use `POST /oauth/token` com `grantType=refresh_token` para renovar

---

## 2. Merchant (Estabelecimento)

**Versão:** v1.0  
**Descrição:** API para interagir com os estabelecimentos (lojas/restaurantes) cadastrados na plataforma. Permite consultar dados do merchant, verificar status de operação, gerenciar interrupções temporárias (pausas), configurar horários de funcionamento e gerar QR codes para check-in.

### 2.1 Merchant (Dados do Estabelecimento)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `GET` | `/merchants` | **List merchants** | Lista todos os estabelecimentos vinculados às suas credenciais de API. Retorna um array com os IDs e dados básicos de cada loja autorizada. É o primeiro endpoint a chamar para descobrir quais merchants você pode gerenciar. |
| `GET` | `/merchants/{merchantId}` | **Get merchant details** | Retorna os detalhes completos de um estabelecimento específico: nome, endereço, tipo de cozinha, configurações, etc. O `merchantId` é o UUID do estabelecimento obtido na listagem. |

### 2.2 Status (Estado de Operação)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `GET` | `/merchants/{merchantId}/status` | **Get merchant status** | Retorna o status operacional atual do estabelecimento — se está aberto, fechado, ou com alguma restrição. Inclui informações sobre todas as operações disponíveis (delivery, takeout, etc.). |
| `GET` | `/merchants/{merchantId}/status/{operation}` | **Get merchant status by operation** | Retorna o status de uma operação específica do estabelecimento. O parâmetro `operation` pode ser, por exemplo, `DELIVERY` ou `TAKEOUT`. Útil para verificar se apenas um canal está ativo. |

### 2.3 Interruption (Interrupções / Pausas)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `GET` | `/merchants/{merchantId}/interruptions` | **List merchant's interruptions** | Lista todas as interrupções ativas e programadas do estabelecimento. Interrupções são pausas temporárias na operação (ex: loja sem insumos, problema técnico). |
| `POST` | `/merchants/{merchantId}/interruptions` | **Create an interruption** | Cria uma nova interrupção (pausa) para o estabelecimento. Você define o início, fim e motivo da pausa. Durante a interrupção, o merchant fica invisível ou indisponível para novos pedidos no iFood. |
| `DELETE` | `/merchants/{merchantId}/interruptions/{interruptionId}` | **Delete an interruption** | Remove/cancela uma interrupção existente, fazendo o estabelecimento voltar a operar normalmente antes do prazo previsto. |

### 2.4 OpeningHours (Horários de Funcionamento)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `GET` | `/merchants/{merchantId}/opening-hours` | **Get opening hours** | Retorna os horários de funcionamento configurados para o estabelecimento, organizados por dia da semana. Cada dia pode ter múltiplos intervalos (ex: 11:00-15:00 e 18:00-23:00). |
| `PUT` | `/merchants/{merchantId}/opening-hours` | **Create an opening hours** | Define/atualiza os horários de funcionamento do estabelecimento. Substitui toda a configuração de horários existente pela nova informação enviada. |

### 2.5 CheckIn (QR Code de Check-in)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `POST` | `/merchants/checkin-qrcode` | **Generate check-in QR code PDF file** | Gera um arquivo PDF contendo o QR code de check-in do estabelecimento. Esse QR code é usado pelo entregador para confirmar que chegou ao local de retirada do pedido. |

---

## 3. Events (Eventos)

**Versão:** v1.0  
**Descrição:** API de eventos para receber notificações sobre mudanças de estado dos pedidos e outras ocorrências na plataforma. Funciona no modelo de **polling** (sua aplicação consulta periodicamente para buscar novos eventos) ao invés de webhooks.

### 3.1 Events (Eventos de Pedidos)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `GET` | `/events:polling` | **Get New Events** | Busca novos eventos não processados. Retorna uma lista de eventos pendentes (ex: novo pedido, pedido confirmado, pedido cancelado, etc.). Sua aplicação deve chamar este endpoint periodicamente (polling) para receber atualizações em tempo real. Cada evento contém um `id`, `code` (tipo do evento), `orderId` e metadados. |
| `POST` | `/events/acknowledgment` | **Acknowledge Events** | Confirma o recebimento de eventos. Após processar os eventos retornados pelo polling, você envia os IDs dos eventos para este endpoint como "acknowledgment" (confirmação). Isso impede que os mesmos eventos sejam retornados nas próximas chamadas de polling. **Se não confirmar, os eventos continuarão aparecendo.** |

**Tipos comuns de eventos:**
- `PLC` — Pedido colocado (novo pedido)
- `CFM` — Pedido confirmado
- `CAN` — Pedido cancelado
- `DSP` — Pedido despachado
- `CON` — Pedido concluído
- E outros códigos para cada mudança de estado

---

## 4. Order (Pedidos)

**Versão:** v1.0  
**Descrição:** API principal para gerenciar pedidos. Dividida em cinco partes: Details (detalhes do pedido), Actions (ações como confirmar/cancelar), Delivery (rastreamento), Handshake Platform (disputas/cancelamento) e Code Validation (verificação de códigos).

### 4.1 Details (Detalhes do Pedido)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `GET` | `/orders/{id}` | **Get Order Details** | Retorna todos os detalhes de um pedido específico: itens pedidos (com quantidades, opções, observações), informações de pagamento (método, valor, troco), dados de entrega (endereço, tipo), dados do cliente, timestamps, valores (subtotal, taxa de entrega, descontos, total), e status atual. É o endpoint mais completo para obter informações do pedido. |
| `GET` | `/orders/{id}/virtual-bag` | **Get Order Virtual Bag** | Retorna a "sacola virtual" do pedido — uma representação estruturada dos itens com detalhes de embalagem. Útil para operações de separação (picking) em supermercados e lojas. |

### 4.2 Actions (Ações sobre o Pedido)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `POST` | `/orders/{id}/confirm` | **Confirm an order** | Confirma o recebimento de um pedido. Esta é a primeira ação obrigatória após receber um novo pedido via eventos. O pedido passa do status "pendente" para "confirmado". Se não confirmar dentro do prazo, o pedido é cancelado automaticamente. |
| `POST` | `/orders/{id}/startPreparation` | **Start Preparation** | Informa que o preparo do pedido foi iniciado. Atualiza o status para "em preparo". Esta etapa é importante para o rastreamento do cliente. |
| `POST` | `/orders/{id}/readyToPickup` | **Ready to Pickup** | Informa que o pedido está pronto para retirada pelo entregador. Atualiza o status para "pronto para coleta". O entregador é notificado de que pode ir ao estabelecimento. |
| `POST` | `/orders/{id}/dispatch` | **Dispatch an order** | Informa que o pedido foi despachado (saiu para entrega). Atualiza o status para "em rota de entrega". A partir daqui o cliente pode acompanhar o entregador no mapa. |
| `GET` | `/orders/{id}/cancellationReasons` | **Get available cancellation codes** | Retorna a lista de motivos de cancelamento disponíveis para o pedido, com seus respectivos códigos. Cada pedido pode ter motivos diferentes dependendo do status atual e do tipo de operação. |
| `POST` | `/orders/{id}/requestCancellation` | **Request to cancel** | Solicita o cancelamento de um pedido. Envia o código do motivo de cancelamento (obtido no endpoint anterior). Dependendo do status do pedido e do motivo, o cancelamento pode ser imediato ou entrar em um processo de mediação (Handshake). |

### 4.3 Delivery (Entrega / Rastreamento)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `GET` | `/orders/{id}/tracking` | **Track the order** | Retorna informações de rastreamento da entrega em tempo real: posição do entregador (latitude/longitude), ETA (tempo estimado), status da entrega, dados do entregador (nome, veículo). Permite exibir o mapa de rastreamento para o cliente. |

### 4.4 Handshake Platform (Plataforma de Disputas)

Mecanismo de negociação para cancelamentos. Quando um cancelamento é solicitado, antes de efetivar, pode abrir uma disputa (handshake) onde as partes negociam.

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `POST` | `/disputes/{disputeId}/accept` | **Request to accept a dispute** | Aceita uma disputa de cancelamento. O lojista concorda com o cancelamento proposto, e o pedido é efetivamente cancelado conforme os termos acordados. |
| `POST` | `/disputes/{disputeId}/reject` | **Reject a Handshake Dispute** | Rejeita uma disputa de cancelamento. O lojista discorda do cancelamento proposto. A disputa segue para mediação do iFood ou é encerrada. |
| `POST` | `/disputes/{disputeId}/alternatives/{alternativeId}` | **Request to send a proposal** | Envia uma contraproposta na disputa. Em vez de aceitar ou rejeitar, o lojista propõe uma alternativa (ex: reembolso parcial, substituição de item). O `alternativeId` identifica qual opção de alternativa está sendo proposta. |

### 4.5 Code Validation (Validação de Códigos)

Mecanismo de segurança para confirmar retirada e entrega por meio de códigos numéricos.

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `POST` | `/orders/{id}/validatePickupCode` | **Send pickup verification code** | Valida o código de retirada (pickup code). Quando o entregador chega ao estabelecimento, ele informa um código. O estabelecimento envia esse código para validação antes de entregar o pacote ao entregador. |
| `POST` | `/orders/{id}/verifyDeliveryCode` | **Send delivery verification code** | Valida o código de entrega (delivery code). Quando o entregador chega ao cliente, o cliente informa um código. Esse código é enviado para verificação, confirmando que a entrega foi feita à pessoa correta. |

---

## 5. Logistics (Logística)

**Versão:** v1.0  
**Descrição:** API para gerenciar a logística de entregas quando o estabelecimento usa **frota própria** (entregadores próprios) ao invés da logística do iFood. Permite controlar todo o ciclo de entrega: atribuir entregador, notificar chegadas e confirmar entrega.

### 5.1 Details (Detalhes do Pedido Logístico)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `GET` | `/orders/{id}` | **Get Logistics Order Details** | Retorna os detalhes logísticos de um pedido: informações de endereço de origem e destino, dados do entregador atribuído, status da entrega, etc. Semelhante ao Order Details mas focado nas informações relevantes para logística. |

### 5.2 Actions (Ações Logísticas)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `POST` | `/orders/{id}/assignDriver` | **Assign Driver** | Atribui um entregador ao pedido. Envia os dados do entregador (nome, veículo, telefone) para que o iFood atualize o rastreamento. Obrigatório como primeiro passo da logística própria. |
| `POST` | `/orders/{id}/goingToOrigin` | **Going to Origin** | Informa que o entregador está a caminho do estabelecimento para retirar o pedido. Atualiza o status de rastreamento. |
| `POST` | `/orders/{id}/arrivedAtOrigin` | **Arrived at Origin** | Informa que o entregador chegou ao estabelecimento. O status muda para "entregador no local de retirada". |
| `POST` | `/orders/{id}/dispatch` | **Dispatched** | Informa que o entregador saiu do estabelecimento com o pedido e está a caminho do cliente. Equivalente ao dispatch da Order API. |
| `POST` | `/orders/{id}/arrivedAtDestination` | **Arrived at Destination** | Informa que o entregador chegou ao endereço de entrega (destino). O cliente é notificado de que o entregador está no local. |

### 5.3 Code Validation (Validação de Código de Entrega)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `POST` | `/orders/{id}/verifyDeliveryCode` | **Send delivery verification code** | Envia o código de verificação de entrega fornecido pelo cliente para confirmar que a entrega foi concluída com segurança. |

---

## 6. Shipping (Envio / Entrega Externa)

**Versão:** v1.0  
**Descrição:** API que permite solicitar entregadores do iFood para pedidos que **não vieram do iFood** — ou seja, pedidos feitos por telefone, WhatsApp, site próprio ou outro canal. Também gerencia endereços de entrega, cancelamentos e rastreamento para esses pedidos.

### 6.1 Delivery Availabilities (Disponibilidade de Entrega)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `GET` | `/merchants/{merchantId}/deliveryAvailabilities` | **Delivery Availabilities** | Consulta se há entregadores disponíveis na região do estabelecimento, com estimativas de tempo e custo. Use antes de solicitar um entregador para saber se o serviço está disponível. Retorna modalidades disponíveis, preço estimado e ETA. |
| `GET` | `/orders/{orderId}/deliveryAvailabilities` | **Delivery Availabilities for an existing order** | Consulta a disponibilidade de entrega para um pedido do iFood já existente. Útil quando o merchant quer solicitar um entregador iFood para um pedido que originalmente seria com entrega própria. |

### 6.2 Request Driver (Solicitar Entregador)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `POST` | `/merchants/{merchantId}/orders` | **Request a driver for an external order** | Solicita um entregador do iFood para um pedido **externo** (não feito pelo iFood). Você envia os dados do pedido (itens, valor, endereço de entrega) e o iFood aloca um entregador da sua rede de parceiros. |
| `POST` | `/orders/{orderId}/requestDriver` | **Request a driver for an iFood order** | Solicita um entregador do iFood para um pedido que já existe na plataforma iFood. Usado quando o merchant originalmente tinha entrega própria mas quer usar a logística do iFood. |

### 6.3 Delivery Address (Endereço de Entrega)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `POST` | `/orders/{orderId}/acceptDeliveryAddressChange` | **Accept Delivery Address Change** | Aceita uma solicitação de mudança de endereço de entrega feita pelo cliente após o pedido já ter sido confirmado. |
| `POST` | `/orders/{orderId}/deliveryAddressChangeRequest` | **Request Delivery Address Change** | Solicita uma mudança no endereço de entrega. Pode ser feita pelo merchant quando identifica que o endereço está incorreto ou precisa de ajuste. |
| `POST` | `/orders/{orderId}/denyDeliveryAddressChange` | **Deny Delivery Address Change** | Nega uma solicitação de mudança de endereço de entrega. Usado quando a alteração não é viável (ex: novo endereço fora da área de cobertura). |
| `POST` | `/orders/{orderId}/userConfirmAddress` | **Confirm User Address** | Confirma o endereço do usuário. Usado quando há uma pendência de confirmação de endereço pelo cliente. |

### 6.4 Cancellation (Cancelamento)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `GET` | `/orders/{orderId}/cancellationReasons` | **Get available cancellation codes** | Retorna os motivos de cancelamento disponíveis para um pedido de shipping. Os códigos podem variar de acordo com o status atual do pedido. |
| `POST` | `/orders/{orderId}/cancel` | **Cancel order for request driver** | Cancela completamente um pedido externo (para o qual foi solicitado um entregador). O pedido e a solicitação de entregador são cancelados. |
| `POST` | `/orders/{orderId}/cancelRequestDriver` | **Cancel request driver** | Cancela apenas a solicitação de entregador, sem cancelar o pedido em si. Útil quando o merchant decide fazer a entrega por conta própria após já ter solicitado um entregador do iFood. |

### 6.5 Safe Delivery (Entrega Segura)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `GET` | `/orders/{orderId}/safeDelivery` | **Get Safe Delivery Score** | Retorna uma pontuação (score) de segurança para a entrega. Avalia fatores de risco do endereço/região de entrega. O merchant pode usar essa informação para tomar precauções adicionais. |

### 6.6 Delivery (Rastreamento)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `GET` | `/orders/{id}/tracking` | **Track the order** | Rastreia a entrega em tempo real — posição do entregador, ETA, status. Mesma funcionalidade do tracking da Order API. |

---

## 7. Catalog (Catálogo)

**Descrição:** API mais extensa da plataforma. Gerencia todo o catálogo de produtos do estabelecimento: catálogos, categorias, produtos, itens, grupos de opções (complementos), opções individuais, estoque, imagens e versionamento. É a API usada para manter o cardápio digital sincronizado com o iFood.

### 7.1 Catalog (Catálogos)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `GET` | `/merchants/{merchantId}/catalogs` | **List catalogs** | Lista todos os catálogos do estabelecimento. Um merchant pode ter mais de um catálogo (ex: catálogo de delivery e catálogo de takeout). Retorna IDs e metadados de cada catálogo. |
| `GET` | `/merchants/{merchantId}/catalogs/{catalogId}/unsellableItems` | **List unsellable items** | Lista itens que não podem ser vendidos no catálogo especificado. Itens podem ficar non-sellable por falta de estoque, preço inválido, informações incompletas, etc. Útil para diagnóstico. |
| `GET` | `/merchants/{merchantId}/catalogs/{groupId}/sellableItems` | **List sellable items** | Lista itens que estão disponíveis para venda em um grupo/catálogo. O contrário do endpoint anterior — mostra o que está ativo e vendável. |
| `GET` | `/merchants/{merchantId}/catalog/version` | **Check Version** | Verifica qual versão do catálogo o merchant está usando (v1 ou v2). Importante pois os endpoints e schemas mudam entre versões. |

### 7.2 Category (Categorias)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `GET` | `/merchants/{merchantId}/catalogs/{catalogId}/categories` | **List categories** | Lista todas as categorias de um catálogo. Categorias organizam os produtos (ex: "Lanches", "Bebidas", "Sobremesas"). Retorna nome, status, ordem de exibição. |
| `POST` | `/merchants/{merchantId}/catalogs/{catalogId}/categories` | **Create a category** | Cria uma nova categoria no catálogo. Envia nome, status (ativo/inativo) e sequência de ordenação. |
| `GET` | `/merchants/{merchantId}/catalogs/{catalogId}/categories/{categoryId}` | **Get a category** | Retorna os detalhes de uma categoria específica. |
| `PATCH` | `/merchants/{merchantId}/catalogs/{catalogId}/categories/{categoryId}` | **Edit a category** | Edita parcialmente uma categoria existente — pode alterar nome, status, ordem, etc. Usa PATCH para atualização parcial. |
| `GET` | `/merchants/{merchantId}/categories/{categoryId}/items` | **List items within that category** | Lista todos os itens (produtos) que pertencem a uma categoria específica. |
| `DELETE` | `/merchants/{merchantId}/categories/{categoryId}` | **Delete a category** | Remove uma categoria. Os produtos dentro dela precisam ser movidos ou removidos antes, ou serão desassociados. |

### 7.3 Product (Produtos)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `GET` | `/merchants/{merchantId}/products` | **List products** | Lista todos os produtos do estabelecimento. Retorna dados básicos: nome, descrição, preço, status, imagem, código externo. |
| `POST` | `/merchants/{merchantId}/products` | **Create a product** | Cria um novo produto. Envia nome, descrição, preço, imagem, EAN/SKU, etc. O produto precisa ser associado a uma categoria para ficar visível. |
| `PUT` | `/merchants/{merchantId}/products/{productId}` | **Edit a product** | Atualiza completamente um produto existente. Substitui todos os dados do produto pelos enviados (PUT = substituição total). |
| `DELETE` | `/merchants/{merchantId}/products/{productId}` | **Delete a product** | Remove um produto do catálogo permanentemente. |
| `PATCH` | `/merchants/{merchantId}/products/status` | **Batch update products' statuses** | Atualiza o status (ativo/inativo/pausado) de múltiplos produtos de uma vez. Útil para pausar vários itens rapidamente (ex: fim do estoque). |
| `PATCH` | `/merchants/{merchantId}/products/price` | **Batch update product's prices** | Atualiza o preço de múltiplos produtos de uma vez. Envie um array de objetos com `productId` e novo `price`. |
| `GET` | `/merchants/{merchantId}/products/externalCode/{externalCode}` | **List products by external code** | Busca produtos pelo código externo (código do PDV/ERP do lojista). Útil para integrar sistemas que usam seus próprios códigos de produto. |
| `GET` | `/merchants/{merchantId}/product/{productId}` | **Get a product by id** | Retorna os detalhes completos de um produto específico pelo seu ID no iFood. |

### 7.4 Item (Itens)

Itens representam a associação de um produto a uma categoria, com possíveis customizações adicionais.

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `DELETE` | `/merchants/{merchantId}/categories/{categoryId}/products/{productId}` | **Delete an item** | Remove a associação de um produto a uma categoria (remove o item, não o produto em si). |
| `GET` | `/merchants/{merchantId}/items/{itemId}/flat` | **Get item flat** | Retorna uma visão "flat" (plana) de um item — inclui o produto, seus grupos de opções e todas as opções, tudo em uma estrutura nivelada sem aninhamento profundo. Facilita a leitura e integração. |
| `PUT` | `/merchants/{merchantId}/items` | **Create or update an item** | Cria ou atualiza um item completo (produto + associação a categoria + grupos de opções). É o endpoint mais poderoso para gerenciar o catálogo de forma atômica. |
| `PATCH` | `/merchants/{merchantId}/items/price` | **Edit the price of an item** | Atualiza o preço de um item específico. |
| `PATCH` | `/merchants/{merchantId}/items/status` | **Edit the status of an item** | Atualiza o status de um item (ativo/inativo). |
| `PATCH` | `/merchants/{merchantId}/items/externalCode` | **Edit the external code of an item** | Atualiza o código externo de um item e suas extensões (complementos). Útil para manter a sincronia com sistemas PDV/ERP. |

### 7.5 Option Group (Grupos de Opções / Complementos)

Grupos de opções são os "grupos de personalização" de um produto (ex: "Escolha o ponto da carne", "Adicionais", "Bebida acompanhante"). Cada grupo contém múltiplas opções.

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `GET` | `/merchants/{merchantId}/optionGroups` | **List option groups** | Lista todos os grupos de opções do estabelecimento. Retorna nome, min/max de seleções, status e os IDs dos produtos associados. |
| `PATCH` | `/merchants/{merchantId}/optionGroups/{optionGroupId}` | **Update option group** | Atualiza um grupo de opções: nome, limites mín/máx de seleção, ordem, etc. |
| `DELETE` | `/merchants/{merchantId}/optionGroups/{optionGroupId}` | **Delete an option group** | Remove um grupo de opções permanentemente. |
| `DELETE` | `/merchants/{merchantId}/optionGroups/{optionGroupId}/products/{productId}` | **Disassociate option group from product** | Desvincula um grupo de opções de um produto específico, sem excluir o grupo. O grupo continua existindo e pode estar vinculado a outros produtos. |
| `PATCH` | `/merchants/{merchantId}/optionGroups/{optionGroupId}/status` | **Update an option group's status** | Atualiza o status (ativo/inativo) de um grupo de opções. |

### 7.6 Option (Opções Individuais)

Opções são os itens dentro dos grupos de opções (ex: dentro do grupo "Adicionais": Bacon, Queijo, Ovo).

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `POST` | `/merchants/{merchantId}/optionGroups/{optionGroupId}/options` | **Create an option** | Cria uma nova opção dentro de um grupo de opções. Envia nome, preço adicional, status, código externo, etc. |
| `DELETE` | `/merchants/{merchantId}/optionGroups/{optionGroupId}/products/{productId}/option` | **Delete an option** | Remove uma opção específica vinculada a um produto dentro de um grupo de opções. |
| `PATCH` | `/merchants/{merchantId}/options/price` | **Update an option's price** | Atualiza o preço de uma opção (o valor adicional cobrado por aquele complemento). |
| `PATCH` | `/merchants/{merchantId}/options/externalCode` | **Update an option's external code** | Atualiza o código externo de uma opção para manter sincronia com PDV/ERP. |
| `PATCH` | `/merchants/{merchantId}/options/status` | **Update an option's status** | Atualiza o status (ativo/inativo) de uma opção. |

### 7.7 Batch (Operações em Lote)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `GET` | `/merchants/{merchantId}/batch/{batchId}` | **List batch operation results** | Consulta o resultado de uma operação em lote (batch). Quando você faz updates em massa (ex: preços, status), a API pode retornar um `batchId`. Use este endpoint para verificar se a operação em lote foi concluída e se houve erros. |

### 7.8 Inventory (Estoque)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `POST` | `/merchants/{merchantId}/inventory` | **Creates or updates the stock** | Cria ou atualiza o estoque de um produto. Envia a quantidade disponível. Quando o estoque chega a zero, o produto fica automaticamente indisponível para venda. |
| `GET` | `/merchants/{merchantId}/inventory/{productId}` | **Gets the stock of a product** | Consulta o estoque atual de um produto específico. Retorna a quantidade disponível. |
| `POST` | `/merchants/{merchantId}/inventory/batchDelete` | **Deletes the stock for a list of products** | Remove o controle de estoque de múltiplos produtos. Após remoção, os produtos voltam a não ter limite de estoque (venda ilimitada). |

### 7.9 Image (Imagens)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `POST` | `/merchants/{merchantId}/image/upload` | **Upload image** | Faz upload de uma imagem para usar no catálogo (fotos de produtos, categorias). Retorna uma URL/referência que pode ser associada a um produto. Aceita formatos comuns (JPEG, PNG). |

### 7.10 Version (Versionamento do Catálogo)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `POST` | `/merchants/{merchantId}/version/upgrade` | **Upgrade catalog version to v2** | Migra o catálogo do estabelecimento da versão 1 para a versão 2. A v2 oferece mais funcionalidades e melhor estrutura de dados. A migração é irreversível na prática. |
| `POST` | `/merchants/{merchantId}/version/downgrade` | **Downgrade catalog version to v1** | Regride o catálogo da versão 2 para a versão 1. Pode haver perda de dados/funcionalidades exclusivas da v2. Usar com cautela. |

---

## 8. Financial (Financeiro)

**Descrição:** API para consultar informações financeiras: conciliação de pagamentos, liquidações, antecipações, vendas e eventos financeiros. Essencial para contabilidade e controle financeiro da operação no iFood.

### 8.1 Conciliation (Conciliação Financeira)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `GET` | `/merchants/{merchantId}/reconciliation` | **Get reconciliation by merchant** | Retorna dados de conciliação financeira do estabelecimento. Permite comparar os valores dos pedidos com os repasses do iFood, identificando divergências. Filtro por período. |
| `GET` | `/merchants/{merchantId}/settlements` | **Get Settlements by merchant** | Retorna os acertos/liquidações (settlements) do iFood para o merchant. Cada settlement representa um pagamento feito pelo iFood ao lojista em determinada data, mostrando o valor líquido após descontos (comissão, taxas, etc.). |
| `GET` | `/merchants/{merchantId}/anticipations` | **Get Anticipations by merchant** | Retorna as antecipações de pagamento — quando o lojista solicita receber antes do prazo padrão. Mostra valores antecipados, taxas de antecipação e datas. |
| `GET` | `/merchants/{merchantId}/sales` | **Get Sales by merchant** | Retorna dados de vendas do estabelecimento: lista de pedidos com valores brutos, subtotais, taxas, descontos e valor líquido. Filtro por período. |
| `GET` | `/merchants/{merchantId}/financial-events` | **Get Financial Events by merchant** | Retorna todos os eventos financeiros: repasses, estornos, cobranças, compensações, etc. Cada evento tem tipo, valor, data e referência ao pedido/settlement relacionado. É o nível mais granular de dados financeiros. |
| `POST` | `/merchants/{merchantId}/reconciliation/on-demand` | **Generate Reconciliation File On Demand** | Solicita a geração de um arquivo de conciliação sob demanda (CSV/Excel). A geração é assíncrona — retorna um `requestId` para consultar depois. |
| `GET` | `/merchants/{merchantId}/reconciliation/on-demand/{requestId}` | **Fetch a Reconciliation File By Request ID** | Busca o arquivo de conciliação gerado sob demanda. Use o `requestId` retornado pelo endpoint anterior. Se o arquivo já estiver pronto, retorna o link para download; se ainda estiver processando, retorna o status. |

---

## 9. Review (Avaliações)

**Descrição:** API para consultar avaliações de pedidos feitas por clientes e responder a elas. Também fornece resumos agregados (média de notas, total de avaliações).

### 9.1 Review (Avaliações Individuais)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `GET` | `/merchants/{merchantId}/reviews` | **List reviews** | Lista avaliações de pedidos do estabelecimento. Suporta filtros por período, nota, respondidas/não respondidas. Cada review contém: nota (1-5), comentário do cliente, data, dados do pedido. |
| `GET` | `/merchants/{merchantId}/reviews/{reviewId}` | **Get a review** | Retorna os detalhes completos de uma avaliação específica: nota, comentário, itens avaliados, data, e a resposta do lojista (se houver). |
| `POST` | `/merchants/{merchantId}/reviews/{reviewId}/answers` | **Post a review reply** | Envia uma resposta do lojista para uma avaliação. A resposta fica visível publicamente no iFood junto com a avaliação do cliente. Só é permitida uma resposta por avaliação. |

### 9.2 Summary (Resumo)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `GET` | `/merchants/{merchantId}/summary` | **Get a summary** | Retorna um resumo agregado das avaliações: nota média geral, total de avaliações, distribuição por estrelas (quantas avaliações de 1, 2, 3, 4, 5 estrelas), e outras métricas de satisfação. |

---

## 10. Picking (Separação de Pedidos)

**Versão:** v1.0  
**Descrição:** API para gerenciar o processo de separação (picking) de pedidos, usada principalmente por supermercados e lojas. Permite modificar itens do pedido durante a separação (substituir, adicionar, remover) e informar início/fim do processo.

### 10.1 Order Modifiers (Modificadores de Pedido)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `POST` | `/orders/{id}/items` | **Add Order Item** | Adiciona um novo item ao pedido durante a separação. Usado quando o separador encontra um produto adicional que o cliente pode querer, ou quando precisa incluir um substituto. |
| `POST` | `/orders/{id}/items/{uniqueId}/replace` | **Replace Order Item** | Substitui um item do pedido por outro. O `uniqueId` identifica o item original. Usado quando o produto pedido está indisponível e há um substituto adequado (ex: trocar marca X por marca Y). |
| `PATCH` | `/orders/{id}/items/{uniqueId}` | **Modify any Order Item** | Modifica parcialmente um item existente no pedido: alterar quantidade, peso, observação, etc. Útil quando o produto está disponível mas em quantidade diferente da solicitada. |
| `DELETE` | `/orders/{id}/items/{uniqueId}` | **Remove Order Item** | Remove um item do pedido durante a separação. Usado quando o produto está indisponível e não há substituto. O valor do pedido é ajustado automaticamente. |

### 10.2 Order Actions (Ações do Processo)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `POST` | `/orders/{id}/startSeparation` | **Start Separation** | Informa que o processo de separação (picking) do pedido foi iniciado. O separador começou a coletar os itens nas prateleiras. |
| `POST` | `/orders/{id}/endSeparation` | **End Separation** | Informa que o processo de separação foi concluído. Todos os itens foram coletados (ou marcados como indisponíveis/substituídos). O pedido está pronto para embalagem e entrega. |

---

## 11. Item (Integração de Produtos Mercado)

**Versão:** v1.0  
**Descrição:** API para integração de produtos de **mercados/groceries** (supermercados, farmácias, pet shops, etc). Diferente do Catalog que é para restaurantes, esta API é otimizada para grandes volumes de SKUs típicos de varejo. Permite ingestão em massa de produtos.

### 11.1 Item Integration (Ingestão de Produtos)

| Método | Endpoint | Nome | Descrição |
|--------|----------|------|-----------|
| `POST` | `/ingestion/{merchantId}?reset={resetCatalog}` | **Post Item Integration** | Envia um lote completo de produtos para integração. O parâmetro `reset` (booleano) indica se o catálogo existente deve ser limpo antes da ingestão: `true` apaga tudo e importa do zero; `false` faz merge (adiciona/atualiza sem remover os existentes). Ideal para a carga inicial ou sincronizações completas. |
| `PATCH` | `/ingestion/{merchantId}` | **Patch Item Integration** | Atualiza parcialmente os produtos integrados. Diferente do POST, este endpoint não substitui todo o catálogo — apenas atualiza os itens enviados. Ideal para atualizações incrementais de preço, estoque ou dados de produtos específicos. |

---

## Resumo de Servers (Base URLs)

Cada seção de API usa URLs base (servers) diferentes. Os servers exatos são configurados na documentação interativa do iFood, mas o padrão geral é:

| API | Base URL |
|-----|----------|
| Authentication | `https://merchant-api.ifood.com.br` |
| Merchant | `https://merchant-api.ifood.com.br` |
| Events | `https://merchant-api.ifood.com.br` |
| Order | `https://merchant-api.ifood.com.br` |
| Logistics | `https://merchant-api.ifood.com.br` |
| Shipping | `https://merchant-api.ifood.com.br` |
| Catalog | `https://merchant-api.ifood.com.br` |
| Financial | `https://merchant-api.ifood.com.br` |
| Review | `https://merchant-api.ifood.com.br` |
| Picking | `https://merchant-api.ifood.com.br` |
| Item | `https://merchant-api.ifood.com.br` |

---

## Contagem Total de Endpoints

| API | Quantidade |
|-----|-----------|
| Authentication | 2 |
| Merchant | 8 |
| Events | 2 |
| Order | 12 |
| Logistics | 7 |
| Shipping | 11 |
| Catalog | 26 |
| Financial | 7 |
| Review | 4 |
| Picking | 6 |
| Item | 2 |
| **Total** | **87 endpoints** |
