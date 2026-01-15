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
$paytrActive = settings('paytr_active') === '1';
$bankTransferActive = settings('bank_transfer_active') === '1';
$quantity = max(1, (int) ($_GET['qty'] ?? 1));
$defaultChannel = $product['order_channel'];
if ($defaultChannel === 'paytr' && !$paytrActive) {
    $defaultChannel = $bankTransferActive ? 'bank_transfer' : 'whatsapp';
}
$availableChannels = [$defaultChannel, $product['order_channel']];
if ($paytrActive) {
    $availableChannels[] = 'paytr';
}
if ($bankTransferActive) {
    $availableChannels[] = 'bank_transfer';
}
$availableChannels = array_values(array_unique($availableChannels));
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
                <input type="hidden" name="channel" value="<?= htmlspecialchars($defaultChannel) ?>">
                <input type="hidden" name="quantity" value="<?= $quantity ?>">
                <div class="form-grid">
                    <label>Ad Soyad
                        <input type="text" name="full_name" placeholder="Ad Soyad" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                    </label>
                    <label>E-posta
                        <input type="email" name="email" placeholder="E-posta" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                    </label>
                    <label>Telefon
                        <input type="tel" name="phone" placeholder="Telefon" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                    </label>
                    <label>Teslimat Adresi
                        <textarea name="address" placeholder="Teslimat Adresi" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </label>
                    <label>Sipariş Notu (Opsiyonel)
                        <textarea name="order_note" placeholder="Sipariş Notu (Opsiyonel)" rows="2"></textarea>
                    </label>
                    <label>Ödeme Yöntemi
                        <select name="payment_method">
                            <option value="<?= htmlspecialchars($defaultChannel) ?>">Standart (<?= htmlspecialchars($defaultChannel) ?>)</option>
                            <?php if ($paytrActive && in_array('paytr', $availableChannels, true) && $defaultChannel !== 'paytr'): ?>
                                <option value="paytr">Kredi Kartı (PayTR)</option>
                            <?php endif; ?>
                            <?php if ($bankTransferActive && in_array('bank_transfer', $availableChannels, true) && $defaultChannel !== 'bank_transfer'): ?>
                                <option value="bank_transfer">Banka Havalesi</option>
                            <?php endif; ?>
                        </select>
                    </label>
                </div>
                <div class="order-summary">
                    <span>Birim Fiyat: <?= currency((float) $product['price']) ?></span>
                    <span>Adet: <?= $quantity ?></span>
                    <strong>Toplam: <?= currency($quantity * (float) $product['price']) ?></strong>
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
