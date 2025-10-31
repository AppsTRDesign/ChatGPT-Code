<?php

require_once __DIR__ . '/../bootstrap.php';

use Helpers\Currency;
use Core\Response;

$from = $_GET['from'] ?? 'TRY';
$to = $_GET['to'] ?? 'TRY';
$amount = (float) ($_GET['amount'] ?? 1);

$primary = Currency::currencyConverter($from, $to, $amount);
$secondary = Currency::currencyConverter2($from, $to, $amount);

Response::json([
    'primary' => $primary,
    'secondary' => $secondary,
]);
