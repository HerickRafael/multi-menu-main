<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

// Modelos específicos do controller
require_once __DIR__ . '/../models/ProductCustomization.php';
require_once __DIR__ . '/../models/CustomizationTemplate.php';


class AdminProductController extends Controller
{
    /**
     * Normaliza o preço promocional garantindo que só valores válidos sejam usados.
     */
    private function sanitizePromoPrice($input, float $basePrice, string $priceMode = 'fixed'): ?float
    {
        if ($input === null) {
            return null;
        }

        if (is_array($input)) {
            $input = reset($input);
        }

        $raw = trim((string)$input);

        if ($raw === '') {
            return null;
        }

        $raw = str_replace(' ', '', $raw);

        if (strpos($raw, ',') !== false && strpos($raw, '.') !== false) {
            $raw = str_replace('.', '', $raw);
        }
        $raw = str_replace(',', '.', $raw);

        // Se estiver no modo 'sum' (somar itens), interpretamos o valor como
        // porcentagem (0-100). Permitir que o admin informe '15' ou '15%'.
        if ($priceMode === 'sum') {
            // remover eventual sinal de porcentagem
            if (substr($raw, -1) === '%') {
                $raw = trim(substr($raw, 0, -1));
            }

            if (!is_numeric($raw)) {
                return null;
            }

            $promo = (float)$raw;
            if ($promo <= 0 || $promo >= 100) {
                return null;
            }

            // armazenaremos a porcentagem bruta (ex: 15 = 15%) no campo promo_price
            return $promo;
        }

        // modo 'fixed' (comportamento antigo): valor absoluto em moeda
        if (!is_numeric($raw)) {
            return null;
        }

        $promo = (float)$raw;

        if ($promo <= 0) {
            return null;
        }

        $price = (float)$basePrice;

        if ($price <= 0 || $promo >= $price) {
            return null;
        }

        return $promo;
    }

    /** Protege rotas e valida empresa/usuário */
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

    /** Lista de produtos */
    public function index($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        $cats  = Category::allByCompany((int)$company['id']);
        $items = Product::listByCompany((int)$company['id'], $_GET['q'] ?? null, false, false);

        return $this->view('admin/products/index', compact('company', 'cats', 'items', 'error'));
    }

    /** Form de criação */
    public function create($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $cats = Category::allByCompany((int)$company['id']);

        $p = [
          'id'          => null,
          'name'        => '',
          'description' => '',
          'price'       => 0.0,
          'promo_price' => null,
          'sku'         => Product::nextSkuForCompany((int)$company['id']),
          'sort_order'  => 0,
          'active'      => 1,
          'category_id' => null,
          'image'       => null,
        ];

        $customization = ['enabled' => false, 'groups' => []];
        $ingredients = Ingredient::allForCompany((int)$company['id']);
        $simpleProducts = Product::simpleProductsForCompany((int)$company['id']);
        $groups = [];
        $custTemplates = CustomizationTemplate::listWithItemsForCompany((int)$company['id']);

        return $this->view('admin/products/form', compact('company', 'cats', 'p', 'customization', 'ingredients', 'simpleProducts', 'groups', 'custTemplates'));
    }

    /**
     * Faz upload de imagem e retorna o caminho relativo (ex.: "uploads/arquivo.jpg").
     * Em caso de erro, preenche $error (e retorna null).
     */
    private function handleUpload(?array $file, ?string &$error = null): ?string
    {
        $error = null;

        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'Erro no upload (código ' . $file['error'] . ')';
            error_log($error . ' para ' . ($file['tmp_name'] ?? 'temp'));

            return null;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
            $error = 'Formato de arquivo inválido. Use JPG, PNG ou WEBP.';

            return null;
        }

        $name = 'p_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $dest = __DIR__ . '/../../public/uploads/' . $name;
        $dir  = dirname($dest);

        if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
            $error = 'Falha ao criar diretório de upload';
            error_log($error . ': ' . $dir);

            return null;
        }

        if (!is_writable($dir)) {
            $error = 'Diretório de upload não gravável';
            error_log($error . ': ' . $dir);

            return null;
        }

        if (!is_uploaded_file($file['tmp_name'] ?? '')) {
            $error = 'Arquivo temporário inexistente';
            error_log($error . ': ' . ($file['tmp_name'] ?? ''));

            return null;
        }

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $error = 'Falha ao salvar o arquivo enviado.';
            $lastError = error_get_last();
            error_log("move_uploaded_file falhou: {$file['tmp_name']} -> {$dest} - " . ($lastError['message'] ?? 'sem detalhes'));

            return null;
        }

        return 'uploads/' . $name;
    }

    /** Persistência da criação */
    public function store($params)
    {
        [$u, $company] = $this->guard($params['slug']);

        $imgError = null;
        $img = $this->handleUpload($_FILES['image'] ?? null, $imgError);

        if ($imgError) {
            $_SESSION['flash_error'] = $imgError;
        }

        $custPayload = $_POST['customization'] ?? [];
        $custData    = ProductCustomization::sanitizePayload(is_array($custPayload) ? $custPayload : [], (int)$company['id']);

        $ptype = ($_POST['type'] ?? 'simple') === 'combo' ? 'combo' : 'simple';
        $priceMode = ($_POST['price_mode'] ?? 'fixed') === 'sum' ? 'sum' : 'fixed';

        $useGroups = $ptype === 'combo' && (!empty($_POST['use_groups']) || !empty($_POST['groups']));
        $groupsPayload = $useGroups && isset($_POST['groups']) && is_array($_POST['groups'])
          ? Product::sanitizeComboGroupsPayload($_POST['groups'], (int)$company['id'])
          : [];

    $price = (float)($_POST['price'] ?? 0);
    
    // Determinar qual campo usar para preço promocional baseado no modo
    $promoInput = null;
    if ($priceMode === 'sum' && isset($_POST['promo_percentage'])) {
        $promoInput = $_POST['promo_percentage'];
    } elseif (isset($_POST['promo_price'])) {
        $promoInput = $_POST['promo_price'];
    }
    
    $promo = $this->sanitizePromoPrice($promoInput, $price, $priceMode);

        $data = [
          'company_id'  => (int)$company['id'],
          'category_id' => $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null,
          'name'        => trim($_POST['name'] ?? ''),
          'description' => trim($_POST['description'] ?? ''),
          'price'       => $price,
          'promo_price' => $promo,
          'promo_start_at' => !empty($_POST['promo_start_at']) ? $_POST['promo_start_at'] : null,
          'promo_end_at'   => !empty($_POST['promo_end_at']) ? $_POST['promo_end_at'] : null,
          'sku'         => Product::nextSkuForCompany((int)$company['id']),
          'image'       => $img, // pode ser null
          'active'      => isset($_POST['active']) ? 1 : 0,
          'sort_order'  => (int)($_POST['sort_order'] ?? 0),
          'allow_customize' => $ptype === 'simple' && !empty($custData['enabled']) && !empty($custData['groups']) ? 1 : 0,
          'type'        => $ptype,
          'price_mode'  => $priceMode,
        ];

        $productId = Product::create($data);
        ProductCustomization::save($productId, $custData);
        Product::saveComboGroupsAndItems($productId, $ptype === 'combo' ? $groupsPayload : []);
        
        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/products'));
        exit;
    }

    /**
     * Busca produtos simples via AJAX (autocomplete)
     * Endpoint direto sem necessidade de API/autenticação complexa
     */
    public function simpleProductsSearch($params)
    {
        // Verificação básica de sessão admin
        [$u, $company] = $this->guard($params['slug']);

        $search = trim($_GET['search'] ?? '');
        $limit = min((int)($_GET['limit'] ?? 20), 50);

        // Resposta sempre em JSON
        header('Content-Type: application/json; charset=utf-8');

        try {
            $db = db();
            
            // Query simplificada e rápida
            $query = "
                SELECT 
                    p.id,
                    p.name,
                    p.sku,
                    p.price,
                    p.allow_customize
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE c.company_id = ? 
                AND p.type = 'simple' 
                AND p.active = 1
            ";
            
            $params = [(int)$company['id']];
            
            // Filtro de busca otimizado
            if ($search !== '') {
                $searchTerm = '%' . $search . '%';
                $query .= " AND (
                    p.name LIKE ? OR 
                    p.sku LIKE ? OR 
                    p.id = ?
                )";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                // Busca exata por ID se for numérico
                $params[] = is_numeric($search) ? (int)$search : 0;
            }
            
            $query .= " ORDER BY p.name ASC LIMIT " . (int)$limit;

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Processamento mínimo para performance
            $result = [];
            foreach ($products as $product) {
                $result[] = [
                    'id' => (int)$product['id'],
                    'name' => $product['name'],
                    'sku' => $product['sku'] ?? '',
                    'price' => (float)$product['price'],
                    'price_formatted' => 'R$ ' . number_format((float)$product['price'], 2, ',', '.'),
                    'allow_customize' => (bool)$product['allow_customize'],
                    'ingredient_count' => 0 // Simplificado por ora
                ];
            }

            echo json_encode([
                'success' => true,
                'products' => $result,
                'total' => count($result)
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erro na busca: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /** Form de edição */
    public function edit($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $cats = Category::allByCompany((int)$company['id']);
        $p = Product::find((int)$params['id'], false);  // false = não aplicar taxa embutida no admin

        if (!$p) {
            echo 'Produto não encontrado.';
            exit;
        }

        $customization = [
          'enabled' => !empty($p['allow_customize']),
          'groups'  => ProductCustomization::loadForAdmin((int)$p['id']),
        ];
        $ingredients = Ingredient::allForCompany((int)$company['id']);
        $simpleProducts = Product::simpleProductsForCompany((int)$company['id']);
        $groups = Product::getComboGroupsWithItems((int)$p['id']);
        $custTemplates = CustomizationTemplate::listWithItemsForCompany((int)$company['id']);

        return $this->view('admin/products/form', compact('company', 'cats', 'p', 'customization', 'ingredients', 'simpleProducts', 'groups', 'custTemplates'));
    }

    /** Persistência da edição */
    public function update($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $p = Product::find((int)$params['id'], false);

        if (!$p) {
            echo 'Produto não encontrado.';
            exit;
        }

        $imgError = null;
        $uploaded = $this->handleUpload($_FILES['image'] ?? null, $imgError);
        $img = $uploaded ?: ($p['image'] ?? null);

        if ($imgError) {
            $_SESSION['flash_error'] = $imgError;
        }

        $custPayload = $_POST['customization'] ?? [];
        $custData    = ProductCustomization::sanitizePayload(is_array($custPayload) ? $custPayload : [], (int)$company['id']);

        $ptype = ($_POST['type'] ?? 'simple') === 'combo' ? 'combo' : 'simple';
        $priceMode = ($_POST['price_mode'] ?? 'fixed') === 'sum' ? 'sum' : 'fixed';

        $useGroups = $ptype === 'combo' && (!empty($_POST['use_groups']) || !empty($_POST['groups']));
        $groupsPayload = $useGroups && isset($_POST['groups']) && is_array($_POST['groups'])
          ? Product::sanitizeComboGroupsPayload($_POST['groups'], (int)$company['id'])
          : [];

    $price = (float)($_POST['price'] ?? 0);
    
    // Determinar qual campo usar para preço promocional baseado no modo
    $promoInput = null;
    if ($priceMode === 'sum' && isset($_POST['promo_percentage'])) {
        $promoInput = $_POST['promo_percentage'];
    } elseif (isset($_POST['promo_price'])) {
        $promoInput = $_POST['promo_price'];
    }
    
    $promo = $this->sanitizePromoPrice($promoInput, $price, $priceMode);

        $data = [
          'category_id' => $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null,
          'name'        => trim($_POST['name'] ?? ''),
          'description' => trim($_POST['description'] ?? ''),
          'price'       => $price,
          'promo_price' => $promo,
          'promo_start_at' => !empty($_POST['promo_start_at']) ? $_POST['promo_start_at'] : null,
          'promo_end_at'   => !empty($_POST['promo_end_at']) ? $_POST['promo_end_at'] : null,
          'sku'         => isset($p['sku']) && $p['sku'] !== '' ? $p['sku'] : Product::nextSkuForCompany((int)$company['id']),
          'image'       => $img,
          'active'      => isset($_POST['active']) ? 1 : 0,
          'sort_order'  => (int)($_POST['sort_order'] ?? 0),
          'allow_customize' => $ptype === 'simple' && !empty($custData['enabled']) && !empty($custData['groups']) ? 1 : 0,
          'type'        => $ptype,
          'price_mode'  => $priceMode,
        ];

        $productId = (int)$params['id'];
        Product::update($productId, $data);
        ProductCustomization::save($productId, $custData);
        Product::saveComboGroupsAndItems($productId, $ptype === 'combo' ? $groupsPayload : []);
        
        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/products'));
        exit;
    }

    /** Exclusão */
    public function destroy($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        Product::delete((int)$params['id']);
        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/products'));
        exit;
    }
}
