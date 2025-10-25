<?php ob_start(); ?>
<div class="row g-4">
    <?php foreach ($packages as $package): ?>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <h5 class="text-primary"><?= htmlspecialchars($package['name']) ?></h5>
                    <p class="text-muted">₺<?= number_format($package['price'], 2) ?>/ay</p>
                    <ul class="list-unstyled small flex-grow-1">
                        <li>Aylık Limit: <?= $package['monthly_limit'] ?></li>
                        <li>Süre: <?= $package['duration_days'] ?> gün</li>
                        <li>Site: <?= $package['site_limit'] ?></li>
                    </ul>
                    <button class="btn btn-primary mt-auto" type="button">Satın Al</button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
