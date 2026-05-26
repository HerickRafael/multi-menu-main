<?php
/* ========= Rota raiz ========= */
$router->get('/', function() {
    header('Location: /wollburger');
    exit;
});

/* ========= Landing page de vendas ========= */
$router->get('/vendas', 'App\\Controllers\\Public\\LandingController@index');

/* ========= SEO: robots.txt dinâmico (multi-tenant) ========= */
$router->get('/robots.txt', function() {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $baseUrl = ($isHttps ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

    header('Content-Type: text/plain; charset=UTF-8');
    echo "User-agent: *\n";
    echo "Allow: /\n";
    echo "Disallow: /admin/\n";
    echo "Disallow: /superadmin\n";
    echo "Disallow: /api/\n";
    echo "Disallow: /*/cart\n";
    echo "Disallow: /*/checkout\n";
    echo "Disallow: /*/profile\n";
    echo "Disallow: /*/reorder/\n";
    echo "\n";
    echo "Sitemap: {$baseUrl}/sitemap.xml\n";
    exit;
});

/* ========= SEO: Sitemap XML dinâmico ========= */
$router->get('/sitemap.xml', function() {
    $db = Database::getInstance();
    $companies = $db->query("SELECT slug FROM companies ORDER BY slug")->fetchAll(\PDO::FETCH_ASSOC);

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $baseUrl = ($isHttps ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $today = date('Y-m-d');

    header('Content-Type: application/xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($companies as $c) {
        $loc = htmlspecialchars($baseUrl . '/' . rawurlencode($c['slug']), ENT_XML1, 'UTF-8');
        echo "  <url><loc>{$loc}</loc><lastmod>{$today}</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>\n";
    }
    echo '</urlset>';
    exit;
});

