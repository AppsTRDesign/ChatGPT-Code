<?php include __DIR__ . '/../layout/header.php'; ?>
<?php
    $featureOptions = [
        'template' => 'Şablon Kullanımı',
        'language' => 'Dil Hedefleme',
        'platform' => 'Platform Seçimi',
        'geo' => 'Ülke / Şehir Hedefleme',
        'segment' => 'Segment ve Etiket',
        'schedule' => 'Zamanlama'
    ];
?>
<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Yeni Paket Oluştur</h2>
            </div>
            <div class="card-body">
                <form data-ajax="true" data-endpoint="/admin/packages/store" data-refresh="#admin-packages-table" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label">Paket Adı</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kısa Ad (Slug)</label>
                        <input type="text" name="slug" class="form-control" placeholder="premium-plan">
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Aylık Limit</label>
                            <input type="number" name="monthly_limit" class="form-control" placeholder="Sınırsız">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Kullanım Süresi (Gün)</label>
                            <input type="number" name="duration_days" class="form-control" placeholder="30">
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-6">
                            <label class="form-label">Site Limiti</label>
                            <input type="number" name="site_limit" class="form-control" placeholder="1">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Fiyat</label>
                            <input type="number" step="0.01" name="price" class="form-control" placeholder="0">
                        </div>
                    </div>
                    <div class="mb-3 mt-1">
                        <label class="form-label">Para Birimi</label>
                        <input type="text" name="currency" class="form-control" value="TRY">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Paket Açıklaması</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Paket açıklamasını girin"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Öne Çıkan Özellikler</label>
                        <textarea name="features" class="form-control" rows="3" placeholder="Her satıra bir özellik yazın"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-block">İzin Verilen Özellikler</label>
                        <?php foreach ($featureOptions as $value => $label): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="allowed_features[]" value="<?= $value ?>" id="create-feature-<?= $value ?>">
                                <label class="form-check-label" for="create-feature-<?= $value ?>"><?= $label ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Durum</label>
                        <select name="status" class="form-select">
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Paketi Kaydet</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Paket Düzenle</h2>
            </div>
            <div class="card-body">
                <form id="package-update-form" data-ajax="true" data-endpoint="/admin/packages/update" data-refresh="#admin-packages-table" autocomplete="off">
                    <input type="hidden" name="id">
                    <div class="mb-3">
                        <label class="form-label">Paket Adı</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control">
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Aylık Limit</label>
                            <input type="number" name="monthly_limit" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Kullanım Süresi (Gün)</label>
                            <input type="number" name="duration_days" class="form-control">
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-6">
                            <label class="form-label">Site Limiti</label>
                            <input type="number" name="site_limit" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Fiyat</label>
                            <input type="number" step="0.01" name="price" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3 mt-1">
                        <label class="form-label">Para Birimi</label>
                        <input type="text" name="currency" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Öne Çıkan Özellikler</label>
                        <textarea name="features" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-block">İzin Verilen Özellikler</label>
                        <?php foreach ($featureOptions as $value => $label): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="allowed_features[]" value="<?= $value ?>" id="update-feature-<?= $value ?>">
                                <label class="form-check-label" for="update-feature-<?= $value ?>"><?= $label ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Durum</label>
                        <select name="status" class="form-select">
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Değişiklikleri Kaydet</button>
                </form>
                <hr>
                <form data-ajax="true" data-endpoint="/admin/packages/delete" data-refresh="#admin-packages-table" data-json="false" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label">Paket ID</label>
                        <input type="number" name="id" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-outline-danger w-100">Paketi Sil</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Paket Bilgilendirmesi</h2>
            </div>
            <div class="card-body">
                <p class="text-muted small">Paketler müşterilere atanabilir ve API izinleri paket düzeyinde kontrol edilir. Özellik listesini girerken her satırda bir madde olacak şekilde düzenleyin.</p>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><strong>Aylık limit</strong> alanı bildirim gönderim sınırı için kullanılır.</li>
                    <li class="mb-2"><strong>Kullanım süresi</strong>, paketin otomatik sona erme tarihini belirler.</li>
                    <li class="mb-2">İzin verilen özellikler, müşteri panelindeki seçeneklerin görünürlüğünü yönetir.</li>
                    <li>Paketin pasif yapılması durumunda yeni satışa kapatılır, mevcut müşteriler etkilenmez.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<section class="mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Tüm Paketler</h2>
        <button class="btn btn-outline-primary btn-sm" data-action="refresh" data-target="#admin-packages-table">Yenile</button>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table class="table table-striped align-middle" id="admin-packages-table" data-source="/admin/packages/data" data-columns='["name","price","currency","duration_days","monthly_limit","status"]' data-fill-form="#package-update-form">
                <thead>
                    <tr>
                        <th>Paket</th>
                        <th>Fiyat</th>
                        <th>Para Birimi</th>
                        <th>Süre (Gün)</th>
                        <th>Aylık Limit</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../layout/footer.php'; ?>
