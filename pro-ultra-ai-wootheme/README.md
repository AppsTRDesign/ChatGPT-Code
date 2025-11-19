# Pro Ultra AI WooTheme

Production-ready, AI-powered WordPress + WooCommerce theme with Shopify/Amazon/Apple-inspired hybrid UI, five layout kits, SVG icon families, AI assistants, and AJAX-first commerce flows.

## Kurulum / Setup
1. **Temayı yükleyip etkinleştirin.** Etkinleştirme sonrası görünen **Setup Wizard** bildirimi ile sihirbazı açın.
2. Sihirbaz adımlarında sunucu gereksinimlerini doğrulayın, WooCommerce ve önerilen eklentileri kurup etkinleştirin, demo ürünler/sayfalar/menüleri içe aktarın ve Ana Sayfa’yı statik sayfa olarak atayın.
3. **Tema Ayarları → AI Ayarları** bölümüne DeepSeek/ChatGPT anahtarlarını girin; AI ürün yazarı, AI görsel işleme, AI satış asistanı, AI sorgu motoru ve AI raporlama modüllerini aç/kapa.
4. **Tasarım & Layout** sekmesinden (Minimal, Dark, Gradient, Classic, Luxury) seçiminizi yapın; **Renk Ayarları** ile marka renklerini belirleyin. SVG ikon seti ve body class seçime göre otomatik güncellenir.
5. WooCommerce para birimi, kargo, vergiler ve ödeme yöntemlerini tamamlayın. Gelişmiş checkout UX paketi PayTR, iyzico, Stripe, WooCommerce Payments ve PayPal görselleriyle uyumlu çalışır.

## Ana Özellikler
- AI ürün içerik yazarı (Yoast / Rank Math / AIOSEO alanlarına otomatik SEO başlık + meta açıklama yazma), AI görsel editörü, AI satış asistanı/chatbot, AI sorgu motoru ve AI raporlama + Chart.js PDF çıktıları.
- AJAX-first deneyim: Amazon/Trendyol benzeri sol filtre paneli (varyasyonlara göre dinamik), wishlist/favori/beğeni, mini-cart, AJAX sepet, checkout kupon işlemleri, gelişmiş profil sekmeleri.
- 5 layout kiti ve layout’a özel ödeme/kargo/UI SVG ikon setleri; premium header/footer, sticky özetler, Apple tarzı mobil sheet menüleri.
- Çoklu dil (TR/EN) desteği, inline çeviri editörü, JSON/PO yükleme ve body class’a göre dil sınıfı ekleme.
- Setup wizard, demo import (örnek ürünler, sayfalar, menüler), tema paneli, özel login/register şablonları ve AI toasts dahil tam çeviri seti.

## Demo & İçerik
- Sihirbaz, “Ana Sayfa - Mağaza” statik sayfası, Mağaza, Sepet, Ödeme, Hesap, Favoriler/Wishlist/Beğeniler, Blog ve İletişim sayfalarını otomatik oluşturur ve menülere ekler.
- Hero alanı ve örnek featured ürün shortcodu, layout seçimine göre premium tasarımla gelir; WooCommerce sayfa atamaları otomatik yapılır.

## Minimum Gereksinimler
- WordPress 6.x, WooCommerce güncel
- PHP 8.0+ (cURL, JSON, mbstring etkin, dosya yazma izinleri)
- Modern tarayıcı, HTTPS önerilir

## Geliştirici Notları
- Kod yapısı OOP, nonce + sanitize kontrolleri ile güçlendirilmiş; SEO anahtarları hiçbir zaman frontende sızdırılmaz.
- `assets/dist` altında minify edilmiş CSS/JS; geliştirme için `SCRIPT_DEBUG` kullanabilir, kaynak dosyalar `assets/js` ve `assets/css` altındadır.
- WooCommerce override başlıkları 10.x sürüm bilgisiyle günceldir; body class’lar view ve layout tercihlerini yansıtır.
