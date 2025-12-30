<?php
require_once __DIR__ . '/auth.php';
admin_require_auth();
$user = admin_current_user();
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Yönetim Paneli</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" integrity="sha512-5/9CR6VqgT6CaiuN4PpxDYbk8TW2z9YQYT7B+HIJaMItLqYATegEz34wzyBbs6K8ZZr3Su2VvulaHYodNsq4qg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-3">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Admin</a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3"><?php echo htmlspecialchars($user['name'] ?? ''); ?></span>
            <a class="btn btn-outline-light btn-sm" href="logout.php">Çıkış</a>
        </div>
    </div>
</nav>
<div class="container-fluid">
    <div class="row g-3" id="stats-row"></div>

    <ul class="nav nav-tabs mt-3" id="adminTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#claims" type="button" role="tab">Doğrulama Talepleri</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab">Yorumlar</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">Kullanıcılar</button>
        </li>
    </ul>
    <div class="tab-content border border-top-0 p-3 bg-white">
        <div class="tab-pane fade show active" id="claims" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-sm align-middle" id="claims-table">
                    <thead><tr><th>ID</th><th>İşletme</th><th>Kullanıcı</th><th>Durum</th><th>Metod</th><th>Oluşturma</th><th>İşlem</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <div class="tab-pane fade" id="reviews" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-sm align-middle" id="reviews-table">
                    <thead><tr><th>ID</th><th>İşletme</th><th>Yazar</th><th>Puan</th><th>Durum</th><th>Metin</th><th>İşlem</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <div class="tab-pane fade" id="users" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-sm align-middle" id="users-table">
                    <thead><tr><th>ID</th><th>Ad</th><th>E-posta</th><th>Rol</th><th>Durum</th><th>İşlem</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js" integrity="sha512-pn8fV8q9Yx6fL2d0wEMpKyGKoU9pYNCXS6/4FwXv4Hknc0aF4Pjk6HoydxHDBPPfQtbDNRsTA70NU6U8i5p9+w==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="assets/js/admin.js"></script>
</body>
</html>
