<section class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1">Sitelerim</h5>
                <small class="text-muted">Bildirim gönderilecek alan adlarını yönetin.</small>
            </div>
            <button class="btn btn-theme btn-sm"><i class="bi bi-plus"></i> Site Ekle</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover datatable align-middle" data-empty="Henüz site eklemediniz.">
                <thead>
                    <tr>
                        <th>Ad</th>
                        <th>Domain</th>
                        <th>Durum</th>
                        <th>Oluşturulma</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sites as $site): ?>
                        <tr>
                            <td><?= htmlspecialchars($site['name']) ?></td>
                            <td><?= htmlspecialchars($site['domain']) ?></td>
                            <td><span class="badge-soft"><?= $site['is_active'] ? 'Aktif' : 'Pasif' ?></span></td>
                            <td><?= htmlspecialchars($site['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (empty($sites)): ?>
            <div class="text-center text-muted py-4" data-empty-state>Henüz site eklemediniz.</div>
        <?php endif; ?>
    </div>
</section>
