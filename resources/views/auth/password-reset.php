<?php ob_start(); ?>
<div class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card glass border-0" style="max-width: 420px; width: 100%;">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <svg width="64" height="64" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="gradReset" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#f97316"/>
                            <stop offset="100%" stop-color="#dc2626"/>
                        </linearGradient>
                    </defs>
                    <rect x="4" y="4" width="56" height="56" rx="18" fill="url(#gradReset)"/>
                    <path d="M32 18l12 12-12 12-12-12z" fill="#0b1120" opacity="0.35"/>
                    <circle cx="32" cy="32" r="9" fill="#fffbeb"/>
                </svg>
                <h2 class="h4 mt-3 text-light">Şifre Sıfırlama</h2>
                <p class="text-secondary">E-postanıza sıfırlama bağlantısı gönderilecektir.</p>
            </div>
            <form data-ajax="true" method="post" action="/admin/password-reset">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <div class="mb-3">
                    <label class="form-label">E-posta</label>
                    <input type="email" name="email" class="form-control form-control-lg" placeholder="admin@example.com">
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <a href="/admin/login" class="small text-info">Girişe geri dön</a>
                </div>
                <button class="btn btn-primary w-100 btn-lg" type="submit">Bağlantı Gönder</button>
            </form>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include resource_path('views/layouts/auth.php');
