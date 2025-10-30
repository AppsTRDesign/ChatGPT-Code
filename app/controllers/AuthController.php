<?php
class AuthController extends BaseController
{
    public function login()
    {
        $data = $this->inputJson();
        $errors = Validator::required($data, ['email', 'password']);
        if ($errors) {
            return Response::json(['errors' => $errors], 422);
        }

        $userModel = new User();
        $user = $userModel->findByEmail($data['email']);

        if (!$user || !Security::verifyPassword($data['password'], $user['password'])) {
            return Response::json(['error' => 'Invalid credentials'], 401);
        }

        if ($user['status'] !== 'active') {
            return Response::json(['error' => 'Account inactive'], 403);
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['restaurant_id'] = $user['restaurant_id'];

        return Response::json(['message' => 'Login successful', 'role' => $user['role']]);
    }

    public function register()
    {
        $data = $this->inputJson();
        $errors = Validator::required($data, ['name', 'email', 'password', 'restaurant_name']);
        if ($errors) {
            return Response::json(['errors' => $errors], 422);
        }

        if (!Validator::email($data['email'])) {
            return Response::json(['error' => 'Invalid email'], 422);
        }

        $userModel = new User();
        if ($userModel->findByEmail($data['email'])) {
            return Response::json(['error' => 'Email already registered'], 409);
        }

        $this->db()->beginTransaction();

        try {
            $userId = $userModel->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Security::hashPassword($data['password']),
                'role' => 'restaurant',
                'status' => 'active'
            ]);

            (new ApiKey())->generateForUser($userId);

            $restaurantModel = new Restaurant();
            $restaurantId = $restaurantModel->create([
                'name' => $data['restaurant_name'],
                'slug' => $this->generateSlug($data['restaurant_name']),
                'user_id' => $userId,
                'plan_id' => $data['plan_id'] ?? 1,
                'timezone' => $data['timezone'] ?? 'Europe/Istanbul',
                'theme' => 'light',
                'primary_color' => '#2563eb'
            ]);

            $this->db()->prepare('UPDATE users SET restaurant_id = :restaurant_id WHERE id = :id')->execute([
                'restaurant_id' => $restaurantId,
                'id' => $userId
            ]);

            $this->db()->commit();
        } catch (Exception $e) {
            $this->db()->rollBack();
            return Response::json(['error' => 'Registration failed'], 500);
        }

        return Response::json(['message' => 'Registration successful. Await approval.']);
    }

    public function logout()
    {
        session_destroy();
        return Response::json(['message' => 'Logged out']);
    }

    private function db(): PDO
    {
        global $container;
        return $container['db'];
    }

    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $restaurant = new Restaurant();
        $original = $slug;
        $counter = 1;
        while ($restaurant->findBySlug($slug)) {
            $slug = $original . '-' . $counter++;
        }
        return $slug;
    }
}
