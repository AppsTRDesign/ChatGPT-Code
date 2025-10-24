<?php
require __DIR__ . '/header.php';

use App\Helpers;
use App\NotificationService;

$csrf = Helpers::csrfToken();
$languages = [
    'tr' => 'Türkçe',
    'en' => 'İngilizce',
    'de' => 'Almanca',
    'fr' => 'Fransızca',
    'es' => 'İspanyolca',
    'ru' => 'Rusça',
    'ar' => 'Arapça',
];
$platforms = [
    'desktop' => 'Masaüstü',
    'mobile' => 'Mobil',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
        Helpers::flash('message', 'Oturum doğrulaması başarısız.');
        redirect('/admin/web-notifications');
    }

    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $imagePath = trim($_POST['image_path'] ?? '');
    $selectedLanguages = $_POST['languages'] ?? [];
    $selectedPlatforms = $_POST['platforms'] ?? [];

    $selectedLanguages = is_array($selectedLanguages) ? array_values(array_intersect(array_keys($languages), array_map('strtolower', $selectedLanguages))) : [];
    $selectedPlatforms = is_array($selectedPlatforms) ? array_values(array_intersect(array_keys($platforms), array_map('strtolower', $selectedPlatforms))) : [];

    if (!$title || !$message) {
        Helpers::flash('message', 'Başlık ve mesaj zorunludur.');
        redirect('/admin/web-notifications');
    }

    $url = $url !== '' ? $url : null;
    $imagePath = $imagePath !== '' ? $imagePath : null;

    NotificationService::create((int) $user['id'], $title, $message, $selectedLanguages, $selectedPlatforms, $url, $imagePath);
    Helpers::flash('message', 'Bildirim oluşturuldu ve aktif oturumlara gönderildi.');
    redirect('/admin/web-notifications');
}
?>
<h1 class="h3 mb-4">Web Bildirimleri</h1>
<div class="row g-4 align-items-stretch">
    <div class="col-12 col-xl-5">
        <div class="card p-4 h-100 d-flex flex-column">
            <h2 class="h5 mb-3">Yeni Bildirim Gönder</h2>
            <form method="post" class="row g-3 flex-grow-1">
                <input type="hidden" name="csrf_token" value="<?= Helpers::e($csrf) ?>">
                <input type="hidden" name="image_path" id="notificationImage" value="">
                <div class="col-12">
                    <label class="form-label" for="notificationTitle">Başlık</label>
                    <input type="text" class="form-control" id="notificationTitle" name="title" maxlength="150" required>
                </div>
                <div class="col-12">
                    <label class="form-label" for="notificationMessage">Mesaj</label>
                    <textarea class="form-control" id="notificationMessage" name="message" rows="4" required placeholder="Kısa ve etkileyici bir bildirim metni yazın."></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="notificationUrl">Bağlantı (Opsiyonel)</label>
                    <input type="url" class="form-control" id="notificationUrl" name="url" placeholder="https://...">
                    <small class="text-white-50">Boş bırakılırsa bildirim bilgi amaçlı gösterilir.</small>
                </div>
                <div class="col-12">
                    <label class="form-label">Dil Seçimi (Opsiyonel)</label>
                    <div class="row g-2">
                        <?php foreach ($languages as $code => $label): ?>
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="languages[]" value="<?= Helpers::e($code) ?>" id="lang-<?= Helpers::e($code) ?>">
                                    <label class="form-check-label" for="lang-<?= Helpers::e($code) ?>"><?= Helpers::e($label) ?></label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="text-white-50">Dil seçilmezse tüm diller hedeflenir.</small>
                </div>
                <div class="col-12">
                    <label class="form-label">Platform (Opsiyonel)</label>
                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach ($platforms as $key => $label): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="platforms[]" value="<?= Helpers::e($key) ?>" id="platform-<?= Helpers::e($key) ?>">
                                <label class="form-check-label" for="platform-<?= Helpers::e($key) ?>"><?= Helpers::e($label) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="text-white-50">Platform seçilmezse masaüstü ve mobil kullanıcıların tamamı hedeflenir.</small>
                </div>
                <div class="col-12">
                    <label class="form-label">Görsel (Opsiyonel)</label>
                    <div class="dropzone notification-dropzone" data-dropzone-url="/admin/upload-notification.php" data-dropzone-type="notification" data-dropzone-message="Görseli sürükleyin veya tıklayın" data-dropzone-input="#notificationImage" data-dropzone-preview="#notificationPreview" data-dropzone-csrf="<?= Helpers::e($csrf) ?>"></div>
                    <div id="notificationPreview" class="mt-3 small text-white-50"></div>
                </div>
                <div class="col-12 mt-auto">
                    <button type="submit" class="btn btn-primary w-100">Bildirimi Gönder</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-12 col-xl-7">
        <div class="card p-4 h-100 d-flex flex-column">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Bildirim İstatistikleri</h2>
                    <p class="text-white-50 mb-0">Gösterim, tıklama ve kapatma eğilimlerini inceleyin.</p>
                </div>
                <div class="chart-toolbar">
                    <select class="form-select form-select-sm w-auto" id="notificationRange">
                        <option value="daily">Son 24 Saat</option>
                        <option value="weekly" selected>Son 7 Gün</option>
                        <option value="monthly">Son 30 Gün</option>
                        <option value="yearly">Son 12 Ay</option>
                    </select>
                    <select class="form-select form-select-sm w-auto" id="notificationFilter">
                        <option value="">Tüm Bildirimler</option>
                    </select>
                    <button class="btn btn-outline-light btn-sm" type="button" data-notification-export="pdf">PDF</button>
                    <button class="btn btn-outline-light btn-sm" type="button" data-notification-export="excel">Excel</button>
                </div>
            </div>
            <div class="chart-wrapper flex-grow-1">
                <canvas id="notificationChart" height="220"></canvas>
            </div>
            <ul class="list-unstyled mt-4 mb-0" id="notificationSummary">
                <li class="text-white-50">Veri yükleniyor...</li>
            </ul>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-12">
        <div class="card p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Gönderim Geçmişi</h2>
                    <p class="text-white-50 mb-0">Planlanan tüm bildirimleri, hedeflerini ve performansını inceleyin.</p>
                </div>
                <button class="btn btn-sm btn-outline-light" type="button" data-refresh-table="#webNotificationsTable">Yenile</button>
            </div>
            <div class="table-responsive">
                <table
                    id="webNotificationsTable"
                    class="table table-dark table-hover align-middle"
                    data-toggle="table"
                    data-url="/admin/data/web-notifications"
                    data-search="true"
                    data-pagination="true"
                    data-page-list="[10,25,50]"
                    data-side-pagination="server"
                    data-response-handler="window.appHandlers.webNotificationResponse"
                    data-unique-id="id"
                    data-mobile-responsive="true"
                    data-card-view="false"
                    data-locale="tr-TR"
                    data-csrf="<?= Helpers::e($csrf) ?>"
                >
                    <thead>
                        <tr>
                            <th data-field="title" data-sortable="true">Başlık</th>
                            <th data-field="targets" data-formatter="window.appHandlers.webNotificationTarget">Hedef</th>
                            <th data-field="delivered" data-align="right" data-sortable="true">Gösterildi</th>
                            <th data-field="clicked" data-align="right" data-sortable="true">Tıklandı</th>
                            <th data-field="dismissed" data-align="right" data-sortable="true">Kapatıldı</th>
                            <th data-field="created_at" data-sortable="true" data-formatter="window.appHandlers.dateTimeFormatter">Oluşturma</th>
                        </tr>
                    </thead>
                </table>
            </div>
            <hr class="border-secondary my-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                <div>
                    <h3 class="h6 mb-1">Detaylı İstatistikler</h3>
                    <p class="text-white-50 mb-0 small">Ülke, şehir, platform ve dil bazında performans dağılımı.</p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <select class="form-select form-select-sm w-auto" id="notificationBreakdownRange">
                        <option value="daily">Son 24 Saat</option>
                        <option value="weekly" selected>Son 7 Gün</option>
                        <option value="monthly">Son 30 Gün</option>
                        <option value="yearly">Son 12 Ay</option>
                    </select>
                    <button class="btn btn-sm btn-outline-light" type="button" data-notification-breakdown-export="pdf">PDF</button>
                    <button class="btn btn-sm btn-outline-light" type="button" data-notification-breakdown-export="excel">Excel</button>
                </div>
            </div>
            <ul class="list-unstyled small text-white mb-3" id="notificationBreakdownSummary">
                <li class="text-white-50">Veri yükleniyor...</li>
            </ul>
            <div class="table-responsive">
                <table
                    id="notificationBreakdownTable"
                    class="table table-dark table-hover align-middle"
                    data-toggle="table"
                    data-url="/admin/data/web-notification-breakdown.php"
                    data-search="true"
                    data-pagination="true"
                    data-page-list="[10,25,50]"
                    data-side-pagination="server"
                    data-query-params="window.appHandlers.notificationBreakdownParams"
                    data-response-handler="window.appHandlers.notificationBreakdownResponse"
                    data-sort-name="clicked"
                    data-sort-order="desc"
                    data-mobile-responsive="true"
                    data-card-view="false"
                    data-locale="tr-TR"
                >
                    <thead>
                        <tr>
                            <th data-field="country" data-sortable="true">Ülke</th>
                            <th data-field="city" data-sortable="true">Şehir</th>
                            <th data-field="language" data-sortable="true">Dil</th>
                            <th data-field="platform" data-sortable="true">Platform</th>
                            <th data-field="delivered" data-align="right" data-sortable="true" data-formatter="window.appHandlers.numberFormatter">Gösterim</th>
                            <th data-field="clicked" data-align="right" data-sortable="true" data-formatter="window.appHandlers.numberFormatter">Tıklama</th>
                            <th data-field="dismissed" data-align="right" data-sortable="true" data-formatter="window.appHandlers.numberFormatter">Kapatma</th>
                            <th data-field="total" data-align="right" data-sortable="true" data-formatter="window.appHandlers.numberFormatter">Toplam</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
