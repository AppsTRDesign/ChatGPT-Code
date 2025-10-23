<?php
require __DIR__ . '/header.php';

use App\Helpers;
use App\PackageManager;

$id = (int) ($_GET['id'] ?? 0);
$package = PackageManager::find($id);

if (!$package) {
    Helpers::flash('message', 'Paket bulunamadı.');
    redirect('/admin/packages');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
        Helpers::flash('message', 'Geçersiz oturum anahtarı.');
        redirect('/admin/package-edit?id=' . $id);
    }

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $monthlyLimit = max(0, (int) ($_POST['monthly_limit'] ?? 0));
    $duration = max(1, (int) ($_POST['duration_days'] ?? 30));
    $price = (float) ($_POST['price'] ?? 0);
    $features = trim($_POST['features'] ?? '');
    $isActive = isset($_POST['is_active']) && (int) $_POST['is_active'] === 1;

    if ($name && $monthlyLimit > 0) {
        PackageManager::update($id, $name, $description, $monthlyLimit, $duration, $features, $price, $isActive);
        Helpers::flash('message', 'Paket güncellendi.');
        redirect('/admin/packages');
    }

    Helpers::flash('message', 'Lütfen tüm alanları eksiksiz doldurun.');
    redirect('/admin/package-edit?id=' . $id);
}
?>
<h1 class="h3 mb-4">Paketi Düzenle</h1>
<div class="card p-4">
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
        <div class="mb-3">
            <label class="form-label">Paket Adı</label>
            <input type="text" name="name" class="form-control" value="<?= Helpers::e($package['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Açıklama</label>
            <textarea name="description" class="form-control" rows="3"><?= Helpers::e($package['description'] ?? '') ?></textarea>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Limit</label>
                <input type="number" name="monthly_limit" class="form-control" min="1" value="<?= Helpers::e($package['monthly_limit']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Süre (Gün)</label>
                <input type="number" name="duration_days" class="form-control" min="1" value="<?= Helpers::e($package['duration_days']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Fiyat (₺)</label>
                <input type="number" step="0.01" name="price" class="form-control" value="<?= Helpers::e(number_format((float) $package['price'], 2, '.', '')) ?>" required>
            </div>
        </div>
        <div class="mb-3 mt-3">
            <label class="form-label">Özellikler (Her satır bir özellik)</label>
            <textarea name="features" class="form-control" rows="5" placeholder="Örn: 500 API isteği"><?= Helpers::e($package['features'] ?? '') ?></textarea>
        </div>
        <div class="form-check form-switch mb-4">
            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" <?= $package['is_active'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_active">Paket aktif</label>
        </div>
        <div class="d-flex gap-3">
            <button type="submit" class="btn btn-primary">Güncelle</button>
            <a href="/admin/packages" class="btn btn-outline-light">İptal</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/footer.php'; ?>
