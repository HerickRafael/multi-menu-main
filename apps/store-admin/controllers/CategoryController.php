<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../../../app/bootstrap.php';
require_once __DIR__ . '/../../../app/modules/auth/AdminGuard.php';
require_once __DIR__ . '/../../../app/services/CategoryService.php';

class StoreAdminCategoryController extends Controller
{
    private function guard($slug)
    {
        return AdminGuard::requireCompanyAccess((string)$slug, true, 'categories.manage');
    }

    public function index($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $cats = CategoryService::listForAdmin((int)$company['id']);
        $slug = (string)$company['slug'];

        $payload = [
            'categories' => array_map(static function (array $row): array {
                return [
                    'id' => (int)$row['id'],
                    'name' => (string)($row['name'] ?? ''),
                    'sort_order' => (int)($row['sort_order'] ?? 0),
                    'active' => (int)($row['active'] ?? 0) === 1,
                ];
            }, $cats),
            'show_most_ordered' => (int)($company['show_most_ordered'] ?? 0) === 1,
            'urls' => [
                'list' => '/admin/' . rawurlencode($slug) . '/categories',
                'create' => '/admin/' . rawurlencode($slug) . '/categories/create',
                'store' => '/admin/' . rawurlencode($slug) . '/categories',
                'edit_base' => '/admin/' . rawurlencode($slug) . '/categories/',
                'update_base' => '/admin/' . rawurlencode($slug) . '/categories/',
                'destroy_base' => '/admin/' . rawurlencode($slug) . '/categories/',
                'most_ordered' => '/admin/' . rawurlencode($slug) . '/categories/most-ordered',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_CATEGORIES__', $payload);
    }

    public function create($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $slug = (string)$company['slug'];

        $payload = [
            'category' => ['id' => null, 'name' => '', 'sort_order' => 0, 'active' => true],
            'urls' => [
                'list' => '/admin/' . rawurlencode($slug) . '/categories',
                'submit' => '/admin/' . rawurlencode($slug) . '/categories',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_CATEGORY_FORM__', $payload);
    }

    public function store($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        CategoryService::save((int)$company['id'], $_POST);
        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/categories'));
        exit;
    }

    public function edit($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $cat = CategoryService::findForCompany((int)$company['id'], (int)$params['id']);
        $slug = (string)$company['slug'];

        if (!$cat) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/categories'));
            exit;
        }

        $payload = [
            'category' => [
                'id' => (int)$cat['id'],
                'name' => (string)($cat['name'] ?? ''),
                'sort_order' => (int)($cat['sort_order'] ?? 0),
                'active' => (int)($cat['active'] ?? 0) === 1,
            ],
            'urls' => [
                'list' => '/admin/' . rawurlencode($slug) . '/categories',
                'submit' => '/admin/' . rawurlencode($slug) . '/categories/' . (int)$cat['id'],
                'destroy' => '/admin/' . rawurlencode($slug) . '/categories/' . (int)$cat['id'] . '/del',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_CATEGORY_FORM__', $payload);
    }

    public function update($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        CategoryService::save((int)$company['id'], $_POST, (int)$params['id']);
        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/categories'));
        exit;
    }

    public function destroy($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        CategoryService::delete((int)$company['id'], (int)$params['id']);
        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/categories'));
        exit;
    }

    public function updateMostOrdered($params)
    {
        [$u, $company] = $this->guard($params['slug']);

        $showMostOrdered = isset($_POST['show_most_ordered']) ? (int)$_POST['show_most_ordered'] : 0;
        $showMostOrdered = ($showMostOrdered === 1) ? 1 : 0;

        try {
            db()->prepare('UPDATE companies SET show_most_ordered = ? WHERE id = ?')
              ->execute([$showMostOrdered, (int)$company['id']]);
        } catch (\Throwable $e) {
            error_log('[AdminCategoryController] updateMostOrdered falhou: ' . $e->getMessage());
        }

        if (class_exists('SmartCache')) {
            SmartCache::forget('company:id:' . (int)$company['id']);
            SmartCache::forget('company:slug:' . (string)$company['slug']);
            SmartCache::forgetByPattern('companies:*');
        }

        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/categories'));
    }

    public function mostOrderedRedirect($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/categories'));
    }

    /**
     * Render the React SPA shell with an injected payload accessible via window.<key>.
     */
}
