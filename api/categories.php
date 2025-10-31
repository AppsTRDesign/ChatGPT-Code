<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Services\MenuService;
use Core\Response;

$service = new MenuService(1);

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            Response::json([
                'categories' => $service->categories(),
            ]);
            break;
        case 'POST':
            $payload = json_decode(file_get_contents('php://input'), true) ?? [];
            $category = $service->saveCategory($payload);
            Response::json([
                'success' => true,
                'category' => $category,
                'categories' => $service->categories(),
                'message' => 'Kategori kaydedildi.',
            ]);
            break;
        case 'DELETE':
            parse_str($_SERVER['QUERY_STRING'] ?? '', $query);
            $id = isset($query['id']) ? (int)$query['id'] : 0;
            if ($id <= 0) {
                throw new InvalidArgumentException('Kategori bulunamadı.');
            }
            $service->deleteCategory($id);
            Response::json([
                'success' => true,
                'categories' => $service->categories(),
                'message' => 'Kategori silindi.',
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
