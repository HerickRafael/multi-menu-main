<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

/**
 * MobileAdminDashboardController
 * 
 * Dashboard mobile otimizado para gestão rápida.
 * Reutiliza models e services do desktop, apenas views são diferentes.
 */
class MobileAdminDashboardController extends Controller
{
    /**
     * Garante autenticação e contexto de empresa para mobile.
     * Usa slug do $_SERVER['MOBILE_SLUG'] definido pelo SubdomainDetector.
     */
    private function guard(): array
    {
        Auth::start();

        $slug = $_SERVER['MOBILE_SLUG'] ?? 'wollburger';

        // Precisa estar logado (admin)
        if (!Auth::checkAdmin()) {
            header('Location: /login');
            exit;
        }

        // Empresa pelo slug
        $company = Company::findBySlug($slug);

        if (!$company || empty($company['id'])) {
            http_response_code(404);
            echo 'Empresa inválida';
            exit;
        }

        // Autorização: root pode tudo; demais só a própria empresa
        $u = Auth::user();
        $isRoot = ($u['role'] === 'root');

        if (!$isRoot && (int)$u['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            echo 'Acesso negado';
            exit;
        }

        // Garante contexto ativo
        $this->ensureCompanyContext((int)$company['id'], $slug);

        return [$u, $company];
    }

    /**
     * GET /dashboard
     * Dashboard mobile com stats e pedidos recentes
     */
    public function index(array $params = [])
    {
        [$u, $company] = $this->guard();

        $companyId = (int)$company['id'];
        $db = db();

        // Stats principais
        $todayOrders = Order::countTodayByCompany($db, $companyId);
        $pendingOrders = Order::countPendingByCompany($db, $companyId);
        $todayRevenue = Order::sumTodayByCompany($db, $companyId);
        $activeProducts = Product::countActiveByCompany($companyId);

        // Pedidos recentes (últimos 10)
        $recentOrders = Order::listRecentByCompany($companyId, 10);

        // Categorias e produtos para contagem real
        $categories = Category::listByCompany($companyId);
        $products = Product::listByCompany($companyId);

        // Formata valores para exibição
        $stats = [
            'today_orders' => $todayOrders,
            'pending_orders' => $pendingOrders,
            'today_revenue' => $todayRevenue,
            'active_products' => $activeProducts,
        ];

        $pageTitle = 'Dashboard';
        $activeNav = 'dashboard';

        return $this->viewMobile('dashboard/index', compact(
            'company',
            'u',
            'stats',
            'recentOrders',
            'categories',
            'products',
            'pageTitle',
            'activeNav'
        ));
    }

    /**
     * GET /manifest.webmanifest
     * Manifest dinâmico com cores da empresa
     */
    public function manifest(array $params = [])
    {
        $slug = $_SERVER['MOBILE_SLUG'] ?? 'wollburger';
        $company = Company::findBySlug($slug);

        $themeColor = $company['menu_header_bg_color'] ?? $company['theme_color'] ?? '#4361ee';
        $companyName = $company['name'] ?? 'Multi Menu';

        $manifest = [
            'name' => $companyName . ' - Admin',
            'short_name' => 'Admin',
            'description' => 'Painel administrativo mobile',
            'start_url' => '/dashboard',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'theme_color' => $themeColor,
            'background_color' => '#ffffff',
            'icons' => [
                [
                    'src' => '/assets/icons/mobile/icon-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => '/assets/icons/mobile/icon-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ]
            ],
            'shortcuts' => [
                [
                    'name' => 'Pedidos',
                    'url' => '/orders',
                    'icons' => [['src' => '/assets/icons/mobile/shortcut-orders.png', 'sizes' => '96x96']]
                ],
                [
                    'name' => 'KDS',
                    'url' => '/kds',
                    'icons' => [['src' => '/assets/icons/mobile/shortcut-kds.png', 'sizes' => '96x96']]
                ]
            ]
        ];

        header('Content-Type: application/manifest+json');
        echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Renderiza view mobile
     */
    protected function viewMobile(string $path, array $data = [])
    {
        $file = __DIR__ . '/../views/admin/mobile/' . $path . '.php';
        
        if (!file_exists($file)) {
            http_response_code(500);
            echo "View mobile não encontrada: $path";
            return;
        }

        extract($data);
        include $file;
    }
}
