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
 p.opening_hours,
 p.busy_hours,
 COALESCE(NULLIF(p.view_total,0), pv.visit_count, 0) AS views
FROM places p
LEFT JOIN (SELECT place_id,COUNT(*) visit_count FROM place_visits GROUP BY place_id) pv ON pv.place_id=p.id
WHERE p.status = 1 AND p.category_slug = :category
";

$pdo = get_pdo();

$params = [':category' => $category];
if ($city) {
    $sql .= " AND (p.city_slug = :city OR p.city_name = :city)";
    $params[':city'] = $city;
}
if ($country) {
    $sql .= " AND (UPPER(p.country_code) = :country_code OR p.country_slug = :country_slug OR p.country_name = :country_name)";
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
