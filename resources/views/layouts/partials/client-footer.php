</main>
<footer class="footer mt-auto">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
        <div class="brand">© <?= date('Y') ?> <?= htmlspecialchars($app['name']) ?></div>
        <div class="d-flex gap-3">
            <a href="<?= base_url('app/support') ?>" class="text-decoration-none text-muted">Destek</a>
            <a href="<?= base_url('app/api-guide') ?>" class="text-decoration-none text-muted">API Kılavuzu</a>
        </div>
    </div>
</footer>
<?php include __DIR__ . '/scripts.php'; ?>
