<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/ProductCustomization.php';
require_once __DIR__ . '/../models/CustomizationTemplate.php';

/**
 * Controller Mobile para Produtos
 * UI/UX otimizado para toque
 */
class MobileAdminProductController extends Controller
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
     * GET /products - Lista de produtos
     */
    public function index(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        
        $search = trim($_GET['q'] ?? '');
        $categoryId = $_GET['category'] ?? null;
        $status = $_GET['status'] ?? 'all'; // all, active, inactive
        
        // Buscar categorias
        $categories = Category::allByCompany($companyId);
        
        // Buscar produtos
        $pdo = db();
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.company_id = ?";
        $params = [$companyId];
        
        if ($search !== '') {
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        if ($categoryId) {
            $sql .= " AND p.category_id = ?";
            $params[] = (int)$categoryId;
        }
        
        if ($status === 'active') {
            $sql .= " AND p.active = 1";
        } elseif ($status === 'inactive') {
            $sql .= " AND p.active = 0";
        }
        
        $sql .= " ORDER BY c.sort_order ASC, p.sort_order ASC, p.name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Stats
        $statsStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN active = 0 THEN 1 ELSE 0 END) as inactive
            FROM products WHERE company_id = ?
        ");
        $statsStmt->execute([$companyId]);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        return $this->viewMobile('products/index', [
            'company' => $company,
            'products' => $products,
            'categories' => $categories,
            'stats' => $stats,
            'search' => $search,
            'categoryId' => $categoryId,
            'status' => $status,
            'pageTitle' => 'Produtos',
            'activeNav' => 'products'
        ]);
    }

    /**
     * GET /products/{id} - Ver produto
     */
    public function show(array $params = [])
    {
        // Redirecionar para edição — view show não existe no mobile
        return $this->edit($params);
    }

    /**
     * GET /products/create - Form criar produto
     */
    public function create(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        
        $categories = Category::allByCompany($companyId);
        $ingredients = Ingredient::allForCompany($companyId);
        $simpleProducts = Product::simpleProductsForCompany($companyId);
        
        $product = [
            'id' => null,
            'name' => '',
            'description' => '',
            'price' => 0.0,
            'promo_price' => null,
            'promo_percentage' => null,
            'sku' => Product::nextSkuForCompany($companyId),
            'sort_order' => 0,
            'active' => 1,
            'category_id' => null,
            'image' => null,
            'type' => 'simple',
            'price_mode' => 'fixed',
        ];
        
        $customization = ['enabled' => false, 'groups' => []];
        $groups = [];
        $custTemplates = CustomizationTemplate::listWithItemsForCompany($companyId);
        
        return $this->viewMobile('products/form', [
            'company' => $company,
            'product' => $product,
            'categories' => $categories,
            'ingredients' => $ingredients,
            'simpleProducts' => $simpleProducts,
            'customization' => $customization,
            'groups' => $groups,
            'custTemplates' => $custTemplates,
            'pageTitle' => 'Novo Produto',
            'activeNav' => 'products',
            'showBackButton' => true
        ]);
    }

    /**
     * POST /products - Criar produto
     */
    public function store(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)str_replace(',', '.', $_POST['price'] ?? '0');
        $promoPrice = !empty($_POST['promo_price']) ? (float)str_replace(',', '.', $_POST['promo_price']) : null;
        $promoPercentage = !empty($_POST['promo_percentage']) ? (int)$_POST['promo_percentage'] : null;
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $active = isset($_POST['active']) ? 1 : 0;
        $type = in_array($_POST['type'] ?? '', ['simple', 'combo']) ? $_POST['type'] : 'simple';
        $priceMode = in_array($_POST['price_mode'] ?? '', ['fixed', 'sum']) ? $_POST['price_mode'] : 'fixed';
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        
        if (empty($name) || $price <= 0) {
            $_SESSION['flash_error'] = 'Nome e preço são obrigatórios';
            header('Location: /products/create');
            exit;
        }
        
        // Upload de imagem
        $image = null;
        if (!empty($_FILES['image']['tmp_name'])) {
            $image = $this->handleUpload($_FILES['image'], 'product');
        }
        
        // Processar personalização
        $custPayload = $_POST['customization'] ?? [];
        $custData = ProductCustomization::sanitizePayload(is_array($custPayload) ? $custPayload : [], $companyId);
        
        // Processar grupos de combo
        $useGroups = $type === 'combo' && (!empty($_POST['use_groups']) || !empty($_POST['groups']));
        $groupsPayload = $useGroups && isset($_POST['groups']) && is_array($_POST['groups'])
            ? Product::sanitizeComboGroupsPayload($_POST['groups'], $companyId)
            : [];
        
        $sku = Product::nextSkuForCompany($companyId);
        
        $data = [
            'company_id' => $companyId,
            'category_id' => $categoryId,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'promo_price' => $promoPrice,
            'sku' => $sku,
            'image' => $image,
            'active' => $active,
            'sort_order' => $sortOrder,
            'allow_customize' => $type === 'simple' && !empty($custData['enabled']) && !empty($custData['groups']) ? 1 : 0,
            'type' => $type,
            'price_mode' => $priceMode,
        ];
        
        $productId = Product::create($data);
        ProductCustomization::save($productId, $custData);
        Product::saveComboGroupsAndItems($productId, $type === 'combo' ? $groupsPayload : []);
        
        $_SESSION['flash_success'] = 'Produto criado com sucesso!';
        header('Location: /products');
        exit;
    }

    /**
     * GET /products/{id}/edit - Form editar produto
     */
    public function edit(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        $id = (int)($params['id'] ?? 0);
        
        $product = Product::find($id, false);
        if (!$product || (int)$product['company_id'] !== $companyId) {
            header('Location: /products');
            exit;
        }
        
        $categories = Category::allByCompany($companyId);
        $ingredients = Ingredient::allForCompany($companyId);
        $simpleProducts = Product::simpleProductsForCompany($companyId);
        
        $customization = [
            'enabled' => !empty($product['allow_customize']),
            'groups' => ProductCustomization::loadForAdmin($id),
        ];
        $groups = Product::getComboGroupsWithItems($id);
        $custTemplates = CustomizationTemplate::listWithItemsForCompany($companyId);
        
        return $this->viewMobile('products/form', [
            'company' => $company,
            'product' => $product,
            'categories' => $categories,
            'ingredients' => $ingredients,
            'simpleProducts' => $simpleProducts,
            'customization' => $customization,
            'groups' => $groups,
            'custTemplates' => $custTemplates,
            'pageTitle' => 'Editar Produto',
            'activeNav' => 'products',
            'showBackButton' => true
        ]);
    }

    /**
     * POST /products/{id} - Atualizar produto
     */
    public function update(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        $id = (int)($params['id'] ?? 0);
        
        $product = Product::find($id);
        if (!$product || (int)$product['company_id'] !== $companyId) {
            header('Location: /products');
            exit;
        }
        
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)str_replace(',', '.', $_POST['price'] ?? '0');
        $promoPrice = !empty($_POST['promo_price']) ? (float)str_replace(',', '.', $_POST['promo_price']) : null;
        $promoPercentage = !empty($_POST['promo_percentage']) ? (int)$_POST['promo_percentage'] : null;
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $active = isset($_POST['active']) ? 1 : 0;
        $type = in_array($_POST['type'] ?? '', ['simple', 'combo']) ? $_POST['type'] : 'simple';
        $priceMode = in_array($_POST['price_mode'] ?? '', ['fixed', 'sum']) ? $_POST['price_mode'] : 'fixed';
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        
        if (empty($name) || $price <= 0) {
            $_SESSION['flash_error'] = 'Nome e preço são obrigatórios';
            header("Location: /products/{$id}/edit");
            exit;
        }
        
        // Upload de imagem
        $image = $product['image'];
        if (!empty($_FILES['image']['tmp_name'])) {
            $newImage = $this->handleUpload($_FILES['image'], 'product');
            if ($newImage) {
                $image = $newImage;
            }
        }
        
        // Processar personalização
        $custPayload = $_POST['customization'] ?? [];
        $custData = ProductCustomization::sanitizePayload(is_array($custPayload) ? $custPayload : [], $companyId);
        
        // Processar grupos de combo
        $useGroups = $type === 'combo' && (!empty($_POST['use_groups']) || !empty($_POST['groups']));
        $groupsPayload = $useGroups && isset($_POST['groups']) && is_array($_POST['groups'])
            ? Product::sanitizeComboGroupsPayload($_POST['groups'], $companyId)
            : [];
        
        $data = [
            'category_id' => $categoryId,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'promo_price' => $promoPrice,
            'sku' => $product['sku'] ?? Product::nextSkuForCompany($companyId),
            'image' => $image,
            'active' => $active,
            'sort_order' => $sortOrder,
            'allow_customize' => $type === 'simple' && !empty($custData['enabled']) && !empty($custData['groups']) ? 1 : 0,
            'type' => $type,
            'price_mode' => $priceMode,
        ];
        
        Product::update($id, $data);
        ProductCustomization::save($id, $custData);
        Product::saveComboGroupsAndItems($id, $type === 'combo' ? $groupsPayload : []);
        
        $_SESSION['flash_success'] = 'Produto atualizado!';
        header('Location: /products');
        exit;
    }

    /**
     * POST /products/{id}/toggle - Toggle ativo/inativo
     */
    public function toggle(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $id = (int)($params['id'] ?? 0);
        
        $product = Product::find($id);
        if (!$product || (int)$product['company_id'] !== (int)$company['id']) {
            $this->jsonResponse(['success' => false, 'message' => 'Produto não encontrado']);
            return;
        }
        
        $newStatus = $product['active'] ? 0 : 1;
        
        $pdo = db();
        $stmt = $pdo->prepare("UPDATE products SET active = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $id]);
        
        // Invalidar cache do produto e de combos que o contêm
        require_once __DIR__ . '/../services/ProductCache.php';
        $cache = ProductCache::instance();
        $cache->invalidateProduct($id);
        $cache->invalidateCombosContainingProduct($id);
        
        $this->jsonResponse([
            'success' => true, 
            'active' => $newStatus,
            'message' => $newStatus ? 'Produto ativado' : 'Produto desativado'
        ]);
    }

    /**
     * POST /products/{id}/delete - Excluir produto
     */
    public function destroy(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $id = (int)($params['id'] ?? 0);
        
        $product = Product::find($id);
        if (!$product || (int)$product['company_id'] !== (int)$company['id']) {
            $_SESSION['flash_error'] = 'Produto não encontrado';
            header('Location: /products');
            exit;
        }
        
        $pdo = db();
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['flash_success'] = 'Produto excluído!';
        header('Location: /products');
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
