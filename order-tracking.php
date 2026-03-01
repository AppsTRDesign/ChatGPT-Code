<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

$user = current_user();
$orderId = (int) ($_GET['order_id'] ?? 0);
if (!$orderId) {
    http_response_code(404);
    render_header('Kargo Takip');
    echo '<main class="container"><p>Sipariş bulunamadı.</p></main>';
    render_footer();
    exit;
}

$orderStmt = db()->prepare('SELECT orders.*, shippers.name AS shipper_name, shippers.address AS shipper_address, shippers.logo AS shipper_logo, shippers.website AS shipper_website, shippers.query_url AS shipper_query_url, shippers.api_url AS shipper_api_url FROM orders LEFT JOIN shippers ON shippers.id = orders.shipper_id WHERE orders.id = :id');
$orderStmt->execute(['id' => $orderId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order || (!$user && empty($order['email']))) {
    http_response_code(404);
    render_header('Kargo Takip');
    echo '<main class="container"><p>Sipariş bulunamadı.</p></main>';
    render_footer();
    exit;
}

if ($user && (int) ($order['user_id'] ?? 0) !== (int) $user['id'] && strcasecmp((string) $order['email'], (string) ($user['email'] ?? '')) !== 0) {
    http_response_code(403);
    render_header('Kargo Takip');
    echo '<main class="container"><p>Bu siparişe erişim yetkiniz yok.</p></main>';
    render_footer();
    exit;
}

$trackingNumber = trim((string) ($order['tracking_number'] ?? ''));
$trackingPayload = null;
$trackingError = '';


$companyQueryUrl = '';
if ($trackingNumber !== '' && !empty($order['shipper_query_url'])) {
    $queryTemplate = trim((string) $order['shipper_query_url']);
    if (str_contains($queryTemplate, '{tracking_number}')) {
        $companyQueryUrl = str_replace('{tracking_number}', rawurlencode($trackingNumber), $queryTemplate);
    } else {
        $companyQueryUrl = rtrim($queryTemplate, '/') . '/' . rawurlencode($trackingNumber);
    }
}


if ($trackingNumber !== '') {
    $apiTemplate = trim((string) ($order['shipper_api_url'] ?? ''));
    if ($apiTemplate === '') {
        $apiTemplate = 'https://cargoafrik.org/api/tracking_json.php?tracking_number={tracking_number}&lang={lang}';
    }
    $lang = substr((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'tr'), 0, 2);
    if ($lang === '') {
        $lang = 'tr';
    }

    $apiUrl = str_replace(
        ['{tracking_number}', '{lang}'],
        [rawurlencode($trackingNumber), rawurlencode($lang)],
        $apiTemplate
    );
    if (!str_contains($apiUrl, 'tracking_number=')) {
        $sep = str_contains($apiUrl, '?') ? '&' : '?';
        $apiUrl .= $sep . 'tracking_number=' . rawurlencode($trackingNumber);
    }
    if (!str_contains($apiUrl, 'lang=')) {
        $sep = str_contains($apiUrl, '?') ? '&' : '?';
        $apiUrl .= $sep . 'lang=' . rawurlencode($lang);
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => 8,
            'ignore_errors' => true,
            'header' => "Accept: application/json\r\n",
        ],
    ]);
    $raw = @file_get_contents($apiUrl, false, $context);
    if ($raw !== false) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            if (!empty($decoded['ok'])) {
                $trackingPayload = $decoded;
            } else {
                $trackingError = (string) ($decoded['message'] ?? 'Takip numarası bulunamadı.');
            }
        } else {
            $trackingError = 'Kargo verisi okunamadı.';
        }
    } else {
        $trackingError = 'Kargo servisine ulaşılamadı.';
    }
}

render_header('Kargo Takip');
?>
<main class="container tracking-page">
    <section class="tracking-card">
        <div class="tracking-head">
            <div>
                <h1>Sipariş #<?= (int) $order['id'] ?> Kargo Takibi</h1>
                <p class="text-muted">Takip No: <strong><?= htmlspecialchars($trackingNumber ?: '-') ?></strong></p>
            </div>
            <div class="tracking-shipper">
                <?php if (!empty($order['shipper_logo'])): ?>
                    <img src="<?= htmlspecialchars($order['shipper_logo']) ?>" alt="<?= htmlspecialchars($order['shipper_name'] ?: 'Kargo') ?>">
                <?php endif; ?>
                <strong><?= htmlspecialchars($order['shipper_name'] ?: 'Kargo Firması') ?></strong>
                <?php if (!empty($order['shipper_address'])): ?><small><?= htmlspecialchars($order['shipper_address']) ?></small><?php endif; ?>
                <?php if (!empty($order['shipper_website'])): ?>
                    <a href="<?= htmlspecialchars($order['shipper_website']) ?>" target="_blank" rel="noopener">Firma Sitesi</a>
                <?php endif; ?>
                <?php if ($companyQueryUrl !== ''): ?>
                    <a class="btn" href="<?= htmlspecialchars($companyQueryUrl) ?>" target="_blank" rel="noopener">Firmada Sorgula</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($trackingNumber === ''): ?>
            <div class="tracking-alert">Kargo bilgisi bekleniyor.</div>
        <?php elseif ($trackingPayload === null): ?>
            <div class="tracking-alert tracking-alert-danger"><?= htmlspecialchars($trackingError ?: 'Takip bilgisi bulunamadı.') ?></div>
        <?php else: ?>
            <?php $shipment = $trackingPayload['data']['shipment'] ?? []; ?>
            <?php $events = $trackingPayload['data']['events'] ?? []; ?>
            <div class="tracking-summary-grid">
                <div class="tracking-box">
                    <span>Mevcut Durum</span>
                    <strong><?= htmlspecialchars((string) ($shipment['status_label'] ?? $shipment['current_status'] ?? '-')) ?></strong>
                </div>
                <div class="tracking-box">
                    <span>Çıkış</span>
                    <strong><?= htmlspecialchars((string) ($shipment['origin_city'] ?? '-')) ?> / <?= htmlspecialchars((string) ($shipment['origin_country_name'] ?? $shipment['origin_country'] ?? '-')) ?></strong>
                </div>
                <div class="tracking-box">
                    <span>Varış</span>
                    <strong><?= htmlspecialchars((string) ($shipment['destination_city'] ?? '-')) ?> / <?= htmlspecialchars((string) ($shipment['destination_country_name'] ?? $shipment['destination_country'] ?? '-')) ?></strong>
                </div>
                <div class="tracking-box">
                    <span>Alıcı</span>
                    <strong><?= htmlspecialchars((string) ($shipment['receiver_name'] ?? '-')) ?></strong>
                </div>
            </div>
            <h3>Kargo Hareketleri</h3>
            <div class="tracking-timeline">
                <?php foreach ($events as $event): ?>
                    <article class="tracking-event">
                        <div class="tracking-event-dot"></div>
                        <div class="tracking-event-body">
                            <h4><?= htmlspecialchars((string) ($event['status_label'] ?? $event['status_code'] ?? '-')) ?></h4>
                            <p><?= htmlspecialchars((string) ($event['status_note'] ?? '-')) ?></p>
                            <small><?= htmlspecialchars((string) ($event['city'] ?? '-')) ?> / <?= htmlspecialchars((string) ($event['country'] ?? '-')) ?> • <?= htmlspecialchars((string) ($event['created_at'] ?? '')) ?></small>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if ($companyQueryUrl !== ''): ?>
                <div class="tracking-company-query">
                    <a class="btn primary" href="<?= htmlspecialchars($companyQueryUrl) ?>" target="_blank" rel="noopener">Firmada Sorgula</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</main>
<?php render_footer(); ?>
