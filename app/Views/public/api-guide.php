<?php include __DIR__ . '/../partials/head.php'; ?>
<nav class="navbar navbar-expand-lg shadow-sm" style="background: linear-gradient(135deg, #0a4d68, #00b8a9);">
    <div class="container">
        <a class="navbar-brand text-white fw-bold" href="/">NoaSoft Web Push</a>
        <div class="collapse navbar-collapse show">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link text-white" href="/contact">İletişim</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="/api-docs">API Dokümanları</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="/login">Giriş Yap</a></li>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-5">
    <h1 class="display-6 mb-4">API Kullanım Rehberi</h1>
    <p class="lead text-muted">Token oluşturma, bildirim gönderme ve raporlama uç noktaları için adım adım rehber.</p>
    <section class="mb-5">
        <h2 class="h4">1. Kimlik Doğrulama</h2>
        <p>API anahtarlarınızı <strong>Müşteri Paneli &gt; API</strong> bölümünden oluşturun. Her anahtar için bildirim gönderme ve token kaydetme izinlerini yönetebilirsiniz.</p>
        <pre class="bg-dark text-white p-3 rounded">Authorization: Bearer &lt;API_KEY&gt;</pre>
    </section>
    <section class="mb-5">
        <h2 class="h4">2. Token Kaydetme</h2>
        <p><code>/api/tokens</code> uç noktasını kullanarak tarayıcı token'larını kaydedin.</p>
        <pre class="bg-dark text-white p-3 rounded">POST /api/tokens
{
    "api_key": "API_KEY",
    "token": "WEB_PUSH_TOKEN",
    "endpoint": "https://fcm.googleapis.com/fcm/send/...",
    "site_identifier": "site_123",
    "language": "tr",
    "user_agent": navigator.userAgent
}</pre>
    </section>
    <section class="mb-5">
        <h2 class="h4">3. Bildirim Gönderme</h2>
        <p>Site, dil, platform gibi filtrelerle hedefleme yapabilirsiniz.</p>
        <pre class="bg-dark text-white p-3 rounded">POST /api/notifications
{
    "api_key": "API_KEY",
    "title": "Yeni Kampanya",
    "message": "Sepette %20 indirim!",
    "target_url": "https://ornek.com/kampanya",
    "site_identifier": "site_123",
    "language": "tr",
    "platform": "Android",
    "duration_type": "timed",
    "duration_value": 2,
    "duration_unit": "hours"
}</pre>
    </section>
    <section class="mb-5">
        <h2 class="h4">4. Raporlama</h2>
        <p><code>/api/inbox</code> ve <code>/api/receipts</code> uç noktaları ile bildirimlerin alındığını ve kullanıcı etkileşimlerini raporlayın.</p>
    </section>
</main>
<footer class="py-3 border-top mt-auto bg-dark text-white">
    <div class="container text-center small">
        © <?= date('Y') ?> NoaSoft - Web Push Platformu
    </div>
</footer>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
