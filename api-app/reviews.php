<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['place_id']) || empty($data['name']) || empty($data['rating'])) {
    echo json_encode(['error' => 'missing_fields']);
    exit;
}

$pdo = get_pdo();

$stmt = $pdo->prepare("
    INSERT INTO user_reviews (place_id, user_id, status, author_name, email, rating, review_text, text_extra, review_photo_urls, created_at)
    VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?, NOW())
");

$textExtra = !empty($data['text_extra']) ? json_encode($data['text_extra'], JSON_UNESCAPED_UNICODE) : null;
$photoUrls = !empty($data['review_photo_urls']) ? json_encode($data['review_photo_urls'], JSON_UNESCAPED_UNICODE) : null;

$stmt->execute([
    $data['place_id'],
    0,
    $data['name'],
    $data['email'] ?? null,
    $data['rating'],
    $data['review'] ?? null,
    $textExtra,
    $photoUrls
]);

echo json_encode(['success' => true]);
