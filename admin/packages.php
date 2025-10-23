<?php
require __DIR__ . '/header.php';

use App\Helpers;
use App\PackageManager;

$db = Helpers::db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
        Helpers::flash('message', 'Geçersiz oturum anahtarı.');
        redirect('/admin/packages');
    }

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $limit = (int) ($_POST['monthly_limit'] ?? 0);
    $duration = max(1, (int) ($_POST['duration_days'] ?? 30));
    $price = (float) ($_POST['price'] ?? 0);
    $features = trim($_POST['features'] ?? '');
    if ($name && $limit > 0) {
        PackageManager::create($name, $description, $limit, $duration, $features, $price);
        Helpers::flash('message', 'Paket oluşturuldu.');
    }
    redirect('/admin/packages');
}

if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    PackageManager::toggle($id);
    Helpers::flash('message', 'Paket durumu güncellendi.');
    redirect('/admin/packages');
}

$packages = $db->query('SELECT * FROM packages ORDER BY created_at DESC')->fetchAll();
?>
<h1 class="h3 mb-4">Paketler</h1>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card p-4">
            <h2 class="h5">Yeni Paket Ekle</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                <div class="mb-3">
                    <label class="form-label">Ad</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Açıklama</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Aylık Limit</label>
                    <input type="number" name="monthly_limit" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kullanım Süresi (Gün)</label>
                    <input type="number" name="duration_days" class="form-control" min="1" value="30" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Fiyat (₺)</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Özellikler (Her satır bir özellik)</label>
                    <textarea name="features" class="form-control" rows="4" placeholder="Örn: 500 API isteği"></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">Kaydet</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card p-4">
            <h2 class="h5">Mevcut Paketler</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Ad</th>
                            <th>Limit</th>
                            <th>Süre (gün)</th>
                            <th>Fiyat</th>
                            <th>Durum</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($packages as $package): ?>
                            <tr>
                                <td><?= Helpers::e($package['name']) ?></td>
                                <td><?= Helpers::e($package['monthly_limit']) ?></td>
                                <td><?= Helpers::e($package['duration_days']) ?></td>
                                <td><?= Helpers::e(number_format((float) $package['price'], 2)) ?> ₺</td>
                                <td><?= $package['is_active'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Pasif</span>' ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-light" href="?toggle=<?= Helpers::e($package['id']) ?>">Durumu Değiştir</a>
                                    <a class="btn btn-sm btn-outline-primary" href="/admin/package-edit?id=<?= Helpers::e($package['id']) ?>">Düzenle</a>
                                    <form action="/admin/package-delete" method="post" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                                        <input type="hidden" name="id" value="<?= Helpers::e($package['id']) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Paket silinsin mi?">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
