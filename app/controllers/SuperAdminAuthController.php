<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

class SuperAdminAuthController extends Controller
{
    private const FLASH_KEY = 'superadmin_flash';

    /** GET /superadmin/login */
    public function showLogin(array $params): void
    {
        SessionManager::start();

        if (Auth::checkSuperAdmin()) {
            header('Location: ' . base_url('superadmin'));
            exit;
        }

        $flash = $_SESSION[self::FLASH_KEY] ?? null;
        unset($_SESSION[self::FLASH_KEY]);

        $this->view('super-admin/login', [
            'title' => 'Super Admin — Login',
            'flash' => $flash,
            'rateLimited' => false,
        ]);
    }

    /** POST /superadmin/login */
    public function login(array $params): void
    {
        SessionManager::start();

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!\App\Middleware\RateLimiter::check('superadmin_login_' . $ip, 5, 900)) {
            $this->view('super-admin/login', [
                'title' => 'Super Admin — Login',
                'flash' => [
                    'type' => 'error',
                    'message' => 'Muitas tentativas. Aguarde até 15 minutos e tente novamente.',
                ],
                'rateLimited' => true,
            ]);

            return;
        }

        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $_SESSION[self::FLASH_KEY] = ['type' => 'error', 'message' => 'Informe email e senha.'];
            header('Location: ' . base_url('superadmin/login'));
            exit;
        }

        $row = SuperAdmin::findByEmail($email);

        if (!$row || empty($row['active']) || !password_verify($password, (string)$row['password_hash'])) {
            $_SESSION[self::FLASH_KEY] = ['type' => 'error', 'message' => 'Credenciais inválidas.'];
            header('Location: ' . base_url('superadmin/login'));
            exit;
        }

        session_regenerate_id(true);
        Auth::loginSuperAdmin([
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'role' => 'root',
        ]);

        SuperAdmin::touchLastLogin((int)$row['id']);

        header('Location: ' . base_url('superadmin'));
        exit;
    }

    /** POST /superadmin/logout */
    public function logout(array $params): void
    {
        SessionManager::start();

        Auth::logoutSuperAdmin();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION[self::FLASH_KEY] = ['type' => 'success', 'message' => 'Sessão encerrada.'];

        header('Location: ' . base_url('superadmin/login'));
        exit;
    }
}
