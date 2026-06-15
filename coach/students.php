<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/paths.php';

pickled_start_secure_session();
pickled_init_csrf();

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'coach') {
    header('Location: ' . pickled_frontend_url('auth/login.php?role=coach&redirect=coach/students.php'));
    exit;
}

$coach = $_SESSION['user'];
$coachName = $coach['name'] ?? 'Coach Mia';
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayLabel = $today->format('M j, Y (D)');
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');

function students_icon(array $icons, string $name): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['students']) . '</svg>';
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
    'download' => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>',
    'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    'more' => '<circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>',
    'star' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9z"/>',
    'trend' => '<path d="m3 17 6-6 4 4 8-8"/><path d="M14 7h7v7"/>',
];

$navItems = [
    ['Dashboard', pickled_frontend_url('coach/dashboard.php'), 'home', false],
    ['My Schedule', pickled_frontend_url('coach/schedule.php'), 'calendar', false],
    ['Students', pickled_frontend_url('coach/students.php'), 'students', true],
    ['Availability', pickled_frontend_url('coach/availability.php'), 'clock', false],
    ['Announcements', pickled_frontend_url('coach/announcements.php'), 'megaphone', false],
    ['Profile', pickled_frontend_url('coach/profile.php'), 'profile', false],
];

$students = [
    ['MR', 'Mia Reyes', 'Kids Class (6-10)', 'Beginner', 'Jun 10, 2026', '92%', 'pink', 'beginner'],
    ['JD', 'Juan Dela Cruz', 'Youth Development', 'Intermediate', 'Jun 9, 2026', '88%', 'green', 'intermediate'],
    ['AS', 'Alyssa Santos', 'Private Coaching', 'Advanced', 'Jun 10, 2026', '95%', 'purple', 'advanced'],
    ['BR', 'Beatrice Ramos', 'Kids Class (6-10)', 'Beginner', 'Jun 8, 2026', '90%', 'pink', 'beginner'],
    ['CL', 'Caleb Lim', 'Youth Development', 'Intermediate', 'Jun 9, 2026', '85%', 'orange', 'intermediate'],
    ['SG', 'Sophia Garcia', 'Private Coaching', 'Advanced', 'Jun 7, 2026', '93%', 'purple', 'advanced'],
    ['MT', 'Miguel Tan', 'Social Play', 'Beginner', 'Jun 7, 2026', '78%', 'orange', 'beginner'],
    ['LP', 'Liam Ong', 'Kids Class (6-10)', 'Beginner', 'Jun 6, 2026', '89%', 'green', 'beginner'],
];

$recentSessions = [
    ['Jun 10', 'Kids Class (6-10)', 'Court Pink', '9:00 AM - 10:00 AM', 'Attended', 'success'],
    ['Jun 8', 'Kids Class (6-10)', 'Court Pink', '9:00 AM - 10:00 AM', 'Attended', 'success'],
    ['Jun 6', 'Kids Class (6-10)', 'Court Pink', '9:00 AM - 10:00 AM', 'Absent', 'danger'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - Pickled Coach</title>
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
                <a class="<?php echo $active ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($href); ?>"><?php echo students_icon($icons, $icon); ?><span><?php echo htmlspecialchars($label); ?></span><?php if ($label === 'Announcements'): ?><em>4</em><?php endif; ?></a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="coach-main students-main">
        <header class="coach-topbar">
            <div><h1>Students</h1></div>
            <div class="coach-top-actions">
                <button class="coach-date-pill" type="button"><?php echo students_icon($icons, 'calendar'); ?><span><?php echo htmlspecialchars($todayLabel); ?></span></button>
                <a class="coach-notification" href="<?php echo htmlspecialchars(pickled_frontend_url('coach/announcements.php')); ?>" aria-label="Announcements"><?php echo students_icon($icons, 'bell'); ?><em>4</em></a>
                <details class="coach-top-profile">
                    <summary><span class="coach-photo small"><img src="<?php echo htmlspecialchars(pickled_asset_url('img/court/academy.png')); ?>" alt="<?php echo htmlspecialchars($coachName); ?>"></span><span><strong>Coach</strong><small>Pickleball Coach</small></span><b>⌄</b></summary>
                    <form method="post" action="<?php echo htmlspecialchars(pickled_frontend_url('auth/logout.php')); ?>"><input type="hidden" name="csrf_token" value="<?php echo $logoutCsrf; ?>"><button type="submit">Logout</button></form>
                </details>
            </div>
        </header>

        <section class="coach-kpi-grid students-kpis page-first-section">
            <article class="coach-kpi green"><?php echo students_icon($icons, 'students'); ?><div><span>My Students</span><strong>38</strong><small>Active</small></div></article>
            <article class="coach-kpi pink"><?php echo students_icon($icons, 'calendar'); ?><div><span>Sessions This Week</span><strong>12</strong><small>With my students</small></div></article>
            <article class="coach-kpi orange"><?php echo students_icon($icons, 'trend'); ?><div><span>Average Attendance</span><strong>91%</strong><small>This month</small></div></article>
            <article class="coach-kpi purple"><?php echo students_icon($icons, 'star'); ?><div><span>Programs</span><strong>4</strong><small>You are coaching</small></div></article>
        </section>

        <section class="students-workspace">
            <section class="coach-card students-table-card">
                <div class="students-toolbar">
                    <label class="schedule-search"><?php echo students_icon($icons, 'search'); ?><input type="search" placeholder="Search student name..."></label>
                    <select><option>All Programs</option><option>Kids Class</option><option>Youth Development</option><option>Private Coaching</option></select>
                    <select><option>All Levels</option><option>Beginner</option><option>Intermediate</option><option>Advanced</option></select>
                    <button><?php echo students_icon($icons, 'download'); ?> Export</button>
                </div>
                <div class="coach-students-table">
                    <div class="coach-student-row head"><span>Student</span><span>Program</span><span>Level</span><span>Last Session</span><span>Attendance</span><span>Actions</span></div>
                    <?php foreach ($students as [$initials, $name, $program, $level, $last, $attendance, $tone, $levelKey]): ?>
                        <div class="coach-student-row <?php echo $name === 'Mia Reyes' ? 'selected' : ''; ?>"><span><b class="<?php echo $tone; ?>"><?php echo htmlspecialchars($initials); ?></b><strong><?php echo htmlspecialchars($name); ?></strong></span><span><?php echo htmlspecialchars($program); ?></span><span><em class="skill-badge <?php echo $levelKey; ?>"><?php echo htmlspecialchars($level); ?></em></span><span><?php echo htmlspecialchars($last); ?></span><span class="<?php echo (int) $attendance < 80 ? 'attendance-warn' : ''; ?>"><?php echo htmlspecialchars($attendance); ?></span><span><button>View</button><button class="icon-only"><?php echo students_icon($icons, 'more'); ?></button></span></div>
                    <?php endforeach; ?>
                </div>
                <footer class="students-pagination"><span>Showing 1 to 8 of 38 students</span><div><button disabled>‹</button><button class="active">1</button><button>2</button><button>3</button><button>4</button><button>5</button><button>›</button></div></footer>
            </section>

            <aside class="coach-card student-detail-panel">
                <header class="student-panel-head"><div><span class="student-photo"><img src="<?php echo htmlspecialchars(pickled_asset_url('img/court/academy.png')); ?>" alt="Mia Reyes"></span><div><h2>Mia Reyes <em>Active</em></h2><p>Age 9 • Student ID: STU-0001</p></div></div><button>×</button></header>
                <nav class="student-tabs"><a class="active" href="#">Overview</a><a href="#">Sessions</a><a href="#">Attendance</a><a href="#">Notes</a></nav>
                <section class="student-info-grid"><p><small>Program</small><strong>Kids Class (6-10)</strong></p><p><small>Email (Parent)</small><strong>dan.reyes@email.com</strong></p><p><small>Level</small><strong>Beginner</strong></p><p><small>Phone (Parent)</small><strong>0917 123 4567</strong></p><p><small>Court Preference</small><strong>Court Pink</strong></p><p><small>Joined</small><strong>May 20, 2026</strong></p></section>
                <section class="profile-widget"><header><h3>Recent Sessions</h3><a href="#">View all</a></header><?php foreach ($recentSessions as [$date, $session, $court, $time, $status, $tone]): ?><article><time><?php echo htmlspecialchars($date); ?></time><div><strong><?php echo htmlspecialchars($session); ?></strong><span><?php echo htmlspecialchars($court); ?> • <?php echo htmlspecialchars($time); ?></span></div><em class="status-pill status-<?php echo $tone; ?>"><?php echo htmlspecialchars($status); ?></em></article><?php endforeach; ?></section>
                <section class="profile-widget progress-widget"><header><h3>Progress Tracker</h3></header><?php foreach ([['Forehand',4],['Backhand',2],['Serve',3],['Footwork',4],['Game Sense',2]] as [$skill, $rating]): ?><p><span><?php echo htmlspecialchars($skill); ?></span><strong><?php echo str_repeat('★', $rating) . str_repeat('☆', 5 - $rating); ?></strong></p><?php endforeach; ?></section>
                <section class="profile-widget notes-widget"><header><h3>Coach Notes</h3><button>Edit</button></header><p>Mia has good footwork and rallies well.</p><p>Needs more consistency on backhand.</p><p>Keep encouraging her during drills.</p></section>
            </aside>
        </section>
    </main>
</div>
</body>
</html>
