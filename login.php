<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/includes/auth.php';

$user = current_user();
if ($user) {
    header('Location: /account.php');
    exit;
}

render_header('Giriş Yap');
?>
<main class="container auth">
    <section class="auth-card">
        <div class="auth-header">
            <h1>Giriş Yap</h1>
            <hr class="section-divider">
        </div>
        <form class="auth-form" data-ajax="login" method="post">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="email" name="email" placeholder="E-posta" required>
            <input type="password" name="password" placeholder="Şifre" required>
            <button class="btn primary" type="submit">Giriş Yap</button>
        </form>
    </section>
</main>
<?php
render_footer();
?>
