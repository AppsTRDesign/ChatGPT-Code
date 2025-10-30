<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\RateLimiterService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response;

final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly RateLimiterService $rateLimiter)
    {
    }

    public function process(Request $request, Handler $handler): Response
    {
        $clientKey = $request->getHeaderLine('X-Access-Key') ?: $request->getAttribute('auth_client_key', 'public');
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $route = $request->getUri()->getPath();
        $client = $request->getAttribute('auth_client');
        $limit = $client->rate_limit_per_min ?? null;

        if (!$this->rateLimiter->allow($clientKey, $ip, $route, $limit)) {
            $response = new Response(429);
            $response->getBody()->write(json_encode([
                'error' => 'rate_limit_exceeded',
                'message' => 'Too many requests',
            ], JSON_THROW_ON_ERROR));

            return $response
                ->withHeader('Retry-After', (string) $this->rateLimiter->getRetryAfterSeconds($clientKey))
                ->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }
}
