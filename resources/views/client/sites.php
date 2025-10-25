<?php ob_start(); ?>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Sitelerim</h5>
            <button class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Site Ekle</button>
        </div>
        <div class="table-responsive">
            <table class="table table-striped datatable">
                <thead>
                <tr>
                    <th>Ad</th>
                    <th>Domain</th>
                    <th>Durum</th>
                    <th>Oluşturulma</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($sites as $site): ?>
                    <tr>
                        <td><?= htmlspecialchars($site['name']) ?></td>
                        <td><?= htmlspecialchars($site['domain']) ?></td>
                        <td><?= $site['is_active'] ? 'Aktif' : 'Pasif' ?></td>
                        <td><?= $site['created_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
