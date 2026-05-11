<?php

declare(strict_types=1);
// app/services/CartStorage.php

require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/db.php';

class CartStorage
{
    private static $instance = null;

    private $redis = null;
    private $useRedis = false;
    private $ttl = 86400;
    private $dbAvailable = false;

    private function __construct()
    {
        if (function_exists('db')) {
            try {
                $pdo = db();

                if ($pdo instanceof PDO) {
                    $this->dbAvailable = true;
                }
            } catch (Throwable $e) {
                $this->dbAvailable = false;
            }
        }

        $cfg = config('redis') ?? [];
        $this->ttl = isset($cfg['ttl']) ? (int)$cfg['ttl'] : 86400;

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

    public static function instance(): CartStorage
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function ensureSession(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (class_exists('Auth') && method_exists('Auth', 'start')) {
                Auth::start();
            } else {
                $name = config('session_name') ?? 'mm_session';

                if ($name && session_name() !== $name) {
                    session_name($name);
                }
                session_start();
            }
        }
        $sid = session_id();

        if (!$sid) {
            $sid = session_create_id();
            session_id($sid);
        }

        return $sid;
    }

    private function key(string $sessionId, string $type): string
    {
        return 'mm:' . $type . ':' . $sessionId;
    }

    private function decode($payload): ?array
    {
        if ($payload === false || $payload === null || $payload === '') {
            return null;
        }
        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function encode(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    public function getCart(?string $sessionId = null): array
    {
        $sid = $sessionId ?: $this->ensureSession();

        if ($this->useRedis) {
            $cached = $this->decode($this->redis->get($this->key($sid, 'cart')));

            if ($cached !== null) {
                $_SESSION['cart'] = $cached;

                return $cached;
            }
        }

        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $dbRow = $this->loadFromDatabase($sid);

            if ($dbRow !== null) {
                $_SESSION['cart'] = $dbRow['cart'];
                $_SESSION['customizations'] = $dbRow['customizations'];

                if ($this->useRedis) {
                    $this->redis->setex($this->key($sid, 'cart'), $this->ttl, $this->encode($_SESSION['cart']));
                    $this->redis->setex($this->key($sid, 'customizations'), $this->ttl, $this->encode($_SESSION['customizations']));
                }
            }
        }

        // Fallback: se a sessão mudou de ID, tentar recuperar do session_id anterior via cookie
        if ((!isset($_SESSION['cart']) || empty($_SESSION['cart'])) && $sessionId === null) {
            $oldSid = $_COOKIE['mm_cart_sid'] ?? null;
            if ($oldSid && is_string($oldSid) && $oldSid !== $sid) {
                $recovered = $this->recoverCartFromOldSession($oldSid, $sid);
                if ($recovered) {
                    return $_SESSION['cart'];
                }
            }
        }

        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        return $_SESSION['cart'];
    }

    /**
     * Recupera carrinho de uma sessão anterior e migra para a sessão atual.
     */
    private function recoverCartFromOldSession(string $oldSid, string $newSid): bool
    {
        $cart = null;
        $customizations = null;

        // Tentar Redis primeiro
        if ($this->useRedis) {
            $cart = $this->decode($this->redis->get($this->key($oldSid, 'cart')));
            $customizations = $this->decode($this->redis->get($this->key($oldSid, 'customizations')));
        }

        // Fallback para DB
        if (!$cart) {
            $dbRow = $this->loadFromDatabase($oldSid);
            if ($dbRow) {
                $cart = $dbRow['cart'];
                $customizations = $dbRow['customizations'];
            }
        }

        if (!$cart || !is_array($cart) || empty($cart)) {
            return false;
        }

        // Migrar para a sessão atual
        $_SESSION['cart'] = $cart;
        $_SESSION['customizations'] = $customizations ?? [];

        // Salvar na sessão nova
        if ($this->useRedis) {
            $this->redis->setex($this->key($newSid, 'cart'), $this->ttl, $this->encode($cart));
            if ($customizations) {
                $this->redis->setex($this->key($newSid, 'customizations'), $this->ttl, $this->encode($customizations));
            }
        }
        $this->saveToDatabase($newSid, $cart, $customizations ?? []);

        return true;
    }

    public function setCart(array $cart, ?string $sessionId = null): void
    {
        $sid = $sessionId ?: $this->ensureSession();

        if ($this->useRedis) {
            $this->redis->setex($this->key($sid, 'cart'), $this->ttl, $this->encode($cart));
        }
        $_SESSION['cart'] = $cart;

        $this->saveToDatabase($sid, $cart, $_SESSION['customizations'] ?? []);

        // Salvar session_id em cookie de backup para recuperação do carrinho
        // se a sessão PHP mudar (regeneração, GC, etc.)
        if (!empty($cart) && php_sapi_name() !== 'cli' && !headers_sent()) {
            setcookie('mm_cart_sid', $sid, [
                'expires'  => time() + $this->ttl,
                'path'     => '/',
                'secure'   => !empty($_SERVER['HTTPS']),
                'httponly'  => true,
                'samesite' => 'Lax',
            ]);
        }
    }

    public function clearCart(?string $sessionId = null): void
    {
        $sid = $sessionId ?: $this->ensureSession();

        if ($this->useRedis) {
            $this->redis->del($this->key($sid, 'cart'));
            $this->redis->del($this->key($sid, 'customizations'));
        }
        $_SESSION['cart'] = [];
        $_SESSION['customizations'] = [];
        $this->deleteFromDatabase($sid);

        // Limpar cookie de backup
        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            setcookie('mm_cart_sid', '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => !empty($_SERVER['HTTPS']),
                'httponly'  => true,
                'samesite' => 'Lax',
            ]);
        }
    }

    // Removido: versão antiga de getCustomization. Usar apenas a versão contextualizada.

    /**
     * Salva personalização contextualizada (parentId:productId:unitN ou parentId:productId ou só productId)
     */
    public function setCustomization(int $productId, array $value, ?string $sessionId = null, ?int $parentId = null, ?int $unitIndex = null): void
    {
        $customs = $this->getCustomizations($sessionId);
        // Formato da chave: parentId:productId:unitN ou parentId:productId ou productId
        $key = $parentId ? ($parentId . ':' . $productId) : (string)$productId;
        if ($unitIndex !== null && $unitIndex > 0) {
            $key .= ':unit' . $unitIndex;
        }
        
        $customs[$key] = $value;
        $sid = $sessionId ?: $this->ensureSession();
        if ($this->useRedis) {
            if ($customs) {
                $this->redis->setex($this->key($sid, 'customizations'), $this->ttl, $this->encode($customs));
            } else {
                $this->redis->del($this->key($sid, 'customizations'));
            }
        }
        $_SESSION['customizations'] = $customs;
        $this->saveToDatabase($sid, $_SESSION['cart'] ?? [], $customs);
    }

    public function removeCustomization(int $productId, ?string $sessionId = null, ?int $parentId = null, ?int $unitIndex = null): void
    {
        $customs = $this->getCustomizations($sessionId);
        $key = $parentId ? ($parentId . ':' . $productId) : (string)$productId;
        if ($unitIndex !== null && $unitIndex > 0) {
            $key .= ':unit' . $unitIndex;
        }
        if (isset($customs[$key])) {
            unset($customs[$key]);
            $sid = $sessionId ?: $this->ensureSession();
            if ($this->useRedis) {
                if ($customs) {
                    $this->redis->setex($this->key($sid, 'customizations'), $this->ttl, $this->encode($customs));
                } else {
                    $this->redis->del($this->key($sid, 'customizations'));
                }
            }
            $_SESSION['customizations'] = $customs;
            $this->saveToDatabase($sid, $_SESSION['cart'] ?? [], $customs);
        }
    }

    public function getCustomizations(?string $sessionId = null): array
    {
        $sid = $sessionId ?: $this->ensureSession();

        if ($this->useRedis) {
            $cached = $this->decode($this->redis->get($this->key($sid, 'customizations')));

            if ($cached !== null) {
                $_SESSION['customizations'] = $cached;
                return $cached;
            }
        }

        if (!isset($_SESSION['customizations']) || !is_array($_SESSION['customizations'])) {
            $dbRow = $this->loadFromDatabase($sid);

            if ($dbRow !== null) {
                $_SESSION['cart'] = $dbRow['cart'];
                $_SESSION['customizations'] = $dbRow['customizations'];

                if ($this->useRedis) {
                    $this->redis->setex($this->key($sid, 'cart'), $this->ttl, $this->encode($_SESSION['cart']));
                    $this->redis->setex($this->key($sid, 'customizations'), $this->ttl, $this->encode($_SESSION['customizations']));
                }
            }
        }

        if (!isset($_SESSION['customizations']) || !is_array($_SESSION['customizations'])) {
            $_SESSION['customizations'] = [];
        }

        return $_SESSION['customizations'];
    }

    /**
     * Busca personalização contextualizada (parentId:productId:unitN ou parentId:productId ou só productId)
     */
    public function getCustomization(int $productId, ?string $sessionId = null, ?int $parentId = null, ?int $unitIndex = null): ?array
    {
        $customs = $this->getCustomizations($sessionId);
        // Formato da chave: parentId:productId:unitN ou parentId:productId ou productId
        $key = $parentId ? ($parentId . ':' . $productId) : (string)$productId;
        if ($unitIndex !== null && $unitIndex > 0) {
            $key .= ':unit' . $unitIndex;
        }
        
        return isset($customs[$key]) && is_array($customs[$key])
            ? $customs[$key]
            : null;
    }

    private function loadFromDatabase(string $sid): ?array
    {
        if (!$this->dbAvailable) {
            return null;
        }
        try {
            $pdo = db();

            if (!$pdo instanceof PDO) {
                return null;
            }
            $stmt = $pdo->prepare('SELECT cart_json, customizations_json FROM cart_sessions WHERE session_id = ? LIMIT 1');
            $stmt->execute([$sid]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return null;
            }
            $cart = $this->decode($row['cart_json']);
            $customs = $this->decode($row['customizations_json']);

            return [
                'cart' => $cart ?? [],
                'customizations' => $customs ?? [],
            ];
        } catch (Throwable $e) {
            $this->dbAvailable = false;

            return null;
        }
    }

    private function saveToDatabase(string $sid, array $cart, array $customs): void
    {
        if (!$this->dbAvailable) {
            return;
        }
        try {
            $pdo = db();

            if (!$pdo instanceof PDO) {
                return;
            }

            if (!$cart && !$customs) {
                $this->deleteFromDatabase($sid);

                return;
            }
            $stmt = $pdo->prepare('INSERT INTO cart_sessions (session_id, cart_json, customizations_json, created_at, updated_at)
                                   VALUES (?, ?, ?, NOW(), NOW())
                                   ON DUPLICATE KEY UPDATE cart_json = VALUES(cart_json),
                                                           customizations_json = VALUES(customizations_json),
                                                           updated_at = NOW()');
            $stmt->execute([
                $sid,
                $this->encode($cart),
                $this->encode($customs),
            ]);
        } catch (Throwable $e) {
            $this->dbAvailable = false;
        }
    }

    private function deleteFromDatabase(string $sid): void
    {
        if (!$this->dbAvailable) {
            return;
        }
        try {
            $pdo = db();

            if (!$pdo instanceof PDO) {
                return;
            }
            $stmt = $pdo->prepare('DELETE FROM cart_sessions WHERE session_id = ?');
            $stmt->execute([$sid]);
        } catch (Throwable $e) {
            $this->dbAvailable = false;
        }
    }
}
