<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/bootstrap.php';
require_once __DIR__ . '/../../../app/modules/auth/MobileAdminGuard.php';
require_once __DIR__ . '/../../../app/services/CategoryService.php';

/**
 * Controller Mobile para Categorias
 * UI/UX otimizado para toque
 */
class MobileAdminCategoryAppController extends Controller
{
    private function guard(): array
    {
        $slug = $_SERVER['MOBILE_SLUG'] ?? 'wollburger';
        [$user, $company] = MobileAdminGuard::requireCompanyAccess('categories.manage');
        
        return [$user, $company, $slug];
    }

    /**
     * GET /categories - Lista de categorias
     */
    public function index(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $categories = CategoryService::listForMobile((int)$company['id']);
        
        $success = $_SESSION['flash_success'] ?? null;
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        
        return $this->viewMobile('categories/index', [
            'company' => $company,
            'categories' => $categories,
            'success' => $success,
            'error' => $error,
            'pageTitle' => 'Categorias',
            'activeNav' => 'products',
            'showBackButton' => true
        ]);
    }

    /**
     * GET /categories/create - Form criar
     */
    public function create(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        return $this->viewMobile('categories/form', [
            'company' => $company,
            'category' => null,
            'pageTitle' => 'Nova Categoria',
            'activeNav' => 'products',
            'showBackButton' => true
        ]);
    }

    /**
     * POST /categories - Criar categoria
     */
    public function store(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        if (trim((string)($_POST['name'] ?? '')) === '') {
            $_SESSION['flash_error'] = 'Nome é obrigatório';
            header('Location: /categories/create');
            exit;
        }
        $image = CategoryService::uploadImage($_FILES['image'] ?? null, 'category');
        CategoryService::save((int)$company['id'], array_merge($_POST, ['image' => $image]));
        
        $_SESSION['flash_success'] = 'Categoria criada!';
        header('Location: /categories');
        exit;
    }

    /**
     * GET /categories/{id}/edit - Form editar
     */
    public function edit(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $id = (int)($params['id'] ?? 0);
        
        $category = CategoryService::findForCompany((int)$company['id'], $id);
        if (!$category) {
            header('Location: /categories');
            exit;
        }
        
        return $this->viewMobile('categories/form', [
            'company' => $company,
            'category' => $category,
            'pageTitle' => 'Editar Categoria',
            'activeNav' => 'products',
            'showBackButton' => true
        ]);
    }

    /**
     * POST /categories/{id} - Atualizar categoria
     */
    public function update(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $id = (int)($params['id'] ?? 0);
        
        $category = CategoryService::findForCompany((int)$company['id'], $id);
        if (!$category) {
            header('Location: /categories');
            exit;
        }
        if (trim((string)($_POST['name'] ?? '')) === '') {
            $_SESSION['flash_error'] = 'Nome é obrigatório';
            header("Location: /categories/{$id}/edit");
            exit;
        }
        $image = $category['image'] ?? null;
        if (!empty($_FILES['image']['tmp_name'])) {
            $newImage = CategoryService::uploadImage($_FILES['image'], 'category');
            if ($newImage) {
                $image = $newImage;
            }
        }
        CategoryService::save((int)$company['id'], array_merge($_POST, ['image' => $image]), $id);
        
        $_SESSION['flash_success'] = 'Categoria atualizada!';
        header('Location: /categories');
        exit;
    }

    /**
     * POST /categories/{id}/toggle - Toggle ativo/inativo
     */
    public function toggle(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $id = (int)($params['id'] ?? 0);
        
        $newStatus = CategoryService::toggle((int)$company['id'], $id);
        if ($newStatus === null) {
            $this->jsonResponse(['success' => false, 'message' => 'Categoria não encontrada']);
            return;
        }
        
        $this->jsonResponse([
            'success' => true, 
            'active' => $newStatus,
            'message' => $newStatus ? 'Categoria ativada' : 'Categoria desativada'
        ]);
    }

    /**
     * POST /categories/{id}/delete - Excluir categoria
     */
    public function destroy(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $id = (int)($params['id'] ?? 0);
        
        if (!CategoryService::delete((int)$company['id'], $id)) {
            $_SESSION['flash_error'] = 'Categoria possui produtos e não pode ser excluída';
            header('Location: /categories');
            exit;
        }
        
        $_SESSION['flash_success'] = 'Categoria excluída!';
        header('Location: /categories');
        exit;
    }

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
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
