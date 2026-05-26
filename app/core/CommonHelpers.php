<?php

declare(strict_types=1);

/**
 * 🔧 HELPERS CENTRALIZADOS - Sistema Multi-Menu
 * 
 * Este arquivo centraliza todas as funções helper usadas em todo o sistema,
 * eliminando duplicações e garantindo consistência.
 * 
 * @version 2.0
 * @author Sistema Multi-Menu
 */

// ============================================================================
// 🔒 SEGURANÇA E SANITIZAÇÃO
// ============================================================================

/**
 * Escape HTML para prevenir XSS
 * @param mixed $value Valor a ser escapado
 * @return string Valor escapado
 */
if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

// ============================================================================
// 🌐 URL E NAVEGAÇÃO
// ============================================================================

/**
 * Gera URL base do sistema
 * @param string $path Caminho adicional
 * @return string URL completa
 */
if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        // When behind a reverse proxy or tunnel (e.g. Cloudflare Tunnel, nginx),
        // use forwarded headers to build the correct public-facing URL.
        $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        $requestHost    = $_SERVER['HTTP_HOST'] ?? '';

        if ($forwardedProto !== '' && $requestHost !== '') {
            $scheme = strtolower(trim($forwardedProto)) === 'https' ? 'https' : 'http';
            $config = $scheme . '://' . $requestHost;
        } else {
            $config = config('base_url');

            if (!$config) {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host   = $requestHost ?: 'localhost';
                $dir    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
                $config = $scheme . '://' . $host . ($dir ? $dir : '');
            }
        }

        $base = rtrim($config, '/');
        $path = ltrim($path, '/');

        return $path ? "$base/$path" : $base;
    }
}

/**
 * Força caminho local em /uploads a partir de URL ou nome de arquivo.
 * Originalmente definida em product.php — centralizada aqui como helper global.
 * @param string|null $maybeUrlOrName URL completa ou nome do arquivo
 * @param string $fallback Caminho de fallback (relativo ao base_url)
 * @return string URL completa para o arquivo em /uploads
 */
if (!function_exists('local_upload_src')) {
    function local_upload_src(?string $maybeUrlOrName, string $fallback = ''): string
    {
        $raw = trim((string)($maybeUrlOrName ?? ''));

        if ($raw === '') {
            return $fallback ? base_url($fallback) : '';
        }
        $path = parse_url($raw, PHP_URL_PATH);
        $base = basename($path ?: $raw);

        if ($base === '' || $base === '/') {
            return $fallback ? base_url($fallback) : '';
        }

        return base_url('uploads/' . $base);
    }
}

// ============================================================================
// 💰 FORMATAÇÃO DE VALORES
// ============================================================================

/**
 * Formata valor para Real Brasileiro
 * @param float|string $value Valor numérico
 * @return string Valor formatado (ex: R$ 123,45)
 */
if (!function_exists('price_br')) {
    function price_br($value): string
    {
        $number = (float)$value;
        return 'R$ ' . number_format($number, 2, ',', '.');
    }
}

/**
 * Formata valor monetário BRL usando Intl se disponível
 * @param float|string $value Valor numérico
 * @return string Valor formatado
 */
if (!function_exists('format_currency_br')) {
    function format_currency_br($value): string
    {
        $number = (float)$value;
        
        if (class_exists('NumberFormatter')) {
            $formatter = new NumberFormatter('pt_BR', NumberFormatter::CURRENCY);
            return $formatter->formatCurrency($number, 'BRL');
        }
        
        return price_br($number);
    }
}

// ============================================================================
// 🎨 UI E COMPONENTES
// ============================================================================

/**
 * Gera badge "Novo" para produtos
 * @param string $date Data de criação
 * @param int $days Dias para considerar novo (padrão: 7)
 * @return string HTML do badge ou string vazia
 */
if (!function_exists('badge_new')) {
    function badge_new(string $date, int $days = 7): string
    {
        if (!$date) return '';
        
        $created = strtotime($date);
        $limit = time() - ($days * 24 * 60 * 60);
        
        if ($created > $limit) {
            return '<span class="badge badge-new">Novo</span>';
        }
        
        return '';
    }
}

/**
 * Gera badge "Promoção" para produtos
 * @param float $price Preço normal
 * @param float $promoPrice Preço promocional
 * @return string HTML do badge ou string vazia
 */
if (!function_exists('badge_promo')) {
    function badge_promo($price, $promoPrice): string
    {
        $price = (float)$price;
        $promoPrice = (float)$promoPrice;
        
        if ($promoPrice > 0 && $promoPrice < $price) {
            $discount = round((($price - $promoPrice) / $price) * 100);
            return "<span class=\"badge badge-promo\">-{$discount}%</span>";
        }
        
        return '';
    }
}

/**
 * Verifica se produto tem promoção de preço ativa (considerando janela de datas).
 * Retorna true quando promo_price é válido, menor que price e dentro do prazo.
 * @param array $p Produto (array com keys: price, promo_price, promo_start_at, promo_end_at)
 * @return bool
 */
if (!function_exists('badgePromo')) {
    function badgePromo($p): bool
    {
        if (!is_array($p)) {
            return false;
        }
        $price = isset($p['price']) ? (float)$p['price'] : 0;
        $promoRaw = $p['promo_price'] ?? null;

        if ($price <= 0 || $promoRaw === null || $promoRaw === '') {
            return false;
        }

        $now = time();
        if (!empty($p['promo_start_at']) && strtotime($p['promo_start_at']) > $now) {
            return false;
        }
        if (!empty($p['promo_end_at']) && strtotime($p['promo_end_at']) < $now) {
            return false;
        }

        if (is_array($promoRaw)) {
            $promoRaw = reset($promoRaw);
        }
        $promoStr = trim((string)$promoRaw);
        if ($promoStr === '') {
            return false;
        }
        $promoStr = str_replace(' ', '', $promoStr);
        if (strpos($promoStr, ',') !== false && strpos($promoStr, '.') !== false) {
            $promoStr = str_replace('.', '', $promoStr);
        }
        $promoStr = str_replace(',', '.', $promoStr);
        if (!is_numeric($promoStr)) {
            return false;
        }
        $promo = (float)$promoStr;

        return $promo > 0 && $promo < $price;
    }
}

/**
 * Normaliza cor hexadecimal
 * @param string $color Cor em formato hex
 * @return string Cor normalizada (#RRGGBB)
 */
if (!function_exists('normalize_color_hex')) {
    function normalize_color_hex(?string $color, string $default = '#000000'): string
    {
        if (empty($color)) {
            return $default;
        }
        
        $color = trim($color);
        
        // Remove # se existir
        $color = ltrim($color, '#');
        
        // Se tem 3 dígitos, expande para 6
        if (strlen($color) === 3) {
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }
        
        // Valida se é hexadecimal válido
        if (!preg_match('/^[0-9A-Fa-f]{6}$/', $color)) {
            return $default;
        }
        
        return '#' . strtoupper($color);
    }
}

// ============================================================================
// 📊 STATUS E ESTADOS
// ============================================================================

/**
 * Gera pill de status unificado
 * @param string $status Status do item
 * @param string|null $text Texto personalizado
 * @param bool $showDot Mostrar dot indicador
 * @return string HTML do status pill
 */
if (!function_exists('status_pill')) {
    function status_pill(string $status, ?string $text = null, bool $showDot = true): string
    {
        $statusMap = [
            // Evolution / Conexão
            'open' => 'connected',
            'connecting' => 'connecting',
            'disconnected' => 'disconnected',
            'close' => 'disconnected',
            
            // Pedidos - apenas pendente, concluído e cancelado
            'concluido' => 'connected',
            'concluded' => 'connected',
            'completed' => 'connected',
            'paid' => 'connected', // Pago agora é tratado como Concluído
            'confirmed' => 'connected',
            'preparing' => 'connected',
            'ready' => 'connected',
            'delivered' => 'connected',
            'cancelado' => 'disconnected',
            'cancelled' => 'disconnected',
            'canceled' => 'disconnected',
            'pendente' => 'pending',
            'pending' => 'pending',
            'erro' => 'error',
            'error' => 'error',
            'failed' => 'error'
        ];
        
        $statusClass = $statusMap[strtolower((string)$status)] ?? 'pending';
        $displayText = $text ?? ucfirst($status);
        $dot = $showDot ? '<span class="status-dot"></span>' : '';
        
        return '<span class="status-pill status-' . $statusClass . '">' . $dot . e($displayText) . '</span>';
    }
}

// ============================================================================
// 🔧 UTILITÁRIOS
// ============================================================================

/**
 * Gera src para upload de arquivo
 * @param string|null $value Caminho do arquivo
 * @param string $fallback Imagem padrão
 * @return string URL completa do arquivo
 */
if (!function_exists('upload_src')) {
    function upload_src(?string $value, string $fallback = 'assets/logo-placeholder.png'): string
    {
        if (!$value || trim($value) === '') {
            return base_url($fallback);
        }
        
        $value = trim($value);
        
        // Se já é uma URL completa, retorna como está
        if (preg_match('/^https?:\/\//', $value)) {
            return $value;
        }
        
        // Se começa com uploads/, adiciona base_url
        if (strpos($value, 'uploads/') === 0) {
            return base_url($value);
        }
        
        // Se não tem uploads/, adiciona o prefixo
        if (strpos($value, '/') !== 0) {
            $value = 'uploads/' . $value;
        }
        
        return base_url($value);
    }
}

/**
 * Trunca texto para exibição
 * @param string $text Texto original
 * @param int $limit Limite de caracteres
 * @param string $suffix Sufixo para texto truncado
 * @return string Texto truncado
 */
if (!function_exists('truncate_text')) {
    function truncate_text(string $text, int $limit = 100, string $suffix = '...'): string
    {
        if (mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }
        
        return mb_substr($text, 0, $limit, 'UTF-8') . $suffix;
    }
}

/**
 * Converte hex para rgba
 * @param string $hex Cor hexadecimal
 * @param float $alpha Valor alpha (0-1)
 * @param string $fallback Cor de fallback
 * @return string Valor rgba
 */
if (!function_exists('hex_to_rgba')) {
    function hex_to_rgba(string $hex, float $alpha = 1.0, string $fallback = '#000000'): string
    {
        $hex = normalize_color_hex($hex);
        
        if ($hex === '#000000' && $hex !== $fallback) {
            $hex = normalize_color_hex($fallback);
        }
        
        $hex = ltrim($hex, '#');
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return "rgba($r, $g, $b, $alpha)";
    }
}

// ============================================================================
// 🎨 TEMAS E CORES
// ============================================================================

/**
 * Obtém cor primária do tema admin
 * @param array|null $company Dados da empresa
 * @return string Cor hexadecimal
 */
if (!function_exists('admin_theme_primary_color')) {
    function admin_theme_primary_color(?array $company = null, string $default = '#4F46E5'): string
    {
        if ($company) {
            // Verificar diferentes campos possíveis para a cor principal
            $color = $company['primary_color'] ?? 
                    $company['menu_header_bg_color'] ?? 
                    $company['menu_logo_bg_color'] ?? 
                    $company['brand_color'] ?? 
                    null;
            
            if (!empty($color)) {
                return normalize_color_hex($color);
            }
        }
        
        return $default;
    }
}

/**
 * Gera gradiente do tema admin
 * @param array|null $company Dados da empresa
 * @return string CSS gradient
 */
if (!function_exists('admin_theme_gradient')) {
    function admin_theme_gradient(?array $company = null): string
    {
        $primary = admin_theme_primary_color($company);
        
        // Gera uma versão mais clara do primary para o gradiente
        $r = hexdec(substr(ltrim($primary, '#'), 0, 2));
        $g = hexdec(substr(ltrim($primary, '#'), 2, 2));
        $b = hexdec(substr(ltrim($primary, '#'), 4, 2));
        
        // Adiciona 30 em cada canal (máximo 255)
        $r2 = min(255, $r + 30);
        $g2 = min(255, $g + 30);
        $b2 = min(255, $b + 30);
        
        $secondary = sprintf('#%02X%02X%02X', $r2, $g2, $b2);
        
        return "linear-gradient(135deg, $primary 0%, $secondary 100%)";
    }
}

// ============================================================================
// 🔐 CSRF E SEGURANÇA
// ============================================================================

/**
 * Gera um token CSRF
 * @return string Token CSRF
 */
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \App\Middleware\CsrfProtection::getToken(false);
    }
}

/**
 * Gera campo hidden com token CSRF para formulários
 * @return string HTML do input hidden
 */
if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return \App\Middleware\CsrfProtection::field();
    }
}

/**
 * Gera campo CSRF se a função existir
 * @return string HTML do campo CSRF ou string vazia
 */
if (!function_exists('csrf_field_safe')) {
    function csrf_field_safe(): string
    {
        if (function_exists('csrf_field')) {
            return csrf_field();
        }
        
        if (function_exists('csrf_token')) {
            return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
        }
        
        return '';
    }
}

// ============================================================================
// 📱 RESPONSIVIDADE E DEVICE
// ============================================================================

/**
 * Detecta se é dispositivo móvel
 * @return bool True se for mobile
 */
if (!function_exists('is_mobile')) {
    function is_mobile(): bool
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        return preg_match('/Mobile|Android|iPhone|iPad|BlackBerry|Windows Phone/i', $userAgent);
    }
}

/**
 * Gera classes responsivas baseadas no contexto
 * @param string $mobile Classes para mobile
 * @param string $desktop Classes para desktop  
 * @return string Classes CSS combinadas
 */
if (!function_exists('responsive_classes')) {
    function responsive_classes(string $mobile, string $desktop): string
    {
        return "$mobile md:$desktop";
    }
}

// ============================================================================
// 📞 FORMATAÇÃO DE TELEFONE/WHATSAPP
// ============================================================================

/**
 * Formata número de telefone brasileiro para exibição
 * Aceita formatos: 5551920017687, 51920017687, 920017687, (51) 92001-7687
 * Retorna: (51) 92001-7687
 * 
 * @param string|null $phone Número de telefone
 * @return string Número formatado ou original se não reconhecido
 */
if (!function_exists('format_phone_br')) {
    function format_phone_br(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }
        
        // Remove tudo que não é dígito
        $digits = preg_replace('/\D/', '', $phone);
        
        // Remove código do país 55 se presente
        if (strlen($digits) >= 12 && substr($digits, 0, 2) === '55') {
            $digits = substr($digits, 2);
        }
        
        $len = strlen($digits);
        
        // Celular com DDD: 11 dígitos (ex: 51920017687)
        if ($len === 11) {
            $ddd = substr($digits, 0, 2);
            $part1 = substr($digits, 2, 5);
            $part2 = substr($digits, 7, 4);
            return "($ddd) $part1-$part2";
        }
        
        // Fixo com DDD: 10 dígitos (ex: 5133334444)
        if ($len === 10) {
            $ddd = substr($digits, 0, 2);
            $part1 = substr($digits, 2, 4);
            $part2 = substr($digits, 6, 4);
            return "($ddd) $part1-$part2";
        }
        
        // Celular sem DDD: 9 dígitos (ex: 920017687)
        if ($len === 9) {
            $part1 = substr($digits, 0, 5);
            $part2 = substr($digits, 5, 4);
            return "$part1-$part2";
        }
        
        // Fixo sem DDD: 8 dígitos (ex: 33334444)
        if ($len === 8) {
            $part1 = substr($digits, 0, 4);
            $part2 = substr($digits, 4, 4);
            return "$part1-$part2";
        }
        
        // Retorna original se não reconhecido
        return $phone;
    }
}

/**
 * Remove formatação de telefone, deixando apenas dígitos
 * @param string|null $phone Número de telefone formatado
 * @return string Apenas dígitos
 */
if (!function_exists('unformat_phone')) {
    function unformat_phone(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }
        return preg_replace('/\D/', '', $phone);
    }
}