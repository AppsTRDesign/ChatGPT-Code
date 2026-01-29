<?php
$normalizeExtras = function($extras) {
    if (!is_array($extras)) return [];
    $out = [];
    foreach ($extras as $k => $v) {
        if (is_int($k)) {
            $out[] = ['label' => is_array($v) ? implode(' ', $v) : $v, 'value' => ''];
        } else {
            $out[] = ['label' => $k, 'value' => is_array($v) ? implode(' ', $v) : $v];
        }
    }
    return $out;
};
?>
<div class="table-card mb-3">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
    <div>
      <h4 class="mb-1">Son Yorumlar</h4>
      <p class="text-muted small mb-0">En yeni kullanıcı yorumlarını keşfedin.</p>
    </div>
    <span class="badge bg-primary">Toplam <?= (int)($totalReviews ?? 0) ?> yorum</span>
  </div>
</div>

<?php if (empty($reviews)): ?>
  <div class="text-muted">Henüz yorum bulunamadı.</div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($reviews as $review): ?>
      <?php
        $createdAt = $review['created_at'] ? date('d.m.Y H:i', strtotime($review['created_at'])) : '';
        $extras = $normalizeExtras($review['text_extra'] ?? []);
      ?>
      <div class="col-12 col-lg-6">
        <div class="review-card h-100">
          <div class="d-flex gap-3 align-items-start">
            <?php if (!empty($review['business_image'])): ?>
              <img src="<?= htmlspecialchars($review['business_image']) ?>" class="avatar" alt="<?= htmlspecialchars($review['name'] ?? '') ?>">
            <?php else: ?>
              <div class="avatar placeholder">🏬</div>
            <?php endif; ?>
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <div>
                  <a class="fw-semibold text-decoration-none" href="/<?= htmlspecialchars($review['slug']) ?>">
                    <?= htmlspecialchars($review['name'] ?? 'İşletme') ?>
                  </a>
                  <div class="text-muted small">
                    <?php if (!empty($review['business_type'])): ?>
                      <a class="text-decoration-none" href="<?= category_url($review['category_slug'] ?? '') ?>">
                        <?= htmlspecialchars($review['business_type']) ?>
                      </a>
                    <?php endif; ?>
                    <?php if (!empty($review['city_name'])): ?>
                      <span>• <?= htmlspecialchars($review['city_name']) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
                <span class="badge bg-warning text-dark">⭐ <?= htmlspecialchars($review['rating'] ?? '-') ?></span>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="text-muted small">👤 <?= htmlspecialchars($review['author_name'] ?? 'Kullanıcı') ?></div>
                <?php if ($createdAt): ?><div class="text-muted small"><?= htmlspecialchars($createdAt) ?></div><?php endif; ?>
              </div>
              <?php if (!empty($review['review_text'])): ?>
                <p class="mb-2"><?= nl2br(htmlspecialchars($review['review_text'])) ?></p>
              <?php endif; ?>
              <?php if ($extras): ?>
                <div class="extra-panel">
                  <?php foreach ($extras as $extra): ?>
                    <div class="extra-row">
                      <span class="extra-label"><?= htmlspecialchars($extra['label']) ?></span>
                      <?php if ($extra['value'] !== ''): ?><span class="extra-value"><?= htmlspecialchars($extra['value']) ?></span><?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?= render_pagination_links($page, $totalPages, '/son-yorumlar'); ?>
<?php endif; ?>
