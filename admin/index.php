<?php
$pageTitle = 'Yönetim Paneli';
$activeNav = 'dashboard';
require_once __DIR__ . '/partials/header.php';
?>
<div class="row g-3" id="stats-row"></div>
<div class="row g-3 mt-3">
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title">Doğrulama Talepleri</h5>
                <p class="text-muted flex-grow-1">Bekleyen claim isteklerini görüntüleyin ve onaylayın.</p>
                <a class="btn btn-primary w-100" href="claims.php">Panele Git</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title">Yorum Yönetimi</h5>
                <p class="text-muted flex-grow-1">Kullanıcı yorumlarını inceleyip onaylayın veya reddedin.</p>
                <a class="btn btn-primary w-100" href="reviews.php">Panele Git</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title">Kullanıcılar</h5>
                <p class="text-muted flex-grow-1">Rolleri ve durumları güncelleyin.</p>
                <a class="btn btn-primary w-100" href="users.php">Panele Git</a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
