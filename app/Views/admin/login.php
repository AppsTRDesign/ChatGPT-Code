<?php require __DIR__ . '/../partials/head.php'; ?>
<main class="auth-wrapper">
    <section class="card">
        <div class="brand">
            <h1>WebPush Kontrol Merkezi</h1>
            <p>Bildirim kampanyalarınızı yönetin, abonelerinizi büyütün.</p>
        </div>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" class="form">
            <label for="email">E-posta</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($email ?? '') ?>" required>

            <label for="password">Şifre</label>
            <input type="password" name="password" id="password" required>

            <button type="submit" class="btn-primary">Giriş Yap</button>
            <p class="helper">Varsayılan kullanıcı: admin@noasoft.org · Şifre: admin123</p>
        </form>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
