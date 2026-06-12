<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../app/services/SchedulingService.php';

pickled_start_secure_session();
pickled_init_csrf();

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'coach') {
    header('Location: ' . pickled_frontend_url('auth/login.php?role=coach&redirect=coach/availability.php'));
    exit;
}

$coach = $_SESSION['user'];
$coachId = (int) ($coach['id'] ?? 0);
$coachName = $coach['name'] ?? 'Coach Mia';
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayLabel = $today->format('M j, Y (D)');
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');
$schedulingService = new SchedulingService();
$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $errorMsg = 'Invalid form submission. Please try again.';
    } else {
        try {
            $action = (string) ($_POST['action'] ?? '');
            $payload = $_POST + ['coach_user_id' => $coachId];
            if ($action === 'create_availability') {
                $schedulingService->createAvailability($payload);
                $successMsg = 'Availability slot added.';
            } elseif ($action === 'update_availability') {
                $schedulingService->updateAvailability((int) ($_POST['availability_id'] ?? 0), $payload);
                $successMsg = 'Availability slot updated.';
            } elseif ($action === 'disable_availability') {
                $schedulingService->setAvailabilityStatus((int) ($_POST['availability_id'] ?? 0), 'unavailable');
                $successMsg = 'Availability slot disabled.';
            }
        } catch (Throwable $e) {
            error_log('Coach availability action failed: ' . $e->getMessage());
            $errorMsg = $e instanceof RuntimeException ? $e->getMessage() : 'Unable to save availability.';
        }
    }
}

function availability_icon(array $icons, string $name): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['clock']) . '</svg>';
}

function availability_h(mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
    'check' => '<path d="m20 6-11 11-5-5"/>',
    'timer' => '<circle cx="12" cy="13" r="8"/><path d="M12 9v4l3 2M9 2h6M12 2v3"/>',
    'gear' => '<path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.6-1H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.6V3a2 2 0 1 1 4 0v.09a1.7 1.7 0 0 0 1 1.51 1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.16.4.56.82 1.1.9H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.51 1.1Z"/>',
    'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>',
    'sunset' => '<path d="M12 10a5 5 0 0 0-5 5h10a5 5 0 0 0-5-5Z"/><path d="M12 2v4M4.22 7.22l2.12 2.12M1 15h22M17.66 9.34l2.12-2.12"/>',
    'trash' => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 15H6L5 6"/><path d="M10 11v6M14 11v6"/>',
    'more' => '<circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>',
    'arrow' => '<path d="m9 18 6-6-6-6"/>',
];

$navItems = [
    ['Dashboard', pickled_frontend_url('coach/dashboard.php'), 'home', false],
    ['My Schedule', pickled_frontend_url('coach/schedule.php'), 'calendar', false],
    ['Students', pickled_frontend_url('coach/students.php'), 'students', false],
    ['Availability', pickled_frontend_url('coach/availability.php'), 'clock', true],
    ['Announcements', pickled_frontend_url('coach/announcements.php'), 'megaphone', false],
    ['Profile', pickled_frontend_url('coach/profile.php'), 'profile', false],
];

$availabilityRows = $coachId ? $schedulingService->availabilityForCoach($coachId, true) : [];
$weekStart = $today->modify('monday this week');
$times = ['7 AM', '8 AM', '9 AM', '10 AM', '11 AM', '12 PM', '1 PM', '2 PM', '3 PM', '4 PM', '5 PM', '6 PM', '7 PM', '8 PM'];
$days = [];
$dayNumbers = [1, 2, 3, 4, 5, 6, 0];
$dayKeys = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
foreach ($dayNumbers as $index => $dayNumber) {
    $date = $weekStart->modify('+' . $index . ' days');
    $days[] = [$dayKeys[$index], strtoupper($date->format('D')), strtoupper($date->format('M j')), $dayNumber];
}
$slotMap = array_fill_keys($dayKeys, array_fill(0, count($times), 'unavailable'));
foreach ($availabilityRows as $row) {
    $dayIndex = array_search((int) $row['day_of_week'], $dayNumbers, true);
    if ($dayIndex === false) {
        continue;
    }
    $key = $dayKeys[$dayIndex];
    $startHour = (int) substr((string) $row['start_time'], 0, 2);
    $endHour = (int) substr((string) $row['end_time'], 0, 2);
    for ($hour = $startHour; $hour < $endHour; $hour++) {
        $slotIndex = $hour - 7;
        if (isset($slotMap[$key][$slotIndex])) {
            $slotMap[$key][$slotIndex] = $row['status'] === 'available' ? 'available' : 'unavailable';
        }
    }
}

$slotLabels = [
    'available' => 'Available',
    'partial' => 'Partially Booked',
    'full' => 'Fully Booked',
    'unavailable' => 'Unavailable',
];

$rules = [
    ['Daily Start Time', '08:00 AM', 'gear'],
    ['Daily End Time', '08:00 PM', 'clock'],
    ['Default Session Duration', '60 minutes', 'clock'],
    ['Break Between Sessions', '15 minutes', 'timer'],
    ['Max Sessions Per Day', '8 sessions', 'calendar'],
    ['Min Notice for Booking', '2 hours', 'clock'],
];

$coachSessions = $coachId ? $schedulingService->sessionsBetween($coachId, $today->format('Y-m-d'), $today->modify('+30 days')->format('Y-m-d')) : [];
$upcomingSessions = array_map(static fn(array $session): array => [
    (new DateTimeImmutable('1970-01-01 ' . $session['start_time']))->format('g:i A'),
    (new DateTimeImmutable('1970-01-01 ' . $session['end_time']))->format('g:i A'),
    $session['name'],
    $session['court'],
    (int) $session['booked_count'] . ' / ' . (int) $session['capacity'] . ' Players',
], array_slice($coachSessions, 0, 4));
$availableDays = count(array_unique(array_map(static fn(array $row): int => (int) $row['day_of_week'], array_filter($availabilityRows, static fn(array $row): bool => $row['status'] === 'available'))));
$availableSlots = count(array_filter($availabilityRows, static fn(array $row): bool => $row['status'] === 'available'));
$bookedSessions = count($coachSessions);
$nextSessionLabel = $upcomingSessions[0][0] ?? 'None';

$templates = [
    ['Morning Coach', '8:00 AM - 12:00 PM', 'Mon - Fri', 'sun', 'orange'],
    ['Evening Coach', '4:00 PM - 9:00 PM', 'Mon - Fri', 'sunset', 'pink'],
    ['Weekend Coach', '8:00 AM - 6:00 PM', 'Sat - Sun', 'calendar', 'purple'],
];

$timeOff = [
    ['JUN 18', 'Wed, June 18, 2026', 'Personal Leave'],
    ['JUN 24', 'Tue, June 24 - Thu, June 26, 2026', 'Vacation'],
    ['JUL 01', 'Tue, July 1, 2026', 'Medical Appointment'],
];

$notifications = [
    ['Booking Requests', '3 Pending', 'orange'],
    ['Schedule Conflicts', '0', 'green'],
    ['Reschedule Requests', '1', 'blue'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Availability - Pickled Coach</title>
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
                <a class="<?php echo $active ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($href); ?>"><?php echo availability_icon($icons, $icon); ?><span><?php echo htmlspecialchars($label); ?></span><?php if ($label === 'Announcements'): ?><em>4</em><?php endif; ?></a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="coach-main availability-main">
        <header class="coach-topbar">
            <div><h1>Availability</h1></div>
            <div class="coach-top-actions">
                <button class="coach-date-pill" type="button"><?php echo availability_icon($icons, 'calendar'); ?><span><?php echo htmlspecialchars($todayLabel); ?></span></button>
                <a class="coach-notification" href="<?php echo htmlspecialchars(pickled_frontend_url('coach/announcements.php')); ?>" aria-label="Announcements"><?php echo availability_icon($icons, 'bell'); ?><em>4</em></a>
                <details class="coach-top-profile">
                    <summary><span class="coach-photo small"><img src="<?php echo htmlspecialchars(pickled_asset_url('img/court/academy.png')); ?>" alt="<?php echo htmlspecialchars($coachName); ?>"></span><span><strong>Coach</strong><small>Pickleball Coach</small></span><b>⌄</b></summary>
                    <form method="post" action="<?php echo htmlspecialchars(pickled_frontend_url('auth/logout.php')); ?>"><input type="hidden" name="csrf_token" value="<?php echo $logoutCsrf; ?>"><button type="submit">Logout</button></form>
                </details>
            </div>
        </header>

        <?php if ($successMsg): ?><div class="alert alert-success"><?php echo availability_h($successMsg); ?></div><?php endif; ?>
        <?php if ($errorMsg): ?><div class="alert alert-error"><?php echo availability_h($errorMsg); ?></div><?php endif; ?>

        <section class="coach-kpi-grid availability-kpis page-first-section">
            <article class="coach-kpi green"><?php echo availability_icon($icons, 'calendar'); ?><div><span>Available Days</span><strong><?php echo number_format($availableDays); ?> Days</strong><small>This Week</small></div></article>
            <article class="coach-kpi blue"><?php echo availability_icon($icons, 'clock'); ?><div><span>Available Slots</span><strong><?php echo number_format($availableSlots); ?> Slots</strong><small>Configured</small></div></article>
            <article class="coach-kpi orange"><?php echo availability_icon($icons, 'students'); ?><div><span>Booked Sessions</span><strong><?php echo number_format($bookedSessions); ?> Sessions</strong><small>Next 30 Days</small></div></article>
            <article class="coach-kpi purple"><?php echo availability_icon($icons, 'timer'); ?><div><span>Upcoming Session</span><strong><?php echo availability_h($nextSessionLabel); ?></strong><small>From MySQL</small></div></article>
        </section>

        <section class="coach-card availability-template-card">
            <header><h2><?php echo availability_icon($icons, 'clock'); ?>Manage Availability</h2></header>
            <form class="catalog-admin-form catalog-admin-form-wide" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo availability_h(pickled_csrf_token()); ?>">
                <label><span>Day</span><select name="day_of_week"><option value="1">Monday</option><option value="2">Tuesday</option><option value="3">Wednesday</option><option value="4">Thursday</option><option value="5">Friday</option><option value="6">Saturday</option><option value="0">Sunday</option></select></label>
                <label><span>Start</span><input type="time" name="start_time" value="09:00" required></label>
                <label><span>End</span><input type="time" name="end_time" value="10:00" required></label>
                <label><span>Status</span><select name="status"><option value="available">Available</option><option value="unavailable">Unavailable</option><option value="leave">Leave</option></select></label>
                <button type="submit" name="action" value="create_availability">Add Slot</button>
            </form>
            <div class="catalog-admin-list">
                <?php foreach ($availabilityRows as $row): ?>
                    <article class="catalog-admin-row">
                        <form class="catalog-admin-form catalog-inline-form" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo availability_h(pickled_csrf_token()); ?>">
                            <input type="hidden" name="availability_id" value="<?php echo (int) $row['id']; ?>">
                            <label><span>Day</span><select name="day_of_week"><?php foreach ([0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'] as $number => $label): ?><option value="<?php echo $number; ?>" <?php echo (int) $row['day_of_week'] === $number ? 'selected' : ''; ?>><?php echo availability_h($label); ?></option><?php endforeach; ?></select></label>
                            <label><span>Start</span><input type="time" name="start_time" value="<?php echo availability_h(substr((string) $row['start_time'], 0, 5)); ?>" required></label>
                            <label><span>End</span><input type="time" name="end_time" value="<?php echo availability_h(substr((string) $row['end_time'], 0, 5)); ?>" required></label>
                            <label><span>Status</span><select name="status"><option value="available" <?php echo $row['status'] === 'available' ? 'selected' : ''; ?>>Available</option><option value="unavailable" <?php echo $row['status'] === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option><option value="leave" <?php echo $row['status'] === 'leave' ? 'selected' : ''; ?>>Leave</option></select></label>
                            <button type="submit" name="action" value="update_availability">Save</button>
                        </form>
                        <form class="catalog-status-form" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo availability_h(pickled_csrf_token()); ?>">
                            <input type="hidden" name="availability_id" value="<?php echo (int) $row['id']; ?>">
                            <button class="danger" type="submit" name="action" value="disable_availability">Disable</button>
                        </form>
                    </article>
                <?php endforeach; ?>
                <?php if (!$availabilityRows): ?><p class="catalog-empty-state">No availability slots yet.</p><?php endif; ?>
            </div>
        </section>

        <section class="availability-workspace">
            <div class="availability-center">
                <section class="coach-card availability-grid-card">
                    <header>
                        <h2><?php echo availability_icon($icons, 'calendar'); ?>Weekly Availability</h2>
                        <div class="availability-actions"><button>Apply Mon-Fri Schedule</button><button>Apply All Week</button><button><?php echo availability_icon($icons, 'trash'); ?> Clear Week</button></div>
                    </header>
                    <div class="availability-grid-scroll">
                        <div class="availability-grid-table">
                            <div class="availability-head time-head">Time</div>
                            <?php foreach ($days as [$key, $day, $date]): ?>
                                <div class="availability-head"><strong><?php echo htmlspecialchars($day); ?></strong><span><?php echo htmlspecialchars($date); ?></span></div>
                            <?php endforeach; ?>
                            <?php foreach ($times as $row => $time): ?>
                                <div class="availability-time"><?php echo htmlspecialchars($time); ?></div>
                                <?php foreach ($days as [$key]): ?>
                                    <?php $state = $slotMap[$key][$row]; ?>
                                    <button class="availability-slot <?php echo htmlspecialchars($state); ?>"><?php echo htmlspecialchars($slotLabels[$state]); ?></button>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <footer class="availability-legend">
                        <span><i class="available"></i>Available</span><span><i class="partial"></i>Partially Booked</span><span><i class="full"></i>Fully Booked</span><span><i class="unavailable"></i>Unavailable</span>
                    </footer>
                </section>

                <section class="availability-bottom-grid">
                    <article class="coach-card availability-template-card">
                        <header><h2>Availability Templates</h2><a href="#">Manage Templates</a></header>
                        <div class="template-grid">
                            <?php foreach ($templates as [$title, $hours, $daysLabel, $icon, $tone]): ?>
                                <article class="template-item <?php echo htmlspecialchars($tone); ?>"><?php echo availability_icon($icons, $icon); ?><div><strong><?php echo htmlspecialchars($title); ?></strong><span><?php echo htmlspecialchars($hours); ?></span><small><?php echo htmlspecialchars($daysLabel); ?></small></div><button>Apply</button></article>
                            <?php endforeach; ?>
                        </div>
                    </article>

                    <article class="coach-card timeoff-card">
                        <header><h2>Time Off</h2><a href="#">Add Time Off</a></header>
                        <div class="timeoff-list">
                            <?php foreach ($timeOff as [$date, $fullDate, $reason]): ?>
                                <article><time><?php echo htmlspecialchars($date); ?></time><div><strong><?php echo htmlspecialchars($fullDate); ?></strong><span><?php echo htmlspecialchars($reason); ?></span></div><button><?php echo availability_icon($icons, 'more'); ?></button></article>
                            <?php endforeach; ?>
                        </div>
                    </article>
                </section>
            </div>

            <aside class="availability-side">
                <section class="coach-card rules-card">
                    <header><h2>Availability Rules</h2><a href="#">Edit</a></header>
                    <div class="rules-list">
                        <?php foreach ($rules as [$label, $value, $icon]): ?>
                            <article><?php echo availability_icon($icons, $icon); ?><span><?php echo htmlspecialchars($label); ?></span><strong><?php echo htmlspecialchars($value); ?></strong></article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="coach-card upcoming-availability-card">
                    <header><h2>Upcoming Sessions</h2><a href="<?php echo htmlspecialchars(pickled_frontend_url('coach/schedule.php')); ?>">View Full Schedule</a></header>
                    <small>TODAY - WED, JUN 11</small>
                    <div class="upcoming-availability-list">
                        <?php foreach ($upcomingSessions as $index => [$start, $end, $title, $court, $count]): ?>
                            <?php if ($index === 2): ?><small>TOMORROW - THU, JUN 12</small><?php endif; ?>
                            <article><time><strong><?php echo htmlspecialchars($start); ?></strong><span><?php echo htmlspecialchars($end); ?></span></time><div><strong><?php echo htmlspecialchars($title); ?></strong><span><?php echo htmlspecialchars($court); ?> • <?php echo htmlspecialchars($count); ?></span></div><em>Confirmed</em></article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="coach-card availability-notices-card" id="announcements">
                    <header><h2>Notifications</h2></header>
                    <div class="availability-notices">
                        <?php foreach ($notifications as [$label, $count, $tone]): ?>
                            <a href="#"><span><?php echo availability_icon($icons, 'calendar'); ?><?php echo htmlspecialchars($label); ?></span><strong class="<?php echo htmlspecialchars($tone); ?>"><?php echo htmlspecialchars($count); ?></strong><?php echo availability_icon($icons, 'arrow'); ?></a>
                        <?php endforeach; ?>
                    </div>
                </section>
            </aside>
        </section>
    </main>
</div>
</body>
</html>
