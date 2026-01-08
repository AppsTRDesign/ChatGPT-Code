<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/helpers.php';

$city = $_GET['city'] ?? null;
$category = $_GET['category'] ?? null;

if (!$city || !$category) {
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
WHERE city_slug = ? AND category_slug = ?
ORDER BY rating DESC, user_ratings_total DESC
";

$pdo = get_pdo();

$stmt = $pdo->prepare($sql);
$stmt->execute([$city, $category]);
$places = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'city' => $city,
    'category' => $category,
    'places' => $places
]);
