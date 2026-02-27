<?php
require_once __DIR__ . '/_init.php';

$title = trim((string) ($_POST['title'] ?? ''));
$desc = trim((string) ($_POST['description'] ?? ''));
$divisor = (float)($_POST['divisor'] ?? 3000);
$fuelRate = (float)($_POST['fuel_rate'] ?? 0);
$codFee = (float)($_POST['cod_fee'] ?? 0);
$minPrice = (float)($_POST['min_price'] ?? 0);
$extraPerKg = (float)($_POST['extra_per_kg'] ?? 12);
if ($title === '') json_response(false, 'Kategori başlığı gerekli');

$params=['t'=>$title,'d'=>$desc,'dv'=>$divisor,'fr'=>$fuelRate,'cf'=>$codFee,'mp'=>$minPrice,'ek'=>$extraPerKg];
db()->prepare('INSERT INTO price_categories(title,description,divisor,fuel_rate,cod_fee,min_price,extra_per_kg) VALUES(:t,:d,:dv,:fr,:cf,:mp,:ek)')->execute($params);
json_response(true, 'Kategori kaydedildi');
