<h4 class="mb-3">Kategori: <?= htmlspecialchars($categoryTitle) ?></h4>
<div class="row g-3 mb-3">
  <?php foreach ($items as $place): ?>
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card card-hover h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <a class="fw-bold text-decoration-none" title="<?= htmlspecialchars($place['name']) ?>" href="/<?= build_place_slug($place) ?>"><?= htmlspecialchars($place['name']) ?></a>
            <span class="badge bg-warning text-dark">⭐ <?= format_rating($place['combined_rating'] ?? $place['rating'] ?? null) ?></span>
          </div>
          <div class="text-muted small mb-2"><?= htmlspecialchars($place['formatted_address']) ?></div>
          <div class="small text-muted d-flex gap-2 flex-wrap">
            <span>Oy: <?= (int)($place['total_votes'] ?? $place['user_ratings_total'] ?? 0) ?></span>
            <span>💬 <?= (int)($place['total_reviews'] ?? 0) ?></span>
            <span>👁 <?= (int)($place['views'] ?? 0) ?></span>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php if (!empty($totalPages)): ?>
  <?= render_pagination_links($page ?? 1, $totalPages, '/kategoriler/' . urlencode($catSlug ?? '')); ?>
<?php endif; ?>

<?php
$maxItems = 20;
$schemaItems = [];
foreach (array_slice($items, 0, $maxItems) as $index => $p) {
    $placeSlug = slugify($p['name']);
    $url = BASE_URL . '/' . slugify($p['business_type']) . '/' . $placeSlug . '-' . $p['id'];
    $schemaItems[] = [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'url' => $url,
        'item' => [
            '@type' => 'LocalBusiness',
            'name' => $p['name'],
            'image' => $p['business_image'] ?? ($p['business_default_image'] ?? null),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $p['formatted_address'] ?? '',
                'addressLocality' => $p['city_name'] ?? '',
                'addressCountry' => 'TR',
            ],
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => (float)($p['rating'] ?? 0),
                'ratingCount' => (int)($p['user_ratings_total'] ?? 0)
            ],
            'url' => $url,
        ],
    ];
}
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => ($categoryTitle ?? 'Kategori') . ' İşletmeleri',
    'itemListOrder' => 'http://schema.org/ItemListOrderAscending',
    'itemListElement' => $schemaItems,
];
?>
<script type="application/ld+json">
<?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>
