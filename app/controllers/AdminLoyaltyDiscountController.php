<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

class AdminLoyaltyDiscountController extends Controller
{
    private function guard($slug)
    {
        Auth::start();
        $u = Auth::user();

        if (!$u) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
            exit;
        }
        $company = Company::findBySlug($slug);

        if (!$company) {
            echo 'Empresa inválida';
            exit;
        }

        if ($u['role'] !== 'root' && (int)$u['company_id'] !== (int)$company['id']) {
            echo 'Acesso negado';
            exit;
        }

        return [$u, $company];
    }

    public function index($params)
    {
        $slug = $params['slug'] ?? '';
        [$user, $company] = $this->guard($slug);

        $db = db();
        
        // Busca taxa embutida para desconto de entrega
        $stmt = $db->prepare("SELECT embedded_delivery_fee, coupon_prefix FROM companies WHERE id = :id");
        $stmt->execute(['id' => $company['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $embedded_delivery_fee = $result['embedded_delivery_fee'] ?? 0.00;
        $couponPrefix = $result['coupon_prefix'] ?? 'WOLL';

        // Busca configurações de desconto por preenchimento de dados
        $stmt = $db->prepare("SELECT is_active, discount_percentage, welcome_message FROM loyalty_discounts WHERE company_id = :id");
        $stmt->execute(['id' => $company['id']]);
        $loyalty = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $loyaltyActive = $loyalty ? (int)$loyalty['is_active'] : 0;
        $loyaltyDiscount = $loyalty ? (float)$loyalty['discount_percentage'] : 0;
        $loyaltyMessage = $loyalty ? ($loyalty['welcome_message'] ?? '') : '';

        // Buscar todos os cupons da empresa
        $stmt = $db->prepare('
            SELECT id, coupon_code, customer_phone, discount_percentage, 
                   usage_limit, times_used, is_used, used_at, created_at
            FROM customer_loyalty_coupons 
            WHERE company_id = ?
            ORDER BY created_at DESC
        ');
        $stmt->execute([$company['id']]);
        $cupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular estatísticas dos cupons
        $cupons_stats = [
            'total' => count($cupons),
            'active' => 0,
            'used' => 0,
            'totalUsage' => 0
        ];

        foreach ($cupons as $cupom) {
            $used = (int)$cupom['is_used'] === 1;
            $timesUsed = (int)($cupom['times_used'] ?? 0);
            $usageLimit = (int)($cupom['usage_limit'] ?? 1);
            
            if ($used || ($usageLimit > 0 && $timesUsed >= $usageLimit)) {
                $cupons_stats['used']++;
            } else {
                $cupons_stats['active']++;
            }
            
            $cupons_stats['totalUsage'] += $timesUsed;
        }

        // Verifica se há mensagem de sucesso
        $success = null;
        if (isset($_GET['success'])) {
            $success = match($_GET['success']) {
                '1' => 'Configurações salvas com sucesso!',
                'created' => 'Cupom criado com sucesso!',
                'updated' => 'Cupom atualizado com sucesso!',
                'deleted' => 'Cupom excluído com sucesso!',
                default => 'Operação realizada com sucesso!'
            };
        }
        
        // Verifica se há mensagem de erro
        $error = null;
        if (isset($_GET['error'])) {
            $error = match($_GET['error']) {
                'delete_failed' => 'Erro ao excluir cupom. Tente novamente.',
                default => 'Ocorreu um erro na operação.'
            };
        }
        
        // Verifica se há mensagem na sessão
        if (isset($_SESSION['success_message'])) {
            $success = $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        }
        if (isset($_SESSION['error_message'])) {
            $error = $_SESSION['error_message'];
            unset($_SESSION['error_message']);
        }

        // Buscar produtos e categorias para seletor de taxa embutida
        $stmtCats = $db->prepare('SELECT id, name FROM categories WHERE company_id = ? AND active = 1 ORDER BY sort_order, name');
        $stmtCats->execute([$company['id']]);
        $categories = $stmtCats->fetchAll(PDO::FETCH_ASSOC);

        $stmtProds = $db->prepare('SELECT id, name, category_id, price, image, embedded_fee_enabled FROM products WHERE company_id = ? AND active = 1 AND deleted_at IS NULL ORDER BY sort_order, name');
        $stmtProds->execute([$company['id']]);
        $allProducts = $stmtProds->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/loyalty-discount/index', [
            'user' => $user,
            'company' => $company,
            'slug' => $slug,
            'embedded_delivery_fee' => number_format((float)$embedded_delivery_fee, 2, '.', ''),
            'loyalty_active' => $loyaltyActive,
            'loyalty_discount' => number_format($loyaltyDiscount, 2, '.', ''),
            'loyalty_message' => $loyaltyMessage,
            'coupon_prefix' => $couponPrefix,
            'cupons' => $cupons,
            'cupons_stats' => $cupons_stats,
            'categories' => $categories,
            'allProducts' => $allProducts,
            'success' => $success,
            'error' => $error
        ]);
    }

    public function save($params)
    {
        $slug = $params['slug'] ?? '';
        [$user, $company] = $this->guard($slug);

        $db = db();

        // Taxa embutida para desconto de entrega
        $embedded_delivery_fee = $_POST['embedded_delivery_fee'] ?? 0;
        $embedded_delivery_fee = (float) str_replace(',', '.', $embedded_delivery_fee);
        if ($embedded_delivery_fee < 0) {
            $embedded_delivery_fee = 0;
        }

        $stmt = $db->prepare("UPDATE companies SET embedded_delivery_fee = :fee WHERE id = :id");
        $stmt->execute([
            'fee' => $embedded_delivery_fee,
            'id' => $company['id']
        ]);

        // Atualizar quais produtos participam da taxa embutida
        $selectedProductIds = $_POST['embedded_fee_products'] ?? [];
        $selectedProductIds = array_map('intval', array_filter($selectedProductIds));
        $selectorPresent = !empty($_POST['embedded_fee_products_present']);

        if ($selectorPresent) {
            // Desabilitar todos primeiro
            $stmtDisable = $db->prepare('UPDATE products SET embedded_fee_enabled = 0 WHERE company_id = ? AND active = 1 AND deleted_at IS NULL');
            $stmtDisable->execute([$company['id']]);

            // Habilitar apenas os selecionados (se houver)
            if (!empty($selectedProductIds)) {
                $placeholders = implode(',', array_fill(0, count($selectedProductIds), '?'));
                $stmtEnable = $db->prepare("UPDATE products SET embedded_fee_enabled = 1 WHERE company_id = ? AND id IN ($placeholders)");
                $stmtEnable->execute(array_merge([$company['id']], $selectedProductIds));
            }
        }

        // Invalidar cache de produtos
        require_once __DIR__ . '/../services/SmartCache.php';
        \SmartCache::forgetByPattern('products:*');

        // Desconto por preenchimento de dados (CPF + Data de nascimento)
        $loyaltyActive = isset($_POST['loyalty_active']) ? 1 : 0;
        $loyaltyDiscount = $_POST['loyalty_discount'] ?? 0;
        $loyaltyDiscount = (float) str_replace(',', '.', $loyaltyDiscount);
        if ($loyaltyDiscount < 0) $loyaltyDiscount = 0;
        if ($loyaltyDiscount > 100) $loyaltyDiscount = 100;
        
        $loyaltyMessage = trim($_POST['loyalty_message'] ?? '');
        
        // Prefixo do cupom
        $couponPrefix = strtoupper(trim($_POST['coupon_prefix'] ?? 'WOLL'));
        if (strlen($couponPrefix) > 10) {
            $couponPrefix = substr($couponPrefix, 0, 10);
        }
        if (empty($couponPrefix)) {
            $couponPrefix = 'WOLL';
        }

        // Atualizar prefixo do cupom na tabela companies
        $stmt = $db->prepare("UPDATE companies SET coupon_prefix = :prefix WHERE id = :id");
        $stmt->execute([
            'prefix' => $couponPrefix,
            'id' => $company['id']
        ]);

        // Insert or Update loyalty discount
        $stmt = $db->prepare("
            INSERT INTO loyalty_discounts (company_id, is_active, discount_percentage, welcome_message)
            VALUES (:company_id, :is_active, :discount, :message)
            ON DUPLICATE KEY UPDATE 
                is_active = VALUES(is_active),
                discount_percentage = VALUES(discount_percentage),
                welcome_message = VALUES(welcome_message)
        ");
        $stmt->execute([
            'company_id' => $company['id'],
            'is_active' => $loyaltyActive,
            'discount' => $loyaltyDiscount,
            'message' => $loyaltyMessage
        ]);

        header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/loyalty-discount?success=1'));
        exit;
    }

    /**
     * Criar cupom manual
     */
    public function createCoupon($params)
    {
        header('Content-Type: application/json');
        
        $slug = $params['slug'] ?? '';
        [$user, $company] = $this->guard($slug);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $code = strtoupper(trim($input['code'] ?? ''));
            $phone = trim($input['phone'] ?? '');
            $discount = (float)($input['discount'] ?? 0);
            $limit = (int)($input['limit'] ?? 1);

            if ($discount < 1 || $discount > 100) {
                echo json_encode(['success' => false, 'message' => 'Desconto deve estar entre 1% e 100%']);
                exit;
            }

            if ($limit < 0) {
                $limit = 0; // 0 = ilimitado
            }

            $db = db();

            // Se tem telefone, verificar se já tem cupom para esse cliente
            if (!empty($phone)) {
                $stmt = $db->prepare('SELECT id FROM customer_loyalty_coupons WHERE company_id = ? AND customer_phone = ?');
                $stmt->execute([$company['id'], $phone]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Cliente já possui um cupom cadastrado']);
                    exit;
                }
            }

            // Gerar código se não foi fornecido
            if (empty($code)) {
                $code = 'CUPOM' . strtoupper(substr(md5(uniqid() . time()), 0, 6));
            }

            // Verificar se código já existe
            $stmt = $db->prepare('SELECT id FROM customer_loyalty_coupons WHERE company_id = ? AND coupon_code = ?');
            $stmt->execute([$company['id'], $code]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Código de cupom já existe']);
                exit;
            }

            // Criar cupom (phone pode ser NULL para cupons genéricos)
            $stmt = $db->prepare('
                INSERT INTO customer_loyalty_coupons 
                (company_id, customer_phone, coupon_code, discount_percentage, usage_limit, times_used, is_used, created_at)
                VALUES (?, ?, ?, ?, ?, 0, 0, NOW())
            ');
            $stmt->execute([
                $company['id'], 
                !empty($phone) ? $phone : null, 
                $code, 
                $discount,
                $limit
            ]);

            echo json_encode([
                'success' => true,
                'coupon_code' => $code,
                'message' => 'Cupom criado com sucesso!'
            ]);
            exit;

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao criar cupom: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Listar cupons criados
     */
    public function listCoupons($params)
    {
        header('Content-Type: application/json');
        
        $slug = $params['slug'] ?? '';
        [$user, $company] = $this->guard($slug);

        try {
            $db = db();
            
            $stmt = $db->prepare('
                SELECT coupon_code, customer_phone, discount_percentage, is_used, used_at, created_at, usage_limit, times_used
                FROM customer_loyalty_coupons 
                WHERE company_id = ?
                ORDER BY created_at DESC
                LIMIT 50
            ');
            $stmt->execute([$company['id']]);
            $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'coupons' => $coupons
            ]);
            exit;

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao listar cupons']);
            exit;
        }
    }
}
