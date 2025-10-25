<?php ob_start(); ?>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Üye Listesi</h5>
            <button class="btn btn-sm btn-outline-primary" data-refresh="#memberTable" data-url="/admin/members">Yenile</button>
        </div>
        <div class="table-responsive">
            <table class="table table-striped datatable" id="memberTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad</th>
                        <th>E-posta</th>
                        <th>Rol</th>
                        <th>Durum</th>
                        <th>Kayıt Tarihi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?= $member['id'] ?></td>
                        <td><?= htmlspecialchars($member['name']) ?></td>
                        <td><?= htmlspecialchars($member['email']) ?></td>
                        <td><?= htmlspecialchars($member['role']) ?></td>
                        <td><?= $member['is_active'] ? 'Aktif' : 'Pasif' ?></td>
                        <td><?= $member['created_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
