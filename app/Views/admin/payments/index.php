<?php include __DIR__ . '/../layout/header.php'; ?>
<section class="mb-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div>
                    <h1 class="h4 mb-1">Ödeme İşlemleri</h1>
                    <p class="text-muted mb-0">IyziCo entegrasyonundan dönen sonuçları görüntüleyin.</p>
                </div>
                <button class="btn btn-outline-primary mt-3 mt-md-0" data-action="refresh" data-target="#admin-payments-table">Yenile</button>
            </div>
        </div>
    </div>
</section>
<section>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table class="table table-striped align-middle" id="admin-payments-table" data-source="/admin/payments/data" data-columns='["client_id","iyzico_payment_id","method","status","amount","currency","note","created_at"]'>
                <thead>
                    <tr>
                        <th>Müşteri ID</th>
                        <th>Ödeme ID</th>
                        <th>Yöntem</th>
                        <th>Durum</th>
                        <th>Tutar</th>
                        <th>Para Birimi</th>
                        <th>Not</th>
                        <th>Tarih</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../layout/footer.php'; ?>
