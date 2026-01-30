<?php
$pageTitle = 'Kullanıcı Yönetimi';
$activeNav = 'users';
require_once __DIR__ . '/partials/header.php';
?>
<div class="card shadow-sm">
    <div class="card-body">
        <form class="row g-2 mb-3" id="user-filters">
            <div class="col-md-6">
                <input type="search" name="q" class="form-control" placeholder="İsim veya e-posta ara">
            </div>
            <div class="col-md-3">
                <select name="per_page" class="form-select">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div class="col-md-3 text-end">
                <button class="btn btn-primary" type="submit">Filtrele</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-sm align-middle" id="users-table">
                <thead><tr><th>ID</th><th>Ad</th><th>E-posta</th><th>Rol</th><th>Durum</th><th>İşlem</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
        <nav><ul class="pagination pagination-sm" id="users-pagination"></ul></nav>
    </div>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
