<section class="mb-4">
  <div class="table-card mb-3">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3 gap-2">
      <div>
        <h5 class="mb-1">Harita</h5>
        <p class="text-muted small mb-0">OpenStreetMap kümeleriyle 200 işletmeye kadar filtreleyin.</p>
      </div>
      <form id="map-search-form" class="row gy-2 gx-2 align-items-center flex-row">
        <div class="col-12 col-md-3"><input name="q" class="form-control" placeholder="İşletme adı veya adres"></div>
        <div class="col-6 col-md-3">
          <select name="category" class="form-select">
            <option value="">Kategori (tümü)</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat['slug'] ?? $cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-3">
          <select name="city" class="form-select">
            <option value="">Şehir (tümü)</option>
            <?php foreach ($cities as $city): ?>
              <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars(ucwords($city)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <select name="sort" class="form-select">
            <option value="newest">Sıralama: Yeni</option>
            <option value="rating">Sıralama: Puan</option>
            <option value="reviews">Sıralama: Yorum</option>
            <option value="views">Sıralama: Görüntülenme</option>
          </select>
        </div>
        <div class="col-6 col-md-2 d-grid"><button class="btn btn-primary" type="submit">Ara</button></div>
      </form>
    </div>
    <div id="map"></div>
  </div>
  <div class="table-card" id="search-card" data-limit="<?= HOME_SEARCH_SECTION_LIMIT ?>">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="mb-0">Arama Sonuçları</h6>
      <small class="text-muted">Sonuç yoksa "Sonuç bulunamadı" gösterilir</small>
    </div>
    <div id="map-results" class="row g-3"></div>
    <div id="map-no-results" class="text-center text-muted py-3 d-none">Sonuç bulunamadı</div>
    <div id="search-pagination" class="d-flex justify-content-center flex-wrap gap-2 mt-3"></div>
  </div>
</section>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "NoaSoft Maps",
  "url": "<?= BASE_URL ?>",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "<?= BASE_URL ?>/search?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>

<section class="row g-3">
  <div class="col-12 col-lg-4">
    <div class="table-card h-100">
      <h6 class="mb-3 d-flex justify-content-between align-items-center">Son Eklenen İşletmeler <a class="small" href="/kategoriler">Kategorilere git</a></h6>
      <?php foreach ($recent as $place): ?>
        <div class="d-flex align-items-center mb-2">
          <div class="flex-grow-1">
            <a class="fw-semibold text-decoration-none" title="<?= htmlspecialchars($place['name']) ?>" href="/<?= build_place_slug($place) ?>"><?= htmlspecialchars($place['name']) ?></a>
            <div class="text-muted small"><a title="<?= htmlspecialchars($place['business_type']) ?>" href="<?= category_url($place['category_slug'] ?? '') ?>" class="text-decoration-none"><?= htmlspecialchars($place['business_type']) ?></a></div>
          </div>
          <span class="badge bg-warning text-dark">⭐ <?= format_rating($place['combined_rating'] ?? $place['rating']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-12 col-lg-4">
    <div class="table-card h-100">
      <h6 class="mb-3">En Çok Görüntülenen</h6>
      <?php foreach ($mostViewed as $place): ?>
        <div class="d-flex align-items-center mb-2">
          <div class="flex-grow-1">
            <a class="fw-semibold text-decoration-none" title="<?= htmlspecialchars($place['name']) ?>" href="/<?= build_place_slug($place) ?>"><?= htmlspecialchars($place['name']) ?></a>
            <div class="text-muted small"><a title="<?= htmlspecialchars($place['business_type']) ?>" href="<?= category_url($place['category_slug'] ?? '') ?>" class="text-decoration-none"><?= htmlspecialchars($place['business_type']) ?></a></div>
          </div>
          <span class="badge bg-info text-dark">👁 <?= (int)($place['views'] ?? 0) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-12 col-lg-4">
    <div class="table-card h-100">
      <h6 class="mb-3">En Yüksek Puanlı</h6>
      <?php foreach ($topRated as $place): ?>
        <div class="d-flex align-items-center mb-2">
          <div class="flex-grow-1">
            <a class="fw-semibold text-decoration-none" title="<?= htmlspecialchars($place['name']) ?>" href="/<?= build_place_slug($place) ?>"><?= htmlspecialchars($place['name']) ?></a>
            <div class="text-muted small"><a title="<?= htmlspecialchars($place['business_type']) ?>" href="<?= category_url($place['category_slug'] ?? '') ?>" class="text-decoration-none"><?= htmlspecialchars($place['business_type']) ?></a></div>
          </div>
          <span class="badge bg-success">⭐ <?= format_rating($place['combined_rating'] ?? $place['rating']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
