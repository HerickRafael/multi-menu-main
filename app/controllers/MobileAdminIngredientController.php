<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

/**
 * Controller Mobile para Ingredientes
 */
class MobileAdminIngredientController extends Controller
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

        if ($user['role'] !== 'root' && (int)$user['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            echo 'Acesso negado';
            exit;
        }

        return [$user, $company, $slug];
    }

    private function parseDecimal($value): ?float
    {
        if (is_int($value) || is_float($value)) return (float)$value;
        if (!is_string($value)) return null;
        $raw = trim($value);
        if ($raw === '') return null;
        $clean = preg_replace('/[^0-9.,-]/', '', $raw);
        if ($clean === null || $clean === '') return null;
        $lastComma = strrpos($clean, ',');
        $lastDot = strrpos($clean, '.');
        if ($lastComma !== false && $lastDot !== false) {
            $dec = $lastComma > $lastDot ? ',' : '.';
        } elseif ($lastComma !== false) {
            $dec = ',';
        } elseif ($lastDot !== false) {
            $dec = '.';
        } else {
            $dec = null;
        }
        if ($dec !== null) {
            $thousand = $dec === ',' ? '.' : ',';
            $clean = str_replace($thousand, '', $clean);
            $clean = str_replace($dec, '.', $clean);
        }
        return is_numeric($clean) ? (float)$clean : null;
    }

    /**
     * GET /ingredients - Lista de ingredientes
     */
    public function index(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];

        $q = isset($_GET['q']) ? trim($_GET['q']) : null;
        $items = Ingredient::listByCompany($companyId, null, $q !== '' ? $q : null);
        $total = Ingredient::countByCompany($companyId);

        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        $pageTitle = 'Ingredientes';
        $activeNav = 'products';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/ingredients/index.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    /**
     * GET /ingredients/create - Formulário novo
     */
    public function create(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $ingredient = null;
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        $pageTitle = 'Novo Ingrediente';
        $activeNav = 'products';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/ingredients/form.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    /**
     * POST /ingredients - Salvar novo
     */
    public function store(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];

        $name = trim($_POST['name'] ?? '');
        $internalName = trim($_POST['internal_name'] ?? '') ?: null;
        $cost = $this->parseDecimal($_POST['cost'] ?? '');
        $salePrice = $this->parseDecimal($_POST['sale_price'] ?? '');
        $unit = trim($_POST['unit'] ?? '');
        $unitValue = $this->parseDecimal($_POST['unit_value'] ?? '');

        if ($name === '') { $_SESSION['flash_error'] = 'Informe o nome'; header('Location: /ingredients/create'); exit; }
        if ($cost === null) { $_SESSION['flash_error'] = 'Informe o custo'; header('Location: /ingredients/create'); exit; }
        if ($salePrice === null) { $_SESSION['flash_error'] = 'Informe o preço de venda'; header('Location: /ingredients/create'); exit; }
        if ($unit === '') { $_SESSION['flash_error'] = 'Informe a unidade'; header('Location: /ingredients/create'); exit; }
        if ($unitValue === null || $unitValue <= 0) { $_SESSION['flash_error'] = 'Informe o valor da unidade'; header('Location: /ingredients/create'); exit; }

        if (Ingredient::existsByName($companyId, $name)) {
            $_SESSION['flash_error'] = 'Já existe um ingrediente com este nome';
            header('Location: /ingredients/create');
            exit;
        }

        // Upload de imagem
        $imagePath = null;
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                $fileName = 'ingredient_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $dest = __DIR__ . '/../../public/uploads/' . $fileName;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $imagePath = 'uploads/' . $fileName;
                }
            }
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

        $_SESSION['flash_success'] = 'Ingrediente criado!';
        header('Location: /ingredients');
        exit;
    }

    /**
     * GET /ingredients/{id}/edit
     */
    public function edit(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $id = (int)($params['id'] ?? 0);

        $ingredient = Ingredient::findForCompany((int)$company['id'], $id);
        if (!$ingredient) {
            header('Location: /ingredients');
            exit;
        }

        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        $pageTitle = 'Editar Ingrediente';
        $activeNav = 'products';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/ingredients/form.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    /**
     * POST /ingredients/{id}
     */
    public function update(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        $id = (int)($params['id'] ?? 0);

        $existing = Ingredient::findForCompany($companyId, $id);
        if (!$existing) { header('Location: /ingredients'); exit; }

        $name = trim($_POST['name'] ?? '');
        $internalName = trim($_POST['internal_name'] ?? '') ?: null;
        $cost = $this->parseDecimal($_POST['cost'] ?? '');
        $salePrice = $this->parseDecimal($_POST['sale_price'] ?? '');
        $unit = trim($_POST['unit'] ?? '');
        $unitValue = $this->parseDecimal($_POST['unit_value'] ?? '');

        if ($name === '') { $_SESSION['flash_error'] = 'Informe o nome'; header("Location: /ingredients/$id/edit"); exit; }
        if ($cost === null) { $_SESSION['flash_error'] = 'Informe o custo'; header("Location: /ingredients/$id/edit"); exit; }
        if ($salePrice === null) { $_SESSION['flash_error'] = 'Informe o preço de venda'; header("Location: /ingredients/$id/edit"); exit; }
        if ($unit === '') { $_SESSION['flash_error'] = 'Informe a unidade'; header("Location: /ingredients/$id/edit"); exit; }
        if ($unitValue === null || $unitValue <= 0) { $_SESSION['flash_error'] = 'Informe o valor da unidade'; header("Location: /ingredients/$id/edit"); exit; }

        if (Ingredient::existsByName($companyId, $name, $id)) {
            $_SESSION['flash_error'] = 'Já existe ingrediente com este nome';
            header("Location: /ingredients/$id/edit");
            exit;
        }

        // Upload
        $imagePath = $existing['image_path'] ?? null;
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                $fileName = 'ingredient_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $dest = __DIR__ . '/../../public/uploads/' . $fileName;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $imagePath = 'uploads/' . $fileName;
                }
            }
        }

        Ingredient::update($id, [
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

        $_SESSION['flash_success'] = 'Ingrediente atualizado!';
        header('Location: /ingredients');
        exit;
    }

    /**
     * POST /ingredients/{id}/toggle - Toggle ativo/inativo (AJAX)
     */
    public function toggle(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $id = (int)($params['id'] ?? 0);
        $companyId = (int)$company['id'];

        $newState = Ingredient::toggleActive($companyId, $id);
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

    /**
     * POST /ingredients/{id}/delete
     */
    public function delete(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $id = (int)($params['id'] ?? 0);

        $existing = Ingredient::findForCompany((int)$company['id'], $id);
        if ($existing) {
            Ingredient::delete((int)$company['id'], $id);
            $_SESSION['flash_success'] = 'Ingrediente excluído!';
        }

        header('Location: /ingredients');
        exit;
    }
}
