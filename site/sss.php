<?php
require_once __DIR__ . '/includes/functions.php';

$title = 'Sıkça Sorulan Sorular | NoaSoft Converter';
$active = 'faq';
$maxSize = (int) ns_config('upload.max_size_mb', 2048);
$maxFiles = (int) ns_config('upload.max_files', 5);
include __DIR__ . '/includes/header.php';
?>
<section class="ns-tool">
    <h1>Sıkça Sorulan Sorular</h1>
    <details class="ns-faq" open>
        <summary>Hangi dosya türlerini destekliyorsunuz?</summary>
        <p>Ses için MP3, WAV, AAC, FLAC, M4A; video için MP4, MOV, MKV, WEBM, GIF; görsel için JPG, PNG, WEBP ve GIF formatlarını destekliyoruz.</p>
    </details>
    <details class="ns-faq">
        <summary>Maksimum dosya boyutu nedir?</summary>
        <p>Her bir dosya için maksimum <?= $maxSize ?> MB yükleme limitimiz bulunmaktadır ve aynı anda <?= $maxFiles ?> dosya yükleyebilirsiniz.</p>
    </details>
    <details class="ns-faq">
        <summary>Dönüştürme işlemleri ne kadar sürer?</summary>
        <p>Dosya boyutu ve seçilen ayarlara göre değişmekle birlikte, gerçek zamanlı yükleme ve dönüştürme çubuklarından süreci takip edebilirsiniz.</p>
    </details>
    <details class="ns-faq">
        <summary>Dosyalarım güvende mi?</summary>
        <p>Yüklenen dosyalar dönüşümden sonra düzenli aralıklarla temizlenir. Paylaşılan bağlantılar gizlidir ve tahmin edilmesi zordur.</p>
    </details>
    <details class="ns-faq">
        <summary>Presetler üzerinde değişiklik yapabilir miyim?</summary>
        <p>Evet, hazır ayarları seçtiğinizde alanlar kilitlenir; özel ayarı seçerek tüm parametreleri değiştirebilirsiniz.</p>
    </details>
</section>
<?php
include __DIR__ . '/includes/footer.php';
?>
