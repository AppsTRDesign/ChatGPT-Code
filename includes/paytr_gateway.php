<?php

function render_paytr_iframe(array $order): string
{
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($autoload)) {
        return '<p>PayTR kütüphanesi bulunamadı. Lütfen composer kurulumu yapın.</p>';
    }

    require_once $autoload;

    return '<p>PayTR ödeme formu burada yüklenecek.</p>';
}
