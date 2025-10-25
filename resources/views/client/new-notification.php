<?php ob_start(); ?>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card p-4">
            <h5>Yeni Bildirim Oluştur</h5>
            <form method="post" class="row g-3">
                <div class="col-12">
                    <label class="form-label">Başlık</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Mesaj</label>
                    <textarea name="message" class="form-control" rows="3" required></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Bağlantı (Opsiyonel)</label>
                    <input type="url" name="link" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Şablon</label>
                    <select class="form-select" name="template_id">
                        <option value="">Varsayılan</option>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?= $template['id'] ?>"><?= $template['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Görsel</label>
                    <div class="dropzone"></div>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">Bildirimi Gönder</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-4">
            <h5>Şablon Önizleme</h5>
            <div class="border rounded p-3 bg-white" id="templatePreview">Bir şablon seçerek önizleyebilirsiniz.</div>
        </div>
    </div>
</div>
<script>
    const select = document.querySelector('select[name="template_id"]');
    const preview = document.getElementById('templatePreview');
    const templates = <?= json_encode($templates, JSON_UNESCAPED_UNICODE) ?>;
    select?.addEventListener('change', function() {
        const selected = templates.find(t => String(t.id) === select.value);
        preview.innerHTML = selected ? selected.html : 'Bir şablon seçerek önizleyebilirsiniz.';
    });
</script>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
