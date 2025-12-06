<?php
$reviews = $place['reviews'] ?? [];
$suggestedExtras = [];
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
$collectExtras = function($source) use (&$suggestedExtras, $normalizeExtras){
    if (empty($source['text_extra'])) return;
    foreach ($normalizeExtras($source['text_extra']) as $extra) {
        if (!empty($extra['label'])) { $suggestedExtras[] = $extra['label']; }
    }
};
foreach ($reviews as $rev) { $collectExtras($rev); }
if (!empty($userReviews)) { foreach ($userReviews as $rev) { $collectExtras($rev); } }
$suggestedExtras = array_values(array_unique(array_filter($suggestedExtras)));
?>
<div class="row g-4">
  <div class="col-12">
    <div class="table-card mb-3">
      <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center">
        <?php if (!empty($place['business_image'])): ?>
          <img class="hero-img minimal" src="<?= htmlspecialchars($place['business_image']) ?>" alt="<?= htmlspecialchars($place['name']) ?>">
        <?php endif; ?>
        <div class="flex-grow-1">
          <div class="d-flex align-items-center gap-2 mb-1">
            <h3 class="mb-0 me-2">
              <?= htmlspecialchars($place['name']) ?>
            </h3>
            <?php if (!empty($place['business_type'])): ?>
              <a class="badge bg-light text-primary text-decoration-none" href="<?= category_url($place['category_slug'] ?? '') ?>"><?= htmlspecialchars($place['business_type']) ?></a>
            <?php endif; ?>
            <?php if (!empty($place['city_name'])): ?><span class="badge bg-secondary"><?= htmlspecialchars($place['city_name']) ?></span><?php endif; ?>
          </div>
          <div class="d-flex flex-wrap gap-2 mb-2">
            <span class="meta-chip">⭐ <?= format_rating($place['combined_rating'] ?? $place['rating'] ?? null) ?> (<?= (int)($place['total_votes'] ?? $place['user_ratings_total'] ?? 0) ?>)</span>
            <?php if (!empty($place['telephone_type'])): ?><span class="meta-chip">Tel: <?= htmlspecialchars($place['telephone_type']) ?></span><?php endif; ?>
            <?php if (!empty($place['views'])): ?><span class="meta-chip">👁 <?= (int)$place['views'] ?></span><?php endif; ?>
          </div>
          <?php if (!empty($place['formatted_address'])): ?><p class="text-muted mb-1">📍 <?= htmlspecialchars($place['formatted_address']) ?></p><?php endif; ?>
          <?php if (!empty($place['website'])): ?><p class="mb-1">🔗 <a href="<?= htmlspecialchars($place['website']) ?>" target="_blank" rel="noopener">Web sitesi</a></p><?php endif; ?>
          <?php if (!empty($place['formatted_phone_number'])): ?><p class="mb-0">☎ <?= htmlspecialchars($place['formatted_phone_number']) ?></p><?php endif; ?>
        </div>
      </div>
    </div>

    <?php if (!empty($place['opening_hours'])): ?>
    <div class="table-card mb-3">
      <h5>Çalışma Saatleri</h5>
      <ul class="list-unstyled mb-0 two-col-list">
        <?php foreach ($place['opening_hours'] as $row): ?>
          <li class="py-1 border-bottom"><span><?= htmlspecialchars($row) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($place['busy_hours'])): ?>
    <div class="table-card mb-3">
      <h5>Yoğun Saatler</h5>
      <canvas id="busyChart" class="busy-chart" data-hours='<?= json_encode($place['busy_hours'], JSON_UNESCAPED_UNICODE) ?>'></canvas>
    </div>
    <?php endif; ?>

    <div class="table-card">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Yorumlar</h5>
        <span class="text-muted small">Toplam <?= (int)($place['total_reviews'] ?? count($reviews)) ?> yorum</span>
      </div>
      <?php foreach ($reviews as $rev): ?>
        <div class="review-card">
          <div class="d-flex align-items-center gap-2 mb-1">
            <?php if (!empty($rev['profile_photo_url'])): ?>
              <img class="avatar" src="<?= htmlspecialchars($rev['profile_photo_url']) ?>" alt="profil">
            <?php else: ?>
              <div class="avatar placeholder">👤</div>
            <?php endif; ?>
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between"><strong><?= htmlspecialchars($rev['author_name'] ?? $rev['author'] ?? 'Kullanıcı') ?></strong><span class="badge bg-warning text-dark">⭐ <?= format_rating($rev['rating'] ?? null) ?></span></div>
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
      <?php endforeach; ?>

      <?php if (!empty($userReviews)): ?>
        <h6 class="mt-3">Ziyaretçi Yorumları</h6>
        <?php foreach ($userReviews as $rev): ?>
          <div class="review-card">
            <div class="d-flex align-items-center gap-2 mb-1">
              <img class="avatar" src="<?= gravatar_url($rev['email'] ?? '', 64) ?>" alt="avatar">
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between"><strong><?= htmlspecialchars($rev['author']) ?></strong><span class="badge bg-success">⭐ <?= (int)$rev['rating'] ?></span></div>
                <div class="text-muted small mb-1"><?= htmlspecialchars($rev['created_at']) ?></div>
              </div>
            </div>
            <?php if (!empty($rev['text'])): ?><p class="mb-1"><?= nl2br(htmlspecialchars($rev['text'])) ?></p><?php endif; ?>
            <?php $userExtras = $normalizeExtras($rev['text_extra'] ?? []); if ($userExtras): ?>
              <div class="extra-panel mb-2">
                <?php foreach ($userExtras as $ue): ?>
                  <div class="extra-row">
                    <span class="extra-label"><?= htmlspecialchars($ue['label']) ?></span>
                    <?php if ($ue['value'] !== ''): ?><span class="extra-value"><?= htmlspecialchars($ue['value']) ?></span><?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <div class="border-top pt-3 mt-3">
        <h6>Yorum Yap</h6>
        <form id="userReviewForm" data-place="<?= (int)$place['id'] ?>">
          <div class="row g-2 align-items-center mb-2">
            <div class="col-md-4"><input required name="name" class="form-control" placeholder="Ad Soyad"></div>
            <div class="col-md-4"><input name="email" class="form-control" placeholder="E-posta"></div>
            <div class="col-md-4">
              <select name="rating" class="form-select" required>
                <option value="">Puan Seçin</option>
                <?php for($i=1;$i<=5;$i++): ?>
                  <option value="<?= $i ?>"><?= $i ?></option>
                <?php endfor; ?>
              </select>
            </div>
          </div>
          <?php if (!empty($suggestedExtras)): ?>
            <div class="mb-2 small">Opsiyonel etiketler:</div>
            <div class="row g-2 mb-2" id="extraInputs">
              <?php foreach ($suggestedExtras as $extra): $key = slugify($extra); ?>
                <div class="col-md-4">
                  <label class="form-label small mb-1"><?= htmlspecialchars($extra) ?></label>
                  <input type="text" class="form-control" data-extra-key="<?= htmlspecialchars($extra) ?>" name="extra_<?= htmlspecialchars($key) ?>" placeholder="<?= htmlspecialchars($extra) ?> (opsiyonel)">
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <div class="mb-3"><textarea name="text" class="form-control" rows="3" placeholder="Yorum"></textarea></div>
          <div class="d-grid"><button class="btn btn-primary" type="submit">Gönder</button></div>
        </form>
      </div>
    </div>
  </div>
</div>
<div id="visit-tracker" data-place="<?= (int)$place['id'] ?>" hidden></div>
<div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-body p-0">
        <img id="photoModalImg" src="" class="w-100" alt="Görsel">
      </div>
    </div>
  </div>
</div>
