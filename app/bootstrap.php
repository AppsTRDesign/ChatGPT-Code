<?php
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

spl_autoload_register(function ($class) {
    $baseDir = __DIR__;
    $paths = [
        'controllers',
        'models',
        'middlewares',
        'utils',
    ];
    foreach ($paths as $path) {
        $file = $baseDir . '/' . $path . '/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

$config = require __DIR__ . '/config/config.php';
$pdo = require __DIR__ . '/config/database.php';

$container = [
    'config' => $config,
    'db' => $pdo,
];

$expectsJson = function (): bool {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    return str_contains($accept, 'application/json')
        || str_contains($contentType, 'application/json')
        || strtolower($requestedWith) === 'xmlhttprequest'
        || in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'PATCH', 'DELETE']);
};

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function (Throwable $exception) use ($config, $expectsJson) {
    http_response_code(500);
    if ($expectsJson()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error' => 'Server error',
            'message' => $config['debug'] ? $exception->getMessage() : 'Beklenmeyen bir hata oluştu'
        ]);
    } else {
        header('Content-Type: text/html; charset=utf-8');
        if ($config['debug']) {
            if (class_exists('Symfony\\Component\\VarDumper\\VarDumper')) {
                \Symfony\Component\VarDumper\VarDumper::dump($exception);
            } else {
                echo '<pre>' . htmlspecialchars((string)$exception, ENT_QUOTES, 'UTF-8') . '</pre>';
            }
        } else {
            echo '<h1>Sunucu hatası</h1>';
        }
    }
    exit;
});

register_shutdown_function(function () use ($config, $expectsJson) {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        if ($expectsJson()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'Server error',
                'message' => $config['debug'] ? $error['message'] : 'Beklenmeyen bir hata oluştu'
            ]);
        } else {
            header('Content-Type: text/html; charset=utf-8');
            if ($config['debug'] && class_exists('Symfony\\Component\\VarDumper\\VarDumper')) {
                \Symfony\Component\VarDumper\VarDumper::dump($error);
            } elseif ($config['debug']) {
                echo '<pre>' . htmlspecialchars(print_r($error, true), ENT_QUOTES, 'UTF-8') . '</pre>';
            } else {
                echo '<h1>Sunucu hatası</h1>';
            }
        }
    }
});
