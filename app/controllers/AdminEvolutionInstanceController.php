<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

class AdminEvolutionInstanceController extends Controller
{
    private function guard($slug)
    {
        Auth::start();
        $u = Auth::user();

        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
                 || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
                 || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if (!$u) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Sessão expirada. Faça login novamente.']);
                exit;
            } else {
                header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
                exit;
            }
        }
        
        $company = Company::findBySlug($slug);

        if (!$company) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Empresa inválida ou inativa']);
                exit;
            } else {
                echo 'Empresa inválida';
                exit;
            }
        }

        if ($u['role'] !== 'root' && (int)$u['company_id'] !== (int)$company['id']) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Acesso negado']);
                exit;
            } else {
                echo 'Acesso negado';
                exit;
            }
        }

        return [$u, $company];
    }

    /**
     * Carregar horários de funcionamento da empresa
     */
    private function loadCompanyHours(int $companyId): array
    {
        $st = db()->prepare('SELECT * FROM company_hours WHERE company_id=? ORDER BY weekday');
        $st->execute([$companyId]);
        $rows = $st->fetchAll();
        $by = [];
        foreach ($rows as $r) {
            $by[(int)$r['weekday']] = $r;
        }
        return $by;
    }

    /**
     * Fazer requisição para Evolution API
     */
    private function evolutionApiRequest(array $company, string $path, string $method = 'GET', ?array $body = null): array
    {
        $server = rtrim($company['evolution_server_url'] ?? '', '/');
        $apiKey = $company['evolution_api_key'] ?? null;

        if (!$server || !$apiKey) {
            return ['error' => 'Configuração Evolution ausente (SERVER_URL e AUTHENTICATION_API_KEY).'];
        }

        $fullUrl = $server . '/' . ltrim($path, '/');
        
        // Log da requisição para debug
        error_log("Evolution API Request: $method $fullUrl");
        if ($body) {
            error_log("Evolution API Body: " . json_encode($body));
        }

        // internal helper to do a single request
        $doRequest = function(string $url) use ($method, $body, $apiKey) {
            $ch = curl_init($url);
            $headers = [
                'Accept: application/json',
                'Content-Type: application/json',
                'apikey: ' . $apiKey
            ];

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CUSTOMREQUEST => $method
            ]);

            if ($body && in_array($method, ['POST', 'PUT', 'PATCH'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Log da resposta para debug
            error_log("Evolution API Response: HTTP $httpCode");
            error_log("Evolution API Response Body: " . substr($response, 0, 500));
            
            if ($curlError) {
                error_log("Evolution API cURL Error: $curlError");
                return ['error' => 'Falha na conexão cURL: ' . $curlError];
            }

            if ($response === false) {
                return ['error' => 'Falha na conexão cURL'];
            }

            $decoded = json_decode($response, true);
            return [
                'code' => $httpCode,
                'data' => $decoded,
                'error' => $httpCode >= 400 ? ($decoded['message'] ?? 'HTTP Error ' . $httpCode) : null
            ];
        };

        return $doRequest($fullUrl);
    }

    /**
     * Página de configuração de uma instância específica
     */
    public function __construct()
    {
        // Constructor simplificado - a autenticação é feita pelo guard()
    }

    /**
     * Página de configuração da instância
     */
    public function config($params)
    {
        try {
            $slug = $params['slug'] ?? null;
            $instanceName = $params['instanceName'] ?? null;

            if (!$slug || !$instanceName) {
                http_response_code(400);
                echo "Parâmetros inválidos";
                return;
            }

            [$user, $company] = $this->guard($slug);
            $instanceData = $this->getInstanceDataByName($instanceName, $company);

            $payload = [
                'instance_name' => (string)$instanceName,
                'instance_data' => is_array($instanceData) ? [
                    'instance_identifier' => (string)($instanceData['instance_identifier'] ?? $instanceData['id'] ?? $instanceName),
                    'status' => (string)($instanceData['status'] ?? 'disconnected'),
                    'connection_status' => (string)($instanceData['connectionStatus'] ?? 'disconnected'),
                    'profile_name' => (string)($instanceData['profileName'] ?? ''),
                    'profile_pic_url' => (string)($instanceData['profilePicUrl'] ?? ''),
                    'number' => (string)($instanceData['number'] ?? $instanceData['ownerJid'] ?? ''),
                    'token' => (string)($instanceData['token'] ?? ''),
                ] : null,
                'urls' => [
                    'instances_list' => '/admin/' . rawurlencode($slug) . '/evolution',
                    'connection_state' => '/admin/' . rawurlencode($slug) . '/evolution/instance/' . rawurlencode($instanceName) . '/connection_state',
                    'connect' => '/admin/' . rawurlencode($slug) . '/evolution/instance/' . rawurlencode($instanceName) . '/connect',
                    'restart' => '/admin/' . rawurlencode($slug) . '/evolution/instance/' . rawurlencode($instanceName) . '/restart',
                    'disconnect' => '/admin/' . rawurlencode($slug) . '/evolution/instance/' . rawurlencode($instanceName) . '/disconnect',
                    'qr_code' => '/admin/' . rawurlencode($slug) . '/evolution/instance/' . rawurlencode($instanceName) . '/qr_code',
                    'pairing_code' => '/admin/' . rawurlencode($slug) . '/evolution/instance/' . rawurlencode($instanceName) . '/pairing_code',
                    'stats' => '/admin/' . rawurlencode($slug) . '/evolution/instance/' . rawurlencode($instanceName) . '/stats',
                    'get_settings' => '/admin/' . rawurlencode($slug) . '/evolution/instance/' . rawurlencode($instanceName) . '/settings',
                    'save_settings' => '/admin/' . rawurlencode($slug) . '/evolution/instance/' . rawurlencode($instanceName) . '/settings',
                    'order_notification' => '/admin/' . rawurlencode($slug) . '/evolution/instance/' . rawurlencode($instanceName) . '/order-notification',
                    'check_conflict' => '/admin/' . rawurlencode($slug) . '/evolution/instance/' . rawurlencode($instanceName) . '/check-notification-conflict',
                    'customer_engagement' => '/admin/' . rawurlencode($slug) . '/evolution/instance/' . rawurlencode($instanceName) . '/customer-engagement',
                    'engagement_stats' => '/admin/' . rawurlencode($slug) . '/evolution/instance/' . rawurlencode($instanceName) . '/engagement-stats',
                    'validate_whatsapp' => '/admin/' . rawurlencode($slug) . '/evolution/instance/' . rawurlencode($instanceName) . '/validate-whatsapp',
                ],
            ];

            \App\Services\AdminStoreSpaRenderer::render((string)$slug, $company, '__ADMIN_STORE_EVOLUTION_INSTANCE__', $payload);
        } catch (Exception $e) {
            error_log("Erro no AdminEvolutionInstanceController::config(): " . $e->getMessage());
            http_response_code(500);
            echo "Erro interno do servidor";
        }
    }

    /**
     * Buscar dados específicos da instância pelo nome
     */
    private function getInstanceDataByName($instanceName, $company = null)
    {
        // Se a empresa não foi passada, tentar obter do contexto
        if (!$company) {
            $slug = $_GET['slug'] ?? $_POST['slug'] ?? 'wollburger'; // fallback temporário
            $company = \Company::findBySlug($slug);
        }
        
        if (!$company) {
            error_log("Empresa não encontrada");
            return [
                'instance_identifier' => $instanceName,
                'status' => 'disconnected',
                'connectionStatus' => 'disconnected',
                'token' => null
            ];
        }
        
        // Usar o método evolutionApiRequest consistente em vez de URL hardcoded
        $result = $this->evolutionApiRequest($company, '/instance/fetchInstances?instanceName=' . rawurlencode($instanceName), 'GET');
        
        $instanceData = null;
        if (!$result['error'] && !empty($result['data'])) {
            $data = $result['data'];
            // Se retornou um array com dados, pegar o primeiro item
            if (is_array($data) && !empty($data)) {
                // Verificar se é um array de arrays (múltiplas instâncias) ou um objeto único
                if (array_key_exists(0, $data) && is_array($data[0])) {
                    $instanceData = $data[0]; // Primeiro item do array
                } else {
                    $instanceData = $data;
                }
            }
        }
        
        if (!$instanceData) {
            error_log("Erro ao buscar dados da instância $instanceName: " . ($result['error'] ?? 'dados vazios'));
            // Buscar estado real mesmo sem dados do fetchInstances
            $realState = $this->getInstanceRealConnectionState($company, $instanceName);
            $normalizedStatus = $this->normalizeEvolutionState($realState, []);
            return [
                'instance_identifier' => $instanceName,
                'status' => $normalizedStatus,
                'connectionStatus' => $normalizedStatus,
                'token' => null
            ];
        }
        
        // Buscar estado REAL direto da API (ignora connectionStatus do fetchInstances)
        $realState = $this->getInstanceRealConnectionState($company, $instanceName);
        $normalizedStatus = $this->normalizeEvolutionState($realState, $instanceData);
        $instanceData['connectionStatus'] = $normalizedStatus;
        $instanceData['status'] = $normalizedStatus;
        error_log("Estado da instância $instanceName: real=$realState, normalized=$normalizedStatus");
        
        return $instanceData;
    }
    
    /**
     * Buscar o estado REAL de conexão de uma instância via /instance/connectionState
     * Retorna: 'open', 'close', 'connecting' ou null se falhar
     */
    private function getInstanceRealConnectionState($company, $instanceName)
    {
        $result = $this->evolutionApiRequest($company, '/instance/connectionState/' . rawurlencode($instanceName), 'GET');
        
        // Log para debug
        error_log("InstanceController connectionState for '$instanceName': " . json_encode($result));
        
        if (!$result['error'] && !empty($result['data'])) {
            // Tentar múltiplos caminhos possíveis na resposta
            // Formato 1: data.instance.state
            if (isset($result['data']['instance']['state'])) {
                return $result['data']['instance']['state'];
            }
            // Formato 2: data.state (resposta direta)
            if (isset($result['data']['state'])) {
                return $result['data']['state'];
            }
            // Formato 3: Apenas verificar se está connected
            if (isset($result['data']['connected']) && $result['data']['connected'] === true) {
                return 'open';
            }
        }
        
        return null;
    }
    
    /**
     * Normalizar estado da Evolution API para status interno
     * Estados da Evolution API: open, close, connecting
     * Estados internos: connected, disconnected, pending
     */
    private function normalizeEvolutionState(?string $realState, array $instanceData = []): string
    {
        // Se conseguimos o estado real, usar ele
        if ($realState !== null) {
            return match(strtolower($realState)) {
                'open', 'connected' => 'connected',
                'connecting', 'qrcode', 'qr' => 'pending',
                'close', 'closed', 'disconnected', 'logout' => 'disconnected',
                default => 'pending'
            };
        }
        
        // Fallback: tentar usar o connectionStatus do fetchInstances
        $connectionStatus = $instanceData['connectionStatus'] ?? null;
        if ($connectionStatus !== null) {
            return match(strtolower((string)$connectionStatus)) {
                'open', 'connected' => 'connected',
                'connecting', 'qrcode', 'qr' => 'pending',
                'close', 'closed', 'disconnected', 'logout' => 'disconnected',
                default => 'pending'
            };
        }
        
        // Se tem número e profile, provavelmente está conectado
        if (!empty($instanceData['number']) && !empty($instanceData['profileName'])) {
            return 'connected';
        }
        
        // Se tem ownerJid, provavelmente está conectado
        if (!empty($instanceData['ownerJid'])) {
            return 'connected';
        }
        
        return 'disconnected';
    }

    /**
     * Helper para obter a URL base da Evolution API das configurações
     */
    private function getEvolutionBaseUrl($company)
    {
        $baseUrl = null;
        if ($company && isset($company['evolution_server_url'])) {
            $baseUrl = rtrim($company['evolution_server_url'], '/');
        }
        
        // Fallback para URL padrão se não estiver configurada
        if (!$baseUrl) {
            $baseUrl = 'https://evolutionvictor.mlojas.com';
            error_log("AVISO: Usando URL padrão da Evolution API. Configure a URL nas Settings da empresa.");
        }
        
        return $baseUrl;
    }

    /**
     * Helper para chamadas consistentes da API Evolution
     */
    private function callEvolutionAPI($url, $method = 'GET', $data = null, $timeout = 15, $company = null)
    {
        $curl = curl_init();
        
        // Buscar API key das configurações da empresa
        $apiKey = null;
        if ($company && isset($company['evolution_api_key'])) {
            $apiKey = $company['evolution_api_key'];
        }
        
        // Fallback para API key hardcoded se não estiver nas configurações
        if (!$apiKey) {
            $apiKey = '0cdfec38b34fdae0d7624e8e28debd9f';
            error_log("AVISO: Usando API key padrão. Configure a API key nas Settings da empresa.");
        }
        
        $headers = [
            'apikey: ' . $apiKey,
            'Content-Type: application/json'
        ];
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $timeout
        ];
        
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            if ($data) {
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        } elseif ($method === 'DELETE') {
            $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
            if ($data) {
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        }
        
        curl_setopt_array($curl, $options);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        
        if ($curlError) {
            return [
                'success' => false,
                'error' => 'Erro de conexão: ' . $curlError,
                'httpCode' => $httpCode,
                'data' => null,
                'rawResponse' => $response
            ];
        }
        
        if ($httpCode !== 200 && $httpCode !== 201 && $httpCode !== 204) {
            $errorData = json_decode($response, true);
            $errorMsg = is_array($errorData) && isset($errorData['message']) 
                ? $errorData['message'] 
                : ($response ?: 'Erro HTTP ' . $httpCode);

            if (is_array($errorMsg) || is_object($errorMsg)) {
                $errorMsg = json_encode($errorMsg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            
            return [
                'success' => false,
                'error' => $errorMsg,
                'httpCode' => $httpCode,
                'data' => $errorData,
                'rawResponse' => $response
            ];
        }
        
        $decodedData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Resposta inválida da API: ' . json_last_error_msg(),
                'httpCode' => $httpCode,
                'data' => null,
                'rawResponse' => $response
            ];
        }
        
        return [
            'success' => true,
            'httpCode' => $httpCode,
            'error' => null,
            'data' => $decodedData,
            'rawResponse' => $response
        ];
    }

    private function errorToText($error): string
    {
        if (is_string($error)) {
            return $error;
        }

        if (is_array($error) || is_object($error)) {
            return json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'Erro desconhecido';
        }

        return (string) ($error ?? '');
    }

    private function isInstanceMissingError(array $result): bool
    {
        $httpCode = (int) ($result['httpCode'] ?? 0);
        $error = strtolower($this->errorToText($result['error'] ?? ''));

        return $httpCode === 404
            || str_contains($error, 'does not exist')
            || str_contains($error, 'not found')
            || str_contains($error, 'nao existe')
            || str_contains($error, 'não existe');
    }

    private function isNameAlreadyInUseError(array $result): bool
    {
        $error = strtolower($this->errorToText($result['error'] ?? ''));

        return str_contains($error, 'already in use')
            || str_contains($error, 'ja esta em uso')
            || str_contains($error, 'já está em uso');
    }

    private function isPairingPostNotSupported(array $result): bool
    {
        $error = strtolower($this->errorToText($result['error'] ?? ''));

        return str_contains($error, 'cannot post /instance/connect/');
    }
    
    private function render($view, $data = [])
    {
        extract($data);
        
        $viewPath = __DIR__ . '/../views/' . $view . '.php';
        
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            http_response_code(404);
            echo "View não encontrada: {$view}";
        }
    }

    /**
     * API - Obter estado da conexão da instância
     */
    public function connection_state($params)
    {
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Nome da instância é obrigatório']);
            return;
        }
        
        try {
            $slug = $params['slug'] ?? null;
            if (!$slug) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Slug da empresa é obrigatório']);
                return;
            }
            
            [$user, $company] = $this->guard($slug);
            
            $baseUrl = $this->getEvolutionBaseUrl($company);
            $url = $baseUrl . "/instance/connectionState/" . rawurlencode($instanceName);
            $result = $this->callEvolutionAPI($url, 'GET', null, 30, $company);
            
            header('Content-Type: application/json');
            
            if ($result['success']) {
                echo json_encode(['success' => true, 'data' => $result['data']]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Falha ao obter estado: ' . $result['error']]);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    public function connect($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            [$user, $company] = $this->guard($slug);
            
            $baseUrl = $this->getEvolutionBaseUrl($company);
            $url = $baseUrl . "/instance/connect/" . rawurlencode($instanceName);
            $result = $this->callEvolutionAPI($url, 'GET', null, 30, $company);
            
            header('Content-Type: application/json');
            
            if ($result['success']) {
                $data = $result['data'];
                
                // Se a resposta contém QR code, significa que precisa escanear
                if (isset($data['base64']) || isset($data['qrcode'])) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'QR Code gerado. Escaneie para conectar.',
                        'needsQr' => true,
                        'data' => $data
                    ]);
                } else {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Instância conectada com sucesso!',
                        'data' => $data
                    ]);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Falha ao conectar: ' . $result['error']]);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    public function restart($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            [$user, $company] = $this->guard($slug);
            
            $baseUrl = $this->getEvolutionBaseUrl($company);
            $url = $baseUrl . "/instance/restart/" . rawurlencode($instanceName);
            $result = $this->callEvolutionAPI($url, 'POST', null, 30, $company);
            
            header('Content-Type: application/json');
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Instância reiniciada com sucesso',
                    'data' => $result['data']
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Falha ao reiniciar: ' . $result['error']]);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    public function disconnect($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            [$user, $company] = $this->guard($slug);
            
            $baseUrl = $this->getEvolutionBaseUrl($company);
            $url = $baseUrl . "/instance/logout/" . rawurlencode($instanceName);
            $result = $this->callEvolutionAPI($url, 'DELETE', null, 30, $company);
            
            header('Content-Type: application/json');
            
            if ($result['success'] || $result['httpCode'] === 204) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Instância desconectada com sucesso',
                    'data' => $result['data']
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Falha ao desconectar: ' . $result['error']]);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    public function qr_code($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            // Usar o método guard para autenticação
            [$user, $company] = $this->guard($slug);
            
            // Fazer a chamada direta para o endpoint de conexão que retorna o QR code
            $baseUrl = $this->getEvolutionBaseUrl($company);
            $url = $baseUrl . "/instance/connect/" . rawurlencode($instanceName);
            $result = $this->callEvolutionAPI($url, 'GET', null, 30, $company);

            header('Content-Type: application/json');

            if (!$result['success']) {
                $error = $this->errorToText($result['error'] ?? '');
                
                // Se instância não existe (404), tentar recriar
                if ($this->isInstanceMissingError($result)) {
                    error_log("Instância $instanceName não existe na API. Tentando recriar...");
                    
                    // Tentar recriar a instância
                    $createUrl = $baseUrl . "/instance/create";
                    $createResult = $this->callEvolutionAPI($createUrl, 'POST', [
                        'instanceName' => $instanceName,
                        'integration' => 'WHATSAPP-BAILEYS'
                    ], 30, $company);
                    
                    if ($createResult['success']) {
                        // Tentar conectar novamente após criar
                        sleep(2); // Aguardar 2 segundos
                        $result = $this->callEvolutionAPI($url, 'GET', null, 30, $company);
                        
                        if (!$result['success']) {
                            echo json_encode([
                                'success' => false, 
                                'error' => 'Instância recriada, mas erro ao gerar QR Code: ' . $this->errorToText($result['error'] ?? 'Erro desconhecido'),
                                'recreated' => true
                            ]);
                            return;
                        }
                    } elseif ($this->isNameAlreadyInUseError($createResult)) {
                        // A API recusou create porque o nome já existe.
                        // Neste cenário, a instância pode existir mas estar em transição; tentamos conectar de novo.
                        error_log("Instância $instanceName já existe na API (already in use). Tentando connect novamente...");
                        sleep(1);
                        $result = $this->callEvolutionAPI($url, 'GET', null, 30, $company);

                        if (!$result['success']) {
                            echo json_encode([
                                'success' => false,
                                'error' => 'Instância já existe, mas falhou ao conectar: ' . $this->errorToText($result['error'] ?? 'Erro desconhecido')
                            ]);
                            return;
                        }
                    } else {
                        echo json_encode([
                            'success' => false, 
                            'error' => 'Instância não existe e erro ao recriar: ' . $this->errorToText($createResult['error'] ?? 'Erro desconhecido')
                        ]);
                        return;
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => $error]);
                    return;
                }
            }

            $data = $result['data'];

            // Verificar se a instância já está conectada
            $state = $data['instance']['state'] ?? $data['state'] ?? null;
            if ($state === 'open') {
                // Já está conectada, não precisa de QR code
                echo json_encode([
                    'success' => true, 
                    'connected' => true,
                    'message' => 'Instância já está conectada!'
                ]);
                return;
            }

            // Buscar o QR code na resposta
            $qr = $data['base64'] ?? $data['qrcode']['base64'] ?? $data['qr'] ?? null;

            if ($qr) {
                // Garantir que o QR tem o prefixo data:image correto
                if (!str_starts_with($qr, 'data:image/')) {
                    $qr = 'data:image/png;base64,' . $qr;
                }
                echo json_encode(['success' => true, 'qr' => $qr]);
            } else {
                // Se não há QR e não está conectada, verificar estado real
                $stateUrl = $baseUrl . "/instance/connectionState/" . rawurlencode($instanceName);
                $stateResult = $this->callEvolutionAPI($stateUrl, 'GET', null, 15, $company);
                $realState = $stateResult['data']['instance']['state'] ?? 'unknown';
                
                if ($realState === 'open') {
                    echo json_encode([
                        'success' => true,
                        'connected' => true,
                        'message' => 'Instância conectada!'
                    ]);
                } else if ($realState === 'connecting') {
                    // Instância presa em connecting, tentar restart
                    error_log("Instância $instanceName presa em 'connecting'. Tentando restart...");
                    $restartUrl = $baseUrl . "/instance/restart/" . rawurlencode($instanceName);
                    $this->callEvolutionAPI($restartUrl, 'POST', null, 15, $company);
                    sleep(2);
                    
                    // Tentar conectar novamente após restart
                    $result = $this->callEvolutionAPI($url, 'GET', null, 30, $company);
                    $qr = $result['data']['base64'] ?? $result['data']['qrcode']['base64'] ?? $result['data']['qr'] ?? null;
                    
                    if ($qr) {
                        if (!str_starts_with($qr, 'data:image/')) {
                            $qr = 'data:image/png;base64,' . $qr;
                        }
                        echo json_encode(['success' => true, 'qr' => $qr]);
                    } else {
                        // Verificar se conectou após restart
                        $stateResult = $this->callEvolutionAPI($stateUrl, 'GET', null, 15, $company);
                        $newState = $stateResult['data']['instance']['state'] ?? 'unknown';
                        
                        if ($newState === 'open') {
                            echo json_encode([
                                'success' => true,
                                'connected' => true,
                                'message' => 'Instância conectada após reinício!'
                            ]);
                        } else {
                            echo json_encode([
                                'success' => false, 
                                'error' => 'Não foi possível gerar QR Code. Estado atual: ' . $newState
                            ]);
                        }
                    }
                } else {
                    // Log da resposta para debug
                    error_log("QR Code Debug - Response: " . json_encode($data) . " - Real state: " . $realState);
                    echo json_encode(['success' => false, 'error' => 'QR Code não encontrado na resposta da API. Estado: ' . $realState]);
                }
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    /**
     * API - Gerar código de pareamento (pairing code) para conexão sem QR Code
     * O usuário informa o número de telefone e recebe um código de 8 dígitos
     * para digitar em WhatsApp > Dispositivos conectados > Conectar com número de telefone
     */
    public function pairing_code($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;

        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }

        try {
            [$user, $company] = $this->guard($slug);

            // Ler número do body
            $input = json_decode(file_get_contents('php://input'), true);
            $phoneNumber = trim($input['number'] ?? '');

            if (empty($phoneNumber)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Informe o número de telefone com DDD (ex: 5551999999999)']);
                return;
            }

            // Normalizar: manter só dígitos
            $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

            // Garantir código país 55
            if (substr($phoneNumber, 0, 2) !== '55' && strlen($phoneNumber) <= 11) {
                $phoneNumber = '55' . $phoneNumber;
            }

            $baseUrl = $this->getEvolutionBaseUrl($company);

            // Primeiro verificar se instância já está conectada
            $stateUrl = $baseUrl . "/instance/connectionState/" . rawurlencode($instanceName);
            $stateResult = $this->callEvolutionAPI($stateUrl, 'GET', null, 15, $company);
            $currentState = $stateResult['data']['instance']['state'] ?? 'unknown';

            if ($currentState === 'open') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'connected' => true,
                    'message' => 'Instância já está conectada!'
                ]);
                return;
            }

            // Se está presa em connecting, reiniciar antes
            if ($currentState === 'connecting') {
                $restartUrl = $baseUrl . "/instance/restart/" . rawurlencode($instanceName);
                $this->callEvolutionAPI($restartUrl, 'POST', null, 15, $company);
                sleep(2);
            }

            // Chamar /instance/connect/{name} com o número no body
            // Quando o body contém "number", a Evolution API retorna pairing code em vez de QR
            $connectUrl = $baseUrl . "/instance/connect/" . rawurlencode($instanceName);
            $result = $this->callEvolutionAPI($connectUrl, 'POST', [
                'number' => $phoneNumber
            ], 30, $company);

            header('Content-Type: application/json');

            if (!$result['success']) {
                if ($this->isPairingPostNotSupported($result)) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Sua Evolution API nao suporta codigo de pareamento por este endpoint. Use QR Code ou atualize a Evolution API.'
                    ]);
                    return;
                }

                // Se instância não existe, tentar recriar
                $error = $this->errorToText($result['error'] ?? '');
                if ($this->isInstanceMissingError($result)) {
                    $createUrl = $baseUrl . "/instance/create";
                    $createResult = $this->callEvolutionAPI($createUrl, 'POST', [
                        'instanceName' => $instanceName,
                        'integration' => 'WHATSAPP-BAILEYS'
                    ], 30, $company);

                    if ($createResult['success']) {
                        sleep(2);
                        $result = $this->callEvolutionAPI($connectUrl, 'POST', [
                            'number' => $phoneNumber
                        ], 30, $company);

                        if (!$result['success']) {
                            if ($this->isPairingPostNotSupported($result)) {
                                echo json_encode([
                                    'success' => false,
                                    'error' => 'Sua Evolution API nao suporta codigo de pareamento por este endpoint. Use QR Code ou atualize a Evolution API.'
                                ]);
                                return;
                            }

                            echo json_encode([
                                'success' => false,
                                'error' => 'Instância recriada, mas erro ao gerar código: ' . $this->errorToText($result['error'] ?? 'Erro desconhecido')
                            ]);
                            return;
                        }
                    } elseif ($this->isNameAlreadyInUseError($createResult)) {
                        error_log("Instância $instanceName já existe na API (already in use). Tentando pairing novamente...");
                        sleep(1);
                        $result = $this->callEvolutionAPI($connectUrl, 'POST', [
                            'number' => $phoneNumber
                        ], 30, $company);

                        if (!$result['success']) {
                            if ($this->isPairingPostNotSupported($result)) {
                                echo json_encode([
                                    'success' => false,
                                    'error' => 'Sua Evolution API nao suporta codigo de pareamento por este endpoint. Use QR Code ou atualize a Evolution API.'
                                ]);
                                return;
                            }

                            echo json_encode([
                                'success' => false,
                                'error' => 'Instância já existe, mas falhou ao gerar código: ' . $this->errorToText($result['error'] ?? 'Erro desconhecido')
                            ]);
                            return;
                        }
                    } else {
                        echo json_encode([
                            'success' => false,
                            'error' => 'Instância não existe e erro ao recriar: ' . $this->errorToText($createResult['error'] ?? 'Erro desconhecido')
                        ]);
                        return;
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => $error ?: 'Erro ao solicitar código de pareamento']);
                    return;
                }
            }

            $data = $result['data'] ?? [];

            // Verificar se conectou direto
            $state = $data['instance']['state'] ?? $data['state'] ?? null;
            if ($state === 'open') {
                echo json_encode([
                    'success' => true,
                    'connected' => true,
                    'message' => 'Instância conectada com sucesso!'
                ]);
                return;
            }

            // Extrair pairing code da resposta
            $pairingCode = $data['pairingCode'] ?? $data['code'] ?? null;

            if ($pairingCode) {
                // Formatar com hífen para facilitar leitura (XXXX-XXXX)
                $formatted = $pairingCode;
                if (strlen($pairingCode) === 8 && strpos($pairingCode, '-') === false) {
                    $formatted = substr($pairingCode, 0, 4) . '-' . substr($pairingCode, 4);
                }

                echo json_encode([
                    'success' => true,
                    'pairingCode' => $formatted,
                    'phone' => $phoneNumber,
                    'message' => 'Código gerado! Digite no WhatsApp em Dispositivos conectados > Conectar com número de telefone.'
                ]);
            } else {
                // Pode ter retornado QR em vez de pairing code
                $qr = $data['base64'] ?? $data['qrcode']['base64'] ?? null;
                if ($qr) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'A API retornou QR Code em vez de código de pareamento. Verifique se o número está correto e tente novamente.'
                    ]);
                } else {
                    error_log("Pairing Code Debug - Response: " . json_encode($data));
                    echo json_encode([
                        'success' => false,
                        'error' => 'Não foi possível gerar o código de pareamento. Tente novamente ou use o QR Code.'
                    ]);
                }
            }

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    /**
     * API - Buscar estatísticas da instância (contatos, chats, mensagens)
     */
    public function stats($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            [$user, $company] = $this->guard($slug);
            
            // Usar o método getInstanceDataByName com a empresa correta
            $instanceData = $this->getInstanceDataByName($instanceName, $company);
            
            header('Content-Type: application/json');
            
            if ($instanceData) {
                // Extrair estatísticas dos dados já obtidos
                $stats = [
                    'status' => $instanceData['connectionStatus'] ?? 'disconnected',
                    'contacts' => $instanceData['_count']['Contact'] ?? 0,
                    'chats' => $instanceData['_count']['Chat'] ?? 0,
                    'messages' => $instanceData['_count']['Message'] ?? 0,
                    'profileName' => $instanceData['profileName'] ?? null,
                    'profilePicUrl' => $instanceData['profilePicUrl'] ?? null,
                    'number' => $instanceData['number'] ?? null
                ];
                
                echo json_encode(['success' => true, 'data' => $stats]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Instância não encontrada']);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    /**
     * API - Salvar configurações da instância
     */
    public function save_settings($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            [$user, $company] = $this->guard($slug);
            
            // Obter dados POST
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Dados de configuração não fornecidos']);
                return;
            }
            
            // Primeiro, buscar configurações atuais
            $currentResult = $this->evolutionApiRequest(
                $company, 
                "/settings/find/{$instanceName}", 
                'GET'
            );
            
            if ($currentResult['error']) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Erro ao buscar configurações atuais: ' . $currentResult['error']]);
                return;
            }
            
            // Configurações padrão da Evolution API v2
            $defaultSettings = [
                'rejectCall' => false,
                'msgCall' => '',
                'groupsIgnore' => false,
                'alwaysOnline' => false,
                'readMessages' => false,
                'readStatus' => false,
                'syncFullHistory' => false
            ];

            // A Evolution /settings/set aceita apenas essas chaves.
            // Sanitizamos dados atuais e input para evitar Bad Request.
            $allowedKeys = array_keys($defaultSettings);
            $currentRaw = is_array($currentResult['data'] ?? null) ? $currentResult['data'] : [];

            $normalizeSetting = static function (string $key, $value) {
                if ($key === 'msgCall') {
                    return (string) ($value ?? '');
                }

                if (is_bool($value)) {
                    return $value;
                }

                if (is_numeric($value)) {
                    return ((int) $value) === 1;
                }

                if (is_string($value)) {
                    $v = strtolower(trim($value));
                    if (in_array($v, ['1', 'true', 'yes', 'on'], true)) {
                        return true;
                    }
                    if (in_array($v, ['0', 'false', 'no', 'off', ''], true)) {
                        return false;
                    }
                }

                return (bool) $value;
            };

            $currentSettings = [];
            foreach ($allowedKeys as $key) {
                if (array_key_exists($key, $currentRaw)) {
                    $currentSettings[$key] = $normalizeSetting($key, $currentRaw[$key]);
                }
            }

            $inputFiltered = [];
            foreach ($allowedKeys as $key) {
                if (array_key_exists($key, $input)) {
                    $inputFiltered[$key] = $normalizeSetting($key, $input[$key]);
                }
            }

            // Mesclar: padrão + atual saneado + input saneado
            $finalSettings = array_merge($defaultSettings, $currentSettings, $inputFiltered);
            
            // Fazer POST com todas as configurações
            $result = $this->evolutionApiRequest(
                $company, 
                "/settings/set/{$instanceName}", 
                'POST', 
                $finalSettings
            );
            
            header('Content-Type: application/json');
            
            if ($result['error']) {
                echo json_encode(['success' => false, 'error' => $result['error']]);
            } else {
                echo json_encode(['success' => true, 'data' => $result['data'], 'sent_payload' => $finalSettings]);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    /**
     * API - Buscar grupos da instância
     */
    public function groups($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            [$user, $company] = $this->guard($slug);
            
            // Log das configurações da empresa
            error_log("Company config - Server: " . ($company['evolution_server_url'] ?? 'NOT SET'));
            error_log("Company config - API Key: " . (isset($company['evolution_api_key']) ? 'SET' : 'NOT SET'));
            
            // Buscar grupos usando o endpoint da Evolution API v2 com parâmetro obrigatório
            $path = "/group/fetchAllGroups/{$instanceName}?getParticipants=false";
            error_log("Buscando grupos para instância: {$instanceName} no path: {$path}");
            
            $result = $this->evolutionApiRequest(
                $company, 
                $path, 
                'GET'
            );
            
            error_log("Resultado da busca de grupos: " . json_encode($result));
            
            header('Content-Type: application/json');
            
            if ($result['error']) {
                echo json_encode(['success' => false, 'error' => $result['error']]);
            } else {
                // Normalizar dados dos grupos para o formato esperado
                $groups = $result['data'] ?? [];
                if (is_array($groups)) {
                    $formattedGroups = array_map(function($group) {
                        return [
                            'id' => $group['id'] ?? '',
                            'subject' => $group['subject'] ?? 'Grupo sem nome',
                            'description' => $group['description'] ?? '',
                            'participants' => count($group['participants'] ?? []),
                            'creation' => $group['creation'] ?? null,
                            'owner' => $group['owner'] ?? null
                        ];
                    }, $groups);
                    
                    echo json_encode(['success' => true, 'data' => $formattedGroups]);
                } else {
                    echo json_encode(['success' => true, 'data' => []]);
                }
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    /**
     * API - Buscar configurações da instância
     */
    public function get_settings($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            [$user, $company] = $this->guard($slug);
            
            // Usar o método evolutionApiRequest para fazer a chamada
            $result = $this->evolutionApiRequest(
                $company, 
                "/settings/find/{$instanceName}", 
                'GET'
            );
            
            header('Content-Type: application/json');
            
            if ($result['error']) {
                echo json_encode(['success' => false, 'error' => $result['error']]);
            } else {
                $defaultSettings = [
                    'rejectCall' => false,
                    'msgCall' => '',
                    'groupsIgnore' => false,
                    'alwaysOnline' => false,
                    'readMessages' => false,
                    'readStatus' => false,
                    'syncFullHistory' => false
                ];

                $allowedKeys = array_keys($defaultSettings);
                $raw = is_array($result['data'] ?? null) ? $result['data'] : [];
                $normalized = $defaultSettings;

                foreach ($allowedKeys as $key) {
                    if (!array_key_exists($key, $raw)) {
                        continue;
                    }

                    if ($key === 'msgCall') {
                        $normalized[$key] = (string) ($raw[$key] ?? '');
                        continue;
                    }

                    $value = $raw[$key];
                    if (is_bool($value)) {
                        $normalized[$key] = $value;
                    } elseif (is_numeric($value)) {
                        $normalized[$key] = ((int) $value) === 1;
                    } elseif (is_string($value)) {
                        $v = strtolower(trim($value));
                        $normalized[$key] = in_array($v, ['1', 'true', 'yes', 'on'], true);
                    } else {
                        $normalized[$key] = (bool) $value;
                    }
                }

                echo json_encode(['success' => true, 'data' => $normalized]);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    /**
     * API - Validar se número existe no WhatsApp
     */
    public function validate_whatsapp($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            [$user, $company] = $this->guard($slug);
            
            $input = json_decode(file_get_contents('php://input'), true);
            $number = $input['number'] ?? '';
            
            if (empty($number)) {
                echo json_encode(['success' => false, 'error' => 'Número não informado']);
                return;
            }
            
            // Limpar número (apenas dígitos)
            $cleanNumber = preg_replace('/[^0-9]/', '', $number);
            
            if (strlen($cleanNumber) < 10 || strlen($cleanNumber) > 15) {
                echo json_encode(['success' => false, 'error' => 'Formato do número inválido']);
                return;
            }
            
            // Usar WhatsAppValidator para verificar
            require_once __DIR__ . '/../services/WhatsAppValidator.php';
            
            $result = WhatsAppValidator::validate($company, $cleanNumber);
            
            header('Content-Type: application/json');
            
            if (!$result['checked']) {
                // Não foi possível verificar (Evolution desconectada)
                echo json_encode([
                    'success' => true, 
                    'exists' => true, 
                    'checked' => false,
                    'message' => 'Não foi possível verificar. A instância Evolution pode estar desconectada.'
                ]);
            } else if ($result['exists']) {
                echo json_encode([
                    'success' => true, 
                    'exists' => true, 
                    'checked' => true,
                    'message' => 'Número válido no WhatsApp!'
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'exists' => false, 
                    'checked' => true,
                    'message' => 'Este número não existe no WhatsApp.'
                ]);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    /**
     * API - Verificar se há conflito de notificação com outra instância
     */
    public function check_notification_conflict($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            [$user, $company] = $this->guard($slug);
            
            // Verificar se já existe outra instância com notificação ativada
            $activeInstance = $this->getActiveNotificationInstance($company['id'], $instanceName);
            
            header('Content-Type: application/json');
            
            if ($activeInstance) {
                echo json_encode([
                    'success' => true,
                    'has_conflict' => true,
                    'active_instance' => $activeInstance['instance_name']
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'has_conflict' => false
                ]);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    /**
     * API - Configurar notificação de pedido
     */
    public function order_notification($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            [$user, $company] = $this->guard($slug);
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Salvar configuração de notificação
                $input = json_decode(file_get_contents('php://input'), true);
                
                $enabled = $input['enabled'] ?? false;
                $primaryNumber = !empty($input['primary_number']) ? normalizePhone($input['primary_number']) : '';
                $secondaryNumber = !empty($input['secondary_number']) ? normalizePhone($input['secondary_number']) : '';
                $messageFields = $input['message_fields'] ?? null;
                $forceSwitch = $input['force_switch'] ?? false;
                
                // Verificar se já existe outra instância com notificação ativada
                if ($enabled && !$forceSwitch) {
                    $activeInstance = $this->getActiveNotificationInstance($company['id'], $instanceName);
                    if ($activeInstance) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false, 
                            'conflict' => true,
                            'active_instance' => $activeInstance['instance_name'],
                            'error' => "A instância '{$activeInstance['instance_name']}' já está com notificações ativadas."
                        ]);
                        return;
                    }
                }
                
                // Se forceSwitch, desativar a outra instância primeiro
                if ($enabled && $forceSwitch) {
                    $this->disableOtherNotificationInstances($company['id'], $instanceName);
                }
                
                // Permitir salvar estado enabled mesmo sem número (usuário vai preencher depois)
                // O número só é obrigatório no momento do envio da notificação
                
                // Validar formato dos números (se fornecidos)
                if ($primaryNumber && !preg_match('/^[0-9]{10,15}$/', $primaryNumber)) {
                    echo json_encode(['success' => false, 'error' => 'Formato do número principal inválido. Use apenas números (10-15 dígitos)']);
                    return;
                }
                
                if ($secondaryNumber && !preg_match('/^[0-9]{10,15}$/', $secondaryNumber)) {
                    echo json_encode(['success' => false, 'error' => 'Formato do número secundário inválido. Use apenas números (10-15 dígitos)']);
                    return;
                }
                
                // Preparar dados para salvar
                $configData = [
                    'enabled' => $enabled,
                    'primary_number' => $primaryNumber,
                    'secondary_number' => $secondaryNumber,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // Adicionar campos da mensagem se fornecidos
                if ($messageFields !== null) {
                    $configData['message_fields'] = $messageFields;
                }
                
                // Debug: Log da configuração que será salva
                error_log('[Order Notification] Salvando configuração: ' . json_encode($configData));
                
                // Salvar configuração na tabela de configurações da empresa
                $this->saveInstanceConfig($company['id'], $instanceName, 'order_notification', $configData);
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Configuração salva com sucesso']);
                
            } else {
                // Carregar configuração de notificação
                $config = $this->getInstanceConfig($company['id'], $instanceName, 'order_notification');
                
                // Debug: Log da configuração carregada
                error_log('[Order Notification] Configuração carregada: ' . json_encode($config));
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'data' => $config]);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    /**
     * Salvar configuração da instância
     */
    private function saveInstanceConfig($companyId, $instanceName, $configKey, $configValue)
    {
        require_once __DIR__ . '/../config/db.php';
        $pdo = db();
        
        $sql = "INSERT INTO instance_configs (company_id, instance_name, config_key, config_value, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW()) 
                ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_at = NOW()";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$companyId, $instanceName, $configKey, json_encode($configValue)]);
    }

    /**
     * Obter configuração da instância
     */
    private function getInstanceConfig($companyId, $instanceName, $configKey)
    {
        require_once __DIR__ . '/../config/db.php';
        $pdo = db();
        
        $sql = "SELECT config_value FROM instance_configs 
                WHERE company_id = ? AND instance_name = ? AND config_key = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$companyId, $instanceName, $configKey]);
        
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($row) {
            return json_decode($row['config_value'], true);
        }
        
        // Retornar configuração padrão
        return [
            'enabled' => false,
            'group_id' => '',
            'custom_message' => ''
        ];
    }

    /**
     * Buscar instância que já tem notificação de pedido ativada (exceto a atual)
     */
    private function getActiveNotificationInstance($companyId, $excludeInstanceName)
    {
        require_once __DIR__ . '/../config/db.php';
        $pdo = db();
        
        $sql = "SELECT instance_name, config_value FROM instance_configs 
                WHERE company_id = ? AND config_key = 'order_notification' AND instance_name != ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$companyId, $excludeInstanceName]);
        
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $config = json_decode($row['config_value'], true);
            if (!empty($config['enabled'])) {
                return [
                    'instance_name' => $row['instance_name'],
                    'config' => $config
                ];
            }
        }
        
        return null;
    }

    /**
     * Desativar notificação de pedido em outras instâncias
     */
    private function disableOtherNotificationInstances($companyId, $excludeInstanceName)
    {
        require_once __DIR__ . '/../config/db.php';
        $pdo = db();
        
        // Buscar todas as instâncias com notificação ativada
        $sql = "SELECT instance_name, config_value FROM instance_configs 
                WHERE company_id = ? AND config_key = 'order_notification' AND instance_name != ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$companyId, $excludeInstanceName]);
        
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $config = json_decode($row['config_value'], true);
            if (!empty($config['enabled'])) {
                // Desativar esta instância
                $config['enabled'] = false;
                $config['updated_at'] = date('Y-m-d H:i:s');
                
                $updateSql = "UPDATE instance_configs SET config_value = ?, updated_at = NOW() 
                              WHERE company_id = ? AND instance_name = ? AND config_key = 'order_notification'";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([json_encode($config), $companyId, $row['instance_name']]);
                
                error_log("[Order Notification] Desativada notificação da instância: " . $row['instance_name']);
            }
        }
    }

    /**
     * API - Configuração de Engajamento de Clientes
     * Gerencia o sistema de mensagens automáticas para clientes inativos ou que não concluíram pedido
     */
    public function customer_engagement($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            [$user, $company] = $this->guard($slug);
            $method = $_SERVER['REQUEST_METHOD'];
            
            header('Content-Type: application/json');
            
            if ($method === 'POST') {
                // Ler dados do corpo da requisição
                $rawInput = file_get_contents('php://input');
                $data = json_decode($rawInput, true) ?? [];
                
                $enabled = isset($data['enabled']) ? (bool)$data['enabled'] : false;
                $scenario1Enabled = isset($data['scenario1_enabled']) ? (bool)$data['scenario1_enabled'] : true;
                $scenario1Delay = isset($data['scenario1_delay']) ? (int)$data['scenario1_delay'] : 10;
                $scenario2Enabled = isset($data['scenario2_enabled']) ? (bool)$data['scenario2_enabled'] : true;
                $scenario2Days = isset($data['scenario2_days']) ? (int)$data['scenario2_days'] : 15;
                $outOfHoursEnabled = isset($data['out_of_hours_enabled']) ? (bool)$data['out_of_hours_enabled'] : true;
                $outOfHoursMessage = isset($data['out_of_hours_message']) ? trim($data['out_of_hours_message']) : null;
                $scheduledPauseEnabled = isset($data['scheduled_pause_enabled']) ? (bool)$data['scheduled_pause_enabled'] : true;
                $scheduledPauseMessage = isset($data['scheduled_pause_message']) ? trim($data['scheduled_pause_message']) : null;
                $businessHoursAutomationEnabled = isset($data['business_hours_automation_enabled']) ? (bool)$data['business_hours_automation_enabled'] : false;
                $forceSwitch = isset($data['force_switch']) ? (bool)$data['force_switch'] : false;
                
                // Verificar se já existe outra instância com engajamento ativado
                if ($enabled && !$forceSwitch) {
                    $activeInstance = $this->getActiveEngagementInstance($company['id'], $instanceName);
                    if ($activeInstance) {
                        echo json_encode([
                            'success' => false, 
                            'conflict' => true,
                            'active_instance' => $activeInstance['instance_name'],
                            'error' => "A instância '{$activeInstance['instance_name']}' já está com engajamento ativado."
                        ]);
                        return;
                    }
                }
                
                // Se forceSwitch, desativar a outra instância primeiro
                if ($enabled && $forceSwitch) {
                    $this->disableOtherEngagementInstances($company['id'], $instanceName);
                }
                
                // Validar limites dos parâmetros
                if ($scenario1Delay < 5 || $scenario1Delay > 60) {
                    echo json_encode(['success' => false, 'error' => 'O tempo de espera deve ser entre 5 e 60 minutos']);
                    return;
                }
                
                if ($scenario2Days < 7 || $scenario2Days > 90) {
                    echo json_encode(['success' => false, 'error' => 'O período de inatividade deve ser entre 7 e 90 dias']);
                    return;
                }
                
                // Salvar na tabela dedicada customer_engagement_config
                $this->saveEngagementConfig($company['id'], $instanceName, [
                    'enabled' => $enabled,
                    'scenario1_enabled' => $scenario1Enabled,
                    'scenario1_delay_minutes' => $scenario1Delay,
                    'scenario2_enabled' => $scenario2Enabled,
                    'scenario2_inactive_days' => $scenario2Days,
                    'out_of_hours_enabled' => $outOfHoursEnabled,
                    'out_of_hours_message' => $outOfHoursMessage,
                    'scheduled_pause_enabled' => $scheduledPauseEnabled,
                    'scheduled_pause_message' => $scheduledPauseMessage,
                    'business_hours_automation_enabled' => $businessHoursAutomationEnabled
                ]);
                
                // Ao desativar, cancelar todos os itens pendentes da fila
                if (!$enabled) {
                    $pdo = db();
                    $cancelStmt = $pdo->prepare("
                        UPDATE customer_engagement_queue 
                        SET status = 'cancelled', is_active = NULL, error_message = 'Sistema desativado pelo admin'
                        WHERE company_id = ? AND status IN ('pending', 'processing')
                    ");
                    $cancelStmt->execute([$company['id']]);
                    $cancelled = $cancelStmt->rowCount();
                    if ($cancelled > 0) {
                        error_log("[Customer Engagement] Fila cancelada ao desativar: {$cancelled} itens para empresa {$company['id']}");
                    }
                }
                
                // Configurar webhook na Evolution API se out_of_hours estiver ativado
                if ($outOfHoursEnabled) {
                    $this->configureOutOfHoursWebhook($company, $instanceName);
                }
                
                echo json_encode(['success' => true, 'message' => 'Configuração de engajamento salva com sucesso']);
                
            } else {
                // GET - Carregar configuração
                $config = $this->getEngagementConfig($company['id'], $instanceName);
                echo json_encode(['success' => true, 'data' => $config]);
            }
            
        } catch (Exception $e) {
            error_log('[Customer Engagement] Erro: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    /**
     * Salvar configuração de engajamento de clientes
     */
    private function saveEngagementConfig($companyId, $instanceName, $config)
    {
        require_once __DIR__ . '/../config/db.php';
        $pdo = db();
        $hasBusinessHoursAutomationColumn = $this->ensureEngagementAutomationColumn($pdo);
        
        // Verificar se já existe configuração para esta empresa
        $checkSql = "SELECT id FROM customer_engagement_config WHERE company_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$companyId]);
        $existing = $checkStmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Atualizar configuração existente
            $sql = "UPDATE customer_engagement_config SET 
                        instance_name = ?,
                        enabled = ?,
                        scenario1_enabled = ?,
                        scenario1_delay_minutes = ?,
                        scenario2_enabled = ?,
                        scenario2_inactive_days = ?,
                        out_of_hours_enabled = ?,
                        out_of_hours_message = ?,
                        scheduled_pause_enabled = ?,
                        scheduled_pause_message = ?";

            if ($hasBusinessHoursAutomationColumn) {
                $sql .= ", business_hours_automation_enabled = ?";
            }

            $sql .= ", updated_at = NOW() WHERE company_id = ?";
            $stmt = $pdo->prepare($sql);

            $params = [
                $instanceName,
                $config['enabled'] ? 1 : 0,
                $config['scenario1_enabled'] ? 1 : 0,
                $config['scenario1_delay_minutes'],
                $config['scenario2_enabled'] ? 1 : 0,
                $config['scenario2_inactive_days'],
                isset($config['out_of_hours_enabled']) ? ($config['out_of_hours_enabled'] ? 1 : 0) : 1,
                $config['out_of_hours_message'] ?? null,
                isset($config['scheduled_pause_enabled']) ? ($config['scheduled_pause_enabled'] ? 1 : 0) : 1,
                $config['scheduled_pause_message'] ?? null
            ];

            if ($hasBusinessHoursAutomationColumn) {
                $params[] = isset($config['business_hours_automation_enabled']) ? ($config['business_hours_automation_enabled'] ? 1 : 0) : 0;
            }

            $params[] = $companyId;
            $stmt->execute($params);
        } else {
            // Inserir nova configuração
            $sql = "INSERT INTO customer_engagement_config 
                        (company_id, instance_name, enabled, scenario1_enabled, scenario1_delay_minutes, 
                         scenario2_enabled, scenario2_inactive_days, out_of_hours_enabled, out_of_hours_message,
                         scheduled_pause_enabled, scheduled_pause_message";

            if ($hasBusinessHoursAutomationColumn) {
                $sql .= ", business_hours_automation_enabled";
            }

            $sql .= ", created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";

            if ($hasBusinessHoursAutomationColumn) {
                $sql .= ", ?";
            }

            $sql .= ", NOW(), NOW())";
            $stmt = $pdo->prepare($sql);

            $params = [
                $companyId,
                $instanceName,
                $config['enabled'] ? 1 : 0,
                $config['scenario1_enabled'] ? 1 : 0,
                $config['scenario1_delay_minutes'],
                $config['scenario2_enabled'] ? 1 : 0,
                $config['scenario2_inactive_days'],
                isset($config['out_of_hours_enabled']) ? ($config['out_of_hours_enabled'] ? 1 : 0) : 1,
                $config['out_of_hours_message'] ?? null,
                isset($config['scheduled_pause_enabled']) ? ($config['scheduled_pause_enabled'] ? 1 : 0) : 1,
                $config['scheduled_pause_message'] ?? null
            ];

            if ($hasBusinessHoursAutomationColumn) {
                $params[] = isset($config['business_hours_automation_enabled']) ? ($config['business_hours_automation_enabled'] ? 1 : 0) : 0;
            }

            $stmt->execute($params);
        }
        
        error_log("[Customer Engagement] Configuração salva para empresa $companyId, instância $instanceName: " . json_encode($config));
    }

    /**
     * Verifica se uma coluna existe na tabela customer_engagement_config
     */
    private function engagementConfigColumnExists($pdo, $columnName, $forceRefresh = false)
    {
        static $columnCache = [];

        if ($forceRefresh && array_key_exists($columnName, $columnCache)) {
            unset($columnCache[$columnName]);
        }

        if (array_key_exists($columnName, $columnCache)) {
            return $columnCache[$columnName];
        }

        try {
            $safe = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$columnName);
            $stmt = $pdo->query("SHOW COLUMNS FROM customer_engagement_config LIKE '" . $safe . "'");
            $columnCache[$columnName] = $stmt !== false && (bool)$stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $columnCache[$columnName] = false;
        }

        return $columnCache[$columnName];
    }

    /**
     * Garante que a coluna business_hours_automation_enabled exista
     * para permitir persistir o toggle de automacao por expediente.
     */
    private function ensureEngagementAutomationColumn($pdo)
    {
        $columnName = 'business_hours_automation_enabled';

        if ($this->engagementConfigColumnExists($pdo, $columnName)) {
            return true;
        }

        try {
            $pdo->exec("ALTER TABLE customer_engagement_config ADD COLUMN business_hours_automation_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER scheduled_pause_message");
            return $this->engagementConfigColumnExists($pdo, $columnName, true);
        } catch (Exception $e) {
            error_log('[Customer Engagement] Nao foi possivel criar coluna business_hours_automation_enabled automaticamente: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obter configuração de engajamento de clientes
     */
    private function getEngagementConfig($companyId, $instanceName = null)
    {
        require_once __DIR__ . '/../config/db.php';
        $pdo = db();
        
        $sql = "SELECT * FROM customer_engagement_config WHERE company_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$companyId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($row) {
            return [
                'enabled' => (bool)$row['enabled'],
                'instance_name' => $row['instance_name'],
                'scenario1_enabled' => (bool)$row['scenario1_enabled'],
                'scenario1_delay' => (int)$row['scenario1_delay_minutes'],
                'scenario2_enabled' => (bool)$row['scenario2_enabled'],
                'scenario2_days' => (int)$row['scenario2_inactive_days'],
                'out_of_hours_enabled' => isset($row['out_of_hours_enabled']) ? (bool)$row['out_of_hours_enabled'] : true,
                'out_of_hours_message' => $row['out_of_hours_message'] ?? '',
                'scheduled_pause_enabled' => isset($row['scheduled_pause_enabled']) ? (bool)$row['scheduled_pause_enabled'] : true,
                'scheduled_pause_message' => $row['scheduled_pause_message'] ?? '',
                'business_hours_automation_enabled' => isset($row['business_hours_automation_enabled']) ? (bool)$row['business_hours_automation_enabled'] : false
            ];
        }
        
        // Retornar configuração padrão
        return [
            'enabled' => false,
            'instance_name' => $instanceName,
            'scenario1_enabled' => true,
            'scenario1_delay' => 10,
            'scenario2_enabled' => true,
            'scenario2_days' => 15,
            'out_of_hours_enabled' => true,
            'out_of_hours_message' => '',
            'scheduled_pause_enabled' => true,
            'scheduled_pause_message' => '',
            'business_hours_automation_enabled' => false
        ];
    }

    /**
     * Buscar instância que já tem engajamento ativado (exceto a atual)
     */
    private function getActiveEngagementInstance($companyId, $excludeInstanceName)
    {
        require_once __DIR__ . '/../config/db.php';
        $pdo = db();
        
        $sql = "SELECT instance_name FROM customer_engagement_config 
                WHERE company_id = ? AND enabled = 1 AND instance_name != ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$companyId, $excludeInstanceName]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($row) {
            return ['instance_name' => $row['instance_name']];
        }
        
        return null;
    }

    /**
     * Desativar engajamento em outras instâncias
     */
    private function disableOtherEngagementInstances($companyId, $excludeInstanceName)
    {
        require_once __DIR__ . '/../config/db.php';
        $pdo = db();
        
        $sql = "UPDATE customer_engagement_config SET enabled = 0, updated_at = NOW() 
                WHERE company_id = ? AND instance_name != ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$companyId, $excludeInstanceName]);
        
        error_log("[Customer Engagement] Desativado engajamento de outras instâncias para empresa $companyId");
    }

    /**
     * API - Estatísticas do Engajamento de Clientes
     */
    public function engagement_stats($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            [$user, $company] = $this->guard($slug);
            
            require_once __DIR__ . '/../config/db.php';
            $pdo = db();
            
            // Estatísticas dos últimos 30 dias
            $stats = [];
            
            // Total de mensagens enviadas
            $sql = "SELECT 
                        COUNT(*) as total_sent,
                        SUM(CASE WHEN scenario_type = 'signup_no_order' THEN 1 ELSE 0 END) as scenario1_sent,
                        SUM(CASE WHEN scenario_type = 'inactive_customer' THEN 1 ELSE 0 END) as scenario2_sent
                    FROM customer_engagement_log 
                    WHERE company_id = ? AND status = 'sent' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$company['id']]);
            $stats['messages'] = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Mensagens pendentes na fila
            $sql = "SELECT COUNT(*) as pending FROM customer_engagement_queue 
                    WHERE company_id = ? AND status = 'pending'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$company['id']]);
            $stats['queue'] = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Dead letter queue (falhas persistentes)
            $sql = "SELECT COUNT(*) as dead_letters FROM customer_engagement_queue 
                    WHERE company_id = ? AND status = 'failed' AND dead_letter_at IS NOT NULL";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$company['id']]);
            $stats['dlq'] = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Métricas de conversão (engajamento → pedido em 7 dias)
            require_once __DIR__ . '/../services/CustomerEngagementService.php';
            $engService = new \CustomerEngagementService((int)$company['id']);
            $stats['conversion'] = $engService->getConversionStats(30);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $stats]);
            
        } catch (Exception $e) {
            error_log('[Customer Engagement Stats] Erro: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor']);
        }
    }

    /**
     * Configura o webhook na Evolution API para resposta fora do expediente
     */
    private function configureOutOfHoursWebhook(array $company, string $instanceName): bool
    {
        $serverUrl = $company['evolution_server_url'] ?? '';
        $apiKey = $company['evolution_api_key'] ?? '';
        $slug = $company['slug'] ?? '';
        
        if (empty($serverUrl) || empty($apiKey)) {
            error_log("[Out of Hours Webhook] Configuração Evolution não encontrada para empresa {$company['id']}");
            return false;
        }
        
        $server = rtrim($serverUrl, '/');
        $webhookUrl = "https://{$slug}.online/webhook/evolution/{$instanceName}";
        
        // Configurar webhook na Evolution API
        $url = "{$server}/webhook/set/{$instanceName}";
        
        $payload = [
            'webhook' => [
                'enabled' => true,
                'url' => $webhookUrl,
                'webhookByEvents' => false,
                'webhookBase64' => false,
                'events' => [
                    'MESSAGES_UPSERT'
                ]
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'apikey: ' . $apiKey
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 15
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError || $httpCode < 200 || $httpCode >= 300) {
            error_log("[Out of Hours Webhook] Erro ao configurar webhook: HTTP {$httpCode}, cURL: {$curlError}, Response: {$response}");
            return false;
        }
        
        error_log("[Out of Hours Webhook] Webhook configurado com sucesso para {$instanceName}: {$webhookUrl}");
        return true;
    }

}