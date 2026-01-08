<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/helpers.php';

$city = isset($_GET['city']) ? trim($_GET['city']) : null;
if (!$city) {
    echo json_encode(['error' => 'missing_city']);
    exit;
}

$sql = "
SELECT business_type,
       category_slug,
       COUNT(*) as total
FROM places
WHERE city_slug = ?
GROUP BY business_type, category_slug
ORDER BY total DESC
";

$pdo = get_pdo();

$stmt = $pdo->prepare($sql);
$stmt->execute([$city]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'city' => $city,
    'categories' => $rows
]);
