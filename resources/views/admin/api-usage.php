<section class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
            <div>
                <h5 class="mb-1">API Kullanım Özeti</h5>
                <small class="text-muted">Token bazlı çağrı istatistikleri.</small>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-spreadsheet"></i> Excel</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover datatable align-middle">
                <thead>
                    <tr>
                        <th>Token</th>
                        <th>Kullanıcı</th>
                        <th>Çağrı Sayısı</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usage as $row): ?>
                        <tr>
                            <td class="text-break"><code><?= htmlspecialchars($row['token']) ?></code></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= number_format((int) $row['usage_count']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
