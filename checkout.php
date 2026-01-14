<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

$slug = $_GET['slug'] ?? '';
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM products WHERE slug = :slug');
$stmt->execute(['slug' => $slug]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    render_header('Sipariş Bulunamadı');
    echo '<main class="container"><p>Ürün bulunamadı.</p></main>';
    render_footer();
    exit;
}

$user = current_user();
render_header('Sipariş Adımları');
?>
<main class="container checkout">
    <h1><?= htmlspecialchars($product['name']) ?> Siparişi</h1>
    <div class="checkout-steps">
        <div class="step <?= $user ? '' : 'active' ?>" data-step="1">
            <h2>1. Giriş / Üyelik</h2>
            <div class="grid">
                <form class="auth-form" data-ajax="login-inline" method="post">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="email" name="email" placeholder="E-posta" required>
                    <input type="password" name="password" placeholder="Şifre" required>
                    <button class="btn primary" type="submit">Giriş Yap</button>
                </form>
                <form class="auth-form" data-ajax="register-inline" method="post">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="text" name="name" placeholder="Ad Soyad" required>
                    <input type="email" name="email" placeholder="E-posta" required>
                    <input type="tel" name="phone" placeholder="Telefon">
                    <input type="password" name="password" placeholder="Şifre" required>
                    <button class="btn primary" type="submit">Kayıt Ol</button>
                </form>
            </div>
        </div>
        <div class="step <?= $user ? 'active' : '' ?>" data-step="2">
            <h2>2. Teslimat Bilgileri</h2>
            <form class="order-form" data-ajax="checkout" method="post">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <input type="hidden" name="channel" value="<?= htmlspecialchars($product['order_channel']) ?>">
                <div class="form-grid">
                    <input type="text" name="full_name" placeholder="Ad Soyad" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                    <input type="email" name="email" placeholder="E-posta" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                    <input type="tel" name="phone" placeholder="Telefon" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                    <textarea name="address" placeholder="Teslimat Adresi" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    <textarea name="order_note" placeholder="Sipariş Notu (Opsiyonel)" rows="2"></textarea>
                    <input type="number" name="quantity" min="1" value="1" required>
                </div>
                <button class="btn primary" type="submit">Siparişi Onayla</button>
            </form>
        </div>
        <div class="step" data-step="3">
            <h2>3. Ödeme / Onay</h2>
            <div id="checkoutResult"></div>
        </div>
    </div>
</main>
<?php
render_footer();
?>
