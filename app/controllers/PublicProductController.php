<?php

declare(strict_types=1);
// app/controllers/PublicProductController.php

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

// Modelos e serviços específicos
require_once __DIR__ . '/../models/ProductCustomization.php';
require_once __DIR__ . '/../models/CrossSellGroup.php';
require_once __DIR__ . '/../services/CartStorage.php';
require_once __DIR__ . '/../services/ScheduledPauseService.php';

class PublicProductController extends Controller
{
    /**
     * Carrega horários de funcionamento da empresa
     */
    private function loadHours(int $companyId): array
    {
        $db = $this->db();
        $stmt = $db->prepare('SELECT * FROM company_hours WHERE company_id=?');
        $stmt->execute([$companyId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $out = [];
        foreach ($rows as $r) {
            $wd = (int)$r['weekday'];
            $out[$wd] = $r;
        }
        
        return $out;
    }

    /**
     * Verifica se está aberto agora
     * Retorna: [bool $isOpen, string $label]
     */
    private function openNow(array $todayRow): array
    {
        date_default_timezone_set(config('timezone') ?? 'America/Sao_Paulo');
        
        $now = new DateTime();
        $today = new DateTime();
        $tomorrow = new DateTime('+1 day');
        
        $ranges = [];
        $mkRange = function (?string $o, ?string $c) use ($today, $tomorrow) {
            if (!$o || !$c) {
                return null;
            }
            $open = DateTime::createFromFormat('Y-m-d H:i:s', $today->format('Y-m-d').' '.$o);
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

    /**
     * Calcula quando a loja abrirá novamente
     * Retorna: ['message' => string, 'time' => string] ou null se não houver próximo horário
     */
    private function getNextOpeningTime(array $hours, int $currentWeekday): ?array
    {
        date_default_timezone_set(config('timezone') ?? 'America/Sao_Paulo');
        $now = new DateTime();
        
        // Verificar se abre ainda hoje (horário futuro)
        $todayRow = $hours[$currentWeekday] ?? null;
        if ($todayRow && !empty($todayRow['is_open'])) {
            // Verificar open2 primeiro (se existir e for futuro)
            if (!empty($todayRow['open2'])) {
                $open2Time = DateTime::createFromFormat('Y-m-d H:i:s', $now->format('Y-m-d').' '.$todayRow['open2']);
                if ($open2Time && $open2Time > $now) {
                    return [
                        'message' => 'Voltaremos hoje às',
                        'time' => substr($todayRow['open2'], 0, 5)
                    ];
                }
            }
            
            // Verificar open1
            if (!empty($todayRow['open1'])) {
                $open1Time = DateTime::createFromFormat('Y-m-d H:i:s', $now->format('Y-m-d').' '.$todayRow['open1']);
                if ($open1Time && $open1Time > $now) {
                    return [
                        'message' => 'Voltaremos hoje às',
                        'time' => substr($todayRow['open1'], 0, 5)
                    ];
                }
            }
        }
        
        // Procurar próximo dia que abre (até 7 dias à frente)
        for ($i = 1; $i <= 7; $i++) {
            $nextDay = $currentWeekday + $i;
            if ($nextDay > 7) {
                $nextDay = $nextDay - 7;
            }
            
            $nextDayRow = $hours[$nextDay] ?? null;
            if ($nextDayRow && !empty($nextDayRow['is_open']) && !empty($nextDayRow['open1'])) {
                $message = $i === 1 ? 'Voltaremos amanhã às' : 'Voltaremos em breve';
                return [
                    'message' => $message,
                    'time' => substr($nextDayRow['open1'], 0, 5)
                ];
            }
        }
        
        return null;
    }

    /**
     * GET /{slug}/produto/{id}
     * Mostra a página pública do produto.
     * - Carrega empresa por slug
     * - Valida que o produto pertence à empresa e está ativo
     * - Carrega grupos de opções (combo) + itens (se o produto for do tipo != 'simple')
     */
    public function show($params)
    {
        $slug = $params['slug'] ?? null;
        $id   = isset($params['id']) ? (int)$params['id'] : 0;

        // Empresa
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa não encontrada';

            return;
        }

        // Produto
        $product = Product::find($id, true, (int)$company['id']);

        if (
            !$product ||
            (int)$product['company_id'] !== (int)$company['id'] ||
            (int)($product['active'] ?? 0) !== 1
        ) {
            http_response_code(404);
            return $this->view('public/product-not-found', [
                'company' => $company,
            ]);
        }

        // Grupos de opções (combo) — somente se tipo != 'simple'
        $comboGroups = [];
        $type = $product['type'] ?? 'simple';

        // Ocultar produto se tem ingrediente padrão inativo
        if (ProductCustomization::hasInactiveDefaultIngredient($id)) {
            http_response_code(404);
            return $this->view('public/product-not-found', [
                'company' => $company,
            ]);
        }

        if ($type !== 'simple' && method_exists('Product', 'getComboGroupsWithItems')) {
            $comboGroups = Product::getComboGroupsWithItems($id);
        }

        $mods = ProductCustomization::loadForPublic($id);
        $hasCustomization = !empty($mods);

        // Limpar personalização salva se acessando produto diretamente (sem parentId)
        // Personalização só deve persistir no contexto de combo
        if (!isset($_GET['customized_combo'])) {
            $store = CartStorage::instance();
            $store->removeCustomization($id, null, null);
        }

        $requireLogin = (bool)(config('login_required') ?? false);
        $isLogged = AuthCustomer::current($slug) !== null;
        $forceLoginModal = $requireLogin && !$isLogged && !empty($_GET['login']);

        // Carregar produtos de cross-sell inteligente (apenas para produtos simples)
        // Agora retorna array de seções: [['title' => '...', 'products' => [...]], ...]
        $crossSellSections = [];
        if ($type === 'simple') {
            $crossSellSections = $this->getSmartRecommendations($id, (int)$company['id']);
        }

        // Verificar se a loja está aberta
        date_default_timezone_set(config('timezone') ?? 'America/Sao_Paulo');
        $hours = $this->loadHours((int)$company['id']);
        $w = (int)date('N');
        $today = $hours[$w] ?? ['is_open' => 0];
        [$isOpenNow, $todayLabel] = $this->openNow($today);
        
        // Calcular próximo horário de abertura se estiver fechado
        $nextOpening = null;
        if (!$isOpenNow) {
            $nextOpening = $this->getNextOpeningTime($hours, $w);
        }
        
        // Verificar pausa programada
        $pauseService = new ScheduledPauseService($this->db());
        $pauseStatus = $pauseService->getPauseStatus((int)$company['id']);
        
        // Se pausado, sobrescreve isOpenNow
        if ($pauseStatus['is_paused']) {
            $isOpenNow = false;
        }

        // Renderiza a view pública
        // A view espera: $company, $product, $comboGroups, $mods
        return $this->view('public/product', [
            'company'           => $company,
            'product'           => $product,
            'comboGroups'       => $comboGroups,
            'mods'              => $mods,
            'hasCustomization'  => $hasCustomization,
            'forceLoginModal'   => $forceLoginModal,
            'crossSellSections' => $crossSellSections,
            'pauseStatus'       => $pauseStatus,
            'isOpenNow'         => $isOpenNow,
            'nextOpening'       => $nextOpening,
        ]);
    }

    /**
     * (Opcional) POST /{slug}/produto/{id}/customizar
     * Persiste a customização escolhida pelo cliente (se necessário) ou já encaminha ao carrinho.
     */
    public function saveCustomization($params)
    {
        $slug = $params['slug'] ?? null;
        $id   = isset($params['id']) ? (int)$params['id'] : 0;

        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa não encontrada';

            return;
        }

        $product = Product::find($id, true, (int)$company['id']);

        if (
            !$product ||
            (int)$product['company_id'] !== (int)$company['id'] ||
            (int)($product['active'] ?? 0) !== 1
        ) {
            http_response_code(404);
            return $this->view('public/product-not-found', [
                'company' => $company,
            ]);
        }

        $mods = ProductCustomization::loadForPublic($id);

        if (!$mods) {
            http_response_code(400);
            echo 'Personalização indisponível para este produto.';

            return;
        }

        $parentId = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
        $returnToParent = isset($_POST['return_to_parent']) && $_POST['return_to_parent'] == '1';
        
        $redirectTarget = $id;

        if ($parentId && $parentId !== $id) {
            $parentProduct = Product::find($parentId, true, (int)$company['id']);

            if (
                $parentProduct &&
                (int)$parentProduct['company_id'] === (int)$company['id'] &&
                (int)($parentProduct['active'] ?? 0) === 1
            ) {
                $redirectTarget = $parentId;
            }
        }

    $store = CartStorage::instance();

        $customSingle = [];
        $customSingleQty = [];

        // Novo formato: custom_single_items[grupo][item] = qty
        if (isset($_POST['custom_single_items']) && is_array($_POST['custom_single_items'])) {
            foreach ($_POST['custom_single_items'] as $g => $items) {
                $gi = (int)$g;
                
                if (!isset($mods[$gi]['items']) || !is_array($mods[$gi]['items'])) {
                    continue;
                }
                
                if (!is_array($items)) {
                    continue;
                }
                
                foreach ($items as $idx => $qty) {
                    $sel = (int)$idx;
                    $qtyVal = (int)$qty;
                    
                    // Só processar itens com qty > 0 (selecionados)
                    if ($qtyVal <= 0) {
                        continue;
                    }
                    
                    $maxIdx = count($mods[$gi]['items']) - 1;
                    if ($sel < 0 || $sel > $maxIdx) {
                        continue;
                    }
                    
                    // Armazenar múltiplas seleções
                    if (!isset($customSingle[$gi])) {
                        $customSingle[$gi] = [];
                        $customSingleQty[$gi] = [];
                    }
                    $customSingle[$gi][] = $sel;
                    $customSingleQty[$gi][$sel] = $qtyVal;
                }
            }
        }
        // Fallback: formato antigo custom_single[grupo] = idx
        elseif (isset($_POST['custom_single']) && is_array($_POST['custom_single'])) {
            foreach ($_POST['custom_single'] as $g => $idx) {
                $gi = (int)$g;
                $sel = (int)$idx;

                if (!isset($mods[$gi]['items']) || !is_array($mods[$gi]['items'])) {
                    continue;
                }
                $maxIdx = count($mods[$gi]['items']) - 1;

                if ($sel < 0 || $sel > $maxIdx) {
                    continue;
                }
                $customSingle[$gi] = [$sel];
                
                // Processar quantidade do item selecionado (custom_single_qty)
                $qty = 1;
                if (isset($_POST['custom_single_qty'][$g])) {
                    $qty = max(1, (int)$_POST['custom_single_qty'][$g]);
                }
                $customSingleQty[$gi] = [$sel => $qty];
            }
        }

        $customQty = [];

        if (isset($_POST['custom_qty']) && is_array($_POST['custom_qty'])) {
            foreach ($_POST['custom_qty'] as $g => $items) {
                if (!is_array($items)) {
                    continue;
                }
                $gi = (int)$g;

                if (!isset($mods[$gi])) {
                    continue;
                }
                $gType = $mods[$gi]['type'] ?? 'extra';

                if ($gType === 'single' || $gType === 'addon') {
                    continue;
                }

                foreach ($items as $i => $qty) {
                    $ii = (int)$i;

                    if (!isset($mods[$gi]['items'][$ii])) {
                        continue;
                    }

                    $item = $mods[$gi]['items'][$ii];
                    $min = isset($item['min']) ? (int)$item['min'] : 0;
                    $max = isset($item['max']) ? (int)$item['max'] : $min;

                    if ($max <= 0) {
                        $max = max($min, 99);
                    }

                    $val = (int)$qty;

                    if ($val < $min) {
                        $val = $min;
                    }

                    if ($max > 0 && $val > $max) {
                        $val = $max;
                    }

                    $customQty[$gi][$ii] = $val;
                }

                // Pool: enforce minimum constraint (extras beyond pool_free are allowed)
                if ($gType === 'pool' && isset($customQty[$gi])) {
                    $poolMin = isset($mods[$gi]['min']) ? (int)$mods[$gi]['min'] : 0;
                    $sum = array_sum($customQty[$gi]);

                    // Não limita mais o máximo — extras são cobrados no carrinho
                    if (false) {
                    }

                    // If under min, clamp up (best effort)
                    if ($sum < $poolMin) {
                        // Leave as-is; frontend should prevent this
                    }
                }
            }
        }

        $customChoice = [];

        if (isset($_POST['custom_choice']) && is_array($_POST['custom_choice'])) {
            foreach ($_POST['custom_choice'] as $g => $vals) {
                $gi = (int)$g;

                if (!isset($mods[$gi]) || ($mods[$gi]['type'] ?? '') !== 'addon') {
                    continue;
                }
                $items = $mods[$gi]['items'] ?? [];

                if (!$items) {
                    continue;
                }
                $maxIdx = count($items) - 1;
                $minSel = isset($mods[$gi]['min']) ? max(0, (int)$mods[$gi]['min']) : 0;
                $maxSel = isset($mods[$gi]['max']) ? (int)$mods[$gi]['max'] : count($items);

                if ($maxSel < 1) {
                    $maxSel = count($items);
                }

                if ($maxSel < $minSel) {
                    $maxSel = $minSel;
                }

                $selected = [];

                foreach ((array)$vals as $val) {
                    $ii = (int)$val;

                    if ($ii < 0 || $ii > $maxIdx) {
                        continue;
                    }

                    if (!in_array($ii, $selected, true)) {
                        $selected[] = $ii;
                    }

                    if ($maxSel > 0 && count($selected) >= $maxSel) {
                        // não permite exceder o máximo
                        continue;
                    }
                }

                if ($maxSel > 0 && count($selected) > $maxSel) {
                    $selected = array_slice($selected, 0, $maxSel);
                }

                if ($minSel > 0 && count($selected) < $minSel) {
                    // garante o mínimo preenchendo com defaults e depois com os primeiros itens disponíveis
                    foreach ($items as $ii => $item) {
                        if (!empty($item['selected']) && !in_array($ii, $selected, true)) {
                            $selected[] = $ii;

                            if (count($selected) >= $minSel) {
                                break;
                            }
                        }
                    }

                    for ($ii = 0; $ii <= $maxIdx && count($selected) < $minSel; $ii++) {
                        if (!in_array($ii, $selected, true)) {
                            $selected[] = $ii;
                        }
                    }
                }

                $customChoice[$gi] = array_slice($selected, 0, max(0, $maxSel));
            }
        }

        $quantity = isset($_POST['qty']) ? max(1, (int)$_POST['qty']) : 1;
        $editCartItemUid = isset($_POST['edit_cart_item']) ? trim($_POST['edit_cart_item']) : (isset($_GET['edit_cart_item']) ? trim($_GET['edit_cart_item']) : '');
        
        // Suporte para personalização de múltiplas unidades
        $unitIndex = isset($_POST['unit']) ? (int)$_POST['unit'] : (isset($_GET['unit']) ? (int)$_GET['unit'] : 0);
        $totalUnits = isset($_POST['total_units']) ? (int)$_POST['total_units'] : (isset($_GET['total_units']) ? (int)$_GET['total_units'] : 1);

        // Salva personalização contextualizada se for cross-sell/combo
        // Usar o novo parâmetro unitIndex do setCustomization
        
        $customToSave = [
            'single'    => $customSingle,
            'singleQty' => $customSingleQty,
            'qty'       => $customQty,
            'choice'    => $customChoice,
            'quantity'  => $quantity,
            'unit_index' => $unitIndex,
            'total_units' => $totalUnits,
        ];
        
        $parentForSave = $parentId && $parentId !== $id ? $parentId : null;
        $unitForSave = $unitIndex > 0 ? $unitIndex : null;
        
        $store->setCustomization(
            $id,
            $customToSave,
            null,
            $parentForSave,
            $unitForSave
        );

        // Se tem parent_id e return_to_parent, apenas salva a personalização e volta
        // Se tem parent_id sem return_to_parent, é personalização de item do combo -> volta para o combo
        // Se não tem parent_id, é produto simples direto -> adiciona ao carrinho e redireciona
        
        // NOVO: Se está personalizando múltiplas unidades e não é a última, ir para a próxima
        if ($unitIndex > 0 && $totalUnits > 1 && $unitIndex < $totalUnits && $parentId && $parentId !== $id) {
            $nextUnit = $unitIndex + 1;
            $redirect = base_url($slug . '/produto/' . $id . '/customizar?parent_id=' . $parentId . '&unit=' . $nextUnit . '&total_units=' . $totalUnits);
            header('Location: ' . $redirect);
            exit;
        }
        
        // CORREÇÃO: Se tem parent_id válido (mesmo sendo a última unidade), sempre volta para o combo
        // Nunca adiciona ao carrinho quando está personalizando item de combo
        if ($parentId && $parentId !== $id && $redirectTarget !== $id) {
            // Verificar se é edição de item do carrinho
            if ($editCartItemUid) {
                // Editando componente de um combo existente no carrinho
                $customizationSnapshot = [
                    'single'    => $customSingle,
                    'singleQty' => $customSingleQty,
                    'qty'       => $customQty,
                    'choice'    => $customChoice,
                    'quantity'  => $quantity,
                ];
                
                $cartRef = $store->getCart();
                $itemFound = false;
                
                foreach ($cartRef as &$cartItem) {
                    if (($cartItem['uid'] ?? '') === $editCartItemUid) {
                        // Atualizar a personalização do componente específico do combo
                        if (!isset($cartItem['component_customizations'])) {
                            $cartItem['component_customizations'] = [];
                        }
                        if (!isset($cartItem['component_customizations'][$id])) {
                            $cartItem['component_customizations'][$id] = [];
                        }
                        
                        // Se está editando uma unidade específica, salvar em unit_customizations
                        if ($unitIndex > 0) {
                            if (!isset($cartItem['component_customizations'][$id]['unit_customizations'])) {
                                $cartItem['component_customizations'][$id]['unit_customizations'] = [];
                            }
                            $cartItem['component_customizations'][$id]['unit_customizations'][$unitIndex] = $customizationSnapshot;
                        } else {
                            // Sem unidade específica, salvar na customization padrão
                            $cartItem['component_customizations'][$id]['customization'] = $customizationSnapshot;
                        }
                        $itemFound = true;
                        break;
                    }
                }
                unset($cartItem);
                
                if ($itemFound) {
                    $store->setCart($cartRef);
                }
                
                $redirect = base_url($slug . '/cart');
            } else {
                // Personalização de item do combo (não edição de carrinho)
                // Apenas volta para a página do combo - NÃO adiciona ao carrinho
                $redirect = base_url($slug . '/produto/' . $redirectTarget);
            }
            
            header('Location: ' . $redirect);
            exit;
        }
        
        if ($returnToParent && $redirectTarget !== $id) {
            // Cross-sell personalizado - redirecionar para o produto pai com parâmetro
            $redirect = base_url($slug . '/produto/' . $redirectTarget . '?customized_cross_sell=' . $id);
            header('Location: ' . $redirect);
            exit;
        }
        
        // Se chegou aqui, é um produto simples independente (sem parent_id válido)
        // Adiciona ou atualiza item no carrinho
        $customizationSnapshot = [
            'single'    => $customSingle,
            'singleQty' => $customSingleQty,
            'qty'       => $customQty,
            'choice'    => $customChoice,
            'quantity'  => $quantity,
        ];

        $cartRef = $store->getCart();
        
        // Verificar se é edição de item existente
        if ($editCartItemUid) {
            $itemFound = false;
            foreach ($cartRef as &$cartItem) {
                if (($cartItem['uid'] ?? '') === $editCartItemUid) {
                    // Atualizar o item existente
                    $cartItem['customization'] = $customizationSnapshot;
                    $cartItem['qty'] = $quantity;
                    $itemFound = true;
                    break;
                }
            }
            unset($cartItem);
            
            if (!$itemFound) {
                // Se não encontrou o item, criar novo (fallback)
                $cartRef[] = [
                    'uid' => $this->generateUid(),
                    'company_id' => (int)$company['id'],
                    'product_id' => $id,
                    'qty' => $quantity,
                    'combo' => null,
                    'customization' => $customizationSnapshot,
                    'combo_customizations' => [],
                    'added_at' => time(),
                ];
            }
        } else {
            // Criar novo item no carrinho
            $cartRef[] = [
                'uid' => $this->generateUid(),
                'company_id' => (int)$company['id'],
                'product_id' => $id,
                'qty' => $quantity,
                'combo' => null,
                'customization' => $customizationSnapshot,
                'combo_customizations' => [],
                'added_at' => time(),
            ];
        }
        
        // Processar produtos de cross-sell (se houver)
        if (isset($_POST['cross_sell']) && is_array($_POST['cross_sell'])) {
            
            foreach ($_POST['cross_sell'] as $crossSellId) {
                $crossSellId = (int)$crossSellId;
                
                if ($crossSellId <= 0) {
                    continue;
                }
                
                $crossSellProduct = Product::find($crossSellId, true, (int)$company['id']);
                
                if (!$crossSellProduct || 
                    (int)($crossSellProduct['company_id'] ?? 0) !== (int)$company['id'] || 
                    (int)($crossSellProduct['active'] ?? 0) !== 1) {
                    continue;
                }
                
                // Buscar personalização salva para este cross-sell (contextualizada com o produto pai)
                $crossSellCustomization = $store->getCustomization($crossSellId, null, $product['id']);
                
                // Adicionar cross-sell ao carrinho
                $cartRef[] = [
                    'uid' => $this->generateUid(),
                    'company_id' => (int)$company['id'],
                    'product_id' => $crossSellId,
                    'qty' => 1,
                    'combo' => null,
                    'customization' => $crossSellCustomization,
                    'combo_customizations' => [],
                    'added_at' => time(),
                ];
                // Consome personalização temporária do cross-sell para não vazar para adições futuras.
                $store->removeCustomization($crossSellId, null, $product['id']);
            }
        }

        // Consome personalização temporária do produto principal após adicionar/editar no carrinho.
        $store->removeCustomization($id, null, null);
        
        $store->setCart($cartRef);

        $redirect = base_url($slug . '/cart');
        
        header('Location: ' . $redirect);
        exit;
    }

    private function generateUid()
    {
        return bin2hex(random_bytes(6));
    }

    /**
     * Obtém recomendações inteligentes
     * Prioridade: 1) Regras de Cross-Sell configuradas, 2) Machine Learning, 3) Mesma categoria
     * Retorna: ['products' => [...], 'title' => '...']
     */
    private function getSmartRecommendations(int $productId, int $companyId): array
    {
        // 1. PRIORIDADE: Verificar se há regras de cross-sell configuradas
        $crossSellSections = $this->loadCrossSellProducts($productId, $companyId);
        
        if (!empty($crossSellSections)) {
            return $crossSellSections;
        }
        
        // 2. Se não houver regras, tentar ML
        require_once __DIR__ . '/../services/RecommendationEngine.php';
        
        $engine = new RecommendationEngine($this->db());
        
        // Obter customer_id se logado
        $customerId = null;
        $sessionId = null;
        
        if (isset($_SESSION['customer']['id'])) {
            $customerId = (int)$_SESSION['customer']['id'];
        }
        
        // Para clientes anônimos, usar session_id do localStorage (será passado via cookie)
        if ($customerId === null && isset($_COOKIE['ml_session_id'])) {
            $sessionId = $_COOKIE['ml_session_id'];
        }
        
        try {
            $recommendations = $engine->getRecommendations(
                $companyId,
                $productId,
                $customerId,
                $sessionId,
                4 // limite de 4 recomendações
            );
            
            // Só retorna se houver recomendações
            if (!empty($recommendations)) {
                return [['products' => $recommendations, 'title' => 'Recomendados para você']];
            }
            
            return [];
        } catch (Exception $e) {
            error_log("Erro ao gerar recomendações ML: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Carrega produtos de cross-sell baseado nas regras de categoria configuradas
     * Retorna: array de seções, cada uma com ['products' => [...], 'title' => '...']
     */
    private function loadCrossSellProducts(int $productId, int $companyId): array
    {
        $db = $this->db();
        
        // Buscar categoria do produto atual
        $stmt = $db->prepare("SELECT category_id FROM products WHERE id = ? AND company_id = ?");
        $stmt->execute([$productId, $companyId]);
        $categoryId = $stmt->fetchColumn();
        
        if (!$categoryId) {
            return [];
        }
        
        // Buscar recomendações configuradas (sistema otimizado de grupos)
        // Retorna array de seções: [['title' => '...', 'products' => [...]], ...]
        $sections = CrossSellGroup::getRecommendationsForCategory($companyId, (int)$categoryId, 6);
        
        if (!empty($sections)) {
            return $sections;
        }
        
        // Fallback inteligente: Buscar produtos de OUTRAS categorias (não da mesma)
        // Isso faz mais sentido para cross-sell - oferecer complementos, não similares
        // Agnóstico a nomes de categorias - funciona para qualquer empresa
        
        $stmt = $db->prepare("
            SELECT p.id, p.name, p.image, p.price, p.promo_price,
                   (SELECT COUNT(*) FROM product_custom_groups WHERE product_id = p.id) as has_ingredients
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.company_id = ?
              AND p.category_id != ?
              AND p.active = 1
              AND p.type = 'simple'
              AND c.active = 1
            ORDER BY RAND()
            LIMIT 6
        ");
        
        $stmt->execute([$companyId, $categoryId]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Só retorna se houver produtos de outras categorias
        if (!empty($products)) {
            return [['products' => $products, 'title' => 'Recomendados para você']];
        }
        
        return [];
    }

    public function customize($params)
    {
        $slug = $params['slug'] ?? null;
        $id   = isset($params['id']) ? (int)$params['id'] : 0;

        $company = Company::findBySlug($slug);

        if (!$company || !$company['active']) {
            http_response_code(404);
            echo 'Empresa não encontrada';

            return;
        }

        // Nota: Personalização NÃO deve exigir login - apenas o checkout
        // O requireLogin verifica se a empresa exige login, mas a personalização 
        // é permitida para visitantes (as customizações são salvas na sessão)

        $product = Product::find($id, true, (int)$company['id']);

        if (!$product || (int)$product['company_id'] !== (int)$company['id'] || (int)($product['active'] ?? 0) !== 1) {
            http_response_code(404);
            return $this->view('public/product-not-found', [
                'company' => $company,
            ]);
        }

        $mods = ProductCustomization::loadForPublic($id);

        if (!$mods) {
            http_response_code(404);
            echo 'Personalização indisponível para este produto.';
            return;
        }

        // Carregar personalização salva na sessão, se existir, usando contexto (parentId:productId)
        $store = CartStorage::instance();
        $parentIdCtx = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;
        $returnToParent = isset($_GET['return_to_parent']) && $_GET['return_to_parent'] == '1';
        
        // Suporte para personalização de múltiplas unidades
        $unitIndex = isset($_GET['unit']) ? (int)$_GET['unit'] : 0;
        $totalUnits = isset($_GET['total_units']) ? (int)$_GET['total_units'] : 1;
        
        $editCartItemUid = isset($_GET['edit_cart_item']) ? trim($_GET['edit_cart_item']) : '';
        
        $savedCustomization = null;
        
        // Se é edição de item do carrinho, carregar a personalização do item
        if ($editCartItemUid) {
            $cartRef = $store->getCart();
            foreach ($cartRef as $cartItem) {
                if (($cartItem['uid'] ?? '') === $editCartItemUid) {
                    // Se tem parent_id, buscar na personalização do componente
                    if ($parentIdCtx && $parentIdCtx !== $id && isset($cartItem['component_customizations'][$id])) {
                        $compData = $cartItem['component_customizations'][$id];
                        // Se tem unit_customizations e está editando uma unidade específica
                        if ($unitIndex > 0 && !empty($compData['unit_customizations'][$unitIndex])) {
                            $savedCustomization = $compData['unit_customizations'][$unitIndex];
                        } elseif (!empty($compData['customization'])) {
                            $savedCustomization = $compData['customization'];
                        }
                    } elseif ((int)($cartItem['product_id'] ?? 0) === $id) {
                        // Senão, é o produto principal
                        $savedCustomization = $cartItem['customization'] ?? null;
                    }
                    break;
                }
            }
        } else {
            // Senão, carregar da sessão temporária (contexto de cross-sell/combo)
            // Usar o parâmetro unitIndex diretamente no getCustomization
            $parentCtx = $parentIdCtx && $parentIdCtx !== $id ? $parentIdCtx : null;
            $savedCustomization = $store->getCustomization($id, null, $parentCtx, $unitIndex > 0 ? $unitIndex : null);
        }
        
        if ($savedCustomization && is_array($savedCustomization)) {
            // Preencher mods com os valores salvos
            // single: [groupIndex => [selectedIndexes]] (suporta múltiplas seleções)
            if (isset($savedCustomization['single']) && is_array($savedCustomization['single'])) {
                foreach ($savedCustomization['single'] as $gi => $selIdxs) {
                    if (!isset($mods[$gi]['items'])) {
                        continue;
                    }
                    
                    // Garantir que seja array
                    $selectedIndexes = is_array($selIdxs) ? $selIdxs : [$selIdxs];
                    
                    foreach ($mods[$gi]['items'] as $ii => &$item) {
                        $item['default'] = in_array($ii, $selectedIndexes) ? 1 : 0;
                        $item['selected'] = $item['default'];
                        
                        // Restaurar quantidade do item selecionado (singleQty)
                        if ($item['default'] && isset($savedCustomization['singleQty'][$gi][$ii])) {
                            $item['selected_qty'] = (int)$savedCustomization['singleQty'][$gi][$ii];
                        } elseif ($item['default'] && isset($savedCustomization['singleQty'][$gi]) && !is_array($savedCustomization['singleQty'][$gi])) {
                            // Fallback para formato antigo
                            $item['selected_qty'] = (int)$savedCustomization['singleQty'][$gi];
                        }
                    }
                    unset($item);
                }
            }
            // qty: [groupIndex => [itemIndex => qty]]
            if (isset($savedCustomization['qty']) && is_array($savedCustomization['qty'])) {
                foreach ($savedCustomization['qty'] as $gi => $items) {
                    foreach ($items as $ii => $qty) {
                        if (isset($mods[$gi]['items'][$ii])) {
                            $mods[$gi]['items'][$ii]['qty'] = (int)$qty;
                        }
                    }
                }
            }
            // choice: [groupIndex => [itemIndexes]]
            if (isset($savedCustomization['choice']) && is_array($savedCustomization['choice'])) {
                foreach ($savedCustomization['choice'] as $gi => $itemIndexes) {
                    if (isset($mods[$gi]['items'])) {
                        foreach ($mods[$gi]['items'] as $ii => &$item) {
                            $item['selected'] = in_array($ii, (array)$itemIndexes) ? 1 : 0;
                        }
                        unset($item);
                    }
                }
            }
        }

        $parentId = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;
        $returnToParent = isset($_GET['return_to_parent']) && $_GET['return_to_parent'] == '1';
        $parentBackUrl = null;

        // Se está editando item do carrinho, voltar para o carrinho
        if ($editCartItemUid) {
            $backUrl = base_url($slug . '/cart');
            $parentBackUrl = null;
        } elseif ($parentId && $parentId !== $id) {
            $parentProduct = Product::find($parentId, true, (int)$company['id']);

            if (
                $parentProduct &&
                (int)$parentProduct['company_id'] === (int)$company['id'] &&
                (int)($parentProduct['active'] ?? 0) === 1
            ) {
                $parentBackUrl = base_url($slug . '/produto/' . $parentId);
            } else {
                $parentId = 0;
                $returnToParent = false;
            }
            $backUrl = $parentBackUrl ?? base_url($slug . '/produto/' . $id);
        } else {
            $parentId = 0;
            $backUrl = base_url($slug . '/produto/' . $id);
        }

        return $this->view('public/customization', [
            'company'        => $company,
            'product'        => $product,
            'mods'           => $mods,
            'parentBackUrl'  => $parentBackUrl,
            'parentProductId' => $parentId,
            'returnToParent' => $returnToParent,
            'unitIndex'      => $unitIndex,
            'totalUnits'     => $totalUnits,
        ]);
    }

    /**
     * Verifica se um produto tem personalização salva na sessão
     */
    public function checkCustomization($params)
    {
        header('Content-Type: application/json');
        
        $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
        $parentId = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;
        $unitIndex = isset($_GET['unit']) ? (int)$_GET['unit'] : 0;
        $totalUnits = isset($_GET['total_units']) ? (int)$_GET['total_units'] : 0;
        
        if ($productId <= 0) {
            echo json_encode(['has_customization' => false]);
            return;
        }
        
        $store = CartStorage::instance();
        $parentCtx = $parentId && $parentId !== $productId ? $parentId : null;
        
        // Se tem totalUnits, verificar cada unidade
        if ($totalUnits > 1) {
            $unitCustomizations = [];
            for ($i = 1; $i <= $totalUnits; $i++) {
                $unitCustom = $store->getCustomization($productId, null, $parentCtx, $i);
                $unitCustomizations[$i] = $unitCustom !== null && !empty($unitCustom);
            }
            echo json_encode([
                'has_customization' => true,
                'unit_customizations' => $unitCustomizations
            ]);
            return;
        }
        
        // Verificar unidade específica
        $customization = $store->getCustomization($productId, null, $parentCtx, $unitIndex > 0 ? $unitIndex : null);
        
        echo json_encode([
            'has_customization' => $customization !== null && !empty($customization)
        ]);
    }

    /**
     * Cancela a personalização (limpa da sessão) e volta para página anterior
     */
    public function cancelCustomization($params)
    {
        $slug = $params['slug'] ?? null;
        $id = isset($params['id']) ? (int)$params['id'] : 0;

        $company = Company::findBySlug($slug);

        if (!$company || !$company['active']) {
            http_response_code(404);
            echo 'Empresa não encontrada';
            return;
        }

        $product = Product::find($id, true, (int)$company['id']);

        if (!$product || (int)$product['company_id'] !== (int)$company['id'] || (int)($product['active'] ?? 0) !== 1) {
            // Produto não encontrado - redirecionar para a home
            header('Location: ' . base_url($slug));
            exit;
        }

        // Buscar parent_id da querystring
        $parentId = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;
        
        // Limpar personalização contextualizada
        $store = CartStorage::instance();
        if ($parentId && $parentId !== $id) {
            $store->removeCustomization($id, null, $parentId);
        } else {
            $store->removeCustomization($id, null, null);
        }

        // Redirecionar de volta
        $backUrl = base_url($slug . '/produto/' . ($parentId && $parentId !== $id ? $parentId : $id));
        header('Location: ' . $backUrl);
        exit;
    }
}
