</main>
<footer class="footer">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <div class="brand">© <?= date('Y') ?> <?= htmlspecialchars($app['name']) ?></div>
        <div class="text-muted"><?= htmlspecialchars($app['tagline'] ?? '') ?></div>
    </div>
</footer>
<?php include __DIR__ . '/scripts.php'; ?>
