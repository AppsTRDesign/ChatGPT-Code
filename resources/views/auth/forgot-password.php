<?php ob_start(); ?>
<div class="row justify-content-center py-5">
    <div class="col-lg-4">
        <div class="card p-4">
            <h2 class="text-center mb-4 text-primary">Şifremi Unuttum</h2>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">E-posta</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="d-grid">
                    <button class="btn btn-primary" type="submit">Şifre Sıfırlama Gönder</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
