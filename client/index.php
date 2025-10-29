<?php
require_once __DIR__ . '/../config.php';
require_login_redirect();
global $pageScripts;
$pageScripts[] = '<script src="' . BASE_URL . '/assets/js/client-dashboard.js?v=1.0.0"></script>';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <div class="row g-4" id="clientSummary">
        <div class="col-md-4">
            <div class="card card-glass p-4 h-100">
                <p class="text-white-50 small mb-1">Toplam Dosya</p>
                <h3 class="h2 mb-0" data-summary="total_files">-</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-glass p-4 h-100">
                <p class="text-white-50 small mb-1">Kullanılan Alan</p>
                <h3 class="h2 mb-0" data-summary="total_size">-</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-glass p-4 h-100">
                <p class="text-white-50 small mb-1">Paket</p>
                <h3 class="h4 mb-0" data-summary="package_name">-</h3>
            </div>
        </div>
    </div>
    <div class="card card-glass p-4 mt-5" id="clientShareAnalyticsCard">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">Paylaşım Analitiği</h2>
                <p class="text-white-50 small mb-0">Paylaşımlarınızın performansını tarih aralıklarına göre inceleyin.</p>
            </div>
            <div class="btn-group btn-group-sm" role="group" id="clientShareRanges">
                <button type="button" class="btn btn-outline-light active" data-range="daily">Günlük</button>
                <button type="button" class="btn btn-outline-light" data-range="weekly">Haftalık</button>
                <button type="button" class="btn btn-outline-light" data-range="monthly">Aylık</button>
                <button type="button" class="btn btn-outline-light" data-range="yearly">Yıllık</button>
            </div>
        </div>
        <div class="chart-wrapper mb-4">
            <canvas id="clientShareChart" height="120"></canvas>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-gradient" data-share-export="csv">Excel (CSV) İndir</button>
            <button type="button" class="btn btn-outline-light" data-share-export="pdf">PDF İndir</button>
        </div>
    </div>
    <div class="row g-4 mt-1" id="clientShareAnalyticsDetails">
        <div class="col-12 col-xl-6">
            <div class="card card-layer h-100">
                <div class="card-body">
                    <h3 class="h6 mb-3">Konum Dağılımı</h3>
                    <ul class="list-group list-group-flush" id="clientShareLocations"></ul>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card card-layer h-100">
                <div class="card-body">
                    <h3 class="h6 mb-3">Cihaz &amp; Platform</h3>
                    <ul class="list-group list-group-flush" id="clientShareDevices"></ul>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card card-layer">
                <div class="card-body">
                    <h3 class="h6 mb-3">Son Paylaşılan Bağlantılar</h3>
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div class="flex-grow-1 flex-md-grow-0" style="min-width: 220px;">
                            <input type="search" class="form-control" id="clientShareSearch" placeholder="Bağlantı ara...">
                        </div>
                        <div class="d-flex align-items-center gap-2 ms-md-auto">
                            <span class="text-white-50 extra-small" id="clientSharePageInfo">0 kayıt</span>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-light" id="clientSharePrev">Önceki</button>
                                <button type="button" class="btn btn-outline-light" id="clientShareNext">Sonraki</button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-modern mb-0" id="clientShareRecent">
                            <thead>
                                <tr>
                                    <th>Dosya</th>
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
