<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
    <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0 h-100 gradient-card">
            <div class="card-body">
                <h5 class="card-title text-uppercase text-muted">Gönderilen Bildirim</h5>
                <p class="display-6 fw-bold mb-0"><?= $stats['sent_notifications'] ?? 0 ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0 h-100 gradient-card">
            <div class="card-body">
                <h5 class="card-title text-uppercase text-muted">Aktif Token</h5>
                <p class="display-6 fw-bold mb-0"><?= $stats['active_tokens'] ?? 0 ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0 h-100 gradient-card">
            <div class="card-body">
                <h5 class="card-title text-uppercase text-muted">Şablon</h5>
                <p class="display-6 fw-bold mb-0"><?= $stats['available_templates'] ?? 0 ?></p>
            </div>
        </div>
    </div>
</div>
<section class="mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">En Son Bildirimler</h2>
        <button class="btn btn-accent btn-sm" data-action="refresh" data-target="client-notifications">Yenile</button>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table class="table table-hover align-middle" id="client-notifications" data-source="/client/notifications.json">
                <thead>
                    <tr>
                        <th>Başlık</th>
                        <th>Durum</th>
                        <th>Gönderim</th>
                        <th>Hedef</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <button class="btn btn-outline-primary btn-sm mt-3" data-action="refresh" data-target="client-notifications">Yenile</button>
        </div>
    </div>
</section>
<section class="mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Performans Grafiği</h2>
        <button class="btn btn-accent btn-sm" data-action="refresh" data-target="client-performance">Yenile</button>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <canvas id="client-performance-chart" class="w-100" height="300"></canvas>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../layout/footer.php'; ?>
