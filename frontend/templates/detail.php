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

$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'LocalBusiness',
    'name' => $place['name'] ?? '',
    'image' => $place['business_image'] ?? ($place['business_default_image'] ?? null),
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => $place['formatted_address'] ?? '',
        'addressLocality' => $place['city_name'] ?? '',
        'addressCountry' => 'TR',
    ],
    'geo' => [
        '@type' => 'GeoCoordinates',
        'latitude' => (float)($place['latitude'] ?? 0),
        'longitude' => (float)($place['longitude'] ?? 0),
    ],
    'telephone' => $place['formatted_phone_number'] ?? null,
    'url' => BASE_URL . '/' . build_place_slug($place),
];

$opening = [];
if (!empty($place['opening_hours'])) {
    $mapDay = [
        'Pazartesi' => 'Mo', 'Salı' => 'Tu', 'Çarşamba' => 'We', 'Perşembe' => 'Th', 'Cuma' => 'Fr', 'Cumartesi' => 'Sa', 'Pazar' => 'Su'
    ];
    foreach ($place['opening_hours'] as $row) {
        if (preg_match('/^(.*?):\s*(.*)$/u', $row, $m)) {
            if (isset($mapDay[$m[1]])) {
                $opening[] = $mapDay[$m[1]] . ' ' . str_replace('–', '-', $m[2]);
            }
        }
    }
}
if ($opening) { $schema['openingHours'] = $opening; }
if (!empty($place['rating'])) {
    $schema['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => (float)$place['rating'],
        'ratingCount' => (int)($place['user_ratings_total'] ?? 0),
    ];
}

$schemaReviews = [];
if (!empty($googleReviews)) {
    foreach (array_slice($googleReviews, 0, 5) as $r) {
        $schemaReviews[] = [
            '@type' => 'Review',
            'author' => ['@type' => 'Person', 'name' => $r['author_name'] ?? ''],
            'reviewRating' => ['@type' => 'Rating', 'ratingValue' => (float)($r['rating'] ?? 0)],
            'reviewBody' => $r['text'] ?? '',
        ];
    }
}
if ($schemaReviews) { $schema['review'] = $schemaReviews; }
?>
<script type="application/ld+json">
<?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>
</script>
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
            <?php if (!empty($place['city_name'])): ?>
              <a class="badge bg-secondary text-decoration-none" title="<?= htmlspecialchars($place['city_name']) ?>" href="<?= city_url($place['city_name']) ?>"><?= htmlspecialchars($place['city_name']) ?></a>
            <?php endif; ?>
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
        <span class="text-muted small" id="review-total">Toplam <?= (int)($place['total_reviews'] ?? count($reviews)) ?> yorum</span>
      </div>
      <div id="reviews-container" data-place="<?= (int)$place['id'] ?>" data-limit="<?= DETAIL_REVIEW_LIMIT ?>">
        <div class="text-muted">Yorumlar yükleniyor...</div>
      </div>
      <div id="reviews-pagination" class="d-flex flex-wrap gap-2 mt-2"></div>

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
