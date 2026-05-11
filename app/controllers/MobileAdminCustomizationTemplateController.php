<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/CustomizationTemplate.php';

/**
 * Controller Mobile para Templates de Personalização
 */
class MobileAdminCustomizationTemplateController extends Controller
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

    /**
     * GET /customization-templates - Lista
     */
    public function index(array $params = []): void
    {
        [$user, $company, $slug] = $this->guard();

        $templates = CustomizationTemplate::allWithUsageCount((int)$company['id'], false);

        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        $pageTitle = 'Grupos de Personalização';
        $activeNav = 'products';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/customization-templates/index.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    /**
     * GET /customization-templates/create - Formulário novo
     */
    public function create(array $params = []): void
    {
        [$user, $company, $slug] = $this->guard();

        $ingredients = Ingredient::allForCompany((int)$company['id']);
        $template = null;
        $productsUsing = [];

        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        $pageTitle = 'Novo Grupo';
        $activeNav = 'products';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/customization-templates/form.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    /**
     * GET /customization-templates/{id}/edit - Formulário edição
     */
    public function edit(array $params = []): void
    {
        [$user, $company, $slug] = $this->guard();

        $id = (int)($params['id'] ?? 0);
        $template = CustomizationTemplate::findWithItems($id);

        if (!$template || (int)$template['company_id'] !== (int)$company['id']) {
            header('Location: /customization-templates');
            exit;
        }

        $ingredients = Ingredient::allForCompany((int)$company['id']);
        $productsUsing = CustomizationTemplate::getProductsUsingTemplate($id);

        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        $pageTitle = 'Editar Grupo';
        $activeNav = 'products';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/customization-templates/form.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    /**
     * POST /customization-templates ou /customization-templates/{id} - Salvar
     */
    public function store(array $params = []): void
    {
        [$user, $company, $slug] = $this->guard();

        $id = isset($params['id']) ? (int)$params['id'] : 0;
        $isEdit = $id > 0;

        if ($isEdit) {
            $existing = CustomizationTemplate::find($id);
            if (!$existing || (int)$existing['company_id'] !== (int)$company['id']) {
                $_SESSION['flash_error'] = 'Template não encontrado.';
                header('Location: /customization-templates');
                exit;
            }
        }

        $name = trim($_POST['name'] ?? '');
        $typeRaw = $_POST['type'] ?? 'extra';
        $type = ($typeRaw === 'choice') ? 'single' : $typeRaw;
        $minQty = (int)($_POST['min_qty'] ?? 0);
        $maxQty = (int)($_POST['max_qty'] ?? 99);
        $active = isset($_POST['active']) ? 1 : 0;
        $hideDuplicates = isset($_POST['hide_duplicates']) ? 1 : 0;

        if ($name === '') {
            $_SESSION['flash_error'] = 'O nome do grupo é obrigatório.';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        // Processar itens
        $items = [];
        if (!empty($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $index => $itemData) {
                $ingredientId = !empty($itemData['ingredient_id']) ? (int)$itemData['ingredient_id'] : null;
                $label = trim($itemData['label'] ?? '');

                if (!$ingredientId && $label === '') {
                    continue;
                }

                if ($ingredientId && $label === '') {
                    $ingredient = Ingredient::find($ingredientId);
                    $label = $ingredient ? $ingredient['name'] : 'Item';
                }

                $items[] = [
                    'ingredient_id' => $ingredientId,
                    'label' => $label,
                    'delta' => (float)($itemData['delta'] ?? 0),
                    'is_default' => !empty($itemData['is_default']) ? 1 : 0,
                    'default_qty' => (int)($itemData['default_qty'] ?? 1),
                    'min_qty' => (int)($itemData['min_qty'] ?? 0),
                    'max_qty' => (int)($itemData['max_qty'] ?? 1),
                    'sort_order' => (int)$index
                ];
            }
        }

        $syncProducts = isset($_POST['sync_products']) && $_POST['sync_products'] === '1';

        try {
            if ($isEdit) {
                CustomizationTemplate::update($id, [
                    'name' => $name,
                    'type' => $type,
                    'min_qty' => $minQty,
                    'max_qty' => $maxQty,
                    'active' => $active,
                    'hide_duplicates' => $hideDuplicates
                ]);
                CustomizationTemplate::saveItems($id, $items);

                $syncCount = 0;
                if ($syncProducts) {
                    $syncCount = CustomizationTemplate::syncToLinkedProducts($id);
                }

                $_SESSION['flash_success'] = $syncCount > 0
                    ? "Grupo atualizado e sincronizado com {$syncCount} produto(s)!"
                    : 'Grupo atualizado com sucesso!';
            } else {
                $id = CustomizationTemplate::create([
                    'company_id' => (int)$company['id'],
                    'name' => $name,
                    'type' => $type,
                    'min_qty' => $minQty,
                    'max_qty' => $maxQty,
                    'active' => $active,
                    'hide_duplicates' => $hideDuplicates
                ]);
                CustomizationTemplate::saveItems($id, $items);
                $_SESSION['flash_success'] = 'Grupo criado com sucesso!';
            }

            header('Location: /customization-templates');
            exit;

        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Erro ao salvar: ' . $e->getMessage();
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    /**
     * POST /customization-templates/{id}/delete
     */
    public function delete(array $params = []): void
    {
        [$user, $company, $slug] = $this->guard();

        $id = (int)($params['id'] ?? 0);
        $template = CustomizationTemplate::find($id);

        if (!$template || (int)$template['company_id'] !== (int)$company['id']) {
            $_SESSION['flash_error'] = 'Template não encontrado.';
            header('Location: /customization-templates');
            exit;
        }

        $count = CustomizationTemplate::countProductsUsingTemplate($id);
        if ($count > 0) {
            $_SESSION['flash_error'] = "Este grupo está sendo usado em {$count} produto(s). Remova primeiro dos produtos.";
            header('Location: /customization-templates');
            exit;
        }

        try {
            CustomizationTemplate::delete($id);
            $_SESSION['flash_success'] = 'Grupo excluído com sucesso!';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Erro ao excluir: ' . $e->getMessage();
        }

        header('Location: /customization-templates');
        exit;
    }

    /**
     * POST /customization-templates/{id}/toggle
     */
    public function toggle(array $params = []): void
    {
        [$user, $company, $slug] = $this->guard();

        $id = (int)($params['id'] ?? 0);

        $template = CustomizationTemplate::find($id);
        if (!$template || (int)$template['company_id'] !== (int)$company['id']) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Template não encontrado']);
            exit;
        }

        try {
            CustomizationTemplate::toggleActive($id);
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * GET /customization-templates/api/list - API para modal de copiar
     */
    public function apiList(array $params = []): void
    {
        [$user, $company, $slug] = $this->guard();

        header('Content-Type: application/json');

        $search = trim($_GET['search'] ?? '');
        $templates = $search
            ? CustomizationTemplate::search((int)$company['id'], $search)
            : CustomizationTemplate::allWithUsageCount((int)$company['id'], true);

        foreach ($templates as &$tpl) {
            $tpl['items_count'] = count(CustomizationTemplate::getItems((int)$tpl['id']));
        }

        echo json_encode(['success' => true, 'templates' => $templates]);
    }

    /**
     * POST /customization-templates/api/copy-to-product
     */
    public function apiCopyToProduct(array $params = []): void
    {
        [$user, $company, $slug] = $this->guard();

        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $templateIds = $input['template_ids'] ?? [];
        $productId = (int)($input['product_id'] ?? 0);

        if (empty($templateIds) || !$productId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
            return;
        }

        $createdGroups = [];

        try {
            foreach ($templateIds as $templateId) {
                $template = CustomizationTemplate::find((int)$templateId);
                if ($template && (int)$template['company_id'] === (int)$company['id']) {
                    $groupId = CustomizationTemplate::copyToProduct((int)$templateId, $productId);
                    $createdGroups[] = [
                        'template_id' => $templateId,
                        'group_id' => $groupId,
                        'name' => $template['name']
                    ];
                }
            }

            echo json_encode([
                'success' => true,
                'created_groups' => $createdGroups,
                'message' => count($createdGroups) . ' grupo(s) adicionado(s) com sucesso!'
            ]);

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * GET /customization-templates/api/{id} — Detalhes de um template (JSON)
     */
    public function apiGet(array $params = []): void
    {
        [$user, $company, $slug] = $this->guard();

        header('Content-Type: application/json');

        $id = (int)($params['id'] ?? 0);
        $template = CustomizationTemplate::findWithItems($id);

        if (!$template || (int)$template['company_id'] !== (int)$company['id']) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Template não encontrado']);
            return;
        }

        echo json_encode(['success' => true, 'template' => $template]);
    }

    /**
     * POST /customization-templates/api/create-from-group — Criar template de um grupo existente
     */
    public function apiCreateFromGroup(array $params = []): void
    {
        [$user, $company, $slug] = $this->guard();

        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $groupId = (int)($input['group_id'] ?? 0);
        $name = trim($input['name'] ?? '');

        if (!$groupId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID do grupo é obrigatório']);
            return;
        }

        try {
            $templateId = CustomizationTemplate::createFromProductGroup($groupId, (int)$company['id'], $name ?: null);
            $template = CustomizationTemplate::find($templateId);

            echo json_encode([
                'success' => true,
                'template_id' => $templateId,
                'template' => $template,
                'message' => 'Template criado com sucesso!'
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
