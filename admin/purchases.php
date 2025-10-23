<?php
require __DIR__ . '/header.php';

use App\Helpers;
use App\Subscription;

$db = Helpers::db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
        respond('Geçersiz oturum anahtarı.', false);
    }

    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($id <= 0) {
        respond('Satın alma kaydı bulunamadı.', false);
    }

    $message = 'Geçersiz işlem.';
    $success = false;

    switch ($action) {
        case 'activate':
            $success = Subscription::activate($id);
            $message = $success ? 'Paket başarıyla aktifleştirildi.' : 'Paket aktifleştirilemedi.';
            break;
        case 'awaiting':
            $success = Subscription::updateStatus($id, 'awaiting_payment');
            $message = $success ? 'Durum ödeme bekliyor olarak güncellendi.' : $message;
            break;
        case 'missing':
            $success = Subscription::updateStatus($id, 'payment_missing');
            $message = $success ? 'Durum eksik ödeme olarak işaretlendi.' : $message;
            break;
        case 'reject':
            $success = Subscription::updateStatus($id, 'rejected');
            $message = $success ? 'Paket talebi reddedildi.' : $message;
            break;
        case 'cancel':
            $success = Subscription::updateStatus($id, 'cancelled');
            $message = $success ? 'Paket talebi iptal edildi.' : $message;
            break;
    }

    respond($message, $success);
}

$csrfToken = Helpers::csrfToken();
?>
<h1 class="h3 mb-4">Satın Alımlar</h1>
<div class="card p-4">
    <table
        id="purchasesTable"
        class="table table-dark table-hover"
        data-toggle="table"
        data-url="/admin/data/purchases"
        data-search="true"
        data-pagination="true"
        data-page-list="[10, 25, 50]"
        data-unique-id="id"
        data-response-handler="window.appHandlers.purchaseResponseHandler"
        data-csrf="<?= Helpers::e($csrfToken) ?>"
        data-mobile-responsive="true"
        data-locale="tr-TR"
    >
        <thead>
            <tr>
                <th data-field="username" data-sortable="true">Kullanıcı</th>
                <th data-field="package_name" data-sortable="true">Paket</th>
                <th data-field="payment_method" data-formatter="window.appHandlers.paymentFormatter" data-sortable="true">Ödeme</th>
                <th data-field="status" data-formatter="window.appHandlers.purchaseStatusFormatter" data-sortable="true">Durum</th>
                <th data-field="note" data-formatter="window.appHandlers.noteFormatter">Not</th>
                <th data-field="created_at" data-formatter="window.appHandlers.purchaseDateFormatter" data-sortable="true">Tarih</th>
                <th data-field="id" data-formatter="window.appHandlers.purchaseActionsFormatter" data-align="right">İşlemler</th>
            </tr>
        </thead>
    </table>
</div>
<?php require __DIR__ . '/footer.php'; ?>

<?php
function respond(string $message, bool $success): void
{
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $acceptsJson = str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

    if ($isAjax || $acceptsJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => $success ? 'success' : 'error',
            'message' => $message,
        ]);
        exit;
    }

    Helpers::flash('message', $message);
    redirect('/admin/purchases');
}
