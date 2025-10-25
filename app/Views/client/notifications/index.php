<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
    <div class="col-12 col-xl-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Yeni Bildirim Oluştur</h2>
            </div>
            <div class="card-body">
                <form data-ajax="true" data-endpoint="/client/notifications/store" data-refresh="#client-notifications-table" data-json="true">
                    <div class="mb-3">
                        <label class="form-label">Başlık</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mesaj</label>
                        <textarea name="message" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hedef URL</label>
                        <input type="url" name="target_url" class="form-control" placeholder="https://">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Şablon</label>
                        <select name="template_id" class="form-select">
                            <option value="">Şablon Seç (Opsiyonel)</option>
                            <?php foreach ($templates as $template): ?>
                                <option value="<?= $template['id'] ?>"><?= htmlspecialchars($template['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Bildirim Gönder</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Bildirim Güncelle</h2>
            </div>
            <div class="card-body">
                <form id="client-notification-update" data-ajax="true" data-endpoint="/client/notifications/update" data-refresh="#client-notifications-table">
                    <input type="hidden" name="id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Başlık</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Durum</label>
                            <select name="status" class="form-select">
                                <option value="queued">Kuyrukta</option>
                                <option value="sending">Gönderiliyor</option>
                                <option value="sent">Gönderildi</option>
                                <option value="failed">Hatalı</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Mesaj</label>
                            <textarea name="message" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Hedef URL</label>
                            <input type="url" name="target_url" class="form-control">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-success">Güncelle</button>
                            <button type="button" class="btn btn-outline-danger ms-2" id="client-notification-delete">Sil</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<section class="mt-5">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table class="table table-striped align-middle" id="client-notifications-table" data-source="/client/notifications/data" data-columns='["title","status","recipient_count","sent_at"]' data-fill-form="#client-notification-update">
                <thead>
                    <tr>
                        <th>Başlık</th>
                        <th>Durum</th>
                        <th>Hedef</th>
                        <th>Gönderim</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const table = document.getElementById('client-notifications-table');
        const form = document.getElementById('client-notification-update');
        const deleteButton = document.getElementById('client-notification-delete');

        if (deleteButton) {
            deleteButton.addEventListener('click', () => {
                const id = form.querySelector('[name="id"]').value;
                if (!id) {
                    Swal.fire({ icon: 'warning', title: 'Uyarı', text: 'Önce bir kayıt seçmelisiniz.' });
                    return;
                }

                fetch('/client/notifications/delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ id })
                })
                    .then((response) => response.json())
                    .then((payload) => {
                        if (payload.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Silindi', text: 'Bildirim kaydı kaldırıldı.' });
                            table.dispatchEvent(new Event('datatable.refresh'));
                        } else {
                            Swal.fire({ icon: 'error', title: 'Hata', text: payload.message || 'Silme işlemi başarısız.' });
                        }
                    });
            });
        }
    });
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
