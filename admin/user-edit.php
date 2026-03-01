<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$userId = (int) ($_GET['id'] ?? 0);
if (!$userId) {
    header('Location: /admin/users.php');
    exit;
}
$stmt = db()->prepare('SELECT id, name, email, phone, address, role FROM users WHERE id = :id');
$stmt->execute(['id' => $userId]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$userData) {
    header('Location: /admin/users.php');
    exit;
}

admin_header('Kullanıcı Düzenle');
?>
<section class="panel">
    <h2>Kullanıcı Düzenle</h2>
    <form class="admin-form" data-ajax="user-admin-update" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= (int) $userData['id'] ?>">
        <label>Ad Soyad<input type="text" name="name" value="<?= htmlspecialchars($userData['name']) ?>" required></label>
        <label>E-posta<input type="email" name="email" value="<?= htmlspecialchars($userData['email']) ?>" required></label>
        <label>Telefon<input type="text" name="phone" value="<?= htmlspecialchars($userData['phone'] ?? '') ?>"></label>
        <label>Adres<textarea name="address" rows="3"><?= htmlspecialchars($userData['address'] ?? '') ?></textarea></label>
        <label>Rol
            <select name="role">
                <option value="customer" <?= $userData['role'] === 'customer' ? 'selected' : '' ?>>Müşteri</option>
                <option value="admin" <?= $userData['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </label>
        <label>Yeni Şifre (opsiyonel)<input type="password" name="password" minlength="6"></label>
        <div class="button-row">
            <button class="btn primary" type="submit">Kaydet</button>
            <a class="btn" href="/admin/users.php">Listeye Dön</a>
        </div>
    </form>
</section>
<?php admin_footer(); ?>
