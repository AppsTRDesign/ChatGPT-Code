<?php
require_once __DIR__ . '/../config.php';
require_login_redirect();
global $pageScripts;
$pageScripts[] = '<script src="' . BASE_URL . '/assets/js/client-packages.js?v=1.1.0"></script>';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <div class="row g-4" id="clientPackages"></div>
</div>

<div class="modal fade" id="paymentProviderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-glass">
            <div class="modal-header border-0">
                <h2 class="h5 mb-0">Ödeme Yöntemi Seçin</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <p class="text-white-50 small">Paketinizi aktif etmek için bir ödeme yöntemi seçin.</p>
                <div class="list-group" id="paymentProviderList"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="bankTransferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content card-glass">
            <div class="modal-header border-0">
                <div>
                    <h2 class="h5 mb-1">Havale / EFT Bildirimi</h2>
                    <p class="text-white-50 small mb-0">Ödemenizi tamamladıysanız dekontu yükleyerek yöneticinin onayına gönderin.</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-dark border-0 text-white-50" id="bankInstructions">
                    Ödeme talimatları burada görüntülenecek.
                </div>
                <dl class="row small text-white-50 mb-4">
                    <dt class="col-sm-4">Paket</dt>
                    <dd class="col-sm-8" data-bank-package>—</dd>
                    <dt class="col-sm-4">Tutar</dt>
                    <dd class="col-sm-8" data-bank-amount>—</dd>
                </dl>
                <form id="bankTransferForm" class="dz-theme" data-dropzone data-upload-multiple="true" data-preview-template="#bankTransferTemplate" action="<?= BASE_URL ?>/api/client.php" method="post">
                    <input type="hidden" name="action" value="payment-proof">
                    <input type="hidden" name="transaction_id" value="">
                    <div class="mb-3">
                        <label class="form-label">Not (isteğe bağlı)</label>
                        <textarea class="form-control" name="note" rows="3" placeholder="Ödeme hakkında ek bilgi"></textarea>
                    </div>
                    <label class="form-label">Dekont / Makbuz Yükleme</label>
                    <div class="dropzone dz-theme" id="bankTransferDropzone">
                        <div class="dz-message">PDF veya görsel dosyalarınızı sürükleyin.</div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-outline-light" data-upload-clear>Temizle</button>
                        <button type="button" class="btn btn-gradient" data-upload-start>Dekontu Gönder</button>
                    </div>
                </form>
                <template id="bankTransferTemplate">
                    <div class="dz-preview dz-file-preview upload-card">
                        <div class="upload-card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <div class="fw-semibold text-white" data-upload-name>Dosya Adı</div>
                                    <div class="text-white-50 small" data-upload-meta>Tür • Boyut</div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-light" data-upload-remove><i class="bi bi-x"></i></button>
                            </div>
                            <div class="progress progress-thin">
                                <div class="progress-bar" role="progressbar" data-upload-progress></div>
                            </div>
                            <div class="text-white-50 small mt-2" data-upload-status>Bekliyor</div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
