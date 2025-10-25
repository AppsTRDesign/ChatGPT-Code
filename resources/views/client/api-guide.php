<?php ob_start(); ?>
<div class="card">
    <div class="card-body">
        <h5>API Kullanım Kılavuzu</h5>
        <p class="text-muted">Aşağıdaki örnekler ile token tabanlı REST API üzerinden bildirim gönderebilirsiniz. İstekler JSON formatında yapılmalı ve HTTPS kullanılmalıdır.</p>
        <h6 class="mt-4">Kimlik Doğrulama</h6>
        <p>HTTP isteğine <code>Authorization: Bearer {TOKEN}</code> başlığı eklenmelidir.</p>
        <div class="bg-light rounded p-3 mb-3">
<pre class="mb-0"><code>curl -X POST https://webpush.noasoft.org/api/notifications \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Kampanya!",
    "message": "Hafta sonu %20 indirim",
    "link": "https://siteniz.com/kampanya"
  }'</code></pre>
        </div>
        <h6>Yanıt</h6>
        <div class="bg-light rounded p-3 mb-3">
<pre class="mb-0"><code>{
  "status": "queued",
  "notification": {
    "id": 15,
    "title": "Kampanya!",
    "message": "Hafta sonu %20 indirim",
    "link": "https://siteniz.com/kampanya"
  }
}</code></pre>
        </div>
        <h6>Token Yönetimi</h6>
        <p>Panelde oluşturduğunuz tokenlar:</p>
        <ul class="list-group list-group-flush mb-3">
            <?php foreach ($tokens as $token): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-break"><code><?= $token['token'] ?></code></span>
                    <span class="badge bg-success">Aktif</span>
                </li>
            <?php endforeach; ?>
            <?php if (empty($tokens)): ?>
                <li class="list-group-item">Henüz token oluşturmadınız.</li>
            <?php endif; ?>
        </ul>
        <h6>İstatistikler</h6>
        <p>API üzerinden gönderilen bildirimler <strong>API Kullanımı</strong> sekmesinde günlük, haftalık, aylık ve yıllık grafikleri ile raporlanır.</p>
    </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
