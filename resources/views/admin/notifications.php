<?php ob_start(); ?>
<div class="card">
    <div class="card-body">
        <h5 class="mb-3">Gönderilen Bildirimler</h5>
        <div class="table-responsive">
            <table class="table table-striped datatable">
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
                        <td><?= htmlspecialchars($notification['link']) ?></td>
                        <td><?= $notification['created_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
