<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">API Anahtarı Oluştur</h2>
            </div>
            <div class="card-body">
                <form data-ajax="true" data-endpoint="/admin/api-keys/store" data-refresh="#admin-api-table" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label">Müşteri</label>
                        <select name="client_id" class="form-select" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Anahtar Adı</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İzinler</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php $permissions = ['notifications.dispatch' => 'Bildirim Gönder', 'tokens.register' => 'Token Kaydet', 'notifications.read' => 'Raporları Oku']; ?>
                            <?php foreach ($permissions as $key => $label): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="perm-create-<?= $key ?>" name="permissions[]" value="<?= $key ?>" checked>
                                    <label class="form-check-label" for="perm-create-<?= $key ?>"><?= $label ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Durum</label>
                        <select name="status" class="form-select">
                            <option value="active">Aktif</option>
                            <option value="revoked">İptal</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Anahtar Oluştur</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">API Anahtarı Güncelle</h2>
            </div>
            <div class="card-body">
                <form id="api-update-form" data-ajax="true" data-endpoint="/admin/api-keys/update" data-refresh="#admin-api-table" autocomplete="off">
                    <input type="hidden" name="id">
                    <div class="mb-3">
                        <label class="form-label">Anahtar Adı</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Durum</label>
                        <select name="status" class="form-select">
                            <option value="active">Aktif</option>
                            <option value="revoked">İptal</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İzinler</label>
                        <?php foreach ($permissions as $key => $label): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="perm-update-<?= $key ?>" name="permissions[]" value="<?= $key ?>">
                                <label class="form-check-label" for="perm-update-<?= $key ?>"><?= $label ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Anahtarı Güncelle</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Kullanım Notları</h2>
            </div>
            <div class="card-body">
                <p class="text-muted small">Tablodaki satırlara tıklayarak güncelleme formunu otomatik doldurabilirsiniz. API anahtarlarının son kullanım tarihleri ve izinleri listelenir.</p>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><span class="badge bg-accent text-dark me-2">notifications.dispatch</span>API üzerinden bildirim göndermeye izin verir.</li>
                    <li class="mb-2"><span class="badge bg-accent text-dark me-2">tokens.register</span>Tarayıcı token kayıtlarını kabul eder.</li>
                    <li><span class="badge bg-accent text-dark me-2">notifications.read</span>Servis worker raporlarının okunmasına izin verir.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<section class="mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">API Anahtar Listesi</h2>
        <button class="btn btn-outline-primary btn-sm" data-action="refresh" data-target="#admin-api-table">Yenile</button>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table class="table table-striped align-middle" id="admin-api-table" data-source="/admin/api-keys/data" data-columns='["name","api_key","status","last_used_at","client_id"]' data-fill-form="#api-update-form">
                <thead>
                    <tr>
                        <th>Ad</th>
                        <th>Anahtar</th>
                        <th>Durum</th>
                        <th>Son Kullanım</th>
                        <th>Müşteri</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../layout/footer.php'; ?>
