<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MapModel;
use App\Models\PlayerModel;
use App\Models\TokenModel;
use App\Models\UserModel;
use RuntimeException;

final class AuthService
{
    public function __construct(
        private readonly UserModel $userModel = new UserModel(),
        private readonly TokenModel $tokenModel = new TokenModel(),
        private readonly PlayerModel $playerModel = new PlayerModel(),
        private readonly MapModel $mapModel = new MapModel()
    ) {
    }

    public function register(string $username, string $email, string $password): array
    {
        if ($this->userModel->findByEmail($email)) {
            throw new RuntimeException('Email already registered');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $userId = $this->userModel->create($username, $email, $hash);

        $regions = $this->mapModel->regions();
        if (count($regions) === 0) {
            throw new RuntimeException('No regions seeded in database');
        }
        $spawnRegion = $regions[array_rand($regions)];
        $this->playerModel->createProfile($userId, (int) $spawnRegion['id'], (int) $spawnRegion['country_id']);
        $this->playerModel->createCitizenship($userId, (int) $spawnRegion['country_id']);

        return $this->issueToken($userId);
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userModel->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new RuntimeException('Invalid credentials');
        }

        return $this->issueToken((int) $user['id']);
    }

    public function resolveUserId(?string $bearerToken): ?int
    {
        if (!$bearerToken) {
            return null;
        }
        $tokenHash = hash('sha256', $bearerToken);
        return $this->tokenModel->findValidUserId($tokenHash);
    }

    private function issueToken(int $userId): array
    {
        $raw = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $raw);
        $this->tokenModel->create($userId, $tokenHash);

        return ['token' => $raw, 'user_id' => $userId];
    }
}
