<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Yeni Site Ekle</h2>
            </div>
            <div class="card-body">
                <form data-ajax="true" data-endpoint="/client/sites/store" data-refresh="#client-sites-table" id="client-site-create">
                    <div class="mb-3">
                        <label class="form-label">Ad</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alan Adı</label>
                        <input type="text" name="domain" class="form-control" placeholder="example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Durum</label>
                        <select name="status" class="form-select">
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Site Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Kayıtlı Siteler</h2>
                <button class="btn btn-outline-primary btn-sm" data-action="refresh" data-target="#client-sites-table">Yenile</button>
            </div>
            <div class="card-body">
                <table class="table table-striped align-middle" id="client-sites-table" data-source="/client/sites/data" data-fill-form="#client-site-update" data-columns='["name","domain","status","api_identifier"]'>
                    <thead>
                        <tr>
                            <th>Ad</th>
                            <th>Alan Adı</th>
                            <th>Durum</th>
                            <th>API Anahtarı</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<section class="mt-5">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Site Güncelle</h2>
            <button class="btn btn-outline-danger btn-sm" data-action="delete-record" data-endpoint="/client/sites/delete" data-table="#client-sites-table" data-form="#client-site-update">Sil</button>
        </div>
        <div class="card-body">
            <form id="client-site-update" data-ajax="true" data-endpoint="/client/sites/update" data-refresh="#client-sites-table">
                <input type="hidden" name="id">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Ad</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Alan Adı</label>
                        <input type="text" name="domain" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Durum</label>
                        <select name="status" class="form-select">
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Açıklama</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success">Değişiklikleri Kaydet</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../layout/footer.php'; ?>
