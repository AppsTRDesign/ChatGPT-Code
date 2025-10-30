<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

(Dotenv\Dotenv::createImmutable(dirname(__DIR__)))->safeLoad();

$inputPath = $argv[1] ?? 'storage/keys/keypair.b64';
$privatePath = $_ENV['OFFLINE_SIGN_PRIVATE_KEY_PATH'] ?? 'storage/keys/private.key';
$publicPath = $_ENV['OFFLINE_SIGN_PUBLIC_KEY_PATH'] ?? 'storage/keys/public.key';

if (!file_exists($inputPath)) {
    fwrite(STDERR, "Keypair not found at {$inputPath}\n");
    exit(1);
}

$keypair = base64_decode(trim((string) file_get_contents($inputPath)), true);
if ($keypair === false) {
    fwrite(STDERR, "Invalid keypair encoding\n");
    exit(1);
}

if (!function_exists('sodium_crypto_sign_secretkey')) {
    fwrite(STDERR, "Sodium extension is required\n");
    exit(1);
}

$secret = sodium_crypto_sign_secretkey($keypair);
$public = sodium_crypto_sign_publickey($keypair);

if (!is_dir(dirname($privatePath))) {
    mkdir(dirname($privatePath), 0775, true);
}

file_put_contents($privatePath, base64_encode($secret));
file_put_contents($publicPath, base64_encode($public));

echo "Private key written to {$privatePath}\n";
echo "Public key written to {$publicPath}\n";
