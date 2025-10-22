<?php
declare(strict_types=1);

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (str_starts_with($class, $prefix)) {
        $relative = substr($class, strlen($prefix));
        $path = __DIR__ . '/../src/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }
});

use App\Database;
use App\Helpers;

session_start();

$pdo = Database::connection();

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = 'admin';
}

$module = $_GET['module'] ?? 'dashboard';
$action = $_GET['action'] ?? 'list';

function render(string $template, array $data = []): void
{
    extract($data);
    include __DIR__ . '/../templates/header.php';
    include __DIR__ . '/../templates/' . $template . '.php';
    include __DIR__ . '/../templates/footer.php';
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function sanitize(array $data): array
{
    return array_map(fn($value) => is_string($value) ? trim($value) : $value, $data);
}

function exportAs(string $type, string $filename, array $headers, array $rows): void
{
    switch ($type) {
        case 'pdf':
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename=' . $filename . '.pdf');
            $lines = [];
            $lines[] = implode("\t", $headers);
            foreach ($rows as $row) {
                $lines[] = implode("\t", array_map(fn($value) => (string)$value, $row));
            }
            $streamContent = 'BT /F1 12 Tf 40 780 Td ';
            $first = true;
            foreach ($lines as $line) {
                $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
                if (!$first) {
                    $streamContent .= 'T* ';
                }
                $streamContent .= '(' . $escaped . ') Tj ';
                $first = false;
            }
            $streamContent .= 'ET';

            $objects = [];
            $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
            $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>';
            $objects[] = '<< /Length ' . strlen($streamContent) . ' >>\nstream\n' . $streamContent . '\nendstream';
            $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

            $pdf = "%PDF-1.4\n";
            $offsets = [0];
            foreach ($objects as $index => $object) {
                $offsets[$index + 1] = strlen($pdf);
                $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
            }
            $xrefOffset = strlen($pdf);
            $count = count($objects) + 1;
            $pdf .= 'xref\n0 ' . $count . "\n0000000000 65535 f \n";
            for ($i = 1; $i < $count; $i++) {
                $pdf .= sprintf('%010d 00000 n ', $offsets[$i]) . "\n";
            }
            $pdf .= 'trailer << /Size ' . $count . ' /Root 1 0 R >>\nstartxref\n' . $xrefOffset . "\n%%EOF";
            echo $pdf;
            exit;
        case 'excel':
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename=' . $filename . '.xls');
            $out = implode("\t", $headers) . "\n";
            foreach ($rows as $row) {
                $out .= implode("\t", $row) . "\n";
            }
            echo $out;
            exit;
        case 'word':
            header('Content-Type: application/msword');
            header('Content-Disposition: attachment; filename=' . $filename . '.doc');
            echo "<table border=1><tr>";
            foreach ($headers as $header) {
                echo '<th>' . htmlspecialchars($header) . '</th>';
            }
            echo '</tr>';
            foreach ($rows as $row) {
                echo '<tr>';
                foreach ($row as $value) {
                    echo '<td>' . htmlspecialchars((string)$value) . '</td>';
                }
                echo '</tr>';
            }
            echo '</table>';
            exit;
    }
}

switch ($module) {
    case 'caris':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payload = sanitize($_POST);
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO caris (name, type, email, phone, address, group_name) VALUES (:name, :type, :email, :phone, :address, :group_name)');
                $stmt->execute([
                    ':name' => $payload['name'],
                    ':type' => $payload['type'],
                    ':email' => $payload['email'] ?? null,
                    ':phone' => $payload['phone'] ?? null,
                    ':address' => $payload['address'] ?? null,
                    ':group_name' => $payload['group_name'] ?? null,
                ]);
                Helpers::log($_SESSION['user'], 'Yeni cari oluşturuldu: ' . $payload['name']);
            } elseif ($action === 'update' && isset($_GET['id'])) {
                $stmt = $pdo->prepare('UPDATE caris SET name=:name, type=:type, email=:email, phone=:phone, address=:address, group_name=:group_name, updated_at=CURRENT_TIMESTAMP WHERE id=:id');
                $stmt->execute([
                    ':name' => $payload['name'],
                    ':type' => $payload['type'],
                    ':email' => $payload['email'] ?? null,
                    ':phone' => $payload['phone'] ?? null,
                    ':address' => $payload['address'] ?? null,
                    ':group_name' => $payload['group_name'] ?? null,
                    ':id' => $_GET['id'],
                ]);
                Helpers::log($_SESSION['user'], 'Cari güncellendi: ' . $payload['name']);
            }
            redirect('index.php?module=caris');
        }
        if ($action === 'delete' && isset($_GET['id'])) {
            $stmt = $pdo->prepare('DELETE FROM caris WHERE id=:id');
            $stmt->execute([':id' => $_GET['id']]);
            Helpers::log($_SESSION['user'], 'Cari silindi #' . $_GET['id']);
            redirect('index.php?module=caris');
        }
        if ($action === 'export') {
            $type = $_GET['type'] ?? 'excel';
            $caris = $pdo->query('SELECT name, type, email, phone, group_name, balance FROM caris ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
            exportAs($type, 'cari_listesi', ['Ad', 'Tip', 'E-posta', 'Telefon', 'Grup', 'Bakiye'], array_map(fn($c) => [$c['name'], $c['type'], $c['email'], $c['phone'], $c['group_name'], $c['balance']], $caris));
        }
        $cari = null;
        if ($action === 'edit' && isset($_GET['id'])) {
            $stmt = $pdo->prepare('SELECT * FROM caris WHERE id = :id');
            $stmt->execute([':id' => $_GET['id']]);
            $cari = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        $caris = $pdo->query('SELECT * FROM caris ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
        render('caris', compact('caris', 'cari'));
        break;
    case 'stock':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payload = sanitize($_POST);
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO stock_items (sku, name, category, description, quantity, critical_level, price) VALUES (:sku, :name, :category, :description, :quantity, :critical_level, :price)');
                $stmt->execute([
                    ':sku' => $payload['sku'] ?? null,
                    ':name' => $payload['name'],
                    ':category' => $payload['category'] ?? null,
                    ':description' => $payload['description'] ?? null,
                    ':quantity' => (float)($payload['quantity'] ?? 0),
                    ':critical_level' => (float)($payload['critical_level'] ?? 0),
                    ':price' => (float)($payload['price'] ?? 0),
                ]);
                Helpers::log($_SESSION['user'], 'Stok eklendi: ' . $payload['name']);
            } elseif ($action === 'update' && isset($_GET['id'])) {
                $stmt = $pdo->prepare('UPDATE stock_items SET sku=:sku, name=:name, category=:category, description=:description, quantity=:quantity, critical_level=:critical_level, price=:price, updated_at=CURRENT_TIMESTAMP WHERE id=:id');
                $stmt->execute([
                    ':sku' => $payload['sku'] ?? null,
                    ':name' => $payload['name'],
                    ':category' => $payload['category'] ?? null,
                    ':description' => $payload['description'] ?? null,
                    ':quantity' => (float)($payload['quantity'] ?? 0),
                    ':critical_level' => (float)($payload['critical_level'] ?? 0),
                    ':price' => (float)($payload['price'] ?? 0),
                    ':id' => $_GET['id'],
                ]);
                Helpers::log($_SESSION['user'], 'Stok güncellendi #' . $_GET['id']);
            }
            redirect('index.php?module=stock');
        }
        if ($action === 'delete' && isset($_GET['id'])) {
            $stmt = $pdo->prepare('DELETE FROM stock_items WHERE id=:id');
            $stmt->execute([':id' => $_GET['id']]);
            Helpers::log($_SESSION['user'], 'Stok silindi #' . $_GET['id']);
            redirect('index.php?module=stock');
        }
        if ($action === 'export') {
            $type = $_GET['type'] ?? 'excel';
            $items = $pdo->query('SELECT sku, name, category, quantity, critical_level, price FROM stock_items ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
            exportAs($type, 'stok_listesi', ['SKU', 'Ad', 'Kategori', 'Miktar', 'Kritik Seviye', 'Fiyat'], array_map(fn($i) => [$i['sku'], $i['name'], $i['category'], $i['quantity'], $i['critical_level'], $i['price']], $items));
        }
        $item = null;
        if ($action === 'edit' && isset($_GET['id'])) {
            $stmt = $pdo->prepare('SELECT * FROM stock_items WHERE id=:id');
            $stmt->execute([':id' => $_GET['id']]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        $items = $pdo->query('SELECT *, CASE WHEN quantity <= critical_level THEN 1 ELSE 0 END AS critical FROM stock_items ORDER BY updated_at DESC')->fetchAll(PDO::FETCH_ASSOC);
        render('stock', compact('items', 'item'));
        break;
    case 'invoices':
        if ($action === 'export') {
            $type = $_GET['type'] ?? 'excel';
            $invoices = $pdo->query('SELECT invoices.invoice_no, invoices.type, invoices.issue_date, invoices.due_date, invoices.total, invoices.currency, caris.name as cari_name FROM invoices JOIN caris ON caris.id = invoices.cari_id ORDER BY issue_date DESC')->fetchAll(PDO::FETCH_ASSOC);
            exportAs($type, 'faturalar', ['No', 'Tip', 'Tarih', 'Vade', 'Cari', 'Toplam', 'Para Birimi'], array_map(fn($i) => [$i['invoice_no'], $i['type'], $i['issue_date'], $i['due_date'], $i['cari_name'], $i['total'], $i['currency']], $invoices));
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
            $payload = sanitize($_POST);
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO invoices (invoice_no, cari_id, type, issue_date, due_date, notes, total, currency) VALUES (:invoice_no, :cari_id, :type, :issue_date, :due_date, :notes, :total, :currency)');
            $stmt->execute([
                ':invoice_no' => $payload['invoice_no'],
                ':cari_id' => (int)$payload['cari_id'],
                ':type' => $payload['type'],
                ':issue_date' => Helpers::parseDate($payload['issue_date']),
                ':due_date' => Helpers::parseDate($payload['due_date']),
                ':notes' => $payload['notes'] ?? null,
                ':total' => (float)$payload['total'],
                ':currency' => $payload['currency'] ?? 'TRY',
            ]);
            $invoiceId = (int)$pdo->lastInsertId();
            if (!empty($payload['items'])) {
                foreach ($payload['items'] as $item) {
                    $item = sanitize($item);
                    $stmtItem = $pdo->prepare('INSERT INTO invoice_items (invoice_id, stock_item_id, description, quantity, unit_price, vat_rate, total) VALUES (:invoice_id, :stock_item_id, :description, :quantity, :unit_price, :vat_rate, :total)');
                    $stmtItem->execute([
                        ':invoice_id' => $invoiceId,
                        ':stock_item_id' => $item['stock_item_id'] ? (int)$item['stock_item_id'] : null,
                        ':description' => $item['description'] ?? null,
                        ':quantity' => (float)$item['quantity'],
                        ':unit_price' => (float)$item['unit_price'],
                        ':vat_rate' => (float)($item['vat_rate'] ?? 0),
                        ':total' => (float)$item['total'],
                    ]);
                    if (!empty($item['stock_item_id'])) {
                        $quantity = (float)$item['quantity'];
                        $sign = $payload['type'] === 'satis' ? -1 : 1;
                        $update = $pdo->prepare('UPDATE stock_items SET quantity = quantity + :diff WHERE id=:id');
                        $update->execute([':diff' => $sign * $quantity, ':id' => (int)$item['stock_item_id']]);
                    }
                }
            }
            $pdo->commit();
            Helpers::log($_SESSION['user'], 'Fatura oluşturuldu #' . $invoiceId);
            redirect('index.php?module=invoices');
        }
        if ($action === 'delete' && isset($_GET['id'])) {
            $pdo->prepare('DELETE FROM invoices WHERE id=:id')->execute([':id' => $_GET['id']]);
            Helpers::log($_SESSION['user'], 'Fatura silindi #' . $_GET['id']);
            redirect('index.php?module=invoices');
        }
        $caris = $pdo->query('SELECT id, name FROM caris ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
        $stockItems = $pdo->query('SELECT id, name, price FROM stock_items ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
        $invoices = $pdo->query('SELECT invoices.*, caris.name as cari_name FROM invoices JOIN caris ON caris.id = invoices.cari_id ORDER BY issue_date DESC')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($invoices as &$invoice) {
            $stmt = $pdo->prepare('SELECT invoice_items.*, stock_items.name as stock_name FROM invoice_items LEFT JOIN stock_items ON stock_items.id = invoice_items.stock_item_id WHERE invoice_items.invoice_id = :invoice_id');
            $stmt->execute([':invoice_id' => $invoice['id']]);
            $invoice['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        render('invoices', compact('caris', 'stockItems', 'invoices'));
        break;
    case 'payments':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payload = sanitize($_POST);
            $stmt = $pdo->prepare('INSERT INTO payments (cari_id, invoice_id, type, amount, payment_date, notes) VALUES (:cari_id, :invoice_id, :type, :amount, :payment_date, :notes)');
            $stmt->execute([
                ':cari_id' => (int)$payload['cari_id'],
                ':invoice_id' => !empty($payload['invoice_id']) ? (int)$payload['invoice_id'] : null,
                ':type' => $payload['type'],
                ':amount' => (float)$payload['amount'],
                ':payment_date' => Helpers::parseDate($payload['payment_date']),
                ':notes' => $payload['notes'] ?? null,
            ]);
            $balanceUpdate = $payload['type'] === 'tahsilat' ? -1 : 1;
            $pdo->prepare('UPDATE caris SET balance = balance + :amount WHERE id=:id')->execute([
                ':amount' => $balanceUpdate * (float)$payload['amount'],
                ':id' => (int)$payload['cari_id'],
            ]);
            Helpers::log($_SESSION['user'], 'Ödeme kaydedildi: ' . $payload['type']);
            redirect('index.php?module=payments');
        }
        if ($action === 'export') {
            $type = $_GET['type'] ?? 'excel';
            $payments = $pdo->query('SELECT payments.payment_date, payments.type, payments.amount, caris.name as cari_name FROM payments JOIN caris ON caris.id = payments.cari_id ORDER BY payments.payment_date DESC')->fetchAll(PDO::FETCH_ASSOC);
            exportAs($type, 'odemeler', ['Tarih', 'Tip', 'Cari', 'Tutar'], array_map(fn($p) => [$p['payment_date'], $p['type'], $p['cari_name'], $p['amount']], $payments));
        }
        $caris = $pdo->query('SELECT id, name FROM caris ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
        $invoices = $pdo->query('SELECT id, invoice_no FROM invoices ORDER BY issue_date DESC')->fetchAll(PDO::FETCH_ASSOC);
        $payments = $pdo->query('SELECT payments.*, caris.name as cari_name, invoices.invoice_no FROM payments JOIN caris ON caris.id = payments.cari_id LEFT JOIN invoices ON invoices.id = payments.invoice_id ORDER BY payment_date DESC')->fetchAll(PDO::FETCH_ASSOC);
        render('payments', compact('caris', 'invoices', 'payments'));
        break;
    case 'reports':
        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-t');
        $sales = $pdo->prepare("SELECT SUM(total) as total FROM invoices WHERE type='satis' AND issue_date BETWEEN :start AND :end");
        $sales->execute([':start' => $start, ':end' => $end]);
        $salesTotal = (float)$sales->fetchColumn();
        $purchases = $pdo->prepare("SELECT SUM(total) as total FROM invoices WHERE type='alis' AND issue_date BETWEEN :start AND :end");
        $purchases->execute([':start' => $start, ':end' => $end]);
        $purchasesTotal = (float)$purchases->fetchColumn();
        $profit = $salesTotal - $purchasesTotal;
        $stocks = $pdo->query('SELECT name, quantity, price FROM stock_items ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
        $cariSummary = $pdo->query('SELECT name, balance FROM caris ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
        if ($action === 'export') {
            $type = $_GET['type'] ?? 'excel';
            $rows = [
                ['Satış Toplamı', $salesTotal],
                ['Alış Toplamı', $purchasesTotal],
                ['Kâr/Zarar', $profit],
            ];
            exportAs($type, 'rapor', ['Kalem', 'Tutar'], $rows);
        }
        render('reports', compact('start', 'end', 'salesTotal', 'purchasesTotal', 'profit', 'stocks', 'cariSummary'));
        break;
    case 'banks':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payload = sanitize($_POST);
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO bank_accounts (bank_name, iban, account_no, balance, currency) VALUES (:bank_name, :iban, :account_no, :balance, :currency)');
                $stmt->execute([
                    ':bank_name' => $payload['bank_name'],
                    ':iban' => $payload['iban'] ?? null,
                    ':account_no' => $payload['account_no'] ?? null,
                    ':balance' => (float)$payload['balance'],
                    ':currency' => $payload['currency'] ?? 'TRY',
                ]);
                Helpers::log($_SESSION['user'], 'Banka hesabı eklendi: ' . $payload['bank_name']);
            } elseif ($action === 'flow') {
                $stmt = $pdo->prepare('INSERT INTO cash_flows (bank_account_id, description, type, amount, flow_date) VALUES (:bank_account_id, :description, :type, :amount, :flow_date)');
                $stmt->execute([
                    ':bank_account_id' => (int)$payload['bank_account_id'],
                    ':description' => $payload['description'] ?? null,
                    ':type' => $payload['type'],
                    ':amount' => (float)$payload['amount'],
                    ':flow_date' => Helpers::parseDate($payload['flow_date']),
                ]);
                $sign = $payload['type'] === 'giris' ? 1 : -1;
                $pdo->prepare('UPDATE bank_accounts SET balance = balance + :amount WHERE id=:id')->execute([
                    ':amount' => $sign * (float)$payload['amount'],
                    ':id' => (int)$payload['bank_account_id'],
                ]);
                Helpers::log($_SESSION['user'], 'Banka hareketi: ' . $payload['type']);
            }
            redirect('index.php?module=banks');
        }
        $accounts = $pdo->query('SELECT * FROM bank_accounts ORDER BY bank_name')->fetchAll(PDO::FETCH_ASSOC);
        $flows = $pdo->query('SELECT cash_flows.*, bank_accounts.bank_name FROM cash_flows LEFT JOIN bank_accounts ON bank_accounts.id = cash_flows.bank_account_id ORDER BY flow_date DESC')->fetchAll(PDO::FETCH_ASSOC);
        render('banks', compact('accounts', 'flows'));
        break;
    case 'settings':
        if ($action === 'backup') {
            $dbPath = __DIR__ . '/../data/app.db';
            if (file_exists($dbPath)) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename=app.db');
                header('Content-Length: ' . filesize($dbPath));
                readfile($dbPath);
            } else {
                header('Content-Type: text/plain; charset=utf-8');
                echo 'Yedeklenecek veritabanı bulunamadı.';
            }
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payload = sanitize($_POST);
            $stmt = $pdo->prepare('UPDATE settings SET company_name=:company_name, address=:address, invoice_template=:invoice_template WHERE id=1');
            $stmt->execute([
                ':company_name' => $payload['company_name'],
                ':address' => $payload['address'] ?? null,
                ':invoice_template' => $payload['invoice_template'] ?? 'standart',
            ]);
            Helpers::log($_SESSION['user'], 'Ayarlar güncellendi');
            redirect('index.php?module=settings');
        }
        $settings = $pdo->query('SELECT * FROM settings WHERE id=1')->fetch(PDO::FETCH_ASSOC);
        render('settings', compact('settings'));
        break;
    case 'logs':
        $logs = $pdo->query('SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
        render('logs', compact('logs'));
        break;
    case 'dashboard':
    default:
        $cariCount = (int)$pdo->query('SELECT COUNT(*) FROM caris')->fetchColumn();
        $stockCount = (int)$pdo->query('SELECT COUNT(*) FROM stock_items')->fetchColumn();
        $invoiceCount = (int)$pdo->query('SELECT COUNT(*) FROM invoices')->fetchColumn();
        $paymentSum = (float)$pdo->query("SELECT COALESCE(SUM(CASE WHEN type='tahsilat' THEN amount ELSE -amount END),0) FROM payments")->fetchColumn();
        $criticalStocks = $pdo->query('SELECT name, quantity, critical_level FROM stock_items WHERE quantity <= critical_level ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
        $recentInvoices = $pdo->query('SELECT invoices.invoice_no, invoices.total, invoices.type, caris.name as cari_name FROM invoices JOIN caris ON caris.id = invoices.cari_id ORDER BY invoices.issue_date DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
        render('dashboard', compact('cariCount', 'stockCount', 'invoiceCount', 'paymentSum', 'criticalStocks', 'recentInvoices'));
        break;
}
