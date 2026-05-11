<?php
/**
 * Gerador de Ícones PWA Admin
 * Gera ícones PNG a partir de SVG usando GD
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Detectar se estamos no container ou local
$possiblePaths = [
    '/var/www/html/public/assets/icons/admin',
    __DIR__ . '/../public/assets/icons/admin'
];

$baseDir = null;
foreach ($possiblePaths as $path) {
    $parentDir = dirname($path);
    if (is_dir($parentDir) || is_dir(dirname($parentDir))) {
        $baseDir = $path;
        break;
    }
}

if (!$baseDir) {
    $baseDir = '/var/www/html/public/assets/icons/admin';
}

// Criar diretório se não existir
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0755, true);
}

// Função para criar ícone com gradiente
function createGradientIcon($size, $outputPath) {
    // Criar imagem
    $image = imagecreatetruecolor($size, $size);
    
    // Habilitar alpha blending
    imagesavealpha($image, true);
    imagealphablending($image, false);
    
    // Cores do gradiente
    $color1 = ['r' => 91, 'g' => 33, 'b' => 182];   // #5B21B6
    $color2 = ['r' => 124, 'g' => 58, 'b' => 237];  // #7C3AED
    
    // Criar gradiente diagonal
    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            // Fator de gradiente baseado na posição diagonal
            $factor = ($x + $y) / ($size * 2);
            
            $r = (int)($color1['r'] + ($color2['r'] - $color1['r']) * $factor);
            $g = (int)($color1['g'] + ($color2['g'] - $color1['g']) * $factor);
            $b = (int)($color1['b'] + ($color2['b'] - $color1['b']) * $factor);
            
            $color = imagecolorallocate($image, $r, $g, $b);
            imagesetpixel($image, $x, $y, $color);
        }
    }
    
    // Adicionar cantos arredondados (simples para tamanhos grandes)
    $radius = (int)($size * 0.18);
    $white = imagecolorallocatealpha($image, 255, 255, 255, 0);
    
    // Desenhar ícone central (simplified dashboard icon)
    $iconSize = (int)($size * 0.5);
    $iconX = (int)(($size - $iconSize) / 2);
    $iconY = (int)(($size - $iconSize) / 2);
    
    // Clipboard retângulo
    $rectWidth = (int)($iconSize * 0.7);
    $rectHeight = (int)($iconSize * 0.85);
    $rectX = (int)(($size - $rectWidth) / 2);
    $rectY = (int)(($size - $rectHeight) / 2) + (int)($iconSize * 0.1);
    
    // Desenhar retângulo (clipboard)
    imagefilledrectangle($image, $rectX, $rectY, $rectX + $rectWidth, $rectY + $rectHeight, $white);
    
    // Área interna (cor do gradiente)
    $innerPadding = max(2, (int)($size * 0.02));
    $innerColor = imagecolorallocate($image, 91, 33, 182);
    imagefilledrectangle(
        $image, 
        $rectX + $innerPadding, 
        $rectY + $innerPadding + (int)($rectHeight * 0.15), 
        $rectX + $rectWidth - $innerPadding, 
        $rectY + $rectHeight - $innerPadding, 
        $innerColor
    );
    
    // Linhas brancas (representando dados)
    $lineY = $rectY + (int)($rectHeight * 0.35);
    $lineSpacing = (int)($rectHeight * 0.15);
    
    for ($i = 0; $i < 3; $i++) {
        $lineWidth = ($i === 1) ? 0.5 : 0.7;
        imagefilledrectangle(
            $image,
            $rectX + (int)($rectWidth * 0.15),
            $lineY + ($i * $lineSpacing),
            $rectX + (int)($rectWidth * $lineWidth),
            $lineY + ($i * $lineSpacing) + max(2, (int)($size * 0.015)),
            $white
        );
    }
    
    // Clip no topo
    $clipWidth = (int)($rectWidth * 0.4);
    $clipHeight = (int)($rectHeight * 0.12);
    $clipX = (int)(($size - $clipWidth) / 2);
    $clipY = $rectY - (int)($clipHeight * 0.3);
    imagefilledrectangle($image, $clipX, $clipY, $clipX + $clipWidth, $clipY + $clipHeight, $white);
    
    // Salvar PNG
    imagepng($image, $outputPath, 9);
    imagedestroy($image);
    
    return true;
}

// Função para criar splash screen
function createSplashScreen($width, $height, $outputPath) {
    $image = imagecreatetruecolor($width, $height);
    
    // Cores do gradiente
    $color1 = ['r' => 91, 'g' => 33, 'b' => 182];
    $color2 = ['r' => 124, 'g' => 58, 'b' => 237];
    
    // Gradiente diagonal
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $factor = ($x + $y) / ($width + $height);
            
            $r = (int)($color1['r'] + ($color2['r'] - $color1['r']) * $factor);
            $g = (int)($color1['g'] + ($color2['g'] - $color1['g']) * $factor);
            $b = (int)($color1['b'] + ($color2['b'] - $color1['b']) * $factor);
            
            $color = imagecolorallocate($image, $r, $g, $b);
            imagesetpixel($image, $x, $y, $color);
        }
    }
    
    // Ícone central
    $iconSize = min($width, $height) / 4;
    $iconImage = imagecreatetruecolor((int)$iconSize, (int)$iconSize);
    
    // Criar ícone pequeno
    $white = imagecolorallocate($iconImage, 255, 255, 255);
    imagefill($iconImage, 0, 0, $white);
    
    // Copiar ícone para o centro
    $destX = (int)(($width - $iconSize) / 2);
    $destY = (int)(($height - $iconSize) / 2);
    
    imagecopyresampled(
        $image, $iconImage,
        $destX, $destY,
        0, 0,
        (int)$iconSize, (int)$iconSize,
        (int)$iconSize, (int)$iconSize
    );
    
    imagedestroy($iconImage);
    
    imagepng($image, $outputPath, 9);
    imagedestroy($image);
    
    return true;
}

echo "Gerando ícones PWA Admin...\n\n";

// Tamanhos de ícones
$sizes = [16, 32, 72, 96, 120, 128, 144, 152, 180, 192, 256, 384, 512];

foreach ($sizes as $size) {
    $filename = "icon-{$size}x{$size}.png";
    $path = "{$baseDir}/{$filename}";
    
    echo "Gerando: {$filename}... ";
    
    if (createGradientIcon($size, $path)) {
        echo "OK\n";
    } else {
        echo "ERRO\n";
    }
}

// Favicons
echo "\nGerando favicons...\n";
copy("{$baseDir}/icon-32x32.png", "{$baseDir}/favicon-32x32.png");
copy("{$baseDir}/icon-16x16.png", "{$baseDir}/favicon-16x16.png");
copy("{$baseDir}/icon-180x180.png", "{$baseDir}/apple-touch-icon.png");
copy("{$baseDir}/icon-72x72.png", "{$baseDir}/badge-72x72.png");

// Ícones de shortcuts
echo "Gerando ícones de shortcuts...\n";
copy("{$baseDir}/icon-96x96.png", "{$baseDir}/orders-icon.png");
copy("{$baseDir}/icon-96x96.png", "{$baseDir}/kds-icon.png");
copy("{$baseDir}/icon-96x96.png", "{$baseDir}/products-icon.png");
copy("{$baseDir}/icon-96x96.png", "{$baseDir}/financial-icon.png");

// Splash screens
echo "\nGerando splash screens...\n";
$splashSizes = [
    [640, 1136],
    [750, 1334],
    [1242, 2208],
    [1125, 2436],
    [1170, 2532],
    [1284, 2778]
];

foreach ($splashSizes as [$width, $height]) {
    $filename = "splash-{$width}x{$height}.png";
    $path = "{$baseDir}/{$filename}";
    
    echo "Gerando: {$filename}... ";
    
    if (createSplashScreen($width, $height, $path)) {
        echo "OK\n";
    } else {
        echo "ERRO\n";
    }
}

// Browserconfig
$browserconfig = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<browserconfig>
  <msapplication>
    <tile>
      <square70x70logo src="/assets/icons/admin/icon-72x72.png"/>
      <square150x150logo src="/assets/icons/admin/icon-152x152.png"/>
      <square310x310logo src="/assets/icons/admin/icon-384x384.png"/>
      <TileColor>#5B21B6</TileColor>
    </tile>
  </msapplication>
</browserconfig>
XML;

file_put_contents("{$baseDir}/browserconfig.xml", $browserconfig);
echo "\nBrowserconfig criado.\n";

echo "\n✓ Todos os ícones foram gerados em: {$baseDir}\n";
echo "\nArquivos:\n";
$files = glob("{$baseDir}/*");
foreach ($files as $file) {
    echo "  - " . basename($file) . " (" . round(filesize($file) / 1024, 1) . " KB)\n";
}
