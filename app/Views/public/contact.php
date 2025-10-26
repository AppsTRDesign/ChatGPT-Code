<?php include __DIR__ . '/../partials/head.php'; ?>
<nav class="navbar navbar-expand-lg shadow-sm" style="background: linear-gradient(135deg, #0a4d68, #00b8a9);">
    <div class="container">
        <a class="navbar-brand text-white fw-bold" href="/">NoaSoft Web Push</a>
        <div class="collapse navbar-collapse show">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link text-white" href="/api-guide">API Rehberi</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="/api-docs">API Dokümanları</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="/login">Giriş Yap</a></li>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h1 class="h4 mb-0">İletişim</h1>
                </div>
                <div class="card-body">
                    <p class="text-muted">Destek, entegrasyon ve teklif talepleriniz için bize ulaşın.</p>
                    <form data-ajax="true" data-endpoint="/contact" id="public-contact-form">
                        <div class="mb-3">
                            <label class="form-label">E-posta</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konu</label>
                            <input type="text" name="subject" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mesaj</label>
                            <textarea name="message" class="form-control" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Mesaj Gönder</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<footer class="py-3 border-top mt-auto bg-dark text-white">
    <div class="container text-center small">
        © <?= date('Y') ?> NoaSoft - Web Push Platformu
    </div>
</footer>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('public-contact-form');
        if (!form) { return; }
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const formData = new FormData(form);
            fetch('/contact', {
                method: 'POST',
                body: formData
            })
                .then((response) => response.json())
                .then((payload) => {
                    if (payload.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Teşekkürler', text: 'Mesajınız iletildi.' });
                        form.reset();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Hata', text: payload.message || 'Mesaj gönderilemedi.' });
                    }
                });
        });
    });
</script>
