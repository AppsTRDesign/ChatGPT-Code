<?php ob_start(); ?>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">API Kullanım Grafiği</h5>
            <div>
                <button class="btn btn-sm btn-outline-primary">PDF</button>
                <button class="btn btn-sm btn-outline-primary">Excel</button>
            </div>
        </div>
        <canvas id="apiChart" height="120"></canvas>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const apiCtx = document.getElementById('apiChart');
    if (apiCtx) {
        const usage = <?= json_encode($usage, JSON_UNESCAPED_UNICODE) ?>;
        new Chart(apiCtx, {
            type: 'line',
            data: {
                labels: usage.map(row => row.day ?? 'Gün'),
                datasets: [{
                    label: 'API Çağrısı',
                    data: usage.map(row => Number(row.calls)),
                    borderColor: '#00a8cc',
                    tension: 0.3
                }]
            }
        });
    }
</script>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
