<?php
require_once __DIR__ . '/../config.php';
require_login_redirect();
global $pageScripts;
$pageScripts[] = '<script src="' . BASE_URL . '/assets/js/client-share-stats.js?v=1.0.0"></script>';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <div class="row g-4">
        <div class="col-12">
            <div class="card card-glass p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h1 class="h4 mb-1">Paylaşım Analitiği</h1>
                        <p class="text-white-50 small mb-0">Bağlantılarınızın performansını zaman, konum ve cihaz bazında takip edin.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-light" data-export="csv">CSV Aktar</button>
                        <button class="btn btn-gradient" data-export="pdf">PDF Aktar</button>
                    </div>
                </div>
                <div class="btn-group btn-group-sm d-flex flex-wrap mb-3" role="group" id="timeseriesRanges">
                    <button type="button" class="btn btn-outline-light active" data-range="daily">Günlük</button>
                    <button type="button" class="btn btn-outline-light" data-range="weekly">Haftalık</button>
                    <button type="button" class="btn btn-outline-light" data-range="monthly">Aylık</button>
                    <button type="button" class="btn btn-outline-light" data-range="yearly">Yıllık</button>
                </div>
                <div class="chart-wrapper">
                    <canvas id="shareTimeseries"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card card-layer h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">Konum Dağılımı</h2>
                    <ul class="list-group list-group-flush" id="locationBreakdown"></ul>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card card-layer h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">Cihaz / Platform</h2>
                    <ul class="list-group list-group-flush" id="deviceBreakdown"></ul>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card card-layer">
                <div class="card-body">
                    <h2 class="h6 mb-3">Son Bağlantı İstekleri</h2>
                    <div class="table-responsive">
                        <table class="table table-modern table-hover mb-0" id="shareRecentTable">
                            <thead>
                                <tr>
                                    <th>Dosya</th>
                                    <th>IP</th>
                                    <th>Konum</th>
                                    <th>Cihaz</th>
                                    <th>Tarayıcı</th>
                                    <th>Tarih</th>
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
