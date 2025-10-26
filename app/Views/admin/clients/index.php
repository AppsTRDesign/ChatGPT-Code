<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Yeni Müşteri Oluştur</h2>
            </div>
            <div class="card-body">
                <form data-ajax="true" data-endpoint="/admin/clients/store" data-refresh="#admin-clients-table" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label">Müşteri Adı</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input type="email" name="email" class="form-control" placeholder="musteri@example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Domain</label>
                        <input type="text" name="domain" class="form-control" placeholder="example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="phone" class="form-control" placeholder="90...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı Adı</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Şifre</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Durum</label>
                        <select name="status" class="form-select">
                            <option value="active">Aktif</option>
                            <option value="suspended">Askıya Alındı</option>
                        </select>
                    </div>
                    <input type="hidden" name="mail_verified" value="0">
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="mail_verified" value="1" class="form-check-input" id="createMailVerified">
                        <label class="form-check-label" for="createMailVerified">E-posta Onaylı</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notlar</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Müşteri hakkında not"></textarea>
                    </div>
                    <div class="border rounded p-3 mb-3">
                        <h3 class="h6 text-uppercase text-muted">Paket Atama</h3>
                        <div class="mb-3">
                            <label class="form-label">Paket</label>
                            <select name="package_id" class="form-select">
                                <option value="">Seçiniz</option>
                                <?php foreach ($packages as $package): ?>
                                    <option value="<?= (int) $package['id'] ?>"><?= htmlspecialchars($package['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Paket Durumu</label>
                            <select name="package_status" class="form-select">
                                <option value="active">Aktif</option>
                                <option value="pending">Onay Bekliyor</option>
                                <option value="cancelled">İptal</option>
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
                        <div class="mb-3">
                            <label class="form-label">Paket Notu</label>
                            <textarea name="package_note" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Müşteri Oluştur</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Müşteri Güncelle</h2>
            </div>
            <div class="card-body">
                <form id="client-update-form" data-ajax="true" data-endpoint="/admin/clients/update" data-refresh="#admin-clients-table" autocomplete="off">
                    <input type="hidden" name="id">
                    <div class="mb-3">
                        <label class="form-label">Müşteri Adı</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input type="email" name="email" class="form-control" placeholder="musteri@example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Domain</label>
                        <input type="text" name="domain" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Yeni Kullanıcı Adı</label>
                        <input type="text" name="username" class="form-control" placeholder="Değiştirmek istemiyorsanız boş bırakın">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Yeni Şifre</label>
                        <input type="password" name="password" class="form-control" placeholder="Opsiyonel">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Durum</label>
                        <select name="status" class="form-select">
                            <option value="active">Aktif</option>
                            <option value="suspended">Askıya Alındı</option>
                        </select>
                    </div>
                    <input type="hidden" name="mail_verified" value="0">
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="mail_verified" value="1" class="form-check-input" id="updateMailVerified">
                        <label class="form-check-label" for="updateMailVerified">E-posta Onaylı</label>
                    </div>
                    <input type="hidden" name="login_block" value="0">
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="login_block" value="1" class="form-check-input" id="updateLoginBlock">
                        <label class="form-check-label" for="updateLoginBlock">Giriş Yasaklı</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Giriş Yasağı Bitiş</label>
                        <input type="datetime-local" name="login_banned_until" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notlar</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Değişiklikleri Kaydet</button>
                </form>
                <hr>
                <form data-ajax="true" data-endpoint="/admin/clients/delete" data-refresh="#admin-clients-table" data-json="false" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label">Müşteri ID</label>
                        <input type="number" name="id" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-outline-danger w-100">Müşteriyi Sil</button>
                </form>
                <p class="text-muted small mt-2">Tablodan satır seçerek formu otomatik doldurabilirsiniz.</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Müşteri Özetleri</h2>
            </div>
            <div class="card-body">
                <p class="text-muted small">Müşteri satırlarına tıklayarak güncelleme formunu doldurabilirsiniz. Aktif/askıya alınan müşteriler, domain bilgisi ve API anahtar sayıları aşağıdaki tabloda listelenir.</p>
                <div class="alert alert-info d-flex align-items-center" role="alert">
                    <span class="material-symbols-outlined me-2">info</span>
                    <span>Yeni oluşturulan müşteriler için ilk API anahtarını <a class="text-decoration-none" href="/admin/api-keys">API yönetimi</a> sayfasından oluşturmayı unutmayın.</span>
                </div>
            </div>
        </div>
    </div>
</div>
<section class="mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Tüm Müşteriler</h2>
        <button class="btn btn-outline-primary btn-sm" data-action="refresh" data-target="#admin-clients-table">Yenile</button>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table class="table table-striped align-middle" id="admin-clients-table" data-source="/admin/clients/data" data-columns='["name","email","domain","status","mail_verified","active_package_count","active_tokens","api_key_count"]' data-fill-form="#client-update-form">
                <thead>
                    <tr>
                        <th>Müşteri</th>
                        <th>E-posta</th>
                        <th>Domain</th>
                        <th>Durum</th>
                        <th>Mail</th>
                        <th>Paket</th>
                        <th>Aktif Token</th>
                        <th>API Anahtarı</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../layout/footer.php'; ?>
