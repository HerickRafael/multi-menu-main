<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

// Modelo específico
require_once __DIR__ . '/../models/CrossSellGroup.php';

class CrossSellGroupController extends Controller
{
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

    /**
     * Lista todos os grupos de cross-sell
     */
    public function index($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $companyId = (int)$company['id'];
        
        $groups = CrossSellGroup::getByCompany($companyId);
        $categories = Category::allByCompany($companyId);
        
        // Enriquecer grupos com nomes das categorias recomendadas
        $categoriesMap = array_column($categories, 'name', 'id');
        
        foreach ($groups as &$group) {
            foreach ($group['recommendations'] as &$rec) {
                $rec['category_name'] = $categoriesMap[$rec['category_id']] ?? 'Categoria';
            }
        }
        
        return $this->view('admin/cross-sell-groups/index', [
            'company' => $company,
            'u' => $u,
            'groups' => $groups,
            'categories' => $categories
        ]);
    }

    /**
     * Cria ou atualiza um grupo de cross-sell
     */
    public function save($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $companyId = (int)$company['id'];
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . base_url('admin/' . rawurlencode($params['slug']) . '/cross-sell-groups'));
            exit;
        }

        $triggerCategoryId = (int)($_POST['trigger_category_id'] ?? 0);
        $recommendedCategories = $_POST['recommended_categories'] ?? [];

        if ($triggerCategoryId <= 0) {
            header('Location: ' . base_url('admin/' . rawurlencode($params['slug']) . '/cross-sell-groups?error=Selecione a categoria disparadora'));
            exit;
        }

        // Processar recomendações
        $recommendations = [];
        
        foreach ($recommendedCategories as $categoryId => $data) {
            if (empty($data['selected'])) {
                continue;
            }

            $categoryId = (int)$categoryId;
            $sectionTitle = trim($data['title'] ?? '');

            if ($categoryId <= 0) {
                continue;
            }

            if (empty($sectionTitle)) {
                header('Location: ' . base_url('admin/' . rawurlencode($params['slug']) . '/cross-sell-groups?error=Todas as categorias selecionadas precisam de título'));
                exit;
            }

            // Não pode recomendar a si mesma
            if ($triggerCategoryId === $categoryId) {
                continue;
            }

            $recommendations[] = [
                'category_id' => $categoryId,
                'section_title' => $sectionTitle
            ];
        }

        if (empty($recommendations)) {
            header('Location: ' . base_url('admin/' . rawurlencode($params['slug']) . '/cross-sell-groups?error=Selecione pelo menos uma categoria para recomendar'));
            exit;
        }

        try {
            CrossSellGroup::createOrUpdate($companyId, $triggerCategoryId, $recommendations);
            header('Location: ' . base_url('admin/' . rawurlencode($params['slug']) . '/cross-sell-groups?success=Grupo salvo com sucesso!'));
        } catch (Exception $e) {
            header('Location: ' . base_url('admin/' . rawurlencode($params['slug']) . '/cross-sell-groups?error=' . urlencode($e->getMessage())));
        }
        exit;
    }

    /**
     * Abre modal para editar grupo existente
     */
    public function edit($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $id = (int)($params['id'] ?? 0);
        
        $group = CrossSellGroup::findById($id);
        
        if (!$group) {
            header('Location: ' . base_url('admin/' . rawurlencode($params['slug']) . '/cross-sell-groups?error=Grupo não encontrado'));
            exit;
        }

        // Retornar JSON para o JavaScript
        header('Content-Type: application/json');
        echo json_encode($group);
        exit;
    }

    /**
     * Toggle ativo/inativo
     */
    public function toggle($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $id = (int)($params['id'] ?? 0);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Método não permitido']);
            exit;
        }

        try {
            CrossSellGroup::toggleActive($id);
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Deleta um grupo
     */
    public function delete($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $id = (int)($params['id'] ?? 0);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Método não permitido']);
            exit;
        }

        try {
            CrossSellGroup::delete($id);
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
}
