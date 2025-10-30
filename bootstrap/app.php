<?php

declare(strict_types=1);

use App\Http\ViewRenderer;
use App\Middleware\AdminAuthMiddleware;
use App\Middleware\AuthenticationMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\JsonBodyParserMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use App\Services\AuthService;
use App\Services\BindingService;
use App\Services\LicenseService;
use App\Services\RateLimiterService;
use App\Services\WebhookService;
use DI\ContainerBuilder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Middleware\ErrorMiddleware;

require_once __DIR__ . '/../vendor/autoload.php';

(Dotenv\Dotenv::createImmutable(dirname(__DIR__)))->safeLoad();

date_default_timezone_set($_ENV['TIMEZONE'] ?? 'UTC');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([
    Logger::class => function (): Logger {
        $logPath = $_ENV['LOG_PATH'] ?? __DIR__ . '/../storage/logs/app.log';
        $logger = new Logger('licenses');
        $logger->pushHandler(new RotatingFileHandler($logPath, 30, $_ENV['LOG_LEVEL'] ?? Logger::INFO));
        return $logger;
    },
    Capsule::class => function () {
        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => $_ENV['DB_CONNECTION'] ?? 'mysql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['DB_PORT'] ?? 3306,
            'database' => $_ENV['DB_DATABASE'] ?? 'licenses',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        return $capsule;
    },
    LicenseService::class => DI\autowire(LicenseService::class),
    BindingService::class => DI\autowire(BindingService::class),
    WebhookService::class => DI\autowire(WebhookService::class),
    AuthService::class => DI\autowire(AuthService::class),
    RateLimiterService::class => DI\autowire(RateLimiterService::class),
    ViewRenderer::class => DI\autowire(ViewRenderer::class),
    CsrfMiddleware::class => DI\autowire(CsrfMiddleware::class),
    AdminAuthMiddleware::class => DI\autowire(AdminAuthMiddleware::class),
]);

$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->add(new CorsMiddleware());
$app->add(new SecurityHeadersMiddleware());
$app->add(new JsonBodyParserMiddleware());
$app->add(new RateLimitMiddleware($container->get(RateLimiterService::class)));
$app->add(new AuthenticationMiddleware($container->get(AuthService::class)));

$errorMiddleware = new ErrorMiddleware(
    $app->getCallableResolver(),
    $app->getResponseFactory(),
    (bool) ($_ENV['APP_DEBUG'] ?? false),
    true,
    true
);
$app->add($errorMiddleware);

(require __DIR__ . '/../routes/api.php')($app);
(require __DIR__ . '/../routes/web.php')($app);

return $app;
