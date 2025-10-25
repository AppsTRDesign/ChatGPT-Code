<?php ob_start(); ?>
<div class="text-center py-5">
    <h1 class="display-3 text-primary">404</h1>
    <p class="lead">Aradığınız sayfa bulunamadı.</p>
    <a class="btn btn-primary" href="/">Ana Sayfaya Dön</a>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
