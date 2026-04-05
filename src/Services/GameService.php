<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use PDO;

final class GameService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DB::connection();
    }

    public function resolveCountryAndCityByIp(string $ip): array
    {
        $countryCode = (new GeoService())->detectCountryCode($ip, $_SERVER);

        $stmt = $this->db->prepare('SELECT id, code, name, flag_emoji FROM countries WHERE code = :code LIMIT 1');
        $stmt->execute(['code' => $countryCode]);
        $country = $stmt->fetch();

        if (!$country) {
            $stmt = $this->db->query('SELECT id, code, name, flag_emoji FROM countries ORDER BY id ASC LIMIT 1');
            $country = $stmt->fetch();
        }

        $cityStmt = $this->db->prepare('SELECT ci.id, ci.name FROM cities ci
            LEFT JOIN users u ON u.city_id = ci.id
            WHERE ci.country_id = :country_id AND ci.is_active = 1
            GROUP BY ci.id, ci.name, ci.base_population
            ORDER BY COUNT(u.id) ASC, ci.base_population DESC
            LIMIT 1');
        $cityStmt->execute(['country_id' => $country['id']]);
        $city = $cityStmt->fetch();

        if (!$city) {
            $fallbackCityStmt = $this->db->prepare('SELECT id, name FROM cities WHERE country_id = :country_id ORDER BY base_population DESC LIMIT 1');
            $fallbackCityStmt->execute(['country_id' => $country['id']]);
            $city = $fallbackCityStmt->fetch();
        }

        return [
            'country' => $country,
            'city' => $city,
        ];
    }

    public function register(string $username, string $email, string $password, string $ip): array
    {
        $username = trim($username);
        $email = mb_strtolower(trim($email));

        if (mb_strlen($username) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 6) {
            return ['ok' => false, 'message' => 'Geçersiz kayıt bilgileri.'];
        }

        $exists = $this->db->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
        $exists->execute(['username' => $username, 'email' => $email]);
        if ($exists->fetch()) {
            return ['ok' => false, 'message' => 'Kullanıcı adı veya e-posta zaten kayıtlı.'];
        }

        $loc = $this->resolveCountryAndCityByIp($ip);

        $stmt = $this->db->prepare('INSERT INTO users (username, email, password_hash, country_id, city_id, register_ip, gold, energy, energy_max, experience, level, strength, education, endurance, labor_points, war_power, created_at, last_energy_at) VALUES (:username, :email, :password_hash, :country_id, :city_id, :register_ip, 10, 3000, 3000, 0, 1, 5, 5, 5, 0, 10, NOW(), NOW())');
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'country_id' => $loc['country']['id'],
            'city_id' => $loc['city']['id'],
            'register_ip' => $ip,
        ]);

        $userId = (int) $this->db->lastInsertId();

        $resourceRows = $this->db->query('SELECT id FROM resources')->fetchAll();
        $ins = $this->db->prepare('INSERT INTO user_resources (user_id, resource_id, quantity) VALUES (:user_id, :resource_id, 0)');
        foreach ($resourceRows as $row) {
            $ins->execute(['user_id' => $userId, 'resource_id' => $row['id']]);
        }

        return ['ok' => true, 'message' => 'Kayıt tamamlandı.', 'user_id' => $userId];
    }

    public function login(string $email, string $password): array
    {
        $stmt = $this->db->prepare('SELECT id, password_hash FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => mb_strtolower(trim($email))]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($password, $row['password_hash'])) {
            return ['ok' => false, 'message' => 'Giriş bilgileri hatalı.'];
        }

        return ['ok' => true, 'user_id' => (int) $row['id']];
    }

    public function regenerateEnergy(array $user): array
    {
        $now = new \DateTimeImmutable('now');
        $last = new \DateTimeImmutable($user['last_energy_at']);
        $seconds = max(0, $now->getTimestamp() - $last->getTimestamp());

        $refillSeconds = ((int) $user['city_id'] === (int) ($this->topCity()['id'] ?? -1)) ? 360 : 600;
        $gained = (int) floor($seconds / $refillSeconds) * 300;

        if ($gained <= 0) {
            return $user;
        }

        $newEnergy = min((int) $user['energy_max'], (int) $user['energy'] + $gained);
        $stmt = $this->db->prepare('UPDATE users SET energy = :energy, last_energy_at = NOW() WHERE id = :id');
        $stmt->execute(['energy' => $newEnergy, 'id' => $user['id']]);

        $user['energy'] = $newEnergy;
        $user['last_energy_at'] = $now->format('Y-m-d H:i:s');
        return $user;
    }

    public function dashboard(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT u.*, c.name AS country_name, c.flag_emoji, c.code AS country_code, ci.name AS city_name,
            (ci.airport_level + ci.industry_level + ci.education_level + ci.army_level + ci.port_level + ci.space_level) AS city_score
            FROM users u
            JOIN countries c ON c.id = u.country_id
            JOIN cities ci ON ci.id = u.city_id
            WHERE u.id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return [];
        }

        $user = $this->regenerateEnergy($user);

        return [
            'user' => $user,
            'resources' => $this->userResources($userId),
            'market' => $this->marketOffers(),
            'countries' => $this->countryPopulation(),
            'map' => $this->mapPayload(),
            'top_city' => $this->topCity(),
        ];
    }

    public function userResources(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT r.id, r.resource_key, r.name, r.unit, ur.quantity, r.base_price FROM user_resources ur JOIN resources r ON r.id = ur.resource_id WHERE ur.user_id = :user_id ORDER BY r.id');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function work(int $userId, string $resourceKey): array
    {
        $dash = $this->dashboard($userId);
        $user = $dash['user'] ?? null;

        if (!$user) {
            return ['ok' => false, 'message' => 'Kullanıcı bulunamadı.'];
        }
        if ((int) $user['energy'] < 300) {
            return ['ok' => false, 'message' => 'Çalışmak için en az 300 enerji gerekli.'];
        }

        $resourceStmt = $this->db->prepare('SELECT id, name FROM resources WHERE resource_key = :k LIMIT 1');
        $resourceStmt->execute(['k' => $resourceKey]);
        $resource = $resourceStmt->fetch();

        if (!$resource) {
            return ['ok' => false, 'message' => 'Kaynak bulunamadı.'];
        }

        $cityTop = ((int) $user['city_id'] === (int) ($this->topCity()['id'] ?? -1));
        $yieldMultiplier = $cityTop ? 1.25 : 1.0;
        $gain = (int) max(1, floor((4 + ((int) $user['strength'] * 0.2)) * $yieldMultiplier));

        $this->db->beginTransaction();
        try {
            $updateUser = $this->db->prepare('UPDATE users SET energy = energy - 300, labor_points = labor_points + 1, experience = experience + 12, gold = gold + :gold WHERE id = :user_id');
            $goldGain = $resourceKey === 'gold' ? ($cityTop ? 1.5 : 1.0) : 0.5;
            $updateUser->execute(['gold' => $goldGain, 'user_id' => $userId]);

            $updateResource = $this->db->prepare('UPDATE user_resources SET quantity = quantity + :gain WHERE user_id = :user_id AND resource_id = :resource_id');
            $updateResource->execute(['gain' => $gain, 'user_id' => $userId, 'resource_id' => $resource['id']]);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => 'Çalışma işlemi başarısız oldu.'];
        }

        $this->autoLevelUp($userId);

        return ['ok' => true, 'message' => $resource['name'] . ' üretimi +' . $gain . ''];
    }

    public function battle(int $userId): array
    {
        $dash = $this->dashboard($userId);
        $user = $dash['user'] ?? null;

        if (!$user || (int) $user['energy'] < 300) {
            return ['ok' => false, 'message' => 'Savaş için enerji yetersiz.'];
        }

        $successChance = min(85, 35 + (int) $user['strength'] + (int) $user['endurance']);
        $roll = random_int(1, 100);
        $won = $roll <= $successChance;
        $xp = $won ? random_int(25, 45) : random_int(8, 18);
        $gold = $won ? random_int(2, 6) : 0;

        $stmt = $this->db->prepare('UPDATE users SET energy = energy - 300, experience = experience + :xp, gold = gold + :gold, war_power = war_power + :wp WHERE id = :id');
        $stmt->execute([
            'xp' => $xp,
            'gold' => $gold,
            'wp' => $won ? 2 : 1,
            'id' => $userId,
        ]);

        $this->autoLevelUp($userId);

        return ['ok' => true, 'message' => $won ? 'Savaşı kazandın! +' . $xp . ' XP' : 'Savaşı kaybettin ama +' . $xp . ' XP'];
    }

    public function upgradeStat(int $userId, string $stat): array
    {
        $allowed = ['strength', 'education', 'endurance'];
        if (!in_array($stat, $allowed, true)) {
            return ['ok' => false, 'message' => 'Stat türü geçersiz.'];
        }

        $stmt = $this->db->prepare('SELECT labor_points, gold, ' . $stat . ' FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['ok' => false, 'message' => 'Kullanıcı yok.'];
        }

        $costLabor = 5;
        $costGold = 5;
        if ((int) $user['labor_points'] < $costLabor || (float) $user['gold'] < $costGold) {
            return ['ok' => false, 'message' => 'Stat geliştirmek için çalışma puanı ve altın gerekli.'];
        }

        $u = $this->db->prepare('UPDATE users SET labor_points = labor_points - :lp, gold = gold - :gold, ' . $stat . ' = ' . $stat . ' + 1 WHERE id = :id');
        $u->execute(['lp' => $costLabor, 'gold' => $costGold, 'id' => $userId]);

        return ['ok' => true, 'message' => 'Stat geliştirildi: ' . $stat];
    }

    public function createMarketOffer(int $userId, int $resourceId, int $quantity, float $pricePerUnit): array
    {
        if ($quantity < 1 || $pricePerUnit <= 0) {
            return ['ok' => false, 'message' => 'Geçersiz market değeri.'];
        }

        $check = $this->db->prepare('SELECT quantity FROM user_resources WHERE user_id = :u AND resource_id = :r LIMIT 1');
        $check->execute(['u' => $userId, 'r' => $resourceId]);
        $row = $check->fetch();

        if (!$row || (int) $row['quantity'] < $quantity) {
            return ['ok' => false, 'message' => 'Yeterli stok yok.'];
        }

        $this->db->beginTransaction();
        try {
            $u = $this->db->prepare('UPDATE user_resources SET quantity = quantity - :q WHERE user_id = :u AND resource_id = :r');
            $u->execute(['q' => $quantity, 'u' => $userId, 'r' => $resourceId]);

            $i = $this->db->prepare('INSERT INTO market_offers (seller_id, resource_id, quantity, price_per_unit, status, created_at) VALUES (:s,:r,:q,:p,\'open\',NOW())');
            $i->execute(['s' => $userId, 'r' => $resourceId, 'q' => $quantity, 'p' => $pricePerUnit]);
            $this->db->commit();
            return ['ok' => true, 'message' => 'Market ilanı açıldı.'];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => 'Market ilanı açılamadı.'];
        }
    }

    public function buyMarketOffer(int $userId, int $offerId): array
    {
        $offerStmt = $this->db->prepare('SELECT * FROM market_offers WHERE id = :id AND status = \"open\" LIMIT 1');
        $offerStmt->execute(['id' => $offerId]);
        $offer = $offerStmt->fetch();
        if (!$offer) {
            return ['ok' => false, 'message' => 'İlan bulunamadı.'];
        }

        $total = (float) $offer['price_per_unit'] * (int) $offer['quantity'];

        $buyerStmt = $this->db->prepare('SELECT gold FROM users WHERE id = :id LIMIT 1');
        $buyerStmt->execute(['id' => $userId]);
        $buyer = $buyerStmt->fetch();

        if (!$buyer || (float) $buyer['gold'] < $total) {
            return ['ok' => false, 'message' => 'Yetersiz altın.'];
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare('UPDATE users SET gold = gold - :g WHERE id = :id')->execute(['g' => $total, 'id' => $userId]);
            $this->db->prepare('UPDATE users SET gold = gold + :g WHERE id = :id')->execute(['g' => $total, 'id' => $offer['seller_id']]);
            $this->db->prepare('UPDATE user_resources SET quantity = quantity + :q WHERE user_id = :u AND resource_id = :r')->execute(['q' => $offer['quantity'], 'u' => $userId, 'r' => $offer['resource_id']]);
            $this->db->prepare('UPDATE market_offers SET status = \"sold\", buyer_id = :b WHERE id = :id')->execute(['b' => $userId, 'id' => $offerId]);
            $this->db->commit();
            return ['ok' => true, 'message' => 'Satın alma başarılı.'];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => 'Satın alma başarısız.'];
        }
    }

    public function marketOffers(): array
    {
        $stmt = $this->db->query('SELECT mo.id, mo.quantity, mo.price_per_unit, r.name AS resource_name, u.username AS seller_name FROM market_offers mo JOIN resources r ON r.id = mo.resource_id JOIN users u ON u.id = mo.seller_id WHERE mo.status = "open" ORDER BY mo.id DESC LIMIT 30');
        return $stmt->fetchAll() ?: [];
    }

    public function countryPopulation(): array
    {
        $sql = 'SELECT c.id, c.name, c.code, c.flag_emoji, COUNT(u.id) AS player_count
            FROM countries c
            LEFT JOIN users u ON u.country_id = c.id
            GROUP BY c.id, c.name, c.code, c.flag_emoji
            ORDER BY player_count DESC, c.name ASC';
        return $this->db->query($sql)->fetchAll() ?: [];
    }

    public function mapPayload(): array
    {
        $cities = $this->db->query('SELECT ci.id, ci.name, ci.lat, ci.lng, c.name AS country_name, c.code AS country_code,
            (SELECT COUNT(*) FROM users u WHERE u.city_id = ci.id) AS player_count
            FROM cities ci
            JOIN countries c ON c.id = ci.country_id
            ORDER BY c.name, ci.name')->fetchAll() ?: [];

        $countryResources = $this->db->query('SELECT c.code, c.name AS country_name, r.resource_key, r.name AS resource_name, cr.daily_yield
            FROM country_resources cr
            JOIN countries c ON c.id = cr.country_id
            JOIN resources r ON r.id = cr.resource_id
            ORDER BY c.code, r.id')->fetchAll() ?: [];

        return ['cities' => $cities, 'country_resources' => $countryResources];
    }

    public function topCity(): ?array
    {
        $stmt = $this->db->query('SELECT ci.id, ci.name, c.name AS country_name,
            (ci.airport_level + ci.industry_level + ci.education_level + ci.army_level + ci.port_level + ci.space_level) AS score
            FROM cities ci JOIN countries c ON c.id = ci.country_id
            ORDER BY score DESC, ci.base_population DESC LIMIT 1');
        return $stmt->fetch() ?: null;
    }

    public function autoLevelUp(int $userId): void
    {
        $stmt = $this->db->prepare('SELECT level, experience, energy_max FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $u = $stmt->fetch();
        if (!$u) {
            return;
        }

        $nextNeed = ((int) $u['level']) * 120;
        if ((int) $u['experience'] >= $nextNeed) {
            $this->db->prepare('UPDATE users SET level = level + 1, energy_max = energy_max + 150, energy = LEAST(energy + 150, energy_max + 150), experience = experience - :need WHERE id = :id')->execute([
                'need' => $nextNeed,
                'id' => $userId,
            ]);
        }
    }

    public function adminWorldData(): array
    {
        return [
            'countries' => $this->db->query('SELECT * FROM countries ORDER BY name')->fetchAll() ?: [],
            'cities' => $this->db->query('SELECT ci.*, c.name AS country_name FROM cities ci JOIN countries c ON c.id = ci.country_id ORDER BY c.name, ci.name')->fetchAll() ?: [],
            'resources' => $this->db->query('SELECT * FROM resources ORDER BY id')->fetchAll() ?: [],
            'country_resources' => $this->db->query('SELECT cr.*, c.name AS country_name, r.name AS resource_name FROM country_resources cr JOIN countries c ON c.id = cr.country_id JOIN resources r ON r.id = cr.resource_id ORDER BY c.name, r.name')->fetchAll() ?: [],
        ];
    }

    public function createCountry(string $code, string $name, string $flag): array
    {
        $code = strtoupper(trim($code));
        if (strlen($code) !== 2 || $name === '') {
            return ['ok' => false, 'message' => 'Ülke bilgisi geçersiz.'];
        }

        $stmt = $this->db->prepare('INSERT INTO countries (code, name, flag_emoji, map_code, created_at) VALUES (:code, :name, :flag, :map_code, NOW())');
        $stmt->execute(['code' => $code, 'name' => trim($name), 'flag' => trim($flag), 'map_code' => $code]);
        return ['ok' => true, 'message' => 'Ülke eklendi.'];
    }

    public function createCity(int $countryId, string $name, float $lat, float $lng): array
    {
        if ($countryId < 1 || $name === '') {
            return ['ok' => false, 'message' => 'Şehir bilgisi geçersiz.'];
        }

        $stmt = $this->db->prepare('INSERT INTO cities (country_id, name, lat, lng, base_population, airport_level, industry_level, education_level, army_level, port_level, space_level) VALUES (:country, :name, :lat, :lng, 1000000, 1, 1, 1, 1, 0, 0)');
        $stmt->execute(['country' => $countryId, 'name' => trim($name), 'lat' => $lat, 'lng' => $lng]);
        return ['ok' => true, 'message' => 'Şehir eklendi.'];
    }

    public function addCountryResource(int $countryId, int $resourceId, int $dailyYield): array
    {
        $stmt = $this->db->prepare('INSERT INTO country_resources (country_id, resource_id, daily_yield, stock) VALUES (:c, :r, :y, :s) ON DUPLICATE KEY UPDATE daily_yield = VALUES(daily_yield)');
        $stmt->execute(['c' => $countryId, 'r' => $resourceId, 'y' => $dailyYield, 's' => $dailyYield * 20]);
        return ['ok' => true, 'message' => 'Kaynak dağılımı güncellendi.'];
    }
}
