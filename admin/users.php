<?php
require_once __DIR__ . '/../config.php';
require_auth(true);
global $pageScripts;
$pageScripts[] = '<script src="' . BASE_URL . '/assets/js/admin-users.js?v=1.0.0"></script>';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <div class="card card-glass p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3 data-table-toolbar">
            <h2 class="h5 mb-0">Üye Yönetimi</h2>
            <input type="search" id="adminUsersSearch" class="form-control data-table-search" placeholder="İsim, e-posta veya paket ara">
        </div>
        <div class="table-responsive">
            <table class="table table-modern align-middle" id="adminUsersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad Soyad</th>
                        <th>E-posta</th>
                        <th>Paket</th>
                        <th>Doğrulama</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-0">
                <h5 class="modal-title">Üye Bilgileri</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" name="id" id="userId">
                    <div class="mb-3">
                        <label class="form-label" for="userName">Ad Soyad</label>
                        <input type="text" class="form-control" id="userName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="userEmail">E-posta</label>
                        <input type="email" class="form-control" id="userEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="userRole">Rol</label>
                        <select class="form-select" id="userRole" name="role">
                            <option value="client">Üye</option>
                            <option value="admin">Yönetici</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="userPackage">Paket</label>
                        <select class="form-select" id="userPackage" name="package_id">
                            <option value="">Seçilmedi</option>
                        </select>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="userVerified">
                        <label class="form-check-label" for="userVerified">E-posta onaylı</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-gradient" onclick="saveUser()">Kaydet</button>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
