<?php

namespace App;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use RuntimeException;

class QrService
{
    public static function buildContent(string $type, array $input): string
    {
        $type = strtolower(trim($type));

        if ($type === '' || $type === 'raw') {
            $type = 'custom';
        }

        switch ($type) {
            case 'url':
                $value = trim((string) ($input['url'] ?? $input['data'] ?? ''));
                if ($value === '') {
                    throw new InvalidArgumentException('Geçerli bir URL girin.');
                }
                return self::normaliseUrl($value);

            case 'text':
                $value = trim((string) ($input['text_content'] ?? $input['data'] ?? ''));
                if ($value === '') {
                    throw new InvalidArgumentException('Metin içeriği boş olamaz.');
                }
                return $value;

            case 'email':
                $email = trim((string) ($input['email_address'] ?? ''));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('Geçerli bir e-posta adresi girin.');
                }
                $subject = trim((string) ($input['email_subject'] ?? ''));
                $body = trim((string) ($input['email_body'] ?? ''));
                $query = [];
                if ($subject !== '') {
                    $query[] = 'subject=' . rawurlencode($subject);
                }
                if ($body !== '') {
                    $query[] = 'body=' . rawurlencode($body);
                }
                $suffix = $query ? '?' . implode('&', $query) : '';
                return 'mailto:' . $email . $suffix;

            case 'phone':
                $phone = preg_replace('/\s+/', '', (string) ($input['phone_number'] ?? ''));
                if ($phone === '') {
                    throw new InvalidArgumentException('Telefon numarası girin.');
                }
                return 'tel:' . $phone;

            case 'sms':
                $number = preg_replace('/\s+/', '', (string) ($input['sms_number'] ?? ''));
                if ($number === '') {
                    throw new InvalidArgumentException('SMS göndermek için telefon numarası zorunludur.');
                }
                $message = trim((string) ($input['sms_message'] ?? ''));
                $encoded = $message !== '' ? rawurlencode($message) : '';
                return 'SMSTO:' . $number . ':' . $encoded;

            case 'wifi':
                $ssid = trim((string) ($input['wifi_ssid'] ?? ''));
                if ($ssid === '') {
                    throw new InvalidArgumentException('Wi-Fi ağ adı gerekli.');
                }
                $encryption = strtoupper(trim((string) ($input['wifi_encryption'] ?? 'WPA')));
                if (!in_array($encryption, ['WPA', 'WEP', 'NOPASS'], true)) {
                    $encryption = 'WPA';
                }
                $password = trim((string) ($input['wifi_password'] ?? ''));
                if ($encryption !== 'NOPASS' && $password === '') {
                    throw new InvalidArgumentException('Şifre korumalı ağlar için parola gerekli.');
                }
                $hidden = !empty($input['wifi_hidden']) ? 'true' : 'false';
                $wifi = 'WIFI:T:' . $encryption;
                $wifi .= ';S:' . self::escapeWifi($ssid);
                if ($encryption !== 'NOPASS') {
                    $wifi .= ';P:' . self::escapeWifi($password);
                }
                $wifi .= ';H:' . $hidden . ';;';
                return $wifi;

            case 'location':
                $lat = trim((string) ($input['location_lat'] ?? ''));
                $lng = trim((string) ($input['location_lng'] ?? ''));
                if ($lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
                    throw new InvalidArgumentException('Geçerli bir enlem ve boylam girin.');
                }
                $label = trim((string) ($input['location_label'] ?? ''));
                $latF = number_format((float) $lat, 6, '.', '');
                $lngF = number_format((float) $lng, 6, '.', '');
                $geo = 'geo:' . $latF . ',' . $lngF;
                if ($label !== '') {
                    $geo .= '?q=' . rawurlencode($label);
                }
                return $geo;

            case 'event':
                $title = trim((string) ($input['event_title'] ?? ''));
                if ($title === '') {
                    throw new InvalidArgumentException('Etkinlik başlığı gerekli.');
                }
                $start = self::parseDateTime((string) ($input['event_start'] ?? ''));
                if (!$start) {
                    throw new InvalidArgumentException('Başlangıç tarihi geçersiz.');
                }
                $end = self::parseDateTime((string) ($input['event_end'] ?? '')) ?? $start;
                if ($end < $start) {
                    $end = $start;
                }
                $location = trim((string) ($input['event_location'] ?? ''));
                $description = trim((string) ($input['event_description'] ?? ''));
                $vevent = [
                    'BEGIN:VEVENT',
                    'SUMMARY:' . self::escapeEventText($title),
                    'DTSTART:' . $start->format('Ymd\THis'),
                    'DTEND:' . $end->format('Ymd\THis'),
                ];
                if ($location !== '') {
                    $vevent[] = 'LOCATION:' . self::escapeEventText($location);
                }
                if ($description !== '') {
                    $vevent[] = 'DESCRIPTION:' . self::escapeEventText($description);
                }
                $vevent[] = 'END:VEVENT';
                return implode("\n", $vevent);

            case 'facebook':
                $value = trim((string) ($input['facebook_value'] ?? ''));
                if ($value === '') {
                    throw new InvalidArgumentException('Facebook bağlantısı girin.');
                }
                return self::normaliseSocialUrl($value, 'https://facebook.com/');

            case 'instagram':
                $value = trim((string) ($input['instagram_value'] ?? ''));
                if ($value === '') {
                    throw new InvalidArgumentException('Instagram bağlantısı girin.');
                }
                return self::normaliseSocialUrl($value, 'https://instagram.com/');

            case 'twitter':
                $value = trim((string) ($input['twitter_value'] ?? ''));
                if ($value === '') {
                    throw new InvalidArgumentException('Twitter bağlantısı girin.');
                }
                return self::normaliseSocialUrl($value, 'https://twitter.com/');

            case 'youtube':
                $value = trim((string) ($input['youtube_value'] ?? ''));
                if ($value === '') {
                    throw new InvalidArgumentException('YouTube bağlantısı girin.');
                }
                return self::normaliseSocialUrl($value, 'https://youtube.com/');

            case 'whatsapp':
                $number = preg_replace('/\D+/', '', (string) ($input['whatsapp_number'] ?? ''));
                if ($number === '') {
                    throw new InvalidArgumentException('WhatsApp numarası girin.');
                }
                $message = trim((string) ($input['whatsapp_message'] ?? ''));
                $url = 'https://wa.me/' . $number;
                if ($message !== '') {
                    $url .= '?text=' . rawurlencode($message);
                }
                return $url;

            case 'bitcoin':
                $address = trim((string) ($input['bitcoin_address'] ?? ''));
                if ($address === '') {
                    throw new InvalidArgumentException('Bitcoin adresi girin.');
                }
                $amount = trim((string) ($input['bitcoin_amount'] ?? ''));
                $suffix = $amount !== '' ? '?amount=' . rawurlencode($amount) : '';
                return 'bitcoin:' . $address . $suffix;

            case 'ethereum':
                $address = trim((string) ($input['ethereum_address'] ?? ''));
                if ($address === '') {
                    throw new InvalidArgumentException('Ethereum adresi girin.');
                }
                $amount = trim((string) ($input['ethereum_amount'] ?? ''));
                $suffix = $amount !== '' ? '?value=' . rawurlencode($amount) : '';
                return 'ethereum:' . $address . $suffix;

            case 'custom':
                $value = trim((string) ($input['custom_data'] ?? $input['data'] ?? ''));
                if ($value === '') {
                    throw new InvalidArgumentException('İçerik boş olamaz.');
                }
                return $value;

            default:
                $value = trim((string) ($input['data'] ?? ''));
                if ($value === '') {
                    throw new InvalidArgumentException('Geçerli bir içerik sağlayın.');
                }
                return $value;
        }
    }

    public static function generate(string $data, array $options = [], ?string $logoPath = null, array $formats = ['png']): array
    {
        $width = max(128, (int) ($options['width'] ?? 512));
        $height = max(128, (int) ($options['height'] ?? $width));
        $scale = max(4, (int) ($options['scale'] ?? 10));
        $color = $options['color'] ?? '#0d6efd';
        $background = $options['background'] ?? '#0b132b';
        $transparentBackground = !empty($options['background_transparent'])
            || (is_string($background) && strtolower($background) === 'transparent');
        if ($transparentBackground) {
            $background = null;
        }

        $qrOptions = new QROptions([
            'version' => $options['version'] ?? 5,
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_H,
            'scale' => $scale,
            'imageBase64' => false,
            'imageTransparent' => false,
            'imageTransparentTransparent' => false,
        ]);

        $qr = new QRCode($qrOptions);
        $imageData = $qr->render($data);
        $qrImage = imagecreatefromstring($imageData);
        if (!$qrImage) {
            throw new RuntimeException('QR kodu oluşturulamadı.');
        }

        imagealphablending($qrImage, false);
        imagesavealpha($qrImage, true);
        $qrImage = self::recolor($qrImage, $color, $background, $transparentBackground);

        $qrImage = self::resize($qrImage, $width, $height, $background, $transparentBackground);

        if ($logoPath) {
            self::overlayLogo($qrImage, $logoPath);
        }

        $results = [];

        ob_start();
        imagepng($qrImage);
        $pngBinary = ob_get_clean();

        if (in_array('png', $formats, true)) {
            $results['png'] = $pngBinary;
        }

        if (in_array('jpg', $formats, true)) {
            $jpg = imagecreatetruecolor($width, $height);
            $white = imagecolorallocate($jpg, 255, 255, 255);
            imagefilledrectangle($jpg, 0, 0, $width, $height, $white);
            imagecopy($jpg, $qrImage, 0, 0, 0, 0, $width, $height);
            ob_start();
            imagejpeg($jpg, null, 92);
            $results['jpg'] = ob_get_clean();
            imagedestroy($jpg);
        }

        if (in_array('svg', $formats, true)) {
            $encoded = base64_encode($pngBinary);
            $results['svg'] = sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%2$d" viewBox="0 0 %1$d %2$d"><image href="data:image/png;base64,%3$s" width="%1$d" height="%2$d" /></svg>',
                $width,
                $height,
                $encoded
            );
        }

        imagedestroy($qrImage);

        return $results;
    }

    private static function recolor($image, string $foregroundHex, ?string $backgroundHex, bool $transparent)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $result = imagecreatetruecolor($width, $height);
        imagealphablending($result, false);
        imagesavealpha($result, true);

        [$fr, $fg, $fb] = self::hexToRgb($foregroundHex);
        $fgColor = imagecolorallocatealpha($result, $fr, $fg, $fb, 0);
        if ($transparent) {
            $bgColor = imagecolorallocatealpha($result, 0, 0, 0, 127);
        } else {
            [$br, $bg, $bb] = self::hexToRgb($backgroundHex ?? '#0b132b');
            $bgColor = imagecolorallocatealpha($result, $br, $bg, $bb, 0);
        }

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $colors = imagecolorsforindex($image, $rgb);
                $isDark = ($colors['red'] + $colors['green'] + $colors['blue']) < (255 * 3 / 2);
                imagesetpixel($result, $x, $y, $isDark ? $fgColor : $bgColor);
            }
        }

        imagedestroy($image);
        return $result;
    }

    private static function resize($image, int $width, int $height, ?string $backgroundHex, bool $transparent)
    {
        $currentWidth = imagesx($image);
        $currentHeight = imagesy($image);

        if ($currentWidth === $width && $currentHeight === $height) {
            return $image;
        }

        $resized = imagecreatetruecolor($width, $height);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        if ($transparent) {
            $bgColor = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        } else {
            [$br, $bg, $bb] = self::hexToRgb($backgroundHex ?? '#0b132b');
            $bgColor = imagecolorallocatealpha($resized, $br, $bg, $bb, 0);
        }
        imagefilledrectangle($resized, 0, 0, $width, $height, $bgColor);

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, $currentWidth, $currentHeight);
        imagedestroy($image);

        return $resized;
    }

    private static function overlayLogo($qrImage, string $logoPath): void
    {
        $logoContent = @file_get_contents($logoPath);
        if ($logoContent === false) {
            return;
        }

        $logoImage = @imagecreatefromstring($logoContent);
        if (!$logoImage) {
            return;
        }

        imagealphablending($qrImage, true);
        imagesavealpha($qrImage, true);
        imagealphablending($logoImage, true);
        imagesavealpha($logoImage, true);

        $qrWidth = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);
        $logoWidth = imagesx($logoImage);
        $logoHeight = imagesy($logoImage);

        $desiredWidth = max(20, (int) ($qrWidth * 0.25));
        $scale = $desiredWidth / $logoWidth;
        $desiredHeight = max(20, (int) ($logoHeight * $scale));

        $resizedLogo = imagecreatetruecolor($desiredWidth, $desiredHeight);
        imagealphablending($resizedLogo, false);
        imagesavealpha($resizedLogo, true);
        $transparent = imagecolorallocatealpha($resizedLogo, 0, 0, 0, 127);
        imagefill($resizedLogo, 0, 0, $transparent);
        imagecopyresampled($resizedLogo, $logoImage, 0, 0, 0, 0, $desiredWidth, $desiredHeight, $logoWidth, $logoHeight);

        $destX = (int) (($qrWidth - $desiredWidth) / 2);
        $destY = (int) (($qrHeight - $desiredHeight) / 2);

        imagecopy($qrImage, $resizedLogo, $destX, $destY, 0, 0, $desiredWidth, $desiredHeight);

        imagedestroy($logoImage);
        imagedestroy($resizedLogo);
    }

    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $int = hexdec($hex);
        return [($int >> 16) & 255, ($int >> 8) & 255, $int & 255];
    }

    private static function normaliseUrl(string $value, string $defaultScheme = 'https://'): string
    {
        if ($value === '') {
            return $value;
        }

        if (!preg_match('~^[a-z][a-z0-9+.-]*://~i', $value)) {
            return rtrim($defaultScheme, '/') . '/' . ltrim($value, '/');
        }

        return $value;
    }

    private static function normaliseSocialUrl(string $value, string $prefix): string
    {
        $value = trim($value);
        if ($value === '') {
            return rtrim($prefix, '/');
        }

        if (preg_match('~^[a-z][a-z0-9+.-]*://~i', $value)) {
            return $value;
        }

        $sanitised = ltrim($value, '@/');
        if (str_contains($sanitised, '.')) {
            return 'https://' . ltrim($sanitised, '/');
        }

        return rtrim($prefix, '/') . '/' . ltrim($sanitised, '/');
    }

    private static function escapeWifi(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', ':', '"'],
            ['\\\\', '\\;', '\\,', '\\:', '\\"'],
            $value
        );
    }

    private static function parseDateTime(string $value): ?DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception $e) {
            return null;
        }
    }

    private static function escapeEventText(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/[\n]+/', '\\n', $value);
        return str_replace(['\\', ';', ','], ['\\\\', '\\;', '\\,'], $value ?? '');
    }
}
