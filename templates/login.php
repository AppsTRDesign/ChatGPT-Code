<?php
/** @var string|null $error */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Girişi</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <script src="/assets/app.js" defer></script>
</head>
<body class="auth-body" data-error="<?php echo htmlspecialchars($error ?? '', ENT_QUOTES); ?>">
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1>Yönetici Girişi</h1>
            <p class="auth-subtitle">Lütfen yönetici bilgilerinizi girin.</p>
            <form method="post" data-ajax="true" class="auth-form">
                <label for="username">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" autocomplete="username" placeholder="admin" value="<?php echo isset($payload['username']) ? htmlspecialchars($payload['username']) : ''; ?>" required>

                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" autocomplete="current-password" placeholder="admin" required>

                <button type="submit">Giriş Yap</button>
            </form>
        </div>
    </div>
</body>
</html>
