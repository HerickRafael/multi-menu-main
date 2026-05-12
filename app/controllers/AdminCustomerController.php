<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

require_once __DIR__ . '/../modules/auth/AdminGuard.php';
require_once __DIR__ . '/../modules/customers/CustomerListService.php';

/**
 * Controller para gestão de Clientes no painel admin
 * 
 * Funcionalidades:
 * - Listagem paginada com busca
 * - Criação/Edição de clientes
 * - Visualização de histórico de pedidos
 * - Exclusão (soft delete para clientes com pedidos)
 */
class AdminCustomerController extends Controller
{
    private const ITEMS_PER_PAGE = 20;

    /**
     * Guarda de autenticação - verifica permissões
     */
    private function guard(string $slug): array
    {
        return AdminGuard::requireCompanyAccess($slug);
    }

    /**
     * Listagem de clientes com busca e paginação
     */
    public function index(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        $pdo = db();
        $search = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));

        $result = CustomerListService::listWithStats($pdo, $companyId, $search, $page, self::ITEMS_PER_PAGE);
        $customers = $result['customers'];
        $stats = $result['stats'];
        $pagination = $result['pagination'];

        // Mensagens de sucesso/erro
        $success = $this->getFlashMessage('success');
        $error = $this->getFlashMessage('error');

        $this->view('admin/customers/index', [
            'user' => $user,
            'company' => $company,
            'customers' => $customers,
            'stats' => $stats,
            'search' => $search,
            'page' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
            'totalItems' => $pagination['totalItems'],
            'success' => $success,
            'error' => $error,
        ]);
    }

    /**
     * Formulário de criação de cliente
     */
    public function create(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        $pdo = db();

        $customer = [
            'id' => null,
            'name' => '',
            'whatsapp' => '',
            'whatsapp_e164' => '',
        ];

        // Buscar cidades cadastradas
        $citiesStmt = $pdo->prepare("SELECT id, name FROM delivery_cities WHERE company_id = ? ORDER BY name");
        $citiesStmt->execute([$companyId]);
        $cities = $citiesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar bairros/zonas cadastrados
        $zonesStmt = $pdo->prepare("
            SELECT dz.id, dz.neighborhood, dz.city_id, dc.name as city_name
            FROM delivery_zones dz
            JOIN delivery_cities dc ON dc.id = dz.city_id
            WHERE dz.company_id = ?
            ORDER BY dc.name, dz.neighborhood
        ");
        $zonesStmt->execute([$companyId]);
        $zones = $zonesStmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/customers/form', [
            'user' => $user,
            'company' => $company,
            'customer' => $customer,
            'isEdit' => false,
            'errors' => [],
            'cities' => $cities,
            'zones' => $zones,
        ]);
    }

    /**
     * Formulário de edição de cliente
     */
    public function edit(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $customerId = (int)($params['id'] ?? 0);
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        $pdo = db();

        // Buscar cliente
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND company_id = ?");
        $stmt->execute([$customerId, $companyId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/customers?error=notfound'));
            exit;
        }

        // Buscar endereços do cliente
        $addrStmt = $pdo->prepare("
            SELECT * FROM customer_addresses 
            WHERE customer_id = ? AND company_id = ?
            ORDER BY is_default DESC, created_at DESC
        ");
        $addrStmt->execute([$customerId, $companyId]);
        $addresses = $addrStmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar histórico de pedidos (últimos 10)
        // Normalizar telefone para comparação (remover formatação e comparar variações)
        $phoneDigits = preg_replace('/\D/', '', $customer['whatsapp'] ?? '');
        $phoneE164 = $customer['whatsapp_e164'] ?? '';
        // Variações possíveis do telefone
        $phoneVariations = array_unique(array_filter([
            $customer['whatsapp'],           // (51) 92001-7687
            $phoneE164,                       // 5551920017687
            $phoneDigits,                     // 51920017687
            '55' . $phoneDigits,              // 5551920017687
            ltrim($phoneE164, '55'),          // 51920017687 (sem código país)
            substr($phoneDigits, -11),        // últimos 11 dígitos
            substr($phoneDigits, -10),        // últimos 10 dígitos (sem DDD duplo)
        ]));
        
        // Construir query com múltiplas variações
        $placeholders = implode(',', array_fill(0, count($phoneVariations), '?'));
        $ordersStmt = $pdo->prepare("
            SELECT id, total, status, created_at
            FROM orders 
            WHERE customer_phone IN ({$placeholders}) AND company_id = ?
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $ordersStmt->execute(array_merge($phoneVariations, [$companyId]));
        $recentOrders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

        // Estatísticas do cliente (usar mesmas variações de telefone)
        $statsStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_orders,
                COALESCE(SUM(total), 0) as total_spent,
                COALESCE(AVG(total), 0) as avg_ticket
            FROM orders 
            WHERE customer_phone IN ({$placeholders}) AND company_id = ? AND status NOT IN ('canceled')
        ");
        $statsStmt->execute(array_merge($phoneVariations, [$companyId]));
        $customerStats = $statsStmt->fetch(PDO::FETCH_ASSOC);

        // Buscar cidades cadastradas para formulário de endereço
        $citiesStmt = $pdo->prepare("SELECT id, name FROM delivery_cities WHERE company_id = ? ORDER BY name");
        $citiesStmt->execute([$companyId]);
        $cities = $citiesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar bairros/zonas cadastrados
        $zonesStmt = $pdo->prepare("
            SELECT dz.id, dz.neighborhood, dz.city_id, dc.name as city_name
            FROM delivery_zones dz
            JOIN delivery_cities dc ON dc.id = dz.city_id
            WHERE dz.company_id = ?
            ORDER BY dc.name, dz.neighborhood
        ");
        $zonesStmt->execute([$companyId]);
        $zones = $zonesStmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/customers/form', [
            'user' => $user,
            'company' => $company,
            'customer' => $customer,
            'addresses' => $addresses ?? [],
            'recentOrders' => $recentOrders ?? [],
            'stats' => $customerStats,
            'isEdit' => true,
            'errors' => [],
            'cities' => $cities,
            'zones' => $zones,
        ]);
    }

    /**
     * Salvar cliente (criar ou atualizar)
     */
    public function store(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $customerId = (int)($params['id'] ?? 0);
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];
        $isEdit = $customerId > 0;

        $pdo = db();

        // Validação
        $errors = [];
        $name = trim($_POST['name'] ?? '');
        $whatsapp = preg_replace('/\D/', '', $_POST['whatsapp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $cpf = trim($_POST['cpf'] ?? '');
        $birthDate = trim($_POST['birth_date'] ?? '');

        if ($name === '') {
            $errors[] = 'O nome é obrigatório';
        } elseif (mb_strlen($name) > 150) {
            $errors[] = 'O nome deve ter no máximo 150 caracteres';
        }

        if ($whatsapp === '') {
            $errors[] = 'O WhatsApp é obrigatório';
        } elseif (strlen($whatsapp) < 10 || strlen($whatsapp) > 15) {
            $errors[] = 'WhatsApp inválido. Use o formato com DDD (ex: 51999999999)';
        }

        // Validar email se preenchido
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail inválido';
        }

        // Validar CPF se preenchido (formato básico)
        if ($cpf !== '' && !preg_match('/^\d{3}\.?\d{3}\.?\d{3}-?\d{2}$/', $cpf)) {
            $errors[] = 'CPF inválido. Use o formato 000.000.000-00';
        }

        // Validar data de nascimento se preenchida
        if ($birthDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthDate)) {
            $errors[] = 'Data de nascimento inválida';
        }

        // Formatar número E.164
        $whatsappE164 = normalizePhone($whatsapp);

        // Verificar duplicidade
        if (empty($errors)) {
            $duplicateCheck = $pdo->prepare("
                SELECT id FROM customers 
                WHERE company_id = ? AND whatsapp_e164 = ? AND id != ?
                LIMIT 1
            ");
            $duplicateCheck->execute([$companyId, $whatsappE164, $customerId]);
            
            if ($duplicateCheck->fetch()) {
                $errors[] = 'Já existe um cliente cadastrado com este número de WhatsApp';
            }
        }

        // Se houver erros, voltar ao formulário
        if (!empty($errors)) {
            $customer = [
                'id' => $customerId ?: null,
                'name' => $name,
                'whatsapp' => $whatsapp,
                'whatsapp_e164' => $whatsappE164,
                'email' => $email,
                'cpf' => $cpf,
                'birth_date' => $birthDate,
            ];

            $this->view('admin/customers/form', [
                'user' => $user,
                'company' => $company,
                'customer' => $customer,
                'isEdit' => $isEdit,
                'errors' => $errors,
            ]);
            return;
        }

        $now = date('Y-m-d H:i:s');

        try {
            if ($isEdit) {
                // Verificar se cliente existe e pertence à empresa
                $checkStmt = $pdo->prepare("SELECT id, cpf, birth_date FROM customers WHERE id = ? AND company_id = ?");
                $checkStmt->execute([$customerId, $companyId]);
                $existingCustomer = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$existingCustomer) {
                    header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/customers?error=notfound'));
                    exit;
                }

                // REGRA: CPF e birth_date só são atualizados se o novo valor NÃO for vazio
                // Isso evita que o cliente "apague" os dados do admin
                $finalCpf = ($cpf !== '') ? $cpf : $existingCustomer['cpf'];
                $finalBirthDate = ($birthDate !== '') ? $birthDate : $existingCustomer['birth_date'];

                $stmt = $pdo->prepare("
                    UPDATE customers SET
                        name = ?,
                        whatsapp = ?,
                        whatsapp_e164 = ?,
                        email = ?,
                        cpf = ?,
                        birth_date = ?,
                        updated_at = ?
                    WHERE id = ? AND company_id = ?
                ");
                $stmt->execute([
                    $name,
                    $whatsapp,
                    $whatsappE164,
                    $email ?: null,
                    $finalCpf ?: null,
                    $finalBirthDate ?: null,
                    $now,
                    $customerId,
                    $companyId,
                ]);

                $message = 'updated';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO customers 
                    (company_id, name, whatsapp, whatsapp_e164, email, cpf, birth_date, created_at, updated_at, last_login_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId,
                    $name,
                    $whatsapp,
                    $whatsappE164,
                    $email ?: null,
                    $cpf ?: null,
                    $birthDate ?: null,
                    $now,
                    $now,
                    $now,
                ]);

                $customerId = (int)$pdo->lastInsertId();
                
                // Se endereço foi preenchido, criar também
                $addressStreet = trim($_POST['address_street'] ?? '');
                $addressNumber = trim($_POST['address_number'] ?? '');
                $addressZoneId = (int)($_POST['address_zone_id'] ?? 0);
                
                if ($addressStreet !== '' && $addressNumber !== '') {
                    // Buscar cidade e bairro pelo zone_id
                    $city = '';
                    $neighborhood = '';
                    
                    if ($addressZoneId > 0) {
                        $zoneStmt = $pdo->prepare("
                            SELECT dz.neighborhood, dc.name as city_name
                            FROM delivery_zones dz
                            JOIN delivery_cities dc ON dc.id = dz.city_id
                            WHERE dz.id = ? AND dz.company_id = ?
                        ");
                        $zoneStmt->execute([$addressZoneId, $companyId]);
                        $zoneData = $zoneStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($zoneData) {
                            $city = $zoneData['city_name'];
                            $neighborhood = $zoneData['neighborhood'];
                        }
                    }
                    
                    $addrStmt = $pdo->prepare("
                        INSERT INTO customer_addresses 
                        (company_id, customer_id, label, city, neighborhood, street, number, complement, reference, is_default, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)
                    ");
                    $addrStmt->execute([
                        $companyId,
                        $customerId,
                        trim($_POST['address_label'] ?? '') ?: 'Casa',
                        $city,
                        $neighborhood,
                        $addressStreet,
                        $addressNumber,
                        trim($_POST['address_complement'] ?? '') ?: null,
                        trim($_POST['address_reference'] ?? '') ?: null,
                        $now,
                        $now,
                    ]);
                }
                
                $message = 'created';
            }

            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/customers?success=' . $message));
            exit;

        } catch (PDOException $e) {
            error_log("Erro ao salvar cliente: " . $e->getMessage());
            
            $customer = [
                'id' => $customerId ?: null,
                'name' => $name,
                'whatsapp' => $whatsapp,
                'whatsapp_e164' => $whatsappE164,
                'email' => $email,
                'cpf' => $cpf,
                'birth_date' => $birthDate,
            ];

            $this->view('admin/customers/form', [
                'user' => $user,
                'company' => $company,
                'customer' => $customer,
                'isEdit' => $isEdit,
                'errors' => ['Erro ao salvar cliente. Tente novamente.'],
            ]);
        }
    }

    /**
     * Excluir cliente
     */
    public function delete(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $customerId = (int)($params['id'] ?? 0);
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        $pdo = db();

        try {
            // Verificar se cliente existe e pegar dados para checar pedidos
            $checkStmt = $pdo->prepare("SELECT id, whatsapp, whatsapp_e164 FROM customers WHERE id = ? AND company_id = ?");
            $checkStmt->execute([$customerId, $companyId]);
            $customerData = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customerData) {
                header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/customers?error=notfound'));
                exit;
            }

            // Verificar se tem pedidos vinculados (por telefone)
            $ordersStmt = $pdo->prepare("
                SELECT COUNT(*) FROM orders 
                WHERE (customer_phone = ? OR customer_phone = ?) AND company_id = ?
            ");
            $ordersStmt->execute([$customerData['whatsapp'], $customerData['whatsapp_e164'], $companyId]);
            $hasOrders = (int)$ordersStmt->fetchColumn() > 0;

            if ($hasOrders) {
                // Soft delete - apenas anonimiza os dados
                $stmt = $pdo->prepare("
                    UPDATE customers SET
                        name = 'Cliente Removido',
                        whatsapp = '0000000000',
                        whatsapp_e164 = '0000000000',
                        updated_at = NOW()
                    WHERE id = ? AND company_id = ?
                ");
                $stmt->execute([$customerId, $companyId]);
            } else {
                // Hard delete - remove completamente
                $pdo->beginTransaction();
                
                // Remover endereços
                $delAddr = $pdo->prepare("DELETE FROM customer_addresses WHERE customer_id = ?");
                $delAddr->execute([$customerId]);
                
                // Remover histórico de pedidos
                $delHistory = $pdo->prepare("DELETE FROM customer_order_history WHERE customer_id = ?");
                $delHistory->execute([$customerId]);
                
                // Remover cliente
                $delCustomer = $pdo->prepare("DELETE FROM customers WHERE id = ? AND company_id = ?");
                $delCustomer->execute([$customerId, $companyId]);
                
                $pdo->commit();
            }

            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/customers?success=deleted'));
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erro ao excluir cliente: " . $e->getMessage());
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/customers?error=delete_failed'));
            exit;
        }
    }

    /**
     * API: Buscar clientes (para autocomplete)
     */
    public function apiSearch(array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];

        header('Content-Type: application/json');

        $search = trim($_GET['q'] ?? '');
        
        if (mb_strlen($search) < 2) {
            echo json_encode(['customers' => []]);
            exit;
        }

        $pdo = db();
        $stmt = $pdo->prepare("
            SELECT id, name, whatsapp, whatsapp_e164
            FROM customers 
            WHERE company_id = ? 
              AND (name LIKE ? OR whatsapp LIKE ? OR whatsapp_e164 LIKE ?)
            ORDER BY name ASC
            LIMIT 10
        ");
        
        $searchTerm = '%' . $search . '%';
        $stmt->execute([$companyId, $searchTerm, $searchTerm, $searchTerm]);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['customers' => $customers]);
        exit;
    }

    /**
     * @deprecated Use normalizePhone() global function instead
     */
    private function formatE164(string $phone): string
    {
        return normalizePhone($phone);
    }

    /**
     * Atualiza um endereço específico do cliente (AJAX)
     */
    public function updateAddress(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];
        
        $customerId = (int)($params['id'] ?? 0);
        $addressId = (int)($params['addressId'] ?? 0);
        
        if ($customerId <= 0 || $addressId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'IDs inválidos']);
            exit;
        }
        
        $pdo = db();
        
        // Verificar se o endereço pertence ao cliente e empresa
        $stmt = $pdo->prepare("
            SELECT a.id 
            FROM customer_addresses a
            JOIN customers c ON c.id = a.customer_id
            WHERE a.id = ? AND a.customer_id = ? AND c.company_id = ?
        ");
        $stmt->execute([$addressId, $customerId, $companyId]);
        
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Endereço não encontrado']);
            exit;
        }
        
        // Obter dados do POST
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }
        
        // Campos permitidos
        $allowedFields = ['label', 'name', 'phone', 'city', 'neighborhood', 'street', 'number', 'complement', 'reference', 'is_default'];
        $updates = [];
        $values = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = ?";
                if ($field === 'is_default') {
                    $values[] = $data[$field] ? 1 : 0;
                } else {
                    $values[] = trim((string)$data[$field]);
                }
            }
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nenhum campo para atualizar']);
            exit;
        }
        
        // Se está definindo como padrão, remover padrão dos outros
        if (!empty($data['is_default'])) {
            $pdo->prepare("UPDATE customer_addresses SET is_default = 0 WHERE customer_id = ? AND id != ?")
                ->execute([$customerId, $addressId]);
        }
        
        $updates[] = "updated_at = NOW()";
        $values[] = $addressId;
        
        $sql = "UPDATE customer_addresses SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($values)) {
            // Buscar endereço atualizado
            $stmt = $pdo->prepare("SELECT * FROM customer_addresses WHERE id = ?");
            $stmt->execute([$addressId]);
            $address = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'address' => $address]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao atualizar endereço']);
        }
        exit;
    }
    
    /**
     * Adiciona um novo endereço ao cliente (AJAX)
     */
    public function storeAddress(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];
        
        $customerId = (int)($params['id'] ?? 0);
        
        if ($customerId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do cliente inválido']);
            exit;
        }
        
        $pdo = db();
        
        // Verificar se o cliente pertence à empresa
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE id = ? AND company_id = ?");
        $stmt->execute([$customerId, $companyId]);
        
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Cliente não encontrado']);
            exit;
        }
        
        // Obter dados do POST
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }
        
        // Validar campos obrigatórios
        $street = trim((string)($data['street'] ?? ''));
        $number = trim((string)($data['number'] ?? ''));
        
        if ($street === '' || $number === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Rua e número são obrigatórios']);
            exit;
        }
        
        $isDefault = !empty($data['is_default']) ? 1 : 0;
        
        // Se é o primeiro endereço ou está definindo como padrão
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_addresses WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        $addressCount = (int)$stmt->fetchColumn();
        
        if ($addressCount === 0) {
            $isDefault = 1; // Primeiro endereço sempre é padrão
        } elseif ($isDefault) {
            // Remove padrão dos outros
            $pdo->prepare("UPDATE customer_addresses SET is_default = 0 WHERE customer_id = ?")
                ->execute([$customerId]);
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO customer_addresses 
            (customer_id, company_id, label, name, phone, city, neighborhood, street, number, complement, reference, is_default, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $result = $stmt->execute([
            $customerId,
            $companyId,
            trim((string)($data['label'] ?? '')),
            trim((string)($data['name'] ?? '')),
            trim((string)($data['phone'] ?? '')),
            trim((string)($data['city'] ?? '')),
            trim((string)($data['neighborhood'] ?? '')),
            $street,
            $number,
            trim((string)($data['complement'] ?? '')),
            trim((string)($data['reference'] ?? '')),
            $isDefault
        ]);
        
        if ($result) {
            $newId = (int)$pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM customer_addresses WHERE id = ?");
            $stmt->execute([$newId]);
            $address = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'address' => $address]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao criar endereço']);
        }
        exit;
    }
    
    /**
     * Remove um endereço do cliente (AJAX)
     */
    public function deleteAddress(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $slug = trim((string)($params['slug'] ?? ''));
        [$user, $company] = $this->guard($slug);
        $companyId = (int)$company['id'];
        
        $customerId = (int)($params['id'] ?? 0);
        $addressId = (int)($params['addressId'] ?? 0);
        
        if ($customerId <= 0 || $addressId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'IDs inválidos']);
            exit;
        }
        
        $pdo = db();
        
        // Verificar se o endereço pertence ao cliente e empresa
        $stmt = $pdo->prepare("
            SELECT a.id, a.is_default 
            FROM customer_addresses a
            JOIN customers c ON c.id = a.customer_id
            WHERE a.id = ? AND a.customer_id = ? AND c.company_id = ?
        ");
        $stmt->execute([$addressId, $customerId, $companyId]);
        $address = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$address) {
            http_response_code(404);
            echo json_encode(['error' => 'Endereço não encontrado']);
            exit;
        }
        
        // Deletar endereço
        $stmt = $pdo->prepare("DELETE FROM customer_addresses WHERE id = ?");
        
        if ($stmt->execute([$addressId])) {
            // Se era o padrão, definir outro como padrão
            if ($address['is_default']) {
                $pdo->prepare("
                    UPDATE customer_addresses 
                    SET is_default = 1 
                    WHERE customer_id = ? 
                    ORDER BY id ASC 
                    LIMIT 1
                ")->execute([$customerId]);
            }
            
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao remover endereço']);
        }
        exit;
    }

    /**
     * API - Validar número de WhatsApp usando Evolution API
     */
    public function validateWhatsapp(array $params): void
    {
        header('Content-Type: application/json');
        
        $slug = trim((string)($params['slug'] ?? ''));
        
        try {
            // Guard para API - retorna JSON em vez de redirect
            Auth::start();
            $user = Auth::user();
            
            if (!$user) {
                echo json_encode(['success' => false, 'error' => 'Sessão expirada. Faça login novamente.']);
                return;
            }
            
            $company = Company::findBySlug($slug);
            if (!$company || empty($company['id'])) {
                echo json_encode(['success' => false, 'error' => 'Empresa não encontrada']);
                return;
            }
            
            $isRoot = ($user['role'] === 'root');
            if (!$isRoot && (int)$user['company_id'] !== (int)$company['id']) {
                echo json_encode(['success' => false, 'error' => 'Acesso negado']);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $whatsapp = $input['whatsapp'] ?? '';
            
            // Limpar número (apenas dígitos)
            $cleanNumber = normalizePhone($whatsapp);
            
            if (strlen($cleanNumber) < 12 || strlen($cleanNumber) > 15) {
                echo json_encode([
                    'success' => false, 
                    'error' => 'Número inválido. Use DDD + número (mínimo 10 dígitos)'
                ]);
                return;
            }
            
            // Verificar se número já está cadastrado no sistema
            $pdo = db();
            $checkStmt = $pdo->prepare("
                SELECT id, name, whatsapp FROM customers 
                WHERE company_id = ? AND (whatsapp_e164 = ? OR whatsapp = ?)
                LIMIT 1
            ");
            $checkStmt->execute([(int)$company['id'], $cleanNumber, $cleanNumber]);
            $existingCustomer = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingCustomer) {
                echo json_encode([
                    'success' => false,
                    'exists_in_system' => true,
                    'customer_id' => $existingCustomer['id'],
                    'customer_name' => $existingCustomer['name'],
                    'error' => 'Este número já está cadastrado para o cliente: ' . $existingCustomer['name']
                ]);
                return;
            }
            
            // Tentar validar no WhatsApp via Evolution API
            $whatsappValid = null;
            $whatsappChecked = false;
            
            if (!empty($company['evolution_server_url']) && !empty($company['evolution_api_key'])) {
                require_once __DIR__ . '/../services/WhatsAppValidator.php';
                
                $result = WhatsAppValidator::validate($company, $cleanNumber);
                $whatsappChecked = $result['checked'] ?? false;
                $whatsappValid = $result['exists'] ?? null;
            }
            
            echo json_encode([
                'success' => true,
                'exists_in_system' => false,
                'whatsapp_checked' => $whatsappChecked,
                'whatsapp_valid' => $whatsappValid,
                'number_formatted' => $cleanNumber,
                'message' => $whatsappChecked 
                    ? ($whatsappValid ? 'Número válido no WhatsApp!' : 'Este número não existe no WhatsApp.')
                    : 'Número disponível (não foi possível verificar no WhatsApp)'
            ]);
            
        } catch (Exception $e) {
            error_log('Erro validateWhatsapp: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor']);
        }
    }

    /**
     * Obtém mensagem flash
     */
    private function getFlashMessage(string $type): ?string
    {
        if (!isset($_GET[$type])) {
            return null;
        }

        $key = $_GET[$type];
        
        return match($type) {
            'success' => match($key) {
                'created' => 'Cliente cadastrado com sucesso!',
                'updated' => 'Cliente atualizado com sucesso!',
                'deleted' => 'Cliente removido com sucesso!',
                'address_updated' => 'Endereço atualizado com sucesso!',
                'address_created' => 'Endereço adicionado com sucesso!',
                'address_deleted' => 'Endereço removido com sucesso!',
                default => 'Operação realizada com sucesso!'
            },
            'error' => match($key) {
                'notfound' => 'Cliente não encontrado',
                'delete_failed' => 'Erro ao remover cliente',
                'address_error' => 'Erro na operação de endereço',
                default => 'Erro na operação'
            },
            default => null
        };
    }
}
