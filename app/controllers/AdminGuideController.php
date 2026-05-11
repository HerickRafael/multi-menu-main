<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

class AdminGuideController extends Controller
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

    public function products($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        return $this->view('admin/guide/products', compact('company'));
    }

    public function ingredients($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        return $this->view('admin/guide/ingredients', compact('company'));
    }

    public function coupons($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        return $this->view('admin/guide/coupons', compact('company'));
    }

    public function crossSell($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        return $this->view('admin/guide/cross-sell', compact('company'));
    }

    public function paymentMethods($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        return $this->view('admin/guide/payment-methods', compact('company'));
    }

    public function deliveryFees($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        return $this->view('admin/guide/delivery-fees', compact('company'));
    }

    public function loyaltyDiscount($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        return $this->view('admin/guide/loyalty-discount', compact('company'));
    }

    public function financial($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        return $this->view('admin/guide/financial', compact('company'));
    }

    public function customizationTemplates($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        return $this->view('admin/guide/customization-templates', compact('company'));
    }

    public function companySettings($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        return $this->view('admin/guide/company-settings', compact('company'));
    }

    public function manualOrder($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        return $this->view('admin/guide/manual-order', compact('company'));
    }

    public function whatsapp($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        return $this->view('admin/guide/whatsapp', compact('company'));
    }

    public function ifood($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        return $this->view('admin/guide/ifood', compact('company'));
    }
}
