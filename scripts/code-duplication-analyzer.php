<?php
/**
 * Analisador de Duplicação de Código - Versão Otimizada
 * 
 * Analisa arquivos PHP, JS e CSS para identificar:
 * - Códigos duplicados ou muito semelhantes
 * - Funções/classes que podem ser reutilizadas
 * - Trechos redundantes ou desnecessários
 */

error_reporting(E_ERROR | E_PARSE);
ini_set('memory_limit', '256M');
set_time_limit(120);

$basePath = dirname(__DIR__);
$excludeDirs = ['vendor', 'node_modules', 'storage', '.git', 'cache'];

// Coletar todos os arquivos
$files = ['php' => [], 'js' => [], 'css' => []];
$stats = [
    'total_files' => 0,
    'total_lines' => 0,
    'by_language' => [
        'php' => ['files' => 0, 'lines' => 0, 'duplications' => []],
        'js' => ['files' => 0, 'lines' => 0, 'duplications' => []],
        'css' => ['files' => 0, 'lines' => 0, 'duplications' => []]
    ]
];

echo "🔍 Analisando código...\n\n";

// Coletar arquivos
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file->isFile()) continue;
    
    $path = $file->getPathname();
    $skip = false;
    foreach ($excludeDirs as $exclude) {
        if (strpos($path, "/{$exclude}/") !== false) {
            $skip = true;
            break;
        }
    }
    if ($skip) continue;
    
    $ext = strtolower($file->getExtension());
    if (in_array($ext, ['php', 'js', 'css'])) {
        $relativePath = str_replace($basePath . '/', '', $path);
        $content = file_get_contents($path);
        $lineCount = substr_count($content, "\n") + 1;
        
        $files[$ext][] = [
            'path' => $relativePath,
            'content' => $content,
            'lines' => $lineCount
        ];
        
        $stats['total_files']++;
        $stats['total_lines'] += $lineCount;
        $stats['by_language'][$ext]['files']++;
        $stats['by_language'][$ext]['lines'] += $lineCount;
    }
}

echo "📁 Arquivos: {$stats['total_files']} | Linhas: {$stats['total_lines']}\n\n";

// Análise de funções duplicadas
$functions = ['php' => [], 'js' => []];
$classes = [];
$duplications = [];
$suggestions = [];

echo "🔎 Analisando funções e classes...\n";

foreach ($files['php'] as $file) {
    // Funções PHP
    preg_match_all('/function\s+(\w+)\s*\(/m', $file['content'], $matches, PREG_OFFSET_CAPTURE);
    foreach ($matches[1] as $match) {
        $funcName = $match[0];
        $line = substr_count(substr($file['content'], 0, $match[1]), "\n") + 1;
        if (!isset($functions['php'][$funcName])) {
            $functions['php'][$funcName] = [];
        }
        $functions['php'][$funcName][] = ['file' => $file['path'], 'line' => $line];
    }
    
    // Classes PHP
    preg_match_all('/class\s+(\w+)/m', $file['content'], $classMatches, PREG_OFFSET_CAPTURE);
    foreach ($classMatches[1] as $match) {
        $className = $match[0];
        $line = substr_count(substr($file['content'], 0, $match[1]), "\n") + 1;
        $classes[$className] = ['file' => $file['path'], 'line' => $line];
    }
}

foreach ($files['js'] as $file) {
    // Funções JS
    preg_match_all('/function\s+(\w+)\s*\(/m', $file['content'], $matches, PREG_OFFSET_CAPTURE);
    foreach ($matches[1] as $match) {
        $funcName = $match[0];
        $line = substr_count(substr($file['content'], 0, $match[1]), "\n") + 1;
        if (!isset($functions['js'][$funcName])) {
            $functions['js'][$funcName] = [];
        }
        $functions['js'][$funcName][] = ['file' => $file['path'], 'line' => $line];
    }
    
    // Arrow functions com nome
    preg_match_all('/(?:const|let|var)\s+(\w+)\s*=\s*(?:\([^)]*\)|[^=])\s*=>/m', $file['content'], $arrowMatches, PREG_OFFSET_CAPTURE);
    foreach ($arrowMatches[1] as $match) {
        $funcName = $match[0];
        $line = substr_count(substr($file['content'], 0, $match[1]), "\n") + 1;
        if (!isset($functions['js'][$funcName])) {
            $functions['js'][$funcName] = [];
        }
        $functions['js'][$funcName][] = ['file' => $file['path'], 'line' => $line, 'type' => 'arrow'];
    }
}

// Detectar funções com mesmo nome
$duplicateFunctions = [];
foreach (['php', 'js'] as $lang) {
    foreach ($functions[$lang] as $name => $locs) {
        if (count($locs) > 1) {
            $duplicateFunctions[] = [
                'name' => $name,
                'language' => $lang,
                'count' => count($locs),
                'locations' => $locs
            ];
            $suggestions[] = [
                'type' => 'duplicate_function',
                'severity' => 'high',
                'language' => $lang,
                'title' => "Função '{$name}' definida em " . count($locs) . " locais",
                'description' => 'Considere consolidar em um único arquivo utilitário.',
                'locations' => $locs,
                'suggestion' => $lang === 'php' 
                    ? 'Mover para app/helpers/ ou app/core/CommonHelpers.php'
                    : 'Mover para public/assets/js/admin-common.js'
            ];
        }
    }
}

echo "   ✓ Funções duplicadas: " . count($duplicateFunctions) . "\n";

// Análise de padrões comuns usando hashing
echo "\n🔎 Detectando blocos de código similares...\n";

function normalizeCode($code) {
    $code = preg_replace('/\s+/', ' ', $code);
    $code = preg_replace('/\$[a-zA-Z_][a-zA-Z0-9_]*/', '$VAR', $code);
    $code = preg_replace('/(["\']).*?\1/', "'STR'", $code);
    return strtolower(trim($code));
}

function extractBlocks($content, $minLines = 5) {
    $lines = explode("\n", $content);
    $blocks = [];
    $lineCount = count($lines);
    
    for ($i = 0; $i < $lineCount - $minLines; $i += 3) {
        $blockLines = array_slice($lines, $i, 10);
        $block = implode("\n", $blockLines);
        
        // Filtrar blocos vazios ou triviais
        $clean = trim(preg_replace('/\s+/', '', $block));
        if (strlen($clean) > 100) {
            $hash = md5(normalizeCode($block));
            $blocks[] = [
                'hash' => $hash,
                'start' => $i + 1,
                'end' => min($i + 10, $lineCount),
                'code' => implode("\n", array_slice($blockLines, 0, 5))
            ];
        }
    }
    
    return $blocks;
}

// Detectar blocos duplicados usando hashing
$blockHashes = [];

foreach (['php', 'js', 'css'] as $lang) {
    foreach ($files[$lang] as $file) {
        $blocks = extractBlocks($file['content']);
        foreach ($blocks as $block) {
            $hash = $block['hash'];
            if (!isset($blockHashes[$hash])) {
                $blockHashes[$hash] = [];
            }
            $blockHashes[$hash][] = [
                'file' => $file['path'],
                'start' => $block['start'],
                'end' => $block['end'],
                'code' => $block['code'],
                'language' => $lang
            ];
        }
    }
}

// Filtrar apenas blocos que aparecem mais de uma vez
$duplicatedBlocks = [];
foreach ($blockHashes as $hash => $locations) {
    if (count($locations) > 1) {
        // Verificar se não são do mesmo arquivo nas mesmas linhas
        $uniqueFiles = [];
        foreach ($locations as $loc) {
            $key = $loc['file'] . ':' . $loc['start'];
            if (!isset($uniqueFiles[$key])) {
                $uniqueFiles[$key] = $loc;
            }
        }
        
        if (count($uniqueFiles) > 1) {
            $firstLoc = reset($uniqueFiles);
            $duplicatedBlocks[] = [
                'count' => count($uniqueFiles),
                'language' => $firstLoc['language'],
                'locations' => array_values($uniqueFiles),
                'code_sample' => $firstLoc['code']
            ];
            
            // Adicionar às duplicações por linguagem
            $lang = $firstLoc['language'];
            $stats['by_language'][$lang]['duplications'][] = [
                'count' => count($uniqueFiles),
                'locations' => array_values($uniqueFiles),
                'code_sample' => $firstLoc['code']
            ];
        }
    }
}

echo "   ✓ Blocos duplicados: " . count($duplicatedBlocks) . "\n";

// Análise de padrões específicos
echo "\n🔎 Analisando padrões reutilizáveis...\n";

// Padrões HTML repetidos em views
$htmlPatterns = [];
foreach ($files['php'] as $file) {
    if (strpos($file['path'], '/views/') !== false) {
        // Detectar cards, botões, formulários repetidos
        preg_match_all('/<div[^>]*class="([^"]*(?:card|btn|form|input|table)[^"]*)"[^>]*>/i', $file['content'], $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[1] as $match) {
            $classes = $match[0];
            $key = normalizeCode($classes);
            if (!isset($htmlPatterns[$key])) {
                $htmlPatterns[$key] = [];
            }
            $line = substr_count(substr($file['content'], 0, $match[1]), "\n") + 1;
            $htmlPatterns[$key][] = ['file' => $file['path'], 'line' => $line, 'classes' => $classes];
        }
    }
}

$repeatedPatterns = array_filter($htmlPatterns, fn($p) => count($p) >= 3);
echo "   ✓ Padrões HTML repetidos: " . count($repeatedPatterns) . "\n";

foreach ($repeatedPatterns as $key => $locs) {
    $suggestions[] = [
        'type' => 'repeated_html_pattern',
        'severity' => 'medium',
        'title' => 'Padrão HTML repetido ' . count($locs) . ' vezes',
        'description' => 'Considere criar um componente PHP reutilizável.',
        'locations' => array_slice($locs, 0, 5),
        'suggestion' => 'Criar componente em app/views/admin/components/'
    ];
}

// Análise de estilos inline
$inlineStyles = [];
foreach ($files['php'] as $file) {
    preg_match_all('/style="([^"]+)"/i', $file['content'], $matches, PREG_OFFSET_CAPTURE);
    foreach ($matches[1] as $match) {
        if (strlen($match[0]) > 50) {
            $line = substr_count(substr($file['content'], 0, $match[1]), "\n") + 1;
            $inlineStyles[] = [
                'file' => $file['path'],
                'line' => $line,
                'style' => substr($match[0], 0, 80) . '...'
            ];
        }
    }
}

if (count($inlineStyles) > 5) {
    $suggestions[] = [
        'type' => 'inline_styles',
        'severity' => 'low',
        'title' => count($inlineStyles) . ' estilos inline encontrados',
        'description' => 'Considere mover para classes CSS.',
        'locations' => array_slice($inlineStyles, 0, 10),
        'suggestion' => 'Extrair para public/assets/css/ui.css'
    ];
}

// Calcular estatísticas finais
$duplicatedLines = 0;
foreach ($duplicatedBlocks as $block) {
    $duplicatedLines += ($block['count'] - 1) * 10; // Aproximação
}
$stats['duplicated_lines'] = $duplicatedLines;
$stats['duplication_percentage'] = $stats['total_lines'] > 0 
    ? round(($duplicatedLines / $stats['total_lines']) * 100, 2) 
    : 0;

foreach (['php', 'js', 'css'] as $lang) {
    $langDupLines = 0;
    foreach ($stats['by_language'][$lang]['duplications'] as $dup) {
        $langDupLines += ($dup['count'] - 1) * 10;
    }
    $stats['by_language'][$lang]['duplicated_lines'] = $langDupLines;
    $stats['by_language'][$lang]['duplication_percentage'] = $stats['by_language'][$lang]['lines'] > 0
        ? round(($langDupLines / $stats['by_language'][$lang]['lines']) * 100, 2)
        : 0;
}

// Preparar resultado
$results = [
    'stats' => $stats,
    'duplicate_functions' => $duplicateFunctions,
    'duplicated_blocks' => array_slice($duplicatedBlocks, 0, 50),
    'classes' => $classes,
    'suggestions' => $suggestions,
    'inline_styles_count' => count($inlineStyles),
    'repeated_patterns_count' => count($repeatedPatterns),
    'generated_at' => date('Y-m-d H:i:s')
];

// Salvar JSON
$jsonPath = $basePath . '/storage/code-duplication-report.json';
file_put_contents($jsonPath, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\n✅ JSON: {$jsonPath}\n";

// Gerar HTML
$html = generateHtmlReport($results);
$htmlPath = $basePath . '/storage/code-duplication-report.html';
file_put_contents($htmlPath, $html);
echo "✅ HTML: {$htmlPath}\n";

// Resumo final
echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RESUMO DA ANÁLISE\n";
echo str_repeat("=", 60) . "\n\n";

echo "📁 Total de Arquivos: {$stats['total_files']}\n";
echo "📝 Total de Linhas: " . number_format($stats['total_lines']) . "\n";
echo "🔄 Linhas Duplicadas: ~" . number_format($stats['duplicated_lines']) . "\n";
echo "📈 Taxa de Duplicação: {$stats['duplication_percentage']}%\n\n";

echo "Por Linguagem:\n";
foreach ($stats['by_language'] as $lang => $data) {
    echo "  " . strtoupper($lang) . ": {$data['files']} arq, " . 
         number_format($data['lines']) . " linhas, " . 
         $data['duplication_percentage'] . "% dup, " .
         count($data['duplications']) . " blocos\n";
}

echo "\n💡 Sugestões: " . count($suggestions) . "\n";
echo "⚠️ Funções duplicadas: " . count($duplicateFunctions) . "\n";
echo "🧱 Blocos duplicados: " . count($duplicatedBlocks) . "\n";

function generateHtmlReport($results) {
    $stats = $results['stats'];
    
    $html = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Duplicação - Multi-Menu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .code-block { background: #1e293b; color: #e2e8f0; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; font-family: monospace; font-size: 0.75rem; white-space: pre-wrap; }
        .severity-high { border-left: 4px solid #ef4444; }
        .severity-medium { border-left: 4px solid #f59e0b; }
        .severity-low { border-left: 4px solid #3b82f6; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">📊 Relatório de Duplicação de Código</h1>
            <p class="text-gray-600 mt-2">Multi-Menu - ' . $results['generated_at'] . '</p>
        </header>
        
        <!-- Resumo -->
        <section class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">📈 Resumo Geral</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 rounded-lg p-4 text-center">
                    <div class="text-3xl font-bold text-blue-600">' . $stats['total_files'] . '</div>
                    <div class="text-sm text-gray-600">Arquivos</div>
                </div>
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <div class="text-3xl font-bold text-green-600">' . number_format($stats['total_lines']) . '</div>
                    <div class="text-sm text-gray-600">Linhas</div>
                </div>
                <div class="bg-orange-50 rounded-lg p-4 text-center">
                    <div class="text-3xl font-bold text-orange-600">~' . number_format($stats['duplicated_lines']) . '</div>
                    <div class="text-sm text-gray-600">Duplicadas</div>
                </div>
                <div class="bg-' . ($stats['duplication_percentage'] > 15 ? 'red' : 'green') . '-50 rounded-lg p-4 text-center">
                    <div class="text-3xl font-bold text-' . ($stats['duplication_percentage'] > 15 ? 'red' : 'green') . '-600">' . $stats['duplication_percentage'] . '%</div>
                    <div class="text-sm text-gray-600">Duplicação</div>
                </div>
            </div>
        </section>
        
        <!-- Por Linguagem -->
        <section class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">📂 Por Linguagem</h2>
            <table class="w-full">
                <thead><tr class="border-b"><th class="text-left py-2">Linguagem</th><th class="text-left py-2">Arquivos</th><th class="text-left py-2">Linhas</th><th class="text-left py-2">Duplicação</th><th class="text-left py-2">Blocos</th></tr></thead>
                <tbody>';
    
    foreach ($stats['by_language'] as $lang => $data) {
        $html .= '<tr class="border-b hover:bg-gray-50">
            <td class="py-2 font-mono font-medium">' . strtoupper($lang) . '</td>
            <td class="py-2">' . $data['files'] . '</td>
            <td class="py-2">' . number_format($data['lines']) . '</td>
            <td class="py-2"><span class="font-medium">' . $data['duplication_percentage'] . '%</span></td>
            <td class="py-2">' . count($data['duplications']) . '</td>
        </tr>';
    }
    
    $html .= '</tbody></table></section>';
    
    // Sugestões
    if (!empty($results['suggestions'])) {
        $html .= '<section class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">💡 Sugestões de Refatoração (' . count($results['suggestions']) . ')</h2>
            <div class="space-y-4">';
        
        // Ordenar por severidade
        usort($results['suggestions'], function($a, $b) {
            $order = ['high' => 0, 'medium' => 1, 'low' => 2];
            return ($order[$a['severity'] ?? 'low'] ?? 2) - ($order[$b['severity'] ?? 'low'] ?? 2);
        });
        
        foreach ($results['suggestions'] as $s) {
            $severity = $s['severity'] ?? 'medium';
            $html .= '<div class="border rounded-lg p-4 severity-' . $severity . '">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-semibold">' . htmlspecialchars($s['title']) . '</h3>
                        <p class="text-gray-600 text-sm mt-1">' . htmlspecialchars($s['description']) . '</p>';
            
            if (!empty($s['suggestion'])) {
                $html .= '<p class="text-blue-600 text-sm mt-2">📌 ' . htmlspecialchars($s['suggestion']) . '</p>';
            }
            
            if (!empty($s['locations'])) {
                $html .= '<div class="mt-2 text-xs text-gray-500">';
                foreach (array_slice($s['locations'], 0, 5) as $loc) {
                    $html .= '<div>📄 ' . htmlspecialchars($loc['file'] ?? '') . (isset($loc['line']) ? ':' . $loc['line'] : '') . '</div>';
                }
                if (count($s['locations']) > 5) {
                    $html .= '<div>... e mais ' . (count($s['locations']) - 5) . ' locais</div>';
                }
                $html .= '</div>';
            }
            
            $html .= '</div>
                    <span class="text-xs px-2 py-1 rounded-full ' . 
                        ($severity === 'high' ? 'bg-red-100 text-red-700' : 
                        ($severity === 'medium' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700')) . '">' . ucfirst($severity) . '</span>
                </div>
            </div>';
        }
        
        $html .= '</div></section>';
    }
    
    // Funções duplicadas
    if (!empty($results['duplicate_functions'])) {
        $html .= '<section class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">⚠️ Funções Duplicadas (' . count($results['duplicate_functions']) . ')</h2>
            <div class="space-y-3">';
        
        foreach ($results['duplicate_functions'] as $func) {
            $html .= '<div class="border rounded-lg p-3">
                <div class="font-mono font-medium text-red-600">' . htmlspecialchars($func['name']) . '() <span class="text-gray-500 text-sm font-normal">' . strtoupper($func['language']) . '</span></div>
                <div class="text-xs text-gray-500 mt-1">';
            foreach ($func['locations'] as $loc) {
                $html .= '<div>📄 ' . htmlspecialchars($loc['file']) . ':' . $loc['line'] . '</div>';
            }
            $html .= '</div></div>';
        }
        
        $html .= '</div></section>';
    }
    
    // Blocos duplicados
    if (!empty($results['duplicated_blocks'])) {
        $html .= '<section class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">🔄 Blocos de Código Duplicados (' . count($results['duplicated_blocks']) . ')</h2>
            <div class="space-y-4">';
        
        foreach (array_slice($results['duplicated_blocks'], 0, 20) as $block) {
            $html .= '<div class="border rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium">' . $block['count'] . ' ocorrências</span>
                    <span class="text-xs bg-gray-100 px-2 py-1 rounded">' . strtoupper($block['language']) . '</span>
                </div>
                <div class="text-xs text-gray-500 mb-2">';
            foreach (array_slice($block['locations'], 0, 3) as $loc) {
                $html .= '<div>📄 ' . htmlspecialchars($loc['file']) . ':' . $loc['start'] . '-' . $loc['end'] . '</div>';
            }
            if (count($block['locations']) > 3) {
                $html .= '<div>... e mais ' . (count($block['locations']) - 3) . ' locais</div>';
            }
            $html .= '</div>
                <div class="code-block">' . htmlspecialchars($block['code_sample']) . '</div>
            </div>';
        }
        
        if (count($results['duplicated_blocks']) > 20) {
            $html .= '<div class="text-center text-gray-500 py-4">... e mais ' . (count($results['duplicated_blocks']) - 20) . ' blocos</div>';
        }
        
        $html .= '</div></section>';
    }
    
    // Classes
    if (!empty($results['classes']) && is_array($results['classes'])) {
        $html .= '<section class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">🏗️ Classes PHP (' . count($results['classes']) . ')</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">';
        
        foreach ($results['classes'] as $name => $info) {
            if (!is_array($info)) continue;
            $html .= '<div class="border rounded p-2 text-sm">
                <span class="font-mono font-medium">' . htmlspecialchars($name) . '</span>
                <span class="text-xs text-gray-500 block">' . htmlspecialchars($info['file'] ?? '') . ':' . ($info['line'] ?? '') . '</span>
            </div>';
        }
        
        $html .= '</div></section>';
    }
    
    $html .= '
        <footer class="text-center text-gray-500 text-sm py-8">
            Gerado pelo Analisador de Duplicação de Código - Multi-Menu
        </footer>
    </div>
</body>
</html>';
    
    return $html;
}
