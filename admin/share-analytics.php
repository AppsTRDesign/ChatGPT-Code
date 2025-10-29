<?php
require_once __DIR__ . '/../config.php';
require_auth(true);
global $pageScripts;
$pageScripts[] = '<script src="' . BASE_URL . '/assets/js/admin-share-analytics.js?v=1.0.0"></script>';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5" id="adminShareAnalytics" data-analytics>
    <div class="card card-glass p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h1 class="h4 mb-1">Paylaşım Analitiği</h1>
                <p class="text-white-50 small mb-0">Tüm kullanıcıların bağlantı performansını tarih, konum ve cihaz bazında izleyin.</p>
            </div>
            <div class="btn-group btn-group-sm" role="group" id="adminShareRanges">
                <button type="button" class="btn btn-outline-light active" data-range="daily">Günlük</button>
                <button type="button" class="btn btn-outline-light" data-range="weekly">Haftalık</button>
                <button type="button" class="btn btn-outline-light" data-range="monthly">Aylık</button>
                <button type="button" class="btn btn-outline-light" data-range="yearly">Yıllık</button>
            </div>
        </div>
        <div class="chart-wrapper mb-4">
            <canvas id="adminShareChart" height="140"></canvas>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-gradient" data-admin-share-export="csv">Excel (CSV) İndir</button>
            <button type="button" class="btn btn-outline-light" data-admin-share-export="pdf">PDF İndir</button>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <div class="card card-layer h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">Konum Dağılımı</h2>
                    <ul class="list-group list-group-flush" id="adminShareLocations"></ul>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card card-layer h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">Cihaz &amp; Platform</h2>
                    <ul class="list-group list-group-flush" id="adminShareDevices"></ul>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card card-layer">
                <div class="card-body">
                    <h2 class="h6 mb-3">Son Paylaşım İstekleri</h2>
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div class="flex-grow-1 flex-md-grow-0" style="min-width: 220px;">
                            <input type="search" class="form-control" id="adminShareSearch" placeholder="Bağlantı ara...">
                        </div>
                        <div class="d-flex align-items-center gap-2 ms-md-auto">
                            <span class="text-white-50 extra-small" id="adminSharePageInfo">0 kayıt</span>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-light" id="adminSharePrev">Önceki</button>
                                <button type="button" class="btn btn-outline-light" id="adminShareNext">Sonraki</button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-modern mb-0" id="adminShareRecent">
                            <thead>
                                <tr>
                                    <th>Dosya</th>
                                    <th>Kullanıcı</th>
                                    <th>Tıklamalar</th>
                                    <th>Tarayıcı</th>
                                    <th>Dil</th>
                                    <th>Cihaz</th>
                                    <th>Konum</th>
                                    <th>Son Tıklama</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
