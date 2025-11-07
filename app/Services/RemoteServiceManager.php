<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Models\Setting;
use phpseclib3\Net\SSH2;
use RuntimeException;
use Throwable;

final class RemoteServiceManager
{
    private function connect(): SSH2
    {
        $host = $this->getValue('remote_host');
        $port = (int) ($this->getValue('remote_port') ?: Config::get('remote.port', 22));
        $username = $this->getValue('remote_username');
        $password = $this->getValue('remote_password');

        if ($host === '') {
            throw new RuntimeException('Uzaktaki sunucu adresi ayarlı değil.');
        }

        if ($username === '' || $password === '') {
            throw new RuntimeException('Uzaktaki sunucu kullanıcı adı veya parolası eksik.');
        }

        $ssh = new SSH2($host, $port ?: 22);

        try {
            if (!$ssh->login($username, $password)) {
                throw new RuntimeException('Uzaktaki sunucu kimlik doğrulaması başarısız.');
            }
        } catch (Throwable $e) {
            throw new RuntimeException('Uzaktaki sunucuya bağlanılamadı: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }

        return $ssh;
    }

    public function start(string $command): string
    {
        $command = trim($command);
        if ($command === '') {
            throw new RuntimeException('Servis başlatma komutu tanımlı değil.');
        }

        $ssh = $this->connect();
        $payload = sprintf('nohup %s > /dev/null 2>&1 & echo $!', $command);
        $response = (string) $ssh->exec($payload);
        $ssh->disconnect();

        $lines = array_values(array_filter(array_map('trim', explode("\n", $response))));
        $pid = $lines !== [] ? (string) end($lines) : '';

        if ($pid === '') {
            throw new RuntimeException('Uzaktaki servis başlatılamadı.');
        }

        return $pid;
    }

    public function stop(string $command): void
    {
        $command = trim($command);
        if ($command === '') {
            throw new RuntimeException('Servis durdurma komutu tanımlı değil.');
        }

        $ssh = $this->connect();
        $pattern = escapeshellarg($command);
        $ssh->exec('pkill -f ' . $pattern);
        $ssh->disconnect();
    }

    public function check(string $command): bool
    {
        $command = trim($command);
        if ($command === '') {
            return false;
        }

        $ssh = $this->connect();
        $pattern = escapeshellarg($command);
        $output = trim((string) $ssh->exec('pgrep -f ' . $pattern));
        $ssh->disconnect();

        return $output !== '';
    }

    private function getValue(string $key): string
    {
        $setting = Setting::get($key);
        if ($setting !== null && $setting !== '') {
            return $setting;
        }

        return (string) Config::get('remote.' . str_replace('remote_', '', $key), '');
    }
}
