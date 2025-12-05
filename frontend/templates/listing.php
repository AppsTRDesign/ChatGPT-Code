<h4 class="mb-3"><?= htmlspecialchars($title ?? 'Liste') ?></h4>
<div class="row g-3">
  <?php foreach ($items as $place): ?>
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card card-hover h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <a class="fw-bold text-decoration-none" href="/<?= build_place_slug($place) ?>"><?= htmlspecialchars($place['name']) ?></a>
            <span class="badge bg-primary">⭐ <?= format_rating($place['combined_rating'] ?? $place['rating'] ?? null) ?></span>
          </div>
          <div class="text-muted small mb-1"><?= htmlspecialchars($place['formatted_address']) ?></div>
          <div class="small mb-2">Kategori: <a class="text-decoration-none" href="<?= category_url($place['category_slug'] ?? '') ?>"><?= htmlspecialchars($place['business_type']) ?></a></div>
          <div class="d-flex flex-wrap gap-2 small text-muted">
            <span>👁 <?= (int)($place['views'] ?? 0) ?></span>
            <span>💬 <?= (int)($place['total_reviews'] ?? 0) ?></span>
            <span>⭐ <?= (int)($place['total_votes'] ?? $place['user_ratings_total'] ?? 0) ?> oy</span>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
