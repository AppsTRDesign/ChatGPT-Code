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
        respond('Ödeme kaydı bulunamadı.', false);
    }

    $message = 'İşlem tamamlanamadı.';
    $success = false;

    if ($action === 'approve') {
        $stmt = $db->prepare('UPDATE payment_notifications SET status = "approved", reviewed_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $stmt = $db->prepare('SELECT user_package_id FROM payment_notifications WHERE id = :id');
        $stmt->execute(['id' => $id]);
        if ($packageId = $stmt->fetchColumn()) {
            Subscription::activate((int) $packageId);
        }
        $message = 'Ödeme onaylandı ve paket aktifleştirildi.';
        $success = true;
    } elseif ($action === 'reject') {
        $db->prepare('UPDATE payment_notifications SET status = "rejected", reviewed_at = NOW() WHERE id = :id')->execute(['id' => $id]);
        $message = 'Ödeme reddedildi.';
        $success = true;
    }

    respond($message, $success);
}

$csrfToken = Helpers::csrfToken();
?>
<h1 class="h3 mb-4">Ödeme Bildirimleri</h1>
<div class="card p-4">
    <div class="table-responsive">
        <table
            id="paymentsTable"
            class="table table-dark table-hover align-middle"
            data-toggle="table"
            data-url="/admin/data/payments"
            data-search="true"
            data-pagination="true"
            data-page-list="[10,25,50]"
            data-unique-id="id"
            data-csrf="<?= Helpers::e($csrfToken) ?>"
            data-response-handler="window.appHandlers.paymentsResponseHandler"
            data-mobile-responsive="true"
            data-card-view="false"
            data-locale="tr-TR"
        >
            <thead>
                <tr>
                    <th data-field="username" data-sortable="true">Kullanıcı</th>
                    <th data-field="package_name" data-sortable="true">Paket</th>
                    <th data-field="amount" data-sortable="true">Tutar</th>
                    <th data-field="note" data-formatter="window.appHandlers.noteFormatter">Not</th>
                    <th data-field="status" data-formatter="window.appHandlers.paymentStatusFormatter" data-sortable="true">Durum</th>
                    <th data-field="id" data-formatter="window.appHandlers.paymentActionsFormatter" data-align="right">İşlemler</th>
                </tr>
            </thead>
        </table>
    </div>
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
    redirect('/admin/payments');
}
