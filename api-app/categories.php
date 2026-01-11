<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/helpers.php';

$city = isset($_GET['city']) ? trim($_GET['city']) : null;
$country = isset($_GET['country']) ? trim($_GET['country']) : null;

$sql = "
SELECT business_type,
       category_slug,
       COUNT(*) as total
FROM places
WHERE status = 1
";

$params = [];
if ($city) {
    $sql .= " AND (city_slug = :city OR city_name = :city)";
    $params[':city'] = $city;
}
if ($country) {
    $sql .= " AND (UPPER(country_code) = :country_code OR country_slug = :country_slug OR country_name = :country_name)";
    $params[':country_code'] = strtoupper($country);
    $params[':country_slug'] = $country;
    $params[':country_name'] = $country;
}

$sql .= "
GROUP BY business_type, category_slug
ORDER BY total DESC
";

$pdo = get_pdo();

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'city' => $city,
    'country' => $country,
    'categories' => $rows
]);
