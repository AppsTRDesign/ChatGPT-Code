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

        $topCity = $this->topCity();
        $nation = $this->nationContext((int) $user['country_id']);
        $formula = new StatFormulaService();
        $cfg = (new BalanceConfigService())->get();
        $refillSeconds = $formula->energyTickSeconds(((int) $user['city_id'] === (int) ($topCity['id'] ?? -1)), (int) $nation['nation_tier'], $cfg);
        $gained = (int) floor($seconds / $refillSeconds) * (int) $cfg['energy_per_tick'];

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
            ci.airport_level, ci.industry_level, ci.education_level, ci.army_level, ci.port_level, ci.space_level,
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

        $nation = $this->nationContext((int) $user['country_id']);

        return [
            'user' => $user,
            'nation' => $nation,
            'progress' => [
                'next_level_xp' => (new StatFormulaService())->levelXpRequirement((int) $user['level'], (new BalanceConfigService())->get()),
            ],
            'resources' => $this->userResources($userId),
            'market' => $this->marketOffers(),
            'countries' => $this->countryPopulation(),
            'map' => $this->mapPayload(),
            'resource_market' => $this->resourceMarketSnapshot(),
            'market_rules' => $this->marketRules(),
            'factory_types' => $this->factoryTypes(),
            'factories' => $this->userFactories($userId),
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

        $countryResourceStmt = $this->db->prepare('SELECT id, stock, daily_yield, quality_index, regeneration_rate FROM country_resources WHERE country_id = :country_id AND resource_id = :resource_id LIMIT 1');
        $countryResourceStmt->execute([
            'country_id' => $user['country_id'],
            'resource_id' => $resource['id'],
        ]);
        $countryResource = $countryResourceStmt->fetch();

        if (!$countryResource || (int) $countryResource['stock'] <= 0) {
            return ['ok' => false, 'message' => 'Bu kaynak ülkende şu an tükenmiş durumda.'];
        }

        $topCity = $this->topCity();
        $nation = $this->nationContext((int) $user['country_id']);
        $formula = new StatFormulaService();
        $cityTop = ((int) $user['city_id'] === (int) ($topCity['id'] ?? -1));
        $baseGain = $formula->workYield((int) $user['strength'], (int) $user['education'], $cityTop, (int) $nation['nation_tier']);
        $scarcityFactor = max(0.35, min(1.40, ((float) $countryResource['stock'] / max(1, (float) $countryResource['daily_yield'] * 30))));
        $qualityFactor = max(0.70, min(1.60, (float) $countryResource['quality_index']));
        $gain = (int) max(1, floor($baseGain * $scarcityFactor * $qualityFactor));

        $this->db->beginTransaction();
        try {
            $cfg = (new BalanceConfigService())->get();
            $workXp = $formula->workXp((int) $nation['nation_tier'], $cfg);
            $updateUser = $this->db->prepare('UPDATE users SET energy = energy - 300, labor_points = labor_points + 1, experience = experience + :work_xp, gold = gold + :gold WHERE id = :user_id');
            $goldGain = $resourceKey === 'gold' ? ($cityTop ? 1.5 : 1.0) : 0.5;
            $updateUser->execute(['work_xp' => $workXp, 'gold' => $goldGain, 'user_id' => $userId]);

            $updateResource = $this->db->prepare('UPDATE user_resources SET quantity = quantity + :gain WHERE user_id = :user_id AND resource_id = :resource_id');
            $updateResource->execute(['gain' => $gain, 'user_id' => $userId, 'resource_id' => $resource['id']]);

            $countryStockUpdate = $this->db->prepare('UPDATE country_resources SET stock = GREATEST(0, stock - :used + FLOOR(daily_yield * regeneration_rate)) WHERE id = :id');
            $countryStockUpdate->execute(['used' => $gain, 'id' => $countryResource['id']]);

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

        $nation = $this->nationContext((int) $user['country_id']);
        $formula = new StatFormulaService();
        $successChance = $formula->battleWinChance((int) $user['strength'], (int) $user['endurance'], (int) $user['level'], (int) $nation['nation_tier']);
        $roll = random_int(1, 100);
        $won = $roll <= $successChance;
        $cfg = (new BalanceConfigService())->get();
        $xp = $formula->battleXp($won, (int) $nation['nation_tier'], $cfg);
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

        $costs = (new StatFormulaService())->statUpgradeCost($stat, (int) $user[$stat]);
        $costLabor = (int) $costs['labor_points'];
        $costGold = (int) $costs['gold'];
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

        $rules = $this->marketRules();
        $pricePerUnit = round($pricePerUnit, 2);

        $openOfferStmt = $this->db->prepare('SELECT COUNT(*) AS open_count FROM market_offers WHERE seller_id = :seller_id AND status = "open"');
        $openOfferStmt->execute(['seller_id' => $userId]);
        $openOfferCount = (int) (($openOfferStmt->fetch()['open_count'] ?? 0));
        if ($openOfferCount >= $rules['max_open_offers']) {
            return ['ok' => false, 'message' => 'Açık ilan limitine ulaştın.'];
        }

        $marketPrice = $this->resourceReferencePrice($resourceId);
        if ($marketPrice <= 0) {
            return ['ok' => false, 'message' => 'Kaynak için referans fiyat hesaplanamadı.'];
        }

        $minPrice = round($marketPrice * $rules['price_floor_ratio'], 2);
        $maxPrice = round($marketPrice * $rules['price_ceiling_ratio'], 2);
        if ($pricePerUnit < $minPrice || $pricePerUnit > $maxPrice) {
            return ['ok' => false, 'message' => 'Fiyat bandı dışında. Kabul aralığı: ' . number_format($minPrice, 2, ',', '.') . ' - ' . number_format($maxPrice, 2, ',', '.')];
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

            $grossTotal = round($quantity * $pricePerUnit, 2);
            $taxTotal = round($grossTotal * ($rules['buyer_tax_percent'] / 100), 2);
            $sellerCommissionTotal = round($grossTotal * ($rules['seller_commission_percent'] / 100), 2);
            $sellerNetTotal = round($grossTotal - $sellerCommissionTotal, 2);

            $i = $this->db->prepare('INSERT INTO market_offers (seller_id, resource_id, quantity, price_per_unit, tax_rate_percent, seller_commission_percent, gross_total, tax_total, seller_commission_total, seller_net_total, status, created_at) VALUES (:s,:r,:q,:p,:tax_rate,:seller_commission_rate,:gross_total,:tax_total,:seller_commission_total,:seller_net_total,\'open\',NOW())');
            $i->execute([
                's' => $userId,
                'r' => $resourceId,
                'q' => $quantity,
                'p' => $pricePerUnit,
                'tax_rate' => $rules['buyer_tax_percent'],
                'seller_commission_rate' => $rules['seller_commission_percent'],
                'gross_total' => $grossTotal,
                'tax_total' => $taxTotal,
                'seller_commission_total' => $sellerCommissionTotal,
                'seller_net_total' => $sellerNetTotal,
            ]);
            $this->db->commit();
            return ['ok' => true, 'message' => 'Market ilanı açıldı.'];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => 'Market ilanı açılamadı.'];
        }
    }

    public function buyMarketOffer(int $userId, int $offerId): array
    {
        $this->db->beginTransaction();
        try {
            $offerStmt = $this->db->prepare('SELECT * FROM market_offers WHERE id = :id AND status = "open" LIMIT 1 FOR UPDATE');
            $offerStmt->execute(['id' => $offerId]);
            $offer = $offerStmt->fetch();

            if (!$offer) {
                throw new \RuntimeException('İlan bulunamadı.');
            }
            if ((int) $offer['seller_id'] === $userId) {
                throw new \RuntimeException('Kendi ilanını satın alamazsın.');
            }

            $grossTotal = round((float) $offer['gross_total'], 2);
            if ($grossTotal <= 0) {
                $grossTotal = round((float) $offer['price_per_unit'] * (int) $offer['quantity'], 2);
            }

            $buyerTaxTotal = round((float) $offer['tax_total'], 2);
            $sellerCommissionTotal = round((float) $offer['seller_commission_total'], 2);
            $sellerNetTotal = round((float) $offer['seller_net_total'], 2);
            if ($sellerNetTotal <= 0) {
                $sellerNetTotal = round($grossTotal - $sellerCommissionTotal, 2);
            }

            $buyerTotalCost = round($grossTotal + $buyerTaxTotal, 2);

            $buyerStmt = $this->db->prepare('SELECT gold FROM users WHERE id = :id LIMIT 1 FOR UPDATE');
            $buyerStmt->execute(['id' => $userId]);
            $buyer = $buyerStmt->fetch();

            if (!$buyer || (float) $buyer['gold'] < $buyerTotalCost) {
                throw new \RuntimeException('Yetersiz altın.');
            }

            $this->db->prepare('UPDATE users SET gold = gold - :g WHERE id = :id')->execute(['g' => $buyerTotalCost, 'id' => $userId]);
            $this->db->prepare('UPDATE users SET gold = gold + :g WHERE id = :id')->execute(['g' => $sellerNetTotal, 'id' => $offer['seller_id']]);
            $this->db->prepare('UPDATE user_resources SET quantity = quantity + :q WHERE user_id = :u AND resource_id = :r')->execute(['q' => $offer['quantity'], 'u' => $userId, 'r' => $offer['resource_id']]);
            $this->db->prepare('UPDATE market_offers SET status = "sold", buyer_id = :b, sold_at = NOW() WHERE id = :id')->execute(['b' => $userId, 'id' => $offerId]);
            $this->db->prepare('INSERT INTO market_transactions (offer_id, buyer_id, seller_id, resource_id, quantity, price_per_unit, gross_total, buyer_tax_total, seller_commission_total, seller_net_total, created_at) VALUES (:offer_id,:buyer_id,:seller_id,:resource_id,:quantity,:price_per_unit,:gross_total,:buyer_tax_total,:seller_commission_total,:seller_net_total,NOW())')->execute([
                'offer_id' => $offer['id'],
                'buyer_id' => $userId,
                'seller_id' => $offer['seller_id'],
                'resource_id' => $offer['resource_id'],
                'quantity' => $offer['quantity'],
                'price_per_unit' => $offer['price_per_unit'],
                'gross_total' => $grossTotal,
                'buyer_tax_total' => $buyerTaxTotal,
                'seller_commission_total' => $sellerCommissionTotal,
                'seller_net_total' => $sellerNetTotal,
            ]);
            $this->db->commit();
            return ['ok' => true, 'message' => 'Satın alma başarılı. Toplam maliyet: ' . number_format($buyerTotalCost, 2, ',', '.') . ' gold'];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => $e->getMessage() ?: 'Satın alma başarısız.'];
        }
    }

    public function marketOffers(): array
    {
        $stmt = $this->db->query('SELECT mo.id, mo.quantity, mo.price_per_unit, mo.tax_rate_percent, mo.seller_commission_percent, mo.gross_total, mo.tax_total, mo.seller_net_total, r.name AS resource_name, u.username AS seller_name FROM market_offers mo JOIN resources r ON r.id = mo.resource_id JOIN users u ON u.id = mo.seller_id WHERE mo.status = "open" ORDER BY mo.id DESC LIMIT 30');
        return $stmt->fetchAll() ?: [];
    }

    public function marketRules(): array
    {
        $defaults = [
            'market_buyer_tax_percent' => '4',
            'market_seller_commission_percent' => '3',
            'market_price_floor_ratio' => '0.50',
            'market_price_ceiling_ratio' => '2.50',
            'market_max_open_offers_per_user' => '15',
        ];

        $keys = array_keys($defaults);
        $inClause = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $this->db->prepare("SELECT `key`, `value` FROM settings WHERE `key` IN ($inClause)");
        $stmt->execute($keys);
        $rows = $stmt->fetchAll() ?: [];

        $map = $defaults;
        foreach ($rows as $row) {
            $map[(string) $row['key']] = (string) $row['value'];
        }

        return [
            'buyer_tax_percent' => max(0.0, min(30.0, (float) $map['market_buyer_tax_percent'])),
            'seller_commission_percent' => max(0.0, min(30.0, (float) $map['market_seller_commission_percent'])),
            'price_floor_ratio' => max(0.10, min(1.0, (float) $map['market_price_floor_ratio'])),
            'price_ceiling_ratio' => max(1.0, min(10.0, (float) $map['market_price_ceiling_ratio'])),
            'max_open_offers' => max(1, min(100, (int) $map['market_max_open_offers_per_user'])),
        ];
    }

    private function resourceReferencePrice(int $resourceId): float
    {
        $stmt = $this->db->prepare('SELECT current_price FROM resource_market_prices WHERE resource_id = :resource_id LIMIT 1');
        $stmt->execute(['resource_id' => $resourceId]);
        $marketRow = $stmt->fetch();
        if ($marketRow && (float) $marketRow['current_price'] > 0) {
            return (float) $marketRow['current_price'];
        }

        $baseStmt = $this->db->prepare('SELECT base_price FROM resources WHERE id = :resource_id LIMIT 1');
        $baseStmt->execute(['resource_id' => $resourceId]);
        $baseRow = $baseStmt->fetch();
        return $baseRow ? (float) $baseRow['base_price'] : 0.0;
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

    public function factoryTypes(): array
    {
        $stmt = $this->db->query('SELECT ft.id, ft.type_key, ft.name, ft.input_resource_id, ft.output_resource_id, ft.base_cycle_minutes, ft.base_output, ft.base_workers, ft.base_energy_cost, r1.name AS input_resource_name, r2.name AS output_resource_name FROM factory_types ft LEFT JOIN resources r1 ON r1.id = ft.input_resource_id JOIN resources r2 ON r2.id = ft.output_resource_id ORDER BY ft.id');
        return $stmt->fetchAll() ?: [];
    }

    public function userFactories(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT uf.*, ft.name AS factory_name, ft.base_cycle_minutes, ft.base_output, ft.base_workers, ft.base_energy_cost, r1.name AS input_resource_name, r2.name AS output_resource_name FROM user_factories uf JOIN factory_types ft ON ft.id = uf.factory_type_id LEFT JOIN resources r1 ON r1.id = ft.input_resource_id JOIN resources r2 ON r2.id = ft.output_resource_id WHERE uf.user_id = :user_id ORDER BY uf.id DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function createFactory(int $userId, int $factoryTypeId): array
    {
        $userStmt = $this->db->prepare('SELECT id, country_id, city_id, gold FROM users WHERE id = :id LIMIT 1');
        $userStmt->execute(['id' => $userId]);
        $user = $userStmt->fetch();
        if (!$user) {
            return ['ok' => false, 'message' => 'Kullanıcı bulunamadı.'];
        }

        $typeStmt = $this->db->prepare('SELECT id, name FROM factory_types WHERE id = :id LIMIT 1');
        $typeStmt->execute(['id' => $factoryTypeId]);
        $type = $typeStmt->fetch();
        if (!$type) {
            return ['ok' => false, 'message' => 'Fabrika tipi bulunamadı.'];
        }

        $buildCost = 50.0;
        if ((float) $user['gold'] < $buildCost) {
            return ['ok' => false, 'message' => 'Fabrika kurmak için 50 gold gerekli.'];
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare('UPDATE users SET gold = gold - :cost WHERE id = :id')->execute(['cost' => $buildCost, 'id' => $userId]);
            $this->db->prepare('INSERT INTO user_factories (user_id, country_id, city_id, factory_type_id, level, workers, status, created_at, updated_at, last_production_at) VALUES (:user_id, :country_id, :city_id, :factory_type_id, 1, 5, "active", NOW(), NOW(), NULL)')->execute([
                'user_id' => $userId,
                'country_id' => $user['country_id'],
                'city_id' => $user['city_id'],
                'factory_type_id' => $factoryTypeId,
            ]);
            $this->db->commit();
            return ['ok' => true, 'message' => $type['name'] . ' kuruldu.'];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => 'Fabrika kurulamadı.'];
        }
    }

    public function produceFactory(int $userId, int $factoryId): array
    {
        $stmt = $this->db->prepare('SELECT uf.*, ft.input_resource_id, ft.output_resource_id, ft.base_output, ft.base_workers, ft.base_energy_cost FROM user_factories uf JOIN factory_types ft ON ft.id = uf.factory_type_id WHERE uf.id = :id AND uf.user_id = :user_id LIMIT 1');
        $stmt->execute(['id' => $factoryId, 'user_id' => $userId]);
        $factory = $stmt->fetch();
        if (!$factory) {
            return ['ok' => false, 'message' => 'Fabrika bulunamadı.'];
        }

        $userStmt = $this->db->prepare('SELECT energy FROM users WHERE id = :id LIMIT 1');
        $userStmt->execute(['id' => $userId]);
        $user = $userStmt->fetch();
        if (!$user || (int) $user['energy'] < (int) $factory['base_energy_cost']) {
            return ['ok' => false, 'message' => 'Fabrika üretimi için enerji yetersiz.'];
        }

        $output = (int) floor(((int) $factory['base_output']) * (1 + ((int) $factory['level'] - 1) * 0.25));
        $inputNeed = (int) floor($output * 0.5);

        $this->db->beginTransaction();
        try {
            if (!empty($factory['input_resource_id'])) {
                $inputStmt = $this->db->prepare('SELECT quantity FROM user_resources WHERE user_id = :u AND resource_id = :r LIMIT 1');
                $inputStmt->execute(['u' => $userId, 'r' => $factory['input_resource_id']]);
                $input = $inputStmt->fetch();
                if (!$input || (int) $input['quantity'] < $inputNeed) {
                    throw new \RuntimeException('Yeterli hammadde yok.');
                }

                $this->db->prepare('UPDATE user_resources SET quantity = quantity - :q WHERE user_id = :u AND resource_id = :r')->execute(['q' => $inputNeed, 'u' => $userId, 'r' => $factory['input_resource_id']]);
            }

            $this->db->prepare('UPDATE user_resources SET quantity = quantity + :q WHERE user_id = :u AND resource_id = :r')->execute(['q' => $output, 'u' => $userId, 'r' => $factory['output_resource_id']]);
            $this->db->prepare('UPDATE users SET energy = energy - :e, experience = experience + 15 WHERE id = :id')->execute(['e' => $factory['base_energy_cost'], 'id' => $userId]);
            $this->db->prepare('UPDATE user_factories SET last_production_at = NOW(), updated_at = NOW() WHERE id = :id')->execute(['id' => $factoryId]);
            $this->db->prepare('INSERT INTO factory_production_logs (factory_id, user_id, input_resource_id, output_resource_id, input_amount, output_amount, energy_used, workers_used, produced_at) VALUES (:factory_id,:user_id,:input_r,:output_r,:input_amount,:output_amount,:energy_used,:workers_used,NOW())')->execute([
                'factory_id' => $factoryId,
                'user_id' => $userId,
                'input_r' => $factory['input_resource_id'] ?: null,
                'output_r' => $factory['output_resource_id'],
                'input_amount' => max(0, $inputNeed),
                'output_amount' => $output,
                'energy_used' => $factory['base_energy_cost'],
                'workers_used' => $factory['base_workers'],
            ]);

            $this->db->commit();
            $this->autoLevelUp($userId);
            return ['ok' => true, 'message' => 'Fabrika üretimi tamamlandı: +' . $output];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => $e->getMessage() ?: 'Fabrika üretimi başarısız.'];
        }
    }

    public function resourceMarketSnapshot(): array
    {
        $rows = $this->db->query('SELECT r.id, r.name, r.base_price, COALESCE(SUM(cr.stock),0) AS total_stock, COALESCE(SUM(cr.daily_yield),0) AS total_yield, COALESCE(AVG(cr.quality_index),1) AS avg_quality
            FROM resources r
            LEFT JOIN country_resources cr ON cr.resource_id = r.id
            GROUP BY r.id, r.name, r.base_price
            ORDER BY r.id')->fetchAll() ?: [];

        $out = [];
        foreach ($rows as $row) {
            $stock = (float) $row['total_stock'];
            $yield = max(1.0, (float) $row['total_yield']);
            $quality = max(0.7, min(1.6, (float) $row['avg_quality']));
            $scarcity = max(0.5, min(2.2, ($yield * 30) / max(1.0, $stock + 1)));
            $price = round((float) $row['base_price'] * $scarcity * $quality, 2);

            $upsert = $this->db->prepare('INSERT INTO resource_market_prices (resource_id, current_price, scarcity_factor) VALUES (:id, :price, :scarcity) ON DUPLICATE KEY UPDATE current_price = VALUES(current_price), scarcity_factor = VALUES(scarcity_factor), updated_at = NOW()');
            $upsert->execute([
                'id' => $row['id'],
                'price' => $price,
                'scarcity' => round($scarcity, 3),
            ]);

            $out[] = [
                'resource_id' => (int) $row['id'],
                'name' => $row['name'],
                'price' => $price,
                'scarcity_factor' => round($scarcity, 3),
                'total_stock' => (int) $stock,
                'total_yield' => (int) $yield,
            ];
        }

        return $out;
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



    public function nationContext(int $countryId): array
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS player_count FROM users WHERE country_id = :country_id');
        $stmt->execute(['country_id' => $countryId]);
        $playerCount = (int) (($stmt->fetch()['player_count'] ?? 0));

        $avgStmt = $this->db->prepare('SELECT AVG((airport_level * 2) + (industry_level * 3) + (education_level * 2) + (army_level * 2) + (port_level * 2) + (space_level * 4)) AS avg_city_score FROM cities WHERE country_id = :country_id AND is_active = 1');
        $avgStmt->execute(['country_id' => $countryId]);
        $avg = (float) (($avgStmt->fetch()['avg_city_score'] ?? 0));

        $tier = (new StatFormulaService())->nationTier($playerCount, $avg);

        return [
            'country_id' => $countryId,
            'player_count' => $playerCount,
            'avg_city_score' => round($avg, 2),
            'nation_tier' => $tier,
        ];
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
        $stmt = $this->db->prepare('SELECT level, experience, energy_max, energy FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $u = $stmt->fetch();
        if (!$u) {
            return;
        }

        $cfg = (new BalanceConfigService())->get();
        $formula = new StatFormulaService();

        $level = (int) $u['level'];
        $xp = (int) $u['experience'];
        $energyMax = (int) $u['energy_max'];
        $energy = (int) $u['energy'];
        $leveledUp = false;

        while ($xp >= $formula->levelXpRequirement($level, $cfg)) {
            $need = $formula->levelXpRequirement($level, $cfg);
            $xp -= $need;
            $level++;
            $energyMax += (int) $cfg['level_energy_gain'];
            $energy = min($energy + (int) $cfg['level_energy_gain'], $energyMax);
            $leveledUp = true;
        }

        if ($leveledUp) {
            $this->db->prepare('UPDATE users SET level = :level, experience = :experience, energy_max = :energy_max, energy = :energy WHERE id = :id')->execute([
                'level' => $level,
                'experience' => $xp,
                'energy_max' => $energyMax,
                'energy' => $energy,
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
