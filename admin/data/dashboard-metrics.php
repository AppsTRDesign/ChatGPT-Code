<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');

$chart = strtolower($_GET['chart'] ?? 'summary');
$period = strtolower($_GET['period'] ?? 'daily');
$format = strtolower($_GET['format'] ?? 'json');

$allowedPeriods = ['daily', 'weekly', 'monthly', 'yearly'];
if (!in_array($period, $allowedPeriods, true)) {
    $period = 'daily';
}

if ($format === 'json') {
    Helpers::requireAjax();
}

$db = Helpers::db();

function dashboard_period_config(string $period): array
{
    $today = new DateTimeImmutable('today');

    return match ($period) {
        'weekly' => [
            'period' => 'weekly',
            'count' => 12,
            'interval' => new DateInterval('P1W'),
            'start' => $today->modify('monday this week')->modify('-11 weeks'),
            'group' => "CONCAT(DATE_FORMAT(%s, '%x'), '-W', LPAD(DATE_FORMAT(%s, '%v'), 2, '0'))",
            'label' => static fn (DateTimeImmutable $date): string => $date->format('d.m') . ' - ' . $date->modify('+6 days')->format('d.m'),
            'key' => static fn (DateTimeImmutable $date): string => sprintf('%s-W%s', $date->format('o'), str_pad($date->format('W'), 2, '0', STR_PAD_LEFT)),
            'rangeLabel' => 'Son 12 Hafta',
            'end' => static fn (DateTimeImmutable $date): DateTimeImmutable => $date->modify('+6 days'),
        ],
        'monthly' => [
            'period' => 'monthly',
            'count' => 12,
            'interval' => new DateInterval('P1M'),
            'start' => $today->modify('first day of this month')->modify('-11 months'),
            'group' => "DATE_FORMAT(%s, '%Y-%m')",
            'label' => static fn (DateTimeImmutable $date): string => $date->format('m.Y'),
            'key' => static fn (DateTimeImmutable $date): string => $date->format('Y-m'),
            'rangeLabel' => 'Son 12 Ay',
            'end' => static fn (DateTimeImmutable $date): DateTimeImmutable => $date->modify('last day of this month'),
        ],
        'yearly' => [
            'period' => 'yearly',
            'count' => 5,
            'interval' => new DateInterval('P1Y'),
            'start' => $today->modify('first day of january this year')->modify('-4 years'),
            'group' => "DATE_FORMAT(%s, '%Y')",
            'label' => static fn (DateTimeImmutable $date): string => $date->format('Y'),
            'key' => static fn (DateTimeImmutable $date): string => $date->format('Y'),
            'rangeLabel' => 'Son 5 Yıl',
            'end' => static fn (DateTimeImmutable $date): DateTimeImmutable => $date->modify('last day of december this year'),
        ],
        default => [
            'period' => 'daily',
            'count' => 14,
            'interval' => new DateInterval('P1D'),
            'start' => $today->modify('-13 days'),
            'group' => 'DATE(%s)',
            'label' => static fn (DateTimeImmutable $date): string => $date->format('d.m'),
            'key' => static fn (DateTimeImmutable $date): string => $date->format('Y-m-d'),
            'rangeLabel' => 'Son 14 Gün',
            'end' => static fn (DateTimeImmutable $date): DateTimeImmutable => $date,
        ],
    };
}

function dashboard_build_buckets(array $config): array
{
    $buckets = [];
    $current = $config['start'];
    for ($i = 0; $i < $config['count']; $i++) {
        $key = ($config['key'])($current);
        $label = ($config['label'])($current);
        $end = ($config['end'])($current);
        $buckets[] = [
            'key' => $key,
            'label' => $label,
            'start' => $current,
            'end' => $end,
        ];
        $current = $current->add($config['interval']);
    }
    return $buckets;
}

function dashboard_group_expression(array $config, string $column): string
{
    $expression = $config['group'];
    $placeholderCount = substr_count($expression, '%s');
    if ($placeholderCount === 0) {
        return $expression;
    }
    if ($placeholderCount === 1) {
        return sprintf($expression, $column);
    }
    return vsprintf($expression, array_fill(0, $placeholderCount, $column));
}

function dashboard_fetch_series(PDO $db, array $config, string $table, string $dateColumn, string $aggregate, array $conditions = [], array $params = [], ?string $join = null): array
{
    $groupExpr = dashboard_group_expression($config, $dateColumn);
    $where = [$dateColumn . ' >= :start'];
    foreach ($conditions as $condition) {
        $trimmed = trim($condition);
        if ($trimmed !== '') {
            $where[] = '(' . $trimmed . ')';
        }
    }
    $sql = sprintf(
        'SELECT %s AS bucket, %s AS total FROM %s %s WHERE %s GROUP BY bucket ORDER BY bucket',
        $groupExpr,
        $aggregate,
        $table,
        $join ? $join : '',
        implode(' AND ', $where)
    );

    $stmt = $db->prepare($sql);
    $params['start'] = $config['start']->format('Y-m-d 00:00:00');
    $stmt->execute($params);
    $rows = [];
    foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
        if (!isset($row['bucket'])) {
            continue;
        }
        $rows[(string) $row['bucket']] = (float) $row['total'];
    }
    return $rows;
}

function dashboard_traffic(PDO $db, string $period): array
{
    $config = dashboard_period_config($period);
    $buckets = dashboard_build_buckets($config);

    $usageMap = dashboard_fetch_series(
        $db,
        $config,
        'api_usage_logs',
        'created_at',
        'COUNT(*)',
        ['status = :status'],
        ['status' => 'success']
    );

    $registrationMap = dashboard_fetch_series(
        $db,
        $config,
        'users',
        'created_at',
        'COUNT(*)',
        ['role = :role'],
        ['role' => 'client']
    );

    $labels = [];
    $usage = [];
    $registrations = [];
    $rows = [];

    foreach ($buckets as $bucket) {
        $key = $bucket['key'];
        $labels[] = $bucket['label'];
        $usageValue = (int) round($usageMap[$key] ?? 0);
        $registrationValue = (int) round($registrationMap[$key] ?? 0);
        $usage[] = $usageValue;
        $registrations[] = $registrationValue;
        $rows[] = [
            'label' => $bucket['label'],
            'usage' => $usageValue,
            'registrations' => $registrationValue,
            'start' => $bucket['start']->format('Y-m-d'),
            'end' => $bucket['end']->format('Y-m-d'),
        ];
    }

    return [
        'period' => $config['period'],
        'rangeLabel' => $config['rangeLabel'],
        'labels' => $labels,
        'usage' => $usage,
        'registrations' => $registrations,
        'rows' => $rows,
    ];
}

function dashboard_revenue(PDO $db, string $period): array
{
    $config = dashboard_period_config($period);
    $buckets = dashboard_build_buckets($config);

    $revenueMap = dashboard_fetch_series(
        $db,
        $config,
        'user_packages up',
        'up.activated_at',
        'SUM(p.price)',
        ['up.status = "active"', 'up.activated_at IS NOT NULL'],
        [],
        'JOIN packages p ON p.id = up.package_id'
    );

    $labels = [];
    $totals = [];
    $rows = [];

    foreach ($buckets as $bucket) {
        $key = $bucket['key'];
        $labels[] = $bucket['label'];
        $totalValue = round($revenueMap[$key] ?? 0, 2);
        $totals[] = $totalValue;
        $rows[] = [
            'label' => $bucket['label'],
            'total' => $totalValue,
            'start' => $bucket['start']->format('Y-m-d'),
            'end' => $bucket['end']->format('Y-m-d'),
        ];
    }

    return [
        'period' => $config['period'],
        'rangeLabel' => $config['rangeLabel'],
        'labels' => $labels,
        'totals' => $totals,
        'rows' => $rows,
    ];
}

function dashboard_summary(PDO $db): array
{
    $totalRevenueStmt = $db->query('SELECT SUM(p.price) FROM user_packages up JOIN packages p ON p.id = up.package_id WHERE up.status = "active"');
    $totalRevenue = (float) ($totalRevenueStmt->fetchColumn() ?: 0);
    $pending = (int) ($db->query('SELECT COUNT(*) FROM user_packages WHERE status IN ("pending","awaiting_payment","payment_missing")')->fetchColumn() ?: 0);
    $failed = (int) ($db->query('SELECT COUNT(*) FROM user_packages WHERE status = "failed"')->fetchColumn() ?: 0);
    $newClientsStmt = $db->query('SELECT COUNT(*) FROM users WHERE role = "client" AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
    $newClients = (int) ($newClientsStmt->fetchColumn() ?: 0);

    return [
        'totalRevenue' => round($totalRevenue, 2),
        'pendingPurchases' => $pending,
        'failedPurchases' => $failed,
        'newClients7' => $newClients,
    ];
}

function dashboard_export(string $chart, string $period, string $format, array $data): void
{
    $periodLabels = [
        'daily' => 'Günlük',
        'weekly' => 'Haftalık',
        'monthly' => 'Aylık',
        'yearly' => 'Yıllık',
    ];

    $title = $chart === 'traffic' ? 'Trafik ve Üyelik' : 'Gelir Analizi';
    $suffix = $periodLabels[$period] ?? ucfirst($period);
    $filenameSlug = $chart . '-' . $period . '-' . date('Ymd-His');

    if ($chart === 'traffic') {
        $headers = ['Aralık', 'API İstekleri', 'Yeni Üyeler'];
        $rows = array_map(static function (array $row): array {
            return [
                $row['label'],
                (string) $row['usage'],
                (string) $row['registrations'],
            ];
        }, $data['rows']);
    } else {
        $headers = ['Aralık', 'Onaylı Gelir (₺)'];
        $rows = array_map(static function (array $row): array {
            return [
                $row['label'],
                number_format((float) $row['total'], 2, ',', '.'),
            ];
        }, $data['rows']);
    }

    if ($format === 'excel') {
        Helpers::streamCsv($filenameSlug . '.csv', $headers, $rows);
    } elseif ($format === 'pdf') {
        Helpers::streamPdf($filenameSlug . '.pdf', $title . ' - ' . $suffix, $headers, $rows);
    }
}

if ($chart === 'traffic') {
    $traffic = dashboard_traffic($db, $period);
    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($traffic);
        return;
    }
    dashboard_export('traffic', $period, $format, $traffic);
    return;
}

if ($chart === 'revenue') {
    $revenue = dashboard_revenue($db, $period);
    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($revenue);
        return;
    }
    dashboard_export('revenue', $period, $format, $revenue);
    return;
}

$summary = dashboard_summary($db);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['summary' => $summary]);
