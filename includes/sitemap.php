<?php

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/xml; charset=UTF-8');

$section = $_GET['section'] ?? 'index';
$limit = 1000;

$formatDate = static function (?string $value): string {
    if (!$value) {
        return date('c');
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('c', $timestamp) : date('c');
};

$printUrl = static function (string $loc, ?string $lastmod = null): void {
    $safeLoc = htmlspecialchars($loc, ENT_XML1);
    echo "  <url>\n";
    echo "    <loc>{$safeLoc}</loc>\n";
    if ($lastmod) {
        $safeDate = htmlspecialchars($lastmod, ENT_XML1);
        echo "    <lastmod>{$safeDate}</lastmod>\n";
    }
    echo "  </url>\n";
};

if ($section === 'index') {
    $productCount = (int) db()->query('SELECT COUNT(*) FROM products')->fetchColumn();
    $totalPages = max(1, (int) ceil($productCount / $limit));

    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    $now = date('c');
    $sitemaps = [
        base_url('sitemap-pages.xml'),
        base_url('sitemap-categories.xml'),
    ];
    for ($page = 1; $page <= $totalPages; $page++) {
        $sitemaps[] = base_url("sitemap-{$page}.xml");
    }
    foreach ($sitemaps as $sitemap) {
        $safeLoc = htmlspecialchars($sitemap, ENT_XML1);
        $safeDate = htmlspecialchars($now, ENT_XML1);
        echo "  <sitemap>\n";
        echo "    <loc>{$safeLoc}</loc>\n";
        echo "    <lastmod>{$safeDate}</lastmod>\n";
        echo "  </sitemap>\n";
    }
    echo "</sitemapindex>\n";
    exit;
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

if ($section === 'pages') {
    $staticPaths = [
        '',
        'kategoriler',
        'icerikler',
        'sss',
        'iletisim',
        'giris',
        'kayit',
    ];
    foreach ($staticPaths as $path) {
        $url = $path === '' ? base_url('') : base_url($path);
        $printUrl($url, date('c'));
    }

    $pagesStmt = db()->query('SELECT slug, created_at FROM pages ORDER BY created_at DESC');
    foreach ($pagesStmt->fetchAll(PDO::FETCH_ASSOC) as $page) {
        $printUrl(base_url('sayfa/' . $page['slug']), $formatDate($page['created_at'] ?? null));
    }
} elseif ($section === 'categories') {
    $categoryStmt = db()->query('SELECT slug, created_at FROM categories ORDER BY name ASC');
    foreach ($categoryStmt->fetchAll(PDO::FETCH_ASSOC) as $category) {
        $printUrl(base_url('kategori/' . $category['slug']), $formatDate($category['created_at'] ?? null));
    }
} elseif ($section === 'products') {
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;
    $productsStmt = db()->prepare('SELECT slug, created_at FROM products ORDER BY id ASC LIMIT :limit OFFSET :offset');
    $productsStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $productsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $productsStmt->execute();
    foreach ($productsStmt->fetchAll(PDO::FETCH_ASSOC) as $product) {
        $printUrl(base_url('urun/' . $product['slug']), $formatDate($product['created_at'] ?? null));
    }
}

echo "</urlset>\n";
