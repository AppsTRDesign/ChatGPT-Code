<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$pdo = db();
$summary = [
    'pending' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
    'approved' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'approved'")->fetchColumn(),
    'preparing' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'preparing'")->fetchColumn(),
    'shipping' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'shipping'")->fetchColumn(),
    'delivered' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn(),
];

$orders = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);

admin_header('Sipariş Dashboard');
?>
<section class="admin-cards">
    <div class="card"><h3>Bekleyen</h3><p><?= $summary['pending'] ?></p></div>
    <div class="card"><h3>Onaylandı</h3><p><?= $summary['approved'] ?></p></div>
    <div class="card"><h3>Hazırlanıyor</h3><p><?= $summary['preparing'] ?></p></div>
    <div class="card"><h3>Yola Çıktı</h3><p><?= $summary['shipping'] ?></p></div>
    <div class="card"><h3>Teslim Edildi</h3><p><?= $summary['delivered'] ?></p></div>
</section>

<section class="chart-section">
    <canvas id="orderChart"></canvas>
    <div class="date-filter">
        <input type="date" id="filterStart">
        <input type="date" id="filterEnd">
        <button class="btn" data-chart-refresh>Raporu Güncelle</button>
        <button class="btn" data-export="pdf">PDF Dışa Aktar</button>
        <button class="btn" data-export="excel">Excel Dışa Aktar</button>
    </div>
</section>

<section class="table-section">
    <h2>Son Siparişler</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Müşteri</th>
                <th>Durum</th>
                <th>Kanal</th>
                <th>Tutar</th>
                <th>Tarih</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= (int) $order['id'] ?></td>
                    <td><?= htmlspecialchars($order['full_name']) ?></td>
                    <td><?= htmlspecialchars($order['status']) ?></td>
                    <td><?= htmlspecialchars($order['channel']) ?></td>
                    <td><?= currency((float) $order['total_amount']) ?></td>
                    <td><?= htmlspecialchars($order['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php
admin_footer();
?>
