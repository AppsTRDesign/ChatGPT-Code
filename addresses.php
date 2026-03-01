<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/account_nav.php';

$user = current_user();
render_header('Adresler');
?>
<main class="container">
    <h1>Adresler</h1>
    <?php if (!$user): ?>
        <p>Adres bilgilerinizi görüntülemek için giriş yapın.</p>
        <div class="button-row">
            <a class="btn" href="/login">Giriş Yap</a>
            <a class="btn primary" href="/register">Üye Ol</a>
        </div>
    <?php else: ?>
        <?php render_account_nav('adresler'); ?>
        <section class="section">
            <h2>Adres Bilgileri</h2>
            <p>Adres bilgilerinizi aşağıdaki formdan güncelleyebilirsiniz.</p>
            <form class="profile-form" data-ajax="address" method="post">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <label>Adres
                    <textarea name="address" rows="3" placeholder="Adres"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </label>
                <button class="btn primary" type="submit">Adres Kaydet</button>
            </form>
        </section>
    <?php endif; ?>
</main>
<?php
render_footer();
?>
