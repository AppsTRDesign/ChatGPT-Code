<?php
$title = 'Dashboard';
ob_start();
?>
<div class="row g-3">
    <div class="col-md-3">
        <div class="card text-bg-primary shadow-sm">
            <div class="card-body">
                <h2 class="card-title h5">Aktif Lisanslar</h2>
                <p class="display-6 mb-0"><?php echo (int) ($licenseCounts->firstWhere('status', 'active')->total ?? 0); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-warning shadow-sm">
            <div class="card-body">
                <h2 class="card-title h5">Grace Durumu</h2>
                <p class="display-6 mb-0"><?php echo (int) ($licenseCounts->firstWhere('status', 'grace')->total ?? 0); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-danger shadow-sm">
            <div class="card-body">
                <h2 class="card-title h5">İptal</h2>
                <p class="display-6 mb-0"><?php echo (int) ($licenseCounts->firstWhere('status', 'revoked')->total ?? 0); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-secondary shadow-sm">
            <div class="card-body">
                <h2 class="card-title h5">Ürün Sayısı</h2>
                <p class="display-6 mb-0"><?php echo $products->count(); ?></p>
            </div>
        </div>
    </div>
</div>
<div class="row g-4 mt-3">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="h5 mb-3">Son Doğrulama Olayları</h3>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                        <tr>
                            <th>Zaman</th>
                            <th>Olay</th>
                            <th>IP</th>
                            <th>Detay</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentEvents as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event->created_at?->toDateTimeString() ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($event->event_type, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($event->ip ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><code><?php echo htmlspecialchars(json_encode($event->payload), ENT_QUOTES, 'UTF-8'); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h3 class="h6">Doğrulama Trendleri</h3>
                <canvas id="validationChart" height="200"></canvas>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="h6">Ürünler</h3>
                <ul class="list-group list-group-flush">
                    <?php foreach ($products as $product): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?php echo htmlspecialchars($product->name, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="badge bg-primary rounded-pill"><?php echo $product->licenses()->count(); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php
$chartData = [
    'labels' => $dailyValidations->pluck('day')->map(fn($day) => (string) $day)->all(),
    'values' => $dailyValidations->pluck('total')->all(),
];
$scripts[] = 'new Chart(document.getElementById("validationChart"), {type: "line", data: {labels: ' . json_encode($chartData['labels']) . ', datasets: [{label: "Doğrulamalar", data: ' . json_encode($chartData['values']) . ', borderColor: "#0d6efd", fill: false}]}});';
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
