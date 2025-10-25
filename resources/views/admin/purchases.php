<section class="card">
    <div class="card-body">
        <h5 class="mb-3">Satın Alımlar</h5>
        <div class="table-responsive">
            <table class="table table-hover datatable align-middle">
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
                            <td>₺<?= number_format((float) $purchase['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($purchase['payment_method']) ?></td>
                            <td><?= htmlspecialchars($purchase['status']) ?></td>
                            <td><?= htmlspecialchars($purchase['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
