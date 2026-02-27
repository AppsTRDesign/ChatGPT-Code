<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(false, 'Invalid request');
if (!verify_csrf($_POST['csrf'] ?? null)) json_response(false, 'CSRF mismatch');

function priceByWeight(float $kg, array $table, float $extraPerKg): float
{
    ksort($table);
    foreach ($table as $limitKg => $price) {
        if ($kg <= (float)$limitKg) return (float)$price;
    }
    $lastLimit = (float)array_key_last($table);
    $lastPrice = (float)$table[$lastLimit];
    $extraKg = max(0, $kg - $lastLimit);
    return $lastPrice + ($extraKg * $extraPerKg);
}

$lang = current_lang();
$countryId = (int) ($_POST['country_id'] ?? 0);
$categoryId = (int) ($_POST['category_id'] ?? 0);
$transportModeId = (int)($_POST['transport_mode_id'] ?? 0);
$en = (float)($_POST['en'] ?? 0);
$boy = (float)($_POST['boy'] ?? 0);
$yuk = (float)($_POST['yukseklik'] ?? 0);
$kg = (float)($_POST['kilo'] ?? 0);

if ($en <= 0 || $boy <= 0 || $yuk <= 0 || $kg <= 0) json_response(false, 'Ölçüler ve kilo 0’dan büyük olmalı.');

$stmt = db()->prepare('SELECT c.currency_code, c.currency_symbol, COALESCE(ctry_t.name, c.name) country_name, COALESCE(cat_t.title, cat.title) category_title, COALESCE(cat_t.description, cat.description) description, tm.mode_key, tm.multiplier AS mode_multiplier, cat.divisor, cat.fuel_rate, cat.cod_fee, cat.min_price, cat.extra_per_kg FROM price_configs pc JOIN countries c ON c.id = pc.country_id JOIN price_categories cat ON cat.id = pc.category_id JOIN transport_modes tm ON tm.id = pc.transport_mode_id LEFT JOIN country_translations ctry_t ON ctry_t.country_id = c.id AND ctry_t.lang_code = :lang LEFT JOIN price_category_translations cat_t ON cat_t.category_id = cat.id AND cat_t.lang_code = :lang2 WHERE pc.country_id = :country AND pc.category_id = :category AND pc.transport_mode_id = :mode LIMIT 1');
$stmt->execute(['country' => $countryId, 'category' => $categoryId, 'mode'=>$transportModeId, 'lang' => $lang, 'lang2' => $lang]);
$row = $stmt->fetch();
if (!$row) json_response(false, t('front', 'pricing_not_found'));

$wpStmt = db()->prepare('SELECT weight_limit, price_amount FROM transport_mode_weight_prices WHERE transport_mode_id=:m ORDER BY weight_limit');
$wpStmt->execute(['m'=>$transportModeId]);
$weights = $wpStmt->fetchAll();
if (!$weights) json_response(false, 'Taşıma seçeneğine ait kilo fiyat tablosu bulunamadı.');
$priceTable=[];
foreach($weights as $w){ $priceTable[(float)$w['weight_limit']] = (float)$w['price_amount']; }

$volumeCm3 = $en * $boy * $yuk;
$volumetricKg = $volumeCm3 / (float)$row['divisor'];
$chargeable = (float)ceil(max($kg, $volumetricKg));
$baseRoad = priceByWeight($chargeable, $priceTable, (float)$row['extra_per_kg']);
$price = $baseRoad * (float)$row['mode_multiplier'];
$price += $price * (float)$row['fuel_rate'];
$price += (float)$row['cod_fee'];
$price = max($price, (float)$row['min_price']);

json_response(true, t('front', 'pricing_found'), [
    'country_name' => $row['country_name'],
    'category_title' => $row['category_title'],
    'description' => $row['description'],
    'currency_code' => 'USD',
    'local_currency_code' => $row['currency_code'],
    'local_currency_symbol' => $row['currency_symbol'],
    'ucret_usd' => round($price, 2),
    'ucret_kilo' => $chargeable,
    'hacimsel_kilo' => round($volumetricKg, 3),
    'mode' => $row['mode_key'],
]);
