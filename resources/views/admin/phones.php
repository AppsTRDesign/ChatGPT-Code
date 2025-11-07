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
                    <th>Oturum</th>
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
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <span class="badge bg-info text-dark text-uppercase"><?= htmlspecialchars($account['session_status'] ?: 'bekleniyor') ?></span>
                                <?php if (!empty($account['two_factor_hint'])): ?>
                                    <small class="text-warning">2FA ipucu: <?= htmlspecialchars($account['two_factor_hint']) ?></small>
                                <?php endif; ?>
                                <?php if (!empty($account['last_error'])): ?>
                                    <small class="text-danger">Hata: <?= htmlspecialchars($account['last_error']) ?></small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= $account['banned_at'] ? '<span class="badge bg-danger">Banlı</span>' : '<span class="badge bg-success">Temiz</span>' ?></td>
                        <td><?= $account['last_seen_at'] ? htmlspecialchars($account['last_seen_at']) : '—' ?></td>
                        <td>
                            <div class="d-flex flex-column flex-md-row gap-2">
                                <form data-ajax="true" action="/admin/phones/<?= (int) $account['id'] ?>/send-code" method="post">
                                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                    <button class="btn btn-sm btn-outline-primary" type="submit">Kod Gönder</button>
                                </form>
                                <form data-ajax="true" action="/admin/phones/<?= (int) $account['id'] ?>/confirm-code" method="post" class="d-flex flex-column flex-lg-row gap-2">
                                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                    <input type="text" name="code" class="form-control form-control-sm" placeholder="Kod">
                                    <input type="password" name="password" class="form-control form-control-sm" placeholder="2FA (varsa)">
                                    <button class="btn btn-sm btn-outline-success" type="submit">Doğrula</button>
                                </form>
                                <form class="d-inline" data-ajax="true" action="/admin/phones/<?= (int) $account['id'] ?>/delete" method="post">
                                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                    <button class="btn btn-sm btn-outline-danger">Sil</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$accounts): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">Kayıtlı telefon bulunmuyor.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include resource_path('views/layouts/app.php');
