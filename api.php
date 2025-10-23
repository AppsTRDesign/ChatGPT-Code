<?php
require __DIR__ . '/templates/header.php';
?>
<div class="row g-4">
    <div class="col-12">
        <div class="card p-5">
            <h1 class="display-6 mb-3">API Dokümantasyonu</h1>
            <p class="lead mb-4">NoaSoft QR Menu API ile token tabanlı olarak QR kod oluşturabilir, renkleri ve logoyu özelleştirebilirsiniz. Her başarılı istek aktif paketinizde tanımlı limit ve sürelerden düşer.</p>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="p-4 bg-transparent border rounded-4 h-100">
                        <h2 class="h5">Kimlik Doğrulama</h2>
                        <p class="mb-2">Admin veya müşteri panelinden aldığınız tokenı aşağıdaki yöntemlerden biriyle gönderin:</p>
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
                        <p class="text-white-50 mb-2">POST isteğinde JSON gövdesi, GET isteğinde sorgu parametreleri kullanın.</p>
                        <p class="text-white-50 mb-0">Doğrudan <code>&lt;img src&gt;</code> kullanımı için <code>output=image</code> veya <code>embed=1</code> parametresi ekleyin.</p>
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
  "logo_url": "https://ornek.com/logo.png"
}</code></pre>
            <p class="mb-0 text-white-50">Base64 logo göndermek için <code>logo_upload</code> alanını kullanın.</p>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-4 h-100">
            <h2 class="h4 mb-3">GET İsteği</h2>
            <p class="text-white-50">Doğrudan görüntü almak için aşağıdaki formatı kullanabilirsiniz. Parametreler URL üzerinden sağlanır.</p>
<pre><code>https://qrmenu.noasoft.org/api/v1/qr?
  token=TOKENINIZ&
  data=https%3A%2F%2Fornek.com%2Fmenu&
  color=%230d6efd&
  background=%230b132b&
  embed=1</code></pre>
            <p class="text-white-50">Varsayılan olarak PNG çıktısı döner. JSON cevap almak için <code>&format=json</code> veya <code>&output=json</code> parametrelerini ekleyin.</p>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-4 h-100">
            <h2 class="h4 mb-3">Başarılı Yanıt (JSON)</h2>
<pre><code>{
  "status": "success",
  "image": "iVBORw0KGgo...",
  "mime": "image/png",
  "embed_url": "https://qrmenu.noasoft.org/api/v1/qr?token=TOKEN&data=..."
}</code></pre>
            <p class="text-white-50 mb-0">Dönen <code>image</code> alanı base64 kodlu PNG verisidir. <code>data:image/png;base64,</code> ön eki ile kullanabilirsiniz.</p>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-4 h-100">
            <h2 class="h4 mb-3">Hata Kodları</h2>
            <ul class="mb-0">
                <li><strong>400</strong> - Parametre eksik veya hatalı</li>
                <li><strong>401</strong> - Token geçersiz, paket bulunamadı veya süresi doldu</li>
                <li><strong>429</strong> - Paket limitiniz doldu</li>
                <li><strong>500</strong> - QR oluşturma sırasında hata</li>
            </ul>
        </div>
    </div>
    <div class="col-12">
        <div class="card p-4">
            <h2 class="h4 mb-3">Paket ve Limitler</h2>
            <p class="text-white-50">Her başarılı QR üretimi aboneliğinizin aktif paketinde tanımlanan limitten ve süreden düşer. Paket süresi dolduğunda API erişimi otomatik olarak durur.</p>
            <p class="text-white-50 mb-0">Tokenlarınızı müşteri panelinden pasif hâle getirebilir veya silebilirsiniz. Pasif tokenlarla yapılan istekler reddedilir.</p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/templates/footer.php'; ?>
