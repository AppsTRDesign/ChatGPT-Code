<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Services\MenuService;
use App\Services\SettingsService;
use Core\Response;

$settings = new SettingsService();
$menu = new MenuService();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        Response::json([
            'items' => $menu->dailyMenu(),
        ]);
    }

    if ($method === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $payload['action'] ?? 'save';

        if ($action === 'reorder') {
            $order = $payload['order'] ?? [];
            $settings->reorderDailyMenu(is_array($order) ? $order : []);
            Response::json([
                'items' => $menu->dailyMenu(),
                'message' => 'Günün menüsü sıralaması güncellendi.',
            ]);
        }

        $settings->saveDailyMenuItem($payload);
        Response::json([
            'items' => $menu->dailyMenu(),
            'message' => empty($payload['id']) ? 'Günün menüsüne eklendi.' : 'Günün menüsü güncellendi.',
        ]);
    }

    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? '';
        if ($id === '') {
            throw new InvalidArgumentException('Silinecek kayıt bulunamadı.');
        }
        $settings->deleteDailyMenuItem($id);
        Response::json([
            'items' => $menu->dailyMenu(),
            'message' => 'Günün menüsü öğesi silindi.',
        ]);
    }

    Response::json([
        'error' => true,
        'message' => 'Desteklenmeyen istek yöntemi',
    ], 405);
} catch (Throwable $exception) {
    Response::json([
        'error' => true,
        'message' => $exception->getMessage(),
    ], 400);
}
