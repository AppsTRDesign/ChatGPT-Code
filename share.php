<?php
require_once __DIR__ . '/config.php';

$token = $_GET['token'] ?? '';
if ($token === '') {
    http_response_code(404);
    exit('Paylaşım bulunamadı.');
}

$file = fetch_shared_file($pdo, $token);
if (!$file || !is_share_active($file)) {
    if ($file && !is_share_active($file)) {
        revoke_share($pdo, (int) $file['id']);
    }
    http_response_code(404);
    exit('Paylaşım süresi dolmuş veya geçersiz.');
}

$downloadUrl = BASE_URL . '/d/' . urlencode($token);
include __DIR__ . '/templates/header.php';
?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card card-glass p-4">
                    <h1 class="h4 mb-2"><?= sanitize($file['filename']) ?></h1>
                    <p class="text-white-50 small mb-3">Boyut: <?= format_bytes((int) $file['size']) ?> • Yükleme: <?= date('d.m.Y H:i', strtotime($file['uploaded_at'])) ?></p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a class="btn btn-gradient" href="<?= $downloadUrl ?>">Dosyayı İndir</a>
                        <button type="button" class="btn btn-outline-light" data-copy><?= $downloadUrl ?></button>
                    </div>
                    <div class="mt-4">
                        <?php if (str_starts_with($file['type'], 'image/')): ?>
                            <img src="<?= $downloadUrl ?>&preview=1" alt="<?= sanitize($file['filename']) ?>" class="img-fluid rounded">
                        <?php elseif ($file['type'] === 'application/pdf'): ?>
                            <iframe src="<?= $downloadUrl ?>&preview=1" class="w-100" style="min-height: 420px; border-radius: 12px; background: #fff;"></iframe>
                        <?php else: ?>
                            <p class="text-white-50">Bu dosya türü için önizleme desteklenmiyor. İndirme butonunu kullanabilirsiniz.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
    document.querySelector('[data-copy]')?.addEventListener('click', async (event) => {
        const button = event.currentTarget;
        try {
            await navigator.clipboard.writeText(button.textContent.trim());
            Swal.fire({ icon: 'success', title: 'Kopyalandı', text: 'Bağlantı panoya kopyalandı.' });
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Hata', text: 'Bağlantı kopyalanamadı.' });
        }
    });
</script>
<?php include __DIR__ . '/templates/footer.php';
