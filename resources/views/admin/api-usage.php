<?php ob_start(); ?>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">API Kullanım Özeti</h5>
            <div>
                <button class="btn btn-sm btn-outline-primary">PDF</button>
                <button class="btn btn-sm btn-outline-primary">Excel</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped datatable">
                <thead>
                <tr>
                    <th>Token</th>
                    <th>Kullanıcı</th>
                    <th>Çağrı Sayısı</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($usage as $row): ?>
                    <tr>
                        <td class="text-break"><code><?= $row['token'] ?></code></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= number_format($row['usage_count']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
