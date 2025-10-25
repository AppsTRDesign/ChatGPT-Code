<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">API Anahtarı Oluştur</h2>
            </div>
            <div class="card-body">
                <form data-ajax="true" data-endpoint="/client/api-keys/store" data-refresh="#client-api-table" data-json="true">
                    <div class="mb-3">
                        <label class="form-label">Anahtar Adı</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İzinler</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="notifications.dispatch" name="permissions[]" id="client-perm-dispatch" checked>
                            <label class="form-check-label" for="client-perm-dispatch">Bildirim Gönder</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="tokens.register" name="permissions[]" id="client-perm-token" checked>
                            <label class="form-check-label" for="client-perm-token">Token Kaydet</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="notifications.read" name="permissions[]" id="client-perm-read" checked>
                            <label class="form-check-label" for="client-perm-read">Rapor Oku</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Anahtar Oluştur</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">API Anahtarı Güncelle</h2>
            </div>
            <div class="card-body">
                <form id="client-api-update" data-ajax="true" data-endpoint="/client/api-keys/update" data-refresh="#client-api-table" data-json="true">
                    <input type="hidden" name="id">
                    <div class="mb-3">
                        <label class="form-label">Ad</label>
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
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="notifications.dispatch" name="permissions[]" id="client-update-dispatch">
                            <label class="form-check-label" for="client-update-dispatch">Bildirim Gönder</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="tokens.register" name="permissions[]" id="client-update-token">
                            <label class="form-check-label" for="client-update-token">Token Kaydet</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="notifications.read" name="permissions[]" id="client-update-read">
                            <label class="form-check-label" for="client-update-read">Rapor Oku</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Anahtarı Güncelle</button>
                </form>
            </div>
        </div>
    </div>
</div>
<section class="mt-5">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table class="table table-striped" id="client-api-table" data-source="/client/api-keys/data" data-columns='["name","api_key","status","last_used_at"]' data-fill-form="#client-api-update">
                <thead>
                    <tr>
                        <th>Ad</th>
                        <th>Anahtar</th>
                        <th>Durum</th>
                        <th>Son Kullanım</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../layout/footer.php'; ?>
