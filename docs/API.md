# 🔌 API REST

Documentação completa da API pública do Multi-Menu.

## Visão Geral

A API REST permite integração com sistemas externos para:
- Consultar produtos e categorias
- Criar e gerenciar pedidos
- Rastrear interações de clientes
- Obter estatísticas

**Base URL:** `https://seu-dominio.com/api/{slug}`

## Autenticação

### API Key (Recomendado)

```http
GET /api/pizzaria-central/products
X-API-Key: sua-api-key-aqui
```

### JWT Bearer Token

```http
POST /api/pizzaria-central/token
Content-Type: application/json

{
    "email": "admin@empresa.com",
    "password": "senha123"
}
```

**Response:**
```json
{
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "expires_in": 3600
}
```

Uso subsequente:
```http
GET /api/pizzaria-central/orders
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

---

## Endpoints

### Empresa

#### GET /api/{slug}
Retorna informações da empresa.

**Response:**
```json
{
    "id": 1,
    "name": "Pizzaria Central",
    "slug": "pizzaria-central",
    "whatsapp": "+5511999999999",
    "address": "Rua das Pizzas, 123",
    "logo": "/uploads/logos/pizzaria-central.png",
    "banner": "/uploads/banners/pizzaria-central.jpg",
    "min_order": 20.00,
    "delivery_options": {
        "min_time": 30,
        "max_time": 60,
        "free_delivery_above": 100.00
    },
    "hours": [
        {"day": 0, "open": "18:00", "close": "23:00"},
        {"day": 1, "open": "18:00", "close": "23:00"}
    ]
}
```

---

#### GET /api/{slug}/stats
Retorna estatísticas da empresa. **Requer autenticação.**

**Response:**
```json
{
    "total_orders": 1250,
    "orders_today": 45,
    "revenue_today": 2350.50,
    "revenue_month": 45000.00,
    "average_ticket": 52.50,
    "top_products": [
        {"id": 5, "name": "Pizza Margherita", "count": 150}
    ]
}
```

---

### Categorias

#### GET /api/{slug}/categories
Lista todas as categorias ativas.

**Response:**
```json
{
    "categories": [
        {
            "id": 1,
            "name": "Pizzas",
            "sort_order": 1,
            "product_count": 15
        },
        {
            "id": 2,
            "name": "Bebidas",
            "sort_order": 2,
            "product_count": 8
        }
    ]
}
```

---

### Produtos

#### GET /api/{slug}/products
Lista todos os produtos ativos.

**Query Parameters:**
| Param | Tipo | Descrição |
|-------|------|-----------|
| `category_id` | int | Filtrar por categoria |
| `search` | string | Busca por nome |
| `page` | int | Página (default: 1) |
| `limit` | int | Itens por página (default: 20) |

**Request:**
```http
GET /api/pizzaria-central/products?category_id=1&limit=10
```

**Response:**
```json
{
    "products": [
        {
            "id": 5,
            "name": "Pizza Margherita",
            "description": "Molho de tomate, mussarela e manjericão",
            "price": 45.90,
            "promo_price": null,
            "image": "/uploads/products/pizza-margherita.jpg",
            "category_id": 1,
            "type": "simple",
            "allow_customize": true
        }
    ],
    "pagination": {
        "current_page": 1,
        "total_pages": 2,
        "total_items": 15,
        "per_page": 10
    }
}
```

---

#### GET /api/{slug}/products/{id}
Detalhes de um produto específico.

**Response:**
```json
{
    "id": 5,
    "name": "Pizza Margherita",
    "description": "Molho de tomate, mussarela e manjericão",
    "price": 45.90,
    "promo_price": null,
    "image": "/uploads/products/pizza-margherita.jpg",
    "category_id": 1,
    "type": "simple",
    "allow_customize": true,
    "customization_groups": [
        {
            "id": 1,
            "name": "Tamanho",
            "type": "single",
            "min_qty": 1,
            "max_qty": 1,
            "items": [
                {"id": 1, "label": "Pequena (4 fatias)", "delta": 0},
                {"id": 2, "label": "Média (6 fatias)", "delta": 10.00},
                {"id": 3, "label": "Grande (8 fatias)", "delta": 20.00}
            ]
        },
        {
            "id": 2,
            "name": "Adicionais",
            "type": "extra",
            "min_qty": 0,
            "max_qty": 5,
            "items": [
                {"id": 4, "label": "Bacon", "delta": 5.00},
                {"id": 5, "label": "Cebola caramelizada", "delta": 3.00}
            ]
        }
    ]
}
```

---

#### GET /api/{slug}/simple-products
Lista apenas produtos simples (não combos).

**Response:**
```json
{
    "products": [
        {
            "id": 5,
            "name": "Pizza Margherita",
            "price": 45.90,
            "category_name": "Pizzas"
        }
    ]
}
```

---

### Pedidos

#### GET /api/{slug}/orders
Lista pedidos. **Requer autenticação.**

**Query Parameters:**
| Param | Tipo | Descrição |
|-------|------|-----------|
| `status` | string | Filtrar por status |
| `date_from` | date | Data inicial (YYYY-MM-DD) |
| `date_to` | date | Data final |
| `page` | int | Página |

**Response:**
```json
{
    "orders": [
        {
            "id": 123,
            "customer_name": "João Silva",
            "customer_phone": "+5511999999999",
            "status": "pending",
            "subtotal": 45.90,
            "delivery_fee": 5.00,
            "discount": 0,
            "total": 50.90,
            "created_at": "2025-01-15T14:30:00Z",
            "items_count": 2
        }
    ],
    "pagination": {...}
}
```

---

#### GET /api/{slug}/orders/{id}
Detalhes de um pedido. **Requer autenticação.**

**Response:**
```json
{
    "id": 123,
    "customer_name": "João Silva",
    "customer_phone": "+5511999999999",
    "status": "pending",
    "subtotal": 45.90,
    "delivery_fee": 5.00,
    "discount": 0,
    "coupon_code": null,
    "total": 50.90,
    "notes": "Sem cebola",
    "address": {
        "street": "Rua das Flores",
        "number": "123",
        "neighborhood": "Centro",
        "city": "São Paulo",
        "complement": "Apto 45"
    },
    "payment_method": "pix",
    "created_at": "2025-01-15T14:30:00Z",
    "items": [
        {
            "id": 1,
            "product_id": 5,
            "product_name": "Pizza Margherita",
            "quantity": 1,
            "unit_price": 45.90,
            "customization": {
                "Tamanho": "Média (6 fatias)",
                "Adicionais": ["Bacon"]
            }
        }
    ]
}
```

---

#### POST /api/{slug}/orders
Cria um novo pedido.

**Request:**
```json
{
    "customer_name": "João Silva",
    "customer_phone": "+5511999999999",
    "items": [
        {
            "product_id": 5,
            "quantity": 1,
            "customization": {
                "1": [2],
                "2": [4]
            },
            "notes": "Sem cebola"
        }
    ],
    "address": {
        "street": "Rua das Flores",
        "number": "123",
        "neighborhood": "Centro",
        "city": "São Paulo",
        "complement": "Apto 45"
    },
    "payment_method_id": 1,
    "coupon_code": "PROMO10",
    "notes": "Entregar no portão"
}
```

**Response:**
```json
{
    "success": true,
    "order": {
        "id": 124,
        "status": "pending",
        "total": 55.90
    }
}
```

---

#### POST /api/{slug}/orders/{id}/status
Atualiza status do pedido. **Requer autenticação.**

**Request:**
```json
{
    "status": "confirmed"
}
```

**Status válidos:**
- `pending` - Pendente
- `confirmed` - Confirmado
- `preparing` - Em preparo
- `ready` - Pronto
- `delivering` - Em entrega
- `delivered` - Entregue
- `canceled` - Cancelado

**Response:**
```json
{
    "success": true,
    "order": {
        "id": 123,
        "status": "confirmed",
        "status_changed_at": "2025-01-15T14:35:00Z"
    }
}
```

---

### Interações

#### POST /api/{slug}/track-interaction
Registra interação para sistema de recomendação.

**Request:**
```json
{
    "customer_id": 45,
    "product_id": 5,
    "event": "view"
}
```

**Eventos válidos:**
- `view` - Visualizou produto
- `add_to_cart` - Adicionou ao carrinho
- `purchase` - Comprou

**Response:**
```json
{
    "success": true
}
```

---

## Códigos de Erro

| Código | Descrição |
|--------|-----------|
| 200 | Sucesso |
| 201 | Criado com sucesso |
| 400 | Requisição inválida |
| 401 | Não autenticado |
| 403 | Não autorizado |
| 404 | Não encontrado |
| 422 | Erro de validação |
| 429 | Rate limit excedido |
| 500 | Erro interno |

**Formato de erro:**
```json
{
    "error": true,
    "message": "Produto não encontrado",
    "code": "PRODUCT_NOT_FOUND"
}
```

---

## Rate Limiting

| Tipo | Limite |
|------|--------|
| Com API Key | 1000 req/min |
| Com JWT | 100 req/min |
| Sem auth (público) | 60 req/min |

**Headers retornados:**
```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 950
X-RateLimit-Reset: 1699900000
```

---

## Webhooks

Configure webhooks para receber eventos em tempo real.

### POST /api/{slug}/webhooks
Configura webhook. **Requer autenticação.**

**Request:**
```json
{
    "url": "https://seu-sistema.com/webhooks/multimenu",
    "events": ["order.created", "order.status_changed"],
    "secret": "seu-secret-para-validacao"
}
```

### Eventos disponíveis

| Evento | Descrição |
|--------|-----------|
| `order.created` | Novo pedido |
| `order.status_changed` | Status alterado |
| `order.canceled` | Pedido cancelado |

### Payload do webhook

```json
{
    "event": "order.created",
    "timestamp": "2025-01-15T14:30:00Z",
    "data": {
        "order_id": 123,
        "customer_name": "João Silva",
        "total": 50.90
    },
    "signature": "sha256=abc123..."
}
```

### Validação de assinatura

```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    exit('Invalid signature');
}
```

---

## SDKs e Exemplos

### PHP

```php
$client = new MultiMenuApi('https://cardapio.exemplo.com/api/pizzaria-central');
$client->setApiKey('sua-api-key');

// Listar produtos
$products = $client->products()->list(['category_id' => 1]);

// Criar pedido
$order = $client->orders()->create([
    'customer_name' => 'João',
    'customer_phone' => '+5511999999999',
    'items' => [...]
]);
```

### JavaScript

```javascript
const api = new MultiMenuApi({
    baseUrl: 'https://cardapio.exemplo.com/api/pizzaria-central',
    apiKey: 'sua-api-key'
});

// Listar produtos
const products = await api.products.list({ category_id: 1 });

// Criar pedido
const order = await api.orders.create({
    customer_name: 'João',
    customer_phone: '+5511999999999',
    items: [...]
});
```

### cURL

```bash
# Listar produtos
curl -X GET "https://cardapio.exemplo.com/api/pizzaria-central/products" \
  -H "X-API-Key: sua-api-key"

# Criar pedido
curl -X POST "https://cardapio.exemplo.com/api/pizzaria-central/orders" \
  -H "X-API-Key: sua-api-key" \
  -H "Content-Type: application/json" \
  -d '{"customer_name": "João", ...}'
```
