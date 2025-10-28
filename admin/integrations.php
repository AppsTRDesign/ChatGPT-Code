<?php
require_once __DIR__ . '/../config.php';
require_auth(true);
global $pageScripts;
$pageScripts[] = '<script src="' . BASE_URL . '/assets/js/admin-integrations.js?v=1.0.0"></script>';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
$settings = fetch_settings($pdo);
?>
<div class="container pb-5">
    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card card-glass h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Plesk API Durumu</h2>
                    <p class="text-white-50 small">Paket atamalarından sonra müşteri limitlerinin Plesk üzerinde güncellenmesini sağlar.</p>
                    <dl class="text-white-50 small">
                        <dt>API URL</dt>
                        <dd><?= sanitize($settings['plesk_api_url'] ?? 'Tanımlı değil') ?></dd>
                        <dt>Kullanıcı</dt>
                        <dd><?= sanitize($settings['plesk_api_login'] ?? 'Tanımlı değil') ?></dd>
                    </dl>
                    <button type="button" class="btn btn-outline-light" data-action="plesk-test">Bağlantıyı Test Et</button>
                    <div class="mt-3" data-result="plesk"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card card-glass h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">WebSocket / Gerçek Zamanlı</h2>
                    <p class="text-white-50 small">Dosya işlemleri ve paket güncellemeleri canlı olarak client panellerine aktarılır.</p>
                    <dl class="text-white-50 small">
                        <dt>Sunucu URL</dt>
                        <dd><?= sanitize($settings['realtime_ws_url'] ?? 'Tanımlı değil') ?></dd>
                        <dt>Aktif Durumu</dt>
                        <dd><?= !empty($settings['realtime_updates_enabled']) ? 'Aktif' : 'Pasif' ?></dd>
                    </dl>
                    <button type="button" class="btn btn-outline-light" data-action="ws-test">WebSocket Test Et</button>
                    <div class="mt-3" data-result="ws"></div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card card-layer p-4">
                <h2 class="h5 mb-3">Gerçek Zamanlı Sunucu Komutu</h2>
                <p class="text-white-50 small">Aşağıdaki komut ile Ratchet tabanlı websocket sunucusunu başlatabilirsiniz.</p>
                <pre class="bg-dark text-white rounded p-3 small"><code>php bin/realtime-server.php</code></pre>
                <p class="text-white-50 small mb-0">Sunucu portu ve adresi için <strong>Genel Ayarlar → Analitik &amp; Gerçek Zamanlı</strong> kartından WebSocket URL alanını güncelleyin.</p>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
