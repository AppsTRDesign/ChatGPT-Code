<?php ob_start(); ?>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Bildirim Geçmişi</h5>
            <a class="btn btn-primary btn-sm" href="/app/notifications/new">Yeni Bildirim</a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped datatable">
                <thead>
                    <tr>
                        <th>Başlık</th>
                        <th>Mesaj</th>
                        <th>Bağlantı</th>
                        <th>Oluşturulma</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($notifications as $notification): ?>
                    <tr>
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
