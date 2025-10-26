<?php
require_once __DIR__ . '/config.php';
redirect_if_authenticated();
include __DIR__ . '/templates/header.php';
?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card card-glass p-4">
                    <h2 class="h4 text-center mb-4">Yeni Hesap Oluştur</h2>
                    <form data-ajax-form action="<?= BASE_URL ?>/api/auth.php" method="post" novalidate>
                        <input type="hidden" name="action" value="register">
                        <div class="mb-3">
                            <label for="registerName" class="form-label">Ad Soyad</label>
                            <input type="text" class="form-control" id="registerName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="registerEmail" class="form-label">E-posta</label>
                            <input type="email" class="form-control" id="registerEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="registerPassword" class="form-label">Şifre</label>
                            <input type="password" class="form-control" id="registerPassword" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-gradient w-100">Kaydı Tamamla</button>
                    </form>
                    <p class="text-center text-white-50 mt-4 small">Zaten hesabınız var mı? <a class="link-light" href="<?= BASE_URL ?>/login">Giriş yapın</a></p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/templates/footer.php'; ?>
