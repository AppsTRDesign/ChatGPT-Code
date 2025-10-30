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
};
