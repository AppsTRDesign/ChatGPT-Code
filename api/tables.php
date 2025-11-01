<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Services\QrService;
use App\Services\SettingsService;
use Core\Database;
use Core\Response;

$restaurantId = 1;
$db = Database::connection();
$settingsService = new SettingsService($restaurantId);
$settings = $settingsService->all();
$qrConfig = $settings['qr'] ?? [];
$qrConfig['logo'] = $settings['branding']['qr_logo'] ?? ($qrConfig['logo'] ?? null);
$defaultLanguage = $settingsService->currentLanguage();
$defaultCurrency = $settingsService->currentCurrency();
$currencyList = $settings['currencies'] ?? [];
foreach ($currencyList as $currencyRow) {
    if (!empty($currencyRow['is_default'])) {
        $defaultCurrency = $currencyRow['code'];
        break;
    }
}
$qrService = new QrService();

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            $payload = json_decode(file_get_contents('php://input'), true) ?? [];
            $id = (int)($payload['id'] ?? 0);
            $name = trim($payload['name'] ?? '');
            $status = $payload['status'] ?? 'available';
            if ($name === '') {
                throw new InvalidArgumentException('Masa adı zorunludur.');
            }

            if ($id > 0) {
                $statement = $db->prepare('UPDATE tables SET name = ?, status = ?, updated_at = NOW() WHERE id = ? AND restaurant_id = ?');
                $statement->execute([$name, $status, $id, $restaurantId]);
            } else {
                $statement = $db->prepare('INSERT INTO tables (restaurant_id, name, status) VALUES (?, ?, ?)');
                $statement->execute([$restaurantId, $name, $status]);
                $id = (int)$db->lastInsertId();
            }

            $qrUrl = tableQrUrl($qrService, $qrConfig, $id, $defaultLanguage, $defaultCurrency);
            $db->prepare('UPDATE tables SET qr_code_url = ? WHERE id = ?')->execute([$qrUrl, $id]);

            Response::json([
                'success' => true,
                'table' => fetchTable($db, $qrService, $qrConfig, $defaultLanguage, $defaultCurrency, $restaurantId, $id),
                'tables' => fetchTables($db, $qrService, $qrConfig, $defaultLanguage, $defaultCurrency, $restaurantId),
                'message' => 'Masa kaydedildi.',
            ]);
            break;
        case 'DELETE':
            parse_str($_SERVER['QUERY_STRING'] ?? '', $query);
            $id = isset($query['id']) ? (int)$query['id'] : 0;
            if ($id <= 0) {
                throw new InvalidArgumentException('Masa bulunamadı.');
            }
            $statement = $db->prepare('DELETE FROM tables WHERE restaurant_id = ? AND id = ?');
            $statement->execute([$restaurantId, $id]);
            Response::json([
                'success' => true,
                'tables' => fetchTables($db, $qrService, $qrConfig, $defaultLanguage, $defaultCurrency, $restaurantId),
                'message' => 'Masa silindi.',
            ]);
            break;
        default:
            Response::json([
                'tables' => fetchTables($db, $qrService, $qrConfig, $defaultLanguage, $defaultCurrency, $restaurantId),
            ]);
    }
} catch (Throwable $exception) {
    Response::json([
        'error' => true,
        'message' => $exception->getMessage(),
    ], 400);
}

function fetchTables(\PDO $db, QrService $qrService, array $qrConfig, string $language, string $currency, int $restaurantId): array
{
    $statement = $db->prepare('SELECT id FROM tables WHERE restaurant_id = ? ORDER BY name');
    $statement->execute([$restaurantId]);
    $tables = [];
    foreach ($statement->fetchAll() ?: [] as $row) {
        $tables[] = fetchTable($db, $qrService, $qrConfig, $language, $currency, $restaurantId, (int)$row['id']);
    }
    return $tables;
}

function fetchTable(\PDO $db, QrService $qrService, array $qrConfig, string $language, string $currency, int $restaurantId, int $tableId): array
{
    $statement = $db->prepare('SELECT id, name, status, qr_code_url FROM tables WHERE id = ?');
    $statement->execute([$tableId]);
    $table = $statement->fetch();
    if (!$table) {
        return [];
    }

    $tableUrl = tableMenuUrl((int)$table['id'], $language, $currency);
    $table['qr_url'] = $tableUrl;
    $table['status_label'] = $table['status'] === 'occupied' ? 'Dolu' : 'Boş';
    $freshQr = tableQrUrl($qrService, $qrConfig, (int)$table['id'], $language, $currency);
    if (($table['qr_code_url'] ?? '') !== $freshQr) {
        $db->prepare('UPDATE tables SET qr_code_url = ? WHERE id = ?')->execute([$freshQr, $table['id']]);
    }
    $table['qr_code_url'] = $freshQr;
    $table['qr_download_url'] = rtrim(BASE_URL, '/') . '/api/qr-download.php?' . http_build_query([
        'table_id' => $table['id'],
        'lang' => $language,
        'currency' => $currency,
    ]);

    $ordersStatement = $db->prepare("SELECT id, status, total, DATE_FORMAT(created_at, '%H:%i') AS created_at FROM orders WHERE table_id = ? AND restaurant_id = ? AND status NOT IN ('Ödeme Alındı', 'Tamamlandı', 'İptal') ORDER BY created_at DESC");
    $ordersStatement->execute([$tableId, $restaurantId]);
    $table['active_orders'] = array_map(static function ($order) use ($currency) {
        $order['total'] = (float)$order['total'];
        $order['total_formatted'] = number_format($order['total'], 2, ',', '.') . ' ' . $currency;
        return $order;
    }, $ordersStatement->fetchAll() ?: []);

    $sessionStatement = $db->prepare("SELECT order_id, status, payload, DATE_FORMAT(updated_at, '%H:%i') AS updated_at FROM table_order_sessions WHERE table_id = ? AND restaurant_id = ? ORDER BY updated_at DESC");
    $sessionStatement->execute([$tableId, $restaurantId]);
    $table['session_orders'] = array_map(static function ($row) use ($currency) {
        $payload = json_decode($row['payload'] ?? '{}', true) ?: [];
        $total = isset($payload['total']) ? (float)$payload['total'] : null;
        return [
            'order_id' => (int)$row['order_id'],
            'status' => $row['status'],
            'updated_at' => $row['updated_at'],
            'total' => $total,
            'total_formatted' => $total !== null ? number_format($total, 2, ',', '.') . ' ' . $currency : null,
            'items' => $payload['items'] ?? [],
        ];
    }, $sessionStatement->fetchAll() ?: []);

    return $table;
}

function tableQrUrl(QrService $qrService, array $qrConfig, int $tableId, string $language, string $currency): string
{
    $url = tableMenuUrl($tableId, $language, $currency);
    return $qrService->generateUrl($url, $qrConfig);
}

function tableMenuUrl(int $tableId, string $language, string $currency): string
{
    $language = strtolower($language ?: 'tr');
    $currency = strtoupper($currency ?: 'TRY');

    return rtrim(BASE_URL, '/') . '/menu/' . $tableId . '/' . $language . '/' . $currency;
}
