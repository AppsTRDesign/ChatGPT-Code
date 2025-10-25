<?php ob_start(); ?>
<div class="row justify-content-center py-5">
    <div class="col-lg-5">
        <div class="card p-4">
            <h2 class="text-center mb-4 text-primary">Üye Ol</h2>
            <form method="post" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Ad Soyad</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">E-posta</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Şifre</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="d-grid mt-4">
                    <button class="btn btn-primary" type="submit">Hesap Oluştur</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
