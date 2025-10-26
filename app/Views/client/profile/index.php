<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Profil Bilgileri</h2>
            </div>
            <div class="card-body">
                <form data-ajax="true" data-endpoint="/client/profile/update" id="client-profile-form">
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı Adı</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($client['email'] ?? $user['email'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Yeni Şifre</label>
                        <input type="password" name="password" class="form-control" placeholder="Boş bırakılırsa değişmez">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Firma / Ad</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($client['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alan Adı</label>
                        <input type="text" name="domain" class="form-control" value="<?= htmlspecialchars($client['domain'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($client['phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Varsayılan Dil</label>
                        <select name="default_language" class="form-select">
                            <?php foreach ($languages as $code => $label): ?>
                                <option value="<?= htmlspecialchars($code) ?>" <?= ($client['default_language'] ?? 'tr') === $code ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notlar</label>
                        <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($client['notes'] ?? '') ?></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Hızlı Bilgiler</h2>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Üyelik Durumu</span>
                        <span class="badge bg-primary"><?= htmlspecialchars($client['status'] ?? 'active') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Kayıt Tarihi</span>
                        <span><?= htmlspecialchars($client['created_at'] ?? '-') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Son Güncelleme</span>
                        <span><?= htmlspecialchars($client['updated_at'] ?? '-') ?></span>
                    </li>
                </ul>
                <div class="alert alert-info mt-3" role="alert">
                    Marka öğeleri ve logo yüklemeleri <strong>Ayarlar &gt; Marka</strong> bölümünden yönetilir.
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
