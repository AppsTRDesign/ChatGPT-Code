<h4 class="mb-3">Kategoriler</h4>
<div class="row g-3">
<?php foreach ($cats as $cat): ?>
  <div class="col-12 col-md-4 col-lg-3">
    <a class="card card-hover h-100 text-decoration-none" href="/kategoriler/<?= urlencode($cat['slug'] ?? slugify($cat['name'])) ?>">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-semibold text-dark"><?= htmlspecialchars($cat['name']) ?></div>
            <div class="text-muted small">İşletme</div>
          </div>
          <span class="badge bg-primary"><?= (int)$cat['total'] ?></span>
        </div>
      </div>
    </a>
  </div>
<?php endforeach; ?>
</div>
