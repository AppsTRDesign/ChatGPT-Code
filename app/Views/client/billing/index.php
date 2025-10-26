<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
    <div class="col-12 col-xxl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Hızlı Ödeme</h2>
            </div>
            <div class="card-body">
                <form id="client-checkout-form">
                    <div class="mb-3">
                        <label class="form-label">Paket Seçimi</label>
                        <select name="package_id" class="form-select">
                            <option value="">Özel Tutar</option>
                            <?php foreach ($packages as $package): ?>
                                <option value="<?= (int) $package['id'] ?>" data-price="<?= htmlspecialchars($package['price']) ?>" data-currency="<?= htmlspecialchars($package['currency']) ?>"><?= htmlspecialchars($package['name']) ?> - <?= number_format((float) $package['price'], 2) ?> <?= htmlspecialchars($package['currency']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tutar</label>
                        <input type="number" name="price" class="form-control" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Para Birimi</label>
                        <select name="currency" class="form-select">
                            <option value="TRY">TRY</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Callback URL</label>
                        <input type="url" name="callback_url" class="form-control" placeholder="https://">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Kredi Kartı ile Öde</button>
                </form>
                <div class="alert alert-info mt-3" id="checkout-response" style="display:none;"></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-xxl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Havale / EFT Bildirimi</h2>
            </div>
            <div class="card-body">
                <form data-ajax="true" data-endpoint="/client/billing/notify-transfer" data-refresh="#client-packages-table" id="client-transfer-form">
                    <div class="mb-3">
                        <label class="form-label">Paket</label>
                        <select name="package_id" class="form-select" required>
                            <?php foreach ($packages as $package): ?>
                                <option value="<?= (int) $package['id'] ?>"><?= htmlspecialchars($package['name']) ?> - <?= number_format((float) $package['price'], 2) ?> <?= htmlspecialchars($package['currency']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tutar</label>
                        <input type="number" name="amount" class="form-control" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Para Birimi</label>
                        <select name="currency" class="form-select">
                            <option value="TRY">TRY</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Referans / Dekont No</label>
                        <input type="text" name="reference" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Not</label>
                        <textarea name="note" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-outline-primary w-100">Bildirim Gönder</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-xxl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Aktif Paketler</h2>
            </div>
            <div class="card-body">
                <table class="table table-sm align-middle" id="client-packages-table">
                    <thead>
                        <tr>
                            <th>Paket</th>
                            <th>Durum</th>
                            <th>Bitiş</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientPackages as $cp): ?>
                            <tr>
                                <td><?= htmlspecialchars($cp['package_name'] ?? '') ?></td>
                                <td><span class="badge bg-primary-subtle text-uppercase"><?= htmlspecialchars($cp['status']) ?></span></td>
                                <td><?= $cp['expires_at'] ? date('d.m.Y', strtotime($cp['expires_at'])) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($clientPackages)): ?>
                            <tr><td colspan="3" class="text-muted text-center">Aktif paket bulunamadı.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<section class="mt-5">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent">
            <h2 class="h5 mb-0">Ödeme Geçmişi</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Ödeme ID</th>
                            <th>Yöntem</th>
                            <th>Durum</th>
                            <th>Tutar</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= htmlspecialchars($payment['iyzico_payment_id']) ?></td>
                                <td><?= htmlspecialchars($payment['method'] ?? 'iyzico') ?></td>
                                <td><span class="badge bg-primary-subtle text-uppercase"><?= htmlspecialchars($payment['status']) ?></span></td>
                                <td><?= number_format((float) $payment['amount'], 2) ?> <?= htmlspecialchars($payment['currency']) ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($payment['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($payments)): ?>
                            <tr><td colspan="5" class="text-muted text-center">Henüz ödeme kaydı bulunmuyor.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('client-checkout-form');
        const responseBox = document.getElementById('checkout-response');
        const packageSelect = form?.querySelector('[name="package_id"]');

        if (packageSelect && form) {
            packageSelect.addEventListener('change', (event) => {
                const option = event.target.selectedOptions[0];
                if (!option || !option.dataset.price) {
                    return;
                }
                form.querySelector('[name="price"]').value = option.dataset.price;
                form.querySelector('[name="currency"]').value = option.dataset.currency;
            });
        }

        if (form) {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const formData = new FormData(form);
                const payload = Object.fromEntries(formData.entries());

                fetch('/client/billing/checkout', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                    .then((response) => response.json())
                    .then((data) => {
                        responseBox.style.display = 'block';
                        if (data.status === 'success' && data.checkout_form) {
                            responseBox.className = 'alert alert-success mt-3';
                            responseBox.innerHTML = data.checkout_form;
                        } else {
                            responseBox.className = 'alert alert-warning mt-3';
                            responseBox.textContent = data.message || 'Ödeme oluşturulamadı';
                        }
                    })
                    .catch(() => {
                        responseBox.style.display = 'block';
                        responseBox.className = 'alert alert-danger mt-3';
                        responseBox.textContent = 'Sunucu hatası';
                    });
            });
        }
    });
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
