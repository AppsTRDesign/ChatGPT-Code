<section class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card p-4 shadow-sm">
            <h5 class="mb-1">Profil Bilgileri</h5>
            <small class="text-muted">Hesap bilgilerinizi güncelleyerek deneyiminizi kişiselleştirin.</small>
            <form class="row g-3 mt-3">
                <div class="col-12">
                    <label class="form-label">Ad Soyad</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($profile['name'] ?? '') ?>" />
                </div>
                <div class="col-12">
                    <label class="form-label">E-posta</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" />
                </div>
                <div class="col-12">
                    <label class="form-label">Şifre</label>
                    <input type="password" class="form-control" placeholder="Yeni şifre" />
                </div>
                <div class="col-12">
                    <button class="btn btn-theme" type="button"><i class="bi bi-save"></i> Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</section>
