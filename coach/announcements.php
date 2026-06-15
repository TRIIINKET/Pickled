<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../app/services/NotificationService.php';

pickled_start_secure_session();
pickled_init_csrf();

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'coach') {
    header('Location: ' . pickled_frontend_url('auth/login.php?role=coach&redirect=coach/announcements.php'));
    exit;
}

$coach = $_SESSION['user'];
$coachId = (int) ($coach['id'] ?? 0);
$coachName = $coach['name'] ?? 'Coach Mia';
$notificationService = new NotificationService();

$notifications = $notificationService->notificationsForUser($coachId, 80);
$coachUnreadCount = $notificationService->unreadCount($coachId);
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayLabel = $today->format('M j, Y (D)');
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');

function announcements_icon(array $icons, string $name): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['megaphone']) . '</svg>';
}

function announcements_type_label(string $type): string {
    return strtoupper(str_replace('_', ' ', $type));
}

function announcements_safe_href(?string $link): string {
    $link = trim((string) $link);
    if ($link === '') {
        return '';
    }

    $parts = parse_url($link);
    if ($parts === false) {
        return '';
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    if ($scheme !== '') {
        return in_array($scheme, ['http', 'https'], true) ? $link : '';
    }

    if (function_exists('pickled_frontend_url') && preg_match('#^(resident|admin|coach|auth)/#', $link)) {
        return pickled_frontend_url($link);
    }

    return $link;
}

$icons = [
    'home' => '<path d="M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
    'students' => '<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'megaphone' => '<path d="m3 11 18-5v12L3 13z"/><path d="M11 14v4a2 2 0 0 1-4 0v-5"/>',
    'profile' => '<path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/>',
    'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
    'wrench' => '<path d="M14.7 6.3a4 4 0 0 0-5 5L3 18l3 3 6.7-6.7a4 4 0 0 0 5-5l-2.4 2.4-3-3z"/>',
    'racket' => '<path d="M14.5 4.5a5 8 45 1 0 5 5 5 8 45 1 0-5-5Z"/><path d="m9.5 14.5-6 6"/><path d="m8 16 2 2"/>',
    'trophy' => '<path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0Z"/><path d="M5 5H3v2a4 4 0 0 0 4 4"/><path d="M19 5h2v2a4 4 0 0 1-4 4"/>',
    'gear' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.6-1H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.6V3a2 2 0 1 1 4 0v.09a1.7 1.7 0 0 0 1 1.51 1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.16.4.56.82 1.1.9H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.51 1.1Z"/>',
    'alert' => '<path d="M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
    'building' => '<path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-6h6v6M9 9h.01M15 9h.01M9 12h.01M15 12h.01"/>',
    'pin' => '<path d="m14 4 6 6-4 1-5 5-1 4-6-6 4-1 5-5z"/>',
    'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h6"/>',
    'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 3.08 5.18 2 2 0 0 1 5.06 3h3a2 2 0 0 1 2 1.72c.12.9.33 1.77.63 2.61a2 2 0 0 1-.45 2.11L9 10.69a16 16 0 0 0 4.31 4.31l1.25-1.24a2 2 0 0 1 2.11-.45c.84.3 1.71.51 2.61.63A2 2 0 0 1 22 16.92Z"/>',
    'check' => '<path d="m20 6-11 11-5-5"/>',
    'arrow' => '<path d="m9 18 6-6-6-6"/>',
];

$navItems = [
    ['Dashboard', pickled_frontend_url('coach/dashboard.php'), 'home', false],
    ['My Schedule', pickled_frontend_url('coach/schedule.php'), 'calendar', false],
    ['Students', pickled_frontend_url('coach/students.php'), 'students', false],
    ['Availability', pickled_frontend_url('coach/availability.php'), 'clock', false],
    ['Announcements', pickled_frontend_url('coach/announcements.php'), 'megaphone', true],
    ['Profile', pickled_frontend_url('coach/profile.php'), 'profile', false],
];

$announcements = [
    ['SCHEDULE CHANGE', '2 hours ago', 'Court Green Maintenance', 'Court Green will be unavailable on June 15 from 8:00 AM - 12:00 PM due to floor maintenance.', 'Affected sessions will be rescheduled.', 'Admin Team', 'Urgent', 'pink', 'wrench'],
    ['COACHING UPDATE', 'Yesterday', 'New Beginner Program Launch', 'A new Beginner Fundamentals Program has been added to Court Pink.', 'Coaches may now accept students under this category.', 'Programs Team', 'Coaching', 'green', 'racket'],
    ['EVENT ANNOUNCEMENT', 'May 30, 2026', 'Weekly Tournament Schedule', "This week's tournament will be held on Saturday at 6:00 PM.", 'Expected Participants: 16 Players', 'Events Team', 'Event', 'orange', 'trophy'],
    ['SYSTEM UPDATE', 'May 28, 2026', 'New Attendance Feature', 'A new attendance tracking feature is now available.', 'You can now record attendance directly from the My Schedule page.', 'System Admin', 'Update', 'purple', 'gear'],
];

$reminders = [
    ['JUN 15', 'Court Green Maintenance', '8:00 AM - 12:00 PM', 'Urgent', 'pink'],
    ['JUN 18', 'Coach Meeting', 'Wednesday • 4:00 PM', 'Meeting', 'green'],
    ['JUN 21', 'Weekly Tournament', 'Saturday • 6:00 PM', 'Event', 'orange'],
];

$pinned = [
    ['Facility Rules', 'Updated May 10, 2026', 'file', 'pink'],
    ['Coaching Guidelines', 'Updated Apr 25, 2026', 'calendar', 'green'],
    ['Emergency Contact Procedures', 'Updated Mar 18, 2026', 'phone', 'purple'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Pickled Coach</title>
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
                <a class="<?php echo $active ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($href); ?>"><?php echo announcements_icon($icons, $icon); ?><span><?php echo htmlspecialchars($label); ?></span><?php if ($label === 'Announcements' && $coachUnreadCount > 0): ?><em><?php echo min($coachUnreadCount, 9); ?></em><?php endif; ?></a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="coach-main announcements-main">
        <header class="coach-topbar">
            <div><h1>Announcements</h1></div>
            <div class="coach-top-actions">
                <span class="coach-date-pill"><?php echo announcements_icon($icons, 'calendar'); ?><span><?php echo htmlspecialchars($todayLabel); ?></span></span>
                <a class="coach-notification" href="<?php echo htmlspecialchars(pickled_frontend_url('coach/announcements.php')); ?>" aria-label="Notifications"><?php echo announcements_icon($icons, 'bell'); ?><?php if ($coachUnreadCount > 0): ?><em><?php echo min($coachUnreadCount, 9); ?></em><?php endif; ?></a>
                <details class="coach-top-profile">
                    <summary><span class="coach-photo small"><img src="<?php echo htmlspecialchars(pickled_asset_url('img/court/academy.png')); ?>" alt="<?php echo htmlspecialchars($coachName); ?>"></span><span><strong>Coach</strong><small>Pickleball Coach</small></span><b>⌄</b></summary>
                    <form method="post" action="<?php echo htmlspecialchars(pickled_frontend_url('auth/logout.php')); ?>"><input type="hidden" name="csrf_token" value="<?php echo $logoutCsrf; ?>"><button type="submit">Logout</button></form>
                </details>
            </div>
        </header>

        <section class="page-intro page-first-section">
            <p>Stay updated with facility news, schedule changes, and coaching updates.</p>
            <span><?php echo announcements_icon($icons, $coachUnreadCount > 0 ? 'bell' : 'check'); ?><?php echo $coachUnreadCount > 0 ? number_format($coachUnreadCount) . ' unread' : 'All caught up'; ?></span>
        </section>

        <div class="announcement-status-strip" aria-label="Announcement status summary">
            <span class="active">All</span><span><?php echo announcements_icon($icons, 'bell'); ?>Unread</span><span><?php echo announcements_icon($icons, 'check'); ?>Read</span>
        </div>

        <section class="announcements-workspace">
            <div class="announcement-feed">
                <?php if ($notifications): ?>
                    <?php foreach ($notifications as $notification): ?>
                        <?php $unread = empty($notification['is_read']); ?>
                        <?php $notificationLink = announcements_safe_href($notification['link'] ?? null); ?>
                        <article class="announcement-feed-card <?php echo $unread ? 'pink' : 'green'; ?>">
                            <i><?php echo announcements_icon($icons, 'bell'); ?></i>
                            <div class="announcement-card-body">
                                <header><span><?php echo htmlspecialchars(announcements_type_label((string) $notification['type'])); ?></span><time><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime((string) $notification['created_at']))); ?></time></header>
                                <h2><?php echo htmlspecialchars($notification['title']); ?></h2>
                                <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                <footer><strong>System</strong><?php if ($notificationLink !== ''): ?><a href="<?php echo htmlspecialchars($notificationLink); ?>">View details</a><?php endif; ?></footer>
                            </div>
                            <em><?php echo $unread ? 'Unread' : 'Read'; ?></em>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                <?php foreach ($announcements as [$category, $time, $title, $body, $detail, $team, $badge, $tone, $icon]): ?>
                    <article class="announcement-feed-card <?php echo htmlspecialchars($tone); ?>">
                        <i><?php echo announcements_icon($icons, $icon); ?></i>
                        <div class="announcement-card-body">
                            <header><span><?php echo htmlspecialchars($category); ?></span><time><?php echo htmlspecialchars($time); ?></time></header>
                            <h2><?php echo htmlspecialchars($title); ?></h2>
                            <p><?php echo htmlspecialchars($body); ?></p>
                            <p><?php echo htmlspecialchars($detail); ?></p>
                            <footer><strong><img src="<?php echo htmlspecialchars(pickled_asset_url('img/LM-DGreen.png')); ?>" alt=""> <?php echo htmlspecialchars($team); ?></strong></footer>
                        </div>
                        <em><?php echo htmlspecialchars($badge); ?></em>
                    </article>
                <?php endforeach; ?>
                <?php foreach ($pinned as [$title, $updated, $icon, $tone]): ?>
                    <article class="announcement-feed-card <?php echo htmlspecialchars($tone); ?>">
                        <i><?php echo announcements_icon($icons, $icon); ?></i>
                        <div class="announcement-card-body">
                            <header><span>Reference</span><time><?php echo htmlspecialchars($updated); ?></time></header>
                            <h2><?php echo htmlspecialchars($title); ?></h2>
                            <p>Important coaching reference available in the announcements feed.</p>
                            <footer><strong>Admin Team</strong></footer>
                        </div>
                        <em>Read</em>
                    </article>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <aside class="announcements-side">
                <section class="coach-card reminder-card">
                    <header><h2><?php echo announcements_icon($icons, 'calendar'); ?>Upcoming Reminders</h2></header>
                    <div class="reminder-list">
                        <?php foreach ($reminders as [$date, $title, $time, $badge, $tone]): ?>
                            <article><time class="<?php echo htmlspecialchars($tone); ?>"><?php echo htmlspecialchars($date); ?></time><div><strong><?php echo htmlspecialchars($title); ?></strong><span><?php echo htmlspecialchars($time); ?></span></div><em class="<?php echo htmlspecialchars($tone); ?>"><?php echo htmlspecialchars($badge); ?></em></article>
                        <?php endforeach; ?>
                    </div>
                    <a class="side-card-link" href="<?php echo htmlspecialchars(pickled_frontend_url('coach/schedule.php')); ?>">View full calendar <?php echo announcements_icon($icons, 'arrow'); ?></a>
                </section>
            </aside>
        </section>
    </main>
</div>
</body>
</html>
