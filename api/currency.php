<?php

require_once __DIR__ . '/../bootstrap.php';

use Helpers\Currency;
use Core\Response;

$from = $_GET['from'] ?? 'TRY';
$to = $_GET['to'] ?? 'TRY';
$amount = (float) ($_GET['amount'] ?? 1);

$primary = Currency::currencyConverter($from, $to, $amount);
$secondary = Currency::currencyConverter2($from, $to, $amount);

$normalize = static function ($value): float {
    if ($value === null) {
        return 0.0;
    }
    $plain = str_replace(',', '', (string) $value);
    $number = (float) $plain;
    return is_finite($number) ? $number : 0.0;
};

$primaryAmount = $normalize($primary);
$secondaryAmount = $normalize($secondary);
$ratePrimary = ($amount > 0) ? $primaryAmount / $amount : 0.0;
$rateSecondary = ($amount > 0) ? $secondaryAmount / $amount : 0.0;
$effectiveRate = $ratePrimary > 0 ? $ratePrimary : $rateSecondary;
$effectiveAmount = $primaryAmount > 0 ? $primaryAmount : $secondaryAmount;

Response::json([
    'primary' => $primary,
    'secondary' => $secondary,
    'rate' => $effectiveRate,
    'amount' => $effectiveAmount,
]);
