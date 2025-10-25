<?php ob_start(); ?>
<div class="card">
    <div class="card-body">
        <h5 class="mb-3">Satın Alımlar</h5>
        <div class="table-responsive">
            <table class="table table-striped datatable">
                <thead>
                <tr>
                    <th>Üye</th>
                    <th>Paket</th>
                    <th>Tutar</th>
                    <th>Ödeme Yöntemi</th>
                    <th>Durum</th>
                    <th>Oluşturulma</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($purchases as $purchase): ?>
                    <tr>
                        <td><?= htmlspecialchars($purchase['email']) ?></td>
                        <td><?= htmlspecialchars($purchase['package_name']) ?></td>
                        <td>₺<?= number_format($purchase['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($purchase['payment_method']) ?></td>
                        <td><?= htmlspecialchars($purchase['status']) ?></td>
                        <td><?= $purchase['created_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
