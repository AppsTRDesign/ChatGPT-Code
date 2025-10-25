<section class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1">Paketler</h5>
                <small class="text-muted">Paketlerin fiyat ve özelliklerini yapılandırın.</small>
            </div>
            <a href="#" class="btn btn-theme btn-sm"><i class="bi bi-plus"></i> Yeni Paket</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover datatable align-middle">
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
                            <td><?= htmlspecialchars($package['monthly_limit']) ?></td>
                            <td><?= htmlspecialchars($package['duration_days']) ?> gün</td>
                            <td><?= htmlspecialchars($package['site_limit']) ?></td>
                            <td>₺<?= number_format((float) $package['price'], 2) ?></td>
                            <td><?= $package['is_active'] ? 'Aktif' : 'Pasif' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
