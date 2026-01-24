<?php
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$user = require_user_payload($data);

try {
    $pdo = get_pdo();

    if (($data['type'] ?? '') === 'review_like') {
        $placeId = (int)($data['place_id'] ?? 0);
        $source = trim((string)($data['source'] ?? ''));
        $reviewRef = trim((string)($data['review_ref'] ?? ''));
        $action = strtolower(trim((string)($data['action'] ?? '')));

        if ($placeId <= 0 || $reviewRef === '' || ($source !== 'google' && $source !== 'user')) {
            http_response_code(422);
            echo json_encode(['error' => 'invalid_payload']);
            exit;
        }

        if ($action !== 'like' && $action !== 'unlike') {
            http_response_code(422);
            echo json_encode(['error' => 'invalid_action']);
            exit;
        }

        $pStmt = $pdo->prepare("SELECT id FROM places WHERE id = ? LIMIT 1");
        $pStmt->execute([$placeId]);
        if (!$pStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'place_not_found']);
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

        if ($action === 'like') {
            $checkStmt = $pdo->prepare("
                SELECT id FROM comments_like
                WHERE place_id = ? AND review_source = ? AND review_ref = ? AND user_id = ?
                LIMIT 1
            ");
            $checkStmt->execute([$placeId, $source, $reviewRef, (int)$user['id']]);
            if (!$checkStmt->fetch()) {
                $likeStmt = $pdo->prepare("
                    INSERT INTO comments_like
                        (place_id, user_id, review_source, review_ref, created_at)
                    VALUES
                        (?, ?, ?, ?, NOW())
                ");
                $likeStmt->execute([$placeId, (int)$user['id'], $source, $reviewRef]);
            }
        } else {
            $deleteStmt = $pdo->prepare("
                DELETE FROM comments_like
                WHERE place_id = ? AND review_source = ? AND review_ref = ? AND user_id = ?
            ");
            $deleteStmt->execute([$placeId, $source, $reviewRef, (int)$user['id']]);
        }

        $countStmt = $pdo->prepare("
            SELECT COUNT(*) FROM comments_like
            WHERE place_id = ? AND review_source = ? AND review_ref = ?
        ");
        $countStmt->execute([$placeId, $source, $reviewRef]);
        $likesCount = (int)$countStmt->fetchColumn();

        $likedByUser = false;
        if ($action === 'like') {
            $likedByUser = true;
        } else {
            $likedStmt = $pdo->prepare("
                SELECT 1 FROM comments_like
                WHERE place_id = ? AND review_source = ? AND review_ref = ? AND user_id = ?
                LIMIT 1
            ");
            $likedStmt->execute([$placeId, $source, $reviewRef, (int)$user['id']]);
            $likedByUser = (bool)$likedStmt->fetchColumn();
        }

        echo json_encode([
            'success' => true,
            'likes_count' => $likesCount,
            'liked_by_user' => $likedByUser
        ]);
        exit;
    }

    if (($data['type'] ?? '') === 'place_favorite') {
        $placeId = (int)($data['place_id'] ?? 0);
        $action = strtolower(trim((string)($data['action'] ?? '')));

        if ($placeId <= 0) {
            http_response_code(422);
            echo json_encode(['error' => 'invalid_payload']);
            exit;
        }

        if ($action !== 'add' && $action !== 'remove') {
            http_response_code(422);
            echo json_encode(['error' => 'invalid_action']);
            exit;
        }

        $pStmt = $pdo->prepare("SELECT id FROM places WHERE id = ? LIMIT 1");
        $pStmt->execute([$placeId]);
        if (!$pStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'place_not_found']);
            exit;
        }

        if ($action === 'add') {
            $checkStmt = $pdo->prepare("
                SELECT id FROM place_favorites
                WHERE place_id = ? AND user_id = ?
                LIMIT 1
            ");
            $checkStmt->execute([$placeId, (int)$user['id']]);
            if (!$checkStmt->fetch()) {
                $favStmt = $pdo->prepare("
                    INSERT INTO place_favorites (place_id, user_id, created_at)
                    VALUES (?, ?, NOW())
                ");
                $favStmt->execute([$placeId, (int)$user['id']]);
            }
        } else {
            $deleteStmt = $pdo->prepare("
                DELETE FROM place_favorites
                WHERE place_id = ? AND user_id = ?
            ");
            $deleteStmt->execute([$placeId, (int)$user['id']]);
        }

        $favCheck = $pdo->prepare("
            SELECT 1 FROM place_favorites
            WHERE place_id = ? AND user_id = ?
            LIMIT 1
        ");
        $favCheck->execute([$placeId, (int)$user['id']]);
        $isFavorite = (bool)$favCheck->fetchColumn();

        echo json_encode([
            'success' => true,
            'is_favorite' => $isFavorite
        ]);
        exit;
    }

    if (($data['type'] ?? '') === 'review_reply') {
        if (empty($user['role'])) {
            http_response_code(422);
            echo json_encode(['error' => 'missing_role']);
            exit;
        }

        $placeId = (int)($data['place_id'] ?? 0);
        $source = trim((string)($data['source'] ?? ''));
        $reviewRef = trim((string)($data['review_ref'] ?? ''));
        $replyText = trim((string)($data['reply_text'] ?? ''));

        if ($placeId <= 0 || $reviewRef === '' || ($source !== 'google' && $source !== 'user')) {
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

        if ($replyText === '') {
            $deleteStmt = $pdo->prepare("
                DELETE FROM review_replies
                WHERE place_id = :place_id
                  AND review_source = :src
                  AND review_ref = :ref
            ");
            $deleteStmt->execute([
                ':place_id' => $placeId,
                ':src' => $source,
                ':ref' => $reviewRef
            ]);
            echo json_encode([
                'status' => 'reply_deleted',
                'place_id' => $placeId,
                'source' => $source,
                'review_ref' => $reviewRef
            ]);
            exit;
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
    $name = trim((string)($data['user_name'] ?? $data['name'] ?? ($user['name'] ?? '')));
    $email = trim((string)($data['user_email'] ?? $data['email'] ?? ($user['email'] ?? '')));

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
        SELECT id, created_at
        FROM user_reviews
        WHERE place_id = ? AND user_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $check->execute([$placeId, (int)$user['id']]);
    $lastReview = $check->fetch(PDO::FETCH_ASSOC);
    if ($lastReview) {
        $lastCreated = strtotime($lastReview['created_at'] ?? '');
        $cooldownSeconds = 7 * 24 * 60 * 60;
        if ($lastCreated && (time() - $lastCreated) < $cooldownSeconds) {
            $nextAllowed = date('Y-m-d H:i:s', $lastCreated + $cooldownSeconds);
            http_response_code(409);
            echo json_encode([
                'error' => 'review_cooldown',
                'next_review_at' => $nextAllowed
            ]);
            exit;
        }
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
