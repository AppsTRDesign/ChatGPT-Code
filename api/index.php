<?php
require __DIR__ . '/lib/Database.php';
require __DIR__ . '/lib/Jwt.php';
require __DIR__ . '/lib/Response.php';

$config = require __DIR__ . '/config.php';
$db = new Database($config);
$pdo = $db->pdo();

// CORS + auth header forwarding (Apache mod_headers/mod_rewrite yanında .htaccess ile desteklenir)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function read_json(): array
{
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    return is_array($data) ? $data : [];
}

function current_user(PDO $pdo, array $config): ?array
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (strpos($auth, 'Bearer ') !== 0) {
        return null;
    }
    $token = substr($auth, 7);
    $payload = Jwt::decode($token, $config['jwt_secret']);
    if (!$payload || empty($payload['uid'])) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT id, email, display_name, points FROM users WHERE id = ?');
    $stmt->execute([$payload['uid']]);
    return $stmt->fetch();
}

function ensure_user(PDO $pdo, array $config): ?array
{
    $user = current_user($pdo, $config);
    if (!$user) {
        Response::error('Kimlik doğrulama gerekiyor', 401);
        return null;
    }
    return $user;
}

function register(PDO $pdo, array $config): void
{
    $data = read_json();
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $password2 = $data['password_confirm'] ?? '';
    $name = $data['name'] ?? '';
    if (!$email || !$password || !$name) {
        Response::error('email, password ve name zorunlu');
        return;
    }
    if ($password !== $password2 && $password2 !== '') {
        Response::error('Şifreler eşleşmiyor');
        return;
    }
    $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $check->execute([$email]);
    if ($check->fetch()) {
        Response::error('Email zaten kayıtlı', 409);
        return;
    }
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $pdo->prepare('INSERT INTO users (email, password_hash, display_name, points) VALUES (?, ?, ?, ?)')
        ->execute([$email, $hash, $name, $config['initial_points']]);
    $id = (int)$pdo->lastInsertId();
    $token = Jwt::encode(['uid' => $id, 'iat' => time()], $config['jwt_secret']);
    Response::json(['token' => $token, 'user' => ['id' => $id, 'email' => $email, 'display_name' => $name, 'points' => $config['initial_points']]], 201);
}

function login(PDO $pdo, array $config): void
{
    $data = read_json();
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $stmt = $pdo->prepare('SELECT id, email, password_hash, display_name, points FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        Response::error('Geçersiz kullanıcı veya şifre', 401);
        return;
    }
    $token = Jwt::encode(['uid' => (int)$user['id'], 'iat' => time()], $config['jwt_secret']);
    unset($user['password_hash']);
    Response::json(['token' => $token, 'user' => $user]);
}

function forgot_password(PDO $pdo): void
{
    $data = read_json();
    $email = $data['email'] ?? '';
    if (!$email) {
        Response::error('email zorunlu');
        return;
    }
    $pdo->prepare('INSERT INTO contact_messages (email, subject, body) VALUES (?,?,?)')
        ->execute([$email, 'Şifre sıfırlama isteği', 'Kullanıcı sıfırlama talebi gönderdi']);
    Response::json(['queued' => true]);
}

function list_sites(PDO $pdo, int $userId): void
{
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = 25;
    $offset = ($page - 1) * $pageSize;
    $allowedSort = ['created_at', 'name', 'dwell_seconds'];
    $sort = $_GET['sort'] ?? 'created_at';
    $sort = in_array($sort, $allowedSort, true) ? $sort : 'created_at';
    $dir = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
    try {
        // MariaDB/MySQL native prepares do not allow bound LIMIT/OFFSET; cast to int and inline safely.
        $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM sites WHERE user_id = ? ORDER BY ' . $sort . ' ' . $dir
            . ' LIMIT ' . (int)$pageSize . ' OFFSET ' . (int)$offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $sites = $stmt->fetchAll();
        foreach ($sites as &$s) {
            $s['media_actions'] = $s['media_actions'] ? json_decode($s['media_actions'], true) : [];
        }
        $total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
        $pages = max(1, (int)ceil($total / $pageSize));
        Response::json(['items' => $sites, 'page' => $page, 'pages' => $pages, 'total' => $total]);
    } catch (PDOException $e) {
        Response::error('Site listeleme hatası', 500, ['detail' => $e->getMessage()]);
    }
}

function has_exceeded_daily_site(PDO $pdo, int $surferId, int $siteId, int $limit): bool
{
    if ($limit <= 0) {
        return false;
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM surf_sessions WHERE surfer_id = ? AND site_id = ? AND DATE(started_at) = CURDATE()');
    $stmt->execute([$surferId, $siteId]);
    return ((int)$stmt->fetchColumn()) >= $limit;
}

function create_site(PDO $pdo, array $config, array $user): void
{
    $data = read_json();
    $name = trim($data['name'] ?? '');
    $url = trim($data['url'] ?? '');
    $dwell = max(5, (int)($data['dwell_seconds'] ?? 0));
    $googleEnabled = !empty($data['google_enabled']);
    $googleKeyword = trim($data['google_keyword'] ?? '');
    $googleCountry = trim($data['google_country'] ?? 'com');
    $googlePages = max(1, (int)($data['google_pages'] ?? 1));
    $googleDwell = max(5, (int)($data['google_dwell'] ?? $dwell));
    $youtubeEnabled = !empty($data['youtube_enabled']);
    $youtubeKeyword = trim($data['youtube_keyword'] ?? '');
    $youtubeLink = trim($data['youtube_link'] ?? '');
    $youtubePages = max(1, (int)($data['youtube_pages'] ?? 1));
    $youtubeDwell = max(5, (int)($data['youtube_dwell'] ?? $dwell));
    if (!$name || !$url) {
        Response::error('name ve url zorunlu');
        return;
    }
    $reserveCost = $dwell;
    if ($googleEnabled) {
        $reserveCost = $googleDwell + (int)($config['google_task_points'] ?? 50) + ($googlePages * (int)($config['google_page_points'] ?? 10));
    } elseif ($youtubeEnabled) {
        $reserveCost = $youtubeDwell + (int)($config['youtube_task_points'] ?? 50)
            + ($youtubePages * (int)($config['youtube_page_points'] ?? 10));
    }
    if ($user['points'] < $reserveCost) {
        Response::error('Puan yetersiz', 409, ['available' => $user['points']]);
        return;
    }
    $flags = [
        'mobile' => !empty($data['mobile']),
        'realistic' => !empty($data['realistic']),
        'mouse_moves' => !empty($data['mouse_moves']),
        'link_clicks' => !empty($data['link_clicks']),
        'scroll' => !empty($data['scroll']),
        'form_fill' => !empty($data['form_fill']),
        'media' => !empty($data['media']),
    ];
    $mediaActions = json_encode($data['media_actions'] ?? []);
    $pdo->prepare('INSERT INTO sites (user_id, name, url, dwell_seconds, google_enabled, google_keyword, google_country, google_pages, google_dwell, youtube_enabled, youtube_keyword, youtube_link, youtube_pages, youtube_dwell, mobile, realistic, mouse_moves, link_clicks, scroll, form_fill, media, media_actions) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([
            $user['id'], $name, $url, $dwell,
            (int)$googleEnabled, $googleKeyword ?: null, $googleCountry ?: 'com', $googlePages, $googleDwell,
            (int)$youtubeEnabled, $youtubeKeyword ?: null, $youtubeLink ?: null, $youtubePages, $youtubeDwell,
            (int)$flags['mobile'], (int)$flags['realistic'], (int)$flags['mouse_moves'],
            (int)$flags['link_clicks'], (int)$flags['scroll'], (int)$flags['form_fill'], (int)$flags['media'],
            $mediaActions,
        ]);
    $siteId = (int)$pdo->lastInsertId();
    $pdo->prepare('UPDATE users SET points = points - ? WHERE id = ?')->execute([$reserveCost, $user['id']]);
    $pdo->prepare('INSERT INTO point_ledger (user_id, change_amount, reason, meta) VALUES (?,?,?,?)')
        ->execute([$user['id'], -$reserveCost, 'site_reserve', json_encode(['site_id' => $siteId])]);
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $freshUser = $stmt->fetch();
    Response::json(['site_id' => $siteId, 'points' => $freshUser['points']], 201);
}

function delete_site(PDO $pdo, int $userId, int $siteId): void
{
    $stmt = $pdo->prepare('DELETE FROM sites WHERE id = ? AND user_id = ?');
    $stmt->execute([$siteId, $userId]);
    Response::json(['deleted' => $stmt->rowCount() > 0]);
}

function update_site(PDO $pdo, int $userId, int $siteId): void
{
    $data = read_json();
    $name = trim($data['name'] ?? '');
    $url = trim($data['url'] ?? '');
    $dwell = isset($data['dwell_seconds']) ? max(5, (int)$data['dwell_seconds']) : null;
    $stmt = $pdo->prepare('SELECT * FROM sites WHERE id = ? AND user_id = ?');
    $stmt->execute([$siteId, $userId]);
    $site = $stmt->fetch();
    if (!$site) {
        Response::error('Site bulunamadı', 404);
        return;
    }
    $flags = [
        'mobile' => isset($data['mobile']) ? (int)!empty($data['mobile']) : (int)$site['mobile'],
        'realistic' => isset($data['realistic']) ? (int)!empty($data['realistic']) : (int)$site['realistic'],
        'mouse_moves' => isset($data['mouse_moves']) ? (int)!empty($data['mouse_moves']) : (int)$site['mouse_moves'],
        'link_clicks' => isset($data['link_clicks']) ? (int)!empty($data['link_clicks']) : (int)$site['link_clicks'],
        'scroll' => isset($data['scroll']) ? (int)!empty($data['scroll']) : (int)$site['scroll'],
        'form_fill' => isset($data['form_fill']) ? (int)!empty($data['form_fill']) : (int)$site['form_fill'],
        'media' => isset($data['media']) ? (int)!empty($data['media']) : (int)$site['media'],
    ];
    $mediaActions = array_key_exists('media_actions', $data) ? json_encode($data['media_actions']) : $site['media_actions'];
    $pdo->prepare('UPDATE sites SET name = ?, url = ?, dwell_seconds = IFNULL(?, dwell_seconds), google_enabled = ?, google_keyword = ?, google_country = ?, google_pages = ?, google_dwell = ?, youtube_enabled = ?, youtube_keyword = ?, youtube_link = ?, youtube_pages = ?, youtube_dwell = ?, mobile = ?, realistic = ?, mouse_moves = ?, link_clicks = ?, scroll = ?, form_fill = ?, media = ?, media_actions = ? WHERE id = ?')
        ->execute([
            $name ?: $site['name'],
            $url ?: $site['url'],
            $dwell,
            !empty($data['google_enabled']) ? 1 : 0,
            trim($data['google_keyword'] ?? $site['google_keyword']),
            trim($data['google_country'] ?? $site['google_country']),
            max(1, (int)($data['google_pages'] ?? $site['google_pages'] ?? 1)),
            max(5, (int)($data['google_dwell'] ?? $site['google_dwell'] ?? $site['dwell_seconds'])),
            !empty($data['youtube_enabled']) ? 1 : 0,
            trim($data['youtube_keyword'] ?? $site['youtube_keyword']),
            trim($data['youtube_link'] ?? $site['youtube_link']),
            max(1, (int)($data['youtube_pages'] ?? $site['youtube_pages'] ?? 1)),
            max(5, (int)($data['youtube_dwell'] ?? $site['youtube_dwell'] ?? $site['dwell_seconds'])),
            $flags['mobile'],
            $flags['realistic'],
            $flags['mouse_moves'],
            $flags['link_clicks'],
            $flags['scroll'],
            $flags['form_fill'],
            $flags['media'],
            $mediaActions,
            $siteId,
        ]);
    Response::json(['updated' => true]);
}

function start_surf(PDO $pdo, array $user, array $config): void
{
    $stmt = $pdo->prepare('SELECT s.*, u.display_name AS owner_name FROM sites s JOIN users u ON u.id = s.user_id WHERE s.user_id != ? ORDER BY s.created_at DESC');
    $stmt->execute([$user['id']]);
    $sites = $stmt->fetchAll();
    foreach ($sites as $site) {
        if ((int)$site['dwell_seconds'] <= 0) {
            continue;
        }
        if (has_exceeded_daily_site($pdo, (int)$user['id'], (int)$site['id'], (int)($config['max_daily_site_visits'] ?? 0))) {
            continue;
        }
        $mode = 'standard';
        $plannedDwell = (int)$site['dwell_seconds'];
        $plannedPages = 0;
        $spend = $plannedDwell;
        $task = null;
        if (!empty($site['google_enabled'])) {
            $mode = 'google';
            $plannedDwell = max(5, (int)($site['google_dwell'] ?: $site['dwell_seconds']));
            $plannedPages = max(1, (int)($site['google_pages'] ?: 1));
            $spend = $plannedDwell + (int)($config['google_task_points'] ?? 50) + ($plannedPages * (int)($config['google_page_points'] ?? 10));
            $task = [
                'keyword' => $site['google_keyword'] ?? '',
                'country' => $site['google_country'] ?: 'com',
                'pages' => $plannedPages,
                'dwell' => $plannedDwell,
                'site_url' => $site['url'],
            ];
        } elseif (!empty($site['youtube_enabled'])) {
            $mode = 'youtube';
            $plannedDwell = max(5, (int)($site['youtube_dwell'] ?: $site['dwell_seconds']));
            $plannedPages = max(1, (int)($site['youtube_pages'] ?: 1));
            $spend = $plannedDwell + (int)($config['youtube_task_points'] ?? 50) + ($plannedPages * (int)($config['youtube_page_points'] ?? 10));
            $task = [
                'keyword' => $site['youtube_keyword'] ?? '',
                'video_link' => $site['youtube_link'] ?? '',
                'pages' => $plannedPages,
                'dwell' => $plannedDwell,
                'site_url' => $site['url'],
            ];
        }
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT points FROM users WHERE id = ? FOR UPDATE');
            $lock->execute([$site['user_id']]);
            $owner = $lock->fetch();
            if (!$owner || $owner['points'] < $spend) {
                $pdo->rollBack();
                continue;
            }
            $pdo->prepare('UPDATE users SET points = points - ? WHERE id = ?')
                ->execute([$spend, $site['user_id']]);
            $pdo->prepare('INSERT INTO point_ledger (user_id, change_amount, reason, meta) VALUES (?,?,?,?)')
                ->execute([$site['user_id'], -$spend, 'surf_spend', json_encode(['site_id' => $site['id'], 'task' => $mode])]);
            $pdo->prepare('INSERT INTO surf_sessions (site_id, surfer_id, task_mode, planned_dwell, planned_pages, status, started_at) VALUES (?,?,?,?,?,"in_progress",NOW())')
                ->execute([(int)$site['id'], $user['id'], $mode, $plannedDwell, $plannedPages]);
            $sessionId = (int)$pdo->lastInsertId();
            $pdo->commit();
            $site['media_actions'] = $site['media_actions'] ? json_decode($site['media_actions'], true) : [];
            $site['dwell_seconds'] = $plannedDwell;
            $plan = $mode === 'standard' ? build_plan($site) : [];
            $site['media_actions'] = $site['media_actions'] ? json_decode($site['media_actions'], true) : [];
            Response::json([
                'session_id' => $sessionId,
                'site' => $site,
                'plan' => $plan,
                'task_mode' => $mode,
                'task' => $task,
                'planned_pages' => $plannedPages,
            ]);
            return;
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
    Response::error('Gezilecek uygun site bulunamadı', 404);
}

function build_plan(array $site): array
{
    $steps = [['title' => 'Sayfayı aç', 'detail' => $site['url'], 'seconds' => 2]];
    if ($site['mouse_moves']) {
        $steps[] = ['title' => 'Mouse hareketleri', 'detail' => 'Yumuşak gezinme', 'seconds' => 4];
    }
    if ($site['scroll']) {
        $steps[] = ['title' => 'Scroll', 'detail' => 'Aşağı-yukarı 2x', 'seconds' => 3];
    }
    if ($site['link_clicks']) {
        $steps[] = ['title' => 'Rastgele link tıkla', 'detail' => 'Yeni sekme açmadan', 'seconds' => 4];
    }
    if ($site['form_fill']) {
        $steps[] = ['title' => 'Form etkileşimi', 'detail' => 'Input focus + yazma', 'seconds' => 5];
    }
    if ($site['media']) {
        $steps[] = ['title' => 'Medya oynat', 'detail' => 'Video/Ses kontrolü', 'seconds' => 6];
    }
    $steps[] = ['title' => 'Bekleme', 'detail' => 'Toplam süreyi tamamla', 'seconds' => max(5, (int)$site['dwell_seconds'] - 10)];
    return $steps;
}

function clamp_reward(PDO $pdo, int $userId, int $base, array $config): int
{
    $check = function (string $sql, array $params, int $cap) use ($pdo) {
        if ($cap <= 0) {
            return null;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $earned = (int)($stmt->fetchColumn() ?: 0);
        return max(0, $cap - $earned);
    };

    $dailyLeft = $check(
        'SELECT SUM(change_amount) FROM point_ledger WHERE user_id = ? AND reason = "surf_reward" AND DATE(created_at) = CURDATE()',
        [$userId],
        (int)($config['max_daily_reward'] ?? 0)
    );
    $weeklyLeft = $check(
        'SELECT SUM(change_amount) FROM point_ledger WHERE user_id = ? AND reason = "surf_reward" AND YEARWEEK(created_at,1) = YEARWEEK(NOW(),1)',
        [$userId],
        (int)($config['max_weekly_reward'] ?? 0)
    );
    $monthlyLeft = $check(
        'SELECT SUM(change_amount) FROM point_ledger WHERE user_id = ? AND reason = "surf_reward" AND DATE_FORMAT(created_at, "%Y-%m") = DATE_FORMAT(NOW(), "%Y-%m")',
        [$userId],
        (int)($config['max_monthly_reward'] ?? 0)
    );

    $limits = array_filter([$dailyLeft, $weeklyLeft, $monthlyLeft], fn($v) => $v !== null);
    if (empty($limits)) {
        return $base;
    }

    return max(0, min($base, ...$limits));
}

function reward_leftovers(PDO $pdo, int $userId, array $config): array
{
    $calc = function (string $sql, int $cap) use ($pdo, $userId) {
        if ($cap <= 0) {
            return null;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $spent = (int)($stmt->fetchColumn() ?: 0);
        return max(0, $cap - $spent);
    };

    return [
        'daily_left' => $calc('SELECT SUM(change_amount) FROM point_ledger WHERE user_id = ? AND reason = "surf_reward" AND DATE(created_at) = CURDATE()', (int)($config['max_daily_reward'] ?? 0)),
        'weekly_left' => $calc('SELECT SUM(change_amount) FROM point_ledger WHERE user_id = ? AND reason = "surf_reward" AND YEARWEEK(created_at,1) = YEARWEEK(NOW(),1)', (int)($config['max_weekly_reward'] ?? 0)),
        'monthly_left' => $calc('SELECT SUM(change_amount) FROM point_ledger WHERE user_id = ? AND reason = "surf_reward" AND DATE_FORMAT(created_at, "%Y-%m") = DATE_FORMAT(NOW(), "%Y-%m")', (int)($config['max_monthly_reward'] ?? 0)),
    ];
}

function complete_surf(PDO $pdo, array $user, array $config): void
{
    $data = read_json();
    $sessionId = (int)($data['session_id'] ?? 0);
    $consumed = (int)($data['consumed_seconds'] ?? 0);
    $telemetry = $data['telemetry'] ?? [];
    if (!$sessionId) {
        Response::error('session_id zorunlu');
        return;
    }
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT ss.*, s.dwell_seconds, s.google_pages, s.youtube_pages FROM surf_sessions ss JOIN sites s ON s.id = ss.site_id WHERE ss.id = ? AND ss.surfer_id = ? FOR UPDATE');
    $stmt->execute([$sessionId, $user['id']]);
    $session = $stmt->fetch();
    if (!$session || $session['status'] !== 'in_progress') {
        $pdo->rollBack();
        Response::error('Aktif oturum bulunamadı', 404);
        return;
    }
    $pdo->prepare('UPDATE surf_sessions SET status = "completed", completed_at = NOW() WHERE id = ?')
        ->execute([$sessionId]);
    $plannedDwell = (int)($session['planned_dwell'] ?: $session['dwell_seconds']);
    $mode = $session['task_mode'] ?: 'standard';
    $pagesVisited = (int)($telemetry['pages_visited'] ?? $session['planned_pages'] ?? 0);
    $reward = $plannedDwell;
    if ($mode === 'google') {
        $reward += (int)($config['google_task_points'] ?? 50);
        $reward += ($pagesVisited ?: (int)($session['google_pages'] ?? 1)) * (int)($config['google_page_points'] ?? 10);
    } elseif ($mode === 'youtube') {
        $reward += (int)($config['youtube_task_points'] ?? 50);
        $reward += ($pagesVisited ?: (int)($session['youtube_pages'] ?? 1)) * (int)($config['youtube_page_points'] ?? 10);
    }
    $reward = clamp_reward($pdo, (int)$user['id'], $reward, $config);
    if ($reward > 0) {
        $pdo->prepare('UPDATE users SET points = points + ? WHERE id = ?')->execute([$reward, $user['id']]);
        $pdo->prepare('INSERT INTO point_ledger (user_id, change_amount, reason, meta) VALUES (?,?,?,?)')
            ->execute([
                $user['id'],
                $reward,
                'surf_reward',
                json_encode([
                    'session_id' => $sessionId,
                    'consumed' => $consumed,
                    'site_id' => $session['site_id'],
                    'task' => $mode,
                    'pages' => $pagesVisited,
                ]),
            ]);
    }
    persist_site_stats($pdo, (int)$session['site_id'], (int)$user['id'], $telemetry);
    $pdo->commit();
    Response::json(['earned' => $reward]);
}

function persist_site_stats(PDO $pdo, int $siteId, int $surferId, array $telemetry): void
{
    $payload = [
        'ip' => $telemetry['ip'] ?? null,
        'country_code' => $telemetry['country_code'] ?? null,
        'country' => $telemetry['country'] ?? null,
        'continent' => $telemetry['continent'] ?? null,
        'city' => $telemetry['city'] ?? null,
        'latitude' => $telemetry['lat'] ?? $telemetry['latitude'] ?? null,
        'longitude' => $telemetry['lon'] ?? $telemetry['longitude'] ?? null,
        'asn' => $telemetry['asn'] ?? null,
        'isp' => $telemetry['isp'] ?? null,
        'network' => $telemetry['network'] ?? null,
        'platform' => $telemetry['platform'] ?? null,
        'device' => $telemetry['device'] ?? null,
        'user_agent' => $telemetry['user_agent'] ?? null,
        'clicks' => (int)($telemetry['clicks'] ?? 0),
        'scrolls' => (int)($telemetry['scrolls'] ?? 0),
        'highlights' => (int)($telemetry['highlights'] ?? 0),
        'forms' => (int)($telemetry['forms'] ?? 0),
        'media' => (int)($telemetry['media'] ?? 0),
    ];
    $stmt = $pdo->prepare('INSERT INTO site_stats (site_id, surfer_id, ip, country_code, country, continent, city, latitude, longitude, asn, isp, network, platform, device, user_agent, clicks, scrolls, highlights, forms, media)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $siteId,
        $surferId,
        $payload['ip'],
        $payload['country_code'],
        $payload['country'],
        $payload['continent'],
        $payload['city'],
        $payload['latitude'],
        $payload['longitude'],
        $payload['asn'],
        $payload['isp'],
        $payload['network'],
        $payload['platform'],
        $payload['device'],
        $payload['user_agent'],
        $payload['clicks'],
        $payload['scrolls'],
        $payload['highlights'],
        $payload['forms'],
        $payload['media'],
    ]);
}

function site_stats(PDO $pdo, array $user, int $siteId): void
{
    $stmt = $pdo->prepare('SELECT * FROM sites WHERE id = ? AND user_id = ?');
    $stmt->execute([$siteId, $user['id']]);
    $site = $stmt->fetch();
    if (!$site) {
        Response::error('Site bulunamadı', 404);
        return;
    }

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 25)));
    $offset = ($page - 1) * $perPage;

    $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM site_stats WHERE site_id = ?');
    $totalStmt->execute([$siteId]);
    $totalEvents = (int)($totalStmt->fetchColumn() ?: 0);

    $allowedSort = ['created_at', 'country', 'city', 'clicks', 'scrolls', 'forms', 'media'];
    $sort = $_GET['sort'] ?? 'created_at';
    $sort = in_array($sort, $allowedSort, true) ? $sort : 'created_at';
    $dir = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

    $eventsStmt = $pdo->prepare('SELECT country, country_code, continent, city, latitude, longitude, ip, asn, isp, network, platform, device, user_agent, clicks, scrolls, highlights, forms, media, created_at
        FROM site_stats WHERE site_id = ? ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ? OFFSET ?');
    $eventsStmt->bindValue(1, $siteId, PDO::PARAM_INT);
    $eventsStmt->bindValue(2, $perPage, PDO::PARAM_INT);
    $eventsStmt->bindValue(3, $offset, PDO::PARAM_INT);
    $eventsStmt->execute();
    $events = $eventsStmt->fetchAll();

    $totalsStmt = $pdo->prepare('SELECT COUNT(*) AS visits, SUM(clicks) AS clicks, SUM(scrolls) AS scrolls, SUM(forms) AS forms, SUM(media) AS media
        FROM site_stats WHERE site_id = ?');
    $totalsStmt->execute([$siteId]);
    $totals = $totalsStmt->fetch();

    $daily = $pdo->prepare('SELECT DATE(created_at) AS label, COUNT(*) AS visits, SUM(clicks) AS clicks
        FROM site_stats WHERE site_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at) ORDER BY DATE(created_at)');
    $daily->execute([$siteId]);
    $weekly = $pdo->prepare('SELECT YEARWEEK(created_at,1) AS label, COUNT(*) AS visits, SUM(clicks) AS clicks
        FROM site_stats WHERE site_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
        GROUP BY YEARWEEK(created_at,1) ORDER BY YEARWEEK(created_at,1)');
    $weekly->execute([$siteId]);
    $monthly = $pdo->prepare('SELECT DATE_FORMAT(created_at, "%Y-%m") AS label, COUNT(*) AS visits, SUM(clicks) AS clicks
        FROM site_stats WHERE site_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, "%Y-%m") ORDER BY DATE_FORMAT(created_at, "%Y-%m")');
    $monthly->execute([$siteId]);

    $pointSeries = function (string $windowSql, string $labelSql, string $groupBy) use ($pdo, $siteId) {
        $sql = "SELECT {$labelSql} AS label, SUM(change_amount) AS net,
                SUM(CASE WHEN change_amount > 0 THEN change_amount ELSE 0 END) AS earned,
                SUM(CASE WHEN change_amount < 0 THEN -change_amount ELSE 0 END) AS spent
            FROM point_ledger
            WHERE JSON_EXTRACT(meta, '$.site_id') = ? AND created_at >= {$windowSql}
            GROUP BY {$groupBy}
            ORDER BY {$groupBy}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$siteId]);
        return $stmt->fetchAll();
    };

    $pointTotals = $pdo->prepare('SELECT SUM(change_amount) AS net,
            SUM(CASE WHEN change_amount > 0 THEN change_amount ELSE 0 END) AS earned,
            SUM(CASE WHEN change_amount < 0 THEN -change_amount ELSE 0 END) AS spent
        FROM point_ledger WHERE JSON_EXTRACT(meta, "$.site_id") = ?');
    $pointTotals->execute([$siteId]);
    $points = $pointTotals->fetch() ?: ['net' => 0, 'earned' => 0, 'spent' => 0];

    $summary = [
        'total_visits' => (int)($totals['visits'] ?? count($events)),
        'last_visit' => $events[0]['created_at'] ?? null,
        'clicks' => (int)($totals['clicks'] ?? array_sum(array_column($events, 'clicks'))),
        'scrolls' => (int)($totals['scrolls'] ?? array_sum(array_column($events, 'scrolls'))),
        'forms' => (int)($totals['forms'] ?? array_sum(array_column($events, 'forms'))),
        'media' => (int)($totals['media'] ?? array_sum(array_column($events, 'media'))),
    ];

    Response::json([
        'site' => $site,
        'events' => $events,
        'charts' => [
            'daily' => $daily->fetchAll(),
            'weekly' => $weekly->fetchAll(),
            'monthly' => $monthly->fetchAll(),
        ],
        'point_charts' => [
            'daily' => $pointSeries('DATE_SUB(CURDATE(), INTERVAL 30 DAY)', 'DATE(created_at)', 'DATE(created_at)'),
            'weekly' => $pointSeries('DATE_SUB(CURDATE(), INTERVAL 12 WEEK)', 'YEARWEEK(created_at,1)', 'YEARWEEK(created_at,1)'),
            'monthly' => $pointSeries('DATE_SUB(CURDATE(), INTERVAL 12 MONTH)', 'DATE_FORMAT(created_at, "%Y-%m")', 'DATE_FORMAT(created_at, "%Y-%m")'),
        ],
        'summary' => $summary,
        'points' => [
            'earned' => (int)($points['earned'] ?? 0),
            'spent' => (int)($points['spent'] ?? 0),
            'net' => (int)($points['net'] ?? 0),
        ],
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $totalEvents,
            'total_pages' => max(1, (int)ceil($totalEvents / max(1, $perPage))),
        ],
    ]);
}

function update_profile_endpoint(PDO $pdo, array $user): void
{
    $data = read_json();
    $name = trim($data['name'] ?? $user['display_name']);
    $password = $data['password'] ?? null;
    $pdo->prepare('UPDATE users SET display_name = ? WHERE id = ?')->execute([$name, $user['id']]);
    if ($password) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $user['id']]);
    }
    $stmt = $pdo->prepare('SELECT id, email, display_name, points FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    Response::json(['user' => $stmt->fetch()]);
}

function contact(PDO $pdo, ?array $user, array $config): void
{
    $data = read_json();
    $email = $data['email'] ?? ($user['email'] ?? ($config['contact_email'] ?? null));
    $subject = trim($data['subject'] ?? '') ?: 'Autosurf talebi';
    $body = trim($data['body'] ?? '');
    if (!$email || !$body) {
        Response::error('email ve body zorunlu');
        return;
    }
    $pdo->prepare('INSERT INTO contact_messages (user_id, email, subject, body) VALUES (?,?,?,?)')
        ->execute([$user['id'] ?? null, $email, $subject, $body]);
    $to = $config['contact_email'] ?? 'info@noasoft.org';
    $from = $config['contact_email'] ?? 'info@noasoft.org';
    $headers = [
        'From' => $from,
        'Reply-To' => $email,
        'Content-Type' => 'text/plain; charset=UTF-8',
    ];
    $headerLines = [];
    foreach ($headers as $key => $value) {
        $headerLines[] = $key . ': ' . $value;
    }
    $fullSubject = '[Autosurf] ' . $subject;
    $fullBody = "Gönderen: {$email}\nKullanıcı: " . ($user['display_name'] ?? 'Anonim') . "\n---\n" . $body;
    $sent = @mail($to, $fullSubject, $fullBody, implode("\r\n", $headerLines));
    Response::json(['queued' => true, 'sent' => (bool)$sent]);
}

function mail_settings(array $config): void
{
    Response::json([
        'to' => $config['contact_email'] ?? 'info@noasoft.org',
        'from' => $config['contact_email'] ?? 'info@noasoft.org',
    ]);
}

function task_config_endpoint(array $config): void
{
    Response::json([
        'google_base' => (int)($config['google_task_points'] ?? 50),
        'youtube_base' => (int)($config['youtube_task_points'] ?? 50),
        'google_page' => (int)($config['google_page_points'] ?? 10),
        'youtube_page' => (int)($config['youtube_page_points'] ?? 10),
    ]);
}

function dashboard_history(PDO $pdo, array $user): void
{
    $stmt = $pdo->prepare('SELECT DATE(created_at) as label, SUM(change_amount) as net FROM point_ledger WHERE user_id = ? GROUP BY DATE(created_at) ORDER BY DATE(created_at) DESC LIMIT 14');
    $stmt->execute([$user['id']]);
    $rows = $stmt->fetchAll();
    $history = [];
    foreach (array_reverse($rows) as $row) {
        $history[] = [
            'label' => $row['label'],
            'daily' => (int)$row['net'],
            'weekly' => (int)$row['net'],
        ];
    }
    Response::json(['history' => $history]);
}

function dashboard(PDO $pdo, array $user): void
{
    $daily = $pdo->prepare('SELECT SUM(change_amount) AS total FROM point_ledger WHERE user_id = ? AND DATE(created_at)=CURDATE()');
    $daily->execute([$user['id']]);
    $weekly = $pdo->prepare('SELECT SUM(change_amount) AS total FROM point_ledger WHERE user_id = ? AND YEARWEEK(created_at,1)=YEARWEEK(NOW(),1)');
    $weekly->execute([$user['id']]);
    $stmt = $pdo->prepare('SELECT SUM(dwell_seconds) AS remaining FROM sites WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    $limits = reward_leftovers($pdo, (int)$user['id'], $GLOBALS['config']);
    Response::json([
        'daily' => (int)($daily->fetch()['total'] ?? 0),
        'weekly' => (int)($weekly->fetch()['total'] ?? 0),
        'remaining_seconds' => (int)($stmt->fetch()['remaining'] ?? 0),
        'points' => (int)$user['points'],
        'limits' => $limits,
    ]);
}

$method = $_SERVER['REQUEST_METHOD'];
$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
if ($path === '') {
    $path = '/';
}

switch (true) {
    case $path === '/auth/register' && $method === 'POST':
        register($pdo, $config);
        break;
    case $path === '/auth/login' && $method === 'POST':
        login($pdo, $config);
        break;
    case $path === '/auth/forgot' && $method === 'POST':
        forgot_password($pdo);
        break;
    case $path === '/profile' && $method === 'GET':
        if ($user = ensure_user($pdo, $config)) {
            Response::json(['user' => $user]);
        }
        break;
    case $path === '/profile' && $method === 'PATCH':
        if ($user = ensure_user($pdo, $config)) {
            update_profile_endpoint($pdo, $user);
        }
        break;
    case $path === '/sites' && $method === 'GET':
        if ($user = ensure_user($pdo, $config)) {
            list_sites($pdo, $user['id']);
        }
        break;
    case $path === '/sites' && $method === 'POST':
        if ($user = ensure_user($pdo, $config)) {
            create_site($pdo, $config, $user);
        }
        break;
    case preg_match('#^/sites/(\d+)$#', $path, $m) && $method === 'DELETE':
        if ($user = ensure_user($pdo, $config)) {
            delete_site($pdo, $user['id'], (int)$m[1]);
        }
        break;
    case preg_match('#^/sites/(\d+)$#', $path, $m) && $method === 'PATCH':
        if ($user = ensure_user($pdo, $config)) {
            update_site($pdo, $user['id'], (int)$m[1]);
        }
        break;
    case preg_match('#^/sites/(\d+)/stats$#', $path, $m) && $method === 'GET':
        if ($user = ensure_user($pdo, $config)) {
            site_stats($pdo, $user, (int)$m[1]);
        }
        break;
    case $path === '/surf/start' && $method === 'POST':
        if ($user = ensure_user($pdo, $config)) {
            start_surf($pdo, $user, $config);
        }
        break;
    case $path === '/surf/complete' && $method === 'POST':
        if ($user = ensure_user($pdo, $config)) {
            complete_surf($pdo, $user, $config);
        }
        break;
    case $path === '/contact' && $method === 'POST':
        $user = current_user($pdo, $config);
        contact($pdo, $user, $config);
        break;
    case $path === '/dashboard' && $method === 'GET':
        if ($user = ensure_user($pdo, $config)) {
            dashboard($pdo, $user);
        }
        break;
    case $path === '/dashboard/history' && $method === 'GET':
        if ($user = ensure_user($pdo, $config)) {
            dashboard_history($pdo, $user);
        }
        break;
    case $path === '/mail/settings' && $method === 'GET':
        mail_settings($config);
        break;
    case $path === '/mail/send' && $method === 'POST':
        $user = current_user($pdo, $config);
        contact($pdo, $user, $config);
        break;
    case $path === '/tasks/config' && $method === 'GET':
        if ($user = ensure_user($pdo, $config)) {
            task_config_endpoint($config);
        }
        break;
    default:
        Response::error('Endpoint bulunamadı', 404, ['path' => $path]);
}
