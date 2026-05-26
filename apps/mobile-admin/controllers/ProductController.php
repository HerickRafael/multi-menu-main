<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/bootstrap.php';
require_once __DIR__ . '/../../../app/modules/auth/MobileAdminGuard.php';
require_once __DIR__ . '/../../../app/services/ProductService.php';

class MobileAdminProductAppController extends Controller
{
    private function guard(): array
    {
        $slug = $_SERVER['MOBILE_SLUG'] ?? 'wollburger';
        [$user, $company] = MobileAdminGuard::requireCompanyAccess('products.manage');

        return [$user, $company, $slug];
    }

    public function index(array $params = [])
    {
        [$user, $company] = $this->guard();
        $search = trim($_GET['q'] ?? '');
        $categoryId = isset($_GET['category']) && $_GET['category'] !== '' ? (int)$_GET['category'] : null;
        $status = $_GET['status'] ?? 'all';

        return $this->viewMobile('products/index', [
            'company' => $company,
            'products' => ProductService::listForMobile((int)$company['id'], $search, $categoryId, $status),
            'categories' => Category::allByCompany((int)$company['id']),
            'stats' => ProductService::productStats((int)$company['id']),
            'search' => $search,
            'categoryId' => $categoryId,
            'status' => $status,
            'pageTitle' => 'Produtos',
            'activeNav' => 'products',
        ]);
    }

    public function show(array $params = [])
    {
        return $this->edit($params);
    }

    public function create(array $params = [])
    {
        [, $company] = $this->guard();
        $form = ProductService::buildFormData((int)$company['id']);

        return $this->viewMobile('products/form', [
            'company' => $company,
            'product' => $form['product'],
            'categories' => $form['categories'],
            'ingredients' => $form['ingredients'],
            'simpleProducts' => $form['simpleProducts'],
            'customization' => $form['customization'],
            'groups' => $form['groups'],
            'custTemplates' => $form['custTemplates'],
            'pageTitle' => 'Novo Produto',
            'activeNav' => 'products',
            'showBackButton' => true,
        ]);
    }

    public function store(array $params = [])
    {
        [, $company] = $this->guard();
        $error = null;
        $productId = ProductService::save((int)$company['id'], $_POST, $_FILES['image'] ?? null, null, $error);

        if ($productId === null) {
            $_SESSION['flash_error'] = $error ?: 'Nome e preço são obrigatórios';
            header('Location: /products/create');
            exit;
        }

        $_SESSION['flash_success'] = 'Produto criado com sucesso!';
        header('Location: /products');
        exit;
    }

    public function edit(array $params = [])
    {
        [, $company] = $this->guard();
        $form = ProductService::buildFormData((int)$company['id'], (int)($params['id'] ?? 0));

        if (empty($form['product']['id'])) {
            header('Location: /products');
            exit;
        }

        return $this->viewMobile('products/form', [
            'company' => $company,
            'product' => $form['product'],
            'categories' => $form['categories'],
            'ingredients' => $form['ingredients'],
            'simpleProducts' => $form['simpleProducts'],
            'customization' => $form['customization'],
            'groups' => $form['groups'],
            'custTemplates' => $form['custTemplates'],
            'pageTitle' => 'Editar Produto',
            'activeNav' => 'products',
            'showBackButton' => true,
        ]);
    }

    public function update(array $params = [])
    {
        [, $company] = $this->guard();
        $id = (int)($params['id'] ?? 0);
        $error = null;
        $productId = ProductService::save((int)$company['id'], $_POST, $_FILES['image'] ?? null, $id, $error);

        if ($productId === null) {
            $_SESSION['flash_error'] = $error ?: 'Nome e preço são obrigatórios';
            header('Location: /products/' . $id . '/edit');
            exit;
        }

        $_SESSION['flash_success'] = 'Produto atualizado!';
        header('Location: /products');
        exit;
    }

    public function toggle(array $params = [])
    {
        [, $company] = $this->guard();
        $id = (int)($params['id'] ?? 0);
        $newStatus = ProductService::toggle((int)$company['id'], $id);

        if ($newStatus === null) {
            $this->jsonResponse(['success' => false, 'message' => 'Produto não encontrado']);
            return;
        }

        $this->jsonResponse([
            'success' => true,
            'message' => $newStatus ? 'Produto ativado!' : 'Produto desativado!',
            'active' => $newStatus,
        ]);
    }

    public function destroy(array $params = [])
    {
        [, $company] = $this->guard();
        $id = (int)($params['id'] ?? 0);

        if (!ProductService::delete((int)$company['id'], $id)) {
            $_SESSION['flash_error'] = 'Produto não encontrado';
            header('Location: /products');
            exit;
        }

        $_SESSION['flash_success'] = 'Produto excluído!';
        header('Location: /products');
        exit;
    }

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function viewMobile(string $view, array $data = [])
    {
        extract($data);
        $viewFile = __DIR__ . '/../views/mobile-admin/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new RuntimeException("View mobile não encontrada: {$view}");
        }
        ob_start();
        require $viewFile;
        $content = ob_get_clean();
        require __DIR__ . '/../views/mobile-admin/layout.php';
    }
}
