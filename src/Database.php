<?php

namespace App;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;
    private static array $config = [];

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            self::$config = self::loadConfig();
            $dbConfig = self::$config['db'];

            $host = $dbConfig['host'];
            $database = $dbConfig['database'];
            $user = $dbConfig['user'];
            $password = $dbConfig['password'];
            $charset = $dbConfig['charset'];
            $collation = $dbConfig['collation'];

            $baseDsn = sprintf('mysql:host=%s;charset=%s', $host, $charset);
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            try {
                $creator = new PDO($baseDsn, $user, $password, $options);
                $quotedDbName = '`' . str_replace('`', '``', $database) . '`';
                $creator->exec(sprintf(
                    'CREATE DATABASE IF NOT EXISTS %s CHARACTER SET %s COLLATE %s',
                    $quotedDbName,
                    $charset,
                    $collation
                ));
                $creator = null;
            } catch (PDOException $exception) {
                throw new PDOException('Veritabanı oluşturulurken hata oluştu: ' . $exception->getMessage(), (int)$exception->getCode(), $exception);
            }

            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $host, $database, $charset);
            self::$pdo = new PDO($dsn, $user, $password, $options);
            self::migrate();
        }

        return self::$pdo;
    }

    private static function migrate(): void
    {
        $charset = self::$config['db']['charset'];
        $collation = self::$config['db']['collation'];
        $tableSuffix = sprintf(' ENGINE=InnoDB DEFAULT CHARSET=%s COLLATE=%s', $charset, $collation);

        $queries = [
            'CREATE TABLE IF NOT EXISTS caris (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(50) NOT NULL,
                email VARCHAR(255) NULL,
                phone VARCHAR(50) NULL,
                address TEXT NULL,
                group_name VARCHAR(100) NULL,
                balance DECIMAL(15,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )' . $tableSuffix,
            'CREATE TABLE IF NOT EXISTS stock_items (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                sku VARCHAR(100) NULL,
                name VARCHAR(255) NOT NULL,
                category VARCHAR(150) NULL,
                description TEXT NULL,
                quantity DECIMAL(15,3) DEFAULT 0,
                critical_level DECIMAL(15,3) DEFAULT 0,
                price DECIMAL(15,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )' . $tableSuffix,
            'CREATE TABLE IF NOT EXISTS invoices (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                invoice_no VARCHAR(100) NOT NULL,
                cari_id INT UNSIGNED NOT NULL,
                type VARCHAR(50) NOT NULL,
                issue_date DATE NOT NULL,
                due_date DATE NULL,
                notes TEXT NULL,
                total DECIMAL(15,2) DEFAULT 0,
                currency VARCHAR(10) DEFAULT "TRY",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_invoices_cari FOREIGN KEY (cari_id) REFERENCES caris(id) ON DELETE CASCADE
            )' . $tableSuffix,
            'CREATE TABLE IF NOT EXISTS invoice_items (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                invoice_id INT UNSIGNED NOT NULL,
                stock_item_id INT UNSIGNED NULL,
                description TEXT NULL,
                quantity DECIMAL(15,3) NOT NULL,
                unit_price DECIMAL(15,2) NOT NULL,
                vat_rate DECIMAL(5,2) DEFAULT 0,
                total DECIMAL(15,2) NOT NULL,
                CONSTRAINT fk_invoice_items_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
                CONSTRAINT fk_invoice_items_stock FOREIGN KEY (stock_item_id) REFERENCES stock_items(id) ON DELETE SET NULL
            )' . $tableSuffix,
            'CREATE TABLE IF NOT EXISTS payments (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                cari_id INT UNSIGNED NOT NULL,
                invoice_id INT UNSIGNED NULL,
                type VARCHAR(50) NOT NULL,
                amount DECIMAL(15,2) NOT NULL,
                payment_date DATE NOT NULL,
                notes TEXT NULL,
                CONSTRAINT fk_payments_cari FOREIGN KEY (cari_id) REFERENCES caris(id) ON DELETE CASCADE,
                CONSTRAINT fk_payments_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL
            )' . $tableSuffix,
            'CREATE TABLE IF NOT EXISTS bank_accounts (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                bank_name VARCHAR(150) NOT NULL,
                iban VARCHAR(34) NULL,
                account_no VARCHAR(100) NULL,
                balance DECIMAL(15,2) DEFAULT 0,
                currency VARCHAR(10) DEFAULT "TRY"
            )' . $tableSuffix,
            'CREATE TABLE IF NOT EXISTS cash_flows (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                bank_account_id INT UNSIGNED NULL,
                description TEXT NULL,
                type VARCHAR(50) NOT NULL,
                amount DECIMAL(15,2) NOT NULL,
                flow_date DATE NOT NULL,
                CONSTRAINT fk_cash_flows_bank FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE SET NULL
            )' . $tableSuffix,
            'CREATE TABLE IF NOT EXISTS activity_logs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_name VARCHAR(150) NOT NULL,
                action TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )' . $tableSuffix,
            'CREATE TABLE IF NOT EXISTS settings (
                id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
                company_name VARCHAR(255) NULL,
                logo_path VARCHAR(255) NULL,
                address TEXT NULL,
                invoice_template VARCHAR(100) DEFAULT "standart",
                preferences TEXT NULL
            )' . $tableSuffix
        ];

        $pdo = self::$pdo;
        foreach ($queries as $query) {
            $pdo->exec($query);
        }

        $pdo->exec('INSERT INTO settings (id, company_name, invoice_template) VALUES (1, "Yeni Firma", "standart") ON DUPLICATE KEY UPDATE id = id');
    }

    public static function exportSql(): string
    {
        $pdo = self::connection();
        $dbName = self::$config['db']['database'];
        $tables = [
            'caris',
            'stock_items',
            'invoices',
            'invoice_items',
            'payments',
            'bank_accounts',
            'cash_flows',
            'activity_logs',
            'settings',
        ];

        $lines = [];
        $lines[] = '-- Yedek oluşturma tarihi: ' . date('Y-m-d H:i:s');
        $lines[] = sprintf('USE `%s`;', str_replace('`', '``', $dbName));
        $lines[] = 'SET FOREIGN_KEY_CHECKS=0;';

        foreach ($tables as $table) {
            $quotedTable = '`' . str_replace('`', '``', $table) . '`';
            $create = $pdo->query('SHOW CREATE TABLE ' . $quotedTable)->fetch(PDO::FETCH_ASSOC);
            if (!isset($create['Create Table'])) {
                continue;
            }
            $lines[] = sprintf('\nDROP TABLE IF EXISTS %s;', $quotedTable);
            $lines[] = $create['Create Table'] . ';';

            $stmt = $pdo->query('SELECT * FROM ' . $quotedTable);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns = array_map(fn($col) => '`' . str_replace('`', '``', $col) . '`', array_keys($row));
                $values = array_map(function ($value) use ($pdo) {
                    if ($value === null) {
                        return 'NULL';
                    }

                    return $pdo->quote((string)$value);
                }, array_values($row));
                $lines[] = sprintf('INSERT INTO %s (%s) VALUES (%s);', $quotedTable, implode(', ', $columns), implode(', ', $values));
            }
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';

        return implode("\n", $lines) . "\n";
    }

    private static function loadConfig(): array
    {
        $configPath = __DIR__ . '/../config.php';
        if (!file_exists($configPath)) {
            throw new PDOException('config.php bulunamadı. Lütfen veritabanı ayarlarınızı tanımlayın.');
        }

        $config = require $configPath;
        if (!isset($config['db'])) {
            throw new PDOException('config.php dosyasında "db" ayarları bulunamadı.');
        }

        $defaults = [
            'host' => '127.0.0.1',
            'database' => 'muhasebe',
            'user' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ];

        $config['db'] = array_merge($defaults, $config['db']);

        return $config;
    }
}
