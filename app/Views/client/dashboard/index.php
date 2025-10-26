<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
    <div class="col-12 col-md-3">
        <div class="card shadow-sm border-0 h-100 gradient-card">
            <div class="card-body">
                <h5 class="card-title text-uppercase text-muted">Gönderilen Bildirim</h5>
                <p class="display-6 fw-bold mb-0"><?= $stats['sent_notifications'] ?? 0 ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card shadow-sm border-0 h-100 gradient-card">
            <div class="card-body">
                <h5 class="card-title text-uppercase text-muted">Aktif Token</h5>
                <p class="display-6 fw-bold mb-0"><?= $stats['active_tokens'] ?? 0 ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card shadow-sm border-0 h-100 gradient-card">
            <div class="card-body">
                <h5 class="card-title text-uppercase text-muted">Şablon</h5>
                <p class="display-6 fw-bold mb-0"><?= $stats['available_templates'] ?? 0 ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card shadow-sm border-0 h-100 gradient-card">
            <div class="card-body">
                <h5 class="card-title text-uppercase text-muted">Tıklama Oranı</h5>
                <p class="display-6 fw-bold mb-0"><?= number_format((float) ($stats['click_rate'] ?? 0), 2) ?>%</p>
            </div>
        </div>
    </div>
</div>
<section class="mt-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h2 class="h5 mb-0">Bildirim Performansı</h2>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm w-auto" id="client-metrics-range">
                <option value="daily">Günlük</option>
                <option value="weekly">Haftalık</option>
                <option value="monthly">Aylık</option>
                <option value="yearly">Yıllık</option>
            </select>
            <button class="btn btn-accent btn-sm" data-action="refresh" data-target="#client-performance-chart">Yenile</button>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <canvas id="client-performance-chart" class="w-100" height="320" data-source="/client/notifications/metrics"></canvas>
            <div class="row g-3 mt-4" id="client-performance-breakdown"></div>
        </div>
    </div>
</section>
<section class="mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">API Kullanımı</h2>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm w-auto" id="client-api-range">
                <option value="daily">Günlük</option>
                <option value="weekly">Haftalık</option>
                <option value="monthly">Aylık</option>
            </select>
            <button class="btn btn-outline-light btn-sm" data-action="refresh" data-target="#client-api-chart">Yenile</button>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <canvas id="client-api-chart" class="w-100" height="280" data-source="/client/api/reports/series"></canvas>
            <div class="table-responsive mt-4">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Endpoint</th>
                            <th>Toplam</th>
                            <th>Hata</th>
                        </tr>
                    </thead>
                    <tbody id="client-api-summary">
                        <?php foreach ($apiSummary as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['endpoint']) ?></td>
                                <td><?= (int) $row['total'] ?></td>
                                <td><?= (int) $row['errors'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<section class="mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Son Bildirimler</h2>
        <button class="btn btn-accent btn-sm" data-action="refresh" data-target="#client-notifications">Yenile</button>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table class="table table-hover align-middle" id="client-notifications" data-source="/client/notifications/data" data-columns='["title","status","recipient_count","sent_at"]'>
                <thead>
                    <tr>
                        <th>Başlık</th>
                        <th>Durum</th>
                        <th>Hedef</th>
                        <th>Gönderim</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</section>
<section class="mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Gönderim Geçmişi</h2>
        <button class="btn btn-outline-primary btn-sm" data-action="refresh" data-target="#client-history">Yenile</button>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle" id="client-history" data-source="/client/notifications/history">
                    <thead>
                        <tr>
                            <th>Başlık</th>
                            <th>Durum</th>
                            <th>Gönderim</th>
                            <th>Gösterim</th>
                            <th>Açılma</th>
                            <th>Tıklama</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['title']) ?></td>
                                <td><?= htmlspecialchars($item['status']) ?></td>
                                <td><?= htmlspecialchars($item['created_at']) ?></td>
                                <td><?= (int) ($item['deliveries'] ?? 0) ?></td>
                                <td><?= (int) ($item['opens'] ?? 0) ?></td>
                                <td><?= (int) ($item['clicks'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../layout/footer.php'; ?>
