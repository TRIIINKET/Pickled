<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../app/services/SchedulingService.php';
require_once __DIR__ . '/../app/services/NotificationService.php';

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
$notificationService = new NotificationService();
$coachUnreadCount = $coachId > 0 ? $notificationService->unreadCount($coachId) : 0;
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
            } elseif ($action === 'create_time_off') {
                $schedulingService->createTimeOffRequest($payload);
                $successMsg = 'Time off request submitted.';
            } elseif ($action === 'update_time_off') {
                $schedulingService->updateTimeOffRequest((int) ($_POST['time_off_id'] ?? 0), $payload);
                $successMsg = 'Time off request updated.';
            } elseif ($action === 'cancel_time_off') {
                $schedulingService->cancelTimeOffRequest((int) ($_POST['time_off_id'] ?? 0), $coachId);
                $successMsg = 'Time off request cancelled.';
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

function availability_status_class(string $status): string {
    return strtolower(str_replace(' ', '-', $status));
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
$weekEnd = $weekStart->modify('+6 days');
$times = ['8 AM', '9 AM', '10 AM', '11 AM', '12 PM', '1 PM', '2 PM', '3 PM', '4 PM', '5 PM', '6 PM', '7 PM', '8 PM', '9 PM'];
$days = [];
$dayNumbers = [1, 2, 3, 4, 5, 6, 0];
$dayKeys = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
foreach ($dayNumbers as $index => $dayNumber) {
    $date = $weekStart->modify('+' . $index . ' days');
    $days[] = [$dayKeys[$index], strtoupper($date->format('D')), strtoupper($date->format('M j')), $dayNumber, $date->format('Y-m-d')];
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
        $slotIndex = $hour - 8;
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
    'timeoff' => 'Time Off',
];

$rules = [
    ['Daily Start Time', '08:00 AM', 'gear'],
    ['Daily End Time', '10:00 PM', 'clock'],
    ['Default Session Duration', '60 minutes', 'clock'],
    ['Break Between Sessions', '15 minutes', 'timer'],
    ['Max Sessions Per Day', '8 sessions', 'calendar'],
    ['Min Notice for Booking', '2 hours', 'clock'],
];

$coachSessions = $coachId ? $schedulingService->sessionsBetween($coachId, $weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')) : [];
$timeOffRequests = $schedulingService->timeOffRequestsForCoach($coachId);
$approvedTimeOff = array_values(array_filter($timeOffRequests, static fn(array $request): bool => ($request['status'] ?? '') === 'approved'));
foreach ($days as [$key, , , , $dateSql]) {
    foreach ($approvedTimeOff as $request) {
        if ($dateSql >= (string) $request['start_date'] && $dateSql <= (string) $request['end_date']) {
            $slotMap[$key] = array_fill(0, count($times), 'timeoff');
            break;
        }
    }
}
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
$nextAvailable = 'None set';
foreach ($availabilityRows as $row) {
    if (($row['status'] ?? '') === 'available') {
        $nextAvailable = (string) $row['day_label'] . ' ' . (string) $row['time_range'];
        break;
    }
}
$upcomingTimeOff = array_values(array_filter($timeOffRequests, static fn(array $request): bool => in_array((string) $request['status'], ['pending', 'approved'], true)));
$pastTimeOff = array_values(array_filter($timeOffRequests, static fn(array $request): bool => in_array((string) $request['status'], ['completed', 'rejected', 'cancelled'], true)));
$timeOffReasons = ['Vacation', 'Personal Leave', 'Medical Appointment', 'Family Commitment', 'Tournament Participation', 'Other'];
$dayLabels = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];
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
    <link rel="stylesheet" href="<?php echo htmlspecialchars(pickled_asset_url('css/coach-dashboard.css?v=20260615a')); ?>">
</head>
<body class="coach-portal-body">
<div class="coach-app-shell">
    <aside class="coach-sidebar">
        <a class="coach-brand" href="<?php echo htmlspecialchars(pickled_frontend_url('coach/dashboard.php')); ?>"><img src="<?php echo htmlspecialchars(pickled_asset_url('img/LM-DGreen.png')); ?>" alt="Pickled"><span>Coach</span></a>
        <nav class="coach-nav" aria-label="Coach navigation">
            <?php foreach ($navItems as [$label, $href, $icon, $active]): ?>
                <a class="<?php echo $active ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($href); ?>"><?php echo availability_icon($icons, $icon); ?><span><?php echo htmlspecialchars($label); ?></span><?php if ($label === 'Announcements' && $coachUnreadCount > 0): ?><em><?php echo min($coachUnreadCount, 9); ?></em><?php endif; ?></a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="coach-main availability-main">
        <header class="coach-topbar">
            <div><h1>Availability</h1></div>
            <div class="coach-top-actions">
                <span class="coach-date-pill"><?php echo availability_icon($icons, 'calendar'); ?><span><?php echo htmlspecialchars($todayLabel); ?></span></span>
                <a class="coach-notification" href="<?php echo htmlspecialchars(pickled_frontend_url('coach/announcements.php')); ?>" aria-label="Announcements"><?php echo availability_icon($icons, 'bell'); ?><?php if ($coachUnreadCount > 0): ?><em><?php echo min($coachUnreadCount, 9); ?></em><?php endif; ?></a>
                <details class="coach-top-profile">
                    <summary><span class="coach-photo small"><img src="<?php echo htmlspecialchars(pickled_asset_url('img/court/academy.png')); ?>" alt="<?php echo htmlspecialchars($coachName); ?>"></span><span><strong>Coach</strong><small>Pickleball Coach</small></span><b>⌄</b></summary>
                    <form method="post" action="<?php echo htmlspecialchars(pickled_frontend_url('auth/logout.php')); ?>"><input type="hidden" name="csrf_token" value="<?php echo $logoutCsrf; ?>"><button type="submit">Logout</button></form>
                </details>
            </div>
        </header>

        <?php if ($successMsg): ?><div class="alert alert-success"><?php echo availability_h($successMsg); ?></div><?php endif; ?>
        <?php if ($errorMsg): ?><div class="alert alert-error"><?php echo availability_h($errorMsg); ?></div><?php endif; ?>

        <section class="coach-kpi-grid availability-summary-grid page-first-section">
            <article class="coach-kpi green"><div><span>Available Days</span><strong><?php echo number_format($availableDays); ?></strong><small>This week</small></div></article>
            <article class="coach-kpi pink"><div><span>Active Time Blocks</span><strong><?php echo number_format($availableSlots); ?></strong><small>Saved availability</small></div></article>
            <article class="coach-kpi orange"><div><span>Booked Sessions</span><strong><?php echo number_format($bookedSessions); ?></strong><small>Next 30 days</small></div></article>
            <article class="coach-kpi purple"><div><span>Next Available Slot</span><strong><?php echo availability_h($nextAvailable); ?></strong><small>From saved blocks</small></div></article>
        </section>

        <section class="coach-card compact-availability-card">
            <header>
                <div>
                    <h2>Weekly Availability</h2>
                    <p>Manage recurring availability blocks. Rows stay read-only until you edit them.</p>
                </div>
                <button class="coach-compact-primary" type="button" data-open-dialog="addAvailabilityDialog">+ Add Availability</button>
            </header>
            <div class="availability-row-list">
                <?php foreach ($availabilityRows as $row): ?>
                    <?php $statusClass = availability_status_class((string) $row['status']); ?>
                    <article class="availability-manage-row" data-edit-row>
                        <div class="availability-read-view">
                            <div><strong><?php echo availability_h($dayLabels[(int) $row['day_of_week']] ?? $row['day_label']); ?></strong><span><?php echo availability_h($row['time_range']); ?></span></div>
                            <em class="availability-status <?php echo availability_h($statusClass); ?>"><?php echo availability_h(ucfirst((string) $row['status'])); ?></em>
                            <span class="compact-actions">
                                <button type="button" data-edit-trigger>Edit</button>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo availability_h(pickled_csrf_token()); ?>">
                                    <input type="hidden" name="availability_id" value="<?php echo (int) $row['id']; ?>">
                                    <button class="danger" type="submit" name="action" value="disable_availability">Disable</button>
                                </form>
                            </span>
                        </div>
                        <form class="availability-edit-form" method="post" hidden>
                            <input type="hidden" name="csrf_token" value="<?php echo availability_h(pickled_csrf_token()); ?>">
                            <input type="hidden" name="availability_id" value="<?php echo (int) $row['id']; ?>">
                            <label><span>Day</span><select name="day_of_week"><?php foreach ($dayLabels as $number => $label): ?><option value="<?php echo $number; ?>" <?php echo (int) $row['day_of_week'] === $number ? 'selected' : ''; ?>><?php echo availability_h($label); ?></option><?php endforeach; ?></select></label>
                            <label><span>Start</span><input type="time" name="start_time" min="08:00" max="21:00" value="<?php echo availability_h(substr((string) $row['start_time'], 0, 5)); ?>" required></label>
                            <label><span>End</span><input type="time" name="end_time" min="09:00" max="22:00" value="<?php echo availability_h(substr((string) $row['end_time'], 0, 5)); ?>" required></label>
                            <label><span>Status</span><select name="status"><option value="available" <?php echo $row['status'] === 'available' ? 'selected' : ''; ?>>Available</option><option value="unavailable" <?php echo $row['status'] === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option><option value="leave" <?php echo $row['status'] === 'leave' ? 'selected' : ''; ?>>Leave</option></select></label>
                            <button type="submit" name="action" value="update_availability">Save</button>
                            <button type="button" data-edit-cancel>Cancel</button>
                        </form>
                    </article>
                <?php endforeach; ?>
                <?php if (!$availabilityRows): ?><p class="catalog-empty-state">No availability slots yet.</p><?php endif; ?>
            </div>
        </section>

        <details class="coach-card collapsed-preview-card">
            <summary><div><h2>Weekly Schedule Preview</h2><p>View read-only calendar grid</p></div><span>Show Preview</span></summary>
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
                            <span class="availability-slot <?php echo htmlspecialchars($state); ?>"><?php echo htmlspecialchars($slotLabels[$state]); ?></span>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <footer class="availability-legend">
                <span><i class="available"></i>Available</span><span><i class="unavailable"></i>Unavailable</span><span><i class="timeoff"></i>Time Off</span>
            </footer>
        </details>

        <details class="coach-card rules-card advanced-settings-card">
            <summary><h2>Advanced Settings</h2><span>Availability rules</span></summary>
            <div class="rules-list">
                <?php foreach ($rules as [$label, $value, $icon]): ?>
                    <article><?php echo availability_icon($icons, $icon); ?><span><?php echo htmlspecialchars($label); ?></span><strong><?php echo htmlspecialchars($value); ?></strong></article>
                <?php endforeach; ?>
            </div>
        </details>

        <section class="coach-card timeoff-card compact-timeoff-card">
            <header><h2>Time Off</h2><button type="button" data-open-dialog="addTimeOffDialog">+ Add Time Off</button></header>
            <nav class="timeoff-tabs" aria-label="Time off views"><button class="active" type="button" data-timeoff-tab="upcoming">Upcoming</button><button type="button" data-timeoff-tab="past">Past</button></nav>
            <div class="timeoff-list" data-timeoff-panel="upcoming">
                <?php foreach ($upcomingTimeOff as $request): ?>
                    <?php $status = (string) $request['status']; ?>
                    <article>
                        <div><strong><?php echo availability_h($request['date_range']); ?></strong><span><?php echo availability_h($request['reason']); ?></span></div>
                        <em class="timeoff-status <?php echo availability_h($status); ?>"><?php echo availability_h($request['status_label']); ?></em>
                        <details class="row-action-menu"><summary>...</summary><div>
                            <button type="button" data-detail='<?php echo availability_h(json_encode($request)); ?>'>View Details</button>
                            <?php if ($status === 'pending'): ?>
                                <button type="button" data-edit-timeoff='<?php echo availability_h(json_encode($request)); ?>'>Edit</button>
                                <button class="danger" type="button" data-cancel-timeoff="<?php echo (int) $request['id']; ?>">Cancel Request</button>
                            <?php endif; ?>
                        </div></details>
                    </article>
                <?php endforeach; ?>
                <?php if (!$upcomingTimeOff): ?><p class="catalog-empty-state">No upcoming time off requests.</p><?php endif; ?>
            </div>
            <div class="timeoff-list" data-timeoff-panel="past" hidden>
                <?php foreach ($pastTimeOff as $request): ?>
                    <article>
                        <div><strong><?php echo availability_h($request['date_range']); ?></strong><span><?php echo availability_h($request['reason']); ?></span></div>
                        <em class="timeoff-status <?php echo availability_h((string) $request['status']); ?>"><?php echo availability_h($request['status_label']); ?></em>
                        <details class="row-action-menu"><summary>...</summary><div><button type="button" data-detail='<?php echo availability_h(json_encode($request)); ?>'>View Details</button></div></details>
                    </article>
                <?php endforeach; ?>
                <?php if (!$pastTimeOff): ?><p class="catalog-empty-state">No past time off requests.</p><?php endif; ?>
            </div>
        </section>

        <dialog class="coach-modal" id="addAvailabilityDialog">
            <form method="post" class="coach-modal-form">
                <header><h2>Add Availability</h2><button type="button" data-close-dialog>Cancel</button></header>
                <input type="hidden" name="csrf_token" value="<?php echo availability_h(pickled_csrf_token()); ?>">
                <label><span>Day</span><select name="day_of_week"><?php foreach ($dayLabels as $number => $label): ?><option value="<?php echo $number; ?>"><?php echo availability_h($label); ?></option><?php endforeach; ?></select></label>
                <label><span>Start Time</span><input type="time" name="start_time" min="08:00" max="21:00" value="09:00" required></label>
                <label><span>End Time</span><input type="time" name="end_time" min="09:00" max="22:00" value="10:00" required></label>
                <label><span>Status</span><select name="status"><option value="available">Available</option><option value="unavailable">Unavailable</option><option value="leave">Leave</option></select></label>
                <footer><button type="button" data-close-dialog>Cancel</button><button type="submit" name="action" value="create_availability">Save Availability</button></footer>
            </form>
        </dialog>

        <dialog class="coach-modal" id="addTimeOffDialog">
            <form method="post" class="coach-modal-form">
                <header><h2>Add Time Off</h2><button type="button" data-close-dialog>Cancel</button></header>
                <input type="hidden" name="csrf_token" value="<?php echo availability_h(pickled_csrf_token()); ?>">
                <label><span>Start Date</span><input type="date" name="start_date" min="<?php echo availability_h($today->format('Y-m-d')); ?>" value="<?php echo availability_h($today->format('Y-m-d')); ?>" required></label>
                <label><span>End Date</span><input type="date" name="end_date" min="<?php echo availability_h($today->format('Y-m-d')); ?>" value="<?php echo availability_h($today->format('Y-m-d')); ?>" required></label>
                <label><span>Reason</span><select name="reason"><?php foreach ($timeOffReasons as $reason): ?><option value="<?php echo availability_h($reason); ?>"><?php echo availability_h($reason); ?></option><?php endforeach; ?></select></label>
                <label class="full"><span>Notes (optional)</span><textarea name="notes" rows="4" maxlength="1000" placeholder="Add useful context for the admin team."></textarea></label>
                <footer><button type="button" data-close-dialog>Cancel</button><button type="submit" name="action" value="create_time_off">Submit Request</button></footer>
            </form>
        </dialog>

        <dialog class="coach-modal" id="editTimeOffDialog">
            <form method="post" class="coach-modal-form">
                <header><h2>Edit Time Off</h2><button type="button" data-close-dialog>Cancel</button></header>
                <input type="hidden" name="csrf_token" value="<?php echo availability_h(pickled_csrf_token()); ?>">
                <input type="hidden" name="time_off_id" id="editTimeOffId">
                <label><span>Start Date</span><input type="date" name="start_date" min="<?php echo availability_h($today->format('Y-m-d')); ?>" id="editTimeOffStart" required></label>
                <label><span>End Date</span><input type="date" name="end_date" min="<?php echo availability_h($today->format('Y-m-d')); ?>" id="editTimeOffEnd" required></label>
                <label><span>Reason</span><select name="reason" id="editTimeOffReason"><?php foreach ($timeOffReasons as $reason): ?><option value="<?php echo availability_h($reason); ?>"><?php echo availability_h($reason); ?></option><?php endforeach; ?></select></label>
                <label class="full"><span>Notes (optional)</span><textarea name="notes" id="editTimeOffNotes" rows="4" maxlength="1000"></textarea></label>
                <footer><button type="button" data-close-dialog>Cancel</button><button type="submit" name="action" value="update_time_off">Save</button></footer>
            </form>
        </dialog>

        <dialog class="coach-modal" id="timeOffDetailsDialog">
            <div class="coach-modal-form">
                <header><h2>Time Off Details</h2><button type="button" data-close-dialog>Close</button></header>
                <dl class="timeoff-detail-list">
                    <div><dt>Reason</dt><dd id="detailReason"></dd></div>
                    <div><dt>Date Range</dt><dd id="detailDateRange"></dd></div>
                    <div><dt>Status</dt><dd id="detailStatus"></dd></div>
                    <div><dt>Notes</dt><dd id="detailNotes"></dd></div>
                    <div><dt>Admin Remarks</dt><dd id="detailAdminRemarks"></dd></div>
                </dl>
            </div>
        </dialog>

        <dialog class="coach-modal" id="cancelTimeOffDialog">
            <form method="post" class="coach-modal-form">
                <header><h2>Cancel Request?</h2></header>
                <p>Are you sure you want to cancel this time off request?</p>
                <input type="hidden" name="csrf_token" value="<?php echo availability_h(pickled_csrf_token()); ?>">
                <input type="hidden" name="time_off_id" id="cancelTimeOffId">
                <footer><button type="button" data-close-dialog>Keep Request</button><button class="danger" type="submit" name="action" value="cancel_time_off">Cancel Request</button></footer>
            </form>
        </dialog>
    </main>
</div>
<script>
(() => {
    const tabs = Array.from(document.querySelectorAll('[data-timeoff-tab]'));
    const panels = Array.from(document.querySelectorAll('[data-timeoff-panel]'));
    tabs.forEach(tab => tab.addEventListener('click', () => {
        tabs.forEach(item => item.classList.toggle('active', item === tab));
        panels.forEach(panel => panel.hidden = panel.dataset.timeoffPanel !== tab.dataset.timeoffTab);
    }));
    document.querySelectorAll('[data-open-dialog]').forEach(button => {
        button.addEventListener('click', () => document.getElementById(button.dataset.openDialog)?.showModal());
    });
    document.querySelectorAll('[data-close-dialog]').forEach(button => {
        button.addEventListener('click', () => button.closest('dialog')?.close());
    });
    document.querySelectorAll('[data-edit-trigger]').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-edit-row]').forEach(row => {
                row.querySelector('.availability-read-view').hidden = false;
                row.querySelector('.availability-edit-form').hidden = true;
            });
            const row = button.closest('[data-edit-row]');
            row.querySelector('.availability-read-view').hidden = true;
            row.querySelector('.availability-edit-form').hidden = false;
        });
    });
    document.querySelectorAll('[data-edit-cancel]').forEach(button => {
        button.addEventListener('click', () => {
            const row = button.closest('[data-edit-row]');
            row.querySelector('.availability-read-view').hidden = false;
            row.querySelector('.availability-edit-form').hidden = true;
        });
    });
    document.querySelectorAll('[data-detail]').forEach(button => {
        button.addEventListener('click', () => {
            const data = JSON.parse(button.dataset.detail || '{}');
            document.getElementById('detailReason').textContent = data.reason || '';
            document.getElementById('detailDateRange').textContent = data.date_range || '';
            document.getElementById('detailStatus').textContent = data.status_label || '';
            document.getElementById('detailNotes').textContent = data.notes || 'None';
            document.getElementById('detailAdminRemarks').textContent = data.admin_remarks || 'None';
            document.getElementById('timeOffDetailsDialog')?.showModal();
        });
    });
    document.querySelectorAll('[data-edit-timeoff]').forEach(button => {
        button.addEventListener('click', () => {
            const data = JSON.parse(button.dataset.editTimeoff || '{}');
            document.getElementById('editTimeOffId').value = data.id || '';
            document.getElementById('editTimeOffStart').value = data.start_date || '';
            document.getElementById('editTimeOffEnd').value = data.end_date || '';
            document.getElementById('editTimeOffReason').value = data.reason || 'Vacation';
            document.getElementById('editTimeOffNotes').value = data.notes || '';
            document.getElementById('editTimeOffDialog')?.showModal();
        });
    });
    document.querySelectorAll('[data-cancel-timeoff]').forEach(button => {
        button.addEventListener('click', () => {
            document.getElementById('cancelTimeOffId').value = button.dataset.cancelTimeoff || '';
            document.getElementById('cancelTimeOffDialog')?.showModal();
        });
    });
})();
</script>
</body>
</html>
