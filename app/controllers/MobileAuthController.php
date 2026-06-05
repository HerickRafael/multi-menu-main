<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado (db(), models autoloaded, helpers)
require_once __DIR__ . '/../bootstrap.php';

// Middleware de segurança (apenas para gerar/assinar o JWT — login é público)
require_once __DIR__ . '/../middleware/ApiSecurity.php';

// Model de refresh tokens (não está no autoload do bootstrap)
require_once __DIR__ . '/../models/MobileRefreshToken.php';

use App\Middleware\ApiSecurity;

/**
 * Autenticação do app mobile (Flutter).
 *
 * Endpoint público — NÃO herda o ApiController (cujo construtor exige token).
 *
 * Rotas:
 *   POST /api/{slug}/auth/login    — e-mail/senha → JWT + refresh token
 *   POST /api/{slug}/auth/refresh  — refresh token → novo JWT + novo refresh (rotação)
 *   POST /api/{slug}/auth/logout   — invalida o refresh token do device
 */
class MobileAuthController
{
    private const ACCESS_TTL = 3600; // 1h, alinhado ao jwt_expiration do ApiSecurity

    private ApiSecurity $apiSecurity;

    public function __construct()
    {
        if (!class_exists('SecurityRequirements', false)) {
            require_once __DIR__ . '/../config/SecurityRequirements.php';
        }

        // require_auth=false: aqui só usamos generateJwt(), sem autenticar a requisição.
        $this->apiSecurity = new ApiSecurity([
            'require_auth' => false,
            'jwt_secret'   => SecurityRequirements::resolveJwtSecret(),
            'cors_enabled' => true,
            'log_requests' => false,
        ], db());
    }

    /**
     * POST /api/{slug}/auth/login
     * Body: { "email": "...", "password": "..." }
     */
    public function login(array $params = []): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->sendError('Método não permitido. Use POST.', 405);
            return;
        }

        // Credenciais primeiro — a loja é resolvida a partir do usuário.
        // Modelo "admin loja": o login criado em uma loja entra naquela loja.
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $this->sendError('Dados JSON inválidos', 400);
            return;
        }

        $email = trim((string) ($input['email'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->sendError('E-mail e senha são obrigatórios', 400);
            return;
        }

        $user = User::findByEmail($email);

        // Mensagem genérica para não revelar se o e-mail existe.
        if (!$user || !password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            $this->sendError('Credenciais inválidas', 401);
            return;
        }

        if ((int) ($user['active'] ?? 0) !== 1) {
            $this->sendError('Usuário inativo', 403);
            return;
        }

        // Resolve a loja. Regra do admin loja: root acessa qualquer loja;
        // demais usuários acessam apenas a loja onde foram cadastrados.
        $isRoot = ($user['role'] ?? '') === 'root';
        $slug = trim((string) ($input['slug'] ?? $params['slug'] ?? ''));

        if ($slug !== '') {
            // Loja informada explicitamente (ex.: root escolhendo a loja).
            $company = Company::findBySlug($slug);
            if (!$company || (int) ($company['active'] ?? 0) !== 1) {
                $this->sendError('Empresa não encontrada ou inativa', 404);
                return;
            }
            if (!$isRoot && (int) ($user['company_id'] ?? 0) !== (int) $company['id']) {
                $this->sendError('Usuário sem acesso a esta loja', 403);
                return;
            }
        } else {
            // Sem slug: resolve a loja pelo cadastro do usuário (caso do app).
            if ($isRoot || (int) ($user['company_id'] ?? 0) === 0) {
                $this->sendError('Informe a loja (slug) para este usuário', 400);
                return;
            }
            $company = Company::find((int) $user['company_id']);
            if (!$company || (int) ($company['active'] ?? 0) !== 1) {
                $this->sendError('Loja do usuário inativa ou não encontrada', 403);
                return;
            }
        }

        $companyId = (int) $company['id'];

        // 1) Access token JWT — payload compatível com o ApiSecurity (sub = user_id).
        $token = $this->apiSecurity->generateJwt([
            'sub'            => (int) $user['id'],
            'scopes'         => ['read', 'write'],
            'company_access' => $company['slug'] ?? '',
            'company_id'     => $companyId,
            'role'           => $user['role'] ?? null,
        ], self::ACCESS_TTL);

        // 2) Refresh token (persistido como hash).
        $refresh = MobileRefreshToken::issue((int) $user['id'], $companyId, [
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'ip_address' => $this->clientIp(),
        ]);

        $this->sendResponse([
            'token'         => $token,
            'token_type'    => 'Bearer',
            'expires_in'    => self::ACCESS_TTL,
            'refresh_token' => $refresh['token'],
            'user'          => [
                'id'    => (int) $user['id'],
                'name'  => $user['name'] ?? null,
                'email' => $user['email'] ?? null,
                'role'  => $user['role'] ?? null,
            ],
            'company'       => [
                'id'    => (int) $company['id'],
                'slug'  => $company['slug'] ?? '',
                'name'  => $company['name'] ?? null,
                'theme' => Company::themeColors($company),
            ],
        ]);
    }

    /**
     * POST /api/{slug}/auth/refresh
     * Body: { "refresh_token": "..." }
     *
     * Rotação segura: invalida o token recebido e emite um novo par (JWT + refresh).
     * Usar o mesmo refresh token duas vezes retorna 401 (token já revogado).
     */
    public function refresh(array $params = []): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->sendError('Método não permitido. Use POST.', 405);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $plainToken = trim((string) ($input['refresh_token'] ?? ''));

        if ($plainToken === '') {
            $this->sendError('refresh_token é obrigatório', 400);
            return;
        }

        // Rotaciona: valida, revoga o atual e emite um novo.
        $new = MobileRefreshToken::rotate($plainToken, [
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'ip_address' => $this->clientIp(),
        ]);

        if (!$new) {
            // Token inexistente, expirado ou já revogado — não distinguir para o cliente.
            $this->sendError('Refresh token inválido ou expirado', 401);
            return;
        }

        // Busca dados do usuário/empresa para emitir o novo JWT.
        $record = MobileRefreshToken::findValidByToken($new['token']);
        if (!$record) {
            // Situação inesperada: acabou de ser emitido mas não encontrou — falha segura.
            $this->sendError('Erro ao emitir novo token', 500);
            return;
        }

        $user = User::findById((int) $record['user_id']);
        if (!$user || (int) ($user['active'] ?? 0) !== 1) {
            MobileRefreshToken::revokeByToken($new['token']);
            $this->sendError('Usuário inativo ou não encontrado', 403);
            return;
        }

        $company = Company::find((int) $record['company_id']);
        if (!$company || (int) ($company['active'] ?? 0) !== 1) {
            MobileRefreshToken::revokeByToken($new['token']);
            $this->sendError('Empresa inativa ou não encontrada', 403);
            return;
        }

        $token = $this->apiSecurity->generateJwt([
            'sub'            => (int) $user['id'],
            'scopes'         => ['read', 'write'],
            'company_access' => $company['slug'] ?? '',
            'company_id'     => (int) $record['company_id'],
            'role'           => $user['role'] ?? null,
        ], self::ACCESS_TTL);

        $this->sendResponse([
            'token'         => $token,
            'token_type'    => 'Bearer',
            'expires_in'    => self::ACCESS_TTL,
            'refresh_token' => $new['token'],
        ]);
    }

    /**
     * POST /api/{slug}/auth/logout
     * Body: { "refresh_token": "..." }
     *
     * Revoga o refresh token do device atual.
     * Sempre retorna 200 (não confirmar se o token existia — evita enumeração).
     */
    public function logout(array $params = []): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->sendError('Método não permitido. Use POST.', 405);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $plainToken = trim((string) ($input['refresh_token'] ?? ''));

        if ($plainToken !== '') {
            MobileRefreshToken::revokeByToken($plainToken);
        }

        // Resposta sempre 200 para não revelar se o token era válido.
        $this->sendResponse(['message' => 'Logout realizado com sucesso']);
    }

    private function clientIp(): ?string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        if ($ip && str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }

        return $ip ? substr((string) $ip, 0, 45) : null;
    }

    /** Envelope de sucesso — idêntico ao usado pelo ApiController. */
    private function sendResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success'   => true,
            'data'      => $data,
            'timestamp' => date('c'),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Envelope de erro — idêntico ao usado pelo ApiController. */
    private function sendError(string $message, int $status = 400): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success'   => false,
            'error'     => ['message' => $message, 'code' => $status],
            'timestamp' => date('c'),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
