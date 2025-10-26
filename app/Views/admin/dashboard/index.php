<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
    <div class="col-12 col-xl-3 col-md-6">
        <div class="card shadow-sm border-0 h-100 gradient-card">
            <div class="card-body">
                <h5 class="card-title text-uppercase text-muted">Aktif Müşteri</h5>
                <p class="display-6 fw-bold mb-0"><?= number_format((int) ($stats['total_clients'] ?? 0)) ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-3 col-md-6">
        <div class="card shadow-sm border-0 h-100 gradient-card">
            <div class="card-body">
                <h5 class="card-title text-uppercase text-muted">Aktif Paket</h5>
                <p class="display-6 fw-bold mb-0"><?= number_format((int) ($stats['active_packages'] ?? 0)) ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-3 col-md-6">
        <div class="card shadow-sm border-0 h-100 gradient-card">
            <div class="card-body">
                <h5 class="card-title text-uppercase text-muted">Bekleyen Satın Alım</h5>
                <p class="display-6 fw-bold mb-0"><?= number_format((int) ($stats['pending_purchases'] ?? 0)) ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-3 col-md-6">
        <div class="card shadow-sm border-0 h-100 gradient-card">
            <div class="card-body">
                <h5 class="card-title text-uppercase text-muted">Onaylı Gelir</h5>
                <p class="display-6 fw-bold mb-0">₺<?= number_format((float) ($stats['approved_revenue'] ?? 0), 2) ?></p>
            </div>
        </div>
    </div>
</div>
<div class="row g-4 mt-1">
    <div class="col-12 col-xl-3 col-md-6">
        <div class="card shadow-sm border-0 h-100 gradient-card">
            <div class="card-body">
                <h5 class="card-title text-uppercase text-muted">Toplam Bildirim</h5>
                <p class="display-6 fw-bold mb-0"><?= number_format((int) ($stats['total_notifications'] ?? 0)) ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-3 col-md-6">
        <div class="card shadow-sm border-0 h-100 gradient-card">
            <div class="card-body">
                <h5 class="card-title text-uppercase text-muted">Aktif Abonelik</h5>
                <p class="display-6 fw-bold mb-0"><?= number_format((int) ($stats['active_subscriptions'] ?? 0)) ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-3 col-md-6">
        <div class="card shadow-sm border-0 h-100 gradient-card">
            <div class="card-body">
                <h5 class="card-title text-uppercase text-muted">API Çağrısı</h5>
                <p class="display-6 fw-bold mb-0"><?= number_format((int) ($stats['total_api_calls'] ?? 0)) ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-3 col-md-6">
        <div class="card shadow-sm border-0 h-100 gradient-card">
            <div class="card-body">
                <h5 class="card-title text-uppercase text-muted">Bloklu Giriş</h5>
                <p class="display-6 fw-bold mb-0"><?= number_format((int) ($stats['blocked_users'] ?? 0)) ?></p>
            </div>
        </div>
    </div>
</div>
<section class="mt-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center">
            <h2 class="h6 mb-0 text-uppercase text-muted">Haftalık Özet</h2>
            <span class="text-muted small">Son 7 gün</span>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-6 col-xl-3">
                    <div class="stat-pill">
                        <span class="label text-uppercase text-muted">Yeni Üye</span>
                        <span class="value fw-semibold"><?= number_format((int) ($stats['recent_new_clients'] ?? 0)) ?></span>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-pill">
                        <span class="label text-uppercase text-muted">Alınan Ödeme</span>
                        <span class="value fw-semibold">₺<?= number_format((float) ($stats['recent_revenue'] ?? 0), 2) ?></span>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-pill">
                        <span class="label text-uppercase text-muted">Bekleyen Satın Alım</span>
                        <span class="value fw-semibold"><?= number_format((int) ($stats['pending_purchases'] ?? 0)) ?></span>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-pill">
                        <span class="label text-uppercase text-muted">Başarısız İşlem</span>
                        <span class="value fw-semibold"><?= number_format((int) ($stats['failed_payments'] ?? 0)) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Son 14 Gün Bildirim Aktivitesi</h2>
        <button class="btn btn-accent btn-sm" data-action="refresh" data-target="#admin-activity-chart">Yenile</button>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <canvas id="admin-activity-chart" class="w-100" height="320" data-series='<?= json_encode($daily, JSON_UNESCAPED_UNICODE) ?>'></canvas>
        </div>
    </div>
</section>
<section class="mt-5">
    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Bildirim Trafiği</h2>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <select class="form-select form-select-sm" data-report-filter="traffic" data-report-scope="dashboard">
                            <option value="daily">Günlük</option>
                            <option value="weekly">Haftalık</option>
                            <option value="monthly" selected>Aylık</option>
                            <option value="yearly">Yıllık</option>
                        </select>
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-outline-primary" data-action="export-report" data-target="traffic" data-scope="dashboard" data-format="excel">Excel</button>
                            <button class="btn btn-outline-secondary" data-action="export-report" data-target="traffic" data-scope="dashboard" data-format="pdf">PDF</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="dashboard-traffic-chart" height="300" data-source="/admin/reports/traffic" data-report-id="traffic" data-report-scope="dashboard" data-range="monthly"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Üyelik Grafiği</h2>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <select class="form-select form-select-sm" data-report-filter="memberships" data-report-scope="dashboard">
                            <option value="daily">Günlük</option>
                            <option value="weekly">Haftalık</option>
                            <option value="monthly" selected>Aylık</option>
                            <option value="yearly">Yıllık</option>
                        </select>
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-outline-primary" data-action="export-report" data-target="memberships" data-scope="dashboard" data-format="excel">Excel</button>
                            <button class="btn btn-outline-secondary" data-action="export-report" data-target="memberships" data-scope="dashboard" data-format="pdf">PDF</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="dashboard-membership-chart" height="300" data-source="/admin/reports/memberships" data-report-id="memberships" data-report-scope="dashboard" data-range="monthly"></canvas>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="mt-5">
    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Gelir Analizi</h2>
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm" data-report-filter="revenue" data-report-scope="dashboard">
                            <option value="daily">Günlük</option>
                            <option value="weekly">Haftalık</option>
                            <option value="monthly" selected>Aylık</option>
                            <option value="yearly">Yıllık</option>
                        </select>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" data-action="export-report" data-target="revenue" data-scope="dashboard" data-format="excel">Excel</button>
                            <button class="btn btn-outline-secondary" data-action="export-report" data-target="revenue" data-scope="dashboard" data-format="pdf">PDF</button>
                        </div>
                        <button class="btn btn-outline-primary btn-sm" data-action="refresh" data-target="#admin-revenue-chart">Yenile</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="admin-revenue-chart" height="300" data-source="/admin/reports/revenue" data-report-id="revenue" data-report-scope="dashboard" data-range="monthly"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">API Kullanımı</h2>
                    <button class="btn btn-outline-primary btn-sm" data-action="refresh" data-target="#admin-api-usage-chart">Yenile</button>
                </div>
                <div class="card-body">
                    <canvas id="admin-api-usage-chart" height="300" data-source="/admin/reports/api/usage"></canvas>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="mt-5">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">API Çağrı Özeti</h2>
            <button class="btn btn-outline-primary btn-sm" data-action="refresh" data-target="#admin-api-usage-table">Yenile</button>
        </div>
        <div class="card-body">
            <table class="table table-striped" id="admin-api-usage-table" data-source="/admin/reports/api/summary" data-columns='["endpoint","total","errors"]'>
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
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Son Bildirimler</h2>
        <a class="btn btn-outline-primary btn-sm" href="/admin/notifications">Tümünü Gör</a>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table class="table table-hover" data-source="/admin/notifications/data" data-columns='["title","status","recipient_count","sent_at"]'>
                <thead>
                    <tr>
                        <th>Başlık</th>
                        <th>Durum</th>
                        <th>Hedef Sayısı</th>
                        <th>Gönderim</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['title']) ?></td>
                            <td><span class="badge bg-primary-subtle text-uppercase"><?= htmlspecialchars($item['status']) ?></span></td>
                            <td><?= (int) ($item['recipient_count'] ?? 0) ?></td>
                            <td><?= $item['sent_at'] ? date('d.m.Y H:i', strtotime($item['sent_at'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../layout/footer.php'; ?>
