<?php
class ApiController extends BaseController
{
    public function menu(string $slug)
    {
        $restaurantModel = new Restaurant();
        $restaurant = $restaurantModel->findBySlug($slug);
        if (!$restaurant || $restaurant['status'] !== 'active') {
            return Response::json(['error' => 'Restaurant not found'], 404);
        }

        $categoryModel = new Category();
        $productModel = new Product();
        $categories = $categoryModel->allByRestaurant($restaurant['id']);
        $products = $productModel->allByRestaurant($restaurant['id']);

        $grouped = [];
        foreach ($categories as $category) {
            $grouped[$category['id']] = $category;
            $grouped[$category['id']]['products'] = [];
        }
        foreach ($products as $product) {
            $grouped[$product['category_id']]['products'][] = $product;
        }

        return Response::json([
            'restaurant' => $restaurant,
            'categories' => array_values($grouped)
        ]);
    }

    public function createOrder(string $slug)
    {
        $restaurantModel = new Restaurant();
        $restaurant = $restaurantModel->findBySlug($slug);
        if (!$restaurant || $restaurant['status'] !== 'active') {
            return Response::json(['error' => 'Restaurant not found'], 404);
        }

        $data = $this->inputJson();
        $errors = Validator::required($data, ['table_number', 'items', 'total_amount']);
        if ($errors) {
            return Response::json(['errors' => $errors], 422);
        }

        $orderModel = new Order();
        $orderId = $orderModel->create([
            'restaurant_id' => $restaurant['id'],
            'table_number' => $data['table_number'],
            'customer_note' => $data['customer_note'] ?? '',
            'items' => $data['items'],
            'total_amount' => $data['total_amount'],
            'locale' => $data['locale'] ?? 'en'
        ]);

        SocketNotifier::notify([
            'event' => 'order:new',
            'restaurant_id' => $restaurant['id'],
            'order_id' => $orderId,
            'table_number' => $data['table_number']
        ]);

        return Response::json(['message' => 'Order received']);
    }
}
