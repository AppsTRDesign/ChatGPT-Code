<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h2 class="h5 mb-0">Bildirim Trafiği</h2>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <select class="form-select form-select-sm" data-report-filter="traffic" data-report-scope="reports">
                        <option value="daily">Günlük</option>
                        <option value="weekly">Haftalık</option>
                        <option value="monthly" selected>Aylık</option>
                        <option value="yearly">Yıllık</option>
                    </select>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" data-action="export-report" data-target="traffic" data-scope="reports" data-format="excel">Excel</button>
                        <button class="btn btn-outline-secondary" data-action="export-report" data-target="traffic" data-scope="reports" data-format="pdf">PDF</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <canvas id="reports-traffic-chart" height="320" data-source="/admin/reports/traffic" data-report-id="traffic" data-report-scope="reports" data-range="monthly"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h2 class="h5 mb-0">Üyelik Trendleri</h2>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <select class="form-select form-select-sm" data-report-filter="memberships" data-report-scope="reports">
                        <option value="daily">Günlük</option>
                        <option value="weekly">Haftalık</option>
                        <option value="monthly" selected>Aylık</option>
                        <option value="yearly">Yıllık</option>
                    </select>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" data-action="export-report" data-target="memberships" data-scope="reports" data-format="excel">Excel</button>
                        <button class="btn btn-outline-secondary" data-action="export-report" data-target="memberships" data-scope="reports" data-format="pdf">PDF</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <canvas id="reports-membership-chart" height="320" data-source="/admin/reports/memberships" data-report-id="memberships" data-report-scope="reports" data-range="monthly"></canvas>
            </div>
        </div>
    </div>
</div>
<div class="row g-4 mt-1">
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">API Kullanım Grafiği</h2>
                <button class="btn btn-outline-primary btn-sm" data-action="refresh" data-target="#reports-api-usage-chart">Yenile</button>
            </div>
            <div class="card-body">
                <canvas id="reports-api-usage-chart" height="320" data-source="/admin/reports/api/usage" data-report-id="api" data-report-scope="reports"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Platform Dağılımı</h2>
                <button class="btn btn-outline-primary btn-sm" id="refresh-platform-report">Yenile</button>
            </div>
            <div class="card-body">
                <canvas id="admin-report-platform" height="320"></canvas>
            </div>
        </div>
    </div>
</div>
<section class="mt-5">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">API Çağrı Özeti</h2>
            <button class="btn btn-outline-primary btn-sm" data-action="refresh" data-target="#reports-api-usage-table">Yenile</button>
        </div>
        <div class="card-body">
            <table class="table table-striped" id="reports-api-usage-table" data-source="/admin/reports/api/summary" data-columns='["endpoint","total","errors"]'>
                <thead>
                    <tr>
                        <th>Endpoint</th>
                        <th>Toplam</th>
                        <th>Hata</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</section>
<section class="mt-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Günlük Gönderim Trendleri</h2>
            <button class="btn btn-outline-primary btn-sm" id="refresh-daily-report">Yenile</button>
        </div>
        <div class="card-body">
            <canvas id="admin-report-daily" height="320"></canvas>
        </div>
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const dailyCanvas = document.getElementById('admin-report-daily');
        const platformCanvas = document.getElementById('admin-report-platform');
        let dailyChart;
        let platformChart;

        function loadDaily() {
            fetch('/admin/reports/daily')
                .then((response) => response.json())
                .then((series) => {
                    const labels = series.map((item) => item.day);
                    const sent = series.map((item) => Number(item.sent || 0));
                    const failed = series.map((item) => Number(item.failed || 0));

                    if (dailyChart) {
                        dailyChart.destroy();
                    }

                    dailyChart = new Chart(dailyCanvas, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [
                                {
                                    label: 'Gönderildi',
                                    data: sent,
                                    borderColor: '#00b8a9',
                                    backgroundColor: 'rgba(0, 184, 169, 0.2)',
                                    fill: true,
                                    tension: 0.4
                                },
                                {
                                    label: 'Hatalı',
                                    data: failed,
                                    borderColor: '#dc3545',
                                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                                    fill: true,
                                    tension: 0.4
                                }
                            ]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                });
        }

        function loadPlatforms() {
            fetch('/admin/reports/platforms')
                .then((response) => response.json())
                .then((series) => {
                    const labels = series.map((item) => item.platform || 'Bilinmiyor');
                    const values = series.map((item) => Number(item.total || 0));

                    if (platformChart) {
                        platformChart.destroy();
                    }

                    platformChart = new Chart(platformCanvas, {
                        type: 'doughnut',
                        data: {
                            labels,
                            datasets: [{
                                data: values,
                                backgroundColor: ['#0a4d68', '#00b8a9', '#4f9da6', '#f9a620', '#f76b8a']
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                });
        }

        const refreshDaily = document.getElementById('refresh-daily-report');
        if (refreshDaily) {
            refreshDaily.addEventListener('click', loadDaily);
        }

        const refreshPlatform = document.getElementById('refresh-platform-report');
        if (refreshPlatform) {
            refreshPlatform.addEventListener('click', loadPlatforms);
        }

        loadDaily();
        loadPlatforms();
    });
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
