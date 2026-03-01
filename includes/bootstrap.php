<?php

session_start();

require_once __DIR__ . '/helpers.php';


function ensure_dynamic_schema(): void
{
    static $initialized = false;
    if ($initialized) {
        return;
    }
    $initialized = true;
    $pdo = db();
    $queries = [
        "CREATE TABLE IF NOT EXISTS currencies (id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(10) NOT NULL UNIQUE, name VARCHAR(100) NOT NULL, symbol VARCHAR(20) NOT NULL, rate DECIMAL(18,6) NOT NULL DEFAULT 1, is_default TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL)",
        "CREATE TABLE IF NOT EXISTS product_prices (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, currency_code VARCHAR(10) NOT NULL, price DECIMAL(18,2) NOT NULL DEFAULT 0, UNIQUE KEY uniq_product_currency (product_id, currency_code), FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE)",
        "CREATE TABLE IF NOT EXISTS crypto_wallets (id INT AUTO_INCREMENT PRIMARY KEY, wallet_name VARCHAR(150) NOT NULL, wallet_address VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL)",
        "CREATE TABLE IF NOT EXISTS crypto_notifications (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, user_id INT NULL, full_name VARCHAR(190) NOT NULL, transaction_no VARCHAR(190) NOT NULL, wallet_name VARCHAR(150) NULL, status VARCHAR(30) NOT NULL DEFAULT 'pending', created_at DATETIME NOT NULL, FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL)",
    ];
    foreach ($queries as $query) {
        try { $pdo->exec($query); } catch (Throwable $e) { }
    }
    try {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM currencies')->fetchColumn();
        if ($count === 0) {
            $pdo->prepare('INSERT INTO currencies (code, name, symbol, rate, is_default, created_at) VALUES (:code,:name,:symbol,:rate,:is_default,:created_at)')
                ->execute(['code' => 'TRY', 'name' => 'Türk Lirası', 'symbol' => '₺', 'rate' => 1, 'is_default' => 1, 'created_at' => date('Y-m-d H:i:s')]);
        }
    } catch (Throwable $e) { }
}

ensure_dynamic_schema();
