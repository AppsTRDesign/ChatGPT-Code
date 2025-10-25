<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
    <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0 h-100 gradient-card">
            <div class="card-body">
                <h5 class="card-title text-uppercase text-muted">Toplam Bildirim</h5>
                <p class="display-6 fw-bold mb-0"><?= $stats['total_notifications'] ?? 0 ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0 h-100 gradient-card">
            <div class="card-body">
                <h5 class="card-title text-uppercase text-muted">Aktif Abonelik</h5>
                <p class="display-6 fw-bold mb-0"><?= $stats['active_subscriptions'] ?? 0 ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0 h-100 gradient-card">
            <div class="card-body">
                <h5 class="card-title text-uppercase text-muted">Şablon</h5>
                <p class="display-6 fw-bold mb-0"><?= $stats['template_count'] ?? 0 ?></p>
            </div>
        </div>
    </div>
</div>
<section class="mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Gerçek Zamanlı Bildirim Aktivitesi</h2>
        <button class="btn btn-accent btn-sm" data-action="refresh" data-target="admin-activity">Yenile</button>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <canvas id="admin-activity-chart" class="w-100" height="300"></canvas>
        </div>
    </div>
</section>
<section class="mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">API Anahtarları</h2>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createApiKeyModal">Yeni API Anahtarı</button>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table class="table table-hover align-middle" id="admin-api-table" data-source="/admin/api/keys.json">
                <thead>
                    <tr>
                        <th>Ad</th>
                        <th>Anahtar</th>
                        <th>Durum</th>
                        <th>Son Kullanım</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <button class="btn btn-outline-primary btn-sm mt-3" data-action="refresh" data-target="admin-api-table">Yenile</button>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../layout/footer.php'; ?>
