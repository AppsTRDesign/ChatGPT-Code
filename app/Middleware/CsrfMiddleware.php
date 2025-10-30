<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response;

final class CsrfMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Handler $handler): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(16));
        }

        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE'], true)) {
            $body = $request->getParsedBody();
            $token = is_array($body) ? ($body['_token'] ?? '') : '';
            $headerToken = $request->getHeaderLine('X-CSRF-Token');
            $valid = hash_equals($_SESSION['_csrf_token'], $token ?: $headerToken);
            if (!$valid) {
                $response = new Response(419);
                $response->getBody()->write('CSRF token mismatch');
                return $response;
            }
        }

        $response = $handler->handle($request);
        return $response->withHeader('X-CSRF-Token', $_SESSION['_csrf_token']);
    }
}
