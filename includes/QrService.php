<?php

namespace App;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use RuntimeException;

class QrService
{
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
}
