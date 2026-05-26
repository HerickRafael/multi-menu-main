<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/bootstrap.php';
require_once __DIR__ . '/../../../app/modules/auth/AdminGuard.php';
require_once __DIR__ . '/../../../app/services/ProductService.php';

class StoreAdminProductController extends Controller
{
    private function guard(string $slug): array
    {
        return AdminGuard::requireCompanyAccess($slug, true, 'products.manage');
    }

    public function index($params)
    {
        [$u, $company] = $this->guard((string)$params['slug']);
        $slug = (string)$company['slug'];
        $companyId = (int)$company['id'];

        $items = ProductService::listForAdmin($companyId);
        $cats = Category::allByCompany($companyId);

        $stats = [
            'total' => count($items),
            'active' => 0,
            'inactive' => 0,
            'combos' => 0,
        ];
        foreach ($items as $row) {
            if ((int)($row['active'] ?? 0) === 1) {
                $stats['active']++;
            } else {
                $stats['inactive']++;
            }
            if (($row['type'] ?? 'simple') === 'combo') {
                $stats['combos']++;
            }
        }

        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        $payload = [
            'products' => array_map(static function (array $row): array {
                return [
                    'id' => (int)$row['id'],
                    'name' => (string)($row['name'] ?? ''),
                    'description' => (string)($row['description'] ?? ''),
                    'sku' => (string)($row['sku'] ?? ''),
                    'price' => isset($row['price']) ? (float)$row['price'] : null,
                    'promo_price' => isset($row['promo_price']) && $row['promo_price'] !== null && $row['promo_price'] !== ''
                        ? (float)$row['promo_price']
                        : null,
                    'price_mode' => (string)($row['price_mode'] ?? 'fixed'),
                    'type' => (string)($row['type'] ?? 'simple'),
                    'category_id' => isset($row['category_id']) && $row['category_id'] !== null
                        ? (int)$row['category_id']
                        : null,
                    'category_name' => (string)($row['category_name'] ?? ''),
                    'image' => (string)($row['image'] ?? ''),
                    'sort_order' => (int)($row['sort_order'] ?? 0),
                    'active' => (int)($row['active'] ?? 0) === 1,
                    'allow_customize' => (int)($row['allow_customize'] ?? 0) === 1,
                ];
            }, $items),
            'categories' => array_map(static function (array $row): array {
                return ['id' => (int)$row['id'], 'name' => (string)($row['name'] ?? '')];
            }, $cats),
            'stats' => $stats,
            'flash' => ['error' => $error],
            'urls' => [
                'list' => '/admin/' . rawurlencode($slug) . '/products',
                'create' => '/admin/' . rawurlencode($slug) . '/products/create',
                'edit_base' => '/admin/' . rawurlencode($slug) . '/products/',
                'destroy_base' => '/admin/' . rawurlencode($slug) . '/products/',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_PRODUCTS__', $payload);
    }

    public function create($params)
    {
        [, $company] = $this->guard((string)$params['slug']);
        $form = ProductService::buildFormData((int)$company['id']);
        $this->renderProductFormSpa((string)$company['slug'], $company, $form, null);
    }

    public function edit($params)
    {
        [, $company] = $this->guard((string)$params['slug']);
        $form = ProductService::buildFormData((int)$company['id'], (int)($params['id'] ?? 0));

        if (empty($form['product']['id'])) {
            header('Location: ' . base_url('admin/' . rawurlencode((string)$company['slug']) . '/products'));
            exit;
        }

        $this->renderProductFormSpa((string)$company['slug'], $company, $form, (int)$form['product']['id']);
    }

    /**
     * Render the SPA shell with the product form payload.
     * @param array<string, mixed> $company
     * @param array<string, mixed> $form
     */
    private function renderProductFormSpa(string $slug, array $company, array $form, ?int $productId): void
    {
        $p = $form['product'] ?? [];
        $isEdit = $productId !== null;

        $payload = [
            'is_edit' => $isEdit,
            'product' => [
                'id' => $isEdit ? (int)$p['id'] : null,
                'name' => (string)($p['name'] ?? ''),
                'description' => (string)($p['description'] ?? ''),
                'sku' => (string)($p['sku'] ?? ''),
                'price' => isset($p['price']) ? (float)$p['price'] : 0,
                'promo_price' => isset($p['promo_price']) && $p['promo_price'] !== null && $p['promo_price'] !== ''
                    ? (float)$p['promo_price']
                    : null,
                'promo_start_at' => $p['promo_start_at'] ?? null,
                'promo_end_at' => $p['promo_end_at'] ?? null,
                'category_id' => isset($p['category_id']) && $p['category_id'] !== null ? (int)$p['category_id'] : null,
                'image' => (string)($p['image'] ?? ''),
                'type' => (string)($p['type'] ?? 'simple'),
                'price_mode' => (string)($p['price_mode'] ?? 'fixed'),
                'sort_order' => (int)($p['sort_order'] ?? 0),
                'active' => (int)($p['active'] ?? 1) === 1,
                'allow_customize' => (int)($p['allow_customize'] ?? 0) === 1,
            ],
            'categories' => array_map(static function (array $c): array {
                return ['id' => (int)$c['id'], 'name' => (string)($c['name'] ?? '')];
            }, $form['categories'] ?? []),
            'ingredients' => array_map(static function (array $i): array {
                return [
                    'id' => (int)$i['id'],
                    'name' => (string)($i['name'] ?? ''),
                    'internal_name' => (string)($i['internal_name'] ?? ''),
                    'cost' => isset($i['cost']) ? (float)$i['cost'] : 0,
                    'sale_price' => isset($i['sale_price']) ? (float)$i['sale_price'] : 0,
                    'unit' => (string)($i['unit'] ?? ''),
                    'image_path' => (string)($i['image_path'] ?? ''),
                ];
            }, $form['ingredients'] ?? []),
            'simple_products' => array_map(static function (array $sp): array {
                return [
                    'id' => (int)$sp['id'],
                    'name' => (string)($sp['name'] ?? ''),
                    'price' => isset($sp['price']) ? (float)$sp['price'] : 0,
                    'allow_customize' => (int)($sp['allow_customize'] ?? 0) === 1,
                    'category_id' => isset($sp['category_id']) && $sp['category_id'] !== null ? (int)$sp['category_id'] : null,
                ];
            }, $form['simpleProducts'] ?? []),
            'customization' => [
                'enabled' => !empty($form['customization']['enabled']),
                'groups' => array_map(static function (array $g): array {
                    $mode = (string)($g['mode'] ?? 'choice');
                    return [
                        'name' => (string)($g['name'] ?? ''),
                        'sort_order' => (int)($g['sort_order'] ?? 0),
                        'mode' => $mode === 'pool' ? 'pool' : 'choice',
                        'min' => (int)($g['min'] ?? 0),
                        'max' => (int)($g['max'] ?? 1),
                        'items' => array_map(static function (array $it): array {
                            return [
                                'ingredient_id' => (int)($it['ingredient_id'] ?? 0),
                                'sort_order' => (int)($it['sort_order'] ?? 0),
                                'min_qty' => (int)($it['min_qty'] ?? 0),
                                'max_qty' => (int)($it['max_qty'] ?? 1),
                                'default' => (int)($it['default'] ?? 0) === 1,
                                'default_qty' => (int)($it['default_qty'] ?? 0),
                            ];
                        }, $g['items'] ?? []),
                    ];
                }, $form['customization']['groups'] ?? []),
            ],
            'combo_groups' => array_map(static function (array $g): array {
                return [
                    'name' => (string)($g['name'] ?? ''),
                    'sort_order' => (int)($g['sort_order'] ?? 0),
                    'min' => (int)($g['min'] ?? 0),
                    'max' => (int)($g['max'] ?? 1),
                    'items' => array_map(static function (array $it): array {
                        return [
                            'product_id' => (int)($it['product_id'] ?? 0),
                            'sort_order' => (int)($it['sort_order'] ?? 0),
                            'customizable' => (int)($it['customizable'] ?? 0) === 1,
                            'price_override' => isset($it['price_override']) && $it['price_override'] !== null && $it['price_override'] !== ''
                                ? (float)$it['price_override']
                                : null,
                            'default_qty' => (int)($it['default_qty'] ?? 0),
                            'default' => (int)($it['default'] ?? 0) === 1,
                        ];
                    }, $g['items'] ?? []),
                ];
            }, $form['groups'] ?? []),
            'use_groups' => !empty($form['groups']),
            'customization_templates' => array_map(static function (array $t): array {
                return [
                    'id' => (int)$t['id'],
                    'name' => (string)($t['name'] ?? ''),
                    'mode' => (string)($t['mode'] ?? 'choice'),
                    'min' => (int)($t['min'] ?? 0),
                    'max' => (int)($t['max'] ?? 1),
                    'items' => array_map(static function (array $it): array {
                        return [
                            'ingredient_id' => (int)($it['ingredient_id'] ?? 0),
                            'ingredient_name' => (string)($it['ingredient_name'] ?? $it['name'] ?? ''),
                            'min_qty' => (int)($it['min_qty'] ?? 0),
                            'max_qty' => (int)($it['max_qty'] ?? 1),
                            'default' => (int)($it['default'] ?? 0) === 1,
                            'default_qty' => (int)($it['default_qty'] ?? 0),
                        ];
                    }, $t['items'] ?? []),
                ];
            }, $form['custTemplates'] ?? []),
            'flash' => ['error' => $_SESSION['flash_error'] ?? null],
            'urls' => [
                'list' => '/admin/' . rawurlencode($slug) . '/products',
                'submit' => $isEdit
                    ? '/admin/' . rawurlencode($slug) . '/products/' . (int)$productId
                    : '/admin/' . rawurlencode($slug) . '/products',
                'destroy' => $isEdit
                    ? '/admin/' . rawurlencode($slug) . '/products/' . (int)$productId . '/del'
                    : null,
            ],
        ];

        unset($_SESSION['flash_error']);

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_PRODUCT_FORM__', $payload);
    }

    public function store($params)
    {
        [, $company] = $this->guard((string)$params['slug']);
        $error = null;
        $productId = ProductService::save((int)$company['id'], $_POST, $_FILES['image'] ?? null, null, $error);

        if ($productId === null) {
            $_SESSION['flash_error'] = $error ?: 'Falha ao criar produto';
            header('Location: ' . base_url('admin/' . rawurlencode((string)$company['slug']) . '/products/create'));
            exit;
        }

        header('Location: ' . base_url('admin/' . rawurlencode((string)$company['slug']) . '/products'));
        exit;
    }

    public function update($params)
    {
        [, $company] = $this->guard((string)$params['slug']);
        $error = null;
        $productId = ProductService::save((int)$company['id'], $_POST, $_FILES['image'] ?? null, (int)$params['id'], $error);

        if ($productId === null) {
            $_SESSION['flash_error'] = $error ?: 'Falha ao atualizar produto';
            header('Location: ' . base_url('admin/' . rawurlencode((string)$company['slug']) . '/products/' . (int)$params['id'] . '/edit'));
            exit;
        }

        header('Location: ' . base_url('admin/' . rawurlencode((string)$company['slug']) . '/products'));
        exit;
    }

    public function destroy($params)
    {
        [, $company] = $this->guard((string)$params['slug']);
        ProductService::delete((int)$company['id'], (int)$params['id']);
        header('Location: ' . base_url('admin/' . rawurlencode((string)$company['slug']) . '/products'));
        exit;
    }

    public function simpleProductsSearch($params)
    {
        [, $company] = $this->guard((string)$params['slug']);
        header('Content-Type: application/json; charset=utf-8');

        try {
            $products = ProductService::searchSimpleProducts(
                (int)$company['id'],
                trim((string)($_GET['search'] ?? '')),
                (int)($_GET['limit'] ?? 20)
            );

            echo json_encode(['success' => true, 'products' => $products], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            error_log('[AdminProductController] simpleProductsSearch falhou: ' . $e->getMessage());
            echo json_encode(['success' => false, 'products' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
