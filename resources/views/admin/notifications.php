<section class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1">Gönderilen Bildirimler</h5>
                <small class="text-muted">Kullanıcı bazlı gönderim geçmişi.</small>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-secondary btn-sm">Aktif</button>
                <button class="btn btn-outline-secondary btn-sm">Pasif</button>
                <button class="btn btn-outline-secondary btn-sm">Sil</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover datatable align-middle">
                <thead>
                    <tr>
                        <th>Kullanıcı</th>
                        <th>Başlık</th>
                        <th>Mesaj</th>
                        <th>Bağlantı</th>
                        <th>Oluşturulma</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $notification): ?>
                        <tr>
                            <td><?= htmlspecialchars($notification['user_name']) ?></td>
                            <td><?= htmlspecialchars($notification['title']) ?></td>
                            <td><?= htmlspecialchars($notification['message']) ?></td>
                            <td><a href="<?= htmlspecialchars($notification['link']) ?>" target="_blank" class="text-decoration-none"><?= htmlspecialchars($notification['link']) ?></a></td>
                            <td><?= htmlspecialchars($notification['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
