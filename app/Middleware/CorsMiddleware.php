<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

final class CorsMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Handler $handler): Response
    {
        $response = $handler->handle($request);

        $allowedOrigins = explode(',', (string) ($_ENV['CORS_ALLOWED_ORIGINS'] ?? '*'));
        $origin = $request->getHeaderLine('Origin');
        $allowOrigin = in_array('*', $allowedOrigins, true) ? '*' : ($origin && in_array($origin, $allowedOrigins, true) ? $origin : '');

        if ($allowOrigin !== '') {
            $response = $response->withHeader('Access-Control-Allow-Origin', $allowOrigin);
        }

        $response = $response
            ->withHeader('Access-Control-Allow-Methods', $_ENV['CORS_ALLOWED_METHODS'] ?? 'GET,POST,OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', $_ENV['CORS_ALLOWED_HEADERS'] ?? 'Content-Type,Authorization')
            ->withHeader('Access-Control-Expose-Headers', $_ENV['CORS_EXPOSE_HEADERS'] ?? 'Retry-After')
            ->withHeader('Access-Control-Max-Age', (string) ($_ENV['CORS_MAX_AGE'] ?? '600'))
            ->withHeader('Vary', 'Origin');

        if ($request->getMethod() === 'OPTIONS') {
            return $response->withStatus(204);
        }

        return $response;
    }
}
