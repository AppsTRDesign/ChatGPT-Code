<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/account_nav.php';

$user = current_user();
$orders = [];
if ($user) {
    $stmt = db()->prepare('SELECT * FROM orders WHERE user_id = :id OR email = :email ORDER BY created_at DESC');
    $stmt->execute(['id' => $user['id'], 'email' => $user['email']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

render_header('Siparişler');
?>
<main class="container">
    <h1>Siparişler</h1>
    <?php if (!$user): ?>
        <p>Siparişlerinizi görüntülemek için giriş yapın.</p>
        <div class="button-row">
            <a class="btn" href="/giris">Giriş Yap</a>
            <a class="btn primary" href="/kayit">Üye Ol</a>
        </div>
    <?php else: ?>
        <?php render_account_nav('siparisler'); ?>
        <section class="section">
            <h2>Sipariş Geçmişi</h2>
            <table>
                <thead>
                    <tr>
                        <th>Sipariş</th>
                        <th>Durum</th>
                        <th>Kanal</th>
                        <th>Tutar</th>
                        <th>Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?= (int) $order['id'] ?></td>
                            <td><span class="badge badge-<?= htmlspecialchars($order['status']) ?>"><?= order_status_label($order['status']) ?></span></td>
                            <td><?= htmlspecialchars($order['channel']) ?></td>
                            <td><?= currency((float) $order['total_amount']) ?></td>
                            <td><?= htmlspecialchars($order['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
</main>
<?php
render_footer();
?>
