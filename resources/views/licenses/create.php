<?php
$title = 'Lisans Oluştur';
ob_start();
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h4 mb-4">Yeni Lisans Oluştur</h1>
                <form method="post" action="/licenses/create">
                    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mb-3">
                        <label class="form-label">Ürün Kodu</label>
                        <input type="text" name="product_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lisans Tipi</label>
                        <select name="type" class="form-select">
                            <option value="perpetual">Süresiz</option>
                            <option value="subscription">Abonelik</option>
                            <option value="trial">Deneme</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Koltuk Sayısı</label>
                        <input type="number" name="seats" class="form-control" min="1" value="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bitiş Tarihi</label>
                        <input type="date" name="expires_at" class="form-control">
                        <small class="text-muted">Süresiz lisans için boş bırakın.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Grace Gün Sayısı</label>
                        <input type="number" name="grace_days" class="form-control" value="3" min="0">
                    </div>
                    <button class="btn btn-success" type="submit">Lisans Oluştur</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
