<?php ob_start(); ?>
<div class="row justify-content-center py-5">
    <div class="col-lg-4">
        <div class="card p-4">
            <h2 class="text-center mb-4 text-primary">Giriş Yap</h2>
            <form method="post" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label class="form-label">E-posta</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Şifre</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="d-grid">
                    <button class="btn btn-primary" type="submit">Giriş</button>
                </div>
                <div class="d-flex justify-content-between mt-3">
                    <a href="/forgot-password">Şifremi unuttum</a>
                    <a href="/register">Üye Ol</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
