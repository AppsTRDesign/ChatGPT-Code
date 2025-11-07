<?php ob_start(); ?>
<div class="card glass border-0 mb-4">
    <div class="card-body">
        <h2 class="h6 text-uppercase text-secondary mb-3">Mesaj Gönderimi</h2>
        <form data-ajax="true" action="/admin/messaging" method="post" class="row g-3">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="mode" value="message">
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
            <div class="col-md-3">
                <label class="form-label">Gönderim Kaynağı</label>
                <select name="source_type" class="form-select">
                    <option value="manual">Manuel hedef</option>
                    <option value="member_template">Üye şablonu</option>
                    <option value="channel_template">Kanal şablonu</option>
                    <option value="group_template">Grup şablonu</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Planlanan Zaman</label>
                <input type="datetime-local" name="scheduled_for" class="form-control">
                <small class="text-muted">Boş bırakılırsa hemen gönderilir.</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">Manuel Hedef Türü</label>
                <select name="target_type" class="form-select">
                    <option value="">Seçiniz</option>
                    <option value="channel">Kanal</option>
                    <option value="group">Grup</option>
                    <option value="direct">DM</option>
                </select>
                <small class="text-muted">Yalnızca manuel mod için gereklidir.</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">Manuel Hedef</label>
                <input type="text" name="target_value" class="form-control" placeholder="@kanal veya kullanıcı">
            </div>
            <div class="col-md-3">
                <label class="form-label">Üye Şablonu</label>
                <select name="member_template_id" class="form-select">
                    <option value="">Seçiniz</option>
                    <?php foreach ($memberTemplates as $template): ?>
                        <option value="<?= (int) $template['id'] ?>"><?= htmlspecialchars($template['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Üyelere DM göndermek için kullanın.</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">Kanal/Grup Şablonu</label>
                <select name="channel_template_id" class="form-select">
                    <option value="">Seçiniz</option>
                    <?php if ($channelTemplates): ?>
                        <optgroup label="Kanallar">
                            <?php foreach ($channelTemplates as $template): ?>
                                <option value="<?= (int) $template['id'] ?>"><?= htmlspecialchars($template['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                    <?php if ($groupTemplates): ?>
                        <optgroup label="Gruplar">
                            <?php foreach ($groupTemplates as $template): ?>
                                <option value="<?= (int) $template['id'] ?>"><?= htmlspecialchars($template['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
                <small class="text-muted">Kanal veya grup gönderimleri için kullanın.</small>
            </div>
            <div class="col-12 text-end">
                <button class="btn btn-primary" type="submit">Gönderim Kuyruğa Al</button>
            </div>
        </form>
    </div>
</div>

<div class="card glass border-0 mb-4">
    <div class="card-body">
        <h2 class="h6 text-uppercase text-secondary mb-3">Üye Davetleri</h2>
        <form data-ajax="true" action="/admin/messaging" method="post" class="row g-3">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="mode" value="invitation">
            <div class="col-md-4">
                <label class="form-label">Davet Adı</label>
                <input type="text" name="invite_name" class="form-control" placeholder="Grup Daveti">
            </div>
            <div class="col-md-4">
                <label class="form-label">Üye Şablonu</label>
                <select name="invite_member_template_id" class="form-select">
                    <option value="">Seçiniz</option>
                    <?php foreach ($memberTemplates as $template): ?>
                        <option value="<?= (int) $template['id'] ?>"><?= htmlspecialchars($template['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Hedef Kanal / Grup</label>
                <select name="channel_id" class="form-select">
                    <option value="">Seçiniz</option>
                    <?php foreach ($channelTargets as $channel): ?>
                        <option value="<?= (int) $channel['id'] ?>">
                            <?= htmlspecialchars(($channel['title'] ?? '-') . ' • ' . ($channel['type'] ?? '?')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 text-end">
                <button class="btn btn-outline-primary" type="submit">Üyeleri Davet Kuyruğuna Ekle</button>
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
                    <th>İşlem</th>
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
                        <td><?= htmlspecialchars($job['action'] ?? 'send_message') ?></td>
                        <td><?= $job['template_id'] ? '#' . (int) $job['template_id'] : '—' ?></td>
                        <td><?= htmlspecialchars(($job['target_type'] ?? '-') . ' • ' . ($job['target_value'] ?? '-')) ?></td>
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
                        <td colspan="8" class="text-center text-muted">Henüz gönderim planı oluşturulmadı.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include resource_path('views/layouts/app.php');
