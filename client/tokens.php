<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';

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

    $label = trim($_POST['label'] ?? 'API Token');
    $token = TokenManager::create((int) $user['id'], $label);
    Helpers::flash('message', 'Yeni token oluşturuldu: ' . $token);
    redirect('/client/tokens');
}

if (isset($_GET['revoke'])) {
    TokenManager::revoke((int) $_GET['revoke'], (int) $user['id']);
    Helpers::flash('message', 'Token pasif hale getirildi.');
    redirect('/client/tokens');
}

$tokens = TokenManager::list((int) $user['id']);

require __DIR__ . '/../templates/header.php';
?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card p-4">
            <h2 class="h4">Yeni Token Oluştur</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
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
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Etiket</th>
                            <th>Token</th>
                            <th>Durum</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tokens as $token): ?>
                            <tr>
                                <td><?= Helpers::e($token['label']) ?></td>
                                <td><small class="text-white-50"><?= Helpers::e($token['token']) ?></small></td>
                                <td><?= $token['revoked_at'] ? '<span class="badge bg-danger">Pasif</span>' : '<span class="badge bg-success">Aktif</span>' ?></td>
                                <td>
                                    <?php if (!$token['revoked_at']): ?>
                                        <a href="?revoke=<?= Helpers::e($token['id']) ?>" class="btn btn-sm btn-outline-light" data-confirm="Token pasif edilsin mi?">Pasif Et</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
