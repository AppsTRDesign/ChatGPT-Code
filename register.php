<?php
require __DIR__ . '/config/config.php';
require __DIR__ . '/vendor/autoload.php';

use App\Auth;
use App\Helpers;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
        Helpers::flash('message', 'Geçersiz oturum anahtarı.');
        redirect('/register.php');
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (Auth::register($username, $email, $password)) {
        Helpers::flash('message', 'Kayıt başarılı. Lütfen giriş yapın.');
        redirect('/login.php');
    }

    Helpers::flash('message', 'Kayıt sırasında bir sorun oluştu.');
    redirect('/register.php');
}

require __DIR__ . '/templates/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card p-4">
            <h2 class="h4 mb-4">Kayıt Ol</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                <div class="mb-3">
                    <label for="username" class="form-label">Kullanıcı Adı</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">E-posta</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Şifre</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Kayıt Ol</button>
            </form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/templates/footer.php'; ?>
