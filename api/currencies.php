<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Services\SettingsService;
use Core\Response;

$service = new SettingsService();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        Response::json([
            'currencies' => $service->currencies(),
        ]);
    }

    if ($method === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        if (($payload['action'] ?? '') === 'default') {
            $currencies = $service->setDefaultCurrency($payload['code']);
            Response::json([
                'currencies' => $currencies,
                'message' => 'Varsayılan para birimi güncellendi.',
            ]);
        }

        $currencies = $service->addCurrency($payload);
        Response::json([
            'currencies' => $currencies,
            'message' => 'Para birimi kaydedildi.',
        ]);
    }

    if ($method === 'DELETE') {
        parse_str($_SERVER['QUERY_STRING'] ?? '', $query);
        $id = (int)($query['id'] ?? 0);
        $currencies = $service->deleteCurrency($id);
        Response::json([
            'currencies' => $currencies,
            'message' => 'Para birimi silindi.',
        ]);
    }

    Response::json(['error' => 'Desteklenmeyen istek yöntemi'], 405);
} catch (Throwable $exception) {
    Response::json([
        'error' => true,
        'message' => $exception->getMessage(),
    ], 500);
}
