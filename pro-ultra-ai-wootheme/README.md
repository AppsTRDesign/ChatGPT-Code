# Pro Ultra AI WooTheme

A production-ready, AI-powered WordPress + WooCommerce theme featuring five layout kits, AJAX-first UX, AI assistants, SVG icon system, enhanced checkout, and onboarding wizard.

## Kurulum
1. **Temayı yükleyip etkinleştirin.**
2. **Setup Wizard** bildirimini takip ederek sunucu gereksinimlerini doğrulayın, WooCommerce eklentisini kurun/etkinleştirin ve demo içeriği yükleyin.
3. **Tema Ayarları → AI Ayarları** bölümüne DeepSeek/ChatGPT API anahtarlarınızı girin, aktif modülleri açın ve dil/çıkış ayarlarını yapılandırın.
4. **Tasarım & Layout** sekmesinden istediğiniz tasarım kitini seçin; **Renk Ayarları** ile markanıza uygun renkleri belirleyin.
5. WooCommerce temel ayarlarını (para birimi, kargo, vergiler, ödeme yöntemleri) tamamlayın.

## Ana Özellikler
- AI ürün içerik yazarı, AI görsel editörü, AI satış asistanı, AI sorgu motoru ve AI raporlama modülleri
- AJAX-first deneyim: sepet, filtreleme, mini-cart, favoriler/wishlist/beğeni, checkout kupon işlemleri
- 5 farklı layout: Minimal White, Dark Future, Gradient Modern, Classic Shop, Luxury Premium Gold
- Layout’a özel SVG ikon setleri (ödeme, kargo, UI) ve gelişmiş checkout UX paketi
- Çoklu dil (TR/EN) desteği, inline çeviri editörü ve JSON/PO yükleme
- Setup wizard, demo import, tema yönetim paneli, gelişmiş profil ve özel login/register şablonları

## Minimum Gereksinimler
- WordPress 6.x
- WooCommerce güncel sürüm
- PHP 8.0+ (cURL, JSON, mbstring etkin)
- Modern tarayıcı ve HTTPS önerilir

## Demo & Layout Notları
- Setup wizard, örnek ürünler, sayfalar ve menüleri yükler; Anasayfa’yı statik sayfa olarak ayarlar.
- Layout seçimi body class ve SVG setini günceller; renk değişkenleri Renk Ayarları sekmesinden güncellenebilir.

## Destek ve Geliştirme
- Kodlar PSR uyumlu yapıda, nonce ve sanitize kontrolleri ile güvenli hale getirilmiştir.
- Minify edilmiş varlıklar `assets/dist` altında bulunur; debug için `SCRIPT_DEBUG` etkinleştirilebilir.
