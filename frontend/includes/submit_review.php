<?php
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$rating = (int)($data['rating'] ?? 0);
$text = trim($data['text'] ?? '');
$textExtra = $data['text_extra'] ?? [];
$placeId = (int)($data['place_id'] ?? 0);

if (!$placeId || $name === '' || $rating < 1 || $rating > 5) {
    http_response_code(422);
    echo json_encode(['error' => 'invalid_payload']);
    exit;
}

try {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('INSERT INTO user_reviews (place_id, author_name, email, rating, review_text, text_extra) VALUES (:pid, :author, :email, :rating, :text, :extra)');
    $stmt->execute([
        ':pid' => $placeId,
        ':author' => $name,
        ':email' => $email,
        ':rating' => $rating,
        ':text' => $text,
        ':extra' => json_encode($textExtra, JSON_UNESCAPED_UNICODE),
    ]);
    echo json_encode(['status' => 'ok']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
