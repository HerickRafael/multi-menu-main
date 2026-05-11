<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

/**
 * Controller Mobile para Configurações
 * UI/UX otimizado para toque
 */
class MobileAdminSettingsController extends Controller
{
    private function guard(): array
    {
        Auth::start();
        $user = Auth::user();
        $slug = $_SERVER['MOBILE_SLUG'] ?? 'wollburger';
        
        if (!$user) {
            header('Location: /login');
            exit;
        }
        
        $company = Company::findBySlug($slug);
        if (!$company) {
            http_response_code(404);
            echo 'Empresa não encontrada';
            exit;
        }
        
        $isRoot = $user['role'] === 'root';
        if (!$isRoot && (int)$user['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            echo 'Acesso negado';
            exit;
        }
        
        return [$user, $company, $slug];
    }

    /**
     * GET /settings - Menu de configurações
     */
    public function index(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $success = $_SESSION['flash_success'] ?? null;
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        
        return $this->viewMobile('settings/index', [
            'company' => $company,
            'user' => $user,
            'success' => $success,
            'error' => $error,
            'pageTitle' => 'Configurações',
            'activeNav' => 'settings'
        ]);
    }

    /**
     * GET /settings/store - Config da loja (Dados, API, Cores, Imagens)
     */
    public function store(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        // Cores padrão
        $colorDefaults = [
            'menu_header_text_color'       => '#FFFFFF',
            'menu_header_button_color'     => '#FACC15',
            'menu_header_bg_color'         => '#4361ee',
            'menu_logo_border_color'       => '#7C3AED',
            'menu_group_title_bg_color'    => '#FACC15',
            'menu_group_title_text_color'  => '#000000',
            'menu_welcome_bg_color'        => '#6B21A8',
            'menu_welcome_text_color'      => '#FFFFFF',
        ];
        
        $colorValues = [];
        foreach ($colorDefaults as $key => $default) {
            $colorValues[$key] = $this->normalizeColor($company[$key] ?? null, $default);
        }
        
        // Aba ativa (via query string)
        $activeTab = $_GET['tab'] ?? 'dados';
        
        return $this->viewMobile('settings/store', [
            'company' => $company,
            'colorValues' => $colorValues,
            'colorDefaults' => $colorDefaults,
            'activeTab' => $activeTab,
            'pageTitle' => 'Configurações da Loja',
            'activeNav' => 'settings',
            'showBackButton' => true
        ]);
    }
    
    /**
     * Normaliza cor hexadecimal
     */
    private function normalizeColor(?string $value, ?string $fallback = null): ?string
    {
        $value = trim((string)($value ?? ''));
        if ($value === '') {
            return $fallback;
        }
        if ($value[0] !== '#') {
            $value = '#' . $value;
        }
        if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
            return $fallback;
        }
        if (strlen($value) === 4) {
            $value = '#' . $value[1] . $value[1] . $value[2] . $value[2] . $value[3] . $value[3];
        }
        return strtoupper($value);
    }
    
    /**
     * Normaliza WhatsApp
     */
    private function normalizeWhatsapp(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw);
        $digits = substr($digits, 0, 15);
        if ($digits !== '' && strlen($digits) <= 11 && strpos($digits, '55') !== 0) {
            $digits = '55' . $digits;
        }
        return $digits;
    }

    /**
     * POST /settings/store - Salvar config loja (todos os campos)
     */
    public function updateStore(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        
        // Campos básicos
        $name = trim($_POST['name'] ?? '');
        $whatsapp = $this->normalizeWhatsapp($_POST['whatsapp'] ?? $company['whatsapp'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $minOrder = ($_POST['min_order'] === '' ? null : (float)str_replace(',', '.', $_POST['min_order'] ?? '0'));
        
        // Tempo médio de entrega
        $avgFrom = (isset($_POST['avg_delivery_min_from']) && $_POST['avg_delivery_min_from'] !== '') 
                    ? (int)$_POST['avg_delivery_min_from'] : null;
        $avgTo = (isset($_POST['avg_delivery_min_to']) && $_POST['avg_delivery_min_to'] !== '') 
                    ? (int)$_POST['avg_delivery_min_to'] : null;
        
        if (empty($name)) {
            $_SESSION['flash_error'] = 'Nome é obrigatório';
            header('Location: /settings/store');
            exit;
        }
        
        // Cores
        $headerTextColor   = $this->normalizeColor($_POST['menu_header_text_color'] ?? null, $company['menu_header_text_color'] ?? null);
        $headerButtonColor = $this->normalizeColor($_POST['menu_header_button_color'] ?? null, $company['menu_header_button_color'] ?? null);
        $headerBgColor     = $this->normalizeColor($_POST['menu_header_bg_color'] ?? null, $company['menu_header_bg_color'] ?? null);
        $logoBorderColor   = $this->normalizeColor($_POST['menu_logo_border_color'] ?? null, $company['menu_logo_border_color'] ?? null);
        $groupBgColor      = $this->normalizeColor($_POST['menu_group_title_bg_color'] ?? null, $company['menu_group_title_bg_color'] ?? null);
        $groupTextColor    = $this->normalizeColor($_POST['menu_group_title_text_color'] ?? null, $company['menu_group_title_text_color'] ?? null);
        $welcomeBgColor    = $this->normalizeColor($_POST['menu_welcome_bg_color'] ?? null, $company['menu_welcome_bg_color'] ?? null);
        $welcomeTextColor  = $this->normalizeColor($_POST['menu_welcome_text_color'] ?? null, $company['menu_welcome_text_color'] ?? null);
        
        // Evolution API
        $evoServer = trim($_POST['evolution_server_url'] ?? $company['evolution_server_url'] ?? '');
        $evoKey    = trim($_POST['evolution_api_key'] ?? $company['evolution_api_key'] ?? '');
        
        // Uploads
        $logo = $company['logo'] ?? null;
        $banner = $company['banner'] ?? null;
        
        if (!empty($_FILES['logo']['tmp_name'])) {
            $newLogo = $this->handleUpload($_FILES['logo'], 'logo');
            if ($newLogo) {
                $logo = $newLogo;
            }
        }
        
        if (!empty($_FILES['banner']['tmp_name'])) {
            $newBanner = $this->handleUpload($_FILES['banner'], 'banner');
            if ($newBanner) {
                $banner = $newBanner;
            }
        }
        
        $pdo = db();
        $stmt = $pdo->prepare("
            UPDATE companies SET 
                name = ?, whatsapp = ?, address = ?, min_order = ?,
                avg_delivery_min_from = ?, avg_delivery_min_to = ?,
                menu_header_text_color = ?, menu_header_button_color = ?,
                menu_header_bg_color = ?, menu_logo_border_color = ?,
                menu_group_title_bg_color = ?, menu_group_title_text_color = ?,
                menu_welcome_bg_color = ?, menu_welcome_text_color = ?,
                evolution_server_url = ?, evolution_api_key = ?,
                ga_measurement_id = ?,
                logo = ?, banner = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $gaId = preg_match('/^G-[A-Z0-9]{6,12}$/i', trim($_POST['ga_measurement_id'] ?? '')) ? trim($_POST['ga_measurement_id']) : '';
        $stmt->execute([
            $name, $whatsapp, $address, $minOrder,
            $avgFrom, $avgTo,
            $headerTextColor, $headerButtonColor,
            $headerBgColor, $logoBorderColor,
            $groupBgColor, $groupTextColor,
            $welcomeBgColor, $welcomeTextColor,
            $evoServer, $evoKey,
            $gaId,
            $logo, $banner, $companyId
        ]);
        
        $_SESSION['flash_success'] = 'Configurações salvas!';
        header('Location: /settings/store?tab=' . ($_POST['active_tab'] ?? 'dados'));
        exit;
    }

    /**
     * GET /settings/hours - Horários
     */
    public function hours(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        
        // Carregar horários da tabela company_hours (mesma do desktop)
        $hours = $this->loadHours($companyId);
        
        return $this->viewMobile('settings/hours', [
            'company' => $company,
            'hours' => $hours,
            'pageTitle' => 'Horários',
            'activeNav' => 'settings',
            'showBackButton' => true
        ]);
    }
    
    /**
     * Carrega horários da tabela company_hours (mesma estrutura do desktop)
     */
    private function loadHours(int $companyId): array
    {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM company_hours WHERE company_id = ? ORDER BY weekday');
        $stmt->execute([$companyId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Se não existir, criar registros padrão
        if (!$rows) {
            for ($d = 1; $d <= 7; $d++) {
                $pdo->prepare('INSERT INTO company_hours (company_id, weekday, is_open) VALUES (?, ?, 0)')
                   ->execute([$companyId, $d]);
            }
            $stmt->execute([$companyId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        
        $by = [];
        foreach ($rows as $r) {
            $by[(int)$r['weekday']] = $r;
        }
        
        return $by;
    }
    
    /**
     * Normaliza horário para formato HH:MM:SS
     */
    private function parseTime(?string $t): ?string
    {
        $t = trim((string)$t);
        if ($t === '') {
            return null;
        }
        
        // Formato HH:MM (input type=time)
        if (preg_match('/^\d{1,2}:\d{2}$/', $t)) {
            return $t . ':00';
        }
        
        // Já está no formato HH:MM:SS
        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $t)) {
            return $t;
        }
        
        return null;
    }

    /**
     * POST /settings/hours - Salvar horários
     */
    public function updateHours(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        
        $pdo = db();
        
        // Mapear dias da semana: 1=Segunda, 2=Terça, ..., 7=Domingo
        $dayMapping = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 7
        ];
        
        foreach ($dayMapping as $dayKey => $weekday) {
            $isOpen = isset($_POST[$dayKey . '_active']) ? 1 : 0;
            $open1 = $this->parseTime($_POST[$dayKey . '_open1'] ?? null);
            $close1 = $this->parseTime($_POST[$dayKey . '_close1'] ?? null);
            $open2 = $this->parseTime($_POST[$dayKey . '_open2'] ?? null);
            $close2 = $this->parseTime($_POST[$dayKey . '_close2'] ?? null);
            
            // Se fechado, limpar horários
            if (!$isOpen) {
                $open1 = $close1 = $open2 = $close2 = null;
            }
            
            $stmt = $pdo->prepare('UPDATE company_hours SET is_open = ?, open1 = ?, close1 = ?, open2 = ?, close2 = ? WHERE company_id = ? AND weekday = ?');
            $stmt->execute([$isOpen, $open1, $close1, $open2, $close2, $companyId, $weekday]);
        }
        
        $_SESSION['flash_success'] = 'Horários atualizados!';
        header('Location: /settings');
        exit;
    }

    /**
     * GET /settings/delivery - Taxas de entrega
     */
    public function delivery(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        
        $pdo = db();
        
        // Carregar cidades
        $stmt = $pdo->prepare("SELECT * FROM delivery_cities WHERE company_id = ? ORDER BY name ASC");
        $stmt->execute([$companyId]);
        $cities = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Carregar zonas com nome da cidade
        $stmt = $pdo->prepare("
            SELECT dz.*, dc.name as city_name 
            FROM delivery_zones dz 
            LEFT JOIN delivery_cities dc ON dz.city_id = dc.id 
            WHERE dz.company_id = ? 
            ORDER BY dc.name, dz.neighborhood ASC
        ");
        $stmt->execute([$companyId]);
        $zones = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Contar zonas por cidade
        $zoneCountByCity = [];
        foreach ($zones as $zone) {
            $cityId = (int)($zone['city_id'] ?? 0);
            if (!isset($zoneCountByCity[$cityId])) {
                $zoneCountByCity[$cityId] = 0;
            }
            $zoneCountByCity[$cityId]++;
        }
        
        // Verificar se está editando
        $editCityId = (int)($_GET['edit_city'] ?? 0);
        $editZoneId = (int)($_GET['edit_zone'] ?? 0);
        $editCity = null;
        $editZone = null;
        
        if ($editCityId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM delivery_cities WHERE id = ? AND company_id = ?");
            $stmt->execute([$editCityId, $companyId]);
            $editCity = $stmt->fetch(\PDO::FETCH_ASSOC);
        }
        
        if ($editZoneId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM delivery_zones WHERE id = ? AND company_id = ?");
            $stmt->execute([$editZoneId, $companyId]);
            $editZone = $stmt->fetch(\PDO::FETCH_ASSOC);
        }
        
        return $this->viewMobile('settings/delivery', [
            'company' => $company,
            'cities' => $cities,
            'zones' => $zones,
            'zoneCountByCity' => $zoneCountByCity,
            'editCity' => $editCity,
            'editZone' => $editZone,
            'pageTitle' => 'Entrega',
            'activeNav' => 'settings',
            'showBackButton' => true
        ]);
    }
    
    /**
     * POST /settings/delivery/city - Criar/Atualizar cidade
     */
    public function saveCity(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        $pdo = db();
        
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        
        if (empty($name)) {
            $_SESSION['flash_error'] = 'Nome da cidade é obrigatório.';
            header('Location: /settings/delivery');
            exit;
        }
        
        if ($id > 0) {
            // Update
            $stmt = $pdo->prepare("UPDATE delivery_cities SET name = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$name, $id, $companyId]);
            $_SESSION['flash_success'] = 'Cidade atualizada!';
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO delivery_cities (company_id, name, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$companyId, $name]);
            $_SESSION['flash_success'] = 'Cidade cadastrada!';
        }
        
        header('Location: /settings/delivery');
        exit;
    }
    
    /**
     * POST /settings/delivery/city/{id}/delete - Excluir cidade
     */
    public function deleteCity(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        $id = (int)($params['id'] ?? 0);
        $pdo = db();
        
        // Excluir zonas vinculadas primeiro
        $stmt = $pdo->prepare("DELETE FROM delivery_zones WHERE city_id = ? AND company_id = ?");
        $stmt->execute([$id, $companyId]);
        
        // Excluir cidade
        $stmt = $pdo->prepare("DELETE FROM delivery_cities WHERE id = ? AND company_id = ?");
        $stmt->execute([$id, $companyId]);
        
        $_SESSION['flash_success'] = 'Cidade excluída!';
        header('Location: /settings/delivery');
        exit;
    }
    
    /**
     * POST /settings/delivery/zone - Criar/Atualizar zona/bairro
     */
    public function saveZone(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        $pdo = db();
        
        $id = (int)($_POST['id'] ?? 0);
        $cityId = (int)($_POST['city_id'] ?? 0);
        $neighborhood = trim($_POST['neighborhood'] ?? '');
        $fee = (float)($_POST['fee'] ?? 0);
        
        if ($cityId <= 0) {
            $_SESSION['flash_error'] = 'Selecione uma cidade.';
            header('Location: /settings/delivery');
            exit;
        }
        
        if (empty($neighborhood)) {
            $_SESSION['flash_error'] = 'Nome do bairro é obrigatório.';
            header('Location: /settings/delivery');
            exit;
        }
        
        if ($id > 0) {
            // Update
            $stmt = $pdo->prepare("UPDATE delivery_zones SET city_id = ?, neighborhood = ?, fee = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$cityId, $neighborhood, $fee, $id, $companyId]);
            $_SESSION['flash_success'] = 'Bairro atualizado!';
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO delivery_zones (company_id, city_id, neighborhood, fee, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$companyId, $cityId, $neighborhood, $fee]);
            $_SESSION['flash_success'] = 'Bairro cadastrado!';
        }
        
        header('Location: /settings/delivery');
        exit;
    }
    
    /**
     * POST /settings/delivery/zone/{id}/delete - Excluir zona/bairro
     */
    public function deleteZone(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        $id = (int)($params['id'] ?? 0);
        $pdo = db();
        
        $stmt = $pdo->prepare("DELETE FROM delivery_zones WHERE id = ? AND company_id = ?");
        $stmt->execute([$id, $companyId]);
        
        $_SESSION['flash_success'] = 'Bairro excluído!';
        header('Location: /settings/delivery');
        exit;
    }
    
    /**
     * POST /settings/delivery/adjust - Ajuste em lote das taxas
     */
    public function adjustFees(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        $pdo = db();
        
        $delta = (float)($_POST['delta'] ?? 0);
        
        if ($delta == 0) {
            $_SESSION['flash_error'] = 'Informe um valor diferente de zero.';
            header('Location: /settings/delivery');
            exit;
        }
        
        // Ajustar todas as taxas (não deixar ficar negativo)
        $stmt = $pdo->prepare("UPDATE delivery_zones SET fee = GREATEST(0, fee + ?) WHERE company_id = ?");
        $stmt->execute([$delta, $companyId]);
        
        $signal = $delta > 0 ? '+' : '';
        $_SESSION['flash_success'] = "Todas as taxas foram ajustadas em {$signal}" . number_format($delta, 2, ',', '.') . "!";
        header('Location: /settings/delivery');
        exit;
    }

    /**
     * POST /settings/delivery/options - Salvar Taxa Após 18h + Toggle Taxa Gratuita
     */
    public function updateDeliveryOptions(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        $pdo = db();

        $afterHoursFee = (float)($_POST['after_hours_fee'] ?? 0);
        $freeDelivery  = (int)($_POST['free_delivery'] ?? 0);

        if ($afterHoursFee < 0) $afterHoursFee = 0;
        $freeDelivery = $freeDelivery ? 1 : 0;

        // Se ativar taxa gratuita, desabilita frete grátis promocional
        if ($freeDelivery) {
            $stmt = $pdo->prepare("UPDATE companies SET delivery_after_hours_fee = ?, delivery_free_enabled = 1, delivery_free_min_value = 0 WHERE id = ?");
            $stmt->execute([$afterHoursFee, $companyId]);
            $_SESSION['flash_success'] = 'Taxa gratuita ativada! Frete grátis promocional foi desativado.';
        } else {
            $stmt = $pdo->prepare("UPDATE companies SET delivery_after_hours_fee = ?, delivery_free_enabled = 0 WHERE id = ?");
            $stmt->execute([$afterHoursFee, $companyId]);
            $_SESSION['flash_success'] = 'Opções de entrega atualizadas!';
        }

        header('Location: /settings/delivery');
        exit;
    }

    /**
     * POST /settings/delivery/free-shipping - Salvar Frete Grátis Promocional
     */
    public function updateFreeShipping(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        $pdo = db();

        $minValue = (float)($_POST['delivery_free_min_value'] ?? 0);
        if ($minValue < 0) $minValue = 0;

        if ($minValue > 0) {
            // Se ativar frete grátis promo, desativa taxa gratuita geral
            $stmt = $pdo->prepare("UPDATE companies SET delivery_free_min_value = ?, delivery_free_enabled = 0 WHERE id = ?");
            $stmt->execute([$minValue, $companyId]);
            $_SESSION['flash_success'] = 'Frete grátis promocional ativado para pedidos acima de R$ ' . number_format($minValue, 2, ',', '.') . '!';
        } else {
            $stmt = $pdo->prepare("UPDATE companies SET delivery_free_min_value = 0 WHERE id = ?");
            $stmt->execute([$companyId]);
            $_SESSION['flash_success'] = 'Frete grátis promocional desativado.';
        }

        header('Location: /settings/delivery');
        exit;
    }

    /**
     * GET /settings/payments - Formas de pagamento
     */
    public function payments(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE company_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$companyId]);
        $methods = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Normalizar meta para array
        foreach ($methods as &$m) {
            if (is_string($m['meta'] ?? null)) {
                $m['meta'] = json_decode($m['meta'], true) ?: [];
            }
            $m['meta'] = is_array($m['meta'] ?? null) ? $m['meta'] : [];
        }
        unset($m);
        
        // Verificar se está editando
        $editMethodId = (int)($_GET['edit'] ?? 0);
        $editMethod = null;
        
        if ($editMethodId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE id = ? AND company_id = ?");
            $stmt->execute([$editMethodId, $companyId]);
            $editMethod = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($editMethod && is_string($editMethod['meta'] ?? null)) {
                $editMethod['meta'] = json_decode($editMethod['meta'], true) ?: [];
            }
        }
        
        // Lista de ícones disponíveis
        $brandIcons = $this->listBrandIcons();
        
        // Tipos disponíveis
        $paymentTypes = [
            'pix' => 'Pix',
            'credit' => 'Crédito',
            'debit' => 'Débito',
            'cash' => 'Dinheiro',
            'voucher' => 'Vale-refeição',
            'others' => 'Outros'
        ];
        
        return $this->viewMobile('settings/payments', [
            'company' => $company,
            'methods' => $methods,
            'editMethod' => $editMethod,
            'brandIcons' => $brandIcons,
            'paymentTypes' => $paymentTypes,
            'pageTitle' => 'Pagamentos',
            'activeNav' => 'settings',
            'showBackButton' => true
        ]);
    }
    
    /**
     * Lista ícones de bandeiras disponíveis
     */
    private function listBrandIcons(): array
    {
        $dir = dirname(__DIR__, 2) . '/public/assets/card-brands';
        $icons = [];
        
        if (is_dir($dir)) {
            foreach (scandir($dir) as $file) {
                if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['svg', 'png', 'jpg', 'webp'])) {
                    $name = pathinfo($file, PATHINFO_FILENAME);
                    $icons[] = [
                        'path' => '/assets/card-brands/' . $file,
                        'name' => ucfirst(str_replace(['-', '_'], ' ', $name))
                    ];
                }
            }
        }
        
        return $icons;
    }
    
    /**
     * POST /settings/payments/save - Criar/Atualizar método
     */
    public function savePayment(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        $pdo = db();
        
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['type'] ?? 'others');
        $active = isset($_POST['active']) ? 1 : 0;
        $icon = trim($_POST['icon'] ?? '');
        
        // Para Pix, guardar chave e tipo
        $meta = [];
        if ($type === 'pix') {
            $meta['px_key'] = trim($_POST['pix_key'] ?? '');
            $meta['px_key_type'] = trim($_POST['pix_key_type'] ?? '');
            $meta['px_holder_name'] = trim($_POST['pix_holder_name'] ?? '');
            if (empty($name)) {
                $name = 'Pix';
            }
            $icon = '/assets/card-brands/pix.svg';
        }
        
        if ($type === 'cash') {
            if (empty($name)) {
                $name = 'Dinheiro';
            }
            $icon = '/assets/card-brands/cash.svg';
        }
        
        if (!empty($icon)) {
            $meta['icon'] = $icon;
        }
        
        if (empty($name)) {
            $_SESSION['flash_error'] = 'Nome do método é obrigatório.';
            header('Location: /settings/payments');
            exit;
        }
        
        $metaJson = json_encode($meta);
        
        if ($id > 0) {
            // Update
            $stmt = $pdo->prepare("UPDATE payment_methods SET name = ?, type = ?, active = ?, meta = ?, updated_at = NOW() WHERE id = ? AND company_id = ?");
            $stmt->execute([$name, $type, $active, $metaJson, $id, $companyId]);
            $_SESSION['flash_success'] = 'Método atualizado!';
        } else {
            // Pegar próximo sort_order
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 as next FROM payment_methods WHERE company_id = ?");
            $stmt->execute([$companyId]);
            $nextOrder = (int)$stmt->fetchColumn();
            
            // Insert
            $stmt = $pdo->prepare("INSERT INTO payment_methods (company_id, name, type, active, meta, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$companyId, $name, $type, $active, $metaJson, $nextOrder]);
            $_SESSION['flash_success'] = 'Método cadastrado!';
        }
        
        header('Location: /settings/payments');
        exit;
    }
    
    /**
     * POST /settings/payments/{id}/delete - Excluir método
     */
    public function deletePayment(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $companyId = (int)$company['id'];
        $id = (int)($params['id'] ?? 0);
        $pdo = db();
        
        $stmt = $pdo->prepare("DELETE FROM payment_methods WHERE id = ? AND company_id = ?");
        $stmt->execute([$id, $companyId]);
        
        $_SESSION['flash_success'] = 'Método excluído!';
        header('Location: /settings/payments');
        exit;
    }

    /**
     * POST /settings/payments/{id}/toggle - Toggle pagamento
     */
    public function togglePayment(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        $id = (int)($params['id'] ?? 0);
        
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE id = ? AND company_id = ?");
        $stmt->execute([$id, (int)$company['id']]);
        $method = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$method) {
            // Verificar se é AJAX
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                $this->jsonResponse(['success' => false, 'message' => 'Não encontrado']);
                return;
            }
            $_SESSION['flash_error'] = 'Método não encontrado.';
            header('Location: /settings/payments');
            exit;
        }
        
        $newStatus = $method['active'] ? 0 : 1;
        
        $updateStmt = $pdo->prepare("UPDATE payment_methods SET active = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$newStatus, $id]);
        
        // Verificar se é AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->jsonResponse([
                'success' => true,
                'active' => $newStatus,
                'message' => $newStatus ? 'Ativado' : 'Desativado'
            ]);
            return;
        }
        
        $_SESSION['flash_success'] = $newStatus ? 'Método ativado!' : 'Método desativado!';
        header('Location: /settings/payments');
        exit;
    }

    /**
     * GET /settings/profile - Perfil do usuário
     */
    public function profile(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        return $this->viewMobile('settings/profile', [
            'company' => $company,
            'user' => $user,
            'pageTitle' => 'Meu Perfil',
            'activeNav' => 'settings',
            'showBackButton' => true
        ]);
    }

    /**
     * POST /settings/profile - Atualizar perfil
     */
    public function updateProfile(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        
        if (empty($name) || empty($email)) {
            $_SESSION['flash_error'] = 'Nome e email são obrigatórios';
            header('Location: /settings/profile');
            exit;
        }
        
        $pdo = db();
        
        // Se mudar senha
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                $_SESSION['flash_error'] = 'Informe a senha atual';
                header('Location: /settings/profile');
                exit;
            }
            
            if (!password_verify($currentPassword, $user['password_hash'])) {
                $_SESSION['flash_error'] = 'Senha atual incorreta';
                header('Location: /settings/profile');
                exit;
            }
            
            if (strlen($newPassword) < 6) {
                $_SESSION['flash_error'] = 'Nova senha deve ter pelo menos 6 caracteres';
                header('Location: /settings/profile');
                exit;
            }
            
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password_hash = ? WHERE id = ?");
            $stmt->execute([$name, $email, $hash, $user['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $user['id']]);
        }
        
        $_SESSION['flash_success'] = 'Perfil atualizado!';
        header('Location: /settings');
        exit;
    }

    /**
     * GET /settings/whatsapp - Gerenciamento WhatsApp/Evolution
     */
    public function whatsapp(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        // Verificar se a Evolution API está configurada
        $hasConfig = !empty($company['evolution_server_url']) && !empty($company['evolution_api_key']);
        
        // Carregar instâncias do banco
        $instances = [];
        if ($hasConfig) {
            $pdo = db();
            $stmt = $pdo->prepare("SELECT * FROM evolution_instances WHERE company_id = ? ORDER BY label");
            $stmt->execute([(int)$company['id']]);
            $instances = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        
        return $this->viewMobile('settings/whatsapp', [
            'company' => $company,
            'hasConfig' => $hasConfig,
            'instances' => $instances,
            'pageTitle' => 'WhatsApp',
            'activeNav' => 'settings',
            'showBackButton' => true
        ]);
    }
    
    /**
     * GET /settings/whatsapp/instances - Dados das instâncias (AJAX)
     * Busca direto da API Evolution para ter dados completos como no desktop
     */
    public function whatsappInstances(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $server = rtrim($company['evolution_server_url'] ?? '', '/');
        $apiKey = $company['evolution_api_key'] ?? '';
        
        if (!$server || !$apiKey) {
            $this->jsonResponse(['error' => 'API não configurada', 'instances' => []]);
            return;
        }
        
        // Buscar instâncias diretamente da API Evolution (como faz o desktop)
        $result = $this->evolutionApiRequest($server, $apiKey, '/instance/fetchInstances', 'GET');
        
        if (isset($result['error'])) {
            $this->jsonResponse(['error' => $result['error'], 'instances' => []]);
            return;
        }
        
        $remoteInstances = [];
        if (isset($result['data'])) {
            $data = $result['data'];
            if (isset($data['instances']) && is_array($data['instances'])) {
                $remoteInstances = $data['instances'];
            } elseif (is_array($data)) {
                $remoteInstances = $data;
            }
        }
        
        // Processar instâncias remotas para o formato esperado
        $processedInstances = [];
        
        foreach ($remoteInstances as $remote) {
            $instanceName = $remote['name'] ?? $remote['instanceName'] ?? 'Instance';
            
            // Buscar estado REAL da conexão
            $stateResult = $this->evolutionApiRequest($server, $apiKey, "/instance/connectionState/$instanceName", 'GET');
            
            $status = 'disconnected';
            if (!isset($stateResult['error'])) {
                $state = strtolower((string)($stateResult['data']['instance']['state'] ?? $stateResult['data']['state'] ?? ''));
                switch ($state) {
                    case 'open':
                    case 'connected':
                        $status = 'connected';
                        break;
                    case 'connecting':
                    case 'qrcode':
                    case 'qr':
                        $status = 'pending';
                        break;
                    default:
                        $status = 'disconnected';
                }
            }
            
            // Número do WhatsApp
            $rawNumber = '';
            if (isset($remote['number']) && $remote['number']) {
                $rawNumber = $remote['number'];
            } elseif (isset($remote['ownerJid']) && $remote['ownerJid']) {
                if (preg_match('/^(\d+)@/', $remote['ownerJid'], $matches)) {
                    $rawNumber = $matches[1];
                }
            }
            
            // Formatar telefone brasileiro
            $formattedPhone = $this->formatPhoneNumber($rawNumber);
            
            // Nome do perfil
            $profileName = $remote['profileName'] ?? 'Contato WhatsApp';
            
            // Contadores
            $chatCount = $remote['_count']['Chat'] ?? 0;
            $messageCount = $remote['_count']['Message'] ?? 0;
            
            $processedInstances[] = [
                'id' => $remote['id'] ?? null,
                'name' => $instanceName,
                'label' => $instanceName,
                'number' => $rawNumber,
                'formatted_number' => $formattedPhone,
                'profile_name' => $profileName,
                'profile_picture' => $remote['profilePicUrl'] ?? '',
                'status' => $status,
                'chats' => (int)$chatCount,
                'messages' => (int)$messageCount,
            ];
        }
        
        $this->jsonResponse(['success' => true, 'instances' => $processedInstances]);
    }
    
    /**
     * Formata número de telefone brasileiro
     */
    private function formatPhoneNumber(string $number): string
    {
        $number = preg_replace('/[^0-9]/', '', $number);
        if (strlen($number) >= 12 && substr($number, 0, 2) === '55') {
            $ddd = substr($number, 2, 2);
            $phone = substr($number, 4);
            if (strlen($phone) === 9) {
                return "+55 ($ddd) " . substr($phone, 0, 5) . "-" . substr($phone, 5);
            } elseif (strlen($phone) === 8) {
                return "+55 ($ddd) " . substr($phone, 0, 4) . "-" . substr($phone, 4);
            }
        }
        return $number ?: '';
    }
    
    /**
     * GET /settings/whatsapp/instance/{name} - Página de configuração da instância
     */
    public function whatsappInstance(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $instanceName = $params['name'] ?? '';
        if (!$instanceName) {
            header('Location: /settings/whatsapp');
            exit;
        }
        
        $server = rtrim($company['evolution_server_url'] ?? '', '/');
        $apiKey = $company['evolution_api_key'] ?? '';
        
        $instanceData = [
            'name' => $instanceName,
            'status' => 'disconnected',
            'number' => '',
            'profileName' => '',
            'profilePicUrl' => '',
            'token' => '',
            'clientName' => '',
            'integration' => 'WHATSAPP-BAILEYS',
            'createdAt' => null,
            'updatedAt' => null,
            'contacts' => 0,
            'chats' => 0,
            'messages' => 0,
        ];
        
        if ($server && $apiKey) {
            // Buscar dados da instância específica
            $result = $this->evolutionApiRequest($server, $apiKey, "/instance/fetchInstances?instanceName=" . rawurlencode($instanceName), 'GET');
            
            if (!isset($result['error']) && isset($result['data'])) {
                $instances = $result['data'];
                
                // Se veio array direto ou dentro de 'instances'
                if (isset($instances['instances'])) {
                    $instances = $instances['instances'];
                }
                
                // Se veio como array indexado
                if (isset($instances[0])) {
                    $inst = $instances[0];
                } else {
                    $inst = $instances;
                }
                
                // Preencher dados
                if (!empty($inst)) {
                    $instanceData['profileName'] = $inst['profileName'] ?? '';
                    $instanceData['profilePicUrl'] = $inst['profilePicUrl'] ?? '';
                    $instanceData['token'] = $inst['token'] ?? '';
                    $instanceData['clientName'] = $inst['clientName'] ?? $inst['name'] ?? '';
                    $instanceData['integration'] = $inst['integration'] ?? 'WHATSAPP-BAILEYS';
                    $instanceData['createdAt'] = $inst['createdAt'] ?? null;
                    $instanceData['updatedAt'] = $inst['updatedAt'] ?? null;
                    
                    // Contadores
                    if (isset($inst['_count'])) {
                        $instanceData['contacts'] = $inst['_count']['Contact'] ?? 0;
                        $instanceData['chats'] = $inst['_count']['Chat'] ?? 0;
                        $instanceData['messages'] = $inst['_count']['Message'] ?? 0;
                    }
                    
                    // Número
                    if (!empty($inst['number'])) {
                        $instanceData['number'] = $inst['number'];
                    } elseif (!empty($inst['ownerJid']) && preg_match('/^(\d+)@/', $inst['ownerJid'], $m)) {
                        $instanceData['number'] = $m[1];
                    }
                }
            }
            
            // Buscar estado de conexão REAL
            $stateResult = $this->evolutionApiRequest($server, $apiKey, "/instance/connectionState/$instanceName", 'GET');
            if (!isset($stateResult['error'])) {
                $state = strtolower((string)($stateResult['data']['instance']['state'] ?? $stateResult['data']['state'] ?? ''));
                switch ($state) {
                    case 'open':
                    case 'connected':
                        $instanceData['status'] = 'connected';
                        break;
                    case 'connecting':
                    case 'qrcode':
                    case 'qr':
                        $instanceData['status'] = 'pending';
                        break;
                    default:
                        $instanceData['status'] = 'disconnected';
                }
            }
            
            // Buscar configurações salvas no banco
            $pdo = db();
            $stmt = $pdo->prepare("SELECT id FROM evolution_instances WHERE company_id = ? AND instance_identifier = ?");
            $stmt->execute([(int)$company['id'], $instanceName]);
            $dbInstance = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($dbInstance) {
                $instanceData['id'] = $dbInstance['id'];
            }

            // Buscar configuração de order_notification via instance_configs
            $cfgStmt = $pdo->prepare("SELECT config_value FROM instance_configs WHERE company_id = ? AND instance_name = ? AND config_key = 'order_notification'");
            $cfgStmt->execute([(int)$company['id'], $instanceName]);
            $cfgRow = $cfgStmt->fetch(\PDO::FETCH_ASSOC);
            if ($cfgRow) {
                $cfgData = json_decode($cfgRow['config_value'], true);
                $instanceData['orderNotificationEnabled'] = (bool)($cfgData['enabled'] ?? false);
            } else {
                $instanceData['orderNotificationEnabled'] = false;
            }
        }
        
        return $this->viewMobile('settings/whatsapp_instance', [
            'company' => $company,
            'instanceName' => $instanceName,
            'instanceData' => $instanceData,
            'pageTitle' => $instanceName,
            'activeNav' => 'settings',
            'showBackButton' => true
        ]);
    }
    
    /**
     * POST /settings/whatsapp/instance/{name}/settings - Salvar configurações da instância
     */
    public function whatsappInstanceSettings(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $instanceName = $params['name'] ?? '';
        if (!$instanceName) {
            $this->jsonResponse(['error' => 'Instância não encontrada']);
            return;
        }
        
        $orderNotification = isset($_POST['order_notification']) && $_POST['order_notification'] === '1';
        
        $pdo = db();
        
        // Salvar via instance_configs (mesma abordagem que whatsappSaveOrderNotification)
        $cfgStmt = $pdo->prepare("SELECT config_value FROM instance_configs WHERE company_id = ? AND instance_name = ? AND config_key = 'order_notification'");
        $cfgStmt->execute([(int)$company['id'], $instanceName]);
        $cfgRow = $cfgStmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($cfgRow) {
            $cfgData = json_decode($cfgRow['config_value'], true) ?: [];
            $cfgData['enabled'] = $orderNotification;
            $cfgData['updated_at'] = date('Y-m-d H:i:s');
            $updateStmt = $pdo->prepare("UPDATE instance_configs SET config_value = ?, updated_at = NOW() WHERE company_id = ? AND instance_name = ? AND config_key = 'order_notification'");
            $updateStmt->execute([json_encode($cfgData), (int)$company['id'], $instanceName]);
        } else {
            $cfgData = ['enabled' => $orderNotification, 'primary_number' => '', 'secondary_number' => '', 'updated_at' => date('Y-m-d H:i:s')];
            $insertStmt = $pdo->prepare("INSERT INTO instance_configs (company_id, instance_name, config_key, config_value, created_at, updated_at) VALUES (?, ?, 'order_notification', ?, NOW(), NOW())");
            $insertStmt->execute([(int)$company['id'], $instanceName, json_encode($cfgData)]);
        }
        
        $this->jsonResponse(['success' => true, 'message' => 'Configurações salvas!']);
    }
    
    /**
     * POST /settings/whatsapp/sync - Sincronizar instâncias da API
     */
    public function whatsappSync(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $server = rtrim($company['evolution_server_url'] ?? '', '/');
        $apiKey = $company['evolution_api_key'] ?? '';
        
        if (!$server || !$apiKey) {
            $this->jsonResponse(['error' => 'API não configurada']);
            return;
        }
        
        // Buscar instâncias remotas
        $remoteInstances = $this->fetchRemoteInstances($server, $apiKey);
        
        if (isset($remoteInstances['error'])) {
            $this->jsonResponse(['error' => $remoteInstances['error']]);
            return;
        }
        
        $pdo = db();
        $companyId = (int)$company['id'];
        $imported = 0;
        
        foreach ($remoteInstances as $remote) {
            $name = $remote['instance']['instanceName'] ?? $remote['instanceName'] ?? null;
            if (!$name) continue;
            
            // Verificar se já existe
            $stmt = $pdo->prepare("SELECT id FROM evolution_instances WHERE company_id = ? AND instance_identifier = ?");
            $stmt->execute([$companyId, $name]);
            
            if (!$stmt->fetch()) {
                // Criar nova
                $number = $remote['instance']['owner'] ?? $remote['owner'] ?? '';
                $number = preg_replace('/[^0-9]/', '', explode('@', $number)[0] ?? '');
                
                $insert = $pdo->prepare("INSERT INTO evolution_instances (company_id, label, instance_identifier, number, created_at) VALUES (?, ?, ?, ?, NOW())");
                $insert->execute([$companyId, $name, $name, $number]);
                $imported++;
            }
        }
        
        $this->jsonResponse(['success' => true, 'imported' => $imported, 'message' => "Sincronizado! $imported novas instâncias."]);
    }
    
    /**
     * POST /settings/whatsapp/create - Criar nova instância
     */
    public function whatsappCreate(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        $name = trim($_POST['name'] ?? '');
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
        
        if (empty($name)) {
            if ($isAjax) {
                $this->jsonResponse(['error' => 'Nome da instância é obrigatório']);
                return;
            }
            $_SESSION['flash_error'] = 'Nome da instância é obrigatório';
            header('Location: /settings/whatsapp');
            exit;
        }
        
        $server = rtrim($company['evolution_server_url'] ?? '', '/');
        $apiKey = $company['evolution_api_key'] ?? '';
        
        if (!$server || !$apiKey) {
            if ($isAjax) {
                $this->jsonResponse(['error' => 'API Evolution não configurada']);
                return;
            }
            $_SESSION['flash_error'] = 'API Evolution não configurada';
            header('Location: /settings/whatsapp');
            exit;
        }
        
        // Criar na API Evolution
        $result = $this->createEvolutionInstance($server, $apiKey, $name);
        
        if (isset($result['error'])) {
            if ($isAjax) {
                $this->jsonResponse(['error' => $result['error']]);
                return;
            }
            $_SESSION['flash_error'] = 'Erro: ' . $result['error'];
            header('Location: /settings/whatsapp');
            exit;
        }
        
        // Salvar no banco
        $pdo = db();
        $stmt = $pdo->prepare("INSERT INTO evolution_instances (company_id, label, instance_identifier, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([(int)$company['id'], $name, $name]);
        
        if ($isAjax) {
            $this->jsonResponse(['success' => true, 'ok' => true, 'name' => $name]);
            return;
        }
        
        $_SESSION['flash_success'] = 'Instância criada! Escaneie o QR Code.';
        header('Location: /settings/whatsapp?instance=' . urlencode($name));
        exit;
    }
    
    /**
     * POST /settings/whatsapp/{name}/qrcode - Obter QR Code
     */
    public function whatsappQrcode(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $instanceName = $params['name'] ?? '';
        $server = rtrim($company['evolution_server_url'] ?? '', '/');
        $apiKey = $company['evolution_api_key'] ?? '';
        
        if (!$server || !$apiKey || !$instanceName) {
            $this->jsonResponse(['error' => 'Configuração inválida']);
            return;
        }
        
        // Buscar estado e QR code
        $state = $this->fetchInstanceState($server, $apiKey, $instanceName);
        
        $this->jsonResponse([
            'success' => true,
            'status' => $state['status'] ?? 'pending',
            'qrcode' => $state['qrcode'] ?? null
        ]);
    }
    
    /**
     * POST /settings/whatsapp/{name}/disconnect - Desconectar instância
     */
    public function whatsappDisconnect(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $instanceName = $params['name'] ?? '';
        $server = rtrim($company['evolution_server_url'] ?? '', '/');
        $apiKey = $company['evolution_api_key'] ?? '';
        
        if (!$server || !$apiKey || !$instanceName) {
            $this->jsonResponse(['error' => 'Configuração inválida']);
            return;
        }
        
        // Logout na API
        $this->evolutionApiRequest($server, $apiKey, "/instance/logout/$instanceName", 'DELETE');
        
        // Atualizar banco (status não existe mais na tabela, apenas atualizamos updated_at)
        $pdo = db();
        $stmt = $pdo->prepare("UPDATE evolution_instances SET updated_at = NOW() WHERE company_id = ? AND instance_identifier = ?");
        $stmt->execute([(int)$company['id'], $instanceName]);
        
        $this->jsonResponse(['success' => true, 'message' => 'Desconectado']);
    }
    
    /**
     * POST /settings/whatsapp/{name}/delete - Excluir instância
     */
    public function whatsappDelete(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        $instanceName = $params['name'] ?? '';
        $server = rtrim($company['evolution_server_url'] ?? '', '/');
        $apiKey = $company['evolution_api_key'] ?? '';
        
        if (!$instanceName) {
            if ($isAjax) {
                $this->jsonResponse(['error' => 'Instância não encontrada']);
                return;
            }
            $_SESSION['flash_error'] = 'Instância não encontrada';
            header('Location: /settings/whatsapp');
            exit;
        }
        
        // Excluir da API
        if ($server && $apiKey) {
            // Primeiro, desconectar (logout) a instância - necessário antes de deletar
            $this->evolutionApiRequest($server, $apiKey, "/instance/logout/$instanceName", 'DELETE');
            usleep(500000); // Aguardar 0.5 segundos para o logout processar
            
            // Agora deletar
            $this->evolutionApiRequest($server, $apiKey, "/instance/delete/$instanceName", 'DELETE');
        }
        
        // Excluir do banco
        $pdo = db();
        $stmt = $pdo->prepare("DELETE FROM evolution_instances WHERE company_id = ? AND instance_identifier = ?");
        $stmt->execute([(int)$company['id'], $instanceName]);
        
        if ($isAjax) {
            $this->jsonResponse(['success' => true, 'message' => 'Instância excluída!']);
            return;
        }
        
        $_SESSION['flash_success'] = 'Instância excluída!';
        header('Location: /settings/whatsapp');
        exit;
    }
    
    /**
     * Buscar instâncias remotas da Evolution API
     */
    private function fetchRemoteInstances(string $server, string $apiKey): array
    {
        $result = $this->evolutionApiRequest($server, $apiKey, '/instance/fetchInstances', 'GET');
        
        if (isset($result['error'])) {
            return ['error' => $result['error']];
        }
        
        return $result['data'] ?? [];
    }
    
    /**
     * Buscar estado de uma instância
     */
    private function fetchInstanceState(string $server, string $apiKey, string $instanceName): array
    {
        // Buscar estado de conexão
        $stateResult = $this->evolutionApiRequest($server, $apiKey, "/instance/connectionState/$instanceName", 'GET');
        
        $status = 'disconnected';
        $qrcode = null;
        
        if (!isset($stateResult['error'])) {
            $state = strtolower((string)($stateResult['data']['instance']['state'] ?? $stateResult['data']['state'] ?? ''));
            
            switch ($state) {
                case 'open':
                case 'connected':
                    $status = 'connected';
                    break;
                case 'connecting':
                case 'qrcode':
                case 'qr':
                    $status = 'pending';
                    break;
                default:
                    $status = 'disconnected';
            }
            
            // Se pendente, buscar QR code
            if ($status === 'pending' || $status === 'disconnected') {
                $qrResult = $this->evolutionApiRequest($server, $apiKey, "/instance/connect/$instanceName", 'GET');
                if (!isset($qrResult['error'])) {
                    $qrcode = $qrResult['data']['base64'] ?? $qrResult['data']['qrcode']['base64'] ?? null;
                }
            }
        }
        
        return ['status' => $status, 'qrcode' => $qrcode];
    }
    
    /**
     * Criar instância na Evolution API
     */
    private function createEvolutionInstance(string $server, string $apiKey, string $name): array
    {
        $body = [
            'instanceName' => $name,
            'qrcode' => true,
            'integration' => 'WHATSAPP-BAILEYS'
        ];
        
        $result = $this->evolutionApiRequest($server, $apiKey, '/instance/create', 'POST', $body);
        
        if (isset($result['error'])) {
            return ['error' => $result['error']];
        }
        
        return $result['data'] ?? [];
    }
    
    /**
     * Requisição à Evolution API
     */
    private function evolutionApiRequest(string $server, string $apiKey, string $path, string $method = 'GET', ?array $body = null): array
    {
        $url = $server . $path;
        
        $ch = curl_init($url);
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'apikey: ' . $apiKey,
        ];
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($err) {
            return ['error' => $err];
        }
        
        $data = json_decode($resp, true);
        
        if ($code >= 400) {
            return ['error' => $data['message'] ?? 'Erro HTTP ' . $code];
        }
        
        return ['data' => $data];
    }

    private function handleUpload(?array $file, string $prefix): ?string
    {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            return null;
        }
        
        $name = $prefix . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $dest = __DIR__ . '/../../public/uploads/' . $name;
        
        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0777, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return 'uploads/' . $name;
        }
        
        return null;
    }

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * POST /settings/whatsapp/{name}/restart - Reiniciar instância
     */
    public function whatsappRestart(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $instanceName = $params['name'] ?? '';
        $server = rtrim($company['evolution_server_url'] ?? '', '/');
        $apiKey = $company['evolution_api_key'] ?? '';
        
        if (!$instanceName || !$server || !$apiKey) {
            $this->jsonResponse(['error' => 'Parâmetros inválidos']);
            return;
        }
        
        // Reiniciar instância
        $result = $this->evolutionApiRequest($server, $apiKey, "/instance/restart/$instanceName", 'PUT');
        
        if (isset($result['error'])) {
            $this->jsonResponse(['error' => $result['error']]);
            return;
        }
        
        $this->jsonResponse(['success' => true, 'message' => 'Instância reiniciada!']);
    }

    /**
     * GET /settings/whatsapp/{name}/api-settings - Buscar configurações da Evolution API
     */
    public function whatsappApiSettings(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $instanceName = $params['name'] ?? '';
        $server = rtrim($company['evolution_server_url'] ?? '', '/');
        $apiKey = $company['evolution_api_key'] ?? '';
        
        if (!$instanceName || !$server || !$apiKey) {
            $this->jsonResponse(['error' => 'Parâmetros inválidos', 'data' => []]);
            return;
        }
        
        // Buscar configurações da Evolution API
        $result = $this->evolutionApiRequest($server, $apiKey, "/settings/find/$instanceName", 'GET');
        
        if (isset($result['error'])) {
            $this->jsonResponse(['error' => $result['error'], 'data' => []]);
            return;
        }
        
        $this->jsonResponse(['success' => true, 'data' => $result['data'] ?? []]);
    }

    /**
     * POST /settings/whatsapp/{name}/api-settings - Salvar configurações da Evolution API
     */
    public function whatsappSaveApiSettings(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $instanceName = $params['name'] ?? '';
        $server = rtrim($company['evolution_server_url'] ?? '', '/');
        $apiKey = $company['evolution_api_key'] ?? '';
        
        if (!$instanceName || !$server || !$apiKey) {
            $this->jsonResponse(['error' => 'Parâmetros inválidos']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        // Configurações padrão
        $defaultSettings = [
            'rejectCall' => false,
            'msgCall' => '',
            'groupsIgnore' => false,
            'alwaysOnline' => false,
            'readMessages' => false,
            'readStatus' => false,
            'syncFullHistory' => false
        ];
        
        // Mesclar com input
        $settings = array_merge($defaultSettings, $input);
        
        // Salvar configurações na Evolution API
        $result = $this->evolutionApiRequest($server, $apiKey, "/settings/set/$instanceName", 'POST', $settings);
        
        if (isset($result['error'])) {
            $this->jsonResponse(['error' => $result['error']]);
            return;
        }
        
        $this->jsonResponse(['success' => true, 'message' => 'Configurações salvas!']);
    }

    /**
     * GET /settings/whatsapp/{name}/order-notification - Buscar configuração de notificação de pedido
     */
    public function whatsappOrderNotification(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $instanceName = $params['name'] ?? '';
        
        if (!$instanceName) {
            $this->jsonResponse(['error' => 'Instância não encontrada']);
            return;
        }
        
        $pdo = db();
        
        // Usar a mesma tabela instance_configs que o desktop usa
        $stmt = $pdo->prepare("SELECT config_value FROM instance_configs WHERE company_id = ? AND instance_name = ? AND config_key = 'order_notification'");
        $stmt->execute([(int)$company['id'], $instanceName]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($row) {
            $config = json_decode($row['config_value'], true);
            $data = [
                'enabled' => (bool)($config['enabled'] ?? false),
                'primary_number' => $config['primary_number'] ?? '',
                'secondary_number' => $config['secondary_number'] ?? '',
                'message_fields' => $config['message_fields'] ?? null
            ];
        } else {
            $data = [
                'enabled' => false,
                'primary_number' => '',
                'secondary_number' => '',
                'message_fields' => null
            ];
        }
        
        $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    /**
     * POST /settings/whatsapp/{name}/order-notification - Salvar configuração de notificação de pedido
     */
    public function whatsappSaveOrderNotification(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $instanceName = $params['name'] ?? '';
        
        if (!$instanceName) {
            $this->jsonResponse(['error' => 'Instância não encontrada']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $enabled = (bool)($input['enabled'] ?? false);
        $primaryNumber = normalizePhone($input['primary_number'] ?? '');
        $secondaryNumber = normalizePhone($input['secondary_number'] ?? '');
        $messageFields = $input['message_fields'] ?? null;
        $forceSwitch = (bool)($input['force_switch'] ?? false);
        
        $pdo = db();
        
        // Se está ativando e force_switch, desativar outras instâncias na tabela instance_configs
        if ($enabled && $forceSwitch) {
            // Buscar todas as configurações de order_notification da empresa
            $stmt = $pdo->prepare("SELECT id, instance_name, config_value FROM instance_configs WHERE company_id = ? AND config_key = 'order_notification' AND instance_name != ?");
            $stmt->execute([(int)$company['id'], $instanceName]);
            
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $config = json_decode($row['config_value'], true);
                if (!empty($config['enabled'])) {
                    $config['enabled'] = false;
                    $updateStmt = $pdo->prepare("UPDATE instance_configs SET config_value = ?, updated_at = NOW() WHERE id = ?");
                    $updateStmt->execute([json_encode($config), $row['id']]);
                }
            }
        }
        
        // Preparar dados para salvar (mesmo formato que o desktop)
        $configData = [
            'enabled' => $enabled,
            'primary_number' => $primaryNumber,
            'secondary_number' => $secondaryNumber,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($messageFields !== null) {
            $configData['message_fields'] = $messageFields;
        }
        
        // Usar INSERT ... ON DUPLICATE KEY UPDATE como o desktop faz
        $sql = "INSERT INTO instance_configs (company_id, instance_name, config_key, config_value, created_at, updated_at) 
                VALUES (?, ?, 'order_notification', ?, NOW(), NOW()) 
                ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_at = NOW()";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([(int)$company['id'], $instanceName, json_encode($configData)]);
        
        $this->jsonResponse(['success' => true, 'message' => 'Configurações salvas!']);
    }

    /**
     * POST /settings/whatsapp/{name}/validate-number - Validar se número existe no WhatsApp
     */
    public function whatsappValidateNumber(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $instanceName = $params['name'] ?? '';
        $server = rtrim($company['evolution_server_url'] ?? '', '/');
        $apiKey = $company['evolution_api_key'] ?? '';
        
        if (!$instanceName || !$server || !$apiKey) {
            $this->jsonResponse(['success' => false, 'error' => 'Parâmetros inválidos']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $number = preg_replace('/\D/', '', $input['number'] ?? '');
        
        if (strlen($number) < 10 || strlen($number) > 15) {
            $this->jsonResponse(['success' => false, 'error' => 'Número inválido']);
            return;
        }
        
        // Verificar se número existe no WhatsApp via Evolution API
        $result = $this->evolutionApiRequest($server, $apiKey, "/chat/whatsappNumbers/$instanceName", 'POST', [
            'numbers' => [$number]
        ]);
        
        if (isset($result['error'])) {
            // Se erro na API, assumir que não foi possível verificar
            $this->jsonResponse(['success' => true, 'exists' => true, 'checked' => false, 'message' => 'Não foi possível verificar']);
            return;
        }
        
        $numbers = $result['data'] ?? [];
        $exists = false;
        
        if (is_array($numbers)) {
            foreach ($numbers as $n) {
                if (isset($n['exists']) && $n['exists']) {
                    $exists = true;
                    break;
                }
            }
        }
        
        $this->jsonResponse(['success' => true, 'exists' => $exists, 'checked' => true]);
    }

    /**
     * GET /settings/whatsapp/{name}/engagement - Buscar configuração de engajamento
     */
    public function whatsappEngagement(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $instanceName = $params['name'] ?? '';
        
        if (!$instanceName) {
            $this->jsonResponse(['error' => 'Instância não encontrada']);
            return;
        }
        
        $pdo = db();
        
        // Buscar configuração da tabela customer_engagement_config
        $stmt = $pdo->prepare("SELECT * FROM customer_engagement_config WHERE company_id = ? AND instance_name = ?");
        $stmt->execute([(int)$company['id'], $instanceName]);
        $config = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $data = [
            'enabled' => (bool)($config['enabled'] ?? false),
            'scenario1_enabled' => (bool)($config['scenario1_enabled'] ?? true),
            'scenario1_delay' => (int)($config['scenario1_delay_minutes'] ?? 10),
            'scenario2_enabled' => (bool)($config['scenario2_enabled'] ?? true),
            'scenario2_days' => (int)($config['scenario2_inactive_days'] ?? 15),
            'out_of_hours_enabled' => isset($config['out_of_hours_enabled']) ? (bool)$config['out_of_hours_enabled'] : true
        ];
        
        // Buscar estatísticas dos últimos 30 dias
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN scenario_type = 'signup_no_order' THEN 1 ELSE 0 END) as scenario1,
                    SUM(CASE WHEN scenario_type = 'inactive_customer' THEN 1 ELSE 0 END) as scenario2
                FROM customer_engagement_log 
                WHERE company_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute([(int)$company['id']]);
            $stats = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            $data['stats'] = [
                'total' => (int)($stats['total'] ?? 0),
                'scenario1' => (int)($stats['scenario1'] ?? 0),
                'scenario2' => (int)($stats['scenario2'] ?? 0)
            ];
        } catch (\Exception $e) {
            // Tabela pode não existir ainda
            $data['stats'] = ['total' => 0, 'scenario1' => 0, 'scenario2' => 0];
        }
        
        $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    /**
     * POST /settings/whatsapp/{name}/engagement - Salvar configuração de engajamento
     */
    public function whatsappSaveEngagement(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $instanceName = $params['name'] ?? '';
        
        if (!$instanceName) {
            $this->jsonResponse(['error' => 'Instância não encontrada']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $enabled = (bool)($input['enabled'] ?? false);
        $scenario1Enabled = (bool)($input['scenario1_enabled'] ?? true);
        $scenario1Delay = max(5, min(60, (int)($input['scenario1_delay'] ?? 10)));
        $scenario2Enabled = (bool)($input['scenario2_enabled'] ?? true);
        $scenario2Days = max(7, min(90, (int)($input['scenario2_days'] ?? 15)));
        // Preservar configuração existente quando o campo não vier no payload
        // (a UI mobile salva engajamento sem enviar out_of_hours_enabled).
        $outOfHoursEnabled = null;
        if (array_key_exists('out_of_hours_enabled', $input)) {
            $outOfHoursEnabled = (bool)$input['out_of_hours_enabled'];
        }
        
        $pdo = db();
        
        try {
            // Verificar se já existe na tabela customer_engagement_config
            $stmt = $pdo->prepare("SELECT id FROM customer_engagement_config WHERE company_id = ?");
            $stmt->execute([(int)$company['id']]);
            $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($existing) {
                $effectiveOutOfHoursEnabled = $outOfHoursEnabled;
                if ($effectiveOutOfHoursEnabled === null) {
                    $currentStmt = $pdo->prepare("SELECT out_of_hours_enabled FROM customer_engagement_config WHERE id = ? LIMIT 1");
                    $currentStmt->execute([$existing['id']]);
                    $currentRow = $currentStmt->fetch(\PDO::FETCH_ASSOC);
                    $effectiveOutOfHoursEnabled = isset($currentRow['out_of_hours_enabled'])
                        ? (bool)$currentRow['out_of_hours_enabled']
                        : true;
                }

                $stmt = $pdo->prepare("UPDATE customer_engagement_config SET 
                    instance_name = ?,
                    enabled = ?, 
                    scenario1_enabled = ?, 
                    scenario1_delay_minutes = ?,
                    scenario2_enabled = ?,
                    scenario2_inactive_days = ?,
                    out_of_hours_enabled = ?,
                    updated_at = NOW() 
                    WHERE id = ?");
                $stmt->execute([
                    $instanceName,
                    $enabled ? 1 : 0,
                    $scenario1Enabled ? 1 : 0,
                    $scenario1Delay,
                    $scenario2Enabled ? 1 : 0,
                    $scenario2Days,
                    $effectiveOutOfHoursEnabled ? 1 : 0,
                    $existing['id']
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO customer_engagement_config 
                    (company_id, instance_name, enabled, scenario1_enabled, scenario1_delay_minutes, scenario2_enabled, scenario2_inactive_days, out_of_hours_enabled, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    (int)$company['id'],
                    $instanceName,
                    $enabled ? 1 : 0,
                    $scenario1Enabled ? 1 : 0,
                    $scenario1Delay,
                    $scenario2Enabled ? 1 : 0,
                    $scenario2Days,
                    ($outOfHoursEnabled ?? true) ? 1 : 0
                ]);
            }
            
            // Ao desativar, cancelar todos os itens pendentes da fila
            if (!$enabled) {
                $cancelStmt = $pdo->prepare("
                    UPDATE customer_engagement_queue 
                    SET status = 'cancelled', is_active = NULL, error_message = 'Sistema desativado pelo admin'
                    WHERE company_id = ? AND status IN ('pending', 'processing')
                ");
                $cancelStmt->execute([(int)$company['id']]);
                $cancelled = $cancelStmt->rowCount();
                if ($cancelled > 0) {
                    error_log("[Customer Engagement] Fila cancelada ao desativar: {$cancelled} itens para empresa {$company['id']}");
                }
            }
            
            // Configurar webhook na Evolution API se out_of_hours estiver ativado
            $shouldConfigureWebhook = $outOfHoursEnabled;
            if ($shouldConfigureWebhook === null) {
                $cfgStmt = $pdo->prepare("SELECT out_of_hours_enabled FROM customer_engagement_config WHERE company_id = ? LIMIT 1");
                $cfgStmt->execute([(int)$company['id']]);
                $cfgRow = $cfgStmt->fetch(\PDO::FETCH_ASSOC);
                $shouldConfigureWebhook = isset($cfgRow['out_of_hours_enabled']) ? (bool)$cfgRow['out_of_hours_enabled'] : false;
            }

            if ($shouldConfigureWebhook) {
                $this->configureOutOfHoursWebhook($company, $instanceName);
            }
            
            $this->jsonResponse(['success' => true, 'message' => 'Configurações de engajamento salvas!']);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'Erro ao salvar configurações: ' . $e->getMessage()]);
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
            error_log("[Out of Hours Webhook Mobile] Configuração Evolution não encontrada para empresa {$company['id']}");
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
            error_log("[Out of Hours Webhook Mobile] Erro ao configurar webhook: HTTP {$httpCode}, cURL: {$curlError}, Response: {$response}");
            return false;
        }
        
        error_log("[Out of Hours Webhook Mobile] Webhook configurado com sucesso para {$instanceName}: {$webhookUrl}");
        return true;
    }

    /**
     * GET /settings/whatsapp/{name}/out-of-hours - Buscar configuração de fora do expediente
     */
    public function whatsappOutOfHours(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $instanceName = $params['name'] ?? '';
        
        if (!$instanceName) {
            $this->jsonResponse(['error' => 'Instância não encontrada']);
            return;
        }
        
        $pdo = db();
        
        // Buscar configuração da tabela customer_engagement_config
        $stmt = $pdo->prepare("SELECT out_of_hours_enabled, out_of_hours_message FROM customer_engagement_config WHERE company_id = ? AND instance_name = ?");
        $stmt->execute([(int)$company['id'], $instanceName]);
        $config = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $data = [
            'enabled' => isset($config['out_of_hours_enabled']) ? (bool)$config['out_of_hours_enabled'] : false,
            'message' => $config['out_of_hours_message'] ?? ''
        ];
        
        $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    /**
     * POST /settings/whatsapp/{name}/out-of-hours - Salvar configuração de fora do expediente
     */
    public function whatsappSaveOutOfHours(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $instanceName = $params['name'] ?? '';
        
        if (!$instanceName) {
            $this->jsonResponse(['error' => 'Instância não encontrada']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $enabled = (bool)($input['enabled'] ?? false);
        $message = isset($input['message']) ? trim($input['message']) : null;
        
        $pdo = db();
        
        try {
            // Verificar se já existe na tabela customer_engagement_config
            $stmt = $pdo->prepare("SELECT id FROM customer_engagement_config WHERE company_id = ?");
            $stmt->execute([(int)$company['id']]);
            $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($existing) {
                $stmt = $pdo->prepare("UPDATE customer_engagement_config SET 
                    instance_name = ?,
                    out_of_hours_enabled = ?,
                    out_of_hours_message = ?,
                    updated_at = NOW() 
                    WHERE id = ?");
                $stmt->execute([
                    $instanceName,
                    $enabled ? 1 : 0,
                    $message,
                    $existing['id']
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO customer_engagement_config 
                    (company_id, instance_name, enabled, out_of_hours_enabled, out_of_hours_message, created_at) 
                    VALUES (?, ?, 0, ?, ?, NOW())");
                $stmt->execute([
                    (int)$company['id'],
                    $instanceName,
                    $enabled ? 1 : 0,
                    $message
                ]);
            }
            
            // Configurar webhook na Evolution API se out_of_hours estiver ativado
            if ($enabled) {
                $this->configureOutOfHoursWebhook($company, $instanceName);
            }
            
            $this->jsonResponse(['success' => true, 'message' => 'Configuração de fora do expediente salva!']);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'Erro ao salvar configuração: ' . $e->getMessage()]);
        }
    }

    // =========================================================
    //  PAUSA PROGRAMADA
    // =========================================================

    /**
     * GET /settings/whatsapp/{name}/scheduled-pause - Obter configuração de pausa programada
     */
    public function whatsappScheduledPause(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $instanceName = $params['name'] ?? '';
        
        if (!$instanceName) {
            $this->jsonResponse(['error' => 'Instância não encontrada']);
            return;
        }
        
        $pdo = db();
        
        $stmt = $pdo->prepare("SELECT scheduled_pause_enabled, scheduled_pause_message FROM customer_engagement_config WHERE company_id = ? AND instance_name = ?");
        $stmt->execute([(int)$company['id'], $instanceName]);
        $config = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $data = [
            'enabled' => isset($config['scheduled_pause_enabled']) ? (bool)$config['scheduled_pause_enabled'] : false,
            'message' => $config['scheduled_pause_message'] ?? ''
        ];
        
        $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    /**
     * POST /settings/whatsapp/{name}/scheduled-pause - Salvar configuração de pausa programada
     */
    public function whatsappSaveScheduledPause(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();
        
        $instanceName = $params['name'] ?? '';
        
        if (!$instanceName) {
            $this->jsonResponse(['error' => 'Instância não encontrada']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $enabled = (bool)($input['enabled'] ?? false);
        $message = isset($input['message']) ? trim($input['message']) : null;
        
        $pdo = db();
        
        try {
            $stmt = $pdo->prepare("SELECT id FROM customer_engagement_config WHERE company_id = ?");
            $stmt->execute([(int)$company['id']]);
            $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($existing) {
                $stmt = $pdo->prepare("UPDATE customer_engagement_config SET 
                    instance_name = ?,
                    scheduled_pause_enabled = ?,
                    scheduled_pause_message = ?,
                    updated_at = NOW() 
                    WHERE id = ?");
                $stmt->execute([
                    $instanceName,
                    $enabled ? 1 : 0,
                    $message,
                    $existing['id']
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO customer_engagement_config 
                    (company_id, instance_name, enabled, scheduled_pause_enabled, scheduled_pause_message, created_at) 
                    VALUES (?, ?, 0, ?, ?, NOW())");
                $stmt->execute([
                    (int)$company['id'],
                    $instanceName,
                    $enabled ? 1 : 0,
                    $message
                ]);
            }
            
            $this->jsonResponse(['success' => true, 'message' => 'Configuração de pausa programada salva!']);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'Erro ao salvar configuração: ' . $e->getMessage()]);
        }
    }

    // =========================================================
    //  API MANAGEMENT
    // =========================================================

    /**
     * GET /settings/api - Página de gerenciamento de API
     */
    public function apiManagement(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        // Inicializar ApiSecurity
        require_once __DIR__ . '/../middleware/ApiSecurity.php';

        $db = db();
        $userId = (int)$user['id'];
        $companyId = (int)$company['id'];

        // Buscar tokens e API keys
        $apiData = ['tokens' => [], 'api_keys' => []];
        try {
            $checkTokens = $db->query("SHOW TABLES LIKE 'oauth_tokens'");
            if ($checkTokens->rowCount() > 0) {
                $stmt = $db->prepare('SELECT id, access_token, jwt_raw, scopes, expires_at, created_at FROM oauth_tokens WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
                $stmt->execute([$userId]);
                $apiData['tokens'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            $checkKeys = $db->query("SHOW TABLES LIKE 'api_keys'");
            if ($checkKeys->rowCount() > 0) {
                $stmt = $db->prepare('SELECT id, key_hash, name, scopes, expires_at, created_at, is_active, revoked_at FROM api_keys WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
                $stmt->execute([$userId]);
                $apiData['api_keys'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
        } catch (\Exception $e) {
            // silently fail
        }

        // Stats
        $stats = ['requests_today' => 0, 'total_requests' => 0, 'top_endpoints' => []];
        try {
            $checkReq = $db->query("SHOW TABLES LIKE 'api_requests'");
            if ($checkReq->rowCount() > 0) {
                $s = $db->query('SELECT COUNT(*) FROM api_requests WHERE DATE(created_at) = CURDATE()');
                $stats['requests_today'] = (int)$s->fetchColumn();

                $s = $db->query('SELECT COUNT(*) FROM api_requests');
                $stats['total_requests'] = (int)$s->fetchColumn();

                $s = $db->query('SELECT endpoint, COUNT(*) as count FROM api_requests WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY endpoint ORDER BY count DESC LIMIT 5');
                $stats['top_endpoints'] = $s->fetchAll(\PDO::FETCH_ASSOC);
            }
        } catch (\Exception $e) {
            // silently fail
        }

        // Endpoints list
        $endpoints = [
            ['method' => 'GET', 'path' => '/api/{slug}', 'description' => 'Informações da empresa'],
            ['method' => 'GET', 'path' => '/api/{slug}/stats', 'description' => 'Estatísticas da empresa'],
            ['method' => 'GET', 'path' => '/api/{slug}/categories', 'description' => 'Lista categorias'],
            ['method' => 'GET', 'path' => '/api/{slug}/products', 'description' => 'Lista produtos'],
            ['method' => 'GET', 'path' => '/api/{slug}/products/{id}', 'description' => 'Detalhes do produto'],
            ['method' => 'GET', 'path' => '/api/{slug}/orders', 'description' => 'Lista pedidos'],
            ['method' => 'GET', 'path' => '/api/{slug}/orders/{id}', 'description' => 'Detalhes do pedido'],
            ['method' => 'POST', 'path' => '/api/{slug}/orders', 'description' => 'Criar novo pedido'],
            ['method' => 'POST', 'path' => '/api/{slug}/orders/{id}/status', 'description' => 'Atualizar status'],
            ['method' => 'POST', 'path' => '/api/{slug}/token', 'description' => 'Gerar JWT token'],
        ];

        $data = [
            'title' => 'API - ' . $company['name'],
            'company' => $company,
            'user' => $user,
            'apiData' => $apiData,
            'stats' => $stats,
            'endpoints' => $endpoints,
            'baseUrl' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api',
        ];

        $this->view('admin/mobile/settings/api', $data);
    }

    /**
     * POST /settings/api/generate-token - Gerar JWT Token
     */
    public function apiGenerateToken(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        require_once __DIR__ . '/../middleware/ApiSecurity.php';

        try {
            $apiSecurity = new \App\Middleware\ApiSecurity([
                'jwt_secret' => $_ENV['JWT_SECRET'] ?? 'multi-menu-api-secret-change-in-production',
                'enforce_https' => false,
            ], db());

            $expiresIn = (int)($_POST['expires_in'] ?? 86400);
            $scopes = $_POST['scopes'] ?? ['read', 'write'];
            if (!is_array($scopes)) $scopes = [$scopes];

            $token = $apiSecurity->generateJwt([
                'sub' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'scopes' => $scopes,
                'company_access' => $slug,
            ], $expiresIn);

            // Save to DB
            $db = db();
            $stmt = $db->prepare('INSERT INTO oauth_tokens (user_id, client_id, access_token, jwt_raw, scopes, expires_at, created_at) VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), NOW())');
            $stmt->execute([
                $user['id'],
                'admin-dashboard',
                hash('sha256', $token),
                $token,
                json_encode($scopes),
                $expiresIn,
            ]);

            $_SESSION['flash_success'] = 'Token JWT gerado com sucesso!';
            $_SESSION['generated_token'] = $token;
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Erro ao gerar token: ' . $e->getMessage();
        }

        header('Location: /settings/api');
        exit;
    }

    /**
     * POST /settings/api/revoke-token - Revogar JWT Token
     */
    public function apiRevokeToken(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $tokenId = (int)($_POST['token_id'] ?? 0);
        if (!$tokenId) {
            $_SESSION['flash_error'] = 'ID do token não fornecido.';
            header('Location: /settings/api');
            exit;
        }

        try {
            $db = db();
            $stmt = $db->prepare('DELETE FROM oauth_tokens WHERE id = ? AND user_id = ?');
            $stmt->execute([$tokenId, $user['id']]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['flash_success'] = 'Token revogado com sucesso!';
            } else {
                $_SESSION['flash_error'] = 'Token não encontrado.';
            }
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Erro ao revogar token.';
        }

        header('Location: /settings/api');
        exit;
    }

    /**
     * POST /settings/api/generate-key - Gerar API Key
     */
    public function apiGenerateKey(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        require_once __DIR__ . '/../middleware/ApiSecurity.php';

        try {
            $apiSecurity = new \App\Middleware\ApiSecurity([
                'jwt_secret' => $_ENV['JWT_SECRET'] ?? 'multi-menu-api-secret-change-in-production',
                'enforce_https' => false,
            ], db());

            $name = trim($_POST['name'] ?? 'API Key - ' . date('Y-m-d H:i:s'));
            $scopes = $_POST['scopes'] ?? ['read'];
            if (!is_array($scopes)) $scopes = [$scopes];

            $apiKey = $apiSecurity->generateApiKey(
                (int)$user['id'],
                $name,
                $scopes,
                null
            );

            $_SESSION['flash_success'] = 'API Key gerada com sucesso!';
            $_SESSION['generated_key'] = $apiKey;
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Erro ao gerar API Key: ' . $e->getMessage();
        }

        header('Location: /settings/api');
        exit;
    }

    /**
     * POST /settings/api/revoke-key - Revogar API Key
     */
    public function apiRevokeKey(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $keyId = (int)($_POST['key_id'] ?? 0);
        if (!$keyId) {
            $_SESSION['flash_error'] = 'ID da chave não fornecido.';
            header('Location: /settings/api');
            exit;
        }

        try {
            $db = db();
            $stmt = $db->prepare('UPDATE api_keys SET is_active = 0, revoked_at = NOW() WHERE id = ? AND user_id = ?');
            $stmt->execute([$keyId, $user['id']]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['flash_success'] = 'API Key revogada com sucesso!';
            } else {
                $_SESSION['flash_error'] = 'API Key não encontrada.';
            }
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Erro ao revogar API Key.';
        }

        header('Location: /settings/api');
        exit;
    }

    // =========================================================
    //  FIDELIDADE
    // =========================================================

    /**
     * GET /settings/loyalty - Página de fidelidade
     */
    public function loyalty(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $db = db();

        // Taxa embutida
        $stmt = $db->prepare("SELECT embedded_delivery_fee, coupon_prefix FROM companies WHERE id = :id");
        $stmt->execute(['id' => $company['id']]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $embedded_delivery_fee = $result['embedded_delivery_fee'] ?? 0.00;
        $couponPrefix = $result['coupon_prefix'] ?? 'WOLL';

        // Desconto por cadastro completo
        $stmt = $db->prepare("SELECT is_active, discount_percentage, welcome_message FROM loyalty_discounts WHERE company_id = :id");
        $stmt->execute(['id' => $company['id']]);
        $loyalty = $stmt->fetch(\PDO::FETCH_ASSOC);

        $loyaltyActive = $loyalty ? (int)$loyalty['is_active'] : 0;
        $loyaltyDiscount = $loyalty ? (float)$loyalty['discount_percentage'] : 0;
        $loyaltyMessage = $loyalty ? ($loyalty['welcome_message'] ?? '') : '';

        // Cupons
        $stmt = $db->prepare('
            SELECT id, coupon_code, customer_phone, discount_percentage,
                   usage_limit, times_used, is_used, used_at, created_at
            FROM customer_loyalty_coupons
            WHERE company_id = ?
            ORDER BY created_at DESC
        ');
        $stmt->execute([$company['id']]);
        $cupons = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Stats
        $cupons_stats = ['total' => count($cupons), 'active' => 0, 'used' => 0, 'totalUsage' => 0];
        foreach ($cupons as $c) {
            $used = (int)$c['is_used'] === 1;
            $tu = (int)($c['times_used'] ?? 0);
            $ul = (int)($c['usage_limit'] ?? 1);
            if ($used || ($ul > 0 && $tu >= $ul)) {
                $cupons_stats['used']++;
            } else {
                $cupons_stats['active']++;
            }
            $cupons_stats['totalUsage'] += $tu;
        }

        $success = $_SESSION['flash_success'] ?? null;
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return $this->viewMobile('settings/loyalty', [
            'company' => $company,
            'user' => $user,
            'pageTitle' => 'Fidelidade',
            'activeNav' => 'settings',
            'showBackButton' => true,
            'embedded_delivery_fee' => number_format((float)$embedded_delivery_fee, 2, '.', ''),
            'loyalty_active' => $loyaltyActive,
            'loyalty_discount' => number_format($loyaltyDiscount, 2, '.', ''),
            'loyalty_message' => $loyaltyMessage,
            'coupon_prefix' => $couponPrefix,
            'cupons' => $cupons,
            'cupons_stats' => $cupons_stats,
            'success' => $success,
            'error' => $error,
        ]);
    }

    /**
     * POST /settings/loyalty - Salvar configurações de fidelidade
     */
    public function saveLoyalty(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $db = db();
        $section = $_POST['_section'] ?? 'taxa';

        if ($section === 'taxa') {
            // Taxa embutida
            $fee = (float)str_replace(',', '.', $_POST['embedded_delivery_fee'] ?? '0');
            if ($fee < 0) $fee = 0;

            $stmt = $db->prepare("UPDATE companies SET embedded_delivery_fee = :fee WHERE id = :id");
            $stmt->execute(['fee' => $fee, 'id' => $company['id']]);
        } else {
            // Cadastro completo
            $loyaltyActive = isset($_POST['loyalty_active']) ? 1 : 0;
            $loyaltyDiscount = (float)str_replace(',', '.', $_POST['loyalty_discount'] ?? '0');
            if ($loyaltyDiscount < 0) $loyaltyDiscount = 0;
            if ($loyaltyDiscount > 100) $loyaltyDiscount = 100;

            $loyaltyMessage = trim($_POST['loyalty_message'] ?? '');

            $couponPrefix = strtoupper(trim($_POST['coupon_prefix'] ?? 'WOLL'));
            if (strlen($couponPrefix) > 10) $couponPrefix = substr($couponPrefix, 0, 10);
            if (empty($couponPrefix)) $couponPrefix = 'WOLL';

            $stmt = $db->prepare("UPDATE companies SET coupon_prefix = :prefix WHERE id = :id");
            $stmt->execute(['prefix' => $couponPrefix, 'id' => $company['id']]);

            $stmt = $db->prepare("
                INSERT INTO loyalty_discounts (company_id, is_active, discount_percentage, welcome_message)
                VALUES (:company_id, :is_active, :discount, :message)
                ON DUPLICATE KEY UPDATE
                    is_active = VALUES(is_active),
                    discount_percentage = VALUES(discount_percentage),
                    welcome_message = VALUES(welcome_message)
            ");
            $stmt->execute([
                'company_id' => $company['id'],
                'is_active' => $loyaltyActive,
                'discount' => $loyaltyDiscount,
                'message' => $loyaltyMessage,
            ]);
        }

        $_SESSION['flash_success'] = 'Configurações salvas com sucesso!';
        $tab = $section === 'taxa' ? 'taxa' : 'cadastro';
        header('Location: /settings/loyalty?tab=' . $tab);
        exit;
    }

    /**
     * POST /settings/loyalty/create-coupon - Criar cupom via AJAX
     */
    public function createLoyaltyCoupon(array $params = [])
    {
        header('Content-Type: application/json');
        [$user, $company, $slug] = $this->guard();

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $code = strtoupper(trim($input['code'] ?? ''));
            $phone = trim($input['phone'] ?? '');
            $discount = (float)($input['discount'] ?? 0);
            $limit = (int)($input['limit'] ?? 1);

            if ($discount < 1 || $discount > 100) {
                echo json_encode(['success' => false, 'message' => 'Desconto deve estar entre 1% e 100%']);
                exit;
            }
            if ($limit < 0) $limit = 0;

            $db = db();

            if (!empty($phone)) {
                $stmt = $db->prepare('SELECT id FROM customer_loyalty_coupons WHERE company_id = ? AND customer_phone = ?');
                $stmt->execute([$company['id'], $phone]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Cliente já possui um cupom cadastrado']);
                    exit;
                }
            }

            if (empty($code)) {
                $code = 'CUPOM' . strtoupper(substr(md5(uniqid() . time()), 0, 6));
            }

            $stmt = $db->prepare('SELECT id FROM customer_loyalty_coupons WHERE company_id = ? AND coupon_code = ?');
            $stmt->execute([$company['id'], $code]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Código de cupom já existe']);
                exit;
            }

            $stmt = $db->prepare('
                INSERT INTO customer_loyalty_coupons
                (company_id, customer_phone, coupon_code, discount_percentage, usage_limit, times_used, is_used, created_at)
                VALUES (?, ?, ?, ?, ?, 0, 0, NOW())
            ');
            $stmt->execute([
                $company['id'],
                !empty($phone) ? $phone : null,
                $code,
                $discount,
                $limit,
            ]);

            echo json_encode(['success' => true, 'coupon_code' => $code, 'message' => 'Cupom criado com sucesso!']);
            exit;
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao criar cupom: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * POST /settings/loyalty/coupon/{id}/delete - Excluir cupom
     */
    public function deleteLoyaltyCoupon(array $params = [])
    {
        [$user, $company, $slug] = $this->guard();

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Cupom inválido';
            header('Location: /settings/loyalty');
            exit;
        }

        $db = db();
        $stmt = $db->prepare('DELETE FROM customer_loyalty_coupons WHERE id = ? AND company_id = ?');
        $stmt->execute([$id, $company['id']]);

        $_SESSION['flash_success'] = 'Cupom excluído com sucesso!';
        header('Location: /settings/loyalty?tab=cupons');
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
