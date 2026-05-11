<?php

declare(strict_types=1);

namespace App\Services;

/**
 * ImageStorageService - Gerenciamento de múltiplos formatos e tamanhos
 * 
 * Estrutura:
 * /original/{category}/{hash}_{version}.{ext}
 * /webp/{category}/{size}/{hash}_{version}.webp
 * /avif/{category}/{size}/{hash}_{version}.avif
 * /thumbs/{category}/{hash}_{version}_{size}.jpg
 */
class ImageStorageService
{
    private string $basePath;
    private array $sizes = [
        'thumb' => 300,
        'small' => 600,
        'medium' => 1200,
        'large' => 1920
    ];

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? ($_ENV['UPLOAD_PATH'] ?? 'public/uploads');
    }

    /**
     * Salva imagem com versionamento automático
     */
    public function store(string $sourcePath, string $category, ?string $customName = null): array
    {
        // Gerar hash único
        $hash = $this->generateHash($sourcePath);
        $version = 1;
        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        
        // Nome customizado ou hash
        $baseName = $customName ? $this->sanitizeName($customName) : $hash;
        
        // Verificar versão existente
        $originalDir = $this->basePath . '/original/' . $category;
        if (!is_dir($originalDir)) {
            mkdir($originalDir, 0755, true);
        }

        // Incrementar versão se já existe
        while (file_exists("{$originalDir}/{$baseName}_v{$version}.{$ext}")) {
            $version++;
        }

        $fileName = "{$baseName}_v{$version}";
        $paths = [];

        // 1. Salvar original
        $originalPath = "{$originalDir}/{$fileName}.{$ext}";
        copy($sourcePath, $originalPath);
        $paths['original'] = $this->relativePath($originalPath);

        // 2. Gerar WebP em todos os tamanhos
        foreach ($this->sizes as $sizeName => $width) {
            $webpDir = $this->basePath . '/webp/' . $category . '/' . $sizeName;
            if (!is_dir($webpDir)) {
                mkdir($webpDir, 0755, true);
            }
            $webpPath = "{$webpDir}/{$fileName}.webp";
            $this->convertToWebP($sourcePath, $webpPath, $width);
            $paths['webp'][$sizeName] = $this->relativePath($webpPath);
        }

        // 3. Gerar AVIF em todos os tamanhos
        foreach ($this->sizes as $sizeName => $width) {
            $avifDir = $this->basePath . '/avif/' . $category . '/' . $sizeName;
            if (!is_dir($avifDir)) {
                mkdir($avifDir, 0755, true);
            }
            $avifPath = "{$avifDir}/{$fileName}.avif";
            $this->convertToAVIF($sourcePath, $avifPath, $width);
            $paths['avif'][$sizeName] = $this->relativePath($avifPath);
        }

        // 4. Gerar thumbs JPEG
        $thumbDir = $this->basePath . '/thumbs/' . $category;
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }
        foreach (['thumb' => 300, 'small' => 600] as $sizeName => $width) {
            $thumbPath = "{$thumbDir}/{$fileName}_{$sizeName}.jpg";
            $this->createThumbnail($sourcePath, $thumbPath, $width);
            $paths['thumbs'][$sizeName] = $this->relativePath($thumbPath);
        }

        // 5. Gerar placeholder LQIP (Low Quality Image Placeholder)
        $lqipPath = "{$thumbDir}/{$fileName}_lqip.jpg";
        $this->createLQIP($sourcePath, $lqipPath);
        $paths['lqip'] = $this->generateBase64LQIP($lqipPath);

        return [
            'hash' => $hash,
            'version' => $version,
            'name' => $fileName,
            'category' => $category,
            'paths' => $paths,
            'metadata' => $this->extractMetadata($sourcePath)
        ];
    }

    /**
     * Converte para WebP otimizado
     */
    private function convertToWebP(string $source, string $dest, int $maxWidth): bool
    {
        $imageInfo = @getimagesize($source);
        if (!$imageInfo) return false;

        [$width, $height, $type] = $imageInfo;

        // Criar source
        $img = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_GIF => @imagecreatefromgif($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
            default => null
        };

        if (!$img) return false;

        // Resize proporcional
        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int)(($height / $width) * $maxWidth);
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Salvar como WebP (qualidade 90)
        $success = imagewebp($resized, $dest, 90);
        
        imagedestroy($img);
        imagedestroy($resized);

        return $success;
    }

    /**
     * Converte para AVIF otimizado
     */
    private function convertToAVIF(string $source, string $dest, int $maxWidth): bool
    {
        // AVIF requer libavif/imagick
        // Fallback: usar imageavif se disponível (PHP 8.1+)
        if (!function_exists('imageavif')) {
            // Copiar WebP como fallback
            $webpPath = str_replace('/avif/', '/webp/', $dest);
            $webpPath = str_replace('.avif', '.webp', $webpPath);
            if (file_exists($webpPath)) {
                copy($webpPath, str_replace('.avif', '.webp', $dest));
            }
            return false;
        }

        $imageInfo = @getimagesize($source);
        if (!$imageInfo) return false;

        [$width, $height, $type] = $imageInfo;

        $img = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_GIF => @imagecreatefromgif($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
            default => null
        };

        if (!$img) return false;

        // Resize
        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int)(($height / $width) * $maxWidth);
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Salvar como AVIF (qualidade 85)
        $success = @imageavif($resized, $dest, 85);
        
        imagedestroy($img);
        imagedestroy($resized);

        return $success;
    }

    /**
     * Cria thumbnail JPEG
     */
    private function createThumbnail(string $source, string $dest, int $maxWidth): bool
    {
        $imageInfo = @getimagesize($source);
        if (!$imageInfo) return false;

        [$width, $height, $type] = $imageInfo;

        $img = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_GIF => @imagecreatefromgif($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
            default => null
        };

        if (!$img) return false;

        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int)(($height / $width) * $maxWidth);
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        $success = imagejpeg($resized, $dest, 85);
        
        imagedestroy($img);
        imagedestroy($resized);

        return $success;
    }

    /**
     * Cria LQIP (Low Quality Image Placeholder) - 20x15 blur
     */
    private function createLQIP(string $source, string $dest): bool
    {
        $imageInfo = @getimagesize($source);
        if (!$imageInfo) return false;

        [$width, $height, $type] = $imageInfo;

        $img = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_GIF => @imagecreatefromgif($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
            default => null
        };

        if (!$img) return false;

        // Mini versão 20x15 para base64
        $tiny = imagecreatetruecolor(20, 15);
        imagecopyresampled($tiny, $img, 0, 0, 0, 0, 20, 15, $width, $height);
        
        // Aplicar blur para efeito suave
        for ($i = 0; $i < 3; $i++) {
            imagefilter($tiny, IMG_FILTER_GAUSSIAN_BLUR);
        }
        
        $success = imagejpeg($tiny, $dest, 50);
        
        imagedestroy($img);
        imagedestroy($tiny);

        return $success;
    }

    /**
     * Gera base64 do LQIP
     */
    private function generateBase64LQIP(string $path): string
    {
        if (!file_exists($path)) {
            return '';
        }
        
        $data = file_get_contents($path);
        return 'data:image/jpeg;base64,' . base64_encode($data);
    }

    /**
     * Extrai metadata da imagem
     */
    private function extractMetadata(string $path): array
    {
        $info = @getimagesize($path);
        if (!$info) {
            return [];
        }

        [$width, $height, $type] = $info;

        return [
            'width' => $width,
            'height' => $height,
            'type' => image_type_to_mime_type($type),
            'size' => filesize($path),
            'created_at' => time()
        ];
    }

    /**
     * Gera hash único da imagem
     */
    private function generateHash(string $path): string
    {
        return substr(md5_file($path), 0, 12);
    }

    /**
     * Sanitiza nome de arquivo
     */
    private function sanitizeName(string $name): string
    {
        $name = preg_replace('/[^a-z0-9\-_]/i', '-', $name);
        return strtolower(trim($name, '-'));
    }

    /**
     * Retorna caminho relativo
     */
    private function relativePath(string $fullPath): string
    {
        return str_replace($this->basePath . '/', '', $fullPath);
    }

    /**
     * Obtém URL da imagem (com CDN se configurado)
     */
    public function getUrl(string $relativePath, ?string $cdnDomain = null): string
    {
        $cdnDomain = $cdnDomain ?? ($_ENV['CDN_DOMAIN'] ?? null);
        
        if ($cdnDomain) {
            return rtrim($cdnDomain, '/') . '/' . ltrim($relativePath, '/');
        }

        return base_url($relativePath);
    }

    /**
     * Lista versões de uma imagem
     */
    public function listVersions(string $category, string $baseName): array
    {
        $dir = $this->basePath . '/original/' . $category;
        $versions = [];

        if (!is_dir($dir)) {
            return $versions;
        }

        $files = glob($dir . '/' . $baseName . '_v*.{jpg,jpeg,png,webp}', GLOB_BRACE);
        
        foreach ($files as $file) {
            if (preg_match('/_v(\d+)\./', basename($file), $matches)) {
                $versions[] = (int)$matches[1];
            }
        }

        rsort($versions);
        return $versions;
    }

    /**
     * Remove versões antigas (manter apenas N últimas)
     */
    public function cleanOldVersions(string $category, string $baseName, int $keepLast = 3): int
    {
        $versions = $this->listVersions($category, $baseName);
        $toDelete = array_slice($versions, $keepLast);
        $deleted = 0;

        foreach ($toDelete as $version) {
            $pattern = "{$baseName}_v{$version}";
            
            // Deletar de todos os diretórios
            $dirs = [
                '/original/' . $category,
                '/thumbs/' . $category
            ];

            foreach (['webp', 'avif'] as $format) {
                foreach ($this->sizes as $sizeName => $width) {
                    $dirs[] = "/{$format}/{$category}/{$sizeName}";
                }
            }

            foreach ($dirs as $dir) {
                $fullDir = $this->basePath . $dir;
                if (is_dir($fullDir)) {
                    $files = glob($fullDir . '/' . $pattern . '*');
                    foreach ($files as $file) {
                        if (unlink($file)) {
                            $deleted++;
                        }
                    }
                }
            }
        }

        return $deleted;
    }
}
