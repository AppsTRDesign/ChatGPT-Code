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
            id,
            name,
            city_name,
            city_slug,
            formatted_address,
            rating,
            user_ratings_total,
            business_image,
            latitude,
            longitude,
            created_at
        FROM places
        WHERE country_code = :code
        ORDER BY created_at DESC
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
