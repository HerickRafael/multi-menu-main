<?php

declare(strict_types=1);
// app/services/ProductCache.php

/**
 * Sistema de Cache para Produtos
 * Reduz carga no banco de dados cacheando produtos e grupos de combo
 */
class ProductCache
{
    private static $instance = null;
    private $redis = null;
    private $useRedis = false;
    private $ttl = 1800; // 30 minutos por padrão
    private $memoryCache = []; // Cache em memória para a requisição atual

    private function __construct()
    {
        $cfg = function_exists('config') ? config('redis') : [];
        $cfg = $cfg ?? [];
        
        // TTL configurável (padrão 30min)
        $this->ttl = isset($cfg['product_cache_ttl']) ? (int)$cfg['product_cache_ttl'] : 1800;

        // Tentar conectar ao Redis se habilitado
        if (!empty($cfg['enabled']) && extension_loaded('redis')) {
            try {
                if (!class_exists('Redis')) {
                    throw new RuntimeException('A classe Redis não está disponível. Certifique-se de que a extensão do Redis está instalada e habilitada.');
                }
                $redis = new Redis();
                $host = $cfg['host'] ?? '127.0.0.1';
                $port = isset($cfg['port']) ? (int)$cfg['port'] : 6379;
                $timeout = isset($cfg['timeout']) ? (float)$cfg['timeout'] : 1.5;
                $redis->connect($host, $port, $timeout);

                if (!empty($cfg['password'])) {
                    $redis->auth($cfg['password']);
                }

                if (isset($cfg['database'])) {
                    $redis->select((int)$cfg['database']);
                }
                
                $this->redis = $redis;
                $this->useRedis = true;
            } catch (Throwable $e) {
                $this->redis = null;
                $this->useRedis = false;
            }
        }
    }

    public static function instance(): ProductCache
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Busca produto no cache
     * @param int $productId
     * @return array|null
     */
    public function getProduct(int $productId): ?array
    {
        // 1. Verificar cache em memória (requisição atual)
        $memKey = "product_{$productId}";
        if (isset($this->memoryCache[$memKey])) {
            return $this->memoryCache[$memKey];
        }

        // 2. Verificar Redis
        if ($this->useRedis) {
            try {
                $cached = $this->redis->get("product:{$productId}");
                if ($cached !== false) {
                    $data = json_decode($cached, true);
                    if (is_array($data)) {
                        $this->memoryCache[$memKey] = $data;
                        return $data;
                    }
                }
            } catch (Throwable $e) {
                // Falha silenciosa, continuar sem cache
            }
        }

        return null;
    }

    /**
     * Salva produto no cache
     * @param int $productId
     * @param array $data
     */
    public function setProduct(int $productId, array $data): void
    {
        $memKey = "product_{$productId}";
        $this->memoryCache[$memKey] = $data;

        if ($this->useRedis) {
            try {
                $this->redis->setex(
                    "product:{$productId}",
                    $this->ttl,
                    json_encode($data)
                );
            } catch (Throwable $e) {
                // Falha silenciosa
            }
        }
    }

    /**
     * Busca grupos de combo no cache
     * @param int $productId
     * @return array|null
     */
    public function getComboGroups(int $productId): ?array
    {
        $memKey = "combo_groups_{$productId}";
        if (isset($this->memoryCache[$memKey])) {
            return $this->memoryCache[$memKey];
        }

        if ($this->useRedis) {
            try {
                $cached = $this->redis->get("combo_groups:{$productId}");
                if ($cached !== false) {
                    $data = json_decode($cached, true);
                    if (is_array($data)) {
                        $this->memoryCache[$memKey] = $data;
                        return $data;
                    }
                }
            } catch (Throwable $e) {
                // Falha silenciosa
            }
        }

        return null;
    }

    /**
     * Salva grupos de combo no cache
     * @param int $productId
     * @param array $groups
     */
    public function setComboGroups(int $productId, array $groups): void
    {
        $memKey = "combo_groups_{$productId}";
        $this->memoryCache[$memKey] = $groups;

        if ($this->useRedis) {
            try {
                $this->redis->setex(
                    "combo_groups:{$productId}",
                    $this->ttl,
                    json_encode($groups)
                );
            } catch (Throwable $e) {
                // Falha silenciosa
            }
        }
    }

    /**
     * Invalida cache de um produto específico
     * @param int $productId
     */
    public function invalidateProduct(int $productId): void
    {
        $memKey = "product_{$productId}";
        $comboKey = "combo_groups_{$productId}";
        unset($this->memoryCache[$memKey]);
        unset($this->memoryCache[$comboKey]);

        if ($this->useRedis) {
            try {
                $this->redis->del("product:{$productId}");
                $this->redis->del("combo_groups:{$productId}");
            } catch (Throwable $e) {
                // Falha silenciosa
            }
        }
    }

    /**
     * Invalida cache de todos os combos que contêm um produto simples específico
     * Deve ser chamado quando um produto simples é desativado/ativado
     * @param int $simpleProductId ID do produto simples
     */
    public function invalidateCombosContainingProduct(int $simpleProductId): void
    {
        try {
            // Buscar todos os combos que contêm este produto
            $pdo = db();
            $stmt = $pdo->prepare("
                SELECT DISTINCT g.product_id
                FROM combo_group_items gi
                INNER JOIN combo_groups g ON g.id = gi.group_id
                WHERE gi.simple_product_id = ?
            ");
            $stmt->execute([$simpleProductId]);
            $comboIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($comboIds as $comboId) {
                $this->invalidateProduct((int)$comboId);
            }
        } catch (Throwable $e) {
            // Falha silenciosa - em caso de erro, o cache expirará naturalmente
        }
    }

    /**
     * Invalida todo o cache de produtos
     * Útil após atualizações em massa
     */
    public function invalidateAll(): void
    {
        $this->memoryCache = [];

        if ($this->useRedis) {
            try {
                // Buscar todas as keys de produtos e deletar
                $keys = $this->redis->keys('product:*');
                if (!empty($keys)) {
                    $this->redis->del($keys);
                }
                $keys = $this->redis->keys('combo_groups:*');
                if (!empty($keys)) {
                    $this->redis->del($keys);
                }
            } catch (Throwable $e) {
                // Falha silenciosa
            }
        }
    }

    /**
     * Busca embedded_delivery_fee e cacheia
     * @param int $companyId
     * @return float
     */
    public function getEmbeddedDeliveryFee(int $companyId): float
    {
        $memKey = "embedded_fee_{$companyId}";
        
        // Cache em memória (dura toda a requisição)
        if (isset($this->memoryCache[$memKey])) {
            return (float)$this->memoryCache[$memKey];
        }

        // Cache Redis
        if ($this->useRedis) {
            try {
                $cached = $this->redis->get("embedded_fee:{$companyId}");
                if ($cached !== false) {
                    $fee = (float)$cached;
                    $this->memoryCache[$memKey] = $fee;
                    return $fee;
                }
            } catch (Throwable $e) {
                // Continuar sem cache
            }
        }

        // Buscar do banco
        try {
            $pdo = function_exists('db') ? db() : null;
            if ($pdo) {
                $stmt = $pdo->prepare('SELECT embedded_delivery_fee FROM companies WHERE id = ?');
                $stmt->execute([$companyId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $fee = (float)($result['embedded_delivery_fee'] ?? 0);

                // Cachear (TTL maior pois não muda com frequência)
                $this->memoryCache[$memKey] = $fee;
                if ($this->useRedis) {
                    try {
                        $this->redis->setex("embedded_fee:{$companyId}", 3600, (string)$fee);
                    } catch (Throwable $e) {
                        // Falha silenciosa
                    }
                }

                return $fee;
            }
        } catch (Throwable $e) {
            // Retornar 0 em caso de erro
        }

        return 0.0;
    }

    /**
     * Estatísticas de cache (útil para debug)
     * @return array
     */
    public function getStats(): array
    {
        return [
            'redis_enabled' => $this->useRedis,
            'ttl' => $this->ttl,
            'memory_cache_items' => count($this->memoryCache),
            'memory_keys' => array_keys($this->memoryCache),
        ];
    }
}
