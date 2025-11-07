<?php ob_start(); ?>
<div class="card glass border-0 mb-4">
    <div class="card-body">
        <form data-ajax="true" action="/admin/messaging" method="post">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Kampanya Adı</label>
                    <input type="text" name="name" class="form-control" placeholder="Mayıs Kampanyası">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mesaj Şablonu</label>
                    <select name="template_id" class="form-select">
                        <option value="">Seçiniz</option>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?= (int) $template['id'] ?>"><?= htmlspecialchars($template['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hedef Türü</label>
                    <select name="target_type" class="form-select">
                        <option value="channel">Kanal</option>
                        <option value="group">Grup</option>
                        <option value="direct">DM</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hedef</label>
                    <input type="text" name="target_value" class="form-control" placeholder="@kanal">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Planlanan Zaman</label>
                    <input type="datetime-local" name="scheduled_for" class="form-control">
                </div>
            </div>
            <div class="mt-3 text-end">
                <button class="btn btn-primary" type="submit">Gönderim Planla</button>
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
                    <th>Ad</th>
                    <th>Şablon</th>
                    <th>Hedef</th>
                    <th>Durum</th>
                    <th>Zaman</th>
                    <th>İşlemler</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($jobs as $job): ?>
                    <tr>
                        <td><?= (int) $job['id'] ?></td>
                        <td><?= htmlspecialchars($job['name']) ?></td>
                        <td>#<?= (int) $job['template_id'] ?></td>
                        <td><?= htmlspecialchars($job['target_type'] . ' - ' . $job['target_value']) ?></td>
                        <td>
                            <span class="badge bg-<?= $job['status'] === 'completed' ? 'success' : ($job['status'] === 'failed' ? 'danger' : 'warning') ?>">
                                <?= htmlspecialchars($job['status']) ?>
                            </span>
                        </td>
                        <td><?= $job['scheduled_for'] ? htmlspecialchars($job['scheduled_for']) : 'Hemen' ?></td>
                        <td class="d-flex gap-2">
                            <form data-ajax="true" method="post" action="/admin/messaging/<?= (int) $job['id'] ?>">
                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="status" value="cancelled">
                                <button class="btn btn-sm btn-outline-warning">İptal</button>
                            </form>
                            <form data-ajax="true" method="post" action="/admin/messaging/<?= (int) $job['id'] ?>/delete">
                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                <button class="btn btn-sm btn-outline-danger">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$jobs): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Henüz gönderim planı oluşturulmadı.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include resource_path('views/layouts/app.php');
