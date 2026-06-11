<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/paths.php';

pickled_start_secure_session();
pickled_init_csrf();

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'coach') {
    header('Location: ' . pickled_frontend_url('auth/login.php?role=coach&redirect=coach/dashboard.php'));
    exit;
}

$coach = $_SESSION['user'];
$coachName = $coach['name'] ?? 'Coach Mia';
$firstName = trim(explode(' ', $coachName)[0] ?? 'Coach');
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayLabel = $today->format('M j, Y (D)');
$scheduleDateLabel = $today->format('l, F j, Y');
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');

function coach_icon(array $icons, string $name): string {
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
    'pin' => '<path d="M20 10c0 5-8 11-8 11s-8-6-8-11a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
    'arrow' => '<path d="m9 18 6-6-6-6"/>',
    'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 3.08 5.18 2 2 0 0 1 5.06 3h3a2 2 0 0 1 2 1.72c.12.9.33 1.77.63 2.61a2 2 0 0 1-.45 2.11L9 10.69a16 16 0 0 0 4.31 4.31l1.25-1.24a2 2 0 0 1 2.11-.45c.84.3 1.71.51 2.61.63A2 2 0 0 1 22 16.92Z"/>',
    'trophy' => '<path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0Z"/><path d="M5 5H3v2a4 4 0 0 0 4 4"/><path d="M19 5h2v2a4 4 0 0 1-4 4"/>',
    'stopwatch' => '<circle cx="12" cy="13" r="8"/><path d="M12 9v4l3 2M9 2h6M12 2v3"/>',
    'check' => '<path d="m20 6-11 11-5-5"/>',
];

$navItems = [
    ['Dashboard', pickled_frontend_url('coach/dashboard.php'), 'home', true],
    ['My Schedule', pickled_frontend_url('coach/schedule.php'), 'calendar', false],
    ['Students', pickled_frontend_url('coach/students.php'), 'students', false],
    ['Availability', pickled_frontend_url('coach/availability.php'), 'clock', false],
    ['Announcements', pickled_frontend_url('coach/announcements.php'), 'megaphone', false],
    ['Profile', pickled_frontend_url('coach/profile.php'), 'profile', false],
];

$sessions = [
    ['09:00 AM', '10:00 AM', 'Kids Class (Ages 6-10)', 'Court Pink', '8 students', 'Confirmed', 'pink', 'students'],
    ['10:00 AM', '11:00 AM', 'Youth Development (Ages 11-17)', 'Court Pink', '10 students', 'Confirmed', 'pink', 'students'],
    ['02:00 PM', '03:00 PM', 'Private Coaching', 'Court Green', 'Shemaiah', 'Confirmed', 'green', 'profile'],
    ['04:00 PM', '06:00 PM', 'Open Match Play', 'Court Green', '16 participants', 'Pending', 'orange', 'trophy'],
];

$students = [
    ['MR', 'Mia Reyes', 'Kids Class (6-10)', 'Jun 10, 2026', '92%', 'pink'],
    ['JD', 'Juan Dela Cruz', 'Youth Development', 'Jun 9, 2026', '88%', 'green'],
    ['AS', 'Alyssa Santos', 'Private Coaching', 'Jun 10, 2026', '95%', 'purple'],
];

$availability = [
    ['WED', 'Jun 11', '8:00 AM - 6:00 PM', 'Available'],
    ['THU', 'Jun 12', '8:00 AM - 6:00 PM', 'Available'],
    ['FRI', 'Jun 13', '8:00 AM - 5:00 PM', 'Available'],
    ['SAT', 'Jun 14', '10:00 AM - 2:00 PM', 'Available'],
    ['SUN', 'Jun 15', 'Day Off', 'Unavailable'],
];

$announcements = [
    ['Tournament Moved', 'The Pickled Inter-Coach Tournament is moved to June 20, 2026.', 'pink', 'calendar'],
    ['New Court Green Schedule', 'Court Green will have a new operating schedule starting June 15, 2026.', 'green', 'megaphone'],
    ['Holiday Hours', 'Please check the adjusted operating hours on June 12.', 'orange', 'clock'],
];

$nextSession = $sessions[1];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coach Dashboard - Pickled</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(pickled_asset_url('css/coach-dashboard.css')); ?>">
</head>
<body class="coach-portal-body">
<div class="coach-app-shell" id="dashboard">
    <aside class="coach-sidebar">
        <a class="coach-brand" href="<?php echo htmlspecialchars(pickled_frontend_url('coach/dashboard.php')); ?>">
            <img src="<?php echo htmlspecialchars(pickled_asset_url('img/LM-DGreen.png')); ?>" alt="Pickled">
            <span>Coach</span>
        </a>

        <nav class="coach-nav" aria-label="Coach navigation">
            <?php foreach ($navItems as [$label, $href, $icon, $active]): ?>
                <a class="<?php echo $active ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($href); ?>">
                    <?php echo coach_icon($icons, $icon); ?>
                    <span><?php echo htmlspecialchars($label); ?></span>
                    <?php if ($label === 'Announcements'): ?><em>4</em><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

    </aside>

    <main class="coach-main">
        <header class="coach-topbar">
            <div>
                <h1>Dashboard</h1>
            </div>
            <div class="coach-top-actions">
                <button class="coach-date-pill" type="button"><?php echo coach_icon($icons, 'calendar'); ?><span><?php echo htmlspecialchars($todayLabel); ?></span></button>
                <a class="coach-notification" href="<?php echo htmlspecialchars(pickled_frontend_url('coach/announcements.php')); ?>" aria-label="Announcements"><?php echo coach_icon($icons, 'bell'); ?><em>4</em></a>
                <details class="coach-top-profile">
                    <summary>
                        <span class="coach-photo small"><img src="<?php echo htmlspecialchars(pickled_asset_url('img/court/academy.png')); ?>" alt="<?php echo htmlspecialchars($coachName); ?>"></span>
                        <span><strong>Coach</strong><small>Pickleball Coach</small></span>
                        <b>⌄</b>
                    </summary>
                    <form method="post" action="<?php echo htmlspecialchars(pickled_frontend_url('auth/logout.php')); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $logoutCsrf; ?>">
                        <button type="submit">Logout</button>
                    </form>
                </details>
            </div>
        </header>

        <section class="coach-welcome">
            <h2>Good morning, <?php echo htmlspecialchars($firstName); ?>!</h2>
            <p>Here's your overview for today.</p>
        </section>

        <section class="coach-kpi-grid" aria-label="Coach overview">
            <article class="coach-kpi green"><?php echo coach_icon($icons, 'calendar'); ?><div><span>Today's Sessions</span><strong>4</strong><small>Confirmed</small></div></article>
            <article class="coach-kpi pink"><?php echo coach_icon($icons, 'calendar'); ?><div><span>Upcoming This Week</span><strong>12</strong><small>Sessions</small></div></article>
            <article class="coach-kpi orange"><?php echo coach_icon($icons, 'students'); ?><div><span>Active Students</span><strong>38</strong><small>This month</small></div></article>
            <article class="coach-kpi purple"><?php echo coach_icon($icons, 'stopwatch'); ?><div><span>Hours Coached</span><strong>24</strong><small>This week</small></div></article>
        </section>

        <section class="coach-content-grid">
            <div class="coach-center-column">
                <article class="coach-card today-schedule-card" id="schedule">
                    <header><h2><?php echo coach_icon($icons, 'calendar'); ?> Today's Schedule</h2><span><?php echo htmlspecialchars($scheduleDateLabel); ?></span><a href="#schedule">View Full Schedule <?php echo coach_icon($icons, 'arrow'); ?></a></header>
                    <div class="coach-session-list">
                        <?php foreach ($sessions as [$start, $end, $name, $court, $studentsCount, $status, $tone, $icon]): ?>
                            <article class="coach-session-item <?php echo $tone; ?>">
                                <time><strong><?php echo htmlspecialchars($start); ?></strong><span><?php echo htmlspecialchars($end); ?></span></time>
                                <i><?php echo coach_icon($icons, $icon); ?></i>
                                <div><strong><?php echo htmlspecialchars($name); ?></strong><span><?php echo htmlspecialchars($court); ?> • <?php echo htmlspecialchars($studentsCount); ?></span></div>
                                <em class="session-status <?php echo strtolower($status); ?>"><?php echo htmlspecialchars($status); ?></em>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </article>

                <section class="coach-lower-grid">
                    <article class="coach-card students-card" id="students">
                        <header><h2>Active Students</h2><a href="#students">View all</a></header>
                        <div class="student-table">
                            <div class="student-row head"><span>Student</span><span>Program</span><span>Last Session</span><span>Attendance</span></div>
                            <?php foreach ($students as [$initials, $name, $program, $lastSession, $attendance, $tone]): ?>
                                <div class="student-row"><span><b class="<?php echo $tone; ?>"><?php echo htmlspecialchars($initials); ?></b><?php echo htmlspecialchars($name); ?></span><span><?php echo htmlspecialchars($program); ?></span><span><?php echo htmlspecialchars($lastSession); ?></span><strong><?php echo htmlspecialchars($attendance); ?></strong></div>
                            <?php endforeach; ?>
                        </div>
                        <a class="coach-card-link" href="#students">See all students <?php echo coach_icon($icons, 'arrow'); ?></a>
                    </article>

                    <article class="coach-card availability-card" id="availability">
                        <header><h2>Availability This Week</h2><a href="<?php echo htmlspecialchars(pickled_frontend_url('coach/availability.php')); ?>">Edit Availability</a></header>
                        <div class="availability-list">
                            <?php foreach ($availability as [$day, $date, $hours, $status]): ?>
                                <article><span><?php echo coach_icon($icons, 'calendar'); ?><strong><?php echo htmlspecialchars($day); ?></strong><small><?php echo htmlspecialchars($date); ?></small></span><b><?php echo htmlspecialchars($hours); ?></b><em class="<?php echo strtolower($status); ?>"><?php echo htmlspecialchars($status); ?></em></article>
                            <?php endforeach; ?>
                        </div>
                    </article>
                </section>
            </div>

            <aside class="coach-right-panel">
                <article class="coach-card next-session-card">
                    <header><h2>Next Session</h2><em>In 1 hour</em></header>
                    <strong><?php echo htmlspecialchars($nextSession[0]); ?></strong>
                    <h3><?php echo htmlspecialchars($nextSession[2]); ?></h3>
                    <span class="court-badge">Court Pink</span>
                    <div><p><?php echo coach_icon($icons, 'students'); ?><b>10</b><small>Students</small></p><p><?php echo coach_icon($icons, 'clock'); ?><b>1 hour</b><small>Duration</small></p></div>
                </article>

                <article class="coach-card announcements-card" id="announcements">
                    <header><h2>Announcements</h2><a href="<?php echo htmlspecialchars(pickled_frontend_url('coach/announcements.php')); ?>">View all</a></header>
                    <?php foreach ($announcements as [$title, $copy, $tone, $icon]): ?>
                        <article class="announcement-item <?php echo $tone; ?>"><i><?php echo coach_icon($icons, $icon); ?></i><div><strong><?php echo htmlspecialchars($title); ?></strong><span><?php echo htmlspecialchars($copy); ?></span></div><b></b></article>
                    <?php endforeach; ?>
                </article>

                <article class="coach-card coach-profile-card" id="profile">
                    <header><h2>My Profile</h2><a href="<?php echo htmlspecialchars(pickled_frontend_url('coach/profile.php')); ?>">View Profile</a></header>
                    <div class="profile-row"><span class="coach-photo"><img src="<?php echo htmlspecialchars(pickled_asset_url('img/court/academy.png')); ?>" alt="<?php echo htmlspecialchars($coachName); ?>"></span><div><strong><?php echo htmlspecialchars($coachName); ?></strong><small>Pickleball Coach</small></div></div>
                    <p><?php echo coach_icon($icons, 'mail'); ?> <?php echo htmlspecialchars($coach['email'] ?? 'mia.coach@pickled.ph'); ?></p>
                    <p><?php echo coach_icon($icons, 'phone'); ?> 0912 345 6789</p>
                    <div class="coach-specialties"><span>Kids Development</span><span>Youth Coaching</span><span>Private Coaching</span><span>Social Play</span></div>
                </article>
            </aside>
        </section>

        <footer class="coach-footer">© <?php echo date('Y'); ?> Pickled. All rights reserved.</footer>
    </main>
</div>
</body>
</html>
