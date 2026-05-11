<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/CrossSellGroup.php';

/**
 * Controller Mobile para Cross-Sell Groups
 */
class MobileAdminCrossSellController extends Controller
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
     * GET /cross-sell - Lista de grupos
     */
    public function index(array $params = []): void
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];

        $groups = CrossSellGroup::getByCompany($companyId);
        $categories = Category::allByCompany($companyId);

        $categoriesMap = array_column($categories, 'name', 'id');
        foreach ($groups as &$group) {
            foreach ($group['recommendations'] as &$rec) {
                $rec['category_name'] = $categoriesMap[$rec['category_id']] ?? 'Categoria';
            }
        }

        $error = $_SESSION['flash_error'] ?? ($_GET['error'] ?? null);
        $success = $_SESSION['flash_success'] ?? ($_GET['success'] ?? null);
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        $pageTitle = 'Cross-Sell';
        $activeNav = 'products';
        $showBackButton = true;

        ob_start();
        require __DIR__ . '/../views/admin/mobile/cross-sell/index.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/admin/mobile/layout.php';
    }

    /**
     * POST /cross-sell/save - Criar ou atualizar
     */
    public function save(array $params = []): void
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];

        $triggerCategoryId = (int)($_POST['trigger_category_id'] ?? 0);
        $recommendedCategories = $_POST['recommended_categories'] ?? [];

        if ($triggerCategoryId <= 0) {
            $_SESSION['flash_error'] = 'Selecione a categoria disparadora.';
            header('Location: /cross-sell');
            exit;
        }

        $recommendations = [];
        foreach ($recommendedCategories as $categoryId => $data) {
            if (empty($data['selected'])) {
                continue;
            }

            $categoryId = (int)$categoryId;
            $sectionTitle = trim($data['title'] ?? '');

            if ($categoryId <= 0 || $triggerCategoryId === $categoryId) {
                continue;
            }

            if ($sectionTitle === '') {
                $_SESSION['flash_error'] = 'Todas as categorias selecionadas precisam de título.';
                header('Location: /cross-sell');
                exit;
            }

            $recommendations[] = [
                'category_id' => $categoryId,
                'section_title' => $sectionTitle
            ];
        }

        if (empty($recommendations)) {
            $_SESSION['flash_error'] = 'Selecione pelo menos uma categoria para recomendar.';
            header('Location: /cross-sell');
            exit;
        }

        try {
            CrossSellGroup::createOrUpdate($companyId, $triggerCategoryId, $recommendations);
            $_SESSION['flash_success'] = 'Grupo salvo com sucesso!';
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }

        header('Location: /cross-sell');
        exit;
    }

    /**
     * GET /cross-sell/{id}/edit - Retorna JSON do grupo
     */
    public function edit(array $params = []): void
    {
        [$user, $company, $slug] = $this->guard();

        $id = (int)($params['id'] ?? 0);
        $group = CrossSellGroup::findById($id);

        if (!$group) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Grupo não encontrado']);
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode($group);
        exit;
    }

    /**
     * POST /cross-sell/{id}/toggle
     */
    public function toggle(array $params = []): void
    {
        [$user, $company, $slug] = $this->guard();

        $id = (int)($params['id'] ?? 0);

        try {
            CrossSellGroup::toggleActive($id);
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * POST /cross-sell/{id}/delete
     */
    public function delete(array $params = []): void
    {
        [$user, $company, $slug] = $this->guard();

        $id = (int)($params['id'] ?? 0);

        try {
            CrossSellGroup::delete($id);
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
}
