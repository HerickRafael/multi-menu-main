<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

// Middleware específico
require_once __DIR__ . '/../middleware/ApiSecurity.php';

use App\Middleware\ApiSecurity;

/**
 * Admin API Controller
 * Gerencia tokens e chaves de API através do painel administrativo
 */
class AdminApiController extends Controller
{
    private ApiSecurity $apiSecurity;

    public function __construct()
    {
        // Inicializa o middleware de segurança da API
        $this->apiSecurity = new ApiSecurity([
            'jwt_secret' => $_ENV['JWT_SECRET'] ?? 'multi-menu-api-secret-change-in-production',
            'enforce_https' => false, // Desenvolvimento
        ], db());
    }

    private function guard($slug)
    {
        Auth::start();
        $u = Auth::user();

        if (!$u) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
            exit;
        }
        $company = Company::findBySlug($slug);
        if (!$company) {
            http_response_code(404);
            echo "Empresa não encontrada";
            exit;
        }
        return [$u, $company];
    }

    /**
     * Página principal da API no admin
     */
    public function index($params): void
    {
        $slug = $params['slug'] ?? '';
        [$user, $company] = $this->guard($slug);

        // Buscar tokens e chaves existentes
        $apiData = $this->getApiData((int)$user['id']);

        // Estatísticas da API
        $stats = $this->getApiStats((int)$company['id']);

        $decodeScopes = static function ($v): array {
            if (is_array($v)) return array_values(array_map('strval', $v));
            if (is_string($v) && $v !== '') {
                $d = json_decode($v, true);
                if (is_array($d)) return array_values(array_map('strval', $d));
                return array_values(array_filter(array_map('trim', explode(',', $v))));
            }
            return [];
        };

        $tokens = array_map(static function (array $t) use ($decodeScopes): array {
            return [
                'id' => (int)($t['id'] ?? 0),
                'access_token' => (string)($t['access_token'] ?? ''),
                'jwt_preview' => isset($t['jwt_raw']) && $t['jwt_raw']
                    ? substr((string)$t['jwt_raw'], 0, 32) . '...'
                    : null,
                'scopes' => $decodeScopes($t['scopes'] ?? []),
                'expires_at' => $t['expires_at'] ?? null,
                'created_at' => $t['created_at'] ?? null,
            ];
        }, $apiData['tokens'] ?? []);

        $apiKeys = array_map(static function (array $k) use ($decodeScopes): array {
            return [
                'id' => (int)($k['id'] ?? 0),
                'name' => (string)($k['name'] ?? ''),
                'key_preview' => isset($k['key_hash']) && $k['key_hash']
                    ? substr((string)$k['key_hash'], 0, 16) . '...'
                    : null,
                'scopes' => $decodeScopes($k['scopes'] ?? []),
                'expires_at' => $k['expires_at'] ?? null,
                'created_at' => $k['created_at'] ?? null,
                'is_active' => (int)($k['is_active'] ?? 1) === 1,
                'revoked_at' => $k['revoked_at'] ?? null,
            ];
        }, $apiData['api_keys'] ?? []);

        $payload = [
            'company_name' => (string)($company['name'] ?? ''),
            'user_name' => (string)($user['name'] ?? ''),
            'tokens' => $tokens,
            'api_keys' => $apiKeys,
            'stats' => [
                'requests_today' => (int)($stats['requests_today'] ?? 0),
                'total_requests' => (int)($stats['total_requests'] ?? 0),
                'top_endpoints' => array_map(static function (array $e): array {
                    return [
                        'endpoint' => (string)($e['endpoint'] ?? ''),
                        'count' => (int)($e['count'] ?? 0),
                    ];
                }, $stats['top_endpoints'] ?? []),
            ],
            'endpoints' => array_map(static function (array $e): array {
                return [
                    'method' => (string)($e['method'] ?? 'GET'),
                    'path' => (string)($e['path'] ?? ''),
                    'description' => (string)($e['description'] ?? ''),
                ];
            }, $this->getEndpointsList()),
            'base_url' => base_url('api'),
            'urls' => [
                'generate_token' => '/admin/' . rawurlencode($slug) . '/api/generate-token',
                'revoke_token' => '/admin/' . rawurlencode($slug) . '/api/revoke-token',
                'generate_key' => '/admin/' . rawurlencode($slug) . '/api/generate-key',
                'revoke_key' => '/admin/' . rawurlencode($slug) . '/api/revoke-key',
                'dashboard' => '/admin/' . rawurlencode($slug) . '/dashboard',
            ],
        ];

        \App\Services\AdminStoreSpaRenderer::render($slug, $company, '__ADMIN_STORE_API__', $payload);
    }

    /**
     * Gerar novo JWT token
     */
    public function generateToken($params): void
    {
        $slug = $params['slug'] ?? '';
        [$user, $company] = $this->guard($slug);

        try {
            // Dados do POST
            $input = json_decode(file_get_contents('php://input'), true);
            $expiresIn = (int)($input['expires_in'] ?? 86400); // 24h padrão
            $scopes = $input['scopes'] ?? ['read', 'write'];

            // Gerar token
            $token = $this->apiSecurity->generateJwt([
                'sub' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'scopes' => $scopes,
                'company_access' => $slug
            ], $expiresIn);

            // Salvar no banco para controle
            $this->saveTokenRecord($user['id'], $token, $expiresIn, $scopes);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Token gerado com sucesso',
                'data' => [
                    'token' => $token,
                    'expires_in' => $expiresIn,
                    'scopes' => $scopes
                ]
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Erro ao gerar token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revogar JWT token
     */
    public function revokeToken($params): void
    {
        $slug = $params['slug'] ?? '';
        [$user, $company] = $this->guard($slug);

        $input = json_decode(file_get_contents('php://input'), true);
        $tokenId = $input['token_id'] ?? null;

        if (!$tokenId) {
            $this->jsonResponse(['success' => false, 'message' => 'ID do token não fornecido'], 400);
            return;
        }

        try {
            $db = db();
            $stmt = $db->prepare('DELETE FROM oauth_tokens WHERE id = ? AND user_id = ?');
            $result = $stmt->execute([$tokenId, $user['id']]);

            if ($result && $stmt->rowCount() > 0) {
                $this->jsonResponse(['success' => true, 'message' => 'Token revogado com sucesso']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Token não encontrado'], 404);
            }

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Erro ao revogar token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gerar nova API Key
     */
    public function generateApiKey($params): void
    {
        $slug = $params['slug'] ?? '';
        [$user, $company] = $this->guard($slug);

        $input = json_decode(file_get_contents('php://input'), true);
        
        $name = $input['name'] ?? 'API Key - ' . date('Y-m-d H:i:s');
        $scopes = $input['scopes'] ?? ['read'];
        $expiresAt = $input['expires_at'] ?? null;

        try {
            $apiKey = $this->apiSecurity->generateApiKey(
                $user['id'],
                $name,
                $scopes,
                $expiresAt
            );

            $this->jsonResponse([
                'success' => true,
                'message' => 'API Key gerada com sucesso',
                'data' => [
                    'api_key' => $apiKey,
                    'name' => $name,
                    'scopes' => $scopes
                ]
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Erro ao gerar API Key: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revogar API Key
     */
    public function revokeApiKey($params): void
    {
        $slug = $params['slug'] ?? '';
        [$user, $company] = $this->guard($slug);

        $input = json_decode(file_get_contents('php://input'), true);
        $keyId = $input['key_id'] ?? null;

        if (!$keyId) {
            $this->jsonResponse(['success' => false, 'message' => 'ID da chave não fornecido'], 400);
            return;
        }

        try {
            $db = db();
            $stmt = $db->prepare('UPDATE api_keys SET is_active = 0, revoked_at = NOW() WHERE id = ? AND user_id = ?');
            $result = $stmt->execute([$keyId, $user['id']]);

            if ($result && $stmt->rowCount() > 0) {
                $this->jsonResponse(['success' => true, 'message' => 'API Key revogada com sucesso']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'API Key não encontrada'], 404);
            }

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Erro ao revogar API Key: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar dados da API do usuário
     */
    private function getApiData(int $userId): array
    {
        $db = db();
        
        try {
            // Verificar se as tabelas existem
            $checkApiKeys = $db->query("SHOW TABLES LIKE 'api_keys'");
            $checkTokens = $db->query("SHOW TABLES LIKE 'oauth_tokens'");
            
            $apiKeysExist = $checkApiKeys->rowCount() > 0;
            $tokensExist = $checkTokens->rowCount() > 0;
            
            // Buscar tokens JWT
            $tokens = [];
            if ($tokensExist) {
                $tokensStmt = $db->prepare('
                    SELECT id, access_token, jwt_raw, scopes, expires_at, created_at
                    FROM oauth_tokens 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 10
                ');
                $tokensStmt->execute([$userId]);
                $tokens = $tokensStmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Buscar API Keys
            $apiKeys = [];
            if ($apiKeysExist) {
                $keysStmt = $db->prepare('
                    SELECT id, key_hash, name, scopes, expires_at, created_at, is_active, revoked_at
                    FROM api_keys 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 10
                ');
                $keysStmt->execute([$userId]);
                $apiKeys = $keysStmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return [
                'tokens' => $tokens,
                'api_keys' => $apiKeys
            ];
            
        } catch (Exception $e) {
            return [
                'tokens' => [],
                'api_keys' => []
            ];
        }
    }

    /**
     * Estatísticas da API
     */
    private function getApiStats(int $companyId): array
    {
        $db = db();
        
        try {
            // Verificar se a tabela api_requests existe
            $checkTable = $db->query("SHOW TABLES LIKE 'api_requests'");
            $tableExists = $checkTable->rowCount() > 0;
            
            if (!$tableExists) {
                return [
                    'requests_today' => 0,
                    'total_requests' => 0,
                    'top_endpoints' => []
                ];
            }
            
            // Requisições hoje
            $todayStmt = $db->prepare('
                SELECT COUNT(*) as count
                FROM api_requests 
                WHERE DATE(created_at) = CURDATE()
            ');
            $todayStmt->execute();
            $requestsToday = $todayStmt->fetchColumn();

            // Total de requisições
            $totalStmt = $db->prepare('SELECT COUNT(*) FROM api_requests');
            $totalStmt->execute();
            $totalRequests = $totalStmt->fetchColumn();

            // Endpoints mais usados
            $endpointsStmt = $db->prepare('
                SELECT endpoint, COUNT(*) as count
                FROM api_requests 
                WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY endpoint 
                ORDER BY count DESC 
                LIMIT 5
            ');
            $endpointsStmt->execute();
            $topEndpoints = $endpointsStmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'requests_today' => $requestsToday,
                'total_requests' => $totalRequests,
                'top_endpoints' => $topEndpoints
            ];
            
        } catch (Exception $e) {
            // Se houver erro, retorna dados vazios
            return [
                'requests_today' => 0,
                'total_requests' => 0,
                'top_endpoints' => []
            ];
        }
    }

    /**
     * Lista de endpoints disponíveis
     */
    private function getEndpointsList(): array
    {
        return [
            ['method' => 'GET', 'path' => '/api/{slug}', 'description' => 'Informações da empresa'],
            ['method' => 'GET', 'path' => '/api/{slug}/stats', 'description' => 'Estatísticas da empresa'],
            ['method' => 'GET', 'path' => '/api/{slug}/categories', 'description' => 'Lista categorias'],
            ['method' => 'GET', 'path' => '/api/{slug}/products', 'description' => 'Lista produtos'],
            ['method' => 'GET', 'path' => '/api/{slug}/products/{id}', 'description' => 'Detalhes do produto'],
            ['method' => 'GET', 'path' => '/api/{slug}/orders', 'description' => 'Lista pedidos'],
            ['method' => 'GET', 'path' => '/api/{slug}/orders/{id}', 'description' => 'Detalhes do pedido'],
            ['method' => 'POST', 'path' => '/api/{slug}/orders', 'description' => 'Criar novo pedido'],
            ['method' => 'POST', 'path' => '/api/{slug}/orders/{id}/status', 'description' => 'Atualizar status do pedido'],
            ['method' => 'POST', 'path' => '/api/{slug}/token', 'description' => 'Gerar novo JWT token']
        ];
    }

    /**
     * Salvar registro do token no banco
     */
    private function saveTokenRecord(int $userId, string $token, int $expiresIn, array $scopes): void
    {
        $db = db();
        $stmt = $db->prepare('
            INSERT INTO oauth_tokens (user_id, client_id, access_token, jwt_raw, scopes, expires_at, created_at) 
            VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), NOW())
        ');
        $stmt->execute([
            $userId,
            'admin-dashboard',
            hash('sha256', $token),
            $token,
            json_encode($scopes),
            $expiresIn
        ]);
    }

    /**
     * Resposta JSON helper
     */
    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Buscar cliente por telefone (para formulário de novo pedido)
     */
    public function searchCustomerByPhone($params): void
    {
        $slug = $params['slug'] ?? '';
        [$user, $company] = $this->guard($slug);

        $phone = trim($_GET['phone'] ?? '');
        
        if (empty($phone)) {
            $this->jsonResponse(['success' => true, 'data' => ['found' => false, 'customer' => null]]);
            return;
        }

        // Limpar telefone - apenas números
        $phoneClean = normalizePhone($phone);
        
        if (strlen($phoneClean) < 10) {
            $this->jsonResponse(['success' => true, 'data' => ['found' => false, 'customer' => null]]);
            return;
        }

        $companyId = (int)$company['id'];
        $db = db();

        // Pegar os últimos 8 dígitos para busca mais flexível
        $last8 = substr($phoneClean, -8);
        $phoneLike = '%' . $last8;
        
        // Buscar por whatsapp (formato original ou E164)
        $sql = "SELECT id, name, whatsapp, whatsapp_e164, email 
                FROM customers 
                WHERE company_id = :cid 
                  AND (
                      whatsapp_e164 LIKE :phone1
                      OR REPLACE(REPLACE(REPLACE(REPLACE(whatsapp, '(', ''), ')', ''), '-', ''), ' ', '') LIKE :phone2
                  )
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':cid' => $companyId,
            ':phone1' => $phoneLike,
            ':phone2' => $phoneLike,
        ]);
        
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer) {
            // Buscar endereço padrão do cliente com taxa de entrega
            $sqlAddr = "SELECT ca.street, ca.number, ca.neighborhood, ca.complement, ca.reference, 
                               ca.city, ca.city_id, ca.zone_id,
                               COALESCE(dz.fee, 0) as delivery_fee
                        FROM customer_addresses ca
                        LEFT JOIN delivery_zones dz ON dz.id = ca.zone_id
                        WHERE ca.customer_id = :cust_id 
                          AND ca.company_id = :cid
                        ORDER BY ca.is_default DESC, ca.updated_at DESC 
                        LIMIT 1";
            $stmtAddr = $db->prepare($sqlAddr);
            $stmtAddr->execute([
                ':cust_id' => $customer['id'],
                ':cid' => $companyId,
            ]);
            $address = $stmtAddr->fetch(PDO::FETCH_ASSOC);
            
            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'found' => true,
                    'customer' => [
                        'id' => (int)$customer['id'],
                        'name' => $customer['name'],
                        'phone' => $customer['whatsapp'] ?? $customer['whatsapp_e164'],
                        'email' => $customer['email'] ?? null,
                        'address' => $address ? [
                            'street' => $address['street'] ?? '',
                            'number' => $address['number'] ?? '',
                            'neighborhood' => $address['neighborhood'] ?? '',
                            'complement' => $address['complement'] ?? '',
                            'reference' => $address['reference'] ?? '',
                            'city' => $address['city'] ?? '',
                            'city_id' => (int)($address['city_id'] ?? 0),
                            'zone_id' => (int)($address['zone_id'] ?? 0),
                            'delivery_fee' => (float)($address['delivery_fee'] ?? 0),
                        ] : null,
                    ]
                ]
            ]);
        } else {
            $this->jsonResponse(['success' => true, 'data' => ['found' => false, 'customer' => null]]);
        }
    }
}