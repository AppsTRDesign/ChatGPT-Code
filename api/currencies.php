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
            'default' => $service->currentCurrency(),
        ]);
    }

    if ($method === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        $currencies = $service->addCurrency($payload);
        Response::json([
            'currencies' => $currencies,
            'message' => 'Para birimi kaydedildi.',
        ]);
    }

    if ($method === 'PATCH') {
        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        $code = $payload['code'] ?? '';
        if ($code === '') {
            throw new InvalidArgumentException('Para birimi kodu zorunludur.');
        }

        $currencies = $service->setDefaultCurrency($code);
        Response::json([
            'currencies' => $currencies,
            'message' => 'Varsayılan para birimi güncellendi.',
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
