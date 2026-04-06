# Task-3 Çıktısı: Auth Hardening

## Uygulanan adımlar
1. Rate-limit altyapısı eklendi (`auth_rate_limits`)
   - login/register/forgot-password için aksiyon bazlı kontrol.
   - pencere (window) + bloklama süresi modeli.
2. Şifre sıfırlama token altyapısı eklendi (`password_reset_tokens`)
   - token hash olarak saklanır.
   - 30 dk süre ve tek kullanımlık tüketim (`used_at`).
3. Session güvenliği güçlendirildi
   - login/logout ve admin login anında `session_regenerate_id(true)`.
   - strict mode/cookie güvenlik ayarları aktif.
4. Güvenlik başlıkları bootstrap'e eklendi
   - `X-Frame-Options: SAMEORIGIN`
   - `X-Content-Type-Options: nosniff`
   - `Referrer-Policy: strict-origin-when-cross-origin`
5. Forgot/reset sayfaları ve route'ları eklendi.

## Dosya etkisi
- `db/migrations/20260405_000003_auth_hardening.sql`
- `src/Services/AuthSecurityService.php`
- `src/Controllers/AuthController.php`
- `src/Core/Auth.php`
- `bootstrap.php`
- `views/auth/forgot.php`
- `views/auth/reset.php`
- `views/auth/login.php`
- `index.php`

## Not
- Şimdilik reset token mail gönderimi yok; dev hızlandırma için token session içinde debug amaçlı gösteriliyor.
- Üretimde token e-posta/SMS ile gönderilmelidir.
