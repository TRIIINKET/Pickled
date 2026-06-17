<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../app/repositories/BookingRepository.php';
require_once __DIR__ . '/../app/services/NotificationService.php';

pickled_start_secure_session();
pickled_init_csrf();

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'coach') {
    header('Location: ' . pickled_frontend_url('auth/login.php?role=coach&redirect=coach/students.php'));
    exit;
}

$coach = $_SESSION['user'];
$coachId = (int) ($coach['id'] ?? 0);
$coachName = $coach['name'] ?? 'Coach Mia';
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todaySql = $today->format('Y-m-d');
$todayLabel = $today->format('M j, Y (D)');
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');
$bookingRepository = new BookingRepository();
$notificationService = new NotificationService();
$coachUnreadCount = $coachId > 0 ? $notificationService->unreadCount($coachId) : 0;

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
    'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
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

$coachBookingItems = $coachId > 0 ? $bookingRepository->getItemsForCoach($coachId) : [];
$studentRowsByKey = [];
foreach ($coachBookingItems as $item) {
    $studentName = trim((string) ($item['user_name'] ?? ''));
    if ($studentName === '') {
        continue;
    }
    $program = (string) ($item['name'] ?? 'Booked session');
    $key = (int) ($item['user_id'] ?? 0) . '|' . strtolower($program);
    $sessionDate = (string) ($item['booking_date_raw'] ?? '');
    $sessionSort = $sessionDate . ' ' . (string) ($item['start_time'] ?? '00:00:00');
    $sessionLabel = $sessionDate !== ''
        ? date('M j, Y', strtotime($sessionDate)) . ' · ' . (string) ($item['booking_time'] ?? '')
        : 'No scheduled date';
    $row = [
        'student_name' => $studentName,
        'email' => (string) ($item['user_email'] ?? ''),
        'phone' => (string) ($item['user_phone'] ?? ''),
        'program' => $program,
        'next_session' => $sessionLabel,
        'booking_status' => ucfirst((string) ($item['booking_status'] ?? 'confirmed')),
        'booking_id' => (int) ($item['booking_id'] ?? 0),
        'sort_date' => $sessionSort,
        'is_future' => $sessionDate >= $todaySql,
    ];

    if (!isset($studentRowsByKey[$key])) {
        $studentRowsByKey[$key] = $row;
        continue;
    }

    $existing = $studentRowsByKey[$key];
    if (($row['is_future'] && !$existing['is_future']) || ($row['is_future'] === $existing['is_future'] && $row['sort_date'] < $existing['sort_date'])) {
        $studentRowsByKey[$key] = $row;
    }
}
$students = array_values($studentRowsByKey);
usort($students, static fn(array $a, array $b): int => [$a['student_name'], $a['program']] <=> [$b['student_name'], $b['program']]);

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
                <a class="<?php echo $active ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($href); ?>"><?php echo students_icon($icons, $icon); ?><span><?php echo htmlspecialchars($label); ?></span><?php if ($label === 'Announcements' && $coachUnreadCount > 0): ?><em><?php echo min($coachUnreadCount, 9); ?></em><?php endif; ?></a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="coach-main students-main">
        <header class="coach-topbar">
            <div><h1>Students</h1></div>
            <div class="coach-top-actions">
                <span class="coach-date-pill"><?php echo students_icon($icons, 'calendar'); ?><span><?php echo htmlspecialchars($todayLabel); ?></span></span>
                <a class="coach-notification" href="<?php echo htmlspecialchars(pickled_frontend_url('coach/announcements.php')); ?>" aria-label="Announcements"><?php echo students_icon($icons, 'bell'); ?><?php if ($coachUnreadCount > 0): ?><em><?php echo min($coachUnreadCount, 9); ?></em><?php endif; ?></a>
                <details class="coach-top-profile">
                    <summary><span class="coach-photo small"><img src="<?php echo htmlspecialchars(pickled_asset_url('img/court/academy.png')); ?>" alt="<?php echo htmlspecialchars($coachName); ?>"></span><span><strong>Coach</strong><small>Pickleball Coach</small></span><b>⌄</b></summary>
                    <form method="post" action="<?php echo htmlspecialchars(pickled_frontend_url('auth/logout.php')); ?>"><input type="hidden" name="csrf_token" value="<?php echo $logoutCsrf; ?>"><button type="submit">Logout</button></form>
                </details>
            </div>
        </header>

        <section class="coach-card students-roster-card page-first-section">
            <header class="student-list-header">
                <div>
                    <h2>Active Students</h2>
                    <p>Real students from confirmed, ongoing, and completed coach bookings.</p>
                </div>
                <span><?php echo count($students); ?> students</span>
            </header>
            <div class="students-toolbar">
                <label class="schedule-search"><?php echo students_icon($icons, 'search'); ?><input id="studentSearch" type="search" placeholder="Search student name or program..."></label>
                <label class="student-sort-control">Sort by
                    <select id="studentSort">
                        <option value="name">Student Name</option>
                        <option value="program">Program</option>
                        <option value="session">Next Session</option>
                        <option value="status">Booking Status</option>
                    </select>
                </label>
            </div>
            <div class="student-list-table" id="studentList">
                <div class="student-list-row head"><span>Student Name</span><span>Email</span><span>Phone</span><span>Program</span><span>Next Session</span><span>Booking Status</span><span>Actions</span></div>
                <?php foreach ($students as $student): ?>
                    <?php
                        $name = (string) $student['student_name'];
                        $parts = preg_split('/\s+/', trim($name));
                        $initials = strtoupper(substr($parts[0] ?? 'S', 0, 1) . substr($parts[1] ?? '', 0, 1));
                    ?>
                    <article class="student-list-row" data-name="<?php echo htmlspecialchars(strtolower($name)); ?>" data-program="<?php echo htmlspecialchars(strtolower((string) $student['program'])); ?>" data-session="<?php echo htmlspecialchars((string) $student['next_session']); ?>" data-status="<?php echo htmlspecialchars(strtolower((string) $student['booking_status'])); ?>">
                        <span class="student-list-name"><b class="green"><?php echo htmlspecialchars($initials); ?></b><strong><?php echo htmlspecialchars($name); ?></strong></span>
                        <span><?php echo htmlspecialchars((string) $student['email']); ?></span>
                        <span><?php echo htmlspecialchars((string) ($student['phone'] ?: '-')); ?></span>
                        <span><?php echo htmlspecialchars((string) $student['program']); ?></span>
                        <span><?php echo htmlspecialchars((string) $student['next_session']); ?></span>
                        <span><?php echo htmlspecialchars((string) $student['booking_status']); ?></span>
                        <span class="student-list-actions">
                            <button type="button" data-student-detail='<?php echo htmlspecialchars(json_encode($student), ENT_QUOTES, 'UTF-8'); ?>'>View Student</button>
                            <a class="student-action-primary" href="<?php echo htmlspecialchars(pickled_frontend_url('coach/schedule.php?booking_id=' . (int) $student['booking_id'])); ?>">View Booking</a>
                        </span>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if (!$students): ?><p class="students-empty-note">No booked students yet.</p><?php endif; ?>
            <p class="students-empty-note" id="studentEmpty" hidden>No students match your search.</p>
        </section>

        <dialog class="coach-modal" id="studentDetailDialog">
            <div class="coach-modal-form">
                <header><h2>Student Details</h2><button type="button" data-close-student>Close</button></header>
                <dl class="timeoff-detail-list">
                    <div><dt>Name</dt><dd id="studentDetailName"></dd></div>
                    <div><dt>Email</dt><dd id="studentDetailEmail"></dd></div>
                    <div><dt>Phone</dt><dd id="studentDetailPhone"></dd></div>
                    <div><dt>Program</dt><dd id="studentDetailProgram"></dd></div>
                    <div><dt>Next Session</dt><dd id="studentDetailSession"></dd></div>
                    <div><dt>Booking Status</dt><dd id="studentDetailStatus"></dd></div>
                </dl>
            </div>
        </dialog>
    </main>
</div>
<script>
(() => {
    const search = document.getElementById('studentSearch');
    const sort = document.getElementById('studentSort');
    const list = document.getElementById('studentList');
    const empty = document.getElementById('studentEmpty');
    if (!search || !sort || !list || !empty) return;
    const rows = Array.from(list.querySelectorAll('.student-list-row:not(.head)'));
    const apply = () => {
        const query = search.value.trim().toLowerCase();
        const key = sort.value;
        rows.sort((a, b) => {
            return String(a.dataset[key] || '').localeCompare(String(b.dataset[key] || ''));
        });
        let visible = 0;
        rows.forEach(row => {
            const match = !query || row.dataset.name.includes(query) || row.dataset.program.includes(query);
            row.hidden = !match;
            if (match) visible++;
            list.appendChild(row);
        });
        empty.hidden = visible > 0;
    };
    search.addEventListener('input', apply);
    sort.addEventListener('change', apply);
    document.querySelectorAll('[data-student-detail]').forEach(button => {
        button.addEventListener('click', () => {
            const data = JSON.parse(button.dataset.studentDetail || '{}');
            document.getElementById('studentDetailName').textContent = data.student_name || '';
            document.getElementById('studentDetailEmail').textContent = data.email || '';
            document.getElementById('studentDetailPhone').textContent = data.phone || '-';
            document.getElementById('studentDetailProgram').textContent = data.program || '';
            document.getElementById('studentDetailSession').textContent = data.next_session || '';
            document.getElementById('studentDetailStatus').textContent = data.booking_status || '';
            document.getElementById('studentDetailDialog')?.showModal();
        });
    });
    document.querySelector('[data-close-student]')?.addEventListener('click', () => document.getElementById('studentDetailDialog')?.close());
})();
</script>
</body>
</html>
