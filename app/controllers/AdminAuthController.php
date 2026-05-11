<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

class AdminAuthController extends Controller
{
    /** GET /admin/{slug}/login */
    public function loginForm(array $params)
    {
        Auth::start();

        $slug = trim((string)($params['slug'] ?? ''));

        if ($slug === '') {
            http_response_code(400);
            echo 'Slug inválido';

            return;
        }

        $company = Company::findBySlug($slug);

        if (!$company || empty($company['active'])) {
            http_response_code(404);
            echo 'Empresa inválida ou inativa';

            return;
        }

        // Se já estiver logado e o contexto for desta empresa, redireciona
        $u = Auth::user();

        if ($u && self::sessionCompanyIdMatches($company['id'])) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/dashboard'));
            exit;
        }

        // Recupera flash message (erro de tentativa anterior)
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        return $this->view('admin/auth/login', compact('company', 'error'));
    }

    /** POST /admin/{slug}/login */
    public function login(array $params)
    {
        Auth::start();

        $slug = trim((string)($params['slug'] ?? ''));

        if ($slug === '') {
            http_response_code(400);
            echo 'Slug inválido';

            return;
        }

        $company = Company::findBySlug($slug);

        if (!$company || empty($company['active'])) {
            http_response_code(404);
            echo 'Empresa inválida ou inativa';

            return;
        }

        // Sanitização básica
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $pass  = (string)($_POST['password'] ?? '');

        if ($email === '' || $pass === '') {
            $_SESSION['flash_error'] = 'Informe e-mail e senha.';
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
            exit;
        }

        $user = User::findByEmail($email);

        // (Opcional) negar usuários inativos, se existir coluna (ex.: $user['active'])
        // if (!$user || empty($user['active'])) { ... }

        if ($user && password_verify($pass, $user['password_hash'])) {
            // Autorização por escopo de empresa:
            $isRoot = ($user['role'] === 'root');

            // Regra: root pode entrar em qualquer empresa; demais só na própria
            $canAccess =
              $isRoot ||
              ((int)$user['company_id'] === (int)$company['id']);

            if ($canAccess) {
                // Faz login do usuário
                Auth::login($user);

                // Define/atualiza o CONTEXTO de empresa ativa na sessão.
                // Isso é importante quando o root loga: user.company_id pode ser null.
                $_SESSION['active_company_id'] = (int)$company['id'];
                $_SESSION['active_company_slug'] = $slug;

                // PRG: redireciona para evitar reenvio de POST
                header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/dashboard'));
                exit;
            }
        }

        // Falha: define flash e volta pro form
        $_SESSION['flash_error'] = 'Credenciais inválidas';
        header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
        exit;
    }

    /** GET /admin/{slug}/logout */
    public function logout(array $params)
    {
        Auth::start();

        $slug = trim((string)($params['slug'] ?? ''));

        if ($slug === '') {
            $slug = 'login';
        } // fallback

        Auth::logout();

        // limpa também o contexto de empresa ativa
        unset($_SESSION['active_company_id'], $_SESSION['active_company_slug']);

        header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
        exit;
    }

    /** Helper: confere se a sessão está no contexto desta empresa */
    private static function sessionCompanyIdMatches(int $companyId): bool
    {
        // Se você usa Auth::companyId() para pegar o company_id do usuário
        // e usa 'active_company_id' para o CONTEXTO (root),
        // priorize 'active_company_id' para a checagem.
        $active = $_SESSION['active_company_id'] ?? null;

        if ($active !== null) {
            return ((int)$active === (int)$companyId);
        }
        // fallback: company_id do próprio usuário
        $u = Auth::user();

        if (!$u) {
            return false;
        }

        return ((int)($u['company_id'] ?? 0) === (int)$companyId);
    }
}
