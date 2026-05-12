<?php

declare(strict_types=1);
// app/models/Product.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/SmartCache.php';

class Product
{
    private static function normalizePromoValue($promo, $price): ?float
    {
        if ($promo === null || $promo === '') {
            return null;
        }

        if (is_array($promo)) {
            $promo = reset($promo);
        }

        $promoStr = trim((string)$promo);

        if ($promoStr === '') {
            return null;
        }

        $promoStr = str_replace(' ', '', $promoStr);

        if (strpos($promoStr, ',') !== false && strpos($promoStr, '.') !== false) {
            $promoStr = str_replace('.', '', $promoStr);
        }
        $promoStr = str_replace(',', '.', $promoStr);

        if (!is_numeric($promoStr) && !is_numeric($promo)) {
            return null;
        }

        $promoVal = (float)$promoStr;
        $priceVal = (float)$price;

        if ($promoVal <= 0) {
            return null;
        }

        if ($priceVal <= 0 || $promoVal >= $priceVal) {
            return null;
        }

        return $promoVal;
    }

    /* ========================
     * LISTAGENS / BÁSICO
     * ======================== */

    /**
     * Aplica a taxa embutida (embedded_delivery_fee) aos preços dos produtos
     * Armazena os preços originais para cálculo correto de descontos percentuais
     */
    public static function applyEmbeddedFee(array $products, float $embeddedFee): array
    {
        if ($embeddedFee <= 0) {
            return $products;
        }

        foreach ($products as &$product) {
            // Pular produtos que não participam da taxa embutida
            if (isset($product['embedded_fee_enabled']) && (int)$product['embedded_fee_enabled'] === 0) {
                $product['embedded_delivery_fee'] = 0;
                continue;
            }

            // Armazena preços originais (sem taxa) para cálculo de desconto
            if (isset($product['price']) && $product['price'] > 0) {
                $product['original_price'] = (float)$product['price'];
                $product['price'] = (float)$product['price'] + $embeddedFee;
            }

            // Para promo_price, NÃO aplicamos a taxa aqui
            // O desconto percentual deve ser calculado sobre o preço original
            // A taxa será aplicada depois do desconto na view
            if (isset($product['promo_price']) && $product['promo_price'] !== null && $product['promo_price'] !== '') {
                $product['original_promo_price'] = $product['promo_price'];
            }
            
            // Armazena a taxa para uso posterior
            $product['embedded_delivery_fee'] = $embeddedFee;
        }

        return $products;
    }

    public static function listByCompany(int $companyId, ?string $q = null, bool $onlyActive = true, bool $applyFee = true): array
    {
        // Criar chave única baseada nos parâmetros
        $activeStr = $onlyActive ? 'active' : 'all';
        $qStr = $q ? md5($q) : 'noquery';
        $feeStr = $applyFee ? 'fee' : 'nofee';
        $key = "products:company:{$companyId}:{$activeStr}:{$qStr}:{$feeStr}";
        
        return SmartCache::remember($key, function() use ($companyId, $q, $onlyActive, $applyFee) {
            $sql = 'SELECT * FROM products WHERE company_id = ?';
            $args = [$companyId];

            if ($onlyActive) {
                $sql .= ' AND active = 1';
            }

            if ($q) {
                $sql .= ' AND (name LIKE ? OR description LIKE ?)';
                $args[] = "%$q%";
                $args[] = "%$q%";
            }
            $sql .= ' ORDER BY COALESCE(updated_at, created_at) DESC, created_at DESC';
            $st = db()->prepare($sql);
            $st->execute($args);

            $products = $st->fetchAll(PDO::FETCH_ASSOC);

            // Aplicar taxa embutida apenas se solicitado (não aplicar no painel admin)
            if ($applyFee) {
                require_once __DIR__ . '/../services/ProductCache.php';
                $cache = ProductCache::instance();
                $embeddedFee = $cache->getEmbeddedDeliveryFee($companyId);
                return self::applyEmbeddedFee($products, $embeddedFee);
            }

            return $products;
        }, 300);
    }

    public static function listByCategory(int $companyId, int $categoryId, ?string $q = null): array
    {
        $qStr = $q ? md5($q) : 'noquery';
        $key = "products:category:{$categoryId}:active:{$qStr}";
        
        return SmartCache::remember($key, function() use ($companyId, $categoryId, $q) {
            $sql = 'SELECT * FROM products WHERE company_id = ? AND category_id = ? AND active = 1';
            $args = [$companyId, $categoryId];

            if ($q) {
                $sql .= ' AND (name LIKE ? OR description LIKE ?)';
                $args[] = "%$q%";
                $args[] = "%$q%";
            }
            $sql .= ' ORDER BY sort_order, name';
            $st = db()->prepare($sql);
            $st->execute($args);

            $products = $st->fetchAll(PDO::FETCH_ASSOC);

            // Buscar taxa embutida usando cache
            require_once __DIR__ . '/../services/ProductCache.php';
            $cache = ProductCache::instance();
            $embeddedFee = $cache->getEmbeddedDeliveryFee($companyId);

            return self::applyEmbeddedFee($products, $embeddedFee);
        }, 300);
    }

    public static function allForCompany(int $companyId): array
    {
        $sql = 'SELECT * FROM products WHERE company_id = ? ORDER BY name';
        $st = db()->prepare($sql);
        $st->execute([$companyId]);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id, bool $applyFee = true, ?int $companyId = null): ?array
    {
        // Tentar buscar do cache apenas se aplicando taxa (comportamento padrão)
        require_once __DIR__ . '/../services/ProductCache.php';
        $cache = ProductCache::instance();
        
        if ($applyFee) {
            $cached = $cache->getProduct($id);
            if ($cached !== null) {
                if ($companyId === null || (int)($cached['company_id'] ?? 0) === (int)$companyId) {
                    return $cached;
                }
            }
        }

        // Buscar do banco
        $sql = 'SELECT * FROM products WHERE id = ?';
        $params = [$id];
        if ($companyId !== null) {
            $sql .= ' AND company_id = ?';
            $params[] = $companyId;
        }
        $st = db()->prepare($sql);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        // Aplicar taxa embutida apenas se solicitado (não aplicar no painel admin)
        if ($applyFee) {
            $embeddedFee = $cache->getEmbeddedDeliveryFee((int)$row['company_id']);

            if ($embeddedFee > 0) {
                $products = self::applyEmbeddedFee([$row], $embeddedFee);
                $result = $products[0];
            } else {
                $result = $row;
            }

            // Salvar no cache
            $cache->setProduct($id, $result);

            return $result;
        }

        return $row;
    }

    /**
     * Buscar múltiplos produtos por IDs em uma única query (evita N+1)
     * @param array $ids Array de IDs de produtos
     * @param int|null $companyId Filtrar por empresa (opcional, para validação)
     * @param bool $applyFee Aplicar taxa embutida
     * @return array Array associativo [id => product]
     */
    public static function findByIds(array $ids, ?int $companyId = null, bool $applyFee = true): array
    {
        if (empty($ids)) {
            return [];
        }

        // Filtrar IDs válidos
        $ids = array_filter(array_map('intval', $ids), fn($id) => $id > 0);
        if (empty($ids)) {
            return [];
        }

        // Construir query com placeholders
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT * FROM products WHERE id IN ($placeholders)";
        $params = array_values($ids);

        if ($companyId !== null) {
            $sql .= " AND company_id = ?";
            $params[] = $companyId;
        }

        $st = db()->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        // Aplicar taxa embutida se necessário
        if ($applyFee && !empty($rows)) {
            require_once __DIR__ . '/../services/ProductCache.php';
            $cache = ProductCache::instance();
            
            // Agrupar por company_id para aplicar taxas corretas
            $byCompany = [];
            foreach ($rows as $row) {
                $cid = (int)$row['company_id'];
                $byCompany[$cid][] = $row;
            }

            $processed = [];
            foreach ($byCompany as $cid => $products) {
                $embeddedFee = $cache->getEmbeddedDeliveryFee($cid);
                if ($embeddedFee > 0) {
                    $products = self::applyEmbeddedFee($products, $embeddedFee);
                }
                foreach ($products as $p) {
                    $processed[] = $p;
                }
            }
            $rows = $processed;
        }

        // Retornar como array associativo [id => product]
        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['id']] = $row;
        }

        return $result;
    }

    /**
     * Retorna a próxima SKU numérica disponível para a empresa.
     * Busca o menor número positivo que ainda não está em uso,
     * permitindo reutilizar gaps quando um produto é excluído.
     */
    public static function nextSkuForCompany(int $companyId): string
    {
        $st = db()->prepare("SELECT sku FROM products WHERE company_id = ? AND sku IS NOT NULL AND sku <> ''");
        $st->execute([$companyId]);

        $used = [];

        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $sku = trim((string)($row['sku'] ?? ''));

            if ($sku === '' || !ctype_digit($sku)) {
                continue;
            }

            $value = (int)$sku;

            if ($value > 0) {
                $used[] = $value;
            }
        }

        sort($used, SORT_NUMERIC);

        $next = 1;

        foreach ($used as $value) {
            if ($value === $next) {
                $next++;
                continue;
            }

            if ($value > $next) {
                break;
            }
        }

        return (string)$next;
    }

    /** Produto garantido por empresa (útil para rotas públicas /{empresa}/produto/{id}) */
    public static function findByCompanyAndId(int $companyId, int $productId): ?array
    {
        $sql = "SELECT * FROM products
            WHERE company_id = ? AND id = ? AND (deleted_at IS NULL OR deleted_at='0000-00-00 00:00:00')";
        $st = db()->prepare($sql);
        $st->execute([$companyId, $productId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function create(array $data): int
    {
        // Campos extras (se existirem na sua tabela): type, price_mode, allow_customize
        $sql = 'INSERT INTO products
              (company_id, category_id, name, description, price, promo_price, promo_start_at, promo_end_at, sku, image,
               type, price_mode, allow_customize, active, sort_order, created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())';
        $pdo = db();
        $st = $pdo->prepare($sql);
        $st->execute([
          $data['company_id'],
          $data['category_id'] ?: null,
          $data['name'],
          $data['description'] ?? null,
          (float)$data['price'],
          self::normalizePromoValue($data['promo_price'] ?? null, $data['price'] ?? 0),
          !empty($data['promo_start_at']) ? $data['promo_start_at'] : null,
          !empty($data['promo_end_at']) ? $data['promo_end_at'] : null,
          $data['sku'] ?? null,
          $data['image'] ?? null,
          $data['type'] ?? 'simple',
          $data['price_mode'] ?? 'fixed',
          !empty($data['allow_customize']) ? 1 : 0,
          isset($data['active']) ? (int)$data['active'] : 1,
          (int)($data['sort_order'] ?? 0),
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $sql = 'UPDATE products
               SET category_id=?,
                   name=?,
                   description=?,
                   price=?,
                   promo_price=?,
                   promo_start_at=?,
                   promo_end_at=?,
                   sku=?,
                   image=?,
                   type=?,
                   price_mode=?,
                   allow_customize=?,
                   active=?,
                   sort_order=?,
                   updated_at=NOW()
             WHERE id=?';
        $st = db()->prepare($sql);
        $st->execute([
          $data['category_id'] ?: null,
          $data['name'],
          $data['description'] ?? null,
          (float)$data['price'],
          self::normalizePromoValue($data['promo_price'] ?? null, $data['price'] ?? 0),
          !empty($data['promo_start_at']) ? $data['promo_start_at'] : null,
          !empty($data['promo_end_at']) ? $data['promo_end_at'] : null,
          $data['sku'] ?? null,
          $data['image'] ?? null,
          $data['type'] ?? 'simple',
          $data['price_mode'] ?? 'fixed',
          !empty($data['allow_customize']) ? 1 : 0,
          isset($data['active']) ? (int)$data['active'] : 1,
          (int)($data['sort_order'] ?? 0),
          $id
        ]);

        // Invalidar cache do produto atualizado
        require_once __DIR__ . '/../services/ProductCache.php';
        $cache = ProductCache::instance();
        $cache->invalidateProduct($id);
        
        // Invalidar cache de combos que contêm este produto (para refletir mudança de status)
        $cache->invalidateCombosContainingProduct($id);
    }

    public static function delete(int $id): void
    {
        // Invalidar cache antes de deletar
        require_once __DIR__ . '/../services/ProductCache.php';
        $cache = ProductCache::instance();
        $cache->invalidateProduct($id);
        
        // Invalidar cache de combos que contêm este produto
        $cache->invalidateCombosContainingProduct($id);

        // Se preferir soft delete, troque por update de deleted_at.
        $st = db()->prepare('DELETE FROM products WHERE id=?');
        $st->execute([$id]);
    }

    /* ========================
     * SUGESTÕES / VITRINES
     * ======================== */

    public static function novidadesByCompanyId(PDO $db, int $companyId, int $dias = 14, int $limit = 12): array
    {
        if ($dias <= 0) {
            return [];
        }
        $sql = 'SELECT p.*
              FROM products p
             WHERE p.company_id = :cid
               AND p.active = 1
               AND p.created_at >= (NOW() - INTERVAL :dias DAY)
          ORDER BY p.created_at DESC
             LIMIT :limit';
        $st = $db->prepare($sql);
        $st->bindValue(':cid', $companyId, PDO::PARAM_INT);
        $st->bindValue(':dias', $dias, PDO::PARAM_INT);
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);
        $st->execute();

        $products = $st->fetchAll(PDO::FETCH_ASSOC);

        // Buscar taxa embutida usando cache
        require_once __DIR__ . '/../services/ProductCache.php';
        $cache = ProductCache::instance();
        $embeddedFee = $cache->getEmbeddedDeliveryFee($companyId);

        return self::applyEmbeddedFee($products, $embeddedFee);
    }

    public static function maisPedidosByCompanyId(PDO $db, int $companyId, int $limit = 12): array
    {
        $sql = "SELECT p.*, SUM(oi.quantity) AS total_pedidos
              FROM order_items oi
              JOIN orders   o ON o.id = oi.order_id
              JOIN products p ON p.id = oi.product_id
             WHERE o.company_id = :cid
                             AND p.active = 1
               AND o.status = 'completed'
          GROUP BY p.id
            HAVING total_pedidos > 0
          ORDER BY total_pedidos DESC
             LIMIT :limit";
        $st = $db->prepare($sql);
        $st->bindValue(':cid', $companyId, PDO::PARAM_INT);
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);
        $st->execute();

        $products = $st->fetchAll(PDO::FETCH_ASSOC);

        // Buscar taxa embutida usando cache
        require_once __DIR__ . '/../services/ProductCache.php';
        $cache = ProductCache::instance();
        $embeddedFee = $cache->getEmbeddedDeliveryFee($companyId);

        return self::applyEmbeddedFee($products, $embeddedFee);
    }

    /* ========================
     * COMBO: GRUPOS + ITENS
     * ======================== */

    /**
     * Lê grupos de combo + itens (com dados do produto simples).
     * Estrutura:
     * [
     *   [
     *     'id','name','type','min','max',
     *     'items' => [
     *        ['simple_id','name','image','base_price','delta','is_default']
     *     ]
     *   ], ...
     * ]
     */
    public static function getComboGroupsWithItems(int $productId): array
    {
        // Tentar buscar do cache
        require_once __DIR__ . '/../services/ProductCache.php';
        $cache = ProductCache::instance();
        
        $cached = $cache->getComboGroups($productId);
        if ($cached !== null) {
            return $cached;
        }

        $pdo = db();

        // grupos
        $gq = $pdo->prepare('
      SELECT id, name, type,
             COALESCE(min_qty,0) AS min,
             COALESCE(max_qty,1) AS max,
             COALESCE(sort,0)    AS sort,
             COALESCE(sort,0)    AS sort_order,
             COALESCE(min_qty,0) AS min_qty,
             COALESCE(max_qty,1) AS max_qty
        FROM combo_groups
       WHERE product_id = ?
    ORDER BY sort ASC, id ASC
    ');
        $gq->execute([$productId]);
        $groups = $gq->fetchAll(PDO::FETCH_ASSOC);

        if (!$groups) {
            return [];
        }

        // itens de 1 grupo (filtra produtos inativos)
        $iq = $pdo->prepare('
      SELECT gi.id,
             gi.group_id,
             gi.simple_product_id AS simple_id,
             COALESCE(gi.delta_price,0) AS delta,
             gi.price_override,
             COALESCE(gi.is_default,0)  AS is_default,
             COALESCE(gi.default_qty,1) AS default_qty,
             COALESCE(gi.allow_customize,0) AS allow_customize,
             COALESCE(gi.sort,0) AS sort,
             sp.name,
             sp.image,
             sp.price AS base_price
        FROM combo_group_items gi
  INNER JOIN products sp ON sp.id = gi.simple_product_id
       WHERE gi.group_id = ?
         AND sp.active = 1
    ORDER BY gi.sort ASC, gi.id ASC
    ');

        $normalized = [];

        foreach ($groups as $g) {
            $groupId = isset($g['id']) ? (int)$g['id'] : 0;

            if ($groupId <= 0) {
                continue;
            }

            $iq->execute([$groupId]);
            $rows = $iq->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Unificação: construir $items completos e coerentes
            $items = [];

            foreach ($rows as $row) {
                $simpleId = isset($row['simple_id']) ? (int)$row['simple_id'] : (int)($row['simple_product_id'] ?? 0);

                if ($simpleId <= 0) {
                    continue;
                }

                $itemId      = isset($row['id']) ? (int)$row['id'] : 0;
                $delta       = isset($row['delta']) ? (float)$row['delta'] : (float)($row['delta_price'] ?? 0);
                $base        = isset($row['base_price']) ? (float)$row['base_price'] : null;
                $priceOverride = isset($row['price_override']) ? (float)$row['price_override'] : null;
                $isDefault   = !empty($row['is_default']);
                $defaultQty  = isset($row['default_qty']) ? (int)$row['default_qty'] : ($isDefault ? 1 : 0);
                $allowCus    = !empty($row['allow_customize']);
                $sortItem    = isset($row['sort']) ? (int)$row['sort'] : 0;

                $items[] = [
                  'id'                => $itemId > 0 ? $itemId : $simpleId,
                  'group_id'          => $groupId,
                  'simple_id'         => $simpleId,
                  'simple_product_id' => $simpleId,
                  'product_id'        => $simpleId,                 // compatibilidade com payload esperado
                  'name'              => (string)($row['name'] ?? ''),
                  'image'             => $row['image'] ?? null,
                  'base_price'        => $base,
                  'price'             => $base,                      // mantém preço base para UI
                  'price_override'    => $priceOverride,
                  'delta'             => $delta,
                  'delta_price'       => $delta,
                  'is_default'        => $isDefault ? 1 : 0,
                  'default'           => $isDefault ? 1 : 0,         // flag duplicada p/ consumo no front
                  'default_qty'       => $defaultQty,                // quantidade padrão do item
                  'allow_customize'   => $allowCus ? 1 : 0,
                  'customizable'      => $allowCus ? 1 : 0,          // idem
                  'sort'              => $sortItem,
                  'sort_order'        => $sortItem,
                ];
            }

            if (!$items) {
                continue;
            }

            $minQty = isset($g['min_qty']) ? (int)$g['min_qty'] : (int)($g['min'] ?? 0);
            $maxQty = isset($g['max_qty']) ? (int)$g['max_qty'] : (int)($g['max'] ?? 1);
            $sort   = isset($g['sort']) ? (int)$g['sort'] : (int)($g['sort_order'] ?? 0);
            $type   = isset($g['type']) && $g['type'] !== '' ? (string)$g['type'] : 'single';

            $normalized[] = [
              'id'          => $groupId,
              'name'        => (string)($g['name'] ?? ''),
              'type'        => $type,
              'min'         => $minQty,
              'max'         => $maxQty,
              'min_qty'     => $minQty,
              'max_qty'     => $maxQty,
              'sort'        => $sort,
              'sort_order'  => $sort,
              'items'       => array_values($items),
            ];
        }

        if (!$normalized) {
            return [];
        }

        usort($normalized, static function ($a, $b) {
            return ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0);
        });

        $result = array_values($normalized);

        // Salvar no cache
        $cache->setComboGroups($productId, $result);

        return $result;
    }

    /**
     * Salva grupos de opções (combo) vindos do formulário Admin.
     * Espera a estrutura semelhante ao seu form:
     * $groups = [
     *   [ 'name'=>'Escolha o produto', 'type'=>'single', 'min'=>1, 'max'=>1,
     *     'items'=>[
     *        ['product_id'=>123, 'delta'=>0.00, 'default'=>true],
     *        ...
     *     ]
     *   ],
     *   ...
     * ]
     * Estratégia: apaga todos e re-insere (mais simples e confiável).
     */
    public static function saveComboGroupsAndItems(int $productId, array $groups): void
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            // apaga existentes
            $pdo->prepare('DELETE gi FROM combo_group_items gi
                      INNER JOIN combo_groups g ON g.id = gi.group_id
                      WHERE g.product_id = ?')->execute([$productId]);
            $pdo->prepare('DELETE FROM combo_groups WHERE product_id = ?')->execute([$productId]);

            if (!empty($groups)) {
                $insG = $pdo->prepare('
          INSERT INTO combo_groups (product_id, name, type, min_qty, max_qty, sort, created_at)
          VALUES (?,?,?,?,?,?,NOW())
        ');
                $insI = $pdo->prepare('
          INSERT INTO combo_group_items (group_id, simple_product_id, delta_price, price_override, is_default, default_qty, allow_customize, sort, created_at)
          VALUES (?,?,?,?,?,?,?,?,NOW())
        ');

                $gSort = 0;

                foreach ($groups as $g) {
                    $name = trim((string)($g['name'] ?? ''));

                    if ($name === '') {
                        continue;
                    }

                    $type = $g['type'] ?? 'single';
                    $min  = (int)($g['min'] ?? 0);
                    $max  = (int)($g['max'] ?? 1);

                    $insG->execute([$productId, $name, $type, $min, $max, $gSort++]);
                    $groupId = (int)$pdo->lastInsertId();

                    $items = $g['items'] ?? [];
                    $iSort = 0;
                    $addedProducts = []; // Tracking para evitar duplicatas

                    foreach ($items as $it) {
                        $spId   = (int)($it['product_id'] ?? 0);

                        if ($spId <= 0) {
                            continue;
                        }

                        // Verificar se já adicionamos este produto neste grupo
                        if (in_array($spId, $addedProducts, true)) {
                            continue; // Pular duplicata
                        }

                        $delta  = (float)($it['delta'] ?? 0);
                        $priceOverride = isset($it['price_override']) ? $it['price_override'] : null;
                        $isDef  = !empty($it['default']) ? 1 : 0;
                        $defaultQty = isset($it['default_qty']) ? max(0, (int)$it['default_qty']) : ($isDef ? 1 : 0);
                        $allowCust = !empty($it['customizable']) ? 1 : 0;
                        $insI->execute([$groupId, $spId, $delta, $priceOverride, $isDef, $defaultQty, $allowCust, $iSort++]);
                        
                        $addedProducts[] = $spId; // Marcar como adicionado
                    }
                }
            }

            $pdo->commit();
            
            // Invalidar cache do produto
            require_once __DIR__ . '/../services/ProductCache.php';
            ProductCache::instance()->invalidateProduct($productId);
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Lista produtos simples disponíveis para uso nos combos de uma empresa.
     * Retorna dados básicos + contagem de ingredientes vinculados (personalização).
     */
    public static function simpleProductsForCompany(int $companyId, bool $onlyActive = true): array
    {
        $pdo = db();
        $sql = "SELECT p.id, p.name, p.price, p.image, p.allow_customize,
                   COALESCE(COUNT(pci.id), 0) AS ingredient_count
              FROM products p
         LEFT JOIN product_custom_groups pcg ON pcg.product_id = p.id
         LEFT JOIN product_custom_items pci ON pci.group_id = pcg.id
             WHERE p.company_id = :cid
               AND p.type = 'simple'";

        if ($onlyActive) {
            $sql .= ' AND p.active = 1';
        }
        $sql .= ' GROUP BY p.id
              ORDER BY p.name';

        $st = $pdo->prepare($sql);
        $st->bindValue(':cid', $companyId, PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Normaliza os dados dos grupos de combo vindos do formulário do admin.
     * Garante índices, produtos válidos e flags coerentes.
     */
    public static function sanitizeComboGroupsPayload(array $payload, int $companyId): array
    {
        if (!$payload) {
            return [];
        }

        $simpleProducts = self::simpleProductsForCompany($companyId, false);
        $simpleMap = [];

        foreach ($simpleProducts as $sp) {
            $simpleMap[(int)$sp['id']] = $sp;
        }

        $groups = [];
        $ordered = [];

        foreach ($payload as $group) {
            if (!is_array($group)) {
                continue;
            }
            $group['_order'] = isset($group['sort_order']) ? (int)$group['sort_order'] : count($ordered);
            $ordered[] = $group;
        }

        usort($ordered, function ($a, $b) {
            return ($a['_order'] ?? 0) <=> ($b['_order'] ?? 0);
        });

        foreach ($ordered as $gIndex => $group) {
            $name = trim((string)($group['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $itemsRaw = isset($group['items']) && is_array($group['items']) ? $group['items'] : [];
            $items = [];
            $iSort = 0;

            foreach ($itemsRaw as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $spId = isset($item['product_id']) ? (int)$item['product_id'] : 0;

                if ($spId <= 0 || !isset($simpleMap[$spId])) {
                    continue;
                }

                $delta = isset($item['delta']) ? (float)$item['delta'] : 0.0;
                $isDefault = !empty($item['default']);
                $customizable = !empty($item['customizable']);
                
                // Novo: preço customizado
                $priceOverride = null;
                if (isset($item['price_override']) && $item['price_override'] !== '' && $item['price_override'] !== null) {
                    $priceOverride = (float)$item['price_override'];
                    // Validar se é diferente do preço original
                    $originalPrice = (float)($simpleMap[$spId]['price'] ?? 0);
                    if (abs($priceOverride - $originalPrice) < 0.01) {
                        $priceOverride = null; // Não é customizado se for igual ao original
                    }
                }

                $simpleInfo = $simpleMap[$spId];
                $ingredientCount = (int)($simpleInfo['ingredient_count'] ?? 0);
                $allowsCustomization = !empty($simpleInfo['allow_customize']);

                if ($customizable && (!$allowsCustomization || $ingredientCount <= 2)) {
                    $customizable = false;
                }

                // Quantidade padrão
                $defaultQty = isset($item['default_qty']) ? max(0, (int)$item['default_qty']) : ($isDefault ? 1 : 0);

                $items[] = [
                  'product_id'     => $spId,
                  'delta'          => $delta,
                  'price_override' => $priceOverride,
                  'default'        => $isDefault ? 1 : 0,
                  'default_qty'    => $defaultQty,
                  'customizable'   => $customizable ? 1 : 0,
                  'sort_order'     => $iSort++,
                ];
            }

            if (!$items) {
                continue;
            }

            $min = isset($group['min']) ? max(0, (int)$group['min']) : 0;
            $max = isset($group['max']) ? (int)$group['max'] : 1;

            if ($max > 0 && $max < $min) {
                $max = $min;
            }

            $groups[] = [
              'name'  => $name,
              'type'  => 'component',
              'min'   => $min,
              'max'   => $max,
              'items' => $items,
              'sort_order' => $gIndex,
            ];
        }

        return $groups;
    }

    /* ========================
     * HELPERS DE CÁLCULO (opcional)
     * ======================== */

    /**
     * Recalcula o total de um produto combo a partir das seleções do cliente.
     * $product: array do produto (deve conter price, price_mode)
     * $selected: array no formato combo_group[group_id] => (id simples OU array de ids)
     * Retorna ['base'=>..., 'sum_delta'=>..., 'total'=>...]
     */
    public static function calculateComboTotal(array $product, array $selected): array
    {
        // Determinar comportamento de promo dependendo do modo de preço
        // Se product['price_mode'] === 'sum' e promo_price estiver definido como
        // um valor entre 0 e 100, interpretamos como porcentagem.
        $rawPromo = $product['promo_price'] ?? null;
        $priceMode = $product['price_mode'] ?? 'fixed';
        $price = (float)($product['price'] ?? 0);

        $isPercentPromo = false;
        $promoVal = null;

        if ($rawPromo !== null && $rawPromo !== '') {
            $promoVal = (float)$rawPromo;
            if ($priceMode === 'sum' && $promoVal > 0 && $promoVal <= 100) {
                $isPercentPromo = true; // promoVal is percentage
            }
        }

        $priceMode = $product['price_mode'] ?? 'fixed'; // 'fixed' | 'sum'
        // Valor base padrão (compatível com comportamento anterior para modo 'fixed')
        $base = $price;
        if ($promoVal !== null && !$isPercentPromo && $promoVal > 0 && $promoVal < $price) {
            $base = $promoVal;
        }

        $sumDelta = 0.0;

        if (!empty($selected)) {
            // pega todos os deltas das seleções
            $pdo = db();
            $pairs = [];

            foreach ($selected as $gid => $val) {
                if (is_array($val)) {
                    foreach ($val as $sid) {
                        $pairs[] = [(int)$gid, (int)$sid];
                    }
                } else {
                    $pairs[] = [(int)$gid, (int)$val];
                }
            }

            if ($pairs) {
                // consulta por lotes
                $place = [];
                $args  = [];

                foreach ($pairs as [$gid, $sid]) {
                    $place[] = '(gi.group_id = ? AND gi.simple_product_id = ?)';
                    $args[] = $gid;
                    $args[] = $sid;
                }
                if ($priceMode === 'sum') {
                    // No modo 'sum', usar price_override ou preço do produto se price_override for NULL
                    $sql = 'SELECT SUM(COALESCE(gi.price_override, p.price, 0)) AS s 
                            FROM combo_group_items gi
                            INNER JOIN products p ON p.id = gi.simple_product_id
                            WHERE ' . implode(' OR ', $place);
                } else {
                    // No modo 'fixed', usar delta_price (comportamento original)
                    $sql = 'SELECT SUM(COALESCE(gi.delta_price,0)) AS s FROM combo_group_items gi WHERE ' . implode(' OR ', $place);
                }
                $st  = $pdo->prepare($sql);
                $st->execute($args);
                $sumDelta = (float)($st->fetchColumn() ?: 0);
            }
        }

        if ($priceMode === 'sum') {
            // No modo 'sum', o total é a soma dos preços dos itens selecionados (price_override)
            $subtotal = $sumDelta; // sumDelta já contém a soma dos price_override
            
            if ($isPercentPromo) {
                // Aplicar desconto percentual sobre o subtotal
                $total = $subtotal * (1 - ($promoVal / 100.0));
                $base = $total; // base é o valor final com desconto
            } else {
                $total = $subtotal;
                $base = $subtotal;
            }
        } else {
            // Modo 'fixed': preço fixo + upgrades (delta_price)
            $total = $base + $sumDelta;
        }

        return [
          'base'      => $base,
          'sum_delta' => $sumDelta,
          'total'     => $total,
        ];
    }

    // ========= MÉTODOS MOBILE =========

    /**
     * Conta produtos ativos para uma empresa
     */
    public static function countActiveByCompany(int $companyId): int
    {
        $db = db();
        $sql = "SELECT COUNT(*) FROM products 
                WHERE company_id = :cid 
                AND active = 1";
        $st = $db->prepare($sql);
        $st->execute([':cid' => $companyId]);
        return (int)$st->fetchColumn();
    }
}
