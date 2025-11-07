<?php
use App\Support\Session;
$flashes = Session::allFlashes();
?>
<!doctype html>
<html lang="tr" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'Telegram Bot Paneli') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css">
    <style>
        body { background: radial-gradient(circle at top, #141a2a, #0b0f1a); min-height: 100vh; }
        header.navbar { backdrop-filter: blur(12px); background: rgba(15, 23, 42, 0.85); }
        .card { background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(148, 163, 184, 0.1); box-shadow: 0 20px 45px rgba(2, 6, 23, 0.45); }
        .glass { background: rgba(15, 23, 42, 0.72); border-radius: 18px; box-shadow: 0 10px 30px rgba(2, 6, 23, 0.5); border: 1px solid rgba(148, 163, 184, 0.15); }
        .toast-container { z-index: 1200; }
        .brand-logo { width: 36px; height: 36px; }
        .nav-link.active { font-weight: 600; color: #38bdf8 !important; }
    </style>
</head>
<body>
<header class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="/admin">
            <svg class="brand-logo me-2" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#38bdf8"/>
                        <stop offset="100%" stop-color="#1e3a8a"/>
                    </linearGradient>
                </defs>
                <rect x="2" y="2" width="60" height="60" rx="18" fill="url(#grad)"/>
                <path d="M18 32c6 0 9-10 14-10s8 10 14 10-8 14-14 14-8-14-14-14z" fill="#0b1120" opacity="0.55"/>
                <path d="M22 30c4 0 6-6 10-6s6 6 10 6-6 9-10 9-6-9-10-9z" fill="#e0f2fe"/>
            </svg>
            <span class="fw-semibold">NoaSoft Telegram</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], '/admin') && !str_contains($_SERVER['REQUEST_URI'], 'phones') && !str_contains($_SERVER['REQUEST_URI'], 'settings') && !str_contains($_SERVER['REQUEST_URI'], 'members') && !str_contains($_SERVER['REQUEST_URI'], 'messaging') ? ' active' : '' ?>" href="/admin">Panel</a></li>
                <li class="nav-item"><a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], 'phones') ? ' active' : '' ?>" href="/admin/phones">Telefonlar</a></li>
                <li class="nav-item"><a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], 'members') ? ' active' : '' ?>" href="/admin/members">Üyeler</a></li>
                <li class="nav-item"><a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], 'messaging') ? ' active' : '' ?>" href="/admin/messaging">Mesajlaşma</a></li>
                <li class="nav-item"><a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], 'message-templates') ? ' active' : '' ?>" href="/admin/message-templates">Şablonlar</a></li>
                <li class="nav-item"><a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], 'services') ? ' active' : '' ?>" href="/admin/services">Servisler</a></li>
                <li class="nav-item"><a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], 'settings') ? ' active' : '' ?>" href="/admin/settings">Ayarlar</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/logout">Çıkış</a></li>
            </ul>
        </div>
    </div>
</header>
<main class="container py-4">
    <?php if (!empty($title)): ?>
        <div class="d-flex align-items-center mb-4">
            <h1 class="h4 text-light mb-0 me-3"><?= htmlspecialchars($title) ?></h1>
        </div>
    <?php endif; ?>
    <?= $content ?? '' ?>
</main>
<div class="toast-container position-fixed top-0 end-0 p-3">
    <?php foreach ($flashes as $type => $message): ?>
        <div class="toast align-items-center text-bg-<?= $type === 'error' ? 'danger' : 'success' ?> border-0 show" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <?= htmlspecialchars($message) ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>
<script>
    document.querySelectorAll('form[data-ajax="true"]').forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const action = form.getAttribute('action') || window.location.pathname;
            const method = (form.getAttribute('method') || 'POST').toUpperCase();
            const formData = new FormData(form);

            (async () => {
                try {
                    const response = await fetch(action, {
                        method,
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });

                    let payload = null;
                    const contentType = response.headers.get('Content-Type') || '';

                    if (contentType.includes('application/json')) {
                        try {
                            payload = await response.json();
                        } catch (error) {
                            payload = null;
                        }
                    } else {
                        const text = await response.text();
                        if (text.trim() !== '') {
                            payload = {
                                status: response.ok ? 'success' : 'error',
                                message: text.trim(),
                            };
                        }
                    }

                    if (!payload) {
                        throw new Error('empty-response');
                    }

                    if (payload.status === 'success') {
                        if (payload.message) {
                            showToast('success', payload.message);
                        }
                        if (payload.redirect) {
                            window.location.href = payload.redirect;
                        } else if (payload.reload) {
                            window.location.reload();
                        }
                    } else {
                        showToast('danger', payload.message || 'Bir hata oluştu.');
                    }
                } catch (error) {
                    showToast('danger', 'Sunucu yanıtı alınamadı.');
                }
            })();
        });
    });

    function showToast(type, message) {
        const container = document.querySelector('.toast-container');
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0 show mb-2`;
        toast.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }
</script>
</body>
</html>
