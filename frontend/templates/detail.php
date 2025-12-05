<?php $reviews = $place['reviews'] ?? []; ?>
<div class="row g-4">
  <div class="col-12 col-lg-8">
    <div class="table-card mb-3">
      <?php if (!empty($place['business_image'])): ?>
        <img class="hero-img mb-3" src="<?= htmlspecialchars($place['business_image']) ?>" alt="<?= htmlspecialchars($place['name']) ?>">
      <?php endif; ?>
      <h3 class="mb-1"><?= htmlspecialchars($place['name']) ?></h3>
      <div class="d-flex flex-wrap gap-2 mb-2">
        <span class="meta-chip">⭐ <?= format_rating($place['rating']) ?> (<?= (int)$place['user_ratings_total'] ?>)</span>
        <?php if (!empty($place['business_type'])): ?><span class="meta-chip"><?= htmlspecialchars($place['business_type']) ?></span><?php endif; ?>
        <?php if (!empty($place['telephone_type'])): ?><span class="meta-chip">Tel: <?= htmlspecialchars($place['telephone_type']) ?></span><?php endif; ?>
        <?php if (!empty($place['city_name'])): ?><span class="meta-chip">Şehir: <?= htmlspecialchars($place['city_name']) ?></span><?php endif; ?>
      </div>
      <?php if (!empty($place['formatted_address'])): ?><p class="text-muted mb-1">📍 <?= htmlspecialchars($place['formatted_address']) ?></p><?php endif; ?>
      <?php if (!empty($place['website'])): ?><p class="mb-1">🔗 <a href="<?= htmlspecialchars($place['website']) ?>" target="_blank" rel="noopener">Web sitesi</a></p><?php endif; ?>
      <?php if (!empty($place['formatted_phone_number'])): ?><p class="mb-0">☎ <?= htmlspecialchars($place['formatted_phone_number']) ?></p><?php endif; ?>
    </div>

    <?php if (!empty($place['opening_hours'])): ?>
    <div class="table-card mb-3">
      <h5>Çalışma Saatleri</h5>
      <ul class="list-unstyled mb-0">
        <?php foreach ($place['opening_hours'] as $row): ?>
          <li class="d-flex justify-content-between border-bottom py-1"><span><?= htmlspecialchars($row) ?></span></li>
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
      </div>
      <?php foreach ($reviews as $rev): ?>
        <div class="review-card">
          <div class="d-flex justify-content-between"><strong><?= htmlspecialchars($rev['author_name'] ?? $rev['author'] ?? 'Kullanıcı') ?></strong><span class="badge bg-warning text-dark">⭐ <?= format_rating($rev['rating'] ?? null) ?></span></div>
          <?php if (!empty($rev['relative_time'])): ?><div class="text-muted small mb-1"><?= htmlspecialchars($rev['relative_time']) ?></div><?php endif; ?>
          <?php if (!empty($rev['text'])): ?><p class="mb-1"><?= nl2br(htmlspecialchars($rev['text'])) ?></p><?php endif; ?>
          <?php if (!empty($rev['text_extra'])): ?>
            <div class="text-muted small">Ek bilgiler: <?= htmlspecialchars(json_encode($rev['text_extra'], JSON_UNESCAPED_UNICODE)) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <?php if (!empty($userReviews)): ?>
        <h6 class="mt-3">Ziyaretçi Yorumları</h6>
        <?php foreach ($userReviews as $rev): ?>
          <div class="review-card">
            <div class="d-flex justify-content-between"><strong><?= htmlspecialchars($rev['author']) ?></strong><span class="badge bg-success">⭐ <?= (int)$rev['rating'] ?></span></div>
            <div class="text-muted small mb-1"><?= htmlspecialchars($rev['created_at']) ?></div>
            <?php if (!empty($rev['text'])): ?><p class="mb-1"><?= nl2br(htmlspecialchars($rev['text'])) ?></p><?php endif; ?>
            <?php if (!empty($rev['text_extra'])): ?><div class="text-muted small">Ekstra: <?= htmlspecialchars(json_encode($rev['text_extra'], JSON_UNESCAPED_UNICODE)) ?></div><?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <div class="border-top pt-3 mt-3">
        <h6>Yorum Yap</h6>
        <form id="userReviewForm" data-place="<?= (int)$place['id'] ?>">
          <div class="row g-2">
            <div class="col-md-6"><input required name="name" class="form-control" placeholder="Ad Soyad"></div>
            <div class="col-md-6"><input name="email" class="form-control" placeholder="E-posta"></div>
            <div class="col-md-4"><input type="number" min="1" max="5" name="rating" class="form-control" placeholder="Puan (1-5)" required></div>
            <div class="col-md-8"><input name="text_extra" class="form-control" placeholder="Ek bilgiler (örn: Kalite, Hizmet)"></div>
            <div class="col-12"><textarea name="text" class="form-control" rows="3" placeholder="Yorum"></textarea></div>
            <div class="col-12 d-grid"><button class="btn btn-primary" type="submit">Gönder</button></div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-4">
    <?php if (!empty($place['business_image'])): ?><img class="hero-img mb-3" src="<?= htmlspecialchars($place['business_image']) ?>" alt="İşletme görseli"><?php endif; ?>
    <div class="table-card">
      <h6>Özet</h6>
      <p class="mb-1"><strong>Kategori:</strong> <?= htmlspecialchars($place['business_type'] ?? '-') ?></p>
      <p class="mb-1"><strong>Şehir:</strong> <?= htmlspecialchars($place['city_name'] ?? '-') ?></p>
      <p class="mb-1"><strong>Telefon:</strong> <?= htmlspecialchars($place['formatted_phone_number'] ?? '-') ?></p>
      <p class="mb-0"><strong>Telefon Tipi:</strong> <?= htmlspecialchars($place['telephone_type'] ?? '-') ?></p>
    </div>
  </div>
</div>
<script>
(function(){
  const chartEl = document.getElementById('busyChart');
  if(chartEl){
    const hours = JSON.parse(chartEl.dataset.hours||'{}');
    const labels = Object.keys(hours);
    const datasets = labels.map(day => ({ label: day, data: (hours[day]||[]).map(item=>item.yogunluk || item.yoğunluk || item.load || 0) }));
    new Chart(chartEl, { type:'bar', data:{ labels: labels, datasets: datasets }, options:{ plugins:{legend:{display:false}}, responsive:true, scales:{y:{beginAtZero:true}} }});
  }
  const form = document.getElementById('userReviewForm');
  if(form){
    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const placeId = form.dataset.place;
      const payload = Object.fromEntries(new FormData(form).entries());
      payload.place_id = placeId;
      if(payload.text_extra){
        payload.text_extra = payload.text_extra.split(',').map(s=>s.trim()).filter(Boolean);
      }
      const res = await fetch('/frontend/includes/submit_review.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
      if(res.ok){
        Swal.fire({ icon:'success', title:'Teşekkürler', text:'Yorumunuz kaydedildi' });
        form.reset();
      } else {
        Swal.fire({ icon:'error', title:'Hata', text:'Yorum kaydedilemedi' });
      }
    });
  }
})();
</script>
