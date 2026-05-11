<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

// Modelo específico
require_once __DIR__ . '/../models/CustomizationTemplate.php';

/**
 * Controller para gerenciar Templates de Personalização
 * Permite criar e gerenciar grupos reutilizáveis de personalização
 */
class AdminCustomizationTemplateController extends Controller
{
    private function guard($slug): array
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

    /**
     * Lista todos os templates de personalização
     */
    public function index(array $params): void
    {
        [$user, $company] = $this->guard($params['slug']);
        
        $templates = CustomizationTemplate::allWithUsageCount((int)$company['id'], false);
        
        $this->view('admin/customization-templates/index', [
            'user' => $user,
            'company' => $company,
            'templates' => $templates,
            'pageTitle' => 'Grupos de Personalização'
        ]);
    }

    /**
     * Formulário de novo template
     */
    public function create(array $params): void
    {
        [$user, $company] = $this->guard($params['slug']);
        
        $ingredients = Ingredient::allForCompany((int)$company['id']);
        
        $this->view('admin/customization-templates/form', [
            'user' => $user,
            'company' => $company,
            'template' => null,
            'ingredients' => $ingredients,
            'pageTitle' => 'Novo Grupo de Personalização'
        ]);
    }

    /**
     * Formulário de edição de template
     */
    public function edit(array $params): void
    {
        [$user, $company] = $this->guard($params['slug']);
        
        $template = CustomizationTemplate::findWithItems((int)$params['id']);
        
        if (!$template || (int)$template['company_id'] !== (int)$company['id']) {
            header('Location: ' . base_url('admin/' . rawurlencode($params['slug']) . '/customization-templates'));
            exit;
        }
        
        $ingredients = Ingredient::allForCompany((int)$company['id']);
        $productsUsing = CustomizationTemplate::getProductsUsingTemplate((int)$params['id']);
        
        $this->view('admin/customization-templates/form', [
            'user' => $user,
            'company' => $company,
            'template' => $template,
            'ingredients' => $ingredients,
            'productsUsing' => $productsUsing,
            'pageTitle' => 'Editar Grupo: ' . $template['name']
        ]);
    }

    /**
     * Salva um template (criação ou atualização)
     */
    public function store(array $params): void
    {
        [$user, $company] = $this->guard($params['slug']);
        
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        $isEdit = $id > 0;
        
        // Validar template existente
        if ($isEdit) {
            $existing = CustomizationTemplate::find($id);
            if (!$existing || (int)$existing['company_id'] !== (int)$company['id']) {
                $_SESSION['flash_error'] = 'Template não encontrado.';
                header('Location: ' . base_url('admin/' . rawurlencode($params['slug']) . '/customization-templates'));
                exit;
            }
        }
        
        $name = trim($_POST['name'] ?? '');
        $typeRaw = $_POST['type'] ?? 'extra';
        // Converter 'choice' para 'single' (valor válido no enum do banco)
        // O formulário usa 'choice' como modo de exibição, mas o banco usa 'single'
        $type = ($typeRaw === 'choice') ? 'single' : $typeRaw;
        $minQty = (int)($_POST['min_qty'] ?? 0);
        $maxQty = (int)($_POST['max_qty'] ?? 99);
        $active = isset($_POST['active']) ? 1 : 0;
        $hideDuplicates = isset($_POST['hide_duplicates']) ? 1 : 0;
        
        if (empty($name)) {
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
                
                // Se não tem ingrediente nem label, pular
                if (!$ingredientId && empty($label)) {
                    continue;
                }
                
                // Se tem ingrediente mas não tem label, buscar nome do ingrediente
                if ($ingredientId && empty($label)) {
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
        
        // Verificar se deve sincronizar com produtos vinculados
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
                
                // Sincronizar com produtos vinculados se solicitado
                $syncCount = 0;
                if ($syncProducts) {
                    $syncCount = CustomizationTemplate::syncToLinkedProducts($id);
                }
                
                if ($syncCount > 0) {
                    $_SESSION['flash_success'] = "Grupo atualizado e sincronizado com {$syncCount} produto(s)!";
                } else {
                    $_SESSION['flash_success'] = 'Grupo atualizado com sucesso!';
                }
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
            
            header('Location: ' . base_url('admin/' . rawurlencode($params['slug']) . '/customization-templates'));
            exit;
            
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Erro ao salvar: ' . $e->getMessage();
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    /**
     * Exclui um template
     */
    public function delete(array $params): void
    {
        [$user, $company] = $this->guard($params['slug']);
        
        $id = (int)$params['id'];
        $template = CustomizationTemplate::find($id);
        
        if (!$template || (int)$template['company_id'] !== (int)$company['id']) {
            $_SESSION['flash_error'] = 'Template não encontrado.';
            header('Location: ' . base_url('admin/' . rawurlencode($params['slug']) . '/customization-templates'));
            exit;
        }
        
        // Verificar se está em uso
        $count = CustomizationTemplate::countProductsUsingTemplate($id);
        if ($count > 0) {
            $_SESSION['flash_error'] = "Este grupo está sendo usado em {$count} produto(s). Remova primeiro dos produtos.";
            header('Location: ' . base_url('admin/' . rawurlencode($params['slug']) . '/customization-templates'));
            exit;
        }
        
        try {
            CustomizationTemplate::delete($id);
            $_SESSION['flash_success'] = 'Grupo excluído com sucesso!';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Erro ao excluir: ' . $e->getMessage();
        }
        
        header('Location: ' . base_url('admin/' . rawurlencode($params['slug']) . '/customization-templates'));
        exit;
    }

    /**
     * Toggle ativo/inativo
     */
    public function toggle(array $params): void
    {
        [$user, $company] = $this->guard($params['slug']);
        
        $id = (int)($params['id'] ?? 0);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Método não permitido']);
            exit;
        }
        
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
        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    // ========== API Endpoints ==========

    /**
     * API: Lista templates para o modal de copiar grupo
     */
    public function apiList(array $params): void
    {
        [$user, $company] = $this->guard($params['slug']);
        
        header('Content-Type: application/json');
        
        $search = trim($_GET['search'] ?? '');
        $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
        
        if ($search) {
            $templates = CustomizationTemplate::search((int)$company['id'], $search);
        } else {
            $templates = CustomizationTemplate::allWithUsageCount((int)$company['id'], true);
        }
        
        // Adicionar informações extras
        foreach ($templates as &$tpl) {
            $tpl['items_count'] = count(CustomizationTemplate::getItems((int)$tpl['id']));
        }
        
        echo json_encode([
            'success' => true,
            'templates' => $templates
        ]);
    }

    /**
     * API: Retorna um template completo com itens
     */
    public function apiGet(array $params): void
    {
        [$user, $company] = $this->guard($params['slug']);
        
        header('Content-Type: application/json');
        
        $id = (int)$params['id'];
        $template = CustomizationTemplate::findWithItems($id);
        
        if (!$template || (int)$template['company_id'] !== (int)$company['id']) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Template não encontrado']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'template' => $template
        ]);
    }

    /**
     * API: Copia template(s) para um produto
     */
    public function apiCopyToProduct(array $params): void
    {
        [$user, $company] = $this->guard($params['slug']);
        
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
            
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * API: Cria template a partir de um grupo existente
     */
    public function apiCreateFromGroup(array $params): void
    {
        [$user, $company] = $this->guard($params['slug']);
        
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
            
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
