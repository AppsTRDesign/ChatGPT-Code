<div class="row g-4">
    <?php foreach ($packages as $package): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="mb-1 text-primary"><?= htmlspecialchars($package['name']) ?></h5>
                            <span class="badge-soft">₺<?= number_format((float) $package['price'], 2) ?>/ay</span>
                        </div>
                        <i class="bi bi-lightning-charge fs-4 text-warning"></i>
                    </div>
                    <ul class="list-unstyled small flex-grow-1 mb-4">
                        <li><i class="bi bi-bell"></i> Aylık Limit: <?= htmlspecialchars($package['monthly_limit']) ?></li>
                        <li><i class="bi bi-clock"></i> Süre: <?= htmlspecialchars($package['duration_days']) ?> gün</li>
                        <li><i class="bi bi-globe"></i> Site: <?= htmlspecialchars($package['site_limit']) ?></li>
                        <li><i class="bi bi-check2-circle"></i> Özellikler: <?= htmlspecialchars($package['features'] ?? 'Tümü') ?></li>
                    </ul>
                    <div class="d-grid gap-2">
                        <button class="btn btn-theme" type="button"><i class="bi bi-credit-card"></i> İyzico ile Satın Al</button>
                        <button class="btn btn-outline-secondary" type="button"><i class="bi bi-bank"></i> Havale/EFT</button>
                        <?php $checkout = $checkoutSamples[$package['id']] ?? null; ?>
                        <?php if ($checkout): ?>
                            <div class="small text-muted">
                                <div>Conversation: <?= htmlspecialchars($checkout['conversationId']) ?></div>
                                <div>Callback: <?= htmlspecialchars($checkout['callbackUrl']) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
