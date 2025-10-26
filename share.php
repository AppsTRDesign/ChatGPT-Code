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

$settings = fetch_settings($pdo);
$downloadUrl = BASE_URL . '/d/' . urlencode($token);
$shareDelay = (int) ($settings['share_download_delay'] ?? 0);
$topAd = $settings['ad_share_top_html'] ?? '';
$bottomAd = $settings['ad_share_bottom_html'] ?? '';
include __DIR__ . '/templates/header.php';
?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if (!empty($topAd)): ?>
                    <div class="card card-glass p-3 mb-4">
                        <?= $topAd ?>
                    </div>
                <?php endif; ?>
                <div class="card card-glass p-4">
                    <h1 class="h4 mb-2"><?= sanitize($file['filename']) ?></h1>
                    <p class="text-white-50 small mb-3">Boyut: <?= format_bytes((int) $file['size']) ?> • Yükleme: <?= date('d.m.Y H:i', strtotime($file['uploaded_at'])) ?></p>
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                        <a class="btn btn-gradient" id="downloadBtn" data-delay="<?= $shareDelay ?>" href="<?= $downloadUrl ?>">Dosyayı İndir</a>
                        <?php if ($shareDelay > 0): ?>
                            <span class="badge bg-warning text-dark" data-countdown="<?= $shareDelay ?>">İndirme <?= $shareDelay ?> sn sonra aktif olacak</span>
                        <?php endif; ?>
                    </div>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="shareLinkInput" value="<?= BASE_URL . '/s/' . sanitize($token) ?>" readonly>
                        <button type="button" class="btn btn-outline-light" id="copyShareLink">Kopyala</button>
                    </div>
                    <div class="alert alert-dark border-0 text-white-50 mb-0">
                        Paylaşılan bağlantı <?= $settings['share_expiry_minutes'] ?? 60 ?> dakika boyunca geçerlidir.
                    </div>
                </div>
                <?php if (!empty($bottomAd)): ?>
                    <div class="card card-glass p-3 mt-4">
                        <?= $bottomAd ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<script>
    const copyButton = document.getElementById('copyShareLink');
    const shareInput = document.getElementById('shareLinkInput');
    copyButton?.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(shareInput?.value || '');
            Swal.fire({ icon: 'success', title: 'Kopyalandı', text: 'Bağlantı panoya kopyalandı.' });
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Hata', text: 'Bağlantı kopyalanamadı.' });
        }
    });
    const downloadButton = document.getElementById('downloadBtn');
    downloadButton?.addEventListener('click', (event) => {
        if (event.currentTarget.classList.contains('disabled')) {
            event.preventDefault();
        }
    });
    (function countdown() {
        const button = downloadButton;
        const badge = document.querySelector('[data-countdown]');
        if (!button || !badge) {
            return;
        }
        let remaining = parseInt(badge.dataset.countdown || '0', 10);
        if (!Number.isFinite(remaining) || remaining <= 0) {
            return;
        }
        button.classList.add('disabled');
        button.setAttribute('aria-disabled', 'true');
        const interval = setInterval(() => {
            remaining -= 1;
            if (remaining <= 0) {
                clearInterval(interval);
                badge.textContent = 'İndirme hazır';
                button.classList.remove('disabled');
                button.removeAttribute('aria-disabled');
            } else {
                badge.textContent = `İndirme ${remaining} sn sonra aktif olacak`;
            }
        }, 1000);
    })();
</script>
<?php include __DIR__ . '/templates/footer.php';
