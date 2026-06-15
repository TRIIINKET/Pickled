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
$profileErrors = [];
$passwordErrors = [];
$toast = '';
$openProfileModal = false;
$openPasswordModal = false;

$provinceCities = [
  'Metro Manila' => ['Makati', 'Manila', 'Quezon City', 'Taguig', 'Pasig'],
  'Laguna' => ['Calamba', 'Los Baños', 'Santa Rosa', 'Cabuyao', 'San Pablo'],
  'Cavite' => ['Bacoor', 'Imus', 'Dasmariñas', 'Tagaytay'],
  'Rizal' => ['Antipolo', 'Taytay', 'Cainta'],
];

function player_profile_avatar_src(string $avatar): string
{
  $avatar = trim($avatar);
  if ($avatar === '' || $avatar === 'avatars/default.png') {
    return '';
  }

  if (str_starts_with($avatar, 'img/')) {
    $path = __DIR__ . '/../assets/' . $avatar;
    return is_file($path) ? '../assets/' . $avatar : '';
  }

  $path = __DIR__ . '/../assets/img/' . ltrim($avatar, '/');
  return is_file($path) ? '../assets/img/' . ltrim($avatar, '/') : '';
}

function player_profile_status_key(string $status): string
{
  $status = strtolower($status);
  if (str_contains($status, 'cancel')) return 'cancelled';
  if (str_contains($status, 'complete')) return 'completed';
  if (str_contains($status, 'approve') || str_contains($status, 'confirm')) return 'approved';
  return 'pending';
}

function player_profile_upload_avatar(array $file, int $userId, string $currentAvatar): string
{
  if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    return $currentAvatar;
  }

  if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    throw new RuntimeException('Unable to upload the selected profile photo.');
  }

  if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
    throw new RuntimeException('Profile photo must be 5MB or smaller.');
  }

  $tmpName = (string) ($file['tmp_name'] ?? '');
  $originalName = strtolower((string) ($file['name'] ?? ''));
  $extension = pathinfo($originalName, PATHINFO_EXTENSION);
  $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
  $allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
  ];

  $mime = '';
  if (class_exists('finfo')) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmpName);
  }

  if (!in_array($extension, $allowedExtensions, true) || ($mime !== '' && !isset($allowedMimes[$mime]))) {
    throw new RuntimeException('Profile photo must be JPG, PNG, or WEBP.');
  }

  $safeExtension = $allowedMimes[$mime] ?? ($extension === 'jpeg' ? 'jpg' : $extension);
  $uploadDir = __DIR__ . '/../assets/img/avatars';
  if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
    throw new RuntimeException('Unable to prepare profile photo storage.');
  }

  $fileName = 'player-' . $userId . '-' . time() . '.' . $safeExtension;
  $destination = $uploadDir . '/' . $fileName;
  if (!move_uploaded_file($tmpName, $destination)) {
    throw new RuntimeException('Unable to save the selected profile photo.');
  }

  return 'img/avatars/' . $fileName;
}

function player_profile_load_user(int $userId, array $fallbackProfile): array
{
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

  if (!$row) {
    return [$_SESSION['user'] ?? [], $fallbackProfile];
  }

  $sessionUser = pickled_session_user($row);
  $loadedProfile = [
    'phone' => (string) $row['phone'],
    'city' => (string) $row['city'],
    'province' => (string) $row['province'],
    'avatar' => (string) $row['avatar'],
  ];
  $_SESSION['user'] = $sessionUser;
  $_SESSION['player_profile'] = $loadedProfile;

  return [$sessionUser, $loadedProfile];
}

if (!empty($_GET['saved'])) {
  $toast = 'Profile updated successfully.';
} elseif (!empty($_GET['password'])) {
  $toast = 'Password updated successfully.';
}

if ($userId > 0) {
  try {
    [$user, $profile] = player_profile_load_user($userId, $profile);
  } catch (Throwable $e) {
    error_log('Profile load failed: ' . $e->getMessage());
    $profile = array_merge($profile, $_SESSION['player_profile'] ?? []);
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $submittedToken = $_POST['csrf_token'] ?? '';
  $action = (string) ($_POST['action'] ?? '');
  if (!pickled_validate_csrf_token($submittedToken)) {
    if ($action === 'change_password') {
      $passwordErrors['form'] = 'Invalid request. Please refresh and try again.';
      $openPasswordModal = true;
    } else {
      $profileErrors['form'] = 'Invalid request. Please refresh and try again.';
      $openProfileModal = true;
    }
  } elseif ($action === 'update_profile') {
    $openProfileModal = true;
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $province = trim((string) ($_POST['province'] ?? ''));
    $city = trim((string) ($_POST['city'] ?? ''));
    $avatar = trim((string) ($profile['avatar'] ?? 'avatars/default.png')) ?: 'avatars/default.png';

    $profile = [
      'phone' => $phone,
      'city' => $city,
      'province' => $province,
      'avatar' => $avatar,
    ];

    if ($name === '') {
      $profileErrors['name'] = 'Please enter your full name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $profileErrors['email'] = 'Please enter a valid email address.';
    }
    if (!preg_match('/^09\d{9}$/', $phone)) {
      $profileErrors['phone'] = 'Please enter a valid Philippine mobile number.';
    }
    if (!isset($provinceCities[$province])) {
      $profileErrors['province'] = 'Please select a valid province.';
    } elseif (!in_array($city, $provinceCities[$province], true)) {
      $profileErrors['city'] = 'Please select a city that belongs to the selected province.';
    }

    if (!$profileErrors && $userId > 0) {
      try {
        $pdo = Database::connection();
        $emailStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
        $emailStmt->execute(['email' => $email, 'id' => $userId]);
        if ($emailStmt->fetchColumn()) {
          $profileErrors['email'] = 'This email address is already in use.';
        }
      } catch (Throwable $e) {
        error_log('Profile email check failed: ' . $e->getMessage());
        $profileErrors['form'] = 'Unable to validate this email right now.';
      }
    }

    if (!$profileErrors) {
      try {
        $profile['avatar'] = player_profile_upload_avatar($_FILES['avatar'] ?? [], $userId, $avatar);

        $pdo = Database::connection();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
          'UPDATE users
           SET name = :name, email = :email
           WHERE id = :id AND role = :role'
        );
        $stmt->execute([
          'name' => $name,
          'email' => $email,
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
        header('Location: profile.php?saved=1');
        exit;
      } catch (RuntimeException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
          $pdo->rollBack();
        }
        $profileErrors['form'] = $e->getMessage();
      } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
          $pdo->rollBack();
        }
        error_log('Profile update failed: ' . $e->getMessage());
        $profileErrors['form'] = 'Unable to save profile changes right now.';
      }
    }
  } elseif ($action === 'change_password') {
    $openPasswordModal = true;
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (strlen($newPassword) < 8) {
      $passwordErrors['new_password'] = 'Password must be at least 8 characters.';
    }
    if ($newPassword !== $confirmPassword) {
      $passwordErrors['confirm_password'] = 'Passwords must match.';
    }

    if (!$passwordErrors && $userId > 0) {
      try {
        $stmt = Database::connection()->prepare('SELECT password_hash FROM users WHERE id = :id AND role = :role LIMIT 1');
        $stmt->execute(['id' => $userId, 'role' => 'player']);
        $passwordHash = (string) $stmt->fetchColumn();

        if ($passwordHash === '' || !password_verify($currentPassword, $passwordHash)) {
          $passwordErrors['current_password'] = 'Current password is incorrect.';
        } else {
          $update = Database::connection()->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id AND role = :role');
          $update->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $userId,
            'role' => 'player',
          ]);
          header('Location: profile.php?password=1');
          exit;
        }
      } catch (Throwable $e) {
        error_log('Password update failed: ' . $e->getMessage());
        $passwordErrors['form'] = 'Unable to update password right now.';
      }
    }
  }
}

if ($userId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
  try {
    [$user, $profile] = player_profile_load_user($userId, $profile);
  } catch (Throwable $e) {
    error_log('Profile reload failed: ' . $e->getMessage());
  }
}

$name = trim((string) ($user['name'] ?? ''));
$email = trim((string) ($user['email'] ?? ''));
$phone = trim((string) ($profile['phone'] ?? ''));
$city = trim((string) ($profile['city'] ?? ''));
$province = trim((string) ($profile['province'] ?? ''));
$initial = strtoupper(substr($name !== '' ? $name : $email, 0, 1));
$avatarSrc = player_profile_avatar_src((string) ($profile['avatar'] ?? ''));
$bookingRows = [];

try {
  if ($userId > 0) {
    $stmt = Database::connection()->prepare(
      "SELECT b.id,
              b.reference,
              b.status AS booking_status,
              b.payment_status,
              b.total,
              COALESCE(bi.name, 'Booking') AS program,
              COALESCE(bi.court, 'Any Court') AS court,
              COALESCE(DATE_FORMAT(bi.booking_date, '%W, %M %e, %Y'), 'To be scheduled') AS session_date,
              COALESCE(CONCAT(TIME_FORMAT(bi.start_time, '%h:%i %p'), ' - ', TIME_FORMAT(bi.end_time, '%h:%i %p')), 'TBD') AS session_time
       FROM bookings b
       LEFT JOIN booking_items bi ON bi.id = (
         SELECT MIN(first_item.id)
         FROM booking_items first_item
         WHERE first_item.booking_id = b.id
       )
       WHERE b.user_id = :user_id
       ORDER BY b.created_at DESC
       LIMIT 8"
    );
    $stmt->execute(['user_id' => $userId]);
    foreach ($stmt->fetchAll() ?: [] as $booking) {
      $bookingRows[] = [
        'id' => (int) $booking['id'],
        'reference' => (string) ($booking['reference'] ?? ''),
        'program' => (string) ($booking['program'] ?? 'Booking'),
        'court' => (string) ($booking['court'] ?? 'Any Court'),
        'date' => (string) ($booking['session_date'] ?? 'To be scheduled'),
        'time' => (string) ($booking['session_time'] ?? 'TBD'),
        'booking_status' => (string) ($booking['booking_status'] ?? 'pending'),
        'payment_status' => (string) ($booking['payment_status'] ?? 'pending'),
        'total' => (float) ($booking['total'] ?? 0),
      ];
    }
  }
} catch (Throwable $e) {
  error_log('Profile booking history failed: ' . $e->getMessage());
}

$extraHead = '<link rel="stylesheet" href="../assets/css/player-profile.css?v=20260616a"/>';

include __DIR__ . '/../includes/header.php';
?>

<main class="player-profile-page">
  <?php if ($toast): ?>
    <div class="player-profile-toast" role="status" aria-live="polite"><?= htmlspecialchars($toast) ?></div>
  <?php endif; ?>

  <div class="player-profile-shell">
    <aside class="player-profile-nav" aria-label="Account navigation">
      <a class="player-profile-nav__item active" href="profile.php">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M5 20a7 7 0 0 1 14 0"></path></svg>
        My Profile
      </a>
      <a class="player-profile-nav__item" href="booking.php">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"></rect><path d="M8 3v4M16 3v4M4 10h16"></path></svg>
        Booking History
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
      <article class="player-profile-card" id="profile-info">
        <div class="player-profile-card__header">
          <h1>Profile Information</h1>
          <button class="player-profile-edit" type="button" data-open-dialog="editProfileDialog">Edit</button>
        </div>

        <div class="player-profile-info">
          <div class="player-profile-identity">
            <div class="player-profile-avatar" aria-hidden="true">
              <?php if ($avatarSrc): ?>
                <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="" />
              <?php else: ?>
                <?= htmlspecialchars($initial ?: 'P') ?>
              <?php endif; ?>
            </div>
            <button class="player-profile-photo" type="button" data-open-dialog="editProfileDialog">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.5 4h-5L8 6H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-3l-1.5-2Z"></path><circle cx="12" cy="13" r="3"></circle></svg>
              Change Photo
            </button>
          </div>

          <div class="player-profile-fields">
            <div>
              <span>Full Name</span>
              <strong><?= htmlspecialchars($name ?: 'Not set') ?></strong>
            </div>
            <div>
              <span>Email Address</span>
              <strong><?= htmlspecialchars($email ?: 'Not set') ?></strong>
            </div>
            <div>
              <span>Province</span>
              <strong><?= htmlspecialchars($province ?: 'Not set') ?></strong>
            </div>
            <div>
              <span>City</span>
              <strong><?= htmlspecialchars($city ?: 'Not set') ?></strong>
            </div>
            <div>
              <span>Phone Number</span>
              <strong><?= htmlspecialchars($phone ?: 'Not set') ?></strong>
            </div>
          </div>
        </div>
      </article>

      <article class="player-profile-card player-profile-card--password">
        <div class="player-profile-card__header">
          <h2>Password</h2>
        </div>
        <div class="player-profile-password">
          <div>
            <span>Password</span>
            <strong>********</strong>
            <small>Use a minimum of 8 characters.</small>
          </div>
          <button class="player-profile-outline" type="button" data-open-dialog="changePasswordDialog">Change Password</button>
        </div>
      </article>

      <article class="player-profile-card player-profile-card--history" id="booking-history">
        <div class="player-profile-card__header">
          <div>
            <h2>Booking History</h2>
            <p>Payments are tracked inside each booking.</p>
          </div>
          <a class="player-profile-edit" href="booking.php">Open Status Page</a>
        </div>

        <?php if ($bookingRows): ?>
          <div class="player-profile-table-wrap">
            <table class="player-profile-bookings">
              <thead>
                <tr>
                  <th>Booking Reference</th>
                  <th>Program</th>
                  <th>Court</th>
                  <th>Session Date</th>
                  <th>Time</th>
                  <th>Booking Status</th>
                  <th>Payment Status</th>
                  <th>Total Amount</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($bookingRows as $row): ?>
                  <?php $statusKey = player_profile_status_key($row['booking_status']); ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($row['reference']) ?></strong></td>
                    <td><?= htmlspecialchars($row['program']) ?></td>
                    <td><?= htmlspecialchars($row['court']) ?></td>
                    <td><?= htmlspecialchars($row['date']) ?></td>
                    <td><?= htmlspecialchars($row['time']) ?></td>
                    <td><span class="player-profile-badge player-profile-badge--<?= htmlspecialchars($statusKey) ?>"><?= htmlspecialchars(ucfirst($row['booking_status'])) ?></span></td>
                    <td><span class="player-profile-badge player-profile-badge--payment"><?= htmlspecialchars(ucfirst($row['payment_status'])) ?></span></td>
                    <td>₱<?= number_format($row['total'], 2) ?></td>
                    <td><a class="player-profile-table-link" href="booking-details.php?id=<?= (int) $row['id'] ?>">View</a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="player-profile-empty">
            <p>No booking history yet.</p>
            <a class="player-profile-outline" href="courts.php#court-detail">Browse Courts</a>
          </div>
        <?php endif; ?>
      </article>
    </section>
  </div>
</main>

<dialog class="player-profile-modal" id="editProfileDialog" aria-labelledby="edit-profile-title">
  <form class="player-profile-modal__dialog" method="post" enctype="multipart/form-data" data-profile-form>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />
    <input type="hidden" name="action" value="update_profile" />
    <div class="player-profile-modal__header">
      <h2 id="edit-profile-title">Edit Profile</h2>
      <button type="button" data-close-dialog aria-label="Close edit profile modal">&times;</button>
    </div>

    <?php if (!empty($profileErrors['form'])): ?>
      <div class="player-profile-form-alert"><?= htmlspecialchars($profileErrors['form']) ?></div>
    <?php endif; ?>

    <div class="player-profile-photo-preview">
      <div class="player-profile-avatar player-profile-avatar--small" data-avatar-preview>
        <?php if ($avatarSrc): ?>
          <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="" />
        <?php else: ?>
          <?= htmlspecialchars($initial ?: 'P') ?>
        <?php endif; ?>
      </div>
      <label class="player-profile-file">
        <span>Profile Photo</span>
        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" data-avatar-input />
        <small>JPG, PNG, or WEBP up to 5MB.</small>
        <em data-error-for="avatar"></em>
      </label>
    </div>

    <div class="player-profile-modal__grid">
      <label>
        <span>Full Name</span>
        <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required />
        <em><?= htmlspecialchars($profileErrors['name'] ?? '') ?></em>
      </label>
      <label>
        <span>Email Address</span>
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required />
        <em data-error-for="email"><?= htmlspecialchars($profileErrors['email'] ?? '') ?></em>
      </label>
      <label>
        <span>Province</span>
        <select name="province" data-province-select required>
          <option value="">Select province</option>
          <?php foreach (array_keys($provinceCities) as $provinceOption): ?>
            <option value="<?= htmlspecialchars($provinceOption) ?>"<?= $province === $provinceOption ? ' selected' : '' ?>><?= htmlspecialchars($provinceOption) ?></option>
          <?php endforeach; ?>
        </select>
        <em><?= htmlspecialchars($profileErrors['province'] ?? '') ?></em>
      </label>
      <label>
        <span>City</span>
        <select name="city" data-city-select data-selected-city="<?= htmlspecialchars($city) ?>" required>
          <option value="">Select city</option>
        </select>
        <em><?= htmlspecialchars($profileErrors['city'] ?? '') ?></em>
      </label>
      <label>
        <span>Phone Number</span>
        <input type="tel" name="phone" value="<?= htmlspecialchars($phone) ?>" inputmode="numeric" maxlength="11" pattern="09[0-9]{9}" required data-phone-input />
        <em data-error-for="phone"><?= htmlspecialchars($profileErrors['phone'] ?? '') ?></em>
      </label>
    </div>
    <div class="player-profile-modal__actions">
      <button class="player-profile-modal__cancel" type="button" data-close-dialog>Cancel</button>
      <button type="submit">Save Changes</button>
    </div>
  </form>
</dialog>

<dialog class="player-profile-modal" id="changePasswordDialog" aria-labelledby="change-password-title">
  <form class="player-profile-modal__dialog player-profile-modal__dialog--narrow" method="post" data-password-form>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />
    <input type="hidden" name="action" value="change_password" />
    <div class="player-profile-modal__header">
      <h2 id="change-password-title">Change Password</h2>
      <button type="button" data-close-dialog aria-label="Close change password modal">&times;</button>
    </div>

    <?php if (!empty($passwordErrors['form'])): ?>
      <div class="player-profile-form-alert"><?= htmlspecialchars($passwordErrors['form']) ?></div>
    <?php endif; ?>

    <div class="player-profile-modal__grid player-profile-modal__grid--single">
      <label>
        <span>Current Password</span>
        <input type="password" name="current_password" required />
        <em><?= htmlspecialchars($passwordErrors['current_password'] ?? '') ?></em>
      </label>
      <label>
        <span>New Password</span>
        <input type="password" name="new_password" minlength="8" required />
        <em data-error-for="new_password"><?= htmlspecialchars($passwordErrors['new_password'] ?? '') ?></em>
      </label>
      <label>
        <span>Confirm Password</span>
        <input type="password" name="confirm_password" minlength="8" required />
        <em data-error-for="confirm_password"><?= htmlspecialchars($passwordErrors['confirm_password'] ?? '') ?></em>
      </label>
    </div>
    <div class="player-profile-modal__actions">
      <button class="player-profile-modal__cancel" type="button" data-close-dialog>Cancel</button>
      <button type="submit">Update Password</button>
    </div>
  </form>
</dialog>

<script>
(function(){
  var provinceCities = <?= json_encode($provinceCities, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

  document.querySelectorAll('[data-open-dialog]').forEach(function(button) {
    button.addEventListener('click', function() {
      var dialog = document.getElementById(button.getAttribute('data-open-dialog'));
      if (dialog && typeof dialog.showModal === 'function') dialog.showModal();
    });
  });

  document.querySelectorAll('.player-profile-modal').forEach(function(dialog) {
    dialog.addEventListener('click', function(event) {
      if (event.target === dialog) dialog.close();
    });
    dialog.querySelectorAll('[data-close-dialog]').forEach(function(button) {
      button.addEventListener('click', function() { dialog.close(); });
    });
  });

  var profileDialog = document.getElementById('editProfileDialog');
  if (profileDialog && <?= $openProfileModal ? 'true' : 'false' ?> && typeof profileDialog.showModal === 'function') {
    profileDialog.showModal();
  }

  var passwordDialog = document.getElementById('changePasswordDialog');
  if (passwordDialog && <?= $openPasswordModal ? 'true' : 'false' ?> && typeof passwordDialog.showModal === 'function') {
    passwordDialog.showModal();
  }

  var provinceSelect = document.querySelector('[data-province-select]');
  var citySelect = document.querySelector('[data-city-select]');
  function renderCities(resetCity) {
    if (!provinceSelect || !citySelect) return;
    var selectedCity = resetCity ? '' : citySelect.getAttribute('data-selected-city');
    var cities = provinceCities[provinceSelect.value] || [];
    citySelect.innerHTML = '<option value="">Select city</option>';
    cities.forEach(function(city) {
      var option = document.createElement('option');
      option.value = city;
      option.textContent = city;
      if (city === selectedCity) option.selected = true;
      citySelect.appendChild(option);
    });
    if (resetCity) citySelect.setAttribute('data-selected-city', '');
  }
  renderCities(false);
  if (provinceSelect) provinceSelect.addEventListener('change', function() { renderCities(true); });

  function setError(form, key, message) {
    var node = form.querySelector('[data-error-for="' + key + '"]');
    if (node) node.textContent = message || '';
  }

  var phoneInput = document.querySelector('[data-phone-input]');
  if (phoneInput) {
    phoneInput.addEventListener('input', function() {
      phoneInput.value = phoneInput.value.replace(/\D/g, '').slice(0, 11);
    });
  }

  var avatarInput = document.querySelector('[data-avatar-input]');
  var avatarPreview = document.querySelector('[data-avatar-preview]');
  if (avatarInput && avatarPreview) {
    avatarInput.addEventListener('change', function() {
      var file = avatarInput.files && avatarInput.files[0];
      var form = avatarInput.closest('form');
      setError(form, 'avatar', '');
      if (!file) return;
      if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
        setError(form, 'avatar', 'Use JPG, PNG, or WEBP up to 5MB.');
        avatarInput.value = '';
        return;
      }
      var reader = new FileReader();
      reader.onload = function(event) {
        avatarPreview.innerHTML = '';
        var image = document.createElement('img');
        image.src = event.target.result;
        image.alt = '';
        avatarPreview.appendChild(image);
      };
      reader.readAsDataURL(file);
    });
  }

  var profileForm = document.querySelector('[data-profile-form]');
  if (profileForm) {
    profileForm.addEventListener('submit', function(event) {
      var valid = true;
      var email = profileForm.elements.email.value.trim();
      var phone = profileForm.elements.phone.value.trim();
      setError(profileForm, 'email', '');
      setError(profileForm, 'phone', '');
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        setError(profileForm, 'email', 'Please enter a valid email address.');
        valid = false;
      }
      if (!/^09\d{9}$/.test(phone)) {
        setError(profileForm, 'phone', 'Please enter a valid Philippine mobile number.');
        valid = false;
      }
      if (!valid) event.preventDefault();
    });
  }

  var passwordForm = document.querySelector('[data-password-form]');
  if (passwordForm) {
    passwordForm.addEventListener('submit', function(event) {
      var valid = true;
      var next = passwordForm.elements.new_password.value;
      var confirm = passwordForm.elements.confirm_password.value;
      setError(passwordForm, 'new_password', '');
      setError(passwordForm, 'confirm_password', '');
      if (next.length < 8) {
        setError(passwordForm, 'new_password', 'Password must be at least 8 characters.');
        valid = false;
      }
      if (next !== confirm) {
        setError(passwordForm, 'confirm_password', 'Passwords must match.');
        valid = false;
      }
      if (!valid) event.preventDefault();
    });
  }
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
