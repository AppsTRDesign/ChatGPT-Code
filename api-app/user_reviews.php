<?php
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');
$userId = (int)($_GET['user_id'] ?? 0);
if ($userId <= 0) {
    json_response(['error' => 'missing_user'], 422);
}
$page = max(1, (int)($_GET['s'] ?? 1));
$limit = (int)($_GET['limit'] ?? 10);
$limit = max(1, min(50, $limit));
$replyStatus = $_GET['reply_status'] ?? null;
$statusFilter = $_GET['status'] ?? null;

$pdo = get_pdo();

$fetchAll = $replyStatus !== null;
if ($fetchAll) {
    $stmt = $pdo->prepare(
        'SELECT ur.*, p.name AS place_name, p.business_image
         FROM user_reviews ur
         LEFT JOIN places p ON p.id = ur.place_id
         WHERE ur.user_id = ?
         ORDER BY ur.created_at DESC'
    );
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM user_reviews WHERE user_id = ?');
    $countStmt->execute([$userId]);
    $total = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $limit));
    $offset = ($page - 1) * $limit;

    $stmt = $pdo->prepare(
        'SELECT ur.*, p.name AS place_name, p.business_image
         FROM user_reviews ur
         LEFT JOIN places p ON p.id = ur.place_id
         WHERE ur.user_id = ?
         ORDER BY ur.created_at DESC
         LIMIT ? OFFSET ?'
    );
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$replyMap = [];
$ids = array_map(fn($row) => (string)$row['id'], $rows);
if (!empty($ids)) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $replyStmt = $pdo->prepare(
        "SELECT * FROM review_replies WHERE review_source = 'user' AND review_ref IN ($placeholders)"
    );
    $replyStmt->execute($ids);
    foreach ($replyStmt->fetchAll(PDO::FETCH_ASSOC) as $reply) {
        $replyMap[(string)$reply['review_ref']] = $reply;
    }
}

$statusFilter = $statusFilter ? strtolower($statusFilter) : null;
if ($statusFilter === 'approved' || $statusFilter === 'pending') {
    $rows = array_values(array_filter($rows, function ($row) use ($statusFilter) {
        return ($row['status'] ?? '') === $statusFilter;
    }));
}

$replyStatus = $replyStatus ? strtolower($replyStatus) : null;
if ($replyStatus === 'answered' || $replyStatus === 'unanswered') {
    $rows = array_values(array_filter($rows, function ($row) use ($replyStatus, $replyMap) {
        $reply = $replyMap[(string)$row['id']] ?? null;
        $hasReply = !empty($reply) && !empty($reply['reply_text']);
        return $replyStatus === 'answered' ? $hasReply : !$hasReply;
    }));
}

$total = count($rows);
$totalPages = max(1, (int)ceil($total / $limit));
$offset = ($page - 1) * $limit;
$rows = array_slice($rows, $offset, $limit);

$payload = [];
foreach ($rows as $row) {
    $reply = $replyMap[(string)$row['id']] ?? null;
    $payload[] = [
        'id' => (int)$row['id'],
        'place_id' => (int)$row['place_id'],
        'place_name' => $row['place_name'] ?? '',
        'place_image' => $row['business_image'] ?? null,
        'rating' => (int)$row['rating'],
        'text' => $row['review_text'],
        'text_extra' => decode_json($row['text_extra'] ?? ''),
        'created_at' => $row['created_at'],
        'status' => $row['status'],
        'reply' => $reply
    ];
}

json_response([
    'success' => true,
    'reviews' => $payload,
    'total' => $total,
    'total_pages' => $totalPages,
    'page' => $page
]);
