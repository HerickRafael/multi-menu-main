<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

class AdminGuideController extends Controller
{
    /**
     * Map of route topic → ['view' => relative view path, 'label' => sidebar label, 'icon' => emoji].
     * Order defines the SPA-side sidebar listing.
     */
    private const TOPICS = [
        'products' => ['view' => 'admin/guide/products', 'label' => 'Produtos', 'icon' => 'Package'],
        'ingredients' => ['view' => 'admin/guide/ingredients', 'label' => 'Ingredientes', 'icon' => 'Carrot'],
        'customization-templates' => ['view' => 'admin/guide/customization-templates', 'label' => 'Templates de customização', 'icon' => 'Sliders'],
        'coupons' => ['view' => 'admin/guide/coupons', 'label' => 'Cupons', 'icon' => 'Ticket'],
        'cross-sell' => ['view' => 'admin/guide/cross-sell', 'label' => 'Cross-sell', 'icon' => 'Layers'],
        'loyalty-discount' => ['view' => 'admin/guide/loyalty-discount', 'label' => 'Fidelidade & Desconto', 'icon' => 'Heart'],
        'payment-methods' => ['view' => 'admin/guide/payment-methods', 'label' => 'Métodos de pagamento', 'icon' => 'CreditCard'],
        'delivery-fees' => ['view' => 'admin/guide/delivery-fees', 'label' => 'Taxas de entrega', 'icon' => 'Truck'],
        'financial' => ['view' => 'admin/guide/financial', 'label' => 'Financeiro', 'icon' => 'BarChart3'],
        'company-settings' => ['view' => 'admin/guide/company-settings', 'label' => 'Configurações da loja', 'icon' => 'Settings'],
        'manual-order' => ['view' => 'admin/guide/manual-order', 'label' => 'Pedido manual', 'icon' => 'ClipboardList'],
        'whatsapp' => ['view' => 'admin/guide/whatsapp', 'label' => 'WhatsApp', 'icon' => 'MessageSquare'],
        'ifood' => ['view' => 'admin/guide/ifood', 'label' => 'iFood', 'icon' => 'Utensils'],
    ];

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

    private function renderTopic(string $topicKey, array $params): void
    {
        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guard($slug);

        $topic = self::TOPICS[$topicKey] ?? null;
        if (!$topic) {
            http_response_code(404);
            echo 'Tópico de guia não encontrado.';
            return;
        }

        // Capture the guide view's content-only output by setting the SPA sentinel.
        $GLOBALS['__SPA_GUIDE_CAPTURE__'] = true;
        try {
            ob_start();
            $this->view($topic['view'], ['company' => $company, 'activeSlug' => $slug]);
            $html = (string)ob_get_clean();
        } finally {
            unset($GLOBALS['__SPA_GUIDE_CAPTURE__']);
        }

        $topicsList = [];
        foreach (self::TOPICS as $k => $t) {
            $topicsList[] = [
                'key' => $k,
                'label' => $t['label'],
                'icon' => $t['icon'],
                'url' => '/admin/' . rawurlencode($slug) . '/guide/' . $k,
            ];
        }

        $payload = [
            'topic' => $topicKey,
            'topic_label' => $topic['label'],
            'topic_icon' => $topic['icon'],
            'html' => $html,
            'topics' => $topicsList,
            'urls' => [
                'dashboard' => '/admin/' . rawurlencode($slug) . '/dashboard',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_GUIDE__', $payload);
    }

    public function products($params) { $this->renderTopic('products', $params); }
    public function ingredients($params) { $this->renderTopic('ingredients', $params); }
    public function coupons($params) { $this->renderTopic('coupons', $params); }
    public function crossSell($params) { $this->renderTopic('cross-sell', $params); }
    public function paymentMethods($params) { $this->renderTopic('payment-methods', $params); }
    public function deliveryFees($params) { $this->renderTopic('delivery-fees', $params); }
    public function loyaltyDiscount($params) { $this->renderTopic('loyalty-discount', $params); }
    public function financial($params) { $this->renderTopic('financial', $params); }
    public function customizationTemplates($params) { $this->renderTopic('customization-templates', $params); }
    public function companySettings($params) { $this->renderTopic('company-settings', $params); }
    public function manualOrder($params) { $this->renderTopic('manual-order', $params); }
    public function whatsapp($params) { $this->renderTopic('whatsapp', $params); }
    public function ifood($params) { $this->renderTopic('ifood', $params); }
}
