<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Helpers;
use App\Subscription;

Auth::requireRole('client');
$user = Auth::user();
$csrfToken = Helpers::csrfToken();
$remaining = Subscription::usageLeft((int) $user['id']);

require __DIR__ . '/../templates/header.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card p-4 h-100">
            <h2 class="h4">QR Geçmişi</h2>
            <p class="text-white-50 mb-4">Panel üzerinden oluşturduğunuz tüm QR kodları burada listelenir. İstediğiniz formatta indirip silebilirsiniz.</p>
            <ul class="list-unstyled small text-white-50 mb-0">
                <li class="mb-2"><strong>Toplam Kayıt:</strong> <span id="qrHistoryTotal">-</span></li>
                <li class="mb-2"><strong>Kalan Limit:</strong> <?= Helpers::e($remaining === null ? 'Sınırsız' : $remaining) ?></li>
                <li class="mb-0">Silinen kayıtlar diskten kalıcı olarak kaldırılır.</li>
            </ul>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 mb-0">Oluşturulan QR Kodlar</h2>
                <a href="/client/qr-builder" class="btn btn-primary">Yeni QR Oluştur</a>
            </div>
            <div class="table-responsive">
                <table
                    id="clientQrHistoryTable"
                    class="table table-dark table-hover align-middle"
                    data-toggle="table"
                    data-url="/client/data/qr-history"
                    data-pagination="true"
                    data-page-size="8"
                    data-search="true"
                    data-mobile-responsive="true"
                    data-card-view="false"
                    data-unique-id="id"
                    data-locale="tr-TR"
                    data-response-handler="window.appHandlers.clientQrHistoryResponseHandler"
                    data-csrf="<?= Helpers::e($csrfToken) ?>"
                >
                    <thead>
                        <tr>
                            <th data-field="created_at" data-sortable="true" data-formatter="window.appHandlers.dateTimeFormatter">Tarih</th>
                            <th data-field="type_label" data-sortable="true">Tür</th>
                            <th data-field="origin_label" data-sortable="true">Kaynak</th>
                            <th data-field="preview" data-formatter="window.appHandlers.clientQrPreviewFormatter">Önizleme</th>
                            <th data-field="id" data-align="right" data-formatter="window.appHandlers.clientQrActionsFormatter"></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    window.clientQrHistoryConfig = {
        tableId: 'clientQrHistoryTable',
        totalElementId: 'qrHistoryTotal'
    };
</script>
<?php require __DIR__ . '/../templates/footer.php'; ?>
