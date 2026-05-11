# Sistema de Personalização — Documentação Detalhada

## Visão Geral

O sistema de personalização permite que clientes modifiquem ingredientes de produtos (simples e combos). Suporta **adicionar**, **remover**, **trocar** ingredientes e **montar** bowls (açaí/poke).

Existem quatro modos de personalização:
- **extra** — Stepper com min/max por item (ex: "2x Bacon Extra")
- **single** — Radio/choice com quantidade (ex: "Escolha o pão")
- **addon** — Checkbox multi-seleção (ex: "Adicionais")
- **pool** — Montagem compartilhada com total livre (ex: "Monte seu Açaí")

---

## Schema do Banco de Dados

### Personalização de Produtos Simples

```sql
CREATE TABLE product_custom_groups (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    product_id    INT NOT NULL,         -- FK → products.id
    name          VARCHAR(100),         -- "Ingredientes", "Extras"
    type          ENUM('single','extra','addon','component','pool'),
    min_qty       INT DEFAULT 0,        -- mínimo de seleções no grupo
    max_qty       INT DEFAULT 1,        -- máximo de seleções no grupo
    hide_duplicates TINYINT DEFAULT 0,  -- ocultar ingredientes já em outros grupos
    sort_order    INT DEFAULT 0
);

CREATE TABLE product_custom_items (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    group_id      INT NOT NULL,         -- FK → product_custom_groups.id
    ingredient_id INT DEFAULT NULL,     -- FK → ingredients.id (opcional)
    label         VARCHAR(100),         -- nome exibido ao cliente
    delta         DECIMAL(10,2) DEFAULT 0.00, -- acréscimo no preço
    is_default    TINYINT DEFAULT 0,    -- 1 = já vem no produto
    default_qty   INT DEFAULT 1,        -- quantidade padrão incluída
    min_qty       INT DEFAULT 0,        -- mínimo permitido
    max_qty       INT DEFAULT 1,        -- máximo permitido
    sort_order    INT DEFAULT 0
);
```

### Personalização de Combos

```sql
CREATE TABLE combo_groups (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,            -- FK → products.id (produto combo)
    name       VARCHAR(100),            -- "Escolha o Hambúrguer"
    type       ENUM('single','remove','add','swap','component','extra','addon'),
    min_qty    INT DEFAULT 1,
    max_qty    INT DEFAULT 1,
    sort       INT DEFAULT 0
);

CREATE TABLE combo_group_items (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    group_id          INT NOT NULL,     -- FK → combo_groups.id
    simple_product_id INT NOT NULL,     -- FK → products.id (produto simples)
    delta_price       DECIMAL(10,2) DEFAULT 0.00,
    is_default        TINYINT DEFAULT 0,
    allow_customize   TINYINT DEFAULT 1, -- permite personalizar o componente
    sort              INT DEFAULT 0
);
```

---

## Fluxo de Dados Completo

### 1. Configuração (Admin)

O admin configura personalização em `AdminProductController` → view `admin/product-form.php`.

**Salvamento:**
```
POST → AdminProductController → ProductCustomization::sanitizePayload() → ProductCustomization::save()
```

`save()` usa transação:
1. Deleta `product_custom_items` do produto
2. Deleta `product_custom_groups` do produto
3. Insere novos grupos e itens

### 2. Carregamento para o Front (Público)

```
PublicProductController::show() → ProductCustomization::loadForPublic($productId)
```

`loadForPublic()` transforma os dados brutos do banco:

| Modo DB | Processamento | Resultado |
|---------|--------------|-----------|
| `pool` | Calcula `pool_free` (= max do grupo). Item min=0, max=99. `sale_price` do ingrediente é usado para cobrar extras. | Steppers com trava no total |
| `single`/`addon` | Min/max de seleções no grupo. Cada item tem `default_qty`. Tipo final = `single` (sempre). | Radio/checkbox com quantidade |
| `extra` (tudo-mais) | Min/max por item. `default_qty` do item padrão. Se todos 1..1 → tipo `single`, senão → `extra`. | Steppers individuais |

**Filtros aplicados:**
- Ingredientes inativos são removidos
- `hide_duplicates`: Itens que já existem em outro grupo do mesmo produto são ocultados

### 3. View de Personalização

Arquivo: `app/views/public/customization.php`

Renderiza controles conforme o tipo do grupo:

| Tipo | Controle HTML | Campo POST |
|------|---------------|------------|
| `single` | Radio buttons com selector de quantidade | `custom_single_items[grupo][item]=qty` |
| `addon` | Checkboxes multi-select | `custom_choice[grupo][]=idx` |
| `extra` | Steppers (+/-) | `custom_qty[grupo][item]=qty` |
| `pool` | Steppers com barra de total | `custom_qty[grupo][item]=qty` |

Campos ocultos extras:
- `parent_id` — ID do produto combo (se componente de combo)
- `unit` — Índice da unidade (se combo com qty > 1)
- `total_units` — Total de unidades

### 4. Salvamento da Personalização (POST)

```
POST /{slug}/produto/{id}/customizar → PublicProductController::saveCustomization()
```

O controller:
1. Carrega `mods = ProductCustomization::loadForPublic($id)`
2. Processa POST separando em `$customSingle`, `$customSingleQty`, `$customQty`, `$customChoice`
3. Valida min/max contra as regras do grupo
4. Salva no `CartStorage` com chave contextualizada:

```php
// Chave contextualizada:
// Produto simples isolado:   "42"
// Componente de combo:       "100:42"          (parentId:productId)
// Componente unidade 2:      "100:42:unit2"    (parentId:productId:unitN)
```

### 5. Adição ao Carrinho

```
POST /{slug}/cart/add → PublicCartController::add()
```

#### Produto Simples

```php
$snapshot = $this->snapshotCustomization($productId);
// lê de CartStorage, sanitiza, monta array com single/singleQty/choice/qty
```

#### Produto Combo

```php
$comboSelection = $this->resolveComboSelection($product, $_POST);
// Para cada componente:
foreach ($selection as $groupId => $simpleIds) {
    // Aplica snapshotCustomization com parentId e unitIndex
    $snapshot = $this->snapshotCustomization($simpleId, $comboId, $unitIndex);
    // Expande customização para renderização
    $expanded = $this->expandCustomization($simpleId, $snapshot);
}
```

### 6. Snapshot de Personalização

`snapshotCustomization()` lê dados do `CartStorage` e sanitiza:

```php
private function snapshotCustomization(int $productId, ?int $parentId, ?int $unitIndex): ?array
{
    $raw = $this->storage->getCustomization($productId, null, $parentId, $unitIndex);
    
    // Resultado com 4 chaves:
    return [
        'single'    => [],   // [grupo => [idx, idx, ...]]
        'singleQty' => [],   // [grupo => [idx => qty, ...]]
        'choice'    => [],   // [grupo => [idx, idx, ...]]
        'qty'       => [],   // [grupo => [idx => qty, ...]]
    ];
}
```

**Regra crucial:** Items com `qty = 0` são preservados (representam remoções de ingredientes como "sem cebola"). Apenas `qty < 0` são descartados.

### 7. Expansão para Renderização

`expandCustomization()` transforma o snapshot em estrutura legível para o carrinho e pedido:

```php
private function expandCustomization(int $productId, ?array $customData): array
{
    return [
        'groups' => [
            [
                'name' => 'Ingredientes',
                'type' => 'qty',
                'items' => [
                    [
                        'name'        => 'Cebola',
                        'qty'         => 0,
                        'default_qty' => 1,
                        'delta_qty'   => -1,
                        'removed'     => true,    // ← remoção detectada
                        'price'       => 0.0,
                    ],
                    [
                        'name'        => 'Bacon Extra',
                        'qty'         => 2,
                        'default_qty' => 0,
                        'delta_qty'   => 2,
                        'removed'     => false,
                        'price'       => 6.00,
                    ],
                ],
            ],
        ],
        'total_delta'       => 6.00,
        'has_customization' => true,
    ];
}
```

**Lógica de preço por tipo:**

| Tipo | Cálculo de preço |
|------|-----------------|
| `single` | `(qty - default_qty) * unit_price` — Só cobra acima do padrão |
| `addon` | `sale_price` por item selecionado |
| `pool` | Primeiros `pool_free` itens grátis; extras cobram `sale_price` |
| `extra` | `(qty - default_qty) * unit_price` se `delta_qty > 0`; remoções = R$0 |

### 8. Exibição no Carrinho

Arquivo: `app/views/public/cart.php`

Para **produtos simples**:
```
Ingredientes:
  Cebola | Removido
  +2x Bacon Extra | +R$ 6,00
```

Para **combos** — mesma lógica, aplicada por componente:
```
Combo Família (1x)
  ├── Hambúrguer Clássico
  │     Cebola Caramelizada | Removido     ← remoção mostra "Removido"
  │     +1x Bacon | +R$ 3,00
  └── Batata Frita
        +1x Cheddar | +R$ 2,00
```

### 9. Mensagem do Pedido (WhatsApp/Impressão)

`OrderMessageBuilder::processCustomizationGroups()` formata para texto:

| Situação | Formato |
|----------|---------|
| Remoção (`removed=true` ou `delta_qty < 0`) | `  Sem Cebola` |
| Adição com preço | `  +2x Bacon Extra ... R$ 6,00` |
| Seleção (single/addon) | `  Cheddar` |
| Pool item | `  2x Morango` |

---

## Diferenças: Produto Simples vs Combo

| Aspecto | Produto Simples | Combo |
|---------|----------------|-------|
| **Onde configura** | `product_custom_groups` + `product_custom_items` | Componente herda de seu própio `product_custom_groups` |
| **Grupo de seleção** | Não tem | `combo_groups` + `combo_group_items` (escolha de componentes) |
| **allow_customize** | Sempre true | Flag por `combo_group_items.allow_customize` |
| **Chave no CartStorage** | `"42"` (productId) | `"100:42"` ou `"100:42:unit2"` |
| **Fluxo POST** | Direto: produto → customizar → carrinho | Combo → personalizar componente → volta ao combo → carrinho |
| **Multi-unidade** | Não suporta | Combo com qty>1: cada unidade tem personalização separada via `unitIndex` |
| **snapshotCustomization** | `($productId)` | `($simpleId, $comboId, $unitIndex)` |

### Fluxo Combo com Múltiplas Unidades

Quando um combo tem `qty > 1` (ex: 2x Combo Família):

1. Usuário seleciona componentes para cada unidade separadamente
2. Personalização de cada componente salva com chave única: `comboId:simpleId:unitN`
3. No carrinho, `unit_customizations` agrupa por unidade:

```json
{
    "unit_customizations": {
        "1": {
            "groups": [{"name": "Ingredientes", "items": [...]}],
            "total_delta": 3.00
        },
        "2": {
            "groups": [{"name": "Ingredientes", "items": [...]}],
            "total_delta": 0.00
        }
    }
}
```

---

## CartStorage — Armazenamento

### Camadas de Persistência (fallback)

1. **Redis** (primário) — `mm:cart:{sessionId}`, `mm:customizations:{sessionId}`
2. **$_SESSION** (secundário)
3. **MySQL** (backup) — Tabela `cart_sessions` com `cart_json` e `customizations_json`

### Recuperação de Sessão

Se a sessão PHP mudar (regeneração, GC), o cookie `mm_cart_sid` permite recuperar o carrinho da sessão anterior via Redis ou DB.

### Métodos Principais

| Método | Parâmetros | Descrição |
|--------|-----------|-----------|
| `setCustomization()` | productId, value, sessionId?, parentId?, unitIndex? | Salva personalização com chave contextualizada |
| `getCustomization()` | productId, sessionId?, parentId?, unitIndex? | Busca personalização pela chave |
| `removeCustomization()` | productId, sessionId?, parentId?, unitIndex? | Remove personalização |
| `getCustomizations()` | sessionId? | Retorna todas as personalizações da sessão |

---

## Modo Pool (Açaí/Poke)

Funciona diferente dos outros modos:

1. O grupo define `pool_free` = quantidade de itens grátis (= max do grupo)
2. Cada item tem stepper independente (min=0, max=99)
3. O total é a soma de todos os steppers
4. Primeiros `pool_free` itens são distribuídos pela ordem de seleção
5. Itens além do `pool_free` cobram `sale_price` do ingrediente

**Exemplo: Monte seu Açaí (pool_free=4)**

| Item | Qty | Gratuito | Pago | Preço |
|------|-----|----------|------|-------|
| Morango | 2 | 2 | 0 | R$ 0 |
| Banana | 1 | 1 | 0 | R$ 0 |
| Granola | 1 | 1 | 0 | R$ 0 |
| Leite Ninho | 1 | 0 | 1 | R$ 3,00 |
| **Total** | **5** | **4** | **1** | **R$ 3,00** |

---

## Diagrama de Fluxo Resumido

```
[Admin configura] → DB (product_custom_groups/items)
                          ↓
[Cliente abre produto] → ProductCustomization::loadForPublic()
                          ↓
[View customization.php] → Formulário (steppers/radios/checkboxes)
                          ↓
[POST customizar] → PublicProductController::saveCustomization()
                          ↓
[CartStorage] ← setCustomization(productId, data, parentId?, unitIndex?)
                          ↓
[POST add to cart] → snapshotCustomization() → expandCustomization()
                          ↓
[Cart view] → Renderiza grupos/itens com preços e remoções
                          ↓
[Finalizar pedido] → OrderMessageBuilder → WhatsApp / Impressão térmica
```
