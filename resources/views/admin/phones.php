<?php ob_start(); ?>
<div class="card glass border-0 mb-4">
    <div class="card-body">
        <form data-ajax="true" method="post" action="/admin/phones">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Telefon Numarası</label>
                    <input type="text" name="phone_number" class="form-control" placeholder="+905...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Etiket</label>
                    <input type="text" name="label" class="form-control" placeholder="Destek, Pazarlama...">
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active">
                        <label class="form-check-label" for="is_active">Aktif</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit">Telefon Ekle</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card glass border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark align-middle mb-0">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Telefon</th>
                    <th>Etiket</th>
                    <th>Durum</th>
                    <th>Ban</th>
                    <th>Son Görülme</th>
                    <th>İşlemler</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($accounts as $account): ?>
                    <tr>
                        <td><?= (int) $account['id'] ?></td>
                        <td><?= htmlspecialchars($account['phone_number']) ?></td>
                        <td><?= htmlspecialchars($account['label']) ?></td>
                        <td>
                            <span class="badge bg-<?= (int) $account['is_active'] === 1 ? 'success' : 'secondary' ?>">
                                <?= (int) $account['is_active'] === 1 ? 'Aktif' : 'Pasif' ?>
                            </span>
                        </td>
                        <td><?= $account['banned_at'] ? '<span class="badge bg-danger">Banlı</span>' : '<span class="badge bg-success">Temiz</span>' ?></td>
                        <td><?= $account['last_seen_at'] ? htmlspecialchars($account['last_seen_at']) : '—' ?></td>
                        <td>
                            <form class="d-inline" data-ajax="true" action="/admin/phones/<?= (int) $account['id'] ?>/delete" method="post">
                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                <button class="btn btn-sm btn-outline-danger">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$accounts): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Kayıtlı telefon bulunmuyor.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include resource_path('views/layouts/app.php');
