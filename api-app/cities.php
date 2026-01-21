<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . '/../includes/helpers.php';

$countryCode = $_GET['country_code'] ?? null;

$response = [
    "success" => false,
    "cities" => [],
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
            city_name AS city,
            city_slug AS slug,
            COUNT(*) AS total_places
        FROM places
        WHERE country_code = :code
          AND city_name IS NOT NULL
          AND city_name <> ''
          AND city_slug IS NOT NULL
        GROUP BY city_slug, city_name
        ORDER BY city_name ASC
    ";

    $pdo = get_pdo();
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':code' => strtoupper($countryCode)]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['cities'] = $rows;

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
