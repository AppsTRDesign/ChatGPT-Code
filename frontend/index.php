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

function fetch_categories(PDO $pdo): array {
    $stmt = $pdo->query('SELECT business_type AS name, COUNT(*) AS total FROM places WHERE business_type IS NOT NULL AND business_type != "" GROUP BY business_type ORDER BY total DESC');
    return $stmt->fetchAll();
}

function fetch_cities(PDO $pdo): array {
    $stmt = $pdo->query('SELECT DISTINCT city_name FROM places WHERE city_name IS NOT NULL AND city_name != "" ORDER BY city_name');
    return array_column($stmt->fetchAll(), 'city_name');
}

function fetch_place(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare('SELECT * FROM places WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $place = $stmt->fetch();
    if ($place) {
        $place['opening_hours'] = decode_json($place['opening_hours']);
        $place['busy_hours'] = decode_json($place['busy_hours']);
        $place['reviews'] = decode_json($place['reviews']);
    }
    return $place ?: null;
}

function fetch_user_reviews(PDO $pdo, int $placeId): array {
    $stmt = $pdo->prepare('SELECT author_name AS author, rating, review_text AS text, text_extra, created_at FROM user_reviews WHERE place_id = :pid ORDER BY created_at DESC');
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

switch ($route) {
    case 'kategoriler':
        $cats = fetch_categories($pdo);
        if (isset($segments[1]) && $segments[1] !== '') {
            $catSlug = urldecode($segments[1]);
            $page = (int)($_GET['s'] ?? 1);
            [$offset, $limit] = paginate($page, DEFAULT_PAGE_LIMIT);
            $stmt = $pdo->prepare('SELECT * FROM places WHERE business_type = :cat ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
            $stmt->bindValue(':cat', str_replace('-', ' ', $catSlug));
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll();
            render(__DIR__ . '/templates/category.php', [
                'meta' => render_meta('Kategori: ' . $catSlug),
                'cats' => $cats,
                'categoryTitle' => $catSlug,
                'items' => $items,
                'page' => $page
            ]);
        }
        render(__DIR__ . '/templates/categories.php', [
            'meta' => render_meta('Kategoriler'),
            'cats' => $cats
        ]);
        break;
    case 'populer':
        $stmt = $pdo->query('SELECT * FROM places ORDER BY rating DESC, user_ratings_total DESC LIMIT 100');
        render(__DIR__ . '/templates/listing.php', [
            'meta' => render_meta('Popüler İşletmeler'),
            'title' => 'Popüler İşletmeler',
            'items' => $stmt->fetchAll()
        ]);
        break;
    case 'en-cok-ziyaret-edilen':
        $stmt = $pdo->query('SELECT * FROM places ORDER BY user_ratings_total DESC LIMIT 100');
        render(__DIR__ . '/templates/listing.php', [
            'meta' => render_meta('En Çok Görüntülenen'),
            'title' => 'En Çok Görüntülenen',
            'items' => $stmt->fetchAll()
        ]);
        break;
    case 'en-cok-yorum-alan':
        $stmt = $pdo->query('SELECT * FROM places ORDER BY JSON_LENGTH(reviews) DESC, user_ratings_total DESC LIMIT 100');
        render(__DIR__ . '/templates/listing.php', [
            'meta' => render_meta('En Çok Yorum Alan'),
            'title' => 'En Çok Yorum Alan',
            'items' => $stmt->fetchAll()
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
        render(__DIR__ . '/templates/detail.php', [
            'meta' => render_meta($place['name'] ?? 'Detay'),
            'place' => $place,
            'userReviews' => $userReviews,
        ]);
        break;
    default:
        $categories = fetch_categories($pdo);
        $cities = fetch_cities($pdo);
        $recent = $pdo->query('SELECT * FROM places ORDER BY created_at DESC LIMIT 6')->fetchAll();
        $topRated = $pdo->query('SELECT * FROM places ORDER BY rating DESC, user_ratings_total DESC LIMIT 6')->fetchAll();
        $mostViewed = $pdo->query('SELECT * FROM places ORDER BY user_ratings_total DESC LIMIT 6')->fetchAll();
        render(__DIR__ . '/templates/home.php', [
            'meta' => render_meta('Harita Portalı'),
            'categories' => $categories,
            'cities' => $cities,
            'recent' => $recent,
            'topRated' => $topRated,
            'mostViewed' => $mostViewed
        ]);
}
