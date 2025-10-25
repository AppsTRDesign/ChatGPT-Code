<?php ob_start(); ?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card p-4">
            <h5>Profil Bilgileri</h5>
            <form class="row g-3">
                <div class="col-12">
                    <label class="form-label">Ad Soyad</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($profile['name'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">E-posta</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($profile['email'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Şifre</label>
                    <input type="password" class="form-control" placeholder="Yeni şifre">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="button">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
