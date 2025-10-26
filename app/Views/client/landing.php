<?php require __DIR__ . '/../partials/head.php'; ?>
<header class="hero">
    <div class="hero-content">
        <h1>WebPush ile kullanıcılarınıza anında ulaşın</h1>
        <p>Gelişmiş hedefleme, ayrıntılı raporlama ve tek panelden yönetilen kampanyalarla tanışın.</p>
        <div class="hero-actions">
            <a class="btn-primary" href="<?= asset('admin/login') ?>">Yönetim Paneline Git</a>
            <button class="btn-secondary" id="register-demo">Demo Abonelik</button>
        </div>
        <small class="note">Script ana dizine kurulur, Plesk + AlmaLinux 8 + PHP 8 uyumludur.</small>
    </div>
    <div class="hero-visual">
        <div class="notification-preview">
            <span class="badge badge-success">Yeni</span>
            <h2>Satışları arttıran kampanya</h2>
            <p>Özel indirimleriniz hazır! Şimdi keşfedin.</p>
        </div>
    </div>
</header>

<section class="features">
    <article class="card">
        <h3>Akıllı Segmentasyon</h3>
        <p>Cihaz, konum, etiket ve davranışa göre otomatik gruplamalar.</p>
    </article>
    <article class="card">
        <h3>Gerçek Zamanlı Analitik</h3>
        <p>Gönderilen, açılan ve tıklanan bildirimleri takip edin.</p>
    </article>
    <article class="card">
        <h3>Geliştirici Dostu</h3>
        <p>REST API ile tüm akışlara kolay entegrasyon sağlayın.</p>
    </article>
</section>

<section class="integration">
    <div class="card">
        <h2>API ile kolay entegrasyon</h2>
        <pre><code>POST <?= asset('client/register') ?>
Content-Type: application/json
{
    "endpoint": "https://fcm.googleapis.com/fake",
    "device": "desktop",
    "browser": "chrome",
    "timezone": "Europe/Istanbul",
    "tags": ["premium", "istanbul"]
}</code></pre>
    </div>
</section>
<?php require __DIR__ . '/../partials/footer.php'; ?>
