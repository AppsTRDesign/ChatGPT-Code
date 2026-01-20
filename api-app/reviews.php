<?php
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$placeId = (int)($_GET['place_id'] ?? 0);
$page = max(1, (int)($_GET['s'] ?? 1));
$limit = defined('DETAIL_REVIEW_LIMIT') ? DETAIL_REVIEW_LIMIT : 10;
$sort = $_GET['sort'] ?? 'new';

if ($placeId <= 0) {
    echo json_encode([
        'html' => '<div class="text-muted">Geçersiz istek</div>',
        'total_pages' => 1,
        'total' => 0,
        'reviews' => []
    ]);
    exit;
}

$pdo = get_pdo();
$user = current_user();

$stmt = $pdo->prepare("SELECT id, reviews, claimed_by FROM places WHERE id = ?");
$stmt->execute([$placeId]);
$place = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$place) {
    echo json_encode([
        'html' => '<div class="text-muted">İşletme bulunamadı</div>',
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
        'reply' => $reply
    ];
}

usort($reviews, function ($a, $b) use ($sort) {
    $ra = $a['rating'] ?? 0;
    $rb = $b['rating'] ?? 0;
    $ta = $a['timestamp'] ?? 0;
    $tb = $b['timestamp'] ?? 0;

    return match ($sort) {
        'old' => $ta <=> $tb,
        'high' => $rb <=> $ra,
        'low' => $ra <=> $rb,
        default => $tb <=> $ta,
    };
});

$total = count($reviews);
$totalPages = max(1, (int)ceil($total / $limit));
$offset = ($page - 1) * $limit;
$chunk = array_slice($reviews, $offset, $limit);

ob_start();

if (!$chunk) {
    echo '<div class="text-muted">Yorum bulunamadı.</div>';
} else {
    foreach ($chunk as $rev) {
        ?>
        <div class="review-card mb-3">
            <div class="d-flex align-items-center gap-2 mb-1">
                <?= render_lazy_img(
                    $rev['profile_photo_url'],
                    48,
                    48,
                    ['class' => 'avatar rounded-circle']
                ); ?>

                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <strong><?= htmlspecialchars($rev['author_name']) ?></strong>
                        <?php if ($rev['rating'] !== null): ?>
                            <span class="badge bg-warning text-dark">
                                ⭐ <?= htmlspecialchars($rev['rating']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($rev['relative_time'])): ?>
                        <div class="text-muted small">
                            <?= htmlspecialchars(date("d.m.Y H:i", strtotime($rev['relative_time']))) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($rev['text'])): ?>
                <p class="mb-1"><?= nl2br(htmlspecialchars($rev['text'])) ?></p>
            <?php endif; ?>

            <?php if (!empty($rev['reply'])): ?>
                <div class="mt-2 p-2 rounded border-start border-4 border-primary bg-light">
                    <span class="badge bg-primary mb-1">İşletme Sahibi Yanıtı</span>
                    <div class="small">
                        <?= nl2br(htmlspecialchars($rev['reply']['reply_text'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($user && can_edit_place($place, $user)): ?>
                <button
                    class="btn btn-sm btn-outline-primary mt-2"
                    onclick="openBusinessReplyModal({
                        place_id: <?= (int)$placeId ?>,
                        source: '<?= $rev['source'] ?>',
                        review_ref: '<?= $rev['review_ref'] ?>'
                    })">
                    Yanıtla
                </button>
            <?php endif; ?>
        </div>
        <?php
    }
}

$html = ob_get_clean();

echo json_encode([
    'html' => $html,
    'total_pages' => $totalPages,
    'total' => $total,
    'reviews' => $chunk
]);
