#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Script de otimização em lote de imagens
 * 
 * Uso:
 *   php scripts/optimize-images.php [--dry-run] [--quality=85] [--max-width=2000]
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Parse argumentos
$options = getopt('', ['dry-run', 'quality::', 'max-width::']);
$dryRun = isset($options['dry-run']);
$quality = isset($options['quality']) ? (int)$options['quality'] : 85;
$maxWidth = isset($options['max-width']) ? (int)$options['max-width'] : 2000;

$uploadsPath = __DIR__ . '/../public/uploads';

echo "═══════════════════════════════════════════════════════════════\n";
echo "  OTIMIZAÇÃO EM LOTE DE IMAGENS\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Diretório: {$uploadsPath}\n";
echo "Qualidade: {$quality}%\n";
echo "Largura máx: {$maxWidth}px\n";
echo "Modo: " . ($dryRun ? "DRY RUN (simulação)" : "PRODUÇÃO") . "\n";
echo "───────────────────────────────────────────────────────────────\n\n";

$stats = [
    'total' => 0,
    'optimized' => 0,
    'skipped' => 0,
    'errors' => 0,
    'bytes_saved' => 0
];

// Iterar por todas as imagens
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($uploadsPath),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $ext = strtolower($file->getExtension());
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        continue;
    }

    $stats['total']++;
    $path = $file->getPathname();
    $originalSize = $file->getSize();

    echo "[{$stats['total']}] " . basename($path) . " ";
    
    // Verificar se já está otimizada
    $imageInfo = @getimagesize($path);
    if (!$imageInfo) {
        echo "❌ ERRO: Não é uma imagem válida\n";
        $stats['errors']++;
        continue;
    }

    [$width, $height] = $imageInfo;

    // Pular se já está no tamanho ideal
    if ($width <= $maxWidth && $originalSize < 500000) { // < 500KB
        echo "✓ Já otimizada ({$width}x{$height}, " . formatBytes($originalSize) . ")\n";
        $stats['skipped']++;
        continue;
    }

    if ($dryRun) {
        echo "→ Seria otimizada ({$width}x{$height} → ";
        if ($width > $maxWidth) {
            $newHeight = (int)(($height / $width) * $maxWidth);
            echo "{$maxWidth}x{$newHeight}";
        } else {
            echo "{$width}x{$height}";
        }
        echo ")\n";
        $stats['optimized']++;
        continue;
    }

    // Otimizar
    try {
        $result = optimizeImage($path, $quality, $maxWidth);
        
        if ($result) {
            $newSize = filesize($path);
            $saved = $originalSize - $newSize;
            $percent = round(($saved / $originalSize) * 100, 1);
            
            echo "✓ Otimizada! ";
            echo formatBytes($originalSize) . " → " . formatBytes($newSize);
            echo " (economizou " . formatBytes($saved) . " / {$percent}%)\n";
            
            $stats['optimized']++;
            $stats['bytes_saved'] += $saved;
        } else {
            echo "⚠ Não foi possível otimizar\n";
            $stats['skipped']++;
        }
    } catch (Exception $e) {
        echo "❌ ERRO: " . $e->getMessage() . "\n";
        $stats['errors']++;
    }
}

// Resumo final
echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  RESUMO\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Total de imagens: {$stats['total']}\n";
echo "Otimizadas: {$stats['optimized']}\n";
echo "Puladas: {$stats['skipped']}\n";
echo "Erros: {$stats['errors']}\n";

if ($stats['bytes_saved'] > 0) {
    echo "Espaço economizado: " . formatBytes($stats['bytes_saved']) . "\n";
    $percentSaved = round(($stats['bytes_saved'] / ($stats['bytes_saved'] + $originalSize)) * 100, 1);
    echo "Redução média: {$percentSaved}%\n";
}

echo "═══════════════════════════════════════════════════════════════\n";

if ($dryRun) {
    echo "\n💡 Execute sem --dry-run para aplicar as otimizações\n";
}

// ============================================
// Funções auxiliares
// ============================================

function optimizeImage(string $path, int $quality, int $maxWidth): bool
{
    $imageInfo = @getimagesize($path);
    if (!$imageInfo) {
        return false;
    }

    [$width, $height, $type] = $imageInfo;

    // Criar backup
    $backupPath = $path . '.bak';
    copy($path, $backupPath);

    // Criar imagem source
    $source = match ($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
        IMAGETYPE_PNG => @imagecreatefrompng($path),
        IMAGETYPE_GIF => @imagecreatefromgif($path),
        IMAGETYPE_WEBP => @imagecreatefromwebp($path),
        default => null
    };

    if (!$source) {
        unlink($backupPath);
        return false;
    }

    // Calcular novas dimensões
    if ($width > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = (int)(($height / $width) * $maxWidth);
    } else {
        $newWidth = $width;
        $newHeight = $height;
    }

    // Criar imagem destino
    $dest = imagecreatetruecolor($newWidth, $newHeight);

    // Preservar transparência para PNG
    if ($type === IMAGETYPE_PNG) {
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefill($dest, 0, 0, $transparent);
    }

    // Resize
    imagecopyresampled(
        $dest, $source,
        0, 0, 0, 0,
        $newWidth, $newHeight,
        $width, $height
    );

    // Salvar otimizada
    $success = match ($type) {
        IMAGETYPE_PNG => @imagepng($dest, $path, (int)(9 - ($quality / 11))),
        IMAGETYPE_GIF => @imagegif($dest, $path),
        default => @imagejpeg($dest, $path, $quality)
    };

    imagedestroy($source);
    imagedestroy($dest);

    if ($success) {
        unlink($backupPath);
        return true;
    } else {
        // Restaurar backup em caso de erro
        rename($backupPath, $path);
        return false;
    }
}

function formatBytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}
