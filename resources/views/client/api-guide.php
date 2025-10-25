<section class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h5 class="mb-1">API Kullanım Kılavuzu</h5>
                <small class="text-muted">Token tabanlı REST API ile Onesignal deneyimini yakalayın.</small>
            </div>
            <span class="badge-soft"><i class="bi bi-key"></i> Güvenli HTTPS istekleri</span>
        </div>
        <h6>Kimlik Doğrulama</h6>
        <p>HTTP isteğinize <code>Authorization: Bearer {TOKEN}</code> başlığını ekleyin. Tüm istekler JSON formatında olmalıdır.</p>
        <div class="bg-light rounded-4 p-3 mb-3">
<pre class="mb-0"><code>curl -X POST <?= base_url('api/notifications') ?> \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Kampanya!",
    "message": "Hafta sonu %20 indirim",
    "link": "https://siteniz.com/kampanya"
  }'</code></pre>
        </div>
        <h6>Yanıt</h6>
        <div class="bg-light rounded-4 p-3 mb-4">
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
        <ul class="list-group list-group-flush mb-4 rounded">
            <?php foreach ($tokens as $token): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-break"><code><?= htmlspecialchars($token['token']) ?></code></span>
                    <span class="badge-soft">Aktif</span>
                </li>
            <?php endforeach; ?>
            <?php if (empty($tokens) && !empty($user)): ?>
                <li class="list-group-item">Henüz token oluşturmadınız.</li>
            <?php elseif (empty($tokens) && empty($user)): ?>
                <li class="list-group-item">Token oluşturmak için hesabınıza giriş yapın.</li>
            <?php endif; ?>
        </ul>
        <h6>Ölçümleme</h6>
        <p>API üzerinden gönderilen bildirimler <strong>API Kullanımı</strong> ekranında günlük, haftalık, aylık ve yıllık grafikler ile raporlanır. PDF ve Excel olarak dışa aktarabilirsiniz.</p>
    </div>
</section>
