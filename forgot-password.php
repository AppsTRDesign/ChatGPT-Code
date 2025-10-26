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
                    <h2 class="h4 text-center mb-4">Şifre Sıfırlama</h2>
                    <form data-ajax-form action="<?= BASE_URL ?>/api/auth.php" method="post" novalidate>
                        <input type="hidden" name="action" value="forgot">
                        <div class="mb-3">
                            <label for="forgotEmail" class="form-label">E-posta</label>
                            <input type="email" class="form-control" id="forgotEmail" name="email" required>
                        </div>
                        <button type="submit" class="btn btn-gradient w-100">Sıfırlama Bağlantısı Gönder</button>
                    </form>
                    <p class="text-center text-white-50 mt-4 small"><a class="link-light" href="<?= BASE_URL ?>/login">Giriş sayfasına dön</a></p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/templates/footer.php'; ?>
