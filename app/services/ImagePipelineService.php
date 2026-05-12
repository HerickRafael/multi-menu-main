<?php

declare(strict_types=1);

namespace App\Services;

/**
 * ImagePipelineService - Pipeline automático de processamento
 * 
 * Fluxo:
 * 1. Upload → 2. Validate → 3. Optimize → 4. Multi-format → 5. Thumbs → 6. LQIP → 7. Store
 */
class ImagePipelineService
{
    private ImageStorageService $storage;
    private array $stats = [];
    private const REMOTE_MAX_BYTES = 10485760; // 10MB
    private const REMOTE_CONNECT_TIMEOUT = 5;
    private const REMOTE_TIMEOUT = 10;

    public function __construct()
    {
        $this->storage = new ImageStorageService();
    }

    /**
     * Processa upload completo com pipeline
     */
    public function processUpload(array $file, string $category, ?string $customName = null): array
    {
        $startTime = microtime(true);

        // 1. Validar
        $this->validateUpload($file);

        // 2. Salvar temporário
        $tempPath = $this->saveTempFile($file);

        try {
            // 3. Otimizar original (remover metadata, comprimir)
            $optimizedPath = $this->optimizeOriginal($tempPath);

            // 4. Processar via storage (gera todos os formatos)
            $result = $this->storage->store($optimizedPath, $category, $customName);

            // 5. Estatísticas
            $result['stats'] = [
                'original_size' => $file['size'],
                'optimized_size' => filesize($optimizedPath),
                'reduction' => round((1 - (filesize($optimizedPath) / $file['size'])) * 100, 2),
                'processing_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms',
                'formats_generated' => $this->countGeneratedFormats($result['paths'])
            ];

            // 6. Limpar temp
            @unlink($tempPath);
            if ($optimizedPath !== $tempPath) {
                @unlink($optimizedPath);
            }

            return $result;

        } catch (\Exception $e) {
            @unlink($tempPath);
            throw $e;
        }
    }

    /**
     * Valida upload
     */
    private function validateUpload(array $file): void
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Erro no upload: ' . $file['error']);
        }

        // Verificar tipo MIME
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimes)) {
            throw new \RuntimeException('Tipo de arquivo não permitido: ' . $mimeType);
        }

        // Verificar tamanho (máx 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxSize) {
            throw new \RuntimeException('Arquivo muito grande. Máximo: 10MB');
        }

        // Verificar dimensões
        $imageInfo = @getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            throw new \RuntimeException('Arquivo não é uma imagem válida');
        }

        [$width, $height] = $imageInfo;
        if ($width < 100 || $height < 100) {
            throw new \RuntimeException('Imagem muito pequena. Mínimo: 100x100px');
        }

        if ($width > 5000 || $height > 5000) {
            throw new \RuntimeException('Imagem muito grande. Máximo: 5000x5000px');
        }
    }

    /**
     * Salva arquivo temporário
     */
    private function saveTempFile(array $file): string
    {
        $tempDir = sys_get_temp_dir() . '/image-pipeline';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = 'bin';
        }
        $tempPath = $tempDir . '/' . uniqid('img_', true) . '.' . $ext;

        $source = (string)($file['tmp_name'] ?? '');
        if ($source === '' || !is_file($source)) {
            throw new \RuntimeException('Arquivo temporário inválido');
        }

        if (is_uploaded_file($source)) {
            if (!move_uploaded_file($source, $tempPath)) {
                throw new \RuntimeException('Falha ao salvar arquivo temporário');
            }
        } else {
            $realSource = realpath($source);
            $tempRoot = realpath(sys_get_temp_dir());
            if ($realSource === false || $tempRoot === false || strpos($realSource, $tempRoot . DIRECTORY_SEPARATOR) !== 0) {
                throw new \RuntimeException('Arquivo temporário inválido');
            }
            if (!@rename($realSource, $tempPath)) {
                if (!@copy($realSource, $tempPath)) {
                    throw new \RuntimeException('Falha ao salvar arquivo temporário');
                }
                @unlink($realSource);
            }
        }

        return $tempPath;
    }

    /**
     * Otimiza imagem original (remove metadata, comprime)
     */
    private function optimizeOriginal(string $path): string
    {
        $imageInfo = @getimagesize($path);
        if (!$imageInfo) {
            return $path;
        }

        [$width, $height, $type] = $imageInfo;

        // Carregar imagem
        $img = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default => null
        };

        if (!$img) {
            return $path;
        }

        // Recriar para remover metadata EXIF
        $clean = imagecreatetruecolor($width, $height);

        // Preservar transparência para PNG/WebP
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($clean, false);
            imagesavealpha($clean, true);
            $transparent = imagecolorallocatealpha($clean, 0, 0, 0, 127);
            imagefill($clean, 0, 0, $transparent);
        }

        imagecopyresampled($clean, $img, 0, 0, 0, 0, $width, $height, $width, $height);

        // Salvar otimizado
        $optimizedPath = $path . '.optimized';
        
        $success = match ($type) {
            IMAGETYPE_PNG => imagepng($clean, $optimizedPath, 9),
            IMAGETYPE_GIF => imagegif($clean, $optimizedPath),
            default => imagejpeg($clean, $optimizedPath, 92)
        };

        imagedestroy($img);
        imagedestroy($clean);

        if ($success && filesize($optimizedPath) < filesize($path)) {
            // Usar versão otimizada se for menor
            unlink($path);
            rename($optimizedPath, $path);
        } elseif (file_exists($optimizedPath)) {
            unlink($optimizedPath);
        }

        return $path;
    }

    /**
     * Conta formatos gerados
     */
    private function countGeneratedFormats(array $paths): int
    {
        $count = 1; // original
        
        if (isset($paths['webp'])) {
            $count += count($paths['webp']);
        }
        
        if (isset($paths['avif'])) {
            $count += count($paths['avif']);
        }
        
        if (isset($paths['thumbs'])) {
            $count += count($paths['thumbs']);
        }

        return $count;
    }

    /**
     * Processa imagem via URL (útil para importação)
     */
    public function processFromUrl(string $url, string $category, ?string $customName = null): array
    {
        $tempPath = sys_get_temp_dir() . '/' . uniqid('img_url_', true) . '.bin';
        $this->downloadRemoteFile($url, $tempPath, self::REMOTE_MAX_BYTES);

        try {
            // Simular upload
            $file = [
                'name' => basename((string)(parse_url($url, PHP_URL_PATH) ?? 'remote-image')),
                'tmp_name' => $tempPath,
                'size' => filesize($tempPath),
                'error' => UPLOAD_ERR_OK
            ];

            return $this->processUpload($file, $category, $customName);

        } finally {
            @unlink($tempPath);
        }
    }

    /**
     * Batch processing (útil para migração)
     */
    public function processBatch(array $files, string $category, ?callable $progressCallback = null): array
    {
        $results = [];
        $total = count($files);
        $success = 0;
        $failed = 0;

        foreach ($files as $index => $file) {
            try {
                $result = $this->processUpload($file, $category);
                $results[] = [
                    'success' => true,
                    'file' => $file['name'],
                    'data' => $result
                ];
                $success++;

            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'file' => $file['name'],
                    'error' => $e->getMessage()
                ];
                $failed++;
            }

            if ($progressCallback) {
                $progressCallback($index + 1, $total, $success, $failed);
            }
        }

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'results' => $results
        ];
    }

    private function downloadRemoteFile(string $url, string $targetPath, int $maxBytes): void
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('cURL indisponível para download remoto');
        }

        $this->assertSafeRemoteUrl($url);

        $fp = fopen($targetPath, 'wb');
        if ($fp === false) {
            throw new \RuntimeException('Falha ao preparar download remoto');
        }

        $downloaded = 0;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_FAILONERROR => true,
            CURLOPT_CONNECTTIMEOUT => self::REMOTE_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => self::REMOTE_TIMEOUT,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_USERAGENT => 'MultiMenu-ImagePipeline/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$downloaded, $maxBytes, $fp) {
                $len = strlen($data);
                $downloaded += $len;
                if ($downloaded > $maxBytes) {
                    return 0;
                }
                return fwrite($fp, $data);
            },
        ]);

        $result = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($result === false || $httpCode < 200 || $httpCode >= 300) {
            @unlink($targetPath);
            throw new \RuntimeException('Falha ao baixar imagem da URL: ' . $err);
        }

        if (!is_file($targetPath) || filesize($targetPath) <= 0) {
            @unlink($targetPath);
            throw new \RuntimeException('Arquivo remoto vazio');
        }
    }

    private function assertSafeRemoteUrl(string $url): void
    {
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            throw new \RuntimeException('URL inválida');
        }

        $scheme = strtolower((string)$parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \RuntimeException('Protocolo não permitido');
        }

        if (!empty($parts['user']) || !empty($parts['pass'])) {
            throw new \RuntimeException('Credenciais embutidas não são permitidas');
        }

        $host = (string)$parts['host'];
        $ips = $this->resolveHostIps($host);
        if (!$ips) {
            throw new \RuntimeException('Host inválido');
        }

        foreach ($ips as $ip) {
            if (!$this->isPublicIp($ip)) {
                throw new \RuntimeException('Host não permitido');
            }
        }
    }

    private function resolveHostIps(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = gethostbynamel($host);
        if ($ips === false) {
            $ips = [];
        }

        if (function_exists('dns_get_record')) {
            $records = dns_get_record($host, DNS_AAAA);
            if (is_array($records)) {
                foreach ($records as $record) {
                    if (!empty($record['ipv6'])) {
                        $ips[] = $record['ipv6'];
                    }
                }
            }
        }

        return array_values(array_unique($ips));
    }

    private function isPublicIp(string $ip): bool
    {
        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * Re-processa imagem existente (útil após atualizar pipeline)
     */
    public function reprocess(string $originalPath, string $category, ?string $customName = null): array
    {
        if (!file_exists($originalPath)) {
            throw new \RuntimeException('Arquivo original não encontrado');
        }

        return $this->storage->store($originalPath, $category, $customName);
    }
}
