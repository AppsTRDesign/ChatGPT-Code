<h4 class="mb-3"><?= htmlspecialchars(ucwords($cityName)) ?> Şehri</h4>
<div class="row g-3 mb-4">
  <div class="col-12 col-lg-5">
    <div class="table-card h-100">
      <h6 class="mb-3">Kategoriler</h6>
      <?php if (!empty($cityCats)): ?>
        <?php foreach ($cityCats as $cat): ?>
          <div class="d-flex justify-content-between border-bottom py-1">
            <a class="text-decoration-none" title="<?= htmlspecialchars($cat['name']) ?>" href="<?= category_url($cat['slug'] ?? '') ?>"><?= htmlspecialchars($cat['name']) ?></a>
            <span class="badge bg-secondary"><?= (int)$cat['total'] ?></span>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="text-muted small">Kategori bulunamadı.</div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-12 col-lg-7">
    <div class="table-card h-100">
      <h6 class="mb-3">Son Eklenen İşletmeler</h6>
      <div class="row g-3">
        <?php foreach ($cityRecent as $place): ?>
          <div class="col-12 col-md-6">
            <div class="card card-hover h-100">
              <div class="card-body">
                <a class="fw-semibold text-decoration-none" title="<?= htmlspecialchars($place['name']) ?>" href="/<?= build_place_slug($place) ?>"><?= htmlspecialchars($place['name']) ?></a>
                <div class="text-muted small mb-1"><?= htmlspecialchars($place['formatted_address']) ?></div>
                <div class="d-flex flex-wrap gap-2 text-muted small">
                  <a class="text-decoration-none" title="<?= htmlspecialchars($place['business_type']) ?>" href="<?= category_url($place['category_slug'] ?? '') ?>"><?= htmlspecialchars($place['business_type']) ?></a>
                  <span>⭐ <?= format_rating($place['combined_rating'] ?? $place['rating']) ?></span>
                  <span>💬 <?= (int)($place['total_reviews'] ?? 0) ?></span>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
