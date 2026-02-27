<?php
require_once __DIR__ . '/_init.php';

$id = (int) ($_POST['category_id'] ?? 0);
$title = trim((string) ($_POST['title'] ?? ''));
$desc = trim((string) ($_POST['description'] ?? ''));
$divisor = (float)($_POST['divisor'] ?? 3000);
$fuelRate = (float)($_POST['fuel_rate'] ?? 0);
$codFee = (float)($_POST['cod_fee'] ?? 0);
$minPrice = (float)($_POST['min_price'] ?? 0);
$extraPerKg = (float)($_POST['extra_per_kg'] ?? 12);
if ($title === '') json_response(false, 'Kategori başlığı gerekli');

$params=['t'=>$title,'d'=>$desc,'dv'=>$divisor,'fr'=>$fuelRate,'cf'=>$codFee,'mp'=>$minPrice,'ek'=>$extraPerKg];
if ($id > 0) {
    $params['id']=$id;
    db()->prepare('UPDATE price_categories SET title=:t, description=:d, divisor=:dv, fuel_rate=:fr, cod_fee=:cf, min_price=:mp, extra_per_kg=:ek WHERE id=:id')->execute($params);
    json_response(true, 'Kategori güncellendi');
}

db()->prepare('INSERT INTO price_categories(title,description,divisor,fuel_rate,cod_fee,min_price,extra_per_kg) VALUES(:t,:d,:dv,:fr,:cf,:mp,:ek)')->execute($params);
json_response(true, 'Kategori kaydedildi');
