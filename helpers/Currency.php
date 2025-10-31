<?php

namespace Helpers;

class Currency
{
    public static function currencyConverter(string $fromCurrency, string $toCurrency, float $amount): string
    {
        $url = self::fetch("https://www.google.com/search?hl=en&q=" . $fromCurrency . "+to+" . $toCurrency);
        $rate = self::ara('data-exchange-rate="', '"', $url);
        $convertedAmount = $amount * ($rate[0] ?? 1);
        return number_format($convertedAmount, 4, '.', ',');
    }

    public static function currencyConverter2(string $fromCurrency, string $toCurrency, float $amount): string
    {
        $url = self::fetch("https://www.forbes.com/advisor/money-transfer/currency-converter/" . mb_strtolower($fromCurrency) . "-" . mb_strtolower($toCurrency) . "/");
        $rate = self::ara('"latestRate":"', '"', $url);
        $convertedAmount = $amount * ($rate[0] ?? 1);
        return number_format($convertedAmount, 4, '.', ',');
    }

    public static function ara(string $bas, string $son, string $yazi): array
    {
        @preg_match_all('/' . preg_quote($bas, '/') . '(.*?)' . preg_quote($son, '/') . '/i', $yazi, $m);
        return @$m[1];
    }

    public static function fetch(string $url): string
    {
        $curlDefaults = [
            CURLOPT_HEADER => 0,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_AUTOREFERER => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_VERBOSE => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ];
        $ch = curl_init();
        curl_setopt_array($ch, $curlDefaults);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.128 Safari/537.36');
        curl_setopt($ch, CURLOPT_URL, $url);
        $html = curl_exec($ch);
        curl_close($ch);
        return $html ?: '';
    }
}
