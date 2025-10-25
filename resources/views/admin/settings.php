<section class="row g-4" data-dropzone>
    <div class="col-lg-6">
        <div class="card p-4 shadow-sm">
            <h5 class="mb-1">Genel Ayarlar</h5>
            <small class="text-muted">Marka öğeleri ve meta bilgileri.</small>
            <form class="mt-3">
                <div class="mb-3">
                    <label class="form-label">Site Adı</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($app['name']) ?>" />
                </div>
                <div class="mb-3">
                    <label class="form-label">Alt Başlık</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($app['tagline']) ?>" />
                </div>
                <div class="mb-3">
                    <label class="form-label">Logo</label>
                    <div class="dropzone" data-dropzone-message="Logo yükleyin"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Favicon</label>
                    <div class="dropzone" data-dropzone-message="Favicon yükleyin"></div>
                </div>
                <button class="btn btn-theme" type="button">Kaydet</button>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-4 shadow-sm">
            <h5 class="mb-1">Ödeme Ayarları</h5>
            <small class="text-muted">iyzico ve banka transferi seçeneklerini yönetin.</small>
            <div class="form-check form-switch my-3">
                <input class="form-check-input" type="checkbox" id="iyzico" checked>
                <label class="form-check-label" for="iyzico">iyzico Aktif</label>
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="bank">
                <label class="form-check-label" for="bank">Havale/EFT Aktif</label>
            </div>
            <h5 class="mt-4">Mail Ayarları</h5>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="mailer" checked>
                <label class="form-check-label" for="mailer">PHPMailer Kullan</label>
            </div>
            <h5 class="mt-4">Firebase Sosyal Giriş</h5>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="firebase">
                <label class="form-check-label" for="firebase">Firebase Giriş Aktif</label>
            </div>
            <h5 class="mt-4">Google Analytics</h5>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="analytics" checked>
                <label class="form-check-label" for="analytics">Analytics Takibi Aktif</label>
            </div>
        </div>
    </div>
</section>
