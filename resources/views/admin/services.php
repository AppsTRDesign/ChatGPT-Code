<?php ob_start(); ?>
<div class="card glass border-0 mb-4">
    <div class="card-body">
        <form data-ajax="true" action="/admin/services" method="post">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Servis Adı</label>
                    <input type="text" name="name" class="form-control" placeholder="Üye Tarayıcı">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Servis Anahtarı</label>
                    <input type="text" name="slug" class="form-control" placeholder="discovery-daemon">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Komut</label>
                    <input type="text" name="command" class="form-control" placeholder="php /path/bin/telegram_worker.php">
                </div>
                <div class="col-12">
                    <label class="form-label">Açıklama</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Servisin görevi ve çalışma şekli"></textarea>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Durum</label>
                    <select name="status" class="form-select">
                        <option value="running">Çalışıyor</option>
                        <option value="stopped">Durdu</option>
                        <option value="warning">Uyarı</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100 mt-4" type="submit">Servis Ekle</button>
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
                    <th>Ad</th>
                    <th>Durum</th>
                    <th>Açıklama</th>
                    <th>Komut</th>
                    <th>Heartbeat</th>
                    <th>İşlemler</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($services as $service): ?>
                    <tr>
                        <td><?= (int) $service['id'] ?></td>
                        <td><?= htmlspecialchars($service['name']) ?></td>
                        <td>
                            <?php
                            $status = $service['status'] ?? 'stopped';
                            $statusClass = match ($status) {
                                'running' => 'success',
                                'warning' => 'warning text-dark',
                                'error' => 'danger',
                                default => 'secondary',
                            };
                            $statusLabel = match ($status) {
                                'running' => 'Başladı',
                                'warning' => 'Uyarı',
                                'error' => 'Hata',
                                default => 'Durduruldu',
                            };
                            ?>
                            <span class="badge bg-<?= $statusClass ?>"><?= $statusLabel ?></span>
                        </td>
                        <td class="small text-muted"><?= $service['description'] ? nl2br(htmlspecialchars($service['description'])) : '—' ?></td>
                        <td>
                            <?php if (!empty($service['command'])): ?>
                                <code class="d-block small text-break"><?= htmlspecialchars($service['command']) ?></code>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $service['last_heartbeat_at'] ? htmlspecialchars($service['last_heartbeat_at']) : '—' ?></td>
                        <td class="d-flex gap-2">
                            <form data-ajax="true" method="post" action="/admin/services/<?= (int) $service['id'] ?>">
                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="status" value="running">
                                <button class="btn btn-sm btn-outline-success">Başlat</button>
                            </form>
                            <form data-ajax="true" method="post" action="/admin/services/<?= (int) $service['id'] ?>">
                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="status" value="stopped">
                                <button class="btn btn-sm btn-outline-warning">Durdur</button>
                            </form>
                            <form data-ajax="true" method="post" action="/admin/services/<?= (int) $service['id'] ?>">
                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="status" value="warning">
                                <button class="btn btn-sm btn-outline-secondary">Uyarı</button>
                            </form>
                            <form data-ajax="true" method="post" action="/admin/services/<?= (int) $service['id'] ?>/delete">
                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                <button class="btn btn-sm btn-outline-danger">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$services): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Servis kaydı yok.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include resource_path('views/layouts/app.php');
