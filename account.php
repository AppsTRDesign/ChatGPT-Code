<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/auth.php';

$user = current_user();
$orders = [];
if ($user) {
    $stmt = db()->prepare('SELECT * FROM orders WHERE user_id = :id OR email = :email ORDER BY created_at DESC');
    $stmt->execute(['id' => $user['id'], 'email' => $user['email']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

render_header('Üyelik');
?>
<main class="container">
    <h1>Üyelik Paneli</h1>
    <?php if (!$user): ?>
        <p>Üyelik panelini kullanmak için giriş yapın veya kayıt olun.</p>
        <div class="button-row">
            <a class="btn" href="/login.php">Giriş Yap</a>
            <a class="btn primary" href="/register.php">Üye Ol</a>
        </div>
    <?php else: ?>
        <div class="account-info">
            <h2>Merhaba, <?= htmlspecialchars($user['name']) ?></h2>
            <p>E-posta: <?= htmlspecialchars($user['email']) ?></p>
            <p>Telefon: <?= htmlspecialchars($user['phone']) ?></p>
        </div>
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
                            <td><?= htmlspecialchars($order['status']) ?></td>
                            <td><?= htmlspecialchars($order['channel']) ?></td>
                            <td><?= currency((float) $order['total_amount']) ?></td>
                            <td><?= htmlspecialchars($order['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        <form class="profile-form" data-ajax="profile" method="post">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
            <button class="btn primary" type="submit">Bilgileri Güncelle</button>
        </form>
        <a class="btn" href="/logout.php">Çıkış Yap</a>
    <?php endif; ?>
</main>
<?php
render_footer();
?>
