<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Ödeme Başlat</h2>
            </div>
            <div class="card-body">
                <form id="client-checkout-form">
                    <div class="mb-3">
                        <label class="form-label">Tutar (TRY)</label>
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
                    <button type="submit" class="btn btn-primary w-100">Ödeme Linki Oluştur</button>
                </form>
                <div class="alert alert-info mt-3" id="checkout-response" style="display:none;"></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Ödeme Geçmişi</h2>
            </div>
            <div class="card-body">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Ödeme ID</th>
                            <th>Durum</th>
                            <th>Tutar</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= htmlspecialchars($payment['iyzico_payment_id']) ?></td>
                                <td><span class="badge bg-primary-subtle text-uppercase"><?= htmlspecialchars($payment['status']) ?></span></td>
                                <td><?= number_format((float) $payment['amount'], 2) ?> <?= htmlspecialchars($payment['currency']) ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($payment['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($payments)): ?>
                            <tr><td colspan="4" class="text-muted text-center">Henüz ödeme kaydı bulunmuyor.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('client-checkout-form');
        const responseBox = document.getElementById('checkout-response');

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
