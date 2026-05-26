<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

// Serviço específico
require_once __DIR__ . '/../services/SmartCache.php';
require_once __DIR__ . '/../services/WhatsAppValidator.php';
require_once __DIR__ . '/../services/CustomerEngagementService.php';
require_once __DIR__ . '/../services/WhatsAppSendService.php';

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

    private function otpCacheKey(int $companyId, string $e164): string
    {
        return 'customer_login_otp:' . $companyId . ':' . $e164;
    }

    private function otpAttemptsKey(int $companyId, string $e164): string
    {
        return 'customer_login_otp_attempts:' . $companyId . ':' . $e164;
    }

    private function generateOtpCode(): string
    {
        return (string) random_int(100000, 999999);
    }

    private function getCompanyOtpInstanceName(int $companyId): ?string
    {
        $instances = EvolutionInstance::allForCompany($companyId);
        if (empty($instances)) {
            return null;
        }

        $instanceName = (string)($instances[0]['instance_identifier'] ?? '');
        return $instanceName !== '' ? $instanceName : null;
    }

    private function buildOtpMessage(string $name, string $code): string
    {
        $recipient = trim($name) !== '' ? $name : 'cliente';
        return "Olá, {$recipient}! Seu código de acesso ao MultiMenu é {$code}. Ele expira em 10 minutos.";
    }

    private function storeOtpChallenge(int $companyId, string $e164, array $payload): void
    {
        SmartCache::set($this->otpCacheKey($companyId, $e164), $payload, 600);
    }

    private function loadOtpChallenge(int $companyId, string $e164): ?array
    {
        $challenge = SmartCache::get($this->otpCacheKey($companyId, $e164));
        return is_array($challenge) ? $challenge : null;
    }

    private function clearOtpChallenge(int $companyId, string $e164): void
    {
        SmartCache::forget($this->otpCacheKey($companyId, $e164));
        SmartCache::forget($this->otpAttemptsKey($companyId, $e164));
    }

    private function sendLoginOtp(array $company, string $name, string $e164): bool
    {
        $companyId = (int)($company['id'] ?? 0);
        $instanceName = $this->getCompanyOtpInstanceName($companyId);

        if (!$instanceName) {
            return false;
        }

        $code = $this->generateOtpCode();
        $challenge = [
            'code_hash' => password_hash($code, PASSWORD_DEFAULT),
            'name' => $name,
            'whatsapp' => $e164,
            'created_at' => time(),
            'company_id' => $companyId,
        ];
        $this->storeOtpChallenge($companyId, $e164, $challenge);

        $message = $this->buildOtpMessage($name, $code);
        $sendService = app_container()->get('whatsapp.send');
        $sendResult = $sendService->sendOnce(
            $companyId,
            $instanceName,
            $e164,
            $message,
            'notification',
            [
                'checkTakeover' => false,
                'checkRateLimit' => false,
                'enqueueOnFail' => false,
                'delay' => 0,
            ]
        );

        if (empty($sendResult['success'])) {
            $this->clearOtpChallenge($companyId, $e164);
            return false;
        }

        return true;
    }

    private function completeCustomerLogin(array $company, string $slug, string $name, string $whatsRaw, string $e164, array $redirectPayload): void
    {
        $companyId = (int)($company['id'] ?? 0);
        $now = date('Y-m-d H:i:s');

        $customer = Customer::findByCompanyAndE164($companyId, $e164);
        $lgpdConsent = !empty($redirectPayload['lgpd_consent']);
        $alreadyConsented = $customer && !empty($customer['lgpd_consent_at']);
        $lgpdFields = ($lgpdConsent && !$alreadyConsented) ? [
            'lgpd_consent_at' => $now,
            'lgpd_consent_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ] : [];

        if (!$customer) {
            if (!$lgpdConsent) {
                $this->json(['ok' => false, 'message' => 'Você precisa aceitar os termos de privacidade para se cadastrar.'], 400);
                return;
            }

            $id = Customer::insert(array_merge([
                'company_id' => $companyId,
                'name' => $name,
                'whatsapp' => $whatsRaw,
                'whatsapp_e164' => $e164,
                'created_at' => $now,
                'updated_at' => $now,
                'last_login_at' => $now,
            ], $lgpdFields));

            $customer = Customer::findById((int)$id, $companyId);

            try {
                $engagement = app_container()->get('customer.engagement', $companyId);
                $engagement->scheduleSignupNoOrderMessage((int)$id, $e164, $name);
            } catch (\Throwable $e) {
                error_log('[CustomerAuth] Falha ao agendar cenário 1: ' . $e->getMessage());
            }
        } else {
            Customer::updateById((int)$customer['id'], array_merge([
                'name' => $name,
                'whatsapp' => $whatsRaw,
                'updated_at' => $now,
                'last_login_at' => $now,
            ], $lgpdFields));

            $customer = Customer::findById((int)$customer['id'], $companyId);
        }

        if (!$customer) {
            $this->json(['ok' => false, 'message' => 'Não foi possível concluir o login.'], 500);
            return;
        }

        $storage = CartStorage::instance();
        $cartBeforeLogin = $storage->getCart();
        $customsBeforeLogin = $storage->getCustomizations();

        if (function_exists('initialize_customer_session')) {
            initialize_customer_session($customer, $companyId);
        } else {
            session_regenerate_id(true);
        }

        if (!empty($cartBeforeLogin) && class_exists('CartStorage')) {
            $_SESSION['cart'] = $cartBeforeLogin;
            $_SESSION['customizations'] = $customsBeforeLogin;
            CartStorage::instance()->setCart($cartBeforeLogin);
        }

        Auth::loginCustomer([
            'id' => (int)$customer['id'],
            'name' => $customer['name'],
            'whatsapp' => $customer['whatsapp'],
            'e164' => $customer['whatsapp_e164'],
            'company_id' => $companyId,
            'company_slug' => $slug,
            'login_at' => $now,
        ]);

        if (function_exists('log_security_event')) {
            log_security_event('customer_login_success', [
                'customer_id' => (int)$customer['id'],
                'customer_name' => $customer['name'],
                'company_slug' => $slug,
                'via' => 'otp',
            ]);
        }

        setcookie('mm_customer_e164', $customer['whatsapp_e164'], [
            'expires' => time() + 60 * 60 * 24 * 365,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $defaultRedirect = base_url(rawurlencode($slug));
        $redirectTarget = trim((string)($redirectPayload['redirect_to'] ?? ''));
        $redirectUrl = $defaultRedirect;

        if ($redirectTarget !== '') {
            if ($redirectTarget[0] === '/') {
                $redirectUrl = base_url(ltrim($redirectTarget, '/'));
            } elseif (preg_match('~^https?://~i', $redirectTarget)) {
                $parsed = parse_url($redirectTarget);
                $host = $_SERVER['HTTP_HOST'] ?? '';

                if (!empty($parsed['host']) && strcasecmp($parsed['host'], $host) === 0) {
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
            return;
        }

        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * POST /{slug}/customer-login
     * Campos: name, whatsapp
     */
    public function login(array $params): void
    {
        // garante sessão
        AuthCustomer::start();
        
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

        $companyId = (int)$company['id'];
        $otpCode = trim((string)($_POST['otp'] ?? ''));
        $pendingChallenge = $this->loadOtpChallenge($companyId, $e164);

        if ($otpCode === '') {
            if ($pendingChallenge && !empty($pendingChallenge['code_hash'])) {
                $this->json([
                    'ok' => false,
                    'otp_required' => true,
                    'message' => 'Enviamos um código para o seu WhatsApp. Informe o código para concluir o login.'
                ], 200);
                return;
            }

            // Se não há WhatsApp configurado/conectado para enviar o OTP, completa o
            // login diretamente em vez de bloquear o cliente. O admin é notificado
            // sobre a desconexão pelo painel; bloquear todo login do site quando
            // a Evolution está offline é pior que prosseguir sem OTP.
            if (!$this->sendLoginOtp($company, $name, $e164)) {
                error_log(sprintf(
                    '[CustomerAuth] OTP indisponível para company_id=%d (%s) — concluindo login sem OTP.',
                    $companyId,
                    $slug
                ));

                $this->completeCustomerLogin($company, $slug, $name, $whatsRaw, $e164, [
                    'redirect_to' => $_POST['redirect_to'] ?? '',
                    'lgpd_consent' => !empty($_POST['lgpd_consent']),
                ]);
                return;
            }

            $this->json([
                'ok' => true,
                'otp_required' => true,
                'message' => 'Enviamos um código para o seu WhatsApp. Informe o código para concluir o login.'
            ]);
            return;
        }

        if (!$pendingChallenge || empty($pendingChallenge['code_hash'])) {
            $this->json(['ok' => false, 'message' => 'Código expirado. Solicite um novo código de acesso.'], 400);
            return;
        }

        // Contador atômico — Redis INCR garante que requisições concorrentes não burlem o limite.
        $attempts = SmartCache::atomicIncrement($this->otpAttemptsKey($companyId, $e164), 600);
        if ($attempts > 5) {
            $this->clearOtpChallenge($companyId, $e164);
            $this->json(['ok' => false, 'message' => 'Muitas tentativas. Solicite um novo código.'], 429);
            return;
        }

        if (!password_verify($otpCode, (string)$pendingChallenge['code_hash'])) {
            $this->json(['ok' => false, 'message' => 'Código inválido.'], 400);
            return;
        }

        $this->clearOtpChallenge($companyId, $e164);

        // Validar se o número existe no WhatsApp (se Evolution estiver conectada)
        // NOTA: Nunca bloquear login por falha na validação — APIs externas podem dar falsos negativos
        $whatsappCheck = WhatsAppValidator::validate($company, $e164);

        $this->completeCustomerLogin($company, $slug, $name, $whatsRaw, $e164, [
            'redirect_to' => $_POST['redirect_to'] ?? '',
            'lgpd_consent' => !empty($_POST['lgpd_consent']),
        ]);
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
            Auth::logoutCustomer();
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
        $c = Auth::customer();
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
