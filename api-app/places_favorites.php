<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . '/../includes/helpers.php';

$countryCode = $_GET['country_code'] ?? null;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : null;
$limitParam = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$sort = $_GET['sort'] ?? 'favorites_week';
$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$lng = isset($_GET['lng']) ? (float)$_GET['lng'] : null;

if ($perPage === null && $limitParam !== null) {
    $perPage = $limitParam;
}
if ($perPage === null || $perPage < 1 || $perPage > 1000) {
    $perPage = 10;
}
if ($page < 1) {
    $page = 1;
}
$offset = ($page - 1) * $perPage;

$response = [
    "success" => false,
    "places" => [],
    "total" => 0,
    "page" => $page,
    "per_page" => $perPage,
    "error" => null
];

if (!$countryCode) {
    $response['error'] = "country_code is required";
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = get_pdo();
$hasLatLng = is_finite($lat) && is_finite($lng);
$distanceSelect = "NULL AS distance_m";
$distanceParams = [];
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

$orderBy = 'favorites_last_week DESC, favorites_last_11_weeks DESC';
switch ($sort) {
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

try {
    $countStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT pf.place_id)
        FROM place_favorites pf
        JOIN places p ON p.id = pf.place_id
        WHERE pf.created_at >= DATE_SUB(NOW(), INTERVAL 11 WEEK)
          AND p.country_code = :code
          AND p.status = 1
    ");
    $countStmt->bindValue(':code', strtoupper($countryCode));
    $countStmt->execute();
    $response['total'] = (int)$countStmt->fetchColumn();

    $sql = "
        SELECT
            p.id,
            p.name,
            p.city_name,
            p.city_slug,
            p.formatted_address,
            p.rating,
            p.user_ratings_total,
            p.business_image,
            p.latitude,
            p.longitude,
            COALESCE(NULLIF(p.view_total,0), pv.visit_count, 0) AS views,
            (COALESCE(JSON_LENGTH(p.reviews),0)+COALESCE(ur.user_review_count,0)) AS total_reviews,
            CASE WHEN (COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0))>0
              THEN (COALESCE(p.rating,0)*COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_sum,0))
                   /(COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0))
              ELSE COALESCE(p.rating,0) END AS combined_rating,
            {$distanceSelect},
            SUM(CASE WHEN pf.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK) THEN 1 ELSE 0 END) AS favorites_last_week,
            COUNT(*) AS favorites_last_11_weeks
        FROM place_favorites pf
        JOIN places p ON p.id = pf.place_id
        LEFT JOIN (
            SELECT place_id,COUNT(*) visit_count FROM place_visits GROUP BY place_id
        ) pv ON pv.place_id=p.id
        LEFT JOIN (
            SELECT place_id,COUNT(*) user_review_count,SUM(rating) user_review_sum FROM user_reviews GROUP BY place_id
        ) ur ON ur.place_id=p.id
        WHERE pf.created_at >= DATE_SUB(NOW(), INTERVAL 11 WEEK)
          AND p.country_code = :code
          AND p.status = 1
        GROUP BY p.id
        ORDER BY {$orderBy}
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':code', strtoupper($countryCode));
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($distanceParams as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['places'] = $rows;
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
