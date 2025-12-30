<?php
$pageTitle = 'İşletmeler';
$activeNav = 'places';
require_once __DIR__ . '/partials/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">İşletmeler</h4>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form id="place-filters" class="row gy-2 gx-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label">Arama</label>
                <input type="text" class="form-control" name="q" placeholder="İsim, şehir, kategori">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Durum</label>
                <select name="status" class="form-select">
                    <option value="">Tümü</option>
                    <option value="0">Beklemede</option>
                    <option value="1">Onaylı</option>
                    <option value="2">Reddedildi</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Sayfa boyutu</label>
                <select name="per_page" class="form-select">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div class="col-12 col-md-3 text-md-end">
                <button type="submit" class="btn btn-primary w-100">Filtrele</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle" id="places-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>İsim</th>
                        <th>Şehir</th>
                        <th>Kategori</th>
                        <th>Durum</th>
                        <th>Not</th>
                        <th>Oluşturma</th>
                        <th>Aksiyon</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <nav>
            <ul class="pagination" id="places-pagination"></ul>
        </nav>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
