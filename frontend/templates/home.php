<section class="row g-3 mb-4">
  <div class="col-12 col-lg-8">
    <div class="table-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h5 class="mb-1">Harita</h5>
          <p class="text-muted small mb-0">OpenStreetMap + Cluster ile 200 işletmeye kadar görüntüleyin.</p>
        </div>
      </div>
      <form id="map-search-form" class="row gy-2 gx-2 mb-3">
        <div class="col-md-4"><input name="q" class="form-control" placeholder="İşletme adı veya adres"></div>
        <div class="col-md-3">
          <select name="category" class="form-select">
            <option value="">Kategori (tümü)</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <select name="city" class="form-select">
            <option value="">Şehir (tümü)</option>
            <?php foreach ($cities as $city): ?>
              <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars(ucwords($city)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit">Ara</button></div>
      </form>
      <div id="map"></div>
    </div>
  </div>
  <div class="col-12 col-lg-4">
    <div class="table-card mb-3">
      <h6 class="mb-3">Son İşletmeler</h6>
      <?php foreach ($recent as $place): ?>
        <div class="d-flex align-items-center mb-2">
          <div class="flex-grow-1">
            <a class="fw-semibold text-decoration-none" href="/<?= build_place_slug($place) ?>"><?= htmlspecialchars($place['name']) ?></a>
            <div class="text-muted small"><?= htmlspecialchars($place['business_type']) ?></div>
          </div>
          <span class="badge bg-warning text-dark">⭐ <?= format_rating($place['rating']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="table-card mb-3">
      <h6 class="mb-3">En Çok Görüntülenen</h6>
      <?php foreach ($mostViewed as $place): ?>
        <div class="d-flex align-items-center mb-2">
          <div class="flex-grow-1">
            <a class="fw-semibold text-decoration-none" href="/<?= build_place_slug($place) ?>"><?= htmlspecialchars($place['name']) ?></a>
            <div class="text-muted small"><?= htmlspecialchars($place['business_type']) ?></div>
          </div>
          <span class="badge bg-info text-dark">👁 <?= (int)$place['user_ratings_total'] ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="table-card">
      <h6 class="mb-3">En Yüksek Puanlı</h6>
      <?php foreach ($topRated as $place): ?>
        <div class="d-flex align-items-center mb-2">
          <div class="flex-grow-1">
            <a class="fw-semibold text-decoration-none" href="/<?= build_place_slug($place) ?>"><?= htmlspecialchars($place['name']) ?></a>
            <div class="text-muted small"><?= htmlspecialchars($place['business_type']) ?></div>
          </div>
          <span class="badge bg-success">⭐ <?= format_rating($place['rating']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
