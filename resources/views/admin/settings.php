<?php ob_start(); ?>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card p-4">
            <h5>Genel Ayarlar</h5>
            <form>
                <div class="mb-3">
                    <label class="form-label">Site Adı</label>
                    <input type="text" class="form-control" placeholder="NoaSoft WebPush">
                </div>
                <div class="mb-3">
                    <label class="form-label">Alt Başlık</label>
                    <input type="text" class="form-control" placeholder="Web bildirim platformu">
                </div>
                <div class="mb-3">
                    <label class="form-label">Logo</label>
                    <div class="dropzone border-dashed"></div>
                </div>
                <button class="btn btn-primary" type="button">Kaydet</button>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-4">
            <h5>Ödeme Ayarları</h5>
            <div class="form-check form-switch mb-3">
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
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
