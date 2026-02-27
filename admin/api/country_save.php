<?php
require_once __DIR__ . '/_init.php';

$id = (int) ($_POST['country_id'] ?? 0);
$name = trim((string) ($_POST['name'] ?? ''));
$currency = strtoupper(trim((string) ($_POST['currency_code'] ?? 'USD')));
if ($name === '') {
    json_response(false, 'Ülke adı gerekli');
}

if ($id > 0) {
    db()->prepare('UPDATE countries SET name=:n, currency_code=:c WHERE id=:id')->execute(['n' => $name, 'c' => $currency, 'id' => $id]);
    json_response(true, 'Ülke güncellendi');
}

db()->prepare('INSERT INTO countries(name,currency_code,is_active) VALUES(:n,:c,1)')->execute(['n' => $name, 'c' => $currency]);
json_response(true, 'Ülke kaydedildi');
