<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/helpers.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo json_encode(['error' => 'missing_id']);
    exit;
}

$pdo = get_pdo();

$stmt = $pdo->prepare("
    SELECT
        p.*,
        COALESCE(NULLIF(p.view_total,0), pv.visit_count, 0) AS views,
        (COALESCE(JSON_LENGTH(p.reviews),0)+COALESCE(ur.user_review_count,0)) AS total_votes,
        (COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0)) AS total_reviews,
        CASE WHEN (COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0))>0
          THEN (COALESCE(p.rating,0)*COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_sum,0))
               /(COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0))
          ELSE COALESCE(p.rating,0) END AS combined_rating
    FROM places p
    LEFT JOIN (
        SELECT place_id,COUNT(*) visit_count
        FROM place_visits
        GROUP BY place_id
    ) pv ON pv.place_id=p.id
    LEFT JOIN (
        SELECT place_id,COUNT(*) user_review_count,SUM(rating) user_review_sum
        FROM user_reviews
        GROUP BY place_id
    ) ur ON ur.place_id=p.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$place = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$place) {
    echo json_encode(['error' => 'not_found']);
    exit;
}

if (empty($place['description'])) {
    $place['description'] = generateBusinessDesc(
        $place['name'] ?? '',
        $place['business_type'] ?? '',
        $place['city_name'] ?? '',
        $place['town_name'] ?? null,
        $place['country_name'] ?? 'Türkiye',
        $place['combined_rating'] ?? null,
        $place['total_votes'] ?? null
    );
}

$place['current_status'] = getPlaceCurrentStatus((int)$place['id']);

$replyStmt = $pdo->prepare("
    SELECT *
    FROM review_replies
    WHERE place_id = ?
");
$replyStmt->execute([$id]);
$replyMap = [];
foreach ($replyStmt->fetchAll(PDO::FETCH_ASSOC) as $reply) {
    $replyMap[$reply['review_source'] . ':' . $reply['review_ref']] = $reply;
}

$googleReviews = decode_json($place['reviews'] ?? '');
if (is_array($googleReviews)) {
    foreach ($googleReviews as &$gr) {
        $googleRef = sha1(
            ($gr['author_name'] ?? '') .
            ($gr['relative_time'] ?? '') .
            ($gr['text'] ?? '')
        );
        $replyKey = 'google:' . $googleRef;
        $gr['reply'] = $replyMap[$replyKey] ?? null;
    }
    unset($gr);
    $place['reviews'] = $googleReviews;
}

$tables = [
    'hours' => 'place_hours',
    'social_links' => 'place_social_links',
    'services' => 'place_services',
    'service_categories' => 'place_service_categories',
    'service_items' => 'place_service_items',
    'service_item_prices' => 'place_service_item_prices',
    'galleries' => 'place_galleries',
    'gallery_images' => 'place_gallery_images',
    'knows_about' => 'place_knows_about',
];

$result = [ "place" => $place ];

foreach ($tables as $key => $tbl) {
    $q = $pdo->prepare("SELECT * FROM {$tbl} WHERE place_id = ?");
    $q->execute([$id]);
    $result[$key] = $q->fetchAll(PDO::FETCH_ASSOC);
}

$reviewStmt = $pdo->prepare("
    SELECT
        ur.id,
        ur.author_name,
        ur.rating,
        ur.review_text,
        ur.text_extra,
        ur.review_photo_urls,
        ur.created_at,
        u.profile_photo
    FROM user_reviews ur
    LEFT JOIN users u ON u.id = ur.user_id
    WHERE ur.place_id = ? AND ur.status = 'approved'
    ORDER BY ur.created_at DESC
");
$reviewStmt->execute([$id]);
$reviews = [];
foreach ($reviewStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $replyKey = 'user:' . $row['id'];
    $reply = $replyMap[$replyKey] ?? null;
    $reviews[] = [
        'id' => (int)$row['id'],
        'author_name' => $row['author_name'],
        'rating' => isset($row['rating']) ? (int)$row['rating'] : null,
        'review_text' => $row['review_text'],
        'text_extra' => decode_json($row['text_extra'] ?? ''),
        'review_photo_urls' => decode_json($row['review_photo_urls'] ?? ''),
        'created_at' => $row['created_at'],
        'profile_photo_url' => $row['profile_photo'] ?: BASE_URL . '/assets/img/default-user.webp',
        'reply' => $reply
    ];
}
$result['user_reviews'] = $reviews;

echo json_encode($result);
