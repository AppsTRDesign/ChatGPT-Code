<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . '/../includes/helpers.php';

$countryCode = $_GET['country_code'] ?? null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($limit < 1 || $limit > 50) $limit = 10;

$response = [
    "success" => false,
    "places" => [],
    "error" => null
];

if (!$countryCode) {
    $response['error'] = "country_code is required";
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
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
            (COALESCE(JSON_LENGTH(p.reviews),0)+COALESCE(ur.user_review_count,0)) AS total_reviews,
            CASE WHEN (COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0))>0
              THEN (COALESCE(p.rating,0)*COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_sum,0))
                   /(COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0))
              ELSE COALESCE(p.rating,0) END AS combined_rating
        FROM places p
        LEFT JOIN (SELECT place_id,COUNT(*) user_review_count,SUM(rating) user_review_sum FROM user_reviews GROUP BY place_id) ur ON ur.place_id=p.id
        WHERE p.country_code = :code AND p.status = 1
        ORDER BY combined_rating DESC, p.user_ratings_total DESC
        LIMIT :limit
    ";

    $pdo = get_pdo();
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':code', strtoupper($countryCode));
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['places'] = $rows;

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
