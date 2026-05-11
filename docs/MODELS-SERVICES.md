# Models & Services — Referência

## Models (24)

### Company
**Tabela:** `companies`
Tenant principal do sistema. Cada empresa tem `slug` único, configurações de cores, horários, taxas de entrega, métodos de pagamento.

**Métodos principais:** `findBySlug()`, `findById()`, `getSettings()`, `updateSettings()`

### Product
**Tabela:** `products`
Produtos simples ou combos. Campo `type` = `simple` | `combo`.

**Métodos principais:** `find()`, `getByCategory()`, `getComboGroupsWithItems()`, `search()`, `toggleActive()`

### Category
**Tabela:** `categories`
Categorias de produtos com ordenação e ativação.

### Order
**Tabela:** `orders`
Pedidos com status (pending → confirmed → preparing → ready → delivering → delivered / cancelled).

Campos JSON: `combo_data`, `customization_data`, `items` (serializado).

**Métodos principais:** `create()`, `updateStatus()`, `findByCompany()`, `findByCustomer()`

### Customer
**Tabela:** `customers`
Clientes identificados por telefone. Vinculados por `company_id`.

**Métodos principais:** `findByPhone()`, `findOrCreate()`, `getAddresses()`

### User
**Tabela:** `users`
Usuários admin (donos de empresa).

### PaymentMethod
**Tabela:** `payment_methods`
Métodos de pagamento configuráveis por empresa (Pix, Dinheiro, Cartão, etc).

### Ingredient
**Tabela:** `ingredients`
Ingredientes globais da empresa. Usados em personalização. Campo `active` controla visibilidade.

**Campo importante:** `sale_price` — preço de venda usado no modo pool.

### DeliveryCity
**Tabela:** `delivery_cities`
Cidades habilitadas para entrega.

### DeliveryZone
**Tabela:** `delivery_zones`
Bairros/zonas com taxa de entrega individual.

### CustomerAddress
**Tabela:** `customer_addresses`
Endereços dos clientes com campos: street, number, complement, neighborhood, city, reference.

### ProductCustomization
**Tabelas:** `product_custom_groups` + `product_custom_items`
Personalização de produtos. Documentação detalhada em [PERSONALIZACAO.md](PERSONALIZACAO.md).

**Métodos principais:**
- `sanitizePayload()` — Normaliza dados do form admin
- `save()` — Persiste em transação (delete + insert)
- `loadForPublic()` — Carrega com transformações de tipo (pool, single, extra)
- `loadForAdmin()` — Carrega dados brutos
- `hasInactiveDefaultIngredient()` — Verifica se produto deve ser oculto
- `productIdsHiddenByIngredient()` — Lista de produtos ocultos por ingrediente inativo

### CustomizationTemplate
**Tabela:** `customization_templates`
Templates reutilizáveis de grupos de personalização. Podem ser copiados para produtos.

### CrossSellGroup
**Tabela:** `cross_sell_groups`
Grupos de venda cruzada (recomendações manuais).

### EvolutionInstance
**Tabela:** `evolution_instances`
Instâncias WhatsApp via Evolution API. Cada empresa pode ter múltiplas instâncias.

### LoyaltyProgram
**Tabela:** `loyalty_programs`
Programa de fidelidade progressiva com tiers.

### Expense / ExpenseCategory
**Tabelas:** `expenses`, `expense_categories`
Controle financeiro de despesas.

### FinancialReport
Model sem tabela própria. Gera relatórios agregando orders, expenses, costs.

### FinancialSettings
**Tabela:** `financial_settings`
Configurações financeiras (markup, margens, impostos).

### PackagingSupply
**Tabela:** `packaging_supplies`
Insumos e embalagens com custo unitário.

### ProductAdditionalCost
**Tabela:** `product_additional_costs`
Custos adicionais de produtos (embalagem, etc).

### ProductNativeIngredient
**Tabela:** `product_native_ingredients`
Ingredientes nativos cadastrados por produto (não personalizáveis).

### PushSubscription
**Tabela:** `push_subscriptions`
Assinaturas de Web Push Notifications.

---

## Services (16)

### CartStorage
**Arquivo:** `app/services/CartStorage.php`
Singleton. Gerencia carrinho e personalizações com 3 camadas de persistência:
1. Redis (`mm:cart:{sid}`, `mm:customizations:{sid}`)
2. `$_SESSION`
3. MySQL (`cart_sessions`)

**Chave de personalização:** `parentId:productId:unitN` (contexto combo) ou `productId` (simples).

Documentação detalhada em [PERSONALIZACAO.md](PERSONALIZACAO.md).

### OrderNotificationService
**Arquivo:** `app/services/OrderNotificationService.php`
Envia notificação de pedido via WhatsApp (Evolution API). Formata mensagem com `OrderMessageBuilder`, envia para instância ativa.

### ImagePipelineService
**Arquivo:** `app/services/ImagePipelineService.php`
Pipeline de processamento de imagens:
- Upload → Resize → WebP → AVIF → Thumbnail
- Diretórios: `uploads/products/`, `uploads/logos/`, `uploads/banners/`

### ImageStorageService
**Arquivo:** `app/services/ImageStorageService.php`
Gerencia armazenamento físico de imagens, paths, cleanup.

### RecommendationEngine
**Arquivo:** `app/services/RecommendationEngine.php`
Motor de recomendações inteligentes para cross-sell. Usa afinidade baseada em histórico de pedidos.

### SmartCache
**Arquivo:** `app/services/SmartCache.php`
Cache adaptativo com Redis. TTL dinâmico baseado em frequência de acesso.

### ProductCache
**Arquivo:** `app/services/ProductCache.php`
Cache especializado para produtos. Invalidação automática em CRUD.

### ThermalReceipt
**Arquivo:** `app/services/ThermalReceipt.php`
Geração de cupom para impressora térmica (80mm/58mm).

### IFoodService
**Arquivo:** `app/services/IFoodService.php`
Integração com API iFood: autenticação OAuth, polling de pedidos, confirmação, despacho.

### CustomerEngagementService
**Arquivo:** `app/services/CustomerEngagementService.php`
Campanhas automáticas de retenção via WhatsApp. Identifica clientes inativos, envia mensagens personalizadas.

### AddressAutocompleteService
**Arquivo:** `app/services/AddressAutocompleteService.php`
Autocomplete de endereços usando DB local de ruas + Redis cache + Overpass API (OpenStreetMap) como fallback.

### CostCalculatorService
**Arquivo:** `app/services/CostCalculatorService.php`
Cálculo de custo de produção (ingredientes + embalagem + custos adicionais). Sugere preço de venda baseado em markup.

### ScheduledPauseService
**Arquivo:** `app/services/ScheduledPauseService.php`
Gerencia pausas programadas da loja (ex: pausa de 30min, pausar até amanhã).

### WebPushService
**Arquivo:** `app/services/WebPushService.php`
Envio de Web Push Notifications usando VAPID keys.

### WhatsAppSendService
**Arquivo:** `app/services/WhatsAppSendService.php`
Envio de mensagens WhatsApp via Evolution API com retry e queue.

### WhatsAppValidator
**Arquivo:** `app/services/WhatsAppValidator.php`
Validação de números de telefone via Evolution API (verifica se é WhatsApp ativo).

---

## Helpers (13)

| Helper | Função |
|--------|--------|
| DataValidator | Extração segura de dados de arrays (getString, getInt, getFloat, getArray) |
| JsonHelper | Encode/decode JSON seguro |
| Logger | Log em arquivo (`storage/logs/`) |
| MoneyFormatter | Formatação BRL (`R$ 12,90`) |
| OrderMessageBuilder | Monta mensagem de pedido (WhatsApp, impressão). Processa combos e personalizações. |
| ReceiptFormatter | Formatação para cupom térmico (indentação, separadores, colunas) |
| TextParser | Parse de texto com extração de preço, quantidade, tags |
| daily_highlight_helper | Destaque diário de produtos |
| image_optimization_helper | Funções auxiliares de otimização de imagem |
| lazy_loading_helper | Lazy loading de imagens com placeholder |
| responsive_image_helper | Geração de `<picture>` com srcset responsivo |
| session_security | Funções de segurança de sessão (regeneração, fixation prevention) |
| xss_helper | Sanitização anti-XSS |

---

## Middleware (15)

Executados em cadeia antes do controller:

| Middleware | Função |
|-----------|--------|
| SubdomainDetector | Detecta subdomínio mobile e seta `IS_MOBILE_SUBDOMAIN` |
| SecurityHeaders | Headers de segurança (CSP, X-Frame-Options, HSTS, etc) |
| SessionManager | Inicia sessão segura, regeneração periódica |
| CsrfProtection | Token CSRF em formulários POST |
| RateLimiter | Limite de requisições por IP (Redis-backed) |
| SqlInjectionPrevention | Detecção de padrões SQL injection |
| XssProtection | Sanitização de input contra XSS |
| InputValidator | Validação genérica de inputs |
| FileUploadSecurity | Validação de uploads (tipo, tamanho, extensão) |
| AuthenticationSystem | Autenticação de admin (sessão) |
| Authorization | Autorização por papel (owner, admin) |
| EncryptionLayer | Criptografia de dados sensíveis |
| ApiSecurity | Validação de API tokens/keys |
| AuditLogger | Log de ações administrativas |
| SecurityMonitoring | Monitoramento de tentativas de ataque |
