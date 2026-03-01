<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$pdo = db();
$editId = (int) ($_GET['edit'] ?? 0);
$walletData = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM crypto_wallets WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $walletData = $stmt->fetch(PDO::FETCH_ASSOC);
}
$wallets = $pdo->query('SELECT * FROM crypto_wallets ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

admin_header('Kripto Ödeme');
?>
<section class="panel">
    <form class="admin-form" data-ajax="crypto-settings" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <label>Kripto Ödeme Aktif
            <select name="crypto_active">
                <option value="0" <?= settings('crypto_active', '0') === '0' ? 'selected' : '' ?>>Hayır</option>
                <option value="1" <?= settings('crypto_active', '0') === '1' ? 'selected' : '' ?>>Evet</option>
            </select>
        </label>
        <button class="btn primary" type="submit">Ayarı Kaydet</button>
    </form>
</section>
<section class="panel">
    <h2><?= $walletData ? 'Cüzdan Düzenle' : 'Cüzdan Ekle' ?></h2>
    <form class="admin-form" data-ajax="crypto-wallet" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= (int) ($walletData['id'] ?? 0) ?>">
        <label>Cüzdan İsmi<input type="text" name="wallet_name" value="<?= htmlspecialchars($walletData['wallet_name'] ?? '') ?>" required></label>
        <label>Cüzdan Adresi<textarea name="wallet_address" rows="3" required><?= htmlspecialchars($walletData['wallet_address'] ?? '') ?></textarea></label>
        <button class="btn primary" type="submit">Kaydet</button>
    </form>
</section>
<section class="panel">
    <h2>Cüzdanlar</h2>
    <table>
        <thead><tr><th>İsim</th><th>Adres</th><th>İşlem</th></tr></thead>
        <tbody>
        <?php foreach ($wallets as $wallet): ?>
            <tr>
                <td><?= htmlspecialchars($wallet['wallet_name']) ?></td>
                <td><?= htmlspecialchars($wallet['wallet_address']) ?></td>
                <td>
                    <a class="btn" href="/admin/crypto.php?edit=<?= (int) $wallet['id'] ?>">Düzenle</a>
                    <button class="btn danger" data-delete-crypto-wallet="<?= (int) $wallet['id'] ?>">Sil</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php admin_footer(); ?>
