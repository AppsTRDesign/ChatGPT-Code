<?php
/** @var array $allowedExtensions */
$extensions = $allowedExtensions ?? [];
$accepted = $extensions ? implode(',', array_map(static fn($ext) => '.' . ltrim($ext, '.'), $extensions)) : '';
$extensionsLabel = $extensions ? implode(', ', array_map(static fn($ext) => strtoupper($ext), $extensions)) : 'Belirtilmedi';
?>
<?php ob_start(); ?>
<div class="template-card card glass border-0 mb-4 overflow-hidden">
    <form data-ajax="true" action="/admin/message-templates" method="post" enctype="multipart/form-data" id="templateForm" class="row g-0 align-items-stretch">
        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
        <div class="col-lg-7">
            <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                <div class="row g-4">
                    <div class="col-12">
                        <label class="form-label text-uppercase small text-secondary">Şablon Başlığı</label>
                        <input type="text" name="title" class="form-control form-control-lg bg-dark-subtle border-0 text-light" placeholder="Karşılama Mesajı">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-uppercase small text-secondary">Mesaj İçeriği</label>
                        <textarea name="body" class="form-control bg-dark-subtle border-0 text-light" rows="5" placeholder="Merhaba ..."></textarea>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <button class="btn btn-primary px-4" type="submit">Şablonu Kaydet</button>
                </div>
            </div>
        </div>
        <div class="col-lg-5 template-upload-column">
            <div class="h-100 p-4 p-lg-5 bg-dark-gradient d-flex flex-column justify-content-center">
                <div class="text-center mb-3">
                    <h6 class="text-light mb-1">Medya / Dosya</h6>
                    <p class="text-secondary small mb-0">İzin verilen uzantılar: <?= htmlspecialchars($extensionsLabel) ?></p>
                </div>
                <div id="templateUploadZone" class="dropzone dropzone-themed rounded-4 py-5">
                    <div class="dz-message">
                        <span class="d-block fw-semibold text-light">Dosyanızı buraya bırakın</span>
                        <small class="text-secondary">veya tıklayarak seçin</small>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="row g-4">
    <?php foreach ($templates as $template): ?>
        <div class="col-md-4">
            <div class="card glass border-0 h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <h5 class="card-title text-light mb-0"><?= htmlspecialchars($template['title']) ?></h5>
                        <span class="badge bg-primary-subtle text-primary-emphasis">Şablon</span>
                    </div>
                    <p class="text-secondary flex-grow-1">
                        <?= nl2br(htmlspecialchars($template['body'])) ?>
                    </p>
                    <?php if ($template['attachment_path']): ?>
                        <a href="/storage/uploads/<?= htmlspecialchars($template['attachment_path']) ?>" class="btn btn-outline-info btn-sm mb-3" target="_blank">Ek Dosyayı Görüntüle</a>
                    <?php endif; ?>
                    <form data-ajax="true" method="post" action="/admin/message-templates/<?= (int) $template['id'] ?>/delete" class="mt-auto">
                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                        <button class="btn btn-outline-danger w-100">Sil</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php if (!$templates): ?>
        <div class="col-12">
            <div class="alert alert-info glass border-0">Henüz şablon tanımlanmadı.</div>
        </div>
    <?php endif; ?>
</div>
<style>
    .template-card { background: linear-gradient(135deg, rgba(30, 41, 59, 0.9), rgba(15, 23, 42, 0.95)); box-shadow: 0 25px 45px rgba(2, 6, 23, 0.55); }
    .template-upload-column { border-top: 1px solid rgba(148, 163, 184, 0.2); border-left: 0; }
    @media (min-width: 992px) {
        .template-upload-column { border-top: 0; border-left: 1px solid rgba(148, 163, 184, 0.2); }
    }
    .template-card .bg-dark-gradient { background: radial-gradient(circle at top, rgba(56, 189, 248, 0.15), rgba(30, 41, 59, 0.85)); border-radius: 0 0 0 80px; }
    .template-card textarea { min-height: 180px; resize: vertical; }
    .dropzone-themed { border: 2px dashed rgba(148, 163, 184, 0.4); background: rgba(15, 23, 42, 0.6); display: flex; align-items: center; justify-content: center; text-align: center; transition: border-color 0.25s ease, transform 0.25s ease; }
    .dropzone-themed.dz-drag-hover { border-color: #38bdf8; transform: translateY(-2px); }
    .dropzone-themed .dz-preview { margin: 0.75rem; }
    .dropzone-themed .dz-preview .dz-image { width: 120px; height: 120px; border-radius: 1rem; overflow: hidden; }
    .dropzone-themed .dz-preview .dz-image img { width: 100%; height: 100%; object-fit: cover; }
    .dropzone-themed .dz-remove { color: #f87171; font-weight: 500; }
    .dropzone-themed .dz-message { margin: 0; }
    .dropzone-themed .dz-hidden-input { display: none !important; }
    .dropzone-themed input[type="file"] { display: none; }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (!window.Dropzone) {
        return;
    }

    const form = document.getElementById('templateForm');
    const zone = document.getElementById('templateUploadZone');
    if (!form || !zone) {
        return;
    }

    const accepted = <?= json_encode($accepted) ?>;
    const dz = new Dropzone(zone, {
        url: form.getAttribute('action'),
        autoProcessQueue: false,
        uploadMultiple: false,
        maxFiles: 1,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        paramName: 'file',
        addRemoveLinks: true,
        dictRemoveFile: 'Kaldır',
        acceptedFiles: accepted || null,
    });

    dz.on('addedfile', () => {
        while (dz.files.length > 1) {
            dz.removeFile(dz.files[0]);
        }
    });

    form.dropzoneInstance = dz;
});
</script>
<?php $content = ob_get_clean(); include resource_path('views/layouts/app.php');
