<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');
$csrfToken = Helpers::csrfToken();

require __DIR__ . '/header.php';
?>
<h1 class="h3 mb-4">QR Kayıtları</h1>
<div class="card p-4">
    <p class="text-white-50">Panel ve API üzerinden oluşturulan tüm QR kodları burada listelenir. Gerektiğinde indirme yapabilir veya kayıtları silebilirsiniz.</p>
    <div id="adminQrToolbar" class="d-flex flex-wrap justify-content-end justify-content-sm-between align-items-center gap-2 mb-3">
        <button type="button" class="btn btn-outline-light btn-sm" data-refresh-table="#adminQrHistoryTable">Yenile</button>
    </div>
    <div class="table-responsive">
        <table
            id="adminQrHistoryTable"
            class="table table-dark table-hover align-middle"
            data-toggle="table"
            data-url="/admin/data/qr-history"
            data-pagination="true"
            data-page-size="10"
            data-search="true"
            data-toolbar="#adminQrToolbar"
            data-mobile-responsive="true"
            data-card-view="false"
            data-unique-id="id"
            data-locale="tr-TR"
            data-response-handler="window.appHandlers.adminQrHistoryResponseHandler"
            data-csrf="<?= Helpers::e($csrfToken) ?>"
        >
            <thead>
                <tr>
                    <th data-field="created_at" data-sortable="true" data-formatter="window.appHandlers.dateTimeFormatter">Tarih</th>
                    <th data-field="user" data-sortable="true">Üye</th>
                    <th data-field="type_label" data-sortable="true">Tür</th>
                    <th data-field="origin_label" data-sortable="true">Kaynak</th>
                    <th data-field="meta_label">Detay</th>
                    <th data-field="id" data-align="right" data-formatter="window.appHandlers.adminQrActionsFormatter"></th>
                </tr>
            </thead>
        </table>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
