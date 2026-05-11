<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

/**
 * Controller Mobile para Analytics
 */
class MobileAdminAnalyticsController extends Controller
{
    private $db;
    private $companyId;

    private function guard(): array
    {
        Auth::start();
        $user = Auth::user();
        $slug = $_SERVER['MOBILE_SLUG'] ?? 'wollburger';

        if (!$user) {
            header('Location: /login');
            exit;
        }

        $company = Company::findBySlug($slug);
        if (!$company) {
            http_response_code(404);
            echo 'Empresa não encontrada';
            exit;
        }

        if ($user['role'] !== 'root' && (int)$user['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            echo 'Acesso negado';
            exit;
        }

        $this->db = db();
        $this->companyId = (int)$company['id'];

        return [$user, $company, $slug];
    }

    /**
     * GET /analytics - Dashboard Analytics
     */
    public function index(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $period = $_GET['period'] ?? '30';
        $endDate = date('Y-m-d 23:59:59');

        if ($period === 'today') {
            $startDate = date('Y-m-d 00:00:00');
        } elseif ($period === 'month') {
            $startDate = date('Y-m-01 00:00:00');
            $endDate = date('Y-m-t 23:59:59');
        } elseif ($period === 'year') {
            $startDate = date('Y-01-01 00:00:00');
            $endDate = date('Y-12-31 23:59:59');
        } elseif (is_numeric($period)) {
            $startDate = date('Y-m-d 00:00:00', strtotime("-{$period} days"));
        } else {
            $period = '30';
            $startDate = date('Y-m-d 00:00:00', strtotime('-30 days'));
        }

        // Summary metrics
        $summary = $this->getSummaryMetrics($startDate, $endDate);
        $todaySales = $this->getTodaySales();
        $salesByDay = $this->getSalesByDay($startDate, $endDate);
        $salesByHour = $this->getSalesByHour($startDate, $endDate);
        $salesByWeekday = $this->getSalesByWeekday($startDate, $endDate);
        $paymentMethods = $this->getPaymentMethods($startDate, $endDate);
        $topProducts = $this->getTopProducts($startDate, $endDate);

        $pageTitle = 'Analytics';
        $activeNav = 'dashboard';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/analytics/index.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    private function getSummaryMetrics($startDate, $endDate): array
    {
        $periodDays = (strtotime($endDate) - strtotime($startDate)) / 86400;
        $prevStartDate = date('Y-m-d 00:00:00', (int)(strtotime($startDate) - ($periodDays * 86400)));
        $prevEndDate = date('Y-m-d 23:59:59', (int)(strtotime($endDate) - ($periodDays * 86400)));

        $stmt = $this->db->prepare('
            SELECT
                COUNT(CASE WHEN created_at BETWEEN ? AND ? AND status != "canceled" THEN 1 END) as total_orders,
                SUM(CASE WHEN created_at BETWEEN ? AND ? AND status != "canceled" THEN total ELSE 0 END) as total_revenue,
                AVG(CASE WHEN created_at BETWEEN ? AND ? AND status != "canceled" THEN total ELSE NULL END) as avg_ticket,
                COUNT(DISTINCT CASE WHEN created_at BETWEEN ? AND ? AND customer_phone IS NOT NULL AND customer_phone != "" THEN customer_phone END) as new_customers,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? AND status != "canceled" THEN 1 END) as prev_orders,
                SUM(CASE WHEN created_at BETWEEN ? AND ? AND status != "canceled" THEN total ELSE 0 END) as prev_revenue
            FROM orders WHERE company_id = ?
        ');
        $stmt->execute([
            $startDate, $endDate,
            $startDate, $endDate,
            $startDate, $endDate,
            $startDate, $endDate,
            $prevStartDate, $prevEndDate,
            $prevStartDate, $prevEndDate,
            $this->companyId
        ]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        $revenueChange = 0;
        if ((float)$data['prev_revenue'] > 0) {
            $revenueChange = round((((float)$data['total_revenue'] - (float)$data['prev_revenue']) / (float)$data['prev_revenue']) * 100, 1);
        }

        return [
            'total_orders' => (int)$data['total_orders'],
            'total_revenue' => (float)$data['total_revenue'],
            'avg_ticket' => (float)$data['avg_ticket'],
            'new_customers' => (int)$data['new_customers'],
            'revenue_change' => $revenueChange
        ];
    }

    private function getTodaySales(): array
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) as orders, COALESCE(SUM(total),0) as revenue, COALESCE(AVG(total),0) as avg_ticket
            FROM orders WHERE company_id = ? AND DATE(created_at) = CURDATE() AND status != "canceled"
        ');
        $stmt->execute([$this->companyId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return ['orders' => (int)$r['orders'], 'revenue' => (float)$r['revenue'], 'avg_ticket' => (float)$r['avg_ticket']];
    }

    private function getSalesByDay($startDate, $endDate): array
    {
        $stmt = $this->db->prepare('
            SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(total) as revenue
            FROM orders WHERE company_id = ? AND created_at BETWEEN ? AND ? AND status != "canceled"
            GROUP BY DATE(created_at) ORDER BY date ASC
        ');
        $stmt->execute([$this->companyId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getSalesByHour($startDate, $endDate): array
    {
        $stmt = $this->db->prepare('
            SELECT HOUR(created_at) as hour, COUNT(*) as orders, SUM(total) as revenue
            FROM orders WHERE company_id = ? AND created_at BETWEEN ? AND ? AND status != "canceled"
            GROUP BY HOUR(created_at) ORDER BY orders DESC
        ');
        $stmt->execute([$this->companyId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getSalesByWeekday($startDate, $endDate): array
    {
        $weekdays = ['', 'Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
        $stmt = $this->db->prepare('
            SELECT DAYOFWEEK(created_at) as weekday, COUNT(*) as orders, SUM(total) as revenue
            FROM orders WHERE company_id = ? AND created_at BETWEEN ? AND ? AND status != "canceled"
            GROUP BY DAYOFWEEK(created_at) ORDER BY weekday ASC
        ');
        $stmt->execute([$this->companyId, $startDate, $endDate]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as &$row) {
            $row['weekday_name'] = $weekdays[$row['weekday']] ?? '';
        }
        return $results;
    }

    private function getPaymentMethods($startDate, $endDate): array
    {
        $stmt = $this->db->prepare("
            SELECT
                CASE
                    WHEN pm.type IS NOT NULL THEN pm.type
                    WHEN o.notes LIKE '%Pix%' OR o.notes LIKE '%PIX%' THEN 'pix'
                    WHEN o.notes LIKE '%Dinheiro%' OR o.notes LIKE '%dinheiro%' THEN 'cash'
                    WHEN o.notes LIKE '%Crédito%' OR o.notes LIKE '%credito%' THEN 'credit'
                    WHEN o.notes LIKE '%Débito%' OR o.notes LIKE '%debito%' THEN 'debit'
                    ELSE 'outros'
                END as payment_type,
                COUNT(o.id) as orders,
                COALESCE(SUM(o.total), 0) as revenue
            FROM orders o
            LEFT JOIN payment_methods pm ON pm.id = o.payment_method_id
            WHERE o.company_id = ? AND o.created_at BETWEEN ? AND ? AND o.status != 'canceled'
            GROUP BY payment_type
            ORDER BY orders DESC
        ");
        $stmt->execute([$this->companyId, $startDate, $endDate]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $labels = ['pix' => 'PIX', 'credit' => 'Crédito', 'debit' => 'Débito', 'cash' => 'Dinheiro', 'outros' => 'Outros'];
        $totalOrders = array_sum(array_column($results, 'orders'));

        foreach ($results as &$row) {
            $row['payment_method'] = $labels[$row['payment_type']] ?? $row['payment_type'];
            $row['percentage'] = $totalOrders > 0 ? round(($row['orders'] / $totalOrders) * 100, 1) : 0;
        }

        return $results;
    }

    private function getTopProducts($startDate, $endDate): array
    {
        $stmt = $this->db->prepare('
            SELECT p.name as product_name, SUM(oi.quantity) as total_quantity,
                   SUM(oi.line_total) as total_revenue, COUNT(DISTINCT oi.order_id) as order_count
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.id
            INNER JOIN products p ON oi.product_id = p.id
            WHERE o.company_id = ? AND o.created_at BETWEEN ? AND ? AND o.status != "canceled"
            GROUP BY oi.product_id, p.name ORDER BY total_quantity DESC LIMIT 10
        ');
        $stmt->execute([$this->companyId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
