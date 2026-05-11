<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

/**
 * MobileAdminAuthController
 * 
 * Autenticação mobile com UI otimizada para toque.
 */
class MobileAdminAuthController extends Controller
{
    /**
     * GET /login
     * Formulário de login mobile
     */
    public function loginForm(array $params = [])
    {
        Auth::start();

        // Se já logado, redireciona para dashboard
        if (Auth::checkAdmin()) {
            header('Location: /dashboard');
            exit;
        }

        $slug = $_SERVER['MOBILE_SLUG'] ?? 'wollburger';
        $company = Company::findBySlug($slug);

        if (!$company) {
            http_response_code(404);
            echo 'Empresa não encontrada';
            exit;
        }

        $error = $_GET['error'] ?? null;
        $pageTitle = 'Login';
        $hideBottomNav = true;

        return $this->viewMobile('auth/login', compact(
            'company',
            'error',
            'pageTitle',
            'hideBottomNav'
        ));
    }

    /**
     * POST /login
     * Processa login
     */
    public function login(array $params = [])
    {
        Auth::start();

        $slug = $_SERVER['MOBILE_SLUG'] ?? 'wollburger';
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            header('Location: /login?error=empty');
            exit;
        }

        $company = Company::findBySlug($slug);
        if (!$company) {
            header('Location: /login?error=company');
            exit;
        }

        // Tenta login
        $user = User::findByEmail($email);

        if (!$user) {
            header('Location: /login?error=credentials');
            exit;
        }

        // Verifica senha (campo é password_hash no banco)
        if (!password_verify($password, $user['password_hash'])) {
            header('Location: /login?error=credentials');
            exit;
        }

        // Verifica se é admin e pertence à empresa (ou é root)
        $isRoot = $user['role'] === 'root';
        $isAdmin = in_array($user['role'], ['root', 'owner']);
        
        if (!$isAdmin) {
            header('Location: /login?error=permission');
            exit;
        }

        if (!$isRoot && (int)$user['company_id'] !== (int)$company['id']) {
            header('Location: /login?error=permission');
            exit;
        }

        // Login bem-sucedido
        Auth::login($user);
        Auth::setActiveCompany((int)$company['id'], $slug);

        header('Location: /dashboard');
        exit;
    }

    /**
     * GET /logout
     * Faz logout
     */
    public function logout(array $params = [])
    {
        Auth::start();
        Auth::logout();
        
        header('Location: /login');
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
