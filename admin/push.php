<?php
require __DIR__ . '/header.php';

use App\Helpers;
use App\Notifications;
use App\Settings;

$oneSignalEnabled = Settings::onesignalEnabled();
$csrfToken = Helpers::csrfToken();
$preselectUser = isset($_GET['user']) ? (int) $_GET['user'] : null;

$currentUserId = (int) $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
        Helpers::flash('message', 'Oturum doğrulaması başarısız.');
        redirect('/admin/push');
    }

    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $imagePath = trim($_POST['image_path'] ?? '');
    $audience = $_POST['audience'] ?? 'all';
    $recipientsRaw = trim($_POST['recipients'] ?? '');
    $recipients = array_filter(array_map('intval', $recipientsRaw !== '' ? explode(',', $recipientsRaw) : []));

    if ($title === '' || $message === '') {
        Helpers::flash('message', 'Başlık ve mesaj alanları zorunludur.');
        redirect('/admin/push');
    }

    if ($audience === 'selected' && empty($recipients)) {
        Helpers::flash('message', 'En az bir üye seçmelisiniz.');
        redirect('/admin/push');
    }

    $db = Helpers::db();
    $stmt = $db->prepare('INSERT INTO web_push_campaigns (title, message, target_url, image_path, audience, target_ids, created_by)
        VALUES (:title, :message, :target_url, :image_path, :audience, :target_ids, :created_by)');
    $stmt->execute([
        'title' => $title,
        'message' => $message,
        'target_url' => $link !== '' ? $link : null,
        'image_path' => $imagePath !== '' ? $imagePath : null,
        'audience' => $audience === 'selected' ? 'selected' : 'all',
        'target_ids' => $audience === 'selected' ? implode(',', $recipients) : null,
        'created_by' => $currentUserId,
    ]);

    $messages = ['Bildirim kuyruğa alındı ve web push geçmişine eklendi.'];

    if ($oneSignalEnabled) {
        $playerIds = $audience === 'all' ? Notifications::playerIds() : Notifications::playerIds($recipients);
        if ($playerIds) {
            $result = Notifications::sendPush($playerIds, $title, $message, [
                'url' => $link !== '' ? $link : null,
                'image' => $imagePath !== '' ? $imagePath : null,
            ]);
            if ($result['success']) {
                $messages[] = 'OneSignal üzerinden cihazlara gönderim başlatıldı.';
            } else {
                $messages[] = 'OneSignal gönderimi başarısız: ' . ($result['message'] ?? 'bilinmeyen hata');
            }
        } else {
            $messages[] = 'Seçilen kriterlere uygun OneSignal cihazı bulunamadı.';
        }
    } else {
        $messages[] = 'OneSignal pasif olduğu için yalnızca dahili web push kullanılacaktır.';
    }

    Helpers::flash('message', implode(' ', $messages));
    redirect('/admin/push');
}
?>
<h1 class="h3 mb-4">Web Push Bildirimleri</h1>
<?php if (!$oneSignalEnabled): ?>
    <div class="alert alert-warning">OneSignal ayarları pasif. Bildirimler yalnızca dahili web push merkezi üzerinden gösterilecektir.</div>
<?php endif; ?>
<div class="row g-4">
    <div class="col-xl-6">
        <div class="card p-4 h-100">
            <form method="post" data-push-form data-preselect-user="<?= $preselectUser ? (int) $preselectUser : '' ?>">
                <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                <div class="mb-3">
                    <label class="form-label">Başlık</label>
                    <input type="text" class="form-control" name="title" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mesaj</label>
                    <textarea class="form-control" name="message" rows="4" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Yönlendirme Bağlantısı (opsiyonel)</label>
                    <input type="url" class="form-control" name="link" placeholder="https://...">
                </div>
                <div class="mb-3">
                    <label class="form-label">Hedef</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="audience" id="audience_all" value="all" checked>
                            <label class="form-check-label" for="audience_all">Tüm üyeler</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="audience" id="audience_selected" value="selected">
                            <label class="form-check-label" for="audience_selected">Seçili üyeler</label>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="recipients" value="" data-push-recipients>
                <input type="hidden" name="image_path" value="" data-push-image>
                <div class="mb-4">
                    <label class="form-label">Bildirim Görseli</label>
                    <div class="push-preview" data-push-preview>
                        <span class="text-white-50 small">Henüz bir görsel seçilmedi.</span>
                    </div>
                    <div class="dropzone push-dropzone mt-3"
                         data-dropzone-url="/admin/upload-push-media"
                         data-dropzone-csrf="<?= $csrfToken ?>"
                         data-dropzone-input="[data-push-image]"
                         data-dropzone-preview="[data-push-preview]"
                         data-dropzone-message="Görseli buraya bırakın veya tıklayın"></div>
                </div>
                <button type="submit" class="btn btn-primary">Bildirim Gönder</button>
            </form>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card p-4 h-100">
            <h2 class="h5 mb-3">Hedef Üyeler</h2>
            <p class="text-white-50 small">Sadece seçili üyelere göndermek için aşağıdaki listeden kullanıcıları işaretleyin.</p>
            <div class="table-responsive">
                <table
                    id="pushRecipientsTable"
                    class="table table-dark table-hover align-middle"
                    data-toggle="table"
                    data-url="/admin/data/push-targets"
                    data-search="true"
                    data-pagination="true"
                    data-page-list="[10,25,50]"
                    data-unique-id="id"
                    data-id-field="id"
                    data-click-to-select="true"
                    data-maintain-selected="true"
                    data-response-handler="window.appHandlers.pushRecipientsHandler"
                    data-checkbox-header="false"
                    data-mobile-responsive="true"
                    data-toolbar-align="left"
                    data-buttons-align="right"
                >
                    <thead>
                    <tr>
                        <th data-field="state" data-checkbox="true"></th>
                        <th data-field="username" data-sortable="true">Kullanıcı</th>
                        <th data-field="email" data-sortable="true">E-posta</th>
                        <th data-field="players" data-sortable="true">Cihaz</th>
                        <th data-field="last_active" data-sortable="true" data-formatter="window.appHandlers.dateTimeFormatter">Son Aktif</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="row g-4 mt-1">
    <div class="col-xl-4">
        <div class="card p-4 h-100">
            <h2 class="h5 mb-3">Gönderim Özeti</h2>
            <ul class="list-unstyled mb-0" id="webPushSummary">
                <li class="text-white-50">Veriler yükleniyor...</li>
            </ul>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card p-4 h-100">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                <h2 class="h5 mb-0">Performans Grafiği</h2>
                <div class="d-flex align-items-center gap-2">
                    <label for="webPushRange" class="form-label mb-0 me-2">Aralık</label>
                    <select class="form-select form-select-sm w-auto" id="webPushRange">
                        <option value="daily">Son 24 Saat</option>
                        <option value="weekly">Son 7 Gün</option>
                        <option value="monthly">Son 30 Gün</option>
                        <option value="yearly">Son 12 Ay</option>
                    </select>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-light" data-web-push-export="pdf">PDF</button>
                        <button type="button" class="btn btn-outline-light" data-web-push-export="excel">Excel</button>
                    </div>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="webPushChart" height="180"></canvas>
            </div>
        </div>
    </div>
</div>
<div class="card p-4 mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
        <h2 class="h5 mb-0">Gönderim Geçmişi</h2>
        <span class="text-white-50 small">Toplam gönderim, görüntülenme ve tıklama adetleri</span>
    </div>
    <div class="table-responsive">
        <table
            id="webPushTable"
            class="table table-dark table-hover align-middle"
            data-toggle="table"
            data-url="/admin/data/web-push-campaigns"
            data-search="true"
            data-pagination="true"
            data-page-list="[10,25,50]"
            data-side-pagination="server"
            data-response-handler="window.appHandlers.webPushHandler"
            data-sort-name="sent_at"
            data-sort-order="desc"
            data-mobile-responsive="true"
            data-locale="tr-TR"
            data-csrf="<?= $csrfToken ?>"
        >
            <thead>
            <tr>
                <th data-field="title" data-sortable="true">Başlık</th>
                <th data-field="audience" data-formatter="window.appHandlers.audienceFormatter">Hedef</th>
                <th data-field="delivered" data-sortable="true">Gönderildi</th>
                <th data-field="viewed" data-sortable="true">Görüntülendi</th>
                <th data-field="clicked" data-sortable="true">Tıklandı</th>
                <th data-field="sent_at" data-sortable="true" data-formatter="window.appHandlers.dateTimeFormatter">Gönderim Tarihi</th>
            </tr>
            </thead>
        </table>
    </div>
</div>
<div class="card p-4 mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <label for="webPushEventFilter" class="form-label mb-0">Kampanya</label>
            <select class="form-select form-select-sm w-auto" id="webPushEventFilter">
                <option value="">Tümü</option>
            </select>
        </div>
        <span class="text-white-50 small">Kaynak, platform, konum ve arama detayları</span>
    </div>
    <div class="table-responsive">
        <table
            id="webPushEventsTable"
            class="table table-dark table-hover align-middle"
            data-toggle="table"
            data-url="/admin/data/web-push-events"
            data-search="true"
            data-pagination="true"
            data-page-list="[10,25,50]"
            data-side-pagination="server"
            data-response-handler="window.appHandlers.webPushEventsHandler"
            data-query-params="window.appHandlers.webPushEventsQuery"
            data-sort-name="created_at"
            data-sort-order="desc"
            data-mobile-responsive="true"
            data-locale="tr-TR"
            data-csrf="<?= $csrfToken ?>"
        >
            <thead>
            <tr>
                <th data-field="title">Kampanya</th>
                <th data-field="event_type" data-formatter="window.appHandlers.pushEventFormatter">Durum</th>
                <th data-field="platform">Platform</th>
                <th data-field="country">Ülke</th>
                <th data-field="city">Şehir</th>
                <th data-field="ip">IP</th>
                <th data-field="referer" data-formatter="window.appHandlers.refererFormatter">Kaynak</th>
                <th data-field="search" data-formatter="window.appHandlers.searchFormatter">Arama</th>
                <th data-field="created_at" data-sortable="true" data-formatter="window.appHandlers.dateTimeFormatter">Zaman</th>
            </tr>
            </thead>
        </table>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
