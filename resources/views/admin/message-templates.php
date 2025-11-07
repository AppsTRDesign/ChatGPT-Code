<?php ob_start(); ?>
<div class="card glass border-0 mb-4">
    <div class="card-body">
        <form data-ajax="true" action="/admin/message-templates" method="post" enctype="multipart/form-data" class="dropzone" id="templateDropzone">
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
                <label class="form-label">Medya (Resim / ZIP / PDF)</label>
                <input type="file" name="file" class="form-control" accept="image/*,.zip,.pdf">
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
<?php $content = ob_get_clean(); include resource_path('views/layouts/app.php');
