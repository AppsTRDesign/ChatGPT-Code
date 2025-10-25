<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
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
                <h5 class="card-title text-uppercase text-muted">Şablon</h5>
                <p class="display-6 fw-bold mb-0"><?= number_format((int) ($stats['template_count'] ?? 0)) ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-3 col-md-6">
        <div class="card shadow-sm border-0 h-100 gradient-card">
            <div class="card-body">
                <h5 class="card-title text-uppercase text-muted">Aktif Müşteri</h5>
                <p class="display-6 fw-bold mb-0"><?= number_format((int) ($stats['total_clients'] ?? 0)) ?></p>
            </div>
        </div>
    </div>
</div>
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
