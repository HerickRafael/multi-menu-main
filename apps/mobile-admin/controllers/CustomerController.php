<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/bootstrap.php';
require_once __DIR__ . '/../../../app/modules/auth/MobileAdminGuard.php';
require_once __DIR__ . '/../../../app/services/CustomerService.php';

/**
 * Controller Mobile para Clientes
 * UI/UX otimizado para toque
 */
class MobileAdminCustomerAppController extends Controller
{
    private const ITEMS_PER_PAGE = 20;

    private function guard(): array
    {
        $slug = $_SERVER['MOBILE_SLUG'] ?? 'wollburger';
        [$user, $company] = MobileAdminGuard::requireCompanyAccess('customers.manage');
        
        return [$user, $company, $slug];
    }

    /**
     * GET /customers - Lista de clientes
     */
    public function index(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        
        $search = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pdo = db();
        $result = CustomerService::listForMobile($pdo, $companyId, $search, $page, self::ITEMS_PER_PAGE);
        $customers = $result['customers'];
        $stats = $result['stats'];
        $totalItems = (int)($result['pagination']['totalItems'] ?? 0);
        $totalPages = (int)($result['pagination']['totalPages'] ?? 1);
        
        $success = $_SESSION['flash_success'] ?? null;
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        
        return $this->viewMobile('customers/index', [
            'company' => $company,
            'customers' => $customers,
            'stats' => $stats,
            'search' => $search,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'success' => $success,
            'error' => $error,
            'pageTitle' => 'Clientes',
            'activeNav' => 'customers'
        ]);
    }

    /**
     * GET /customers/{id} - Ver cliente
     */
    public function show(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $id = (int)($params['id'] ?? $_GET['id'] ?? 0);
        
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND company_id = ?");
        $stmt->execute([$id, (int)$company['id']]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            header('Location: /customers');
            exit;
        }
        
        // Buscar pedidos do cliente
        $ordersStmt = $pdo->prepare("
            SELECT * FROM orders 
            WHERE company_id = ? 
            AND (customer_phone = ? OR customer_phone = ?)
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $ordersStmt->execute([(int)$company['id'], $customer['whatsapp'], $customer['whatsapp_e164'] ?? '']);
        $orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Endereços
        $addrStmt = $pdo->prepare("SELECT * FROM customer_addresses WHERE customer_id = ? ORDER BY is_default DESC");
        $addrStmt->execute([$id]);
        $addresses = $addrStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $this->viewMobile('customers/show', [
            'company' => $company,
            'customer' => $customer,
            'orders' => $orders,
            'addresses' => $addresses,
            'pageTitle' => $customer['name'] ?: 'Cliente',
            'activeNav' => 'customers',
            'showBackButton' => true
        ]);
    }

    /**
     * GET /customers/create - Form criar
     */
    public function create(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        return $this->viewMobile('customers/form', [
            'company' => $company,
            'customer' => null,
            'pageTitle' => 'Novo Cliente',
            'activeNav' => 'customers',
            'showBackButton' => true
        ]);
    }

    /**
     * POST /customers - Criar cliente
     */
    public function store(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $result = CustomerService::save(db(), (int)$company['id'], $_POST);
        if (!$result['success']) {
            $_SESSION['flash_error'] = implode(' ', $result['errors'] ?? ['Erro ao salvar cliente']);
            header('Location: /customers/create');
            exit;
        }
        
        $_SESSION['flash_success'] = 'Cliente cadastrado com sucesso!';
        header('Location: /customers');
        exit;
    }

    /**
     * GET /customers/{id}/edit - Form editar
     */
    public function edit(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $id = (int)($params['id'] ?? 0);
        
        $customer = CustomerService::findForCompany(db(), (int)$company['id'], $id);
        
        if (!$customer) {
            header('Location: /customers');
            exit;
        }
        
        return $this->viewMobile('customers/form', [
            'company' => $company,
            'customer' => $customer,
            'pageTitle' => 'Editar Cliente',
            'activeNav' => 'customers',
            'showBackButton' => true
        ]);
    }

    /**
     * POST /customers/{id} - Atualizar cliente
     */
    public function update(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $id = (int)($params['id'] ?? 0);
        
        $customer = CustomerService::findForCompany(db(), (int)$company['id'], $id);
        
        if (!$customer) {
            header('Location: /customers');
            exit;
        }
        
        $result = CustomerService::save(db(), (int)$company['id'], $_POST, $id);
        if (!$result['success']) {
            $_SESSION['flash_error'] = implode(' ', $result['errors'] ?? ['Erro ao atualizar cliente']);
            header("Location: /customers/{$id}/edit");
            exit;
        }
        
        $_SESSION['flash_success'] = 'Cliente atualizado!';
        header('Location: /customers');
        exit;
    }

    /**
     * POST /customers/{id}/delete - Excluir cliente
     */
    public function destroy(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $id = (int)($params['id'] ?? 0);
        
        $result = CustomerService::delete(db(), (int)$company['id'], $id);
        if (!$result['success']) {
            $_SESSION['flash_error'] = $result['message'] ?? 'Falha ao excluir cliente';
            header('Location: /customers');
            exit;
        }
        
        $_SESSION['flash_success'] = 'Cliente excluído!';
        header('Location: /customers');
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
