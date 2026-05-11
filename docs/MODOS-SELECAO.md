# Sistema de Personalização — Modos de Seleção

> Documento técnico completo sobre os 3 modos de seleção disponíveis para grupos de personalização de produtos.
> Este documento cobre: conceito de cada modo, fluxo de dados completo, exibição em cada saída, ocultação de duplicados e cálculo de preços.

---

## Visão Geral

Cada produto pode ter múltiplos **grupos de personalização**. Cada grupo opera em um dos 3 modos abaixo. **Os modos são completamente independentes** — possuem caminhos de código distintos no backend, frontend, carrinho, checkout e mensagens de notificação.

| Modo no Admin | Nome Interno (`type`) | Caso de Uso |
|---|---|---|
| Adicionar ingredientes livremente | `extra` (ou `qty`) | Adicionais com stepper (ex: bacon extra, molho) |
| Escolher ingrediente | `single` (originado de `choice`) | Trocar ingrediente incluso (ex: queijo mussarela → cheddar) |
| Montagem (açaí, poke...) | `pool` | Monte seu prato com cota gratuita + extras pagos |

---

## 1. MODO EXTRA (Adicionar ingredientes livremente)

### 1.1. Conceito
O cliente pode adicionar ou remover ingredientes individualmente. Cada item tem seu próprio stepper (controle +/-) com `min` e `max` independentes. O preço é cobrado apenas quando a quantidade ultrapassa o `default_qty` (quantidade padrão incluída).

### 1.2. Configuração no Admin
- **mode**: (vazio/padrão) — qualquer grupo que não seja `choice` nem `pool` vira `extra`
- **Campos por item**: `min_qty`, `max_qty`, `default_qty`, `sale_price`, `is_default`
- Se `is_default=1`, o item começa com `default_qty` unidades selecionadas

### 1.3. Fluxo de Dados

#### Model (`ProductCustomization::loadForPublic`)
```
type = 'extra'
items[].min = min_qty     (default 0)
items[].max = max_qty     (default 99)
items[].qty = default_qty (quantidade inicial visível no stepper)
items[].default_qty = default_qty
items[].sale_price = preço por unidade extra
```

#### Frontend (`customization.php`)
- **Container CSS**: `.list` (class genérica, não confundível com `.single-group` ou `.pool-group`)
- **HTML por item**: `.row` com `.stepper` (botões dec/inc e `.st-val`)
- **Form field**: `custom_qty[{grupoIndex}][{itemIndex}]` → valor inteiro da quantidade
- **JS handler**: Seleciona `.list .stepper .st-btn`, clamp entre `data-min` e `data-max`
- **Preço**: Estático no PHP, não recalcula em JS

#### Controller Save (`PublicProductController::saveCustomization`)
```php
// Lê custom_qty[grupo][item] do POST
// IGNORA grupos com type 'single' ou 'addon' (filtra explicitamente)
// Aplica clamp: val = max(min, min(max, val))
```

#### Carrinho (`PublicCartController::expandCustomization`)
```php
// Branch: else (último branch do if/elseif chain)
// type guardado como 'qty'
deltaQty = qty - default_qty
linePrice = deltaQty > 0 ? sale_price * deltaQty : 0
// Se qty=0 e default_qty>0 → marcado como removed=true (remoção)
```

#### Checkout (`checkout.php` customItems section)
```
// Caminho: seção "--- lógica exclusiva para modo EXTRA/QTY ---"
// Se deltaQty > 0 → mostra "+Nx Item" com preço (extra pago)
// Se deltaQty < 0 → mostra "Sem Item" (remoção)
// Se deltaQty = 0 e preço = 0 → NÃO mostra ("Incluso" é ignorado via continue)
// ⚠️ Itens inclusos NUNCA aparecem no modo Extra — diferente do modo Pool
```

#### Mensagem WhatsApp — Notificação da Loja (`PublicCartController` → `OrderMessageBuilder`)
```
// PublicCartController monta $customParts[] separados por \n (um por linha)
// OrderMessageBuilder::processSimpleCustomizationFormatted() parseia por \n ou ", "
// Cada item vira: - `+1x Bacon — R$ 3,00` (com backticks) ou - Sem Alface (sem backticks)
// Itens inclusos NÃO aparecem
```

#### Mensagem WhatsApp — Cliente (`checkout.php` DOM → `order-processing.php` JS)
```
// JS lê do DOM do checkout (.summary-item-detail spans)
// Reproduz exatamente o que o checkout renderiza — inclusive sem inclusos
```

### 1.4. Exemplo Prático
**Produto**: X-Burger  
**Grupo**: "Ingredientes" (modo extra)
| Item | default_qty | sale_price | Cliente escolhe qty | Resultado |
|---|---|---|---|---|
| Queijo | 1 | R$ 2,00 | 1 | Incluso (delta=0) |
| Bacon | 0 | R$ 3,00 | 2 | +2x Bacon → R$ 6,00 |
| Alface | 1 | R$ 0,00 | 0 | Sem Alface (remoção) |

---

## 2. MODO SINGLE (Escolher ingrediente)

### 2.1. Conceito
O cliente pode **trocar** o ingrediente padrão por outro do grupo, sem custo adicional pela primeira unidade. Funciona como radio button com quantidade opcional. 1 unidade é SEMPRE gratuita (inclusa no preço base do produto).

### 2.2. Configuração no Admin
- **mode**: `choice` → normalizado para `type: 'single'` internamente
- **Campos do grupo**: `min` (mínimo de seleções), `max` (máximo de seleções)
- **Campos por item**: `sale_price`, `default_qty`, `is_default`
- Se `max=1`: comportamento de radio (uma única escolha)
- Se `max>1`: múltiplas seleções permitidas

### 2.3. Fluxo de Dados

#### Model (`ProductCustomization::loadForPublic`)
```
type = 'single' (sempre, mesmo se veio como 'addon')
items[].default_qty = default_qty do banco (default 1)
items[].sale_price = preço por unidade EXTRA (além da 1ª inclusa)
items[].selected = true apenas se is_default=1
```

#### Frontend (`customization.php`)
- **Container CSS**: `.single-group` (exclusivo deste modo)
- **HTML por item**: `.row.radio` com `.quantity-selector` (dot + controls inc/dec)
- **Form field**: `custom_single_items[{grupoIndex}][{itemIndex}]` → quantidade selecionada
- **JS handler**:
  - Click na row → `toggle()` ativa/desativa o seletor
  - Botão Dec → diminui qty; se chegar a 0, desativa
  - Botão Inc → aumenta qty; ativa automaticamente se não estiver ativo
- **Preço**: Estático e exibido no PHP

#### Controller Save (`PublicProductController::saveCustomization`)
```php
// Lê custom_single_items[grupo][item] = qty do POST
// OU fallback: custom_single[grupo] = idx (formato antigo)
// Armazena em: session['single'][gi] = [indices], session['singleQty'][gi][idx] = qty
```

#### Carrinho (`PublicCartController::expandCustomization`)
```php
// Branch: if ($type === 'single')
// 1 unidade é SEMPRE inclusa: default_qty mínimo = 1
deltaQty = qty - max(default_qty, 1)
linePrice = deltaQty > 0 ? sale_price * deltaQty : 0
// Trocar de queijo mussarela para cheddar = delta 0 = grátis
```

#### Checkout (`checkout.php` customItems section)
```
// Caminho: if ($isChoiceGroup && $isSelected)
// Mostra o item selecionado pelo nome, com preço do extra se houver
```

#### Mensagem WhatsApp — Notificação da Loja (`PublicCartController` → `OrderMessageBuilder`)
```
// Caminho: if ($isChoiceGroup && $isSelected && qty > 0)
// Mostra nome do item selecionado + preço se cobrado
```

#### Mensagem WhatsApp — Cliente (`checkout.php` DOM → `order-processing.php` JS)
```
// Herda do DOM do checkout — mostra o item selecionado com preço se houver
```

### 2.4. Exemplo Prático
**Produto**: X-Burger  
**Grupo**: "Tipo de queijo" (modo choice, max=1)
| Item | default_qty | sale_price | is_default | Cliente escolhe | Resultado |
|---|---|---|---|---|---|
| Mussarela | 1 | R$ 0,00 | 1 | (não seleciona) | — |
| Cheddar | 1 | R$ 2,00 | 0 | 1x | Cheddar → R$ 0,00 (incluso, troca) |
| Cheddar | 1 | R$ 2,00 | 0 | 3x | Cheddar → R$ 4,00 (1 incluso + 2 extras) |

---

## 3. MODO POOL (Montagem — açaí, poke...)

### 3.1. Conceito
O cliente monta seu prato escolhendo ingredientes de uma **cota compartilhada** (pool). Os primeiros `pool_free` itens (= `max` do grupo) são gratuitos. Itens além da cota são cobrados individualmente pelo `sale_price`.

### 3.2. Configuração no Admin
- **mode**: `pool`
- **Campos do grupo**: `min` (mínimo de itens), `max` (= `pool_free`, quantos itens são grátis)
- **Campos por item**: `sale_price` (preço por unidade extra além da cota)
- Não existe `default_qty` nem `is_default` — todos começam com qty=0

### 3.3. Fluxo de Dados

#### Model (`ProductCustomization::loadForPublic`)
```
type = 'pool'
pool_free = max (quantos itens totais são gratuitos)
min = poolMin
max = poolMax
items[].min = 0
items[].max = 99 (sem limite individual — o pool controla o total)
items[].qty = 0 (todos começam zerados)
items[].sale_price = preço do ingrediente quando extra
```

#### Frontend (`customization.php`)
- **Container CSS**: `.pool-group` (exclusivo deste modo)
- **Contador visual**: `.pool-counter` mostra `X/Y inclusos` + badge de extras
- **HTML por item**: `.row.pool-row` com `.stepper` e `data-price`
- **Form field**: `custom_qty[{grupoIndex}][{itemIndex}]` → quantidade do item
- **JS handler** (exclusivo e mais complexo):
  - Stepper buttons incrementam/decrementam
  - Após cada mudança, recalcula TODO o pool:
    ```
    sumTotal = soma de todas as quantidades do grupo
    Para cada item em ordem:
      freeSlots = max(0, poolFree - running)
      paidQty = max(0, qty - freeSlots)
      running += qty
    ```
  - Atualiza label por item: "Incluso" vs "R$ X,XX · extra"
  - Atualiza contador: "3/4 inclusos" + "+2 extras"
  - Atualiza CSS: `.charged` (vermelho) vs `.free` (verde)

#### Controller Save (`PublicProductController::saveCustomization`)
```php
// Lê custom_qty[grupo][item] do POST (mesmo campo que extra)
// Discrimina pelo type: IGNORA groups com type 'single'/'addon'
// Pool: valida min/max por item (clamp)
// Pool minimum é soft-enforced (comentário no código: "frontend should prevent")
```

#### Carrinho (`PublicCartController::expandCustomization`)
```php
// Branch: elseif ($type === 'pool') — exclusivo
poolFree = grupo.pool_free (ou grupo.max)
Para cada item selecionado (qty > 0):
  freeSlots = max(0, poolFree - totalUnitsAcumulado)
  freeQty = min(qty, freeSlots)
  paidQty = qty - freeQty
  linePrice = paidQty * sale_price
  totalUnitsAcumulado += qty
// Dados gerados: qty, unit_price, price, free_qty, paid_qty
```

#### Checkout (`checkout.php` customItems section)
```
// Caminho: if ($groupType === 'pool') — exclusivo
// Mostra "Nx Item" com preço se pago, sem preço se incluso
```

#### Mensagem WhatsApp — Notificação da Loja (`PublicCartController` → `OrderMessageBuilder`)
```
// PublicCartController monta $customParts[] para pool:
//   Itens gratuitos: "Morango" (sem preço)
//   Itens pagos:     "Morango (+R$ 3,00)" (com preço)
// Separados por \n e parseados por processSimpleCustomizationFormatted()
```

#### Mensagem WhatsApp — Cliente (`checkout.php` DOM → `order-processing.php` JS)
```
// Herda do DOM do checkout — mostra TODOS os itens com qty > 0
// Preço aparece apenas para itens pagos
```

### 3.4. Exemplo Prático
**Produto**: Açaí 500ml (R$ 18,00)  
**Grupo**: "Complementos" (modo pool, pool_free=4)
| Item | sale_price | Cliente escolhe qty | Running total | Free/Paid | Resultado |
|---|---|---|---|---|---|
| Granola | R$ 2,00 | 2 | 2 | 2 free / 0 paid | Incluso |
| Leite condensado | R$ 2,00 | 1 | 3 | 1 free / 0 paid | Incluso |
| Morango | R$ 3,00 | 1 | 4 | 1 free / 0 paid | Incluso |
| Paçoca | R$ 2,50 | 2 | 6 | 0 free / 2 paid | +R$ 5,00 |

**Total**: R$ 18,00 (base) + R$ 5,00 (2x Paçoca extra) = **R$ 23,00**

---

## 4. Isolamento entre Modos (Diagrama de Fluxo)

### 4.1. Campos de Formulário (Nunca se misturam)

```
SINGLE (Escolher):    custom_single_items[grupo][item] = qty
POOL (Montagem):      custom_qty[grupo][item] = qty    ← discriminado por group.type
EXTRA (Adicionar):    custom_qty[grupo][item] = qty    ← discriminado por group.type
```

> Pool e Extra usam o mesmo campo `custom_qty`, mas o backend discrimina pelo `type` do grupo:
> `saveCustomization()` verifica `$gType === 'single' || $gType === 'addon'` e ignora esses.
> `expandCustomization()` usa `if/elseif/elseif/else` com tipos: `single → addon → pool → extra`.

### 4.2. Seletores CSS (Nunca colidem)

| Modo | Seletor JS | Classe do container |
|---|---|---|
| Single | `document.querySelectorAll('.single-group')` | `.single-group` |
| Pool | `document.querySelectorAll('.pool-group')` | `.pool-group` |
| Extra | `.list .row:not(.pool-row)` steppers | `.list` |

### 4.3. Branch Map no Backend

```
expandCustomization() - PublicCartController.php
├── if (type === 'single')       → lê customData['single']    → output type: 'single'
├── elseif (type === 'addon')    → lê customData['choice']    → output type: 'addon'
├── elseif (type === 'pool')     → lê customData['qty']       → output type: 'pool'
└── else                         → lê customData['qty']       → output type: 'qty'

processCustomizationGroups() - OrderMessageBuilder.php
├── if (removed)                 → "Sem X"                     → continue
├── if (isChoiceGroup && ...)    → nome do item selecionado    → continue
├── elseif (type === 'pool')     → "Nx Item" com paid_qty      → continue
├── if (deltaQty < 0)           → "Sem X" (remoção por delta) → continue
└── else                         → "+Nx Item" ou "Incluso"

checkout.php customItems
├── if (removed)                 → "Sem X"                     → continue
├── if (isChoiceGroup && ...)    → item selecionado            → continue
├── if (type === 'pool')         → "Nx Item" com preço         → continue
├── if (deltaQty < 0)           → "Sem X"                     → continue
└── else                         → lógica delta/incluso/preço (EXTRA/QTY only)
```

### 4.4. Dados Gerados por Modo (Campos exclusivos)

| Campo | Extra | Single | Pool |
|---|---|---|---|
| `qty` | ✅ | ✅ | ✅ |
| `unit_price` | ✅ | ✅ | ✅ |
| `price` | ✅ | ✅ | ✅ |
| `default_qty` | ✅ | ✅ | ❌ |
| `delta_qty` | ✅ | ✅ | ❌ |
| `removed` | ✅ | ❌ | ❌ |
| `free_qty` | ❌ | ❌ | ✅ |
| `paid_qty` | ❌ | ❌ | ✅ |

---

## 5. Arquivos Relevantes por Modo

### Camada Model
- `app/models/ProductCustomization.php` → `loadForPublic()` (linhas 112-280)
- `app/models/ProductCustomization.php` → `normalizeGroups()` (linhas 290-400)

### Camada Controller
- `app/controllers/PublicProductController.php` → `saveCustomization()` (linhas 261-530)
- `app/controllers/PublicCartController.php` → `expandCustomization()` (linhas 730-990)

### Camada View
- `app/views/public/customization.php` → HTML e JS dos 3 modos
- `app/views/public/checkout.php` → Display no resumo (linhas 1460-1560)
- `app/views/public/cart.php` → Display no carrinho

### Camada Notificação
- `app/helpers/OrderMessageBuilder.php` → `processCustomizationGroups()`
- `app/services/OrderNotificationService.php` → `generateStandardOrderMessage()`
- `app/views/public/checkout_success.php` → Mensagem WhatsApp do cliente

---

## 6. Garantias de Isolamento

1. **Frontend**: Cada modo usa classes CSS exclusivas (`.single-group`, `.pool-group`, `.list`), event listeners separados, e form fields distintos.

2. **Save (Controller)**: `custom_single_items` é processado apenas para single. `custom_qty` é processado para pool e extra, mas o backend verifica `group.type` e ignora tipos incompatíveis.

3. **Expand (Carrinho)**: Chain `if/elseif/elseif/else` garante que cada item entra em exatamente UM branch. Sem fall-through.

4. **Display (Checkout/Mensagens)**: Cada modo tem seu próprio `if` com `continue`, impedindo processamento duplo. Pool e Choice são tratados antes da lógica genérica de Extra.

5. **Dados**: Cada modo gera campos de dados diferentes (`delta_qty` vs `paid_qty`), tornando impossível que a lógica de um modo interprete incorretamente os dados de outro.

---

## 7. Ocultação de Duplicados (`hide_duplicates`)

### 7.1. Conceito
Quando um produto tem múltiplos grupos de personalização (ex: "Monte seu burger" + "Turbine seu Woll"), o mesmo ingrediente pode existir nos dois grupos. Com `hide_duplicates=1` ativado num grupo, itens cujo `ingredient_id` já apareça em **qualquer outro grupo sem a flag** serão automaticamente ocultados na tela de personalização.

### 7.2. Configuração
- **No Template de Personalização**: Toggle "Ocultar ingredientes duplicados" na tela de edição do template
- **No Banco**: Coluna `hide_duplicates` na tabela `product_custom_groups` (TINYINT, 0 ou 1)
- **Quando um template é aplicado** ao produto: O valor é copiado para `product_custom_groups`

### 7.3. Lógica de Filtragem (`ProductCustomization::loadForPublic`)

```
Passo 1: Percorre TODOS os grupos SEM hide_duplicates
         → Coleta todos os ingredient_ids desses grupos em $existingIngredientIds

Passo 2: Para cada grupo COM hide_duplicates=1
         → Remove itens cujo ingredient_id existe em $existingIngredientIds
         → Se o grupo ficar vazio, é removido completamente
```

**Exemplo prático**:
- Grupo "Monte o burger" (hide_dup=0): Pão Brioche, Cebola, Molho Woll, Burger 90g
- Grupo "Turbine seu Woll" (hide_dup=1): Alface, Bacon, Cebola, Burger 90g, Molho Cheddar, Farofa
- **Resultado**: "Turbine" exibe apenas Alface, Bacon, Molho Cheddar, Farofa (Cebola e Burger 90g ocultados)

### 7.4. Persistência
O campo `hide_duplicates` é:
- ✅ Lido do banco em `fetchGroups()`
- ✅ Salvo pelo `save()` no INSERT de `product_custom_groups`
- ✅ Preservado pelo `normalizeGroups()` no array normalizado
- ✅ Copiado quando um template é aplicado via `CustomizationTemplate::applyToProduct()`

---

## 8. Exibição por Modo em Cada Saída

Cada modo tem regras distintas sobre quais itens aparecem e como. O preço na **linha principal do item** é sempre o **preço base** (sem extras de personalização), e os extras aparecem listados abaixo como sub-itens.

### 8.1. Checkout DOM (checkout.php) → Mensagem WhatsApp do Cliente (order-processing.php)

O JS lê diretamente do DOM (`.summary-item-detail` spans) e reproduz na mensagem. Portanto, o que aparece no checkout é exatamente o que vai na mensagem do cliente.

| Modo | O que mostra | Preço do sub-item |
|---|---|---|
| **Extra/qty** | Apenas extras pagos (+Nx Item) e remoções (Sem X). **NÃO mostra inclusos.** | `R$ X,XX` (preço do extra) |
| **Single/choice** | O item selecionado pelo cliente. | `R$ X,XX` se houver preço extra, senão nada |
| **Pool** | **TODOS** os itens com qty > 0 (gratuitos + pagos). | `R$ X,XX` para pagos, sem preço para inclusos |

### 8.2. Notificação da Loja (Evolution API — OrderNotificationService + PublicCartController)

O `PublicCartController` monta `$customParts` (texto) separados por `\n`. O `OrderMessageBuilder::processSimpleCustomizationFormatted` parseia e renderiza com `- ` prefixo e backticks.

| Modo | O que mostra | Formato |
|---|---|---|
| **Extra/qty** | Extras pagos e remoções. **NÃO mostra inclusos.** | `- \`+1x Bacon — R$ 3,00\`` |
| **Single/choice** | O item selecionado. | `- \`Queijo Cheddar — R$ 2,00\`` ou `- Queijo Cheddar` |
| **Pool** | **TODOS** os itens com qty > 0. | `- \`Morango — R$ 3,00\`` (pago) ou `- Granola` (incluso) |

### 8.3. Fallback WhatsApp (checkout_success.php)

| Modo | O que mostra | Formato |
|---|---|---|
| **Extra/qty** | Extras pagos (➕) e remoções (❌). **NÃO mostra inclusos.** | `➕ Bacon (+R$ 3,00)` |
| **Single/choice** | O item selecionado (✅). | `✅ Queijo Cheddar (+R$ 2,00)` |
| **Pool** | **TODOS** os itens com qty > 0 (✅). | `✅ Morango (+R$ 3,00)` ou `✅ Granola` |

### 8.4. Preço na Linha Principal do Produto

Em **todas** as saídas, o preço na linha principal é calculado como:

```
displayPrice = (unit_price - customization_delta - component_swap_extra) × qty
```

Onde:
- `unit_price` = preço total hidratado (base + extras de personalização + extras de combo)
- `customization_delta` = soma dos extras de personalização (`total_delta`)
- `component_swap_extra` = extra por troca de componente de combo

Isso garante que o preço do produto aparece como preço base, e os extras são listados individualmente nos sub-itens.
