<?php
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/json; charset=utf-8');
require_login_json();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];

$placeId = (int)($data['place_id'] ?? 0);
$rating = (int)($data['rating'] ?? 0);
$text = trim($data['text'] ?? '');
$name = trim($data['name'] ?? ($user['name'] ?? ''));
$email = trim($data['email'] ?? ($user['email'] ?? ''));

$reviewPhotoUrls = $data['review_photo_urls'] ?? [];
if (!is_array($reviewPhotoUrls)) $reviewPhotoUrls = [];
$reviewPhotoUrls = array_slice(array_values(array_filter($reviewPhotoUrls)), 0, 10);

$textExtra = $data['text_extra'] ?? [];
if (!is_array($textExtra)) {
    $textExtra = [];
}

if (!$placeId || $rating < 1 || $rating > 5 || $text === '') {
    http_response_code(422);
    echo json_encode(['error' => 'invalid_payload']);
    exit;
}

try {
    $pdo = get_pdo();

    $check = $pdo->prepare(
        "SELECT id FROM user_reviews 
         WHERE place_id = ? AND user_id = ? LIMIT 1"
    );
    $check->execute([$placeId, $user['id']]);
    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'already_reviewed']);
        exit;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO user_reviews
        (place_id, user_id, status, author_name, email, rating, review_text, text_extra, review_photo_urls)
        VALUES
        (?, ?, 'pending', ?, ?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $placeId,
        $user['id'],
        $name,
        $email,
        $rating,
        $text,
        json_encode($textExtra, JSON_UNESCAPED_UNICODE),
        json_encode($reviewPhotoUrls, JSON_UNESCAPED_UNICODE)
    ]);

    echo json_encode(['status' => 'review_pending', 'place_id' => $placeId, 'rating' => $rating]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'server_error',
        'debug' => $e->getMessage()
    ]);
}
