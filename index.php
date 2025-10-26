<?php
require_once __DIR__ . '/config.php';
$settings = fetch_settings($pdo);
include __DIR__ . '/templates/header.php';
?>
<section class="hero-section position-relative py-5 py-lg-5">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="hero-card p-5 h-100">
                    <span class="badge badge-custom mb-3">NoaSoft File Depot</span>
                    <h1 class="display-5 fw-bold mb-3">Dosyalarınızı ışık hızında yönetin.</h1>
                    <p class="lead text-white-50 mb-4">
                        Sürükle &amp; bırak yüklemeler, otomatik paylaşımlar, paket temelli kotalar ve gelişmiş güvenlik politikaları tek platformda birleşti. Müşterilerinize kurumsal seviyede dosya deneyimi yaşatın.
                    </p>
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-hdd-network text-accent"></i>
                            <span class="text-white-50">Sınıfının en hızlı depolama mimarisi</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-shield-lock text-accent"></i>
                            <span class="text-white-50">MIME, CSRF ve paylaşım süreleri ile koruma</span>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="<?= current_user() ? BASE_URL . (is_admin() ? '/admin' : '/client') : BASE_URL . '/login' ?>" class="btn btn-gradient btn-lg">
                            <i class="bi bi-speedometer2 me-2"></i>Kontrol Paneline Git
                        </a>
                        <a href="<?= BASE_URL ?>/register" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-person-plus me-2"></i>Hemen Üye Ol
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card hero-card p-4 shadow-lg border-0">
                    <div class="d-flex flex-column gap-4">
                        <div>
                            <h2 class="h4 text-white mb-2">Gerçek Zamanlı Dosya Akışı</h2>
                            <p class="text-white-50 mb-0">Sıralı yükleme kuyruğu, canlı ilerleme ve otomatik paket kontrolleriyle hata payını azaltın.</p>
                        </div>
                        <div class="d-flex gap-3">
                            <div class="flex-fill text-center p-3 card-glass">
                                <div class="h4 mb-1 text-white">50MB</div>
                                <p class="text-white-50 extra-small mb-0">Tekil dosya yükleme limiti</p>
                            </div>
                            <div class="flex-fill text-center p-3 card-glass">
                                <div class="h4 mb-1 text-white">7/24</div>
                                <p class="text-white-50 extra-small mb-0">Otomatik paylaşılan link yönetimi</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-circle">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                            <p class="text-white-50 mb-0">Günlük ve yıllık yükleme raporlarıyla sisteminizi ölçümlerin.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card card-glass p-4 h-100 text-center">
                    <div class="icon-circle mb-3"><i class="bi bi-cloud-arrow-up"></i></div>
                    <h3 class="h6 text-white">Dropzone Kuyrukları</h3>
                    <p class="text-white-50 small mb-0">Manuel başlatılan yüklemeler, iptal edilebilir görevler ve tek tıkla temizlenen listeler.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-glass p-4 h-100 text-center">
                    <div class="icon-circle mb-3"><i class="bi bi-folder-symlink"></i></div>
                    <h3 class="h6 text-white">Akıllı Klasörler</h3>
                    <p class="text-white-50 small mb-0">Şifresiz hızlı klasör açma, paylaşılan linkler ve ikon bazlı görünümle masaüstü hissi.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-glass p-4 h-100 text-center">
                    <div class="icon-circle mb-3"><i class="bi bi-credit-card"></i></div>
                    <h3 class="h6 text-white">Akıllı Paketler</h3>
                    <p class="text-white-50 small mb-0">Iyzico ve banka havalesi seçenekleri, admin onayı ve otomatik paket ataması.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-glass p-4 h-100 text-center">
                    <div class="icon-circle mb-3"><i class="bi bi-lightning-charge"></i></div>
                    <h3 class="h6 text-white">Anlık Bildirimler</h3>
                    <p class="text-white-50 small mb-0">SweetAlert destekli geri bildirimler ve anında aksiyon çağrıları.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 border-top border-light-subtle">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <h2 class="h3 text-white mb-3">Dakikalar içinde kullanıma hazır.</h2>
                <p class="text-white-50 mb-4">Kurulumdan kullanıcı yönetimine kadar tüm adımlar otomatikleştirildi. Sadece veritabanınızı tanımlayın, gerisini platform üstlensin.</p>
                <ul class="timeline list-unstyled text-white-50">
                    <li>
                        <div class="timeline-bullet"></div>
                        <div>
                            <strong class="text-white">1. Kaydol / Giriş Yap</strong>
                            <p class="mb-0 small">Üyelik işlemleri, e-posta doğrulama ve şifre sıfırlama tek akış.</p>
                        </div>
                    </li>
                    <li>
                        <div class="timeline-bullet"></div>
                        <div>
                            <strong class="text-white">2. Paketi Seç</strong>
                            <p class="mb-0 small">Iyzico veya havale ile ödeme, admin onayından sonra otomatik paket ataması.</p>
                        </div>
                    </li>
                    <li>
                        <div class="timeline-bullet"></div>
                        <div>
                            <strong class="text-white">3. Dosya Yönet</strong>
                            <p class="mb-0 small">Sıralama, çoklu seçim, zipleme ve paylaşım ayarlarıyla dosyalarınızı kontrol edin.</p>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="col-lg-7">
                <div class="card card-glass p-4">
                    <h3 class="h5 text-white mb-4">Yönetici Panosu Özetleri</h3>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="mini-card">
                                <h4 class="h6 text-white mb-1">Grafikli Raporlar</h4>
                                <p class="text-white-50 small mb-0">Günlük, haftalık ve yıllık yükleme verilerini Chart.js grafikleriyle inceleyin.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mini-card">
                                <h4 class="h6 text-white mb-1">PDF / Excel Aktarımı</h4>
                                <p class="text-white-50 small mb-0">İndirilebilir raporlarla denetim süreçlerini hızlandırın.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mini-card">
                                <h4 class="h6 text-white mb-1">Reklam Alanları</h4>
                                <p class="text-white-50 small mb-0">Paylaşım sayfalarına ve dashboard'a özel reklam blokları tanımlayın.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mini-card">
                                <h4 class="h6 text-white mb-1">Detaylı Paket Kontrolü</h4>
                                <p class="text-white-50 small mb-0">Dosya türleri, kota ve yükleme hızlarını paket bazında yönetin.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between mb-4">
            <div>
                <h2 class="h3 text-white mb-2">Hazır Paketlerle Hemen Başlayın</h2>
                <p class="text-white-50 mb-0">Depolama kapasitesi, eş zamanlı yükleme ve izin verilen MIME türlerini dilediğiniz gibi tanımlayın.</p>
            </div>
            <a href="<?= BASE_URL ?>/contact" class="btn btn-outline-light"><i class="bi bi-chat-dots me-2"></i>Satış ile İletişime Geç</a>
        </div>
        <div class="row g-4">
            <?php
            $packages = $pdo->query('SELECT * FROM packages WHERE is_active = 1 ORDER BY price ASC')->fetchAll();
            foreach ($packages as $package):
                $features = json_decode($package['features'] ?? '[]', true) ?: [];
            ?>
            <div class="col-md-4">
                <div class="card card-glass h-100 p-4 text-center">
                    <span class="badge badge-custom mb-3"><?= $package['price'] > 0 ? 'Premium' : 'Ücretsiz' ?></span>
                    <h3 class="h4 text-white mb-3"><?= sanitize($package['name']) ?></h3>
                    <p class="display-6 fw-bold text-white mb-2"><?= $package['price'] > 0 ? number_format($package['price'], 2) . '₺' : '0₺' ?></p>
                    <p class="text-white-50 small mb-3">Depolama: <?= format_bytes((int) $package['storage_limit']) ?> • Paralel: <?= (int) $package['max_concurrent_uploads'] ?></p>
                    <ul class="list-unstyled text-white-50 mb-4">
                        <?php foreach ($features as $feature): ?>
                            <li>• <?= sanitize($feature) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= BASE_URL . (current_user() ? '/client/packages' : '/register') ?>" class="btn btn-gradient w-100">Paketi İncele</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/templates/footer.php';
?>
