<?php ob_start(); ?>
<div class="card glass border-0">
    <div class="card-body">
        <form data-ajax="true" action="/admin/settings" method="post" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="row g-4">
                <div class="col-md-6">
                    <h5 class="text-secondary">Telegram API</h5>
                    <div class="mb-3">
                        <label class="form-label">API ID</label>
                        <input type="text" name="telegram_api_id" value="<?= htmlspecialchars($settings['telegram_api_id'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">API Hash</label>
                        <input type="text" name="telegram_api_hash" value="<?= htmlspecialchars($settings['telegram_api_hash'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Global Rate Limit</label>
                        <input type="number" name="rate_limit_global" value="<?= htmlspecialchars($settings['rate_limit_global'] ?? '60') ?>" class="form-control">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Telefon Başına</label>
                            <input type="number" name="rate_limit_per_phone" value="<?= htmlspecialchars($settings['rate_limit_per_phone'] ?? '30') ?>" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kanal Başına</label>
                            <input type="number" name="rate_limit_per_channel" value="<?= htmlspecialchars($settings['rate_limit_per_channel'] ?? '15') ?>" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5 class="text-secondary">Mail Ayarları</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Sunucu</label>
                            <input type="text" name="mail_host" value="<?= htmlspecialchars($settings['mail_host'] ?? '') ?>" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Port</label>
                            <input type="text" name="mail_port" value="<?= htmlspecialchars($settings['mail_port'] ?? '') ?>" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kullanıcı Adı</label>
                            <input type="text" name="mail_username" value="<?= htmlspecialchars($settings['mail_username'] ?? '') ?>" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Parola</label>
                            <input type="password" name="mail_password" value="<?= htmlspecialchars($settings['mail_password'] ?? '') ?>" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Şifreleme</label>
                            <input type="text" name="mail_encryption" value="<?= htmlspecialchars($settings['mail_encryption'] ?? '') ?>" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gönderici Adresi</label>
                            <input type="email" name="mail_from_address" value="<?= htmlspecialchars($settings['mail_from_address'] ?? '') ?>" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gönderici Adı</label>
                            <input type="text" name="mail_from_name" value="<?= htmlspecialchars($settings['mail_from_name'] ?? '') ?>" class="form-control">
                        </div>
                    </div>
                    <div class="mt-4">
                        <h5 class="text-secondary">Marka Ayarları</h5>
                        <div class="mb-3">
                            <label class="form-label">Logo ve Favicon (SVG önerilir)</label>
                            <input type="file" name="branding[]" class="form-control" accept="image/svg+xml,image/png,image/x-icon" multiple>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-4 text-end">
                <button class="btn btn-primary" type="submit">Ayarları Kaydet</button>
            </div>
        </form>
    </div>
</div>
<?php $content = ob_get_clean(); include resource_path('views/layouts/app.php');
