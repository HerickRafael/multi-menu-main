<?php
declare(strict_types=1);
/**
 * SPA Wrapper for React Frontend
 * This file outputs the compiled React SPA index.html directly
 */

// Set header to indicate this is the SPA view
header('X-SPA-Version: React-Vite-Build');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Get the compiled SPA HTML
$indexPath = __DIR__ . '/../../public/superadmin/index.html';

if (file_exists($indexPath)) {
    // Read and output the index.html file
    readfile($indexPath);
    exit;
} else {
    http_response_code(500);
    echo '<h1>500 Internal Server Error</h1>';
    echo '<p>React SPA not found at ' . htmlspecialchars($indexPath) . '</p>';
    exit;
}

