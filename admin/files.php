<?php
require_once __DIR__ . '/../config.php';
require_auth(true);
global $pageScripts;
$pageScripts[] = '<script src="' . BASE_URL . '/assets/js/admin-files.js?v=1.0.0"></script>';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <div class="card card-glass p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3 data-table-toolbar">
            <h2 class="h5 mb-0">Dosya Yönetimi</h2>
            <input type="search" id="adminFilesSearch" class="form-control data-table-search" placeholder="Dosya, kullanıcı veya tür ara">
        </div>
        <div class="table-responsive">
            <table class="table table-modern align-middle" id="adminFilesTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Dosya</th>
                        <th>Boyut</th>
                        <th>Kullanıcı</th>
                        <th>Tarih</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
