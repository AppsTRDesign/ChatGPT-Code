<?php
require __DIR__ . '/templates/header.php';
?>
<div class="row g-4">
    <div class="col-12">
        <div class="card p-5">
            <h1 class="display-6 mb-3">API Dokümantasyonu</h1>
            <p class="lead mb-4">NoaSoft QR Menu API ile token tabanlı olarak QR kod üretebilir, renkleri, arka planı ve logoyu özelleştirebilir ve sonuçları doğrudan web sitenize gömebilirsiniz. Her çağrı aboneliğinizde tanımlı limite göre takip edilir.</p>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="p-4 bg-transparent border rounded-4 h-100">
                        <h2 class="h5">Kimlik Doğrulama</h2>
                        <p class="mb-2">Admin veya müşteri panelinden oluşturduğunuz tokenları aşağıdaki yöntemlerden biriyle gönderin:</p>
                        <ul class="mb-0">
                            <li><code>Authorization: Bearer &lt;TOKEN&gt;</code> başlığı</li>
                            <li>JSON gövdesinde <code>token</code> alanı</li>
                            <li>GET isteklerinde <code>?token=&lt;TOKEN&gt;</code> parametresi</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-4 bg-transparent border rounded-4 h-100">
                        <h2 class="h5">Temel Endpoint</h2>
                        <p class="mb-2"><code>https://qrmenu.noasoft.org/api/v1/qr</code></p>
                        <p class="text-white-50 mb-2">POST isteğinde JSON gövdesi, GET isteğinde sorgu parametreleri kullanın. Her başarılı istek aboneliğinizin aktif paketindeki kullanım hakkından düşer.</p>
                        <p class="text-white-50 mb-0">Doğrudan <code>&lt;img src&gt;</code> kullanımı için <code>output=image</code> veya <code>embed=1</code> parametresi ekleyin. <code>width</code>, <code>height</code>, <code>aspect_ratio</code> ve <code>format</code> parametreleri ile çıktıyı özelleştirebilirsiniz.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-4 h-100">
            <h2 class="h4 mb-3">POST İsteği</h2>
            <p class="text-white-50">JSON gövdesi ile renk, arka plan ve logo bilgilerini gönderebilirsiniz. Logo için URL veya base64 verisi desteklenir.</p>
<pre><code>{
  "token": "TOKENINIZ",
  "data": "https://ornek.com/menu",
  "color": "#0d6efd",
  "background": "#0b132b",
  "background_transparent": false,
  "width": 640,
  "height": 480,
  "aspect_ratio": "4:3",
  "formats": ["png", "svg"],
  "logo_url": "https://ornek.com/logo.png",
  "embed": true
}</code></pre>
            <p class="mb-0 text-white-50">Base64 logo göndermek için <code>logo_upload</code> alanını kullanın. <code>formats</code> alanı birden çok çıktı türünü aynı anda döndürür. <code>aspect_ratio</code> parametresi (ör. <code>1:1</code>, <code>4:3</code>) yüksekliği otomatik hesaplar; <code>custom</code> göndererek serbest değer verebilirsiniz. PNG çıktılarında şeffaf arka plan istiyorsanız <code>background_transparent</code> değerini <code>true</code> yapın.</p>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-4 h-100">
            <h2 class="h4 mb-3">GET İsteği</h2>
            <p class="text-white-50">Doğrudan görüntü almak için aşağıdaki formatı kullanabilirsiniz. Parametreler URL üzerinden sağlanır.</p>
<pre><code>https://qrmenu.noasoft.org/api/v1/qr?
  token=TOKENINIZ&
  data=https%3A%2F%2Fornek.com%2Fmenu&
  width=640&
  aspect_ratio=1:1&
  format=svg&
  output=image&
  background_transparent=true</code></pre>
            <p class="text-white-50">Varsayılan olarak PNG çıktısı döner. JSON cevap almak için <code>&format=json</code> veya <code>&output=json</code> parametrelerini ekleyin; JSON yanıtta tüm seçtiğiniz formatlar base64 olarak döner.</p>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-4 h-100">
            <h2 class="h4 mb-3">HTML İçerisinde Kullanım</h2>
            <p class="text-white-50">Tokenınız ile doğrudan web sitenize gömülebilir bir <code>&lt;img&gt;</code> etiketi oluşturabilirsiniz. Her görüntüleme paket limitinizden otomatik düşer.</p>
<pre><code>&lt;img
  src="https://qrmenu.noasoft.org/api/v1/qr?token=TOKENINIZ&amp;data=https%3A%2F%2Fornek.com"
  alt="Menü QR Kodunuz"
  width="280" /&gt;</code></pre>
            <p class="text-white-50 mb-0">Renk, arka plan veya logo parametrelerini aynı URL üzerinde kullanmaya devam edebilirsiniz.</p>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-4 h-100">
            <h2 class="h4 mb-3">Başarılı Yanıt (JSON)</h2>
<pre><code>{
  "status": "success",
  "downloads": [
    { "format": "png", "mime": "image/png", "data": "iVBORw0KGgo..." },
    { "format": "svg", "mime": "image/svg+xml", "data": "PHN2ZyB4bWxu..." }
  ],
  "embed_url": "https://qrmenu.noasoft.org/api/v1/qr?token=TOKEN&data=...&output=image",
  "remaining": 96
}</code></pre>
            <p class="text-white-50 mb-0"><code>downloads</code> alanında her format base64 olarak döner. <code>remaining</code> değeri mevcut paketinizde kalan hakkınızı gösterir.</p>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-4 h-100">
            <h2 class="h4 mb-3">Hata Kodları</h2>
            <ul class="mb-0">
                <li><strong>400</strong> - Parametre eksik veya hatalı</li>
                <li><strong>401</strong> - Token geçersiz, e-posta doğrulanmamış veya paket bulunamadı</li>
                <li><strong>429</strong> - Paket limitiniz doldu</li>
                <li><strong>500</strong> - QR oluşturma sırasında hata</li>
            </ul>
        </div>
    </div>
    <div class="col-12">
        <div class="card p-4">
            <h2 class="h4 mb-3">Paket ve Limitler</h2>
            <p class="text-white-50">Her başarılı QR üretimi aboneliğinizin aktif paketinde tanımlanan limitten ve süreden düşer. Limitler %50, %25 ve %5 seviyelerine indiğinde sistem e-posta bilgilendirmesi yapar.</p>
            <p class="text-white-50 mb-0">Tokenlarınızı müşteri panelinden pasif hâle getirebilir veya silebilirsiniz. Pasif tokenlarla yapılan istekler reddedilir.</p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/templates/footer.php'; ?>
