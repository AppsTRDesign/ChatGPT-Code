<section class="card" data-chart='<?= json_encode([
    'type' => 'line',
    'data' => [
        'labels' => array_map(fn($row) => $row['day'] ?? 'Gün', $usage),
        'datasets' => [[
            'label' => 'API Çağrısı',
            'data' => array_map(fn($row) => (int) ($row['calls'] ?? 0), $usage),
            'borderColor' => '#0ba7c4',
            'backgroundColor' => 'rgba(11,167,196,0.2)',
            'tension' => 0.3,
            'fill' => true,
        ]],
    ],
    'options' => ['responsive' => true, 'maintainAspectRatio' => false],
], JSON_UNESCAPED_UNICODE) ?>'>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1">API Kullanım Grafiği</h5>
                <small class="text-muted">Token bazlı çağrı istatistikleri.</small>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-spreadsheet"></i> Excel</button>
            </div>
        </div>
        <div class="position-relative" style="height:280px;">
            <canvas id="apiChart"></canvas>
        </div>
    </div>
</section>
