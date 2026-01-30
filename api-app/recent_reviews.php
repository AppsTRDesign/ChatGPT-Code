<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . '/../includes/helpers.php';

$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$countryCode = $_GET['country_code'] ?? null;
$sort = $_GET['sort'] ?? 'new';

if ($perPage < 1 || $perPage > 100) {
    $perPage = 10;
}
if ($page < 1) {
    $page = 1;
}
$offset = ($page - 1) * $perPage;

$pdo = get_pdo();
$params = [];
$where = "p.status = 1";
if ($countryCode) {
    $where .= " AND p.country_code = :code";
    $params[':code'] = strtoupper($countryCode);
}

$normalizeExtras = function ($extras): array {
    if (!is_array($extras)) {
        return [];
    }
    $out = [];
    foreach ($extras as $key => $value) {
        if (is_int($key)) {
            $out[] = [
                'label' => is_array($value) ? implode(' ', $value) : (string)$value,
                'value' => ''
            ];
        } else {
            $out[] = [
                'label' => (string)$key,
                'value' => is_array($value) ? implode(' ', $value) : (string)$value
            ];
        }
    }
    return $out;
};

$reviews = [];

$userSql = "
    SELECT
        ur.id,
        ur.place_id,
        ur.author_name,
        ur.rating,
        ur.review_text,
        ur.text_extra,
        ur.review_photo_urls,
        ur.created_at,
        u.profile_photo,
        p.name AS place_name,
        p.business_image,
        p.business_type,
        p.city_name
    FROM user_reviews ur
    JOIN places p ON p.id = ur.place_id
    LEFT JOIN users u ON u.id = ur.user_id
    WHERE ur.status = 'approved' AND {$where}
";
$userStmt = $pdo->prepare($userSql);
foreach ($params as $key => $value) {
    $userStmt->bindValue($key, $value);
}
$userStmt->execute();
$userRows = $userStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($userRows as $row) {
    $timestamp = strtotime($row['created_at']) ?: 0;
    $reviews[] = [
        'source' => 'user',
        'review_ref' => (string)$row['id'],
        'id' => (int)$row['id'],
        'place_id' => (int)$row['place_id'],
        'place_name' => $row['place_name'],
        'place_image' => $row['business_image'],
        'business_type' => $row['business_type'],
        'city_name' => $row['city_name'],
        'author_name' => $row['author_name'] ?: 'Kullanıcı',
        'profile_photo_url' => $row['profile_photo'] ?: BASE_URL . '/assets/img/default-user.webp',
        'rating' => isset($row['rating']) ? (float)$row['rating'] : null,
        'review_text' => $row['review_text'],
        'created_at' => $row['created_at'],
        'timestamp' => $timestamp,
        'text_extra' => $normalizeExtras(decode_json($row['text_extra'] ?? '')),
        'review_photo_urls' => decode_json($row['review_photo_urls'] ?? ''),
        'likes_count' => 0
    ];
}

$placeSql = "
    SELECT p.id, p.name, p.business_image, p.business_type, p.city_name, p.reviews
    FROM places p
    WHERE {$where}
";
$placeStmt = $pdo->prepare($placeSql);
foreach ($params as $key => $value) {
    $placeStmt->bindValue($key, $value);
}
$placeStmt->execute();
$places = $placeStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($places as $place) {
    $googleReviews = decode_json($place['reviews'] ?? '');
    foreach ($googleReviews as $gr) {
        $relativeTime = $gr['relative_time'] ?? ($gr['relative_time_description'] ?? '');
        $timestamp = 0;
        if (!empty($gr['time'])) {
            $timestamp = is_numeric($gr['time']) ? (int)$gr['time'] : strtotime($gr['time']);
        } elseif (!empty($gr['review_time'])) {
            $timestamp = is_numeric($gr['review_time']) ? (int)$gr['review_time'] : strtotime($gr['review_time']);
        }
        if (!$timestamp && $relativeTime) {
            $timestamp = strtotime($relativeTime) ?: 0;
        }
        if ($timestamp > 2000000000) {
            $timestamp = (int)($timestamp / 1000);
        }
        $reviewRef = sha1(
            ($gr['author_name'] ?? '') .
            $relativeTime .
            ($gr['text'] ?? '')
        );
        $reviews[] = [
            'source' => 'google',
            'review_ref' => $reviewRef,
            'id' => null,
            'place_id' => (int)$place['id'],
            'place_name' => $place['name'],
            'place_image' => $place['business_image'],
            'business_type' => $place['business_type'],
            'city_name' => $place['city_name'],
            'author_name' => $gr['author_name'] ?? 'Google Kullanıcısı',
            'profile_photo_url' => $gr['profile_photo_url'] ?? '',
            'rating' => isset($gr['rating']) ? (float)$gr['rating'] : null,
            'review_text' => $gr['text'] ?? '',
            'created_at' => $relativeTime,
            'timestamp' => $timestamp,
            'text_extra' => [],
            'review_photo_urls' => $gr['review_photo_urls'] ?? [],
            'likes_count' => 0
        ];
    }
}

$likeSql = "
    SELECT review_source, review_ref, COUNT(*) AS total
    FROM comments_like cl
    JOIN places p ON p.id = cl.place_id
    WHERE {$where}
    GROUP BY review_source, review_ref
";
$likeStmt = $pdo->prepare($likeSql);
foreach ($params as $key => $value) {
    $likeStmt->bindValue($key, $value);
}
$likeStmt->execute();
$likeCounts = [];
foreach ($likeStmt->fetchAll(PDO::FETCH_ASSOC) as $like) {
    $likeCounts[$like['review_source'] . ':' . $like['review_ref']] = (int)$like['total'];
}

foreach ($reviews as &$review) {
    $likeKey = $review['source'] . ':' . $review['review_ref'];
    $review['likes_count'] = $likeCounts[$likeKey] ?? 0;
}
unset($review);

usort($reviews, function ($a, $b) use ($sort) {
    $ra = $a['rating'] ?? 0;
    $rb = $b['rating'] ?? 0;
    $ta = $a['timestamp'] ?? 0;
    $tb = $b['timestamp'] ?? 0;
    $la = $a['likes_count'] ?? 0;
    $lb = $b['likes_count'] ?? 0;

    return match ($sort) {
        'old' => $ta <=> $tb,
        'new' => $tb <=> $ta,
        'high' => $rb <=> $ra,
        'low' => $ra <=> $rb,
        'likes' => ($lb <=> $la) ?: ($tb <=> $ta),
        default => $tb <=> $ta,
    };
});

$total = count($reviews);
$totalPages = max(1, (int)ceil($total / $perPage));
$chunk = array_slice($reviews, $offset, $perPage);

json_response([
    'success' => true,
    'reviews' => $chunk,
    'total' => $total,
    'page' => $page,
    'per_page' => $perPage,
    'total_pages' => $totalPages
]);
