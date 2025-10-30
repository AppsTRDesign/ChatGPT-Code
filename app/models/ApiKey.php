<?php
class ApiKey extends BaseModel
{
    public function generateForUser(int $userId): string
    {
        $key = bin2hex(random_bytes(32));
        $stmt = $this->db->prepare('UPDATE users SET api_key = :api_key WHERE id = :id');
        $stmt->execute(['api_key' => $key, 'id' => $userId]);
        return $key;
    }

    public function validate(string $key)
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE api_key = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        return $stmt->fetch();
    }
}
