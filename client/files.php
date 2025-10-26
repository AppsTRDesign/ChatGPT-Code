<?php
require_once __DIR__ . '/../config.php';
require_login_redirect();
global $pageScripts;
$pageScripts[] = '<script src="' . BASE_URL . '/assets/js/file-manager.js?v=1.1.0"></script>';
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
                <button type="button" class="btn btn-outline-light" data-fm-action="rename"><i class="bi bi-pencil-square me-1"></i>Ad Değiştir</button>
                <button type="button" class="btn btn-outline-light" data-fm-action="move"><i class="bi bi-arrows-move me-1"></i>Taşı</button>
                <button type="button" class="btn btn-outline-light" data-fm-action="zip"><i class="bi bi-file-zip me-1"></i>Zip Oluştur</button>
                <button type="button" class="btn btn-outline-danger ms-lg-auto" data-fm-action="delete"><i class="bi bi-trash me-1"></i>Sil</button>
            </div>
        </div>

        <div class="card card-glass p-3 mb-4">
            <nav class="breadcrumb breadcrumb-dark mb-3" aria-label="breadcrumbs" data-fm-breadcrumbs></nav>
            <div class="fm-grid" data-fm-grid>
                <div class="text-white-50 small">İçerik yükleniyor…</div>
            </div>
        </div>

        <div class="card card-glass p-3">
            <h2 class="h6 mb-3">Sürükle &amp; Bırak Yükleme</h2>
            <form action="<?= BASE_URL ?>/api/upload.php" class="dropzone fm-dropzone" id="clientUploadZone" data-dropzone data-parallel-uploads="1">
                <div class="dz-message">
                    Dosyalarınızı buraya sürükleyip bırakın veya tıklayarak seçin.
                    <span class="d-block text-white-50 small">Desteklenen türler: <span data-fm-allowed></span></span>
                </div>
            </form>
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
        <li data-action="visibility">Görünürlüğü Değiştir</li>
        <li data-action="zip">Zip Oluştur</li>
        <li data-action="delete" class="text-danger">Sil</li>
    </ul>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
