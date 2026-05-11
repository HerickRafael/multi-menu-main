<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

/**
 * Controller Mobile para Categorias
 * UI/UX otimizado para toque
 */
class MobileAdminCategoryController extends Controller
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
        
        $isRoot = $user['role'] === 'root';
        if (!$isRoot && (int)$user['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            echo 'Acesso negado';
            exit;
        }
        
        return [$user, $company, $slug];
    }

    /**
     * GET /categories - Lista de categorias
     */
    public function index(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        
        $pdo = db();
        
        // Buscar categorias com contagem de produtos
        $sql = "
            SELECT c.*, 
                COUNT(p.id) as product_count,
                SUM(CASE WHEN p.active = 1 THEN 1 ELSE 0 END) as active_products
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id
            WHERE c.company_id = ?
            GROUP BY c.id
            ORDER BY c.sort_order ASC, c.name ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$companyId]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
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
        $companyId = (int)$company['id'];
        
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        
        if (empty($name)) {
            $_SESSION['flash_error'] = 'Nome é obrigatório';
            header('Location: /categories/create');
            exit;
        }
        
        // Upload de imagem
        $image = null;
        if (!empty($_FILES['image']['tmp_name'])) {
            $image = $this->handleUpload($_FILES['image'], 'category');
        }
        
        $pdo = db();
        $stmt = $pdo->prepare("
            INSERT INTO categories (company_id, name, description, image, active, sort_order, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$companyId, $name, $description, $image, $active, $sortOrder]);
        
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
        
        $category = Category::find($id);
        if (!$category || (int)$category['company_id'] !== (int)$company['id']) {
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
        
        $category = Category::find($id);
        if (!$category || (int)$category['company_id'] !== (int)$company['id']) {
            header('Location: /categories');
            exit;
        }
        
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        
        if (empty($name)) {
            $_SESSION['flash_error'] = 'Nome é obrigatório';
            header("Location: /categories/{$id}/edit");
            exit;
        }
        
        $image = $category['image'];
        if (!empty($_FILES['image']['tmp_name'])) {
            $newImage = $this->handleUpload($_FILES['image'], 'category');
            if ($newImage) {
                $image = $newImage;
            }
        }
        
        $pdo = db();
        $stmt = $pdo->prepare("
            UPDATE categories SET name = ?, description = ?, image = ?, active = ?, sort_order = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$name, $description, $image, $active, $sortOrder, $id]);
        
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
        
        $category = Category::find($id);
        if (!$category || (int)$category['company_id'] !== (int)$company['id']) {
            $this->jsonResponse(['success' => false, 'message' => 'Categoria não encontrada']);
            return;
        }
        
        $newStatus = $category['active'] ? 0 : 1;
        
        $pdo = db();
        $stmt = $pdo->prepare("UPDATE categories SET active = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $id]);
        
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
        
        $pdo = db();
        
        // Verificar produtos
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $checkStmt->execute([$id]);
        
        if ($checkStmt->fetchColumn() > 0) {
            $_SESSION['flash_error'] = 'Categoria possui produtos e não pode ser excluída';
            header('Location: /categories');
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND company_id = ?");
        $stmt->execute([$id, (int)$company['id']]);
        
        $_SESSION['flash_success'] = 'Categoria excluída!';
        header('Location: /categories');
        exit;
    }

    private function handleUpload(?array $file, string $prefix): ?string
    {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            return null;
        }
        
        $name = $prefix . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $dest = __DIR__ . '/../../public/uploads/' . $name;
        
        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0777, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return 'uploads/' . $name;
        }
        
        return null;
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
