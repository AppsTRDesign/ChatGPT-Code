<?php

require_once __DIR__ . '/../bootstrap.php';

use Core\Database;
use Core\Response;

$restaurantId = 1;
$db = Database::connection();

$sql = "SELECT id, name, status, qr_code_url FROM tables WHERE restaurant_id = :restaurant ORDER BY name";
$statement = $db->prepare($sql);
$statement->execute([':restaurant' => $restaurantId]);

$tables = array_map(static function ($table) {
    $statusLabels = [
        'available' => 'Boş',
        'occupied' => 'Dolu',
    ];
    $table['status_label'] = $statusLabels[$table['status']] ?? $table['status'];
    $table['qr_url'] = $table['qr_code_url'] ?: BASE_URL . '/menu.php?table=' . $table['id'];
    return $table;
}, $statement->fetchAll() ?: []);

Response::json([
    'tables' => $tables,
]);
