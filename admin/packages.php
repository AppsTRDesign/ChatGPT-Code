<?php
require __DIR__ . '/header.php';

use App\Helpers;
use App\PackageManager;

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
$csrfToken = Helpers::csrfToken();
?>
<h1 class="h3 mb-4">Paketler</h1>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card p-4">
            <h2 class="h5">Yeni Paket Ekle</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Helpers::e($csrfToken) ?>">
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
                <table
                    id="packagesTable"
                    class="table table-dark table-hover align-middle"
                    data-toggle="table"
                    data-url="/admin/data/packages"
                    data-side-pagination="server"
                    data-search="true"
                    data-pagination="true"
                    data-page-list="[10, 25, 50]"
                    data-unique-id="id"
                    data-mobile-responsive="true"
                    data-card-view="false"
                    data-response-handler="window.appHandlers.packageResponseHandler"
                    data-locale="tr-TR"
                    data-csrf="<?= Helpers::e($csrfToken) ?>"
                >
                    <thead>
                        <tr>
                            <th data-field="name" data-sortable="true">Ad</th>
                            <th data-field="monthly_limit" data-align="right" data-sortable="true">Aylık Limit</th>
                            <th data-field="duration_days" data-align="right" data-sortable="true">Süre (gün)</th>
                            <th data-field="price" data-align="right" data-formatter="window.appHandlers.packagePriceFormatter" data-sortable="true">Fiyat</th>
                            <th data-field="features" data-formatter="window.appHandlers.packageFeaturesFormatter">Özellikler</th>
                            <th data-field="is_active" data-formatter="window.appHandlers.packageStatusFormatter" data-sortable="true">Durum</th>
                            <th data-field="id" data-formatter="window.appHandlers.packageActionsFormatter" data-align="right">İşlemler</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
