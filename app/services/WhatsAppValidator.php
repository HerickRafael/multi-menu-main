<?php
declare(strict_types=1);
/**
 * WhatsAppValidator - Valida se um número existe no WhatsApp
 * 
 * OTIMIZADO v3: Evolution API + fallback com validação brasileira
 * - Usa instância do banco de dados (não busca na API)
 * - Cache Redis para evitar queries repetidas
 * - Fallback: validação estrutural de formato brasileiro
 * - wa.me como semi-validação adicional (detecta erros client-side no HTML)
 * - Nunca bloqueia usuário por falha de API externa
 */

require_once __DIR__ . '/../models/EvolutionInstance.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/SmartCache.php';

class WhatsAppValidator
{
    // Cache da instância por 10 minutos
    private static int $INSTANCE_CACHE_TTL = 600;
    
    // DDDs válidos no Brasil
    private static array $VALID_DDDS = [
        11,12,13,14,15,16,17,18,19, // SP
        21,22,24,                     // RJ
        27,28,                        // ES
        31,32,33,34,35,37,38,         // MG
        41,42,43,44,45,46,            // PR
        47,48,49,                     // SC
        51,53,54,55,                  // RS
        61,                           // DF
        62,64,                        // GO
        63,                           // TO
        65,66,                        // MT
        67,                           // MS
        68,                           // AC
        69,                           // RO
        71,73,74,75,77,               // BA
        79,                           // SE
        81,87,                        // PE
        82,                           // AL
        83,                           // PB
        84,                           // RN
        85,88,                        // CE
        86,89,                        // PI
        91,93,94,                     // PA
        92,97,                        // AM
        95,                           // RR
        96,                           // AP
        98,99,                        // MA
    ];
    
    /**
     * Verifica se o número existe no WhatsApp
     * Fluxo: Cache → Evolution API (2x) → Validação formato BR + wa.me
     */
    public static function validate(array $company, string $phoneE164): array
    {
        $companyId = (int)($company['id'] ?? 0);
        
        // Preparar número (normalizar para E.164)
        $number = normalizePhone($phoneE164);
        
        // Cache de resultados positivos (número existe) por 24h
        $cacheKey = "whatsapp:exists:{$number}";
        $cached = SmartCache::get($cacheKey);
        if ($cached === '1') {
            return ['exists' => true, 'checked' => true, 'error' => null, 'jid' => null, 'number' => $number];
        }
        
        // Verificar configurações da empresa para Evolution API
        $serverUrl = rtrim($company['evolution_server_url'] ?? '', '/');
        $apiKey = $company['evolution_api_key'] ?? null;
        
        // Se Evolution está configurada, tentar primeiro
        if ($serverUrl && $apiKey) {
            $instanceName = self::getInstanceName($companyId);
            
            if ($instanceName) {
                $result = self::checkViaEvolutionApi($serverUrl, $apiKey, $instanceName, $number);
                
                // Se Evolution respondeu com sucesso, retornar resultado
                if ($result['checked']) {
                    if ($result['exists']) {
                        SmartCache::set($cacheKey, '1', 86400);
                    }
                    return $result;
                }
                
                // Evolution falhou — tentar fallback
                error_log("[WhatsAppValidator] Evolution API falhou para {$number}, usando fallback");
            }
        }
        
        // Fallback 1: Validação estrutural de formato brasileiro
        $formatResult = self::checkBrazilianFormat($number);
        
        if (!$formatResult['valid']) {
            // Formato inválido — número certamente não é um celular brasileiro válido
            error_log("[WhatsAppValidator] Formato BR inválido para {$number}: " . $formatResult['reason']);
            return ['exists' => false, 'checked' => true, 'error' => null, 'method' => 'format_br'];
        }
        
        // Fallback 2: Semi-validação via wa.me (tenta detectar erro no HTML)
        $wameResult = self::checkViaWaMe($number);
        
        if ($wameResult['checked'] && !$wameResult['exists']) {
            // wa.me detectou número inválido
            return $wameResult;
        }
        
        // Formato BR válido + wa.me não detectou erro → permite login
        // (número tem formato correto de celular brasileiro)
        SmartCache::set($cacheKey, '1', 86400);
        error_log("[WhatsAppValidator] Fallback: formato BR válido para {$number}, permitindo");
        return ['exists' => true, 'checked' => true, 'error' => null, 'method' => 'format_br'];
    }
    
    /**
     * Invalida cache de instância
     */
    public static function invalidateInstanceCache(int $companyId): void
    {
        SmartCache::set("evolution:instance_name:{$companyId}", '', 1);
    }
    
    /**
     * Busca nome da instância Evolution do cache ou banco
     */
    private static function getInstanceName(int $companyId): ?string
    {
        $cacheKey = "evolution:instance_name:{$companyId}";
        $instanceName = SmartCache::get($cacheKey);
        
        if (!$instanceName) {
            $instances = EvolutionInstance::allForCompany($companyId);
            
            if (!empty($instances)) {
                $instanceName = $instances[0]['instance_identifier'] ?? null;
                
                if ($instanceName) {
                    SmartCache::set($cacheKey, $instanceName, self::$INSTANCE_CACHE_TTL);
                }
            }
        }
        
        return $instanceName ?: null;
    }
    
    /**
     * Verifica número via Evolution API (até 2 tentativas)
     */
    private static function checkViaEvolutionApi(
        string $serverUrl, 
        string $apiKey, 
        string $instanceName, 
        string $number
    ): array {
        $url = $serverUrl . '/chat/whatsappNumbers/' . urlencode($instanceName);
        
        $maxAttempts = 2;
        $response = null;
        $httpCode = 0;
        $curlError = '';

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'apikey: ' . $apiKey,
                ],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['numbers' => [$number]]),
                CURLOPT_TIMEOUT => 6,
                CURLOPT_CONNECTTIMEOUT => 3,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if (!$curlError && $httpCode === 200) {
                break;
            }

            if ($attempt < $maxAttempts) {
                usleep(300000); // 300ms antes de retry
            }
        }
        
        if ($curlError || $httpCode !== 200) {
            return ['exists' => true, 'checked' => false, 'error' => 'evolution_unavailable'];
        }
        
        $data = json_decode($response, true);
        
        if (!is_array($data) || empty($data[0])) {
            return ['exists' => true, 'checked' => false, 'error' => 'invalid_response'];
        }
        
        $exists = !empty($data[0]['exists']);
        
        return [
            'exists' => $exists,
            'checked' => true,
            'error' => null,
            'jid' => $data[0]['jid'] ?? null,
            'number' => $data[0]['number'] ?? null
        ];
    }
    
    /**
     * Validação estrutural de formato brasileiro
     * Verifica se o número segue o padrão real de celular/fixo BR
     * 
     * Formato celular BR: 55 + DDD(2) + 9 + 8 dígitos = 13 dígitos
     * Formato fixo BR:    55 + DDD(2) + 8 dígitos      = 12 dígitos
     */
    private static function checkBrazilianFormat(string $number): array
    {
        $digits = preg_replace('/[^0-9]/', '', $number);
        
        // Número muito curto ou muito longo
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return ['valid' => false, 'reason' => 'tamanho_invalido'];
        }
        
        // Se não começa com 55, pode ser número internacional — aceitar
        if (!str_starts_with($digits, '55')) {
            return ['valid' => true, 'reason' => 'internacional'];
        }
        
        // Número brasileiro: 55 + DDD(2) + número(8 ou 9)
        $withoutCountry = substr($digits, 2); // Remove o "55"
        
        if (strlen($withoutCountry) < 10 || strlen($withoutCountry) > 11) {
            return ['valid' => false, 'reason' => 'tamanho_br_invalido'];
        }
        
        // Número local (sem DDD)
        $local = substr($withoutCountry, 2);
        
        // Celular: deve começar com 9 e ter 9 dígitos
        if (strlen($local) === 9) {
            if ($local[0] !== '9') {
                return ['valid' => false, 'reason' => 'celular_sem_nove'];
            }
        }
        
        // Verificar números repetidos (ex: 99999999999)
        if (preg_match('/^(\d)\1+$/', $local)) {
            return ['valid' => false, 'reason' => 'digitos_repetidos'];
        }
        
        // Verificar sequências simples (1234567890, 0987654321)
        $sequential = true;
        for ($i = 1; $i < strlen($local); $i++) {
            if (abs((int)$local[$i] - (int)$local[$i-1]) !== 1) {
                $sequential = false;
                break;
            }
        }
        if ($sequential && strlen($local) >= 8) {
            return ['valid' => false, 'reason' => 'sequencia_numerica'];
        }
        
        return ['valid' => true, 'reason' => 'formato_ok'];
    }
    
    /**
     * Semi-validação via wa.me
     * 
     * wa.me sempre retorna HTTP 200 do servidor (validação real é client-side JS),
     * mas o HTML pode conter indicadores de erro em alguns casos.
     * Usado como camada adicional, nunca como validação única.
     */
    private static function checkViaWaMe(string $number): array
    {
        $cleanNumber = preg_replace('/[^0-9]/', '', $number);
        
        if (strlen($cleanNumber) < 10) {
            return ['exists' => false, 'checked' => true, 'error' => null, 'method' => 'wame'];
        }
        
        $url = 'https://wa.me/' . $cleanNumber;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml',
                'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8',
            ],
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError || $httpCode === 0) {
            // wa.me indisponível — inconclusivo (não bloqueia)
            return ['exists' => true, 'checked' => false, 'error' => 'wame_unavailable'];
        }
        
        // 404 = número definitivamente não existe
        if ($httpCode === 404) {
            return ['exists' => false, 'checked' => true, 'error' => null, 'method' => 'wame'];
        }
        
        if ($httpCode === 200 && is_string($body)) {
            $bodyLower = strtolower($body);
            
            // Indicadores de número INVÁLIDO renderizados no HTML
            $invalidIndicators = [
                'phone number shared via url is invalid',
                'check the phone number',
                'número de telefone compartilhado via url é inválido',
            ];
            
            foreach ($invalidIndicators as $indicator) {
                if (strpos($bodyLower, $indicator) !== false) {
                    error_log("[WhatsAppValidator] wa.me detectou número inválido: {$cleanNumber}");
                    return ['exists' => false, 'checked' => true, 'error' => null, 'method' => 'wame'];
                }
            }
        }
        
        // wa.me não detectou erro — inconclusivo (normal, validação é client-side)
        return ['exists' => true, 'checked' => false, 'error' => 'wame_inconclusive'];
    }
}
