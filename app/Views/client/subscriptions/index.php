<?php include __DIR__ . '/../layout/header.php'; ?>
<section class="mb-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1 class="h5 mb-1">Abonelikler</h1>
                <p class="text-muted mb-0">GeoIP2 ve DeviceDetector verileri ile token detaylarını inceleyin.</p>
            </div>
            <button class="btn btn-outline-primary mt-3 mt-sm-0" data-action="refresh" data-target="#client-subscriptions-table">Yenile</button>
        </div>
    </div>
</section>
<section>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table class="table table-hover align-middle" id="client-subscriptions-table" data-source="/client/subscriptions/data" data-columns='["token","country","city","platform","browser","device_type","created_at"]'>
                <thead>
                    <tr>
                        <th>Token</th>
                        <th>Ülke</th>
                        <th>Şehir</th>
                        <th>Platform</th>
                        <th>Tarayıcı</th>
                        <th>Cihaz Tipi</th>
                        <th>Kayıt Tarihi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../layout/footer.php'; ?>
