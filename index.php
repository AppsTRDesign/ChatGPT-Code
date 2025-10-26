<?php
require __DIR__ . '/functions.php';

$path = rtrim(get_request_path(), '/');
if ($path === '') {
    $path = '/';
}

switch (true) {
    case $path === '/':
        if (!is_logged_in()) {
            redirect('login');
            break;
        }
        redirect('dashboard');
        break;

    case $path === '/login' && $_SERVER['REQUEST_METHOD'] === 'POST':
        handle_login();
        break;

    case $path === '/login':
        if (is_logged_in()) {
            redirect('dashboard');
        }
        render_login();
        break;

    case $path === '/logout':
        logout();
        redirect('login');
        break;

    case $path === '/dashboard':
        require_login();
        render_dashboard();
        break;

    case $path === '/upload' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require_login();
        handle_upload();
        break;

    case preg_match('#^/file/(\\d+)-#', $path, $matches):
        require_login();
        $id = (int) $matches[1];
        render_file_detail($id);
        break;

    case preg_match('#^/download/(\\d+)$#', $path, $matches):
        require_login();
        $id = (int) $matches[1];
        handle_download($id, false);
        break;

    case preg_match('#^/preview/(\\d+)$#', $path, $matches):
        require_login();
        $id = (int) $matches[1];
        handle_download($id, true);
        break;

    case preg_match('#^/delete/(\\d+)$#', $path, $matches) && $_SERVER['REQUEST_METHOD'] === 'POST':
        require_login();
        handle_delete((int) $matches[1]);
        break;

    case preg_match('#^/edit/(\\d+)$#', $path, $matches) && $_SERVER['REQUEST_METHOD'] === 'POST':
        require_login();
        handle_edit((int) $matches[1]);
        break;

    default:
        http_response_code(404);
        echo render_layout('Sayfa bulunamadı', '<div class="alert alert-danger">İstediğiniz sayfa bulunamadı.</div>');
        break;
}

die();

function render_login(): void
{
    $token = generate_csrf_token();
    $action = base_url('login');
    $content = <<<HTML
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h4 mb-4 text-center">Dosya Deposu Giriş</h1>
                    <form method="post" action="{$action}" novalidate>
                        <input type="hidden" name="csrf_token" value="{$token}">
                        <div class="mb-3">
                            <label for="username" class="form-label">Kullanıcı Adı</label>
                            <input type="text" class="form-control" id="username" name="username" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Parola</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
HTML;

    echo render_layout('Giriş', $content, ['hide_nav' => true]);
}

function handle_login(): void
{
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        echo render_layout('Hata', '<div class="alert alert-danger">Geçersiz CSRF token.</div>', ['hide_nav' => true]);
        return;
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (verify_credentials($username, $password)) {
        login($username);
        redirect('dashboard');
        return;
    }

    $token = generate_csrf_token();
    $action = base_url('login');
    $error = '<div class="alert alert-danger">Kullanıcı adı veya parola hatalı.</div>';
    $content = <<<HTML
    {$error}
    <form method="post" action="{$action}">
        <input type="hidden" name="csrf_token" value="{$token}">
        <div class="mb-3">
            <label for="username" class="form-label">Kullanıcı Adı</label>
            <input type="text" class="form-control" id="username" name="username" required autofocus value="" autocomplete="username">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Parola</label>
            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
    </form>
HTML;

    echo render_layout('Giriş', '<div class="row justify-content-center"><div class="col-md-4"><div class="card shadow-sm"><div class="card-body"><h1 class="h4 mb-4 text-center">Dosya Deposu Giriş</h1>' . $content . '</div></div></div></div>', ['hide_nav' => true]);
}

function render_dashboard(): void
{
    $token = generate_csrf_token();
    $files = get_all_files();
    $stats = get_storage_stats();

    $totalFiles = safe_output((string) $stats['total_files']);
    $totalSize = safe_output(format_bytes((int) $stats['total_size']));
    $statsHtml = '<div class="row mb-4">'
        . '<div class="col-md-4"><div class="card shadow-sm"><div class="card-body text-center"><div class="text-muted">Dosya Sayısı</div><div class="display-6">' . $totalFiles . '</div></div></div></div>'
        . '<div class="col-md-4"><div class="card shadow-sm"><div class="card-body text-center"><div class="text-muted">Toplam Boyut</div><div class="display-6">' . $totalSize . '</div></div></div></div>'
        . '</div>';

    $uploadAction = base_url('upload');
    $uploadForm = <<<HTML
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3">Dosya Yükle</h2>
            <form id="upload-form" action="{$uploadAction}" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="{$token}">
                <div class="mb-3">
                    <label for="file" class="form-label">Dosya Seç</label>
                    <input type="file" class="form-control" id="file" name="file" required>
                </div>
                <div id="drop-zone" class="border border-2 border-dashed rounded p-5 text-center mb-3">
                    <p class="text-muted mb-0">Dosyayı buraya sürükleyip bırakın veya yukarıdan seçin.</p>
                </div>
                <button type="submit" class="btn btn-success">Yükle</button>
            </form>
        </div>
    </div>
HTML;

    $rows = '';
    foreach ($files as $file) {
        $id = (int) $file['id'];
        $slug = create_slug($file['filename']);
        $detailUrl = base_url('file/' . $id . '-' . $slug);
        $downloadUrl = base_url('download/' . $id);
        $previewUrl = base_url('preview/' . $id);
        $editUrl = base_url('edit/' . $id);
        $deleteUrl = base_url('delete/' . $id);
        $formattedSize = format_bytes((int) $file['size']);
        $filenameEscaped = safe_output($file['filename']);
        $uploadedAt = safe_output(date('d.m.Y H:i', strtotime($file['uploaded_at'])));

        $rows .= <<<HTML
            <tr>
                <td>{$id}</td>
                <td>
                    <form action="{$editUrl}" method="post" class="d-flex align-items-center gap-2">
                        <input type="hidden" name="csrf_token" value="{$token}">
                        <input type="text" name="filename" value="{$filenameEscaped}" class="form-control form-control-sm" required>
                        <button class="btn btn-sm btn-outline-primary" type="submit">Kaydet</button>
                    </form>
                </td>
                <td>{$formattedSize}</td>
                <td>{$file['type']}</td>
                <td>{$uploadedAt}</td>
                <td>
                    <a class="btn btn-sm btn-outline-secondary" href="{$detailUrl}">Görüntüle</a>
                    <a class="btn btn-sm btn-outline-success" href="{$downloadUrl}">İndir</a>
                    <form action="{$deleteUrl}" method="post" class="d-inline">
                        <input type="hidden" name="csrf_token" value="{$token}">
                        <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Dosyayı silmek istediğinize emin misiniz?');">Sil</button>
                    </form>
                </td>
            </tr>
        HTML;
    }

    if ($rows === '') {
        $rows = '<tr><td colspan="6" class="text-center text-muted">Henüz dosya yüklenmedi.</td></tr>';
    }

    $table = <<<HTML
    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3">Dosya Listesi</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Dosya Adı</th>
                            <th>Boyut</th>
                            <th>Tür</th>
                            <th>Yüklenme Tarihi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$rows}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
HTML;

    $content = $statsHtml . $uploadForm . $table;

    $scripts = <<<HTML
    <script>
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file');
    const form = document.getElementById('upload-form');

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.add('bg-light');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('bg-light');
        });
    });

    dropZone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length) {
            fileInput.files = files;
        }
    });

    form.addEventListener('submit', () => {
        form.classList.add('was-validated');
    });
    </script>
HTML;

    echo render_layout('Yönetim Paneli', $content, ['scripts' => $scripts]);
}

function handle_upload(): void
{
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        echo render_layout('Hata', '<div class="alert alert-danger">Geçersiz CSRF token.</div>');
        return;
    }

    if (!isset($_FILES['file'])) {
        echo render_layout('Hata', '<div class="alert alert-danger">Dosya bulunamadı.</div>');
        return;
    }

    try {
        $id = store_file($_FILES['file'], $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $file = get_file($id);
        if ($file) {
            $slug = create_slug($file['filename']);
            redirect('file/' . $id . '-' . $slug);
            return;
        }
    } catch (Throwable $e) {
        $message = safe_output($e->getMessage());
        echo render_layout('Hata', '<div class="alert alert-danger">' . $message . '</div>');
        return;
    }

    redirect('dashboard');
}

function render_file_detail(int $id): void
{
    $file = get_file($id);
    if (!$file) {
        http_response_code(404);
        echo render_layout('Dosya bulunamadı', '<div class="alert alert-danger">Dosya bulunamadı.</div>');
        return;
    }

    $token = generate_csrf_token();
    $downloadUrl = base_url('download/' . $file['id']);
    $previewUrl = base_url('preview/' . $file['id']);
    $deleteUrl = base_url('delete/' . $file['id']);

    $details = '<div class="card shadow-sm mb-4"><div class="card-body">'
        . '<h2 class="h5 mb-3">Dosya Bilgileri</h2>'
        . '<dl class="row mb-0">'
        . '<dt class="col-sm-3">Dosya Adı</dt><dd class="col-sm-9">' . safe_output($file['filename']) . '</dd>'
        . '<dt class="col-sm-3">Boyut</dt><dd class="col-sm-9">' . format_bytes((int) $file['size']) . '</dd>'
        . '<dt class="col-sm-3">Tür</dt><dd class="col-sm-9">' . safe_output($file['type']) . '</dd>'
        . '<dt class="col-sm-3">Yüklendi</dt><dd class="col-sm-9">' . safe_output(date('d.m.Y H:i', strtotime($file['uploaded_at']))) . '</dd>'
        . '<dt class="col-sm-3">IP</dt><dd class="col-sm-9">' . safe_output($file['uploader_ip']) . '</dd>'
        . '</dl>'
        . '<div class="mt-3 d-flex gap-2">'
        . '<a href="' . $downloadUrl . '" class="btn btn-success">İndir</a>'
        . '<a href="' . $previewUrl . '" class="btn btn-outline-secondary" target="_blank" rel="noopener">Önizleme</a>'
        . '<form action="' . $deleteUrl . '" method="post" onsubmit="return confirm(\'Dosyayı silmek istediğinize emin misiniz?\');">'
        . '<input type="hidden" name="csrf_token" value="' . $token . '">'
        . '<button class="btn btn-danger" type="submit">Sil</button>'
        . '</form>'
        . '</div>'
        . '</div></div>';

    $previewHtml = '';
    if (str_starts_with($file['type'], 'image/')) {
        $previewHtml = '<div class="card shadow-sm"><div class="card-body text-center"><img src="' . $previewUrl . '" class="img-fluid" alt="Önizleme"></div></div>';
    } elseif ($file['type'] === 'application/pdf') {
        $previewHtml = '<div class="ratio ratio-4x3"><iframe src="' . $previewUrl . '" title="PDF Önizleme" frameborder="0"></iframe></div>';
    }

    echo render_layout('Dosya Detayı', $details . $previewHtml);
}

function handle_download(int $id, bool $inline): void
{
    $file = get_file($id);
    if (!$file) {
        http_response_code(404);
        echo 'Dosya bulunamadı';
        return;
    }

    $path = __DIR__ . '/uploads/' . $file['stored_name'];
    if (!is_file($path)) {
        http_response_code(404);
        echo 'Dosya bulunamadı';
        return;
    }

    $disposition = $inline ? 'inline' : 'attachment';

    header('Content-Type: ' . $file['type']);
    header('Content-Length: ' . $file['size']);
    header('Content-Disposition: ' . $disposition . '; filename="' . basename($file['filename']) . '"');

    readfile($path);
    exit;
}

function handle_delete(int $id): void
{
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        echo render_layout('Hata', '<div class="alert alert-danger">Geçersiz CSRF token.</div>');
        return;
    }

    delete_file($id);
    redirect('dashboard');
}

function handle_edit(int $id): void
{
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        echo render_layout('Hata', '<div class="alert alert-danger">Geçersiz CSRF token.</div>');
        return;
    }

    $filename = trim($_POST['filename'] ?? '');
    if ($filename === '') {
        redirect('dashboard');
        return;
    }

    update_file_name($id, $filename);
    $slug = create_slug($filename);
    redirect('file/' . $id . '-' . $slug);
}

function render_layout(string $title, string $content, array $options = []): string
{
    $base = base_url();
    $hideNav = $options['hide_nav'] ?? false;
    $additionalScripts = $options['scripts'] ?? '';

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo safe_output($title); ?> - Dosya Deposu</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/style.css">
    </head>
    <body class="bg-light">
        <?php if (!$hideNav): ?>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
            <div class="container">
                <a class="navbar-brand" href="<?php echo $base; ?>/dashboard">Dosya Deposu</a>
                <div class="collapse navbar-collapse">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base; ?>/dashboard">Panel</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base; ?>/logout">Çıkış</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <?php endif; ?>
        <main class="container py-4">
            <?php echo $content; ?>
        </main>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
        <?php echo $additionalScripts; ?>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
