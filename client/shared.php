<?php
require_once __DIR__ . '/../config.php';
require_login_redirect();
global $pageScripts;
$pageScripts[] = '<script src="' . BASE_URL . '/assets/js/client-shared.js?v=1.0.0"></script>';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <div class="card card-glass p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h1 class="h4 mb-1">Paylaşılan Dosyalarım</h1>
                <p class="text-white-50 small mb-0">Paylaşılan bağlantılarınızı görüntüleyin, kopyalayın veya paylaşımı sonlandırın.</p>
            </div>
            <button type="button" class="btn btn-outline-light btn-sm" data-refresh-shares><i class="bi bi-arrow-clockwise me-1"></i>Yenile</button>
        </div>
        <div class="table-responsive">
            <table class="table table-modern mb-0" id="clientSharedTable">
                <thead>
                    <tr>
                        <th>Dosya</th>
                        <th>Bağlantı</th>
                        <th>Oluşturulma</th>
                        <th>Son Kullanım</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
