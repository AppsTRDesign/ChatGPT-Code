<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . '/../includes/helpers.php';

$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$countryCode = $_GET['country_code'] ?? null;

if ($perPage < 1 || $perPage > 100) {
    $perPage = 10;
}
if ($page < 1) {
    $page = 1;
}
$offset = ($page - 1) * $perPage;

$pdo = get_pdo();
$params = [];
$where = "ur.status = 'approved'";
if ($countryCode) {
    $where .= " AND p.country_code = :code";
    $params[':code'] = strtoupper($countryCode);
}

$countSql = "
    SELECT COUNT(*)
    FROM user_reviews ur
    JOIN places p ON p.id = ur.place_id
    WHERE {$where}
";
$countStmt = $pdo->prepare($countSql);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$total = (int)$countStmt->fetchColumn();

$sql = "
    SELECT
        ur.id,
        ur.place_id,
        ur.author_name,
        ur.rating,
        ur.review_text,
        ur.created_at,
        p.name AS place_name,
        p.business_image,
        p.business_type,
        p.city_name
    FROM user_reviews ur
    JOIN places p ON p.id = ur.place_id
    WHERE {$where}
    ORDER BY ur.created_at DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

json_response([
    'success' => true,
    'reviews' => $rows,
    'total' => $total,
    'page' => $page,
    'per_page' => $perPage
]);
