<?php
/**
 * MIGRATION SCRIPT - Convert existing images to new format structure
 * 
 * Este script:
 * 1. Scanneia /public/uploads/ procurando imagens antigas
 * 2. Processa cada imagem via ImagePipelineService
 * 3. Gera AVIF, WebP, JPEG, thumbnails
 * 4. Mantém imagens antigas como backup
 * 5. Log detalhado de progresso e erros
 * 
 * Usage:
 *   php scripts/migrate-images.php
 *   php scripts/migrate-images.php --dry-run
 *   php scripts/migrate-images.php --category=produtos
 *   php scripts/migrate-images.php --limit=50
 * 
 * @author Enterprise Image System
 * @version 1.0.0
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/services/ImagePipelineService.php';
require_once __DIR__ . '/../app/services/ImageStorageService.php';

use App\Services\ImageStorageService;
use App\Services\ImagePipelineService;

// ============================================
// CLI OPTIONS
// ============================================

$options = getopt('', ['dry-run', 'category:', 'limit:', 'verbose']);

$dryRun = isset($options['dry-run']);
$categoryFilter = $options['category'] ?? null;
$limit = isset($options['limit']) ? (int)$options['limit'] : PHP_INT_MAX;
$verbose = isset($options['verbose']);

// ============================================
// CONFIGURATION
// ============================================

define('UPLOADS_DIR', __DIR__ . '/../public/uploads');
define('LOG_FILE', __DIR__ . '/../storage/logs/migration-' . date('Y-m-d_H-i-s') . '.log');
define('BATCH_SIZE', 10); // Processar 10 imagens por vez para não sobrecarregar

// Categorias detectadas automaticamente por subpastas
$categories = [
    'root' => UPLOADS_DIR,  // Imagens na raiz (atual)
    'produtos' => UPLOADS_DIR . '/produtos',
    'banners' => UPLOADS_DIR . '/banners',
    'categorias' => UPLOADS_DIR . '/categorias',
    'lojas' => UPLOADS_DIR . '/lojas',
];

// ============================================
// INITIALIZATION
// ============================================

$storage = new ImageStorageService();
$pipeline = new ImagePipelineService();

$stats = [
    'total' => 0,
    'processed' => 0,
    'skipped' => 0,
    'errors' => 0,
    'size_before' => 0,
    'size_after' => 0,
    'start_time' => microtime(true),
];

// ============================================
// LOGGING
// ============================================

function logMessage($message, $level = 'INFO') {
    global $verbose;
    
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Sempre gravar no arquivo
    file_put_contents(LOG_FILE, $logLine, FILE_APPEND);
    
    // Console apenas se verbose ou erro
    if ($verbose || $level === 'ERROR') {
        echo $logLine;
    }
}

function logProgress($current, $total, $filename) {
    $percent = round(($current / $total) * 100, 1);
    $bar = str_repeat('█', (int)($percent / 2));
    $space = str_repeat('░', 50 - (int)($percent / 2));
    
    echo "\r[{$bar}{$space}] {$percent}% - {$filename}                    ";
    flush();
}

// ============================================
// SCAN IMAGES
// ============================================

function scanImages($dir, $category) {
    global $categoryFilter, $stats;
    
    if ($categoryFilter && $category !== $categoryFilter) {
        return [];
    }
    
    if (!is_dir($dir)) {
        logMessage("Directory not found: $dir", 'WARN');
        return [];
    }
    
    $images = [];
    $extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    // Para 'root', escanear apenas arquivos diretos (não recursivo)
    if ($category === 'root') {
        $files = glob($dir . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        
        foreach ($files as $filepath) {
            $file = new SplFileInfo($filepath);
            $ext = strtolower($file->getExtension());
            
            if (in_array($ext, $extensions)) {
                // Skip se já foi processado (tem _v1 no nome)
                if (preg_match('/_v\d+/', $file->getFilename())) {
                    $stats['skipped']++;
                    continue;
                }
                
                // Detectar categoria pelo prefixo do nome
                $detectedCategory = 'produtos'; // default
                if (preg_match('/^(banner|logo|ingredient|pm_brand|p)_/', $file->getFilename(), $matches)) {
                    switch ($matches[1]) {
                        case 'banner':
                            $detectedCategory = 'banners';
                            break;
                        case 'logo':
                            $detectedCategory = 'lojas';
                            break;
                        case 'ingredient':
                        case 'pm_brand':
                        case 'p':
                            $detectedCategory = 'produtos';
                            break;
                    }
                }
                
                $images[] = [
                    'path' => $file->getPathname(),
                    'filename' => $file->getFilename(),
                    'category' => $detectedCategory,
                    'size' => $file->getSize(),
                ];
                
                $stats['total']++;
                $stats['size_before'] += $file->getSize();
            }
        }
    } else {
        // Para outras categorias, escanear recursivamente
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                
                if (in_array($ext, $extensions)) {
                    // Skip se já foi processado (tem _v1 no nome)
                    if (preg_match('/_v\d+/', $file->getFilename())) {
                        $stats['skipped']++;
                        continue;
                    }
                    
                    $images[] = [
                        'path' => $file->getPathname(),
                        'filename' => $file->getFilename(),
                        'category' => $category,
                        'size' => $file->getSize(),
                    ];
                    
                    $stats['total']++;
                    $stats['size_before'] += $file->getSize();
                }
            }
        }
    }
    
    return $images;
}

// ============================================
// PROCESS IMAGE
// ============================================

function processImage($image) {
    global $pipeline, $stats, $dryRun;
    
    if ($dryRun) {
        logMessage("DRY-RUN: Would process {$image['filename']}", 'INFO');
        return true;
    }
    
    try {
        $result = $pipeline->reprocess(
            $image['path'],
            $image['category'],
            pathinfo($image['filename'], PATHINFO_FILENAME)
        );
        
        // reprocess retorna apenas o resultado do storage, não tem 'success'
        if ($result && isset($result['hash'])) {
            $stats['processed']++;
            
            // Calcular tamanho após (soma de todos os formatos gerados)
            $optimizedSize = 0;
            if (isset($result['paths']['webp'])) {
                foreach ($result['paths']['webp'] as $path) {
                    if (file_exists($path)) {
                        $optimizedSize += filesize($path);
                    }
                }
            }
            
            $stats['size_after'] += $optimizedSize;
            $reduction = $optimizedSize > 0 ? round((1 - ($optimizedSize / $image['size'])) * 100, 1) : 0;
            
            logMessage(
                "✓ {$image['filename']} -> {$result['hash']}_v{$result['version']} - " .
                "Reduced: {$reduction}%",
                'SUCCESS'
            );
            
            return true;
        }
        
    } catch (Exception $e) {
        $stats['errors']++;
        logMessage("✗ {$image['filename']} - Error: {$e->getMessage()}", 'ERROR');
        return false;
    }
    
    return false;
}

// ============================================
// MAIN EXECUTION
// ============================================

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║           IMAGE MIGRATION - ENTERPRISE SYSTEM                ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

if ($dryRun) {
    echo "⚠️  DRY-RUN MODE - No changes will be made\n\n";
}

logMessage("Migration started", 'INFO');
logMessage("Configuration: dry-run=$dryRun, category=$categoryFilter, limit=$limit", 'INFO');

// Scan todas as categorias
$allImages = [];
foreach ($categories as $category => $dir) {
    echo "Scanning category: $category...\n";
    $images = scanImages($dir, $category);
    $allImages = array_merge($allImages, $images);
    echo "  Found: " . count($images) . " images\n";
}

echo "\nTotal images found: {$stats['total']}\n";
echo "Already migrated (skipped): {$stats['skipped']}\n";

if ($stats['total'] === 0) {
    echo "\n✓ No images to migrate!\n\n";
    exit(0);
}

// Aplicar limite
if (count($allImages) > $limit) {
    echo "Limiting to first $limit images\n";
    $allImages = array_slice($allImages, 0, $limit);
}

echo "\nProcessing " . count($allImages) . " images...\n\n";

// Processar em lotes
$processed = 0;
$total = count($allImages);

foreach ($allImages as $index => $image) {
    $processed++;
    
    // Progress bar
    if (!$verbose) {
        logProgress($processed, $total, $image['filename']);
    }
    
    // Processar imagem
    processImage($image);
    
    // Sleep para não sobrecarregar (100ms)
    usleep(100000);
}

echo "\n\n";

// ============================================
// FINAL STATISTICS
// ============================================

$stats['end_time'] = microtime(true);
$stats['duration'] = round($stats['end_time'] - $stats['start_time'], 2);

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    MIGRATION COMPLETED                       ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "📊 STATISTICS:\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "Total found:       {$stats['total']}\n";
echo "Processed:         {$stats['processed']}\n";
echo "Skipped:           {$stats['skipped']}\n";
echo "Errors:            {$stats['errors']}\n";
echo "Duration:          {$stats['duration']}s\n";
echo "\n";

if (!$dryRun && $stats['processed'] > 0) {
    $sizeBefore = round($stats['size_before'] / 1024 / 1024, 2);
    $sizeAfter = round($stats['size_after'] / 1024 / 1024, 2);
    $reduction = round((1 - ($stats['size_after'] / $stats['size_before'])) * 100, 1);
    
    echo "💾 STORAGE:\n";
    echo "─────────────────────────────────────────────────────────────\n";
    echo "Before:            {$sizeBefore} MB\n";
    echo "After:             {$sizeAfter} MB\n";
    echo "Reduction:         {$reduction}%\n";
    echo "\n";
}

echo "📝 Full log saved to: " . LOG_FILE . "\n";
echo "\n";

if ($stats['errors'] > 0) {
    echo "⚠️  Some images failed to process. Check the log for details.\n\n";
    exit(1);
}

if ($dryRun) {
    echo "✓ Dry-run completed successfully!\n";
    echo "  Run without --dry-run to perform actual migration.\n\n";
} else {
    echo "✓ Migration completed successfully!\n\n";
}

logMessage("Migration completed successfully", 'INFO');
exit(0);
