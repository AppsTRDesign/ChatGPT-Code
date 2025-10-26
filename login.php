<?php
require_once __DIR__ . '/config.php';
redirect_if_authenticated();
include __DIR__ . '/templates/header.php';
?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card card-glass p-4">
                    <h2 class="h4 text-center mb-4">Giriş Yap</h2>
                    <form data-ajax-form action="<?= BASE_URL ?>/api/auth.php" method="post" novalidate>
                        <input type="hidden" name="action" value="login">
                        <div class="mb-3">
                            <label for="loginEmail" class="form-label">E-posta</label>
                            <input type="email" class="form-control" id="loginEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="loginPassword" class="form-label">Şifre</label>
                            <input type="password" class="form-control" id="loginPassword" name="password" required>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <a href="<?= BASE_URL ?>/forgot-password" class="link-light small">Şifremi unuttum</a>
                        </div>
                        <button type="submit" class="btn btn-gradient w-100">Giriş Yap</button>
                    </form>
                    <p class="text-center text-white-50 mt-4 small">Hesabınız yok mu? <a class="link-light" href="<?= BASE_URL ?>/register">Hemen üye olun</a></p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/templates/footer.php'; ?>
