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
        (COALESCE(JSON_LENGTH(p.reviews),0)+COALESCE(ur.user_review_count,0)) AS total_votes,
        CASE WHEN (COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0))>0
          THEN (COALESCE(p.rating,0)*COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_sum,0))
               /(COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0))
          ELSE COALESCE(p.rating,0) END AS combined_rating
    FROM places p
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
    'user_reviews' => 'user_reviews',
];

$result = [ "place" => $place ];

foreach ($tables as $key => $tbl) {
    $orderBy = $key === 'user_reviews' ? ' ORDER BY created_at DESC' : '';
    $q = $pdo->prepare("SELECT * FROM {$tbl} WHERE place_id = ?{$orderBy}");
    $q->execute([$id]);
    $result[$key] = $q->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($result);
