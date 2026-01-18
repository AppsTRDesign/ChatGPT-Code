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
<div class="modal fade" id="placeDetailModal" tabindex="-1" aria-labelledby="placeDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="placeDetailModalLabel">İşletme Detayı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <img id="place-detail-image" class="img-fluid rounded border d-none" alt="İşletme görseli">
                    </div>
                    <div class="col-md-8">
                        <div><strong>İsim:</strong> <span id="place-detail-name"></span></div>
                        <div><strong>Telefon:</strong> <span id="place-detail-phone"></span></div>
                        <div><strong>Şehir:</strong> <span id="place-detail-city"></span></div>
                        <div><strong>Kategori:</strong> <span id="place-detail-category"></span></div>
                    </div>
                </div>
                <hr>
                <h6>Kısa Açıklama</h6>
                <p id="place-detail-description" class="mb-0"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
