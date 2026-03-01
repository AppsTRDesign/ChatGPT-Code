<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$pdo = db();
$editId = (int) ($_GET['edit'] ?? 0);
$currencyData = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM currencies WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $currencyData = $stmt->fetch(PDO::FETCH_ASSOC);
}
$currencies = $pdo->query('SELECT * FROM currencies ORDER BY is_default DESC, name ASC')->fetchAll(PDO::FETCH_ASSOC);

admin_header('Para Birimleri');
?>
<section class="panel">
    <h2><?= $currencyData ? 'Para Birimi Düzenle' : 'Yeni Para Birimi' ?></h2>
    <form class="admin-form" data-ajax="currency" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= (int) ($currencyData['id'] ?? 0) ?>">
        <label>Kod (TRY, USD)<input type="text" name="code" maxlength="10" value="<?= htmlspecialchars($currencyData['code'] ?? '') ?>" required></label>
        <label>Adı<input type="text" name="name" value="<?= htmlspecialchars($currencyData['name'] ?? '') ?>" required></label>
        <label>Sembol<input type="text" name="symbol" value="<?= htmlspecialchars($currencyData['symbol'] ?? '') ?>" required></label>
        <label>Kur (Varsayılan para birimine göre)
            <input type="number" step="0.000001" min="0" name="rate" value="<?= htmlspecialchars((string) ($currencyData['rate'] ?? '1')) ?>" required>
        </label>
        <label>Varsayılan
            <select name="is_default">
                <option value="0" <?= !empty($currencyData) && (int) $currencyData['is_default'] === 0 ? 'selected' : '' ?>>Hayır</option>
                <option value="1" <?= !empty($currencyData) && (int) $currencyData['is_default'] === 1 ? 'selected' : '' ?>>Evet</option>
            </select>
        </label>
        <button class="btn primary" type="submit">Kaydet</button>
    </form>
</section>
<section class="panel">
    <h2>Para Birimleri</h2>
    <table>
        <thead><tr><th>Kod</th><th>Ad</th><th>Sembol</th><th>Kur</th><th>Varsayılan</th><th>İşlem</th></tr></thead>
        <tbody>
        <?php foreach ($currencies as $currency): ?>
            <tr>
                <td><?= htmlspecialchars($currency['code']) ?></td>
                <td><?= htmlspecialchars($currency['name']) ?></td>
                <td><?= htmlspecialchars($currency['symbol']) ?></td>
                <td><?= htmlspecialchars((string) $currency['rate']) ?></td>
                <td><?= (int) $currency['is_default'] === 1 ? 'Evet' : 'Hayır' ?></td>
                <td>
                    <a class="btn" href="/admin/currencies.php?edit=<?= (int) $currency['id'] ?>">Düzenle</a>
                    <button class="btn danger" data-delete-currency="<?= (int) $currency['id'] ?>">Sil</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php admin_footer(); ?>
