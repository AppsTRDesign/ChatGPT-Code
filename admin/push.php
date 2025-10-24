<?php
require __DIR__ . '/header.php';

use App\Helpers;
use App\Notifications;
use App\Settings;

$oneSignalEnabled = Settings::onesignalEnabled();
$csrfToken = Helpers::csrfToken();
$preselectUser = isset($_GET['user']) ? (int) $_GET['user'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
        Helpers::flash('message', 'Oturum doğrulaması başarısız.');
        redirect('/admin/push');
    }

    if (!$oneSignalEnabled) {
        Helpers::flash('message', 'OneSignal entegrasyonu aktif değil.');
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

    $playerIds = $audience === 'all' ? Notifications::playerIds() : Notifications::playerIds($recipients);
    if (!$playerIds) {
        Helpers::flash('message', 'Seçilen kriterlere uygun cihaz bulunamadı.');
        redirect('/admin/push');
    }

    $result = Notifications::sendPush($playerIds, $title, $message, [
        'url' => $link !== '' ? $link : null,
        'image' => $imagePath !== '' ? $imagePath : null,
    ]);

    if ($result['success']) {
        Helpers::flash('message', 'Push bildirimi gönderildi.');
    } else {
        Helpers::flash('message', 'Bildirim gönderilemedi: ' . ($result['message'] ?? 'Bilinmeyen hata'));
    }

    redirect('/admin/push');
}
?>
<h1 class="h3 mb-4">Push Bildirimleri</h1>
<?php if (!$oneSignalEnabled): ?>
    <div class="alert alert-warning">OneSignal ayarları pasif. Bildirim göndermeden önce Ayarlar &gt; OneSignal bölümünden gerekli bilgileri doldurun.</div>
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
                <button type="submit" class="btn btn-primary" <?= $oneSignalEnabled ? '' : 'disabled' ?>>Bildirim Gönder</button>
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
<?php require __DIR__ . '/footer.php'; ?>
