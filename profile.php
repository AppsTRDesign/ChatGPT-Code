<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/account_nav.php';

$user = current_user();
render_header('Profil');
?>
<main class="container">
    <h1>Profil</h1>
    <?php if (!$user): ?>
        <p>Profil bilgilerinizi görüntülemek için giriş yapın.</p>
        <div class="button-row">
            <a class="btn" href="/giris">Giriş Yap</a>
            <a class="btn primary" href="/kayit">Üye Ol</a>
        </div>
    <?php else: ?>
        <div class="account-info">
            <h2>Merhaba, <?= htmlspecialchars($user['name']) ?></h2>
            <?php if (!empty($user['avatar'])): ?>
                <img class="avatar-image" src="<?= htmlspecialchars($user['avatar']) ?>" alt="<?= htmlspecialchars($user['name']) ?>">
            <?php endif; ?>
            <p>E-posta: <?= htmlspecialchars($user['email']) ?></p>
            <p>Telefon: <?= htmlspecialchars($user['phone']) ?></p>
        </div>
        <?php render_account_nav('profil'); ?>
        <form class="profile-form" data-ajax="profile" enctype="multipart/form-data" method="post">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <label>Ad Soyad
                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </label>
            <label>E-posta
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </label>
            <label>Telefon
                <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
            </label>
            <label>Profil Fotoğrafı<input type="file" name="avatar"></label>
            <button class="btn primary" type="submit">Bilgileri Güncelle</button>
        </form>
    <?php endif; ?>
</main>
<?php
render_footer();
?>
