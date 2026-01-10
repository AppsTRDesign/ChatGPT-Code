<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . '/../includes/helpers.php';

$countryCode = $_GET['country_code'] ?? null;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : null;
$limitParam = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($perPage === null && $limitParam !== null) {
    $perPage = $limitParam;
}
if ($perPage === null || $perPage < 1 || $perPage > 1000) {
    $perPage = 10;
}
if ($page < 1) $page = 1;
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

try {
    $countSql = "SELECT COUNT(*) FROM places p WHERE p.country_code = :code AND p.status = 1";
    $pdo = get_pdo();
    $countStmt = $pdo->prepare($countSql);
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
            p.created_at,
            (COALESCE(JSON_LENGTH(p.reviews),0)+COALESCE(ur.user_review_count,0)) AS total_reviews,
            CASE WHEN (COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0))>0
              THEN (COALESCE(p.rating,0)*COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_sum,0))
                   /(COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0))
              ELSE COALESCE(p.rating,0) END AS combined_rating
        FROM places p
        LEFT JOIN (SELECT place_id,COUNT(*) user_review_count,SUM(rating) user_review_sum FROM user_reviews GROUP BY place_id) ur ON ur.place_id=p.id
        WHERE p.country_code = :code AND p.status = 1
        ORDER BY p.created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':code', strtoupper($countryCode));
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['places'] = $rows;

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
