<section class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
            <div>
                <h5 class="mb-1">Bildirim Geçmişi</h5>
                <small class="text-muted">Planlanan tüm bildirimler, hedefler ve performans verileri.</small>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary btn-sm" href="#"><i class="bi bi-file-earmark-text"></i> Rapor</a>
                <a class="btn btn-theme btn-sm" href="<?= base_url('app/notifications/new') ?>"><i class="bi bi-plus-circle"></i> Yeni Bildirim</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover datatable align-middle">
                <thead>
                    <tr>
                        <th>Başlık</th>
                        <th>Mesaj</th>
                        <th>Bağlantı</th>
                        <th>Gösterim</th>
                        <th>Tıklama</th>
                        <th>Oluşturulma</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $notification): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($notification['title']) ?></td>
                            <td><?= htmlspecialchars($notification['message']) ?></td>
                            <td><a href="<?= htmlspecialchars($notification['link']) ?>" target="_blank" class="text-decoration-none"><?= htmlspecialchars($notification['link']) ?></a></td>
                            <td><?= rand(100, 1000) ?></td>
                            <td><?= rand(10, 300) ?></td>
                            <td><?= htmlspecialchars($notification['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
