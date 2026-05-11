# Integração iFood - Multi Menu

## Visão Geral

A integração com o iFood permite receber pedidos diretamente do aplicativo iFood no painel administrativo do Multi Menu. **Os pedidos do iFood aparecem junto com os demais pedidos na lista de pedidos principal**, identificados com um badge vermelho "iFood".

## Funcionalidades

- ✅ Recebimento automático de pedidos
- ✅ Confirmação/cancelamento de pedidos
- ✅ Atualização de status (pronto, despachado)
- ✅ Criação automática de pedido local na tabela `orders`
- ✅ **Pedidos unificados** - iFood aparece na mesma lista de pedidos
- ✅ **Badge de origem** - Identificação visual (iFood, Site, Manual)
- ✅ **Filtro por origem** - Filtrar pedidos por fonte
- ✅ Notificações push para novos pedidos
- ✅ Mapeamento de produtos iFood <-> Local
- ✅ Suporte a entrega iFood e própria

## Configuração

### 1. Criar conta no iFood Developer

1. Acesse [developer.ifood.com.br](https://developer.ifood.com.br)
2. Crie uma conta com e-mail que **não esteja** associado ao Portal do Parceiro
3. Crie um aplicativo
4. Anote o **Client ID** e **Client Secret**

### 2. Solicitar permissões

Solicite acesso às seguintes APIs:
- **Order** - Para receber e gerenciar pedidos
- **Merchant** - Para gerenciar status da loja
- **Events** - Para receber eventos via polling

### 3. Configurar no Multi Menu

1. Acesse o painel admin: `/admin/{slug}/ifood/config`
2. Preencha:
   - **Client ID**: ID do seu aplicativo
   - **Client Secret**: Chave secreta
   - **Merchant ID**: Selecione sua loja após testar a conexão
3. Ative a integração

### 4. Configurar Polling Automático

Adicione ao crontab para buscar pedidos a cada minuto:

```bash
* * * * * php /home/ubuntu/multi-menu/scripts/ifood_polling_cron.php >> /var/log/ifood_polling.log 2>&1
```

## Fluxo de Pedidos

```
iFood                          Multi Menu
  |                                |
  |-- Pedido PLACED ------------->|-- Cria pedido local
  |                                |-- Envia notificação push
  |                                |
  |<-- Confirmar (CFM) ------------|-- Admin confirma
  |                                |
  |<-- Pronto (RTP) --------------|-- Admin marca pronto
  |                                |
  |-- Entregador designado ------>|-- Atualiza status
  |                                |
  |-- Concluído (CON) ----------->|-- Fecha pedido
```

## Estrutura do Banco de Dados

### Tabela: `ifood_integrations`
Armazena configurações da integração por empresa.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| company_id | INT | ID da empresa |
| client_id | VARCHAR | Client ID do iFood |
| client_secret | VARCHAR | Secret (encriptado) |
| merchant_id | VARCHAR | ID da loja no iFood |
| access_token | TEXT | Token atual (encriptado) |
| is_active | BOOL | Se integração está ativa |
| auto_confirm | BOOL | Confirmar automaticamente |

### Tabela: `ifood_orders`
Armazena pedidos recebidos do iFood.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| ifood_order_id | VARCHAR | UUID do pedido no iFood |
| order_id | INT | ID do pedido local (orders) |
| status | VARCHAR | Status atual no iFood |
| items | JSON | Itens do pedido |
| payments | JSON | Dados de pagamento |
| delivery_address | JSON | Endereço de entrega |

### Tabela: `ifood_events_log`
Log de todos os eventos recebidos para auditoria.

### Tabela: `ifood_product_mapping`
Mapeamento de produtos iFood <-> produtos locais.

## API Endpoints

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/admin/{slug}/ifood/config` | Página de configuração |
| POST | `/admin/{slug}/ifood/config/save` | Salvar configuração |
| GET | `/admin/{slug}/ifood/orders` | Lista de pedidos |
| GET | `/admin/{slug}/ifood/orders/{id}` | Detalhes do pedido |
| POST | `/admin/{slug}/ifood/orders/{id}/confirm` | Confirmar pedido |
| POST | `/admin/{slug}/ifood/orders/{id}/ready` | Marcar pronto |
| POST | `/admin/{slug}/ifood/orders/{id}/dispatch` | Despachar |
| POST | `/admin/{slug}/ifood/orders/{id}/cancel` | Cancelar |
| POST | `/admin/{slug}/ifood/poll` | Buscar pedidos manualmente |
| POST | `/admin/{slug}/ifood/test-connection` | Testar conexão |

## Status do Pedido

| Status iFood | Status Local | Descrição |
|--------------|--------------|-----------|
| PLACED | pending | Novo pedido |
| CONFIRMED | confirmed | Confirmado/Em preparo |
| READY_TO_PICKUP | ready | Pronto para retirada |
| DISPATCHED | delivering | Saiu para entrega |
| CONCLUDED | delivered | Entregue |
| CANCELLED | cancelled | Cancelado |

## Segurança

- Client Secret é armazenado encriptado (AES-256-CBC)
- Access Token é renovado automaticamente
- Todas as rotas requerem autenticação admin

## Troubleshooting

### Token inválido
- Verifique se Client ID e Secret estão corretos
- Tente gerar novo token via "Testar Conexão"

### Pedidos não aparecem
- Verifique se integração está ativa
- Execute polling manual
- Verifique logs em `/storage/logs/`

### Erro "Access denied"
- Verifique permissões do aplicativo no portal iFood
- Confirme que Merchant ID está correto

## Referências

- [Documentação iFood](https://developer.ifood.com.br/pt-BR/docs/getting-started)
- [API Reference](https://developer.ifood.com.br/pt-BR/docs/references)
- [Changelog](https://developer.ifood.com.br/pt-BR/docs/changelog)
