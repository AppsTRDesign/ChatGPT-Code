<section class="row g-4">
    <div class="col-md-4">
        <div class="card metric-card h-100">
            <div class="icon-wrapper"><i class="bi bi-bell"></i></div>
            <h6 class="text-uppercase text-muted">Toplam Bildirim</h6>
            <div class="value"><?= count($notifications) ?></div>
            <p class="mb-0 text-muted">Yayınladığınız kampanyaların toplamı.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card metric-card h-100">
            <div class="icon-wrapper"><i class="bi bi-key"></i></div>
            <h6 class="text-uppercase text-muted">Aktif Token</h6>
            <div class="value"><?= count($tokens) ?></div>
            <p class="mb-0 text-muted">API entegrasyonları için oluşturduğunuz anahtarlar.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card metric-card h-100">
            <div class="icon-wrapper"><i class="bi bi-globe"></i></div>
            <h6 class="text-uppercase text-muted">Kayıtlı Site</h6>
            <div class="value"><?= count($sites) ?></div>
            <p class="mb-0 text-muted">Bildirim alan sitelerin toplamı.</p>
        </div>
    </div>
</section>

<section class="card chart-card mt-4" data-chart='<?= json_encode($chartConfig, JSON_UNESCAPED_UNICODE) ?>'>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="mb-1">Performans Özeti</h5>
                <small class="text-muted">Gösterim ve tıklama trendleri</small>
            </div>
            <div class="chart-actions btn-group">
                <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-spreadsheet"></i> Excel</button>
            </div>
        </div>
        <div class="position-relative" style="height:320px;">
            <canvas id="performanceChart"></canvas>
        </div>
    </div>
</section>

<section class="card mt-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1">Son Bildirimler</h5>
                <small class="text-muted">Onesignal benzeri gelişmiş takip</small>
            </div>
            <button class="btn btn-theme btn-sm" data-refresh="#recentNotifications" data-url="<?= base_url('app/notifications') ?>"><i class="bi bi-arrow-repeat"></i> Yenile</button>
        </div>
        <div class="table-responsive" id="recentNotifications">
            <table class="table table-hover align-middle datatable">
                <thead>
                    <tr>
                        <th>Başlık</th>
                        <th>Mesaj</th>
                        <th>Gönderim Tarihi</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $notification): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($notification['title']) ?></td>
                            <td><?= htmlspecialchars($notification['message']) ?></td>
                            <td><?= htmlspecialchars($notification['created_at']) ?></td>
                            <td><span class="badge-soft">Aktif</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
