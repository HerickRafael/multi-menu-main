<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * ImageController - Servir imagens otimizadas com cache profissional
 * 
 * Features:
 * - Cache HTTP agressivo (1 ano)
 * - ETags para validação
 * - Compressão automática
 * - Suporte a WebP
 * - Resize on-demand
 * - Cache em disco
 */
class ImageController
{
    private string $uploadPath;
    private string $cachePath;
    private int $cacheMaxAge = 31536000; // 1 ano
    private int $quality = 85; // Qualidade JPEG/WebP

    public function __construct()
    {
        $this->uploadPath = rtrim($_ENV['UPLOAD_PATH'] ?? 'public/uploads', '/');
        $this->cachePath = rtrim($_ENV['IMAGE_CACHE_PATH'] ?? 'storage/cache/images', '/');
        
        // Criar diretório de cache se não existir
        if (!is_dir($this->cachePath)) {
            @mkdir($this->cachePath, 0755, true);
        }
    }

    /**
     * Serve imagem otimizada
     * 
     * GET /img/{path}?w=300&h=300&q=85&format=webp
     */
    public function serve(array $params): void
    {
        $path = $params['path'] ?? '';
        
        // Sanitizar path (prevenir directory traversal)
        $path = str_replace(['..', '\\'], ['', '/'], $path);
        $path = ltrim($path, '/');

        if (str_ends_with(strtolower($path), '.svg')) {
            $this->send404();
            return;
        }

        $originalPath = $this->uploadPath . '/' . $path;
        
        // Verificar se arquivo existe
        if (!file_exists($originalPath) || !is_file($originalPath)) {
            $this->send404();
            return;
        }

        // Parâmetros de otimização
        $width = isset($_GET['w']) ? (int)$_GET['w'] : null;
        $height = isset($_GET['h']) ? (int)$_GET['h'] : null;
        $quality = isset($_GET['q']) ? min(100, max(1, (int)$_GET['q'])) : $this->quality;
        $format = $_GET['format'] ?? null;
        
        // Detectar suporte a WebP
        $supportsWebP = $this->supportsWebP();
        if ($format === 'webp' && !$supportsWebP) {
            $format = null; // Fallback para formato original
        }

        // Gerar chave de cache
        $cacheKey = $this->getCacheKey($path, $width, $height, $quality, $format);
        $cachedPath = $this->cachePath . '/' . $cacheKey;

        // Verificar cache em disco
        if (file_exists($cachedPath)) {
            $this->serveFromCache($cachedPath, $originalPath);
            return;
        }

        // Processar imagem
        $processedPath = $this->processImage(
            $originalPath,
            $cachedPath,
            $width,
            $height,
            $quality,
            $format
        );

        if ($processedPath) {
            $this->serveFromCache($processedPath, $originalPath);
        } else {
            // Fallback: servir original
            $this->serveOriginal($originalPath);
        }
    }

    /**
     * Processa e otimiza imagem
     */
    private function processImage(
        string $sourcePath,
        string $destPath,
        ?int $width,
        ?int $height,
        int $quality,
        ?string $format
    ): ?string {
        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) {
            return null;
        }

        [$origWidth, $origHeight, $type] = $imageInfo;

        // Criar imagem source
        $source = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_GIF => @imagecreatefromgif($sourcePath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($sourcePath),
            default => null
        };

        if (!$source) {
            return null;
        }

        // Calcular dimensões finais
        $newWidth = $width ?? $origWidth;
        $newHeight = $height ?? $origHeight;

        // Manter aspect ratio se apenas uma dimensão foi especificada
        if ($width && !$height) {
            $newHeight = (int)(($origHeight / $origWidth) * $width);
        } elseif ($height && !$width) {
            $newWidth = (int)(($origWidth / $origHeight) * $height);
        } elseif ($width && $height) {
            // Crop para caber exatamente
            $ratioOrig = $origWidth / $origHeight;
            $ratioNew = $width / $height;

            if ($ratioOrig > $ratioNew) {
                $newWidth = (int)($origHeight * $ratioNew);
                $newHeight = $origHeight;
            } else {
                $newHeight = (int)($origWidth / $ratioNew);
                $newWidth = $origWidth;
            }
        }

        // Criar imagem destino
        $dest = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preservar transparência para PNG/WebP
        if ($type === IMAGETYPE_PNG || $format === 'webp') {
            imagealphablending($dest, false);
            imagesavealpha($dest, true);
            $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
            imagefill($dest, 0, 0, $transparent);
        }

        // Resize com alta qualidade
        imagecopyresampled(
            $dest, $source,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $origWidth, $origHeight
        );

        // Criar diretório se necessário
        $destDir = dirname($destPath);
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        // Salvar em formato otimizado
        $success = match ($format) {
            'webp' => @imagewebp($dest, $destPath, $quality),
            'png' => @imagepng($dest, $destPath, (int)(9 - ($quality / 11))),
            'gif' => @imagegif($dest, $destPath),
            default => @imagejpeg($dest, $destPath, $quality)
        };

        imagedestroy($source);
        imagedestroy($dest);

        return $success ? $destPath : null;
    }

    /**
     * Serve imagem do cache com headers otimizados
     */
    private function serveFromCache(string $cachedPath, string $originalPath): void
    {
        $etag = $this->generateETag($cachedPath);
        $lastModified = filemtime($originalPath);

        // Verificar ETag
        $clientETag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if ($clientETag === $etag) {
            http_response_code(304);
            header('ETag: ' . $etag);
            exit;
        }

        // Verificar Last-Modified
        $clientLastModified = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
        if ($clientLastModified && strtotime($clientLastModified) >= $lastModified) {
            http_response_code(304);
            header('ETag: ' . $etag);
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
            exit;
        }

        // Headers de cache agressivo
        $this->sendCacheHeaders($etag, $lastModified);

        // Content-Type
        $mimeType = $this->getMimeType($cachedPath);
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($cachedPath));

        // Servir arquivo
        readfile($cachedPath);
        exit;
    }

    /**
     * Serve imagem original (fallback)
     */
    private function serveOriginal(string $path): void
    {
        $etag = $this->generateETag($path);
        $lastModified = filemtime($path);

        $this->sendCacheHeaders($etag, $lastModified);

        $mimeType = $this->getMimeType($path);
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($path));

        readfile($path);
        exit;
    }

    /**
     * Envia headers de cache HTTP
     */
    private function sendCacheHeaders(string $etag, int $lastModified): void
    {
        header('ETag: ' . $etag);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
        header('Cache-Control: public, max-age=' . $this->cacheMaxAge . ', immutable');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $this->cacheMaxAge) . ' GMT');
        header('Vary: Accept'); // Para WebP
    }

    /**
     * Gera ETag único para cache
     */
    private function generateETag(string $path): string
    {
        $hash = md5_file($path);
        return '"' . $hash . '"';
    }

    /**
     * Gera chave de cache
     */
    private function getCacheKey(
        string $path,
        ?int $width,
        ?int $height,
        int $quality,
        ?string $format
    ): string {
        $key = $path;
        if ($width) $key .= '_w' . $width;
        if ($height) $key .= '_h' . $height;
        if ($quality !== $this->quality) $key .= '_q' . $quality;
        if ($format) $key .= '.' . $format;
        
        return md5($key) . ($format ? '.' . $format : '.jpg');
    }

    /**
     * Detecta suporte a WebP
     */
    private function supportsWebP(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'image/webp');
    }

    /**
     * Obtém MIME type
     */
    private function getMimeType(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'webp' => 'image/webp',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            default => 'image/jpeg'
        };
    }

    /**
     * Envia 404
     */
    private function send404(): void
    {
        http_response_code(404);
        header('Content-Type: image/svg+xml');
        echo '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">
            <rect fill="#f3f4f6" width="400" height="300"/>
            <text x="200" y="150" font-family="Arial" font-size="16" fill="#9ca3af" text-anchor="middle">Imagem não encontrada</text>
        </svg>';
        exit;
    }

    /**
     * Limpa cache antigo (opcional - chamar via cron)
     */
    public function cleanCache(int $olderThanDays = 30): int
    {
        $count = 0;
        $maxAge = time() - ($olderThanDays * 86400);
        
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->cachePath),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getMTime() < $maxAge) {
                @unlink($file->getPathname());
                $count++;
            }
        }

        return $count;
    }
}
