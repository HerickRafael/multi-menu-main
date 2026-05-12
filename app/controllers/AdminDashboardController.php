<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/AdminGuard.php';

class AdminDashboardController extends Controller
{
    /**
     * Garante autenticação e contexto de empresa pelo slug.
     * Retorna [ $user, $company ].
     */
    private function guard(string $slug): array
    {
        return AdminGuard::requireCompanyAccess($slug);
    }

    /** GET /admin/{slug}/dashboard */
    public function index(array $params)
    {
        $slug = trim((string)($params['slug'] ?? ''));

        if ($slug === '') {
            http_response_code(400);
            echo 'Slug inválido';

            return;
        }

        [ $u, $company ] = $this->guard($slug);

        $companyId = (int)$company['id'];
        $db = db();
        $categories = Category::listByCompany($companyId);
        $products   = Product::listByCompany($companyId);
        $ingredientsCount = Ingredient::countByCompany($companyId);
        $ordersCount = Order::countByCompany($db, $companyId);
        $recentIngredients = Ingredient::listRecentByCompany($companyId, 8);
        $recentOrders = Order::listRecentByCompany($companyId, 8);

        foreach ($recentIngredients as &$ing) {
            $assigned = Ingredient::assignedProducts((int)$ing['id']);
            $ing['product_names'] = array_column($assigned, 'name');
        }
        unset($ing);

        // slug efetivo do contexto (usado para montar URLs no dashboard, ex.: botão Pedidos)
        $activeSlug = $this->currentCompanySlug() ?? $slug;

        return $this->view('admin/dashboard/index', compact(
            'company',
            'u',
            'categories',
            'products',
            'ingredientsCount',
            'ordersCount',
            'recentIngredients',
            'recentOrders',
            'activeSlug'
        ));
    }

    /**
     * GET /admin/{slug}/manifest.webmanifest
     * Manifest dinâmico com cores da empresa
     */
    public function manifest(array $params)
    {
        $slug = trim((string)($params['slug'] ?? ''));
        
        if ($slug === '') {
            http_response_code(404);
            return;
        }
        
        $company = Company::findBySlug($slug);
        
        if (!$company) {
            http_response_code(404);
            return;
        }
        
        // Cores da empresa
        $primaryColor = admin_theme_primary_color($company);
        $companyName = $company['name'] ?? 'Admin';
        
        $manifest = [
            'name' => "{$companyName} - Painel Administrativo",
            'short_name' => $companyName,
            'description' => "Painel administrativo de {$companyName}",
            'start_url' => "/admin/{$slug}/dashboard",
            'scope' => "/admin/{$slug}",
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => '#f8fafc',
            'theme_color' => $primaryColor,
            'lang' => 'pt-BR',
            'dir' => 'ltr',
            'categories' => ['business', 'productivity'],
            'icons' => [
                [
                    'src' => '/assets/icons/admin/icon-72x72.png',
                    'sizes' => '72x72',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => '/assets/icons/admin/icon-96x96.png',
                    'sizes' => '96x96',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => '/assets/icons/admin/icon-128x128.png',
                    'sizes' => '128x128',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => '/assets/icons/admin/icon-144x144.png',
                    'sizes' => '144x144',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => '/assets/icons/admin/icon-152x152.png',
                    'sizes' => '152x152',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => '/assets/icons/admin/icon-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => '/assets/icons/admin/icon-384x384.png',
                    'sizes' => '384x384',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => '/assets/icons/admin/icon-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ]
            ],
            'screenshots' => [
                [
                    'src' => '/assets/screenshots/admin-dashboard.png',
                    'sizes' => '1280x720',
                    'type' => 'image/png',
                    'form_factor' => 'wide'
                ],
                [
                    'src' => '/assets/screenshots/admin-mobile.png',
                    'sizes' => '390x844',
                    'type' => 'image/png',
                    'form_factor' => 'narrow'
                ]
            ]
        ];
        
        header('Content-Type: application/manifest+json');
        header('Cache-Control: public, max-age=86400');
        echo json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
