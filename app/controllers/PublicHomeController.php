<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

// Helper específico
require_once __DIR__ . '/../helpers/daily_highlight_helper.php';
require_once __DIR__ . '/../models/ProductCustomization.php';

class PublicHomeController extends Controller
{
    private function maybeRedirectToCanonicalSlug(string $requestSlug, array $company): void
    {
        $canonical = (string)($company['slug'] ?? '');
        if ($canonical === '') {
            return;
        }

        $decodedRequest = trim(rawurldecode($requestSlug));
        if ($decodedRequest === '' || $decodedRequest === $canonical) {
            return;
        }

        $query = $_SERVER['QUERY_STRING'] ?? '';
        $target = '/' . rawurlencode($canonical) . ($query !== '' ? ('?' . $query) : '');
        header('Location: ' . $target, true, 301);
        exit;
    }

    private function loadHours(int $companyId): array
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

    private function openNow(array $todayRow): array
    {
        date_default_timezone_set(config('timezone') ?? 'America/Sao_Paulo');
        $today    = new DateTime('today 00:00:00');
        $tomorrow = (clone $today)->modify('+1 day');
        $now      = new DateTime();

        $ranges = [];
        $mkRange = function (?string $o, ?string $c) use ($today, $tomorrow) {
            if (!$o || !$c) {
                return null;
            }
            $open  = DateTime::createFromFormat('Y-m-d H:i:s', $today->format('Y-m-d').' '.$o);
            $close = DateTime::createFromFormat('Y-m-d H:i:s', $today->format('Y-m-d').' '.$c);

            if (!$open || !$close) {
                return null;
            }

            if ($close < $open) { // vira à meia-noite
                $close = DateTime::createFromFormat('Y-m-d H:i:s', $tomorrow->format('Y-m-d').' '.$c);
            }

            return [$open, $close];
        };

        if (!empty($todayRow['is_open'])) {
            if ($r = $mkRange($todayRow['open1'] ?? null, $todayRow['close1'] ?? null)) {
                $ranges[] = $r;
            }

            if ($r = $mkRange($todayRow['open2'] ?? null, $todayRow['close2'] ?? null)) {
                $ranges[] = $r;
            }
        }

        $open = false;

        foreach ($ranges as [$a, $b]) {
            if ($now >= $a && $now <= $b) {
                $open = true;
                break;
            }
        }

        $label = 'Fechado hoje';

        if (!empty($todayRow['is_open']) && !empty($todayRow['open1']) && !empty($todayRow['close1'])) {
            $label = substr($todayRow['open1'], 0, 5).' - '.substr($todayRow['close1'], 0, 5);

            if (!empty($todayRow['open2']) && !empty($todayRow['close2'])) {
                $label .= ' / '.substr($todayRow['open2'], 0, 5).' - '.substr($todayRow['close2'], 0, 5);
            }
        }

        return [$open, $label];
    }

    /** HOME */
    public function index($params)
    {
        $slug = $params['slug'] ?? null;
        $q    = isset($_GET['q']) ? trim($_GET['q']) : '';

        // Rotas reservadas: não tratar como cardápio (evita "Empresa não encontrada")
        $slugNorm = strtolower((string)$slug);
        if ($slugNorm === 'superadmin' || $slugNorm === 'super-admin') {
            header('Location: ' . base_url('superadmin'), true, 302);
            exit;
        }
        if (in_array($slugNorm, ['admin', 'api', 'webhook', 'vendas', 'robots', 'sitemap', 'img', 'push'], true)) {
            http_response_code(404);
            echo 'Rota reservada';
            return;
        }

        // Verificar se sessão expirou (para abrir modal de login)
        $forceLoginModal = isset($_GET['expired']) || isset($_GET['session_expired']);

        $company = Company::findBySlug((string)$slug);

        if (!$company || !$company['active']) {
            http_response_code(404);
            echo 'Empresa não encontrada';

            return;
        }

        $this->maybeRedirectToCanonicalSlug((string)$slug, $company);

        date_default_timezone_set(config('timezone') ?? 'America/Sao_Paulo');

        $cid = (int)$company['id'];
        $showMostOrdered = !isset($company['show_most_ordered']) || (int)$company['show_most_ordered'] === 1;
        $db  = $this->db();

        $categories   = Category::listByCompany($cid);
        // Cardápio completo
        $products     = Product::listByCompany($cid);
        // Resultados da busca (se houver termo)
        $searchResults = ($q !== '') ? Product::listByCompany($cid, $q) : [];

        // Ocultar produtos cujo ingrediente padrão está inativo
        $hiddenProductIds = ProductCustomization::productIdsHiddenByIngredient($cid);
        if ($hiddenProductIds) {
            $hiddenMap = array_flip($hiddenProductIds);
            $products = array_values(array_filter($products, fn($p) => !isset($hiddenMap[$p['id']])));
            if ($searchResults) {
                $searchResults = array_values(array_filter($searchResults, fn($p) => !isset($hiddenMap[$p['id']])));
            }
        }

        $hours  = $this->loadHours($cid);
        $w      = (int)date('N');
        $today  = $hours[$w] ?? ['is_open' => 0];
        [$isOpenNow, $todayLabel] = $this->openNow($today);

        // ===== Pausa Programada =====
        require_once __DIR__ . '/../services/ScheduledPauseService.php';
        $pauseService = new \ScheduledPauseService($db);
        $pauseStatus = $pauseService->getPauseStatus($cid);
        
        // Se está em pausa programada, considera como fechado
        if ($pauseStatus['is_paused']) {
            $isOpenNow = false;
        }

        // ===== Novidades & Mais pedidos =====
        $diasNovidade = (int)(config('novidades_days') ?? 14);
        $novidades    = Product::novidadesByCompanyId($db, $cid, $diasNovidade, 12);
        $maisPedidos  = $showMostOrdered ? Product::maisPedidosByCompanyId($db, $cid, 12) : [];

        // Remover produtos ocultos das seções especiais
        if ($hiddenProductIds) {
            $novidades = array_values(array_filter($novidades, fn($p) => !isset($hiddenMap[$p['id']])));
            $maisPedidos = array_values(array_filter($maisPedidos, fn($p) => !isset($hiddenMap[$p['id']])));
        }

        $mostraNovidade    = count($novidades) > 0;
        $mostraMaisPedidos = count($maisPedidos) > 0;
        $temAbas           = $mostraNovidade || $mostraMaisPedidos;
        $topMaisPedido     = $mostraMaisPedidos ? $maisPedidos[0] : null;

        // Último pedido do cliente logado (para "Repetir pedido")
        $lastOrder = null;
        $lastOrderItems = [];
        $customerSession = $_SESSION['customer'] ?? null;
        if ($customerSession) {
            $phone = $customerSession['e164'] ?? $customerSession['whatsapp'] ?? '';
            if ($phone !== '') {
                $lastOrder = Order::lastCompletedByPhone($db, $phone, $cid);
                if ($lastOrder && !empty($lastOrder['id'])) {
                    // Buscar itens que ainda podem ser reordenados (produtos ativos)
                    $lastOrderItems = Order::getReorderableItems((int)$lastOrder['id'], $cid);
                }
            }
        }

        return $this->view('public/menu/index', compact(
            'company',
            'categories',
            'products',
            'searchResults',
            'q',
            'hours',
            'isOpenNow',
            'todayLabel',
            'novidades',
            'maisPedidos',
            'topMaisPedido',
            'mostraNovidade',
            'mostraMaisPedidos',
            'temAbas',
            'forceLoginModal',
            'pauseStatus',
            'lastOrder',
            'lastOrderItems'
        ));
    }

    /** POLÍTICA DE PRIVACIDADE */
    public function privacyPolicy($params)
    {
        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company || !$company['active']) {
            http_response_code(404);
            echo 'Empresa não encontrada';
            return;
        }

        $title = 'Política de Privacidade - ' . ($company['name'] ?? 'Cardápio');
        return $this->view('public/privacy_policy', compact('company', 'title'));
    }

    /** BUSCAR */
    public function buscar($params)
    {
        $slug = $params['slug'] ?? null;
        $q    = isset($_GET['q']) ? trim($_GET['q']) : '';

        $company = Company::findBySlug($slug);

        if (!$company || !$company['active']) {
            http_response_code(404);
            echo 'Empresa não encontrada';

            return;
        }

        $cid    = (int)$company['id'];
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($isAjax) {
            // Retorna o HTML com os produtos para busca instantânea
            $products = Product::listByCompany($cid, $q);

            // Ocultar produtos cujo ingrediente padrão está inativo
            $hiddenProductIds = ProductCustomization::productIdsHiddenByIngredient($cid);
            if ($hiddenProductIds) {
                $hiddenMap = array_flip($hiddenProductIds);
                $products = array_values(array_filter($products, fn($p) => !isset($hiddenMap[$p['id']])));
            }

            // badgePromo() definida globalmente em app/core/CommonHelpers.php
            ob_start();

            if ($q !== '') {
                echo '<h2 class="text-xl font-bold mb-2">Resultado da busca</h2><div class="grid gap-3">';

                if (!$products) {
                    echo '<div class="p-4 border bg-white rounded-xl">Nada encontrado para <strong>'.e($q).'</strong>.</div>';
                }

                foreach ($products as $p) {
                    include __DIR__ . '/../views/public/partials_card.php';
                }
                echo '</div>';
            }
            $html = ob_get_clean();
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;

            return;
        }

        // Fallback: redireciona para a home com o termo de busca
        $url = base_url(rawurlencode($slug));

        if ($q !== '') {
            $url .= '?q=' . urlencode($q);
        }
        header('Location: ' . $url);
        exit;
    }
}
