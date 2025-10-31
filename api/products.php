<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Services\MenuService;
use Core\Response;

$service = new MenuService(1);

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            Response::json([
                'products' => $service->products(),
            ]);
            break;
        case 'POST':
            $payload = json_decode(file_get_contents('php://input'), true) ?? [];
            $product = $service->saveProduct($payload);
            Response::json([
                'success' => true,
                'product' => $product,
                'products' => $service->products(),
                'message' => 'Ürün kaydedildi.',
            ]);
            break;
        case 'DELETE':
            parse_str($_SERVER['QUERY_STRING'] ?? '', $query);
            $id = isset($query['id']) ? (int)$query['id'] : 0;
            if ($id <= 0) {
                throw new InvalidArgumentException('Ürün bulunamadı.');
            }
            $service->deleteProduct($id);
            Response::json([
                'success' => true,
                'products' => $service->products(),
                'message' => 'Ürün silindi.',
            ]);
            break;
        default:
            Response::json(['error' => true, 'message' => 'İzin verilmeyen istek.'], 405);
    }
} catch (Throwable $exception) {
    Response::json([
        'error' => true,
        'message' => $exception->getMessage(),
    ], 400);
}
