<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('client');
$user = Auth::user();
$db = Helpers::db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
        Helpers::flash('message', 'Geçersiz oturum anahtarı.');
        redirect('/client/profile');
    }

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $db->prepare('UPDATE users SET email = :email WHERE id = :id');
    $stmt->execute(['email' => $email, 'id' => $user['id']]);

    if ($password !== '') {
        $stmt = $db->prepare('UPDATE users SET password = :password WHERE id = :id');
        $stmt->execute(['password' => password_hash($password, PASSWORD_DEFAULT), 'id' => $user['id']]);
    }

    Helpers::flash('message', 'Profil güncellendi.');
    redirect('/client/profile');
}

$stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute(['id' => $user['id']]);
$current = $stmt->fetch();

require __DIR__ . '/../templates/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card p-4">
            <h2 class="h4 mb-4">Profil Ayarları</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                <div class="mb-3">
                    <label class="form-label">Kullanıcı Adı</label>
                    <input type="text" class="form-control" value="<?= Helpers::e($current['username']) ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">E-posta</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= Helpers::e($current['email'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Yeni Şifre</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Boş bırakırsanız değişmez">
                </div>
                <button type="submit" class="btn btn-primary w-100">Kaydet</button>
            </form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
