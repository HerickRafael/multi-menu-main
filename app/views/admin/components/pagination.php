<?php
/**
 * Componente: Paginação
 * 
 * @param int $currentPage Página atual (1-indexed)
 * @param int $totalPages Total de páginas
 * @param int $totalItems Total de itens
 * @param int $perPage Itens por página
 * @param string $baseUrl URL base para os links (ex: /admin/products)
 * @param string $pageParam Nome do parâmetro de página (default: 'page')
 * @param array $queryParams Parâmetros adicionais para manter na URL
 * @param int $maxVisiblePages Máximo de páginas visíveis
 */

$currentPage = max(1, intval($currentPage ?? 1));
$totalPages = max(1, intval($totalPages ?? 1));
$totalItems = intval($totalItems ?? 0);
$perPage = max(1, intval($perPage ?? 10));
$baseUrl = $baseUrl ?? '';
$pageParam = $pageParam ?? 'page';
$queryParams = $queryParams ?? [];
$maxVisiblePages = $maxVisiblePages ?? 5;

if ($totalPages <= 1) {
    return; // Não mostra paginação se só tem uma página
}

// Função para gerar URL
function buildPaginationUrl($page, $baseUrl, $pageParam, $queryParams) {
    $params = array_merge($queryParams, [$pageParam => $page]);
    $query = http_build_query($params);
    return $baseUrl . '?' . $query;
}

// Calcular intervalo de páginas visíveis
$halfVisible = floor($maxVisiblePages / 2);
$startPage = max(1, $currentPage - $halfVisible);
$endPage = min($totalPages, $startPage + $maxVisiblePages - 1);

if ($endPage - $startPage < $maxVisiblePages - 1) {
    $startPage = max(1, $endPage - $maxVisiblePages + 1);
}

// Calcular range de itens mostrados
$startItem = (($currentPage - 1) * $perPage) + 1;
$endItem = min($currentPage * $perPage, $totalItems);
?>

<nav class="flex flex-col sm:flex-row items-center justify-between gap-4 py-4" aria-label="Paginação">
    <!-- Info de itens -->
    <div class="text-sm text-gray-600">
        Mostrando <span class="font-medium"><?= $startItem ?></span> a <span class="font-medium"><?= $endItem ?></span> de <span class="font-medium"><?= number_format($totalItems, 0, ',', '.') ?></span> resultados
    </div>
    
    <!-- Controles de paginação -->
    <div class="flex items-center gap-1">
        <!-- Primeira página -->
        <?php if ($currentPage > 2): ?>
        <a 
            href="<?= e(buildPaginationUrl(1, $baseUrl, $pageParam, $queryParams)) ?>" 
            class="px-2 py-1 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
            title="Primeira página"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
            </svg>
        </a>
        <?php endif; ?>
        
        <!-- Anterior -->
        <?php if ($currentPage > 1): ?>
        <a 
            href="<?= e(buildPaginationUrl($currentPage - 1, $baseUrl, $pageParam, $queryParams)) ?>" 
            class="px-3 py-1 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition-colors flex items-center gap-1"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7"/>
            </svg>
            <span class="hidden sm:inline">Anterior</span>
        </a>
        <?php else: ?>
        <span class="px-3 py-1 text-sm text-gray-300 cursor-not-allowed flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7"/>
            </svg>
            <span class="hidden sm:inline">Anterior</span>
        </span>
        <?php endif; ?>
        
        <!-- Páginas -->
        <div class="flex items-center gap-1 mx-2">
            <?php if ($startPage > 1): ?>
            <span class="px-2 py-1 text-sm text-gray-400">...</span>
            <?php endif; ?>
            
            <?php for ($page = $startPage; $page <= $endPage; $page++): ?>
            <?php if ($page === $currentPage): ?>
            <span class="px-3 py-1 text-sm font-medium bg-blue-600 text-white rounded-lg"><?= $page ?></span>
            <?php else: ?>
            <a 
                href="<?= e(buildPaginationUrl($page, $baseUrl, $pageParam, $queryParams)) ?>" 
                class="px-3 py-1 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
            ><?= $page ?></a>
            <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($endPage < $totalPages): ?>
            <span class="px-2 py-1 text-sm text-gray-400">...</span>
            <?php endif; ?>
        </div>
        
        <!-- Próximo -->
        <?php if ($currentPage < $totalPages): ?>
        <a 
            href="<?= e(buildPaginationUrl($currentPage + 1, $baseUrl, $pageParam, $queryParams)) ?>" 
            class="px-3 py-1 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition-colors flex items-center gap-1"
        >
            <span class="hidden sm:inline">Próximo</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <?php else: ?>
        <span class="px-3 py-1 text-sm text-gray-300 cursor-not-allowed flex items-center gap-1">
            <span class="hidden sm:inline">Próximo</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/>
            </svg>
        </span>
        <?php endif; ?>
        
        <!-- Última página -->
        <?php if ($currentPage < $totalPages - 1): ?>
        <a 
            href="<?= e(buildPaginationUrl($totalPages, $baseUrl, $pageParam, $queryParams)) ?>" 
            class="px-2 py-1 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
            title="Última página"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
            </svg>
        </a>
        <?php endif; ?>
    </div>
</nav>
