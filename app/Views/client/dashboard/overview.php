<?php include __DIR__ . '/../layout/header.php'; ?>
<section class="hero-section py-5">
    <div class="row align-items-center g-4">
        <div class="col-12 col-lg-6">
            <h1 class="display-5 fw-bold text-white">Onesignal Tarzında Web Push Platformu</h1>
            <p class="lead text-white-50">NoaSoft Web Push ile ziyaretçilerinize hedefli bildirimler gönderin, kampanyalarınızı otomatikleştirin ve gerçek zamanlı performansı takip edin.</p>
            <div class="d-flex flex-wrap gap-3">
                <a href="/login" class="btn btn-primary btn-lg">Panele Giriş</a>
                <a href="#ozellikler" class="btn btn-outline-light btn-lg">Özellikler</a>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-lg gradient-card p-4">
                <h2 class="h4 text-white">Öne Çıkan Özellikler</h2>
                <ul class="list-unstyled text-white-50 mt-3">
                    <li><i class="bi bi-bell-fill me-2"></i>Gelişmiş API yönetimi ve token bazlı gönderim</li>
                    <li><i class="bi bi-geo-alt-fill me-2"></i>GeoIP2 ile lokasyon bazlı hedefleme</li>
                    <li><i class="bi bi-device-hdd-fill me-2"></i>DeviceDetector ile platform & cihaz analizi</li>
                    <li><i class="bi bi-credit-card-2-front-fill me-2"></i>iyzico ile abonelik ve ödeme yönetimi</li>
                </ul>
            </div>
        </div>
    </div>
</section>
<section id="ozellikler" class="py-5">
    <h2 class="h3 text-white mb-4">Platform Özellikleri</h2>
    <div class="row g-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="feature-card p-4 h-100">
                <h3 class="h5">Bildirim Şablonları</h3>
                <p>10 adet responsive bildirim şablonu ile anında yayına hazır olun.</p>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="feature-card p-4 h-100">
                <h3 class="h5">Gerçek Zamanlı Analiz</h3>
                <p>JSON tabanlı veri kaynakları ile dashboard verilerini yenileyin.</p>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="feature-card p-4 h-100">
                <h3 class="h5">Ajax Tabanlı Yönetim</h3>
                <p>Formlar, tablolar ve grafikler tamamen Ajax ile çalışır.</p>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="feature-card p-4 h-100">
                <h3 class="h5">Güvenli Erişim</h3>
                <p>JSON çıktıları sadece yetkili oturumlar tarafından erişilebilir.</p>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../layout/footer.php'; ?>
