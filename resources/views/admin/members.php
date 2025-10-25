<section class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1">Üye Listesi</h5>
                <small class="text-muted">Üyelerin paketlerini, durumlarını ve rollerini yönetin.</small>
            </div>
            <button class="btn btn-theme btn-sm" data-refresh="#memberTable" data-url="<?= base_url('admin/members') ?>"><i class="bi bi-arrow-repeat"></i> Yenile</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover datatable align-middle" id="memberTable">
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
                            <td><?= htmlspecialchars($member['id']) ?></td>
                            <td><?= htmlspecialchars($member['name']) ?></td>
                            <td><?= htmlspecialchars($member['email']) ?></td>
                            <td><span class="badge-soft text-uppercase"><?= htmlspecialchars($member['role']) ?></span></td>
                            <td><?= $member['is_active'] ? 'Aktif' : 'Pasif' ?></td>
                            <td><?= htmlspecialchars($member['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
