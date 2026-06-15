<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/booking-system.php';
require_once __DIR__ . '/../database/Database.php';

pickled_start_secure_session();
pickled_init_csrf();
pickled_require_login('resident/profile.php');

$pageTitle = 'My Profile - Pickled';
$activePage = 'profile.php';
$basePath = '../';
$csrfToken = pickled_csrf_token();
$user = $_SESSION['user'] ?? [];
$userId = (int) ($user['id'] ?? 0);
$profile = [
  'phone' => '',
  'city' => '',
  'province' => '',
  'avatar' => 'avatars/default.png',
];
$message = '';

if ($userId > 0) {
  try {
    $stmt = Database::connection()->prepare(
      'SELECT u.id, u.name, u.email, u.role,
              COALESCE(up.phone, \'\') AS phone,
              COALESCE(up.city, \'\') AS city,
              COALESCE(up.province, \'\') AS province,
              COALESCE(up.avatar, \'avatars/default.png\') AS avatar
       FROM users u
       LEFT JOIN user_profiles up ON up.user_id = u.id
       WHERE u.id = :id
       LIMIT 1'
    );
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch();

    if ($row) {
      $user = pickled_session_user($row);
      $_SESSION['user'] = $user;
      $profile = [
        'phone' => (string) $row['phone'],
        'city' => (string) $row['city'],
        'province' => (string) $row['province'],
        'avatar' => (string) $row['avatar'],
      ];
      $_SESSION['player_profile'] = $profile;
    }
  } catch (Throwable $e) {
    error_log('Profile load failed: ' . $e->getMessage());
    $profile = array_merge($profile, $_SESSION['player_profile'] ?? []);
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $submittedToken = $_POST['csrf_token'] ?? '';
  if (!pickled_validate_csrf_token($submittedToken)) {
    $message = 'Invalid request. Please refresh and try again.';
  } elseif (($_POST['action'] ?? '') === 'update_profile') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $avatar = trim((string) ($profile['avatar'] ?? 'avatars/default.png'));
    $avatar = $avatar !== '' ? $avatar : 'avatars/default.png';
    $profile = [
      'phone' => trim((string) ($_POST['phone'] ?? '')),
      'city' => trim((string) ($_POST['city'] ?? '')),
      'province' => trim((string) ($_POST['province'] ?? '')),
      'avatar' => $avatar,
    ];

    if ($userId <= 0 || $name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $message = 'Please enter a valid name and email.';
    } else {
      try {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
          'UPDATE users
           SET name = :name, email = :email
           WHERE id = :id AND role = :role'
        );
        $stmt->execute([
          'name' => $name,
          'email' => strtolower($email),
          'id' => $userId,
          'role' => 'player',
        ]);

        $stmt = $pdo->prepare(
          'INSERT INTO user_profiles (user_id, phone, city, province, avatar)
           VALUES (:user_id, :phone, :city, :province, :avatar)
           ON DUPLICATE KEY UPDATE
             phone = VALUES(phone),
             city = VALUES(city),
             province = VALUES(province),
             avatar = VALUES(avatar)'
        );
        $stmt->execute([
          'user_id' => $userId,
          'phone' => $profile['phone'],
          'city' => $profile['city'],
          'province' => $profile['province'],
          'avatar' => $profile['avatar'],
        ]);

        $pdo->commit();

        $user['name'] = $name;
        $user['email'] = strtolower($email);
        $_SESSION['user'] = $user;
        $_SESSION['player_profile'] = $profile;
        $message = 'Profile changes saved.';
      } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
          $pdo->rollBack();
        }
        error_log('Profile update failed: ' . $e->getMessage());
        $message = 'Unable to save profile changes right now.';
      }
    }
  }
}

$name = trim((string) ($user['name'] ?? 'Shemaiah Ezra'));
$email = trim((string) ($user['email'] ?? 'shemaiah@email.com'));
$phone = $profile['phone'] ?? '0917 123 4567';
$city = $profile['city'] ?? 'Quezon City';
$province = $profile['province'] ?? 'Metro Manila';
$initial = strtoupper(substr($name !== '' ? $name : $email, 0, 1));

$extraHead = '<link rel="stylesheet" href="../assets/css/player-profile.css?v=20260615a"/>';

include __DIR__ . '/../includes/header.php';
?>

<main class="player-profile-page">
  <div class="player-profile-shell">
    <aside class="player-profile-nav" aria-label="Account navigation">
      <a class="player-profile-nav__item active" href="profile.php">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M5 20a7 7 0 0 1 14 0"></path></svg>
        My Profile
      </a>
      <a class="player-profile-nav__item" href="#bookings" id="bookings">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"></rect><path d="M8 3v4M16 3v4M4 10h16"></path></svg>
        My Bookings
      </a>
      <a class="player-profile-nav__item" href="#payments" id="payments">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2"></rect><path d="M3 10h18M7 15h3"></path></svg>
        My Payments
      </a>
      <a class="player-profile-nav__item" href="#settings" id="settings">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.6-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1A2 2 0 1 1 7.1 4l.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.6V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1A2 2 0 1 1 19.9 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.1a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.7 1Z"></path></svg>
        Settings
      </a>
      <form method="post" action="<?= htmlspecialchars(pickled_frontend_url('auth/logout.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />
        <button class="player-profile-nav__item player-profile-nav__item--logout" type="submit">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17 15 12 10 7"></path><path d="M15 12H3"></path><path d="M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"></path></svg>
          Logout
        </button>
      </form>
    </aside>

    <section class="player-profile-main" aria-label="Profile settings">
      <?php if ($message): ?>
        <div class="player-profile-message"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <article class="player-profile-card">
        <div class="player-profile-card__header">
          <h1>Profile Information</h1>
          <a class="player-profile-edit" href="#edit-profile">Edit</a>
        </div>

        <div class="player-profile-info">
          <div class="player-profile-identity">
            <div class="player-profile-avatar" aria-hidden="true"><?= htmlspecialchars($initial) ?></div>
            <button class="player-profile-photo" type="button">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.5 4h-5L8 6H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-3l-1.5-2Z"></path><circle cx="12" cy="13" r="3"></circle></svg>
              Change Photo
            </button>
          </div>

          <div class="player-profile-fields">
            <div>
              <span>Full Name</span>
              <strong><?= htmlspecialchars($name) ?></strong>
            </div>
            <div>
              <span>City</span>
              <strong><?= htmlspecialchars($city) ?></strong>
            </div>
            <div>
              <span>Email Address</span>
              <strong><?= htmlspecialchars($email) ?></strong>
            </div>
            <div>
              <span>Province</span>
              <strong><?= htmlspecialchars($province) ?></strong>
            </div>
            <div>
              <span>Phone Number</span>
              <strong><?= htmlspecialchars($phone) ?></strong>
            </div>
          </div>
        </div>
      </article>

      <article class="player-profile-card player-profile-card--password">
        <div class="player-profile-card__header">
          <h2>Password</h2>
          <a class="player-profile-edit" href="#settings">Edit</a>
        </div>
        <div class="player-profile-password">
          <div>
            <span>Password</span>
            <strong>********</strong>
            <small>Last changed 2 months ago</small>
          </div>
          <a class="player-profile-outline" href="<?= htmlspecialchars(pickled_frontend_url('auth/change-password.php')) ?>">Change Password</a>
        </div>
      </article>
    </section>
  </div>
</main>

<section class="player-profile-modal" id="edit-profile" aria-labelledby="edit-profile-title">
  <a class="player-profile-modal__backdrop" href="#" aria-label="Close edit profile modal"></a>
  <form class="player-profile-modal__dialog" method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />
    <input type="hidden" name="action" value="update_profile" />
    <div class="player-profile-modal__header">
      <h2 id="edit-profile-title">Edit Profile</h2>
      <a href="#" aria-label="Close edit profile modal">&times;</a>
    </div>
    <div class="player-profile-modal__grid">
      <label>
        <span>Full Name</span>
        <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" />
      </label>
      <label>
        <span>City</span>
        <select name="city">
          <option<?= $city === 'Quezon City' ? ' selected' : '' ?>>Quezon City</option>
          <option<?= $city === 'Makati' ? ' selected' : '' ?>>Makati</option>
          <option<?= $city === 'Taguig' ? ' selected' : '' ?>>Taguig</option>
          <option<?= $city === 'Manila' ? ' selected' : '' ?>>Manila</option>
        </select>
      </label>
      <label>
        <span>Email Address</span>
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" />
      </label>
      <label>
        <span>Province</span>
        <select name="province">
          <option<?= $province === 'Metro Manila' ? ' selected' : '' ?>>Metro Manila</option>
          <option<?= $province === 'Rizal' ? ' selected' : '' ?>>Rizal</option>
          <option<?= $province === 'Cavite' ? ' selected' : '' ?>>Cavite</option>
          <option<?= $province === 'Laguna' ? ' selected' : '' ?>>Laguna</option>
        </select>
      </label>
      <label>
        <span>Phone Number</span>
        <input type="tel" name="phone" value="<?= htmlspecialchars($phone) ?>" />
      </label>
    </div>
    <div class="player-profile-modal__actions">
      <a class="player-profile-modal__cancel" href="#">Cancel</a>
      <button type="submit">Save Changes</button>
    </div>
  </form>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
