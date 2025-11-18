# AI Commerce Pro (WordPress + WooCommerce Theme)

Bu depo, WooCommerce ile tam uyumlu, AI destekli örnek bir tema sunar. Tasarım hafif, SEO dostu ve hız skorunu yüksek tutacak şekilde hazırlanmıştır. Tema; ürün içerik üretimi, görsel iyileştirme, satış danışmanı, karşılaştırma önerileri, favori/wishlist, gelişmiş profil sayfası, login/register şablonları, demo kurulum sihirbazı ve davranış analitiği için kancaları içerir.

## Özellik Özeti
- **AI içerik üretimi:** Ürün başlığı, kısa açıklama, anahtar kelime ve özellik maddeleri için DeepSeek veya ChatCPT API’lerine istek gönderme kancaları; ürün düzenleme ekranında otomatik üretim checkbox’ı.
- **Görsel işleme kancası:** Arka plan kaldırma ve yeniden boyutlandırma kontrolü için ayar alanı ve meta kutu; API isteği başarılı olursa yeni görsel otomatik kaydedilir.
- **Satış asistanı:** AJAX tabanlı sohbet son noktası; SKU durumu, kargo sorgusu, karşılaştırma ve davranışa göre öneri için bağlam hazırlığı ve yüzer sohbet paneli.
- **Analitik & öneriler:** Ziyaret, favori, popülerlik metrikleri; AI rapor ve PDF/HTML export (Dompdf varsa) için veri modeli ve AJAX butonu.
- **Lisans denetimi:** `https://wpapi.noasoft.org` üzerinden domain bazlı lisans doğrulama kancası.
- **Çoklu dil (TR/EN):** JSON tabanlı sözlük dosyaları ve tema ayarından dil seçimi.
- **Beş varyant konsepti:** `classic`, `modern`, `minimal`, `neo`, `contrast` seçenekleri tema ayarlarından değiştirilebilir.
- **WooCommerce entegrasyonu:** Ürün arşivi, kategori, tek ürün, sepet ve checkout şablonları; AJAX sepete ekleme, popüler ürün vitrinleri, varyasyon/filtre formu.
- **Kurulum sihirbazı kancası:** Gerekli eklentileri kurma/demoyu içeri aktarma için aşama listesi (progress bar için `ai_commerce_progress_steps`) ve yönetici arayüzünden tek tık kurulum düğmesi.
- **Favori/Wishlist/Beğeni:** Shortcode tabanlı sayfalar, AJAX toggle endpoint’leri ve popüler ürün listeleri.
- **Profil & Auth sayfaları:** `[aicart_profile]` ve `[aicart_auth]` kısa kodlarıyla gelişmiş profil, giriş, kayıt ve şifre sıfırlama görünümü.

## Dosya Yapısı
```
ai-woocommerce-theme/
├─ style.css                # Tema bildirimi ve temel tasarım değişkenleri
├─ functions.php            # Tema bootstrap, kurulum sihirbazı kancası
├─ header.php, footer.php   # Basit layout ve dil seçici
├─ index.php                # Anasayfa: AI önerileri, popüler kartlar, asistan formu
├─ assets/frontend.js       # AJAX sepete/favori/wishlist/like ve filtreler
├─ page-*.php               # Favoriler, wishlist, beğeniler, popüler listeler, profil, auth sayfaları
├─ woocommerce/
│  ├─ archive-product.php   # Arşiv/kategori grid + filtre
│  ├─ single-product.php    # Galeri, varyasyon, AI öneri, benzer ürünler, yorumlar
│  ├─ cart/cart.php         # Ajax uyumlu sepet görünümü
│  ├─ checkout/form-checkout.php # PayTR/Iyzico/Stripe mesajlı checkout
│  └─ content-product.php   # Loop kartı + beğeni/favori/wishlist butonları
└─ inc/
   ├─ setup.php             # Tema destekleri, menü, widget
   ├─ assets.php            # Stil/script enqueue, JS localization
   ├─ options.php           # Tema ayarları + tek tık kurulum ilerleme barı
   ├─ ai-services.php       # AI içerik/sohbet istekleri, lisans doğrulama, AJAX handler
   ├─ analytics.php         # Ziyaret/favori/wishlist/beğeni izleme, AJAX toggle
   └─ templates.php         # Shortcode tabanlı gridler, profil ve auth şablonları
└─ languages/
   ├─ tr.json               # Türkçe sözlük
   └─ en.json               # İngilizce sözlük
```

## Kurulum
1. Depodaki `ai-woocommerce-theme` klasörünü `wp-content/themes/` dizinine kopyalayın.
2. WordPress yönetiminde **Görünüm → Temalar** bölümünden “AI Commerce Pro” temasını etkinleştirin.
3. **Görünüm → AI Commerce** sayfasında aşağıdaki ayarları yapın:
   - API sağlayıcısı (DeepSeek veya ChatCPT) ve anahtarı
   - Arka plan rengi/yeniden boyut genişliği, otomatik içerik ve satış asistanı checkbox’ları
   - Kargo/SKU sorgu endpoint’i
   - Renk ve varyant seçimi
   - Lisans anahtarını girip kaydedin (domain bazlı doğrulama yapılır).
   - Dil (TR/EN) seçimini yapın.
4. WooCommerce’in kurulu ve yapılandırılmış olduğundan emin olun (ödeme sağlayıcıları için PayTR, Iyzico, Stripe eklentileri tek tık kurulumda da denenir).
5. **Görünüm → AI Commerce** sayfasında tek tık kurulum düğmesine basarak `plugins → demo → menus → license → complete` aşamalarını progress bar üzerinde takip edin (arka planda `aicart_run_installer` AJAX çağrılır ve menüler konumlandırılır).

## AI Akış Örnekleri
- **Ürün içerik üretimi:** Ürün kaydı sırasında `AICart\AI\generate_product_content( $product_id )` çağrısı yaparak SEO uyumlu başlık, kısa açıklama, anahtar kelime ve özellik maddelerini meta alanlarına yazabilirsiniz; meta kutudaki checkbox otomatik çalıştırır.
- **Görsel iyileştirme:** Ürün düzenleme ekranında arka plan temizleme ve yeniden boyutlandırma checkbox’ı öne çıkan görseli API’ye gönderir, yanıt yeni medya öğesi olarak eklenir.
- **Satış asistanı:** `aicart_ai_assistant` AJAX endpoint’ine `prompt` ve `mode` (`chat`, `recommend`, `compare`, `order`) göndererek kullanıcıya uygun öneri/karşılaştırma/kargo-SKU yanıtı alın. Davranış verilerini `AICart\Analytics` metrikleriyle bağlayabilirsiniz.
- **Karşılaştırma & kargo formları:** Tek ürün sayfasındaki formlar `aicart_compare_products` ve `aicart_order_lookup` endpoint’lerini çağırır; sonuçlar inline render edilir.
- **Sepete ekleme (AJAX):** `.ai-ajax-cart` butonu `aicart_add_to_cart` endpoint’ini kullanır; mini sepet fragmenti otomatik güncellenir.
- **Favori/Wishlist/Beğeni:** `.ai-fav-toggle` ve `.ai-like-toggle` butonları ilgili AJAX endpoint’lerini çağırır, `analytics.php` sayaçlarını günceller.
- **Arşiv filtreleri:** `archive-product.php` içindeki `#ai-filter-form` fiyat ve attribute filtrelerini URL parametreleriyle uygular.

## Performans ve SEO Notları
- Temanın renk/token sistemi `:root` değişkenleri ile çalışır, minimal CSS kullanır.
- Başlık etiketleri, breadcrumbs, ürün şeması gibi ek SEO özellikleri için Yoast/RankMath gibi eklentilerle birlikte çalışacak kancalar ayrılmıştır.
- Statik varlıklar tek bir JS ve CSS dosyasına indirgenmiştir; üretim ortamında minify/concat önerilir.

## Lisans Sistemi
- `AICart\AI\license_valid()` fonksiyonu, kayıtlı lisans anahtarını `https://wpapi.noasoft.org/license/verify` endpoint’inde domain bilgisi ile doğrular.
- Yanıt 1 saat önbelleğe alınır; başarısız doğrulamada AI özellikleri devre dışı kalır.

## Çeviri
- Dil JSON dosyaları `languages/` altında tutulur. `ai_commerce_language` ayarı TR/EN arasında geçiş yapar; ek dillere genişletilebilir.

## Notlar
- Bu tema bir iskelet örneğidir; gerçek AI görüntü işleme, PDF raporlama, kullanıcı davranışından öğrenen öneri motoru ve ödeme entegrasyonları için ilgili API/eklenti tarafında geliştirme yapılmalıdır.
- Kod yapısı AJAX ve modüler kancalarla genişletilebilir olacak şekilde hazırlanmıştır.
