<?php
class Plan extends BaseModel
{
    public function all()
    {
        return $this->db->query('SELECT p.*, (SELECT COUNT(*) FROM restaurants r WHERE r.plan_id = p.id) AS restaurant_count FROM plans p ORDER BY price ASC')->fetchAll();
    }
}
