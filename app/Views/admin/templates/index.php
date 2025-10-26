<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
    <div class="col-12 col-xl-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Yeni Şablon Oluştur</h2>
            </div>
            <div class="card-body">
                <form data-ajax="true" data-endpoint="/admin/templates/store" data-refresh="#admin-templates-table" data-json="true" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label">Müşteri (Opsiyonel)</label>
                        <select name="client_id" class="form-select">
                            <option value="">Tüm Müşteriler İçin</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Şablon Adı</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" placeholder="template-kampanya">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İçerik (HTML)</label>
                        <textarea name="content" class="form-control" rows="8" data-html-editor="true" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Durum</label>
                        <select name="status" class="form-select">
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Şablon Oluştur</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Şablon Kütüphanesi</h2>
            </div>
            <div class="card-body">
                <p class="text-muted small">Şablon satırlarını seçerek içeriği kopyalayabilir veya güncelleme için düzenleme formunu kullanabilirsiniz.</p>
                <div class="mb-3">
                    <label class="form-label">Şablon İçeriği Önizleme</label>
                    <div id="template-preview" class="template-preview border rounded p-3 text-muted">Bir şablon seçin veya yeni içerik oluşturun.</div>
                </div>
                <form id="template-update-form" data-ajax="true" data-endpoint="/admin/templates/update" data-refresh="#admin-templates-table" data-json="true">
                    <input type="hidden" name="id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Ad</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Durum</label>
                            <select name="status" class="form-select">
                                <option value="active">Aktif</option>
                                <option value="inactive">Pasif</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">İçerik</label>
                            <textarea name="content" class="form-control" rows="6" data-html-editor="true" required></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-success w-100">Şablonu Güncelle</button>
                        </div>
                    </div>
                </form>
                <hr>
                <form data-ajax="true" data-endpoint="/admin/templates/delete" data-refresh="#admin-templates-table">
                    <div class="row g-2 align-items-end">
                        <div class="col">
                            <label class="form-label">Şablon ID</label>
                            <input type="number" name="id" class="form-control" required>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-outline-danger">Şablonu Sil</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<section class="mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Mevcut Şablonlar</h2>
        <button class="btn btn-outline-primary btn-sm" data-action="refresh" data-target="#admin-templates-table">Yenile</button>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table class="table table-hover" id="admin-templates-table" data-source="/admin/templates/data" data-columns='["name","slug","status","client_id","updated_at"]' data-fill-form="#template-update-form">
                <thead>
                    <tr>
                        <th>Ad</th>
                        <th>Slug</th>
                        <th>Durum</th>
                        <th>Müşteri</th>
                        <th>Güncelleme</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const table = document.getElementById('admin-templates-table');
        const preview = document.getElementById('template-preview');
        const updateForm = document.getElementById('template-update-form');

        if (table) {
            table.addEventListener('click', (event) => {
                const row = event.target.closest('tr[data-row]');
                if (!row) {
                    return;
                }

                try {
                    const record = JSON.parse(row.getAttribute('data-row'));
                    if (preview) {
                        const hasContent = !!record.content;
                        preview.innerHTML = hasContent ? record.content : 'Bir şablon seçin veya yeni içerik oluşturun.';
                        preview.classList.toggle('text-muted', !hasContent);
                    }
                    if (updateForm) {
                        updateForm.querySelector('[name="id"]').value = record.id;
                        updateForm.querySelector('[name="name"]').value = record.name;
                        updateForm.querySelector('[name="status"]').value = record.status;
                        const contentField = updateForm.querySelector('[name="content"]');
                        contentField.value = record.content;
                        contentField.dispatchEvent(new CustomEvent('html-editor:update', { detail: record.content }));
                    }
                } catch (error) {
                    console.error('Şablon satırı parse edilemedi', error);
                }
            });
        }
    });
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
