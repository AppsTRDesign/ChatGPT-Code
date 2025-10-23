</div>
<?php
use App\Settings;
$footerHtml = Settings::footerHtml();
$siteName = Settings::siteName();
?>
<footer>
    <div class="container">
        <?php if ($footerHtml): ?>
            <?= $footerHtml ?>
        <?php else: ?>
            <p class="mb-0">&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>. Tüm hakları saklıdır.</p>
        <?php endif; ?>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
<script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>
