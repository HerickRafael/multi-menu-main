<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/MobileAdminGuard.php';

/**
 * Controller Mobile para Gestão de Cupons
 */
class MobileAdminCouponsController extends Controller
{
    private function guard(): array
    {
        $slug = $_SERVER['MOBILE_SLUG'] ?? 'wollburger';
        [$user, $company] = MobileAdminGuard::requireCompanyAccess('coupons.manage');

        return [$user, $company, $slug];
    }

    /**
     * GET /coupons - Lista de cupons
     */
    public function index(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $db = db();
        $companyId = (int)$company['id'];

        // Buscar cupons
        $stmt = $db->prepare('
            SELECT id, coupon_code, customer_phone, discount_percentage,
                   usage_limit, times_used, is_used, used_at, created_at,
                   allow_multiple_uses_per_customer
            FROM customer_loyalty_coupons
            WHERE company_id = ?
            ORDER BY created_at DESC
        ');
        $stmt->execute([$companyId]);
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Estatísticas
        $stats = ['total' => count($coupons), 'active' => 0, 'used' => 0, 'total_usage' => 0];
        foreach ($coupons as $c) {
            $isUsed = (int)$c['is_used'] === 1;
            $timesUsed = (int)($c['times_used'] ?? 0);
            $usageLimit = (int)($c['usage_limit'] ?? 1);
            if ($isUsed || ($usageLimit > 0 && $timesUsed >= $usageLimit)) {
                $stats['used']++;
            } else {
                $stats['active']++;
            }
            $stats['total_usage'] += $timesUsed;
        }

        $success = $_SESSION['flash_success'] ?? null;
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $pageTitle = 'Cupons';
        $activeNav = 'settings';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/coupons/index.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    /**
     * GET /coupons/create - Formulário de criação
     */
    public function create(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $coupon = null;
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        $pageTitle = 'Novo Cupom';
        $activeNav = 'settings';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/coupons/form.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    /**
     * POST /coupons - Salvar novo cupom
     */
    public function store(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $db = db();
        $companyId = (int)$company['id'];

        try {
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $phone = trim($_POST['customer_phone'] ?? '');
            $discountPercentage = (float)($_POST['discount_percentage'] ?? 0);
            $usageLimit = (int)($_POST['usage_limit'] ?? 1);
            $allowMultiple = isset($_POST['allow_multiple_uses_per_customer']) ? 1 : 0;

            if (empty($code)) {
                $code = 'CUPOM' . strtoupper(substr(md5(uniqid() . time()), 0, 6));
            }

            if ($discountPercentage <= 0 || $discountPercentage > 100) {
                throw new \Exception('Desconto deve estar entre 1% e 100%');
            }

            if (!empty($phone)) {
                $stmt = $db->prepare('SELECT id FROM customer_loyalty_coupons WHERE company_id = ? AND customer_phone = ? AND is_used = 0');
                $stmt->execute([$companyId, $phone]);
                if ($stmt->fetch()) {
                    throw new \Exception('Cliente já possui um cupom ativo');
                }
            }

            $stmt = $db->prepare('SELECT id FROM customer_loyalty_coupons WHERE company_id = ? AND coupon_code = ?');
            $stmt->execute([$companyId, $code]);
            if ($stmt->fetch()) {
                throw new \Exception('Código de cupom já existe');
            }

            $stmt = $db->prepare('
                INSERT INTO customer_loyalty_coupons
                (company_id, customer_phone, coupon_code, discount_percentage,
                 usage_limit, allow_multiple_uses_per_customer, times_used, is_used, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 0, 0, NOW())
            ');
            $stmt->execute([$companyId, !empty($phone) ? $phone : null, $code, $discountPercentage, $usageLimit, $allowMultiple]);

            $_SESSION['flash_success'] = 'Cupom criado com sucesso!';
            header('Location: /coupons');
            exit;

        } catch (\Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: /coupons/create');
            exit;
        }
    }

    /**
     * GET /coupons/{id}/edit - Formulário de edição
     */
    public function edit(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $db = db();
        $id = (int)($params['id'] ?? 0);

        $stmt = $db->prepare('SELECT * FROM customer_loyalty_coupons WHERE id = ? AND company_id = ?');
        $stmt->execute([$id, (int)$company['id']]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$coupon) {
            header('Location: /coupons');
            exit;
        }

        // Buscar estatísticas de uso
        $usage_stats = null;
        try {
            $stmt = $db->prepare('SELECT COUNT(DISTINCT customer_phone) as unique_customers, COUNT(*) as total_uses FROM coupon_usage WHERE company_id = ? AND coupon_code = ?');
            $stmt->execute([(int)$company['id'], $coupon['coupon_code']]);
            $usage_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // tabela pode não existir
        }

        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        $pageTitle = 'Editar Cupom';
        $activeNav = 'settings';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/coupons/form.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    /**
     * POST /coupons/{id} - Atualizar cupom
     */
    public function update(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $db = db();
        $id = (int)($params['id'] ?? 0);
        $companyId = (int)$company['id'];

        try {
            $stmt = $db->prepare('SELECT coupon_code FROM customer_loyalty_coupons WHERE id = ? AND company_id = ?');
            $stmt->execute([$id, $companyId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                throw new \Exception('Cupom não encontrado');
            }

            $code = strtoupper(trim($_POST['code'] ?? ''));
            $phone = trim($_POST['customer_phone'] ?? '');
            $discountPercentage = (float)($_POST['discount_percentage'] ?? 0);
            $usageLimit = (int)($_POST['usage_limit'] ?? 1);
            $allowMultiple = isset($_POST['allow_multiple_uses_per_customer']) ? 1 : 0;

            if (empty($code)) throw new \Exception('Código do cupom é obrigatório');
            if ($discountPercentage <= 0 || $discountPercentage > 100) throw new \Exception('Desconto deve estar entre 1% e 100%');

            if ($code !== $existing['coupon_code']) {
                $stmt = $db->prepare('SELECT id FROM customer_loyalty_coupons WHERE company_id = ? AND coupon_code = ? AND id != ?');
                $stmt->execute([$companyId, $code, $id]);
                if ($stmt->fetch()) throw new \Exception('Código de cupom já existe');
            }

            $stmt = $db->prepare('
                UPDATE customer_loyalty_coupons SET
                    coupon_code = ?, customer_phone = ?, discount_percentage = ?,
                    usage_limit = ?, allow_multiple_uses_per_customer = ?
                WHERE id = ? AND company_id = ?
            ');
            $stmt->execute([$code, !empty($phone) ? $phone : null, $discountPercentage, $usageLimit, $allowMultiple, $id, $companyId]);

            $_SESSION['flash_success'] = 'Cupom atualizado com sucesso!';
            header('Location: /coupons');
            exit;

        } catch (\Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: /coupons/' . $id . '/edit');
            exit;
        }
    }

    /**
     * POST /coupons/{id}/delete - Excluir cupom
     */
    public function delete(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $db = db();
        $id = (int)($params['id'] ?? 0);

        try {
            $stmt = $db->prepare('DELETE FROM customer_loyalty_coupons WHERE id = ? AND company_id = ?');
            $stmt->execute([$id, (int)$company['id']]);
            $_SESSION['flash_success'] = 'Cupom excluído com sucesso!';
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Erro ao excluir cupom';
        }

        header('Location: /coupons');
        exit;
    }

    /**
     * POST /coupons/{id}/toggle - Ativar/desativar cupom
     */
    public function toggle(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $db = db();
        $id = (int)($params['id'] ?? 0);

        try {
            $stmt = $db->prepare('UPDATE customer_loyalty_coupons SET is_used = NOT is_used WHERE id = ? AND company_id = ?');
            $stmt->execute([$id, (int)$company['id']]);
            $_SESSION['flash_success'] = 'Status do cupom alterado!';
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Erro ao alterar status';
        }

        header('Location: /coupons');
        exit;
    }

    /**
     * GET /coupons/history — Página de histórico de uso
     * GET /coupons/history?code=XXX — Histórico de uso (API JSON)
     */
    public function history(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];

        // Se ?code= for passado, retorna JSON (API)
        if (!empty($_GET['code'])) {
            header('Content-Type: application/json');
            try {
                $couponCode = $_GET['code'];
                $db = db();
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
                $stmt->execute([$companyId, $couponCode]);
                $history = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                $formattedHistory = [];
                $now = new \DateTime();
                foreach ($history as $item) {
                    $usedAt = new \DateTime($item['used_at']);
                    $diff = $now->diff($usedAt);

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

                    $formattedHistory[] = [
                        'customer_phone' => $item['customer_phone'],
                        'order_id' => $item['order_id'],
                        'used_at' => $item['formatted_date'],
                        'time_ago' => $timeAgo,
                    ];
                }

                echo json_encode(['success' => true, 'history' => $formattedHistory]);
            } catch (\Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Erro ao buscar histórico', 'history' => []]);
            }
            exit;
        }

        // Página HTML de histórico
        $db = db();

        // Stats
        $stmt = $db->prepare('
            SELECT COUNT(*) as total_uses, COUNT(DISTINCT customer_phone) as unique_customers
            FROM coupon_usage WHERE company_id = ?
        ');
        $stmt->execute([$companyId]);
        $historyStats = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Últimos 50 usos
        $stmt = $db->prepare('
            SELECT 
                cu.coupon_code,
                cu.customer_phone,
                cu.order_id,
                cu.used_at,
                DATE_FORMAT(cu.used_at, "%d/%m/%Y %H:%i") as formatted_date
            FROM coupon_usage cu
            WHERE cu.company_id = ?
            ORDER BY cu.used_at DESC
            LIMIT 50
        ');
        $stmt->execute([$companyId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $now = new \DateTime();
        $history = [];
        foreach ($rows as $item) {
            $usedAt = new \DateTime($item['used_at']);
            $diff = $now->diff($usedAt);

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

            $history[] = [
                'coupon_code' => $item['coupon_code'],
                'customer_phone' => $item['customer_phone'],
                'order_id' => $item['order_id'],
                'used_at' => $item['formatted_date'],
                'time_ago' => $timeAgo,
            ];
        }

        $pageTitle = 'Histórico de Cupons';
        $activeNav = 'settings';

        ob_start();
        require __DIR__ . '/../views/admin/mobile/coupons/history.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }
}
