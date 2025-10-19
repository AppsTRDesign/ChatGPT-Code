<?php
require __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek yöntemi']);
    exit;
}

set_time_limit(0);
ensure_directories();

$jobId = $_POST['job_id'] ?? '';
$jobId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $jobId);
if (!$jobId) {
    echo json_encode(['success' => false, 'message' => 'İş ID eksik']);
    exit;
}

$type = $_POST['type'] ?? 'general';
$optionsJson = $_POST['options'] ?? '{}';
$options = json_decode($optionsJson, true);
if (!is_array($options)) {
    $options = [];
}

write_job($jobId, [
    'status' => 'uploading',
    'upload_percent' => 0,
    'convert_percent' => 0,
    'upload_text' => 'Yükleme başlatıldı'
]);

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    update_job($jobId, [
        'status' => 'error',
        'message' => 'Dosya yüklenemedi.'
    ]);
    echo json_encode(['success' => false, 'message' => 'Dosya yüklenemedi.']);
    exit;
}

$fileInfo = $_FILES['file'];
if ($fileInfo['size'] > 2048 * 1024 * 1024) {
    update_job($jobId, [
        'status' => 'error',
        'message' => '2048 MB sınırı aşıldı.'
    ]);
    echo json_encode(['success' => false, 'message' => '2048 MB sınırı aşıldı.']);
    exit;
}

$originalName = sanitize_filename($fileInfo['name']);
$tempPath = $fileInfo['tmp_name'];
$uniqueName = uniqid('upload_', true) . '_' . $originalName;
$storedPath = NS_UPLOAD_PATH . '/' . $uniqueName;

if (!move_uploaded_file($tempPath, $storedPath)) {
    update_job($jobId, [
        'status' => 'error',
        'message' => 'Dosya sunucuya taşınamadı.'
    ]);
    echo json_encode(['success' => false, 'message' => 'Dosya sunucuya taşınamadı.']);
    exit;
}

update_job($jobId, [
    'status' => 'processing',
    'upload_percent' => 100,
    'upload_text' => 'Yükleme tamamlandı'
]);

try {
    switch ($type) {
        case 'audio':
            $output = convert_audio($storedPath, $originalName, $options, $jobId);
            break;
        case 'video':
            $output = convert_video($storedPath, $originalName, $options, $jobId);
            break;
        case 'image':
            $output = convert_image($storedPath, $originalName, $options, $jobId);
            break;
        default:
            throw new RuntimeException('Desteklenmeyen dönüştürme türü.');
    }

    if (!$output || !file_exists($output)) {
        throw new RuntimeException('Çıktı oluşturulamadı.');
    }

    $downloadUrl = '/download.php?token=' . urlencode(build_download_token($output));

    update_job($jobId, [
        'status' => 'completed',
        'convert_percent' => 100,
        'convert_text' => 'Dönüştürme tamamlandı.',
        'download_file' => basename($output),
        'download_url' => $downloadUrl
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Dönüştürme tamamlandı.',
        'download_url' => $downloadUrl
    ]);
} catch (Throwable $e) {
    update_job($jobId, [
        'status' => 'error',
        'message' => $e->getMessage(),
        'convert_text' => $e->getMessage()
    ]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function convert_audio(string $inputPath, string $originalName, array $options, string $jobId): string {
    $format = $options['audio_format'] ?? 'mp3';
    $bitrate = (int)($options['audio_bitrate'] ?? 192);
    $samplerate = (int)($options['audio_samplerate'] ?? 44100);
    $channels = (int)($options['audio_channels'] ?? 2);
    $normalize = $options['audio_normalize'] ?? 'none';

    $outputName = pathinfo($originalName, PATHINFO_FILENAME) . '.' . $format;
    $outputPath = NS_OUTPUT_PATH . '/' . uniqid('audio_', true) . '_' . sanitize_filename($outputName);

    $filters = [];
    if ($normalize === 'ebu_r128') {
        $filters[] = 'loudnorm=I=-16:TP=-1.5:LRA=11';
    } elseif ($normalize === 'peak') {
        $filters[] = 'dynaudnorm=f=75:g=15';
    }

    $filterParam = $filters ? ' -af ' . escapeshellarg(implode(',', $filters)) : '';

    $cmd = sprintf(
        'ffmpeg -y -i %s -vn -ar %d -ac %d -b:a %dk%s -progress pipe:1 -nostats %s',
        escapeshellarg($inputPath),
        $samplerate,
        $channels,
        max($bitrate, 32),
        $filterParam,
        escapeshellarg($outputPath)
    );

    $duration = ffprobe_duration($inputPath);
    $exit = run_ffmpeg_with_progress($cmd, $jobId, $duration);
    if ($exit !== 0) {
        throw new RuntimeException('Ses dönüştürme başarısız oldu.');
    }
    return $outputPath;
}

function convert_video(string $inputPath, string $originalName, array $options, string $jobId): string {
    $format = $options['video_format'] ?? 'mp4';
    $resolution = $options['video_resolution'] ?? '1920x1080';
    $fps = (int)($options['video_fps'] ?? 30);
    $videoBitrate = (int)($options['video_bitrate'] ?? 8000);
    $audioBitrate = (int)($options['audio_bitrate'] ?? 192);
    $videoCodec = $options['video_codec'] ?? 'libx264';
    $audioCodec = $options['audio_codec'] ?? 'aac';
    $extra = trim($options['video_extra'] ?? '');

    if (!preg_match('/^(\d+)x(\d+)$/', $resolution, $matches)) {
        $resolution = '1920x1080';
        $matches = [0, 1920, 1080];
    }
    [$full, $width, $height] = $matches;

    $outputName = pathinfo($originalName, PATHINFO_FILENAME) . '.' . $format;
    $outputPath = NS_OUTPUT_PATH . '/' . uniqid('video_', true) . '_' . sanitize_filename($outputName);

    $scaleFilter = sprintf('scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2', $width, $height, $width, $height);
    $filters = ['format=yuv420p', $scaleFilter];

    $filterParam = ' -vf ' . escapeshellarg(implode(',', $filters));
    $extraParams = $extra ? ' ' . $extra : '';

    $audioParams = $audioCodec === 'none' ? '-an' : sprintf('-c:a %s -b:a %dk', escapeshellarg($audioCodec), max($audioBitrate, 64));

    $cmd = sprintf(
        'ffmpeg -y -i %s -c:v %s -b:v %dk -r %d%s %s %s -progress pipe:1 -nostats %s',
        escapeshellarg($inputPath),
        escapeshellarg($videoCodec),
        max($videoBitrate, 500),
        max($fps, 10),
        $filterParam,
        $audioParams,
        $extraParams,
        escapeshellarg($outputPath)
    );

    $duration = ffprobe_duration($inputPath);
    $exit = run_ffmpeg_with_progress($cmd, $jobId, $duration);
    if ($exit !== 0) {
        throw new RuntimeException('Video dönüştürme başarısız oldu.');
    }
    return $outputPath;
}

function convert_image(string $inputPath, string $originalName, array $options, string $jobId): string {
    $format = strtolower($options['image_format'] ?? 'webp');
    $width = (int)($options['image_width'] ?? 1920);
    $height = (int)($options['image_height'] ?? 1080);
    $quality = (int)($options['image_quality'] ?? 85);
    $keepAspect = ($options['image_keep_aspect'] ?? 'yes') === 'yes';
    $background = $options['image_background'] ?? '#000000';

    $outputName = pathinfo($originalName, PATHINFO_FILENAME) . '.' . $format;
    $outputPath = NS_OUTPUT_PATH . '/' . uniqid('image_', true) . '_' . sanitize_filename($outputName);

    if (!extension_loaded('gd')) {
        throw new RuntimeException('Sunucuda GD eklentisi bulunamadı.');
    }

    $imageData = file_get_contents($inputPath);
    if ($imageData === false) {
        throw new RuntimeException('Görsel okunamadı.');
    }

    $source = imagecreatefromstring($imageData);
    if (!$source) {
        throw new RuntimeException('Görsel çözümlenemedi.');
    }

    $srcWidth = imagesx($source);
    $srcHeight = imagesy($source);

    $targetWidth = $width;
    $targetHeight = $height;

    if ($keepAspect) {
        $ratio = min($width / $srcWidth, $height / $srcHeight);
        $targetWidth = (int)($srcWidth * $ratio);
        $targetHeight = (int)($srcHeight * $ratio);
    }

    $canvas = imagecreatetruecolor($width, $height);

    if (in_array($format, ['png', 'webp', 'gif'], true)) {
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
    } else {
        [$r, $g, $b] = hex_to_rgb($background);
        $bg = imagecolorallocate($canvas, $r, $g, $b);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $bg);
    }

    $dstX = (int)(($width - $targetWidth) / 2);
    $dstY = (int)(($height - $targetHeight) / 2);

    imagecopyresampled($canvas, $source, $dstX, $dstY, 0, 0, $targetWidth, $targetHeight, $srcWidth, $srcHeight);

    switch ($format) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($canvas, $outputPath, max(min($quality, 100), 10));
            break;
        case 'png':
            $pngQuality = (int)round((100 - max(min($quality, 100), 10)) / 10);
            imagepng($canvas, $outputPath, $pngQuality);
            break;
        case 'gif':
            imagegif($canvas, $outputPath);
            break;
        default:
            imagewebp($canvas, $outputPath, max(min($quality, 100), 10));
            break;
    }

    imagedestroy($canvas);
    imagedestroy($source);

    update_job($jobId, [
        'convert_percent' => 100,
        'convert_text' => 'Görsel işlendi.'
    ]);

    return $outputPath;
}

function hex_to_rgb(string $hex): array {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    $int = hexdec($hex);
    return [($int >> 16) & 255, ($int >> 8) & 255, $int & 255];
}
