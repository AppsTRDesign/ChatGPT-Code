<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

final class JsonBodyParserMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Handler $handler): Response
    {
        $contentType = $request->getHeaderLine('Content-Type');

        if ($contentType && str_contains($contentType, 'application/json')) {
            $contents = (string) $request->getBody();
            if ($contents !== '') {
                $parsed = json_decode($contents, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                    $request = $request->withParsedBody($parsed);
                } else {
                    $request = $request->withAttribute('json_error', json_last_error_msg());
                }
            }
        }

        return $handler->handle($request);
    }
}
