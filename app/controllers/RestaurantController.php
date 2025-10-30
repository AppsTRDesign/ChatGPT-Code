<?php
class RestaurantController extends BaseController
{
    public function categories()
    {
        $restaurantId = $_SESSION['restaurant_id'];
        $categoryModel = new Category();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return Response::json(['categories' => $categoryModel->allByRestaurant($restaurantId)]);
        }

        $data = $this->inputJson();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $categoryModel->create([
                'restaurant_id' => $restaurantId,
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
            return Response::json(['message' => 'Category created']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $categoryModel->update((int)$data['id'], $data);
            return Response::json(['message' => 'Category updated']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $categoryModel->delete((int)$data['id']);
            return Response::json(['message' => 'Category deleted']);
        }

        return Response::json(['error' => 'Unsupported method'], 405);
    }

    public function products()
    {
        $restaurantId = $_SESSION['restaurant_id'];
        $productModel = new Product();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return Response::json(['products' => $productModel->allByRestaurant($restaurantId)]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
            $config = require __DIR__ . '/../config/config.php';
            $upload = FileUploader::uploadToCloudinary($_FILES['image'], $config['upload']);
            $data = Security::sanitize($_POST);
            $data['image_url'] = $upload['secure_url'] ?? null;
            $productModel->create(array_merge($data, ['restaurant_id' => $restaurantId]));
            return Response::json(['message' => 'Product created']);
        }

        $data = $this->inputJson();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productModel->create(array_merge($data, ['restaurant_id' => $restaurantId]));
            return Response::json(['message' => 'Product created']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $productModel->update((int)$data['id'], $data);
            return Response::json(['message' => 'Product updated']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $productModel->delete((int)$data['id']);
            return Response::json(['message' => 'Product deleted']);
        }

        return Response::json(['error' => 'Unsupported method'], 405);
    }

    public function orders()
    {
        $restaurantId = $_SESSION['restaurant_id'];
        $orderModel = new Order();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return Response::json(['orders' => $orderModel->allByRestaurant($restaurantId)]);
        }

        $data = $this->inputJson();
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $orderModel->updateStatus((int)$data['order_id'], $data['status']);
            SocketNotifier::notify([
                'event' => 'order:update',
                'restaurant_id' => $restaurantId,
                'order_id' => $data['order_id'],
                'status' => $data['status']
            ]);
            return Response::json(['message' => 'Order updated']);
        }

        return Response::json(['error' => 'Unsupported method'], 405);
    }

    public function reports()
    {
        $restaurantId = $_SESSION['restaurant_id'];
        $orderModel = new Order();
        $data = $this->inputJson();
        $from = $data['from'] ?? date('Y-m-01 00:00:00');
        $to = $data['to'] ?? date('Y-m-t 23:59:59');
        $report = $orderModel->report($restaurantId, $from, $to);
        return Response::json(['report' => $report]);
    }

    public function theme()
    {
        $restaurantId = $_SESSION['restaurant_id'];
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $restaurant = (new Restaurant())->find($restaurantId);
            return Response::json(['theme' => [
                'theme' => $restaurant['theme'],
                'primary_color' => $restaurant['primary_color'],
                'slug' => $restaurant['slug']
            ]]);
        }

        $data = $this->inputJson();
        $stmt = $this->db()->prepare('UPDATE restaurants SET theme = :theme, primary_color = :primary_color WHERE id = :id');
        $stmt->execute([
            'theme' => $data['theme'],
            'primary_color' => $data['primary_color'],
            'id' => $restaurantId
        ]);
        return Response::json(['message' => 'Theme updated']);
    }

    private function db(): PDO
    {
        global $container;
        return $container['db'];
    }
}
