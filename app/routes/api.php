<?php
return function (Router $router) {
    $router->add('GET', '/api/menu', function () {
        $slug = $_GET['slug'] ?? null;
        if (!$slug) {
            return Response::json(['error' => 'Slug required'], 422);
        }
        return (new ApiController())->menu($slug);
    }, [ApiAuthMiddleware::class]);

    $router->add('POST', '/api/menu/order', function () {
        $data = json_decode(file_get_contents('php://input'), true);
        $slug = $data['slug'] ?? null;
        if (!$slug) {
            return Response::json(['error' => 'Slug required'], 422);
        }
        return (new ApiController())->createOrder($slug);
    }, [ApiAuthMiddleware::class]);

    $router->add('POST', '/api/menu/waiter-call', function () {
        $data = json_decode(file_get_contents('php://input'), true);
        $slug = $data['slug'] ?? null;
        if (!$slug) {
            return Response::json(['error' => 'Slug required'], 422);
        }
        return (new ApiController())->waiterCall($slug);
    }, [ApiAuthMiddleware::class]);

    $router->add('GET', '/api/menu/order-status', function () {
        $slug = $_GET['slug'] ?? null;
        $orderNumber = $_GET['order_number'] ?? null;
        if (!$slug || !$orderNumber) {
            return Response::json(['error' => 'Parametreler eksik'], 422);
        }
        return (new ApiController())->orderStatus($slug, $orderNumber);
    }, [ApiAuthMiddleware::class]);

    $router->add('GET', '/api/menu/currency', function () {
        $slug = $_GET['slug'] ?? null;
        if (!$slug) {
            return Response::json(['error' => 'Slug required'], 422);
        }
        return (new ApiController())->currencyRate($slug);
    }, [ApiAuthMiddleware::class]);

    $router->add('POST', '/api/menu/receipt', function () {
        $slug = $_GET['slug'] ?? null;
        if (!$slug) {
            return Response::json(['error' => 'Slug required'], 422);
        }
        return (new ApiController())->receipt($slug);
    }, [ApiAuthMiddleware::class]);
};
