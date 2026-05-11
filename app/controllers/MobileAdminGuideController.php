<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

class MobileAdminGuideController extends Controller
{
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

        return [$user, $company, $slug];
    }

    public function products(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $pageTitle = 'Guia de Produtos';
        $activeNav = 'products';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/guide/products.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    public function ingredients(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $pageTitle = 'Guia de Ingredientes';
        $activeNav = 'ingredients';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/guide/ingredients.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    public function coupons(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $pageTitle = 'Guia de Cupons';
        $activeNav = 'coupons';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/guide/coupons.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    public function crossSell(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $pageTitle = 'Guia de Cross-Sell';
        $activeNav = 'products';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/guide/cross-sell.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    public function paymentMethods(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $pageTitle = 'Guia de Pagamentos';
        $activeNav = 'settings';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/guide/payment-methods.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    public function deliveryFees(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $pageTitle = 'Guia de Taxas de Entrega';
        $activeNav = 'settings';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/guide/delivery-fees.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    public function loyaltyDiscount(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $pageTitle = 'Guia de Fidelidade';
        $activeNav = 'settings';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/guide/loyalty-discount.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    public function financial(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $pageTitle = 'Guia Financeiro';
        $activeNav = 'financial';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/guide/financial.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    public function customizationTemplates(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $pageTitle = 'Guia de Personalização';
        $activeNav = 'products';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/guide/customization-templates.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    public function companySettings(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $pageTitle = 'Guia de Configurações';
        $activeNav = 'settings';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/guide/company-settings.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    public function manualOrder(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $pageTitle = 'Guia de Pedido Manual';
        $activeNav = 'orders';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/guide/manual-order.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    public function whatsapp(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $pageTitle = 'Guia WhatsApp';
        $activeNav = 'settings';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/guide/whatsapp.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    public function ifood(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $pageTitle = 'Guia iFood';
        $activeNav = 'ifood';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/guide/ifood.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }
}
