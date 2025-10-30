<?php
return function (Router $router) {
    $router->add('POST', '/auth/login', [new AuthController(), 'login']);
    $router->add('POST', '/auth/register', [new AuthController(), 'register']);
    $router->add('POST', '/auth/logout', [new AuthController(), 'logout'], [AuthMiddleware::class]);

    $router->add('GET', '/admin/dashboard', [new AdminController(), 'dashboard'], [AuthMiddleware::class, AdminMiddleware::class]);
    $router->add('GET', '/admin/restaurants', [new AdminController(), 'listRestaurants'], [AuthMiddleware::class, AdminMiddleware::class]);
    $router->add('POST', '/admin/restaurants/approve', [new AdminController(), 'approveRestaurant'], [AuthMiddleware::class, AdminMiddleware::class]);
    $router->add('DELETE', '/admin/restaurants/delete', [new AdminController(), 'deleteRestaurant'], [AuthMiddleware::class, AdminMiddleware::class]);
    $router->add('GET', '/admin/settings', [new AdminController(), 'settings'], [AuthMiddleware::class, AdminMiddleware::class]);
    $router->add('PUT', '/admin/settings', [new AdminController(), 'settings'], [AuthMiddleware::class, AdminMiddleware::class]);

    $router->add('GET', '/dashboard/categories', [new RestaurantController(), 'categories'], [AuthMiddleware::class]);
    $router->add('POST', '/dashboard/categories', [new RestaurantController(), 'categories'], [AuthMiddleware::class]);
    $router->add('PUT', '/dashboard/categories', [new RestaurantController(), 'categories'], [AuthMiddleware::class]);
    $router->add('DELETE', '/dashboard/categories', [new RestaurantController(), 'categories'], [AuthMiddleware::class]);

    $router->add('GET', '/dashboard/products', [new RestaurantController(), 'products'], [AuthMiddleware::class]);
    $router->add('POST', '/dashboard/products', [new RestaurantController(), 'products'], [AuthMiddleware::class]);
    $router->add('PUT', '/dashboard/products', [new RestaurantController(), 'products'], [AuthMiddleware::class]);
    $router->add('DELETE', '/dashboard/products', [new RestaurantController(), 'products'], [AuthMiddleware::class]);
    $router->add('POST', '/dashboard/products/upload', [new RestaurantController(), 'uploadProductImage'], [AuthMiddleware::class]);

    $router->add('GET', '/dashboard/orders', [new RestaurantController(), 'orders'], [AuthMiddleware::class]);
    $router->add('PUT', '/dashboard/orders', [new RestaurantController(), 'orders'], [AuthMiddleware::class]);

    $router->add('POST', '/dashboard/reports', [new RestaurantController(), 'reports'], [AuthMiddleware::class]);
    $router->add('GET', '/dashboard/theme', [new RestaurantController(), 'theme'], [AuthMiddleware::class]);
    $router->add('PUT', '/dashboard/theme', [new RestaurantController(), 'theme'], [AuthMiddleware::class]);
};
