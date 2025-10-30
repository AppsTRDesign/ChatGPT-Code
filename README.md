# Lisans Yönetim Sistemi

Slim 4 ve Eloquent kullanan, PHP 8.2+ üzerinde çalışan üretim seviyesinde lisans yönetim platformu. Plesk / AlmaLinux 8 hedef ortamı için optimize edilmiştir.

## Başlangıç

### Kurulum

```bash
composer install
cp .env.example .env
php scripts/migrate.php
php scripts/seed.php
php -S 0.0.0.0:8080 -t public
```

Varsayılan yönetici hesabı `admin@example.com / Password123!` şeklindedir. Giriş sonrasında parolanızı değiştirin.

### Çevresel Değişkenler

`.env` dosyasında aşağıdaki anahtarları yapılandırın:

- `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `JWT_SECRET`, `JWT_TTL`, `JWT_REFRESH_TTL`
- `OFFLINE_SIGN_PRIVATE_KEY_PATH` ve `OFFLINE_SIGN_PUBLIC_KEY_PATH`
- `CORS_ALLOWED_ORIGINS` (virgülle ayrılmış liste)
- `RATE_LIMIT_DEFAULT`, `RATE_LIMIT_WINDOW`

Offline lisans imzası için Ed25519 anahtar ikilisi oluşturun:

```bash
php -r "echo base64_encode(sodium_crypto_sign_keypair());" > storage/keys/keypair.b64
php scripts/extract-keys.php
```

## Plesk / AlmaLinux 8 Dağıtımı

1. **Plesk Alanı**: Domain veya subdomain oluşturun, Document Root olarak `public/` dizinini seçin.
2. **PHP Ayarları**:
   - PHP version: 8.2 FPM
   - `display_errors=Off`, `expose_php=Off`
   - `post_max_size=16M`, `upload_max_filesize=16M`
   - GZip ve HTTP/2 açık olmalı.
3. **.env Yönetimi**: Plesk dosya yöneticisi ile `.env` dosyasını düzenleyin. Gizli anahtarları Plesk Secret Store ile koruyun.
4. **Bağımlılıklar**: SSH ile sunucuya bağlanıp `composer install --no-dev` komutunu çalıştırın.
5. **Veritabanı**: `php scripts/migrate.php` ve `php scripts/seed.php` komutlarını çalıştırarak tablo ve örnek veriyi oluşturun.
6. **Cron / Worker**: Webhook kuyrukları için dakikada bir cron ekleyin:
   ```
   * * * * * /opt/plesk/php/8.2/bin/php /var/www/vhosts/<domain>/scripts/webhook-worker.php
   ```
7. **Log Rotasyonu**: Plesk Log Browser üzerinden `storage/logs` için rotasyonu günlük olarak ayarlayın.

## REST API

OpenAPI tanımı `public/openapi.yaml` dosyasındadır. Postman koleksiyonu `tools/postman_collection.json` olarak eklenmiştir.

### Örnek İstekler

```bash
curl -X POST "https://licenses.example.com/api/v1/licenses/issue" \
  -H "Authorization: Bearer <JWT>" \
  -H "Content-Type: application/json" \
  -d '{"product_code":"APPX","type":"subscription","seats":5,"expires_at":"2024-12-31T23:59:59Z"}'

curl -X POST "https://licenses.example.com/api/v1/licenses/validate" \
  -H "Content-Type: application/json" \
  -H "X-Access-Key: <ACCESS>" \
  -H "X-Signature: <SIGNATURE>" \
  -H "X-Timestamp: <TIMESTAMP>" \
  -H "X-Nonce: <NONCE>" \
  -d '{"product_code":"APPX","key":"PROD-202401-XXXX-XXXX-XXXX","hwid":"device-1"}'
```

### Offline Lisans Doğrulama

İstemciler Ed25519 public anahtarı ile token imzasını doğrulayabilir:

```php
$bundle = json_decode($offlineToken, true);
$payload = json_encode($bundle['payload']);
$signature = base64_decode($bundle['signature']);
$publicKey = base64_decode(trim(file_get_contents('public.key')));
if (sodium_crypto_sign_verify_detached($signature, $payload, $publicKey)) {
    // geçerli
}
```

## Admin Paneli

- Bootstrap 5, DataTables ve Chart.js ile responsive UI
- Dashboard, lisans listesi ve hızlı oluşturma sayfaları
- CSRF korumalı formlar, session tabanlı kimlik doğrulama

## Test ve Kalite

```bash
composer test
composer cs-fix
vendor/bin/phpstan analyse --level=8 app
```

## İstemci Entegrasyon Örneği (PHP)

```php
function sign_request(string $secret, string $accessKey, array $body): array {
    $json = json_encode($body, JSON_THROW_ON_ERROR);
    $timestamp = time();
    $nonce = bin2hex(random_bytes(8));
    $bodyHash = hash('sha256', $json);
    $baseString = implode("\n", [$accessKey, $timestamp, $nonce, $bodyHash]);
    $signature = hash_hmac('sha256', $baseString, $secret);
    return [$json, $timestamp, $nonce, $signature];
}
```

## Güvenlik Önlemleri

- Argon2id parola ve lisans anahtarı hash'leri
- HMAC imzalı istekler, nonce ve zaman damgası kontrolü
- Rate limit tablosu ile IP ve anahtar bazlı sınırlama
- Tüm API cevapları `application/json; charset=utf-8`
- CORS politikası sadece izinli originlere açık

## Ek Araçlar

- `scripts/migrate.php`: Migration çalıştırma
- `scripts/seed.php`: Örnek veri ekleme
- `public/openapi.yaml`: OpenAPI şeması
- `tools/postman_collection.json`: Postman koleksiyonu

