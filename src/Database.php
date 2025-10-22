<?php

namespace App;

use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $dbPath = __DIR__ . '/../data/app.db';
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $dsn = 'sqlite:' . $dbPath;
            self::$pdo = new PDO($dsn);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::migrate();
        }

        return self::$pdo;
    }

    private static function migrate(): void
    {
        $queries = [
            'CREATE TABLE IF NOT EXISTS caris (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                type TEXT NOT NULL,
                email TEXT,
                phone TEXT,
                address TEXT,
                group_name TEXT,
                balance REAL DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )',
            'CREATE TABLE IF NOT EXISTS stock_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                sku TEXT,
                name TEXT NOT NULL,
                category TEXT,
                description TEXT,
                quantity REAL DEFAULT 0,
                critical_level REAL DEFAULT 0,
                price REAL DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )',
            'CREATE TABLE IF NOT EXISTS invoices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_no TEXT NOT NULL,
                cari_id INTEGER NOT NULL,
                type TEXT NOT NULL,
                issue_date TEXT NOT NULL,
                due_date TEXT,
                notes TEXT,
                total REAL DEFAULT 0,
                currency TEXT DEFAULT "TRY",
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (cari_id) REFERENCES caris(id) ON DELETE CASCADE
            )',
            'CREATE TABLE IF NOT EXISTS invoice_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_id INTEGER NOT NULL,
                stock_item_id INTEGER,
                description TEXT,
                quantity REAL NOT NULL,
                unit_price REAL NOT NULL,
                vat_rate REAL DEFAULT 0,
                total REAL NOT NULL,
                FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
                FOREIGN KEY (stock_item_id) REFERENCES stock_items(id) ON DELETE SET NULL
            )',
            'CREATE TABLE IF NOT EXISTS payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cari_id INTEGER NOT NULL,
                invoice_id INTEGER,
                type TEXT NOT NULL,
                amount REAL NOT NULL,
                payment_date TEXT NOT NULL,
                notes TEXT,
                FOREIGN KEY (cari_id) REFERENCES caris(id) ON DELETE CASCADE,
                FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL
            )',
            'CREATE TABLE IF NOT EXISTS bank_accounts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                bank_name TEXT NOT NULL,
                iban TEXT,
                account_no TEXT,
                balance REAL DEFAULT 0,
                currency TEXT DEFAULT "TRY"
            )',
            'CREATE TABLE IF NOT EXISTS cash_flows (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                bank_account_id INTEGER,
                description TEXT,
                type TEXT NOT NULL,
                amount REAL NOT NULL,
                flow_date TEXT NOT NULL,
                FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE SET NULL
            )',
            'CREATE TABLE IF NOT EXISTS activity_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_name TEXT NOT NULL,
                action TEXT NOT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )',
            'CREATE TABLE IF NOT EXISTS settings (
                id INTEGER PRIMARY KEY CHECK (id = 1),
                company_name TEXT,
                logo_path TEXT,
                address TEXT,
                invoice_template TEXT DEFAULT "standart",
                preferences TEXT
            )'
        ];

        $pdo = self::$pdo;
        foreach ($queries as $query) {
            $pdo->exec($query);
        }

        $pdo->exec('INSERT OR IGNORE INTO settings(id, company_name, invoice_template) VALUES (1, "Yeni Firma", "standart")');
    }
}
