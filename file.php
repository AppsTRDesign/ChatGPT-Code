<?php
require_once __DIR__ . '/config.php';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$file = $id > 0 ? fetch_file($pdo, $id) : null;
if (!$file) {
    http_response_code(404);
    echo 'Dosya bulunamadı.';
    exit;
}
$user = current_user();
$isOwner = $user && (int) $file['user_id'] === (int) $user['id'];
if (!$isOwner && !is_admin() && !(int) $file['is_public']) {
    http_response_code(403);
    exit('Bu dosyayı görüntüleme yetkiniz yok.');
}

$downloadUrl = BASE_URL . '/download.php?id=' . (int) $file['id'];
$previewUrl = $downloadUrl . '&preview=1';
$slug = slugify(pathinfo($file['filename'], PATHINFO_FILENAME));
include __DIR__ . '/templates/header.php';
?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card card-glass p-4" id="file-<?= (int) $file['id'] ?>">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <h1 class="h4 mb-1"><?= sanitize($file['filename']) ?></h1>
                            <p class="text-white-50 small mb-0">Boyut: <?= format_bytes((int) $file['size']) ?> • Yükleme: <?= date('d.m.Y H:i', strtotime($file['uploaded_at'])) ?></p>
                            <?php if (!empty($file['uploader_email'])): ?>
                                <p class="text-white-50 small mb-0">Yükleyen: <?= sanitize($file['uploader_email']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-2">
                        <a class="btn btn-gradient" href="<?= $downloadUrl ?>">İndir</a>
                    </div>
                </div>
                <hr class="text-white-25">
                <?php if (str_starts_with($file['type'], 'image/')): ?>
                        <img src="<?= $previewUrl ?>" alt="<?= sanitize($file['filename']) ?>" class="img-fluid rounded" loading="lazy">
                <?php elseif ($file['type'] === 'application/pdf'): ?>
                        <iframe src="<?= $previewUrl ?>" class="w-100" style="min-height:480px; border-radius:12px; background:#fff;"></iframe>
                <?php else: ?>
                        <p class="text-white-50">Bu dosya türü için önizleme desteklenmiyor. Aşağıdaki bağlantıyı kullanarak indirebilirsiniz.</p>
                <?php endif; ?>
                <div class="mt-3">
                        <p class="text-white-50 small">Kalıcı bağlantı:</p>
                        <code class="text-white-50 d-block"><?= BASE_URL ?>/file/<?= (int) $file['id'] ?>-<?= $slug ?></code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/templates/footer.php'; ?>
