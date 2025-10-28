<?php
require_once __DIR__ . '/../config.php';
require_auth(true);
global $pageScripts;
$pageScripts[] = '<script src="' . BASE_URL . '/assets/js/admin-purchases.js?v=1.0.0"></script>';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <div class="card card-glass p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="h4 mb-1">Satın Alma Geçmişi</h1>
                <p class="text-white-50 small mb-0">Tüm ödeme sağlayıcılarından gelen işlemleri yönetin.</p>
            </div>
            <div class="d-flex gap-2">
                <input type="search" class="form-control" id="transactionSearch" placeholder="İşlem ara...">
                <select class="form-select" id="transactionStatus">
                    <option value="">Tümü</option>
                    <option value="pending">Beklemede</option>
                    <option value="paid">Ödendi</option>
                    <option value="failed">Başarısız</option>
                    <option value="cancelled">İptal</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle" id="transactionTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Kullanıcı</th>
                        <th>Paket</th>
                        <th>Tutar</th>
                        <th>Sağlayıcı</th>
                        <th>Durum</th>
                        <th>Oluşturma</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
