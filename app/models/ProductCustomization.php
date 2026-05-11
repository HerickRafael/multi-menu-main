<?php

declare(strict_types=1);
// app/models/ProductCustomization.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/Ingredient.php';

class ProductCustomization
{
    /**
     * Normaliza os dados vindos do formulário do admin.
     * Retorna um array no formato ['enabled'=>bool,'groups'=>[...]].
     */
    public static function sanitizePayload(array $payload, int $companyId): array
    {
        $enabled = !empty($payload['enabled']);
        $groups  = [];

        if (!empty($payload['groups']) && is_array($payload['groups'])) {
            $groups = self::normalizeGroups($payload['groups'], $companyId);
        }

        if (!$groups) {
            $enabled = false;
        }

        return [
            'enabled' => $enabled,
            'groups'  => $groups,
        ];
    }

    /**
     * Persiste os grupos/itens de personalização de um produto.
     * Espera receber os dados já normalizados via sanitizePayload().
     */
    public static function save(int $productId, array $customization): void
    {
        $enabled = !empty($customization['enabled']) && !empty($customization['groups']);
        $groups  = $enabled ? $customization['groups'] : [];

        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Limpa vínculos e grupos anteriores
            $pdo->prepare('DELETE pci FROM product_custom_items pci
                              INNER JOIN product_custom_groups pcg ON pcg.id = pci.group_id
                             WHERE pcg.product_id = ?')
                ->execute([$productId]);

            $pdo->prepare('DELETE FROM product_custom_groups WHERE product_id = ?')
                ->execute([$productId]);

            if ($groups) {
                $insGroup = $pdo->prepare(
                    'INSERT INTO product_custom_groups (product_id, name, type, min_qty, max_qty, hide_duplicates, sort_order)
                     VALUES (?,?,?,?,?,?,?)'
                );
                $insItem = $pdo->prepare(
                    'INSERT INTO product_custom_items (group_id, ingredient_id, label, delta, is_default, default_qty, min_qty, max_qty, sort_order)
                     VALUES (?,?,?,?,?,?,?,?,?)'
                );

                foreach ($groups as $gIndex => $group) {
                    $insGroup->execute([
                        $productId,
                        $group['name'],
                        $group['type'],
                        $group['min'],
                        $group['max'],
                        $group['hide_duplicates'] ?? 0,
                        $group['sort_order'] ?? $gIndex,
                    ]);
                    $groupId = (int)$pdo->lastInsertId();

                    $items = $group['items'] ?? [];

                    foreach ($items as $iIndex => $item) {
                        $insItem->execute([
                            $groupId,
                            $item['ingredient_id'] ?? null,
                            $item['label'],
                            isset($item['delta']) ? (float)$item['delta'] : 0.00,
                            !empty($item['default']) ? 1 : 0,
                            (int)($item['default_qty'] ?? 1),
                            (int)($item['min_qty'] ?? 0),
                            (int)($item['max_qty'] ?? 1),
                            $item['sort_order'] ?? $iIndex,
                        ]);
                    }
                }
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Carrega grupos/itens para uso no formulário do admin.
     */
    public static function loadForAdmin(int $productId): array
    {
        return self::fetchGroups($productId);
    }

    /**
     * Verifica se o produto tem algum ingrediente padrão que está inativo.
     * Nesse caso, o produto inteiro deve ser ocultado do cardápio público.
     */
    public static function hasInactiveDefaultIngredient(int $productId): bool
    {
        $pdo = db();
        $sql = 'SELECT 1
                  FROM product_custom_items pci
                  JOIN product_custom_groups pcg ON pcg.id = pci.group_id
                  JOIN ingredients ing ON ing.id = pci.ingredient_id
                 WHERE pcg.product_id = ?
                   AND pci.is_default = 1
                   AND ing.active = 0
                 LIMIT 1';
        $st = $pdo->prepare($sql);
        $st->execute([$productId]);
        return (bool)$st->fetchColumn();
    }

    /**
     * Retorna IDs de produtos que devem ser ocultados porque possuem
     * ingrediente padrão inativo, filtrado por company_id.
     */
    public static function productIdsHiddenByIngredient(int $companyId): array
    {
        $pdo = db();
        $sql = 'SELECT DISTINCT pcg.product_id
                  FROM product_custom_items pci
                  JOIN product_custom_groups pcg ON pcg.id = pci.group_id
                  JOIN products p ON p.id = pcg.product_id
                  JOIN ingredients ing ON ing.id = pci.ingredient_id
                 WHERE p.company_id = ?
                   AND pci.is_default = 1
                   AND ing.active = 0';
        $st = $pdo->prepare($sql);
        $st->execute([$companyId]);
        return $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Carrega grupos/itens para uso no front público (product/customization).
     * Ingredientes inativos são filtrados automaticamente.
     */
    public static function loadForPublic(int $productId): array
    {
        $groups = self::fetchGroups($productId);

        // Filtrar ingredientes inativos dos grupos
        foreach ($groups as &$group) {
            $items = $group['items'] ?? [];
            $filtered = [];
            foreach ($items as $it) {
                if (empty($it['ingredient_id']) || !empty($it['ingredient_active'])) {
                    $filtered[] = $it;
                }
            }
            $group['items'] = $filtered;
        }
        unset($group);
        
        // Primeiro passo: coletar todos os ingredient_ids de grupos que NÃO têm hide_duplicates
        // Esses são os ingredientes "principais" que não devem aparecer em grupos com hide_duplicates
        $existingIngredientIds = [];
        foreach ($groups as $group) {
            // Grupos SEM hide_duplicates definem quais ingredientes já existem
            if (empty($group['hide_duplicates'])) {
                foreach ($group['items'] ?? [] as $item) {
                    if (!empty($item['ingredient_id'])) {
                        $existingIngredientIds[(int)$item['ingredient_id']] = true;
                    }
                }
            }
        }

        foreach ($groups as &$group) {
            $items = $group['items'] ?? [];

            if (!is_array($items) || !$items) {
                $group['items'] = [];
                $group['type']  = 'extra';
                continue;
            }
            
            // Se o grupo tem hide_duplicates ativado, filtrar itens que já existem em outros grupos
            if (!empty($group['hide_duplicates']) && !empty($existingIngredientIds)) {
                $filteredItems = [];
                foreach ($items as $item) {
                    $ingredientId = $item['ingredient_id'] ?? null;
                    // Manter o item apenas se não existir em outros grupos
                    if (empty($ingredientId) || !isset($existingIngredientIds[(int)$ingredientId])) {
                        $filteredItems[] = $item;
                    }
                }
                $items = $filteredItems;
                $group['items'] = $items;
                
                // Se todos os itens foram filtrados, pular este grupo
                if (empty($items)) {
                    continue;
                }
            }

            $gType = $group['type'] ?? 'extra';

            // Modo pool (açaí) — stepper por item, trava no total do grupo
            // Primeiros pool_free (= max do grupo) itens são grátis;
            // além disso o cliente pode adicionar mais, cobrando sale_price.
            if ($gType === 'pool') {
                $poolMin = isset($group['min']) ? max(0, (int)$group['min']) : 0;
                $poolMax = isset($group['max']) ? (int)$group['max'] : 4;
                if ($poolMax < 1) $poolMax = 4;
                if ($poolMax < $poolMin) $poolMax = $poolMin;
                $group['min']       = $poolMin;
                $group['max']       = $poolMax;
                $group['pool_free'] = $poolMax; // quantos itens são grátis

                foreach ($items as &$item) {
                    $item['name']  = $item['label'];
                    $item['delta'] = 0.0;
                    $item['img']   = $item['img'] ?? ($item['image_path'] ?? null);
                    $item['min']   = 0;
                    $item['max']   = 99; // sem trava individual — o pool controla
                    $item['qty']   = 0;
                    // sale_price já foi carregado pelo fetchGroups via ing.sale_price.
                    // A chave no dict é 'sale_price', não 'ingredient_sale_price'.
                    // Garantir float; se 0 usar delta original do item como fallback.
                    $item['sale_price'] = (float)($item['sale_price'] ?? 0.0) > 0.0
                        ? (float)$item['sale_price']
                        : (float)($item['delta'] ?? 0.0);
                }
                unset($item);
                $group['items'] = $items;
                continue;
            }

            // Modo choice (single/addon) - sempre usa radio com quantidade
            if ($gType === 'single' || $gType === 'addon') {
                $minSel = isset($group['min']) ? max(0, (int)$group['min']) : 0;
                $maxSel = isset($group['max']) ? (int)$group['max'] : 1;

                if ($maxSel < 1) {
                    $maxSel = 1;
                }

                if ($maxSel < $minSel) {
                    $maxSel = $minSel;
                }
                $group['min'] = $minSel;
                $group['max'] = $maxSel;
                // Sempre usar tipo single para ter o radio com quantidade
                $group['type'] = 'single';

                foreach ($items as &$item) {
                    $item['name']       = $item['label'];
                    $item['img']        = $item['img'] ?? ($item['image_path'] ?? null);
                    $item['sale_price'] = isset($item['sale_price']) ? (float)$item['sale_price'] : 0.0;
                    // Passar default_qty para o frontend (quantidade inicial ao selecionar)
                    $item['default_qty'] = isset($item['default_qty']) ? (int)$item['default_qty'] : 1;
                    // Marcar como selecionado APENAS se is_default=1
                    $item['selected']   = !empty($item['default']);
                }
                unset($item);

                $group['items'] = $items;
                continue;
            }

            $isSingle = true;

            foreach ($items as &$item) {
                $item['name']  = $item['label'];
                $item['delta'] = isset($item['delta']) ? (float)$item['delta'] : 0.0;
                $item['img']   = $item['img'] ?? ($item['image_path'] ?? null);

                // Normalização robusta de min/max/qty
                $min = isset($item['min_qty']) ? (int)$item['min_qty'] : 0;
                $max = isset($item['max_qty']) ? (int)$item['max_qty'] : $min;

                if ($max < $min) {
                    $max = $min;
                }

                if ($max <= 0) {
                    $max = max($min, 99);
                }

                $defaultQty = !empty($item['default']) ? (int)($item['default_qty'] ?? $min) : $min;

                if ($defaultQty < $min) {
                    $defaultQty = $min;
                }

                if ($max > 0 && $defaultQty > $max) {
                    $defaultQty = $max;
                }

                $item['min']         = $min;
                $item['max']         = $max;
                $item['qty']         = $defaultQty;
                $item['default_qty'] = $defaultQty;

                // Disponibiliza o preço de venda do ingrediente para a UI pública
                $item['sale_price'] = isset($item['sale_price']) ? (float)$item['sale_price'] : 0.0;

                if ($item['min'] !== 1 || $item['max'] !== 1) {
                    $isSingle = false;
                }
            }
            unset($item);

            $group['items'] = $items;
            // Se todos os itens do grupo são 1..1, tratamos como 'single'; caso contrário preserva tipo original
            $group['type'] = $isSingle ? 'single' : 'extra';
        }
        unset($group);

        // Remover grupos que ficaram sem itens (após filtragem de duplicados)
        $groups = array_filter($groups, function($g) {
            return !empty($g['items']);
        });

        return array_values($groups);
    }

    /**
     * Normaliza grupos vindos do formulário do admin.
     */
    private static function normalizeGroups(array $groups, int $companyId): array
    {
        $normalized = [];
        $gSort = 0;

        $orderedGroups = [];

        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }
            $group['_order'] = isset($group['sort_order']) ? (int)$group['sort_order'] : count($orderedGroups);
            $orderedGroups[] = $group;
        }

        usort($orderedGroups, function ($a, $b) {
            return ($a['_order'] ?? 0) <=> ($b['_order'] ?? 0);
        });

        foreach ($orderedGroups as $group) {
            if (!is_array($group)) {
                continue;
            }
            unset($group['_order']);

            $name = trim((string)($group['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $itemsRaw = $group['items'] ?? [];

            if (!is_array($itemsRaw)) {
                $itemsRaw = [];
            }

            $items = [];
            $seenIngredients = [];
            $iSort = 0;

            $modeRaw = $group['mode'] ?? 'extra';
            $mode = in_array($modeRaw, ['choice', 'pool'], true) ? $modeRaw : 'extra';
            $choiceCfg = is_array($group['choice'] ?? null) ? $group['choice'] : [];
            $poolCfg   = is_array($group['pool'] ?? null) ? $group['pool'] : [];

            // Min/max de seleções (choice) ou total (pool)
            $cfgSource = $mode === 'pool' ? $poolCfg : $choiceCfg;
            $cfgMin = isset($cfgSource['min']) ? max(0, (int)$cfgSource['min']) : 0;
            $cfgMax = isset($cfgSource['max']) ? (int)$cfgSource['max'] : ($mode === 'pool' ? 4 : 1);

            if ($cfgMax < 1) {
                $cfgMax = 1;
            }

            if ($cfgMax < $cfgMin) {
                $cfgMax = $cfgMin;
            }

            foreach ($itemsRaw as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $ingredientId = isset($item['ingredient_id']) ? (int)$item['ingredient_id'] : 0;

                if ($ingredientId <= 0) {
                    continue;
                }

                $ingredient = Ingredient::findForCompany($companyId, $ingredientId);

                if (!$ingredient) {
                    continue;
                }

                // Evita duplicar o mesmo ingrediente no mesmo grupo
                if (isset($seenIngredients[$ingredientId])) {
                    continue;
                }
                $seenIngredients[$ingredientId] = true;

                // Usa min/max vindos do formulário para o item (com saneamento)
                $minQty = isset($item['min_qty']) ? max(0, (int)$item['min_qty']) : 0;
                $maxQty = isset($item['max_qty']) ? (int)$item['max_qty'] : $minQty;

                if ($mode === 'choice') {
                    $minQty = 0;
                    $maxQty = 1;
                }

                if ($mode === 'pool') {
                    // No pool, cada item vai de 0 até o max do grupo
                    $minQty = 0;
                    $maxQty = $cfgMax;
                }

                if ($maxQty < $minQty) {
                    $maxQty = $minQty;
                }

                $isDefault  = !empty($item['default']) && (string)$item['default'] !== '0';
                $defaultQty = isset($item['default_qty']) ? (int)$item['default_qty'] : $minQty;

                // No modo choice, permitir qualquer valor de default_qty (não limitar a 0 ou 1)
                if ($defaultQty < 0) {
                    $defaultQty = 0;
                }

                $items[] = [
                    'ingredient_id' => $ingredientId,
                    'label'         => $ingredient['name'],
                    'delta'         => 0.0, // ajuste aqui se a UI enviar delta
                    'default'       => $isDefault,
                    'default_qty'   => $defaultQty, // Sempre salvar o valor definido pelo usuário
                    'min_qty'       => $minQty,
                    'max_qty'       => $maxQty,
                    'image_path'    => $ingredient['image_path'] ?? null,
                    'sort_order'    => $iSort++,
                ];
            }

            if (!$items) {
                continue;
            }

            $groupType = 'extra';
            $groupMin  = 0;
            $groupMax  = 99;

            if ($mode === 'choice') {
                // Modo choice sempre usa tipo single (radio com quantidade)
                $groupType = 'single';
                $groupMin  = $cfgMin;
                $groupMax  = $cfgMax;
            } elseif ($mode === 'pool') {
                // Modo pool: limite no total de unidades do grupo (açaí)
                $groupType = 'pool';
                $groupMin  = $cfgMin;
                $groupMax  = $cfgMax;
            }

            $normalized[] = [
                'name'       => $name,
                'type'       => $groupType,
                'min'        => $groupMin,
                'max'        => $groupMax,
                'hide_duplicates' => !empty($group['hide_duplicates']) ? 1 : 0,
                'sort_order' => $gSort++,
                'items'      => $items,
            ];
        }

        return $normalized;
    }

    /**
     * Consulta grupos/itens no banco.
     */
    private static function fetchGroups(int $productId): array
    {
        $pdo = db();
        $sql = 'SELECT pcg.id            AS group_id,
                       pcg.name          AS group_name,
                       pcg.type          AS group_type,
                       pcg.min_qty       AS group_min,
                       pcg.max_qty       AS group_max,
                       pcg.sort_order    AS group_sort,
                       pcg.hide_duplicates AS group_hide_duplicates,
                       pci.id            AS item_id,
                       COALESCE(ing.name, pci.label) AS item_label,
                       pci.delta         AS item_delta,
                       pci.is_default    AS item_default,
                       pci.default_qty   AS item_default_qty,
                       pci.min_qty       AS item_min_qty,
                       pci.max_qty       AS item_max_qty,
                       pci.sort_order    AS item_sort,
                       pci.ingredient_id AS item_ingredient_id,
                       ing.image_path    AS ingredient_image,
                       ing.sale_price    AS ingredient_sale_price,
                       ing.active        AS ingredient_active
                  FROM product_custom_groups pcg
             LEFT JOIN product_custom_items  pci ON pci.group_id = pcg.id
             LEFT JOIN ingredients ing          ON ing.id = pci.ingredient_id
                 WHERE pcg.product_id = ?
              ORDER BY pcg.sort_order ASC, pcg.id ASC, pci.sort_order ASC, pci.id ASC';

        $st = $pdo->prepare($sql);
        $st->execute([$productId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return [];
        }

        $groups = [];

        foreach ($rows as $row) {
            $gid = (int)$row['group_id'];

            if (!isset($groups[$gid])) {
                $groups[$gid] = [
                    'id'              => $gid,
                    'name'            => $row['group_name'],
                    'type'            => $row['group_type'] ?: 'extra',
                    'min'             => (int)$row['group_min'],
                    'max'             => (int)$row['group_max'],
                    'sort_order'      => (int)$row['group_sort'],
                    'hide_duplicates' => (int)($row['group_hide_duplicates'] ?? 0),
                    'items'           => [],
                ];
            }

            if (!empty($row['item_id'])) {
                $groups[$gid]['items'][] = [
                    'id'            => (int)$row['item_id'],
                    'label'         => $row['item_label'],
                    'delta'         => (float)$row['item_delta'],
                    'default'       => (bool)$row['item_default'],
                    'default_qty'   => (int)$row['item_default_qty'],
                    'min_qty'       => (int)$row['item_min_qty'],
                    'max_qty'       => (int)$row['item_max_qty'],
                    'ingredient_id' => $row['item_ingredient_id'] ? (int)$row['item_ingredient_id'] : null,
                    'image_path'    => $row['ingredient_image'] ?? null,
                    // Disponibiliza o preço de venda do ingrediente para a UI pública
                    'sale_price'    => isset($row['ingredient_sale_price']) ? (float)$row['ingredient_sale_price'] : 0.0,
                    'sort_order'    => (int)$row['item_sort'],
                    'ingredient_active' => isset($row['ingredient_active']) ? (int)$row['ingredient_active'] : 1,
                ];
            }
        }

        // Ordena itens por sort_order (defensivo)
        foreach ($groups as &$group) {
            if (isset($group['items'])) {
                usort($group['items'], function ($a, $b) {
                    return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
                });
            }
        }
        unset($group);

        return array_values($groups);
    }
}
