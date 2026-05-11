<?php

declare(strict_types=1);

/**
 * Helper de otimização de imagens
 * 
 * Gera URLs para imagens otimizadas com cache, resize e WebP
 */

if (!function_exists('optimizedImage')) {
    /**
     * Gera URL de imagem otimizada
     * 
     * @param string $path Caminho da imagem (relativo a uploads/)
     * @param array $options Opções de otimização
     *   - width: int (largura desejada)
     *   - height: int (altura desejada)
     *   - quality: int (qualidade 1-100, padrão 85)
     *   - format: string (webp, jpg, png - auto detecta WebP)
     *   - sizes: string (preset: thumb|small|medium|large)
     * 
     * @return string URL da imagem otimizada
     */
    function optimizedImage(string $path, array $options = []): string
    {
        // Remover prefixo public/uploads se já estiver no path
        $path = str_replace(['public/uploads/', 'uploads/'], '', $path);
        $path = ltrim($path, '/');

        // Presets de tamanhos
        $sizePresets = [
            'thumb' => ['width' => 120, 'height' => 120, 'quality' => 80],
            'small' => ['width' => 300, 'height' => 300, 'quality' => 85],
            'medium' => ['width' => 600, 'height' => 600, 'quality' => 85],
            'large' => ['width' => 1200, 'height' => 1200, 'quality' => 90],
        ];

        // Aplicar preset se especificado
        if (isset($options['sizes']) && isset($sizePresets[$options['sizes']])) {
            $options = array_merge($sizePresets[$options['sizes']], $options);
        }

        // Construir query string
        $queryParams = [];
        
        if (isset($options['width'])) {
            $queryParams['w'] = (int)$options['width'];
        }
        
        if (isset($options['height'])) {
            $queryParams['h'] = (int)$options['height'];
        }
        
        if (isset($options['quality'])) {
            $queryParams['q'] = (int)$options['quality'];
        }

        // Auto WebP se suportado
        if (!isset($options['format']) || $options['format'] === 'auto') {
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            if (str_contains($accept, 'image/webp')) {
                $queryParams['format'] = 'webp';
            }
        } elseif (isset($options['format'])) {
            $queryParams['format'] = $options['format'];
        }

        $query = http_build_query($queryParams);
        $url = base_url('img/' . $path);
        
        return $query ? $url . '?' . $query : $url;
    }
}

if (!function_exists('imgAttrs')) {
    /**
     * Gera atributos HTML completos para imagem otimizada
     * 
     * @param string $path Caminho da imagem
     * @param string $alt Texto alternativo
     * @param array $options Opções (width, height, quality, class, sizes, etc)
     * 
     * @return string Atributos HTML prontos para usar
     */
    function imgAttrs(string $path, string $alt = '', array $options = []): string
    {
        $class = $options['class'] ?? 'lazy-load';
        $loading = $options['loading'] ?? 'lazy';
        
        // URL principal
        $src = optimizedImage($path, $options);
        
        // Gerar srcset para responsividade
        $srcset = [];
        if (isset($options['srcset']) && $options['srcset']) {
            $baseWidth = $options['width'] ?? 300;
            
            // 1x, 1.5x, 2x
            $srcset[] = optimizedImage($path, array_merge($options, ['width' => $baseWidth])) . ' 1x';
            $srcset[] = optimizedImage($path, array_merge($options, ['width' => (int)($baseWidth * 1.5)])) . ' 1.5x';
            $srcset[] = optimizedImage($path, array_merge($options, ['width' => $baseWidth * 2])) . ' 2x';
        }

        $attrs = [
            'src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"',
            'alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '"',
            'class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"',
            'loading="' . $loading . '"'
        ];

        if (!empty($srcset)) {
            $attrs[] = 'srcset="' . implode(', ', $srcset) . '"';
        }

        if (isset($options['width'])) {
            $attrs[] = 'width="' . (int)$options['width'] . '"';
        }

        if (isset($options['height'])) {
            $attrs[] = 'height="' . (int)$options['height'] . '"';
        }

        return implode(' ', $attrs);
    }
}

if (!function_exists('responsiveImage')) {
    /**
     * Gera tag <picture> completa com WebP + fallback
     * 
     * @param string $path Caminho da imagem
     * @param string $alt Texto alternativo
     * @param array $options Opções de otimização
     * 
     * @return string Tag <picture> HTML completa
     */
    function responsiveImage(string $path, string $alt = '', array $options = []): string
    {
        $class = $options['class'] ?? 'lazy-load';
        $loading = $options['loading'] ?? 'lazy';
        
        // WebP source
        $webpUrl = optimizedImage($path, array_merge($options, ['format' => 'webp']));
        
        // Fallback JPEG/PNG
        $fallbackUrl = optimizedImage($path, $options);
        
        $width = isset($options['width']) ? ' width="' . (int)$options['width'] . '"' : '';
        $height = isset($options['height']) ? ' height="' . (int)$options['height'] . '"' : '';

        return <<<HTML
<picture>
  <source srcset="{$webpUrl}" type="image/webp">
  <img src="{$fallbackUrl}" 
       alt="{$alt}" 
       class="{$class}" 
       loading="{$loading}"{$width}{$height}>
</picture>
HTML;
    }
}

if (!function_exists('imagePreload')) {
    /**
     * Gera tag de preload para imagens críticas (above-the-fold)
     * 
     * @param string $path Caminho da imagem
     * @param array $options Opções de otimização
     * 
     * @return string Tag <link rel="preload"> HTML
     */
    function imagePreload(string $path, array $options = []): string
    {
        $url = optimizedImage($path, $options);
        $as = 'image';
        
        // Detectar tipo
        $format = $options['format'] ?? 'jpeg';
        $type = match ($format) {
            'webp' => 'image/webp',
            'png' => 'image/png',
            'gif' => 'image/gif',
            default => 'image/jpeg'
        };

        return '<link rel="preload" href="' . $url . '" as="' . $as . '" type="' . $type . '">';
    }
}

if (!function_exists('imagePlaceholder')) {
    /**
     * Gera placeholder SVG inline (para usar com lazy loading)
     * 
     * @param int $width Largura
     * @param int $height Altura
     * @param string $color Cor de fundo (hex)
     * 
     * @return string Data URI SVG
     */
    function imagePlaceholder(int $width = 400, int $height = 300, string $color = '#f3f4f6'): string
    {
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
  <rect fill="{$color}" width="{$width}" height="{$height}"/>
  <path fill="#d1d5db" d="M{$cx},{$cy}m-20,0a20,20 0 1,0 40,0a20,20 0 1,0 -40,0z" opacity="0.3"/>
</svg>
SVG;
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
