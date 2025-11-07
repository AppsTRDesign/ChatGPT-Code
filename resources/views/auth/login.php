<?php ob_start(); ?>
<div class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card glass border-0" style="max-width: 420px; width: 100%;">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <svg width="64" height="64" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="gradAuth" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#38bdf8"/>
                            <stop offset="100%" stop-color="#1d4ed8"/>
                        </linearGradient>
                    </defs>
                    <rect x="4" y="4" width="56" height="56" rx="18" fill="url(#gradAuth)"/>
                    <path d="M20 24h24l-12 24z" fill="#0b1120" opacity="0.4"/>
                    <circle cx="32" cy="24" r="7" fill="#e0f2fe"/>
                </svg>
                <h2 class="h4 mt-3 text-light">NoaSoft Telegram Panel</h2>
                <p class="text-secondary">Admin girişi için bilgilerinizi giriniz</p>
            </div>
            <form data-ajax="true" method="post" action="/admin/login">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <div class="mb-3">
                    <label class="form-label">Kullanıcı Adı</label>
                    <input type="text" name="username" class="form-control form-control-lg" value="<?= old('username') ?>" placeholder="admin">
                </div>
                <div class="mb-3">
                    <label class="form-label">Şifre</label>
                    <input type="password" name="password" class="form-control form-control-lg" placeholder="••••••">
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <a href="/admin/password-reset" class="small text-info">Şifremi unuttum</a>
                </div>
                <button class="btn btn-primary w-100 btn-lg" type="submit">Giriş Yap</button>
            </form>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include resource_path('views/layouts/auth.php');
