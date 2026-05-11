<?php
/**
 * Helper para Lazy Loading de Imagens
 * Centraliza a lógica de lazy loading para todo o sistema
 */

/**
 * Gera atributos HTML para lazy loading de imagens
 * 
 * @param string $src URL da imagem
 * @param string $alt Texto alternativo
 * @param array $options Opções adicionais
 *   - 'class': Classes CSS adicionais
 *   - 'eager': Se true, não usa lazy loading (carrega imediatamente)
 *   - 'fallback': URL de fallback caso a imagem não carregue
 *   - 'sizes': Tamanho da imagem (thumb, card, banner, hero)
 *   - 'attributes': Array de atributos HTML adicionais
 * 
 * @return string Atributos HTML prontos para uso
 * 
 * Exemplo de uso:
 * <img <?= lazyImageAttrs($imageSrc, 'Produto', ['class' => 'rounded-lg', 'sizes' => 'card']) ?>>
 */
function lazyImageAttrs($src, $alt = '', $options = []) {
    $eager = $options['eager'] ?? false;
    $fallback = $options['fallback'] ?? null;
    $sizes = $options['sizes'] ?? '';
    $additionalClass = $options['class'] ?? '';
    $attributes = $options['attributes'] ?? [];
    
    $attrs = [];
    
    // Classes CSS
    $classes = [];
    if (!$eager) {
        $classes[] = 'lazy-load';
        if ($sizes) {
            $classes[] = 'lazy-' . $sizes;
        }
    }
    if ($additionalClass) {
        $classes[] = $additionalClass;
    }
    
    if (!empty($classes)) {
        $attrs[] = 'class="' . htmlspecialchars(implode(' ', $classes), ENT_QUOTES, 'UTF-8') . '"';
    }
    
    // Atributo src/data-src
    if ($eager) {
        $attrs[] = 'src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"';
    } else {
        // Usar a imagem real diretamente + loading="lazy" nativo do browser
        $attrs[] = 'src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"';
        $attrs[] = 'data-src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"';
        $attrs[] = 'loading="lazy"';
    }
    
    // Alt text
    $attrs[] = 'alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '"';
    
    // Fallback
    if ($fallback && !$eager) {
        $attrs[] = 'data-fallback="' . htmlspecialchars($fallback, ENT_QUOTES, 'UTF-8') . '"';
    }
    
    // Atributos adicionais
    foreach ($attributes as $key => $value) {
        if ($value === true) {
            $attrs[] = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
        } else {
            $attrs[] = htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }
    }
    
    return implode(' ', $attrs);
}

/**
 * Gera uma tag <img> completa com lazy loading
 * 
 * @param string $src URL da imagem
 * @param string $alt Texto alternativo
 * @param array $options Opções (mesmas de lazyImageAttrs)
 * 
 * @return string Tag <img> completa
 * 
 * Exemplo de uso:
 * <?= lazyImage($imageSrc, 'Produto', ['class' => 'rounded-lg object-cover', 'sizes' => 'card']) ?>
 */
function lazyImage($src, $alt = '', $options = []) {
    return '<img ' . lazyImageAttrs($src, $alt, $options) . '>';
}

/**
 * Verifica se o lazy loading deve ser aplicado
 * Útil para condicionar o uso em diferentes contextos
 * 
 * @param array $context Contexto da página/componente
 * @return bool
 */
function shouldUseLazyLoading($context = []) {
    // Sempre usar lazy loading exceto em casos específicos
    $eager = $context['eager'] ?? false;
    $isAboveFold = $context['above_fold'] ?? false;
    $isCritical = $context['critical'] ?? false;
    
    // Não usar lazy loading para:
    // - Imagens marcadas como eager
    // - Imagens above the fold (visíveis sem scroll)
    // - Imagens críticas para LCP (Largest Contentful Paint)
    return !($eager || $isAboveFold || $isCritical);
}

/**
 * Gera atributos para container de lazy loading com skeleton
 * 
 * @param array $options Opções
 *   - 'class': Classes CSS adicionais
 *   - 'style': Estilos inline
 * 
 * @return string Atributos HTML
 */
function lazyContainerAttrs($options = []) {
    $additionalClass = $options['class'] ?? '';
    $style = $options['style'] ?? '';
    
    $classes = ['lazy-container'];
    if ($additionalClass) {
        $classes[] = $additionalClass;
    }
    
    $attrs = [];
    $attrs[] = 'class="' . htmlspecialchars(implode(' ', $classes), ENT_QUOTES, 'UTF-8') . '"';
    
    if ($style) {
        $attrs[] = 'style="' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '"';
    }
    
    return implode(' ', $attrs);
}

// Nota: função e() já definida em app/core/CommonHelpers.php
