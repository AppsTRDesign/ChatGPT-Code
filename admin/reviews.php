<?php
$pageTitle = 'Yorum Yönetimi';
$activeNav = 'reviews';
require_once __DIR__ . '/partials/header.php';
?>
<div class="card shadow-sm">
    <div class="card-body">
        <form class="row g-2 mb-3" id="review-filters">
            <div class="col-md-4">
                <input type="search" name="q" class="form-control" placeholder="İşletme veya yazar ara">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Tüm Durumlar</option>
                    <option value="pending">Beklemede</option>
                    <option value="approved">Onaylanan</option>
                    <option value="rejected">Reddedilen</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="per_page" class="form-select">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div class="col-md-3 text-end">
                <button class="btn btn-primary" type="submit">Filtrele</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-sm align-middle" id="reviews-table">
                <thead><tr><th>ID</th><th>İşletme</th><th>Yazar</th><th>Puan</th><th>Durum</th><th>Metin</th><th>İşlem</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
        <nav><ul class="pagination pagination-sm" id="reviews-pagination"></ul></nav>
    </div>
</div>
<div class="modal fade" id="reviewDetailModal" tabindex="-1" aria-labelledby="reviewDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewDetailModalLabel">Yorum Detayı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div><strong>İşletme:</strong> <span id="review-detail-place"></span></div>
                        <div><strong>Yazar:</strong> <span id="review-detail-author"></span></div>
                    </div>
                    <div class="col-md-6">
                        <div><strong>Puan:</strong> <span id="review-detail-rating"></span></div>
                        <div><strong>Durum:</strong> <span id="review-detail-status"></span></div>
                        <div><strong>Tarih:</strong> <span id="review-detail-date"></span></div>
                    </div>
                </div>
                <div class="mb-3">
                    <h6>Yorum</h6>
                    <p id="review-detail-text" class="mb-0"></p>
                </div>
                <div class="mb-3">
                    <h6>Text Extra</h6>
                    <div id="review-detail-extra" class="review-extra-list"></div>
                </div>
                <div>
                    <h6>Fotoğraflar</h6>
                    <div id="review-detail-photos" class="review-photo-list"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
