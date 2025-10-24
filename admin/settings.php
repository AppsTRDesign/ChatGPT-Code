<?php
require __DIR__ . '/header.php';

use App\Helpers;
use App\MailSettings;
use App\Payment;
use App\Settings;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
        Helpers::flash('message', 'Oturum doğrulaması başarısız.');
        redirect('/admin/settings');
    }

    $section = $_POST['section'] ?? 'general';

    if ($section === 'general') {
        Settings::setMany([
            'site_name' => trim($_POST['site_name'] ?? ''),
            'site_tagline' => trim($_POST['site_tagline'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
            'header_html' => trim($_POST['header_html'] ?? ''),
            'footer_html' => trim($_POST['footer_html'] ?? ''),
        ]);
        Helpers::flash('message', 'Genel ayarlar güncellendi.');
    } elseif ($section === 'payments') {
        Payment::update([
            'iyzico_enabled' => isset($_POST['iyzico_enabled']) ? 1 : 0,
            'iyzico_api_key' => trim($_POST['iyzico_api_key'] ?? ''),
            'iyzico_secret_key' => trim($_POST['iyzico_secret_key'] ?? ''),
            'iyzico_base_url' => trim($_POST['iyzico_base_url'] ?? 'https://sandbox-api.iyzipay.com'),
            'bank_account' => trim($_POST['bank_account'] ?? ''),
            'bank_enabled' => isset($_POST['bank_enabled']) ? 1 : 0,
        ]);
        Helpers::flash('message', 'Ödeme ayarları güncellendi.');
    } elseif ($section === 'mail') {
        MailSettings::update([
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'transport' => $_POST['transport'] ?? 'mail',
            'host' => trim($_POST['host'] ?? ''),
            'port' => (int) ($_POST['port'] ?? 587),
            'username' => trim($_POST['username'] ?? ''),
            'password' => trim($_POST['password'] ?? ''),
            'encryption' => $_POST['encryption'] ?? 'none',
            'from_email' => trim($_POST['from_email'] ?? ''),
            'from_name' => trim($_POST['from_name'] ?? ''),
            'reply_to_email' => trim($_POST['reply_to_email'] ?? ''),
        ]);
        Helpers::flash('message', 'Mail ayarları güncellendi.');
    } elseif ($section === 'firebase') {
        $configRaw = trim($_POST['firebase_config'] ?? '');
        if ($configRaw !== '') {
            $decoded = json_decode($configRaw, true);
            if (!is_array($decoded)) {
                Helpers::flash('message', 'Firebase yapılandırması geçersiz JSON formatında.');
                redirect('/admin/settings');
            }
            $configRaw = json_encode($decoded, JSON_UNESCAPED_UNICODE);
        }

        $providersInput = $_POST['firebase_providers'] ?? [];
        if (!is_array($providersInput)) {
            $providersInput = [];
        }
        $allowedProviders = ['google', 'facebook', 'twitter', 'github', 'microsoft', 'apple', 'yahoo'];
        $providers = array_values(array_intersect($allowedProviders, array_map(static function ($value) {
            return strtolower((string) $value);
        }, $providersInput)));

        Settings::setMany([
            'firebase_enabled' => isset($_POST['firebase_enabled']) ? '1' : '0',
            'firebase_config' => $configRaw,
            'firebase_providers' => $providers,
        ]);
        Helpers::flash('message', 'Firebase ayarları güncellendi.');
    } elseif ($section === 'analytics') {
        Settings::setMany([
            'google_analytics_enabled' => isset($_POST['google_analytics_enabled']) ? '1' : '0',
            'google_analytics_id' => trim($_POST['google_analytics_id'] ?? ''),
        ]);
        Helpers::flash('message', 'Google Analytics ayarları güncellendi.');
    } elseif ($section === 'onesignal') {
        Settings::setMany([
            'onesignal_enabled' => isset($_POST['onesignal_enabled']) ? '1' : '0',
            'onesignal_app_id' => trim($_POST['onesignal_app_id'] ?? ''),
            'onesignal_rest_key' => trim($_POST['onesignal_rest_key'] ?? ''),
            'onesignal_safari_web_id' => trim($_POST['onesignal_safari_web_id'] ?? ''),
        ]);
        Helpers::flash('message', 'OneSignal ayarları güncellendi.');
    }

    redirect('/admin/settings');
}

$siteSettings = Settings::all();
$paymentSettings = Payment::settings();
$mailSettings = MailSettings::get();
$logoUrl = Settings::logoUrl();
$faviconUrl = Settings::faviconUrl();
$firebaseConfig = $siteSettings['firebase_config'] ?? '';
$firebaseProviders = Settings::firebaseProviders();
$firebaseEnabled = Settings::firebaseEnabled();
$gaEnabled = Settings::googleAnalyticsEnabled();
$gaId = Settings::googleAnalyticsId();
$onesignalEnabled = Settings::onesignalEnabled();
$onesignalAppId = Settings::onesignalAppId();
$onesignalSafari = Settings::onesignalSafariWebId();
$onesignalRest = Settings::onesignalRestKey();
$providerLabels = [
    'google' => 'Google',
    'facebook' => 'Facebook',
    'twitter' => 'Twitter',
    'github' => 'GitHub',
    'microsoft' => 'Microsoft',
    'apple' => 'Apple',
    'yahoo' => 'Yahoo',
];
?>
<h1 class="h3 mb-4">Site ve Ödeme Ayarları</h1>
<div class="row g-4">
    <div class="col-xl-6">
        <div class="card p-4 h-100">
            <h2 class="h5 mb-3">Genel Bilgiler</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                <input type="hidden" name="section" value="general">
                <div class="mb-3">
                    <label class="form-label">Site Adı</label>
                    <input type="text" class="form-control" name="site_name" value="<?= Helpers::e($siteSettings['site_name'] ?? Settings::siteName()) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Alt Başlık</label>
                    <input type="text" class="form-control" name="site_tagline" value="<?= Helpers::e($siteSettings['site_tagline'] ?? Settings::siteTagline()) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Meta Açıklama</label>
                    <textarea class="form-control" name="meta_description" rows="3"><?= Helpers::e($siteSettings['meta_description'] ?? Settings::metaDescription()) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Meta Anahtar Kelimeler</label>
                    <textarea class="form-control" name="meta_keywords" rows="2" placeholder="virgül ile ayırın"><?= Helpers::e($siteSettings['meta_keywords'] ?? Settings::metaKeywords()) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Header HTML</label>
                    <textarea class="form-control" name="header_html" rows="3" placeholder="Analytics, script vb."><?= Helpers::e($siteSettings['header_html'] ?? Settings::headerHtml()) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Footer HTML</label>
                    <textarea class="form-control" name="footer_html" rows="3" placeholder="İletişim bilgileri veya scriptler"><?= Helpers::e($siteSettings['footer_html'] ?? Settings::footerHtml()) ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </form>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card brand-card p-4 h-100">
            <h2 class="h5 mb-3">Marka Öğeleri</h2>
            <div class="row g-4">
                <div class="col-sm-6">
                    <div class="brand-upload text-center p-4 h-100">
                        <h3 class="h6 text-uppercase text-white-50 mb-3">Logo</h3>
                        <div class="brand-preview mb-3">
                            <?php if ($logoUrl): ?>
                                <img src="<?= Helpers::e($logoUrl) ?>" alt="Site Logosu" class="img-fluid brand-media">
                                <form method="post" action="/admin/delete-branding" class="d-inline mt-3">
                                    <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                                    <input type="hidden" name="type" value="logo">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Kaldır</button>
                                </form>
                            <?php else: ?>
                                <p class="text-white-50">Logo yüklenmedi. Varsayılan olarak site adı gösterilecek.</p>
                            <?php endif; ?>
                        </div>
                        <form action="/admin/upload-branding"
                              class="dropzone brand-dropzone"
                              data-dropzone-url="/admin/upload-branding"
                              data-dropzone-type="logo"
                              data-dropzone-csrf="<?= Helpers::csrfToken() ?>"
                              data-dropzone-refresh="1"></form>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="brand-upload text-center p-4 h-100">
                        <h3 class="h6 text-uppercase text-white-50 mb-3">Favicon</h3>
                        <div class="brand-preview mb-3">
                            <?php if ($faviconUrl): ?>
                                <img src="<?= Helpers::e($faviconUrl) ?>" alt="Site Favicon" class="img-fluid brand-media brand-media--small">
                                <form method="post" action="/admin/delete-branding" class="d-inline mt-3">
                                    <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                                    <input type="hidden" name="type" value="favicon">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Kaldır</button>
                                </form>
                            <?php else: ?>
                                <p class="text-white-50">Favicon yüklenmedi.</p>
                            <?php endif; ?>
                        </div>
                        <form action="/admin/upload-branding"
                              class="dropzone brand-dropzone"
                              data-dropzone-url="/admin/upload-branding"
                              data-dropzone-type="favicon"
                              data-dropzone-csrf="<?= Helpers::csrfToken() ?>"
                              data-dropzone-refresh="1"></form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card p-4 h-100">
            <h2 class="h5 mb-3">Ödeme Ayarları</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                <input type="hidden" name="section" value="payments">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="iyzico_enabled" name="iyzico_enabled" <?= $paymentSettings['iyzico_enabled'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="iyzico_enabled">İyzico (Kredi Kartı) Aktif</label>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">İyzico API Key</label>
                        <input type="text" class="form-control" name="iyzico_api_key" value="<?= Helpers::e($paymentSettings['iyzico_api_key'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">İyzico Secret Key</label>
                        <input type="text" class="form-control" name="iyzico_secret_key" value="<?= Helpers::e($paymentSettings['iyzico_secret_key'] ?? '') ?>">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">İyzico Base URL</label>
                    <input type="text" class="form-control" name="iyzico_base_url" value="<?= Helpers::e($paymentSettings['iyzico_base_url'] ?? 'https://sandbox-api.iyzipay.com') ?>" placeholder="https://sandbox-api.iyzipay.com">
                    <small class="text-white-50">Test ortamı için sandbox adresini kullanın.</small>
                </div>
                <div class="form-check form-switch my-3">
                    <input class="form-check-input" type="checkbox" id="bank_enabled" name="bank_enabled" <?= $paymentSettings['bank_enabled'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="bank_enabled">Banka Havalesi Aktif</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Banka Bilgileri</label>
                    <textarea class="form-control" name="bank_account" rows="4" placeholder="Banka adı, IBAN, açıklama vb."><?= Helpers::e($paymentSettings['bank_account'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </form>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card p-4 h-100">
            <h2 class="h5 mb-3">Mail Ayarları</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                <input type="hidden" name="section" value="mail">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?= $mailSettings['is_active'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">PHPMailer ile SMTP gönderimi aktif</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Gönderim Yöntemi</label>
                    <select name="transport" class="form-select">
                        <option value="mail" <?= ($mailSettings['transport'] ?? 'mail') === 'mail' ? 'selected' : '' ?>>PHP mail()</option>
                        <option value="smtp" <?= ($mailSettings['transport'] ?? 'mail') === 'smtp' ? 'selected' : '' ?>>SMTP</option>
                    </select>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" class="form-control" name="host" value="<?= Helpers::e($mailSettings['host'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Port</label>
                        <input type="number" class="form-control" name="port" value="<?= Helpers::e($mailSettings['port'] ?? 587) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Şifreleme</label>
                        <select name="encryption" class="form-select">
                            <option value="none" <?= ($mailSettings['encryption'] ?? 'none') === 'none' ? 'selected' : '' ?>>Yok</option>
                            <option value="ssl" <?= ($mailSettings['encryption'] ?? 'none') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                            <option value="tls" <?= ($mailSettings['encryption'] ?? 'none') === 'tls' ? 'selected' : '' ?>>TLS</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label">Kullanıcı Adı</label>
                        <input type="text" class="form-control" name="username" value="<?= Helpers::e($mailSettings['username'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Şifre</label>
                        <input type="password" class="form-control" name="password" value="<?= Helpers::e($mailSettings['password'] ?? '') ?>">
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label">Gönderen E-posta</label>
                        <input type="email" class="form-control" name="from_email" value="<?= Helpers::e($mailSettings['from_email'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Gönderen Adı</label>
                        <input type="text" class="form-control" name="from_name" value="<?= Helpers::e($mailSettings['from_name'] ?? Settings::siteName()) ?>">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">Yanıt Adresi</label>
                    <input type="email" class="form-control" name="reply_to_email" value="<?= Helpers::e($mailSettings['reply_to_email'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary mt-3">Kaydet</button>
            </form>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card p-4 h-100">
            <h2 class="h5 mb-3">Firebase Sosyal Giriş</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                <input type="hidden" name="section" value="firebase">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="firebase_enabled" name="firebase_enabled" <?= $firebaseEnabled ? 'checked' : '' ?>>
                    <label class="form-check-label" for="firebase_enabled">Firebase ile sosyal medya girişi aktif</label>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="firebase_config">Firebase Config JSON</label>
                    <textarea class="form-control" name="firebase_config" id="firebase_config" rows="6" placeholder='{"apiKey":"..."}'><?= Helpers::e($firebaseConfig) ?></textarea>
                    <small class="text-white-50">Firebase projenizin web uygulaması yapılandırmasını buraya yapıştırın.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Aktif Sosyal Sağlayıcılar</label>
                    <div class="row g-2">
                        <?php foreach ($providerLabels as $key => $label): ?>
                            <div class="col-sm-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="firebase_providers[]" id="provider_<?= $key ?>" value="<?= $key ?>" <?= in_array($key, $firebaseProviders, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="provider_<?= $key ?>"><?= $label ?></label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </form>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card p-4 h-100">
            <h2 class="h5 mb-3">Google Analytics</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                <input type="hidden" name="section" value="analytics">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="google_analytics_enabled" name="google_analytics_enabled" <?= $gaEnabled ? 'checked' : '' ?>>
                    <label class="form-check-label" for="google_analytics_enabled">Google Analytics aktif</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Measurement ID</label>
                    <input type="text" class="form-control" name="google_analytics_id" value="<?= Helpers::e($gaId ?? '') ?>" placeholder="G-XXXXXXXXXX">
                </div>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </form>
            <p class="small text-white-50 mt-3 mb-0">Analytics kodu tüm sayfalara otomatik olarak eklenecektir.</p>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card p-4 h-100">
            <h2 class="h5 mb-3">OneSignal Web Push</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                <input type="hidden" name="section" value="onesignal">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="onesignal_enabled" name="onesignal_enabled" <?= $onesignalEnabled ? 'checked' : '' ?>>
                    <label class="form-check-label" for="onesignal_enabled">OneSignal entegrasyonunu aktif et</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">App ID</label>
                    <input type="text" class="form-control" name="onesignal_app_id" value="<?= Helpers::e($onesignalAppId ?? '') ?>" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label">REST API Anahtarı</label>
                    <input type="text" class="form-control" name="onesignal_rest_key" value="<?= Helpers::e($onesignalRest ?? '') ?>" autocomplete="off" placeholder="NTk4Z...">
                    <small class="text-white-50">Sunucu anahtarınız yalnızca yönetici panelinde saklanır.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Safari Web ID (Opsiyonel)</label>
                    <input type="text" class="form-control" name="onesignal_safari_web_id" value="<?= Helpers::e($onesignalSafari ?? '') ?>" placeholder="web.onesignal.auto....">
                </div>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </form>
            <p class="small text-white-50 mt-3 mb-0">OneSignal Web SDK v16 ile uyumludur. Ayarları kaydettikten sonra abone listesini eşitleyin.</p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
