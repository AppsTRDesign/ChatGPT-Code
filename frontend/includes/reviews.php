<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

header('Content-Type: application/json; charset=utf-8');

$placeId = (int)($_GET['place_id'] ?? 0);
$page = max(1, (int)($_GET['s'] ?? 1));
$limit = DETAIL_REVIEW_LIMIT;

if ($placeId <= 0) {
    echo json_encode(['html' => '<div class="text-muted">Geçersiz istek</div>', 'total_pages' => 1]);
    exit;
}

$pdo = get_pdo();
$stmt = $pdo->prepare('SELECT reviews, name, business_image, business_type, formatted_address, city_name, rating, user_ratings_total FROM places WHERE id = :id');
$stmt->execute([':id' => $placeId]);
$place = $stmt->fetch();
if (!$place) {
    echo json_encode(['html' => '<div class="text-muted">İşletme bulunamadı</div>', 'total_pages' => 1]);
    exit;
}

$googleReviews = decode_json($place['reviews'] ?? '');
$userStmt = $pdo->prepare('SELECT author_name AS author_name, email, rating, review_text AS text, text_extra, created_at FROM user_reviews WHERE place_id = :pid ORDER BY created_at DESC');
$userStmt->execute([':pid' => $placeId]);
$userRows = $userStmt->fetchAll();

$reviews = [];
$reviewIndex = 0;
$toTimestamp = function($value): int {
    if ($value === null || $value === '') {
        return 0;
    }
    if (is_numeric($value)) {
        $ts = (int)$value;
        return $ts > 2000000000 ? (int)($ts / 1000) : $ts;
    }
    $parsed = strtotime((string)$value);
    return $parsed ?: 0;
};
foreach ($googleReviews as $rev) {
    $sortTime = $toTimestamp($rev['time'] ?? ($rev['review_time'] ?? ''));
    $reviews[] = [
        'author_name' => $rev['author_name'] ?? '',
        'rating' => $rev['rating'] ?? null,
        'relative_time' => $rev['relative_time'] ?? ($rev['relative_time_description'] ?? ''),
        'text' => $rev['text'] ?? '',
        'profile_photo_url' => $rev['profile_photo_url'] ?? '',
        'text_extra' => $rev['text_extra'] ?? [],
        'review_photo_urls' => $rev['review_photo_urls'] ?? [],
        'source' => 'Google',
        'sort_time' => $sortTime,
        'sort_index' => $reviewIndex++,
    ];
}
foreach ($userRows as $rev) {
    $sortTime = $toTimestamp($rev['created_at'] ?? '');
    $reviews[] = [
        'author_name' => $rev['author_name'] ?? $rev['author'] ?? '',
        'rating' => $rev['rating'] ?? null,
        'relative_time' => $rev['created_at'] ?? '',
        'text' => $rev['text'] ?? $rev['review_text'] ?? '',
        'profile_photo_url' => gravatar_url($rev['email'] ?? '', 64),
        'text_extra' => decode_json($rev['text_extra'] ?? ''),
        'review_photo_urls' => [],
        'source' => 'Kullanıcı',
        'sort_time' => $sortTime,
        'sort_index' => $reviewIndex++,
    ];
}

usort($reviews, function($a, $b) {
    $timeDiff = ($b['sort_time'] ?? 0) <=> ($a['sort_time'] ?? 0);
    if ($timeDiff !== 0) {
        return $timeDiff;
    }
    return ($a['sort_index'] ?? 0) <=> ($b['sort_index'] ?? 0);
});

$total = count($reviews);
$totalPages = max(1, (int)ceil($total / $limit));
$offset = ($page - 1) * $limit;
$chunk = array_slice($reviews, $offset, $limit);

$normalizeExtras = function($extras) {
    $out = [];
    if (!is_array($extras)) return $out;
    foreach ($extras as $k => $v) {
        if (is_int($k)) {
            $out[] = ['label' => is_array($v) ? implode(' ', $v) : $v, 'value' => ''];
        } else {
            $out[] = ['label' => $k, 'value' => is_array($v) ? implode(' ', $v) : $v];
        }
    }
    return $out;
};

ob_start();
if (!$chunk) {
    echo '<div class="text-muted">Yorum bulunamadı.</div>';
} else {
    foreach ($chunk as $rev) {
        ?>
        <div class="review-card">
          <div class="d-flex align-items-center gap-2 mb-1">
            <?php if (!empty($rev['profile_photo_url'])): ?>
              <img class="avatar" src="<?= htmlspecialchars($rev['profile_photo_url']) ?>" alt="profil">
            <?php else: ?>
              <div class="avatar placeholder">👤</div>
            <?php endif; ?>
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between align-items-center">
                <strong><?= htmlspecialchars($rev['author_name'] ?? 'Kullanıcı') ?></strong>
                <div class="d-flex align-items-center gap-1">
                  <?php if (!empty($rev['source'])): ?><span class="badge bg-light text-dark"><?= htmlspecialchars($rev['source']) ?></span><?php endif; ?>
                  <span class="badge bg-warning text-dark">⭐ <?= htmlspecialchars($rev['rating'] ?? '-') ?></span>
                </div>
              </div>
              <?php if (!empty($rev['relative_time'])): ?><div class="text-muted small mb-1"><?= htmlspecialchars($rev['relative_time']) ?></div><?php endif; ?>
            </div>
          </div>
          <?php if (!empty($rev['text'])): ?><p class="mb-1"><?= nl2br(htmlspecialchars($rev['text'])) ?></p><?php endif; ?>
          <?php $extras = $normalizeExtras($rev['text_extra'] ?? []); if ($extras): ?>
            <div class="extra-panel mb-2">
              <?php foreach ($extras as $extra): ?>
                <div class="extra-row">
                  <span class="extra-label"><?= htmlspecialchars($extra['label']) ?></span>
                  <?php if ($extra['value'] !== ''): ?><span class="extra-value"><?= htmlspecialchars($extra['value']) ?></span><?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <?php if (!empty($rev['review_photo_urls'])): ?>
            <div class="photo-frame d-flex flex-wrap gap-2">
              <?php foreach ($rev['review_photo_urls'] as $photoUrl): ?>
                <img src="<?= htmlspecialchars($photoUrl) ?>" class="review-thumb" data-full="<?= htmlspecialchars($photoUrl) ?>" alt="yorum görseli">
              <?php endforeach; ?>
            </div>
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
]);
