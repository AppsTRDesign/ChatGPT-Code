<?php include __DIR__ . '/../partials/head.php'; ?>
<nav class="navbar navbar-expand-lg shadow-sm" style="background: linear-gradient(135deg, #0a4d68, #00b8a9);">
    <div class="container">
        <a class="navbar-brand text-white fw-bold" href="/">NoaSoft Web Push</a>
        <div class="collapse navbar-collapse show">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link text-white" href="/contact">İletişim</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="/api-guide">API Rehberi</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="/login">Giriş Yap</a></li>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-5">
    <h1 class="display-6 mb-4">Web Push Entegrasyon Dokümanı</h1>
    <p class="lead text-muted">Servis worker, istemci betikleri ve izin yönetimi adımlarını takip ederek dakikalar içinde entegre olun.</p>
    <section class="mb-5">
        <h2 class="h4">1. Servis Worker Dosyası</h2>
        <p><code>https://webpush.noasoft.org/sw.js</code> dosyasını kendi alan adınızın kök dizinine kopyalayın.</p>
    </section>
    <section class="mb-5">
        <h2 class="h4">2. İstemci Betiği</h2>
        <p>Aşağıdaki betiği sitenizin <code>&lt;head&gt;</code> alanına ekleyin. <code>DATA-SITE</code> parametresine panelden oluşturduğunuz site anahtarını yazın.</p>
        <pre class="bg-dark text-white p-3 rounded">&lt;script src="https://webpush.noasoft.org/public/js/webpush-client.js" data-site="site_123" defer&gt;&lt;/script&gt;</pre>
    </section>
    <section class="mb-5">
        <h2 class="h4">3. İzin Yönetimi</h2>
        <p>Betik otomatik olarak izin isteyecek ve token kayıtlarını tamamlayacaktır. Manuel kontrol için aşağıdaki metotları kullanabilirsiniz.</p>
        <pre class="bg-dark text-white p-3 rounded">window.NoaPush.requestPermission();
window.NoaPush.subscribe();
window.NoaPush.unsubscribe();</pre>
    </section>
    <section class="mb-5">
        <h2 class="h4">4. Örnek Bildirim Göstergesi</h2>
        <pre class="bg-dark text-white p-3 rounded">window.NoaPush.preview({
    title: 'Hoş Geldiniz',
    message: 'İlk kampanyanızı oluşturun!',
    target_url: 'https://ornek.com'
});</pre>
    </section>
</main>
<footer class="py-3 border-top mt-auto bg-dark text-white">
    <div class="container text-center small">
        © <?= date('Y') ?> NoaSoft - Web Push Platformu
    </div>
</footer>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
