<?php
require __DIR__ . '/lib/Database.php';
require __DIR__ . '/lib/Jwt.php';
require __DIR__ . '/lib/Response.php';

$config = require __DIR__ . '/config.php';
$db = new Database($config);
$pdo = $db->pdo();

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
    $name = $data['name'] ?? '';
    if (!$email || !$password || !$name) {
        Response::error('email, password ve name zorunlu');
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

function list_sites(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare('SELECT * FROM sites WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$userId]);
    $sites = $stmt->fetchAll();
    Response::json(['items' => $sites]);
}

function create_site(PDO $pdo, array $config, array $user): void
{
    $data = read_json();
    $name = trim($data['name'] ?? '');
    $url = trim($data['url'] ?? '');
    $dwell = max(5, (int)($data['dwell_seconds'] ?? 0));
    if (!$name || !$url) {
        Response::error('name ve url zorunlu');
        return;
    }
    if ($user['points'] < $dwell) {
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
    $pdo->prepare('INSERT INTO sites (user_id, name, url, dwell_seconds, mobile, realistic, mouse_moves, link_clicks, scroll, form_fill, media) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([
            $user['id'], $name, $url, $dwell,
            (int)$flags['mobile'], (int)$flags['realistic'], (int)$flags['mouse_moves'],
            (int)$flags['link_clicks'], (int)$flags['scroll'], (int)$flags['form_fill'], (int)$flags['media'],
        ]);
    $siteId = (int)$pdo->lastInsertId();
    $pdo->prepare('UPDATE users SET points = points - ? WHERE id = ?')->execute([$dwell, $user['id']]);
    $pdo->prepare('INSERT INTO point_ledger (user_id, change_amount, reason, meta) VALUES (?,?,?,?)')
        ->execute([$user['id'], -$dwell, 'site_reserve', json_encode(['site_id' => $siteId])]);
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
    $pdo->prepare('UPDATE sites SET name = ?, url = ?, dwell_seconds = IFNULL(?, dwell_seconds) WHERE id = ?')
        ->execute([
            $name ?: $site['name'],
            $url ?: $site['url'],
            $dwell,
            $siteId,
        ]);
    Response::json(['updated' => true]);
}

function start_surf(PDO $pdo, array $user): void
{
    $stmt = $pdo->prepare('SELECT s.*, u.display_name AS owner_name FROM sites s JOIN users u ON u.id = s.user_id WHERE s.user_id != ? ORDER BY s.created_at DESC');
    $stmt->execute([$user['id']]);
    $sites = $stmt->fetchAll();
    foreach ($sites as $site) {
        if ((int)$site['dwell_seconds'] <= 0) {
            continue;
        }
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT points FROM users WHERE id = ? FOR UPDATE');
            $lock->execute([$site['user_id']]);
            $owner = $lock->fetch();
            if (!$owner || $owner['points'] < $site['dwell_seconds']) {
                $pdo->rollBack();
                continue;
            }
            $pdo->prepare('UPDATE users SET points = points - ? WHERE id = ?')
                ->execute([(int)$site['dwell_seconds'], $site['user_id']]);
            $pdo->prepare('INSERT INTO point_ledger (user_id, change_amount, reason, meta) VALUES (?,?,?,?)')
                ->execute([$site['user_id'], -$site['dwell_seconds'], 'surf_spend', json_encode(['site_id' => $site['id']])]);
            $pdo->prepare('INSERT INTO surf_sessions (site_id, surfer_id, status, started_at) VALUES (?,?,"in_progress",NOW())')
                ->execute([(int)$site['id'], $user['id']]);
            $sessionId = (int)$pdo->lastInsertId();
            $pdo->commit();
            $plan = build_plan($site);
            Response::json([
                'session_id' => $sessionId,
                'site' => $site,
                'plan' => $plan,
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

function complete_surf(PDO $pdo, array $user): void
{
    $data = read_json();
    $sessionId = (int)($data['session_id'] ?? 0);
    if (!$sessionId) {
        Response::error('session_id zorunlu');
        return;
    }
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT ss.*, s.dwell_seconds FROM surf_sessions ss JOIN sites s ON s.id = ss.site_id WHERE ss.id = ? AND ss.surfer_id = ? FOR UPDATE');
    $stmt->execute([$sessionId, $user['id']]);
    $session = $stmt->fetch();
    if (!$session || $session['status'] !== 'in_progress') {
        $pdo->rollBack();
        Response::error('Aktif oturum bulunamadı', 404);
        return;
    }
    $pdo->prepare('UPDATE surf_sessions SET status = "completed", completed_at = NOW() WHERE id = ?')
        ->execute([$sessionId]);
    $reward = (int)$session['dwell_seconds'];
    $pdo->prepare('UPDATE users SET points = points + ? WHERE id = ?')->execute([$reward, $user['id']]);
    $pdo->prepare('INSERT INTO point_ledger (user_id, change_amount, reason, meta) VALUES (?,?,?,?)')
        ->execute([$user['id'], $reward, 'surf_reward', json_encode(['session_id' => $sessionId])]);
    $pdo->commit();
    Response::json(['earned' => $reward]);
}

function contact(PDO $pdo, ?array $user): void
{
    $data = read_json();
    $email = $data['email'] ?? ($user['email'] ?? null);
    $subject = $data['subject'] ?? 'Autosurf talebi';
    $body = $data['body'] ?? '';
    if (!$email || !$body) {
        Response::error('email ve body zorunlu');
        return;
    }
    $pdo->prepare('INSERT INTO contact_messages (user_id, email, subject, body) VALUES (?,?,?,?)')
        ->execute([$user['id'] ?? null, $email, $subject, $body]);
    Response::json(['queued' => true]);
}

function dashboard(PDO $pdo, array $user): void
{
    $daily = $pdo->prepare('SELECT SUM(change_amount) AS total FROM point_ledger WHERE user_id = ? AND DATE(created_at)=CURDATE()');
    $daily->execute([$user['id']]);
    $weekly = $pdo->prepare('SELECT SUM(change_amount) AS total FROM point_ledger WHERE user_id = ? AND YEARWEEK(created_at,1)=YEARWEEK(NOW(),1)');
    $weekly->execute([$user['id']]);
    $stmt = $pdo->prepare('SELECT SUM(dwell_seconds) AS remaining FROM sites WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    Response::json([
        'daily' => (int)($daily->fetch()['total'] ?? 0),
        'weekly' => (int)($weekly->fetch()['total'] ?? 0),
        'remaining_seconds' => (int)($stmt->fetch()['remaining'] ?? 0),
        'points' => (int)$user['points'],
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
    case $path === '/profile' && $method === 'GET':
        if ($user = ensure_user($pdo, $config)) {
            Response::json(['user' => $user]);
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
    case $path === '/surf/start' && $method === 'POST':
        if ($user = ensure_user($pdo, $config)) {
            start_surf($pdo, $user);
        }
        break;
    case $path === '/surf/complete' && $method === 'POST':
        if ($user = ensure_user($pdo, $config)) {
            complete_surf($pdo, $user);
        }
        break;
    case $path === '/contact' && $method === 'POST':
        $user = current_user($pdo, $config);
        contact($pdo, $user);
        break;
    case $path === '/dashboard' && $method === 'GET':
        if ($user = ensure_user($pdo, $config)) {
            dashboard($pdo, $user);
        }
        break;
    default:
        Response::error('Endpoint bulunamadı', 404, ['path' => $path]);
}
