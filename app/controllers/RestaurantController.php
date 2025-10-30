<?php
class RestaurantController extends BaseController
{
    private function userId(): int
    {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    private function restaurantId(): int
    {
        return (int)($_SESSION['restaurant_id'] ?? 0);
    }

    private function ensureRestaurant(): void
    {
        if (!$this->restaurantId()) {
            Response::json(['error' => 'Restoran oturumu bulunamadı'], 401);
            exit;
        }
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

    public function categories()
    {
        $this->ensureRestaurant();
        $restaurantId = $this->restaurantId();
        $categoryModel = new Category();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return Response::json(['categories' => $categoryModel->allByRestaurant($restaurantId)]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payload = $_POST ?: $this->inputJson();
            $payload = Security::sanitize($payload);
            $imageUrl = $payload['image_url'] ?? null;
            if (!empty($_FILES['image'])) {
                $imageUrl = $this->handleUpload($_FILES['image']);
            }
            $categoryModel->create([
                'restaurant_id' => $restaurantId,
                'name' => $payload['name'],
                'description' => $payload['description'] ?? '',
                'icon_class' => $payload['icon_class'] ?? null,
                'image_url' => $imageUrl,
                'sort_order' => $payload['sort_order'] ?? 0,
            ]);
            return Response::json(['message' => 'Kategori oluşturuldu']);
        }

        $data = $this->inputJson();
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $categoryModel->update((int)$data['id'], [
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'icon_class' => $data['icon_class'] ?? null,
                'image_url' => $data['image_url'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
            return Response::json(['message' => 'Kategori güncellendi']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $categoryModel->delete((int)$data['id']);
            return Response::json(['message' => 'Kategori silindi']);
        }

        return Response::json(['error' => 'Desteklenmeyen istek'], 405);
    }

    public function categoryUpload()
    {
        $this->ensureRestaurant();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image'])) {
            return Response::json(['error' => 'Dosya yüklenmedi'], 400);
        }
        try {
            $url = $this->handleUpload($_FILES['image']);
        } catch (Exception $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
        return Response::json(['message' => 'Yükleme başarılı', 'url' => $url]);
    }

    public function productUpload()
    {
        $this->ensureRestaurant();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image'])) {
            return Response::json(['error' => 'Dosya yüklenmedi'], 400);
        }
        try {
            $url = $this->handleUpload($_FILES['image']);
        } catch (Exception $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
        return Response::json(['message' => 'Yükleme başarılı', 'url' => $url]);
    }

    public function products()
    {
        $this->ensureRestaurant();
        $restaurantId = $this->restaurantId();
        $productModel = new Product();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return Response::json(['products' => $productModel->allByRestaurant($restaurantId)]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!empty($_FILES['image'])) {
                $payload = $_POST;
                $payload['image_url'] = $this->handleUpload($_FILES['image']);
            } else {
                $payload = $this->inputJson();
            }
            $payload = Security::sanitize($payload);
            $productModel->create(array_merge($payload, ['restaurant_id' => $restaurantId]));
            return Response::json(['message' => 'Ürün eklendi']);
        }

        $data = $this->inputJson();
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $productModel->update((int)$data['id'], $data);
            return Response::json(['message' => 'Ürün güncellendi']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $productModel->delete((int)$data['id']);
            return Response::json(['message' => 'Ürün silindi']);
        }

        return Response::json(['error' => 'Desteklenmeyen istek'], 405);
    }

    public function tables()
    {
        $this->ensureRestaurant();
        $restaurantId = $this->restaurantId();
        $tableModel = new RestaurantTable();
        $orderModel = new Order();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $tables = $tableModel->allByRestaurant($restaurantId);
            $openOrders = $orderModel->openByTable($restaurantId);
            $indexed = [];
            foreach ($openOrders as $order) {
                $tableId = (int)($order['table_id'] ?? 0);
                if ($tableId && !isset($indexed[$tableId])) {
                    $indexed[$tableId] = $order;
                }
            }
            $tables = array_map(function ($table) use ($indexed) {
                $table['open_order'] = $indexed[(int)$table['id']] ?? null;
                if ($table['open_order']) {
                    $table['open_order_total'] = (float)($table['open_order']['total_amount'] ?? 0);
                }
                return $table;
            }, $tables);
            return Response::json(['tables' => $tables]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Security::sanitize($this->inputJson());
            $errors = Validator::required($data, ['name']);
            if ($errors) {
                return Response::json(['error' => 'Zorunlu alanlar eksik', 'errors' => $errors], 422);
            }
            $name = trim((string)$data['name']);
            $slug = $this->slugify($data['slug'] ?? $name);
            if ($tableModel->slugExists($restaurantId, $slug)) {
                return Response::json(['error' => 'Bu kısa ad başka bir masa tarafından kullanılıyor'], 409);
            }
            $token = bin2hex(random_bytes(16));
            $seats = isset($data['seats']) ? max(1, (int)$data['seats']) : 4;
            $status = $data['status'] === 'occupied' ? 'occupied' : 'vacant';
            try {
                $tableId = $tableModel->create([
                    'restaurant_id' => $restaurantId,
                    'name' => $name,
                    'slug' => $slug,
                    'qr_token' => $token,
                    'seats' => $seats,
                    'status' => $status,
                ]);
            } catch (Throwable $exception) {
                return Response::json(['error' => 'Masa oluşturulamadı'], 500);
            }
            return Response::json(['message' => 'Masa oluşturuldu', 'id' => $tableId]);
        }

        $data = $this->inputJson();
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            if (!empty($data['action']) && $data['action'] === 'status') {
                $table = $tableModel->find((int)$data['id']);
                if (!$table || (int)$table['restaurant_id'] !== $restaurantId) {
                    return Response::json(['error' => 'Masa bulunamadı'], 404);
                }
                $status = $data['status'] === 'occupied' ? 'occupied' : 'vacant';
                $tableModel->updateStatus((int)$data['id'], $status);
                SocketNotifier::notify([
                    'event' => 'table:status',
                    'restaurant_id' => $restaurantId,
                    'table_id' => (int)$data['id'],
                    'status' => $status,
                    'table_token' => $table['qr_token'] ?? null,
                ]);
                return Response::json(['message' => 'Masa durumu güncellendi']);
            }
            if (!empty($data['action']) && $data['action'] === 'settle') {
                $orderId = (int)($data['order_id'] ?? 0);
                if (!$orderId) {
                    return Response::json(['error' => 'Sipariş bulunamadı'], 422);
                }
                $order = $orderModel->findByNumber($restaurantId, $data['order_number'] ?? '');
                if (!$order || (int)$order['id'] !== $orderId) {
                    return Response::json(['error' => 'Sipariş bulunamadı'], 404);
                }
                $orderModel->markPaid($orderId, $data['method'] ?? 'cash');
                if ($order['status'] !== 'completed') {
                    $orderModel->updateStatus($orderId, 'completed', 'Masa panelinden kapatıldı');
                }
                if (!empty($order['table_id'])) {
                    $tableModel->updateStatus((int)$order['table_id'], 'vacant');
                }
                SocketNotifier::notify([
                    'event' => 'order:status',
                    'restaurant_id' => $restaurantId,
                    'order_id' => $orderId,
                    'status' => 'completed',
                    'table_token' => $order['qr_token'] ?? null,
                ]);
                if (!empty($order['table_id'])) {
                    SocketNotifier::notify([
                        'event' => 'table:status',
                        'restaurant_id' => $restaurantId,
                        'table_id' => (int)$order['table_id'],
                        'status' => 'vacant',
                        'table_token' => $order['qr_token'] ?? null,
                    ]);
                }
                return Response::json(['message' => 'Masa hesabı kapatıldı']);
            }
            $name = trim((string)($data['name'] ?? ''));
            if ($name === '') {
                return Response::json(['error' => 'Masa adı boş olamaz'], 422);
            }
            $slug = $this->slugify($data['slug'] ?? $name);
            if ($tableModel->slugExists($restaurantId, $slug, (int)$data['id'])) {
                return Response::json(['error' => 'Bu kısa ad başka bir masa tarafından kullanılıyor'], 409);
            }
            $seats = isset($data['seats']) ? max(1, (int)$data['seats']) : 4;
            $status = $data['status'] === 'occupied' ? 'occupied' : 'vacant';
            try {
                $tableModel->update((int)$data['id'], [
                    'name' => $name,
                    'slug' => $slug,
                    'seats' => $seats,
                    'status' => $status,
                ]);
            } catch (Throwable $exception) {
                return Response::json(['error' => 'Masa güncellenemedi'], 500);
            }
            return Response::json(['message' => 'Masa güncellendi']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $tableModel->delete((int)$data['id']);
            return Response::json(['message' => 'Masa silindi']);
        }

        return Response::json(['error' => 'Desteklenmeyen istek'], 405);
    }

    public function orders()
    {
        $this->ensureRestaurant();
        $restaurantId = $this->restaurantId();
        $orderModel = new Order();
        $tableModel = new RestaurantTable();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $status = $_GET['status'] ?? null;
            $range = $_GET['range'] ?? null;
            $from = $_GET['from'] ?? null;
            $to = $_GET['to'] ?? null;
            if ($from && $to) {
                $fromDate = $from;
                $toDate = $to;
            } else {
                [$fromDate, $toDate] = $this->rangeToDates($range);
            }
            $orders = $orderModel->allByRestaurant($restaurantId, [
                'status' => $status,
                'from' => $fromDate,
                'to' => $toDate,
            ]);
            $metrics = $orderModel->aggregateByStatus($restaurantId);
            $tables = $tableModel->allByRestaurant($restaurantId);
            return Response::json([
                'orders' => $orders,
                'metrics' => $metrics,
                'tables' => $tables,
            ]);
        }

        $data = $this->inputJson();
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            if (($data['action'] ?? '') === 'status') {
                $orderModel->updateStatus((int)$data['order_id'], $data['status'], $data['note'] ?? null);
                if (!empty($data['table_id'])) {
                    if ($data['status'] === 'cancelled') {
                        $tableModel->updateStatus((int)$data['table_id'], 'vacant');
                        SocketNotifier::notify([
                            'event' => 'table:status',
                            'restaurant_id' => $restaurantId,
                            'table_id' => (int)$data['table_id'],
                            'status' => 'vacant',
                        ]);
                    } elseif (in_array($data['status'], ['pending', 'preparing', 'ready', 'completed'], true)) {
                        $tableModel->updateStatus((int)$data['table_id'], 'occupied');
                        SocketNotifier::notify([
                            'event' => 'table:status',
                            'restaurant_id' => $restaurantId,
                            'table_id' => (int)$data['table_id'],
                            'status' => 'occupied',
                        ]);
                    }
                }
                $payload = [
                    'event' => 'order:status',
                    'restaurant_id' => $restaurantId,
                    'order_id' => $data['order_id'],
                    'status' => $data['status'],
                    'table_token' => $data['table_token'] ?? null,
                ];
                SocketNotifier::notify($payload);
                return Response::json(['message' => 'Sipariş durumu güncellendi']);
            }

            if (($data['action'] ?? '') === 'payment') {
                $order = $orderModel->findByNumber($restaurantId, $data['order_number'] ?? '');
                if (!$order || (int)$order['id'] !== (int)$data['order_id']) {
                    return Response::json(['error' => 'Sipariş bulunamadı'], 404);
                }
                $orderModel->markPaid((int)$data['order_id'], $data['method'] ?? 'cash');
                if (!empty($order['table_id'])) {
                    $tableModel->updateStatus((int)$order['table_id'], 'vacant');
                }
                SocketNotifier::notify([
                    'event' => 'order:status',
                    'restaurant_id' => $restaurantId,
                    'order_id' => (int)$order['id'],
                    'status' => $order['status'] ?? 'completed',
                    'table_token' => $order['qr_token'] ?? null,
                ]);
                if (!empty($order['table_id'])) {
                    SocketNotifier::notify([
                        'event' => 'table:status',
                        'restaurant_id' => $restaurantId,
                        'table_id' => (int)$order['table_id'],
                        'status' => 'vacant',
                        'table_token' => $order['qr_token'] ?? null,
                    ]);
                }
                return Response::json(['message' => 'Ödeme alındı']);
            }
        }

        return Response::json(['error' => 'Desteklenmeyen istek'], 405);
    }

    public function calls()
    {
        $this->ensureRestaurant();
        $restaurantId = $this->restaurantId();
        $callModel = new WaiterCall();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return Response::json(['calls' => $callModel->listActive($restaurantId)]);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $data = $this->inputJson();
            $callModel->updateStatus((int)$data['id'], $data['status']);
            SocketNotifier::notify([
                'event' => 'waiter:update',
                'restaurant_id' => $restaurantId,
                'call_id' => $data['id'],
                'status' => $data['status'],
            ]);
            return Response::json(['message' => 'Garson çağrısı güncellendi']);
        }
        return Response::json(['error' => 'Desteklenmeyen istek'], 405);
    }

    public function settings()
    {
        $this->ensureRestaurant();
        $restaurantId = $this->restaurantId();
        $restaurantModel = new Restaurant();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $restaurant = $restaurantModel->find($restaurantId);
            $restaurant['available_currencies'] = $this->availableCurrencies($restaurant);
            return Response::json(['settings' => $restaurant]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $data = $this->inputJson();
            $restaurant = $restaurantModel->find($restaurantId);
            $available = $this->availableCurrencies($restaurant);
            if (!empty($data['currency'])) {
                $data['currency'] = strtoupper($data['currency']);
                if (!in_array($data['currency'], $available, true)) {
                    return Response::json(['error' => 'Geçersiz para birimi seçimi'], 422);
                }
            }
            $restaurantModel->updateSettings($restaurantId, $data);
            return Response::json(['message' => 'Ayarlar kaydedildi']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!empty($_FILES['logo']) || !empty($_FILES['favicon'])) {
                $branding = [];
                if (!empty($_FILES['logo'])) {
                    $branding['logo_url'] = $this->handleUpload($_FILES['logo']);
                }
                if (!empty($_FILES['favicon'])) {
                    $branding['favicon_url'] = $this->handleUpload($_FILES['favicon']);
                }
                $restaurantModel->updateBranding($restaurantId, $branding);
                return Response::json(['message' => 'Marka görselleri güncellendi', 'branding' => $branding]);
            }
        }

        return Response::json(['error' => 'Desteklenmeyen istek'], 405);
    }

    public function api()
    {
        $this->ensureRestaurant();
        $restaurantId = $this->restaurantId();
        $userId = $this->userId();
        if (!$userId) {
            return Response::json(['error' => 'Yetkilendirme bulunamadı'], 403);
        }

        $userModel = new User();
        $user = $userModel->find($userId);
        if (!$user) {
            return Response::json(['error' => 'Kullanıcı bulunamadı'], 404);
        }

        $apiKeyModel = new ApiKey();
        if (empty($user['api_key'])) {
            $user['api_key'] = $apiKeyModel->generateForUser($userId);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $apiKey = $apiKeyModel->generateForUser($userId);
            return Response::json(['api' => $this->buildApiPayload($restaurantId, $apiKey)]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return Response::json(['api' => $this->buildApiPayload($restaurantId, $user['api_key'])]);
        }

        return Response::json(['error' => 'Desteklenmeyen istek'], 405);
    }

    public function theme()
    {
        $this->ensureRestaurant();
        $restaurantId = $this->restaurantId();
        $restaurantModel = new Restaurant();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $restaurant = $restaurantModel->find($restaurantId);
            return Response::json(['theme' => [
                'theme' => $restaurant['theme'],
                'primary_color' => $restaurant['primary_color'],
                'menu_layout' => $restaurant['menu_layout'],
            ]]);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $data = $this->inputJson();
            $restaurantModel->updateTheme($restaurantId, $data);
            return Response::json(['message' => 'Tema güncellendi']);
        }
        return Response::json(['error' => 'Desteklenmeyen istek'], 405);
    }

    public function reports()
    {
        $this->ensureRestaurant();
        $restaurantId = $this->restaurantId();
        $orderModel = new Order();
        $data = $this->inputJson();
        $range = $data['range'] ?? 'month';
        [$from, $to] = $this->rangeToDates($range);
        $report = $orderModel->report($restaurantId, $from, $to);
        return Response::json(['report' => $report, 'from' => $from, 'to' => $to]);
    }

    public function export()
    {
        $this->ensureRestaurant();
        $restaurantId = $this->restaurantId();
        $orderModel = new Order();
        $data = $this->inputJson();
        if (!empty($data['from']) && !empty($data['to'])) {
            $from = $data['from'];
            $to = $data['to'];
        } else {
            [$from, $to] = $this->rangeToDates($data['range'] ?? 'month');
        }
        $orders = $orderModel->allByRestaurant($restaurantId, ['from' => $from, 'to' => $to]);
        $format = $data['format'] ?? 'pdf';
        $restaurant = (new Restaurant())->find($restaurantId);
        if ($format === 'excel') {
            $content = ReportExporter::toCsv($orders, $restaurant['currency'] ?? 'TRY');
            return Response::json([
                'filename' => 'rapor-' . date('YmdHis') . '.csv',
                'content' => base64_encode($content),
                'mime' => 'text/csv'
            ]);
        }
        try {
            $payload = InvoiceGenerator::ordersReport($orders, $restaurant, ['from' => $from, 'to' => $to]);
        } catch (Throwable $exception) {
            return Response::json(['error' => $exception->getMessage()], 500);
        }
        return Response::json($payload);
    }

    public function receipt()
    {
        $this->ensureRestaurant();
        $restaurantId = $this->restaurantId();
        $orderModel = new Order();
        $data = $this->inputJson();
        $order = $orderModel->findByNumber($restaurantId, $data['order_number']);
        if (!$order) {
            return Response::json(['error' => 'Sipariş bulunamadı'], 404);
        }
        $restaurant = (new Restaurant())->find($restaurantId);
        try {
            $payload = InvoiceGenerator::cashReceipt($restaurant, $order);
        } catch (Throwable $exception) {
            return Response::json(['error' => $exception->getMessage()], 500);
        }
        return Response::json($payload);
    }

    private function handleUpload(array $file): string
    {
        $config = require __DIR__ . '/../config/config.php';
        $upload = FileUploader::uploadLocal($file, $config['upload']);
        return $upload['url'];
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value);
        return trim($value, '-') ?: uniqid('masa');
    }

    private function rangeToDates(?string $range): array
    {
        $now = new DateTimeImmutable('now');
        switch ($range) {
            case 'day':
                $from = $now->setTime(0, 0)->format('Y-m-d H:i:s');
                $to = $now->setTime(23, 59, 59)->format('Y-m-d H:i:s');
                break;
            case 'week':
                $from = $now->modify('monday this week')->setTime(0, 0)->format('Y-m-d H:i:s');
                $to = $now->modify('sunday this week')->setTime(23, 59, 59)->format('Y-m-d H:i:s');
                break;
            case 'year':
                $from = $now->setDate((int)$now->format('Y'), 1, 1)->setTime(0, 0)->format('Y-m-d H:i:s');
                $to = $now->setDate((int)$now->format('Y'), 12, 31)->setTime(23, 59, 59)->format('Y-m-d H:i:s');
                break;
            default:
                $from = $now->setDate((int)$now->format('Y'), (int)$now->format('m'), 1)->setTime(0, 0)->format('Y-m-d H:i:s');
                $to = $now->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('t'))->setTime(23, 59, 59)->format('Y-m-d H:i:s');
        }
        return [$from, $to];
    }

    private function buildApiPayload(int $restaurantId, string $apiKey): array
    {
        $config = require __DIR__ . '/../config/config.php';
        $baseUrl = rtrim($config['base_url'], '/');
        $restaurant = (new Restaurant())->find($restaurantId);
        $slug = $restaurant['slug'] ?? '';

        $tableModel = new RestaurantTable();
        $tables = array_map(function ($table) use ($baseUrl, $slug) {
            return [
                'id' => (int)$table['id'],
                'name' => $table['name'],
                'slug' => $table['slug'],
                'status' => $table['status'],
                'token' => $table['qr_token'],
                'url' => $baseUrl . '/menu/' . $slug . '/table/' . $table['slug'] . '?token=' . $table['qr_token'],
            ];
        }, $tableModel->allByRestaurant($restaurantId));

        $endpoints = [
            [
                'method' => 'GET',
                'path' => '/api/menu?slug=' . $slug . '&token={table_token}',
                'full_url' => $baseUrl . '/api/menu?slug=' . $slug . '&token={table_token}',
                'description' => 'Restoran menüsünü kategorileriyle birlikte döner.',
            ],
            [
                'method' => 'POST',
                'path' => '/api/menu/order',
                'full_url' => $baseUrl . '/api/menu/order',
                'description' => 'Sepet öğeleri ile yeni sipariş oluşturur.',
            ],
            [
                'method' => 'POST',
                'path' => '/api/menu/waiter-call',
                'full_url' => $baseUrl . '/api/menu/waiter-call',
                'description' => 'Belirli bir masadan garson çağrısı gönderir.',
            ],
            [
                'method' => 'GET',
                'path' => '/api/menu/order-status?slug=' . $slug . '&order_number={order_number}',
                'full_url' => $baseUrl . '/api/menu/order-status?slug=' . $slug . '&order_number={order_number}',
                'description' => 'Sipariş durum geçmişini listeler.',
            ],
            [
                'method' => 'GET',
                'path' => '/api/menu/currency?slug=' . $slug . '&to={currency}',
                'full_url' => $baseUrl . '/api/menu/currency?slug=' . $slug . '&to={currency}',
                'description' => 'Anlık döviz kuru hesaplar ve dönüş oranını verir.',
            ],
            [
                'method' => 'POST',
                'path' => '/api/menu/receipt?slug=' . $slug,
                'full_url' => $baseUrl . '/api/menu/receipt?slug=' . $slug,
                'description' => 'Sipariş ve masa token bilgisi ile PDF adisyonu üretir.',
            ],
        ];

        return [
            'key' => $apiKey,
            'base_url' => $baseUrl,
            'socket_url' => $config['api']['socket_client'],
            'restaurant_slug' => $slug,
            'endpoints' => $endpoints,
            'table_links' => $tables,
            'available_currencies' => $this->availableCurrencies($restaurant),
        ];
    }
}
