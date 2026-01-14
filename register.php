<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

render_header('Üye Ol');
?>
<main class="container auth">
    <h1>Üye Ol</h1>
    <form class="auth-form" data-ajax="register" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="text" name="name" placeholder="Ad Soyad" required>
        <input type="email" name="email" placeholder="E-posta" required>
        <input type="tel" name="phone" placeholder="Telefon">
        <input type="password" name="password" placeholder="Şifre" required>
        <button class="btn primary" type="submit">Kayıt Ol</button>
    </form>
</main>
<?php
render_footer();
?>
