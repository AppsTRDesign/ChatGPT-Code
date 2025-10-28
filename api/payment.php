<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

$provider = $_GET['provider'] ?? $_POST['provider'] ?? '';

switch ($provider) {
    case 'iyzico':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed');
        }
        $options = iyzico_client($pdo);
        if (!$options) {
            http_response_code(400);
            exit('Iyzico devre dışı.');
        }
        $token = $_POST['token'] ?? '';
        if ($token === '') {
            http_response_code(400);
            exit('Eksik token.');
        }
        $transactionStmt = $pdo->prepare('SELECT * FROM transactions WHERE reference = :reference LIMIT 1');
        $transactionStmt->execute([':reference' => $token]);
        $transaction = $transactionStmt->fetch();
        if (!$transaction) {
            http_response_code(404);
            exit('İşlem bulunamadı.');
        }
        $request = new Iyzipay\Request\RetrieveCheckoutFormRequest();
        $request->setToken($token);
        $checkout = Iyzipay\Model\CheckoutForm::retrieve($request, $options);
        $status = strtolower($checkout->getPaymentStatus() ?? '');
        $payload = json_decode($checkout->getRawResult() ?? '{}', true);
        if ($status === 'success') {
            complete_transaction($pdo, (int) $transaction['id'], 'paid', $token, $payload ?: []);
            $returnUrl = $_GET['return'] ?? ($_POST['return'] ?? null);
            if ($returnUrl) {
                header('Location: ' . $returnUrl);
                exit;
            }
            echo 'Ödeme başarılı.';
        } else {
            complete_transaction($pdo, (int) $transaction['id'], 'failed', $token, $payload ?: []);
            echo 'Ödeme başarısız.';
        }
        break;

    case 'stripe':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
            exit;
        }
        header('Content-Type: application/json');
        $settings = fetch_settings($pdo);
        $webhookSecret = $settings['stripe_webhook_secret'] ?? '';
        if ($webhookSecret === '') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Webhook yapılandırması eksik']);
            exit;
        }
        $payload = file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        try {
            $event = \Stripe\Webhook::constructEvent($payload ?: '', $sigHeader, $webhookSecret);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
        if ($event->type === 'checkout.session.completed') {
            /** @var \Stripe\Checkout\Session $session */
            $session = $event->data->object;
            $transactionId = (int) ($session->metadata['transaction_id'] ?? 0);
            if ($transactionId > 0) {
                complete_transaction($pdo, $transactionId, 'paid', $session->id, $session->toArray());
            }
        } elseif ($event->type === 'checkout.session.expired') {
            $session = $event->data->object;
            $transactionId = (int) ($session->metadata['transaction_id'] ?? 0);
            if ($transactionId > 0) {
                complete_transaction($pdo, $transactionId, 'failed', $session->id, $session->toArray());
            }
        }
        echo json_encode(['status' => 'ok']);
        break;

    default:
        http_response_code(404);
        echo 'Sağlayıcı bulunamadı.';
}
