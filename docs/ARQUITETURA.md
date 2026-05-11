# Arquitetura do Sistema Multi-Menu

## Visão Geral

Sistema de cardápio digital multi-tenant em PHP (MVC), onde cada empresa é identificada por um `slug` único nas URLs. Roda em Docker Swarm com Traefik como proxy reverso.

## Stack

| Camada | Tecnologia |
|--------|-----------|
| Linguagem | PHP 8.x |
| Banco | MySQL 8.x |
| Cache/Sessão | Redis |
| Proxy | Traefik (SSL automático) |
| Container | Docker Swarm |
| Frontend | HTML/CSS/JS vanilla, Tailwind (admin) |

## Estrutura de Diretórios

```
app/
├── bootstrap.php              # Autoload centralizado
├── config/                    # Configurações (app, db, FormatConstants)
├── controllers/               # 59 controllers
├── core/                      # Router, Controller base, Auth, Database, Helpers
├── helpers/                   # 13 helpers (DataValidator, MoneyFormatter, etc)
├── middleware/                 # 15 middlewares de segurança (OWASP)
├── models/                    # 24 models
├── services/                  # 16 services
└── views/
    ├── admin/                 # 29 seções + layout + components/
    └── public/                # Cardápio público (16 views)

database/
├── multi_menu.sql             # Schema completo
└── migrations/                # Migrações incrementais

public/
├── index.php                  # Entry point
├── assets/                    # CSS, JS, ícones
├── js/                        # JS público (lazy-load, mobile, promo)
└── uploads/                   # Imagens (logos, banners, produtos, webp, avif, thumbs)

routes/
└── web.php                    # ~250 rotas (GET + POST)
```

## Fluxo de Requisição

```
Cliente → Traefik (SSL) → public/index.php → Router → Middleware → Controller → Model/Service → View
```

1. `public/index.php` carrega autoloader e `ErrorHandler`
2. `Router` despacha para o controller correto baseado na URL
3. Middlewares executam (CSRF, Rate Limit, Security Headers, Auth)
4. Controller processa lógica, consulta Models/Services
5. View renderiza HTML (ou JSON para APIs)

## Convenção de Nome de Rotas

Regra oficial para `routes/web.php`:

- Padrão canônico: `{modulo}.{acao}`
- `->name(...)` explícito é obrigatório para rotas de negócio
- Auto-name do `Router` é apenas fallback para CRUD simples

Exemplos válidos:

- `orders.index`
- `orders.status`
- `financial.recalculate`
- `products.simple_search`

Observabilidade:

- Quando rota de negócio estiver sem `->name(...)`, o Router registra warning estruturado
- Se faltar `routeName` canônico no render da sidebar, produção entra em fallback seguro e mostra alerta visual: `Sidebar fallback active`

## Controllers (59)

### Admin Desktop (27)
AdminAnalyticsController, AdminApiController, AdminAuthController, AdminCategoryController, AdminCouponsController, AdminCustomerController, AdminCustomizationTemplateController, AdminDashboardController, AdminDeliveryFeeController, AdminEvolutionController, AdminEvolutionInstanceController, AdminExpenseController, AdminFinancialController, AdminGuideController, AdminIFoodController, AdminIngredientController, AdminKdsController, AdminLoyaltyDiscountController, AdminLoyaltyProgramController, AdminOrdersController, AdminPackagingController, AdminPauseController, AdminPaymentMethodController, AdminProductController, AdminProductCostController, AdminPushController, AdminSettingsController

### Admin Mobile (19)
MobileAdminAnalyticsController, MobileAdminAuthController, MobileAdminCategoryController, MobileAdminCouponsController, MobileAdminCrossSellController, MobileAdminCustomerController, MobileAdminCustomizationTemplateController, MobileAdminDashboardController, MobileAdminFinancialController, MobileAdminGuideController, MobileAdminIFoodController, MobileAdminIngredientController, MobileAdminKdsController, MobileAdminOrdersController, MobileAdminPackagingController, MobileAdminPauseController, MobileAdminProductController, MobileAdminProductCostController, MobileAdminSettingsController

### Público (7)
PublicHomeController, PublicProductController, PublicCartController, PublicOrderController, PublicProfileController, PublicAddressController, PublicStreetAutocompleteController

### API / Utilitários (6)
ApiController, MobileApiController, CustomerAuthController, CrossSellGroupController, ImageController, WebhookEvolutionController, WebhookIFoodController

## Models (24)

| Model | Tabela | Descrição |
|-------|--------|-----------|
| Company | companies | Tenant principal |
| Product | products | Produtos (simple/combo) |
| Category | categories | Categorias de produtos |
| Order | orders | Pedidos |
| Customer | customers | Clientes |
| User | users | Usuários admin |
| PaymentMethod | payment_methods | Métodos de pagamento |
| Ingredient | ingredients | Ingredientes |
| DeliveryCity | delivery_cities | Cidades de entrega |
| DeliveryZone | delivery_zones | Zonas de entrega |
| CustomerAddress | customer_addresses | Endereços dos clientes |
| ProductCustomization | product_custom_groups/items | Personalização de produtos |
| CustomizationTemplate | customization_templates | Templates de personalização |
| CrossSellGroup | cross_sell_groups | Grupos de venda cruzada |
| EvolutionInstance | evolution_instances | Instâncias WhatsApp |
| LoyaltyProgram | loyalty_programs | Programa de fidelidade |
| Expense | expenses | Despesas |
| ExpenseCategory | expense_categories | Categorias de despesas |
| FinancialReport | — | Relatórios financeiros |
| FinancialSettings | financial_settings | Config financeiro |
| PackagingSupply | packaging_supplies | Embalagens |
| ProductAdditionalCost | product_additional_costs | Custos adicionais |
| ProductNativeIngredient | product_native_ingredients | Ingredientes nativos |
| PushSubscription | push_subscriptions | Assinaturas push |

## Services (16)

| Service | Arquivo | Função |
|---------|---------|--------|
| CartStorage | CartStorage.php | Carrinho (Redis + sessão + DB) |
| OrderNotificationService | OrderNotificationService.php | Notificações de pedidos (WhatsApp) |
| ImagePipelineService | ImagePipelineService.php | Pipeline de imagens (WebP, AVIF, thumbs) |
| ImageStorageService | ImageStorageService.php | Armazenamento de imagens |
| RecommendationEngine | RecommendationEngine.php | Recomendações inteligentes |
| SmartCache | SmartCache.php | Cache adaptativo Redis |
| ProductCache | ProductCache.php | Cache de produtos |
| ThermalReceipt | ThermalReceipt.php | Impressão térmica |
| IFoodService | IFoodService.php | Integração iFood |
| CustomerEngagementService | CustomerEngagementService.php | Engajamento/retenção |
| AddressAutocompleteService | AddressAutocompleteService.php | Autocomplete de endereços |
| CostCalculatorService | CostCalculatorService.php | Cálculo de custos |
| ScheduledPauseService | ScheduledPauseService.php | Pausas programadas |
| WebPushService | WebPushService.php | Notificações push |
| WhatsAppSendService | WhatsAppSendService.php | Envio WhatsApp |
| WhatsAppValidator | WhatsAppValidator.php | Validação de telefones |

## Helpers (13)

DataValidator, JsonHelper, Logger, MoneyFormatter, OrderMessageBuilder, ReceiptFormatter, TextParser, daily_highlight_helper, image_optimization_helper, lazy_loading_helper, responsive_image_helper, session_security, xss_helper

## Middleware (15)

ApiSecurity, AuditLogger, AuthenticationSystem, Authorization, CsrfProtection, EncryptionLayer, FileUploadSecurity, InputValidator, RateLimiter, SecurityHeaders, SecurityMonitoring, SessionManager, SqlInjectionPrevention, SubdomainDetector, XssProtection

## Integrações

| Sistema | Arquivos |
|---------|---------|
| WhatsApp (Evolution API) | AdminEvolutionController, AdminEvolutionInstanceController, WhatsAppSendService, WebhookEvolutionController |
| iFood | AdminIFoodController, IFoodService, WebhookIFoodController |
| Push Notifications | AdminPushController, WebPushService, PushSubscription |

## Multi-tenancy

Cada empresa possui:
- `slug` único usado em todas as URLs (`/{slug}/produto/1`)
- `company_id` como FK em todas as tabelas de dados
- Configurações isoladas (cores, horários, taxas, pagamentos)
- Instâncias WhatsApp independentes
