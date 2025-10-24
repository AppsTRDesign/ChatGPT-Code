<?php
require_once __DIR__ . '/config/config.php';

use App\Auth;
use App\Helpers;
use App\Settings;

if (Auth::check()) {
    $destination = Auth::user()['role'] === 'admin' ? '/admin/dashboard' : '/client/dashboard';
    redirect($destination);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
        Helpers::flash('message', 'Geçersiz oturum anahtarı.');
        redirect('/register');
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $userId = Auth::register($username, $email, $password);
    if ($userId) {
        Auth::createVerification($userId, $email);
        Helpers::flash('message', 'Kayıt başarılı. E-postanızı doğrulamak için gelen kutunuzu kontrol edin.');
        redirect('/login');
    }

    Helpers::flash('message', 'Kayıt sırasında bir sorun oluştu. Kullanıcı adı veya e-posta daha önce kullanılmış olabilir.');
    redirect('/register');
}

$socialProviders = Settings::firebaseProviders();
$showSocialLogin = Settings::firebaseEnabled() && !empty($socialProviders);
$providerLabels = [
    'google' => 'Google',
    'facebook' => 'Facebook',
    'twitter' => 'Twitter',
    'github' => 'GitHub',
    'microsoft' => 'Microsoft',
    'apple' => 'Apple',
    'yahoo' => 'Yahoo',
];

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
            <?php if ($showSocialLogin): ?>
                <div class="mt-4">
                    <p class="text-white-50 small mb-2">Sosyal hesabınızla kayıt olun:</p>
                    <div class="d-flex flex-wrap gap-2" data-social-auth>
                        <?php foreach ($socialProviders as $provider): $key = strtolower($provider); ?>
                            <?php if (!isset($providerLabels[$key])) { continue; } ?>
                            <button type="button" class="btn btn-outline-info flex-grow-1" data-firebase-provider="<?= $key ?>">
                                <?= $providerLabels[$key] ?> ile devam et
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/templates/footer.php'; ?>
