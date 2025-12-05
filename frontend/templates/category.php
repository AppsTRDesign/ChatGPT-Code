<h4 class="mb-3">Kategori: <?= htmlspecialchars($categoryTitle) ?></h4>
<div class="row g-3 mb-3">
  <?php foreach ($items as $place): ?>
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card card-hover h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <a class="fw-bold text-decoration-none" href="/<?= build_place_slug($place) ?>"><?= htmlspecialchars($place['name']) ?></a>
            <span class="badge bg-warning text-dark">⭐ <?= format_rating($place['rating']) ?></span>
          </div>
          <div class="text-muted small mb-2"><?= htmlspecialchars($place['formatted_address']) ?></div>
          <div class="small text-muted">Değerlendirme: <?= (int)$place['user_ratings_total'] ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
