<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\UserModel;
use RuntimeException;

final class AuthService
{
    public function __construct(
        private readonly UserModel $userModel = new UserModel(),
        private readonly LocationService $locationService = new LocationService()
    ) {
    }

    public function register(string $username, string $email, string $password): array
    {
        if ($this->userModel->findByEmail($email)) {
            throw new RuntimeException('Email already registered');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo = Database::connection();

        try {
            $pdo->beginTransaction();
            $userId = $this->userModel->create($username, $email, $hash);
            $this->locationService->assignPlayerLocation($userId);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new RuntimeException('Registration failed: ' . $e->getMessage());
        }

        return ['user_id' => $userId];
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userModel->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new RuntimeException('Invalid credentials');
        }

        return ['user_id' => (int) $user['id']];
    }
}
