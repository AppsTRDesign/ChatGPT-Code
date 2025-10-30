<?php
class ApiController extends BaseController
{
    private function apiUser()
    {
        return $_SERVER['api_user'] ?? null;
    }

    private function availableCurrencies(?array $restaurant = null): array
    {
        $settings = new Setting();
        $currencies = $settings->get('currencies', []);
        if (!is_array($currencies)) {
            $currencies = [];
        }
        if ($restaurant && !in_array($restaurant['currency'] ?? 'TRY', $currencies, true)) {
            $currencies[] = strtoupper($restaurant['currency']);
        }
        if (!$currencies) {
            $currencies = ['TRY'];
        }
        return array_values(array_unique(array_map('strtoupper', $currencies)));
    }

    public function menu(string $slug)
    {
        $restaurantModel = new Restaurant();
        $token = $_GET['token'] ?? null;
        $restaurant = $token ? $restaurantModel->findByTableToken($slug, $token) : $restaurantModel->findBySlug($slug);
        if (!$restaurant || $restaurant['status'] !== 'active') {
            return Response::json(['error' => 'Restoran bulunamadı'], 404);
        }

        $categoryModel = new Category();
        $categories = $categoryModel->withProducts($restaurant['id']);
        return Response::json([
            'restaurant' => [
                'id' => $restaurant['id'],
                'name' => $restaurant['name'],
                'description' => $restaurant['description'],
                'currency' => $restaurant['currency'],
                'primary_language' => $restaurant['primary_language'],
                'supported_languages' => $restaurant['supported_languages'],
                'theme' => $restaurant['theme'],
                'primary_color' => $restaurant['primary_color'],
                'logo_url' => $restaurant['logo_url'],
                'favicon_url' => $restaurant['favicon_url'],
                'menu_layout' => $restaurant['menu_layout'],
                'qr_table_prefix' => $restaurant['qr_table_prefix'],
            ],
            'table' => $token ? [
                'id' => $restaurant['table_id'],
                'name' => $restaurant['table_name'],
                'slug' => $restaurant['table_slug'],
                'token' => $restaurant['qr_token'],
            ] : null,
            'categories' => $categories,
            'available_currencies' => $this->availableCurrencies($restaurant),
        ]);
    }

    public function createOrder(string $slug)
    {
        $restaurantModel = new Restaurant();
        $data = $this->inputJson();
        $token = $data['table_token'] ?? null;
        $restaurant = $token ? $restaurantModel->findByTableToken($slug, $token) : $restaurantModel->findBySlug($slug);
        if (!$restaurant || $restaurant['status'] !== 'active') {
            return Response::json(['error' => 'Restoran bulunamadı'], 404);
        }
        if (!$token || !$restaurant['table_id']) {
            return Response::json(['error' => 'Masa doğrulanamadı'], 422);
        }

        $errors = Validator::required($data, ['items', 'total_amount']);
        if ($errors) {
            return Response::json(['errors' => $errors], 422);
        }
        if (!is_array($data['items']) || !count($data['items'])) {
            return Response::json(['error' => 'Sepet boş'], 422);
        }

        $orderModel = new Order();
        $orderNumber = $this->generateOrderNumber($restaurant['id']);
        $orderId = $orderModel->create([
            'restaurant_id' => $restaurant['id'],
            'table_id' => $restaurant['table_id'],
            'table_number' => $restaurant['table_name'] ?? $restaurant['qr_table_prefix'],
            'order_number' => $orderNumber,
            'customer_note' => $data['customer_note'] ?? '',
            'items' => $data['items'],
            'total_amount' => $data['total_amount'],
            'currency' => $restaurant['currency'] ?? 'TRY',
            'locale' => $data['locale'] ?? $restaurant['primary_language'],
        ]);

        (new RestaurantTable())->updateStatus((int)$restaurant['table_id'], 'occupied');
        SocketNotifier::notify([
            'event' => 'table:status',
            'restaurant_id' => $restaurant['id'],
            'table_id' => (int)$restaurant['table_id'],
            'status' => 'occupied',
            'table_token' => $restaurant['qr_token'] ?? null,
        ]);

        SocketNotifier::notify([
            'event' => 'order:new',
            'restaurant_id' => $restaurant['id'],
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'table_number' => $restaurant['table_name'],
            'table_token' => $restaurant['qr_token'],
        ]);

        return Response::json([
            'message' => 'Sipariş alındı',
            'order_number' => $orderNumber,
        ], 201);
    }

    public function waiterCall(string $slug)
    {
        $restaurantModel = new Restaurant();
        $data = $this->inputJson();
        $token = $data['table_token'] ?? null;
        $restaurant = $token ? $restaurantModel->findByTableToken($slug, $token) : null;
        if (!$restaurant || $restaurant['status'] !== 'active') {
            return Response::json(['error' => 'Restoran bulunamadı'], 404);
        }
        $callModel = new WaiterCall();
        $callId = $callModel->create([
            'restaurant_id' => $restaurant['id'],
            'table_id' => $restaurant['table_id'],
            'table_number' => $restaurant['table_name'],
        ]);
        (new RestaurantTable())->updateStatus((int)$restaurant['table_id'], 'occupied');
        SocketNotifier::notify([
            'event' => 'table:status',
            'restaurant_id' => $restaurant['id'],
            'table_id' => (int)$restaurant['table_id'],
            'status' => 'occupied',
            'table_token' => $restaurant['qr_token'] ?? null,
        ]);
        SocketNotifier::notify([
            'event' => 'waiter:call',
            'restaurant_id' => $restaurant['id'],
            'table_number' => $restaurant['table_name'],
            'call_id' => $callId,
        ]);
        return Response::json(['message' => 'Garson çağrısı iletildi']);
    }

    public function orderStatus(string $slug, string $orderNumber)
    {
        $restaurantModel = new Restaurant();
        $restaurant = $restaurantModel->findBySlug($slug);
        if (!$restaurant) {
            return Response::json(['error' => 'Restoran bulunamadı'], 404);
        }
        $orderModel = new Order();
        $order = $orderModel->findByNumber($restaurant['id'], $orderNumber);
        if (!$order) {
            return Response::json(['error' => 'Sipariş bulunamadı'], 404);
        }
        $timeline = $orderModel->timeline((int)$order['id']);
        return Response::json([
            'order' => $order,
            'timeline' => $timeline,
        ]);
    }

    public function currencyRate(string $slug)
    {
        $restaurantModel = new Restaurant();
        $restaurant = $restaurantModel->findBySlug($slug);
        if (!$restaurant || $restaurant['status'] !== 'active') {
            return Response::json(['error' => 'Restoran bulunamadı'], 404);
        }
        $to = strtoupper($_GET['to'] ?? '');
        if (!$to) {
            return Response::json(['error' => 'Hedef para birimi belirtilmedi'], 422);
        }
        $allowed = $this->availableCurrencies($restaurant);
        if (!in_array($to, $allowed, true)) {
            return Response::json(['error' => 'Desteklenmeyen para birimi'], 422);
        }
        $amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 1.0;
        try {
            $conversion = Currency::convert($restaurant['currency'], $to, $amount);
        } catch (Throwable $exception) {
            return Response::json(['error' => $exception->getMessage()], 502);
        }
        return Response::json([
            'from' => $restaurant['currency'],
            'to' => $to,
            'rate' => $conversion['rate'],
            'amount' => $conversion['amount'],
        ]);
    }

    public function receipt(string $slug)
    {
        $data = $this->inputJson();
        $token = $data['table_token'] ?? null;
        $orderNumber = $data['order_number'] ?? null;
        if (!$token || !$orderNumber) {
            return Response::json(['error' => 'Masa veya sipariş bilgisi eksik'], 422);
        }
        $restaurantModel = new Restaurant();
        $restaurant = $restaurantModel->findByTableToken($slug, $token);
        if (!$restaurant || $restaurant['status'] !== 'active') {
            return Response::json(['error' => 'Restoran bulunamadı'], 404);
        }
        $orderModel = new Order();
        $order = $orderModel->findByNumber($restaurant['id'], $orderNumber);
        if (!$order) {
            return Response::json(['error' => 'Sipariş bulunamadı'], 404);
        }
        try {
            $payload = InvoiceGenerator::customerReceipt($restaurant, $order);
        } catch (Throwable $exception) {
            return Response::json(['error' => $exception->getMessage()], 500);
        }
        return Response::json($payload);
    }

    private function generateOrderNumber(int $restaurantId): string
    {
        $base = date('ymd');
        $random = strtoupper(bin2hex(random_bytes(3)));
        return $restaurantId . '-' . $base . '-' . $random;
    }
}
