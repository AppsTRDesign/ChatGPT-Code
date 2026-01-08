<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/helpers.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo json_encode(['error' => 'missing_id']);
    exit;
}

$pdo = get_pdo();

$stmt = $pdo->prepare("SELECT * FROM places WHERE id = ?");
$stmt->execute([$id]);
$place = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$place) {
    echo json_encode(['error' => 'not_found']);
    exit;
}

$tables = [
    'hours' => 'place_hours',
    'social_links' => 'place_social_links',
    'services' => 'place_services',
    'service_categories' => 'place_service_categories',
    'service_items' => 'place_service_items',
    'service_item_prices' => 'place_service_item_prices',
    'galleries' => 'place_galleries',
    'gallery_images' => 'place_gallery_images',
    'knows_about' => 'place_knows_about',
    'google_reviews' => 'reviews',
    'user_reviews' => 'user_reviews',
];

$result = [ "place" => $place ];

foreach ($tables as $key => $tbl) {
    $q = $pdo->prepare("SELECT * FROM {$tbl} WHERE place_id = ?");
    $q->execute([$id]);
    $result[$key] = $q->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($result);
