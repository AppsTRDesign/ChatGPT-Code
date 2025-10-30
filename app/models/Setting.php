<?php
class Setting extends BaseModel
{
    public function all()
    {
        $rows = $this->db->query('SELECT `key`, `value` FROM settings')->fetchAll();
        return array_map(fn($row) => [
            'key' => $row['key'],
            'value' => $this->decodeValue($row['key'], $row['value']),
        ], $rows);
    }

    public function updateMany(array $data)
    {
        $stmt = $this->db->prepare('REPLACE INTO settings (`key`, `value`) VALUES (:key, :value)');
        $this->db->beginTransaction();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode(array_values(array_unique(array_filter($value))), JSON_UNESCAPED_UNICODE);
            }
            $stmt->execute([
                'key' => $key,
                'value' => $value,
            ]);
        }
        $this->db->commit();
    }

    public function get(string $key, $default = null)
    {
        $stmt = $this->db->prepare('SELECT `value` FROM settings WHERE `key` = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        $row = $stmt->fetch();
        if (!$row) {
            return $default;
        }
        return $this->decodeValue($key, $row['value']);
    }

    private function decodeValue(string $key, $value)
    {
        if (in_array($key, ['currencies'], true)) {
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return array_values(array_unique(array_map('strtoupper', array_filter($decoded))));
                }
            }
            if (is_array($value)) {
                return array_values(array_unique(array_map('strtoupper', array_filter($value))));
            }
            return [];
        }
        return $value;
    }
}
