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
    case 'login-inline':
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (login_user($email, $password)) {
            echo json_encode(['success' => true, 'message' => 'Giriş başarılı.']);
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
    case 'register-inline':
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
        echo json_encode(['success' => true, 'message' => 'Kayıt tamamlandı.']);
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
    case 'checkout':
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
        $channel = $_POST['channel'] ?? $product['order_channel'];

        $stmt = db()->prepare('INSERT INTO orders (user_id, full_name, email, phone, address, status, channel, total_amount, created_at) VALUES (:user_id, :full_name, :email, :phone, :address, :status, :channel, :total_amount, :created_at)');
        $stmt->execute([
            'user_id' => $_SESSION['user_id'] ?? null,
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'status' => 'pending',
            'channel' => $channel,
            'total_amount' => $total,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $orderId = (int) db()->lastInsertId();
        db()->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (:order_id, :product_id, :quantity, :unit_price)')
            ->execute([
                'order_id' => $orderId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $product['price'],
            ]);

        if ($channel === 'paytr') {
            $html = '<p>PayTR ödeme adımına geçin.</p><a class="btn primary" href="/paytr.php?order_id=' . $orderId . '">PayTR ile Öde</a>';
            echo json_encode(['success' => true, 'message' => 'Ödeme adımına geçiliyor.', 'html' => $html]);
        } else {
            $whatsapp = $product['order_link'] ?: settings('whatsapp_number');
            $message = urlencode($product['name'] . ' için sipariş verdim. Sipariş No: #' . $orderId);
            $link = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $whatsapp) . '?text=' . $message;
            $html = '<p>Siparişiniz alındı. WhatsApp üzerinden bilgilendirme için tıklayın.</p><a class="btn primary" href="' . $link . '" target="_blank" rel="noopener">WhatsApp ile Bilgilendir</a>';
            echo json_encode(['success' => true, 'message' => 'Sipariş oluşturuldu.', 'html' => $html]);
        }
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
            'lightbox_provider',
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
        $productId = (int) ($_POST['id'] ?? 0);
        $orderChannel = $_POST['order_channel'] ?? 'whatsapp';
        $orderLink = $orderChannel === 'whatsapp' ? trim($_POST['order_link'] ?? '') : '';
        $slug = permalink($_POST['slug'] ?? '');
        if (!$slug) {
            $slug = permalink($_POST['name'] ?? '');
        }
        $mainImage = handle_upload('main_image');
        if ($productId) {
            $stmt = db()->prepare('UPDATE products SET name = :name, slug = :slug, description = :description, price = :price, category_id = :category_id, main_image = COALESCE(:main_image, main_image), order_channel = :order_channel, order_link = :order_link WHERE id = :id');
            $stmt->execute([
                'name' => trim($_POST['name'] ?? ''),
                'slug' => $slug,
                'description' => trim($_POST['description'] ?? ''),
                'price' => (float) ($_POST['price'] ?? 0),
                'category_id' => $_POST['category_id'] ?: null,
                'main_image' => $mainImage,
                'order_channel' => $orderChannel,
                'order_link' => $orderLink,
                'id' => $productId,
            ]);
        } else {
            $stmt = db()->prepare('INSERT INTO products (name, slug, description, price, main_image, category_id, order_channel, order_link, created_at) VALUES (:name, :slug, :description, :price, :main_image, :category_id, :order_channel, :order_link, :created_at)');
            $stmt->execute([
                'name' => trim($_POST['name'] ?? ''),
                'slug' => $slug,
                'description' => trim($_POST['description'] ?? ''),
                'price' => (float) ($_POST['price'] ?? 0),
                'main_image' => $mainImage,
                'category_id' => $_POST['category_id'] ?: null,
                'order_channel' => $orderChannel,
                'order_link' => $orderLink,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $productId = (int) db()->lastInsertId();
        }
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
        $pageId = (int) ($_POST['id'] ?? 0);
        $slug = permalink($_POST['slug'] ?? '');
        if (!$slug) {
            $slug = permalink($_POST['title'] ?? '');
        }
        if ($pageId) {
            $stmt = db()->prepare('UPDATE pages SET title = :title, slug = :slug, summary = :summary, content = :content WHERE id = :id');
            $stmt->execute([
                'title' => trim($_POST['title'] ?? ''),
                'slug' => $slug,
                'summary' => trim($_POST['summary'] ?? ''),
                'content' => trim($_POST['content'] ?? ''),
                'id' => $pageId,
            ]);
        } else {
            $stmt = db()->prepare('INSERT INTO pages (title, slug, summary, content, created_at) VALUES (:title, :slug, :summary, :content, :created_at)');
            $stmt->execute([
                'title' => trim($_POST['title'] ?? ''),
                'slug' => $slug,
                'summary' => trim($_POST['summary'] ?? ''),
                'content' => trim($_POST['content'] ?? ''),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        echo json_encode(['success' => true, 'message' => 'Sayfa kaydedildi.']);
        break;
    case 'category':
        if (!is_admin()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Yetkisiz.']);
            break;
        }
        $categoryId = (int) ($_POST['id'] ?? 0);
        $slug = permalink($_POST['slug'] ?? '');
        if (!$slug) {
            $slug = permalink($_POST['name'] ?? '');
        }
        $image = handle_upload('image');
        if ($categoryId) {
            $stmt = db()->prepare('UPDATE categories SET name = :name, slug = :slug, parent_id = :parent_id, icon = :icon, image = COALESCE(:image, image) WHERE id = :id');
            $stmt->execute([
                'name' => trim($_POST['name'] ?? ''),
                'slug' => $slug,
                'parent_id' => $_POST['parent_id'] ?: null,
                'icon' => trim($_POST['icon'] ?? ''),
                'image' => $image,
                'id' => $categoryId,
            ]);
        } else {
            $stmt = db()->prepare('INSERT INTO categories (name, slug, parent_id, icon, image, created_at) VALUES (:name, :slug, :parent_id, :icon, :image, :created_at)');
            $stmt->execute([
                'name' => trim($_POST['name'] ?? ''),
                'slug' => $slug,
                'parent_id' => $_POST['parent_id'] ?: null,
                'icon' => trim($_POST['icon'] ?? ''),
                'image' => $image,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        echo json_encode(['success' => true, 'message' => 'Kategori kaydedildi.']);
        break;
    case 'faq':
        if (!is_admin()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Yetkisiz.']);
            break;
        }
        $faqId = (int) ($_POST['id'] ?? 0);
        if ($faqId) {
            $stmt = db()->prepare('UPDATE faqs SET question = :question, answer = :answer WHERE id = :id');
            $stmt->execute([
                'question' => trim($_POST['question'] ?? ''),
                'answer' => trim($_POST['answer'] ?? ''),
                'id' => $faqId,
            ]);
        } else {
            $stmt = db()->prepare('INSERT INTO faqs (question, answer, created_at) VALUES (:question, :answer, :created_at)');
            $stmt->execute([
                'question' => trim($_POST['question'] ?? ''),
                'answer' => trim($_POST['answer'] ?? ''),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        echo json_encode(['success' => true, 'message' => 'SSS kaydedildi.']);
        break;
    case 'delete-product':
        if (!is_admin()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Yetkisiz.']);
            break;
        }
        $productId = (int) ($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM products WHERE id = :id')->execute(['id' => $productId]);
        echo json_encode(['success' => true, 'message' => 'Ürün silindi.']);
        break;
    case 'delete-page':
        if (!is_admin()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Yetkisiz.']);
            break;
        }
        $pageId = (int) ($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM pages WHERE id = :id')->execute(['id' => $pageId]);
        echo json_encode(['success' => true, 'message' => 'Sayfa silindi.']);
        break;
    case 'delete-category':
        if (!is_admin()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Yetkisiz.']);
            break;
        }
        $categoryId = (int) ($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM categories WHERE id = :id')->execute(['id' => $categoryId]);
        echo json_encode(['success' => true, 'message' => 'Kategori silindi.']);
        break;
    case 'delete-faq':
        if (!is_admin()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Yetkisiz.']);
            break;
        }
        $faqId = (int) ($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM faqs WHERE id = :id')->execute(['id' => $faqId]);
        echo json_encode(['success' => true, 'message' => 'SSS silindi.']);
        break;
    case 'favorite':
        $user = current_user();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Favori için giriş yapın.']);
            break;
        }
        $productId = (int) ($_POST['product_id'] ?? 0);
        $checkStmt = db()->prepare('SELECT id FROM favorites WHERE user_id = :user_id AND product_id = :product_id');
        $checkStmt->execute(['user_id' => $user['id'], 'product_id' => $productId]);
        $favoriteId = $checkStmt->fetchColumn();
        if ($favoriteId) {
            db()->prepare('DELETE FROM favorites WHERE id = :id')->execute(['id' => $favoriteId]);
            echo json_encode(['success' => true, 'message' => 'Favoriden çıkarıldı.', 'favorited' => false]);
        } else {
            db()->prepare('INSERT INTO favorites (user_id, product_id, created_at) VALUES (:user_id, :product_id, :created_at)')
                ->execute(['user_id' => $user['id'], 'product_id' => $productId, 'created_at' => date('Y-m-d H:i:s')]);
            echo json_encode(['success' => true, 'message' => 'Favorilere eklendi.', 'favorited' => true]);
        }
        break;
    case 'cart-add':
        $productId = (int) ($_POST['product_id'] ?? 0);
        if (!$productId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Ürün seçilmedi.']);
            break;
        }
        $_SESSION['cart'] = $_SESSION['cart'] ?? [];
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + 1;
        echo json_encode(['success' => true, 'message' => 'Sepete eklendi.']);
        break;
    case 'cart-remove':
        $productId = (int) ($_POST['product_id'] ?? 0);
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
        }
        echo json_encode(['success' => true, 'message' => 'Sepetten çıkarıldı.']);
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
