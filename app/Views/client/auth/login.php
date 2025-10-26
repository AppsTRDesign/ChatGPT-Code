<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row justify-content-center py-5">
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card border-0 shadow-lg">
            <div class="card-body p-4">
                <h1 class="h4 text-center mb-4">Panele Giriş</h1>
                <form id="login-form" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Kullanıcı Adı</label>
                        <input type="text" class="form-control form-control-lg" id="username" name="username" required>
                        <div class="invalid-feedback">Kullanıcı adı zorunludur.</div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                        <div class="invalid-feedback">Şifre zorunludur.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg mb-3">Giriş Yap</button>
                    <div class="d-flex justify-content-between small">
                        <a href="/register" class="link-light">Kayıt Ol</a>
                        <a href="/forgot-password" class="link-light">Şifremi Unuttum</a>
                        <a href="/resend-activation" class="link-light">Aktivasyon Tekrarı</a>
                    </div>
                </form>
                <?php if (!empty($firebase['active'])): ?>
                    <div class="text-center mt-4">
                        <button class="btn btn-outline-light w-100" data-action="firebase-login">Google ile Giriş Yap</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
<?php if (!empty($firebase['active'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const button = document.querySelector('[data-action="firebase-login"]');
        if (!button) { return; }
        button.addEventListener('click', () => {
            Swal.fire({ icon: 'info', title: 'Firebase', text: 'Firebase sosyal giriş entegrasyonu etkin. Lütfen yönetim panelinden yapılandırın.' });
        });
    });
</script>
<?php endif; ?>
