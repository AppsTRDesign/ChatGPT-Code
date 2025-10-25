<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($title ?? 'Web Push Platformu') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropzone@5/dist/min/dropzone.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <style>
        :root {
            --theme-primary: #00a8cc;
            --theme-secondary: #007c91;
        }
        body {
            background: #f2f8fb;
        }
        .navbar, .btn-primary {
            background: var(--theme-primary) !important;
            border-color: var(--theme-primary) !important;
        }
        .btn-outline-primary {
            color: var(--theme-primary);
            border-color: var(--theme-primary);
        }
        .btn-outline-primary:hover {
            background: var(--theme-primary);
            color: #fff;
        }
        .card {
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05);
        }
        .table thead {
            background: var(--theme-secondary);
            color: #fff;
        }
        .dz-default.dz-message {
            border: 2px dashed var(--theme-secondary);
            border-radius: 1rem;
            padding: 2rem;
        }
        input.form-control, select.form-select, textarea.form-control {
            border-radius: 0.8rem;
            border-color: rgba(0, 168, 204, 0.4);
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/">NoaSoft WebPush</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (!empty($user)): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $user['role'] === 'admin' ? '/admin/dashboard' : '/app/dashboard' ?>">Panel</a></li>
                    <li class="nav-item"><a class="nav-link" href="/logout">Çıkış</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/login">Giriş</a></li>
                    <li class="nav-item"><a class="nav-link" href="/register">Üye Ol</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container my-4">
    <?php if (!empty($flash)): ?>
        <script>window.flashMessages = <?= json_encode($flash, JSON_UNESCAPED_UNICODE) ?>;</script>
    <?php endif; ?>
    <?= $content ?? '' ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dropzone@5/dist/min/dropzone.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    (function() {
        if (window.flashMessages) {
            for (const message of window.flashMessages) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    timer: 3500,
                    timerProgressBar: true,
                    icon: message.type,
                    title: message.message,
                    showConfirmButton: false,
                    background: '#e0f7ff',
                });
            }
        }

        document.querySelectorAll('table.datatable').forEach(function(table) {
            $(table).DataTable({ responsive: true, language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json' } });
        });

        document.querySelectorAll('[data-refresh]').forEach(function(button) {
            button.addEventListener('click', function() {
                const target = document.querySelector(button.dataset.refresh);
                if (!target) return;
                fetch(button.dataset.url || window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(resp => resp.json())
                    .then(data => {
                        target.dispatchEvent(new CustomEvent('refresh', { detail: data }));
                    });
            });
        });
    })();
</script>
</body>
</html>
