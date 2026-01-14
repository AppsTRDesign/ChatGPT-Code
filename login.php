<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

render_header('Giriş Yap');
?>
<main class="container auth">
    <h1>Giriş Yap</h1>
    <form class="auth-form" data-ajax="login" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="email" name="email" placeholder="E-posta" required>
        <input type="password" name="password" placeholder="Şifre" required>
        <button class="btn primary" type="submit">Giriş Yap</button>
    </form>
</main>
<?php
render_footer();
?>
