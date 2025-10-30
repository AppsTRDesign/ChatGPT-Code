<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response;

final class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function process(Request $request, Handler $handler): Response
    {
        $route = $request->getAttribute('route');
        if ($route === null) {
            return $handler->handle($request);
        }

        $requirement = $route->getArgument('auth') ?? 'optional';
        $authContext = [];

        $jwt = $request->getHeaderLine('Authorization');
        if ($jwt !== '') {
            $user = $this->authService->authenticateJwt($jwt);
            if ($user !== null) {
                $authContext['user'] = $user;
            }
        }

        $accessKey = $request->getHeaderLine('X-Access-Key');
        $signature = $request->getHeaderLine('X-Signature');
        $timestamp = $request->getHeaderLine('X-Timestamp');
        $nonce = $request->getHeaderLine('X-Nonce');

        if ($accessKey !== '') {
            $client = $this->authService->authenticateHmac($accessKey, $signature, $timestamp, $nonce, $request);
            if ($client !== null) {
                $authContext['client'] = $client;
            }
        }

        if ($requirement === 'jwt' && !isset($authContext['user'])) {
            return $this->unauthorized('jwt_required');
        }

        if ($requirement === 'hmac' && !isset($authContext['client'])) {
            return $this->unauthorized('hmac_required');
        }

        if ($requirement === 'mixed' && empty($authContext)) {
            return $this->unauthorized('authentication_required');
        }

        foreach ($authContext as $key => $value) {
            $request = $request->withAttribute('auth_' . $key, $value);
        }

        return $handler->handle($request);
    }

    private function unauthorized(string $reason): Response
    {
        $response = new Response(401);
        $response->getBody()->write(json_encode([
            'error' => 'unauthorized',
            'message' => $reason,
        ], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
