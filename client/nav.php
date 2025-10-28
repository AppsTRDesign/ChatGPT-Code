<?php
require_once __DIR__ . '/../config.php';
require_login_redirect();
$settings = fetch_settings($pdo);
?>
<?php if (!empty($settings['ad_dashboard_html'])): ?>
    <div class="container mt-4">
        <div class="card card-glass p-3 mb-4">
            <?= $settings['ad_dashboard_html'] ?>
        </div>
    </div>
<?php endif; ?>
