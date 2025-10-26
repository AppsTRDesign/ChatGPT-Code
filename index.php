<?php
require_once __DIR__ . '/config.php';
$settings = fetch_settings($pdo);
include __DIR__ . '/templates/header.php';
?>
<section class="hero-section">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6">
                <div class="hero-card p-5">
                    <h1 class="display-5 fw-bold mb-3">Güçlü ve Güvenli Dosya Deposu</h1>
                    <p class="lead text-white-50 mb-4">
                        Modern arayüz, drag & drop yükleme, gelişmiş yönetim araçları ve paket seçenekleri ile NoaSoft Depo işletmenizin dosya yönetimini kolaylaştırır.
                    </p>
                    <ul class="list-unstyled text-white-50 mb-4">
                        <li class="mb-2">✔ 50MB'a kadar güvenli dosya yükleme</li>
                        <li class="mb-2">✔ SEO dostu bağlantılar ve önizlemeler</li>
                        <li class="mb-2">✔ Yönetici ve kullanıcı panelleri</li>
                        <li>✔ Ajax tabanlı hızlı etkileşim</li>
                    </ul>
                    <div class="d-flex gap-3">
                        <a href="#yukle" class="btn btn-gradient btn-lg">Hemen Yükle</a>
                        <a href="<?= BASE_URL ?>/register" class="btn btn-outline-light btn-lg">Ücretsiz Üye Ol</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card hero-card p-4" id="yukle">
                    <?php if (!current_user()): ?>
                        <div class="alert alert-warning bg-transparent border border-warning text-white">
                            Dosya yüklemek için lütfen giriş yapın.
                        </div>
                    <?php endif; ?>
                    <form action="<?= BASE_URL ?>/api/upload.php" class="dropzone" id="uploadZone" data-require-auth="1">
                        <div class="dz-message">
                            Dosyalarınızı sürükleyip bırakın veya tıklayın.<br>
                            <small class="text-white-50">Maksimum dosya boyutu: 50MB</small>
                        </div>
                    </form>
                    <p class="mt-3 text-white-50 small">Desteklenen türler: JPG, PNG, GIF, PDF, ZIP, DOCX, XLSX ve daha fazlası.</p>
                </div>
            </div>
        </div>
    </div>
</section>
<section id="ozellikler" class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-glass h-100 p-4">
                    <h3 class="h5 fw-semibold text-white">Anlık Önizleme</h3>
                    <p class="text-white-50">Görüntü ve PDF dosyaları için hızlı önizleme ile dosyalarınızı paylaşmadan önce kontrol edin.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-glass h-100 p-4">
                    <h3 class="h5 fw-semibold text-white">Gelişmiş Güvenlik</h3>
                    <p class="text-white-50">CSRF koruması, MIME kontrolü ve erişim yönetimi ile verileriniz güvende.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-glass h-100 p-4">
                    <h3 class="h5 fw-semibold text-white">SEO Dostu Linkler</h3>
                    <p class="text-white-50">Dosyalarınızı kolayca paylaşın, arama motorları için optimize edilmiş bağlantılarla erişin.</p>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="py-5">
    <div class="container">
        <h2 class="h3 text-center mb-4">Hazır Paketler</h2>
        <div class="row g-4">
            <?php
            $packages = $pdo->query('SELECT * FROM packages WHERE is_active = 1 ORDER BY price ASC')->fetchAll();
            foreach ($packages as $package):
                $features = json_decode($package['features'] ?? '[]', true) ?: [];
            ?>
            <div class="col-md-4">
                <div class="card card-glass h-100 p-4 text-center">
                    <h3 class="h4 text-white mb-3"><?= sanitize($package['name']) ?></h3>
                    <p class="display-6 fw-bold text-white"><?= $package['price'] > 0 ? number_format($package['price'], 2) . '₺' : 'Ücretsiz' ?></p>
                    <span class="badge badge-custom mb-3">Depo Alanı: <?= format_bytes((int) $package['storage_limit']) ?></span>
                    <ul class="list-unstyled text-white-50 mb-4">
                        <?php foreach ($features as $feature): ?>
                            <li>• <?= sanitize($feature) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= BASE_URL ?>/register?package=<?= (int) $package['id'] ?>" class="btn btn-gradient w-100">Hemen Başla</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/templates/footer.php';
?>
