<?php
require_once __DIR__ . '/../auth.php';
admin_require_auth();
$user = admin_current_user();
$pageTitle = $pageTitle ?? 'Yönetim Paneli';
$activeNav = $activeNav ?? '';
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" integrity="sha512-5/9CR6VqgT6CaiuN4PpxDYbk8TW2z9YQYT7B+HIJaMItLqYATegEz34wzyBbs6K8ZZr3Su2VvulaHYodNsq4qg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body data-page="<?php echo htmlspecialchars($activeNav); ?>">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-3">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link <?php echo $activeNav==='dashboard'?'active':''; ?>" href="index.php">Gösterge Paneli</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $activeNav==='claims'?'active':''; ?>" href="claims.php">Doğrulama Talepleri</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $activeNav==='reviews'?'active':''; ?>" href="reviews.php">Yorumlar</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $activeNav==='users'?'active':''; ?>" href="users.php">Kullanıcılar</a></li>
            </ul>
            <div class="d-flex align-items-center text-white gap-3">
                <span><?php echo htmlspecialchars($user['name'] ?? ''); ?></span>
                <a class="btn btn-outline-light btn-sm" href="logout.php">Çıkış</a>
            </div>
        </div>
    </div>
</nav>
<div class="container-fluid mb-4">
