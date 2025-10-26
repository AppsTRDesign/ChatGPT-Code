<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
    <div class="col-12 col-xl-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Yeni Bildirim Oluştur</h2>
                <button class="btn btn-outline-secondary btn-sm" type="button" data-action="reset-form" data-target="#client-notification-create">Temizle</button>
            </div>
            <div class="card-body">
                <form id="client-notification-create" data-ajax="true" data-endpoint="/client/notifications/store" data-refresh="#client-notifications-table" data-json="true">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Başlık</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Mesaj</label>
                            <textarea name="message" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Hedef URL</label>
                            <input type="url" name="target_url" class="form-control" placeholder="https://">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Dil</label>
                            <select name="language" class="form-select">
                                <option value="">Varsayılan (<?= htmlspecialchars($defaultLanguage ?? 'tr') ?>)</option>
                                <?php foreach ($languages as $code => $label): ?>
                                    <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Site</label>
                            <select name="site_id" class="form-select">
                                <option value="">Tüm Siteler</option>
                                <?php foreach ($sites as $site): ?>
                                    <option value="<?= (int) $site['id'] ?>"><?= htmlspecialchars($site['name']) ?> (<?= htmlspecialchars($site['domain']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Platform</label>
                            <input type="text" name="platform" class="form-control" placeholder="iOS, Android, Web">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Tarayıcı</label>
                            <input type="text" name="browser" class="form-control" placeholder="Chrome, Safari">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Cihaz Tipi</label>
                            <input type="text" name="device_type" class="form-control" placeholder="desktop, mobile">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Model</label>
                            <input type="text" name="device_model" class="form-control" placeholder="iPhone 15, Galaxy">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Ülke</label>
                            <input type="text" name="country" class="form-control" placeholder="TR, US">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Şehir</label>
                            <input type="text" name="city" class="form-control" placeholder="İstanbul">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Şablon</label>
                            <select name="template_id" class="form-select" data-template-preview>
                                <option value="">Varsayılan Şablon</option>
                                <?php foreach ($templates as $template): ?>
                                    <option value="<?= (int) $template['id'] ?>" data-content='<?= htmlspecialchars(json_encode($template['content'] ?? ''), ENT_QUOTES) ?>'><?= htmlspecialchars($template['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Buton Ayarları</label>
                            <div class="input-group">
                                <span class="input-group-text">Ad</span>
                                <input type="text" name="button_text" class="form-control" placeholder="Örn. Hemen İncele">
                                <span class="input-group-text">URL</span>
                                <input type="url" name="button_url" class="form-control" placeholder="https://">
                            </div>
                            <div class="form-text">Buton boş bırakılırsa bildirim bağlantısı tüm karta uygulanır.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bildirim Süresi</label>
                            <div class="row g-2 align-items-center">
                                <div class="col-auto">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="notification-duration-toggle" data-toggle="duration">
                                        <label class="form-check-label" for="notification-duration-toggle">Süreli</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="input-group">
                                        <input type="number" min="1" name="duration_value" class="form-control" placeholder="Süre" disabled>
                                        <select name="duration_unit" class="form-select" disabled>
                                            <option value="minutes">Dakika</option>
                                            <option value="hours">Saat</option>
                                            <option value="days">Gün</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Zamanlama</label>
                            <input type="datetime-local" name="schedule_at" class="form-control">
                            <div class="form-text">Boş bırakılırsa bildirim hemen kuyruğa alınır.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Görsel</label>
                            <div class="dropzone" data-dropzone="true" data-upload="/client/notifications/upload" data-input="[name=\"image_path\"]"></div>
                            <input type="hidden" name="image_path">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Logo</label>
                            <div class="dropzone" data-dropzone="true" data-upload="/client/notifications/upload" data-input="[name=\"icon_path\"]"></div>
                            <input type="hidden" name="icon_path">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">Bildirim Gönder</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Şablon Önizleme</h2>
            </div>
            <div class="card-body">
                <div id="notification-template-preview" class="border rounded p-3 bg-light">
                    <p class="text-muted mb-0">Şablon seçtiğinizde önizleme burada görüntülenecektir.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<section class="mt-5">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Bildirim Kayıtları</h2>
            <button class="btn btn-outline-primary btn-sm" data-action="refresh" data-target="#client-notifications-table">Yenile</button>
        </div>
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
<section class="mt-5">
    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Bildirim Güncelle</h2>
                    <button class="btn btn-outline-danger btn-sm" id="client-notification-delete">Seçileni Sil</button>
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
                                <textarea name="message" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Hedef URL</label>
                                <input type="url" name="target_url" class="form-control">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success">Güncelle</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Bildirim İstatistikleri</h2>
                    <select class="form-select form-select-sm w-auto" id="notification-insight-range">
                        <option value="daily">Günlük</option>
                        <option value="weekly">Haftalık</option>
                        <option value="monthly">Aylık</option>
                        <option value="yearly">Yıllık</option>
                    </select>
                </div>
                <div class="card-body">
                    <canvas id="notification-insight-chart" height="220" data-source="/client/notifications/metrics"></canvas>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm" id="notification-insight-breakdown">
                            <thead>
                                <tr>
                                    <th>Özellik</th>
                                    <th>Değer</th>
                                    <th>Gösterim</th>
                                    <th>Açılma</th>
                                    <th>Tıklama</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const table = document.getElementById('client-notifications-table');
        const form = document.getElementById('client-notification-update');
        const deleteButton = document.getElementById('client-notification-delete');
        const templateSelect = document.querySelector('[data-template-preview]');
        const preview = document.getElementById('notification-template-preview');
        const durationToggle = document.querySelector('[data-toggle="duration"]');
        const durationInputs = document.querySelectorAll('[name="duration_value"], [name="duration_unit"]');

        if (durationToggle) {
            durationToggle.addEventListener('change', () => {
                const enabled = durationToggle.checked;
                durationInputs.forEach((input) => {
                    input.disabled = !enabled;
                });
            });
        }

        if (templateSelect && preview) {
            templateSelect.addEventListener('change', (event) => {
                const option = event.target.selectedOptions[0];
                const content = option?.dataset?.content ?? '';
                preview.innerHTML = content || '<p class="text-muted mb-0">Varsayılan şablon kullanılacaktır.</p>';
            });
        }

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
