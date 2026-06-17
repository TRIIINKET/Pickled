<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../includes/avatar-helper.php';
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../app/services/FeedbackService.php';

pickled_start_secure_session();
pickled_init_csrf();

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'coach') {
    header('Location: ' . pickled_frontend_url('auth/login.php?role=coach&redirect=coach/profile.php'));
    exit;
}

$coach = $_SESSION['user'];
$coachId = (int) ($coach['id'] ?? 0);
$coachName = $coach['name'] ?? 'Coach Mia Santos';
$coachEmail = $coach['email'] ?? '';
$coachProfile = [
    'phone' => '',
    'city' => '',
    'province' => '',
    'avatar' => pickled_avatar_default_path(),
    'specialization' => '',
    'experience' => '',
    'bio' => '',
    'status' => 'active',
];
$successMsg = '';
$errorMsg = '';
$fieldErrors = [];
$feedbackService = new FeedbackService();
$coachFeedbackStats = $feedbackService->statsForCoach($coachId);
$recentCoachFeedback = $feedbackService->recentForCoach($coachId, 8);
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayLabel = $today->format('M j, Y (D)');
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');

$pdo = Database::enabled() ? Database::connection() : null;
$coachImageSelect = $pdo && pickled_avatar_profile_column_exists($pdo, 'coach_profiles', 'profile_image')
    ? "COALESCE(NULLIF(cp.profile_image, ''), NULLIF(up.avatar, ''), 'avatars/default.png') AS avatar"
    : "COALESCE(NULLIF(up.avatar, ''), 'avatars/default.png') AS avatar";

if ($pdo && $coachId > 0) {
    try {
        $stmt = $pdo->prepare(
            "SELECT u.name, u.email,
                    COALESCE(up.phone, '') AS phone,
                    COALESCE(up.city, '') AS city,
                    COALESCE(up.province, '') AS province,
                    COALESCE(cp.specialization, '') AS specialization,
                    COALESCE(cp.experience, '') AS experience,
                    COALESCE(cp.bio, '') AS bio,
                    COALESCE(cp.status, 'active') AS status,
                    $coachImageSelect
             FROM users u
             LEFT JOIN user_profiles up ON up.user_id = u.id
             LEFT JOIN coach_profiles cp ON cp.user_id = u.id
             WHERE u.id = :id AND u.role = 'coach'
             LIMIT 1"
        );
        $stmt->execute(['id' => $coachId]);
        $row = $stmt->fetch();
        if ($row) {
            $coachName = (string) $row['name'];
            $coachEmail = (string) $row['email'];
            $coachProfile = [
                'phone' => (string) $row['phone'],
                'city' => (string) $row['city'],
                'province' => (string) $row['province'],
                'avatar' => (string) $row['avatar'],
                'specialization' => (string) $row['specialization'],
                'experience' => (string) $row['experience'],
                'bio' => (string) $row['bio'],
                'status' => (string) $row['status'],
            ];
            $_SESSION['user']['name'] = $coachName;
            $_SESSION['user']['email'] = $coachEmail;
        }
    } catch (Throwable $e) {
        error_log('Coach profile load failed: ' . $e->getMessage());
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'update_coach_profile') {
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Invalid request. Please refresh and try again.';
    } elseif (!$pdo || $coachId <= 0) {
        $errorMsg = 'Please log in again before updating your profile.';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        $province = trim((string) ($_POST['province'] ?? ''));
        $specialization = trim((string) ($_POST['specialization'] ?? ''));
        $experience = trim((string) ($_POST['experience'] ?? ''));
        $bio = trim((string) ($_POST['bio'] ?? ''));
        $avatar = trim((string) ($coachProfile['avatar'] ?? pickled_avatar_default_path())) ?: pickled_avatar_default_path();

        try { $name = validateName($name); } catch (RuntimeException $e) { $fieldErrors['name'] = $e->getMessage(); }
        try { $phone = $phone !== '' ? validatePhonePH($phone) : ''; } catch (RuntimeException $e) { $fieldErrors['phone'] = $e->getMessage(); }
        try { $city = validateText($city, false, 120); } catch (RuntimeException $e) { $fieldErrors['city'] = $e->getMessage(); }
        try { $province = validateText($province, false, 120); } catch (RuntimeException $e) { $fieldErrors['province'] = $e->getMessage(); }
        try { $specialization = validateText($specialization, false, 160); } catch (RuntimeException $e) { $fieldErrors['specialization'] = $e->getMessage(); }
        try { $experience = validateText($experience, false, 160); } catch (RuntimeException $e) { $fieldErrors['experience'] = $e->getMessage(); }
        try { $bio = validateText($bio, false, 1000); } catch (RuntimeException $e) { $fieldErrors['bio'] = $e->getMessage(); }

        try {
            $newAvatar = pickled_store_avatar_upload($_FILES['avatar'] ?? [], $coachId, 'coach');
            if ($newAvatar !== null) {
                $avatar = $newAvatar;
            }
        } catch (Throwable $e) {
            error_log('Coach avatar upload failed: ' . $e->getMessage());
            $fieldErrors['avatar'] = $e instanceof RuntimeException ? $e->getMessage() : 'Profile photo upload failed. Please try again.';
        }

        if ($fieldErrors) {
            $errorMsg = reset($fieldErrors) ?: 'Please check the highlighted fields.';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('UPDATE users SET name = :name WHERE id = :id AND role = :role');
                $stmt->execute(['name' => $name, 'id' => $coachId, 'role' => 'coach']);

                $stmt = $pdo->prepare(
                    'INSERT INTO user_profiles (user_id, phone, city, province, avatar)
                     VALUES (:user_id, :phone, :city, :province, :avatar)
                     ON DUPLICATE KEY UPDATE
                        phone = VALUES(phone),
                        city = VALUES(city),
                        province = VALUES(province),
                        avatar = VALUES(avatar)'
                );
                $profileSaved = $stmt->execute([
                    'user_id' => $coachId,
                    'phone' => $phone,
                    'city' => $city,
                    'province' => $province,
                    'avatar' => $avatar,
                ]);
                error_log('Avatar database update result: coach user_profiles.avatar user_id=' . $coachId . '; avatar=' . $avatar . '; result=' . ($profileSaved ? 'success' : 'failed') . '; row_count=' . $stmt->rowCount());

                $stmt = $pdo->prepare(
                    'INSERT INTO coach_profiles (user_id, specialization, bio, experience, status)
                     VALUES (:user_id, :specialization, :bio, :experience, :status)
                     ON DUPLICATE KEY UPDATE
                        specialization = VALUES(specialization),
                        bio = VALUES(bio),
                        experience = VALUES(experience),
                        status = VALUES(status)'
                );
                $stmt->execute([
                    'user_id' => $coachId,
                    'specialization' => $specialization,
                    'bio' => $bio,
                    'experience' => $experience,
                    'status' => $coachProfile['status'] ?: 'active',
                ]);
                pickled_update_coach_profile_image_if_available($pdo, $coachId, $avatar);

                $pdo->commit();
                $_SESSION['user']['name'] = $name;
                $coachName = $name;
                $coachProfile = [
                    'phone' => $phone,
                    'city' => $city,
                    'province' => $province,
                    'avatar' => $avatar,
                    'specialization' => $specialization,
                    'experience' => $experience,
                    'bio' => $bio,
                    'status' => $coachProfile['status'] ?: 'active',
                ];
                $successMsg = 'Coach profile updated.';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Coach profile update failed: ' . $e->getMessage());
                $errorMsg = 'Unable to save coach profile changes right now.';
            }
        }
    }
}

$coachAvatarUrl = pickled_avatar_url($coachProfile['avatar'] ?? pickled_avatar_default_path());
$coachInitial = strtoupper(substr($coachName !== '' ? $coachName : $coachEmail, 0, 1));

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
    'star' => '<path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9Z"/>',
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
    ['Reviews', number_format((int) $coachFeedbackStats['total_reviews'])],
    ['Sessions This Month', '36'],
    ['Years Coaching', '3'],
    ['Rating', number_format((float) $coachFeedbackStats['average_rating'], 1)],
];

$specializations = array_values(array_filter(array_map('trim', explode(',', (string) ($coachProfile['specialization'] ?: 'Beginner Coaching, Youth Development, Social Play')))));
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
    <link rel="stylesheet" href="<?php echo htmlspecialchars(pickled_asset_url('css/coach-dashboard.css?v=20260615a')); ?>">
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
                    <summary><span class="coach-photo small" data-fallback="<?php echo htmlspecialchars($coachInitial); ?>"><img src="<?php echo htmlspecialchars($coachAvatarUrl); ?>" alt="<?php echo htmlspecialchars($coachName); ?>" onerror="this.remove();"></span><span><strong>Coach</strong><small>Pickleball Coach</small></span><b>⌄</b></summary>
                    <form method="post" action="<?php echo htmlspecialchars(pickled_frontend_url('auth/logout.php')); ?>"><input type="hidden" name="csrf_token" value="<?php echo $logoutCsrf; ?>"><button type="submit">Logout</button></form>
                </details>
            </div>
        </header>

        <?php if ($successMsg): ?><div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div><?php endif; ?>
        <?php if ($errorMsg): ?><div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div><?php endif; ?>

        <section class="page-intro page-first-section">
            <p>Manage your coaching account and personal details.</p>
            <span><?php echo profile_icon($icons, 'check'); ?>Profile settings</span>
        </section>

        <section class="profile-workspace">
            <aside class="profile-left">
                <article class="coach-card profile-summary-card">
                    <span class="coach-photo profile-large" data-fallback="<?php echo htmlspecialchars($coachInitial); ?>"><img id="coachAvatarPreview" src="<?php echo htmlspecialchars($coachAvatarUrl); ?>" alt="<?php echo htmlspecialchars($coachName); ?>" onerror="this.remove();"></span>
                    <h2><?php echo htmlspecialchars($coachName); ?></h2>
                    <p>Pickleball Coach</p>
                    <small>Member Since May 2026</small>
                    <em>Active</em>
                    <div class="profile-stats">
                        <?php foreach ($stats as [$label, $value]): ?><article><strong><?php echo htmlspecialchars($value); ?></strong><span><?php echo htmlspecialchars($label); ?></span></article><?php endforeach; ?>
                    </div>
                    <div class="profile-actions">
                        <label class="coach-photo-upload"><?php echo profile_icon($icons, 'camera'); ?>Change Photo<input id="coachAvatarInput" form="coachProfileForm" type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></label>
                        <?php if (isset($fieldErrors['avatar'])): ?><small class="form-error"><?php echo htmlspecialchars($fieldErrors['avatar']); ?></small><?php endif; ?>
                    </div>
                </article>
            </aside>

            <div class="profile-settings">
                <section class="coach-card profile-form-card">
                    <header><h2>Account Information</h2><span><?php echo profile_icon($icons, 'check'); ?>Editable</span></header>
                    <form id="coachProfileForm" class="profile-form-grid" method="post" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="update_coach_profile">
                        <label class="full">Full Name<input type="text" name="name" value="<?php echo htmlspecialchars($coachName); ?>" minlength="2" maxlength="80" pattern="[A-Za-z][A-Za-z .'\-]*" required><?php if (isset($fieldErrors['name'])): ?><em><?php echo htmlspecialchars($fieldErrors['name']); ?></em><?php endif; ?></label>
                        <label>Email<input type="email" value="<?php echo htmlspecialchars($coachEmail); ?>" readonly aria-readonly="true"></label>
                        <label>Phone Number<input type="tel" name="phone" value="<?php echo htmlspecialchars((string) $coachProfile['phone']); ?>" maxlength="13" pattern="(09[0-9]{9}|\+639[0-9]{9}|639[0-9]{9})" placeholder="09123456789"><?php if (isset($fieldErrors['phone'])): ?><em><?php echo htmlspecialchars($fieldErrors['phone']); ?></em><?php endif; ?></label>
                        <label>City<input type="text" name="city" value="<?php echo htmlspecialchars((string) $coachProfile['city']); ?>" maxlength="120"><?php if (isset($fieldErrors['city'])): ?><em><?php echo htmlspecialchars($fieldErrors['city']); ?></em><?php endif; ?></label>
                        <label>Province<input type="text" name="province" value="<?php echo htmlspecialchars((string) $coachProfile['province']); ?>" maxlength="120"><?php if (isset($fieldErrors['province'])): ?><em><?php echo htmlspecialchars($fieldErrors['province']); ?></em><?php endif; ?></label>
                        <label>Experience<input type="text" name="experience" value="<?php echo htmlspecialchars((string) $coachProfile['experience']); ?>" maxlength="160"><?php if (isset($fieldErrors['experience'])): ?><em><?php echo htmlspecialchars($fieldErrors['experience']); ?></em><?php endif; ?></label>
                        <label class="full">Specialization<input type="text" name="specialization" value="<?php echo htmlspecialchars((string) $coachProfile['specialization']); ?>" maxlength="160" placeholder="Beginner Coaching, Youth Development"><?php if (isset($fieldErrors['specialization'])): ?><em><?php echo htmlspecialchars($fieldErrors['specialization']); ?></em><?php endif; ?></label>
                        <label class="full">Bio<textarea name="bio" maxlength="1000"><?php echo htmlspecialchars((string) $coachProfile['bio']); ?></textarea><?php if (isset($fieldErrors['bio'])): ?><em><?php echo htmlspecialchars($fieldErrors['bio']); ?></em><?php endif; ?></label>
                        <div class="settings-actions full"><button class="bookings-button primary" type="submit">Save Profile</button></div>
                    </form>
                </section>

                <section class="coach-card profile-form-card">
                    <header><h2>Coaching Information</h2><span><?php echo profile_icon($icons, 'check'); ?>Changes saved</span></header>
                    <div class="coach-check-list">
                        <?php foreach ($specializations as $item): ?><label><input type="checkbox" checked> <?php echo htmlspecialchars($item); ?></label><?php endforeach; ?>
                    </div>
                </section>

                <section class="coach-card profile-form-card" id="feedback">
                    <header><h2><?php echo profile_icon($icons, 'star'); ?>Feedback</h2><span><?php echo number_format((float) $coachFeedbackStats['average_rating'], 1); ?> / 5 average</span></header>
                    <div class="coach-check-list">
                        <?php foreach ($recentCoachFeedback as $review): ?>
                            <label>
                                <strong><?php echo (int) $review['rating']; ?> / 5 - <?php echo htmlspecialchars($review['user_name'] ?? 'Player'); ?></strong>
                                <span><?php echo htmlspecialchars((string) ($review['comment'] ?? '')); ?></span>
                            </label>
                        <?php endforeach; ?>
                        <?php if (!$recentCoachFeedback): ?>
                            <label>No feedback has been submitted for your assigned sessions yet.</label>
                        <?php endif; ?>
                    </div>
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
                    <div class="password-summary"><label>Password<input type="password" value="password" readonly></label><a href="<?php echo htmlspecialchars(pickled_frontend_url('auth/change-password.php')); ?>">Change Password</a></div>
                </section>
            </div>
        </section>
    </main>
</div>
<script>
    const coachAvatarInput = document.getElementById('coachAvatarInput');
    const coachAvatarPreview = document.getElementById('coachAvatarPreview');
    coachAvatarInput?.addEventListener('change', () => {
        const file = coachAvatarInput.files?.[0];
        if (!file || !coachAvatarPreview) return;
        const allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowed.includes(file.type) || file.size > 2 * 1024 * 1024) {
            coachAvatarInput.value = '';
            alert('Profile photo must be JPG, JPEG, PNG, or WEBP and 2MB or smaller.');
            return;
        }
        coachAvatarPreview.src = URL.createObjectURL(file);
    });
</script>
</body>
</html>
