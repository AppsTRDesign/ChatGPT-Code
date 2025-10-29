<?php
require_once __DIR__ . '/../config.php';
require_login_redirect();
global $pageScripts;
$pageScripts[] = '<script src="' . BASE_URL . '/assets/js/file-manager.js?v=1.2.0"></script>';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <div id="fileManagerApp" class="file-manager" data-initial-folder="">
        <div class="card card-glass p-4 mb-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <div>
                    <h1 class="h4 mb-1">Dosya Depom</h1>
                    <p class="text-white-50 mb-0">Klasörleri yönetin, dosyalarınızı sürükleyip bırakın ve paylaşım bağlantıları oluşturun.</p>
                </div>
                <div class="fm-stats text-end">
                    <div class="text-white-50 small">Kullanılan Alan</div>
                    <div class="fw-semibold" data-fm-stat="usage">0 MB</div>
                </div>
            </div>
            <div class="fm-toolbar mt-4 d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-gradient" data-fm-action="new-folder"><i class="bi bi-folder-plus me-1"></i>Klasör Oluştur</button>
                <button type="button" class="btn btn-outline-light" data-fm-action="upload"><i class="bi bi-cloud-arrow-up me-1"></i>Dosya Yükle</button>
                <button type="button" class="btn btn-outline-light" data-fm-action="new-file"><i class="bi bi-file-earmark-plus me-1"></i>Yeni Dosya</button>
                <button type="button" class="btn btn-outline-light" data-fm-action="rename"><i class="bi bi-pencil-square me-1"></i>Ad Değiştir</button>
                <button type="button" class="btn btn-outline-light" data-fm-action="move"><i class="bi bi-arrows-move me-1"></i>Taşı</button>
                <button type="button" class="btn btn-outline-light" data-fm-action="select-all"><i class="bi bi-check2-all me-1"></i>Tümünü Seç</button>
                <button type="button" class="btn btn-outline-light" data-fm-action="zip"><i class="bi bi-file-zip me-1"></i>Zip Oluştur</button>
                <button type="button" class="btn btn-outline-danger ms-lg-auto" data-fm-action="delete"><i class="bi bi-trash me-1"></i>Sil</button>
            </div>
        </div>

        <div class="card card-glass p-3 mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <nav class="breadcrumb breadcrumb-dark mb-0" aria-label="breadcrumbs" data-fm-breadcrumbs></nav>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <label class="text-white-50 small mb-0" for="fm-sort">Sırala</label>
                    <select id="fm-sort" class="form-select form-select-sm fm-sort" data-fm-sort>
                        <option value="name|asc">Ada göre (A-Z)</option>
                        <option value="name|desc">Ada göre (Z-A)</option>
                        <option value="date|desc">Yükleme Tarihi (Yeni)</option>
                        <option value="date|asc">Yükleme Tarihi (Eski)</option>
                        <option value="size|desc">Boyuta göre (Büyük)</option>
                        <option value="size|asc">Boyuta göre (Küçük)</option>
                    </select>
                </div>
            </div>
            <div class="fm-grid" data-fm-grid>
                <div class="text-white-50 small">İçerik yükleniyor…</div>
            </div>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-4" data-fm-pagination>
                <div class="text-white-50 small" data-fm-summary></div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-light btn-sm" data-fm-page="prev"><i class="bi bi-chevron-left"></i></button>
                    <span class="text-white fw-semibold" data-fm-page-label>1 / 1</span>
                    <button type="button" class="btn btn-outline-light btn-sm" data-fm-page="next"><i class="bi bi-chevron-right"></i></button>
                </div>
            </div>
        </div>

        <div class="card card-glass p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <h2 class="h6 mb-0">Sürükle &amp; Bırak Yükleme</h2>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-light btn-sm" data-upload-start>
                        <i class="bi bi-cloud-upload me-1"></i>Yüklemeye Başla
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" data-upload-clear>
                        <i class="bi bi-x-lg me-1"></i>Listeyi Temizle
                    </button>
                </div>
            </div>
            <form action="<?= BASE_URL ?>/api/upload.php" class="dropzone fm-dropzone" id="clientUploadZone" data-dropzone data-parallel-uploads="1" data-preview-template="#fm-upload-item-template" data-previews-container="[data-upload-list]">
                <div class="dz-message">
                    Dosyalarınızı buraya sürükleyip bırakın veya tıklayarak seçin.
                </div>
            </form>
            <div class="mt-3 p-3 border border-dashed rounded-3 bg-dark-40">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <div class="fw-semibold text-uppercase extra-small text-white-50">İzin Verilen Uzantılar</div>
                        <div class="text-white" data-fm-allowed>—</div>
                    </div>
                    <div class="text-white-50 extra-small" data-fm-upload-limit></div>
                </div>
            </div>
            <div class="fm-upload-list" data-upload-list></div>
        </div>
    </div>
</div>

<template id="fm-folder-template">
    <div class="fm-item" data-type="folder">
        <div class="fm-icon fm-icon-folder"></div>
        <div class="fm-info">
            <div class="fm-name"></div>
            <div class="fm-meta"></div>
        </div>
    </div>
</template>

<template id="fm-file-template">
    <div class="fm-item" data-type="file">
        <div class="fm-icon fm-icon-file"></div>
        <div class="fm-info">
            <div class="fm-name"></div>
            <div class="fm-meta"></div>
        </div>
    </div>
</template>

<div class="fm-context" data-fm-context hidden>
    <ul class="list-unstyled mb-0">
        <li data-action="open">Aç</li>
        <li data-action="rename">Ad Değiştir</li>
        <li data-action="move">Taşı</li>
        <li data-action="share">Paylaş</li>
        <li data-action="protect">Şifrele</li>
        <li data-action="zip">Zip Oluştur</li>
        <li data-action="delete" class="text-danger">Sil</li>
    </ul>
</div>

<template id="fm-upload-item-template">
    <div class="fm-upload-item">
        <div class="d-flex align-items-start justify-content-between">
            <div>
                <div class="fw-semibold text-white small mb-1" data-upload-name></div>
                <div class="text-white-50 extra-small" data-upload-meta></div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-light" data-upload-remove title="Kaldır">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="progress mt-3" role="progressbar" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" data-upload-progress style="width: 0%"></div>
        </div>
        <div class="text-white-50 extra-small mt-2" data-upload-status></div>
    </div>
</template>

<?php include __DIR__ . '/../templates/footer.php'; ?>
