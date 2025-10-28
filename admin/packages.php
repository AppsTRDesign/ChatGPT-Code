<?php
require_once __DIR__ . '/../config.php';
require_auth(true);
global $pageScripts;
$pageScripts[] = '<script src="' . BASE_URL . '/assets/js/admin-packages.js?v=1.0.0"></script>';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <div class="card card-glass p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3 data-table-toolbar">
            <h2 class="h5 mb-0">Paketler</h2>
            <div class="d-flex gap-2">
                <input type="search" id="adminPackagesSearch" class="form-control data-table-search" placeholder="Paket ara">
                <button class="btn btn-gradient" id="newPackageButton" data-bs-toggle="modal" data-bs-target="#packageModal">Yeni Paket</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-modern align-middle" id="adminPackagesTable">
                <thead>
                    <tr>
                        <th>Ad</th>
                        <th>Depolama</th>
                        <th>Maks. Yükleme</th>
                        <th>İzinli Türler</th>
                        <th>Fiyat</th>
                        <th>Durum</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="packageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-0">
                <h5 class="modal-title">Paket</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="packageForm">
                    <input type="hidden" name="id" id="packageId">
                    <div class="mb-3">
                        <label class="form-label" for="packageName">Paket Adı</label>
                        <input type="text" class="form-control" id="packageName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="packageStorage">Depolama (byte)</label>
                        <input type="number" class="form-control" id="packageStorage" name="storage_limit" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="packageUploads">Maksimum aynı anda yükleme</label>
                        <input type="number" class="form-control" id="packageUploads" name="max_concurrent_uploads" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="packagePrice">Fiyat</label>
                        <input type="number" step="0.01" class="form-control" id="packagePrice" name="price" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="packageFeatures">Özellikler (virgülle ayırın)</label>
                        <input type="text" class="form-control" id="packageFeatures" name="features">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="packageExtensions">İzinli Uzantılar</label>
                        <textarea class="form-control" id="packageExtensions" name="allowed_extensions" rows="3" placeholder="jpg
png
pdf"></textarea>
                        <small class="text-white-50">Her satıra bir uzantı yazın. Boş bırakılırsa genel ayarlardaki liste uygulanır.</small>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="packageActive">
                        <label class="form-check-label" for="packageActive">Aktif</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-gradient" onclick="savePackage()">Kaydet</button>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
