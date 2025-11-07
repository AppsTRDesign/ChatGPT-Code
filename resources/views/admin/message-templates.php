<?php
/** @var array $allowedExtensions */
$extensions = $allowedExtensions ?? [];
$accepted = $extensions ? implode(',', array_map(static fn($ext) => '.' . ltrim($ext, '.'), $extensions)) : '';
$extensionsLabel = $extensions ? implode(', ', array_map(static fn($ext) => strtoupper($ext), $extensions)) : 'Belirtilmedi';
?>
<?php ob_start(); ?>
<div class="card glass border-0 mb-4">
    <div class="card-body">
        <form data-ajax="true" action="/admin/message-templates" method="post" enctype="multipart/form-data" id="templateForm">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Şablon Başlığı</label>
                    <input type="text" name="title" class="form-control" placeholder="Karşılama Mesajı">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Mesaj İçeriği</label>
                    <textarea name="body" class="form-control" rows="3" placeholder="Merhaba ..."></textarea>
                </div>
            </div>
            <div class="mt-3">
                <label class="form-label">Medya Yükleme</label>
                <div id="templateUploadZone" class="dropzone dz-clickable rounded-3 p-4 text-center text-secondary">
                    <div class="dz-message">
                        Dosyanızı buraya bırakın ya da tıklayın.
                    </div>
                </div>
                <small class="text-muted d-block mt-2">İzin verilen uzantılar: <?= htmlspecialchars($extensionsLabel) ?></small>
            </div>
            <div class="mt-3 text-end">
                <button class="btn btn-primary" type="submit">Şablon Kaydet</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($templates as $template): ?>
        <div class="col-md-4">
            <div class="card glass border-0 h-100">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-light"><?= htmlspecialchars($template['title']) ?></h5>
                    <p class="text-secondary flex-grow-1">
                        <?= nl2br(htmlspecialchars($template['body'])) ?>
                    </p>
                    <?php if ($template['attachment_path']): ?>
                        <a href="/storage/uploads/<?= htmlspecialchars($template['attachment_path']) ?>" class="btn btn-outline-info btn-sm mb-2" target="_blank">Ek Dosyayı Görüntüle</a>
                    <?php endif; ?>
                    <div class="d-flex gap-2">
                        <form data-ajax="true" method="post" action="/admin/message-templates/<?= (int) $template['id'] ?>/delete" class="w-100">
                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                            <button class="btn btn-outline-danger w-100">Sil</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$templates): ?>
        <div class="col-12">
            <div class="alert alert-info">Henüz şablon tanımlanmadı.</div>
        </div>
    <?php endif; ?>
</div>
<style>
    #templateUploadZone { border: 2px dashed rgba(148, 163, 184, 0.35); background: rgba(15, 23, 42, 0.45); transition: border-color 0.2s ease; }
    #templateUploadZone.dz-drag-hover { border-color: #38bdf8; }
    #templateUploadZone.dz-started .dz-message { display: none; }
    #templateUploadZone .dz-preview .dz-image { width: 120px; height: 120px; }
    #templateUploadZone .dz-preview .dz-image img { object-fit: cover; width: 100%; height: 100%; }
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
