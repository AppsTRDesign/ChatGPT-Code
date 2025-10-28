<?php
require_once __DIR__ . '/../config.php';
require_auth(true);
global $pageScripts;
$pageScripts[] = '<script src="' . BASE_URL . '/assets/js/admin-dashboard.js?v=1.0.0"></script>';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <div class="row g-4" id="adminStats">
        <div class="col-md-4">
            <div class="card card-glass p-4 h-100">
                <p class="text-white-50 small mb-1">Toplam Dosya</p>
                <h3 class="h2 mb-0" data-stat="total_files">-</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-glass p-4 h-100">
                <p class="text-white-50 small mb-1">Toplam Dosya Boyutu</p>
                <h3 class="h2 mb-0" data-stat="total_size">-</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-glass p-4 h-100">
                <p class="text-white-50 small mb-1">Kayıtlı Üye</p>
                <h3 class="h2 mb-0" data-stat="total_users">-</h3>
            </div>
        </div>
    </div>
    <div class="card card-glass p-4 mt-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">Depo Kullanım Eğilimleri</h2>
                <p class="text-white-50 small mb-0">Günlük, haftalık, aylık ve yıllık dosya yükleme istatistikleri</p>
            </div>
            <div class="btn-group" role="group" aria-label="Zaman seçici">
                <button type="button" class="btn btn-outline-light" data-range="daily">Günlük</button>
                <button type="button" class="btn btn-outline-light" data-range="weekly">Haftalık</button>
                <button type="button" class="btn btn-outline-light" data-range="monthly">Aylık</button>
                <button type="button" class="btn btn-outline-light" data-range="yearly">Yıllık</button>
            </div>
        </div>
        <div class="chart-wrapper mb-4">
            <canvas id="adminUsageChart" height="120"></canvas>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-gradient" data-export="csv">Excel (CSV) İndir</button>
            <button type="button" class="btn btn-outline-light" data-export="pdf">PDF İndir</button>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
