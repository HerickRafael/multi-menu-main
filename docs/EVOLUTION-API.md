# 📱 Integração Evolution API (WhatsApp)

Documentação da integração com Evolution API v2.3 para envio de mensagens WhatsApp.

## Visão Geral

O Multi-Menu utiliza a **Evolution API v2.3** para:
- Criar e gerenciar instâncias WhatsApp
- Conectar via QR Code
- Enviar notificações de pedidos
- Validar números de telefone

## Configuração

### Variáveis de Ambiente

```env
EVOLUTION_API_URL=https://api.evolution.com
EVOLUTION_API_KEY=sua-api-key
```

### Configuração por Empresa

Cada empresa pode ter sua própria configuração no banco de dados:

```sql
-- Tabela companies
evolution_server_url VARCHAR(255)  -- URL do servidor Evolution
evolution_api_key VARCHAR(255)     -- API Key específica
```

---

## Controllers

### AdminEvolutionController
**Arquivo:** `app/controllers/AdminEvolutionController.php`

Gerenciamento de instâncias WhatsApp.

| Ação | Endpoint | Descrição |
|------|----------|-----------|
| Listar | GET `/admin/{slug}/evolution` | Página de instâncias |
| Dados | GET `/admin/{slug}/evolution/instances/data` | JSON com status |
| Criar | POST `/admin/{slug}/evolution/create` | Nova instância |
| Excluir | POST `/admin/{slug}/evolution/delete` | Remove instância |
| Sincronizar | POST `/admin/{slug}/evolution/sync` | Atualiza da API |

### AdminEvolutionInstanceController
**Arquivo:** `app/controllers/AdminEvolutionInstanceController.php`

Configuração individual de instâncias.

| Ação | Endpoint | Descrição |
|------|----------|-----------|
| Config | GET `.../instance/{name}` | Página de config |
| Estado | GET `.../instance/{name}/connection_state` | Estado da conexão |
| QR Code | GET `.../instance/{name}/qr_code` | Obter QR Code |
| Conectar | GET `.../instance/{name}/connect` | Iniciar conexão |
| Desconectar | POST `.../instance/{name}/disconnect` | Desconectar |
| Reiniciar | POST `.../instance/{name}/restart` | Reiniciar instância |
| Grupos | GET `.../instance/{name}/groups` | Listar grupos |
| Teste | POST `.../instance/{name}/order-notification` | Testar notificação |

---

## API Evolution v2.3

### Endpoints Utilizados

#### Criar Instância
```http
POST /instance/create

{
    "instanceName": "pizzaria-01",
    "integration": "WHATSAPP-BAILEYS",
    "qrcode": true
}
```

**Response:**
```json
{
    "instance": {
        "instanceName": "pizzaria-01",
        "instanceId": "abc123",
        "status": "created"
    },
    "qrcode": {
        "base64": "data:image/png;base64,..."
    }
}
```

#### Estado da Conexão
```http
GET /instance/connectionState/{instanceName}
```

**Response:**
```json
{
    "instance": {
        "instanceName": "pizzaria-01",
        "state": "open"
    }
}
```

**Estados possíveis:**
- `open` - Conectado
- `close` - Desconectado
- `connecting` - Conectando

#### Obter QR Code
```http
GET /instance/connect/{instanceName}
```

**Response:**
```json
{
    "base64": "data:image/png;base64,...",
    "code": "2@..."
}
```

#### Excluir Instância
```http
DELETE /instance/delete/{instanceName}
```

#### Enviar Mensagem Texto
```http
POST /message/sendText/{instanceName}

{
    "number": "5511999999999",
    "text": "🍕 Novo pedido #123\n\n..."
}
```

#### Verificar Número
```http
POST /chat/whatsappNumbers/{instanceName}

{
    "numbers": ["5511999999999"]
}
```

**Response:**
```json
[
    {
        "exists": true,
        "jid": "5511999999999@s.whatsapp.net",
        "number": "5511999999999"
    }
]
```

---

## Services

### OrderNotificationService
**Arquivo:** `app/services/OrderNotificationService.php`

Envio automático de notificações de pedidos.

```php
$service = new OrderNotificationService($db);
$result = $service->sendOrderNotification($order, $company);

if ($result['success']) {
    // Mensagem enviada
} else {
    Logger::error('WhatsApp error', $result['error']);
}
```

**Fluxo:**
1. Busca instância conectada
2. Gera mensagem formatada
3. Envia via API
4. Registra log

### WhatsAppValidator
**Arquivo:** `app/services/WhatsAppValidator.php`

Validação de números.

```php
$validator = new WhatsAppValidator($db);

// Validar número
$result = $validator->validate('5511999999999', $companyId);

if ($result['exists']) {
    // Número válido no WhatsApp
}
```

---

## Formato de Mensagem

### Template de Pedido

```
🍕 *NOVO PEDIDO #123*

━━━━━━━━━━━━━━━━━━━━━━━━━

👤 *Cliente:* João Silva
📱 *Telefone:* (11) 99999-9999

📦 *ITENS:*
• 1x Hambúrguer Classic ....... R$ 25,90
  └ Ponto: Ao ponto
  └ Adicionais: Bacon, Cheddar

━━━━━━━━━━━━━━━━━━━━━━━━━

💰 *Subtotal:* R$ 25,90
🛵 *Entrega:* R$ 5,00
📍 *Total:* R$ 30,90

📍 *ENDEREÇO:*
Rua das Flores, 123
Centro - São Paulo/SP

💳 *Pagamento:* PIX

⏰ *Horário:* 14:30
```

### Helpers de Formatação

```php
use App\Helpers\ReceiptFormatter;
use App\Helpers\MoneyFormatter;

// Linha com valor alinhado
$line = ReceiptFormatter::alignRight('Total:', 'R$ 30,90', 58);

// Separador
$sep = ReceiptFormatter::separator(58);

// Formatação monetária
$value = MoneyFormatter::format(30.90); // "R$ 30,90"
```

---

## QR Code Auto-Refresh

A página de configuração da instância implementa refresh automático:

```javascript
// A cada 20 segundos
setInterval(async () => {
    const response = await fetch(`/evolution/instance/${name}/qr_code`);
    const data = await response.json();
    
    if (data.connected) {
        location.reload(); // Conectou!
    } else {
        qrImage.src = data.base64;
        countdown = 20;
    }
}, 20000);
```

---

## Tratamento de Erros

### Erros Comuns

| Código | Erro | Solução |
|--------|------|---------|
| 401 | Unauthorized | Verificar API Key |
| 404 | Instance not found | Instância não existe |
| 500 | Internal error | Verificar logs Evolution |

### Retry Logic

```php
class EvolutionApiPlugin {
    private $maxRetries = 3;
    private $retryDelay = 1000; // ms
    
    public function sendWithRetry($instanceName, $message) {
        for ($i = 0; $i < $this->maxRetries; $i++) {
            try {
                return $this->send($instanceName, $message);
            } catch (Exception $e) {
                if ($i === $this->maxRetries - 1) throw $e;
                usleep($this->retryDelay * 1000);
            }
        }
    }
}
```

---

## Webhooks

### Configuração

```http
POST /webhook/set/{instanceName}

{
    "url": "https://meusite.com/webhook/evolution",
    "events": [
        "MESSAGES_UPSERT",
        "CONNECTION_UPDATE",
        "QRCODE_UPDATED"
    ]
}
```

### Eventos Suportados

| Evento | Descrição |
|--------|-----------|
| `MESSAGES_UPSERT` | Nova mensagem recebida |
| `CONNECTION_UPDATE` | Mudança de conexão |
| `QRCODE_UPDATED` | QR Code atualizado |

---

## Boas Práticas

### Segurança
- ✅ API Key nunca exposta no frontend
- ✅ Validar números antes de enviar
- ✅ Rate limiting nas requisições
- ✅ Logs de todas as mensagens

### Performance
- ✅ Cache do estado de conexão
- ✅ Retry com backoff exponencial
- ✅ Envio assíncrono quando possível

### Confiabilidade
- ✅ Verificar conexão antes de enviar
- ✅ Fallback para email se WhatsApp falhar
- ✅ Monitorar taxa de entrega
