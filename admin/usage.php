<?php
require __DIR__ . '/header.php';

use App\Helpers;

$db = Helpers::db();
$logs = $db->query('SELECT l.*, u.username FROM api_usage_logs l JOIN users u ON u.id = l.user_id ORDER BY l.created_at DESC LIMIT 200')->fetchAll();
?>
<h1 class="h3 mb-4">API Kullanım Raporları</h1>
<div class="card p-4">
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Kullanıcı</th>
                    <th>Endpoint</th>
                    <th>Durum</th>
                    <th>Not</th>
                    <th>Tarih</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= Helpers::e($log['username']) ?></td>
                        <td><?= Helpers::e($log['endpoint']) ?></td>
                        <td><?= Helpers::e($log['status']) ?></td>
                        <td><?= Helpers::e($log['note']) ?></td>
                        <td><?= Helpers::e($log['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
