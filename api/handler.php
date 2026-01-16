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
        $avatar = handle_upload('avatar');
        if ($avatar) {
            $oldStmt = db()->prepare('SELECT avatar FROM users WHERE id = :id');
            $oldStmt->execute(['id' => $user['id']]);
            $oldPath = $oldStmt->fetchColumn();
            if ($oldPath && is_file(__DIR__ . '/..' . $oldPath)) {
                unlink(__DIR__ . '/..' . $oldPath);
            }
        }
        $stmt = db()->prepare('UPDATE users SET name = :name, email = :email, phone = :phone, avatar = COALESCE(:avatar, avatar) WHERE id = :id');
        $stmt->execute([
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'avatar' => $avatar,
            'id' => $user['id'],
        ]);
        echo json_encode(['success' => true, 'message' => 'Bilgiler güncellendi.']);
        break;
    case 'address':
        $user = current_user();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Önce giriş yapın.']);
            break;
        }
        $stmt = db()->prepare('UPDATE users SET address = :address WHERE id = :id');
        $stmt->execute([
            'address' => trim($_POST['address'] ?? ''),
            'id' => $user['id'],
        ]);
        echo json_encode(['success' => true, 'message' => 'Adres kaydedildi.']);
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

        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        if ($fullName === '' || $email === '' || $phone === '' || $address === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Ad soyad, e-posta, telefon ve adres zorunludur.']);
            break;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Geçerli bir e-posta girin.']);
            break;
        }

        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        $unitPrice = product_discounted_price($product);
        $vatRate = (float) settings('vat_rate', '0');
        $shippingFee = (float) settings('shipping_fee', '0');
        $shippingFeeApplied = !empty($product['free_shipping']) ? 0.0 : $shippingFee;
        $subtotal = $quantity * $unitPrice;
        $vatAmount = $subtotal * ($vatRate / 100);
        $total = $subtotal + $vatAmount + $shippingFeeApplied;
        $status = $product['order_channel'] === 'paytr' ? 'approved' : 'pending';

        $stmt = db()->prepare('INSERT INTO orders (user_id, full_name, email, phone, address, status, channel, total_amount, created_at) VALUES (:user_id, :full_name, :email, :phone, :address, :status, :channel, :total_amount, :created_at)');
        $stmt->execute([
            'user_id' => $_SESSION['user_id'] ?? null,
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
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
            'unit_price' => $unitPrice,
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
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        if ($fullName === '' || $email === '' || $phone === '' || $address === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Ad soyad, e-posta, telefon ve adres zorunludur.']);
            break;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Geçerli bir e-posta girin.']);
            break;
        }
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        $unitPrice = product_discounted_price($product);
        $stock = (int) ($product['stock'] ?? 0);
        if ($stock > 0 && $quantity > $stock) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Stokta yeterli ürün yok.']);
            break;
        }
        $vatRate = (float) settings('vat_rate', '0');
        $shippingFee = (float) settings('shipping_fee', '0');
        $shippingFeeApplied = !empty($product['free_shipping']) ? 0.0 : $shippingFee;
        $subtotal = $quantity * $unitPrice;
        $vatAmount = $subtotal * ($vatRate / 100);
        $total = $subtotal + $vatAmount + $shippingFeeApplied;
        $paytrActive = settings('paytr_active') === '1';
        $bankTransferActive = settings('bank_transfer_active') === '1';
        $requestedChannel = $_POST['payment_method'] ?? $product['order_channel'];
        $availableChannels = [];
        if ($product['order_channel'] === 'whatsapp') {
            $availableChannels[] = 'whatsapp';
        }
        if ($paytrActive) {
            $availableChannels[] = 'paytr';
        }
        if ($bankTransferActive) {
            $availableChannels[] = 'bank_transfer';
        }
        if ($product['order_channel'] === 'paytr' && $paytrActive) {
            $availableChannels[] = 'paytr';
        }
        $availableChannels = array_values(array_unique($availableChannels));
        if (!in_array($requestedChannel, $availableChannels, true)) {
            $requestedChannel = $availableChannels[0] ?? 'whatsapp';
        }
        $channel = $requestedChannel;

        $stmt = db()->prepare('INSERT INTO orders (user_id, full_name, email, phone, address, order_note, status, channel, total_amount, created_at) VALUES (:user_id, :full_name, :email, :phone, :address, :order_note, :status, :channel, :total_amount, :created_at)');
        $stmt->execute([
            'user_id' => $_SESSION['user_id'] ?? null,
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'order_note' => trim($_POST['order_note'] ?? ''),
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
                'unit_price' => $unitPrice,
            ]);
        if ($stock > 0) {
            db()->prepare('UPDATE products SET stock = stock - :quantity WHERE id = :id')->execute([
                'quantity' => $quantity,
                'id' => $productId,
            ]);
        }

        if ($channel === 'paytr') {
            $html = '<p>PayTR ödeme adımına geçin.</p><a class="btn primary" href="/paytr.php?order_id=' . $orderId . '">PayTR ile Öde</a>';
            echo json_encode(['success' => true, 'message' => 'Ödeme adımına geçiliyor.', 'html' => $html]);
        } elseif ($channel === 'bank_transfer') {
            $html = '<p>Banka havalesi için aşağıdaki bilgileri kullanın.</p>'
                . '<p><strong>Banka:</strong> ' . htmlspecialchars(settings('bank_name')) . '</p>'
                . '<p><strong>IBAN:</strong> ' . htmlspecialchars(settings('bank_iban')) . '</p>'
                . '<p><strong>Alıcı:</strong> ' . htmlspecialchars(settings('bank_account_name')) . '</p>';
            echo json_encode(['success' => true, 'message' => 'Havale bilgileri hazır.', 'html' => $html]);
        } else {
            $whatsapp = $product['order_link'] ?: settings('whatsapp_number');
            $message = urlencode($product['name'] . ' için sipariş verdim. Sipariş No: #' . $orderId);
            $link = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $whatsapp) . '?text=' . $message;
            $html = '<p>Siparişiniz alındı. WhatsApp üzerinden bilgilendirme için tıklayın.</p><a class="btn primary" href="' . $link . '" target="_blank" rel="noopener">WhatsApp ile Bilgilendir</a>';
            echo json_encode(['success' => true, 'message' => 'Sipariş oluşturuldu.', 'html' => $html]);
        }
        break;
    case 'checkout-cart':
        $cart = $_SESSION['cart'] ?? [];
        if (!$cart) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Sepetiniz boş.']);
            break;
        }
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        if ($fullName === '' || $email === '' || $phone === '' || $address === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Ad soyad, e-posta, telefon ve adres zorunludur.']);
            break;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Geçerli bir e-posta girin.']);
            break;
        }
        $productIds = array_keys($cart);
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $productsStmt = db()->prepare("SELECT * FROM products WHERE id IN ({$placeholders})");
        $productsStmt->execute($productIds);
        $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($products) !== count($productIds)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Sepetteki bazı ürünler bulunamadı.']);
            break;
        }
        $vatRate = (float) settings('vat_rate', '0');
        $shippingFee = (float) settings('shipping_fee', '0');
        $subtotal = 0.0;
        $hasNonFreeShipping = false;
        foreach ($products as $product) {
            $quantity = max(1, (int) ($cart[$product['id']] ?? 1));
            $stock = (int) ($product['stock'] ?? 0);
            if ($stock > 0 && $quantity > $stock) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Stokta yeterli ürün yok.']);
                break 2;
            }
            $subtotal += $quantity * product_discounted_price($product);
            if (empty($product['free_shipping'])) {
                $hasNonFreeShipping = true;
            }
        }
        $shippingFeeApplied = $hasNonFreeShipping ? $shippingFee : 0.0;
        $vatAmount = $subtotal * ($vatRate / 100);
        $total = $subtotal + $vatAmount + $shippingFeeApplied;
        $paytrActive = settings('paytr_active') === '1';
        $bankTransferActive = settings('bank_transfer_active') === '1';
        $requestedChannel = $_POST['payment_method'] ?? 'whatsapp';
        $availableChannels = ['whatsapp'];
        if ($paytrActive) {
            $availableChannels[] = 'paytr';
        }
        if ($bankTransferActive) {
            $availableChannels[] = 'bank_transfer';
        }
        if (!in_array($requestedChannel, $availableChannels, true)) {
            $requestedChannel = $availableChannels[0] ?? 'whatsapp';
        }
        $channel = $requestedChannel;

        $stmt = db()->prepare('INSERT INTO orders (user_id, full_name, email, phone, address, order_note, status, channel, total_amount, created_at) VALUES (:user_id, :full_name, :email, :phone, :address, :order_note, :status, :channel, :total_amount, :created_at)');
        $stmt->execute([
            'user_id' => $_SESSION['user_id'] ?? null,
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'order_note' => trim($_POST['order_note'] ?? ''),
            'status' => 'pending',
            'channel' => $channel,
            'total_amount' => $total,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $orderId = (int) db()->lastInsertId();
        $itemsMessage = [];
        foreach ($products as $product) {
            $quantity = max(1, (int) ($cart[$product['id']] ?? 1));
            $unitPrice = product_discounted_price($product);
            db()->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (:order_id, :product_id, :quantity, :unit_price)')
                ->execute([
                    'order_id' => $orderId,
                    'product_id' => $product['id'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ]);
            $stock = (int) ($product['stock'] ?? 0);
            if ($stock > 0) {
                db()->prepare('UPDATE products SET stock = stock - :quantity WHERE id = :id')->execute([
                    'quantity' => $quantity,
                    'id' => $product['id'],
                ]);
            }
            $itemsMessage[] = $product['name'] . ' x' . $quantity;
        }
        unset($_SESSION['cart']);

        if ($channel === 'paytr') {
            $html = '<p>PayTR ödeme adımına geçin.</p><a class="btn primary" href="/paytr.php?order_id=' . $orderId . '">PayTR ile Öde</a>';
            echo json_encode(['success' => true, 'message' => 'Ödeme adımına geçiliyor.', 'html' => $html]);
        } elseif ($channel === 'bank_transfer') {
            $html = '<p>Banka havalesi için aşağıdaki bilgileri kullanın.</p>'
                . '<p><strong>Banka:</strong> ' . htmlspecialchars(settings('bank_name')) . '</p>'
                . '<p><strong>IBAN:</strong> ' . htmlspecialchars(settings('bank_iban')) . '</p>'
                . '<p><strong>Alıcı:</strong> ' . htmlspecialchars(settings('bank_account_name')) . '</p>';
            echo json_encode(['success' => true, 'message' => 'Havale bilgileri hazır.', 'html' => $html]);
        } else {
            $whatsapp = settings('whatsapp_number');
            $message = urlencode('Sipariş No: #' . $orderId . ' için sipariş verdim. Ürünler: ' . implode(', ', $itemsMessage));
            $link = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $whatsapp) . '?text=' . $message;
            $html = '<p>Siparişiniz alındı. WhatsApp üzerinden bilgilendirme için tıklayın.</p><a class="btn primary" href="' . $link . '" target="_blank" rel="noopener">WhatsApp ile Bilgilendir</a>';
            echo json_encode(['success' => true, 'message' => 'Sipariş oluşturuldu.', 'html' => $html]);
        }
        break;
    case 'bank-transfer-notify':
        $user = current_user();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Önce giriş yapın.']);
            break;
        }
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $bankName = trim($_POST['bank_name'] ?? '');
        if (!$orderId || $fullName === '' || $bankName === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Sipariş, ad soyad ve banka adı zorunludur.']);
            break;
        }
        $orderStmt = db()->prepare('SELECT id, user_id, email, channel FROM orders WHERE id = :id');
        $orderStmt->execute(['id' => $orderId]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Sipariş bulunamadı.']);
            break;
        }
        if ($order['channel'] !== 'bank_transfer') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Bu sipariş için havale bildirimi yapılamaz.']);
            break;
        }
        if ($order['user_id'] && (int) $order['user_id'] !== (int) $user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bu sipariş için bildirim yapamazsınız.']);
            break;
        }
        $receiptPath = handle_upload('receipt');
        if (!$receiptPath) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Dekont yükleyin.']);
            break;
        }
        $stmt = db()->prepare('INSERT INTO bank_transfer_notifications (order_id, user_id, full_name, bank_name, receipt_path, status, created_at) VALUES (:order_id, :user_id, :full_name, :bank_name, :receipt_path, :status, :created_at)');
        $stmt->execute([
            'order_id' => $orderId,
            'user_id' => $user['id'],
            'full_name' => $fullName,
            'bank_name' => $bankName,
            'receipt_path' => $receiptPath,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        echo json_encode(['success' => true, 'message' => 'Havale bildirimi alındı.']);
        break;
    case 'bank-transfer-approve':
        if (!is_admin()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Yetkisiz.']);
            break;
        }
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        if (!$notificationId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Bildirim bulunamadı.']);
            break;
        }
        $notificationStmt = db()->prepare('SELECT * FROM bank_transfer_notifications WHERE id = :id');
        $notificationStmt->execute(['id' => $notificationId]);
        $notification = $notificationStmt->fetch(PDO::FETCH_ASSOC);
        if (!$notification) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Bildirim bulunamadı.']);
            break;
        }
        db()->prepare('UPDATE bank_transfer_notifications SET status = :status WHERE id = :id')->execute([
            'status' => 'approved',
            'id' => $notificationId,
        ]);
        db()->prepare('UPDATE orders SET status = :status WHERE id = :id')->execute([
            'status' => 'approved',
            'id' => $notification['order_id'],
        ]);
        $orderStmt = db()->prepare('SELECT full_name, email FROM orders WHERE id = :id');
        $orderStmt->execute(['id' => $notification['order_id']]);
        if ($order = $orderStmt->fetch(PDO::FETCH_ASSOC)) {
            send_order_status_email($order['email'], $order['full_name'], 'approved', (int) $notification['order_id']);
        }
        echo json_encode(['success' => true, 'message' => 'Sipariş onaylandı.']);
        break;
    case 'bank-transfer-delete':
        if (!is_admin()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Yetkisiz.']);
            break;
        }
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        if (!$notificationId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Bildirim bulunamadı.']);
            break;
        }
        $notificationStmt = db()->prepare('SELECT receipt_path FROM bank_transfer_notifications WHERE id = :id');
        $notificationStmt->execute(['id' => $notificationId]);
        $receiptPath = $notificationStmt->fetchColumn();
        if ($receiptPath && is_file(__DIR__ . '/..' . $receiptPath)) {
            unlink(__DIR__ . '/..' . $receiptPath);
        }
        db()->prepare('DELETE FROM bank_transfer_notifications WHERE id = :id')->execute(['id' => $notificationId]);
        echo json_encode(['success' => true, 'message' => 'Bildirim silindi.']);
        break;
    case 'review':
        $productId = (int) ($_POST['product_id'] ?? 0);
        if (!$productId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Ürün seçilmedi.']);
            break;
        }
        $user = current_user();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Yorum için giriş yapın.']);
            break;
        }
        $orderCheck = db()->prepare('SELECT COUNT(*) FROM order_items INNER JOIN orders ON orders.id = order_items.order_id WHERE orders.user_id = :user_id AND order_items.product_id = :product_id');
        $orderCheck->execute(['user_id' => $user['id'], 'product_id' => $productId]);
        if (!$orderCheck->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bu ürün için sadece sipariş veren kullanıcılar yorum yapabilir.']);
            break;
        }
        $stmt = db()->prepare('INSERT INTO reviews (product_id, user_id, reviewer_name, rating, comment, approved, created_at) VALUES (:product_id, :user_id, :reviewer_name, :rating, :comment, :approved, :created_at)');
        $stmt->execute([
            'product_id' => $productId,
            'user_id' => $user['id'],
            'reviewer_name' => $user['name'],
            'rating' => (int) ($_POST['rating'] ?? 5),
            'comment' => trim($_POST['comment'] ?? ''),
            'approved' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        echo json_encode(['success' => true, 'message' => 'Yorumunuz incelenmek üzere gönderildi.']);
        break;
    case 'review-like':
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        if (!$reviewId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Yorum bulunamadı.']);
            break;
        }
        $user = current_user();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Beğeni için giriş yapın.']);
            break;
        }
        db()->prepare('UPDATE reviews SET likes = likes + 1 WHERE id = :id')->execute(['id' => $reviewId]);
        $likesStmt = db()->prepare('SELECT likes FROM reviews WHERE id = :id');
        $likesStmt->execute(['id' => $reviewId]);
        $likes = (int) $likesStmt->fetchColumn();
        echo json_encode(['success' => true, 'message' => 'Yorum beğenildi.', 'likes' => $likes]);
        break;
    case 'review-update':
        $user = current_user();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Önce giriş yapın.']);
            break;
        }
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        if (!$reviewId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Yorum bulunamadı.']);
            break;
        }
        $checkStmt = db()->prepare('SELECT id FROM reviews WHERE id = :id AND user_id = :user_id');
        $checkStmt->execute(['id' => $reviewId, 'user_id' => $user['id']]);
        if (!$checkStmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Yorum düzenleme yetkiniz yok.']);
            break;
        }
        $stmt = db()->prepare('UPDATE reviews SET rating = :rating, comment = :comment WHERE id = :id');
        $stmt->execute([
            'rating' => (int) ($_POST['rating'] ?? 5),
            'comment' => trim($_POST['comment'] ?? ''),
            'id' => $reviewId,
        ]);
        echo json_encode(['success' => true, 'message' => 'Yorum güncellendi.']);
        break;
    case 'review-delete':
        $user = current_user();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Önce giriş yapın.']);
            break;
        }
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        if (!$reviewId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Yorum bulunamadı.']);
            break;
        }
        $checkStmt = db()->prepare('SELECT id FROM reviews WHERE id = :id AND user_id = :user_id');
        $checkStmt->execute(['id' => $reviewId, 'user_id' => $user['id']]);
        if (!$checkStmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Yorum silme yetkiniz yok.']);
            break;
        }
        db()->prepare('DELETE FROM reviews WHERE id = :id')->execute(['id' => $reviewId]);
        echo json_encode(['success' => true, 'message' => 'Yorum silindi.']);
        break;
    case 'reviews-list':
        $productId = (int) ($_POST['product_id'] ?? 0);
        if (!$productId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı.']);
            break;
        }
        $sort = $_POST['sort'] ?? 'top';
        $sortMap = [
            'top' => 'likes DESC, created_at DESC',
            'new' => 'created_at DESC',
            'old' => 'created_at ASC',
            'low' => 'rating ASC, created_at DESC',
            'high' => 'rating DESC, created_at DESC',
        ];
        $sortSql = $sortMap[$sort] ?? $sortMap['top'];
        $perPage = (int) settings('reviews_per_page', '5');
        $page = max(1, (int) ($_POST['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $countStmt = db()->prepare('SELECT COUNT(*) FROM reviews WHERE product_id = :product_id AND approved = 1');
        $countStmt->execute(['product_id' => $productId]);
        $reviewTotal = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($reviewTotal / $perPage));

        $reviewStmt = db()->prepare("SELECT reviews.*, users.avatar FROM reviews LEFT JOIN users ON users.id = reviews.user_id WHERE reviews.product_id = :product_id AND reviews.approved = 1 ORDER BY {$sortSql} LIMIT :limit OFFSET :offset");
        $reviewStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $reviewStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $reviewStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $reviewStmt->execute();
        $reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);

        ob_start();
        foreach ($reviews as $review) {
            ?>
            <div class="review-card">
                <div class="review-header">
                    <div class="review-user">
                        <div class="avatar">
                            <?php if (!empty($review['avatar'])): ?>
                                <img src="<?= htmlspecialchars($review['avatar']) ?>" alt="<?= htmlspecialchars($review['reviewer_name']) ?>">
                            <?php else: ?>
                                <?= strtoupper(mb_substr($review['reviewer_name'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <strong><?= htmlspecialchars($review['reviewer_name']) ?></strong>
                            <span class="review-date"><?= htmlspecialchars($review['created_at']) ?></span>
                        </div>
                    </div>
                    <span class="stars"><?= render_stars((int) $review['rating']) ?></span>
                </div>
                <p><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                <button class="btn" type="button" data-review-like="<?= (int) $review['id'] ?>" data-review-likes="<?= (int) $review['likes'] ?>">Faydalı (<?= (int) $review['likes'] ?>)</button>
            </div>
            <?php
        }
        $html = ob_get_clean();
        $pages = array_unique(array_filter([
            1,
            2,
            $totalPages,
            $totalPages - 1,
            $page - 1,
            $page,
            $page + 1,
        ], static fn($value) => $value >= 1 && $value <= $totalPages));
        sort($pages);
        $pagination = '';
        $lastPage = 0;
        foreach ($pages as $reviewPageNumber) {
            if ($reviewPageNumber - $lastPage > 1) {
                $pagination .= '<span class="pagination-ellipsis">…</span>';
            }
            $active = $reviewPageNumber === $page ? ' primary' : '';
            $pagination .= '<button class="btn' . $active . '" type="button" data-review-page="' . $reviewPageNumber . '">' . $reviewPageNumber . '</button>';
            $lastPage = $reviewPageNumber;
        }
        echo json_encode(['success' => true, 'html' => $html, 'pagination' => $pagination, 'total_pages' => $totalPages]);
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
            'text_color',
            'light_bg',
            'border_color',
            'shadow',
            'font_family',
            'mobile_menu_toggle_color',
            'mobile_menu_text_color',
            'dropdown_menu_bg',
            'site_header_bg',
            'site_header_text_color',
            'site_footer_bg',
            'site_footer_text_color',
            'cart_count_bg',
            'framed_section_border',
            'framed_section_padding',
            'framed_section_radius',
            'tab_active_bg',
            'tab_active_color',
            'lightbox_provider',
            'homepage_layout',
            'site_width',
            'homepage_latest_limit',
            'homepage_ordered_limit',
            'homepage_visited_limit',
            'homepage_favorited_limit',
            'homepage_discounted_limit',
            'reviews_per_page',
            'vat_rate',
            'shipping_fee',
            'bank_transfer_active',
            'bank_name',
            'bank_iban',
            'bank_account_name',
            'header_html',
            'footer_html',
        ];
        foreach ($fields as $field) {
            if (in_array($field, ['header_html', 'footer_html', 'map_embed'], true)) {
                update_setting($field, $_POST[$field] ?? '');
                continue;
            }
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
    case 'social-link':
        if (!is_admin()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Yetkisiz.']);
            break;
        }
        $linkId = (int) ($_POST['id'] ?? 0);
        $label = trim($_POST['label'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $iconClass = trim($_POST['icon_class'] ?? '');
        if ($label === '' || $url === '' || $iconClass === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Tüm alanlar zorunludur.']);
            break;
        }
        if ($linkId) {
            $stmt = db()->prepare('UPDATE social_links SET label = :label, url = :url, icon_class = :icon_class WHERE id = :id');
            $stmt->execute([
                'label' => $label,
                'url' => $url,
                'icon_class' => $iconClass,
                'id' => $linkId,
            ]);
        } else {
            $stmt = db()->prepare('INSERT INTO social_links (label, url, icon_class, created_at) VALUES (:label, :url, :icon_class, :created_at)');
            $stmt->execute([
                'label' => $label,
                'url' => $url,
                'icon_class' => $iconClass,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        echo json_encode(['success' => true, 'message' => 'Sosyal medya bağlantısı kaydedildi.']);
        break;
    case 'social-link-delete':
        if (!is_admin()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Yetkisiz.']);
            break;
        }
        $linkId = (int) ($_POST['id'] ?? 0);
        if (!$linkId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Bağlantı bulunamadı.']);
            break;
        }
        db()->prepare('DELETE FROM social_links WHERE id = :id')->execute(['id' => $linkId]);
        echo json_encode(['success' => true, 'message' => 'Bağlantı silindi.']);
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
        $name = trim($_POST['name'] ?? '');
        $sku = trim($_POST['sku'] ?? '');
        $stock = max(0, (int) ($_POST['stock'] ?? 0));
        $badgeText = trim($_POST['badge_text'] ?? '');
        $shortDescription = trim($_POST['short_description'] ?? '');
        $freeShipping = isset($_POST['free_shipping']) ? 1 : 0;
        $discountType = $_POST['discount_type'] ?? '';
        $discountValue = (float) ($_POST['discount_value'] ?? 0);
        if (!in_array($discountType, ['percent', 'amount'], true)) {
            $discountType = null;
            $discountValue = 0;
        }
        $slug = permalink($name);
        $mainImage = handle_upload('main_image');
        if ($productId) {
            if ($mainImage) {
                $oldStmt = db()->prepare('SELECT main_image FROM products WHERE id = :id');
                $oldStmt->execute(['id' => $productId]);
                $oldPath = $oldStmt->fetchColumn();
                if ($oldPath && is_file(__DIR__ . '/..' . $oldPath)) {
                    unlink(__DIR__ . '/..' . $oldPath);
                }
            }
            $stmt = db()->prepare('UPDATE products SET name = :name, slug = :slug, sku = :sku, short_description = :short_description, description = :description, price = :price, stock = :stock, badge_text = :badge_text, free_shipping = :free_shipping, discount_type = :discount_type, discount_value = :discount_value, category_id = :category_id, main_image = COALESCE(:main_image, main_image), order_channel = :order_channel, order_link = :order_link WHERE id = :id');
            $stmt->execute([
                'name' => $name,
                'slug' => $slug,
                'sku' => $sku,
                'short_description' => $shortDescription,
                'description' => trim($_POST['description'] ?? ''),
                'price' => (float) ($_POST['price'] ?? 0),
                'stock' => $stock,
                'badge_text' => $badgeText,
                'free_shipping' => $freeShipping,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'category_id' => $_POST['category_id'] ?: null,
                'main_image' => $mainImage,
                'order_channel' => $orderChannel,
                'order_link' => $orderLink,
                'id' => $productId,
            ]);
        } else {
            $stmt = db()->prepare('INSERT INTO products (name, slug, sku, short_description, description, price, stock, badge_text, free_shipping, discount_type, discount_value, main_image, category_id, order_channel, order_link, created_at) VALUES (:name, :slug, :sku, :short_description, :description, :price, :stock, :badge_text, :free_shipping, :discount_type, :discount_value, :main_image, :category_id, :order_channel, :order_link, :created_at)');
            $stmt->execute([
                'name' => $name,
                'slug' => $slug,
                'sku' => $sku,
                'short_description' => $shortDescription,
                'description' => trim($_POST['description'] ?? ''),
                'price' => (float) ($_POST['price'] ?? 0),
                'stock' => $stock,
                'badge_text' => $badgeText,
                'free_shipping' => $freeShipping,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
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
        $featuresRaw = trim($_POST['features'] ?? '');
        if ($featuresRaw !== '') {
            db()->prepare('DELETE FROM product_features WHERE product_id = :product_id')->execute(['product_id' => $productId]);
            foreach (explode("\n", $featuresRaw) as $line) {
                $line = trim($line);
                if ($line === '' || !str_contains($line, ':')) {
                    continue;
                }
                [$name, $value] = array_map('trim', explode(':', $line, 2));
                if ($name && $value) {
                    db()->prepare('INSERT INTO product_features (product_id, feature_name, feature_value) VALUES (:product_id, :feature_name, :feature_value)')
                        ->execute(['product_id' => $productId, 'feature_name' => $name, 'feature_value' => $value]);
                }
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
        $title = trim($_POST['title'] ?? '');
        $slug = permalink($title);
        if ($pageId) {
            $stmt = db()->prepare('UPDATE pages SET title = :title, slug = :slug, summary = :summary, content = :content WHERE id = :id');
            $stmt->execute([
                'title' => $title,
                'slug' => $slug,
                'summary' => trim($_POST['summary'] ?? ''),
                'content' => trim($_POST['content'] ?? ''),
                'id' => $pageId,
            ]);
        } else {
            $stmt = db()->prepare('INSERT INTO pages (title, slug, summary, content, created_at) VALUES (:title, :slug, :summary, :content, :created_at)');
            $stmt->execute([
                'title' => $title,
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
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $slug = permalink($name);
        $image = handle_upload('image');
        if ($categoryId) {
            $stmt = db()->prepare('UPDATE categories SET name = :name, slug = :slug, description = :description, parent_id = :parent_id, icon = :icon, image = COALESCE(:image, image) WHERE id = :id');
            $stmt->execute([
                'name' => $name,
                'slug' => $slug,
                'parent_id' => $_POST['parent_id'] ?: null,
                'description' => $description,
                'icon' => trim($_POST['icon'] ?? ''),
                'image' => $image,
                'id' => $categoryId,
            ]);
        } else {
            $stmt = db()->prepare('INSERT INTO categories (name, slug, description, parent_id, icon, image, created_at) VALUES (:name, :slug, :description, :parent_id, :icon, :image, :created_at)');
            $stmt->execute([
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
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
    case 'slider':
        if (!is_admin()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Yetkisiz.']);
            break;
        }
        $sliderId = (int) ($_POST['id'] ?? 0);
        $image = handle_upload('image');
        $isActive = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;
        $sliderData = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'button_text' => trim($_POST['button_text'] ?? ''),
            'button_url' => trim($_POST['button_url'] ?? ''),
            'gradient_start' => trim($_POST['gradient_start'] ?? ''),
            'gradient_end' => trim($_POST['gradient_end'] ?? ''),
            'title_color' => trim($_POST['title_color'] ?? ''),
            'description_color' => trim($_POST['description_color'] ?? ''),
            'image_position' => $_POST['image_position'] ?? 'right',
            'slide_effect' => $_POST['slide_effect'] ?? 'fade-up',
            'image' => $image,
            'is_active' => $isActive,
        ];
        if ($sliderId) {
            $stmt = db()->prepare('UPDATE sliders SET title = :title, description = :description, button_text = :button_text, button_url = :button_url, image = COALESCE(:image, image), gradient_start = :gradient_start, gradient_end = :gradient_end, title_color = :title_color, description_color = :description_color, image_position = :image_position, slide_effect = :slide_effect, is_active = :is_active WHERE id = :id');
            $sliderData['id'] = $sliderId;
            $stmt->execute($sliderData);
        } else {
            $stmt = db()->prepare('INSERT INTO sliders (title, description, button_text, button_url, image, gradient_start, gradient_end, title_color, description_color, image_position, slide_effect, is_active, created_at) VALUES (:title, :description, :button_text, :button_url, :image, :gradient_start, :gradient_end, :title_color, :description_color, :image_position, :slide_effect, :is_active, :created_at)');
            $sliderData['created_at'] = date('Y-m-d H:i:s');
            $stmt->execute($sliderData);
        }
        echo json_encode(['success' => true, 'message' => 'Slider kaydedildi.']);
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
    case 'delete-slider':
        if (!is_admin()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Yetkisiz.']);
            break;
        }
        $sliderId = (int) ($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM sliders WHERE id = :id')->execute(['id' => $sliderId]);
        echo json_encode(['success' => true, 'message' => 'Slider silindi.']);
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
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        if (!$productId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Ürün seçilmedi.']);
            break;
        }
        $_SESSION['cart'] = $_SESSION['cart'] ?? [];
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $quantity;
        $cartCount = array_sum($_SESSION['cart']);
        echo json_encode(['success' => true, 'message' => 'Sepete eklendi.', 'cart_count' => $cartCount]);
        break;
    case 'cart-remove':
        $productId = (int) ($_POST['product_id'] ?? 0);
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
        }
        $cartCount = array_sum($_SESSION['cart'] ?? []);
        echo json_encode(['success' => true, 'message' => 'Sepetten çıkarıldı.', 'cart_count' => $cartCount]);
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
    case 'stats':
        if (!is_admin()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Yetkisiz.']);
            break;
        }
        $stmt = db()->query("SELECT DATE(created_at) as day, COUNT(*) as count FROM orders GROUP BY day ORDER BY day DESC LIMIT 7");
        $rows = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
        $labels = array_map(fn($row) => $row['day'], $rows);
        $counts = array_map(fn($row) => (int) $row['count'], $rows);
        $summary = [
            'pending' => (int) db()->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
            'approved' => (int) db()->query("SELECT COUNT(*) FROM orders WHERE status = 'approved'")->fetchColumn(),
            'preparing' => (int) db()->query("SELECT COUNT(*) FROM orders WHERE status = 'preparing'")->fetchColumn(),
            'shipping' => (int) db()->query("SELECT COUNT(*) FROM orders WHERE status = 'shipping'")->fetchColumn(),
            'delivered' => (int) db()->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn(),
        ];
        echo json_encode(['success' => true, 'labels' => $labels, 'counts' => $counts, 'summary' => $summary]);
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
