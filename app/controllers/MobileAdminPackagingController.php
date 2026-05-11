<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/PackagingSupply.php';

/**
 * MobileAdminPackagingController
 *
 * CRUD de insumos/embalagens otimizado para mobile.
 */
class MobileAdminPackagingController extends Controller
{
    private function guard(): array
    {
        Auth::start();

        $slug = $_SERVER['MOBILE_SLUG'] ?? 'wollburger';

        if (!Auth::checkAdmin()) {
            header('Location: /login');
            exit;
        }

        $company = Company::findBySlug($slug);

        if (!$company || empty($company['id'])) {
            http_response_code(404);
            echo 'Empresa inválida';
            exit;
        }

        $u = Auth::user();
        if ($u['role'] !== 'root' && (int)$u['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            echo 'Acesso negado';
            exit;
        }

        $this->ensureCompanyContext((int)$company['id'], $slug);

        return [$u, $company];
    }

    /**
     * GET /packaging — Lista insumos
     */
    public function index(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $companyId = (int)$company['id'];

        $supplies = PackagingSupply::listByCompany($companyId, false);

        // Inject product_count
        foreach ($supplies as &$s) {
            $s['product_count'] = PackagingSupply::countProductsUsing((int)$s['id']);
        }
        unset($s);

        $success = $_GET['success'] ?? null;
        $error = $_GET['error'] ?? null;

        $this->view('admin/mobile/packaging/index', [
            'pageTitle' => 'Insumos & Embalagens',
            'activeNav' => 'settings',
            'supplies' => $supplies,
            'success' => $success,
            'error' => $error,
            'company' => $company,
            'user' => $u,
        ]);
    }

    /**
     * GET /packaging/create — Form criação
     */
    public function create(array $params = []): void
    {
        [$u, $company] = $this->guard();

        $this->view('admin/mobile/packaging/form', [
            'pageTitle' => 'Novo Insumo',
            'activeNav' => 'settings',
            'supply' => null,
            'isEdit' => false,
            'company' => $company,
            'user' => $u,
        ]);
    }

    /**
     * GET /packaging/{id}/edit — Form edição
     */
    public function edit(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $companyId = (int)$company['id'];
        $id = (int)($params['id'] ?? 0);

        $supply = PackagingSupply::findByCompany($id, $companyId);
        if (!$supply) {
            header('Location: /packaging?error=notfound');
            exit;
        }

        $products = PackagingSupply::getProductsUsing($id);

        $this->view('admin/mobile/packaging/form', [
            'pageTitle' => 'Editar Insumo',
            'activeNav' => 'settings',
            'supply' => $supply,
            'isEdit' => true,
            'products' => $products,
            'company' => $company,
            'user' => $u,
        ]);
    }

    /**
     * POST /packaging/store — Salvar (create/update)
     */
    public function store(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $companyId = (int)$company['id'];

        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            header('Location: /packaging?error=name');
            exit;
        }

        if (PackagingSupply::existsByName($companyId, $name, $id)) {
            header('Location: /packaging?error=duplicate');
            exit;
        }

        $data = [
            'company_id' => $companyId,
            'name' => $name,
            'description' => trim($_POST['description'] ?? ''),
            'unit' => $_POST['unit'] ?? 'un',
            'cost_per_unit' => (float)str_replace(',', '.', $_POST['cost_per_unit'] ?? '0'),
            'stock_quantity' => (float)str_replace(',', '.', $_POST['stock_quantity'] ?? '0'),
            'min_stock_alert' => (float)str_replace(',', '.', $_POST['min_stock_alert'] ?? '0'),
            'supplier' => trim($_POST['supplier'] ?? ''),
            'active' => isset($_POST['active']) ? 1 : 0,
        ];

        if ($id) {
            PackagingSupply::update($id, $data);
            header('Location: /packaging?success=updated');
        } else {
            PackagingSupply::create($data);
            header('Location: /packaging?success=created');
        }
        exit;
    }

    /**
     * POST /packaging/{id}/delete — Excluir
     */
    public function delete(array $params = []): void
    {
        [$u, $company] = $this->guard();
        $companyId = (int)$company['id'];
        $id = (int)($params['id'] ?? 0);

        $supply = PackagingSupply::findByCompany($id, $companyId);
        if (!$supply) {
            header('Location: /packaging?error=notfound');
            exit;
        }

        PackagingSupply::delete($id);
        header('Location: /packaging?success=deleted');
        exit;
    }
}
