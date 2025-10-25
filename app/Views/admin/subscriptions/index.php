<?php include __DIR__ . '/../layout/header.php'; ?>
<section class="mb-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
                <div>
                    <h1 class="h4 mb-1">Abonelik Yönetimi</h1>
                    <p class="text-muted mb-0">Aktif tarayıcı token'larını GeoIP ve cihaz bilgileriyle birlikte inceleyin.</p>
                </div>
                <button class="btn btn-outline-primary mt-3 mt-lg-0" data-action="refresh" data-target="#admin-subscriptions-table">Verileri Yenile</button>
            </div>
        </div>
    </div>
</section>
<section>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table class="table table-hover align-middle" id="admin-subscriptions-table" data-source="/admin/subscriptions/data" data-columns='["client_id","token","country","city","platform","browser","status","created_at"]'>
                <thead>
                    <tr>
                        <th>Müşteri ID</th>
                        <th>Token</th>
                        <th>Ülke</th>
                        <th>Şehir</th>
                        <th>Platform</th>
                        <th>Tarayıcı</th>
                        <th>Durum</th>
                        <th>Kayıt Tarihi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../layout/footer.php'; ?>
