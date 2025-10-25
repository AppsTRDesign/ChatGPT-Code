<?php ob_start(); ?>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Paketler</h5>
            <a href="#" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Yeni Paket</a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped datatable">
                <thead>
                    <tr>
                        <th>Ad</th>
                        <th>Aylık Limit</th>
                        <th>Kullanım Süresi</th>
                        <th>Site Sayısı</th>
                        <th>Fiyat</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($packages as $package): ?>
                    <tr>
                        <td><?= htmlspecialchars($package['name']) ?></td>
                        <td><?= $package['monthly_limit'] ?></td>
                        <td><?= $package['duration_days'] ?> gün</td>
                        <td><?= $package['site_limit'] ?></td>
                        <td>₺<?= number_format($package['price'], 2) ?></td>
                        <td><?= $package['is_active'] ? 'Aktif' : 'Pasif' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
