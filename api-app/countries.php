<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . '/../includes/helpers.php';

$response = [
    "success" => false,
    "countries" => [],
    "error" => null
];

try {
    $sql = "
        SELECT 
            UPPER(country_code) AS code,
            country_name AS name,
            COUNT(*) AS total_places
        FROM places
        WHERE country_code IS NOT NULL
          AND country_name IS NOT NULL
        GROUP BY UPPER(country_code), country_name
        ORDER BY total_places DESC
    ";

    $pdo = get_pdo();
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['countries'] = $rows;

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
