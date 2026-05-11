<?php

declare(strict_types=1);

/**
 * Helpers para entrega de imagens otimizadas enterprise-grade
 * 
 * AVIF > WebP > JPEG com srcset responsivo automático
 */

if (!function_exists('responsiveImg')) {
    /**
     * Gera tag <picture> completa com AVIF, WebP e JPEG
     * 
     * @param array $imagePaths Paths retornados por ImageStorageService
     * @param string $alt Texto alternativo
     * @param array $options Opções (class, loading, sizes, etc)
     * @return string HTML completo
     */
    function responsiveImg(array $imagePaths, string $alt = '', array $options = []): string
    {
        $class = $options['class'] ?? '';
        $loading = $options['loading'] ?? 'lazy';
        $sizes = $options['sizes'] ?? '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw';
        
        $cdnDomain = $_ENV['CDN_DOMAIN'] ?? '';
        
        // Função helper para URL
        $url = function($path) use ($cdnDomain) {
            if ($cdnDomain) {
                return rtrim($cdnDomain, '/') . '/' . ltrim($path, '/');
            }
            return base_url($path);
        };

        // LQIP (placeholder blur)
        $lqip = $imagePaths['lqip'] ?? '';
        $lqipStyle = $lqip ? ' style="background-image:url(' . $lqip . ');background-size:cover;"' : '';

        // Construir srcset para cada formato
        $avifSrcset = [];
        $webpSrcset = [];
        $jpegSrcset = [];

        // AVIF
        if (isset($imagePaths['avif'])) {
            foreach ($imagePaths['avif'] as $size => $path) {
                $width = match($size) {
                    'thumb' => '300w',
                    'small' => '600w',
                    'medium' => '1200w',
                    'large' => '1920w',
                    default => '600w'
                };
                $avifSrcset[] = $url($path) . ' ' . $width;
            }
        }

        // WebP
        if (isset($imagePaths['webp'])) {
            foreach ($imagePaths['webp'] as $size => $path) {
                $width = match($size) {
                    'thumb' => '300w',
                    'small' => '600w',
                    'medium' => '1200w',
                    'large' => '1920w',
                    default => '600w'
                };
                $webpSrcset[] = $url($path) . ' ' . $width;
            }
        }

        // JPEG (fallback)
        if (isset($imagePaths['thumbs'])) {
            foreach ($imagePaths['thumbs'] as $size => $path) {
                $width = match($size) {
                    'thumb' => '300w',
                    'small' => '600w',
                    default => '300w'
                };
                $jpegSrcset[] = $url($path) . ' ' . $width;
            }
        }

        // Fallback src (menor thumb)
        $fallbackSrc = $url($imagePaths['thumbs']['thumb'] ?? $imagePaths['original'] ?? '');

        // Width/Height para layout shift
        $width = $options['width'] ?? '';
        $height = $options['height'] ?? '';
        $dimensions = '';
        if ($width && $height) {
            $dimensions = " width=\"{$width}\" height=\"{$height}\"";
        }

        $html = '<picture' . $lqipStyle . '>';
        
        // AVIF (melhor compressão)
        if (!empty($avifSrcset)) {
            $html .= '<source type="image/avif" srcset="' . implode(', ', $avifSrcset) . '" sizes="' . $sizes . '">';
        }

        // WebP (boa compressão, amplo suporte)
        if (!empty($webpSrcset)) {
            $html .= '<source type="image/webp" srcset="' . implode(', ', $webpSrcset) . '" sizes="' . $sizes . '">';
        }

        // JPEG (fallback universal)
        if (!empty($jpegSrcset)) {
            $html .= '<source type="image/jpeg" srcset="' . implode(', ', $jpegSrcset) . '" sizes="' . $sizes . '">';
        }

        // Tag <img> final
        $html .= '<img src="' . $fallbackSrc . '" alt="' . htmlspecialchars($alt, ENT_QUOTES) . '" class="' . $class . '" loading="' . $loading . '"' . $dimensions . '>';
        $html .= '</picture>';

        return $html;
    }
}

if (!function_exists('preloadImg')) {
    /**
     * Gera preload para imagens críticas (above-the-fold)
     * 
     * @param array $imagePaths Paths da imagem
     * @param string $size Tamanho a precarregar (thumb, small, medium)
     * @return string HTML <link rel="preload">
     */
    function preloadImg(array $imagePaths, string $size = 'small'): string
    {
        $cdnDomain = $_ENV['CDN_DOMAIN'] ?? '';
        
        $url = function($path) use ($cdnDomain) {
            if ($cdnDomain) {
                return rtrim($cdnDomain, '/') . '/' . ltrim($path, '/');
            }
            return base_url($path);
        };

        $links = [];

        // AVIF (priority)
        if (isset($imagePaths['avif'][$size])) {
            $links[] = '<link rel="preload" as="image" type="image/avif" href="' . $url($imagePaths['avif'][$size]) . '">';
        }

        // WebP
        if (isset($imagePaths['webp'][$size])) {
            $links[] = '<link rel="preload" as="image" type="image/webp" href="' . $url($imagePaths['webp'][$size]) . '">';
        }

        return implode("\n", $links);
    }
}

if (!function_exists('fastImg')) {
    /**
     * Versão simplificada para performance máxima
     * Retorna apenas WebP + JPEG (menos tags HTML)
     * 
     * @param array $imagePaths Paths da imagem
     * @param string $alt Texto alternativo
     * @param string $size Tamanho (thumb, small, medium)
     * @return string HTML
     */
    function fastImg(array $imagePaths, string $alt = '', string $size = 'small', array $options = []): string
    {
        $class = $options['class'] ?? '';
        $loading = $options['loading'] ?? 'lazy';
        
        $cdnDomain = $_ENV['CDN_DOMAIN'] ?? '';
        
        $url = function($path) use ($cdnDomain) {
            if ($cdnDomain) {
                return rtrim($cdnDomain, '/') . '/' . ltrim($path, '/');
            }
            return base_url($path);
        };

        $webpUrl = $url($imagePaths['webp'][$size] ?? '');
        $jpegUrl = $url($imagePaths['thumbs'][$size] ?? $imagePaths['original'] ?? '');
        $lqip = $imagePaths['lqip'] ?? '';

        $lqipStyle = $lqip ? ' style="background-image:url(' . $lqip . ')"' : '';

        return <<<HTML
<picture{$lqipStyle}>
  <source srcset="{$webpUrl}" type="image/webp">
  <img src="{$jpegUrl}" alt="{$alt}" class="{$class}" loading="{$loading}">
</picture>
HTML;
    }
}

if (!function_exists('imageManifest')) {
    /**
     * Gera JSON manifest para prefetch inteligente (Service Worker)
     * 
     * @param array $images Array de imagePaths
     * @return string JSON
     */
    function imageManifest(array $images): string
    {
        $cdnDomain = $_ENV['CDN_DOMAIN'] ?? '';
        
        $manifest = [];
        
        foreach ($images as $key => $imagePaths) {
            $urls = [];
            
            // WebP preferencial
            if (isset($imagePaths['webp'])) {
                foreach ($imagePaths['webp'] as $size => $path) {
                    $fullUrl = $cdnDomain ? rtrim($cdnDomain, '/') . '/' . ltrim($path, '/') : base_url($path);
                    $urls[$size] = $fullUrl;
                }
            }
            
            $manifest[$key] = [
                'urls' => $urls,
                'lqip' => $imagePaths['lqip'] ?? null
            ];
        }

        return json_encode($manifest, JSON_UNESCAPED_SLASHES);
    }
}

if (!function_exists('imgCard')) {
    /**
     * Shortcut para cards de produto (otimizado para listagem)
     * 
     * @param array $imagePaths Paths da imagem
     * @param string $alt Nome do produto
     * @param string $size Tamanho (thumb padrão para cards)
     * @return string HTML
     */
    function imgCard(array $imagePaths, string $alt = '', string $size = 'thumb'): string
    {
        return fastImg($imagePaths, $alt, $size, [
            'class' => 'w-full h-full object-cover lazy-load',
            'loading' => 'lazy'
        ]);
    }
}

if (!function_exists('imgHero')) {
    /**
     * Shortcut para imagens hero (banners, destaques)
     * 
     * @param array $imagePaths Paths da imagem
     * @param string $alt Texto alternativo
     * @return string HTML
     */
    function imgHero(array $imagePaths, string $alt = ''): string
    {
        return responsiveImg($imagePaths, $alt, [
            'class' => 'w-full h-full object-cover',
            'loading' => 'eager', // Hero sempre eager
            'sizes' => '100vw'
        ]);
    }
}

if (!function_exists('cdnUrl')) {
    /**
     * Retorna URL com CDN
     * 
     * @param string $path Caminho relativo
     * @return string URL completa
     */
    function cdnUrl(string $path): string
    {
        $cdnDomain = $_ENV['CDN_DOMAIN'] ?? '';
        
        if ($cdnDomain) {
            return rtrim($cdnDomain, '/') . '/' . ltrim($path, '/');
        }
        
        return base_url($path);
    }
}
