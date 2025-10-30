<?php
class Setting extends BaseModel
{
    public function all()
    {
        return $this->db->query('SELECT `key`, `value` FROM settings')->fetchAll();
    }

    public function updateMany(array $data)
    {
        $stmt = $this->db->prepare('REPLACE INTO settings (`key`, `value`) VALUES (:key, :value)');
        $this->db->beginTransaction();
        foreach ($data as $key => $value) {
            $stmt->execute([
                'key' => $key,
                'value' => $value,
            ]);
        }
        $this->db->commit();
    }
}
