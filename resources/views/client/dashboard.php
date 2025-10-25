<?php ob_start(); ?>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card p-4 text-center">
            <h6>Toplam Bildirim</h6>
            <h3 class="fw-bold"><?= count($notifications) ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 text-center">
            <h6>Aktif Token</h6>
            <h3 class="fw-bold"><?= count($tokens) ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 text-center">
            <h6>Kayıtlı Site</h6>
            <h3 class="fw-bold"><?= count($sites) ?></h3>
        </div>
    </div>
</div>
<section class="card mt-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Performans Grafiği</h5>
            <div>
                <button class="btn btn-sm btn-outline-primary">PDF</button>
                <button class="btn btn-sm btn-outline-primary">Excel</button>
            </div>
        </div>
        <canvas id="performanceChart" height="120"></canvas>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const chart = document.getElementById('performanceChart');
    if (chart) {
        new Chart(chart, {
            type: 'bar',
            data: {
                labels: ['Bugün', 'Dün', '3 Gün', '4 Gün', '5 Gün', '6 Gün', '7 Gün'],
                datasets: [
                    { label: 'Gösterim', data: [120, 132, 101, 134, 90, 230, 210], backgroundColor: '#00a8cc' },
                    { label: 'Tıklama', data: [45, 60, 40, 55, 35, 75, 80], backgroundColor: '#007c91' }
                ]
            }
        });
    }
</script>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
