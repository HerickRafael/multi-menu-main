<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

class AdminIngredientController extends Controller
{
    private function parseDecimal($value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float)$value;
        }

        if (!is_string($value)) {
            return null;
        }

        $raw = trim($value);

        if ($raw === '') {
            return null;
        }

        $clean = preg_replace('/[^0-9.,-]/', '', $raw);

        if ($clean === null || $clean === '') {
            return null;
        }

        $isNegative = strncmp($clean, '-', 1) === 0;

        if ($isNegative) {
            $clean = substr($clean, 1);
        }

        // Remove any stray minus signs that might have appeared after the first one
        $clean = str_replace('-', '', $clean);

        if ($clean === '') {
            return null;
        }

        $lastComma = strrpos($clean, ',');
        $lastDot = strrpos($clean, '.');

        if ($lastComma !== false && $lastDot !== false) {
            $decimalSeparator = $lastComma > $lastDot ? ',' : '.';
        } elseif ($lastComma !== false) {
            $decimalSeparator = ',';
        } elseif ($lastDot !== false) {
            $decimalSeparator = '.';
        } else {
            $decimalSeparator = null;
        }

        if ($decimalSeparator !== null) {
            $thousandSeparator = $decimalSeparator === ',' ? '.' : ',';
            $clean = str_replace($thousandSeparator, '', $clean);
            $clean = str_replace($decimalSeparator, '.', $clean);
        } else {
            $clean = str_replace([',', '.'], '', $clean);
        }

        if ($clean === '' || !is_numeric($clean)) {
            return null;
        }

        $number = (float)$clean;

        return $isNegative ? -$number : $number;
    }

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

    private function consumeFlash(string $key)
    {
        $value = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);

        return $value;
    }

    public function index($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $companyId = (int)$company['id'];

        $productId = isset($_GET['product_id']) && $_GET['product_id'] !== '' ? (int)$_GET['product_id'] : null;
        $q = isset($_GET['q']) ? trim($_GET['q']) : null;

        $items = Ingredient::listByCompany($companyId, $productId, $q !== '' ? $q : null);
        $products = Product::allForCompany($companyId);
        $error = $this->consumeFlash('flash_error');

        return $this->view('admin/ingredients/index', compact('company', 'items', 'products', 'error', 'productId', 'q'));
    }

    public function create($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $companyId = (int)$company['id'];

        $error = $this->consumeFlash('flash_error');
        $old = $this->consumeFlash('flash_old_ingredient');

        $ingredient = [
          'id' => null,
          'name' => $old['name'] ?? '',
          'internal_name' => $old['internal_name'] ?? '',
          'cost' => $old['cost'] ?? '',
          'sale_price' => $old['sale_price'] ?? '',
          'unit' => $old['unit'] ?? '',
          'unit_value' => $old['unit_value'] ?? '',
          'image_path' => null,
        ];

        return $this->view('admin/ingredients/form', compact('company', 'ingredient', 'error'));
    }

    public function store($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $companyId = (int)$company['id'];

        $name = trim($_POST['name'] ?? '');
        $internalName = trim($_POST['internal_name'] ?? '');
        $internalName = $internalName !== '' ? $internalName : null;
        $costRaw = $_POST['cost'] ?? '';
        $salePriceRaw = $_POST['sale_price'] ?? '';
        $unitSelect = trim((string)($_POST['unit_select'] ?? ''));
        $unitCustom = trim((string)($_POST['unit_custom'] ?? ''));
        $unit = $unitSelect === 'custom' ? $unitCustom : $unitSelect;

        if ($unit === '' && $unitCustom !== '') {
            $unit = $unitCustom;
        }
        $unitValueRaw = $_POST['unit_value'] ?? '';

        $cost = $this->parseDecimal($costRaw);
        $salePrice = $this->parseDecimal($salePriceRaw);
        $unitValue = $this->parseDecimal($unitValueRaw);

        [$imagePath, $uploadError] = $this->handleUpload($_FILES['image'] ?? null);

        if ($name === '') {
            $_SESSION['flash_error'] = 'Informe o nome do ingrediente.';
            $_SESSION['flash_old_ingredient'] = [
              'name' => $name,
              'internal_name' => $internalName,
              'cost' => $costRaw,
              'sale_price' => $salePriceRaw,
              'unit' => $unit,
              'unit_value' => $unitValueRaw,
            ];
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/create'));
            exit;
        }

        if ($cost === null) {
            $_SESSION['flash_error'] = 'Informe o custo do ingrediente.';
            $_SESSION['flash_old_ingredient'] = [
              'name' => $name,
              'internal_name' => $internalName,
              'cost' => $costRaw,
              'sale_price' => $salePriceRaw,
              'unit' => $unit,
              'unit_value' => $unitValueRaw,
            ];
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/create'));
            exit;
        }

        if ($salePrice === null) {
            $_SESSION['flash_error'] = 'Informe o valor de venda do ingrediente.';
            $_SESSION['flash_old_ingredient'] = [
              'name' => $name,
              'internal_name' => $internalName,
              'cost' => $costRaw,
              'sale_price' => $salePriceRaw,
              'unit' => $unit,
              'unit_value' => $unitValueRaw,
            ];
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/create'));
            exit;
        }

        if ($unit === '') {
            $_SESSION['flash_error'] = 'Informe a unidade de medida.';
            $_SESSION['flash_old_ingredient'] = [
              'name' => $name,
              'internal_name' => $internalName,
              'cost' => $costRaw,
              'sale_price' => $salePriceRaw,
              'unit' => $unit,
              'unit_value' => $unitValueRaw,
            ];
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/create'));
            exit;
        }

        if ($unitValue === null || $unitValue <= 0) {
            $_SESSION['flash_error'] = 'Informe o valor da unidade de medida.';
            $_SESSION['flash_old_ingredient'] = [
              'name' => $name,
              'internal_name' => $internalName,
              'cost' => $costRaw,
              'sale_price' => $salePriceRaw,
              'unit' => $unit,
              'unit_value' => $unitValueRaw,
            ];
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/create'));
            exit;
        }

        // Mantido do branch: checagem de duplicidade por nome
        if (Ingredient::existsByName($companyId, $name)) {
            $_SESSION['flash_error'] = 'Já existe um ingrediente com este nome.';
            $_SESSION['flash_old_ingredient'] = [
              'name' => $name,
              'internal_name' => $internalName,
              'cost' => $costRaw,
              'sale_price' => $salePriceRaw,
              'unit' => $unit,
              'unit_value' => $unitValueRaw,
            ];
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/create'));
            exit;
        }

        if ($uploadError) {
            $_SESSION['flash_error'] = $uploadError;
            $_SESSION['flash_old_ingredient'] = [
              'name' => $name,
              'internal_name' => $internalName,
              'cost' => $costRaw,
              'sale_price' => $salePriceRaw,
              'unit' => $unit,
              'unit_value' => $unitValueRaw,
            ];
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/create'));
            exit;
        }

        Ingredient::create([
          'company_id' => $companyId,
          'name' => $name,
          'internal_name' => $internalName,
          'cost' => $cost,
          'sale_price' => $salePrice,
          'unit' => $unit,
          'unit_value' => $unitValue,
          'min_qty' => 0,
          'max_qty' => 1,
          'image_path' => $imagePath,
        ]);

        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients'));
        exit;
    }

    public function edit($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $companyId = (int)$company['id'];

        $ingredient = Ingredient::findForCompany($companyId, (int)$params['id']);

        if (!$ingredient) {
            echo 'Ingrediente não encontrado.';
            exit;
        }

        $error = $this->consumeFlash('flash_error');
        $old = $this->consumeFlash('flash_old_ingredient');

        if ($old) {
            $ingredient['name'] = $old['name'];
            $ingredient['internal_name'] = $old['internal_name'] ?? null;
            $ingredient['cost'] = $old['cost'];
            $ingredient['sale_price'] = $old['sale_price'];
            $ingredient['unit'] = $old['unit'];
            $ingredient['unit_value'] = $old['unit_value'];
        }

        return $this->view('admin/ingredients/form', compact('company', 'ingredient', 'error'));
    }

    public function update($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $companyId = (int)$company['id'];
        $ingredientId = (int)$params['id'];

        $ingredient = Ingredient::findForCompany($companyId, $ingredientId);

        if (!$ingredient) {
            echo 'Ingrediente não encontrado.';
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $internalName = trim($_POST['internal_name'] ?? '');
        $internalName = $internalName !== '' ? $internalName : null;
        $costRaw = $_POST['cost'] ?? '';
        $salePriceRaw = $_POST['sale_price'] ?? '';
        $unitSelect = trim((string)($_POST['unit_select'] ?? ''));
        $unitCustom = trim((string)($_POST['unit_custom'] ?? ''));
        $unit = $unitSelect === 'custom' ? $unitCustom : $unitSelect;

        if ($unit === '' && $unitCustom !== '') {
            $unit = $unitCustom;
        }
        $unitValueRaw = $_POST['unit_value'] ?? '';

        $cost = $this->parseDecimal($costRaw);
        $salePrice = $this->parseDecimal($salePriceRaw);
        $unitValue = $this->parseDecimal($unitValueRaw);

        [$imagePath, $uploadError] = $this->handleUpload($_FILES['image'] ?? null, $ingredient['image_path'] ?? null);

        if ($name === '') {
            $_SESSION['flash_error'] = 'Informe o nome do ingrediente.';
            $_SESSION['flash_old_ingredient'] = [
              'name' => $name,
              'internal_name' => $internalName,
              'cost' => $costRaw,
              'sale_price' => $salePriceRaw,
              'unit' => $unit,
              'unit_value' => $unitValueRaw,
            ];
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/' . $ingredientId . '/edit'));
            exit;
        }

        if ($cost === null) {
            $_SESSION['flash_error'] = 'Informe o custo do ingrediente.';
            $_SESSION['flash_old_ingredient'] = [
              'name' => $name,
              'internal_name' => $internalName,
              'cost' => $costRaw,
              'sale_price' => $salePriceRaw,
              'unit' => $unit,
              'unit_value' => $unitValueRaw,
            ];
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/' . $ingredientId . '/edit'));
            exit;
        }

        if ($salePrice === null) {
            $_SESSION['flash_error'] = 'Informe o valor de venda do ingrediente.';
            $_SESSION['flash_old_ingredient'] = [
              'name' => $name,
              'internal_name' => $internalName,
              'cost' => $costRaw,
              'sale_price' => $salePriceRaw,
              'unit' => $unit,
              'unit_value' => $unitValueRaw,
            ];
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/' . $ingredientId . '/edit'));
            exit;
        }

        if ($unit === '') {
            $_SESSION['flash_error'] = 'Informe a unidade de medida.';
            $_SESSION['flash_old_ingredient'] = [
              'name' => $name,
              'internal_name' => $internalName,
              'cost' => $costRaw,
              'sale_price' => $salePriceRaw,
              'unit' => $unit,
              'unit_value' => $unitValueRaw,
            ];
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/' . $ingredientId . '/edit'));
            exit;
        }

        if ($unitValue === null || $unitValue <= 0) {
            $_SESSION['flash_error'] = 'Informe o valor da unidade de medida.';
            $_SESSION['flash_old_ingredient'] = [
              'name' => $name,
              'internal_name' => $internalName,
              'cost' => $costRaw,
              'sale_price' => $salePriceRaw,
              'unit' => $unit,
              'unit_value' => $unitValueRaw,
            ];
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/' . $ingredientId . '/edit'));
            exit;
        }

        // Mantido do branch: checagem de duplicidade por nome (ignorando o próprio ID)
        if (Ingredient::existsByName($companyId, $name, $ingredientId)) {
            $_SESSION['flash_error'] = 'Já existe um ingrediente com este nome.';
            $_SESSION['flash_old_ingredient'] = [
              'name' => $name,
              'internal_name' => $internalName,
              'cost' => $costRaw,
              'sale_price' => $salePriceRaw,
              'unit' => $unit,
              'unit_value' => $unitValueRaw,
            ];
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/' . $ingredientId . '/edit'));
            exit;
        }

        if ($uploadError) {
            $_SESSION['flash_error'] = $uploadError;
            $_SESSION['flash_old_ingredient'] = [
              'name' => $name,
              'internal_name' => $internalName,
              'cost' => $costRaw,
              'sale_price' => $salePriceRaw,
              'unit' => $unit,
              'unit_value' => $unitValueRaw,
            ];
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/' . $ingredientId . '/edit'));
            exit;
        }

        Ingredient::update($ingredientId, [
          'name' => $name,
          'internal_name' => $internalName,
          'cost' => $cost,
          'sale_price' => $salePrice,
          'unit' => $unit,
          'unit_value' => $unitValue,
          'min_qty' => 0,
          'max_qty' => 1,
          'image_path' => $imagePath,
        ]);

        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients'));
        exit;
    }

    public function destroy($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $companyId = (int)$company['id'];
        $ingredientId = (int)$params['id'];

        $ingredient = Ingredient::findForCompany($companyId, $ingredientId);

        if (!$ingredient) {
            echo 'Ingrediente não encontrado.';
            exit;
        }

        Ingredient::delete($companyId, $ingredientId);

        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients'));
        exit;
    }

    public function toggle($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $companyId = (int)$company['id'];
        $ingredientId = (int)$params['id'];

        $newState = Ingredient::toggleActive($companyId, $ingredientId);
        if ($newState === null) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Ingrediente não encontrado']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'active'  => $newState,
            'message' => $newState ? 'Ingrediente ativado' : 'Ingrediente ocultado',
        ]);
    }

    private function handleUpload(?array $file, ?string $current = null): array
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [$current, null];
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return [$current, 'Falha ao enviar a imagem (código ' . $file['error'] . ').'];
        }

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
            return [$current, 'Formato inválido. Use JPG, PNG ou WEBP.'];
        }

        $name = 'ingredient_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $dest = __DIR__ . '/../../public/uploads/' . $name;
        $dir = dirname($dest);

        if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
            return [$current, 'Não foi possível criar o diretório de uploads.'];
        }

        if (!is_writable($dir)) {
            return [$current, 'Diretório de uploads sem permissão de escrita.'];
        }

        if (!is_uploaded_file($file['tmp_name'] ?? '')) {
            return [$current, 'Arquivo inválido.'];
        }

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return [$current, 'Não foi possível salvar o arquivo enviado.'];
        }

        return ['uploads/' . $name, null];
    }
}
