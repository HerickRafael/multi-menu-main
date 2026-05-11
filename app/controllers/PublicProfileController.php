<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

class PublicProfileController extends Controller
{
    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        if (class_exists('SessionManager', false)) {
            SessionManager::start();
        }
    }

    private function guard(array $params): array
    {
        $slug = $params['slug'] ?? '';
        $company = Company::findBySlug($slug);

        if (!$company || empty($company['active'])) {
            http_response_code(404);
            echo 'Empresa não encontrada';
            exit;
        }

        $this->ensureSession();
        $customer = $_SESSION['customer'] ?? null;

        if (!$customer || (int)($customer['company_id'] ?? 0) !== (int)$company['id']) {
            // Redirecionar para home sem login=1 - JavaScript do rodapé abrirá o modal
            $redirect = base_url(rawurlencode((string)$company['slug']));
            header('Location: ' . $redirect);
            exit;
        }

        return [$company, $customer];
    }

    public function index(array $params)
    {
        [$company, $customer] = $this->guard($params);
        $slug = $params['slug'] ?? '';

        // Buscar endereços do banco de dados
        $customerId = (int)($customer['id'] ?? 0);
        $companyId = (int)($company['id'] ?? 0);
        $addresses = [];
        
        if ($customerId > 0 && $companyId > 0) {
            try {
                $addresses = CustomerAddress::getByCustomer($customerId, $companyId);
            } catch (Exception $e) {
                error_log("Erro ao buscar endereços: " . $e->getMessage());
                $addresses = [];
            }
        }

        // Buscar progresso no programa de fidelidade
        $loyalty = null;
        if ($customerId > 0 && $companyId > 0) {
            try {
                require_once __DIR__ . '/../models/LoyaltyProgram.php';
                $loyalty = LoyaltyProgram::getCustomerLoyalty($this->db(), $customerId, $companyId);
            } catch (Exception $e) {
                error_log("Erro ao buscar fidelidade: " . $e->getMessage());
            }
        }

        // --- Lógica de cupom de fidelidade (legacy) ---
        $loyaltyActive   = false;
        $loyaltyDiscount = 0;
        $loyaltyMessage  = '';
        $customerCoupon  = null;
        $showCoupon      = false;

        $hasFilledBirthdate  = !empty(trim($customer['birthdate'] ?? ''));
        $hasFilledDocument   = !empty(trim($customer['document'] ?? ''));
        $hasCompletedProfile = $hasFilledBirthdate && $hasFilledDocument;
        $customerPhone       = trim($customer['whatsapp'] ?? '');

        try {
            $db   = $this->db();
            $stmt = $db->prepare(
                'SELECT is_active, discount_percentage, welcome_message FROM loyalty_discounts WHERE company_id = ? LIMIT 1'
            );
            $stmt->execute([$companyId]);
            $loyaltyDiscountRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($loyaltyDiscountRow) {
                $loyaltyActive   = (int)($loyaltyDiscountRow['is_active'] ?? 0) === 1;
                $loyaltyDiscount = (float)($loyaltyDiscountRow['discount_percentage'] ?? 0);
                $loyaltyMessage  = trim($loyaltyDiscountRow['welcome_message'] ?? '');
            }

            if ($loyaltyActive && $customerPhone !== '') {
                $stmt = $db->prepare(
                    'SELECT id, coupon_code, discount_percentage, is_used, used_at
                     FROM customer_loyalty_coupons
                     WHERE company_id = ? AND customer_phone = ?
                     LIMIT 1'
                );
                $stmt->execute([$companyId, $customerPhone]);
                $customerCoupon = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($customerCoupon && $hasCompletedProfile) {
                    if ((int)($customerCoupon['is_used'] ?? 0) === 1 && !empty($customerCoupon['used_at'])) {
                        $hoursSinceUse = (time() - strtotime($customerCoupon['used_at'])) / 3600;
                        if ($hoursSinceUse <= 24) {
                            $showCoupon = true;
                        }
                    } else {
                        $showCoupon = true;
                    }
                }

                if ($hasCompletedProfile && !$customerCoupon && $loyaltyDiscount > 0) {
                    $couponPrefix = strtoupper(trim($company['coupon_prefix'] ?? substr($company['slug'] ?? 'CUPOM', 0, 4)));
                    $phoneHash    = strtoupper(substr(md5($customerPhone . $companyId), 0, 6));
                    $couponCode   = $couponPrefix . $phoneHash;

                    try {
                        $stmt = $db->prepare(
                            'INSERT INTO customer_loyalty_coupons
                             (company_id, customer_phone, coupon_code, discount_percentage, is_used, usage_limit)
                             VALUES (?, ?, ?, ?, 0, 1)'
                        );
                        $stmt->execute([$companyId, $customerPhone, $couponCode, $loyaltyDiscount]);
                        $customerCoupon = [
                            'id'                  => $db->lastInsertId(),
                            'coupon_code'         => $couponCode,
                            'discount_percentage' => $loyaltyDiscount,
                            'is_used'             => 0,
                            'used_at'             => null,
                        ];
                        $showCoupon = true;
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), 'Duplicate') !== false) {
                            $stmt = $db->prepare(
                                'SELECT id, coupon_code, discount_percentage, is_used, used_at
                                 FROM customer_loyalty_coupons
                                 WHERE company_id = ? AND customer_phone = ?
                                 LIMIT 1'
                            );
                            $stmt->execute([$companyId, $customerPhone]);
                            $customerCoupon = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($customerCoupon) {
                                $showCoupon = true;
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Erro no sistema de cupons: " . $e->getMessage());
            $loyaltyActive = false;
        }

        $showIcons = $loyaltyActive && $loyaltyDiscount > 0 && !$showCoupon;
        // --- Fim lógica de cupom ---

        // --- Histórico de pedidos ---
        $orders = [];
        if (!empty($customer['whatsapp'])) {
            try {
                $phone   = preg_replace('/[^0-9]/', '', $customer['whatsapp']);
                $phone11 = preg_replace('/^55/', '', $phone);
                $phone13 = '55' . $phone11;

                $stmt = $this->db()->prepare(
                    'SELECT id, total, status, created_at
                     FROM orders
                     WHERE company_id = ?
                       AND REGEXP_REPLACE(customer_phone, "[^0-9]", "") IN (?, ?)
                     ORDER BY created_at DESC
                     LIMIT 10'
                );
                $stmt->execute([$companyId, $phone11, $phone13]);
                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Erro ao buscar pedidos: " . $e->getMessage());
            }
        }
        // --- Fim histórico de pedidos ---

        return $this->view('public/profile', compact(
            'company', 'customer', 'addresses', 'slug', 'loyalty',
            'loyaltyActive', 'loyaltyDiscount', 'loyaltyMessage',
            'customerCoupon', 'showCoupon', 'hasCompletedProfile', 'showIcons',
            'orders'
        ));
    }

    public function update(array $params)
    {
        [$company, $customer] = $this->guard($params);

        $payload = $_POST['profile'] ?? [];

        if (!is_array($payload)) {
            $payload = [];
        }

        // Sanitizar e validar dados
        $updates = [
            'name'      => trim((string)($payload['name'] ?? $customer['name'] ?? '')),
            'whatsapp'  => trim((string)($payload['whatsapp'] ?? $customer['whatsapp'] ?? '')),
            'email'     => trim((string)($payload['email'] ?? $customer['email'] ?? '')),
            'birthdate' => trim((string)($payload['birthdate'] ?? $customer['birthdate'] ?? '')),
            'document'  => trim((string)($payload['document'] ?? $customer['document'] ?? '')),
            'notes'     => trim((string)($payload['notes'] ?? $customer['notes'] ?? '')),
        ];

        // Validar dados obrigatórios
        if (empty($updates['name']) || empty($updates['whatsapp'])) {
            $slug = trim((string)$company['slug']);
            $redirect = base_url(($slug !== '' ? $slug . '/' : '') . 'profile?error=required');
            header('Location: ' . $redirect);
            exit;
        }

        // Validar data de nascimento (se preenchida)
        if (!empty($updates['birthdate'])) {
            $birthValidation = $this->validateBirthdate($updates['birthdate']);
            if ($birthValidation !== true) {
                $slug = trim((string)$company['slug']);
                $redirect = base_url(($slug !== '' ? $slug . '/' : '') . 'profile?error=' . urlencode($birthValidation));
                header('Location: ' . $redirect);
                exit;
            }
        }

        // Persistir no banco de dados
        $customerId = (int)($customer['id'] ?? 0);
        $companyId = (int)($company['id'] ?? 0);
        
        if ($customerId > 0 && $companyId > 0) {
            try {
                $db = db();
                
                // Atualizar dados do cliente no banco
                $stmt = $db->prepare('
                    UPDATE customers 
                    SET 
                        name = ?,
                        email = ?,
                        birth_date = ?,
                        cpf = ?,
                        updated_at = NOW()
                    WHERE id = ? AND company_id = ?
                ');
                
                // Converter data de nascimento para formato do banco
                $birthDate = !empty($updates['birthdate']) ? $updates['birthdate'] : null;
                
                // Limpar CPF (manter apenas números e formatação)
                $cpf = !empty($updates['document']) ? $updates['document'] : null;
                
                $stmt->execute([
                    $updates['name'],
                    !empty($updates['email']) ? $updates['email'] : null,
                    $birthDate,
                    $cpf,
                    $customerId,
                    $companyId
                ]);
                
            } catch (Exception $e) {
                error_log("Erro ao atualizar perfil no banco: " . $e->getMessage());
                // Continua mesmo com erro no banco - salva na sessão
            }
        }

        // Atualizar sessão
        $customer = array_merge($customer, $updates);
        $_SESSION['customer'] = $customer;

        $slug = trim((string)$company['slug']);
        $redirect = base_url(($slug !== '' ? $slug . '/' : '') . 'profile?updated=1');
        header('Location: ' . $redirect);
        exit;
    }

    /**
     * Valida data de nascimento
     * @param string $birthdate Data no formato YYYY-MM-DD
     * @return true|string True se válido, mensagem de erro se inválido
     */
    private function validateBirthdate(string $birthdate)
    {
        // Verificar formato da data
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
            return 'birthdate_invalid_format';
        }

        // Converter para timestamp
        $birthTimestamp = strtotime($birthdate);
        if ($birthTimestamp === false) {
            return 'birthdate_invalid_format';
        }

        $birthDateTime = new DateTime($birthdate);
        $today = new DateTime('today');

        // Data não pode ser futura
        if ($birthDateTime > $today) {
            return 'birthdate_future';
        }

        // Calcular idade
        $age = $today->diff($birthDateTime)->y;

        // Idade mínima: 13 anos
        if ($age < 13) {
            return 'birthdate_too_young';
        }

        // Idade máxima: 120 anos (pessoa mais velha registrada tinha 122)
        if ($age > 120) {
            return 'birthdate_too_old';
        }

        return true;
    }

    /**
     * GET /{slug}/profile/export-data
     * Exporta dados pessoais do cliente como JSON (LGPD Art. 18).
     */
    public function exportData(array $params): void
    {
        [$company, $customer] = $this->guard($params);
        $db = $this->db();
        $customerId = (int)$customer['id'];
        $companyId = (int)$company['id'];

        // Dados pessoais
        $st = $db->prepare('SELECT id, name, whatsapp, whatsapp_e164, email, cpf, birth_date, created_at, lgpd_consent_at FROM customers WHERE id = ? AND company_id = ?');
        $st->execute([$customerId, $companyId]);
        $personal = $st->fetch(PDO::FETCH_ASSOC);

        // Endereços
        $st = $db->prepare('SELECT street, number, complement, neighborhood, city, state, zip_code, label FROM customer_addresses WHERE customer_id = ?');
        $st->execute([$customerId]);
        $addresses = $st->fetchAll(PDO::FETCH_ASSOC);

        // Pedidos
        $phone = $customer['e164'] ?? $customer['whatsapp'] ?? '';
        $phoneClean = preg_replace('/[^0-9]/', '', $phone);
        $st = $db->prepare("
            SELECT o.id, o.total, o.status, o.created_at, o.customer_address
            FROM orders o
            WHERE o.company_id = ?
              AND REPLACE(REPLACE(REPLACE(o.customer_phone, '+', ''), '-', ''), ' ', '') = ?
            ORDER BY o.created_at DESC
            LIMIT 100
        ");
        $st->execute([$companyId, $phoneClean]);
        $orders = $st->fetchAll(PDO::FETCH_ASSOC);

        $export = [
            'exported_at' => date('Y-m-d\TH:i:s'),
            'company' => $company['name'],
            'personal_data' => $personal,
            'addresses' => $addresses,
            'orders' => $orders,
        ];

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="meus-dados-' . date('Y-m-d') . '.json"');
        echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * POST /{slug}/profile/request-deletion
     * Exclui os dados pessoais do cliente (LGPD Art. 18).
     * Mantém CPF e telefone para evitar que o cliente crie nova conta
     * e abuse do desconto de fidelidade (2% OFF por completar perfil).
     */
    public function requestDeletion(array $params): void
    {
        [$company, $customer] = $this->guard($params);
        $db = $this->db();
        $customerId = (int)$customer['id'];
        $companyId  = (int)$company['id'];

        // Anonimizar dados pessoais, mantendo CPF (cpf) e telefone (whatsapp)
        // para bloquear re-cadastro voltado a abusar do cupom de fidelidade.
        try {
            $st = $db->prepare("
                UPDATE customers
                SET
                    name           = '[CONTA REMOVIDA]',
                    email          = NULL,
                    birth_date     = NULL,
                    notes          = NULL,
                    lgpd_consent_at = NOW(),
                    updated_at     = NOW()
                WHERE id = ? AND company_id = ?
            ");
            $st->execute([$customerId, $companyId]);
        } catch (\Exception $e) {
            error_log("Erro ao anonimizar cliente na exclusão: " . $e->getMessage());
        }

        // Registrar solicitação para auditoria
        try {
            $st = $db->prepare('INSERT IGNORE INTO data_deletion_requests (customer_id, company_id, status, requested_at) VALUES (?, ?, ?, NOW())');
            $st->execute([$customerId, $companyId, 'completed']);
        } catch (\Exception $e) {
            error_log("Erro ao registrar solicitação de exclusão: " . $e->getMessage());
        }

        // Encerrar sessão do cliente
        $this->ensureSession();
        unset($_SESSION['customer']);
        session_destroy();

        // Redirecionar para home da empresa
        $slug = trim((string)$company['slug']);
        header('Location: ' . base_url($slug !== '' ? rawurlencode($slug) : ''));
        exit;
    }
}
