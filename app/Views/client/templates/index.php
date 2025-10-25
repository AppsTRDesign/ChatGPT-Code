<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Şablon Oluştur</h2>
            </div>
            <div class="card-body">
                <form data-ajax="true" data-endpoint="/client/templates/store" data-refresh="#client-templates-table" data-json="true">
                    <div class="mb-3">
                        <label class="form-label">Şablon Adı</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" placeholder="kampanya-yilbasi">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İçerik (HTML)</label>
                        <textarea name="content" class="form-control" rows="6" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Şablon Oluştur</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Şablon Güncelle</h2>
            </div>
            <div class="card-body">
                <form id="client-template-update" data-ajax="true" data-endpoint="/client/templates/update" data-refresh="#client-templates-table" data-json="true">
                    <input type="hidden" name="id">
                    <div class="mb-3">
                        <label class="form-label">Ad</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Durum</label>
                        <select name="status" class="form-select">
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İçerik</label>
                        <textarea name="content" class="form-control" rows="6" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Güncelle</button>
                    <button type="button" class="btn btn-outline-danger ms-2" id="client-template-delete">Sil</button>
                </form>
            </div>
        </div>
    </div>
</div>
<section class="mt-5">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table class="table table-hover" id="client-templates-table" data-source="/client/templates/data" data-columns='["name","status","updated_at"]' data-fill-form="#client-template-update">
                <thead>
                    <tr>
                        <th>Ad</th>
                        <th>Durum</th>
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
        const deleteButton = document.getElementById('client-template-delete');
        const updateForm = document.getElementById('client-template-update');

        if (deleteButton) {
            deleteButton.addEventListener('click', () => {
                const id = updateForm.querySelector('[name="id"]').value;
                if (!id) {
                    Swal.fire({ icon: 'warning', title: 'Uyarı', text: 'Önce bir şablon seçin.' });
                    return;
                }

                fetch('/client/templates/delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ id })
                })
                    .then((response) => response.json())
                    .then((payload) => {
                        if (payload.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Silindi', text: 'Şablon silindi.' });
                            document.getElementById('client-templates-table').dispatchEvent(new Event('datatable.refresh'));
                        } else {
                            Swal.fire({ icon: 'error', title: 'Hata', text: payload.message || 'Şablon silinemedi.' });
                        }
                    });
            });
        }
    });
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
