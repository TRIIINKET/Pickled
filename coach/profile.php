<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/paths.php';

pickled_start_secure_session();
pickled_init_csrf();

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'coach') {
    header('Location: ' . pickled_frontend_url('auth/login.php?role=coach&redirect=coach/profile.php'));
    exit;
}

$coach = $_SESSION['user'];
$coachName = $coach['name'] ?? 'Coach Mia Santos';
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayLabel = $today->format('M j, Y (D)');
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');

function profile_icon(array $icons, string $name): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['profile']) . '</svg>';
}

$icons = [
    'home' => '<path d="M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
    'students' => '<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'megaphone' => '<path d="m3 11 18-5v12L3 13z"/><path d="M11 14v4a2 2 0 0 1-4 0v-5"/>',
    'profile' => '<path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/>',
    'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
    'check' => '<path d="m20 6-11 11-5-5"/>',
    'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    'camera' => '<path d="M14.5 4 16 7h4a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h4l1.5-3z"/><circle cx="12" cy="13" r="4"/>',
    'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/>',
];

$navItems = [
    ['Dashboard', pickled_frontend_url('coach/dashboard.php'), 'home', false],
    ['My Schedule', pickled_frontend_url('coach/schedule.php'), 'calendar', false],
    ['Students', pickled_frontend_url('coach/students.php'), 'students', false],
    ['Availability', pickled_frontend_url('coach/availability.php'), 'clock', false],
    ['Announcements', pickled_frontend_url('coach/announcements.php'), 'megaphone', false],
    ['Profile', pickled_frontend_url('coach/profile.php'), 'profile', true],
];

$stats = [
    ['Students', '48'],
    ['Sessions This Month', '36'],
    ['Years Coaching', '3'],
    ['Rating', '4.8'],
];

$specializations = ['Beginner Coaching', 'Youth Development', 'Social Play'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Pickled Coach</title>
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
                <a class="<?php echo $active ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($href); ?>"><?php echo profile_icon($icons, $icon); ?><span><?php echo htmlspecialchars($label); ?></span><?php if ($label === 'Announcements'): ?><em>4</em><?php endif; ?></a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="coach-main profile-main">
        <header class="coach-topbar">
            <div><h1>Profile</h1></div>
            <div class="coach-top-actions">
                <button class="coach-date-pill" type="button"><?php echo profile_icon($icons, 'calendar'); ?><span><?php echo htmlspecialchars($todayLabel); ?></span></button>
                <a class="coach-notification" href="<?php echo htmlspecialchars(pickled_frontend_url('coach/announcements.php')); ?>" aria-label="Announcements"><?php echo profile_icon($icons, 'bell'); ?><em>4</em></a>
                <details class="coach-top-profile">
                    <summary><span class="coach-photo small"><img src="<?php echo htmlspecialchars(pickled_asset_url('img/court/academy.png')); ?>" alt="<?php echo htmlspecialchars($coachName); ?>"></span><span><strong>Coach</strong><small>Pickleball Coach</small></span><b>⌄</b></summary>
                    <form method="post" action="<?php echo htmlspecialchars(pickled_frontend_url('auth/logout.php')); ?>"><input type="hidden" name="csrf_token" value="<?php echo $logoutCsrf; ?>"><button type="submit">Logout</button></form>
                </details>
            </div>
        </header>

        <section class="page-intro page-first-section">
            <p>Manage your coaching account and personal details.</p>
            <span><?php echo profile_icon($icons, 'check'); ?>Changes saved</span>
        </section>

        <section class="profile-workspace">
            <aside class="profile-left">
                <article class="coach-card profile-summary-card">
                    <span class="coach-photo profile-large"><img src="<?php echo htmlspecialchars(pickled_asset_url('img/court/academy.png')); ?>" alt="<?php echo htmlspecialchars($coachName); ?>"></span>
                    <h2><?php echo htmlspecialchars($coachName); ?></h2>
                    <p>Pickleball Coach</p>
                    <small>Member Since May 2026</small>
                    <em>Active</em>
                    <div class="profile-stats">
                        <?php foreach ($stats as [$label, $value]): ?><article><strong><?php echo htmlspecialchars($value); ?></strong><span><?php echo htmlspecialchars($label); ?></span></article><?php endforeach; ?>
                    </div>
                    <div class="profile-actions"><button><?php echo profile_icon($icons, 'camera'); ?>Change Photo</button><a href="#"><?php echo profile_icon($icons, 'eye'); ?>View Public Profile</a></div>
                </article>
            </aside>

            <div class="profile-settings">
                <section class="coach-card profile-form-card">
                    <header><h2>Account Information</h2><span><?php echo profile_icon($icons, 'check'); ?>Changes saved</span></header>
                    <form class="profile-form-grid">
                        <label>First Name<input type="text" value="Mia"></label>
                        <label>Last Name<input type="text" value="Santos"></label>
                        <label>Email<input type="email" value="mia.coach@pickled.ph"></label>
                        <label>Phone Number<input type="tel" value="0912 345 6789"></label>
                        <label>City<input type="text" value="Makati"></label>
                        <label>Province<input type="text" value="Metro Manila"></label>
                    </form>
                </section>

                <section class="coach-card profile-form-card">
                    <header><h2>Coaching Information</h2><span><?php echo profile_icon($icons, 'check'); ?>Changes saved</span></header>
                    <div class="coach-check-list">
                        <?php foreach ($specializations as $item): ?><label><input type="checkbox" checked> <?php echo htmlspecialchars($item); ?></label><?php endforeach; ?>
                    </div>
                    <form class="profile-form-grid">
                        <label>Experience<input type="text" value="3 Years Coaching"></label>
                        <label>Certifications<input type="text" value="PPR Certified Coach"></label>
                        <label class="full">Bio<textarea>Patient and encouraging pickleball coach focused on beginner confidence, youth development, and practical game habits.</textarea></label>
                    </form>
                </section>

                <section class="coach-card profile-form-card">
                    <header><h2>Availability Preferences</h2><span><?php echo profile_icon($icons, 'check'); ?>Changes saved</span></header>
                    <form class="profile-form-grid">
                        <label>Preferred Coaching Hours<select><option>Morning</option><option>Afternoon</option><option>Evening</option></select></label>
                        <label>Preferred Court<select><option>Court Green</option><option>Court Pink</option></select></label>
                        <label>Maximum Sessions Per Day<input type="number" value="8"></label>
                    </form>
                </section>

                <section class="coach-card profile-form-card security-card">
                    <header><h2><?php echo profile_icon($icons, 'shield'); ?>Security</h2><span><?php echo profile_icon($icons, 'check'); ?>Changes saved</span></header>
                    <div class="password-summary"><label>Password<input type="password" value="password" readonly></label><button type="button">Change Password</button></div>
                </section>
            </div>
        </section>
    </main>
</div>
</body>
</html>
