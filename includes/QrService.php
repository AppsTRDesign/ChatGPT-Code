<?php

namespace App;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrService
{
    public static function generate(string $data, array $options = [], ?string $logoPath = null): string
    {
        $defaults = [
            'version' => 5,
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_H,
            'scale' => $options['scale'] ?? 6,
            'imageTransparent' => false,
            'imageBase64' => false,
            'imageTransparentTransparent' => false,
        ];

        $qrOptions = new QROptions($defaults);

        $qr = new QRCode($qrOptions);
        $imageData = $qr->render($data);

        $qrImage = imagecreatefromstring($imageData);
        if (!$qrImage) {
            return $imageData;
        }

        $color = $options['color'] ?? '#0d6efd';
        $background = $options['background'] ?? '#0b132b';
        $qrImage = self::recolor($qrImage, $color, $background);

        if (!$logoPath) {
            ob_start();
            imagepng($qrImage);
            $colored = ob_get_clean();
            imagedestroy($qrImage);
            return $colored;
        }

        $logoContent = file_get_contents($logoPath);
        if ($logoContent === false) {
            ob_start();
            imagepng($qrImage);
            $colored = ob_get_clean();
            imagedestroy($qrImage);
            return $colored;
        }

        $logoImage = imagecreatefromstring($logoContent);
        if (!$logoImage) {
            ob_start();
            imagepng($qrImage);
            $colored = ob_get_clean();
            imagedestroy($qrImage);
            return $colored;
        }

        $qrWidth = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);
        $logoWidth = imagesx($logoImage);
        $logoHeight = imagesy($logoImage);

        $desiredWidth = (int) ($qrWidth * 0.25);
        $scale = $desiredWidth / $logoWidth;
        $desiredHeight = (int) ($logoHeight * $scale);

        $resizedLogo = imagecreatetruecolor($desiredWidth, $desiredHeight);
        imagealphablending($resizedLogo, false);
        imagesavealpha($resizedLogo, true);
        imagecopyresampled($resizedLogo, $logoImage, 0, 0, 0, 0, $desiredWidth, $desiredHeight, $logoWidth, $logoHeight);

        $destX = (int) (($qrWidth - $desiredWidth) / 2);
        $destY = (int) (($qrHeight - $desiredHeight) / 2);

        imagecopy($qrImage, $resizedLogo, $destX, $destY, 0, 0, $desiredWidth, $desiredHeight);

        ob_start();
        imagepng($qrImage);
        $finalImage = ob_get_clean();

        imagedestroy($qrImage);
        imagedestroy($logoImage);
        imagedestroy($resizedLogo);

        return $finalImage;
    }

    private static function recolor($image, string $foregroundHex, string $backgroundHex)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $result = imagecreatetruecolor($width, $height);
        imagealphablending($result, false);
        imagesavealpha($result, true);

        [$fr, $fg, $fb] = self::hexToRgb($foregroundHex);
        [$br, $bg, $bb] = self::hexToRgb($backgroundHex);

        $fgColor = imagecolorallocatealpha($result, $fr, $fg, $fb, 0);
        $bgColor = imagecolorallocatealpha($result, $br, $bg, $bb, 0);

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
