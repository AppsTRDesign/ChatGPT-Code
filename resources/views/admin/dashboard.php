<?php ob_start(); ?>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card p-3 text-center">
            <span class="text-muted">Aktif Üyeler</span>
            <h3 class="fw-bold"><?= number_format($stats['members'] ?? 0) ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 text-center">
            <span class="text-muted">API Token</span>
            <h3 class="fw-bold"><?= number_format($stats['tokens'] ?? 0) ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 text-center">
            <span class="text-muted">Toplam API Çağrısı</span>
            <h3 class="fw-bold"><?= number_format($stats['apiCalls'] ?? 0) ?></h3>
        </div>
    </div>
</div>
<div class="row g-4 mt-1">
    <div class="col-md-4">
        <div class="card p-3 text-center">
            <span class="text-muted">Bekleyen Satın Alım</span>
            <h3 class="fw-bold"><?= number_format($stats['pendingPurchases'] ?? 0) ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 text-center">
            <span class="text-muted">Onaylı Gelir</span>
            <h3 class="fw-bold">₺<?= number_format($stats['approvedRevenue'] ?? 0, 2) ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 text-center">
            <span class="text-muted">Başarısız İşlem</span>
            <h3 class="fw-bold"><?= number_format($stats['failedTransactions'] ?? 0) ?></h3>
        </div>
    </div>
</div>
<section class="card mt-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Trafik ve Üyelik Grafiği</h5>
            <div>
                <button class="btn btn-sm btn-outline-primary">PDF</button>
                <button class="btn btn-sm btn-outline-primary">Excel</button>
            </div>
        </div>
        <canvas id="trafficChart" height="120"></canvas>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const ctx = document.getElementById('trafficChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'],
                datasets: [
                    {
                        label: 'Yeni Üye',
                        data: [5, 8, 6, 10, 4, 7, 9],
                        borderColor: '#00a8cc',
                        fill: false
                    },
                    {
                        label: 'Bildirim Gönderimi',
                        data: [30, 42, 55, 48, 61, 53, 70],
                        borderColor: '#007c91',
                        fill: false
                    }
                ]
            }
        });
    }
</script>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
