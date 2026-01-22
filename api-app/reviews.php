<?php
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$placeId = (int)($_GET['place_id'] ?? 0);
$userId = (int)($_GET['user_id'] ?? 0);
$page = max(1, (int)($_GET['s'] ?? 1));
$limit = defined('DETAIL_REVIEW_LIMIT') ? DETAIL_REVIEW_LIMIT : 10;
$sort = $_GET['sort'] ?? 'new';
$status = $_GET['status'] ?? null;

if ($placeId <= 0) {
    echo json_encode([
        'total_pages' => 1,
        'total' => 0,
        'reviews' => []
    ]);
    exit;
}

$pdo = get_pdo();

$stmt = $pdo->prepare("SELECT id, reviews, claimed_by FROM places WHERE id = ?");
$stmt->execute([$placeId]);
$place = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$place) {
    echo json_encode([
        'total_pages' => 1,
        'total' => 0,
        'reviews' => []
    ]);
    exit;
}

$rStmt = $pdo->prepare("
    SELECT *
    FROM review_replies
    WHERE place_id = ?
");
$rStmt->execute([$placeId]);

$replyMap = [];
foreach ($rStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $replyMap[$r['review_source'] . ':' . $r['review_ref']] = $r;
}

$likeStmt = $pdo->prepare("
    SELECT review_source, review_ref, user_id
    FROM comments_like
    WHERE place_id = ?
");
$likeStmt->execute([$placeId]);
$likeCounts = [];
$likedByUser = [];
foreach ($likeStmt->fetchAll(PDO::FETCH_ASSOC) as $like) {
    $key = $like['review_source'] . ':' . $like['review_ref'];
    $likeCounts[$key] = ($likeCounts[$key] ?? 0) + 1;
    if ($userId > 0 && (int)$like['user_id'] === $userId) {
        $likedByUser[$key] = true;
    }
}

$reviews = [];

$googleReviews = decode_json($place['reviews'] ?? '');

foreach ($googleReviews as $gr) {

    $ts = 0;
    if (!empty($gr['relative_time'])) {
        $ts = strtotime($gr['relative_time']) ?: 0;
    }

    $googleRef = sha1(
        ($gr['author_name'] ?? '') .
        ($gr['relative_time'] ?? '') .
        ($gr['text'] ?? '')
    );

    $replyKey = 'google:' . $googleRef;
    $reply = $replyMap[$replyKey] ?? null;

    $reviews[] = [
        'source' => 'google',
        'review_ref' => $googleRef,
        'id' => null,
        'author_name' => $gr['author_name'] ?? 'Google Kullanıcısı',
        'rating' => isset($gr['rating']) ? (float)$gr['rating'] : null,
        'timestamp' => $ts,
        'relative_time' => $gr['relative_time'] ?? '',
        'text' => $gr['text'] ?? '',
        'profile_photo_url' => $gr['profile_photo_url'] ?? '',
        'text_extra' => [],
        'review_photo_urls' => [],
        'likes_count' => $likeCounts[$replyKey] ?? 0,
        'liked_by_user' => !empty($likedByUser[$replyKey]),
        'reply' => $reply
    ];
}

$uStmt = $pdo->prepare("
    SELECT *
    FROM user_reviews
    WHERE place_id = ? AND status = 'approved'
");
$uStmt->execute([$placeId]);

foreach ($uStmt->fetchAll(PDO::FETCH_ASSOC) as $ur) {

    $logUser = get_user_by_id((int)$ur['user_id']);
    $ts = strtotime($ur['created_at']) ?: 0;

    $replyKey = 'user:' . $ur['id'];
    $reply = $replyMap[$replyKey] ?? null;

    $reviews[] = [
        'source' => 'user',
        'review_ref' => (string)$ur['id'],
        'id' => (int)$ur['id'],
        'author_name' => $ur['author_name'] ?: 'Kullanıcı',
        'rating' => (float)$ur['rating'],
        'timestamp' => $ts,
        'relative_time' => $ur['created_at'],
        'text' => $ur['review_text'],
        'profile_photo_url' => !empty($logUser['profile_photo'])
            ? $logUser['profile_photo']
            : BASE_URL . '/assets/img/default-user.webp',
        'text_extra' => decode_json($ur['text_extra'] ?? ''),
        'review_photo_urls' => decode_json($ur['review_photo_urls'] ?? ''),
        'likes_count' => $likeCounts['user:' . $ur['id']] ?? 0,
        'liked_by_user' => !empty($likedByUser['user:' . $ur['id']]),
        'reply' => $reply
    ];
}

usort($reviews, function ($a, $b) use ($sort) {
    $ra = $a['rating'] ?? 0;
    $rb = $b['rating'] ?? 0;
    $ta = $a['timestamp'] ?? 0;
    $tb = $b['timestamp'] ?? 0;
    $la = $a['likes_count'] ?? 0;
    $lb = $b['likes_count'] ?? 0;

    return match ($sort) {
        'old' => $ta <=> $tb,
        'new' => $tb <=> $ta,
        'high' => $rb <=> $ra,
        'low' => $ra <=> $rb,
        'likes' => ($lb <=> $la) ?: ($tb <=> $ta),
        default => $tb <=> $ta,
    };
});

$status = $status ? strtolower($status) : null;
if ($status === 'answered' || $status === 'unanswered') {
    $reviews = array_values(array_filter($reviews, function ($review) use ($status) {
        $hasReply = !empty($review['reply']) && !empty($review['reply']['reply_text']);
        return $status === 'answered' ? $hasReply : !$hasReply;
    }));
}

$total = count($reviews);
$totalPages = max(1, (int)ceil($total / $limit));
$offset = ($page - 1) * $limit;
$chunk = array_slice($reviews, $offset, $limit);

echo json_encode([
    'total_pages' => $totalPages,
    'total' => $total,
    'reviews' => $chunk
]);
