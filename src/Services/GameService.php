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
        $this->closeExpiredElections((int) $user['country_id']);
        $this->closeExpiredLaws((int) $user['country_id']);
        $this->processBorderQueue();

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
            'active_war' => $this->activeWarForCountry((int) $user['country_id']),
            'war_reports' => $this->recentWarReports((int) $user['country_id']),
            'my_party' => $this->myParty($userId),
            'parties' => $this->partiesByCountry((int) $user['country_id']),
            'election' => $this->electionSnapshot((int) $user['country_id']),
            'parliament_laws' => $this->parliamentSnapshot((int) $user['country_id']),
            'government' => $this->governmentSnapshot((int) $user['country_id']),
            'my_permissions' => $this->userPermissions($userId, (int) $user['country_id']),
            'travel_permits' => $this->userTravelPermits($userId),
            'citizenship_requests' => $this->userCitizenshipRequests($userId),
            'travel_policies' => $this->travelPolicies(),
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

    public function startWar(int $userId, int $defenderCountryId): array
    {
        $userStmt = $this->db->prepare('SELECT id, country_id, level, war_power FROM users WHERE id = :id LIMIT 1');
        $userStmt->execute(['id' => $userId]);
        $user = $userStmt->fetch();
        if (!$user) {
            return ['ok' => false, 'message' => 'Kullanıcı bulunamadı.'];
        }

        $attackerCountryId = (int) $user['country_id'];
        if ($defenderCountryId <= 0 || $defenderCountryId === $attackerCountryId) {
            return ['ok' => false, 'message' => 'Geçerli bir düşman ülke seçmelisin.'];
        }
        if ((int) $user['level'] < 3 || (int) $user['war_power'] < 20) {
            return ['ok' => false, 'message' => 'Savaş başlatmak için seviye 3 ve en az 20 savaş gücü gerekli.'];
        }

        $countryStmt = $this->db->prepare('SELECT id FROM countries WHERE id = :id LIMIT 1');
        $countryStmt->execute(['id' => $defenderCountryId]);
        if (!$countryStmt->fetch()) {
            return ['ok' => false, 'message' => 'Hedef ülke bulunamadı.'];
        }

        $activeStmt = $this->db->prepare('SELECT id FROM wars WHERE status = "active" AND (
                attacker_country_id = :attacker_country_id OR
                defender_country_id = :attacker_country_id OR
                attacker_country_id = :defender_country_id OR
                defender_country_id = :defender_country_id
            ) LIMIT 1');
        $activeStmt->execute([
            'attacker_country_id' => $attackerCountryId,
            'defender_country_id' => $defenderCountryId,
        ]);
        if ($activeStmt->fetch()) {
            return ['ok' => false, 'message' => 'Ülkelerden biri zaten aktif savaşta.'];
        }

        $scoreToWin = $this->warRules()['score_to_win'];
        $ins = $this->db->prepare('INSERT INTO wars (attacker_country_id, defender_country_id, started_by_user_id, status, score_to_win, started_at, created_at, updated_at) VALUES (:attacker_country_id, :defender_country_id, :started_by_user_id, "active", :score_to_win, NOW(), NOW(), NOW())');
        $ins->execute([
            'attacker_country_id' => $attackerCountryId,
            'defender_country_id' => $defenderCountryId,
            'started_by_user_id' => $userId,
            'score_to_win' => $scoreToWin,
        ]);

        return ['ok' => true, 'message' => 'Savaş ilan edildi. Cephe açıldı!'];
    }

    public function warAttack(int $userId, int $warId): array
    {
        $rules = $this->warRules();

        $this->db->beginTransaction();
        try {
            $userStmt = $this->db->prepare('SELECT id, country_id, energy, strength, education, endurance, level FROM users WHERE id = :id LIMIT 1 FOR UPDATE');
            $userStmt->execute(['id' => $userId]);
            $user = $userStmt->fetch();
            if (!$user) {
                throw new \RuntimeException('Kullanıcı bulunamadı.');
            }
            if ((int) $user['energy'] < $rules['attack_energy_cost']) {
                throw new \RuntimeException('Savaş saldırısı için enerji yetersiz.');
            }

            $warStmt = $this->db->prepare('SELECT * FROM wars WHERE id = :id AND status = "active" LIMIT 1 FOR UPDATE');
            $warStmt->execute(['id' => $warId]);
            $war = $warStmt->fetch();
            if (!$war) {
                throw new \RuntimeException('Aktif savaş bulunamadı.');
            }

            $attackerCountryId = (int) $war['attacker_country_id'];
            $defenderCountryId = (int) $war['defender_country_id'];
            $userCountryId = (int) $user['country_id'];
            if ($userCountryId !== $attackerCountryId && $userCountryId !== $defenderCountryId) {
                throw new \RuntimeException('Bu savaşa katılım yetkin yok.');
            }

            $cooldownStmt = $this->db->prepare('SELECT created_at FROM war_battles WHERE war_id = :war_id AND attacker_user_id = :user_id ORDER BY id DESC LIMIT 1');
            $cooldownStmt->execute(['war_id' => $warId, 'user_id' => $userId]);
            $lastAttack = $cooldownStmt->fetch();
            if ($lastAttack) {
                $lastAt = new \DateTimeImmutable((string) $lastAttack['created_at']);
                $nextAllowed = $lastAt->modify('+' . $rules['attack_cooldown_seconds'] . ' seconds');
                if ($nextAllowed > new \DateTimeImmutable('now')) {
                    throw new \RuntimeException('Saldırı cooldown aktif. Biraz bekle.');
                }
            }

            $nation = $this->nationContext($userCountryId);
            $statPower = ((int) $user['strength'] * 1.9) + ((int) $user['education'] * 1.1) + ((int) $user['endurance'] * 1.6) + ((int) $user['level'] * 2);
            $tierBoost = 1 + (((int) $nation['nation_tier'] - 1) * 0.08);
            $roll = random_int($rules['damage_min'], $rules['damage_max']);
            $damage = (int) max(1, floor(($statPower + $roll) * $tierBoost / 8));

            $newAttackerScore = (int) $war['attacker_score'];
            $newDefenderScore = (int) $war['defender_score'];
            if ($userCountryId === $attackerCountryId) {
                $newAttackerScore += $damage;
            } else {
                $newDefenderScore += $damage;
            }

            $winnerCountryId = null;
            $status = 'active';
            $endedAt = null;
            if ($newAttackerScore >= (int) $war['score_to_win']) {
                $winnerCountryId = $attackerCountryId;
                $status = 'ended';
                $endedAt = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
            } elseif ($newDefenderScore >= (int) $war['score_to_win']) {
                $winnerCountryId = $defenderCountryId;
                $status = 'ended';
                $endedAt = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
            }

            $updateWar = $this->db->prepare('UPDATE wars SET attacker_score = :attacker_score, defender_score = :defender_score, status = :status, winner_country_id = :winner_country_id, ended_at = :ended_at, updated_at = NOW() WHERE id = :id');
            $updateWar->execute([
                'attacker_score' => $newAttackerScore,
                'defender_score' => $newDefenderScore,
                'status' => $status,
                'winner_country_id' => $winnerCountryId,
                'ended_at' => $endedAt,
                'id' => $warId,
            ]);

            $xpGain = max(10, (int) floor($damage / 3));
            $this->db->prepare('UPDATE users SET energy = energy - :energy_cost, experience = experience + :xp_gain, war_power = war_power + 1 WHERE id = :id')->execute([
                'energy_cost' => $rules['attack_energy_cost'],
                'xp_gain' => $xpGain,
                'id' => $userId,
            ]);

            $this->db->prepare('INSERT INTO war_battles (war_id, attacker_user_id, attacker_country_id, defender_country_id, roll_value, damage, attacker_score_after, defender_score_after, created_at) VALUES (:war_id,:attacker_user_id,:attacker_country_id,:defender_country_id,:roll_value,:damage,:attacker_score_after,:defender_score_after,NOW())')->execute([
                'war_id' => $warId,
                'attacker_user_id' => $userId,
                'attacker_country_id' => $userCountryId,
                'defender_country_id' => $userCountryId === $attackerCountryId ? $defenderCountryId : $attackerCountryId,
                'roll_value' => $roll,
                'damage' => $damage,
                'attacker_score_after' => $newAttackerScore,
                'defender_score_after' => $newDefenderScore,
            ]);

            $this->db->commit();
            $this->autoLevelUp($userId);
            $msg = 'Saldırı başarılı. +' . $damage . ' savaş skoru, +' . $xpGain . ' XP';
            if ($status === 'ended') {
                $msg .= ' | Savaş bitti.';
            }
            return ['ok' => true, 'message' => $msg];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => $e->getMessage() ?: 'Savaş saldırısı başarısız.'];
        }
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

        $layers = $this->db->query('SELECT c.code AS country_code, wml.layer_key, wml.color_hex, wml.intensity, wml.note
            FROM world_map_layers wml
            JOIN countries c ON c.id = wml.country_id
            ORDER BY wml.layer_key, c.code')->fetchAll() ?: [];

        $pois = $this->db->query('SELECT cp.id, cp.poi_type, cp.title, cp.description, ci.lat, ci.lng, ci.name AS city_name, c.code AS country_code, c.name AS country_name
            FROM city_points_of_interest cp
            JOIN cities ci ON ci.id = cp.city_id
            JOIN countries c ON c.id = ci.country_id
            ORDER BY cp.id DESC')->fetchAll() ?: [];

        $countryResources = $this->db->query('SELECT c.code, c.name AS country_name, r.resource_key, r.name AS resource_name, cr.daily_yield
            FROM country_resources cr
            JOIN countries c ON c.id = cr.country_id
            JOIN resources r ON r.id = cr.resource_id
            ORDER BY c.code, r.id')->fetchAll() ?: [];

        return ['cities' => $cities, 'country_resources' => $countryResources, 'layers' => $layers, 'pois' => $pois];
    }

    public function activeWarForCountry(int $countryId): ?array
    {
        $stmt = $this->db->prepare('SELECT w.*, ca.name AS attacker_country_name, cd.name AS defender_country_name, cw.name AS winner_country_name
            FROM wars w
            JOIN countries ca ON ca.id = w.attacker_country_id
            JOIN countries cd ON cd.id = w.defender_country_id
            LEFT JOIN countries cw ON cw.id = w.winner_country_id
            WHERE w.status = "active" AND (w.attacker_country_id = :country_id OR w.defender_country_id = :country_id)
            ORDER BY w.id DESC LIMIT 1');
        $stmt->execute(['country_id' => $countryId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function recentWarReports(int $countryId, int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = $this->db->prepare('SELECT wb.id, wb.war_id, wb.attacker_user_id, wb.attacker_country_id, wb.defender_country_id, wb.roll_value, wb.damage, wb.attacker_score_after, wb.defender_score_after, wb.created_at,
                u.username AS attacker_user_name,
                ca.name AS attacker_country_name,
                cd.name AS defender_country_name
            FROM war_battles wb
            JOIN wars w ON w.id = wb.war_id
            JOIN users u ON u.id = wb.attacker_user_id
            JOIN countries ca ON ca.id = wb.attacker_country_id
            JOIN countries cd ON cd.id = wb.defender_country_id
            WHERE w.attacker_country_id = :country_id OR w.defender_country_id = :country_id
            ORDER BY wb.id DESC LIMIT ' . $limit);
        $stmt->execute(['country_id' => $countryId]);
        return $stmt->fetchAll() ?: [];
    }

    public function warRules(): array
    {
        $defaults = [
            'war_attack_energy_cost' => '280',
            'war_attack_cooldown_seconds' => '45',
            'war_damage_min' => '40',
            'war_damage_max' => '160',
            'war_score_to_win' => '1000',
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

        $minDamage = max(1, min(500, (int) $map['war_damage_min']));
        $maxDamage = max($minDamage, min(1200, (int) $map['war_damage_max']));

        return [
            'attack_energy_cost' => max(100, min(1000, (int) $map['war_attack_energy_cost'])),
            'attack_cooldown_seconds' => max(0, min(600, (int) $map['war_attack_cooldown_seconds'])),
            'damage_min' => $minDamage,
            'damage_max' => $maxDamage,
            'score_to_win' => max(200, min(10000, (int) $map['war_score_to_win'])),
        ];
    }

    public function createParty(int $userId, string $name, string $ideology): array
    {
        $name = trim($name);
        $ideology = trim($ideology);
        if (mb_strlen($name) < 3 || mb_strlen($ideology) < 3) {
            return ['ok' => false, 'message' => 'Parti adı ve ideoloji en az 3 karakter olmalı.'];
        }

        $userStmt = $this->db->prepare('SELECT id, country_id, gold FROM users WHERE id = :id LIMIT 1');
        $userStmt->execute(['id' => $userId]);
        $user = $userStmt->fetch();
        if (!$user) {
            return ['ok' => false, 'message' => 'Kullanıcı bulunamadı.'];
        }
        if ($this->myParty($userId)) {
            return ['ok' => false, 'message' => 'Zaten bir partiye üyesin.'];
        }

        $cost = $this->politicsRules()['party_create_gold_cost'];
        if ((float) $user['gold'] < $cost) {
            return ['ok' => false, 'message' => 'Parti kurmak için ' . number_format($cost, 2, ',', '.') . ' gold gerekli.'];
        }

        $existsStmt = $this->db->prepare('SELECT id FROM parties WHERE country_id = :country_id AND name = :name LIMIT 1');
        $existsStmt->execute(['country_id' => $user['country_id'], 'name' => $name]);
        if ($existsStmt->fetch()) {
            return ['ok' => false, 'message' => 'Bu isimde parti zaten var.'];
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare('UPDATE users SET gold = gold - :gold WHERE id = :id')->execute(['gold' => $cost, 'id' => $userId]);
            $this->db->prepare('INSERT INTO parties (country_id, founder_id, name, ideology, created_at) VALUES (:country_id,:founder_id,:name,:ideology,NOW())')->execute([
                'country_id' => $user['country_id'],
                'founder_id' => $userId,
                'name' => $name,
                'ideology' => $ideology,
            ]);
            $partyId = (int) $this->db->lastInsertId();
            $this->db->prepare('INSERT INTO party_members (party_id, user_id, role_name, joined_at) VALUES (:party_id, :user_id, "founder", NOW())')->execute([
                'party_id' => $partyId,
                'user_id' => $userId,
            ]);
            $this->db->commit();
            return ['ok' => true, 'message' => 'Parti kuruldu: ' . $name];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => 'Parti kurulamadı.'];
        }
    }

    public function joinParty(int $userId, int $partyId): array
    {
        if ($partyId <= 0) {
            return ['ok' => false, 'message' => 'Geçersiz parti.'];
        }
        if ($this->myParty($userId)) {
            return ['ok' => false, 'message' => 'Önce mevcut partinden ayrılmalısın.'];
        }

        $userStmt = $this->db->prepare('SELECT id, country_id FROM users WHERE id = :id LIMIT 1');
        $userStmt->execute(['id' => $userId]);
        $user = $userStmt->fetch();
        if (!$user) {
            return ['ok' => false, 'message' => 'Kullanıcı bulunamadı.'];
        }

        $partyStmt = $this->db->prepare('SELECT id, country_id, name FROM parties WHERE id = :id LIMIT 1');
        $partyStmt->execute(['id' => $partyId]);
        $party = $partyStmt->fetch();
        if (!$party || (int) $party['country_id'] !== (int) $user['country_id']) {
            return ['ok' => false, 'message' => 'Sadece kendi ülkenin partilerine katılabilirsin.'];
        }

        $this->db->prepare('INSERT INTO party_members (party_id, user_id, role_name, joined_at) VALUES (:party_id, :user_id, "member", NOW())')->execute([
            'party_id' => $partyId,
            'user_id' => $userId,
        ]);

        return ['ok' => true, 'message' => $party['name'] . ' partisine katıldın.'];
    }

    public function leaveParty(int $userId): array
    {
        $party = $this->myParty($userId);
        if (!$party) {
            return ['ok' => false, 'message' => 'Üye olduğun parti yok.'];
        }
        if (($party['my_role'] ?? 'member') === 'founder') {
            return ['ok' => false, 'message' => 'Kurucu olduğun partiden çıkamazsın.'];
        }

        $stmt = $this->db->prepare('DELETE FROM party_members WHERE party_id = :party_id AND user_id = :user_id');
        $stmt->execute(['party_id' => $party['id'], 'user_id' => $userId]);
        return ['ok' => true, 'message' => 'Partiden ayrıldın.'];
    }

    public function openElection(int $userId): array
    {
        $party = $this->myParty($userId);
        if (!$party) {
            return ['ok' => false, 'message' => 'Seçim açmak için bir partide olmalısın.'];
        }

        $userStmt = $this->db->prepare('SELECT id, country_id, level FROM users WHERE id = :id LIMIT 1');
        $userStmt->execute(['id' => $userId]);
        $user = $userStmt->fetch();
        if (!$user) {
            return ['ok' => false, 'message' => 'Kullanıcı bulunamadı.'];
        }
        if ((int) $user['level'] < 5) {
            return ['ok' => false, 'message' => 'Seçim açmak için en az seviye 5 olmalısın.'];
        }
        if (!in_array((string) ($party['my_role'] ?? 'member'), ['founder', 'leader'], true)) {
            return ['ok' => false, 'message' => 'Seçim açmak için parti yöneticisi olmalısın.'];
        }

        $openStmt = $this->db->prepare('SELECT id FROM elections WHERE country_id = :country_id AND status = "open" LIMIT 1');
        $openStmt->execute(['country_id' => $user['country_id']]);
        if ($openStmt->fetch()) {
            return ['ok' => false, 'message' => 'Bu ülkede zaten açık seçim var.'];
        }

        $hours = $this->politicsRules()['election_default_duration_hours'];
        $ins = $this->db->prepare('INSERT INTO elections (country_id, system, starts_at, ends_at, status, created_at) VALUES (:country_id, "republic", NOW(), DATE_ADD(NOW(), INTERVAL :hours HOUR), "open", NOW())');
        $ins->execute(['country_id' => $user['country_id'], 'hours' => $hours]);

        return ['ok' => true, 'message' => 'Seçim açıldı.'];
    }

    public function voteElection(int $userId, int $electionId, int $partyId): array
    {
        if ($electionId <= 0 || $partyId <= 0) {
            return ['ok' => false, 'message' => 'Geçersiz seçim oyu.'];
        }
        $rules = $this->politicsRules();

        $this->db->beginTransaction();
        try {
            $userStmt = $this->db->prepare('SELECT id, country_id, energy FROM users WHERE id = :id LIMIT 1 FOR UPDATE');
            $userStmt->execute(['id' => $userId]);
            $user = $userStmt->fetch();
            if (!$user) {
                throw new \RuntimeException('Kullanıcı bulunamadı.');
            }
            if ((int) $user['energy'] < $rules['election_vote_energy_cost']) {
                throw new \RuntimeException('Oy kullanmak için enerji yetersiz.');
            }

            $electionStmt = $this->db->prepare('SELECT * FROM elections WHERE id = :id AND status = "open" LIMIT 1 FOR UPDATE');
            $electionStmt->execute(['id' => $electionId]);
            $election = $electionStmt->fetch();
            if (!$election) {
                throw new \RuntimeException('Açık seçim bulunamadı.');
            }
            if ((int) $election['country_id'] !== (int) $user['country_id']) {
                throw new \RuntimeException('Sadece kendi ülke seçiminde oy kullanabilirsin.');
            }

            $partyStmt = $this->db->prepare('SELECT id FROM parties WHERE id = :id AND country_id = :country_id LIMIT 1');
            $partyStmt->execute(['id' => $partyId, 'country_id' => $user['country_id']]);
            if (!$partyStmt->fetch()) {
                throw new \RuntimeException('Seçilen parti geçersiz.');
            }

            $this->db->prepare('INSERT INTO election_votes (election_id, voter_id, party_id, created_at) VALUES (:election_id,:voter_id,:party_id,NOW())')->execute([
                'election_id' => $electionId,
                'voter_id' => $userId,
                'party_id' => $partyId,
            ]);

            $this->db->prepare('UPDATE users SET energy = energy - :energy, experience = experience + 8 WHERE id = :id')->execute([
                'energy' => $rules['election_vote_energy_cost'],
                'id' => $userId,
            ]);

            $this->db->commit();
            return ['ok' => true, 'message' => 'Oyun başarıyla kaydedildi.'];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => $e->getMessage() ?: 'Oy verme başarısız.'];
        }
    }

    public function proposeLaw(int $userId, string $title, string $body): array
    {
        $title = trim($title);
        $body = trim($body);
        if (mb_strlen($title) < 5 || mb_strlen($body) < 10) {
            return ['ok' => false, 'message' => 'Kanun başlığı ve metni çok kısa.'];
        }

        $party = $this->myParty($userId);
        if (!$party) {
            return ['ok' => false, 'message' => 'Kanun önermek için parti üyesi olmalısın.'];
        }
        if (!in_array((string) ($party['my_role'] ?? 'member'), ['founder', 'leader'], true)) {
            return ['ok' => false, 'message' => 'Kanun önermek için parti yöneticisi olmalısın.'];
        }

        $userStmt = $this->db->prepare('SELECT country_id FROM users WHERE id = :id LIMIT 1');
        $userStmt->execute(['id' => $userId]);
        $user = $userStmt->fetch();
        if (!$user) {
            return ['ok' => false, 'message' => 'Kullanıcı bulunamadı.'];
        }

        $hours = $this->politicsRules()['law_default_duration_hours'];
        $stmt = $this->db->prepare('INSERT INTO parliament_laws (country_id, proposer_user_id, title, body, status, ends_at, created_at, updated_at) VALUES (:country_id,:proposer_user_id,:title,:body,"open",DATE_ADD(NOW(), INTERVAL :hours HOUR),NOW(),NOW())');
        $stmt->execute([
            'country_id' => $user['country_id'],
            'proposer_user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'hours' => $hours,
        ]);

        return ['ok' => true, 'message' => 'Kanun teklifi meclise sunuldu.'];
    }

    public function voteLaw(int $userId, int $lawId, string $vote): array
    {
        if ($lawId <= 0 || !in_array($vote, ['yes', 'no'], true)) {
            return ['ok' => false, 'message' => 'Geçersiz kanun oyu.'];
        }
        $rules = $this->politicsRules();

        $this->db->beginTransaction();
        try {
            $userStmt = $this->db->prepare('SELECT id, country_id, energy FROM users WHERE id = :id LIMIT 1 FOR UPDATE');
            $userStmt->execute(['id' => $userId]);
            $user = $userStmt->fetch();
            if (!$user) {
                throw new \RuntimeException('Kullanıcı bulunamadı.');
            }
            if ((int) $user['energy'] < $rules['law_vote_energy_cost']) {
                throw new \RuntimeException('Meclis oyu için enerji yetersiz.');
            }

            $lawStmt = $this->db->prepare('SELECT * FROM parliament_laws WHERE id = :id AND status = "open" LIMIT 1 FOR UPDATE');
            $lawStmt->execute(['id' => $lawId]);
            $law = $lawStmt->fetch();
            if (!$law) {
                throw new \RuntimeException('Açık kanun teklifi bulunamadı.');
            }
            if ((int) $law['country_id'] !== (int) $user['country_id']) {
                throw new \RuntimeException('Sadece kendi ülkenin kanunlarında oy kullanabilirsin.');
            }

            $this->db->prepare('INSERT INTO parliament_law_votes (law_id, voter_user_id, vote, created_at) VALUES (:law_id,:voter_user_id,:vote,NOW())')->execute([
                'law_id' => $lawId,
                'voter_user_id' => $userId,
                'vote' => $vote,
            ]);

            $updateSql = $vote === 'yes'
                ? 'UPDATE parliament_laws SET yes_votes = yes_votes + 1, updated_at = NOW() WHERE id = :id'
                : 'UPDATE parliament_laws SET no_votes = no_votes + 1, updated_at = NOW() WHERE id = :id';
            $this->db->prepare($updateSql)->execute(['id' => $lawId]);

            $this->db->prepare('UPDATE users SET energy = energy - :energy, experience = experience + 5 WHERE id = :id')->execute([
                'energy' => $rules['law_vote_energy_cost'],
                'id' => $userId,
            ]);

            $this->db->commit();
            return ['ok' => true, 'message' => 'Kanun oyu kaydedildi.'];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => $e->getMessage() ?: 'Kanun oylaması başarısız.'];
        }
    }

    public function partiesByCountry(int $countryId): array
    {
        $stmt = $this->db->prepare('SELECT p.id, p.name, p.ideology, p.founder_id, p.created_at, COUNT(pm.user_id) AS member_count
            FROM parties p
            LEFT JOIN party_members pm ON pm.party_id = p.id
            WHERE p.country_id = :country_id
            GROUP BY p.id, p.name, p.ideology, p.founder_id, p.created_at
            ORDER BY member_count DESC, p.name ASC');
        $stmt->execute(['country_id' => $countryId]);
        return $stmt->fetchAll() ?: [];
    }

    public function myParty(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT p.id, p.country_id, p.name, p.ideology, pm.role_name AS my_role
            FROM party_members pm
            JOIN parties p ON p.id = pm.party_id
            WHERE pm.user_id = :user_id
            LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function electionSnapshot(int $countryId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM elections WHERE country_id = :country_id AND status = "open" ORDER BY id DESC LIMIT 1');
        $stmt->execute(['country_id' => $countryId]);
        $election = $stmt->fetch();
        if (!$election) {
            return null;
        }

        $votesStmt = $this->db->prepare('SELECT p.id, p.name, COUNT(ev.voter_id) AS vote_count
            FROM parties p
            LEFT JOIN election_votes ev ON ev.party_id = p.id AND ev.election_id = :election_id
            WHERE p.country_id = :country_id
            GROUP BY p.id, p.name
            ORDER BY vote_count DESC, p.name ASC');
        $votesStmt->execute([
            'election_id' => $election['id'],
            'country_id' => $countryId,
        ]);

        $election['parties'] = $votesStmt->fetchAll() ?: [];
        return $election;
    }

    public function parliamentSnapshot(int $countryId): array
    {
        $stmt = $this->db->prepare('SELECT pl.id, pl.title, pl.status, pl.yes_votes, pl.no_votes, pl.ends_at, pl.created_at, u.username AS proposer_name
            FROM parliament_laws pl
            JOIN users u ON u.id = pl.proposer_user_id
            WHERE pl.country_id = :country_id
            ORDER BY pl.id DESC
            LIMIT 20');
        $stmt->execute(['country_id' => $countryId]);
        return $stmt->fetchAll() ?: [];
    }

    public function politicsRules(): array
    {
        $defaults = [
            'election_default_duration_hours' => '24',
            'election_vote_energy_cost' => '120',
            'party_create_gold_cost' => '75',
            'law_default_duration_hours' => '24',
            'law_vote_energy_cost' => '60',
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
            'election_default_duration_hours' => max(1, min(168, (int) $map['election_default_duration_hours'])),
            'election_vote_energy_cost' => max(10, min(600, (int) $map['election_vote_energy_cost'])),
            'party_create_gold_cost' => max(0.0, min(10000.0, (float) $map['party_create_gold_cost'])),
            'law_default_duration_hours' => max(1, min(168, (int) $map['law_default_duration_hours'])),
            'law_vote_energy_cost' => max(10, min(600, (int) $map['law_vote_energy_cost'])),
        ];
    }

    public function closeExpiredElections(int $countryId): void
    {
        $expiredStmt = $this->db->prepare('SELECT id FROM elections WHERE country_id = :country_id AND status = "open" AND ends_at <= NOW()');
        $expiredStmt->execute(['country_id' => $countryId]);
        $expired = $expiredStmt->fetchAll() ?: [];
        foreach ($expired as $row) {
            $electionId = (int) $row['id'];
            $winnerStmt = $this->db->prepare('SELECT party_id, COUNT(*) AS vote_count FROM election_votes WHERE election_id = :election_id GROUP BY party_id ORDER BY vote_count DESC, party_id ASC LIMIT 1');
            $winnerStmt->execute(['election_id' => $electionId]);
            $winner = $winnerStmt->fetch();

            $this->db->beginTransaction();
            try {
                $this->db->prepare('UPDATE elections SET status = "closed" WHERE id = :id')->execute(['id' => $electionId]);
                if ($winner) {
                    $leaderStmt = $this->db->prepare('SELECT user_id FROM party_members WHERE party_id = :party_id ORDER BY role_name = "founder" DESC, joined_at ASC LIMIT 1');
                    $leaderStmt->execute(['party_id' => $winner['party_id']]);
                    $leader = $leaderStmt->fetch();
                    if ($leader) {
                        $this->db->prepare('INSERT INTO country_government_roles (country_id, user_id, role_key, assigned_at) VALUES (:country_id,:user_id,"president",NOW()) ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), assigned_at = NOW()')->execute([
                            'country_id' => $countryId,
                            'user_id' => $leader['user_id'],
                        ]);
                    }
                }
                $this->db->commit();
            } catch (\Throwable) {
                $this->db->rollBack();
            }
        }
    }

    public function closeExpiredLaws(int $countryId): void
    {
        $stmt = $this->db->prepare('UPDATE parliament_laws
            SET status = CASE WHEN yes_votes > no_votes THEN "accepted" ELSE "rejected" END, updated_at = NOW()
            WHERE country_id = :country_id AND status = "open" AND ends_at <= NOW()');
        $stmt->execute(['country_id' => $countryId]);
    }

    public function assignGovernmentRole(int $actorUserId, int $targetUserId, string $roleKey): array
    {
        $allowedRoles = ['minister_economy', 'minister_defense', 'minister_interior'];
        if (!in_array($roleKey, $allowedRoles, true)) {
            return ['ok' => false, 'message' => 'Geçersiz bakanlık rolü.'];
        }

        $actorStmt = $this->db->prepare('SELECT id, country_id FROM users WHERE id = :id LIMIT 1');
        $actorStmt->execute(['id' => $actorUserId]);
        $actor = $actorStmt->fetch();
        if (!$actor) {
            return ['ok' => false, 'message' => 'Yetkili kullanıcı bulunamadı.'];
        }

        if (!$this->hasPermission($actorUserId, (int) $actor['country_id'], 'gov.assign_roles')) {
            return ['ok' => false, 'message' => 'Rol atama yetkin yok.'];
        }

        $targetStmt = $this->db->prepare('SELECT id, country_id FROM users WHERE id = :id LIMIT 1');
        $targetStmt->execute(['id' => $targetUserId]);
        $target = $targetStmt->fetch();
        if (!$target || (int) $target['country_id'] !== (int) $actor['country_id']) {
            return ['ok' => false, 'message' => 'Aynı ülkeden hedef oyuncu seçmelisin.'];
        }

        $stmt = $this->db->prepare('INSERT INTO country_government_roles (country_id, user_id, role_key, assigned_at) VALUES (:country_id,:user_id,:role_key,NOW()) ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), assigned_at = NOW()');
        $stmt->execute([
            'country_id' => $actor['country_id'],
            'user_id' => $targetUserId,
            'role_key' => $roleKey,
        ]);

        $this->logMinistryAction((int) $actor['country_id'], $actorUserId, 'president', 'gov.assign_role', [
            'target_user_id' => $targetUserId,
            'role_key' => $roleKey,
        ]);

        return ['ok' => true, 'message' => 'Bakanlık rolü atandı.'];
    }

    public function ministryAction(int $actorUserId, string $actionKey, array $payload): array
    {
        $actorStmt = $this->db->prepare('SELECT id, country_id FROM users WHERE id = :id LIMIT 1');
        $actorStmt->execute(['id' => $actorUserId]);
        $actor = $actorStmt->fetch();
        if (!$actor) {
            return ['ok' => false, 'message' => 'Kullanıcı bulunamadı.'];
        }

        $countryId = (int) $actor['country_id'];
        if ($actionKey === 'market.adjust_tax') {
            if (!$this->hasPermission($actorUserId, $countryId, 'gov.market.adjust_tax')) {
                return ['ok' => false, 'message' => 'Pazar vergisi düzenleme yetkin yok.'];
            }

            $tax = max(0.0, min(30.0, (float) ($payload['buyer_tax_percent'] ?? 0)));
            $commission = max(0.0, min(30.0, (float) ($payload['seller_commission_percent'] ?? 0)));

            $this->upsertSetting('market_buyer_tax_percent', (string) $tax);
            $this->upsertSetting('market_seller_commission_percent', (string) $commission);

            $this->logMinistryAction($countryId, $actorUserId, $this->primaryGovernmentRole($actorUserId, $countryId), 'market.adjust_tax', [
                'buyer_tax_percent' => $tax,
                'seller_commission_percent' => $commission,
            ]);

            return ['ok' => true, 'message' => 'Pazar vergi/komisyon ayarı güncellendi.'];
        }

        if ($actionKey === 'war.adjust_score_to_win') {
            if (!$this->hasPermission($actorUserId, $countryId, 'gov.war.adjust_score_to_win')) {
                return ['ok' => false, 'message' => 'Savaş skor hedefi ayarlama yetkin yok.'];
            }

            $score = max(200, min(10000, (int) ($payload['score_to_win'] ?? 1000)));
            $this->upsertSetting('war_score_to_win', (string) $score);

            $this->logMinistryAction($countryId, $actorUserId, $this->primaryGovernmentRole($actorUserId, $countryId), 'war.adjust_score_to_win', [
                'score_to_win' => $score,
            ]);

            return ['ok' => true, 'message' => 'Savaş skor hedefi güncellendi.'];
        }

        return ['ok' => false, 'message' => 'Bilinmeyen bakanlık aksiyonu.'];
    }

    public function governmentSnapshot(int $countryId): array
    {
        $rolesStmt = $this->db->prepare('SELECT cgr.role_key, cgr.user_id, u.username, cgr.assigned_at
            FROM country_government_roles cgr
            JOIN users u ON u.id = cgr.user_id
            WHERE cgr.country_id = :country_id
            ORDER BY cgr.role_key');
        $rolesStmt->execute(['country_id' => $countryId]);
        $roles = $rolesStmt->fetchAll() ?: [];

        $actionsStmt = $this->db->prepare('SELECT mal.id, mal.role_key, mal.action_key, mal.payload_json, mal.created_at, u.username AS actor_name
            FROM ministry_action_logs mal
            JOIN users u ON u.id = mal.actor_user_id
            WHERE mal.country_id = :country_id
            ORDER BY mal.id DESC
            LIMIT 20');
        $actionsStmt->execute(['country_id' => $countryId]);
        $actions = $actionsStmt->fetchAll() ?: [];

        return [
            'roles' => $roles,
            'actions' => $actions,
        ];
    }

    public function userPermissions(int $userId, int $countryId): array
    {
        $stmt = $this->db->prepare('SELECT grp.permission_key
            FROM country_government_roles cgr
            JOIN government_role_permissions grp ON grp.role_key = cgr.role_key
            WHERE cgr.country_id = :country_id AND cgr.user_id = :user_id');
        $stmt->execute([
            'country_id' => $countryId,
            'user_id' => $userId,
        ]);
        $rows = $stmt->fetchAll() ?: [];
        return array_values(array_unique(array_map(static fn (array $row): string => (string) $row['permission_key'], $rows)));
    }

    private function hasPermission(int $userId, int $countryId, string $permissionKey): bool
    {
        $permissions = $this->userPermissions($userId, $countryId);
        return in_array($permissionKey, $permissions, true);
    }

    private function primaryGovernmentRole(int $userId, int $countryId): string
    {
        $stmt = $this->db->prepare('SELECT role_key FROM country_government_roles WHERE country_id = :country_id AND user_id = :user_id ORDER BY role_key = "president" DESC LIMIT 1');
        $stmt->execute(['country_id' => $countryId, 'user_id' => $userId]);
        $row = $stmt->fetch();
        return (string) ($row['role_key'] ?? 'minister');
    }

    private function logMinistryAction(int $countryId, int $actorUserId, string $roleKey, string $actionKey, array $payload): void
    {
        $stmt = $this->db->prepare('INSERT INTO ministry_action_logs (country_id, actor_user_id, role_key, action_key, payload_json, created_at)
            VALUES (:country_id,:actor_user_id,:role_key,:action_key,:payload_json,NOW())');
        $stmt->execute([
            'country_id' => $countryId,
            'actor_user_id' => $actorUserId,
            'role_key' => $roleKey,
            'action_key' => $actionKey,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function upsertSetting(string $key, string $value): void
    {
        $stmt = $this->db->prepare('INSERT INTO settings (`key`, `value`) VALUES (:key, :value) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW()');
        $stmt->execute(['key' => $key, 'value' => $value]);
    }

    public function requestTravelPermit(int $userId, int $toCountryId): array
    {
        if ($toCountryId <= 0) {
            return ['ok' => false, 'message' => 'Geçersiz hedef ülke.'];
        }

        $userStmt = $this->db->prepare('SELECT id, country_id, level FROM users WHERE id = :id LIMIT 1');
        $userStmt->execute(['id' => $userId]);
        $user = $userStmt->fetch();
        if (!$user) {
            return ['ok' => false, 'message' => 'Kullanıcı bulunamadı.'];
        }
        if ((int) $user['country_id'] === $toCountryId) {
            return ['ok' => false, 'message' => 'Zaten bu ülkedesin.'];
        }

        $policyStmt = $this->db->prepare('SELECT visa_required, visa_fee, min_level, permit_duration_hours FROM country_travel_policies WHERE country_id = :country_id LIMIT 1');
        $policyStmt->execute(['country_id' => $toCountryId]);
        $policy = $policyStmt->fetch();
        if (!$policy) {
            return ['ok' => false, 'message' => 'Hedef ülke seyahat politikası bulunamadı.'];
        }
        if ((int) $user['level'] < (int) $policy['min_level']) {
            return ['ok' => false, 'message' => 'Bu ülke için minimum seviye şartı sağlanmıyor.'];
        }

        if ((int) $policy['visa_required'] === 0) {
            return ['ok' => true, 'message' => 'Bu ülke vizesiz geçiş veriyor. Doğrudan şehir seçip taşınabilirsin.'];
        }

        $existingStmt = $this->db->prepare('SELECT id FROM residence_permits WHERE user_id = :user_id AND to_country_id = :to_country_id AND status = "pending" LIMIT 1');
        $existingStmt->execute(['user_id' => $userId, 'to_country_id' => $toCountryId]);
        if ($existingStmt->fetch()) {
            return ['ok' => false, 'message' => 'Bu ülke için zaten bekleyen iznin var.'];
        }

        $stmt = $this->db->prepare('INSERT INTO residence_permits (user_id, from_country_id, to_country_id, status, visa_fee, requested_at, note) VALUES (:user_id,:from_country_id,:to_country_id,"pending",:visa_fee,NOW(),:note)');
        $stmt->execute([
            'user_id' => $userId,
            'from_country_id' => $user['country_id'],
            'to_country_id' => $toCountryId,
            'visa_fee' => $policy['visa_fee'],
            'note' => 'Süre: ' . (int) $policy['permit_duration_hours'] . ' saat',
        ]);

        return ['ok' => true, 'message' => 'Oturum/geçiş izni talebi gönderildi.'];
    }

    public function decideTravelPermit(int $actorUserId, int $permitId, string $decision): array
    {
        if ($permitId <= 0 || !in_array($decision, ['approved', 'rejected'], true)) {
            return ['ok' => false, 'message' => 'Geçersiz izin kararı.'];
        }

        $actorStmt = $this->db->prepare('SELECT id, country_id FROM users WHERE id = :id LIMIT 1');
        $actorStmt->execute(['id' => $actorUserId]);
        $actor = $actorStmt->fetch();
        if (!$actor) {
            return ['ok' => false, 'message' => 'Yetkili kullanıcı bulunamadı.'];
        }

        if (!$this->hasPermission($actorUserId, (int) $actor['country_id'], 'gov.permit.decide')) {
            return ['ok' => false, 'message' => 'İzin kararı verme yetkin yok.'];
        }

        $this->db->beginTransaction();
        try {
            $permitStmt = $this->db->prepare('SELECT * FROM residence_permits WHERE id = :id AND status = "pending" LIMIT 1 FOR UPDATE');
            $permitStmt->execute(['id' => $permitId]);
            $permit = $permitStmt->fetch();
            if (!$permit) {
                throw new \RuntimeException('Bekleyen izin bulunamadı.');
            }
            if ((int) $permit['to_country_id'] !== (int) $actor['country_id']) {
                throw new \RuntimeException('Sadece kendi ülkenin izin taleplerini kararlandırabilirsin.');
            }

            $validUntil = null;
            if ($decision === 'approved') {
                $policyStmt = $this->db->prepare('SELECT permit_duration_hours FROM country_travel_policies WHERE country_id = :country_id LIMIT 1');
                $policyStmt->execute(['country_id' => $permit['to_country_id']]);
                $policy = $policyStmt->fetch();
                $durationHours = max(1, (int) ($policy['permit_duration_hours'] ?? 72));
                $validUntil = (new \DateTimeImmutable('now'))->modify('+' . $durationHours . ' hours')->format('Y-m-d H:i:s');
            }

            $this->db->prepare('UPDATE residence_permits SET status = :status, decided_by_user_id = :decided_by_user_id, decided_at = NOW(), approved_at = CASE WHEN :status = "approved" THEN NOW() ELSE approved_at END, valid_until = :valid_until WHERE id = :id')->execute([
                'status' => $decision,
                'decided_by_user_id' => $actorUserId,
                'valid_until' => $validUntil,
                'id' => $permitId,
            ]);

            if ($decision === 'approved' && $validUntil !== null) {
                $this->enqueueBorderEvent('permit.expire_return', (int) $permit['user_id'], (int) $permit['to_country_id'], $validUntil, [
                    'permit_id' => (int) $permit['id'],
                    'home_country_id' => (int) $permit['from_country_id'],
                ]);
            }

            $this->logMinistryAction((int) $actor['country_id'], $actorUserId, $this->primaryGovernmentRole($actorUserId, (int) $actor['country_id']), 'permit.' . $decision, [
                'permit_id' => $permitId,
                'user_id' => $permit['user_id'],
            ]);

            $this->db->commit();
            return ['ok' => true, 'message' => $decision === 'approved' ? 'İzin onaylandı.' : 'İzin reddedildi.'];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => $e->getMessage() ?: 'İzin kararı başarısız.'];
        }
    }

    public function travelToCity(int $userId, int $cityId): array
    {
        if ($cityId <= 0) {
            return ['ok' => false, 'message' => 'Geçersiz şehir.'];
        }

        $userStmt = $this->db->prepare('SELECT id, country_id, city_id, gold FROM users WHERE id = :id LIMIT 1 FOR UPDATE');
        $cityStmt = $this->db->prepare('SELECT id, country_id, name, is_active FROM cities WHERE id = :id LIMIT 1');

        $this->db->beginTransaction();
        try {
            $userStmt->execute(['id' => $userId]);
            $user = $userStmt->fetch();
            if (!$user) {
                throw new \RuntimeException('Kullanıcı bulunamadı.');
            }

            $cityStmt->execute(['id' => $cityId]);
            $city = $cityStmt->fetch();
            if (!$city || (int) $city['is_active'] !== 1) {
                throw new \RuntimeException('Hedef şehir aktif değil.');
            }

            $toCountryId = (int) $city['country_id'];
            $fromCountryId = (int) $user['country_id'];
            $permitId = null;

            if ($toCountryId !== $fromCountryId) {
                $policyStmt = $this->db->prepare('SELECT visa_required, visa_fee FROM country_travel_policies WHERE country_id = :country_id LIMIT 1');
                $policyStmt->execute(['country_id' => $toCountryId]);
                $policy = $policyStmt->fetch();
                if (!$policy) {
                    throw new \RuntimeException('Hedef ülke politikası bulunamadı.');
                }

                if ((int) $policy['visa_required'] === 1) {
                    $permitStmt = $this->db->prepare('SELECT id, visa_fee, valid_until FROM residence_permits WHERE user_id = :user_id AND to_country_id = :to_country_id AND status = "approved" ORDER BY id ASC LIMIT 1 FOR UPDATE');
                    $permitStmt->execute(['user_id' => $userId, 'to_country_id' => $toCountryId]);
                    $permit = $permitStmt->fetch();
                    if (!$permit) {
                        throw new \RuntimeException('Bu ülkeye geçiş için onaylı izin gerekli.');
                    }

                    if (!empty($permit['valid_until']) && new \DateTimeImmutable((string) $permit['valid_until']) < new \DateTimeImmutable('now')) {
                        $this->db->prepare('UPDATE residence_permits SET status = "violated", violated_at = NOW(), violation_reason = :reason WHERE id = :id')->execute([
                            'reason' => 'Süresi dolmuş izinle sınır geçiş denemesi.',
                            'id' => $permit['id'],
                        ]);
                        throw new \RuntimeException('İzin süresi dolmuş. Yeni başvuru gerekli.');
                    }

                    $visaFee = (float) $permit['visa_fee'];
                    if ((float) $user['gold'] < $visaFee) {
                        throw new \RuntimeException('Vize ücreti için yeterli gold yok.');
                    }

                    $this->db->prepare('UPDATE users SET gold = gold - :visa_fee WHERE id = :id')->execute([
                        'visa_fee' => $visaFee,
                        'id' => $userId,
                    ]);
                    $this->db->prepare('UPDATE residence_permits SET status = "used" WHERE id = :id')->execute(['id' => $permit['id']]);
                    $permitId = (int) $permit['id'];
                }
            }

            $this->db->prepare('UPDATE users SET country_id = :country_id, city_id = :city_id WHERE id = :id')->execute([
                'country_id' => $toCountryId,
                'city_id' => $cityId,
                'id' => $userId,
            ]);

            $this->db->prepare('INSERT INTO travel_logs (user_id, from_city_id, to_city_id, from_country_id, to_country_id, permit_id, traveled_at) VALUES (:user_id,:from_city_id,:to_city_id,:from_country_id,:to_country_id,:permit_id,NOW())')->execute([
                'user_id' => $userId,
                'from_city_id' => $user['city_id'],
                'to_city_id' => $cityId,
                'from_country_id' => $fromCountryId,
                'to_country_id' => $toCountryId,
                'permit_id' => $permitId,
            ]);

            $this->db->commit();
            return ['ok' => true, 'message' => 'Yeni şehir: ' . $city['name']];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => $e->getMessage() ?: 'Seyahat başarısız.'];
        }
    }

    public function userTravelPermits(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT rp.id, rp.status, rp.visa_fee, rp.requested_at, rp.decided_at, rp.valid_until, rp.violated_at, rp.violation_reason, c1.name AS from_country_name, c2.name AS to_country_name
            FROM residence_permits rp
            JOIN countries c1 ON c1.id = rp.from_country_id
            JOIN countries c2 ON c2.id = rp.to_country_id
            WHERE rp.user_id = :user_id
            ORDER BY rp.id DESC
            LIMIT 20');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function travelPolicies(): array
    {
        $stmt = $this->db->query('SELECT ctp.country_id, c.name AS country_name, ctp.visa_required, ctp.visa_fee, ctp.min_level, ctp.permit_duration_hours
            FROM country_travel_policies ctp
            JOIN countries c ON c.id = ctp.country_id
            ORDER BY c.name');
        return $stmt->fetchAll() ?: [];
    }

    public function requestCitizenship(int $userId, int $toCountryId): array
    {
        if ($toCountryId <= 0) {
            return ['ok' => false, 'message' => 'Geçersiz hedef ülke.'];
        }

        $rules = $this->citizenshipRules();
        $userStmt = $this->db->prepare('SELECT id, country_id, level, gold FROM users WHERE id = :id LIMIT 1');
        $userStmt->execute(['id' => $userId]);
        $user = $userStmt->fetch();
        if (!$user) {
            return ['ok' => false, 'message' => 'Kullanıcı bulunamadı.'];
        }
        if ((int) $user['country_id'] === $toCountryId) {
            return ['ok' => false, 'message' => 'Zaten bu ülkenin vatandaşısın.'];
        }
        if ((int) $user['level'] < $rules['min_level']) {
            return ['ok' => false, 'message' => 'Vatandaşlık başvurusu için minimum seviye ' . $rules['min_level']];
        }
        if ((float) $user['gold'] < $rules['gold_cost']) {
            return ['ok' => false, 'message' => 'Vatandaşlık başvuru maliyeti için gold yetersiz.'];
        }

        $existingStmt = $this->db->prepare('SELECT id FROM citizenship_requests WHERE user_id = :user_id AND to_country_id = :to_country_id AND status = "pending" LIMIT 1');
        $existingStmt->execute(['user_id' => $userId, 'to_country_id' => $toCountryId]);
        if ($existingStmt->fetch()) {
            return ['ok' => false, 'message' => 'Bu ülke için bekleyen vatandaşlık başvurun var.'];
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare('UPDATE users SET gold = gold - :gold_cost WHERE id = :id')->execute([
                'gold_cost' => $rules['gold_cost'],
                'id' => $userId,
            ]);
            $this->db->prepare('INSERT INTO citizenship_requests (user_id, from_country_id, to_country_id, status, requested_at) VALUES (:user_id,:from_country_id,:to_country_id,"pending",NOW())')->execute([
                'user_id' => $userId,
                'from_country_id' => $user['country_id'],
                'to_country_id' => $toCountryId,
            ]);
            $this->db->commit();
            return ['ok' => true, 'message' => 'Vatandaşlık başvurusu gönderildi.'];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => 'Vatandaşlık başvurusu başarısız.'];
        }
    }

    public function decideCitizenship(int $actorUserId, int $requestId, string $decision): array
    {
        if ($requestId <= 0 || !in_array($decision, ['approved', 'rejected'], true)) {
            return ['ok' => false, 'message' => 'Geçersiz vatandaşlık kararı.'];
        }
        $actorStmt = $this->db->prepare('SELECT id, country_id FROM users WHERE id = :id LIMIT 1');
        $actorStmt->execute(['id' => $actorUserId]);
        $actor = $actorStmt->fetch();
        if (!$actor) {
            return ['ok' => false, 'message' => 'Yetkili kullanıcı bulunamadı.'];
        }
        if (!$this->hasPermission($actorUserId, (int) $actor['country_id'], 'gov.permit.decide')) {
            return ['ok' => false, 'message' => 'Vatandaşlık kararı için yetkin yok.'];
        }

        $this->db->beginTransaction();
        try {
            $reqStmt = $this->db->prepare('SELECT * FROM citizenship_requests WHERE id = :id AND status = "pending" LIMIT 1 FOR UPDATE');
            $reqStmt->execute(['id' => $requestId]);
            $request = $reqStmt->fetch();
            if (!$request) {
                throw new \RuntimeException('Bekleyen vatandaşlık başvurusu yok.');
            }
            if ((int) $request['to_country_id'] !== (int) $actor['country_id']) {
                throw new \RuntimeException('Sadece kendi ülkenin vatandaşlık başvurusunu kararlandırabilirsin.');
            }

            $this->db->prepare('UPDATE citizenship_requests SET status = :status, decided_at = NOW(), decided_by_user_id = :actor_user_id WHERE id = :id')->execute([
                'status' => $decision,
                'actor_user_id' => $actorUserId,
                'id' => $requestId,
            ]);

            if ($decision === 'approved') {
                $cityStmt = $this->db->prepare('SELECT id FROM cities WHERE country_id = :country_id AND is_active = 1 ORDER BY base_population DESC LIMIT 1');
                $cityStmt->execute(['country_id' => $request['to_country_id']]);
                $city = $cityStmt->fetch();
                if (!$city) {
                    throw new \RuntimeException('Hedef ülkede aktif şehir bulunamadı.');
                }
                $this->db->prepare('UPDATE users SET country_id = :country_id, city_id = :city_id WHERE id = :id')->execute([
                    'country_id' => $request['to_country_id'],
                    'city_id' => $city['id'],
                    'id' => $request['user_id'],
                ]);
            }

            $this->logMinistryAction((int) $actor['country_id'], $actorUserId, $this->primaryGovernmentRole($actorUserId, (int) $actor['country_id']), 'citizenship.' . $decision, [
                'request_id' => $requestId,
                'user_id' => $request['user_id'],
            ]);

            $this->db->commit();
            return ['ok' => true, 'message' => $decision === 'approved' ? 'Vatandaşlık onaylandı.' : 'Vatandaşlık reddedildi.'];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => $e->getMessage() ?: 'Vatandaşlık kararı başarısız.'];
        }
    }

    public function userCitizenshipRequests(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT cr.id, cr.status, cr.requested_at, cr.decided_at, c1.name AS from_country_name, c2.name AS to_country_name
            FROM citizenship_requests cr
            JOIN countries c1 ON c1.id = cr.from_country_id
            JOIN countries c2 ON c2.id = cr.to_country_id
            WHERE cr.user_id = :user_id
            ORDER BY cr.id DESC
            LIMIT 20');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function processBorderQueue(): void
    {
        $batchSize = $this->queueBatchSize();
        $eventsStmt = $this->db->prepare('SELECT * FROM border_event_queue WHERE status = "pending" AND execute_at <= NOW() ORDER BY id ASC LIMIT ' . $batchSize);
        $eventsStmt->execute();
        $events = $eventsStmt->fetchAll() ?: [];

        foreach ($events as $event) {
            $eventId = (int) $event['id'];
            $this->db->beginTransaction();
            try {
                $this->db->prepare('UPDATE border_event_queue SET status = "processing", attempts = attempts + 1 WHERE id = :id')->execute(['id' => $eventId]);
                $payload = json_decode((string) ($event['payload_json'] ?? '{}'), true);

                if (($event['event_type'] ?? '') === 'permit.expire_return') {
                    $this->handlePermitExpireReturn((int) $event['user_id'], (int) ($payload['permit_id'] ?? 0), (int) ($payload['home_country_id'] ?? 0));
                }

                $this->db->prepare('UPDATE border_event_queue SET status = "done", processed_at = NOW() WHERE id = :id')->execute(['id' => $eventId]);
                $this->db->commit();
            } catch (\Throwable $e) {
                $this->db->rollBack();
                $this->db->prepare('UPDATE border_event_queue SET status = "failed", last_error = :last_error, processed_at = NOW() WHERE id = :id')->execute([
                    'last_error' => mb_substr($e->getMessage(), 0, 250),
                    'id' => $eventId,
                ]);
                $this->logAppError('error', 'border.queue', $e->getMessage(), ['event_id' => $eventId]);
            }
        }
    }

    private function enqueueBorderEvent(string $eventType, int $userId, int $countryId, string $executeAt, array $payload): void
    {
        $stmt = $this->db->prepare('INSERT INTO border_event_queue (event_type, user_id, country_id, execute_at, payload_json, status, created_at) VALUES (:event_type,:user_id,:country_id,:execute_at,:payload_json,"pending",NOW())');
        $stmt->execute([
            'event_type' => $eventType,
            'user_id' => $userId,
            'country_id' => $countryId,
            'execute_at' => $executeAt,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function handlePermitExpireReturn(int $userId, int $permitId, int $homeCountryId): void
    {
        $permitStmt = $this->db->prepare('SELECT * FROM residence_permits WHERE id = :id LIMIT 1 FOR UPDATE');
        $permitStmt->execute(['id' => $permitId]);
        $permit = $permitStmt->fetch();
        if (!$permit) {
            return;
        }
        if (!in_array((string) $permit['status'], ['used', 'approved'], true)) {
            return;
        }

        $userStmt = $this->db->prepare('SELECT city_id, country_id FROM users WHERE id = :id LIMIT 1 FOR UPDATE');
        $userStmt->execute(['id' => $userId]);
        $user = $userStmt->fetch();
        if (!$user) {
            return;
        }

        if ((int) $user['country_id'] !== (int) $permit['to_country_id']) {
            return;
        }

        $homeCityStmt = $this->db->prepare('SELECT id FROM cities WHERE country_id = :country_id AND is_active = 1 ORDER BY base_population DESC LIMIT 1');
        $homeCityStmt->execute(['country_id' => $homeCountryId]);
        $homeCity = $homeCityStmt->fetch();
        if (!$homeCity) {
            throw new \RuntimeException('Otomatik dönüş için aktif şehir bulunamadı.');
        }

        $this->db->prepare('UPDATE users SET country_id = :country_id, city_id = :city_id WHERE id = :id')->execute([
            'country_id' => $homeCountryId,
            'city_id' => $homeCity['id'],
            'id' => $userId,
        ]);
        $this->db->prepare('UPDATE residence_permits SET status = "expired" WHERE id = :id')->execute(['id' => $permitId]);
    }

    private function citizenshipRules(): array
    {
        $stmt = $this->db->prepare('SELECT `key`, `value` FROM settings WHERE `key` IN ("citizenship_min_level","citizenship_gold_cost")');
        $stmt->execute();
        $rows = $stmt->fetchAll() ?: [];
        $map = ['citizenship_min_level' => '8', 'citizenship_gold_cost' => '120'];
        foreach ($rows as $row) {
            $map[(string) $row['key']] = (string) $row['value'];
        }

        return [
            'min_level' => max(1, min(100, (int) $map['citizenship_min_level'])),
            'gold_cost' => max(0.0, min(50000.0, (float) $map['citizenship_gold_cost'])),
        ];
    }

    private function queueBatchSize(): int
    {
        $stmt = $this->db->prepare('SELECT `value` FROM settings WHERE `key` = "border_queue_batch_size" LIMIT 1');
        $stmt->execute();
        $row = $stmt->fetch();
        return max(1, min(200, (int) ($row['value'] ?? 50)));
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
            'map_layers' => $this->db->query('SELECT wml.*, c.name AS country_name, c.code AS country_code FROM world_map_layers wml JOIN countries c ON c.id = wml.country_id ORDER BY wml.layer_key, c.name')->fetchAll() ?: [],
            'city_pois' => $this->db->query('SELECT cp.*, ci.name AS city_name, c.name AS country_name FROM city_points_of_interest cp JOIN cities ci ON ci.id = cp.city_id JOIN countries c ON c.id = ci.country_id ORDER BY cp.id DESC')->fetchAll() ?: [],
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

    public function updateCountry(int $countryId, string $code, string $name, string $flag, bool $isActive): array
    {
        $code = strtoupper(trim($code));
        $name = trim($name);
        $flag = trim($flag);
        if ($countryId <= 0 || strlen($code) !== 2 || $name === '') {
            return ['ok' => false, 'message' => 'Ülke güncelleme verisi geçersiz.'];
        }

        $stmt = $this->db->prepare('UPDATE countries SET code = :code, map_code = :map_code, name = :name, flag_emoji = :flag_emoji, is_active = :is_active, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $countryId,
            'code' => $code,
            'map_code' => $code,
            'name' => $name,
            'flag_emoji' => $flag !== '' ? $flag : '🏳️',
            'is_active' => $isActive ? 1 : 0,
        ]);

        return ['ok' => true, 'message' => 'Ülke güncellendi.'];
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

    public function updateCity(int $cityId, int $countryId, string $name, float $lat, float $lng, bool $isActive): array
    {
        $name = trim($name);
        if ($cityId <= 0 || $countryId <= 0 || $name === '') {
            return ['ok' => false, 'message' => 'Şehir güncelleme verisi geçersiz.'];
        }
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return ['ok' => false, 'message' => 'Koordinatlar geçersiz.'];
        }

        $stmt = $this->db->prepare('UPDATE cities SET country_id = :country_id, name = :name, lat = :lat, lng = :lng, is_active = :is_active, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $cityId,
            'country_id' => $countryId,
            'name' => $name,
            'lat' => $lat,
            'lng' => $lng,
            'is_active' => $isActive ? 1 : 0,
        ]);

        return ['ok' => true, 'message' => 'Şehir güncellendi.'];
    }

    public function addCountryResource(int $countryId, int $resourceId, int $dailyYield): array
    {
        if ($countryId <= 0 || $resourceId <= 0 || $dailyYield < 0) {
            return ['ok' => false, 'message' => 'Kaynak dağılımı bilgisi geçersiz.'];
        }

        $stmt = $this->db->prepare('INSERT INTO country_resources (country_id, resource_id, daily_yield, stock) VALUES (:c, :r, :y, :s) ON DUPLICATE KEY UPDATE daily_yield = VALUES(daily_yield)');
        $stmt->execute(['c' => $countryId, 'r' => $resourceId, 'y' => $dailyYield, 's' => $dailyYield * 20]);
        return ['ok' => true, 'message' => 'Kaynak dağılımı güncellendi.'];
    }

    public function deleteCountryResource(int $countryResourceId): array
    {
        if ($countryResourceId <= 0) {
            return ['ok' => false, 'message' => 'Silinecek dağılım bulunamadı.'];
        }

        $stmt = $this->db->prepare('DELETE FROM country_resources WHERE id = :id');
        $stmt->execute(['id' => $countryResourceId]);
        return ['ok' => true, 'message' => 'Kaynak dağılımı silindi.'];
    }

    public function addMapLayer(int $countryId, string $layerKey, string $colorHex, float $intensity, ?string $note = null): array
    {
        $layerKey = trim($layerKey);
        $colorHex = strtoupper(trim($colorHex));
        if ($countryId <= 0 || $layerKey === '' || !preg_match('/^#[0-9A-F]{6}$/', $colorHex)) {
            return ['ok' => false, 'message' => 'Harita katman bilgileri geçersiz.'];
        }

        $intensity = max(0.1, min(5.0, $intensity));
        $stmt = $this->db->prepare('INSERT INTO world_map_layers (country_id, layer_key, color_hex, intensity, note, created_at, updated_at) VALUES (:country_id,:layer_key,:color_hex,:intensity,:note,NOW(),NOW()) ON DUPLICATE KEY UPDATE color_hex = VALUES(color_hex), intensity = VALUES(intensity), note = VALUES(note), updated_at = NOW()');
        $stmt->execute([
            'country_id' => $countryId,
            'layer_key' => $layerKey,
            'color_hex' => $colorHex,
            'intensity' => $intensity,
            'note' => $note !== null ? trim($note) : null,
        ]);

        return ['ok' => true, 'message' => 'Harita katmanı kaydedildi.'];
    }

    public function deleteMapLayer(int $mapLayerId): array
    {
        if ($mapLayerId <= 0) {
            return ['ok' => false, 'message' => 'Silinecek katman seçilmedi.'];
        }

        $stmt = $this->db->prepare('DELETE FROM world_map_layers WHERE id = :id');
        $stmt->execute(['id' => $mapLayerId]);
        return ['ok' => true, 'message' => 'Harita katmanı silindi.'];
    }

    public function addCityPoi(int $cityId, string $poiType, string $title, ?string $description = null): array
    {
        $poiType = trim($poiType);
        $title = trim($title);
        if ($cityId <= 0 || $poiType === '' || mb_strlen($title) < 2) {
            return ['ok' => false, 'message' => 'POI bilgileri geçersiz.'];
        }

        $stmt = $this->db->prepare('INSERT INTO city_points_of_interest (city_id, poi_type, title, description, created_at) VALUES (:city_id,:poi_type,:title,:description,NOW())');
        $stmt->execute([
            'city_id' => $cityId,
            'poi_type' => $poiType,
            'title' => $title,
            'description' => $description !== null ? trim($description) : null,
        ]);

        return ['ok' => true, 'message' => 'Şehir POI kaydedildi.'];
    }

    public function deleteCityPoi(int $cityPoiId): array
    {
        if ($cityPoiId <= 0) {
            return ['ok' => false, 'message' => 'Silinecek POI seçilmedi.'];
        }

        $stmt = $this->db->prepare('DELETE FROM city_points_of_interest WHERE id = :id');
        $stmt->execute(['id' => $cityPoiId]);
        return ['ok' => true, 'message' => 'Şehir POI silindi.'];
    }

    public function exportWorldBuilder(): array
    {
        return [
            'exported_at' => (new \DateTimeImmutable('now'))->format(DATE_ATOM),
            'countries' => $this->db->query('SELECT code, name, flag_emoji, is_active FROM countries ORDER BY id')->fetchAll() ?: [],
            'cities' => $this->db->query('SELECT c.code AS country_code, ci.name, ci.lat, ci.lng, ci.base_population, ci.is_active FROM cities ci JOIN countries c ON c.id = ci.country_id ORDER BY c.code, ci.name')->fetchAll() ?: [],
            'country_resources' => $this->db->query('SELECT c.code AS country_code, r.resource_key, cr.daily_yield, cr.stock FROM country_resources cr JOIN countries c ON c.id = cr.country_id JOIN resources r ON r.id = cr.resource_id ORDER BY c.code, r.resource_key')->fetchAll() ?: [],
            'map_layers' => $this->db->query('SELECT c.code AS country_code, wml.layer_key, wml.color_hex, wml.intensity, wml.note FROM world_map_layers wml JOIN countries c ON c.id = wml.country_id ORDER BY wml.layer_key, c.code')->fetchAll() ?: [],
            'city_pois' => $this->db->query('SELECT c.code AS country_code, ci.name AS city_name, cp.poi_type, cp.title, cp.description FROM city_points_of_interest cp JOIN cities ci ON ci.id = cp.city_id JOIN countries c ON c.id = ci.country_id ORDER BY cp.id')->fetchAll() ?: [],
        ];
    }

    public function importWorldBuilder(string $jsonPayload): array
    {
        $decoded = json_decode($jsonPayload, true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'message' => 'Import JSON formatı geçersiz.'];
        }

        $countries = is_array($decoded['countries'] ?? null) ? $decoded['countries'] : [];
        $cities = is_array($decoded['cities'] ?? null) ? $decoded['cities'] : [];
        $countryResources = is_array($decoded['country_resources'] ?? null) ? $decoded['country_resources'] : [];
        $mapLayers = is_array($decoded['map_layers'] ?? null) ? $decoded['map_layers'] : [];
        $cityPois = is_array($decoded['city_pois'] ?? null) ? $decoded['city_pois'] : [];

        $this->db->beginTransaction();
        try {
            foreach ($countries as $country) {
                $code = strtoupper(trim((string) ($country['code'] ?? '')));
                $name = trim((string) ($country['name'] ?? ''));
                if (strlen($code) !== 2 || $name === '') {
                    continue;
                }
                $this->db->prepare('INSERT INTO countries (code, name, flag_emoji, map_code, is_active, created_at, updated_at) VALUES (:code,:name,:flag,:map_code,:is_active,NOW(),NOW()) ON DUPLICATE KEY UPDATE name = VALUES(name), flag_emoji = VALUES(flag_emoji), map_code = VALUES(map_code), is_active = VALUES(is_active), updated_at = NOW()')
                    ->execute([
                        'code' => $code,
                        'name' => $name,
                        'flag' => trim((string) ($country['flag_emoji'] ?? '🏳️')) ?: '🏳️',
                        'map_code' => $code,
                        'is_active' => (int) ($country['is_active'] ?? 1) === 1 ? 1 : 0,
                    ]);
            }

            foreach ($cities as $city) {
                $countryCode = strtoupper(trim((string) ($city['country_code'] ?? '')));
                $name = trim((string) ($city['name'] ?? ''));
                $lat = (float) ($city['lat'] ?? 0);
                $lng = (float) ($city['lng'] ?? 0);
                if (strlen($countryCode) !== 2 || $name === '' || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                    continue;
                }

                $countryIdStmt = $this->db->prepare('SELECT id FROM countries WHERE code = :code LIMIT 1');
                $countryIdStmt->execute(['code' => $countryCode]);
                $countryId = (int) (($countryIdStmt->fetch()['id'] ?? 0));
                if ($countryId <= 0) {
                    continue;
                }

                $this->db->prepare('INSERT INTO cities (country_id, name, lat, lng, base_population, is_active, created_at, updated_at) VALUES (:country_id,:name,:lat,:lng,:base_population,:is_active,NOW(),NOW()) ON DUPLICATE KEY UPDATE lat = VALUES(lat), lng = VALUES(lng), base_population = VALUES(base_population), is_active = VALUES(is_active), updated_at = NOW()')
                    ->execute([
                        'country_id' => $countryId,
                        'name' => $name,
                        'lat' => $lat,
                        'lng' => $lng,
                        'base_population' => max(0, (int) ($city['base_population'] ?? 0)),
                        'is_active' => (int) ($city['is_active'] ?? 1) === 1 ? 1 : 0,
                    ]);
            }

            foreach ($countryResources as $row) {
                $countryCode = strtoupper(trim((string) ($row['country_code'] ?? '')));
                $resourceKey = trim((string) ($row['resource_key'] ?? ''));
                if (strlen($countryCode) !== 2 || $resourceKey === '') {
                    continue;
                }

                $stmt = $this->db->prepare('INSERT INTO country_resources (country_id, resource_id, daily_yield, stock, created_at, updated_at)
                    SELECT c.id, r.id, :daily_yield, :stock, NOW(), NOW()
                    FROM countries c
                    JOIN resources r ON r.resource_key = :resource_key
                    WHERE c.code = :country_code
                    ON DUPLICATE KEY UPDATE daily_yield = VALUES(daily_yield), stock = VALUES(stock), updated_at = NOW()');
                $stmt->execute([
                    'daily_yield' => max(0, (int) ($row['daily_yield'] ?? 0)),
                    'stock' => max(0, (int) ($row['stock'] ?? 0)),
                    'resource_key' => $resourceKey,
                    'country_code' => $countryCode,
                ]);
            }

            foreach ($mapLayers as $row) {
                $countryCode = strtoupper(trim((string) ($row['country_code'] ?? '')));
                $layerKey = trim((string) ($row['layer_key'] ?? ''));
                $colorHex = strtoupper(trim((string) ($row['color_hex'] ?? '#0D6EFD')));
                if (strlen($countryCode) !== 2 || $layerKey === '' || !preg_match('/^#[0-9A-F]{6}$/', $colorHex)) {
                    continue;
                }

                $stmt = $this->db->prepare('INSERT INTO world_map_layers (country_id, layer_key, color_hex, intensity, note, created_at, updated_at)
                    SELECT c.id, :layer_key, :color_hex, :intensity, :note, NOW(), NOW()
                    FROM countries c
                    WHERE c.code = :country_code
                    ON DUPLICATE KEY UPDATE color_hex = VALUES(color_hex), intensity = VALUES(intensity), note = VALUES(note), updated_at = NOW()');
                $stmt->execute([
                    'layer_key' => $layerKey,
                    'color_hex' => $colorHex,
                    'intensity' => max(0.1, min(5.0, (float) ($row['intensity'] ?? 1))),
                    'note' => trim((string) ($row['note'] ?? '')),
                    'country_code' => $countryCode,
                ]);
            }

            foreach ($cityPois as $poi) {
                $countryCode = strtoupper(trim((string) ($poi['country_code'] ?? '')));
                $cityName = trim((string) ($poi['city_name'] ?? ''));
                $poiType = trim((string) ($poi['poi_type'] ?? ''));
                $title = trim((string) ($poi['title'] ?? ''));
                if (strlen($countryCode) !== 2 || $cityName === '' || $poiType === '' || mb_strlen($title) < 2) {
                    continue;
                }

                $stmt = $this->db->prepare('INSERT INTO city_points_of_interest (city_id, poi_type, title, description, created_at)
                    SELECT ci.id, :poi_type, :title, :description, NOW()
                    FROM cities ci
                    JOIN countries c ON c.id = ci.country_id
                    WHERE c.code = :country_code AND ci.name = :city_name
                    LIMIT 1');
                $stmt->execute([
                    'poi_type' => $poiType,
                    'title' => $title,
                    'description' => trim((string) ($poi['description'] ?? '')),
                    'country_code' => $countryCode,
                    'city_name' => $cityName,
                ]);
            }

            $this->db->commit();
            return ['ok' => true, 'message' => 'World builder import tamamlandı.'];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => $e->getMessage() ?: 'World builder import başarısız.'];
        }
    }

    public function healthSnapshot(): array
    {
        $started = microtime(true);
        $dbOk = false;
        $dbLatencyMs = 0;

        try {
            $dbStart = microtime(true);
            $this->db->query('SELECT 1')->fetchColumn();
            $dbLatencyMs = (int) round((microtime(true) - $dbStart) * 1000);
            $dbOk = true;
        } catch (\Throwable $e) {
            $this->logAppError('error', 'health.db', $e->getMessage(), ['exception' => get_class($e)]);
        }

        $settingsStmt = $this->db->prepare('SELECT `key`, `value` FROM settings WHERE `key` IN ("app_release_channel","app_release_version","monitor_heartbeat_enabled")');
        $settingsStmt->execute();
        $settingsRows = $settingsStmt->fetchAll() ?: [];
        $settings = [];
        foreach ($settingsRows as $row) {
            $settings[(string) $row['key']] = (string) $row['value'];
        }

        $status = $dbOk ? 'ok' : 'degraded';
        $responseMs = (int) round((microtime(true) - $started) * 1000);

        if (($settings['monitor_heartbeat_enabled'] ?? '1') === '1') {
            $this->logHeartbeat($status, $dbOk, (string) ($settings['app_release_version'] ?? '0.1.0'), $responseMs);
        }

        return [
            'ok' => $dbOk,
            'status' => $status,
            'db_ok' => $dbOk,
            'db_latency_ms' => $dbLatencyMs,
            'response_ms' => $responseMs,
            'release_channel' => $settings['app_release_channel'] ?? 'stable',
            'release_version' => $settings['app_release_version'] ?? '0.1.0',
            'server_time' => (new \DateTimeImmutable('now'))->format(DATE_ATOM),
        ];
    }

    private function logHeartbeat(string $status, bool $dbOk, string $version, int $responseMs): void
    {
        $stmt = $this->db->prepare('INSERT INTO app_heartbeat_logs (status, db_ok, app_version, response_ms, created_at) VALUES (:status,:db_ok,:app_version,:response_ms,NOW())');
        $stmt->execute([
            'status' => $status,
            'db_ok' => $dbOk ? 1 : 0,
            'app_version' => $version,
            'response_ms' => max(0, $responseMs),
        ]);
    }

    private function logAppError(string $level, string $contextKey, string $message, ?array $payload = null): void
    {
        try {
            $stmt = $this->db->prepare('INSERT INTO app_error_events (level, context_key, message, payload_json, created_at) VALUES (:level,:context_key,:message,:payload_json,NOW())');
            $stmt->execute([
                'level' => $level,
                'context_key' => $contextKey,
                'message' => mb_substr($message, 0, 4000),
                'payload_json' => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            ]);
        } catch (\Throwable) {
            // silent: avoid recursive logging failures
        }
    }
}
