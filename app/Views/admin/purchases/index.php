<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Satın Alım Kaydı Oluştur</h2>
            </div>
            <div class="card-body">
                <form data-ajax="true" data-endpoint="/admin/purchases/store" data-refresh="#admin-purchases-table" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label">Müşteri</label>
                        <select name="client_id" class="form-select" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= (int) $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Paket</label>
                        <select name="package_id" class="form-select" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($packages as $package): ?>
                                <option value="<?= (int) $package['id'] ?>"><?= htmlspecialchars($package['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ödeme Yöntemi</label>
                        <select name="payment_method" class="form-select">
                            <option value="manual">Manuel</option>
                            <option value="iyzico">IyziCo</option>
                            <option value="bank_transfer">Havale/EFT</option>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Tutar</label>
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Para Birimi</label>
                            <input type="text" name="currency" value="TRY" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label">Başlangıç Durumu</label>
                        <select name="status" class="form-select">
                            <option value="pending">Onay Bekliyor</option>
                            <option value="active">Aktif</option>
                            <option value="partial">Eksik Ödeme</option>
                            <option value="cancelled">İptal</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Not</label>
                        <textarea name="note" class="form-control" rows="3" placeholder="Satın alım notu"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Satın Alımı Kaydet</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Durum Güncelle</h2>
            </div>
            <div class="card-body">
                <form id="purchase-update-form" data-ajax="true" data-endpoint="/admin/purchases/update-status" data-refresh="#admin-purchases-table" autocomplete="off">
                    <input type="hidden" name="id">
                    <div class="mb-3">
                        <label class="form-label">İşlem</label>
                        <select name="action" class="form-select" required>
                            <option value="approve">Onayla</option>
                            <option value="reject">Reddet</option>
                            <option value="cancel">İptal Et</option>
                            <option value="partial">Eksik Ödeme</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Not</label>
                        <textarea name="note" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Durumu Güncelle</button>
                </form>
                <hr>
                <p class="text-muted small">Tablodan satır seçerek ID alanı otomatik doldurulur. IyziCo üzerinden başarılı işlemler otomatik onaylanır.</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Satın Alım Bilgilendirmesi</h2>
            </div>
            <div class="card-body">
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2">Onaylanan satın alımlar müşterinin paketini anında aktif eder ve bitiş tarihi paketin süresine göre hesaplanır.</li>
                    <li class="mb-2">Reddedilen talepler için not alanına sebep yazılması önerilir.</li>
                    <li class="mb-2">Bankadan gelen ödemeler <strong>Havale/EFT</strong> yöntemi ile kaydedilip manuel olarak onaylanabilir.</li>
                    <li class="mb-2"><strong>Eksik Ödeme</strong> olarak işaretlenen kayıtlar raporlamada bekleyen durumunda kalır ve ödeme tamamlandığında tekrar onaylanmalıdır.</li>
                    <li>Kayıt oluştururken fiyat alanı boş bırakılırsa paket fiyatı otomatik olarak kullanılır.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<section class="mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Satın Alımlar</h2>
        <button class="btn btn-outline-primary btn-sm" data-action="refresh" data-target="#admin-purchases-table">Yenile</button>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table class="table table-striped align-middle" id="admin-purchases-table" data-source="/admin/purchases/data" data-columns='["client_name","package_name","status","payment_method","amount","requested_at","approved_at","expires_at"]' data-fill-form="#purchase-update-form">
                <thead>
                    <tr>
                        <th>Müşteri</th>
                        <th>Paket</th>
                        <th>Durum</th>
                        <th>Ödeme</th>
                        <th>Tutar</th>
                        <th>Talep</th>
                        <th>Onay</th>
                        <th>Bitiş</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../layout/footer.php'; ?>
