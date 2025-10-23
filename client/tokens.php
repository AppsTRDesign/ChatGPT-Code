<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Helpers;
use App\TokenManager;

Auth::requireRole('client');
$user = Auth::user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
        Helpers::flash('message', 'Geçersiz oturum anahtarı.');
        redirect('/client/tokens');
    }

    $action = $_POST['action'] ?? 'create';
    $tokenId = (int) ($_POST['token_id'] ?? 0);

    switch ($action) {
        case 'revoke':
            TokenManager::revoke($tokenId, (int) $user['id']);
            Helpers::flash('message', 'Token pasif hale getirildi.');
            break;
        case 'restore':
            TokenManager::restore($tokenId, (int) $user['id']);
            Helpers::flash('message', 'Token tekrar aktifleştirildi.');
            break;
        case 'delete':
            TokenManager::delete($tokenId, (int) $user['id']);
            Helpers::flash('message', 'Token tamamen silindi.');
            break;
        default:
            $label = trim($_POST['label'] ?? 'API Token');
            $token = TokenManager::create((int) $user['id'], $label);
            Helpers::flash('message', 'Yeni token oluşturuldu: ' . $token);
            break;
    }

    redirect('/client/tokens');
}

$csrfToken = Helpers::csrfToken();

require __DIR__ . '/../templates/header.php';
?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card p-4">
            <h2 class="h4">Yeni Token Oluştur</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Helpers::e($csrfToken) ?>">
                <input type="hidden" name="action" value="create">
                <div class="mb-3">
                    <label class="form-label">Token Etiketi</label>
                    <input type="text" class="form-control" name="label" placeholder="Örn: Mobil Uygulama" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Oluştur</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card p-4">
            <h2 class="h4">Tokenlarım</h2>
            <table
                id="tokensTable"
                class="table table-dark table-hover"
                data-toggle="table"
                data-url="/client/data/tokens"
                data-pagination="true"
                data-page-size="6"
                data-search="false"
                data-mobile-responsive="true"
                data-card-view="true"
                data-unique-id="id"
                data-locale="tr-TR"
                data-response-handler="window.appHandlers.clientTokenResponseHandler"
                data-csrf="<?= Helpers::e($csrfToken) ?>"
            >
                <thead>
                    <tr>
                        <th data-field="label" data-sortable="true">Etiket</th>
                        <th data-field="token" data-formatter="window.appHandlers.clientTokenValueFormatter">Token</th>
                        <th data-field="status" data-formatter="window.appHandlers.clientTokenStatusFormatter" data-sortable="true">Durum</th>
                        <th data-field="created_at" data-formatter="window.appHandlers.clientTokenDateFormatter" data-sortable="true">Oluşturulma</th>
                        <th data-field="id" data-formatter="window.appHandlers.clientTokenActionsFormatter" data-align="right"></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
