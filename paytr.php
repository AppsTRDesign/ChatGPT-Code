<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

render_header('PayTR Ödeme');
?>
<main class="container">
    <h1>PayTR Ödeme</h1>
    <p>PayTR ödeme ekranı için gerekli dosyaları <strong>/includes</strong> klasörüne ekledikten sonra bu sayfaya yönlendirme yapılacaktır.</p>
    <div class="panel">
        <p>PayTR Merchant ID: <?= htmlspecialchars(settings('paytr_merchant_id')) ?></p>
        <p>PayTR Success URL: <?= htmlspecialchars(settings('paytr_success_url')) ?></p>
    </div>
</main>
<?php
render_footer();
?>
