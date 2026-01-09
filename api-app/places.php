<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/helpers.php';

$city = $_GET['city'] ?? null;
$category = $_GET['category'] ?? null;
$country = $_GET['country'] ?? null;

if (!$category) {
    echo json_encode(['error' => 'missing_params']);
    exit;
}

$sql = "
SELECT 
 id,
 name,
 formatted_address,
 latitude,
 longitude,
 rating,
 description,
 user_ratings_total,
 business_type,
 category_slug,
 opening_hours,
 busy_hours
FROM places
WHERE status = 1 AND category_slug = :category
";

$pdo = get_pdo();

$params = [':category' => $category];
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
$sql .= " ORDER BY rating DESC, user_ratings_total DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$places = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'city' => $city,
    'category' => $category,
    'country' => $country,
    'places' => $places
]);
