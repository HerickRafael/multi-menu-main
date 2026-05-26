<?php
declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

class AdminAnalyticsController
{
    private $db;
    private $companyId;

    public function index($params)
    {
        $slug = $params['slug'] ?? '';
        
        Auth::start();
        $user = Auth::user();
        
        if (!$user) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
            exit;
        }
        
        $this->db = db();

        // Buscar company
        $company = Company::findBySlug($slug);

        if (!$company) {
            http_response_code(404);
            echo 'Empresa não encontrada';
            return;
        }

        if ($user['role'] !== 'root' && (int)$user['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            echo 'Acesso negado';
            return;
        }

        $this->companyId = (int)$company['id'];

        // Obter período do filtro (padrão: últimos 30 dias)
        $period = $_GET['period'] ?? '30';
        $endDate = date('Y-m-d 23:59:59');

        // Determinar data de início baseada no período
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
            // Valor inválido, usar 30 dias como padrão
            $period = '30';
            $startDate = date('Y-m-d 00:00:00', strtotime('-30 days'));
        }

        // Carregar todas as métricas COM CACHE para melhorar performance
        $cacheKey = "analytics:{$this->companyId}:{$period}:" . date('Y-m-d-H');
        
        $metrics = SmartCache::remember($cacheKey, function() use ($startDate, $endDate) {
            return [
                'summary' => $this->getSummaryMetrics($startDate, $endDate),
                'todaySales' => $this->getTodaySales(),
                'salesByDay' => $this->getSalesByDay($startDate, $endDate),
                'salesByHour' => $this->getSalesByHour($startDate, $endDate),
                'salesByWeekday' => $this->getSalesByWeekday($startDate, $endDate),
                'paymentMethods' => $this->getPaymentMethods($startDate, $endDate),
                'topProducts' => $this->getTopProducts($startDate, $endDate),
                'recentOrders' => $this->getRecentOrders(),
                'pageViews' => $this->getPageViews($startDate, $endDate),
                'conversionFunnel' => $this->getConversionFunnel($startDate, $endDate)
            ];
        }, 300); // Cache por 5 minutos
        
        $payload = [
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'summary' => $metrics['summary'] ?? [],
            'today_sales' => $metrics['todaySales'] ?? [],
            'sales_by_day' => $metrics['salesByDay'] ?? [],
            'sales_by_hour' => $metrics['salesByHour'] ?? [],
            'sales_by_weekday' => $metrics['salesByWeekday'] ?? [],
            'payment_methods' => $metrics['paymentMethods'] ?? [],
            'top_products' => $metrics['topProducts'] ?? [],
            'recent_orders' => $metrics['recentOrders'] ?? [],
            'page_views' => $metrics['pageViews'] ?? [],
            'conversion_funnel' => $metrics['conversionFunnel'] ?? [],
            'urls' => [
                'list' => '/admin/' . rawurlencode($slug) . '/analytics',
                'data' => '/admin/' . rawurlencode($slug) . '/analytics/data',
                'orders_base' => '/admin/' . rawurlencode($slug) . '/orders/',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_ANALYTICS__', $payload);
    }

    /**
     * Métricas resumidas principais - OTIMIZADO com query única
     */
    private function getSummaryMetrics($startDate, $endDate): array
    {
        // Calcular período anterior
        $periodDays = (strtotime($endDate) - strtotime($startDate)) / 86400;
        $prevStartDate = date('Y-m-d 00:00:00', (int)(strtotime($startDate) - ($periodDays * 86400)));
        $prevEndDate = date('Y-m-d 23:59:59', (int)(strtotime($endDate) - ($periodDays * 86400)));
        $todayDate = date('Y-m-d');

        // QUERY ÚNICA consolidada - substitui 6+ queries separadas
        $stmt = $this->db->prepare('
            SELECT 
                -- Período atual
                COUNT(CASE WHEN created_at BETWEEN :start1 AND :end1 THEN 1 END) as total_orders,
                SUM(CASE WHEN created_at BETWEEN :start2 AND :end2 AND status = "canceled" THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(CASE WHEN created_at BETWEEN :start3 AND :end3 AND status != "canceled" THEN total ELSE 0 END) as total_revenue,
                AVG(CASE WHEN created_at BETWEEN :start4 AND :end4 AND status != "canceled" THEN total ELSE NULL END) as avg_ticket,
                COUNT(DISTINCT CASE WHEN created_at BETWEEN :start5 AND :end5 AND customer_phone IS NOT NULL AND customer_phone != "" THEN customer_phone END) as new_customers,
                
                -- Hoje
                COUNT(CASE WHEN DATE(created_at) = :today1 AND status != "canceled" THEN 1 END) as today_orders,
                SUM(CASE WHEN DATE(created_at) = :today2 AND status != "canceled" THEN total ELSE 0 END) as today_revenue,
                
                -- Período anterior (para comparação)
                COUNT(CASE WHEN created_at BETWEEN :prev_start1 AND :prev_end1 AND status != "canceled" THEN 1 END) as prev_orders,
                SUM(CASE WHEN created_at BETWEEN :prev_start2 AND :prev_end2 AND status != "canceled" THEN total ELSE 0 END) as prev_revenue,
                AVG(CASE WHEN created_at BETWEEN :prev_start3 AND :prev_end3 AND status != "canceled" THEN total ELSE NULL END) as prev_avg_ticket,
                COUNT(DISTINCT CASE WHEN created_at BETWEEN :prev_start4 AND :prev_end4 AND customer_phone IS NOT NULL AND customer_phone != "" THEN customer_phone END) as prev_customers
                
            FROM orders 
            WHERE company_id = :company_id
            AND (
                created_at BETWEEN :range_start AND :range_end
                OR DATE(created_at) = :today3
            )
        ');

        $stmt->execute([
            ':company_id' => $this->companyId,
            ':start1' => $startDate, ':end1' => $endDate,
            ':start2' => $startDate, ':end2' => $endDate,
            ':start3' => $startDate, ':end3' => $endDate,
            ':start4' => $startDate, ':end4' => $endDate,
            ':start5' => $startDate, ':end5' => $endDate,
            ':today1' => $todayDate,
            ':today2' => $todayDate,
            ':today3' => $todayDate,
            ':prev_start1' => $prevStartDate, ':prev_end1' => $prevEndDate,
            ':prev_start2' => $prevStartDate, ':prev_end2' => $prevEndDate,
            ':prev_start3' => $prevStartDate, ':prev_end3' => $prevEndDate,
            ':prev_start4' => $prevStartDate, ':prev_end4' => $prevEndDate,
            ':range_start' => min($prevStartDate, $startDate),
            ':range_end' => max($prevEndDate, $endDate),
        ]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        // Calcular variações percentuais
        $revenueChange = 0;
        $ordersChange = 0;
        $ticketChange = 0;
        $customersChange = 0;

        if ($data['prev_revenue'] > 0) {
            $revenueChange = (($data['total_revenue'] - $data['prev_revenue']) / $data['prev_revenue']) * 100;
        }
        
        $validOrders = (int)$data['total_orders'] - (int)$data['cancelled_orders'];
        if ($data['prev_orders'] > 0) {
            $ordersChange = (($validOrders - $data['prev_orders']) / $data['prev_orders']) * 100;
        }

        if ($data['prev_avg_ticket'] > 0) {
            $ticketChange = (($data['avg_ticket'] - $data['prev_avg_ticket']) / $data['prev_avg_ticket']) * 100;
        }

        if ($data['prev_customers'] > 0) {
            $customersChange = (($data['new_customers'] - $data['prev_customers']) / $data['prev_customers']) * 100;
        }

        return [
            'total_orders' => $validOrders,
            'total_revenue' => (float)$data['total_revenue'],
            'avg_ticket' => (float)$data['avg_ticket'],
            'cancelled_orders' => (int)$data['cancelled_orders'],
            'today_orders' => (int)$data['today_orders'],
            'today_revenue' => (float)$data['today_revenue'],
            'revenue_change' => round($revenueChange, 1),
            'orders_change' => round($ordersChange, 1),
            'ticket_change' => round($ticketChange, 1),
            'new_customers' => (int)$data['new_customers'],
            'customers_change' => round($customersChange, 1)
        ];
    }

    /**
     * Vendas por dia
     */
    private function getSalesByDay($startDate, $endDate): array
    {
        $stmt = $this->db->prepare('
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as orders,
                SUM(total) as revenue
            FROM orders 
            WHERE company_id = ? 
            AND created_at BETWEEN ? AND ?
            AND status != "canceled"
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ');
        $stmt->execute([$this->companyId, $startDate, $endDate]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vendas do dia atual
     */
    private function getTodaySales(): array
    {
        $today = date('Y-m-d');
        
        $stmt = $this->db->prepare('
            SELECT 
                COUNT(*) as orders,
                COALESCE(SUM(total), 0) as revenue,
                COALESCE(AVG(total), 0) as avg_ticket
            FROM orders 
            WHERE company_id = ? 
            AND DATE(created_at) = ?
            AND status != "canceled"
        ');
        $stmt->execute([$this->companyId, $today]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'orders' => (int)$result['orders'],
            'revenue' => (float)$result['revenue'],
            'avg_ticket' => (float)$result['avg_ticket']
        ];
    }

    /**
     * Vendas por hora do dia
     */
    private function getSalesByHour($startDate, $endDate): array
    {
        $stmt = $this->db->prepare('
            SELECT 
                HOUR(created_at) as hour,
                COUNT(*) as orders,
                SUM(total) as revenue
            FROM orders 
            WHERE company_id = ? 
            AND created_at BETWEEN ? AND ?
            AND status != "canceled"
            GROUP BY HOUR(created_at)
            ORDER BY orders DESC
        ');
        $stmt->execute([$this->companyId, $startDate, $endDate]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vendas por dia da semana
     */
    private function getSalesByWeekday($startDate, $endDate): array
    {
        $stmt = $this->db->prepare('
            SELECT 
                DAYOFWEEK(created_at) as weekday,
                COUNT(*) as orders,
                SUM(total) as revenue
            FROM orders 
            WHERE company_id = ? 
            AND created_at BETWEEN ? AND ?
            AND status != "canceled"
            GROUP BY DAYOFWEEK(created_at)
            ORDER BY weekday ASC
        ');
        $stmt->execute([$this->companyId, $startDate, $endDate]);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mapear para nomes dos dias
        $weekdays = ['', 'Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
        foreach ($results as &$row) {
            $row['weekday_name'] = $weekdays[$row['weekday']] ?? '';
        }
        
        return $results;
    }

    /**
     * Formas de pagamento mais usadas (agrupadas por tipo: crédito, débito, pix, dinheiro)
     * Extrai do campo notes quando payment_method_id está vazio
     */
    private function getPaymentMethods($startDate, $endDate): array
    {
        // Query que extrai o tipo de pagamento do campo notes quando payment_method_id é NULL
        $stmt = $this->db->prepare("
            SELECT 
                CASE 
                    WHEN pm.type IS NOT NULL THEN pm.type
                    WHEN o.notes LIKE '%Pagamento: Pix%' OR o.notes LIKE '%PIX%' THEN 'pix'
                    WHEN o.notes LIKE '%Pagamento: Dinheiro%' OR o.notes LIKE '%dinheiro%' THEN 'cash'
                    WHEN o.notes LIKE '%Crédito%' OR o.notes LIKE '%credito%' THEN 'credit'
                    WHEN o.notes LIKE '%Débito%' OR o.notes LIKE '%debito%' THEN 'debit'
                    ELSE 'outros'
                END as payment_type,
                COUNT(o.id) as orders,
                COALESCE(SUM(o.total), 0) as revenue
            FROM orders o
            LEFT JOIN payment_methods pm ON pm.id = o.payment_method_id
            WHERE o.company_id = ?
            AND o.created_at BETWEEN ? AND ?
            AND o.status != 'canceled'
            GROUP BY payment_type
            HAVING payment_type IN ('credit', 'debit', 'pix', 'cash')
            ORDER BY orders DESC
        ");
        $stmt->execute([$this->companyId, $startDate, $endDate]);
        
        $dbResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Criar array indexado por tipo
        $resultsByType = [];
        foreach ($dbResults as $row) {
            $resultsByType[$row['payment_type']] = $row;
        }
        
        // Garantir que todos os 4 tipos apareçam
        $allTypes = [
            'pix' => 'PIX',
            'credit' => 'Crédito',
            'debit' => 'Débito',
            'cash' => 'Dinheiro'
        ];
        
        $results = [];
        foreach ($allTypes as $type => $label) {
            if (isset($resultsByType[$type])) {
                $results[] = [
                    'payment_type' => $type,
                    'payment_method' => $label,
                    'orders' => (int)$resultsByType[$type]['orders'],
                    'revenue' => (float)$resultsByType[$type]['revenue']
                ];
            } else {
                $results[] = [
                    'payment_type' => $type,
                    'payment_method' => $label,
                    'orders' => 0,
                    'revenue' => 0
                ];
            }
        }
        
        // Ordenar por quantidade de pedidos (decrescente)
        usort($results, function($a, $b) {
            return $b['orders'] - $a['orders'];
        });
        
        // Calcular total de pedidos para percentual
        $totalOrders = array_sum(array_column($results, 'orders'));
        
        // Calcular percentual
        foreach ($results as &$row) {
            $row['percentage'] = $totalOrders > 0 ? round(($row['orders'] / $totalOrders) * 100, 1) : 0;
        }
        
        return $results;
    }

    /**
     * Produtos mais vendidos
     */
    private function getTopProducts($startDate, $endDate): array
    {
        $stmt = $this->db->prepare('
            SELECT 
                p.name as product_name,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.line_total) as total_revenue,
                COUNT(DISTINCT oi.order_id) as order_count
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.id
            INNER JOIN products p ON oi.product_id = p.id
            WHERE o.company_id = ? 
            AND o.created_at BETWEEN ? AND ?
            AND o.status != "canceled"
            GROUP BY oi.product_id, p.name
            ORDER BY total_quantity DESC
            LIMIT 10
        ');
        $stmt->execute([$this->companyId, $startDate, $endDate]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Pedidos recentes
     */
    private function getRecentOrders(): array
    {
        $stmt = $this->db->prepare('
            SELECT 
                id,
                customer_name,
                total,
                status,
                created_at
            FROM orders 
            WHERE company_id = ?
            ORDER BY created_at DESC
            LIMIT 10
        ');
        $stmt->execute([$this->companyId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Funil de conversão baseado em dados reais
     */
    private function getConversionFunnel($startDate, $endDate): array
    {
        // Total de clientes cadastrados no período
        $stmt = $this->db->prepare('
            SELECT COUNT(*) as total
            FROM customers
            WHERE company_id = ? AND created_at BETWEEN ? AND ?
        ');
        $stmt->execute([$this->companyId, $startDate, $endDate]);
        $customersRegistered = (int)$stmt->fetchColumn();

        // Total de clientes cadastrados (all time) como base
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM customers WHERE company_id = ?');
        $stmt->execute([$this->companyId]);
        $totalCustomers = (int)$stmt->fetchColumn();

        // Pedidos no período (todas as métricas de uma vez)
        $stmt = $this->db->prepare('
            SELECT
                COUNT(*) as total_orders,
                SUM(CASE WHEN status != "canceled" THEN 1 ELSE 0 END) as valid_orders,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status = "canceled" THEN 1 ELSE 0 END) as canceled_orders,
                COUNT(DISTINCT customer_phone) as unique_customers_ordered,
                SUM(CASE WHEN status != "canceled" THEN total ELSE 0 END) as revenue,
                AVG(CASE WHEN status != "canceled" THEN total ELSE NULL END) as avg_ticket
            FROM orders
            WHERE company_id = ? AND created_at BETWEEN ? AND ?
        ');
        $stmt->execute([$this->companyId, $startDate, $endDate]);
        $orderData = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalOrders = (int)$orderData['total_orders'];
        $validOrders = (int)$orderData['valid_orders'];
        $completedOrders = (int)$orderData['completed_orders'];
        $canceledOrders = (int)$orderData['canceled_orders'];
        $uniqueCustomersOrdered = (int)$orderData['unique_customers_ordered'];
        $revenue = (float)$orderData['revenue'];
        $avgTicket = (float)$orderData['avg_ticket'];

        // Clientes recorrentes (>1 pedido) no período
        $stmt = $this->db->prepare('
            SELECT
                COUNT(*) as total_buyers,
                SUM(CASE WHEN cnt > 1 THEN 1 ELSE 0 END) as returning,
                SUM(CASE WHEN cnt = 1 THEN 1 ELSE 0 END) as first_timers
            FROM (
                SELECT customer_phone, COUNT(*) as cnt
                FROM orders
                WHERE company_id = ? AND created_at BETWEEN ? AND ? AND status != "canceled"
                GROUP BY customer_phone
            ) sub
        ');
        $stmt->execute([$this->companyId, $startDate, $endDate]);
        $recurrence = $stmt->fetch(PDO::FETCH_ASSOC);

        $returningCustomers = (int)($recurrence['returning'] ?? 0);
        $firstTimers = (int)($recurrence['first_timers'] ?? 0);
        $totalBuyers = (int)($recurrence['total_buyers'] ?? 0);

        // Itens médios por pedido
        $stmt = $this->db->prepare('
            SELECT AVG(item_count) as avg_items
            FROM (
                SELECT oi.order_id, SUM(oi.quantity) as item_count
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE o.company_id = ? AND o.created_at BETWEEN ? AND ? AND o.status != "canceled"
                GROUP BY oi.order_id
            ) sub
        ');
        $stmt->execute([$this->companyId, $startDate, $endDate]);
        $avgItems = round((float)$stmt->fetchColumn(), 1);

        // Calcular taxas
        $completionRate = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0;
        $cancelRate = $totalOrders > 0 ? round(($canceledOrders / $totalOrders) * 100, 1) : 0;
        $recurrenceRate = $totalBuyers > 0 ? round(($returningCustomers / $totalBuyers) * 100, 1) : 0;
        $conversionRate = $totalCustomers > 0 ? round(($uniqueCustomersOrdered / $totalCustomers) * 100, 1) : 0;

        return [
            'total_customers' => $totalCustomers,
            'customers_registered_period' => $customersRegistered,
            'unique_buyers' => $uniqueCustomersOrdered,
            'total_orders' => $totalOrders,
            'valid_orders' => $validOrders,
            'completed_orders' => $completedOrders,
            'canceled_orders' => $canceledOrders,
            'revenue' => $revenue,
            'avg_ticket' => $avgTicket,
            'avg_items' => $avgItems,
            'returning_customers' => $returningCustomers,
            'first_timers' => $firstTimers,
            'completion_rate' => $completionRate,
            'cancel_rate' => $cancelRate,
            'recurrence_rate' => $recurrenceRate,
            'conversion_rate' => $conversionRate,
        ];
    }

    /**
     * Flag para evitar CREATE TABLE em cada requisição
     */
    private static $pageViewsTableChecked = false;

    /**
     * Visualizações de página
     */
    private function getPageViews($startDate, $endDate): array
    {
        // Criar tabela se não existir - apenas uma vez por processo
        if (!self::$pageViewsTableChecked) {
            try {
                $this->db->exec('
                    CREATE TABLE IF NOT EXISTS page_views (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        company_id INT NOT NULL,
                        page VARCHAR(100) NOT NULL,
                        ip_address VARCHAR(45),
                        user_agent TEXT,
                        viewed_at DATETIME NOT NULL,
                        INDEX idx_company_date (company_id, viewed_at),
                        INDEX idx_page (page)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ');
            } catch (PDOException $e) {
                // Tabela já existe ou erro - silenciar
            }
            self::$pageViewsTableChecked = true;
        }

        // Total de visualizações
        $stmt = $this->db->prepare('
            SELECT 
                COUNT(*) as total_views,
                COUNT(DISTINCT ip_address) as unique_visitors
            FROM page_views
            WHERE company_id = ?
            AND viewed_at BETWEEN ? AND ?
        ');
        $stmt->execute([$this->companyId, $startDate, $endDate]);
        $views = $stmt->fetch(PDO::FETCH_ASSOC);

        // Visualizações de hoje
        $stmt = $this->db->prepare('
            SELECT COUNT(*) as today_views
            FROM page_views
            WHERE company_id = ?
            AND DATE(viewed_at) = CURDATE()
        ');
        $stmt->execute([$this->companyId]);
        $today = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_views' => (int)($views['total_views'] ?? 0),
            'unique_visitors' => (int)($views['unique_visitors'] ?? 0),
            'today_views' => (int)($today['today_views'] ?? 0)
        ];
    }

    /**
     * API endpoint para dados em JSON (para gráficos dinâmicos)
     */
    public function apiData($params)
    {
        $slug = $params['slug'] ?? '';
        
        Auth::start();
        $user = Auth::user();
        
        if (!$user) {
            http_response_code(403);
            echo json_encode(['error' => 'Não autenticado']);
            return;
        }
        
        $this->db = db();

        // Buscar company
        $company = Company::findBySlug($slug);

        if (!$company) {
            http_response_code(404);
            echo json_encode(['error' => 'Empresa não encontrada']);
            return;
        }

        if ($user['role'] !== 'root' && (int)$user['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado']);
            return;
        }

        $this->companyId = (int)$company['id'];

        $period = $_GET['period'] ?? '30';
        $startDate = date('Y-m-d 00:00:00', strtotime("-{$period} days"));
        $endDate = date('Y-m-d 23:59:59');

        if ($period === 'today') {
            $startDate = date('Y-m-d 00:00:00');
            $endDate = date('Y-m-d 23:59:59');
        }

        $data = [
            'summary' => $this->getSummaryMetrics($startDate, $endDate),
            'salesByDay' => $this->getSalesByDay($startDate, $endDate),
            'salesByHour' => $this->getSalesByHour($startDate, $endDate),
            'salesByWeekday' => $this->getSalesByWeekday($startDate, $endDate),
            'paymentMethods' => $this->getPaymentMethods($startDate, $endDate),
            'topProducts' => $this->getTopProducts($startDate, $endDate)
        ];

        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
