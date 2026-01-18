<?php
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');
require_login_json();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];

try {
    $pdo = get_pdo();

    if (($data['type'] ?? '') === 'review_reply') {
        $placeId = (int)($data['place_id'] ?? 0);
        $source = trim((string)($data['source'] ?? ''));
        $reviewRef = trim((string)($data['review_ref'] ?? ''));
        $replyText = trim((string)($data['reply_text'] ?? ''));

        if ($placeId <= 0 || $replyText === '' || $reviewRef === '' || ($source !== 'google' && $source !== 'user')) {
            http_response_code(422);
            echo json_encode(['error' => 'invalid_payload']);
            exit;
        }

        $pStmt = $pdo->prepare("SELECT id, claimed_by FROM places WHERE id = ? LIMIT 1");
        $pStmt->execute([$placeId]);
        $place = $pStmt->fetch(PDO::FETCH_ASSOC);

        if (!$place) {
            http_response_code(404);
            echo json_encode(['error' => 'place_not_found']);
            exit;
        }

        if (!can_edit_place($place, $user)) {
            http_response_code(403);
            echo json_encode(['error' => 'forbidden']);
            exit;
        }

        if ($source === 'user') {
            $rid = (int)$reviewRef;
            if ($rid <= 0) {
                http_response_code(422);
                echo json_encode(['error' => 'invalid_review_ref']);
                exit;
            }

            $rStmt = $pdo->prepare("SELECT id FROM user_reviews WHERE id = ? AND place_id = ? LIMIT 1");
            $rStmt->execute([$rid, $placeId]);
            if (!$rStmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'review_not_found']);
                exit;
            }

            $reviewRef = (string)$rid;
        }

        if ($source === 'google') {
            if (strlen($reviewRef) < 16 || strlen($reviewRef) > 190) {
                http_response_code(422);
                echo json_encode(['error' => 'invalid_review_ref']);
                exit;
            }
        }

        if (mb_strlen($replyText) > 2000) {
            http_response_code(422);
            echo json_encode(['error' => 'reply_too_long']);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO review_replies
                (place_id, review_source, review_ref, reply_text, replied_by, replied_role, created_at)
            VALUES
                (:place_id, :src, :ref, :txt, :uid, :role, NOW())
            ON DUPLICATE KEY UPDATE
                reply_text = VALUES(reply_text),
                replied_by = VALUES(replied_by),
                replied_role = VALUES(replied_role),
                updated_at = NOW()
        ");

        $stmt->execute([
            ':place_id' => $placeId,
            ':src' => $source,
            ':ref' => $reviewRef,
            ':txt' => $replyText,
            ':uid' => (int)$user['id'],
            ':role' => (string)$user['role'],
        ]);

        echo json_encode([
            'status' => 'reply_saved',
            'place_id' => $placeId,
            'source' => $source,
            'review_ref' => $reviewRef
        ]);
        exit;
    }

    $placeId = (int)($data['place_id'] ?? 0);
    $rating = (int)($data['rating'] ?? 0);
    $text = trim((string)($data['text'] ?? ''));
    $name = trim((string)($data['name'] ?? ($user['name'] ?? '')));
    $email = trim((string)($data['email'] ?? ($user['email'] ?? '')));

    $reviewPhotoUrls = $data['review_photo_urls'] ?? [];
    if (!is_array($reviewPhotoUrls)) $reviewPhotoUrls = [];
    $reviewPhotoUrls = array_slice(array_values(array_filter($reviewPhotoUrls)), 0, 10);

    $textExtra = $data['text_extra'] ?? [];
    if (!is_array($textExtra)) $textExtra = [];

    if (!$placeId || $rating < 1 || $rating > 5 || $text === '') {
        http_response_code(422);
        echo json_encode(['error' => 'invalid_payload']);
        exit;
    }

    $check = $pdo->prepare("
        SELECT id
        FROM user_reviews
        WHERE place_id = ? AND user_id = ?
        LIMIT 1
    ");
    $check->execute([$placeId, (int)$user['id']]);
    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'already_reviewed']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO user_reviews
            (place_id, user_id, status, author_name, email, rating, review_text, text_extra, review_photo_urls)
        VALUES
            (?, ?, 'pending', ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $placeId,
        (int)$user['id'],
        $name,
        $email,
        $rating,
        $text,
        json_encode($textExtra, JSON_UNESCAPED_UNICODE),
        json_encode($reviewPhotoUrls, JSON_UNESCAPED_UNICODE),
    ]);

    echo json_encode([
        'status' => 'review_pending',
        'place_id' => $placeId,
        'rating' => $rating
    ]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'server_error',
        'debug' => $e->getMessage()
    ]);
    exit;
}
