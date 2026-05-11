# Controllers & Rotas — Referência Completa

## Estrutura de URLs

| Contexto | Padrão de URL |
|----------|--------------|
| Público | `/{slug}/...` |
| Admin Desktop | `/admin/{slug}/...` |
| Admin Mobile | `/...` (subdomínio `m.wollburger.online`) |
| API REST | `/api/{slug}/...` |
| Webhooks | `/webhook/...` |

---

## Controllers Públicos (7)

### PublicHomeController
Cardápio principal e busca.

| Método | Rota | Ação |
|--------|------|------|
| GET | `/{slug}` | `index` — Listagem de categorias e produtos |
| GET | `/{slug}/buscar` | `buscar` — Busca de produtos |
| GET | `/{slug}/politica-privacidade` | `privacyPolicy` |

### PublicProductController
Página de produto e personalização.

| Método | Rota | Ação |
|--------|------|------|
| GET | `/{slug}/produto/{id}` | `show` — Exibe produto (simples ou combo) |
| GET | `/{slug}/produto/{id}/customizar` | `customize` — Form de personalização |
| POST | `/{slug}/produto/{id}/customizar` | `saveCustomization` — Salva personalização no CartStorage |
| GET | `/{slug}/produto/{id}/customizar/cancelar` | `cancelCustomization` |
| GET | `/{slug}/check-customization` | `checkCustomization` — Verifica estado (AJAX) |

### PublicCartController
Carrinho, checkout e finalização de pedido.

| Método | Rota | Ação |
|--------|------|------|
| GET | `/{slug}/cart` | `index` — Visualizar carrinho |
| POST | `/{slug}/cart/add` | `add` — Adicionar ao carrinho |
| POST | `/{slug}/cart/update` | `update` — Atualizar quantidade/remover |
| POST | `/{slug}/validate-coupon` | `validateCoupon` |
| POST | `/{slug}/sync-coupon` | `syncCoupon` |
| GET | `/{slug}/checkout` | `checkout` — Tela de checkout |
| POST | `/{slug}/checkout` | `submitCheckout` — Finalizar pedido |
| GET | `/{slug}/checkout/processing` | `processing` — Tela de processamento |
| GET | `/{slug}/checkout/success` | `checkoutSuccess` — Confirmação |
| POST | `/{slug}/reorder/{orderId}` | `reorder` — Repetir pedido anterior |

### PublicOrderController
Acompanhamento de pedidos.

| Método | Rota | Ação |
|--------|------|------|
| GET | `/{slug}/order/{id}` | `show` — Detalhes do pedido |
| POST | `/{slug}/order/{id}/cancel` | `cancel` — Cancelar pedido |

### PublicProfileController
Perfil do cliente.

| Método | Rota | Ação |
|--------|------|------|
| GET | `/{slug}/profile` | `index` — Ver perfil |
| POST | `/{slug}/profile/update` | `update` — Atualizar dados |
| GET | `/{slug}/profile/export-data` | `exportData` — LGPD export |
| POST | `/{slug}/profile/request-deletion` | `requestDeletion` — LGPD deletion |

### PublicAddressController
Endereços do cliente.

| Método | Rota | Ação |
|--------|------|------|
| GET | `/{slug}/addresses` | `index` |
| GET/POST | `/{slug}/addresses/create` | `create` / `store` |
| GET | `/{slug}/addresses/edit/{id}` | `edit` |
| POST | `/{slug}/addresses/update` | `update` |
| POST | `/{slug}/addresses/delete` | `delete` |
| POST | `/{slug}/addresses/set-default` | `setDefault` |
| POST | `/{slug}/addresses/update-label` | `updateLabel` |

### PublicStreetAutocompleteController
Autocomplete de ruas (DB local + Redis + Overpass fallback).

| Método | Rota | Ação |
|--------|------|------|
| GET | `/{slug}/street-autocomplete` | `search` |
| POST | `/{slug}/street-autocomplete/popularity` | `popularity` |
| POST | `/{slug}/street-autocomplete/learn` | `learn` |

### CustomerAuthController
Autenticação do cliente.

| Método | Rota | Ação |
|--------|------|------|
| POST | `/{slug}/customer-login` | `login` |
| POST | `/{slug}/customer-logout` | `logout` |
| GET | `/{slug}/customer-me` | `me` |
| POST | `/{slug}/customer-lookup` | `lookup` |

---

## Controllers Admin Desktop (27)

### AdminAuthController
| GET | `/admin/{slug}/login` | `loginForm` |
| POST | `/admin/{slug}/login` | `login` |
| GET | `/admin/{slug}/logout` | `logout` |

### AdminDashboardController
| GET | `/admin/{slug}/dashboard` | `index` |
| GET | `/admin/{slug}/manifest.webmanifest` | `manifest` — PWA manifest dinâmico |

### AdminSettingsController
| GET/POST | `/admin/{slug}/settings` | `index` / `save` |

### AdminOrdersController
| GET | `/admin/{slug}/orders` | `index` — Lista de pedidos |
| GET | `/admin/{slug}/orders/show` | `show` — Detalhes |
| GET | `/admin/{slug}/orders/print` | `printPdf` — Impressão |
| GET | `/admin/{slug}/orders/create` | `create` — Pedido manual |
| POST | `/admin/{slug}/orders` | `store` |
| POST | `/admin/{slug}/orders/setStatus` | `setStatus` |
| POST | `/admin/{slug}/orders/{id}/del` | `destroy` |

### AdminProductController
| GET | `/admin/{slug}/products` | `index` |
| GET | `/admin/{slug}/products/create` | `create` |
| POST | `/admin/{slug}/products` | `store` |
| GET | `/admin/{slug}/products/{id}/edit` | `edit` |
| POST | `/admin/{slug}/products/{id}` | `update` |
| POST | `/admin/{slug}/products/{id}/del` | `destroy` |
| GET | `/admin/{slug}/products/simple-search` | `simpleProductsSearch` — Busca para combos |

### AdminCategoryController
CRUD completo: `index`, `create`, `store`, `edit`, `update`, `destroy`

### AdminIngredientController
CRUD + toggle: `index`, `create`, `store`, `edit`, `update`, `toggle`, `destroy`

### AdminCustomizationTemplateController
Templates reutilizáveis de personalização.

| Rota | Ação |
|------|------|
| `/admin/{slug}/customization-templates` | CRUD: `index`, `create`, `store`, `edit`, `delete`, `toggle` |
| `/admin/{slug}/customization-templates/api/list` | `apiList` — Lista JSON |
| `/admin/{slug}/customization-templates/api/{id}` | `apiGet` — Detalhes JSON |
| `/admin/{slug}/customization-templates/api/copy-to-product` | `apiCopyToProduct` |
| `/admin/{slug}/customization-templates/api/create-from-group` | `apiCreateFromGroup` |

### AdminPaymentMethodController
| Rotas | `index`, `store`, `batchUpdate`, `update`, `destroy` |

### AdminDeliveryFeeController
Cidades e zonas de entrega.

| Rotas | `index`, `storeCity`, `updateCity`, `destroyCity`, `storeZone`, `adjustZones`, `updateZone`, `destroyZone`, `updateOptions`, `updateFreeShipping` |

### AdminCouponsController
| Rotas | `api`, `history`, `create`, `store`, `edit`, `update`, `delete`, `toggle` |

### AdminLoyaltyDiscountController
| Rotas | `index`, `save`, `createCoupon`, `listCoupons` |

### AdminLoyaltyProgramController
| Rotas | `index`, `save`, `toggle`, `stats` |

### AdminCustomerController
CRUD + endereços: `index`, `create`, `edit`, `store`, `delete`, `apiSearch`, `validateWhatsapp`, `storeAddress`, `updateAddress`, `deleteAddress`

### AdminAnalyticsController
| GET | `/admin/{slug}/analytics` | `index` |
| GET | `/admin/{slug}/analytics/data` | `apiData` — JSON |

### AdminKdsController (Kitchen Display)
| GET | `/admin/{slug}/kds` | `index` |
| GET | `/admin/{slug}/kds/data` | `data` — JSON polling |
| POST | `/admin/{slug}/kds/status` | `status` — Atualizar status |

### AdminEvolutionController (WhatsApp)
Gerenciamento de instâncias Evolution API.

| Rotas | `instances`, `instancesData`, `create`, `refresh_qr`, `delete`, `import_remote`, `fetch_and_import`, `sync`, `configure_webhook`, `remove_webhook`, `webhook_status` |

### AdminEvolutionInstanceController
Configuração individual de instância WhatsApp.

| Rotas | `config`, `connection_state`, `connect`, `restart`, `disconnect`, `qr_code`, `stats`, `groups`, `order_notification`, `check_notification_conflict`, `validate_whatsapp`, `save_settings`, `get_settings`, `customer_engagement`, `engagement_stats` |

### AdminIFoodController
| Rotas | `config`, `saveConfig`, `orders`, `viewOrder`, `confirmOrder`, `readyOrder`, `dispatchOrder`, `cancelOrder`, `getCancellationReasons`, `poll`, `testConnection`, `clearError`, `status` |

### AdminFinancialController
| Rotas | `dashboard`, `monthly`, `yearly`, `settings`, `saveSettings`, `recalculateCosts`, `chartData` |

### AdminExpenseController
| Rotas | `index`, `create`, `store`, `edit`, `update`, `destroy`, `categories`, `storeCategory`, `updateCategory`, `destroyCategory`, `seedCategories` |

### AdminProductCostController
| Rotas | `index`, `edit`, `update`, `updatePackaging`, `bulkUpdate`, `calculate`, `suggestPrice` |

### AdminPackagingController
| Rotas | `index`, `create`, `edit`, `store`, `delete`, `apiList`, `apiSaveProductPackaging` |

### AdminApiController
| Rotas | `index`, `generateToken`, `revokeToken`, `generateApiKey`, `revokeApiKey`, `searchCustomerByPhone` |

### AdminPushController
| Rotas | `getVapidKey`, `subscribe`, `unsubscribe`, `test`, `status` |

### AdminPauseController
| Rotas | `status`, `enable`, `disable`, `extend` |

### AdminGuideController
Guias interativos: `products`, `ingredients`, `coupons`, `crossSell`, `paymentMethods`, `deliveryFees`, `loyaltyDiscount`, `financial`, `customizationTemplates`, `companySettings`, `manualOrder`, `whatsapp`, `ifood`

### CrossSellGroupController
| Rotas | `index`, `save`, `edit`, `toggle`, `delete` |

---

## Controllers Admin Mobile (19)

Espelham os controllers desktop com adaptações para a UI mobile. Rotas sem prefixo `/admin/{slug}` (subdomínio dedicado).

Cada controller Mobile corresponde a um Desktop:
- MobileAdminAuthController, MobileAdminDashboardController, MobileAdminOrdersController
- MobileAdminProductController, MobileAdminCategoryController, MobileAdminCustomerController
- MobileAdminSettingsController (concentra config geral + WhatsApp + fidelidade + API)
- MobileAdminCouponsController, MobileAdminKdsController, MobileAdminIFoodController
- MobileAdminAnalyticsController, MobileAdminFinancialController
- MobileAdminProductCostController, MobileAdminPackagingController
- MobileAdminIngredientController, MobileAdminCustomizationTemplateController
- MobileAdminCrossSellController, MobileAdminPauseController, MobileAdminGuideController

---

## API REST (ApiController)

Autenticação via JWT Token ou API Key.

| Método | Rota | Ação |
|--------|------|------|
| GET | `/api/{slug}` | `getCompany` — Info da empresa |
| GET | `/api/{slug}/stats` | `getStats` — Estatísticas |
| GET | `/api/{slug}/categories` | `getCategories` |
| GET | `/api/{slug}/products` | `getProducts` |
| GET | `/api/{slug}/products/{id}` | `getProduct` |
| GET | `/api/{slug}/simple-products` | `getSimpleProducts` |
| GET | `/api/{slug}/orders` | `getOrders` |
| GET | `/api/{slug}/orders/{id}` | `getOrder` |
| POST | `/api/{slug}/orders` | `createOrder` |
| POST | `/api/{slug}/orders/{id}/status` | `updateOrderStatus` |
| POST | `/api/{slug}/track-interaction` | `trackInteraction` — ML tracking |
| POST | `/api/{slug}/token` | `generateToken` — JWT |

---

## Webhooks

| Rota | Controller | Descrição |
|------|-----------|-----------|
| POST `/webhook/evolution/{instanceName}` | WebhookEvolutionController | Mensagens WhatsApp |
| POST `/webhook/ifood` | WebhookIFoodController | Eventos iFood |

---

## Utilitários

### ImageController
| GET | `/img/{path}` | `serve` — Imagens otimizadas com cache (WebP, AVIF, thumbs) |

### MobileApiController
API AJAX para o admin mobile.

| GET | `/api/orders` | `getOrders` |
| GET | `/api/orders/{id}` | `getOrder` |
| POST | `/api/orders/{id}/status` | `updateOrderStatus` |
| GET | `/api/stats` | `getStats` |
| GET | `/api/customers/search` | `searchCustomerByPhone` |

---

## Rotas SEO

| Rota | Descrição |
|------|-----------|
| GET `/robots.txt` | robots.txt dinâmico (bloqueia admin, cart, checkout) |
| GET `/sitemap.xml` | Sitemap XML gerado do banco (todas as empresas) |
