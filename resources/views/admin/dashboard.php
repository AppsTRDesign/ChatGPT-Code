<section class="row g-4">
    <div class="col-md-4">
        <div class="card metric-card h-100">
            <div class="icon-wrapper"><i class="bi bi-people"></i></div>
            <h6 class="text-uppercase text-muted">Aktif Üyeler</h6>
            <div class="value"><?= number_format($stats['members'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card metric-card h-100">
            <div class="icon-wrapper"><i class="bi bi-shield-lock"></i></div>
            <h6 class="text-uppercase text-muted">API Token</h6>
            <div class="value"><?= number_format($stats['tokens'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card metric-card h-100">
            <div class="icon-wrapper"><i class="bi bi-activity"></i></div>
            <h6 class="text-uppercase text-muted">Toplam API Çağrısı</h6>
            <div class="value"><?= number_format($stats['apiCalls'] ?? 0) ?></div>
        </div>
    </div>
</section>
<section class="row g-4 mt-1">
    <div class="col-md-4">
        <div class="card metric-card h-100">
            <div class="icon-wrapper"><i class="bi bi-clock-history"></i></div>
            <h6 class="text-uppercase text-muted">Bekleyen Satın Alım</h6>
            <div class="value"><?= number_format($stats['pendingPurchases'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card metric-card h-100">
            <div class="icon-wrapper"><i class="bi bi-cash-coin"></i></div>
            <h6 class="text-uppercase text-muted">Onaylı Gelir</h6>
            <div class="value">₺<?= number_format($stats['approvedRevenue'] ?? 0, 2) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card metric-card h-100">
            <div class="icon-wrapper"><i class="bi bi-exclamation-triangle"></i></div>
            <h6 class="text-uppercase text-muted">Başarısız İşlem</h6>
            <div class="value"><?= number_format($stats['failedTransactions'] ?? 0) ?></div>
        </div>
    </div>
</section>

<section class="card mt-4" data-chart='<?= json_encode($trafficChart, JSON_UNESCAPED_UNICODE) ?>'>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
            <div>
                <h5 class="mb-1">Trafik ve Üyelik Grafiği</h5>
                <small class="text-muted">Günlük, haftalık analiz</small>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-spreadsheet"></i> Excel</button>
            </div>
        </div>
        <div class="position-relative" style="height:320px;">
            <canvas id="trafficChart"></canvas>
        </div>
    </div>
</section>
