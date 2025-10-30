<?php
class User extends BaseModel
{
    public function findByEmail(string $email)
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function create(array $data)
    {
        $stmt = $this->db->prepare('INSERT INTO users (name, email, password, role, restaurant_id, status) VALUES (:name, :email, :password, :role, :restaurant_id, :status)');
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'] ?? 'restaurant',
            'restaurant_id' => $data['restaurant_id'] ?? null,
            'status' => $data['status'] ?? 'pending'
        ]);
        return $this->db->lastInsertId();
    }

    public function allByRole(string $role)
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE role = :role ORDER BY created_at DESC');
        $stmt->execute(['role' => $role]);
        return $stmt->fetchAll();
    }

    public function updateStatus(int $userId, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET status = :status WHERE id = :id');
        return $stmt->execute(['status' => $status, 'id' => $userId]);
    }

    public function updatePassword(int $userId, string $hash): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET password = :password WHERE id = :id');
        return $stmt->execute(['password' => $hash, 'id' => $userId]);
    }

    public function find(int $id)
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
}
