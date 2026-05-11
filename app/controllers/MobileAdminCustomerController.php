<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

/**
 * Controller Mobile para Clientes
 * UI/UX otimizado para toque
 */
class MobileAdminCustomerController extends Controller
{
    private const ITEMS_PER_PAGE = 20;

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
     * GET /customers - Lista de clientes
     */
    public function index(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        
        $search = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::ITEMS_PER_PAGE;
        
        $pdo = db();
        
        // Query base
        $whereClause = 'WHERE c.company_id = ?';
        $queryParams = [$companyId];
        
        if ($search !== '') {
            $whereClause .= ' AND (c.name LIKE ? OR c.whatsapp LIKE ?)';
            $queryParams[] = "%{$search}%";
            $queryParams[] = "%{$search}%";
        }
        
        // Total
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM customers c {$whereClause}");
        $countStmt->execute($queryParams);
        $totalItems = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($totalItems / self::ITEMS_PER_PAGE));
        
        // Buscar clientes
        $sql = "
            SELECT 
                c.*,
                COUNT(DISTINCT o.id) as total_orders,
                COALESCE(SUM(CASE WHEN o.status NOT IN ('canceled') THEN o.total ELSE 0 END), 0) as total_spent,
                MAX(o.created_at) as last_order_at
            FROM customers c
            LEFT JOIN orders o ON (o.customer_phone = c.whatsapp OR o.customer_phone = c.whatsapp_e164) 
                AND o.company_id = c.company_id
            {$whereClause}
            GROUP BY c.id
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $queryParams[] = self::ITEMS_PER_PAGE;
        $queryParams[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($queryParams);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Stats
        $statsStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_30d,
                COUNT(CASE WHEN last_login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as active_7d
            FROM customers WHERE company_id = ?
        ");
        $statsStmt->execute([$companyId]);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
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
        $companyId = (int)$company['id'];
        
        $name = trim($_POST['name'] ?? '');
        $whatsapp = preg_replace('/\D/', '', $_POST['whatsapp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($whatsapp)) {
            $_SESSION['flash_error'] = 'WhatsApp é obrigatório';
            header('Location: /customers/create');
            exit;
        }
        
        // Formatar E164
        $whatsappE164 = normalizePhone($whatsapp);
        
        // Verificar duplicado
        $pdo = db();
        $checkStmt = $pdo->prepare("SELECT id FROM customers WHERE company_id = ? AND (whatsapp = ? OR whatsapp_e164 = ?)");
        $checkStmt->execute([$companyId, $whatsapp, $whatsappE164]);
        if ($checkStmt->fetch()) {
            $_SESSION['flash_error'] = 'Cliente já cadastrado com este WhatsApp';
            header('Location: /customers/create');
            exit;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO customers (company_id, name, whatsapp, whatsapp_e164, email, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$companyId, $name, $whatsapp, $whatsappE164, $email ?: null, $notes ?: null]);
        
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
        
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND company_id = ?");
        $stmt->execute([$id, (int)$company['id']]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
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
        
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND company_id = ?");
        $stmt->execute([$id, (int)$company['id']]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            header('Location: /customers');
            exit;
        }
        
        $name = trim($_POST['name'] ?? '');
        $whatsapp = preg_replace('/\D/', '', $_POST['whatsapp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($whatsapp)) {
            $_SESSION['flash_error'] = 'WhatsApp é obrigatório';
            header("Location: /customers/{$id}/edit");
            exit;
        }
        
        $whatsappE164 = normalizePhone($whatsapp);
        
        $updateStmt = $pdo->prepare("
            UPDATE customers SET 
                name = ?, whatsapp = ?, whatsapp_e164 = ?, email = ?, notes = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$name, $whatsapp, $whatsappE164, $email ?: null, $notes ?: null, $id]);
        
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
        
        $pdo = db();
        
        // Verificar se tem pedidos
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) FROM orders o
            JOIN customers c ON (o.customer_phone = c.whatsapp OR o.customer_phone = c.whatsapp_e164)
            WHERE c.id = ?
        ");
        $checkStmt->execute([$id]);
        
        if ($checkStmt->fetchColumn() > 0) {
            $_SESSION['flash_error'] = 'Cliente tem pedidos e não pode ser excluído';
            header('Location: /customers');
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ? AND company_id = ?");
        $stmt->execute([$id, (int)$company['id']]);
        
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
