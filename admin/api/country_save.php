<?php
require_once __DIR__ . '/_init.php';

$id = (int) ($_POST['country_id'] ?? 0);
$name = trim((string) ($_POST['name'] ?? ''));
$currency = strtoupper(trim((string) ($_POST['currency_code'] ?? 'USD')));
$symbol = trim((string) ($_POST['currency_symbol'] ?? '$')) ?: '$';
$isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
if ($name === '') json_response(false, 'Ülke adı gerekli');

if ($id > 0) {
    db()->prepare('UPDATE countries SET name=:n, currency_code=:c, currency_symbol=:s, is_active=:a WHERE id=:id')
        ->execute(['n' => $name, 'c' => $currency, 's'=>$symbol, 'a'=>$isActive, 'id' => $id]);
    json_response(true, 'Ülke güncellendi');
}

db()->prepare('INSERT INTO countries(name,currency_code,currency_symbol,is_active) VALUES(:n,:c,:s,:a)')
    ->execute(['n' => $name, 'c' => $currency, 's'=>$symbol, 'a'=>$isActive]);
json_response(true, 'Ülke kaydedildi');
