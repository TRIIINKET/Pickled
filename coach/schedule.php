<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../app/services/SchedulingService.php';

pickled_start_secure_session();
pickled_init_csrf();

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'coach') {
    header('Location: ' . pickled_frontend_url('auth/login.php?role=coach&redirect=coach/schedule.php'));
    exit;
}

$coach = $_SESSION['user'];
$coachId = (int) ($coach['id'] ?? 0);
$coachName = $coach['name'] ?? 'Coach Mia';
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayDate = $today->format('Y-m-d');
$todayLabel = $today->format('M j, Y (D)');
$scheduleDateLabel = $today->format('l, F j, Y');
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');
$schedulingService = new SchedulingService();

function schedule_icon(array $icons, string $name): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['calendar']) . '</svg>';
}

$icons = [
    'home' => '<path d="M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
    'students' => '<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'megaphone' => '<path d="m3 11 18-5v12L3 13z"/><path d="M11 14v4a2 2 0 0 1-4 0v-5"/>',
    'profile' => '<path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/>',
    'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
    'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 3.08 5.18 2 2 0 0 1 5.06 3h3a2 2 0 0 1 2 1.72c.12.9.33 1.77.63 2.61a2 2 0 0 1-.45 2.11L9 10.69a16 16 0 0 0 4.31 4.31l1.25-1.24a2 2 0 0 1 2.11-.45c.84.3 1.71.51 2.61.63A2 2 0 0 1 22 16.92Z"/>',
    'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    'arrow' => '<path d="m9 18 6-6-6-6"/>',
    'check' => '<path d="m20 6-11 11-5-5"/>',
    'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
    'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    'more' => '<circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>',
    'court' => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M12 4v16M4 12h16"/>',
];

$navItems = [
    ['Dashboard', pickled_frontend_url('coach/dashboard.php'), 'home', false],
    ['My Schedule', pickled_frontend_url('coach/schedule.php'), 'calendar', true],
    ['Students', pickled_frontend_url('coach/students.php'), 'students', false],
    ['Availability', pickled_frontend_url('coach/availability.php'), 'clock', false],
    ['Announcements', pickled_frontend_url('coach/announcements.php'), 'megaphone', false],
    ['Profile', pickled_frontend_url('coach/profile.php'), 'profile', false],
];

$weekStart = $today->modify('monday this week');
$weekEnd = $weekStart->modify('+6 days');
$coachSessions = $coachId ? $schedulingService->sessionsBetween($coachId, $weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')) : [];
$dayKeyByNumber = [0 => 'sun', 1 => 'mon', 2 => 'tue', 3 => 'wed', 4 => 'thu', 5 => 'fri', 6 => 'sat'];
$sessions = array_map(static function (array $session) use ($dayKeyByNumber): array {
    $dayNumber = (int) (new DateTimeImmutable($session['session_date']))->format('w');
    $startHour = (int) substr((string) $session['start_time'], 0, 2);
    $endHour = (int) substr((string) $session['end_time'], 0, 2);
    $tone = str_contains(strtolower((string) $session['court']), 'pink') ? 'pink' : 'green';
    return [
        $session['name'],
        $session['session_time'],
        $session['court'],
        (int) $session['booked_count'] . ' / ' . (int) $session['capacity'] . ' players',
        ucfirst((string) $session['status']),
        $tone,
        $dayKeyByNumber[$dayNumber] ?? 'mon',
        (string) $startHour,
        (string) max(1, ($endHour - $startHour) * 1.7),
    ];
}, $coachSessions);

$todaySessions = array_map(static function (array $session): array {
    $tone = str_contains(strtolower((string) $session['court']), 'pink') ? 'pink' : 'green';
    return [
        $session['session_time'],
        $session['name'],
        $session['court'],
        (int) $session['booked_count'] . ' / ' . (int) $session['capacity'] . ' players',
        ucfirst((string) $session['status']),
        $tone,
    ];
}, array_values(array_filter($coachSessions, fn(array $session): bool => $session['session_date'] === $todayDate)));
$nextScheduleSession = $coachSessions[0] ?? null;
$studentsToday = array_sum(array_map(static fn(array $session): int => (int) $session['booked_count'], array_filter($coachSessions, fn(array $session): bool => $session['session_date'] === $todayDate)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - Pickled Coach</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(pickled_asset_url('css/coach-dashboard.css')); ?>">
</head>
<body class="coach-portal-body">
<div class="coach-app-shell">
    <aside class="coach-sidebar">
        <a class="coach-brand" href="<?php echo htmlspecialchars(pickled_frontend_url('coach/dashboard.php')); ?>"><img src="<?php echo htmlspecialchars(pickled_asset_url('img/LM-DGreen.png')); ?>" alt="Pickled"><span>Coach</span></a>
        <nav class="coach-nav" aria-label="Coach navigation">
            <?php foreach ($navItems as [$label, $href, $icon, $active]): ?>
                <a class="<?php echo $active ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($href); ?>"><?php echo schedule_icon($icons, $icon); ?><span><?php echo htmlspecialchars($label); ?></span><?php if ($label === 'Announcements'): ?><em>4</em><?php endif; ?></a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="coach-main schedule-main">
        <header class="coach-topbar">
            <div><h1>My Schedule</h1></div>
            <div class="coach-top-actions">
                <button class="coach-date-pill" type="button"><?php echo schedule_icon($icons, 'calendar'); ?><span><?php echo htmlspecialchars($todayLabel); ?></span></button>
                <a class="coach-notification" href="<?php echo htmlspecialchars(pickled_frontend_url('coach/announcements.php')); ?>" aria-label="Announcements"><?php echo schedule_icon($icons, 'bell'); ?><em>4</em></a>
                <details class="coach-top-profile">
                    <summary><span class="coach-photo small"><img src="<?php echo htmlspecialchars(pickled_asset_url('img/court/academy.png')); ?>" alt="<?php echo htmlspecialchars($coachName); ?>"></span><span><strong>Coach</strong><small>Pickleball Coach</small></span><b>⌄</b></summary>
                    <form method="post" action="<?php echo htmlspecialchars(pickled_frontend_url('auth/logout.php')); ?>"><input type="hidden" name="csrf_token" value="<?php echo $logoutCsrf; ?>"><button type="submit">Logout</button></form>
                </details>
            </div>
        </header>

        <section class="schedule-hero-row page-first-section">
            <div class="coach-kpi-grid schedule-kpis">
                <article class="coach-kpi green"><?php echo schedule_icon($icons, 'calendar'); ?><div><span>Today's Sessions</span><strong><?php echo number_format(count($todaySessions)); ?></strong><small>From MySQL</small></div></article>
                <article class="coach-kpi pink"><?php echo schedule_icon($icons, 'calendar'); ?><div><span>Upcoming This Week</span><strong><?php echo number_format(count($coachSessions)); ?></strong><small>Sessions</small></div></article>
                <article class="coach-kpi orange"><?php echo schedule_icon($icons, 'students'); ?><div><span>Students Today</span><strong><?php echo number_format($studentsToday); ?></strong><small>Across sessions</small></div></article>
                <article class="coach-kpi purple"><?php echo schedule_icon($icons, 'clock'); ?><div><span>Next Session</span><strong><?php echo htmlspecialchars($nextScheduleSession['session_time'] ?? 'None'); ?></strong><small><?php echo htmlspecialchars($nextScheduleSession['name'] ?? 'No upcoming session'); ?></small></div></article>
            </div>
            <label class="availability-toggle"><span>Available Today</span><input type="checkbox" checked><b></b></label>
        </section>

        <section class="schedule-workspace">
            <div class="schedule-main-column">
                <section class="coach-card calendar-card">
                    <div class="schedule-toolbar">
                        <div class="schedule-tabs"><button>Today</button><button class="active">Week</button><button>Month</button></div>
                        <div class="schedule-range"><button>‹</button><span><?php echo schedule_icon($icons, 'calendar'); ?> Jun 9 - Jun 15, 2026</span><button>›</button></div>
                        <label class="schedule-search"><?php echo schedule_icon($icons, 'search'); ?><input type="search" placeholder="Search session..."></label>
                        <select><option>All Courts</option><option>Court Green</option><option>Court Pink</option></select>
                        <select><option>All Programs</option><option>Kids Class</option><option>Private Coaching</option><option>Social Play</option></select>
                    </div>
                    <div class="week-calendar-grid">
                        <div class="time-col"><?php foreach (['8 AM','9 AM','10 AM','11 AM','12 PM','1 PM','2 PM','3 PM','4 PM','5 PM','6 PM','7 PM'] as $time): ?><span><?php echo $time; ?></span><?php endforeach; ?></div>
                        <?php foreach ([['mon','MON 9'],['tue','TUE 10'],['wed','WED 11'],['thu','THU 12'],['fri','FRI 13'],['sat','SAT 14'],['sun','SUN 15']] as [$dayKey, $dayLabel]): ?>
                            <div class="day-col <?php echo $dayKey === 'wed' ? 'today' : ''; ?>"><strong><?php echo $dayLabel; ?></strong>
                                <?php foreach ($sessions as [$name, $time, $court, $count, $status, $tone, $day, $start, $span]): ?>
                                    <?php if ($day === $dayKey): ?><article class="calendar-block <?php echo $tone; ?>" style="--start:<?php echo htmlspecialchars($start); ?>;--span:<?php echo htmlspecialchars($span); ?>"><b><?php echo htmlspecialchars($name); ?></b><span><?php echo htmlspecialchars($time); ?></span><small><?php echo htmlspecialchars($court); ?></small><small><?php echo htmlspecialchars($count); ?></small></article><?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="coach-card today-table-card">
                    <header><h2>Today's Sessions <span>(<?php echo htmlspecialchars($scheduleDateLabel); ?>)</span></h2></header>
                    <div class="schedule-table">
                        <div class="schedule-row head"><span>Time</span><span>Program</span><span>Court</span><span>Students</span><span>Status</span><span>Action</span></div>
                        <?php foreach ($todaySessions as [$time, $program, $court, $count, $status, $tone]): ?>
                            <div class="schedule-row <?php echo $program === 'Youth Development (Ages 11-17)' ? 'selected' : ''; ?>"><span><?php echo htmlspecialchars($time); ?></span><span><?php echo htmlspecialchars($program); ?></span><span><i class="<?php echo $tone; ?>"></i><?php echo htmlspecialchars($court); ?></span><span><?php echo htmlspecialchars($count); ?></span><span><em class="session-status <?php echo strtolower($status); ?>"><?php echo htmlspecialchars($status); ?></em></span><span><button><?php echo schedule_icon($icons, 'eye'); ?> View</button><button class="icon-only"><?php echo schedule_icon($icons, 'more'); ?></button></span></div>
                        <?php endforeach; ?>
                        <?php if (!$todaySessions): ?><div class="schedule-row"><span>No sessions today.</span><span></span><span></span><span></span><span></span><span></span></div><?php endif; ?>
                    </div>
                </section>
            </div>

            <aside class="coach-card session-details-panel">
                <header><h2>Session Details</h2><em class="session-status confirmed"><?php echo htmlspecialchars(ucfirst((string) ($nextScheduleSession['status'] ?? 'open'))); ?></em></header>
                <div class="session-detail-title"><i><?php echo schedule_icon($icons, 'calendar'); ?></i><div><strong><?php echo htmlspecialchars($nextScheduleSession['name'] ?? 'No session selected'); ?></strong><span><?php echo htmlspecialchars($nextScheduleSession['category'] ?? 'Schedule details'); ?></span></div></div>
                <dl>
                    <div><dt><?php echo schedule_icon($icons, 'clock'); ?> Time</dt><dd><?php echo htmlspecialchars($nextScheduleSession['session_time'] ?? 'Not scheduled'); ?></dd></div>
                    <div><dt><?php echo schedule_icon($icons, 'calendar'); ?> Date</dt><dd><?php echo htmlspecialchars($nextScheduleSession['display_date'] ?? 'Not scheduled'); ?></dd></div>
                    <div><dt><?php echo schedule_icon($icons, 'court'); ?> Court</dt><dd><?php echo htmlspecialchars($nextScheduleSession['court'] ?? 'No court'); ?></dd></div>
                    <div><dt><?php echo schedule_icon($icons, 'students'); ?> Students</dt><dd><?php echo htmlspecialchars(isset($nextScheduleSession['booked_count']) ? ((int) $nextScheduleSession['booked_count'] . ' / ' . (int) $nextScheduleSession['capacity']) : '0'); ?></dd></div>
                    <div><dt><?php echo schedule_icon($icons, 'profile'); ?> Coach</dt><dd><?php echo htmlspecialchars($coachName); ?></dd></div>
                </dl>
                <section class="attendance-box"><h3>Attendance</h3><div><button class="active"><?php echo schedule_icon($icons, 'check'); ?> Present</button><button><?php echo schedule_icon($icons, 'x'); ?> Absent</button><button><?php echo schedule_icon($icons, 'clock'); ?> Late</button></div></section>
                <section class="notes-box"><header><h3>Session Notes</h3><button>Edit</button></header><textarea rows="6">Focus on footwork and dinks today.
Students showed good improvement on third shot drops.</textarea><button class="save-notes">Save Notes</button></section>
            </aside>
        </section>

        <footer class="coach-footer">© <?php echo date('Y'); ?> Pickled. All rights reserved.</footer>
    </main>
</div>
</body>
</html>
