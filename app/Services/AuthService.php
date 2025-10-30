<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApiClient;
use App\Models\User;
use App\Support\NonceStore;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Carbon;
use Psr\Http\Message\ServerRequestInterface;

final class AuthService
{
    private NonceStore $nonceStore;

    public function __construct()
    {
        $this->nonceStore = new NonceStore();
    }

    public function authenticateJwt(string $authorizationHeader): ?User
    {
        if (!str_starts_with(strtolower($authorizationHeader), 'bearer ')) {
            return null;
        }

        $token = trim(substr($authorizationHeader, 7));
        if ($token === '') {
            return null;
        }

        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'] ?? '', 'HS256'));
        } catch (\Throwable) {
            return null;
        }

        if (!isset($decoded->sub)) {
            return null;
        }

        /** @var User|null $user */
        $user = User::query()->find($decoded->sub);
        return $user;
    }

    public function authenticateHmac(string $accessKey, string $signature, string $timestamp, string $nonce, ServerRequestInterface $request): ?ApiClient
    {
        if ($signature === '' || $timestamp === '' || $nonce === '') {
            return null;
        }

        $allowedDrift = 300;
        if (abs(time() - (int) $timestamp) > $allowedDrift) {
            return null;
        }

        if ($this->nonceStore->has($accessKey, $nonce)) {
            return null;
        }

        /** @var ApiClient|null $client */
        $client = ApiClient::query()->where('access_key', $accessKey)->first();
        if ($client === null) {
            return null;
        }

        $bodyStream = $request->getBody();
        $bodyStream->rewind();
        $body = $bodyStream->getContents();
        $bodyStream->rewind();

        $algo = $_ENV['HMAC_ALGO'] ?? 'sha256';
        $bodyHash = hash($algo, $body);
        $baseString = implode('\n', [$accessKey, $timestamp, $nonce, $bodyHash]);
        $expected = hash_hmac($algo, $baseString, $client->secret_key);

        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $this->nonceStore->put($accessKey, $nonce, 600);

        return $client;
    }

    public function createJwtForUser(User $user): string
    {
        $payload = [
            'sub' => $user->id,
            'iss' => $_ENV['JWT_ISSUER'] ?? 'licenses',
            'aud' => $_ENV['JWT_AUDIENCE'] ?? 'licenses_api',
            'iat' => time(),
            'exp' => time() + (int) ($_ENV['JWT_TTL'] ?? 3600),
        ];

        return JWT::encode($payload, $_ENV['JWT_SECRET'] ?? '', 'HS256');
    }

    public function attemptLogin(string $email, string $password): ?User
    {
        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            return null;
        }

        if (!password_verify($password, $user->password)) {
            return null;
        }

        return $user;
    }

    public function createRefreshToken(User $user): string
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expiresAt = Carbon::now()->addSeconds((int) ($_ENV['JWT_REFRESH_TTL'] ?? 1209600));

        $user->refreshTokens()->create([
            'token_hash' => $hash,
            'expires_at' => $expiresAt,
        ]);

        return $token;
    }

    public function validateRefreshToken(User $user, string $token): bool
    {
        $hash = hash('sha256', $token);
        return $user->refreshTokens()
            ->where('token_hash', $hash)
            ->where('expires_at', '>', Carbon::now())
            ->exists();
    }
}
