<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

// Serviço específico
require_once __DIR__ . '/../services/WhatsAppValidator.php';
require_once __DIR__ . '/../services/CustomerEngagementService.php';

class CustomerAuthController extends Controller
{
    /**
     * Busca a empresa pelo slug.
     * Ajuste o nome da tabela/campos se necessário.
     */
    protected function findCompanyBySlug(string $slug): ?array
    {
        return Customer::findCompanyBySlug($slug);
    }

    /**
     * POST /{slug}/customer-login
     * Campos: name, whatsapp
     */
    public function login(array $params): void
    {
        // garante sessão
        AuthCustomer::start();
        
        // Log para debug
        error_log('Login attempt - X-Requested-With: ' . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'not set'));
        error_log('Login attempt - Accept: ' . ($_SERVER['HTTP_ACCEPT'] ?? 'not set'));

        $slug = $params['slug'] ?? null;

        if (!$slug) {
            $this->json(['ok' => false, 'message' => 'Empresa inválida.'], 400);
        }

        $company = $this->findCompanyBySlug($slug);

        if (!$company) {
            $this->json(['ok' => false, 'message' => 'Empresa não encontrada.'], 404);
        }

        $name     = trim($_POST['name'] ?? $_POST['nome'] ?? '');
        $whatsRaw = trim($_POST['whatsapp'] ?? '');

        if ($name === '' || $whatsRaw === '') {
            $this->json(['ok' => false, 'message' => 'Informe nome e WhatsApp.'], 400);
            return;
        }

        $e164 = normalize_whatsapp_e164($whatsRaw);

        if ($e164 === '' || strlen($e164) < 12) {
            $this->json(['ok' => false, 'message' => 'WhatsApp inválido.'], 400);
            return;
        }

        // Validar se o número existe no WhatsApp (se Evolution estiver conectada)
        // NOTA: Nunca bloquear login por falha na validação — APIs externas podem dar falsos negativos
        $whatsappCheck = WhatsAppValidator::validate($company, $e164);

        $now = date('Y-m-d H:i:s');

        // procura cliente por (company_id, whatsapp_e164)
        $customer = Customer::findByCompanyAndE164((int)$company['id'], $e164);

        // LGPD consent
        $lgpdConsent = !empty($_POST['lgpd_consent']);
        // Se cliente já existe e já aceitou LGPD, não exigir novamente
        $alreadyConsented = $customer && !empty($customer['lgpd_consent_at']);
        $lgpdFields = ($lgpdConsent && !$alreadyConsented) ? [
            'lgpd_consent_at' => $now,
            'lgpd_consent_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ] : [];

        if (!$customer) {
            // Novo cliente — LGPD obrigatório
            if (!$lgpdConsent) {
                $this->json([
                    'ok' => false,
                    'message' => 'Você precisa aceitar os termos de privacidade para se cadastrar.'
                ], 400);
                return;
            }
            // cria
            $id = Customer::insert(array_merge([
                'company_id'    => (int)$company['id'],
                'name'          => $name,
                'whatsapp'      => $whatsRaw,
                'whatsapp_e164' => $e164,
                'created_at'    => $now,
                'updated_at'    => $now,
                'last_login_at' => $now,
            ], $lgpdFields));
            $customer = Customer::findById((int)$id, (int)$company['id']);

            // Agenda cenário 1 imediatamente para reduzir o tempo entre cadastro e contato.
            try {
                $engagement = new CustomerEngagementService((int)$company['id']);
                $engagement->scheduleSignupNoOrderMessage((int)$id, $e164, $name);
            } catch (\Throwable $e) {
                error_log('[CustomerAuth] Falha ao agendar cenário 1: ' . $e->getMessage());
            }
        } else {
            // atualiza
            Customer::updateById((int)$customer['id'], array_merge([
                'name'          => $name,
                'whatsapp'      => $whatsRaw,
                'updated_at'    => $now,
                'last_login_at' => $now,
            ], $lgpdFields));
            $customer = Customer::findById((int)$customer['id'], (int)$company['id']);
        }

        // Preservar carrinho antes de regenerar sessão
        // Usar CartStorage para recuperar do Redis/DB caso a sessão PHP tenha expirado (GC)
        $storage = CartStorage::instance();
        $cartBeforeLogin         = $storage->getCart();
        $customsBeforeLogin      = $storage->getCustomizations();

        // evita fixation e salva sessão com escopo da empresa
        // 🔐 Usar sistema de segurança aprimorado
        if (function_exists('initialize_customer_session')) {
            initialize_customer_session($customer, (int)$company['id']);
        } else {
            // Fallback para sistema antigo
            session_regenerate_id(true);
        }

        // Restaurar carrinho no novo session_id (initialize_customer_session limpa $_SESSION inteiro)
        if (!empty($cartBeforeLogin) && class_exists('CartStorage')) {
            $_SESSION['cart']           = $cartBeforeLogin;
            $_SESSION['customizations'] = $customsBeforeLogin;
            // Persiste no Redis/DB sob o novo session_id
            CartStorage::instance()->setCart($cartBeforeLogin);
        }
        
        // salva sessão com escopo da empresa
        $_SESSION['customer'] = [
            'id'           => (int)$customer['id'],
            'name'         => $customer['name'],
            'whatsapp'     => $customer['whatsapp'],
            'e164'         => $customer['whatsapp_e164'],
            'company_id'   => (int)$company['id'],
            'company_slug' => $slug,
            'login_at'     => $now,
        ];
        
        // 🔐 Sincronizar dados para o novo sistema de segurança
        $_SESSION['customer_id'] = (int)$customer['id'];
        $_SESSION['customer_phone'] = $customer['whatsapp'];
        $_SESSION['customer_name'] = $customer['name'];
        $_SESSION['company_id'] = (int)$company['id'];
        
        // Log de login para auditoria
        if (function_exists('log_security_event')) {
            log_security_event('customer_login_success', [
                'customer_id' => (int)$customer['id'],
                'customer_name' => $customer['name'],
                'company_slug' => $slug
            ]);
        }

        // cookie 1 ano (opcional)
        setcookie('mm_customer_e164', $customer['whatsapp_e164'], [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $defaultRedirect = base_url(rawurlencode($slug));
        $redirectTarget = trim($_POST['redirect_to'] ?? '');
        $redirectUrl = $defaultRedirect;

        if ($redirectTarget !== '') {
            if ($redirectTarget[0] === '/') {
                // Usar base_url() para garantir o protocolo correto (considera config e proxy)
                $redirectUrl = base_url(ltrim($redirectTarget, '/'));
            } elseif (preg_match('~^https?://~i', $redirectTarget)) {
                $parsed = parse_url($redirectTarget);
                $host = $_SERVER['HTTP_HOST'] ?? '';

                if (!empty($parsed['host']) && strcasecmp($parsed['host'], $host) === 0) {
                    // Forçar HTTPS se a base_url usa HTTPS
                    $baseScheme = parse_url(base_url(), PHP_URL_SCHEME);
                    if ($baseScheme === 'https') {
                        $redirectTarget = preg_replace('~^http://~i', 'https://', $redirectTarget);
                    }
                    $redirectUrl = $redirectTarget;
                }
            } else {
                $redirectUrl = base_url(ltrim($redirectTarget, '/'));
            }
        }

        $wantJson = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
                 || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

        if ($wantJson) {
            $this->json(['ok' => true, 'redirect' => $redirectUrl]);
        }
        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * POST /{slug}/customer-logout
     */
    public function logout(array $params): void
    {
        AuthCustomer::start();
        $slug = $params['slug'] ?? '';
        
        // 🔐 Log de logout para auditoria
        if (function_exists('log_security_event')) {
            log_security_event('customer_logout', [
                'customer_id' => $_SESSION['customer']['id'] ?? $_SESSION['customer_id'] ?? null,
                'customer_name' => $_SESSION['customer']['name'] ?? $_SESSION['customer_name'] ?? null,
                'company_slug' => $slug
            ]);
        }
        
        // 🔐 Usar sistema de logout seguro se disponível
        if (function_exists('logout_customer')) {
            logout_customer();
        } else {
            unset($_SESSION['customer']);
            unset($_SESSION['customer_id']);
            unset($_SESSION['customer_phone']);
            unset($_SESSION['customer_name']);
            unset($_SESSION['company_id']);
            unset($_SESSION['couponCode']);
            unset($_SESSION['couponDiscount']);
        }
        
        setcookie('mm_customer_e164', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_regenerate_id(true);

        $homeUrl = $slug ? base_url(rawurlencode($slug)) : base_url();
        $wantJson = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
                 || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

        if ($wantJson) {
            $this->json(['ok' => true]);
        }
        header('Location: ' . $homeUrl);
        exit;
    }

    /**
     * GET /{slug}/customer-me
     */
    public function me(array $params): void
    {
        AuthCustomer::start();
        $c = $_SESSION['customer'] ?? null;
        $this->json(['logged' => (bool)$c, 'customer' => $c ?: null]);
    }

    /**
     * POST /{slug}/customer-lookup
     * Busca cliente por WhatsApp e valida se número existe no WhatsApp
     */
    public function lookup(array $params): void
    {
        AuthCustomer::start();
        
        $slug = $params['slug'] ?? null;
        
        if (!$slug) {
            $this->json(['ok' => false, 'message' => 'Empresa inválida.'], 400);
            return;
        }
        
        $company = $this->findCompanyBySlug($slug);
        
        if (!$company) {
            $this->json(['ok' => false, 'message' => 'Empresa não encontrada.'], 404);
            return;
        }
        
        $whatsRaw = trim($_POST['whatsapp'] ?? '');
        
        if ($whatsRaw === '') {
            $this->json(['ok' => false, 'message' => 'WhatsApp não informado.'], 400);
            return;
        }
        
        $e164 = normalize_whatsapp_e164($whatsRaw);
        
        if ($e164 === '' || strlen($e164) < 12) {
            $this->json(['ok' => false, 'message' => 'WhatsApp inválido.', 'valid' => false], 400);
            return;
        }
        
        // Validar se o número existe no WhatsApp (se Evolution estiver conectada)
        // NOTA: Resultado é informativo — nunca bloqueia o cadastro/login
        $whatsappCheck = WhatsAppValidator::validate($company, $e164);
        
        // Buscar cliente no banco de dados
        $customer = Customer::findByCompanyAndE164((int)$company['id'], $e164);
        
        // Se validação diz que não existe MAS o cliente já está cadastrado, ignorar (falso negativo da API)
        $whatsappInvalid = $whatsappCheck['checked'] && !$whatsappCheck['exists'] && !$customer;
        
        // Gerar link wa.me para verificação visual pelo cliente
        $cleanDigits = preg_replace('/[^0-9]/', '', $e164);
        $wameLink = 'https://wa.me/' . $cleanDigits;
        
        $this->json([
            'ok' => true,
            'valid' => true,
            'checked' => $whatsappCheck['checked'],
            'exists' => $whatsappCheck['exists'] ?? true,
            'whatsapp_warning' => $whatsappInvalid ? 'Não identificamos este número no WhatsApp. Verifique se está correto.' : null,
            'wame_link' => $whatsappInvalid ? $wameLink : null,
            'customer' => $customer ? [
                'id' => (int)$customer['id'],
                'name' => $customer['name'] ?? '',
                'lgpd_accepted' => !empty($customer['lgpd_consent_at'])
            ] : null
        ]);
    }

    /**
     * Utilitário para responder JSON (caso seu Controller base não tenha).
     */
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}
