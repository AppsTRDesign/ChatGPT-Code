<?php
require __DIR__ . '/config/config.php';
require __DIR__ . '/vendor/autoload.php';

use App\Auth;
use App\Helpers;

if (Auth::check()) {
    $destination = Auth::user()['role'] === 'admin' ? '/admin/dashboard' : '/client/dashboard';
    redirect($destination);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
        Helpers::flash('message', 'Geçersiz oturum anahtarı.');
        redirect('/login');
    }

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (Auth::login($username, $password)) {
        Helpers::flash('message', 'Hoş geldiniz.');
        $redirect = '/client/dashboard';
        if (Auth::user()['role'] === 'admin') {
            $redirect = '/admin/dashboard';
        }
        redirect($redirect);
    }

    Helpers::flash('message', 'Kullanıcı adı veya şifre hatalı.');
    redirect('/login');
}

require __DIR__ . '/templates/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card p-4">
            <h2 class="h4 mb-4">Giriş Yap</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                <div class="mb-3">
                    <label for="username" class="form-label">Kullanıcı Adı</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Şifre</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
            </form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/templates/footer.php'; ?>
