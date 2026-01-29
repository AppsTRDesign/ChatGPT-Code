<?php
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$userId = (int)($_GET['user_id'] ?? 0);
$page = max(1, (int)($_GET['s'] ?? 1));
$limit = (int)($_GET['limit'] ?? 10);
$limit = max(1, min(50, $limit));
$sort = $_GET['sort'] ?? 'new';
$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$lng = isset($_GET['lng']) ? (float)$_GET['lng'] : null;

if ($userId <= 0) {
    json_response(['error' => 'missing_user'], 422);
}

$pdo = get_pdo();

$distanceSelect = "NULL AS distance_m";
$distanceWhere = "";
$distanceParams = [];
$hasLatLng = is_finite($lat) && is_finite($lng);

if ($hasLatLng) {
    $distanceSelect = "
        ST_Distance_Sphere(
            POINT(CAST(p.longitude AS DECIMAL(10,6)), CAST(p.latitude AS DECIMAL(10,6))),
            POINT(:lng, :lat)
        ) AS distance_m
    ";
    $distanceParams[':lat'] = $lat;
    $distanceParams[':lng'] = $lng;
}

$orderBy = 'pf.created_at DESC';
switch ($sort) {
    case 'old':
        $orderBy = 'pf.created_at ASC';
        break;
    case 'name_asc':
        $orderBy = 'p.name ASC';
        break;
    case 'name_desc':
        $orderBy = 'p.name DESC';
        break;
    case 'rating_desc':
        $orderBy = 'combined_rating DESC';
        break;
    case 'views':
        $orderBy = 'views DESC';
        break;
    case 'distance':
        if ($hasLatLng) {
            $orderBy = 'distance_m ASC';
        }
        break;
}

$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM place_favorites pf
    WHERE pf.user_id = ?
");
$countStmt->execute([$userId]);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $limit));
$offset = ($page - 1) * $limit;

$sql = "
    SELECT
        p.id,
        p.name,
        p.formatted_address,
        p.latitude,
        p.longitude,
        p.rating,
        p.description,
        p.user_ratings_total,
        p.business_type,
        p.category_slug,
        p.business_image,
        p.city_name,
        p.city_slug,
        COALESCE(NULLIF(p.view_total,0), pv.visit_count, 0) AS views,
        (COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0)) AS total_reviews,
        CASE WHEN (COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0))>0
          THEN (COALESCE(p.rating,0)*COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_sum,0))
               /(COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0))
          ELSE COALESCE(p.rating,0) END AS combined_rating,
        {$distanceSelect}
    FROM place_favorites pf
    JOIN places p ON p.id = pf.place_id
    LEFT JOIN (
        SELECT place_id,COUNT(*) visit_count
        FROM place_visits
        GROUP BY place_id
    ) pv ON pv.place_id = p.id
    LEFT JOIN (
        SELECT place_id,COUNT(*) user_review_count,SUM(rating) user_review_sum
        FROM user_reviews
        GROUP BY place_id
    ) ur ON ur.place_id = p.id
    WHERE pf.user_id = :user_id
    ORDER BY {$orderBy}
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($distanceParams as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();

$places = $stmt->fetchAll(PDO::FETCH_ASSOC);

json_response([
    'success' => true,
    'places' => $places,
    'total' => $total,
    'total_pages' => $totalPages,
    'page' => $page
]);
