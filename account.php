<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/includes/auth.php';

$user = current_user();
$orders = [];
$favorites = [];
$reviews = [];
if ($user) {
    $stmt = db()->prepare('SELECT * FROM orders WHERE user_id = :id OR email = :email ORDER BY created_at DESC');
    $stmt->execute(['id' => $user['id'], 'email' => $user['email']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $favStmt = db()->prepare('SELECT products.* FROM favorites INNER JOIN products ON products.id = favorites.product_id WHERE favorites.user_id = :id ORDER BY favorites.created_at DESC');
    $favStmt->execute(['id' => $user['id']]);
    $favorites = $favStmt->fetchAll(PDO::FETCH_ASSOC);
    $reviewStmt = db()->prepare('SELECT reviews.*, products.slug, products.name AS product_name FROM reviews INNER JOIN products ON products.id = reviews.product_id WHERE reviews.user_id = :id ORDER BY reviews.created_at DESC');
    $reviewStmt->execute(['id' => $user['id']]);
    $reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);
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
            <?php if (!empty($user['avatar'])): ?>
                <img class="avatar-image" src="<?= htmlspecialchars($user['avatar']) ?>" alt="<?= htmlspecialchars($user['name']) ?>">
            <?php endif; ?>
            <p>E-posta: <?= htmlspecialchars($user['email']) ?></p>
            <p>Telefon: <?= htmlspecialchars($user['phone']) ?></p>
        </div>
        <nav class="account-nav">
            <a href="#profil">Profil</a>
            <a href="#adres">Adresler</a>
            <a href="#siparisler">Siparişler</a>
            <a href="#favoriler">Favoriler</a>
            <a href="#yorumlar">Yorumlar</a>
        </nav>
        <section class="section" id="profil">
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
        <section class="section" id="adres">
            <h2>Adres Bilgileri</h2>
            <p>Adres bilgilerinizi aşağıdaki formdan güncelleyebilirsiniz.</p>
            <form class="profile-form" data-ajax="address" method="post">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <textarea name="address" rows="3" placeholder="Adres"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                <button class="btn primary" type="submit">Adres Kaydet</button>
            </form>
        </section>
        <section class="section" id="siparisler">
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
        <section class="section" id="favoriler">
            <h2>Favorilerim</h2>
            <div class="grid">
                <?php foreach ($favorites as $product): ?>
                    <article class="card">
                        <img loading="lazy" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        <div class="card-body">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <a class="btn" href="<?= product_url($product) ?>">Ürünü İncele</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="section" id="yorumlar">
            <h2>Yorumlarım</h2>
            <div class="reviews">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <strong><a href="/urun/<?= urlencode($review['slug']) ?>"><?= htmlspecialchars($review['product_name']) ?></a></strong>
                            <span class="stars"><?= render_stars((int) $review['rating']) ?></span>
                        </div>
                        <p><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <form class="profile-form" data-ajax="profile" enctype="multipart/form-data" method="post">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
            <label>Profil Fotoğrafı<input type="file" name="avatar"></label>
            <button class="btn primary" type="submit">Bilgileri Güncelle</button>
        </form>
        <a class="btn" href="/logout.php">Çıkış Yap</a>
    <?php endif; ?>
</main>
<?php
render_footer();
?>
