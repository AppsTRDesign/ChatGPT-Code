<?php
require __DIR__ . '/header.php';

use App\Helpers;
use App\Settings;

$pushConfig = [
    'enabled' => Settings::onesignalEnabled(),
    'sendEndpoint' => '/admin/push-send.php',
    'syncEndpoint' => '/admin/onesignal-sync.php',
    'refreshEndpoint' => '/admin/push-refresh.php',
    'targetsEndpoint' => '/admin/data/push-targets.php',
    'campaignEndpoint' => '/admin/data/push-campaigns.php',
    'statsEndpoint' => '/admin/data/push-stats.php',
    'uploadEndpoint' => '/admin/upload-push-media.php',
    'csrf' => Helpers::csrfToken(),
];
?>
<h1 class="h3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <span>Web Push Bildirimleri</span>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-light" id="refreshPushStatsButton">
            <span class="me-2" aria-hidden="true">📊</span>İstatistikleri Yenile
        </button>
        <button type="button" class="btn btn-outline-light" id="syncOnesignalButton">
            <span class="me-2" aria-hidden="true">🔄</span>Aboneleri Eşitle
        </button>
    </div>
</h1>
<?php if (!$pushConfig['enabled']): ?>
    <div class="alert alert-warning bg-opacity-10 border-warning text-warning">
        OneSignal ayarları tamamlanmamış veya devre dışı. Ayarlar &gt; OneSignal bölümünden bilgileri güncelledikten sonra buradan bildirim gönderebilirsiniz.
    </div>
<?php endif; ?>
<div class="row g-4" id="pushManager" data-enabled="<?= $pushConfig['enabled'] ? '1' : '0' ?>">
    <div class="col-12 col-xl-5">
        <div class="card p-4 h-100">
            <h2 class="h5 mb-3">Yeni Bildirim Gönder</h2>
            <form id="pushForm">
                <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                <div class="mb-3">
                    <label class="form-label">Başlık</label>
                    <input type="text" class="form-control" name="title" maxlength="120" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mesaj</label>
                    <textarea class="form-control" name="message" rows="3" maxlength="200" required></textarea>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label">Dil</label>
                        <select class="form-select" name="language">
                            <option value="tr" selected>Türkçe</option>
                            <option value="en">İngilizce</option>
                            <option value="de">Almanca</option>
                            <option value="ar">Arapça</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Hedef</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="target_type" id="targetAll" value="all" checked>
                            <label class="btn btn-outline-light" for="targetAll">Tüm Aboneler</label>
                            <input type="radio" class="btn-check" name="target_type" id="targetSelected" value="players">
                            <label class="btn btn-outline-light" for="targetSelected">Seçili Kişiler</label>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Yönlendirme URL'si (opsiyonel)</label>
                    <input type="url" class="form-control" name="url" placeholder="https://...">
                </div>
                <div class="mb-3">
                    <label class="form-label">Görsel (opsiyonel)</label>
                    <div class="dropzone push-dropzone" id="pushMediaDropzone" data-dropzone-url="<?= $pushConfig['uploadEndpoint'] ?>" data-dropzone-type="push" data-dropzone-input="#push_image" data-dropzone-preview="#pushImagePreview" data-dropzone-csrf="<?= Helpers::csrfToken() ?>"></div>
                    <input type="hidden" name="image_path" id="push_image">
                    <div id="pushImagePreview" class="mt-2 text-white-50 small">Görsel seçilmedi.</div>
                </div>
                <div class="mb-4">
                    <p class="small text-white-50 mb-2">Seçili kişiler seçeneğinde aşağıdaki tablodan aboneleri işaretleyin.</p>
                    <div class="alert alert-info bg-opacity-10 border-info text-info mb-0">
                        Web push bildirimi gönderildiğinde OneSignal istatistikleri otomatik olarak eşitlenecek ve aşağıdaki grafiğe yansıyacaktır.
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100" id="pushSubmitButton">Bildirimi Gönder</button>
            </form>
        </div>
    </div>
    <div class="col-12 col-xl-7">
        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h2 class="h5 mb-0">Kayıtlı Aboneler</h2>
                <span class="badge bg-primary" id="pushTargetTotal">0 kayıt</span>
            </div>
            <div class="table-responsive">
                <table
                    id="pushTargetsTable"
                    class="table table-dark table-striped align-middle"
                    data-toggle="table"
                    data-toolbar="#pushToolbar"
                    data-search="true"
                    data-pagination="true"
                    data-side-pagination="server"
                    data-url="<?= $pushConfig['targetsEndpoint'] ?>"
                    data-id-field="player_id"
                    data-click-to-select="true"
                    data-response-handler="appHandlers.pushTargetResponse"
                    data-query-params="appHandlers.pushTargetQuery"
                >
                    <thead>
                        <tr>
                            <th data-field="state" data-checkbox="true"></th>
                            <th data-field="player_id" data-sortable="true">Player ID</th>
                            <th data-field="external_id" data-sortable="true">Kullanıcı</th>
                            <th data-field="country" data-sortable="true">Ülke</th>
                            <th data-field="platform" data-sortable="true">Platform</th>
                            <th data-field="last_active" data-sortable="true" data-formatter="appHandlers.dateTimeFormatter">Son Aktif</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
        <div class="card p-4">
            <div class="d-flex justify-content-between flex-wrap gap-2 align-items-center mb-3">
                <h2 class="h5 mb-0">Gönderim Geçmişi</h2>
                <span class="badge bg-secondary" id="pushCampaignTotal">0 kampanya</span>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-7">
                    <div class="chart-container chart-container--push">
                        <canvas id="pushStatsChart" height="240"></canvas>
                    </div>
                </div>
                <div class="col-12 col-md-5">
                    <ul class="list-unstyled mb-0" id="pushStatsSummary">
                        <li class="text-white-50">Henüz istatistik yok.</li>
                    </ul>
                </div>
            </div>
            <div class="table-responsive">
                <table
                    id="pushCampaignTable"
                    class="table table-dark table-striped align-middle"
                    data-toggle="table"
                    data-url="<?= $pushConfig['campaignEndpoint'] ?>"
                    data-search="true"
                    data-pagination="true"
                    data-side-pagination="server"
                    data-response-handler="appHandlers.pushCampaignResponse"
                >
                    <thead>
                        <tr>
                            <th data-field="title" data-sortable="true">Başlık</th>
                            <th data-field="created_at" data-sortable="true" data-formatter="appHandlers.dateTimeFormatter">Oluşturulma</th>
                            <th data-field="status" data-formatter="appHandlers.pushStatusFormatter" data-sortable="true">Durum</th>
                            <th data-field="stats" data-formatter="appHandlers.pushStatsFormatter">İstatistik</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<script>window.pushConfig = <?= json_encode($pushConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
<?php require __DIR__ . '/footer.php'; ?>
