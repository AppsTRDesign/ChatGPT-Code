<?php
require_once __DIR__ . '/includes/functions.php';

$title = 'NoaSoft Converter | Ana Sayfa';
$active = 'home';
$maxFiles = (int) ns_config('upload.max_files', 5);
$maxSize = (int) ns_config('upload.max_size_mb', 2048);
include __DIR__ . '/includes/header.php';
?>
<section class="ns-hero">
    <h1>Ses, Video ve Görseller için <span>Tek Nokta</span></h1>
    <p>ffmpeg 4.2.10 destekli güçlü altyapımız ile medya dosyalarınızı saniyeler içinde dönüştürün.</p>
    <div class="ns-cta">
        <a href="<?= htmlspecialchars(ns_link('audio', '/ses-donusturucu.php')) ?>" class="ns-btn ns-btn-primary">Ses Dönüştür</a>
        <a href="<?= htmlspecialchars(ns_link('video', '/video-donusturucu.php')) ?>" class="ns-btn">Video Dönüştür</a>
        <a href="<?= htmlspecialchars(ns_link('image', '/gorsel-donusturucu.php')) ?>" class="ns-btn">Görsel Dönüştür</a>
    </div>
</section>
<section class="ns-cards">
    <article class="ns-card">
        <h3>Mobil Uyumlu Tasarım</h3>
        <p>Modern mavi-siyah tema, yüksek kontrast ve sezgisel kullanıcı deneyimi ile tüm cihazlarda kusursuz.</p>
    </article>
    <article class="ns-card">
        <h3>Güçlü Dönüştürme Motoru</h3>
        <p>FFmpeg ve optimize edilmiş PHP komutları ile ses, video ve görseller için ön tanımlı sosyal medya presetleri.</p>
    </article>
    <article class="ns-card">
        <h3>Güvenli & Hızlı</h3>
        <p><?= $maxFiles ?> dosyaya kadar sürükle-bırak yükleme, <?= $maxSize ?> MB sınır kontrolü, gerçek zamanlı ilerleme takibi ve güvenli indirme.</p>
    </article>
</section>
<section class="ns-tool" style="margin-top:40px;">
    <h2>Nasıl Çalışır?</h2>
    <ol class="ns-steps">
        <li>Dönüştürmek istediğiniz aracı (ses, video, görsel) seçin.</li>
        <li>Dosyalarınızı sürükle-bırak ya da dosya seç ile yükleyin.</li>
        <li>Preset seçin veya parametreleri özelleştirin.</li>
        <li>"Dönüştür" butonuna tıklayın ve gerçek zamanlı ilerleme çubuklarını izleyin.</li>
        <li>İşlem tamamlandığında indirme butonu aktif olacaktır.</li>
    </ol>
</section>
<section class="ns-tool" style="margin-top:40px;">
    <h2>Öne Çıkan Özellikler</h2>
    <ul class="ns-list">
        <li>Çoklu yüklemelerde her dosya için ayrı ilerleme çubuğu</li>
        <li>Sosyal medya presetleri (YouTube, Instagram, TikTok, Facebook ve daha fazlası)</li>
        <li><?= $maxSize ?> MB'a kadar yüksek boyutlu dosya desteği</li>
        <li>SweetAlert2 tabanlı modern uyarı sistemi</li>
        <li>AJAX altyapısı ile sayfa yenilemeden dönüşüm</li>
    </ul>
</section>
<?php
include __DIR__ . '/includes/footer.php';
