<?php ob_start(); ?>
<div class="card glass border-0 mb-4">
    <div class="card-body">
        <form data-ajax="true" method="post" action="/admin/members">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Telegram ID</label>
                    <input type="text" name="telegram_id" class="form-control" placeholder="123456789">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kullanıcı Adı</label>
                    <input type="text" name="username" class="form-control" placeholder="@kullanici">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Son Aktif</label>
                    <input type="text" name="last_active_at" class="form-control" placeholder="2024-05-01">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Durum</label>
                    <input type="text" name="online_status" class="form-control" placeholder="Online / 5 dk">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 mt-4">Üye Kaydet</button>
                </div>
            </div>
        </form>
        <div class="d-flex gap-2 mt-3">
            <a href="/admin/members/export" class="btn btn-outline-light">CSV Dışa Aktar</a>
            <form data-ajax="true" action="/admin/members/import" method="post" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <div class="input-group">
                    <input type="file" name="file" class="form-control">
                    <button class="btn btn-outline-primary" type="submit">İçe Aktar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card glass border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark align-middle mb-0">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Telegram ID</th>
                    <th>Kullanıcı Adı</th>
                    <th>Public</th>
                    <th>Kaynak Kanal</th>
                    <th>Son Aktif</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?= (int) $member['id'] ?></td>
                        <td><?= htmlspecialchars($member['telegram_id']) ?></td>
                        <td><?= htmlspecialchars($member['username']) ?></td>
                        <td><?= (int) $member['is_public'] === 1 ? 'Evet' : 'Hayır' ?></td>
                        <td><?= htmlspecialchars($member['joined_from_channel']) ?></td>
                        <td><?= htmlspecialchars($member['last_active_at']) ?></td>
                        <td><?= htmlspecialchars($member['online_status']) ?></td>
                        <td>
                            <form data-ajax="true" class="d-inline" method="post" action="/admin/members/<?= (int) $member['id'] ?>/delete">
                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                <button class="btn btn-sm btn-outline-danger">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$members): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">Henüz üye kaydı yok.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include resource_path('views/layouts/app.php');
