<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class Order
{
    /**
     * Lista pedidos com contagem de itens
     */
    public static function listByCompany(PDO $db, int $companyId, ?string $status = null, int $limit = 50, int $offset = 0, ?string $search = null, ?string $source = null, ?string $excludeSource = null): array
    {
        $sql = 'SELECT o.*, 
                       (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as items_count,
                       (SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.order_id = o.id) as items_qty
                FROM orders o 
                WHERE o.company_id = :cid';
        $args = [':cid' => $companyId];

        if ($status) {
            $sql .= ' AND o.status = :st';
            $args[':st'] = $status;
        }
        
        // Filtro por origem (manual, website, ifood)
        if ($source) {
            $sql .= ' AND LOWER(TRIM(o.source)) = :source';
            $args[':source'] = strtolower(trim($source));
        } elseif ($excludeSource) {
            $sql .= ' AND (o.source IS NULL OR LOWER(TRIM(o.source)) != :exclude_source)';
            $args[':exclude_source'] = strtolower(trim($excludeSource));
        }
        
        // Busca por texto (nome, telefone, ID)
        if ($search !== null && trim($search) !== '') {
            $searchTerm = trim($search);
            // Normalizar telefone removendo formatação
            $phoneDigits = preg_replace('/\D/', '', $searchTerm);
            
            if (strlen($phoneDigits) >= 8) {
                // Busca por telefone - comparar variações
                $sql .= ' AND (
                    o.customer_phone LIKE :search_phone 
                    OR o.customer_phone LIKE :phone_digits
                    OR REGEXP_REPLACE(o.customer_phone, "[^0-9]", "") LIKE :phone_digits
                    OR o.customer_phone = :phone_exact
                    OR o.customer_name LIKE :search_name
                    OR CAST(o.id AS CHAR) = :search_id
                    OR CAST(o.order_number AS CHAR) = :search_id
                )';
                $args[':search_phone'] = '%' . $searchTerm . '%';
                $args[':search_name'] = '%' . $searchTerm . '%';
                $args[':phone_digits'] = '%' . $phoneDigits . '%';
                $args[':phone_exact'] = $phoneDigits;
                $args[':search_id'] = $searchTerm;
            } else {
                // Busca por nome ou ID
                $sql .= ' AND (o.customer_name LIKE :search OR CAST(o.id AS CHAR) = :search_id OR CAST(o.order_number AS CHAR) = :search_id)';
                $args[':search'] = '%' . $searchTerm . '%';
                $args[':search_id'] = $searchTerm;
            }
        }
        
        $sql .= ' ORDER BY o.id DESC LIMIT :lim OFFSET :off';
        $st = $db->prepare($sql);

        foreach ($args as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Conta total de pedidos para paginação
     */
    public static function countByCompany(PDO $db, int $companyId, ?string $status = null, ?string $search = null, ?string $source = null, ?string $excludeSource = null): int
    {
        $sql = 'SELECT COUNT(*) FROM orders WHERE company_id = :cid';
        $args = [':cid' => $companyId];

        if ($status) {
            $sql .= ' AND status = :st';
            $args[':st'] = $status;
        }
        
        // Filtro por origem (manual, website, ifood)
        if ($source) {
            $sql .= ' AND LOWER(TRIM(source)) = :source';
            $args[':source'] = strtolower(trim($source));
        } elseif ($excludeSource) {
            $sql .= ' AND (source IS NULL OR LOWER(TRIM(source)) != :exclude_source)';
            $args[':exclude_source'] = strtolower(trim($excludeSource));
        }
        
        // Busca por texto (nome, telefone, ID)
        if ($search !== null && trim($search) !== '') {
            $searchTerm = trim($search);
            // Normalizar telefone removendo formatação
            $phoneDigits = preg_replace('/\D/', '', $searchTerm);
            
            if (strlen($phoneDigits) >= 8) {
                // Busca por telefone - comparar variações
                $sql .= ' AND (
                    customer_phone LIKE :search_phone 
                    OR customer_phone LIKE :phone_digits
                    OR REGEXP_REPLACE(customer_phone, "[^0-9]", "") LIKE :phone_digits
                    OR customer_phone = :phone_exact
                    OR customer_name LIKE :search_name
                    OR CAST(id AS CHAR) = :search_id
                    OR CAST(order_number AS CHAR) = :search_id
                )';
                $args[':search_phone'] = '%' . $searchTerm . '%';
                $args[':search_name'] = '%' . $searchTerm . '%';
                $args[':phone_digits'] = '%' . $phoneDigits . '%';
                $args[':phone_exact'] = $phoneDigits;
                $args[':search_id'] = $searchTerm;
            } else {
                // Busca por nome ou ID
                $sql .= ' AND (customer_name LIKE :search OR CAST(id AS CHAR) = :search_id OR CAST(order_number AS CHAR) = :search_id)';
                $args[':search'] = '%' . $searchTerm . '%';
                $args[':search_id'] = $searchTerm;
            }
        }

        $st = $db->prepare($sql);
        $st->execute($args);

        return (int)$st->fetchColumn();
    }

    /**
     * Encontra o próximo número de pedido disponível (reutiliza números de pedidos deletados)
     * Prioriza números de pedidos excluídos, se disponíveis
     */
    public static function getNextOrderNumber(PDO $db, int $companyId): int
    {
        // Primeiro, tenta obter um número reutilizável de pedidos excluídos
        try {
            $st = $db->prepare('
                SELECT id, order_number 
                FROM available_order_numbers 
                WHERE company_id = :cid 
                ORDER BY order_number ASC 
                LIMIT 1
                FOR UPDATE
            ');
            $st->execute([':cid' => $companyId]);
            $available = $st->fetch(PDO::FETCH_ASSOC);
            
            if ($available) {
                // Remove o número da lista de disponíveis
                $delSt = $db->prepare('DELETE FROM available_order_numbers WHERE id = ?');
                $delSt->execute([$available['id']]);
                return (int)$available['order_number'];
            }
        } catch (PDOException $e) {
            // Tabela pode não existir ainda, ignora e usa sequência
        }
        
        // Se não há números reutilizáveis, usa a sequência
        try {
            // Tenta obter e incrementar o próximo número da sequência
            $st = $db->prepare('
                SELECT next_order_number 
                FROM company_order_number_sequence 
                WHERE company_id = :cid 
                FOR UPDATE
            ');
            $st->execute([':cid' => $companyId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                $nextNumber = (int)$row['next_order_number'];
                // Incrementa o próximo número
                $updateSt = $db->prepare('
                    UPDATE company_order_number_sequence 
                    SET next_order_number = next_order_number + 1 
                    WHERE company_id = ?
                ');
                $updateSt->execute([$companyId]);

                // Verificar se o número já existe (sequência desincronizada)
                // Se sim, avançar a sequência até um número livre
                $checkSt = $db->prepare('SELECT COUNT(*) FROM orders WHERE company_id = ? AND order_number = ?');
                $checkSt->execute([$companyId, $nextNumber]);
                while ((int)$checkSt->fetchColumn() > 0) {
                    $db->prepare('UPDATE company_order_number_sequence SET next_order_number = next_order_number + 1 WHERE company_id = ?')
                       ->execute([$companyId]);
                    $nextNumber++;
                    $checkSt->execute([$companyId, $nextNumber]);
                }

                return $nextNumber;
            } else {
                // Cria registro para nova empresa usando INSERT ... ON DUPLICATE KEY
                // para evitar race condition em requests simultâneos
                $maxSt = $db->prepare('SELECT COALESCE(MAX(order_number), 0) + 1 FROM orders WHERE company_id = ?');
                $maxSt->execute([$companyId]);
                $startAt = max(1, (int)$maxSt->fetchColumn());

                $insertSt = $db->prepare('
                    INSERT INTO company_order_number_sequence (company_id, next_order_number) 
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE next_order_number = GREATEST(next_order_number, VALUES(next_order_number))
                ');
                $insertSt->execute([$companyId, $startAt + 1]);
                return $startAt;
            }
        } catch (PDOException $e) {
            // Fallback: calcula baseado no máximo existente
            $st = $db->prepare('SELECT COALESCE(MAX(order_number), 0) + 1 FROM orders WHERE company_id = ?');
            $st->execute([$companyId]);
            return (int)$st->fetchColumn();
        }
    }

    /**
     * Libera um número de pedido para reutilização (chamado ao deletar pedido)
     */
    public static function releaseOrderNumber(PDO $db, int $companyId, int $orderNumber): void
    {
        if ($orderNumber <= 0) {
            return;
        }
        
        try {
            $st = $db->prepare('
                INSERT IGNORE INTO available_order_numbers (company_id, order_number) 
                VALUES (?, ?)
            ');
            $st->execute([$companyId, $orderNumber]);
        } catch (PDOException $e) {
            // Tabela pode não existir, ignora silenciosamente
        }
    }

    /**
     * @deprecated Use getNextOrderNumber instead
     * Encontra o próximo ID disponível (reutiliza IDs de pedidos deletados)
     */
    public static function getNextAvailableId(PDO $db, int $companyId): int
    {
        return self::getNextOrderNumber($db, $companyId);
    }

    public static function create(PDO $db, array $data): int
    {
        $defaults = [
            'company_id'        => 0,
            'customer_name'     => null,
            'customer_phone'    => null,
            'subtotal'          => 0,
            'delivery_fee'      => 0,
            'discount'          => 0,
            'loyalty_discount'  => 0,
            'coupon_code'       => null,
            'total'             => 0,
            'status'            => 'pending',
            'notes'             => null,
            'customer_address'  => null,
            'source'            => 'website',
            'payment_method_id' => null,
        ];
        $payload = array_merge($defaults, $data);

        // Obtém o próximo número de pedido (reutiliza números de pedidos excluídos)
        $orderNumber = self::getNextOrderNumber($db, (int)$payload['company_id']);

        $sql = 'INSERT INTO orders (company_id, order_number, customer_name, customer_phone, subtotal, delivery_fee, discount, loyalty_discount, coupon_code, total, status, notes, customer_address, source, payment_method_id)
                VALUES (:cid,:order_num,:name,:phone,:sub,:fee,:disc,:loyal,:coupon,:tot,:status,:notes,:address,:source,:payment_method_id)';
        $stmt = $db->prepare($sql);
        try {
            $stmt->execute([
                ':cid'               => $payload['company_id'],
                ':order_num'         => $orderNumber,
                ':name'              => $payload['customer_name'],
                ':phone'             => $payload['customer_phone'],
                ':sub'               => $payload['subtotal'],
                ':fee'               => $payload['delivery_fee'],
                ':disc'              => $payload['discount'],
                ':loyal'             => $payload['loyalty_discount'],
                ':coupon'            => $payload['coupon_code'],
                ':tot'               => $payload['total'],
                ':status'            => $payload['status'],
                ':notes'             => $payload['notes'],
                ':address'           => $payload['customer_address'],
                ':source'            => $payload['source'],
                ':payment_method_id' => $payload['payment_method_id'],
            ]);
        } catch (PDOException $e) {
            // NUNCA absorver erros de constraint violation (duplicate key, FK) — esses devem propagar
            // SQLSTATE 23000 = Integrity Constraint Violation (inclui duplicate entry)
            if ($e->getCode() === '23000' || strpos($e->getMessage(), 'Duplicate') !== false) {
                throw $e;
            }
            // fallback apenas para esquema antigo (coluna inexistente — SQLSTATE 42000/HY000)
            if (stripos($e->getMessage(), 'order_number') !== false) {
                // Tenta sem order_number (banco antigo)
                $sql = 'INSERT INTO orders (company_id, customer_name, customer_phone, subtotal, delivery_fee, discount, loyalty_discount, coupon_code, total, status, notes, customer_address)
                        VALUES (:cid,:name,:phone,:sub,:fee,:disc,:loyal,:coupon,:tot,:status,:notes,:address)';
                $stmt = $db->prepare($sql);
                try {
                    $stmt->execute([
                        ':cid'     => $payload['company_id'],
                        ':name'    => $payload['customer_name'],
                        ':phone'   => $payload['customer_phone'],
                        ':sub'     => $payload['subtotal'],
                        ':fee'     => $payload['delivery_fee'],
                        ':disc'    => $payload['discount'],
                        ':loyal'   => $payload['loyalty_discount'],
                        ':coupon'  => $payload['coupon_code'],
                        ':tot'     => $payload['total'],
                        ':status'  => $payload['status'],
                        ':notes'   => $payload['notes'],
                        ':address' => $payload['customer_address'],
                    ]);
                } catch (PDOException $e2) {
                    if (stripos($e2->getMessage(), 'customer_address') !== false || 
                        stripos($e2->getMessage(), 'loyalty_discount') !== false ||
                        stripos($e2->getMessage(), 'coupon_code') !== false) {
                        $stmt = $db->prepare('INSERT INTO orders (company_id, customer_name, customer_phone, subtotal, delivery_fee, discount, total, status, notes)
                                              VALUES (:cid,:name,:phone,:sub,:fee,:disc,:tot,:status,:notes)');
                        $stmt->execute([
                            ':cid'    => $payload['company_id'],
                            ':name'   => $payload['customer_name'],
                            ':phone'  => $payload['customer_phone'],
                            ':sub'    => $payload['subtotal'],
                            ':fee'    => $payload['delivery_fee'],
                            ':disc'   => $payload['discount'],
                            ':tot'    => $payload['total'],
                            ':status' => $payload['status'],
                            ':notes'  => $payload['notes'],
                        ]);
                    } else {
                        throw $e2;
                    }
                }
            } elseif (stripos($e->getMessage(), 'customer_address') !== false || 
                stripos($e->getMessage(), 'loyalty_discount') !== false ||
                stripos($e->getMessage(), 'coupon_code') !== false) {
                $stmt = $db->prepare('INSERT INTO orders (company_id, customer_name, customer_phone, subtotal, delivery_fee, discount, total, status, notes)
                                      VALUES (:cid,:name,:phone,:sub,:fee,:disc,:tot,:status,:notes)');
                $stmt->execute([
                    ':cid'    => $payload['company_id'],
                    ':name'   => $payload['customer_name'],
                    ':phone'  => $payload['customer_phone'],
                    ':sub'    => $payload['subtotal'],
                    ':fee'    => $payload['delivery_fee'],
                    ':disc'   => $payload['discount'],
                    ':tot'    => $payload['total'],
                    ':status' => $payload['status'],
                    ':notes'  => $payload['notes'],
                ]);
            } else {
                throw $e;
            }
        }

        return (int)$db->lastInsertId();
    }

    public static function addItem(PDO $db, int $orderId, array $item): void
    {
        $sql = 'INSERT INTO order_items (order_id, product_id, quantity, unit_price, line_total, combo_data, customization_data, notes)
                VALUES (:oid,:pid,:qty,:unit,:line,:combo,:custom,:notes)';
        $st = $db->prepare($sql);
        $st->execute([
            ':oid'    => $orderId,
            ':pid'    => $item['product_id'],
            ':qty'    => $item['quantity'],
            ':unit'   => $item['unit_price'],
            ':line'   => $item['line_total'],
            ':combo'  => isset($item['combo_data']) ? json_encode($item['combo_data']) : null,
            ':custom' => isset($item['customization_data']) ? json_encode($item['customization_data']) : null,
            ':notes'  => $item['notes'] ?? null,
        ]);
    }

    public static function findWithItems(PDO $db, int $orderId, int $companyId): ?array
    {
        $st = $db->prepare('SELECT * FROM orders WHERE id = ? AND company_id = ?');
        $st->execute([$orderId, $companyId]);
        $order = $st->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }
        $items = self::itemsForOrders($db, [$orderId]);
        $order['items'] = $items[$orderId] ?? [];

        return $order;
    }

    public static function findBasic(PDO $db, int $orderId, int $companyId): ?array
    {
        $st = $db->prepare('SELECT * FROM orders WHERE id = ? AND company_id = ?');
        $st->execute([$orderId, $companyId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function findForKds(PDO $db, int $orderId, int $companyId): ?array
    {
        $order = self::findBasic($db, $orderId, $companyId);

        if (!$order) {
            return null;
        }
        $items = self::itemsForOrders($db, [$orderId]);
        $order['items'] = $items[$orderId] ?? [];

        // fetch company-specific SLA "avg_delivery_min_to"
        $companyAvgTo = 0;
        try {
            $stc = $db->prepare('SELECT avg_delivery_min_to FROM companies WHERE id = ?');
            $stc->execute([$companyId]);
            $crow = $stc->fetch(PDO::FETCH_ASSOC);
            $companyAvgTo = isset($crow['avg_delivery_min_to']) ? (int)$crow['avg_delivery_min_to'] : 0;
        } catch (PDOException $e) {
            $companyAvgTo = 0;
        }

        return self::serializeForKds($order, true, $companyAvgTo);
    }

    public static function updateStatus(PDO $db, int $orderId, int $companyId, string $status): bool
    {
        $allowed = ['pending','paid','completed','canceled'];

        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $updated = false;
        $previousStatus = null;
        $current = self::findBasic($db, $orderId, $companyId);
        if ($current) {
            $previousStatus = $current['status'] ?? null;
        }
        $sql = 'UPDATE orders SET status = :status, status_changed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND company_id = :company';
        $stmt = $db->prepare($sql);
        try {
            $updated = $stmt->execute([
                ':status'  => $status,
                ':id'      => $orderId,
                ':company' => $companyId,
            ]);
        } catch (PDOException $e) {
            if (stripos($e->getMessage(), 'status_changed_at') !== false || stripos($e->getMessage(), 'updated_at') !== false) {
                $stmt = $db->prepare('UPDATE orders SET status = :status WHERE id = :id AND company_id = :company');
                $updated = $stmt->execute([
                    ':status'  => $status,
                    ':id'      => $orderId,
                    ':company' => $companyId,
                ]);
            } else {
                throw $e;
            }
        }

        if ($updated) {
            $eventType = $status === 'canceled' ? 'order.canceled' : 'order.status_changed';
            $meta = [];
            if ($previousStatus !== null && $previousStatus !== $status) {
                $meta['status_change'] = [
                    'from' => $previousStatus,
                    'to' => $status,
                ];
            }
            self::emitOrderEvent($db, $orderId, $companyId, $eventType, $meta);

            // Programa de Fidelidade: incrementar progresso quando pedido é completado
            if ($status === 'completed') {
                try {
                    require_once __DIR__ . '/LoyaltyProgram.php';
                    $order = self::findBasic($db, $orderId, $companyId);
                    if ($order && !empty($order['customer_phone'])) {
                        $phone = preg_replace('/[^0-9]/', '', $order['customer_phone']);
                        $custStmt = $db->prepare('SELECT id FROM customers WHERE company_id = ? AND (whatsapp = ? OR whatsapp_e164 = ?) LIMIT 1');
                        $custStmt->execute([$companyId, $phone, $phone]);
                        $cust = $custStmt->fetch(\PDO::FETCH_ASSOC);
                        if ($cust) {
                            LoyaltyProgram::incrementProgress($db, (int)$cust['id'], $companyId, $phone);
                        }
                    }
                } catch (\Exception $e) {
                    error_log("Erro ao incrementar fidelidade no updateStatus: " . $e->getMessage());
                }
            }
        }

        return $updated;
    }

    public static function updatePendingOrder(PDO $db, int $orderId, int $companyId, array $orderData, array $items): bool
    {
        $order = self::findWithItems($db, $orderId, $companyId);

        if (!$order) {
            return false;
        }

        if (($order['status'] ?? '') !== 'pending') {
            return false;
        }

        if (($order['source'] ?? '') === 'ifood') {
            return false;
        }

        $beforeSummary = self::summarizeForEvent($order, $order['items'] ?? []);

        $normalizedItems = [];
        $subtotal = 0.0;

        foreach ($items as $item) {
            $qty = (int)($item['quantity'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $unit = (float)($item['unit_price'] ?? 0);
            $line = isset($item['line_total']) ? (float)$item['line_total'] : $unit * $qty;
            $normalizedItems[] = [
                'product_id' => (int)($item['product_id'] ?? 0),
                'quantity' => $qty,
                'unit_price' => $unit,
                'line_total' => $line,
                'combo_data' => $item['combo_data'] ?? null,
                'customization_data' => $item['customization_data'] ?? null,
                'notes' => $item['notes'] ?? null,
            ];
            $subtotal += $line;
        }

        if (empty($normalizedItems)) {
            return false;
        }

        $deliveryFee = (float)($orderData['delivery_fee'] ?? 0);
        $discount = (float)($orderData['discount'] ?? 0);
        $loyaltyDiscount = (float)($order['loyalty_discount'] ?? 0);
        $total = max(0, $subtotal + $deliveryFee - $discount - $loyaltyDiscount);

        $payload = [
            'customer_name' => $orderData['customer_name'] ?? ($order['customer_name'] ?? ''),
            'customer_phone' => $orderData['customer_phone'] ?? ($order['customer_phone'] ?? ''),
            'customer_address' => $orderData['customer_address'] ?? ($order['customer_address'] ?? null),
            'payment_method_id' => $orderData['payment_method_id'] ?? ($order['payment_method_id'] ?? null),
            'notes' => $orderData['notes'] ?? ($order['notes'] ?? null),
        ];
        if ($payload['customer_address'] === '') {
            $payload['customer_address'] = null;
        }

        try {
            $db->beginTransaction();

            $sql = 'UPDATE orders
                    SET customer_name = :name,
                        customer_phone = :phone,
                        customer_address = :address,
                        payment_method_id = :payment_method_id,
                        subtotal = :subtotal,
                        delivery_fee = :delivery_fee,
                        discount = :discount,
                        total = :total,
                        notes = :notes,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id AND company_id = :company';
            $stmt = $db->prepare($sql);
            try {
                $stmt->execute([
                    ':name' => $payload['customer_name'],
                    ':phone' => $payload['customer_phone'],
                    ':address' => $payload['customer_address'],
                    ':payment_method_id' => $payload['payment_method_id'],
                    ':subtotal' => $subtotal,
                    ':delivery_fee' => $deliveryFee,
                    ':discount' => $discount,
                    ':total' => $total,
                    ':notes' => $payload['notes'],
                    ':id' => $orderId,
                    ':company' => $companyId,
                ]);
            } catch (PDOException $e) {
                if (stripos($e->getMessage(), 'updated_at') !== false) {
                    $stmt = $db->prepare('UPDATE orders
                                          SET customer_name = :name,
                                              customer_phone = :phone,
                                              customer_address = :address,
                                              payment_method_id = :payment_method_id,
                                              subtotal = :subtotal,
                                              delivery_fee = :delivery_fee,
                                              discount = :discount,
                                              total = :total,
                                              notes = :notes
                                          WHERE id = :id AND company_id = :company');
                    $stmt->execute([
                        ':name' => $payload['customer_name'],
                        ':phone' => $payload['customer_phone'],
                        ':address' => $payload['customer_address'],
                        ':payment_method_id' => $payload['payment_method_id'],
                        ':subtotal' => $subtotal,
                        ':delivery_fee' => $deliveryFee,
                        ':discount' => $discount,
                        ':total' => $total,
                        ':notes' => $payload['notes'],
                        ':id' => $orderId,
                        ':company' => $companyId,
                    ]);
                } else {
                    throw $e;
                }
            }

            $db->prepare('DELETE FROM order_items WHERE order_id = ?')->execute([$orderId]);
            foreach ($normalizedItems as $item) {
                self::addItem($db, $orderId, $item);
            }

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        $updatedOrder = self::findWithItems($db, $orderId, $companyId);
        if ($updatedOrder) {
            $afterSummary = self::summarizeForEvent($updatedOrder, $updatedOrder['items'] ?? []);
            self::emitOrderEvent($db, $orderId, $companyId, 'order.updated', [
                'order_update' => [
                    'before' => $beforeSummary,
                    'after' => $afterSummary,
                ],
            ]);
        }

        return true;
    }

    public static function delete(PDO $db, int $orderId, int $companyId): bool
    {
        // Primeiro, obtém o order_number antes de deletar para poder reutilizá-lo
        $order = self::findBasic($db, $orderId, $companyId);
        $orderNumber = $order['order_number'] ?? 0;
        
        $st = $db->prepare('DELETE FROM orders WHERE id = ? AND company_id = ?');
        $result = $st->execute([$orderId, $companyId]);
        
        // Se deletou com sucesso e tinha order_number, libera para reutilização
        if ($result && $st->rowCount() > 0 && $orderNumber > 0) {
            self::releaseOrderNumber($db, $companyId, (int)$orderNumber);
        }

        return $result;
    }

    public static function listRecentByCompany(int $companyId, int $limit = 8): array
    {
        $pdo = db();
        $sql = 'SELECT id, customer_name, total, status, created_at
                FROM orders
                WHERE company_id = :cid
                ORDER BY created_at DESC, id DESC
                LIMIT :lim';
        $st = $pdo->prepare($sql);
        $st->bindValue(':cid', $companyId, PDO::PARAM_INT);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function snapshot(PDO $db, int $companyId): array
    {
        $sql = "SELECT *
                FROM orders
                WHERE company_id = :cid
                  AND status IN ('pending','paid','completed','canceled')
                ORDER BY created_at ASC, id ASC
                LIMIT 150";
        $st = $db->prepare($sql);
        $st->execute([':cid' => $companyId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return [];
        }
        $orderIds = array_map(static fn ($row) => (int)$row['id'], $rows);
        $itemsMap = self::itemsForOrders($db, $orderIds);
        // fetch company-specific SLA "avg_delivery_min_to" once to avoid repeated queries
        $companyAvgTo = 0;
        try {
            $stc = $db->prepare('SELECT avg_delivery_min_to FROM companies WHERE id = ?');
            $stc->execute([$companyId]);
            $crow = $stc->fetch(PDO::FETCH_ASSOC);
            $companyAvgTo = isset($crow['avg_delivery_min_to']) ? (int)$crow['avg_delivery_min_to'] : 0;
        } catch (PDOException $e) {
            $companyAvgTo = 0;
        }

        $result = [];

        foreach ($rows as $row) {
            $row['items'] = $itemsMap[$row['id']] ?? [];
            $result[] = self::serializeForKds($row, true, $companyAvgTo);
        }

        return $result;
    }

    public static function snapshotDelta(PDO $db, int $companyId, string $sinceIso): array
    {
        $sinceIso = trim($sinceIso);
        $sinceTs = $sinceIso !== '' ? strtotime($sinceIso) : false;

        if (!$sinceTs) {
            return [
                'orders'       => self::snapshot($db, $companyId),
                'removed_ids'  => [],
                'full_refresh' => true,
            ];
        }

        $sinceDb = date('Y-m-d H:i:s', $sinceTs);
        $statusFilter = ['pending','paid','completed','canceled'];

        $rows = [];
        $executed = false;
        $expressions = [
            'COALESCE(status_changed_at, updated_at, created_at)',
            'COALESCE(updated_at, created_at)',
            'created_at',
        ];

        foreach ($expressions as $expr) {
            $sql = "SELECT *
                    FROM orders
                    WHERE company_id = :cid
                      AND $expr >= :since
                    ORDER BY created_at ASC, id ASC
                    LIMIT 150";
            try {
                $st = $db->prepare($sql);
                $st->execute([
                    ':cid'   => $companyId,
                    ':since' => $sinceDb,
                ]);
                $rows = $st->fetchAll(PDO::FETCH_ASSOC);
                $executed = true;
                break;
            } catch (PDOException $e) {
                continue;
            }
        }

        if (!$executed) {
            return [
                'orders'       => self::snapshot($db, $companyId),
                'removed_ids'  => [],
                'full_refresh' => true,
            ];
        }

        if (!$rows) {
            return [
                'orders'       => [],
                'removed_ids'  => [],
                'full_refresh' => false,
            ];
        }

        $activeRows = [];
        $removed = [];

        foreach ($rows as $row) {
            $status = (string)($row['status'] ?? '');

            if (!in_array($status, $statusFilter, true)) {
                $removed[] = (int)$row['id'];
                continue;
            }
            $activeRows[] = $row;
        }

        $orders = [];
        // fetch company-specific SLA "avg_delivery_min_to"
        $companyAvgTo = 0;
        try {
            $stc = $db->prepare('SELECT avg_delivery_min_to FROM companies WHERE id = ?');
            $stc->execute([$companyId]);
            $crow = $stc->fetch(PDO::FETCH_ASSOC);
            $companyAvgTo = isset($crow['avg_delivery_min_to']) ? (int)$crow['avg_delivery_min_to'] : 0;
        } catch (PDOException $e) {
            $companyAvgTo = 0;
        }

        if ($activeRows) {
            $orderIds = array_map(static fn ($row) => (int)$row['id'], $activeRows);
            $itemsMap = self::itemsForOrders($db, $orderIds);

            foreach ($activeRows as $row) {
                $row['items'] = $itemsMap[$row['id']] ?? [];
                $orders[] = self::serializeForKds($row, true, $companyAvgTo);
            }
        }

        return [
            'orders'       => $orders,
            'removed_ids'  => $removed,
            'full_refresh' => false,
        ];
    }

    public static function latestChangeToken(PDO $db, int $companyId): ?string
    {
        $candidates = [
            'COALESCE(status_changed_at, updated_at, created_at)',
            'COALESCE(updated_at, created_at)',
            'created_at',
        ];

        foreach ($candidates as $expression) {
            $sql = "SELECT MAX($expression) AS last_change FROM orders WHERE company_id = :cid";
            try {
                $st = $db->prepare($sql);
                $st->execute([':cid' => $companyId]);
                $row = $st->fetch(PDO::FETCH_ASSOC);

                if (!$row || empty($row['last_change'])) {
                    continue;
                }
                $ts = strtotime((string)$row['last_change']);

                if ($ts) {
                    return gmdate('c', $ts);
                }
            } catch (PDOException $e) {
                continue;
            }
        }

        return null;
    }

    private static function itemsForOrders(PDO $db, array $orderIds): array
    {
        if (!$orderIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $sql = "SELECT oi.*, p.name AS product_name
                FROM order_items oi
                LEFT JOIN products p ON p.id = oi.product_id
                WHERE oi.order_id IN ($placeholders)
                ORDER BY oi.id";
        $st = $db->prepare($sql);
        $st->execute($orderIds);
        $map = [];

        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $orderId = (int)$row['order_id'];

            if (!isset($map[$orderId])) {
                $map[$orderId] = [];
            }
            $map[$orderId][] = $row;
        }

        return $map;
    }

    public static function serializeForKds(array $order, bool $withItems = true, int $companyAvgTo = 0): array
    {
        $formatIso = function ($value) {
            if (!$value) {
                return null;
            }
            $ts = strtotime((string)$value);

            return $ts ? gmdate('c', $ts) : null;
        };

        $result = [
            'id' => (int)($order['id'] ?? 0),
            'order_number' => (int)($order['order_number'] ?? $order['id'] ?? 0),
            'company_id' => (int)($order['company_id'] ?? 0),
            'status' => (string)($order['status'] ?? 'pending'),
            'customer_name' => (string)($order['customer_name'] ?? ''),
            'customer_phone' => (string)($order['customer_phone'] ?? ''),
            'customer_address' => (string)($order['customer_address'] ?? ''),
            'notes' => $order['notes'] ?? '',
            'subtotal' => (float)($order['subtotal'] ?? 0),
            'delivery_fee' => (float)($order['delivery_fee'] ?? 0),
            'discount' => (float)($order['discount'] ?? 0),
            'total' => (float)($order['total'] ?? 0),
            'created_at' => $formatIso($order['created_at'] ?? null),
            'updated_at' => $formatIso($order['updated_at'] ?? null),
            'status_changed_at' => $formatIso($order['status_changed_at'] ?? null),
        ];

        if (!empty($order['sla_deadline'])) {
            $result['sla_deadline'] = $formatIso($order['sla_deadline']);
        } else {
            // prefer company-specific average "to" when provided, else fall back to config
            $slaMinutes = $companyAvgTo > 0 ? $companyAvgTo : (int)(function_exists('config') ? (config('kds_sla_minutes') ?? 20) : 20);
            $createdAt = $order['created_at'] ?? null;

            if ($slaMinutes > 0 && $createdAt) {
                $deadline = strtotime($createdAt . ' +' . $slaMinutes . ' minutes');

                if ($deadline) {
                    $result['sla_deadline'] = gmdate('c', $deadline);
                }
            }
        }

        if ($withItems) {
            $items = [];
            $source = $order['items'] ?? [];

            foreach ($source as $item) {
                $items[] = self::formatItemForKds($item);
            }
            $result['items'] = $items;
        }

        return $result;
    }

    private static function formatItemForKds(array $item): array
    {
        $name = $item['product_name'] ?? $item['name'] ?? '';
        $quantity = (int)($item['quantity'] ?? $item['qty'] ?? 0);
        $lineTotal = (float)($item['line_total'] ?? $item['total'] ?? 0);

        return [
            'id' => (int)($item['id'] ?? 0),
            'product_id' => (int)($item['product_id'] ?? 0),
            'name' => (string)$name,
            'qty' => $quantity,
            'quantity' => $quantity,
            'unit_price' => (float)($item['unit_price'] ?? 0),
            'line_total' => $lineTotal,
        ];
    }

    public static function emitOrderEvent(PDO $db, int $orderId, int $companyId, string $eventType, array $meta = []): void
    {
        $order = self::findBasic($db, $orderId, $companyId);

        if (!$order) {
            return;
        }
        $items = self::itemsForOrders($db, [$orderId]);
        $order['items'] = $items[$orderId] ?? [];
        // fetch company-specific SLA "avg_delivery_min_to"
        $companyAvgTo = 0;
        try {
            $stc = $db->prepare('SELECT avg_delivery_min_to FROM companies WHERE id = ?');
            $stc->execute([$companyId]);
            $crow = $stc->fetch(PDO::FETCH_ASSOC);
            $companyAvgTo = isset($crow['avg_delivery_min_to']) ? (int)$crow['avg_delivery_min_to'] : 0;
        } catch (PDOException $e) {
            $companyAvgTo = 0;
        }

        $payload = [
            'order' => self::serializeForKds($order, true, $companyAvgTo),
            'created_at' => gmdate('c'),
        ];
        if (!empty($meta)) {
            $payload['meta'] = $meta;
        }
        self::logEvent($db, $orderId, $companyId, $eventType, $order['status'] ?? null, $payload);
        
        // NOTA: Notificação de pedido é enviada pelo PublicCartController após criar o pedido
        // Não duplicar aqui para evitar envio múltiplo
    }

    private static function logEvent(PDO $db, int $orderId, int $companyId, string $eventType, ?string $status, array $payload = []): void
    {
        $sql = 'INSERT INTO order_events (order_id, company_id, event_type, status, payload)
                VALUES (:order_id, :company_id, :event_type, :status, :payload)';
        $stmt = $db->prepare($sql);
        try {
            $stmt->execute([
                ':order_id'   => $orderId,
                ':company_id' => $companyId,
                ':event_type' => $eventType,
                ':status'     => $status,
                ':payload'    => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        } catch (PDOException $e) {
            // Banco sem tabela de eventos -> ignora
            if (stripos($e->getMessage(), 'order_events') === false) {
                throw $e;
            }
        }
    }

    public static function eventsForOrder(PDO $db, int $companyId, int $orderId, int $limit = 50): array
    {
        try {
            $sql = 'SELECT id, order_id, company_id, event_type, status, payload, created_at
                    FROM order_events
                    WHERE company_id = :cid AND order_id = :oid
                    ORDER BY id DESC
                    LIMIT :lim';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':cid', $companyId, PDO::PARAM_INT);
            $stmt->bindValue(':oid', $orderId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            if (stripos($e->getMessage(), 'order_events') !== false) {
                return [];
            }
            throw $e;
        }

        $events = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $payload = null;
            if (!empty($row['payload'])) {
                $decoded = json_decode($row['payload'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $payload = $decoded;
                }
            }
            $events[] = [
                'id' => (int)$row['id'],
                'order_id' => (int)$row['order_id'],
                'company_id' => (int)$row['company_id'],
                'event_type' => $row['event_type'],
                'status' => $row['status'],
                'payload' => $payload,
                'created_at' => $row['created_at'],
            ];
        }

        return $events;
    }

    private static function summarizeForEvent(array $order, array $items): array
    {
        $summaryItems = [];
        foreach ($items as $item) {
            $qty = (int)($item['quantity'] ?? 0);
            $unit = (float)($item['unit_price'] ?? 0);
            $line = (float)($item['line_total'] ?? ($unit * $qty));
            $summaryItems[] = [
                'product_id' => (int)($item['product_id'] ?? 0),
                'name' => (string)($item['product_name'] ?? $item['name'] ?? ''),
                'quantity' => $qty,
                'unit_price' => $unit,
                'line_total' => $line,
                'customization_data' => $item['customization_data'] ?? null,
            ];
        }

        return [
            'customer_name' => (string)($order['customer_name'] ?? ''),
            'customer_phone' => (string)($order['customer_phone'] ?? ''),
            'customer_address' => (string)($order['customer_address'] ?? ''),
            'payment_method_id' => $order['payment_method_id'] ?? null,
            'notes' => $order['notes'] ?? '',
            'subtotal' => (float)($order['subtotal'] ?? 0),
            'delivery_fee' => (float)($order['delivery_fee'] ?? 0),
            'discount' => (float)($order['discount'] ?? 0),
            'loyalty_discount' => (float)($order['loyalty_discount'] ?? 0),
            'total' => (float)($order['total'] ?? 0),
            'items' => $summaryItems,
        ];
    }

    public static function latestEvents(PDO $db, int $companyId, int $afterId = 0, int $limit = 100): array
    {
        try {
            $sql = 'SELECT id, order_id, company_id, event_type, status, payload, created_at
                    FROM order_events
                    WHERE company_id = :cid AND id > :after
                    ORDER BY id ASC
                    LIMIT :lim';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':cid', $companyId, PDO::PARAM_INT);
            $stmt->bindValue(':after', $afterId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            if (stripos($e->getMessage(), 'order_events') !== false) {
                return [];
            }
            throw $e;
        }

        $events = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $payload = null;

            if (!empty($row['payload'])) {
                $decoded = json_decode($row['payload'], true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $payload = $decoded;
                }
            }
            $events[] = [
                'id'         => (int)$row['id'],
                'order_id'   => (int)$row['order_id'],
                'company_id' => (int)$row['company_id'],
                'event_type' => $row['event_type'],
                'status'     => $row['status'],
                'payload'    => $payload,
                'created_at' => $row['created_at'],
            ];
        }

        return $events;
    }

    public static function lastEventId(PDO $db, int $companyId): int
    {
        try {
            $stmt = $db->prepare('SELECT MAX(id) AS max_id FROM order_events WHERE company_id = :cid');
            $stmt->execute([':cid' => $companyId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)($row['max_id'] ?? 0);
        } catch (PDOException $e) {
            if (stripos($e->getMessage(), 'order_events') !== false) {
                return 0;
            }
            throw $e;
        }
    }

    // ========= MÉTODOS MOBILE DASHBOARD =========

    /**
     * Conta pedidos de hoje para uma empresa
     */
    public static function countTodayByCompany(PDO $db, int $companyId): int
    {
        $sql = "SELECT COUNT(*) FROM orders 
                WHERE company_id = :cid 
                AND DATE(created_at) = CURDATE()";
        $st = $db->prepare($sql);
        $st->execute([':cid' => $companyId]);
        return (int)$st->fetchColumn();
    }

    /**
     * Conta pedidos pendentes para uma empresa
     */
    public static function countPendingByCompany(PDO $db, int $companyId): int
    {
        $sql = "SELECT COUNT(*) FROM orders 
                WHERE company_id = :cid 
                AND status = 'pending'";
        $st = $db->prepare($sql);
        $st->execute([':cid' => $companyId]);
        return (int)$st->fetchColumn();
    }

    /**
     * Soma faturamento de hoje para uma empresa
     */
    public static function sumTodayByCompany(PDO $db, int $companyId): float
    {
        $sql = "SELECT COALESCE(SUM(total), 0) FROM orders 
                WHERE company_id = :cid 
                AND DATE(created_at) = CURDATE()
                AND status NOT IN ('cancelled', 'canceled')";
        $st = $db->prepare($sql);
        $st->execute([':cid' => $companyId]);
        return (float)$st->fetchColumn();
    }

    /**
     * Conta pedidos por empresa e status específico
     */
    public static function countByCompanyAndStatus(PDO $db, int $companyId, string $status): int
    {
        $sql = "SELECT COUNT(*) FROM orders 
                WHERE company_id = :cid 
                AND status = :status";
        $st = $db->prepare($sql);
        $st->execute([':cid' => $companyId, ':status' => $status]);
        return (int)$st->fetchColumn();
    }

    /**
     * Lista pedidos por empresa e status
     */
    public static function listByCompanyAndStatus(int $companyId, string $status, int $limit = 50, int $offset = 0): array
    {
        $db = db();
        $sql = 'SELECT o.*, 
                       (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count,
                       (SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.order_id = o.id) as items_qty
                FROM orders o 
                WHERE o.company_id = :cid
                AND o.status = :status
                ORDER BY o.id DESC 
                LIMIT :lim OFFSET :off';
        $st = $db->prepare($sql);
        $st->bindValue(':cid', $companyId, PDO::PARAM_INT);
        $st->bindValue(':status', $status, PDO::PARAM_STR);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca pedido simples por ID (opcionalmente filtrado por company_id)
     */
    public static function find(int $orderId, ?int $companyId = null): ?array
    {
        $db = db();
        $sql = 'SELECT * FROM orders WHERE id = ?';
        $params = [$orderId];
        if ($companyId !== null) {
            $sql .= ' AND company_id = ?';
            $params[] = $companyId;
        }
        $st = $db->prepare($sql);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Busca itens de um pedido
     */
    public static function getItems(int $orderId, ?int $companyId = null): array
    {
        $db = db();
        $params = [$orderId];

        if ($companyId !== null) {
            $sql = 'SELECT oi.*, p.name as product_name, p.image as product_image
                    FROM order_items oi
                    INNER JOIN orders o ON o.id = oi.order_id
                    LEFT JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = ? AND o.company_id = ?
                    ORDER BY oi.id ASC';
            $params[] = $companyId;
        } else {
            $sql = 'SELECT oi.*, p.name as product_name, p.image as product_image
                    FROM order_items oi
                    LEFT JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = ?
                    ORDER BY oi.id ASC';
        }

        $st = $db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Último pedido pago/completado de um cliente por telefone
     */
    public static function lastCompletedByPhone(PDO $db, string $phone, int $companyId): ?array
    {
        $normalized = preg_replace('/[^0-9]/', '', $phone);
        $stmt = $db->prepare("
            SELECT o.id, o.total, o.created_at
            FROM orders o
            WHERE o.company_id = ?
              AND o.status = 'completed'
              AND REPLACE(REPLACE(REPLACE(o.customer_phone, '+', ''), '-', ''), ' ', '') = ?
            ORDER BY o.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$companyId, $normalized]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Itens de um pedido filtrados por produtos ainda ativos
     */
    public static function getReorderableItems(int $orderId, int $companyId): array
    {
        $db = db();
        $st = $db->prepare("
            SELECT oi.product_id, oi.quantity, oi.combo_data, oi.customization_data,
                   p.name as product_name, p.price, p.promo_price, p.image as product_image
            FROM order_items oi
            INNER JOIN products p ON p.id = oi.product_id AND p.company_id = ? AND p.active = 1
            WHERE oi.order_id = ?
            ORDER BY oi.id ASC
        ");
        $st->execute([$companyId, $orderId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}

