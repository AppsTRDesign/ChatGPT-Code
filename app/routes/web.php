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
    $router->add('POST', '/dashboard/categories/upload', [new RestaurantController(), 'categoryUpload'], [AuthMiddleware::class]);

    $router->add('GET', '/dashboard/products', [new RestaurantController(), 'products'], [AuthMiddleware::class]);
    $router->add('POST', '/dashboard/products', [new RestaurantController(), 'products'], [AuthMiddleware::class]);
    $router->add('PUT', '/dashboard/products', [new RestaurantController(), 'products'], [AuthMiddleware::class]);
    $router->add('DELETE', '/dashboard/products', [new RestaurantController(), 'products'], [AuthMiddleware::class]);
    $router->add('POST', '/dashboard/products/upload', [new RestaurantController(), 'productUpload'], [AuthMiddleware::class]);

    $router->add('GET', '/dashboard/tables', [new RestaurantController(), 'tables'], [AuthMiddleware::class]);
    $router->add('POST', '/dashboard/tables', [new RestaurantController(), 'tables'], [AuthMiddleware::class]);
    $router->add('PUT', '/dashboard/tables', [new RestaurantController(), 'tables'], [AuthMiddleware::class]);
    $router->add('DELETE', '/dashboard/tables', [new RestaurantController(), 'tables'], [AuthMiddleware::class]);

    $router->add('GET', '/dashboard/orders', [new RestaurantController(), 'orders'], [AuthMiddleware::class]);
    $router->add('PUT', '/dashboard/orders', [new RestaurantController(), 'orders'], [AuthMiddleware::class]);
    $router->add('POST', '/dashboard/orders/export', [new RestaurantController(), 'export'], [AuthMiddleware::class]);
    $router->add('POST', '/dashboard/orders/receipt', [new RestaurantController(), 'receipt'], [AuthMiddleware::class]);

    $router->add('GET', '/dashboard/calls', [new RestaurantController(), 'calls'], [AuthMiddleware::class]);
    $router->add('PUT', '/dashboard/calls', [new RestaurantController(), 'calls'], [AuthMiddleware::class]);

    $router->add('POST', '/dashboard/reports', [new RestaurantController(), 'reports'], [AuthMiddleware::class]);

    $router->add('GET', '/dashboard/settings', [new RestaurantController(), 'settings'], [AuthMiddleware::class]);
    $router->add('PUT', '/dashboard/settings', [new RestaurantController(), 'settings'], [AuthMiddleware::class]);
    $router->add('POST', '/dashboard/settings', [new RestaurantController(), 'settings'], [AuthMiddleware::class]);
    $router->add('GET', '/dashboard/settings/api', [new RestaurantController(), 'api'], [AuthMiddleware::class]);
    $router->add('POST', '/dashboard/settings/api', [new RestaurantController(), 'api'], [AuthMiddleware::class]);

    $router->add('GET', '/dashboard/theme', [new RestaurantController(), 'theme'], [AuthMiddleware::class]);
    $router->add('PUT', '/dashboard/theme', [new RestaurantController(), 'theme'], [AuthMiddleware::class]);
};
