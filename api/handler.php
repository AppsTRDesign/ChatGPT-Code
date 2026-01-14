<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mail.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Yalnızca POST isteği desteklenir.']);
    exit;
}

verify_csrf();

switch ($action) {
    case 'login':
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (login_user($email, $password)) {
            $redirect = is_admin() ? '/admin/index.php' : '/account.php';
            echo json_encode(['success' => true, 'redirect' => $redirect]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Giriş başarısız.']);
        }
        break;
    case 'register':
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$name || !$email || !$password) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Lütfen tüm zorunlu alanları doldurun.']);
            break;
        }

        $stmt = db()->prepare('INSERT INTO users (name, email, phone, password_hash, role, created_at) VALUES (:name, :email, :phone, :hash, :role, :created_at)');
        try {
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'customer',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $exception) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'E-posta adresi kullanımda.']);
            break;
        }

        login_user($email, $password);
        echo json_encode(['success' => true, 'redirect' => '/account.php']);
        break;
    case 'profile':
        $user = current_user();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Önce giriş yapın.']);
            break;
        }
        $stmt = db()->prepare('UPDATE users SET name = :name, email = :email, phone = :phone WHERE id = :id');
        $stmt->execute([
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'id' => $user['id'],
        ]);
        echo json_encode(['success' => true, 'message' => 'Bilgiler güncellendi.']);
        break;
    case 'order':
        $productId = (int) ($_POST['product_id'] ?? 0);
        $productStmt = db()->prepare('SELECT * FROM products WHERE id = :id');
        $productStmt->execute(['id' => $productId]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı.']);
            break;
        }

        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        $total = $quantity * (float) $product['price'];
        $status = $product['order_channel'] === 'paytr' ? 'approved' : 'pending';

        $stmt = db()->prepare('INSERT INTO orders (user_id, full_name, email, phone, address, status, channel, total_amount, created_at) VALUES (:user_id, :full_name, :email, :phone, :address, :status, :channel, :total_amount, :created_at)');
        $stmt->execute([
            'user_id' => $_SESSION['user_id'] ?? null,
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'status' => $status,
            'channel' => $product['order_channel'],
            'total_amount' => $total,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $orderId = db()->lastInsertId();
        $itemStmt = db()->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (:order_id, :product_id, :quantity, :unit_price)');
        $itemStmt->execute([
            'order_id' => $orderId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $product['price'],
        ]);

        echo json_encode(['success' => true, 'message' => 'Siparişiniz alınmıştır.', 'redirect' => '/account.php']);
        break;
    case 'contact':
        echo json_encode(['success' => true, 'message' => 'Mesajınız alındı.']);
        break;
    case 'settings':
        if (!is_admin()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Yetkisiz.']);
            break;
        }
        $fields = [
            'base_url',
            'site_name',
            'site_address',
            'map_embed',
            'contact_phone',
            'whatsapp_number',
            'contact_email',
            'meta_title',
            'meta_description',
            'theme_color',
        ];
        foreach ($fields as $field) {
            update_setting($field, trim($_POST[$field] ?? ''));
        }

        $logoPath = handle_upload('logo');
        if ($logoPath) {
            update_setting('logo', $logoPath);
        }
        $faviconPath = handle_upload('favicon');
        if ($faviconPath) {
            update_setting('favicon', $faviconPath);
        }

        echo json_encode(['success' => true, 'message' => 'Ayarlar kaydedildi.']);
        break;
    case 'product':
        if (!is_admin()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Yetkisiz.']);
            break;
        }
        $stmt = db()->prepare('INSERT INTO products (name, description, price, main_image, order_channel, order_link, created_at) VALUES (:name, :description, :price, :main_image, :order_channel, :order_link, :created_at)');
        $mainImage = handle_upload('main_image');
        $stmt->execute([
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'price' => (float) ($_POST['price'] ?? 0),
            'main_image' => $mainImage,
            'order_channel' => $_POST['order_channel'] ?? 'whatsapp',
            'order_link' => trim($_POST['order_link'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $productId = db()->lastInsertId();
        if (!empty($_FILES['gallery']['name'][0])) {
            foreach ($_FILES['gallery']['name'] as $index => $name) {
                if (!$_FILES['gallery']['tmp_name'][$index]) {
                    continue;
                }
                $path = store_upload($_FILES['gallery']['tmp_name'][$index], $name);
                $imageStmt = db()->prepare('INSERT INTO product_images (product_id, image_path) VALUES (:product_id, :image_path)');
                $imageStmt->execute(['product_id' => $productId, 'image_path' => $path]);
            }
        }

        echo json_encode(['success' => true, 'message' => 'Ürün kaydedildi.']);
        break;
    case 'page':
        if (!is_admin()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Yetkisiz.']);
            break;
        }
        $stmt = db()->prepare('INSERT INTO pages (title, slug, summary, content, created_at) VALUES (:title, :slug, :summary, :content, :created_at)');
        $stmt->execute([
            'title' => trim($_POST['title'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'summary' => trim($_POST['summary'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        echo json_encode(['success' => true, 'message' => 'Sayfa kaydedildi.']);
        break;
    case 'paytr':
        if (!is_admin()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Yetkisiz.']);
            break;
        }
        update_setting('paytr_active', isset($_POST['paytr_active']) ? '1' : '0');
        update_setting('paytr_merchant_id', trim($_POST['paytr_merchant_id'] ?? ''));
        update_setting('paytr_merchant_key', trim($_POST['paytr_merchant_key'] ?? ''));
        update_setting('paytr_merchant_salt', trim($_POST['paytr_merchant_salt'] ?? ''));
        update_setting('paytr_success_url', trim($_POST['paytr_success_url'] ?? ''));
        update_setting('paytr_fail_url', trim($_POST['paytr_fail_url'] ?? ''));
        echo json_encode(['success' => true, 'message' => 'PayTR ayarları kaydedildi.']);
        break;
    case 'order-status':
        if (!is_admin()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Yetkisiz.']);
            break;
        }
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? 'pending';
        $stmt = db()->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $orderId]);
        $orderStmt = db()->prepare('SELECT full_name, email FROM orders WHERE id = :id');
        $orderStmt->execute(['id' => $orderId]);
        if ($order = $orderStmt->fetch(PDO::FETCH_ASSOC)) {
            send_order_status_email($order['email'], $order['full_name'], $status, $orderId);
        }
        echo json_encode(['success' => true, 'message' => 'Durum güncellendi.']);
        break;
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'İşlem bulunamadı.']);
}

function handle_upload(string $field): ?string
{
    if (empty($_FILES[$field]['name'])) {
        return null;
    }

    $tmp = $_FILES[$field]['tmp_name'];
    $name = $_FILES[$field]['name'];

    return store_upload($tmp, $name);
}

function store_upload(string $tmp, string $name): string
{
    $uploadDir = __DIR__ . '/../storage/uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }
    $cleanName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
    $fileName = time() . '_' . $cleanName;
    $target = $uploadDir . '/' . $fileName;
    move_uploaded_file($tmp, $target);
    return '/storage/uploads/' . $fileName;
}
