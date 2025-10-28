<?php
require_once __DIR__ . '/../config.php';
require_auth(true);
global $pageScripts;
$pageScripts[] = '<script src="' . BASE_URL . '/assets/js/admin-payment-notifications.js?v=1.0.0"></script>';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <div class="card card-glass p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="h4 mb-1">Ödeme Bildirimleri</h1>
                <p class="text-white-50 small mb-0">Havale/EFT dekontlarını inceleyin ve paket atamasını tamamlayın.</p>
            </div>
            <select class="form-select w-auto" id="notificationFilter">
                <option value="">Tümü</option>
                <option value="pending">Beklemede</option>
                <option value="approved">Onaylandı</option>
                <option value="rejected">Reddedildi</option>
                <option value="insufficient">Eksik ödeme</option>
            </select>
        </div>
        <div id="notificationList" class="row g-3"></div>
    </div>
</div>
<template id="notificationTemplate">
    <div class="col-12 col-lg-6">
        <div class="card card-layer h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h2 class="h6 mb-1" data-notify-user>—</h2>
                        <p class="text-white-50 small mb-0" data-notify-package>—</p>
                    </div>
                    <span class="badge rounded-pill" data-notify-status>Beklemede</span>
                </div>
                <ul class="list-unstyled text-white-50 small mb-3">
                    <li><strong>Tutar:</strong> <span data-notify-amount>—</span></li>
                    <li><strong>Sağlayıcı:</strong> <span data-notify-provider>—</span></li>
                    <li><strong>Gönderim:</strong> <span data-notify-date>—</span></li>
                </ul>
                <p class="text-white-50 small flex-grow-1" data-notify-note></p>
                <div class="d-flex flex-wrap gap-2 align-items-center mt-3">
                    <button type="button" class="btn btn-sm btn-outline-light" data-action="approve">Onayla</button>
                    <button type="button" class="btn btn-sm btn-outline-warning" data-action="insufficient">Eksik Ödeme</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-action="reject">Reddet</button>
                    <div class="ms-auto" data-notify-files></div>
                </div>
            </div>
        </div>
    </div>
</template>
<?php include __DIR__ . '/../templates/footer.php'; ?>
