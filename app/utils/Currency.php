<?php

class Currency
{
    public static function convert(string $fromCurrency, string $toCurrency, float $amount = 1.0): array
    {
        $fromCurrency = strtoupper(trim($fromCurrency));
        $toCurrency = strtoupper(trim($toCurrency));
        if ($fromCurrency === $toCurrency) {
            return [
                'rate' => 1.0,
                'amount' => round($amount, 4),
            ];
        }

        $rate = self::googleRate($fromCurrency, $toCurrency);
        if (!$rate) {
            $rate = self::forbesRate($fromCurrency, $toCurrency);
        }
        if (!$rate) {
            throw new RuntimeException('Kur bilgisi alınamadı');
        }

        $converted = $amount * $rate;
        return [
            'rate' => round($rate, 6),
            'amount' => round($converted, 4),
        ];
    }

    private static function googleRate(string $fromCurrency, string $toCurrency): ?float
    {
        $url = sprintf('https://www.google.com/search?hl=en&q=%s+to+%s', urlencode($fromCurrency), urlencode($toCurrency));
        $html = self::fetch($url);
        $match = self::extract('data-exchange-rate="', '"', $html);
        if (!empty($match[0]) && is_numeric($match[0])) {
            return (float)$match[0];
        }
        return null;
    }

    private static function forbesRate(string $fromCurrency, string $toCurrency): ?float
    {
        $url = sprintf('https://www.forbes.com/advisor/money-transfer/currency-converter/%s-%s/', strtolower($fromCurrency), strtolower($toCurrency));
        $html = self::fetch($url);
        $match = self::extract('"latestRate":"', '"', $html);
        if (!empty($match[0]) && is_numeric($match[0])) {
            return (float)$match[0];
        }
        return null;
    }

    private static function fetch(string $url): string
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
        $handle = curl_init();
        curl_setopt_array($handle, $curlDefaults);
        curl_setopt($handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.128 Safari/537.36');
        curl_setopt($handle, CURLOPT_URL, $url);
        $html = curl_exec($handle);
        if ($html === false) {
            $error = curl_error($handle);
            curl_close($handle);
            throw new RuntimeException('Kur bilgisi alınamadı: ' . $error);
        }
        curl_close($handle);
        return (string)$html;
    }

    private static function extract(string $start, string $end, string $text): array
    {
        @preg_match_all('/' . preg_quote($start, '/') . '(.*?)' . preg_quote($end, '/') . '/i', $text, $matches);
        return $matches[1] ?? [];
    }
}
