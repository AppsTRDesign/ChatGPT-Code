<?php
class AdminController extends BaseController
{
    public function dashboard()
    {
        $restaurantModel = new Restaurant();
        $orderModel = new Order();
        $userModel = new User();
        $planModel = new Plan();

        $stats = [
            'restaurants' => count($restaurantModel->all()),
            'orders_today' => $this->countOrders(date('Y-m-d') . ' 00:00:00', date('Y-m-d') . ' 23:59:59'),
            'users' => count($userModel->allByRole('restaurant')),
            'plans' => $planModel->all(),
        ];

        return Response::json(['stats' => $stats]);
    }

    public function listRestaurants()
    {
        $restaurantModel = new Restaurant();
        return Response::json(['restaurants' => $restaurantModel->all()]);
    }

    public function approveRestaurant()
    {
        $data = $this->inputJson();
        (new Restaurant())->updateStatus((int)$data['restaurant_id'], 'active');
        (new User())->updateStatus((int)$data['user_id'], 'active');
        return Response::json(['message' => 'Restaurant approved']);
    }

    public function deleteRestaurant()
    {
        $data = $this->inputJson();
        $stmt = $this->db()->prepare('DELETE FROM restaurants WHERE id = :id');
        $stmt->execute(['id' => $data['restaurant_id']]);
        return Response::json(['message' => 'Restaurant deleted']);
    }

    public function settings()
    {
        $settings = new Setting();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return Response::json(['settings' => $settings->all()]);
        }

        $data = $this->inputJson();
        if (isset($data['currencies'])) {
            if (is_string($data['currencies'])) {
                $data['currencies'] = array_filter(array_map('trim', explode(',', $data['currencies'])));
            }
            if (is_array($data['currencies'])) {
                $data['currencies'] = array_values(array_unique(array_map('strtoupper', $data['currencies'])));
            } else {
                unset($data['currencies']);
            }
        }
        $settings->updateMany($data);
        return Response::json(['message' => 'Settings saved']);
    }

    private function countOrders(string $from, string $to): int
    {
        $stmt = $this->db()->prepare('SELECT COUNT(*) as total FROM orders WHERE created_at BETWEEN :from AND :to');
        $stmt->execute(['from' => $from, 'to' => $to]);
        $result = $stmt->fetch();
        return (int)$result['total'];
    }

    private function db(): PDO
    {
        global $container;
        return $container['db'];
    }
}
