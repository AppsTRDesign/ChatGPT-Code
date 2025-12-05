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
    $stmt = $pdo->query("SELECT business_type AS name, category_slug AS slug, COUNT(*) AS total FROM places WHERE business_type IS NOT NULL AND business_type != '' GROUP BY category_slug, business_type ORDER BY total DESC");
    return $stmt->fetchAll();
}

function fetch_cities(PDO $pdo): array {
    $stmt = $pdo->query("SELECT DISTINCT city_name FROM places WHERE city_name IS NOT NULL AND city_name != '' ORDER BY city_name");
    return array_column($stmt->fetchAll(), 'city_name');
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

switch ($route) {
    case 'kategoriler':
        $cats = fetch_categories($pdo);
        if (isset($segments[1]) && $segments[1] !== '') {
            $catSlug = urldecode($segments[1]);
            $page = (int)($_GET['s'] ?? 1);
            [$offset, $limit] = paginate($page, DEFAULT_PAGE_LIMIT);
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
                'meta' => render_meta('Kategori: ' . $categoryTitle),
                'cats' => $cats,
                'categoryTitle' => $categoryTitle,
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
        $stmt = $pdo->query(base_metrics_sql() . ' ORDER BY combined_rating DESC, total_votes DESC LIMIT 100');
        render(__DIR__ . '/templates/listing.php', [
            'meta' => render_meta('Popüler İşletmeler'),
            'title' => 'Popüler İşletmeler',
            'items' => $stmt->fetchAll()
        ]);
        break;
    case 'en-cok-ziyaret-edilen':
        $stmt = $pdo->query(base_metrics_sql() . ' ORDER BY views DESC, combined_rating DESC LIMIT 100');
        render(__DIR__ . '/templates/listing.php', [
            'meta' => render_meta('En Çok Görüntülenen'),
            'title' => 'En Çok Görüntülenen',
            'items' => $stmt->fetchAll()
        ]);
        break;
    case 'en-cok-yorum-alan':
        $stmt = $pdo->query(base_metrics_sql() . ' ORDER BY total_reviews DESC, combined_rating DESC LIMIT 100');
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
        $recent = $pdo->query(base_metrics_sql() . ' ORDER BY p.created_at DESC LIMIT 6')->fetchAll();
        $topRated = $pdo->query(base_metrics_sql() . ' ORDER BY combined_rating DESC, total_votes DESC LIMIT 6')->fetchAll();
        $mostViewed = $pdo->query(base_metrics_sql() . ' ORDER BY views DESC, combined_rating DESC LIMIT 6')->fetchAll();
        render(__DIR__ . '/templates/home.php', [
            'meta' => render_meta('Harita Portalı'),
            'categories' => $categories,
            'cities' => $cities,
            'recent' => $recent,
            'topRated' => $topRated,
            'mostViewed' => $mostViewed
        ]);
}
