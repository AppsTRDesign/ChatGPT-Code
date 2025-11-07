<?php ob_start(); ?>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card glass border-0 h-100">
            <div class="card-body">
                <h5 class="card-title text-secondary">Toplam Telefon</h5>
                <p class="display-6 text-light mb-0"><?= (int) ($stats['total_accounts'] ?? 0) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card glass border-0 h-100">
            <div class="card-body">
                <h5 class="card-title text-secondary">Toplanan Üye</h5>
                <p class="display-6 text-light mb-0"><?= (int) ($stats['total_members'] ?? 0) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card glass border-0 h-100">
            <div class="card-body">
                <h5 class="card-title text-secondary">Şablon Sayısı</h5>
                <p class="display-6 text-light mb-0"><?= (int) ($stats['active_templates'] ?? 0) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card glass border-0 mt-4">
    <div class="card-header border-0 text-uppercase text-secondary">Servis Durumları</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark table-striped align-middle mb-0">
                <thead>
                <tr>
                    <th>Servis</th>
                    <th>Durum</th>
                    <th>Son Hareket</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($services as $service): ?>
                    <tr>
                        <td><?= htmlspecialchars($service['name']) ?></td>
                        <td>
                            <?php
                            $status = $service['status'] ?? 'stopped';
                            $badge = match ($status) {
                                'running' => 'success',
                                'stopped' => 'secondary',
                                default => 'warning',
                            };
                            ?>
                            <span class="badge bg-<?= $badge ?> text-uppercase"><?= htmlspecialchars($status) ?></span>
                        </td>
                        <td><?= $service['last_heartbeat_at'] ? htmlspecialchars($service['last_heartbeat_at']) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$services): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted">Herhangi bir servis tanımlanmadı.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include resource_path('views/layouts/app.php');
