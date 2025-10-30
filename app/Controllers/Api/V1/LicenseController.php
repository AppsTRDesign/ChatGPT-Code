<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Models\Product;
use App\Services\LicenseService;
use App\Services\WebhookService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class LicenseController
{
    public function __construct(
        private readonly LicenseService $licenseService,
        private readonly WebhookService $webhookService
    ) {
    }

    public function issue(Request $request, Response $response): Response
    {
        return $this->execute($response, function () use ($request, $response) {
            $input = $this->validateJson($request, ['product_code', 'type']);
            $actor = $request->getAttribute('auth_user');
            $client = $request->getAttribute('auth_client');

            $result = $this->licenseService->issue(
                $input,
                $actor,
                $client,
                $this->clientIp($request),
                $request->getHeaderLine('User-Agent')
            );

            return $this->json($response, $result);
        });
    }

    public function validate(Request $request, Response $response): Response
    {
        return $this->execute($response, function () use ($request, $response) {
            $input = $this->validateJson($request, ['product_code', 'key']);
            $input['ip'] = $this->clientIp($request);
            $result = $this->licenseService->validate(
                $input,
                $this->clientIp($request),
                $request->getHeaderLine('User-Agent')
            );

            return $this->json($response->withStatus($result['http_status']), $result);
        });
    }

    public function heartbeat(Request $request, Response $response): Response
    {
        return $this->execute($response, function () use ($request, $response) {
            $input = $this->validateJson($request, ['product_code', 'key']);
            $input['ip'] = $this->clientIp($request);
            $result = $this->licenseService->heartbeat(
                $input,
                $this->clientIp($request),
                $request->getHeaderLine('User-Agent')
            );

            return $this->json($response->withStatus($result['http_status']), $result);
        });
    }

    public function revoke(Request $request, Response $response): Response
    {
        return $this->execute($response, function () use ($request, $response) {
            $input = $this->validateJson($request, []);
            $result = $this->licenseService->revoke(
                $input,
                $this->clientIp($request),
                $request->getHeaderLine('User-Agent')
            );

            return $this->json($response, $result);
        });
    }

    public function refresh(Request $request, Response $response): Response
    {
        return $this->execute($response, function () use ($request, $response) {
            $input = $this->validateJson($request, ['key']);
            $result = $this->licenseService->refresh(
                $input,
                $this->clientIp($request),
                $request->getHeaderLine('User-Agent')
            );

            return $this->json($response, $result);
        });
    }

    public function bind(Request $request, Response $response): Response
    {
        return $this->execute($response, function () use ($request, $response) {
            $input = $this->validateJson($request, ['key']);
            $result = $this->licenseService->bind(
                $input,
                $this->clientIp($request),
                $request->getHeaderLine('User-Agent')
            );

            $status = $result['status'] ?? null;
            $httpStatus = $status === 'seat_limit' ? 409 : 200;

            return $this->json($response->withStatus($httpStatus), $result);
        });
    }

    public function unbind(Request $request, Response $response): Response
    {
        return $this->execute($response, function () use ($request, $response) {
            $input = $this->validateJson($request, ['key']);
            $result = $this->licenseService->unbind(
                $input,
                $this->clientIp($request),
                $request->getHeaderLine('User-Agent')
            );

            return $this->json($response, $result);
        });
    }

    public function status(Request $request, Response $response, array $args): Response
    {
        return $this->execute($response, function () use ($response, $args) {
            $key = $args['key'];
            $result = $this->licenseService->status($key);
            return $this->json($response, $result);
        });
    }

    public function webhookTest(Request $request, Response $response): Response
    {
        return $this->execute($response, function () use ($request, $response) {
            $input = $this->validateJson($request, ['product_code', 'event_type']);
            $product = Product::query()->where('code', $input['product_code'])->firstOrFail();

            $payload = [
                'event' => $input['event_type'],
                'triggered_by' => $request->getAttribute('auth_user')?->id,
            ];

            $this->webhookService->dispatch($product, $input['event_type'], $payload);

            return $this->json($response, ['status' => 'sent']);
        });
    }

    /**
     * @param string[] $required
     * @return array<string, mixed>
     */
    private function validateJson(Request $request, array $required): array
    {
        if ($request->getAttribute('json_error')) {
            throw new \InvalidArgumentException('Invalid JSON: ' . $request->getAttribute('json_error'));
        }

        $data = $request->getParsedBody();
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invalid payload');
        }

        foreach ($required as $field) {
            if (!array_key_exists($field, $data)) {
                throw new \InvalidArgumentException("Field {$field} is required");
            }
        }

        return $data;
    }

    private function execute(Response $response, callable $callback): Response
    {
        try {
            return $callback();
        } catch (\InvalidArgumentException $exception) {
            return $this->json($response->withStatus(422), [
                'error' => 'validation_error',
                'message' => $exception->getMessage(),
            ]);
        } catch (\RuntimeException $exception) {
            return $this->json($response->withStatus(400), [
                'error' => 'bad_request',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function json(Response $response, array $payload): Response
    {
        $response->getBody()->write(json_encode($payload, JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function clientIp(Request $request): string
    {
        return $request->getServerParams()['HTTP_X_FORWARDED_FOR']
            ?? $request->getServerParams()['REMOTE_ADDR']
            ?? '0.0.0.0';
    }
}
