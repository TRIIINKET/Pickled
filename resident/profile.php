<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../includes/booking-system.php';
require_once __DIR__ . '/../includes/avatar-helper.php';
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../includes/EmailVerification.php';

pickled_start_secure_session();
pickled_init_csrf();
pickled_require_login('resident/profile.php');

$pageTitle = 'My Profile - Pickled';
$activePage = 'profile.php';
$basePath = '../';
$csrfToken = pickled_csrf_token();
$user = $_SESSION['user'] ?? [];
$userId = (int) ($user['id'] ?? 0);
$defaultAvatar = pickled_avatar_default_path();
$profile = [
  'phone' => '',
  'city' => '',
  'province' => '',
  'avatar' => $defaultAvatar,
];
$message = '';
$messageType = 'success';
$fieldErrors = [];
$isVerified = false;

$provinceCities = [
  'Metro Manila' => ['Caloocan', 'Las Pinas', 'Makati', 'Malabon', 'Mandaluyong', 'Manila', 'Marikina', 'Muntinlupa', 'Navotas', 'Paranaque', 'Pasay', 'Pasig', 'Quezon City', 'San Juan', 'Taguig', 'Valenzuela'],
  'Batangas' => ['Batangas City', 'Lipa', 'Santo Tomas', 'Tanauan', 'Bauan', 'Calaca', 'Nasugbu'],
  'Bulacan' => ['Malolos', 'Meycauayan', 'San Jose del Monte', 'Baliuag', 'Marilao', 'Santa Maria'],
  'Cavite' => ['Bacoor', 'Cavite City', 'Dasmarinas', 'General Trias', 'Imus', 'Tagaytay', 'Trece Martires'],
  'Cebu' => ['Cebu City', 'Lapu-Lapu', 'Mandaue', 'Talisay', 'Toledo', 'Danao'],
  'Davao del Sur' => ['Davao City', 'Digos', 'Bansalan', 'Hagonoy', 'Santa Cruz'],
  'Iloilo' => ['Iloilo City', 'Passi', 'Oton', 'Pavia', 'Santa Barbara'],
  'Laguna' => ['Calamba', 'Los Banos', 'Santa Rosa', 'San Pablo', 'Binan', 'Cabuyao', 'San Pedro'],
  'Pampanga' => ['Angeles', 'San Fernando', 'Mabalacat', 'Apalit', 'Guagua'],
  'Rizal' => ['Antipolo', 'Binangonan', 'Cainta', 'Rodriguez', 'San Mateo', 'Taytay'],
];

function pickled_profile_valid_location(array $provinceCities, string $province, string $city): bool {
  return isset($provinceCities[$province]) && in_array($city, $provinceCities[$province], true);
}

if ($userId > 0) {
  try {
    $stmt = Database::connection()->prepare(
      'SELECT u.id, u.name, u.email, u.role, COALESCE(u.is_verified, 0) AS is_verified,
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
      $isVerified = (int) ($row['is_verified'] ?? 0) === 1;
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
  $action = (string) ($_POST['action'] ?? '');

  if (!pickled_validate_csrf_token($submittedToken)) {
    $message = 'Invalid request. Please refresh and try again.';
    $messageType = 'error';
  } elseif ($action === 'resend_verification') {
    if ($isVerified) {
      $message = 'Your email is already verified.';
    } elseif (EmailVerification::issue($user)) {
      header('Location: ' . pickled_frontend_url('auth/verify-otp.php'));
      exit;
    } else {
      $message = 'Unable to send verification email right now. Please try again later.';
      $messageType = 'error';
    }
  } elseif ($action === 'update_profile') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $province = trim((string) ($_POST['province'] ?? ''));
    $city = trim((string) ($_POST['city'] ?? ''));
    $avatar = trim((string) ($profile['avatar'] ?? $defaultAvatar));
    $avatar = $avatar !== '' ? $avatar : $defaultAvatar;
    $profile = [
      'phone' => $phone,
      'city' => $city,
      'province' => $province,
      'avatar' => $avatar,
    ];

    if ($userId <= 0) {
      $fieldErrors['profile'] = 'Please log in again before updating your profile.';
    }

    try {
      $name = validateName($name);
    } catch (RuntimeException $e) {
      $fieldErrors['name'] = $e->getMessage();
    }

    try {
      $phone = validatePhonePH($phone);
      $profile['phone'] = $phone;
    } catch (RuntimeException $e) {
      $fieldErrors['phone'] = $e->getMessage();
    }

    if (!pickled_profile_valid_location($provinceCities, $province, $city)) {
      $fieldErrors['location'] = 'Please select a valid city for the selected province.';
    }

    try {
      $newAvatar = pickled_store_avatar_upload($_FILES['avatar'] ?? [], $userId, 'player');
      if ($newAvatar !== null) {
        $profile['avatar'] = $newAvatar;
      }
    } catch (Throwable $e) {
      error_log('Profile avatar upload failed: ' . $e->getMessage());
      $fieldErrors['avatar'] = $e instanceof RuntimeException ? $e->getMessage() : 'Profile photo upload failed. Please try again.';
    }

    if ($fieldErrors) {
      $message = reset($fieldErrors) ?: 'Please check the highlighted fields.';
      $messageType = 'error';
    } else {
      try {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
          'UPDATE users
           SET name = :name
           WHERE id = :id AND role = :role'
        );
        $stmt->execute([
          'name' => $name,
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
        $profileSaved = $stmt->execute([
          'user_id' => $userId,
          'phone' => $profile['phone'],
          'city' => $profile['city'],
          'province' => $profile['province'],
          'avatar' => $profile['avatar'],
        ]);
        error_log('Avatar database update result: resident profile user_id=' . $userId . '; avatar=' . $profile['avatar'] . '; result=' . ($profileSaved ? 'success' : 'failed') . '; row_count=' . $stmt->rowCount());

        $pdo->commit();

        $user['name'] = $name;
        $_SESSION['user'] = $user;
        $_SESSION['player_profile'] = $profile;
        $message = 'Profile changes saved.';
      } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
          $pdo->rollBack();
        }
        error_log('Profile update failed: ' . $e->getMessage());
        $message = 'Unable to save profile changes right now.';
        $messageType = 'error';
      }
    }
  }
}

$name = trim((string) ($user['name'] ?? ''));
$email = trim((string) ($user['email'] ?? ''));
$phone = trim((string) ($profile['phone'] ?? ''));
$city = trim((string) ($profile['city'] ?? ''));
$province = trim((string) ($profile['province'] ?? ''));
$avatar = trim((string) ($profile['avatar'] ?? $defaultAvatar));
$initial = strtoupper(substr($name !== '' ? $name : $email, 0, 1));
$avatarUrl = pickled_avatar_url($avatar);
$displayPhone = $phone !== '' ? formatPhonePH($phone) : 'Not added yet';
$displayLocation = ($city !== '' && $province !== '') ? $city . ', ' . $province : 'Not added yet';

$extraHead = '<link rel="stylesheet" href="../assets/css/player-profile.css?v=20260616a"/>';

include __DIR__ . '/../includes/header.php';
?>

<main class="player-profile-page">
  <div class="player-profile-shell">
    <aside class="player-profile-nav" aria-label="Account navigation">
      <a class="player-profile-nav__item active" href="profile.php">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M5 20a7 7 0 0 1 14 0"></path></svg>
        My Profile
      </a>
      <a class="player-profile-nav__item" href="booking.php">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"></rect><path d="M8 3v4M16 3v4M4 10h16"></path></svg>
        My Bookings
      </a>
      <a class="player-profile-nav__item" href="cart.php">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="20" r="1"></circle><circle cx="17" cy="20" r="1"></circle><path d="M3 4h2l2.4 11.4a2 2 0 0 0 2 1.6h7.4a2 2 0 0 0 2-1.6L20 8H7"></path></svg>
        My Cart
      </a>
      <a class="player-profile-nav__item" href="<?= htmlspecialchars(pickled_frontend_url('auth/change-password.php')) ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="9" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>
        Security
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
        <div class="player-profile-message player-profile-message--<?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <article class="player-profile-card">
        <div class="player-profile-card__header">
          <div>
            <p class="player-profile-eyebrow">Player Account</p>
            <h1>Profile Information</h1>
          </div>
          <a class="player-profile-edit" href="#edit-profile">Edit Profile</a>
        </div>

        <div class="player-profile-info">
          <div class="player-profile-identity">
            <div class="player-profile-avatar">
              <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="" onerror="this.remove(); this.parentElement.dataset.fallback='<?= htmlspecialchars($initial) ?>';" />
            </div>
            <strong><?= htmlspecialchars($name !== '' ? $name : 'Player') ?></strong>
          </div>

          <div class="player-profile-fields">
            <div>
              <span>Full Name</span>
              <strong><?= htmlspecialchars($name !== '' ? $name : 'Not added yet') ?></strong>
            </div>
            <div>
              <span>Phone Number</span>
              <strong><?= htmlspecialchars($displayPhone) ?></strong>
            </div>
            <div class="player-profile-fields__wide">
              <span>Email Address</span>
              <strong><?= htmlspecialchars($email) ?></strong>
              <span class="player-profile-badge <?= $isVerified ? 'player-profile-badge--verified' : 'player-profile-badge--warning' ?>">
                <?= $isVerified ? '✓ Verified' : '⚠ Email Not Verified' ?>
              </span>
              <?php if (!$isVerified): ?>
                <p class="player-profile-help">Please verify your email address to access all features.</p>
                <form class="player-profile-inline-form" method="post">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />
                  <input type="hidden" name="action" value="resend_verification" />
                  <button type="submit">Resend Verification Email</button>
                </form>
              <?php endif; ?>
            </div>
            <div>
              <span>City and Province</span>
              <strong><?= htmlspecialchars($displayLocation) ?></strong>
            </div>
          </div>
        </div>
      </article>

      <article class="player-profile-card player-profile-card--security">
        <div class="player-profile-card__header">
          <div>
            <p class="player-profile-eyebrow">Security</p>
            <h2>Password Management</h2>
          </div>
          <a class="player-profile-edit" href="<?= htmlspecialchars(pickled_frontend_url('auth/change-password.php')) ?>">Manage Security Settings</a>
        </div>
        <p class="player-profile-security-copy">For security purposes, password management is handled in Settings.</p>
      </article>
    </section>
  </div>
</main>

<section class="player-profile-modal" id="edit-profile" aria-labelledby="edit-profile-title">
  <a class="player-profile-modal__backdrop" href="#" aria-label="Close edit profile modal"></a>
  <form class="player-profile-modal__dialog" method="post" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />
    <input type="hidden" name="action" value="update_profile" />
    <div class="player-profile-modal__header">
      <div>
        <p class="player-profile-eyebrow">Edit Mode</p>
        <h2 id="edit-profile-title">Profile Information</h2>
      </div>
      <a href="#" aria-label="Close edit profile modal">&times;</a>
    </div>

    <div class="player-profile-photo-editor">
      <div class="player-profile-avatar player-profile-avatar--edit">
        <img id="avatarPreview" src="<?= htmlspecialchars($avatarUrl) ?>" alt="" />
      </div>
      <label class="player-profile-photo-input">
        <span>Profile Picture</span>
        <input id="avatarInput" type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" />
        <small>JPG, JPEG, PNG, or WEBP. Max 2MB.</small>
        <?php if (isset($fieldErrors['avatar'])): ?><em><?= htmlspecialchars($fieldErrors['avatar']) ?></em><?php endif; ?>
      </label>
    </div>

    <div class="player-profile-modal__grid">
      <label>
        <span>Full Name</span>
        <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" minlength="2" maxlength="80" pattern="[A-Za-z][A-Za-z .'\-]*" title="Please enter a valid name." required />
        <?php if (isset($fieldErrors['name'])): ?><em><?= htmlspecialchars($fieldErrors['name']) ?></em><?php endif; ?>
      </label>
      <label>
        <span>Email Address</span>
        <input type="email" value="<?= htmlspecialchars($email) ?>" readonly aria-readonly="true" />
        <small>Email cannot be edited here.</small>
      </label>
      <label>
        <span>Province</span>
        <select id="provinceSelect" name="province" required>
          <option value="">Select province first</option>
          <?php foreach ($provinceCities as $provinceName => $cities): ?>
            <option value="<?= htmlspecialchars($provinceName) ?>"<?= $province === $provinceName ? ' selected' : '' ?>><?= htmlspecialchars($provinceName) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>
        <span>City</span>
        <select id="citySelect" name="city" data-current-city="<?= htmlspecialchars($city) ?>" required>
          <option value="">Select city</option>
        </select>
        <?php if (isset($fieldErrors['location'])): ?><em><?= htmlspecialchars($fieldErrors['location']) ?></em><?php endif; ?>
      </label>
      <label class="player-profile-modal__wide">
        <span>Phone Number</span>
        <input id="phoneInput" type="tel" name="phone" value="<?= htmlspecialchars($phone) ?>" inputmode="tel" maxlength="13" pattern="(9[0-9]{9}|09[0-9]{9}|\+639[0-9]{9}|639[0-9]{9})" placeholder="09123456789" required />
        <small>Use 9XXXXXXXXX, 09XXXXXXXXX, or +639XXXXXXXXX.</small>
        <?php if (isset($fieldErrors['phone'])): ?><em><?= htmlspecialchars($fieldErrors['phone']) ?></em><?php endif; ?>
      </label>
    </div>
    <div class="player-profile-modal__actions">
      <a class="player-profile-modal__cancel" href="#">Cancel</a>
      <button type="submit">Save Changes</button>
    </div>
  </form>
</section>

<script>
  window.pickledProvinceCities = <?= json_encode($provinceCities, JSON_UNESCAPED_SLASHES) ?>;

  const provinceSelect = document.getElementById('provinceSelect');
  const citySelect = document.getElementById('citySelect');
  const phoneInput = document.getElementById('phoneInput');
  const avatarInput = document.getElementById('avatarInput');
  const avatarPreview = document.getElementById('avatarPreview');

  function syncCities() {
    if (!provinceSelect || !citySelect) return;
    const selectedProvince = provinceSelect.value;
    const currentCity = citySelect.dataset.currentCity || '';
    const cities = window.pickledProvinceCities[selectedProvince] || [];
    citySelect.innerHTML = '<option value="">Select city</option>';
    cities.forEach((city) => {
      const option = document.createElement('option');
      option.value = city;
      option.textContent = city;
      option.selected = city === currentCity;
      citySelect.appendChild(option);
    });
    if (!cities.includes(currentCity)) {
      citySelect.value = '';
    }
  }

  provinceSelect?.addEventListener('change', () => {
    citySelect.dataset.currentCity = '';
    syncCities();
  });
  syncCities();

  phoneInput?.addEventListener('input', () => {
    phoneInput.value = phoneInput.value.replace(/[^\d+]/g, '').replace(/(?!^)\+/g, '').slice(0, 13);
    phoneInput.setCustomValidity(/^(9\d{9}|09\d{9}|\+639\d{9}|639\d{9})$/.test(phoneInput.value) ? '' : 'Please enter a valid Philippine mobile number.');
  });

  avatarInput?.addEventListener('change', () => {
    const file = avatarInput.files?.[0];
    if (!file || !avatarPreview) return;
    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowed.includes(file.type) || file.size > 2 * 1024 * 1024) {
      avatarInput.value = '';
      alert('Profile photo must be JPG, JPEG, PNG, or WEBP and 2MB or smaller.');
      return;
    }
    avatarPreview.src = URL.createObjectURL(file);
  });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
