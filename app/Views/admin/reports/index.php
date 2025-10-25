<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Günlük Gönderim Trendleri</h2>
                <button class="btn btn-outline-primary btn-sm" id="refresh-daily-report">Yenile</button>
            </div>
            <div class="card-body">
                <canvas id="admin-report-daily" height="320"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
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

        document.getElementById('refresh-daily-report').addEventListener('click', loadDaily);
        document.getElementById('refresh-platform-report').addEventListener('click', loadPlatforms);

        loadDaily();
        loadPlatforms();
    });
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
