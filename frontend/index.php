<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$pdo = get_pdo();
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$segments = $path === '' ? [] : explode('/', $path);

$route = $segments[0] ?? '';
$idFromSlug = function(string $slug): int {
    if (preg_match('/^(\d+)-?/', $slug, $m)) {
        return (int)$m[1];
    }
    return 0;
};

function emit_xml(string $xml): void {
    header('Content-Type: application/xml; charset=utf-8');
    echo $xml;
    exit;
}

function fetch_categories(PDO $pdo): array {
    $stmt = $pdo->query("SELECT business_type AS name, category_slug AS slug, COUNT(*) AS total FROM places WHERE business_type IS NOT NULL AND business_type != '' GROUP BY category_slug, business_type ORDER BY total DESC");
    return $stmt->fetchAll();
}

function fetch_cities(PDO $pdo): array {
    $stmt = $pdo->query("SELECT DISTINCT city_name FROM places WHERE city_name IS NOT NULL AND city_name != '' ORDER BY city_name");
    return array_column($stmt->fetchAll(), 'city_name');
}

function find_city_by_slug(PDO $pdo, string $slug): ?string {
    $cities = fetch_cities($pdo);
    foreach ($cities as $city) {
        if (city_slug($city) === $slug) {
            return $city;
        }
    }
    return null;
}

function fetch_place(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare('SELECT p.*, COALESCE(NULLIF(p.view_total,0), pv.visit_count, 0) AS views,
        COALESCE(ur.user_review_count,0) AS user_review_count,
        COALESCE(ur.user_review_sum,0) AS user_review_sum,
        (COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0)) AS total_votes,
        CASE WHEN (COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0))>0
          THEN (COALESCE(p.rating,0)*COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_sum,0)) /(COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0))
          ELSE 0 END AS combined_rating,
        (COALESCE(JSON_LENGTH(p.reviews),0)+COALESCE(ur.user_review_count,0)) AS total_reviews
        FROM places p
        LEFT JOIN (SELECT place_id, COUNT(*) AS user_review_count, SUM(rating) AS user_review_sum FROM user_reviews GROUP BY place_id) ur ON ur.place_id = p.id
        LEFT JOIN (SELECT place_id, COUNT(*) AS visit_count FROM place_visits GROUP BY place_id) pv ON pv.place_id = p.id
        WHERE p.id = :id');
    $stmt->execute([':id' => $id]);
    $place = $stmt->fetch();
    if ($place) {
        $place['opening_hours'] = decode_json($place['opening_hours']);
        $place['busy_hours'] = decode_json($place['busy_hours']);
        $place['reviews'] = decode_json($place['reviews']);
    }
    return $place ?: null;
}

function base_metrics_sql(): string {
    return "SELECT p.*, COALESCE(NULLIF(p.view_total,0), pv.visit_count, 0) AS views,
        COALESCE(ur.user_review_count,0) AS user_review_count,
        COALESCE(ur.user_review_sum,0) AS user_review_sum,
        (COALESCE(JSON_LENGTH(p.reviews),0)+COALESCE(ur.user_review_count,0)) AS total_reviews,
        (COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0)) AS total_votes,
        CASE WHEN (COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0))>0
          THEN (COALESCE(p.rating,0)*COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_sum,0)) /(COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0))
          ELSE 0 END AS combined_rating
        FROM places p
        LEFT JOIN (SELECT place_id, COUNT(*) AS user_review_count, SUM(rating) AS user_review_sum FROM user_reviews GROUP BY place_id) ur ON ur.place_id = p.id
        LEFT JOIN (SELECT place_id, COUNT(*) AS visit_count FROM place_visits GROUP BY place_id) pv ON pv.place_id = p.id";
}

function fetch_user_reviews(PDO $pdo, int $placeId): array {
    $stmt = $pdo->prepare('SELECT author_name AS author, email, rating, review_text AS text, text_extra, created_at FROM user_reviews WHERE place_id = :pid ORDER BY created_at DESC');
    $stmt->execute([':pid' => $placeId]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['text_extra'] = decode_json($row['text_extra']);
    }
    return $rows;
}

function render($template, $data = []) {
    extract($data);
    include __DIR__ . "/templates/header.php";
    include $template;
    include __DIR__ . "/templates/footer.php";
    exit;
}

if ($route === 'sitemap.xml') {
    $total = (int)$pdo->query('SELECT COUNT(*) FROM places')->fetchColumn();
    $pages = max(1, (int)ceil($total / max(1, SITEMAP_PLACE_LIMIT)));
    $entries = [];
    for ($i = 1; $i <= $pages; $i++) {
        $entries[] = BASE_URL . '/sitemap-places-' . $i . '.xml';
    }
    $entries[] = BASE_URL . '/sitemap-categories.xml';
    $entries[] = BASE_URL . '/sitemap-cities.xml';
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";
    foreach ($entries as $loc) {
        $xml .= "<sitemap><loc>{$loc}</loc></sitemap>";
    }
    $xml .= '</sitemapindex>';
    emit_xml($xml);
}

if (preg_match('/^sitemap-places-(\d+)\.xml$/', $route, $m)) {
    $page = max(1, (int)$m[1]);
    $limit = max(1, SITEMAP_PLACE_LIMIT);
    $offset = ($page - 1) * $limit;
    $stmt = $pdo->prepare('SELECT id, name, business_type, category_slug, formatted_address, city_name, created_at, business_image FROM places ORDER BY id ASC LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";
    foreach ($rows as $row) {
        $loc = BASE_URL . '/' . build_place_slug($row);
        $lastmod = $row['created_at'] ? date('c', strtotime($row['created_at'])) : null;
        $xml .= '<url>';
        $xml .= '<loc>' . htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') . '</loc>';
        if ($lastmod) {
            $xml .= '<lastmod>' . $lastmod . '</lastmod>';
        }
        $xml .= '</url>';
    }
    $xml .= '</urlset>';
    emit_xml($xml);
}

if ($route === 'sitemap-categories.xml') {
    $cats = fetch_categories($pdo);
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";
    foreach ($cats as $cat) {
        $loc = BASE_URL . '/kategoriler/' . urlencode($cat['slug'] ?? '');
        $xml .= '<url><loc>' . htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') . '</loc></url>';
    }
    $xml .= '</urlset>';
    emit_xml($xml);
}

if ($route === 'sitemap-cities.xml') {
    $cities = fetch_cities($pdo);
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";
    foreach ($cities as $city) {
        $loc = BASE_URL . '/sehir/' . urlencode(city_slug($city));
        $xml .= '<url><loc>' . htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') . '</loc></url>';
    }
    $xml .= '</urlset>';
    emit_xml($xml);
}

switch ($route) {
    case 'kategoriler':
        $cats = fetch_categories($pdo);
        if (isset($segments[1]) && $segments[1] !== '') {
            $catSlug = urldecode($segments[1]);
            $page = (int)($_GET['s'] ?? 1);
            [$offset, $limit] = paginate($page, LISTING_PAGE_LIMIT);
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM places WHERE category_slug = :cat');
            $countStmt->execute([':cat' => $catSlug]);
            $total = (int)$countStmt->fetchColumn();
            $totalPages = (int)ceil($total / $limit);
            $stmt = $pdo->prepare(base_metrics_sql() . ' WHERE p.category_slug = :cat ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset');
            $stmt->bindValue(':cat', $catSlug);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll();
            $categoryTitle = $catSlug;
            foreach ($cats as $cat) {
                if (($cat['slug'] ?? '') === $catSlug) {
                    $categoryTitle = $cat['name'];
                    break;
                }
            }
            render(__DIR__ . '/templates/category.php', [
                'meta' => render_meta(['title' => 'Kategori: ' . $categoryTitle, 'description' => $categoryTitle, 'url' => BASE_URL . '/kategoriler/' . urlencode($catSlug)]),
                'cats' => $cats,
                'categoryTitle' => $categoryTitle,
                'items' => $items,
                'page' => $page,
                'totalPages' => $totalPages,
                'catSlug' => $catSlug,
            ]);
        }
        render(__DIR__ . '/templates/categories.php', [
            'meta' => render_meta('Kategoriler'),
            'cats' => $cats
        ]);
        break;
    case 'sehir':
        $citySlug = $segments[1] ?? '';
        $cityName = $citySlug ? find_city_by_slug($pdo, $citySlug) : null;
        if (!$cityName) {
            http_response_code(404);
            render(__DIR__ . '/templates/not_found.php', ['meta' => render_meta('Bulunamadı')]);
        }
        $catStmt = $pdo->prepare("SELECT business_type AS name, category_slug AS slug, COUNT(*) AS total FROM places WHERE city_name = :city AND business_type IS NOT NULL AND business_type != '' GROUP BY category_slug, business_type ORDER BY total DESC");
        $catStmt->execute([':city' => $cityName]);
        $cityCats = $catStmt->fetchAll();

        $recentStmt = $pdo->prepare(base_metrics_sql() . ' WHERE p.city_name = :city ORDER BY p.created_at DESC LIMIT :limit');
        $recentStmt->bindValue(':city', $cityName);
        $recentStmt->bindValue(':limit', LISTING_PAGE_LIMIT, PDO::PARAM_INT);
        $recentStmt->execute();
        $cityRecent = $recentStmt->fetchAll();

        render(__DIR__ . '/templates/city.php', [
            'meta' => render_meta([
                'title' => ucfirst($cityName) . ' İşletmeleri',
                'description' => ucfirst($cityName) . ' işletmeleri ve kategorileri',
                'url' => BASE_URL . '/sehir/' . urlencode($citySlug),
            ]),
            'cityName' => $cityName,
            'citySlug' => $citySlug,
            'cityCats' => $cityCats,
            'cityRecent' => $cityRecent,
        ]);
        break;
    case 'search':
        $q = trim($_GET['q'] ?? '');
        $page = (int)($_GET['s'] ?? 1);
        [$offset, $limit] = paginate($page, LISTING_PAGE_LIMIT);
        $where = '1=1';
        $params = [];
        if ($q !== '') {
            $where .= ' AND (p.name LIKE :q OR p.formatted_address LIKE :q)';
            $params[':q'] = "%{$q}%";
        }
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM places p WHERE {$where}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $totalPages = (int)ceil($total / $limit);

        $dataStmt = $pdo->prepare(base_metrics_sql() . " WHERE {$where} ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset");
        foreach ($params as $k => $v) { $dataStmt->bindValue($k, $v); }
        $dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $dataStmt->execute();
        $items = $dataStmt->fetchAll();
        render(__DIR__ . '/templates/listing.php', [
            'meta' => render_meta(['title' => 'Arama: ' . $q, 'description' => 'Arama sonuçları', 'url' => BASE_URL . '/search?q=' . urlencode($q)]),
            'title' => 'Arama Sonuçları',
            'items' => $items,
            'page' => $page,
            'totalPages' => $totalPages,
            'baseUrl' => '/search',
            'queryParams' => ['q' => $q],
        ]);
        break;
    case 'populer':
        $page = (int)($_GET['s'] ?? 1);
        [$offset, $limit] = paginate($page, LISTING_PAGE_LIMIT);
        $total = (int)$pdo->query('SELECT COUNT(*) FROM places')->fetchColumn();
        $totalPages = (int)ceil($total / $limit);
        $stmt = $pdo->prepare(base_metrics_sql() . ' ORDER BY combined_rating DESC, total_votes DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        render(__DIR__ . '/templates/listing.php', [
            'meta' => render_meta(['title' => 'Popüler İşletmeler', 'url' => BASE_URL . '/populer']),
            'title' => 'Popüler İşletmeler',
            'items' => $stmt->fetchAll(),
            'page' => $page,
            'totalPages' => $totalPages,
            'baseUrl' => '/populer',
            'queryParams' => []
        ]);
        break;
    case 'en-cok-ziyaret-edilen':
        $page = (int)($_GET['s'] ?? 1);
        [$offset, $limit] = paginate($page, LISTING_PAGE_LIMIT);
        $total = (int)$pdo->query('SELECT COUNT(*) FROM places')->fetchColumn();
        $totalPages = (int)ceil($total / $limit);
        $stmt = $pdo->prepare(base_metrics_sql() . ' ORDER BY views DESC, combined_rating DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        render(__DIR__ . '/templates/listing.php', [
            'meta' => render_meta(['title' => 'En Çok Görüntülenen', 'url' => BASE_URL . '/en-cok-ziyaret-edilen']),
            'title' => 'En Çok Görüntülenen',
            'items' => $stmt->fetchAll(),
            'page' => $page,
            'totalPages' => $totalPages,
            'baseUrl' => '/en-cok-ziyaret-edilen',
            'queryParams' => []
        ]);
        break;
    case 'en-cok-yorum-alan':
        $page = (int)($_GET['s'] ?? 1);
        [$offset, $limit] = paginate($page, LISTING_PAGE_LIMIT);
        $total = (int)$pdo->query('SELECT COUNT(*) FROM places')->fetchColumn();
        $totalPages = (int)ceil($total / $limit);
        $stmt = $pdo->prepare(base_metrics_sql() . ' ORDER BY total_reviews DESC, combined_rating DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        render(__DIR__ . '/templates/listing.php', [
            'meta' => render_meta(['title' => 'En Çok Yorum Alan', 'url' => BASE_URL . '/en-cok-yorum-alan']),
            'title' => 'En Çok Yorum Alan',
            'items' => $stmt->fetchAll(),
            'page' => $page,
            'totalPages' => $totalPages,
            'baseUrl' => '/en-cok-yorum-alan',
            'queryParams' => []
        ]);
        break;
    case 'isletme':
        $slug = $segments[1] ?? '';
        $id = $idFromSlug($slug);
        $place = $id ? fetch_place($pdo, $id) : null;
        if (!$place) {
            http_response_code(404);
            render(__DIR__ . '/templates/not_found.php', ['meta' => render_meta('Bulunamadı')]);
        }
        $userReviews = fetch_user_reviews($pdo, $id);
        $googleReviews = $place['reviews'] ?? [];
        $metaUrl = BASE_URL . '/' . build_place_slug($place);
        $metaImage = $place['business_image'] ?: BASE_URL . '/assets/img/default.jpg';
        $meta = render_meta([
            'title' => $place['name'] ?? 'Detay',
            'description' => trim(($place['name'] ?? '') . ' ' . ($place['formatted_address'] ?? '')),
            'keywords' => implode(',', array_filter([$place['name'] ?? '', $place['business_type'] ?? '', $place['city_name'] ?? ''])),
            'url' => $metaUrl,
            'image' => $metaImage,
        ]);
        render(__DIR__ . '/templates/detail.php', [
            'meta' => $meta,
            'place' => $place,
            'userReviews' => $userReviews,
            'googleReviews' => $googleReviews,
        ]);
        break;
    default:
        $categories = fetch_categories($pdo);
        $cities = fetch_cities($pdo);
        $recent = $pdo->query(base_metrics_sql() . ' ORDER BY p.created_at DESC LIMIT ' . (int)HOME_RECENT_LIMIT)->fetchAll();
        $topRated = $pdo->query(base_metrics_sql() . ' ORDER BY combined_rating DESC, total_votes DESC LIMIT ' . (int)HOME_RECENT_LIMIT)->fetchAll();
        $mostViewed = $pdo->query(base_metrics_sql() . ' ORDER BY views DESC, combined_rating DESC LIMIT ' . (int)HOME_RECENT_LIMIT)->fetchAll();
        render(__DIR__ . '/templates/home.php', [
            'meta' => render_meta(['title' => 'NoaSoft Maps', 'description' => 'Türkiye genelinde işletmeleri keşfedin', 'url' => BASE_URL]),
            'categories' => $categories,
            'cities' => $cities,
            'recent' => $recent,
            'topRated' => $topRated,
            'mostViewed' => $mostViewed
        ]);
}
