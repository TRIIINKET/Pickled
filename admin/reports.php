<?php
$pageTitle = 'Reports & Analytics';
$activePage = 'reports';
$bodyClass = 'admin-dashboard-body';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/admin-paths.php';
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../app/services/FeedbackService.php';
require_once __DIR__ . '/../app/services/AdminLogService.php';

pickled_start_secure_session();
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: admin-login.php');
    exit;
}

pickled_init_csrf();

$pdo = Database::enabled() ? Database::connection() : null;
$feedbackService = new FeedbackService();
$adminLogService = new AdminLogService();
$adminName = $_SESSION['user']['name'] ?? 'Admin';
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayLabel = $today->format('M j, Y (D)');
$defaultStart = $today->modify('-24 days')->format('Y-m-d');
$defaultEnd = $today->format('Y-m-d');
$dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['date_from'] ?? '')) ? (string) $_GET['date_from'] : $defaultStart;
$dateTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['date_to'] ?? '')) ? (string) $_GET['date_to'] : $defaultEnd;
if (strtotime($dateFrom) === false) {
    $dateFrom = $defaultStart;
}
if (strtotime($dateTo) === false) {
    $dateTo = $defaultEnd;
}
if (strtotime($dateFrom) > strtotime($dateTo)) {
    [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
}
$rangeStart = date('M j', strtotime($dateFrom));
$rangeEnd = date('M j, Y', strtotime($dateTo));
$reportParams = ['date_from' => $dateFrom, 'date_to' => $dateTo];
$reportDateClause = 'bi.booking_date BETWEEN :date_from AND :date_to';
$feedbackRatingFilter = isset($_GET['feedback_rating']) && $_GET['feedback_rating'] !== '' ? (int) $_GET['feedback_rating'] : null;
$feedbackSearch = trim((string) ($_GET['feedback_q'] ?? ''));
$logActionFilter = trim((string) ($_GET['log_action'] ?? ''));
$logEntityFilter = trim((string) ($_GET['log_entity_type'] ?? ''));
$logSearch = trim((string) ($_GET['log_q'] ?? ''));
$logSort = strtolower((string) ($_GET['log_sort'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

function reports_scalar(?PDO $pdo, string $sql, array $params = [], float|int $fallback = 0): float|int {
    if (!$pdo) {
        return $fallback;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        return is_numeric($value) ? (float) $value : $fallback;
    } catch (Throwable $e) {
        error_log('Reports query failed: ' . $e->getMessage());
        return $fallback;
    }
}

function reports_rows(?PDO $pdo, string $sql, array $params = []): array {
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('Reports query failed: ' . $e->getMessage());
        return [];
    }
}

function reports_asset(string $path): string {
    return htmlspecialchars(pickled_admin_asset_url($path), ENT_QUOTES, 'UTF-8');
}

function reports_icon(array $icons, string $name): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['chart']) . '</svg>';
}

function reports_status_key(string $value): string {
    $value = strtolower($value);
    if (str_contains($value, 'confirm') || str_contains($value, 'complete') || str_contains($value, 'paid')) return 'success';
    if (str_contains($value, 'pending') || str_contains($value, 'registration')) return 'warning';
    if (str_contains($value, 'cancel') || str_contains($value, 'reject')) return 'danger';
    return 'neutral';
}

function reports_log_label(string $value): string {
    return ucwords(str_replace(['_', '-'], ' ', $value));
}

function reports_csv_download(string $filename, array $sections): void {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'wb');
    fwrite($out, "\xEF\xBB\xBF");
    foreach ($sections as $section) {
        fputcsv($out, [$section['title']]);
        foreach ($section['rows'] as $row) {
            fputcsv($out, $row);
        }
        fputcsv($out, []);
    }
    fclose($out);
    exit;
}

function reports_printable_report(array $report): void {
    $logo = htmlspecialchars($report['logo'], ENT_QUOTES, 'UTF-8');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PICKLED Booking and Revenue Report</title>
    <style>
        body { margin: 0; background: #f3f4f6; color: #111827; font-family: Arial, sans-serif; }
        .sheet { max-width: 1080px; margin: 28px auto; padding: 36px; background: #fff; border: 1px solid #d0d5dd; }
        .print-actions { max-width: 1080px; margin: 18px auto 0; text-align: right; }
        button { padding: 10px 16px; border: 1px solid #1f4d2b; background: #1f4d2b; color: #fff; border-radius: 4px; font-weight: 700; }
        header { display: grid; grid-template-columns: 130px 1fr 130px; align-items: center; border-bottom: 2px solid #111827; padding-bottom: 18px; }
        header img { max-width: 100px; }
        h1 { margin: 0; text-align: center; font-size: 22px; text-transform: uppercase; }
        h2 { margin: 28px 0 10px; font-size: 15px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #98a2b3; padding: 8px 10px; font-size: 12px; text-align: left; vertical-align: top; }
        th { background: #f2f4f7; font-weight: 800; }
        .summary td:first-child { width: 28%; font-weight: 800; background: #f9fafb; }
        .footer { display: grid; grid-template-columns: 1fr 1fr; gap: 70px; margin-top: 42px; }
        .line { border-top: 1px solid #111827; padding-top: 8px; font-size: 12px; text-align: center; }
        @media print { body { background: #fff; } .sheet { margin: 0; max-width: none; border: 0; } .print-actions { display: none; } }
    </style>
</head>
<body>
    <div class="print-actions"><button type="button" onclick="window.print()">Print / Save as PDF</button></div>
    <main class="sheet">
        <header>
            <img src="<?php echo $logo; ?>" alt="PICKLED">
            <h1>PICKLED Booking and Revenue Report</h1>
            <span></span>
        </header>
        <?php echo $report['html']; ?>
    </main>
</body>
</html>
    <?php
    exit;
}

$icons = [
    'home' => '<path d="M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
    'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    'user' => '<path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/>',
    'target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4"/><path d="M22 2 12 12"/>',
    'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
    'chart' => '<path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/>',
    'courts' => '<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/>',
    'image' => '<rect x="3" y="5" width="18" height="16" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="m21 16-5-5L5 21"/>',
    'tag' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8Z"/><circle cx="8" cy="8" r="1.5"/>',
    'gear' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3a2 2 0 1 1 4 0v.09A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.38.22.74.57 1 .95.26.38.4.8.4 1.2V12a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.31-.6Z"/>',
    'filter' => '<path d="M22 3H2l8 9.46V19l4 2v-8.54Z"/>',
    'download' => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>',
    'peso' => '<path d="M8 5h6a4 4 0 0 1 0 8H8M8 5v14M5 9h12M5 13h9"/>',
    'trophy' => '<path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0Z"/><path d="M5 5H3v2a4 4 0 0 0 4 4"/><path d="M19 5h2v2a4 4 0 0 1-4 4"/>',
    'arrow' => '<path d="m9 18 6-6-6-6"/>',
    'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/>',
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'star' => '<path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9Z"/>',
];

$dashboardNav = [
    ['type' => 'single', 'label' => 'Dashboard', 'href' => 'admin-dashboard.php', 'key' => 'dashboard', 'icon' => 'home'],
    ['type' => 'group', 'label' => 'Bookings', 'href' => 'manage-bookings.php', 'key' => 'bookings', 'icon' => 'calendar', 'children' => [['All Bookings', 'manage-bookings.php', ''], ['Calendar View', 'manage-bookings.php?view=calendar', '']]],
    ['type' => 'group', 'label' => 'Users', 'href' => 'manage-users.php?role=player', 'key' => 'users', 'icon' => 'users', 'children' => [['Players', 'manage-users.php?role=player', ''], ['Coaches', 'manage-users.php?role=coach', '']]],
    ['type' => 'group', 'label' => 'Courts', 'href' => 'manage-events.php?court=green', 'key' => 'courts', 'icon' => 'courts', 'children' => [['Court Green', 'manage-events.php?court=green', ''], ['Court Pink', 'manage-events.php?court=pink', '']]],
    ['type' => 'group', 'label' => 'Programs & Events', 'href' => 'manage-events.php?program=social-play', 'key' => 'events', 'icon' => 'target', 'children' => [['Social Play', 'manage-events.php?program=social-play', ''], ['Private Packages', 'private-sessions.php', '']]],
['type' => 'single', 'label' => 'Content', 'href' => 'content.php', 'key' => 'content', 'icon' => 'image'],
['type' => 'single', 'label' => 'Reports', 'href' => 'reports.php', 'key' => 'reports', 'icon' => 'chart'],
['type' => 'single', 'label' => 'Admin Profile', 'href' => 'admin-profile.php', 'key' => 'admin-profile', 'icon' => 'users'],
];

$programPerformance = reports_rows($pdo, "
    SELECT COALESCE(NULLIF(TRIM(bi.name), ''), 'Unspecified Program') AS program_name,
           COALESCE(NULLIF(TRIM(bi.category), ''), 'Uncategorized') AS category_name,
           COALESCE(SUM(bi.quantity), 0) AS bookings,
           COUNT(DISTINCT b.id) AS booking_records,
           COALESCE(SUM(bi.quantity * bi.unit_price), 0) AS revenue
    FROM booking_items bi
    JOIN bookings b ON b.id = bi.booking_id
    WHERE $reportDateClause
      AND LOWER(COALESCE(b.status, '')) NOT IN ('cancelled', 'rejected', 'expired', 'refunded')
    GROUP BY COALESCE(NULLIF(TRIM(bi.name), ''), 'Unspecified Program'),
             COALESCE(NULLIF(TRIM(bi.category), ''), 'Uncategorized')
    ORDER BY revenue DESC, bookings DESC, program_name ASC
", $reportParams);

$totalBookings = (int) reports_scalar($pdo, "
    SELECT COUNT(DISTINCT b.id)
    FROM bookings b
    JOIN booking_items bi ON bi.booking_id = b.id
    WHERE $reportDateClause
      AND LOWER(COALESCE(b.status, '')) NOT IN ('cancelled', 'rejected', 'expired', 'refunded')
", $reportParams, 0);
$totalRevenue = (float) reports_scalar($pdo, "
    SELECT COALESCE(SUM(bi.quantity * bi.unit_price), 0)
    FROM booking_items bi
    JOIN bookings b ON b.id = bi.booking_id
    WHERE $reportDateClause
      AND LOWER(COALESCE(b.status, '')) NOT IN ('cancelled', 'rejected', 'expired', 'refunded')
", $reportParams, 0);
$activePlayers = (int) reports_scalar($pdo, "
    SELECT COUNT(DISTINCT b.user_id)
    FROM bookings b
    JOIN booking_items bi ON bi.booking_id = b.id
    WHERE $reportDateClause
      AND LOWER(COALESCE(b.status, '')) NOT IN ('cancelled', 'rejected', 'expired', 'refunded')
", $reportParams, 0);
$activeCoaches = (int) reports_scalar($pdo, "
    SELECT COUNT(DISTINCT COALESCE(bi.coach_user_id, s.coach_user_id))
    FROM booking_items bi
    JOIN bookings b ON b.id = bi.booking_id
    LEFT JOIN sessions s ON s.id = bi.session_id
    WHERE $reportDateClause
      AND COALESCE(bi.coach_user_id, s.coach_user_id) IS NOT NULL
      AND LOWER(COALESCE(b.status, '')) NOT IN ('cancelled', 'rejected', 'expired', 'refunded')
", $reportParams, 0);
$platformFeedbackStats = reports_rows($pdo, "
    SELECT COUNT(*) AS total_reviews, COALESCE(AVG(rating), 0) AS average_rating
    FROM feedback
    WHERE DATE(created_at) BETWEEN :date_from AND :date_to
", $reportParams)[0] ?? ['total_reviews' => 0, 'average_rating' => 0];
$coachFeedbackSummary = $feedbackService->coachSummary();
$feedbackRows = $feedbackService->allFeedback($feedbackRatingFilter, $feedbackSearch, 80);
$adminLogFilters = [];
if ($logActionFilter !== '') {
    $adminLogFilters['action'] = $logActionFilter;
}
if ($logEntityFilter !== '') {
    $adminLogFilters['entity_type'] = $logEntityFilter;
}
if ($logSearch !== '') {
    $adminLogFilters['q'] = $logSearch;
}

$adminLogRows = [];
$adminLogActions = [];
$adminLogEntities = [];
try {
    $adminLogRows = $adminLogService->logs($adminLogFilters, 100, $logSort);
    $adminLogActions = $adminLogService->actionOptions();
    $adminLogEntities = $adminLogService->entityTypeOptions();
} catch (Throwable $e) {
    error_log('Admin log report failed: ' . $e->getMessage());
}

$popularServices = array_slice(array_values(array_filter($programPerformance, static fn(array $service): bool => (int) $service['bookings'] > 0)), 0, 5);
$popularTotal = max(1, array_sum(array_column($popularServices, 'bookings')));

$paymentSummary = reports_rows($pdo, "
    SELECT COALESCE(p.status, b.payment_status, 'pending') AS status,
           COUNT(DISTINCT b.id) AS bookings,
           COALESCE(SUM(b.total), 0) AS amount
    FROM bookings b
    LEFT JOIN payments p ON p.id = (
        SELECT p2.id FROM payments p2
        WHERE p2.booking_id = b.id
        ORDER BY p2.created_at DESC, p2.id DESC
        LIMIT 1
    )
    WHERE EXISTS (
        SELECT 1 FROM booking_items bi
        WHERE bi.booking_id = b.id
          AND bi.booking_date BETWEEN :date_from AND :date_to
    )
    GROUP BY COALESCE(p.status, b.payment_status, 'pending')
    ORDER BY bookings DESC, status ASC
", $reportParams);

$bookingStatusSummary = reports_rows($pdo, "
    SELECT COALESCE(b.status, 'pending') AS status,
           COUNT(DISTINCT b.id) AS bookings,
           COALESCE(SUM(b.total), 0) AS amount
    FROM bookings b
    WHERE EXISTS (
        SELECT 1 FROM booking_items bi
        WHERE bi.booking_id = b.id
          AND bi.booking_date BETWEEN :date_from AND :date_to
    )
    GROUP BY COALESCE(b.status, 'pending')
    ORDER BY bookings DESC, status ASC
", $reportParams);

$revenueByProgram = $programPerformance;
usort($revenueByProgram, static fn(array $a, array $b): int => ((float) $b['revenue'] <=> (float) $a['revenue']) ?: ((int) $b['bookings'] <=> (int) $a['bookings']));

$recentBookings = reports_rows($pdo, "
    SELECT b.reference, b.status, b.payment_status, b.total, b.created_at, u.name AS user_name,
           GROUP_CONCAT(DISTINCT bi.name ORDER BY bi.id SEPARATOR ', ') AS program_names,
           MIN(bi.booking_date) AS booking_date
    FROM bookings b
    LEFT JOIN users u ON u.id = b.user_id
    JOIN booking_items bi ON bi.booking_id = b.id
    WHERE $reportDateClause
    GROUP BY b.id
    ORDER BY MIN(bi.booking_date) DESC, b.created_at DESC
    LIMIT 10
", $reportParams);

$activityFeed = [];
foreach ($recentBookings as $booking) {
    $programName = $booking['program_names'] ?: 'a Pickled session';
    $activityFeed[] = [
        'user' => $booking['user_name'] ?: 'Guest',
        'activity' => 'booked ' . $programName,
        'time' => date('M j, Y - g:i A', strtotime($booking['created_at'] ?? 'now')),
        'badge' => pickled_booking_status_label($booking['status'] ?? 'New Booking'),
        'tone' => reports_status_key(($booking['status'] ?? '') . ' ' . ($booking['payment_status'] ?? '')),
    ];
}

$exportParams = ['date_from' => $dateFrom, 'date_to' => $dateTo];
$pdfExportUrl = pickled_admin_url('reports.php?' . http_build_query($exportParams + ['export' => 'pdf']));
$excelExportUrl = pickled_admin_url('reports.php?' . http_build_query($exportParams + ['export' => 'excel']));

ob_start();
?>
<table class="summary">
    <tr><td>Prepared by</td><td><?php echo htmlspecialchars($adminName); ?></td><td>Date generated</td><td><?php echo htmlspecialchars($today->format('F j, Y g:i A')); ?></td></tr>
    <tr><td>Coverage period</td><td><?php echo htmlspecialchars($rangeStart . ' - ' . $rangeEnd); ?></td><td>Total bookings</td><td><?php echo number_format($totalBookings); ?></td></tr>
    <tr><td>Total revenue</td><td>PHP <?php echo number_format($totalRevenue, 2); ?></td><td>Active players</td><td><?php echo number_format($activePlayers); ?></td></tr>
    <tr><td>Active coaches</td><td><?php echo number_format($activeCoaches); ?></td><td>Average rating</td><td><?php echo number_format((float) $platformFeedbackStats['average_rating'], 1); ?> / 5</td></tr>
</table>
<h2>Payment Summary</h2>
<table><thead><tr><th>Status</th><th>Total Bookings</th><th>Total Amount</th></tr></thead><tbody>
<?php foreach ($paymentSummary as $row): ?><tr><td><?php echo htmlspecialchars(reports_log_label((string) $row['status'])); ?></td><td><?php echo number_format((int) $row['bookings']); ?></td><td>PHP <?php echo number_format((float) $row['amount'], 2); ?></td></tr><?php endforeach; ?>
<?php if (!$paymentSummary): ?><tr><td colspan="3">No payment records for this period.</td></tr><?php endif; ?>
</tbody></table>
<h2>Booking Status Summary</h2>
<table><thead><tr><th>Status</th><th>Total Bookings</th><th>Total Amount</th></tr></thead><tbody>
<?php foreach ($bookingStatusSummary as $row): ?><tr><td><?php echo htmlspecialchars(reports_log_label((string) $row['status'])); ?></td><td><?php echo number_format((int) $row['bookings']); ?></td><td>PHP <?php echo number_format((float) $row['amount'], 2); ?></td></tr><?php endforeach; ?>
<?php if (!$bookingStatusSummary): ?><tr><td colspan="3">No booking records for this period.</td></tr><?php endif; ?>
</tbody></table>
<h2>Program / Service Performance</h2>
<table><thead><tr><th>Program / Service</th><th>Category</th><th>Bookings</th><th>Revenue</th><th>Average Revenue</th></tr></thead><tbody>
<?php foreach ($programPerformance as $row): $avg = (int) $row['bookings'] > 0 ? (float) $row['revenue'] / (int) $row['bookings'] : 0; ?><tr><td><?php echo htmlspecialchars((string) $row['program_name']); ?></td><td><?php echo htmlspecialchars((string) $row['category_name']); ?></td><td><?php echo number_format((int) $row['bookings']); ?></td><td>PHP <?php echo number_format((float) $row['revenue'], 2); ?></td><td>PHP <?php echo number_format($avg, 2); ?></td></tr><?php endforeach; ?>
<?php if (!$programPerformance): ?><tr><td colspan="5">No program data for this period.</td></tr><?php endif; ?>
<tr><th colspan="2">Totals</th><th><?php echo number_format(array_sum(array_map('intval', array_column($programPerformance, 'bookings')))); ?></th><th>PHP <?php echo number_format($totalRevenue, 2); ?></th><th></th></tr>
</tbody></table>
<h2>Revenue by Program</h2>
<table><thead><tr><th>Program / Service</th><th>Revenue</th><th>Share</th></tr></thead><tbody>
<?php foreach ($revenueByProgram as $row): $share = $totalRevenue > 0 ? ((float) $row['revenue'] / $totalRevenue) * 100 : 0; ?><tr><td><?php echo htmlspecialchars((string) $row['program_name']); ?></td><td>PHP <?php echo number_format((float) $row['revenue'], 2); ?></td><td><?php echo number_format($share, 1); ?>%</td></tr><?php endforeach; ?>
<?php if (!$revenueByProgram): ?><tr><td colspan="3">No revenue data for this period.</td></tr><?php endif; ?>
</tbody></table>
<h2>Top Booked Services</h2>
<table><thead><tr><th>Rank</th><th>Service</th><th>Bookings</th><th>Revenue</th></tr></thead><tbody>
<?php foreach ($popularServices as $index => $row): ?><tr><td><?php echo $index + 1; ?></td><td><?php echo htmlspecialchars((string) $row['program_name']); ?></td><td><?php echo number_format((int) $row['bookings']); ?></td><td>PHP <?php echo number_format((float) $row['revenue'], 2); ?></td></tr><?php endforeach; ?>
<?php if (!$popularServices): ?><tr><td colspan="4">No top services for this period.</td></tr><?php endif; ?>
</tbody></table>
<h2>Feedback / Rating Summary</h2>
<table><thead><tr><th>Total Reviews</th><th>Average Rating</th></tr></thead><tbody><tr><td><?php echo number_format((int) $platformFeedbackStats['total_reviews']); ?></td><td><?php echo number_format((float) $platformFeedbackStats['average_rating'], 1); ?> / 5</td></tr></tbody></table>
<div class="footer"><div class="line">Prepared by: <?php echo htmlspecialchars($adminName); ?></div><div class="line">Checked by</div></div>
<?php
$crystalReportHtml = ob_get_clean();

if (($_GET['export'] ?? '') === 'excel') {
    $sections = [
        ['title' => 'PICKLED Booking and Revenue Report', 'rows' => [
            ['Prepared by', $adminName],
            ['Date generated', $today->format('Y-m-d H:i:s')],
            ['Coverage period', $dateFrom . ' to ' . $dateTo],
            ['Total bookings', $totalBookings],
            ['Total revenue', number_format($totalRevenue, 2, '.', '')],
            ['Active players', $activePlayers],
            ['Active coaches', $activeCoaches],
        ]],
        ['title' => 'Payment Summary', 'rows' => array_merge([['Status', 'Bookings', 'Amount']], array_map(static fn(array $row): array => [(string) $row['status'], (int) $row['bookings'], number_format((float) $row['amount'], 2, '.', '')], $paymentSummary))],
        ['title' => 'Booking Status Summary', 'rows' => array_merge([['Status', 'Bookings', 'Amount']], array_map(static fn(array $row): array => [(string) $row['status'], (int) $row['bookings'], number_format((float) $row['amount'], 2, '.', '')], $bookingStatusSummary))],
        ['title' => 'Program Performance', 'rows' => array_merge([['Program', 'Category', 'Bookings', 'Revenue']], array_map(static fn(array $row): array => [(string) $row['program_name'], (string) $row['category_name'], (int) $row['bookings'], number_format((float) $row['revenue'], 2, '.', '')], $programPerformance))],
        ['title' => 'Feedback Summary', 'rows' => [['Total Reviews', 'Average Rating'], [(int) $platformFeedbackStats['total_reviews'], number_format((float) $platformFeedbackStats['average_rating'], 1, '.', '')]]],
    ];
    reports_csv_download('pickled-crystal-report-' . $dateFrom . '-to-' . $dateTo . '.csv', $sections);
}

if (($_GET['export'] ?? '') === 'pdf') {
    reports_printable_report([
        'logo' => pickled_asset_url('img/WM-DGreen.png'),
        'html' => $crystalReportHtml,
    ]);
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-app-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?php echo pickled_admin_url('admin-dashboard.php'); ?>"><img src="<?php echo reports_asset('img/WM-DGreen.png'); ?>" alt="Pickled"><span>Admin</span></a>
        <nav class="admin-side-nav" aria-label="Admin navigation">
            <?php foreach ($dashboardNav as $item): ?>
                <?php if ($item['type'] === 'group'): ?>
                    <section class="admin-nav-group"><a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>"><?php echo reports_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span></a><div class="admin-nav-children"><?php foreach ($item['children'] as [$childLabel, $childHref]): ?><a href="<?php echo pickled_admin_url($childHref); ?>"><?php echo htmlspecialchars($childLabel); ?></a><?php endforeach; ?></div></section>
                <?php else: ?>
                    <a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>"><?php echo reports_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="admin-dashboard-main reports-main">
        <header class="admin-topbar reports-topbar">
            <div><h1>Reports &amp; Analytics</h1><p>Business overview and performance insights</p></div>
            <div class="admin-topbar-actions">
                <button class="admin-date-pill reports-range" type="button"><?php echo reports_icon($icons, 'calendar'); ?><span><?php echo htmlspecialchars($rangeStart . ' - ' . $rangeEnd); ?></span></button>
                <form class="reports-filter-form" method="get">
                    <label><span>From</span><input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>"></label>
                    <label><span>To</span><input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>"></label>
                    <button class="bookings-button ghost" type="submit">Filter</button>
                </form>
                <a class="bookings-button ghost reports-export" href="<?php echo htmlspecialchars($pdfExportUrl); ?>">Export PDF</a>
                <a class="bookings-button primary reports-export" href="<?php echo htmlspecialchars($excelExportUrl); ?>">Export Excel</a>
                <a class="admin-notification" href="<?php echo pickled_admin_url('notifications.php'); ?>" aria-label="Notifications"><?php echo reports_icon($icons, 'bell'); ?>
                </a>
                <?php echo pickled_admin_account_menu($adminName, $logoutCsrf, 'topbar'); ?>
            </div>
        </header>

        <section class="reports-kpi-grid" aria-label="Reports summary metrics">
            <article class="reports-kpi-card"><span>Total Bookings</span><strong><?php echo number_format($totalBookings); ?></strong><small><?php echo $totalBookings > 0 ? 'Bookings within selected period' : 'No bookings yet'; ?></small></article>
            <article class="reports-kpi-card"><span>Total Revenue</span><strong>₱<?php echo number_format($totalRevenue, 0); ?></strong><small><?php echo $totalRevenue > 0 ? 'Recorded booking item revenue' : 'No revenue yet'; ?></small></article>
            <article class="reports-kpi-card"><span>Active Players</span><strong><?php echo number_format($activePlayers); ?></strong><small>Players with bookings in this period</small></article>
            <article class="reports-kpi-card"><span>Active Coaches</span><strong><?php echo number_format($activeCoaches); ?></strong><small>Coaches assigned in this period</small></article>
            <article class="reports-kpi-card"><span>Platform Rating</span><strong><?php echo number_format((float) $platformFeedbackStats['average_rating'], 1); ?></strong><small><?php echo number_format((int) $platformFeedbackStats['total_reviews']); ?> reviews in range</small></article>
        </section>

        <section class="reports-insights-grid">
            <article class="reports-panel popular-panel">
                <header><h2>Most Popular Services</h2></header>
                <div class="popular-service-list">
                    <?php foreach ($popularServices as $index => $service): ?>
                        <?php $pct = (int) round(($service['bookings'] / $popularTotal) * 100); ?>
                        <article class="popular-service-item"><b><?php echo $index + 1; ?></b><div><strong><?php echo htmlspecialchars($service['program_name']); ?></strong><small><?php echo number_format((int) $service['bookings']); ?> bookings · ₱<?php echo number_format((float) $service['revenue'], 0); ?></small></div><em><?php echo $pct; ?>%</em></article>
                    <?php endforeach; ?>
                    <?php if (!$popularServices): ?>
                        <p class="reports-empty-state">No booking activity yet. Popular services will appear after customers complete bookings.</p>
                    <?php endif; ?>
                </div>
            </article>

            <article class="reports-panel revenue-panel">
                <header><h2>Revenue by Program</h2></header>
                <?php if ($totalRevenue > 0): ?>
                    <div class="revenue-breakdown">
                        <div class="revenue-list">
                            <?php foreach (array_slice($revenueByProgram, 0, 6) as $program): ?>
                                <?php $pct = (int) round(((float) $program['revenue'] / $totalRevenue) * 100); ?>
                                <article class="revenue-item"><strong><?php echo htmlspecialchars((string) $program['program_name']); ?></strong><i><b style="width: <?php echo min(100, $pct); ?>%"></b></i><em>₱<?php echo number_format((float) $program['revenue'], 0); ?></em><small><?php echo $pct; ?>%</small></article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="reports-empty-state">No revenue data yet. Revenue breakdown will appear after paid or confirmed bookings.</p>
                <?php endif; ?>
            </article>
        </section>

        <section class="reports-bottom-grid reports-report-tables-grid">
            <article class="reports-panel performance-panel">
                <header><h2>Program Performance</h2></header>
                <?php if ($totalBookings > 0): ?>
                    <div class="program-performance-table">
                        <div class="program-row head"><span>Program</span><span>Bookings</span><span>Revenue</span><span>Avg. Revenue / Booking</span><span>Share</span></div>
                        <?php foreach ($programPerformance as $program): ?>
                            <?php $avg = (int) $program['bookings'] > 0 ? ((float) $program['revenue'] / (int) $program['bookings']) : 0; $share = $totalRevenue > 0 ? ((float) $program['revenue'] / $totalRevenue) * 100 : 0; ?>
                            <div class="program-row"><span><?php echo htmlspecialchars((string) $program['program_name']); ?></span><span><?php echo number_format((int) $program['bookings']); ?></span><span>₱<?php echo number_format((float) $program['revenue'], 0); ?></span><span>₱<?php echo number_format($avg, 0); ?></span><span><?php echo number_format($share, 1); ?>%</span></div>
                        <?php endforeach; ?>
                        <div class="program-row total"><span>Total</span><span><?php echo number_format($totalBookings); ?></span><span>₱<?php echo number_format($totalRevenue, 0); ?></span><span>₱<?php echo number_format($totalRevenue / $totalBookings, 0); ?></span><span></span></div>
                    </div>
                <?php else: ?>
                    <p class="reports-empty-state">No program performance yet. This table will populate after booking transactions are recorded.</p>
                <?php endif; ?>
            </article>

            <article class="reports-panel report-export-panel">
                <header><h2>Report Tables</h2><div><a href="<?php echo htmlspecialchars($pdfExportUrl); ?>">PDF</a><a href="<?php echo htmlspecialchars($excelExportUrl); ?>">Excel</a></div></header>
                <section class="crystal-report-list">
                    <article>
                        <h3>Booking Report</h3>
                        <div class="mini-report-table"><span>Date</span><span>Reference</span><span>Player</span><span>Program</span><span>Amount</span><span>Status</span></div>
                    <?php foreach ($recentBookings as $booking): ?>
                        <div class="mini-report-table"><span><?php echo date('M j, Y', strtotime($booking['booking_date'] ?? $booking['created_at'] ?? 'now')); ?></span><span><?php echo htmlspecialchars($booking['reference'] ?? '-'); ?></span><span><?php echo htmlspecialchars($booking['user_name'] ?? 'Guest'); ?></span><span><?php echo htmlspecialchars($booking['program_names'] ?? 'Court Rental'); ?></span><span>₱<?php echo number_format((float) ($booking['total'] ?? 0), 0); ?></span><span><?php echo htmlspecialchars(reports_log_label((string) ($booking['status'] ?? 'pending'))); ?></span></div>
                    <?php endforeach; ?>
                    <?php if (!$recentBookings): ?><p class="reports-empty-state">No booking report data yet.</p><?php endif; ?>
                    </article>
                    <article>
                        <h3>Revenue Report</h3>
                        <div class="mini-report-table three"><span>Period</span><span>Revenue</span><span>Bookings</span><span>Average Revenue</span></div>
                        <?php if ($totalBookings > 0): ?>
                            <div class="mini-report-table three"><span><?php echo htmlspecialchars($rangeStart . ' - ' . $rangeEnd); ?></span><span>₱<?php echo number_format($totalRevenue, 0); ?></span><span><?php echo number_format($totalBookings); ?></span><span>₱<?php echo number_format($totalRevenue / $totalBookings, 0); ?></span></div>
                        <?php else: ?>
                            <p class="reports-empty-state">No revenue report data yet.</p>
                        <?php endif; ?>
                    </article>
                    <article>
                        <h3>Program Report</h3>
                        <?php if ($totalBookings > 0): ?>
                            <?php foreach ($programPerformance as $program): ?>
                                <?php $pct = (int) round(((int) $program['bookings'] / max(1, array_sum(array_map('intval', array_column($programPerformance, 'bookings'))))) * 100); ?>
                                <div class="mini-report-table three"><span><?php echo htmlspecialchars((string) $program['program_name']); ?></span><span><?php echo number_format((int) $program['bookings']); ?> bookings</span><span>₱<?php echo number_format((float) $program['revenue'], 0); ?></span><span><?php echo $pct; ?>%</span></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="reports-empty-state">No program report data yet.</p>
                        <?php endif; ?>
                    </article>
                </section>
            </article>
        </section>

        <section class="crystal-report-section" id="crystal-report">
            <div class="crystal-report-toolbar">
                <div><h2>Crystal Report</h2><p>Formal printable booking and revenue report for the selected coverage period.</p></div>
                <div><a class="bookings-button ghost reports-export" href="<?php echo htmlspecialchars($pdfExportUrl); ?>">Export PDF</a><a class="bookings-button primary reports-export" href="<?php echo htmlspecialchars($excelExportUrl); ?>">Export Excel</a></div>
            </div>
            <article class="crystal-report-sheet">
                <header class="crystal-report-header">
                    <img src="<?php echo reports_asset('img/WM-DGreen.png'); ?>" alt="PICKLED">
                    <div><h2>PICKLED Booking and Revenue Report</h2><p>Coverage Period: <?php echo htmlspecialchars($rangeStart . ' - ' . $rangeEnd); ?></p><p>Generated: <?php echo htmlspecialchars($today->format('F j, Y g:i A')); ?></p></div>
                </header>
                <?php echo $crystalReportHtml; ?>
            </article>
        </section>

        <section class="reports-bottom-grid" id="feedback">
            <article class="reports-panel performance-panel">
                <header><h2>Coach Ratings Summary</h2></header>
                <div class="program-performance-table">
                    <div class="program-row head"><span>Coach</span><span>Email</span><span>Average Rating</span><span>Total Reviews</span><span>Status</span></div>
                    <?php foreach ($coachFeedbackSummary as $coachRow): ?>
                        <div class="program-row">
                            <span><?php echo htmlspecialchars($coachRow['coach_name'] ?? 'Coach'); ?></span>
                            <span><?php echo htmlspecialchars($coachRow['coach_email'] ?? '-'); ?></span>
                            <span><?php echo number_format((float) ($coachRow['average_rating'] ?? 0), 1); ?> / 5</span>
                            <span><?php echo number_format((int) ($coachRow['total_reviews'] ?? 0)); ?></span>
                            <span><?php echo ((int) ($coachRow['total_reviews'] ?? 0)) > 0 ? 'Reviewed' : 'No reviews yet'; ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$coachFeedbackSummary): ?>
                        <div class="program-row"><span>No coaches found.</span><span></span><span></span><span></span><span></span></div>
                    <?php endif; ?>
                </div>
            </article>

            <article class="reports-panel report-export-panel">
                <header><h2>All Feedback</h2></header>
                <form class="booking-filter-bar" method="get">
                    <input type="hidden" name="feedback_section" value="1">
                    <select name="feedback_rating">
                        <option value="">All ratings</option>
                        <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                            <option value="<?php echo $rating; ?>" <?php echo $feedbackRatingFilter === $rating ? 'selected' : ''; ?>><?php echo $rating; ?> / 5</option>
                        <?php endfor; ?>
                    </select>
                    <input type="search" name="feedback_q" value="<?php echo htmlspecialchars($feedbackSearch); ?>" placeholder="Search comments, booking, player, or coach">
                    <button type="submit">Apply</button>
                </form>
                <section class="crystal-report-list">
                    <?php foreach ($feedbackRows as $review): ?>
                        <article>
                            <h3><?php echo (int) $review['rating']; ?> / 5 - <?php echo htmlspecialchars($review['reference'] ?? 'Booking'); ?></h3>
                            <div class="mini-report-table three">
                                <span><?php echo htmlspecialchars($review['user_name'] ?? 'Player'); ?></span>
                                <span><?php echo htmlspecialchars($review['coach_name'] ?? 'No coach assigned'); ?></span>
                                <span><?php echo htmlspecialchars(date('M j, Y', strtotime((string) $review['created_at']))); ?></span>
                                <span><?php echo htmlspecialchars($review['booking_item_name'] ?? 'Overall booking'); ?></span>
                            </div>
                            <p><?php echo htmlspecialchars((string) ($review['comment'] ?? '')); ?></p>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$feedbackRows): ?>
                        <article><h3>No feedback found</h3><p>Completed booking reviews will appear here.</p></article>
                    <?php endif; ?>
                </section>
            </article>
        </section>

        <section class="reports-bottom-grid" id="activity-logs">
            <article class="reports-panel performance-panel">
                <header><h2>Activity Logs</h2></header>
                <form class="booking-filter-bar" method="get">
                    <input type="hidden" name="logs_section" value="1">
                    <select name="log_action">
                        <option value="">All actions</option>
                        <?php foreach ($adminLogActions as $actionOption): ?>
                            <option value="<?php echo htmlspecialchars($actionOption); ?>" <?php echo $logActionFilter === $actionOption ? 'selected' : ''; ?>><?php echo htmlspecialchars(reports_log_label((string) $actionOption)); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="log_entity_type">
                        <option value="">All entities</option>
                        <?php foreach ($adminLogEntities as $entityOption): ?>
                            <option value="<?php echo htmlspecialchars($entityOption); ?>" <?php echo $logEntityFilter === $entityOption ? 'selected' : ''; ?>><?php echo htmlspecialchars(reports_log_label((string) $entityOption)); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="log_sort">
                        <option value="desc" <?php echo $logSort === 'desc' ? 'selected' : ''; ?>>Newest first</option>
                        <option value="asc" <?php echo $logSort === 'asc' ? 'selected' : ''; ?>>Oldest first</option>
                    </select>
                    <input type="search" name="log_q" value="<?php echo htmlspecialchars($logSearch); ?>" placeholder="Search action, description, or admin">
                    <button type="submit">Apply</button>
                </form>
                <div class="program-performance-table">
                    <div class="program-row head"><span>Date</span><span>Admin</span><span>Action</span><span>Entity</span><span>Description</span></div>
                    <?php foreach ($adminLogRows as $logRow): ?>
                        <div class="program-row report-green">
                            <span><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime((string) $logRow['created_at']))); ?></span>
                            <span><?php echo htmlspecialchars($logRow['admin_name'] ?? 'User #' . (string) $logRow['admin_id']); ?></span>
                            <span><?php echo htmlspecialchars(reports_log_label((string) $logRow['action'])); ?></span>
                            <span><?php echo htmlspecialchars(reports_log_label((string) $logRow['entity_type'])); ?><?php echo !empty($logRow['entity_id']) ? ' #' . (int) $logRow['entity_id'] : ''; ?></span>
                            <span><?php echo htmlspecialchars((string) ($logRow['description'] ?? '')); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$adminLogRows): ?>
                        <div class="program-row"><span>No activity logs found.</span><span></span><span></span><span></span><span></span></div>
                    <?php endif; ?>
                </div>
            </article>

            <article class="reports-panel report-export-panel">
                <header><h2>Log Search</h2></header>
                <section class="crystal-report-list">
                    <article>
                        <h3>Available Filters</h3>
                        <p>Use action, entity, search text, and date sorting to review admin activity. Logs are written automatically by booking, payment, catalog, scheduling, notification, and expiry workflows.</p>
                    </article>
                </section>
            </article>
        </section>
    </main>
</div>

<script src="<?php echo pickled_admin_asset_url('js/admin.js'); ?>"></script>
</body>
</html>
