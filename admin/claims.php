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
<?php require_once __DIR__ . '/partials/footer.php'; ?>
