<?php
$pageTitle = 'Doğrulama Talepleri';
$activeNav = 'claims';
require_once __DIR__ . '/partials/header.php';
?>
<div class="card shadow-sm">
    <div class="card-body">
        <form class="row g-2 mb-3" id="claim-filters">
            <div class="col-md-4">
                <input type="search" name="q" class="form-control" placeholder="İşletme veya kullanıcı ara">
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
            <table class="table table-sm align-middle" id="claims-table">
                <thead><tr><th>ID</th><th>İşletme</th><th>Kullanıcı</th><th>Durum</th><th>Metod</th><th>Oluşturma</th><th>İşlem</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
        <nav><ul class="pagination pagination-sm" id="claims-pagination"></ul></nav>
    </div>
</div>
<div class="modal fade" id="claimDetailModal" tabindex="-1" aria-labelledby="claimDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="claimDetailModalLabel">Talep Detayı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div><strong>İşletme:</strong> <span id="claim-detail-place"></span></div>
                        <div><strong>Kullanıcı:</strong> <span id="claim-detail-user"></span></div>
                        <div><strong>Metod:</strong> <span id="claim-detail-method"></span></div>
                    </div>
                    <div class="col-md-6">
                        <div><strong>Durum:</strong> <span id="claim-detail-status"></span></div>
                        <div><strong>Doğrulama Tarihi:</strong> <span id="claim-detail-verified"></span></div>
                    </div>
                </div>
                <hr>
                <h6>Detaylar</h6>
                <div id="claim-detail-payload" class="small text-muted"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
