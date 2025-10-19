<?php

define('NS_STORAGE', __DIR__ . '/../storage');
define('NS_UPLOAD_PATH', NS_STORAGE . '/uploads');
define('NS_OUTPUT_PATH', NS_STORAGE . '/output');
define('NS_JOB_PATH', NS_STORAGE . '/jobs');

function ensure_directories(): void {
    foreach ([NS_STORAGE, NS_UPLOAD_PATH, NS_OUTPUT_PATH, NS_JOB_PATH] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }
}

function job_file(string $jobId): string {
    return NS_JOB_PATH . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $jobId) . '.json';
}

function write_job(string $jobId, array $data): void {
    ensure_directories();
    $path = job_file($jobId);
    $payload = array_merge([
        'job_id' => $jobId,
        'status' => 'pending',
        'upload_percent' => 0,
        'convert_percent' => 0,
        'upload_text' => '',
        'convert_text' => ''
    ], $data);
    file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function update_job(string $jobId, array $data): void {
    $existing = read_job($jobId);
    $merged = array_merge($existing, $data);
    write_job($jobId, $merged);
}

function read_job(string $jobId): array {
    $path = job_file($jobId);
    if (!file_exists($path)) {
        return [
            'job_id' => $jobId,
            'status' => 'pending',
            'upload_percent' => 0,
            'convert_percent' => 0
        ];
    }
    $json = file_get_contents($path);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return [
            'job_id' => $jobId,
            'status' => 'pending',
            'upload_percent' => 0,
            'convert_percent' => 0
        ];
    }
    return $data;
}

function build_download_token(string $path): string {
    $basename = basename($path);
    $hash = hash('sha256', $path . '|' . filesize($path));
    return $hash . ':' . $basename;
}

function resolve_download_token(string $token): ?string {
    $parts = explode(':', $token, 2);
    if (count($parts) !== 2) {
        return null;
    }
    [$hash, $basename] = $parts;
    $fullPath = realpath(NS_OUTPUT_PATH . '/' . $basename);
    if (!$fullPath || !str_starts_with($fullPath, realpath(NS_OUTPUT_PATH))) {
        return null;
    }
    if (!file_exists($fullPath)) {
        return null;
    }
    $expected = hash('sha256', $fullPath . '|' . filesize($fullPath));
    return hash_equals($expected, $hash) ? $fullPath : null;
}

function ffprobe_duration(string $file): ?float {
    $cmd = sprintf('ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s', escapeshellarg($file));
    $duration = shell_exec($cmd);
    if ($duration === null) {
        return null;
    }
    $duration = trim($duration);
    return is_numeric($duration) ? (float)$duration : null;
}

function run_ffmpeg_with_progress(string $command, string $jobId, ?float $duration = null): int {
    $descriptorSpec = [
        1 => ['pipe', 'w'], // stdout
        2 => ['pipe', 'w']  // stderr
    ];
    $process = proc_open($command, $descriptorSpec, $pipes);
    if (!is_resource($process)) {
        update_job($jobId, [
            'status' => 'error',
            'convert_text' => 'FFmpeg başlatılamadı.'
        ]);
        return 1;
    }

    stream_set_blocking($pipes[1], true);
    stream_set_blocking($pipes[2], true);

    while (!feof($pipes[1])) {
        $line = fgets($pipes[1]);
        if ($line === false) {
            break;
        }
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (str_starts_with($line, 'out_time_ms=')) {
            $ms = (float)substr($line, strlen('out_time_ms='));
            if ($duration && $duration > 0) {
                $percent = min(100, round(($ms / 1000000) / $duration * 100));
                update_job($jobId, [
                    'convert_percent' => $percent,
                    'convert_text' => sprintf('FFmpeg işleniyor... %d%%', $percent),
                    'status' => 'processing'
                ]);
            }
        }
        if ($line === 'progress=end') {
            update_job($jobId, [
                'convert_percent' => 100,
                'convert_text' => 'Dönüştürme tamamlandı.',
                'status' => 'processing'
            ]);
        }
    }

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        update_job($jobId, [
            'status' => 'error',
            'convert_text' => 'FFmpeg hata verdi.',
            'message' => $stderr
        ]);
    }

    return $exitCode;
}

function sanitize_filename(string $name): string {
    $name = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $name);
    return $name ?: 'dosya';
}

