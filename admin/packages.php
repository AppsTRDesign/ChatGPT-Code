<?php
require __DIR__ . '/header.php';

use App\Helpers;
use App\PackageManager;

$db = Helpers::db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $limit = (int) ($_POST['monthly_limit'] ?? 0);
    $price = (float) ($_POST['price'] ?? 0);
    if ($name && $limit > 0) {
        PackageManager::create($name, $description, $limit, $price);
        Helpers::flash('message', 'Paket oluşturuldu.');
    }
    redirect('/admin/packages.php');
}

if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $db->prepare('UPDATE packages SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id')->execute(['id' => $id]);
    Helpers::flash('message', 'Paket durumu güncellendi.');
    redirect('/admin/packages.php');
}

$packages = $db->query('SELECT * FROM packages ORDER BY created_at DESC')->fetchAll();
?>
<h1 class="h3 mb-4">Paketler</h1>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card p-4">
            <h2 class="h5">Yeni Paket Ekle</h2>
            <form method="post">
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
                    <label class="form-label">Fiyat (₺)</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
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
                                <td><?= Helpers::e(number_format((float) $package['price'], 2)) ?> ₺</td>
                                <td><?= $package['is_active'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Pasif</span>' ?></td>
                                <td><a class="btn btn-sm btn-outline-light" href="?toggle=<?= Helpers::e($package['id']) ?>">Durumu Değiştir</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
