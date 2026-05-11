<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

class AdminPaymentMethodController extends Controller
{
    private function labelFromLibraryIcon(string $iconPath): string
    {
        // Espera caminho como "/assets/card-brands/slug.ext"
        $base = trim($iconPath);
        $slug = strtolower(pathinfo($base, PATHINFO_FILENAME));
        $labels = [
            'visa' => 'Visa',
            'mastercard' => 'Mastercard',
            'elo' => 'Elo',
            'hipercard' => 'Hipercard',
            'amex' => 'American Express',
            'diners' => 'Diners Club',
            'pix' => 'Pix',
            'credit' => 'Crédito',
            'debit' => 'Débito',
            'voucher' => 'Vale-refeição',
            'others' => 'Outros',
            'cash' => 'Dinheiro',
        ];
        if (isset($labels[$slug])) {
            return $labels[$slug];
        }
        $fallback = ucwords(str_replace(['-', '_'], ' ', $slug));
        return $fallback !== '' ? $fallback : 'Pagamento';
    }

    private function normaliseLibraryIcon($icon): ?string
    {
        if (!is_string($icon)) {
            return null;
        }

        $icon = trim($icon);
        if ($icon === '') {
            return null;
        }

        if (str_starts_with($icon, '/assets/card-brands/')) {
            return $icon;
        }

        if (str_starts_with($icon, 'assets/card-brands/')) {
            return '/' . ltrim($icon, '/');
        }

        if (preg_match('#^https?://#i', $icon)) {
            $baseUrl = function_exists('base_url') ? (string)base_url() : '';
            $baseHost = $baseUrl !== '' ? parse_url($baseUrl, PHP_URL_HOST) : null;
            $basePort = $baseUrl !== '' ? parse_url($baseUrl, PHP_URL_PORT) : null;

            $iconHost = parse_url($icon, PHP_URL_HOST);
            $iconPort = parse_url($icon, PHP_URL_PORT);
            if ($baseHost && $iconHost && strcasecmp($baseHost, $iconHost) !== 0) {
                return null;
            }
            if ($basePort !== null && $iconPort !== null && (int)$basePort !== (int)$iconPort) {
                return null;
            }
            if ($basePort === null && $iconPort !== null && !in_array((int)$iconPort, [80, 443], true)) {
                return null;
            }

            $path = parse_url($icon, PHP_URL_PATH) ?: '';
            if ($path !== '') {
                $basePath = $baseUrl !== '' ? (parse_url($baseUrl, PHP_URL_PATH) ?? '') : '';
                $basePath = $basePath !== '' ? rtrim($basePath, '/') : '';
                if ($basePath !== '' && str_starts_with($path, $basePath)) {
                    $path = substr($path, strlen($basePath));
                    if ($path === '' || $path[0] !== '/') {
                        $path = '/' . ltrim($path, '/');
                    }
                }
                if (str_starts_with($path, '/assets/card-brands/')) {
                    return $path;
                }
            }
        }

        return null;
    }
    private function listBrandLibrary(): array
    {
        $root = dirname(__DIR__, 2);
        $dir = $root . '/public/assets/card-brands';
        $allowed = ['svg', 'png', 'jpg', 'jpeg', 'webp'];
        $labels = [
            'visa' => 'Visa',
            'mastercard' => 'Mastercard',
            'elo' => 'Elo',
            'hipercard' => 'Hipercard',
            'amex' => 'American Express',
            'diners' => 'Diners Club',
            'pix' => 'Pix',
            'credit' => 'Crédito (genérico)',
            'debit' => 'Débito (genérico)',
            'voucher' => 'Vale-refeição (genérico)',
            'others' => 'Outros (genérico)',
            'cash' => 'Dinheiro (genérico)'
        ];
        $items = [];
        if (is_dir($dir)) {
            foreach (scandir($dir) ?: [] as $file) {
                if ($file === '.' || $file === '..') continue;
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed, true)) continue;
                $slug = strtolower(pathinfo($file, PATHINFO_FILENAME));
                // não listar Pix na biblioteca (ícone interno será usado para Pix)
                if ($slug === 'pix') continue;
                $items[] = [
                    'slug' => $slug,
                    'label' => $labels[$slug] ?? ucwords(str_replace(['-', '_'], ' ', $slug)),
                    'url' => function_exists('base_url') ? base_url('assets/card-brands/' . $file) : '/assets/card-brands/' . $file,
                    'value' => '/assets/card-brands/' . $file,
                ];
            }
        }
        // ordena por label
        usort($items, fn($a, $b) => strcmp($a['label'], $b['label']));
        return $items;
    }
    private function uploadBrandIcon(): ?string
    {
        if (empty($_FILES['brand_icon']) || !is_array($_FILES['brand_icon'])) {
            return null;
        }
        $f = $_FILES['brand_icon'];
        if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $tmp = (string)($f['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return null;
        }

        $name = (string)($f['name'] ?? 'icon');
        $size = (int)($f['size'] ?? 0);
        // limite de ~2MB
        if ($size <= 0 || $size > 2 * 1024 * 1024) {
            return null;
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed = ['svg', 'png', 'jpg', 'jpeg', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            return null;
        }

        $root = dirname(__DIR__, 2); // raiz do projeto
        $uploadDir = $root . '/public/uploads';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }
        $rand = random_int(1000, 9999);
        $fileName = 'pm_brand_' . time() . '_' . $rand . '.' . $ext;
        $destPath = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($tmp, $destPath)) {
            return null;
        }

        // caminho público relativo
        return '/uploads/' . $fileName;
    }
    private function detectPixKeyType(string $key): string
    {
        $key = trim($key);
        if ($key === '') {
            return '';
        }

        if (filter_var($key, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }

        $digits = preg_replace('/\D+/', '', $key);
        if (strlen($digits) === 11) {
            return 'cpf';
        }

        if (strlen($digits) === 14) {
            return 'cnpj';
        }

        if (strlen($digits) >= 10 && strlen($digits) <= 13) {
            return 'telefone';
        }

        return 'aleatoria';
    }

    private function normaliseMeta($rawMeta): array
    {
        $meta = [];

        if (is_array($rawMeta)) {
            foreach ($rawMeta as $key => $value) {
                if (!is_string($key)) {
                    continue;
                }
                $value = trim((string)$value);
                if ($value === '') {
                    continue;
                }
                $meta[$key] = $value;
            }
        }

        return $meta;
    }

    private function buildIconUrlFromMeta(array $meta = []): string
    {
        $icon = '';
        if (!empty($meta['icon']) && is_string($meta['icon'])) {
            $icon = trim($meta['icon']);
        }
        if ($icon === '') return '';

        // URLs absolutas
        if (preg_match('#^https?://#i', $icon)) {
            return $icon;
        }

        // caminhos públicos conhecidos
        if (str_starts_with($icon, '/')) {
            // se houver base_url disponível, prefixa
            if (function_exists('base_url')) {
                $base = rtrim((string)base_url(), '/');
                return $base . $icon;
            }
            return $icon;
        }

        // caminhos relativos
        if (function_exists('base_url')) {
            return base_url($icon);
        }

        return '/' . ltrim($icon, '/');
    }

    private function guard(string $slug): array
    {
        Auth::start();
        $user = Auth::user();

        if (!$user) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
            exit;
        }

        $company = Company::findBySlug($slug);

        if (!$company) {
            echo 'Empresa inválida';
            exit;
        }

        if ($user['role'] !== 'root' && (int)($user['company_id'] ?? 0) !== (int)$company['id']) {
            echo 'Acesso negado';
            exit;
        }

        return [$user, $company];
    }

    private function flash(array $payload): void
    {
        $_SESSION['flash_payment'] = $payload;
    }

    private function previous(array $payload): void
    {
        $_SESSION['old_payment'] = $payload;
    }

    private function errors(array $payload): void
    {
        $_SESSION['errors_payment'] = $payload;
    }

    private function redirectToIndex(string $slug): void
    {
        header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/payment-methods'));
        exit;
    }

    public function index($params)
    {
        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guard($slug);
        $methods = PaymentMethod::allByCompany((int)$company['id']);

        $flash = $_SESSION['flash_payment'] ?? null;
        $old   = $_SESSION['old_payment'] ?? [
            'name' => '',
            'instructions' => '',
            'sort_order' => PaymentMethod::nextSortOrder((int)$company['id']),
            'active' => 1,
            'type' => 'credit',
            'meta' => [],
        ];
        $errors = $_SESSION['errors_payment'] ?? [];

        unset($_SESSION['flash_payment'], $_SESSION['old_payment'], $_SESSION['errors_payment']);

        $title = 'Métodos de pagamento - ' . ($company['name'] ?? '');
        $brandLibrary = $this->listBrandLibrary();

        return $this->view('admin/payments/index', compact('company', 'user', 'methods', 'flash', 'old', 'errors', 'title', 'brandLibrary'));
    }

    private function isAjaxRequest(): bool
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }
        if (!empty($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            return true;
        }
        return false;
    }

    public function store($params)
    {
        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guard($slug);

    $name = trim($_POST['name'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');
        $sortOrder = isset($_POST['sort_order']) && $_POST['sort_order'] !== ''
            ? (int)$_POST['sort_order']
            : PaymentMethod::nextSortOrder((int)$company['id']);
    // active não é mais definido no formulário de criação; controle é pela lista
    $active = 1; // por padrão, novo método entra ativo (pode ser alterado depois nos toggles)

        $allowedTypes = ['credit', 'debit', 'others', 'voucher', 'pix', 'cash'];
        $type = trim($_POST['type'] ?? 'others');
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'others';
        }

        // normaliza meta e padroniza nome para Pix quando necessário
        $meta = $this->normaliseMeta($_POST['meta'] ?? []);
        if ($type === 'pix' && $name === '') {
            $name = 'Pix';
        }

        // mapear pix_key (e metadados) quando tipo = pix
        $pixKey = '';
        if ($type === 'pix') {
            $pixKey = trim($meta['px_key'] ?? '');
            if ($pixKey === '') {
                $pixKey = trim($_POST['pix_key'] ?? '');
            }
            if ($pixKey === '') {
                $nameFallback = trim($name);
                if ($nameFallback !== '' && strcasecmp($nameFallback, 'Pix') !== 0) {
                    $pixKey = $nameFallback; // usuário pode ter digitado a chave no campo de nome
                }
            }
            if ($pixKey !== '') {
                $meta['px_key'] = $pixKey;
                $meta['px_key_type'] = $this->detectPixKeyType($pixKey);
            } else {
                unset($meta['px_key'], $meta['px_key_type']);
            }

            $pixHolder = trim($meta['px_holder_name'] ?? ($_POST['pix_holder_name'] ?? ''));
            if ($pixHolder !== '') {
                $meta['px_holder_name'] = $pixHolder;
            }
        }

        if ($type !== 'pix') {
            unset($meta['px_key'], $meta['px_provider'], $meta['px_holder_name'], $meta['px_key_type']);
        }

        // ícone: para Pix, não usa biblioteca nem upload
        if ($type === 'pix') {
            unset($meta['icon']);
        } else {
            // upload tem prioridade; caso contrário, aceitar seleção da biblioteca
            $icon = $this->uploadBrandIcon();
            if ($icon) {
                $meta['icon'] = $icon;
            } else {
                $libIcon = $this->normaliseLibraryIcon($meta['icon'] ?? null);
                if ($libIcon !== null) {
                    $meta['icon'] = $libIcon;
                } else {
                    unset($meta['icon']);
                }
            }
        }

        // se veio ícone da biblioteca e nome vazio (e não é Pix), deduz o nome pela biblioteca
        if ($type !== 'pix' && $name === '' && !empty($meta['icon']) && str_starts_with((string)$meta['icon'], '/assets/card-brands/')) {
            $name = $this->labelFromLibraryIcon((string)$meta['icon']);
        }

        // validação final do nome (após tratar biblioteca/pix)
        if ($name === '') {
            $this->errors(['Informe o nome do método de pagamento.']);
            $this->previous([
                'name' => $name,
                'instructions' => $instructions,
                'sort_order' => $sortOrder,
                'active' => $active,
                'type' => $type,
                'meta' => $meta,
            ]);
            $this->flash(['type' => 'error', 'message' => 'Não foi possível salvar o método.']);
            $this->redirectToIndex($company['slug']);
        }
        // para tipo pix, salva nome canônico
        $saveName = $type === 'pix' ? 'Pix' : $name;

        // Verificar duplicatas por nome ou ícone (quando aplicável) — restrito pelo tipo
        $iconCheck = isset($meta['icon']) ? trim((string)$meta['icon']) : '';
        try {
            if ($iconCheck !== '') {
                // usa JSON_EXTRACT para comparar exatamente o campo icon dentro do JSON meta, limitada pelo tipo
                $sql = 'SELECT id FROM payment_methods WHERE company_id = ? AND `type` = ? AND (name = ? OR JSON_UNQUOTE(JSON_EXTRACT(meta, "$.icon")) = ?) LIMIT 1';
                $st = db()->prepare($sql);
                $st->execute([$company['id'], $type, $saveName, $iconCheck]);
            } else {
                $sql = 'SELECT id FROM payment_methods WHERE company_id = ? AND `type` = ? AND name = ? LIMIT 1';
                $st = db()->prepare($sql);
                $st->execute([$company['id'], $type, $saveName]);
            }
            $exists = $st->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $exists = false;
        }

        if ($exists) {
            // já existe método com mesmo nome/ícone
            $message = 'Já existe um método com o mesmo nome ou bandeira neste tipo.';
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $message]);
                exit;
            }
            $this->errors([$message]);
            $this->previous([
                'name' => $name,
                'instructions' => $instructions,
                'sort_order' => $sortOrder,
                'active' => $active,
                'type' => $type,
                'meta' => $meta,
            ]);
            $this->flash(['type' => 'error', 'message' => 'Não foi possível salvar: método duplicado.']);
            $this->redirectToIndex($company['slug']);
        }

        // tenta reutilizar um ID vazio (casos onde registros foram apagados)
        $reuseId = null;
        try {
            $missing = PaymentMethod::findMissingId();
            if ($missing && $missing > 0) {
                $reuseId = $missing;
            }
        } catch (Exception $e) {
            $reuseId = null;
        }

        $payload = [
            'company_id' => (int)$company['id'],
            'name' => $saveName,
            'instructions' => $instructions !== '' ? $instructions : null,
            'sort_order' => $sortOrder,
            'active' => $active,
            'type' => $type,
            'meta' => $meta,
            'icon' => isset($meta['icon']) ? $meta['icon'] : null,
            'pix_key' => $pixKey ?: null,
        ];
        if ($reuseId) $payload['id'] = $reuseId;

        $newId = PaymentMethod::create($payload);

        // Carrega registro criado
        $created = PaymentMethod::findForCompany((int)$newId, (int)$company['id']);
        if ($created && isset($created['meta']) && is_string($created['meta'])) {
            $decodedMeta = json_decode($created['meta'], true);
            $created['meta'] = is_array($decodedMeta) ? $decodedMeta : [];
        }

        if ($this->isAjaxRequest()) {
            // garantir que o frontend tenha uma URL completa para o ícone
            if (isset($created['meta']) && is_array($created['meta'])) {
                $created['icon_url'] = $this->buildIconUrlFromMeta($created['meta']);
            } else {
                $created['icon_url'] = '';
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'method' => $created, 'message' => 'Método adicionado com sucesso.']);
            exit;
        }

        $this->flash(['type' => 'success', 'message' => 'Método adicionado com sucesso.']);
        $this->redirectToIndex($company['slug']);
    }

    public function update($params)
    {
        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guard($slug);

        $id = (int)($params['id'] ?? 0);
        $method = PaymentMethod::findForCompany($id, (int)$company['id']);

        if (!$method) {
            $this->flash(['type' => 'error', 'message' => 'Método não encontrado.']);
            $this->redirectToIndex($company['slug']);
        }

        $name = trim($_POST['name'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');
        $sortOrder = isset($_POST['sort_order']) && $_POST['sort_order'] !== ''
            ? (int)$_POST['sort_order']
            : (int)$method['sort_order'];
        // active pode vir no update (toggle via AJAX) ou não; quando não vier, mantém o atual
        if (isset($_POST['active'])) {
            $active = ($_POST['active'] == '1') ? 1 : 0;
        } else {
            $active = (int)($method['active'] ?? 0);
        }

        $allowedTypes = ['credit', 'debit', 'others', 'voucher', 'pix', 'cash'];
        $type = trim($_POST['type'] ?? ($method['type'] ?? 'others'));
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'others';
        }

        // meta existente do registro (caso POST não envie nada)
        $existingMeta = [];
        if (!empty($method['meta'])) {
            $decodedMeta = json_decode((string)$method['meta'], true);
            if (is_array($decodedMeta)) {
                $existingMeta = $this->normaliseMeta($decodedMeta);
            }
        }

        // normaliza meta vinda do POST; se vier vazio, reaproveita a existente
        $meta = $this->normaliseMeta($_POST['meta'] ?? []);
        if (!$meta && $existingMeta) {
            $meta = $existingMeta;
        }

        $isAjax = $this->isAjaxRequest();
        $hasName = $name !== '';

        if ($type === 'pix' && $name === '') {
            $name = 'Pix';
            $hasName = true;
        }

        if (!$hasName && !$isAjax) {
            $this->flash(['type' => 'error', 'message' => 'Informe o nome do método.']);
            $this->redirectToIndex($company['slug']);
        }

        if ($isAjax && !$hasName && empty($_POST['meta']) && !isset($_POST['type']) && !isset($_POST['instructions'])) {
            // atualização apenas de toggle: preserva dados existentes
            $name = (string)$method['name'];
            $instructions = (string)($method['instructions'] ?? '');
            $sortOrder = (int)$method['sort_order'];
            $type = (string)($method['type'] ?? 'others');
            $meta = $existingMeta;
        }

        // ícone: para Pix, não usa biblioteca nem upload; para outros, upload substitui; senão, aceitar biblioteca
        if ($type === 'pix') {
            unset($meta['icon']);
        } else {
            $icon = $this->uploadBrandIcon();
            if ($icon) {
                $meta['icon'] = $icon;
            } else {
                $libIcon = $this->normaliseLibraryIcon($meta['icon'] ?? null);
                if ($libIcon !== null) {
                    $meta['icon'] = $libIcon;
                } else {
                    if (!isset($existingMeta['icon'])) {
                        unset($meta['icon']);
                    }
                }
            }
        }

        // se veio ícone da biblioteca e nome vazio (e não é Pix), deduz o nome pela biblioteca
        if ($type !== 'pix' && $name === '' && !empty($meta['icon']) && str_starts_with((string)$meta['icon'], '/assets/card-brands/')) {
            $name = $this->labelFromLibraryIcon((string)$meta['icon']);
            $hasName = true;
        }

        // mapear pix_key (e metadados) quando tipo = pix
        $pixKey = '';
        if ($type === 'pix') {
            $pixKey = trim($meta['px_key'] ?? '');
            if ($pixKey === '') {
                $pixKey = trim($_POST['pix_key'] ?? '');
            }
            if ($pixKey === '') {
                $nameFallback = trim($name);
                if ($nameFallback !== '' && strcasecmp($nameFallback, 'Pix') !== 0) {
                    $pixKey = $nameFallback; // usuário pode ter digitado a chave no campo de nome
                }
            }
            if ($pixKey !== '') {
                $meta['px_key'] = $pixKey;
                $meta['px_key_type'] = $this->detectPixKeyType($pixKey);
            } else {
                unset($meta['px_key'], $meta['px_key_type']);
            }

            $pixHolder = trim($meta['px_holder_name'] ?? ($_POST['pix_holder_name'] ?? ''));
            if ($pixHolder !== '') {
                $meta['px_holder_name'] = $pixHolder;
            }
        }

        if ($type !== 'pix') {
            unset($meta['px_key'], $meta['px_provider'], $meta['px_holder_name'], $meta['px_key_type']);
        }

        $saveName = $type === 'pix' ? 'Pix' : $name;

        // Verificar duplicatas ao atualizar (mesmo company + mesmo type), ignorando o próprio id
        $iconCheck = isset($meta['icon']) ? trim((string)$meta['icon']) : '';
        try {
            if ($iconCheck !== '') {
                $sql = 'SELECT id FROM payment_methods WHERE company_id = ? AND `type` = ? AND id <> ? AND (name = ? OR JSON_UNQUOTE(JSON_EXTRACT(meta, "$.icon")) = ?) LIMIT 1';
                $st = db()->prepare($sql);
                $st->execute([$company['id'], $type, $id, $saveName, $iconCheck]);
            } else {
                $sql = 'SELECT id FROM payment_methods WHERE company_id = ? AND `type` = ? AND id <> ? AND name = ? LIMIT 1';
                $st = db()->prepare($sql);
                $st->execute([$company['id'], $type, $id, $saveName]);
            }
            $conflict = $st->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $conflict = false;
        }

        if ($conflict) {
            $message = 'Já existe um método com o mesmo nome ou bandeira neste tipo.';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $message]);
                exit;
            }
            $this->flash(['type' => 'error', 'message' => $message]);
            $this->redirectToIndex($company['slug']);
        }

        PaymentMethod::update($id, (int)$company['id'], [
            'name' => $saveName,
            'instructions' => $instructions !== '' ? $instructions : null,
            'sort_order' => $sortOrder,
            'active' => $active,
            'type' => $type,
            'meta' => $meta,
            'icon' => isset($meta['icon']) ? $meta['icon'] : null,
            'pix_key' => $pixKey ?: null,
        ]);

        $updated = PaymentMethod::findForCompany($id, (int)$company['id']);
        if ($updated && isset($updated['meta']) && is_string($updated['meta'])) {
            $decodedMeta = json_decode($updated['meta'], true);
            $updated['meta'] = is_array($decodedMeta) ? $decodedMeta : [];
        }

        if ($isAjax) {
            // anexar icon_url para facilitar renderização no frontend
            if (isset($updated['meta']) && is_array($updated['meta'])) {
                $updated['icon_url'] = $this->buildIconUrlFromMeta($updated['meta']);
            } else {
                $updated['icon_url'] = '';
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'method' => $updated, 'message' => 'Método atualizado.']);
            exit;
        }

        $this->flash(['type' => 'success', 'message' => 'Método atualizado.']);
        $this->redirectToIndex($company['slug']);
    }

    public function batchUpdate($params)
    {
        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guard($slug);

        $active = isset($_POST['active']) && $_POST['active'] == '1' ? 1 : 0;

        // perform update
        require_once __DIR__ . '/../models/PaymentMethod.php';
        try {
            PaymentMethod::setAllActiveForCompany((int)$company['id'], $active);
        } catch (Exception $e) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
            $this->flash(['type' => 'error', 'message' => 'Erro ao atualizar métodos.']);
            $this->redirectToIndex($company['slug']);
        }

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Métodos atualizados.']);
            exit;
        }

        $this->flash(['type' => 'success', 'message' => 'Métodos atualizados.']);
        $this->redirectToIndex($company['slug']);
    }

    public function destroy($params)
    {
        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guard($slug);

        $id = (int)($params['id'] ?? 0);
        PaymentMethod::delete($id, (int)$company['id']);

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Método removido.']);
            exit;
        }

        $this->flash(['type' => 'success', 'message' => 'M e9todo removido.']);
        $this->redirectToIndex($company['slug']);
    }
}
