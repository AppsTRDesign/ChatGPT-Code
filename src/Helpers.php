<?php

namespace App;

use DateTime;

class Helpers
{
    public static function log(string $user, string $action): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO activity_logs (user_name, action) VALUES (:user_name, :action)');
        $stmt->execute([
            ':user_name' => $user,
            ':action' => $action,
        ]);
    }

    public static function formatCurrency(float $amount, string $currency = 'TRY'): string
    {
        return number_format($amount, 2, ',', '.') . ' ' . $currency;
    }

    public static function parseDate(?string $date): ?string
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        $dt = new DateTime($date);
        return $dt->format('Y-m-d');
    }
}
