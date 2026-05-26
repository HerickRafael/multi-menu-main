<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/AdminGuard.php';

class AdminCouponsController extends Controller
{
    private function guard($slug)
    {
        return AdminGuard::requireCompanyAccess((string)$slug, true, 'coupons.manage');
    }

    public function index($params)
    {
        $slug = $params['slug'] ?? '';
        [$user, $company] = $this->guard($slug);

        $db = db();
        
        // Buscar todos os cupons da tabela customer_loyalty_coupons
        $stmt = $db->prepare('
            SELECT id, coupon_code, customer_phone, discount_percentage, 
                   usage_limit, times_used, is_used, used_at, created_at
            FROM customer_loyalty_coupons 
            WHERE company_id = ?
            ORDER BY created_at DESC
        ');
        $stmt->execute([$company['id']]);
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular estatísticas
        $stats = [
            'total' => count($coupons),
            'active' => 0,
            'used' => 0,
            'expired' => 0,
            'total_usage' => 0
        ];

        $now = time();
        foreach ($coupons as $coupon) {
            $used = (int)$coupon['is_used'] === 1;
            $timesUsed = (int)($coupon['times_used'] ?? 0);
            $usageLimit = (int)($coupon['usage_limit'] ?? 1);
            
            if ($used || ($usageLimit > 0 && $timesUsed >= $usageLimit)) {
                $stats['used']++;
            } else {
                $stats['active']++;
            }
            
            $stats['total_usage'] += $timesUsed;
        }

        // Mensagens de sucesso/erro
        $success = null;
        $error = null;

        if (isset($_GET['success'])) {
            $success = match($_GET['success']) {
                'created' => 'Cupom criado com sucesso!',
                'updated' => 'Cupom atualizado com sucesso!',
                'deleted' => 'Cupom excluído com sucesso!',
                default => 'Operação realizada com sucesso!'
            };
        }

        if (isset($_GET['error'])) {
            $error = match($_GET['error']) {
                'delete_failed' => 'Erro ao excluir cupom',
                default => 'Erro na operação'
            };
        }

        $this->view('admin/coupons/index', [
            'user' => $user,
            'company' => $company,
            'slug' => $slug,
            'coupons' => $coupons,
            'stats' => $stats,
            'success' => $success,
            'error' => $error
        ]);
    }

    public function create($params)
    {
        $slug = $params['slug'] ?? '';
        [$user, $company] = $this->guard($slug);

        $error = $_SESSION['coupon_error'] ?? null;
        unset($_SESSION['coupon_error']);

        $payload = [
            'coupon' => null,
            'usage_stats' => null,
            'flash' => ['error' => $error],
            'urls' => [
                'list' => '/admin/' . rawurlencode($slug) . '/loyalty-discount?section=cupons',
                'submit' => '/admin/' . rawurlencode($slug) . '/coupons/store',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_COUPON_FORM__', $payload);
    }

    public function store($params)
    {
        $slug = $params['slug'] ?? '';
        [$user, $company] = $this->guard($slug);

        $db = db();

        try {
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $phone = trim($_POST['customer_phone'] ?? '');
            $discountPercentage = (float)($_POST['discount_percentage'] ?? 0);
            $usageLimit = (int)($_POST['usage_limit'] ?? 0);
            $allowMultipleUsesPerCustomer = isset($_POST['allow_multiple_uses_per_customer']) ? 1 : 0;

            // Gerar código se não foi fornecido
            if (empty($code)) {
                $code = 'CUPOM' . strtoupper(substr(md5(uniqid() . time()), 0, 6));
            }

            // Validações
            if ($discountPercentage <= 0 || $discountPercentage > 100) {
                throw new Exception('Desconto deve estar entre 1% e 100%');
            }

            // Se tem telefone, verificar se já tem cupom ativo para esse cliente
            if (!empty($phone)) {
                $stmt = $db->prepare('
                    SELECT id FROM customer_loyalty_coupons 
                    WHERE company_id = ? AND customer_phone = ? AND is_used = 0
                ');
                $stmt->execute([$company['id'], $phone]);
                if ($stmt->fetch()) {
                    throw new Exception('Cliente já possui um cupom ativo');
                }
            }

            // Verificar se código já existe
            $stmt = $db->prepare('SELECT id FROM customer_loyalty_coupons WHERE company_id = ? AND coupon_code = ?');
            $stmt->execute([$company['id'], $code]);
            if ($stmt->fetch()) {
                throw new Exception('Código de cupom já existe');
            }

            // Inserir cupom
            $stmt = $db->prepare('
                INSERT INTO customer_loyalty_coupons 
                (company_id, customer_phone, coupon_code, discount_percentage, 
                 usage_limit, allow_multiple_uses_per_customer, times_used, is_used, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 0, 0, NOW())
            ');

            $stmt->execute([
                $company['id'],
                !empty($phone) ? $phone : null,
                $code,
                $discountPercentage,
                $usageLimit,
                $allowMultipleUsesPerCustomer
            ]);

            $_SESSION['success_message'] = 'Cupom criado com sucesso!';
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/loyalty-discount?section=cupons&success=created'));
            exit;

        } catch (Exception $e) {
            $_SESSION['coupon_error'] = $e->getMessage();
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/coupons/create'));
            exit;
        }
    }

    public function edit($params)
    {
        $slug = $params['slug'] ?? '';
        $id = (int)($params['id'] ?? 0);
        [$user, $company] = $this->guard($slug);

        $db = db();

        // Buscar cupom
        $stmt = $db->prepare('SELECT * FROM customer_loyalty_coupons WHERE id = ? AND company_id = ?');
        $stmt->execute([$id, $company['id']]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$coupon) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/loyalty-discount?section=cupons'));
            exit;
        }

        // Buscar estatísticas de uso (da tabela coupon_usage)
        $stmt = $db->prepare('
            SELECT COUNT(DISTINCT customer_phone) as unique_customers,
                   COUNT(*) as total_uses
            FROM coupon_usage 
            WHERE company_id = ? AND coupon_code = ?
        ');
        $stmt->execute([$company['id'], $coupon['coupon_code']]);
        $usage_stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $error = $_SESSION['coupon_error'] ?? null;
        unset($_SESSION['coupon_error']);

        $payload = [
            'coupon' => [
                'id' => (int)$coupon['id'],
                'coupon_code' => (string)($coupon['coupon_code'] ?? ''),
                'customer_phone' => (string)($coupon['customer_phone'] ?? ''),
                'discount_percentage' => isset($coupon['discount_percentage']) ? (float)$coupon['discount_percentage'] : 0,
                'usage_limit' => (int)($coupon['usage_limit'] ?? 0),
                'times_used' => (int)($coupon['times_used'] ?? 0),
                'is_used' => (int)($coupon['is_used'] ?? 0) === 1,
                'allow_multiple_uses_per_customer' => (int)($coupon['allow_multiple_uses_per_customer'] ?? 0) === 1,
            ],
            'usage_stats' => $usage_stats ? [
                'unique_customers' => (int)($usage_stats['unique_customers'] ?? 0),
                'total_uses' => (int)($usage_stats['total_uses'] ?? 0),
            ] : null,
            'flash' => ['error' => $error],
            'urls' => [
                'list' => '/admin/' . rawurlencode($slug) . '/loyalty-discount?section=cupons',
                'submit' => '/admin/' . rawurlencode($slug) . '/coupons/' . (int)$coupon['id'] . '/update',
                'destroy' => '/admin/' . rawurlencode($slug) . '/coupons/' . (int)$coupon['id'] . '/delete',
                'toggle' => '/admin/' . rawurlencode($slug) . '/coupons/' . (int)$coupon['id'] . '/toggle',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_COUPON_FORM__', $payload);
    }

    public function update($params)
    {
        $slug = $params['slug'] ?? '';
        $id = (int)($params['id'] ?? 0);
        [$user, $company] = $this->guard($slug);

        $db = db();

        try {
            // Verificar se cupom existe
            $stmt = $db->prepare('SELECT coupon_code FROM customer_loyalty_coupons WHERE id = ? AND company_id = ?');
            $stmt->execute([$id, $company['id']]);
            $existingCoupon = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingCoupon) {
                throw new Exception('Cupom não encontrado');
            }

            $code = strtoupper(trim($_POST['code'] ?? ''));
            $phone = trim($_POST['customer_phone'] ?? '');
            $discountPercentage = (float)($_POST['discount_percentage'] ?? 0);
            $usageLimit = (int)($_POST['usage_limit'] ?? 0);
            $allowMultipleUsesPerCustomer = isset($_POST['allow_multiple_uses_per_customer']) ? 1 : 0;

            // Validações
            if (empty($code)) {
                throw new Exception('Código do cupom é obrigatório');
            }

            if ($discountPercentage <= 0 || $discountPercentage > 100) {
                throw new Exception('Desconto deve estar entre 1% e 100%');
            }

            // Se mudou o código, verificar se novo código já existe
            if ($code !== $existingCoupon['coupon_code']) {
                $stmt = $db->prepare('SELECT id FROM customer_loyalty_coupons WHERE company_id = ? AND coupon_code = ? AND id != ?');
                $stmt->execute([$company['id'], $code, $id]);
                if ($stmt->fetch()) {
                    throw new Exception('Código de cupom já existe');
                }
            }

            // Atualizar cupom
            $stmt = $db->prepare('
                UPDATE customer_loyalty_coupons SET
                    coupon_code = ?, 
                    customer_phone = ?, 
                    discount_percentage = ?,
                    usage_limit = ?,
                    allow_multiple_uses_per_customer = ?
                WHERE id = ? AND company_id = ?
            ');

            $stmt->execute([
                $code,
                !empty($phone) ? $phone : null,
                $discountPercentage,
                $usageLimit,
                $allowMultipleUsesPerCustomer,
                $id,
                $company['id']
            ]);

            $_SESSION['success_message'] = 'Cupom atualizado com sucesso!';
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/loyalty-discount?section=cupons&success=updated'));
            exit;

        } catch (Exception $e) {
            $_SESSION['coupon_error'] = $e->getMessage();
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/coupons/' . $id . '/edit'));
            exit;
        }
    }

    public function delete($params)
    {
        $slug = $params['slug'] ?? '';
        $id = (int)($params['id'] ?? 0);
        [$user, $company] = $this->guard($slug);

        $db = db();

        try {
            $stmt = $db->prepare('DELETE FROM customer_loyalty_coupons WHERE id = ? AND company_id = ?');
            $stmt->execute([$id, $company['id']]);

            // Verificar se é requisição AJAX
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            // Ou se o método HTTP é DELETE
            $isDelete = $_SERVER['REQUEST_METHOD'] === 'DELETE';

            if ($isAjax || $isDelete) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Cupom excluído com sucesso']);
                exit;
            }

            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/loyalty-discount?section=cupons&success=deleted'));
            exit;

        } catch (Exception $e) {
            // Verificar se é requisição AJAX
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            $isDelete = $_SERVER['REQUEST_METHOD'] === 'DELETE';

            if ($isAjax || $isDelete) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }

            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/loyalty-discount?section=cupons&error=delete_failed'));
            exit;
        }
    }

    public function toggle($params)
    {
        header('Content-Type: application/json');
        
        $slug = $params['slug'] ?? '';
        $id = (int)($params['id'] ?? 0);
        [$user, $company] = $this->guard($slug);

        $db = db();

        try {
            // Toggle is_used (usado como ativo/inativo)
            $stmt = $db->prepare('UPDATE customer_loyalty_coupons SET is_used = NOT is_used WHERE id = ? AND company_id = ?');
            $stmt->execute([$id, $company['id']]);

            echo json_encode(['success' => true]);
            exit;

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function api($params)
    {
        header('Content-Type: application/json');
        
        $slug = $params['slug'] ?? '';
        [$user, $company] = $this->guard($slug);

        $db = db();

        try {
            // Buscar todos os cupons
            $stmt = $db->prepare('
                SELECT id, coupon_code, customer_phone, discount_percentage, 
                       usage_limit, times_used, is_used, used_at, created_at
                FROM customer_loyalty_coupons 
                WHERE company_id = ?
                ORDER BY created_at DESC
            ');
            $stmt->execute([$company['id']]);
            $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calcular estatísticas
            $stats = [
                'total' => count($coupons),
                'active' => 0,
                'used' => 0,
                'totalUsage' => 0
            ];

            foreach ($coupons as $coupon) {
                $used = (int)$coupon['is_used'] === 1;
                $timesUsed = (int)($coupon['times_used'] ?? 0);
                $usageLimit = (int)($coupon['usage_limit'] ?? 1);
                
                if ($used || ($usageLimit > 0 && $timesUsed >= $usageLimit)) {
                    $stats['used']++;
                } else {
                    $stats['active']++;
                }
                
                $stats['totalUsage'] += $timesUsed;
            }

            echo json_encode([
                'success' => true,
                'cupons' => $coupons,
                'stats' => $stats
            ]);
            exit;

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'cupons' => [],
                'stats' => [
                    'total' => 0,
                    'active' => 0,
                    'used' => 0,
                    'totalUsage' => 0
                ]
            ]);
            exit;
        }
    }

    /**
     * Retorna o histórico de uso de um cupom
     */
    public function history($params)
    {
        header('Content-Type: application/json');
        
        $slug = $params['slug'] ?? '';
        [$user, $company] = $this->guard($slug);

        try {
            $couponCode = $_GET['code'] ?? '';
            
            if (empty($couponCode)) {
                echo json_encode(['success' => false, 'message' => 'Código do cupom não fornecido', 'history' => []]);
                exit;
            }

            $db = db();
            
            // Buscar histórico de uso na tabela coupon_usage
            $stmt = $db->prepare('
                SELECT 
                    cu.customer_phone,
                    cu.order_id,
                    cu.used_at,
                    DATE_FORMAT(cu.used_at, "%d/%m/%Y às %H:%i") as formatted_date
                FROM coupon_usage cu
                WHERE cu.company_id = ? 
                AND cu.coupon_code = ?
                ORDER BY cu.used_at DESC
            ');
            $stmt->execute([$company['id'], $couponCode]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Formatar dados para exibição
            $formattedHistory = array_map(function($item) {
                $usedAt = new DateTime($item['used_at']);
                $now = new DateTime();
                $diff = $now->diff($usedAt);
                
                // Calcular "tempo atrás"
                if ($diff->days == 0) {
                    if ($diff->h == 0) {
                        $timeAgo = $diff->i <= 1 ? 'Agora mesmo' : $diff->i . ' minutos atrás';
                    } else {
                        $timeAgo = $diff->h == 1 ? '1 hora atrás' : $diff->h . ' horas atrás';
                    }
                } elseif ($diff->days == 1) {
                    $timeAgo = 'Ontem';
                } elseif ($diff->days < 7) {
                    $timeAgo = $diff->days . ' dias atrás';
                } else {
                    $timeAgo = $item['formatted_date'];
                }
                
                return [
                    'customer_phone' => $item['customer_phone'],
                    'order_id' => $item['order_id'],
                    'used_at' => $item['formatted_date'],
                    'time_ago' => $timeAgo
                ];
            }, $history);

            echo json_encode([
                'success' => true,
                'history' => $formattedHistory
            ]);
            exit;

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao buscar histórico',
                'history' => []
            ]);
            exit;
        }
    }
}
