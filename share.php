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
$folder = null;
if (!empty($file['folder_id'])) {
    $folder = fetch_folder($pdo, (int) $file['folder_id']);
}

$requiresPassword = false;
if ($folder && !empty($folder['password_hash'])) {
    $requiresPassword = !empty($settings['share_password_required']) || (int) $folder['is_protected'] === 1;
}

$passwordVerified = !$requiresPassword;
$passwordError = null;
if ($requiresPassword) {
    if (!empty($_SESSION['share_access'][$token])) {
        $passwordVerified = true;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $candidate = trim($_POST['share_password'] ?? '');
        if ($candidate !== '' && password_verify($candidate, $folder['password_hash'])) {
            $_SESSION['share_access'][$token] = true;
            $passwordVerified = true;
        } else {
            $passwordError = 'Şifre doğrulanamadı. Tekrar deneyin.';
        }
    }
}

if (!$passwordVerified) {
    include __DIR__ . '/templates/header.php';
    ?>
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card card-glass p-4">
                        <h1 class="h5 mb-3">Klasör Şifresi Gerekli</h1>
                        <p class="text-white-50">Bu paylaşımı görüntülemek için klasör şifresini girmeniz gerekiyor.</p>
                        <?php if ($passwordError): ?>
                            <div class="alert alert-danger border-0"><?= sanitize($passwordError) ?></div>
                        <?php endif; ?>
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label" for="share_password">Şifre</label>
                                <input type="password" name="share_password" id="share_password" class="form-control" required autocomplete="current-password">
                            </div>
                            <button type="submit" class="btn btn-gradient w-100">Doğrula</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php
    include __DIR__ . '/templates/footer.php';
    exit;
}

$downloadUrl = BASE_URL . '/d/' . urlencode($token);
$shareDelay = (int) ($settings['share_download_delay'] ?? 0);
$topAd = $settings['ad_share_top_html'] ?? '';
$bottomAd = $settings['ad_share_bottom_html'] ?? '';
$previewUrl = BASE_URL . '/uploads/' . ltrim($file['stored_name'], '/');
$mimeType = $file['type'] ?? '';
$isVideo = str_starts_with($mimeType, 'video/');
$isAudio = str_starts_with($mimeType, 'audio/');
$isImage = str_starts_with($mimeType, 'image/');
$isPdf = $mimeType === 'application/pdf';

log_file_access($pdo, $file, current_user(), $token);
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
                    <?php if ($isVideo): ?>
                        <div class="ratio ratio-16x9 mb-4">
                            <video controls preload="metadata" class="rounded border border-secondary-subtle">
                                <source src="<?= $previewUrl ?>" type="<?= sanitize($mimeType) ?>">
                                Tarayıcınız video oynatmayı desteklemiyor.
                            </video>
                        </div>
                    <?php elseif ($isAudio): ?>
                        <div class="mb-4">
                            <audio controls class="w-100">
                                <source src="<?= $previewUrl ?>" type="<?= sanitize($mimeType) ?>">
                                Tarayıcınız ses oynatmayı desteklemiyor.
                            </audio>
                        </div>
                    <?php elseif ($isImage): ?>
                        <div class="mb-4 text-center">
                            <img src="<?= $previewUrl ?>" alt="Önizleme" class="img-fluid rounded shadow-sm border border-secondary-subtle">
                        </div>
                    <?php elseif ($isPdf): ?>
                        <div class="mb-4">
                            <iframe src="<?= $previewUrl ?>" class="w-100 rounded" style="min-height:420px" title="PDF Önizleme"></iframe>
                        </div>
                    <?php endif; ?>
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
