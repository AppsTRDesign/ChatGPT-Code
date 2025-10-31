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
$currency = $db->query("SELECT currency FROM restaurants WHERE id = {$restaurantId}")->fetchColumn() ?: 'TRY';
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

            $qrUrl = tableQrUrl($qrService, $qrConfig, $id);
            $db->prepare('UPDATE tables SET qr_code_url = ? WHERE id = ?')->execute([$qrUrl, $id]);

            Response::json([
                'success' => true,
                'table' => fetchTable($db, $qrService, $qrConfig, $currency, $restaurantId, $id),
                'tables' => fetchTables($db, $qrService, $qrConfig, $currency, $restaurantId),
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
                'tables' => fetchTables($db, $qrService, $qrConfig, $currency, $restaurantId),
                'message' => 'Masa silindi.',
            ]);
            break;
        default:
            Response::json([
                'tables' => fetchTables($db, $qrService, $qrConfig, $currency, $restaurantId),
            ]);
    }
} catch (Throwable $exception) {
    Response::json([
        'error' => true,
        'message' => $exception->getMessage(),
    ], 400);
}

function fetchTables(\PDO $db, QrService $qrService, array $qrConfig, string $currency, int $restaurantId): array
{
    $statement = $db->prepare('SELECT id FROM tables WHERE restaurant_id = ? ORDER BY name');
    $statement->execute([$restaurantId]);
    $tables = [];
    foreach ($statement->fetchAll() ?: [] as $row) {
        $tables[] = fetchTable($db, $qrService, $qrConfig, $currency, $restaurantId, (int)$row['id']);
    }
    return $tables;
}

function fetchTable(\PDO $db, QrService $qrService, array $qrConfig, string $currency, int $restaurantId, int $tableId): array
{
    $statement = $db->prepare('SELECT id, name, status, qr_code_url FROM tables WHERE id = ?');
    $statement->execute([$tableId]);
    $table = $statement->fetch();
    if (!$table) {
        return [];
    }

    $tableUrl = BASE_URL . '/menu.php?table=' . $table['id'];
    $table['qr_url'] = $tableUrl;
    $table['status_label'] = $table['status'] === 'occupied' ? 'Dolu' : 'Boş';
    $table['qr_code_url'] = $table['qr_code_url'] ?: tableQrUrl($qrService, $qrConfig, (int)$table['id']);

    $ordersStatement = $db->prepare("SELECT id, status, total, DATE_FORMAT(created_at, '%H:%i') AS created_at FROM orders WHERE table_id = ? AND restaurant_id = ? AND status NOT IN ('Ödeme Alındı', 'Tamamlandı', 'İptal') ORDER BY created_at DESC");
    $ordersStatement->execute([$tableId, $restaurantId]);
    $table['active_orders'] = array_map(static function ($order) use ($currency) {
        $order['total'] = (float)$order['total'];
        $order['total_formatted'] = number_format($order['total'], 2, ',', '.') . ' ' . $currency;
        return $order;
    }, $ordersStatement->fetchAll() ?: []);

    return $table;
}

function tableQrUrl(QrService $qrService, array $qrConfig, int $tableId): string
{
    $url = BASE_URL . '/menu.php?table=' . $tableId;
    return $qrService->generateUrl($url, $qrConfig);
}
