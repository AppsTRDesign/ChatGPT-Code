<?php

namespace App;

use PDO;

class Helpers
{
    public static function db(): PDO
    {
        return \get_db();
    }

    public static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function randomString(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::randomString(64);
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrf(?string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string) $token);
    }

    public static function flash(string $key, ?string $message = null): ?string
    {
        if ($message !== null) {
            $_SESSION['flash'][$key] = $message;
            return null;
        }

        if (isset($_SESSION['flash'][$key])) {
            $msg = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $msg;
        }

        return null;
    }

    public static function requireAjax(): void
    {
        $isXmlHttp = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $isFetch = isset($_SERVER['HTTP_SEC_FETCH_MODE'])
            && in_array(strtolower($_SERVER['HTTP_SEC_FETCH_MODE']), ['cors', 'same-origin'], true);
        $acceptsJson = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;

        if ($isXmlHttp || $isFetch || $acceptsJson) {
            return;
        }

        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'message' => 'Bu kaynağa doğrudan erişim engellendi.',
        ]);
        exit;
    }

    public static function tableExists(string $table): bool
    {
        $table = trim($table);
        if ($table === '') {
            return false;
        }

        try {
            $stmt = self::db()->prepare('SHOW TABLES LIKE :table');
            $stmt->execute(['table' => $table]);
            return $stmt->fetchColumn() !== false;
        } catch (\Throwable $exception) {
            error_log('Table existence check failed: ' . $exception->getMessage());
            return false;
        }
    }

    public static function tableColumns(string $table): array
    {
        $table = preg_replace('/[^A-Za-z0-9_]/', '', $table);
        if ($table === '') {
            return [];
        }

        try {
            if (!self::tableExists($table)) {
                return [];
            }

            $stmt = self::db()->query(sprintf('SHOW COLUMNS FROM `%s`', $table));
            $columns = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if (!empty($row['Field']) && is_string($row['Field'])) {
                    $columns[] = strtolower($row['Field']);
                }
            }

            return $columns;
        } catch (\Throwable $exception) {
            error_log('Table column fetch failed: ' . $exception->getMessage());
            return [];
        }
    }

    public static function columnExists(string $table, string $column): bool
    {
        $column = strtolower(preg_replace('/[^A-Za-z0-9_]/', '', $column));
        if ($column === '') {
            return false;
        }

        $columns = self::tableColumns($table);
        return in_array($column, $columns, true);
    }

    public static function sanitizeFilename(string $filename): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $filename);
        $clean = trim((string) $clean, '_');
        return $clean !== '' ? $clean : 'export';
    }

    private static function normalizeRowValues(array $row): array
    {
        return array_map(static function ($value): string {
            if (is_int($value) || is_float($value)) {
                return (string) $value;
            }
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d H:i:s');
            }
            if (is_bool($value)) {
                return $value ? '1' : '0';
            }
            return (string) $value;
        }, $row);
    }

    public static function streamCsv(string $filename, array $headers, array $rows): void
    {
        $safeName = self::sanitizeFilename($filename);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $safeName . '"');
        header('X-Filename: ' . $safeName);

        $output = fopen('php://output', 'wb');
        if ($output === false) {
            echo 'sep=;' . "\n";
            echo implode(';', self::normalizeRowValues($headers)) . "\n";
            foreach ($rows as $row) {
                echo implode(';', self::normalizeRowValues($row)) . "\n";
            }
            exit;
        }

        fprintf($output, "\xEF\xBB\xBF");
        fputcsv($output, self::normalizeRowValues($headers), ';');
        foreach ($rows as $row) {
            fputcsv($output, self::normalizeRowValues($row), ';');
        }
        fclose($output);
        exit;
    }

    public static function streamPdf(string $filename, string $title, array $headers, array $rows): void
    {
        $safeName = self::sanitizeFilename($filename);
        $content = PdfExporter::fromTable($title, $headers, $rows);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $safeName . '"');
        header('Content-Length: ' . strlen($content));
        header('X-Filename: ' . $safeName);

        echo $content;
        exit;
    }
}
