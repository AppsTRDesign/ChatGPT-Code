<?php $favicon = branding_favicon(); ?>
<!doctype html>
<html lang="tr" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'Giriş') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <?php if ($favicon): ?>
        <link rel="icon" type="<?= htmlspecialchars($favicon['mime']) ?>" href="<?= htmlspecialchars($favicon['url']) ?>">
    <?php endif; ?>
    <style>
        body { background: radial-gradient(circle at top, #0f172a, #020617); min-height: 100vh; }
        .glass { background: rgba(15, 23, 42, 0.85); border-radius: 24px; border: 1px solid rgba(148, 163, 184, 0.15); box-shadow: 0 20px 60px rgba(2, 6, 23, 0.6); }
    </style>
</head>
<body>
<?= $content ?? '' ?>
<div class="toast-container position-fixed top-0 end-0 p-3"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('form[data-ajax="true"]').forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const formData = new FormData(form);

            (async () => {
                try {
                    const response = await fetch(form.getAttribute('action'), {
                        method: 'POST',
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
                        window.location.href = payload.redirect || '/admin';
                    } else {
                        showToast(payload.message || 'Bir hata oluştu.');
                    }
                } catch (error) {
                    showToast('Sunucu yanıtı alınamadı.');
                }
            })();
        });
    });

    function showToast(message) {
        const container = document.querySelector('.toast-container');
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-bg-danger border-0 show mb-2';
        toast.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }
</script>
</body>
</html>
