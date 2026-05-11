# Evolution API - Arquitetura e Funcionamento

## Visão Geral

Este documento explica como funciona a integração com a Evolution API para WhatsApp no sistema Multi-Menu.

## Princípio Fundamental

> **O campo `connectionStatus` do endpoint `/instance/fetchInstances` É IGNORADO.**
> 
> Usamos `/instance/fetchInstances` APENAS para obter a lista de instâncias e seus metadados (nome, número, contadores).
> 
> **Para o status de conexão, SEMPRE usamos `/instance/connectionState/{instanceName}`.**

---

## Arquitetura

### Fluxo de Dados

```
┌─────────────────────────────────────────────────────────────────────┐
│                         PAINEL ADMIN                                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│   1. Listagem de Instâncias (AdminEvolutionController)               │
│      │                                                                │
│      ├── Busca lista: GET /instance/fetchInstances                   │
│      │   (retorna nomes, números, contadores - NÃO CONFIAR no status)│
│      │                                                                │
│      └── Para CADA instância, verifica estado REAL:                  │
│          GET /instance/connectionState/{instanceName}                 │
│          └── Retorna: { instance: { state: "open" | "connecting" | "close" } }
│                                                                       │
│   2. Página da Instância (AdminEvolutionInstanceController)          │
│      │                                                                │
│      └── Mesma lógica: busca dados + verifica estado REAL            │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
```

### Fluxo de Envio de Notificações

```
┌─────────────────────────────────────────────────────────────────────┐
│                    ENVIO DE NOTIFICAÇÕES                             │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│   OrderNotificationService::sendOrderNotification()                  │
│   │                                                                   │
│   ├── 1. Busca instâncias da API                                     │
│   │      GET /instance/fetchInstances                                │
│   │                                                                   │
│   ├── 2. Para cada instância, verifica estado REAL                   │
│   │      GET /instance/connectionState/{instanceName}                │
│   │      └── Só considera conectada se state = "open"                │
│   │                                                                   │
│   ├── 3. Busca configuração de notificação (banco local)             │
│   │      SELECT FROM instance_configs WHERE config_key = 'order_notification'
│   │                                                                   │
│   ├── 4. Verifica se instância ativa está REALMENTE conectada        │
│   │                                                                   │
│   └── 5. Envia mensagem via Evolution API                            │
│          POST /message/sendText/{instanceName}                       │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Estados de Conexão

| Estado      | Significado                          | Ação do Sistema              |
|-------------|--------------------------------------|------------------------------|
| `open`      | ✅ Conectado e funcionando            | Pode enviar mensagens        |
| `connecting`| ⚠️ Tentando reconectar               | NÃO enviar, mostrar aviso    |
| `close`     | ❌ Desconectado                       | Não enviar, pedir QR code    |
| `unknown`   | ❓ Não foi possível verificar         | Mostrar "Verificando..."     |

### Mapeamento na Interface

```php
$statusText = match($status) {
    'open' => 'Conectado',           // Verde
    'connecting' => 'Reconectando',  // Amarelo (NÃO é "Conectando"!)
    'close', 'disconnected' => 'Desconectado',  // Vermelho
    'unknown' => 'Verificando...',   // Cinza
    default => ucfirst($status)
};
```

---

## Endpoints da Evolution API

### Verificação de Status

```bash
# CORRETO - Estado REAL da conexão
GET /instance/connectionState/{instanceName}
Headers: apikey: {sua_api_key}

# Resposta
{
  "instance": {
    "instanceName": "minha-instancia",
    "state": "open"  // ou "connecting" ou "close"
  }
}
```

### Listagem de Instâncias (apenas para metadados)

```bash
# NÃO CONFIAR no connectionStatus!
GET /instance/fetchInstances
Headers: apikey: {sua_api_key}

# Resposta - connectionStatus pode estar ERRADO
[
  {
    "id": "uuid-aqui",
    "name": "minha-instancia",
    "connectionStatus": "open",  // ⚠️ PODE ESTAR ERRADO!
    "ownerJid": "5511999999999@s.whatsapp.net",
    "profileName": "Meu Negócio",
    "_count": { "Chat": 150, "Message": 5000 }
  }
]
```

### Gerar QR Code para Conectar

```bash
GET /instance/connect/{instanceName}
Headers: apikey: {sua_api_key}

# Resposta - QR code em base64
{
  "base64": "data:image/png;base64,..."
}

# OU se já está conectado
{
  "instance": {
    "state": "open"
  }
}
```

### Enviar Mensagem

```bash
POST /message/sendText/{instanceName}
Headers: 
  apikey: {sua_api_key}
  Content-Type: application/json

Body:
{
  "number": "5511999999999",
  "text": "Sua mensagem aqui"
}
```

---

## Arquivos Principais

### Controllers

| Arquivo | Responsabilidade |
|---------|------------------|
| `AdminEvolutionController.php` | Listagem de instâncias, criação |
| `AdminEvolutionInstanceController.php` | Configuração individual, QR code, stats |

### Services

| Arquivo | Responsabilidade |
|---------|------------------|
| `OrderNotificationService.php` | Envio de notificações de pedido |
| `WhatsAppValidator.php` | Validação de números WhatsApp |

### Views

| Arquivo | Responsabilidade |
|---------|------------------|
| `views/admin/evolution/instances.php` | Lista de instâncias |
| `views/admin/evolution/instance_config.php` | Configuração da instância |

---

## Banco de Dados

### Tabela `instance_configs` (USADA)

Armazena configurações locais como notificações.

```sql
CREATE TABLE instance_configs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  company_id INT NOT NULL,
  instance_name VARCHAR(255) NOT NULL,
  config_key VARCHAR(100) NOT NULL,
  config_value JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY (company_id, instance_name, config_key)
);
```

### Tabela `evolution_instances` (PARCIALMENTE USADA)

Apenas para metadados locais. **O STATUS VEM DA API, NÃO DO BANCO!**

```sql
-- Colunas removidas (não usadas):
-- - status (agora vem da API)
-- - qr_code (agora vem da API)
-- - connected_at (agora verificado na API)
```

---

## Checklist de Verificação

Quando algo não funcionar, verifique:

1. ✅ O estado está sendo buscado de `/instance/connectionState/`?
2. ✅ Não está usando `connectionStatus` do `fetchInstances`?
3. ✅ A instância está com state = "open" (não "connecting")?
4. ✅ A API key está correta nas configurações da empresa?
5. ✅ O servidor Evolution está acessível?

---

## Problemas Comuns

### "Conectado" mas não envia mensagens

**Causa:** O `fetchInstances` retorna `connectionStatus: "open"` mas o estado real é "connecting".

**Solução:** Sempre verificar com `/instance/connectionState/{name}`.

### Instância presa em "connecting"

**Causa:** WhatsApp desconectou mas a instância não resetou.

**Solução:** 
1. Fazer logout: `DELETE /instance/logout/{name}`
2. Reiniciar: `POST /instance/restart/{name}`
3. Reconectar: `GET /instance/connect/{name}`

### QR Code não aparece

**Causa:** Instância já conectada ou API retornando erro.

**Verificar:**
```bash
curl -X GET "https://api.grifet.com/instance/connectionState/{name}" \
  -H "apikey: {sua_key}"
```

---

## Configuração da Empresa

As credenciais da Evolution API são armazenadas na tabela `companies`:

- `evolution_server_url`: URL base da API (ex: https://api.grifet.com)
- `evolution_api_key`: Chave de autenticação

---

## Resumo Final

```
┌────────────────────────────────────────────────────────────────┐
│  REGRA DE OURO                                                  │
│                                                                  │
│  Para saber se uma instância está REALMENTE conectada:          │
│                                                                  │
│  GET /instance/connectionState/{instanceName}                   │
│  → Se state === "open"  →  CONECTADA                            │
│  → Qualquer outra coisa →  NÃO CONECTADA                        │
│                                                                  │
│  NUNCA usar connectionStatus do fetchInstances!                 │
└────────────────────────────────────────────────────────────────┘
```
