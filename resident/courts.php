<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/schedule-time.php';
require_once __DIR__ . '/../app/services/CatalogService.php';
require_once __DIR__ . '/../app/services/SchedulingService.php';
pickled_init_csrf();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$pageTitle  = 'Courts - Pickled';
$activePage = 'courts.php';
$basePath   = '../';
$extraHead  = '<link rel="stylesheet" href="../assets/css/courts.css?v=20260615b"/>';

function pickled_catalog_note(array $variant): string
{
  $category = trim((string) ($variant['category'] ?? 'Service'));
  $duration = trim((string) ($variant['duration_label'] ?? ''));
  $limit = (int) ($variant['participants_limit'] ?? 0);
  $note = trim($category . ($duration !== '' ? ' - ' . $duration : ''));
  if ($limit > 1) {
    $note .= ' for up to ' . $limit . ' players';
  }
  return $note !== '' ? $note : 'Available booking service.';
}

function pickled_catalog_option(array $variant): array
{
  $name = (string) ($variant['name'] ?? 'Booking Service');
  $price = (float) ($variant['price'] ?? 0);
  $slug = (string) ($variant['slug'] ?? '');
  $participantsLimit = max(1, (int) ($variant['participants_limit'] ?? 1));
  $capacity = max(1, (int) ($variant['capacity'] ?? $participantsLimit));
  $maxPlayers = max(1, min($participantsLimit, $capacity));
  $lower = strtolower($slug . ' ' . $name);
  $option = [
    'variant' => $slug,
    'label' => strtoupper($name),
    'price' => $price,
    'duration' => (string) ($variant['duration_label'] ?? '1 hour'),
    'court' => strtoupper((string) ($variant['court'] ?? 'Court')),
    'title' => strtoupper($name) . ' ₱' . number_format($price, 0),
    'note' => pickled_catalog_note($variant),
    'participantsLimit' => $participantsLimit,
    'capacity' => $capacity,
    'maxPlayers' => $maxPlayers,
  ];
  if (str_contains($lower, 'coach') || str_contains($lower, 'private') || str_contains($lower, 'lesson') || str_contains($lower, 'training') || str_contains($lower, 'class') || str_contains($lower, 'kids') || str_contains($lower, 'youth') || str_contains($lower, 'parent')) {
    $option['dateMode'] = 'coach';
  }
  return $option;
}

function pickled_booking_rate_attrs(array $rate, bool $bookingButton = false): string
{
  $attrs = [
    ($bookingButton ? 'data-booking-variant' : 'data-variant') => $rate['variant'] ?? '',
    ($bookingButton ? 'data-booking-label' : 'data-label') => $rate['label'] ?? '',
    ($bookingButton ? 'data-booking-note' : 'data-note') => $rate['note'] ?? '',
    ($bookingButton ? 'data-booking-price' : 'data-price') => $rate['price'] ?? 0,
    ($bookingButton ? 'data-booking-duration' : 'data-duration') => $rate['duration'] ?? '',
    ($bookingButton ? 'data-booking-court' : 'data-court') => $rate['court'] ?? '',
    'data-participants-limit' => $rate['participantsLimit'] ?? 1,
    'data-capacity' => $rate['capacity'] ?? 1,
    'data-max-players' => $rate['maxPlayers'] ?? 1,
  ];
  if (!empty($rate['dateMode'])) {
    $attrs['data-date-mode'] = $rate['dateMode'];
  }

  $html = [];
  foreach ($attrs as $name => $value) {
    $html[] = $name . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
  }
  return implode(' ', $html);
}

function pickled_court_image_path(string $path): string
{
  $path = str_replace('\\', '/', trim($path));
  $path = preg_replace('#^(\.\./)?assets/#', '', $path) ?? $path;
  return '../assets/' . ltrim($path, '/');
}

function pickled_court_media(int $courtId): array
{
  if ($courtId <= 0) {
    return [];
  }

  try {
    $stmt = Database::connection()->prepare("SELECT * FROM court_media WHERE court_id = :court_id AND status = 'active' ORDER BY is_hero DESC, sort_order ASC, id ASC");
    $stmt->execute(['court_id' => $courtId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } catch (Throwable $e) {
    error_log('Public court media load failed: ' . $e->getMessage());
    return [];
  }
}

function pickled_public_time_range(string $start, string $end): string
{
  return (new DateTimeImmutable('1970-01-01 ' . $start))->format('h:i A')
    . ' - '
    . (new DateTimeImmutable('1970-01-01 ' . $end))->format('h:i A');
}

function pickled_public_coach_schedule(array $availability): array
{
  $dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  $days = [];
  $displaySlots = [];
  $bookableSlots = [];
  foreach ($availability as $row) {
    if (($row['status'] ?? '') !== 'available') {
      continue;
    }
    $days[] = (string) $row['day_of_week'];
    $start = (string) $row['start_time'];
    $end = (string) $row['end_time'];
    $displaySlots[] = pickled_public_time_range($start, $end);

    $cursor = new DateTimeImmutable('1970-01-01 ' . $start);
    $endAt = new DateTimeImmutable('1970-01-01 ' . $end);
    while ($cursor < $endAt) {
      $next = $cursor->modify('+1 hour');
      if ($next > $endAt) {
        break;
      }
      $bookableSlots[] = $cursor->format('h:i A') . ' - ' . $next->format('h:i A');
      $cursor = $next;
    }
  }
  $days = array_values(array_unique($days));
  $displaySlots = array_values(array_unique($displaySlots));
  $bookableSlots = array_values(array_unique($bookableSlots));
  $dayText = $days ? implode(', ', array_map(static fn(string $day): string => $dayLabels[(int) $day] ?? 'Day', $days)) : 'No availability';
  $slotText = $displaySlots ? implode(', ', $displaySlots) : 'No active slots';
  return [$dayText . ' · ' . $slotText, implode(',', $days), implode('|', $bookableSlots)];
}

$catalogService = new CatalogService();
$schedulingService = new SchedulingService();
$catalogCourts = [];
$courtRateCatalog = [];
$coachRows = [];

try {
  $catalogCourts = $catalogService->courts(false);
  $hiddenCourtCatalogSlugs = array_flip([
    'green-open-match-play',
    'green-weekly-tournament',
    'pink-foundational-ages-6-10',
    'pink-youth-development-ages-11-17',
    'pink-adult-beginner-bootcamp',
    'pink-introductory-trial-class',
    'pink-parent-child-trial',
  ]);
  foreach ($catalogCourts as $catalogCourt) {
    $courtSlug = (string) $catalogCourt['slug'];
    $courtVariants = $catalogService->variantsForCourtSlug($courtSlug, false);
    $courtVariants = array_values(array_filter($courtVariants, static fn(array $variant): bool => !isset($hiddenCourtCatalogSlugs[(string) ($variant['slug'] ?? '')])));
    $courtRateCatalog[$courtSlug] = array_map('pickled_catalog_option', $courtVariants);
  }
  $coachRows = $schedulingService->coaches();
} catch (Throwable $e) {
  error_log('Court catalog load failed: ' . $e->getMessage());
}

$courtAssetDefaults = [
  'green' => [
    'title' => 'COURT GREEN',
    'tag' => "EVERYONE'S GAME",
    'image' => '../assets/img/court/court green-1.png',
    'thumbs' => [
      '../assets/img/court/court green-1.png',
      '../assets/img/court/court green-2.png',
      '../assets/img/court/court green-3.png',
    ],
  ],
  'pink' => [
    'title' => 'COURT PINK',
    'tag' => 'VIBE ON',
    'image' => '../assets/img/court/court pink-1.webp',
    'thumbs' => [
      '../assets/img/court/court pink-1.webp',
      '../assets/img/court/court pink-2.png',
      '../assets/img/court/court pink-3.png',
    ],
  ],
];

$courtImages = [];
foreach ($catalogCourts as $catalogCourt) {
  $slug = (string) $catalogCourt['slug'];
  $assets = $courtAssetDefaults[$slug] ?? $courtAssetDefaults['green'];
  $mediaRows = pickled_court_media((int) ($catalogCourt['id'] ?? 0));
  if ($mediaRows) {
    $assets['image'] = pickled_court_image_path((string) $mediaRows[0]['image_path']);
    $assets['thumbs'] = array_map(static fn(array $row): string => pickled_court_image_path((string) $row['image_path']), $mediaRows);
  }
  $assets['title'] = strtoupper((string) $catalogCourt['name']);
  $assets['tag'] = $assets['tag'] ?? 'BOOK NOW';
  $assets['description'] = trim((string) ($catalogCourt['description'] ?? '')) ?: ($slug === 'pink' ? 'Youth-friendly indoor court' : 'Main standard indoor court');
  $assets['capacity'] = (int) ($catalogCourt['capacity'] ?? 0);
  $assets['operating_hours'] = trim((string) ($catalogCourt['operating_hours'] ?? '')) ?: '8AM - 10PM';
  $assets['court_type'] = trim((string) ($catalogCourt['court_type'] ?? '')) ?: 'Indoor';
  $courtImages[$slug] = $assets;
}

$defaultCourtKey = isset($courtImages['green']) ? 'green' : (array_key_first($courtImages) ?: 'green');
$defaultCourtAssets = $courtImages[$defaultCourtKey] ?? $courtAssetDefaults['green'];
$defaultRate = $courtRateCatalog[$defaultCourtKey][0] ?? [
  'variant' => '',
  'label' => 'COURT BOOKING',
  'price' => 0,
  'duration' => '1 hour',
  'court' => $defaultCourtAssets['title'],
  'title' => 'COURT BOOKING',
  'note' => 'No active booking services are available yet.',
  'participantsLimit' => max(1, (int) ($defaultCourtAssets['capacity'] ?? 1)),
  'capacity' => max(1, (int) ($defaultCourtAssets['capacity'] ?? 1)),
  'maxPlayers' => max(1, (int) ($defaultCourtAssets['capacity'] ?? 1)),
];
$privateCoachRate = $defaultRate;
$kidsClassRate = $defaultRate;
foreach ($courtRateCatalog as $rates) {
  foreach ($rates as $rate) {
    $search = strtolower(($rate['variant'] ?? '') . ' ' . ($rate['label'] ?? ''));
    if (str_contains($search, 'coach') || str_contains($search, 'private')) {
      $privateCoachRate = $rate;
      break 2;
    }
  }
}
foreach ($courtRateCatalog as $rates) {
  foreach ($rates as $rate) {
    $search = strtolower(($rate['variant'] ?? '') . ' ' . ($rate['label'] ?? ''));
    if (str_contains($search, 'kids') || str_contains($search, 'youth') || str_contains($search, 'class')) {
      $kidsClassRate = $rate;
      break 2;
    }
  }
}
$courtGalleryCatalog = [];
foreach ($courtImages as $key => $courtImage) {
  $courtGalleryCatalog[$key] = [
    'title' => $courtImage['title'],
    'image' => $courtImage['image'],
    'alt' => $courtImage['title'] . ' main view',
    'thumbAlt' => $courtImage['title'] . ' view',
    'thumbs' => $courtImage['thumbs'],
    'description' => $courtImage['description'] ?? '',
    'capacity' => $courtImage['capacity'] ?? 0,
    'operatingHours' => $courtImage['operating_hours'] ?? '8AM - 10PM',
    'courtType' => $courtImage['court_type'] ?? 'Indoor',
  ];
}

include __DIR__ . '/../includes/header.php';

$cartErrorMessages = [
  'invalid' => 'Please choose a valid available date and time.',
  'capacity' => 'This schedule is already full. Please choose another time.',
  'conflict' => 'That court is already booked for the selected date and time.',
  'coach_unavailable' => 'No coach is available for the selected date and time.',
  'duplicate' => 'That booking is already in your cart.',
  'limit' => 'Cart limit reached. Please complete checkout before adding more reservations.',
  'expired_schedule' => 'This schedule is no longer available. Please select a future time slot.',
  'login' => 'Please log in before booking.',
];
$cartError = $cartErrorMessages[(string) ($_GET['cart_error'] ?? '')] ?? '';
$serverNow = pickled_schedule_now();

$coaches = [];
foreach ($coachRows as $index => $coachRow) {
  $availability = $schedulingService->availabilityForCoach((int) $coachRow['id'], true);
  [$scheduleLabel, $daysLabel, $slotsLabel] = pickled_public_coach_schedule($availability);
  $coaches[] = [
    str_pad((string) $coachRow['id'], 2, '0', STR_PAD_LEFT),
    $coachRow['name'],
    $index % 2 ? 'green' : 'pink',
    $coachRow['bio'] ?: (($coachRow['specialization'] ?? 'Pickleball') . ' coach available for PICKLED sessions.'),
    'all',
    $scheduleLabel,
    $daysLabel,
    $slotsLabel,
    '../assets/img/court/academy.png',
  ];
}
?>

<main class="courts-page">
  <?php if ($cartError): ?>
    <div class="court-wrap"><div class="booking-alert"><?= htmlspecialchars($cartError) ?></div></div>
  <?php endif; ?>
  <section class="court-hero" id="court-top">
    <div class="court-wrap">
      <h1>COURT</h1>
      <div class="court-hero__grid">
        <?php foreach ($courtImages as $key => $court): ?>
          <button class="court-tile" type="button" data-jump-court="<?= htmlspecialchars($key) ?>">
            <span class="court-tile__media">
              <img src="<?= htmlspecialchars($court['image']) ?>" alt="<?= htmlspecialchars($court['title']) ?>" />
              <span><?= htmlspecialchars($court['tag']) ?></span>
            </span>
            <span class="court-tile__body">
              <small>PICKLE &amp;</small>
              <strong><?= htmlspecialchars($court['title']) ?></strong>
            </span>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="court-detail" id="court-detail">
    <div class="court-wrap court-product">
      <div class="court-gallery">
        <div class="court-thumbs" aria-label="Court gallery thumbnails">
          <?php foreach ($defaultCourtAssets['thumbs'] as $index => $thumb): ?>
            <button class="court-thumb <?= $index === 0 ? 'is-active' : '' ?>" type="button" data-gallery-src="<?= htmlspecialchars($thumb) ?>">
              <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($defaultCourtAssets['title']) ?> view <?= $index + 1 ?>" />
            </button>
          <?php endforeach; ?>
        </div>
        <div class="court-gallery__main">
          <img id="courtMainImage" src="<?= htmlspecialchars($defaultCourtAssets['image']) ?>" alt="<?= htmlspecialchars($defaultCourtAssets['title']) ?> main view" />
        </div>
      </div>

      <div class="court-product__info">
        <p class="court-kicker">PICKLE &amp;</p>
        <h2 id="selectedCourtTitle"><?= htmlspecialchars($defaultRate['court']) ?></h2>
        <p class="court-price"><span id="selectedCourtPrice">₱<?= number_format((float) $defaultRate['price'], 2) ?></span> <small>/ session</small></p>
        <p class="court-description" id="selectedCourtDescription"><?= htmlspecialchars((string) ($defaultCourtAssets['description'] ?? '')) ?></p>
        <div class="court-meta-row" aria-label="Selected court details">
          <span><strong id="selectedCourtCapacity"><?= (int) ($defaultCourtAssets['capacity'] ?? 0) ?: '—' ?></strong> capacity</span>
          <span><strong id="selectedCourtType"><?= htmlspecialchars((string) ($defaultCourtAssets['court_type'] ?? 'Indoor')) ?></strong> court</span>
          <span><strong id="selectedCourtHours"><?= htmlspecialchars((string) ($defaultCourtAssets['operating_hours'] ?? '8AM - 10PM')) ?></strong></span>
        </div>

        <div class="rate-list" aria-label="Court rates">
          <?php foreach (($courtRateCatalog[$defaultCourtKey] ?? []) as $index => $rate): ?>
            <button class="rate-option <?= $index === 0 ? 'is-selected' : '' ?>" type="button" <?= pickled_booking_rate_attrs($rate) ?>>
              <strong><?= htmlspecialchars($rate['title']) ?></strong>
              <span><?= htmlspecialchars($rate['note']) ?></span>
            </button>
          <?php endforeach; ?>
          <?php if (empty($courtRateCatalog[$defaultCourtKey])): ?><p>No active court services available yet.</p><?php endif; ?>
        </div>

        <div class="court-booking-actions">
          <button class="book-trigger" type="button" data-tooltip="Order now">Book now</button>
          <form method="post" action="cart.php" class="court-cart-form" id="courtCartForm">
            <input type="hidden" name="action" value="add_booking" />
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
            <input type="hidden" name="variant_id" value="<?= htmlspecialchars($defaultRate['variant']) ?>" />
            <input type="hidden" name="date" value="" />
            <input type="hidden" name="time" value="" />
            <input type="hidden" name="quantity" value="1" />
            <input type="hidden" name="coach_user_id" value="" />
            <button class="court-cart-button" type="submit">Add to cart</button>
          </form>
        </div>
        <p class="urgency">Limited seats. Book ASAP.</p>
        <div class="court-actions">
          <span>♡ MAIN COURT</span>
          <button class="court-share" type="button" data-share-title="Pickled Court Booking" data-share-text="Book a Pickled court session." data-share-url="courts.php#court-detail">Share</button>
        </div>
      </div>
    </div>
  </section>

  <section class="classes-section" id="classes">
    <div class="court-wrap">
      <div class="classes-head">
        <h2>CLASSES</h2>
        <p>Beginner-friendly courses, private lessons, and semi-private lessons with Coaches Martina, David, Anton, Kenji, and Sophia. Learn the rules, find your rhythm, and start playing with confidence.</p>
      </div>
      <div class="classes-carousel" data-classes-carousel>
        <div class="classes-track">
          <article class="class-slide is-active" data-class-slide>
            <img src="../assets/img/court/private lesson.png" alt="Private and semi-private pickleball lesson" />
            <div>
              <p>Pickled Classes</p>
              <h3>PRIVATE AND SEMI-PRIVATE LESSON</h3>
              <span>Up to <?= (int) ($privateCoachRate['maxPlayers'] ?? 1) ?> players with an internationally certified coach</span>
              <button class="book-trigger" type="button" data-tooltip="Order now" <?= pickled_booking_rate_attrs($privateCoachRate + ['dateMode' => $privateCoachRate['dateMode'] ?? 'coach'], true) ?>>Book now</button>
            </div>
          </article>
          <article class="class-slide" data-class-slide hidden>
            <img src="../assets/img/court/court pink-3.png" alt="Kids pickleball class" />
            <div>
              <p>Pickle &amp; Classes</p>
              <h3>KIDS</h3>
              <span>Up to <?= (int) ($kidsClassRate['maxPlayers'] ?? 1) ?> players with internationally certified coach</span>
              <button class="book-trigger" type="button" data-tooltip="Order now" <?= pickled_booking_rate_attrs($kidsClassRate + ['dateMode' => $kidsClassRate['dateMode'] ?? 'coach'], true) ?>>Book now</button>
            </div>
          </article>
        </div>

        <div class="classes-controls" aria-label="Classes slideshow controls">
          <button type="button" data-class-prev aria-label="Previous class">‹</button>
          <button type="button" class="is-active" data-class-dot="0" aria-label="Show class 1"></button>
          <button type="button" data-class-dot="1" aria-label="Show class 2"></button>
          <button type="button" data-class-next aria-label="Next class">›</button>
        </div>
      </div>
    </div>
  </section>

  <section class="testimonial-section">
    <div class="court-wrap testimonial-panel">
      <div class="pickle-mark" aria-hidden="true"><span></span></div>
      <blockquote>AS A FAMILY NEW TO PICKLEBALL, WE WERE NERVOUS, BUT THE COACHES MADE IT SO EASY AND FUN! THEIR PATIENCE, CLEAR GUIDANCE, AND ENCOURAGEMENT TURNED LEARNING INTO PURE JOY. WE'VE NOT ONLY GAINED SKILLS BUT A REAL LOVE FOR THE GAME. HIGHLY RECOMMEND THEM.</blockquote>
      <p>Parent</p>
      <strong>JESSICA</strong>
      <div class="verified">✓ Verified customer</div>
    </div>
  </section>

  <section class="coaches-section" id="coaches">
    <div class="court-wrap coaches-layout">
      <aside class="coaches-intro">
        <p>Pickle &amp;</p>
        <h2>COACHES</h2>
        <span>Our team is a diverse group of internationally certified coaches united by a common goal, working together harmoniously to achieve success.</span>
        <button class="book-trigger coaches-book" type="button" data-tooltip="Order now" <?= pickled_booking_rate_attrs($privateCoachRate + ['dateMode' => $privateCoachRate['dateMode'] ?? 'coach'], true) ?>>BOOK NOW ›</button>
        <div class="coach-filter">
          <button class="is-active" type="button" data-coach-filter="all">All</button>
          <button type="button" data-coach-filter="mens">Men</button>
          <button type="button" data-coach-filter="women">Women</button>
        </div>
      </aside>
      <div class="coach-grid">
        <?php foreach ($coaches as $coach): ?>
          <article class="coach-card coach-card--<?= htmlspecialchars($coach[2]) ?>" data-coach-gender="<?= htmlspecialchars($coach[4]) ?>">
            <button class="coach-toggle" type="button" aria-expanded="false">+</button>
            <p><?= htmlspecialchars($coach[0]) ?></p>
            <h3><?= htmlspecialchars($coach[1]) ?></h3>
            <div class="coach-photo">
              <img src="<?= htmlspecialchars($coach[8]) ?>" alt="<?= htmlspecialchars($coach[1]) ?> photo placeholder" />
            </div>
            <div class="coach-detail"><?= htmlspecialchars($coach[3]) ?><strong>Schedule: <?= htmlspecialchars($coach[5]) ?></strong></div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

</main>

<?php
$bookingReference = 'PKL-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
$defaultCoach = $coaches[0] ?? ['00', 'Coach', 'green', '', 'all', '', '', '', '../assets/img/court/academy.png'];
$initialCalendar = new DateTimeImmutable('first day of this month');
$initialCalendarTitle = $initialCalendar->format('F Y');
$initialDaysInMonth = (int) $initialCalendar->format('t');
$initialMondayOffset = ((int) $initialCalendar->format('w') + 6) % 7;
$initialCalendarCells = (int) ceil(($initialMondayOffset + $initialDaysInMonth) / 7) * 7;
?>

<div class="booking-modal" id="bookingModal" aria-hidden="true">
  <div class="booking-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="bookingTitle">
    <button class="booking-close" type="button" aria-label="Close booking">×</button>
    <div class="booking-step booking-step--date is-active">
      <div class="booking-calendar">
        <img src="<?= htmlspecialchars($defaultCourtAssets['image']) ?>" alt="" />
        <span class="booking-label" id="bookingType"><?= htmlspecialchars($defaultRate['label']) ?></span>
        <p id="bookingHint"><?= htmlspecialchars($defaultRate['note']) ?></p>
        <div class="calendar-head">
          <button type="button" aria-label="Previous month">‹</button>
          <strong><?= htmlspecialchars($initialCalendarTitle) ?></strong>
          <button type="button" aria-label="Next month">›</button>
        </div>
        <div class="calendar-grid" aria-label="<?= htmlspecialchars($initialCalendarTitle) ?> calendar">
          <span>MO</span><span>TU</span><span>WE</span><span>TH</span><span>FR</span><span>SA</span><span>SU</span>
          <?php for ($i = 0; $i < $initialCalendarCells; $i++): $day = $i - $initialMondayOffset + 1; ?>
            <?php if ($day < 1 || $day > $initialDaysInMonth): ?>
              <button type="button" disabled></button>
            <?php else: ?>
              <?php $dateLabel = $initialCalendar->setDate((int) $initialCalendar->format('Y'), (int) $initialCalendar->format('n'), $day)->format('l, F j, Y'); ?>
              <button type="button" disabled data-date="<?= htmlspecialchars($dateLabel) ?>"><?= $day ?></button>
            <?php endif; ?>
          <?php endfor; ?>
        </div>
        <div class="calendar-legend" aria-label="Calendar legend">
          <span><i class="legend-dot legend-dot--available"></i>Available</span>
          <span><i class="legend-dot legend-dot--selected"></i>Selected</span>
          <span><i class="legend-dot legend-dot--booked"></i>Booked</span>
          <span><i class="legend-dot legend-dot--unavailable"></i>Not available</span>
        </div>
      </div>
      <div class="booking-times">
        <div class="booking-title-row">
          <h2 id="bookingTitle">Book a <span id="bookingDuration">1 hour</span> session</h2>
        </div>
        <label class="person-row">
          <span>Person <span id="personCount">1</span></span>
          <input id="personInput" type="range" min="1" max="<?= (int) ($defaultRate['maxPlayers'] ?? 1) ?>" value="1" />
          <strong id="bookingTotal">₱600.00</strong>
        </label>
        <label class="coach-row" id="coachRow" hidden>
          <span>Coach</span>
          <select id="coachSelect">
            <?php foreach ($coaches as $coach): ?>
              <option value="<?= (int) $coach[0] ?>" data-name="<?= htmlspecialchars($coach[1]) ?>" data-schedule="<?= htmlspecialchars($coach[5]) ?>" data-days="<?= htmlspecialchars($coach[6]) ?>" data-slots="<?= htmlspecialchars($coach[7]) ?>"><?= htmlspecialchars($coach[1]) ?> · <?= htmlspecialchars($coach[5]) ?></option>
            <?php endforeach; ?>
          </select>
          <small id="coachSchedule">Choose your preferred coach schedule.</small>
        </label>
        <h3>What time works best?</h3>
        <p class="timezone">Asia/Manila · <span id="manilaTime">PH time</span></p>
        <div class="time-tabs" role="group" aria-label="Time format">
          <button class="is-active" type="button" data-time-format="12">AM/PM</button>
          <button type="button" data-time-format="24">24h</button>
        </div>
        <div class="time-slot-grid" id="timeSlotGrid">
          <button class="time-slot" type="button" data-time="07:00 AM - 08:00 AM">07:00 AM - 08:00 AM</button>
          <button class="time-slot" type="button" data-time="08:00 AM - 09:00 AM">08:00 AM - 09:00 AM</button>
          <button class="time-slot" type="button" data-time="09:00 AM - 10:00 AM">09:00 AM - 10:00 AM</button>
          <button class="time-slot" type="button" data-time="10:00 AM - 11:00 AM">10:00 AM - 11:00 AM</button>
          <button class="time-slot" type="button" data-time="11:00 AM - 12:00 PM">11:00 AM - 12:00 PM</button>
          <button class="time-slot" type="button" data-time="01:00 PM - 02:00 PM">01:00 PM - 02:00 PM</button>
          <button class="time-slot" type="button" data-time="02:00 PM - 03:00 PM">02:00 PM - 03:00 PM</button>
          <button class="time-slot" type="button" data-time="03:00 PM - 04:00 PM">03:00 PM - 04:00 PM</button>
          <button class="time-slot" type="button" data-time="04:00 PM - 05:00 PM">04:00 PM - 05:00 PM</button>
          <button class="time-slot" type="button" data-time="05:00 PM - 06:00 PM">05:00 PM - 06:00 PM</button>
          <button class="time-slot" type="button" data-time="06:00 PM - 07:00 PM">06:00 PM - 07:00 PM</button>
          <button class="time-slot" type="button" data-time="07:00 PM - 08:00 PM">07:00 PM - 08:00 PM</button>
          <button class="time-slot" type="button" data-time="08:00 PM - 09:00 PM">08:00 PM - 09:00 PM</button>
          <button class="time-slot" type="button" data-time="09:00 PM - 10:00 PM">09:00 PM - 10:00 PM</button>
        </div>
        <p class="slot-help">Select as many available hours as you want.</p>
        <button class="continue-booking" type="button">Continue</button>
      </div>
    </div>

    <div class="booking-step booking-step--details">
      <aside class="booking-summary">
        <button class="back-to-dates" type="button">‹ Back to dates</button>
        <img src="<?= htmlspecialchars($defaultCourtAssets['image']) ?>" alt="" />
        <h3 id="summaryProduct">COURT RENTALS</h3>
        <p id="summaryNote">Selected booking details</p>
        <dl>
          <dt>Date</dt><dd id="summaryDate">Selected date</dd>
          <dt>Time</dt><dd id="summaryTime">10:00 AM - 11:00 AM</dd>
          <dt>Duration</dt><dd id="summaryDuration">1 hour</dd>
          <dt>Quantity</dt><dd id="summaryQty">1</dd>
          <dt>Court / Coach</dt><dd id="summaryCourt">COURT GREEN</dd>
          <dt>Subtotal</dt><dd id="summarySubtotal">₱600.00</dd>
          <dt>Payment fee</dt><dd id="summaryFee">₱0.00</dd>
          <dt>Total</dt><dd id="summaryTotal">₱600.00</dd>
        </dl>
      </aside>
      <form class="booking-form booking-details-form">
        <h2>Your details</h2>
        <div class="booking-alert">This will add your selected schedule to cart. Payment happens during checkout.</div>
        <label>Name *<input type="text" placeholder="Enter your name" required /></label>
        <label>Email *<input type="email" placeholder="Enter your email" required /></label>
        <fieldset>
          <legend>What is your or your group's experience level in pickleball? *</legend>
          <label><input type="radio" name="level" required /> New or had trial class experience</label>
          <label><input type="radio" name="level" /> DUPR 2.0-2.5</label>
          <label><input type="radio" name="level" /> DUPR 2.5-3.0</label>
          <label><input type="radio" name="level" /> DUPR 3-3.5</label>
        </fieldset>
        <button type="submit">Add to cart</button>
      </form>
    </div>

    <div class="booking-step booking-step--payment">
      <form class="booking-form booking-payment-form">
        <button class="back-to-details" type="button">‹ Back to details</button>
        <h2>Payment</h2>
        <fieldset class="payment-methods">
          <legend>Payment method *</legend>
          <label><input type="radio" name="payment" value="GCash" data-fee-rate="0" checked /> GCash</label>
        </fieldset>
        <div class="booking-breakdown">
          <span>Subtotal <strong id="formSubtotal">₱1,200.00</strong></span>
          <span>Total <strong id="formTotal">₱1,200.00</strong></span>
        </div>
        <button type="submit">Pay and confirm</button>
      </form>
    </div>

    <div class="booking-step booking-step--confirmation">
      <div class="booking-confirmation">
        <div class="confirmation-title">
          <p>Booking confirmed</p>
          <h2>Booking Confirmed</h2>
          <span>Reference No. <strong id="referenceNumber"><?= htmlspecialchars($bookingReference) ?></strong></span>
        </div>
        <div class="confirmation-cart">
          <article class="confirmation-item">
            <img src="<?= htmlspecialchars($defaultCourtAssets['image']) ?>" alt="" />
            <div>
              <h3 id="confirmedProduct">COURT RENTALS</h3>
              <p id="confirmedCourt">COURT GREEN</p>
              <strong id="confirmedTotal">₱1,200.00</strong>
              <span>Name: <b id="confirmedName">Guest</b></span>
              <span>Email: <b id="confirmedEmail">guest@example.com</b></span>
              <span>Date: <b id="confirmedDate">Selected date</b></span>
              <span>Time: <b id="confirmedSchedule">07:00 AM - 09:00 AM</b></span>
            </div>
          </article>
          <aside class="confirmation-side">
            <div class="confirmation-total">
              <span>Total</span>
              <strong id="confirmedGrandTotal">₱1,200.00</strong>
              <p>Please keep your reference number for check-in.</p>
            </div>
            <form class="feedback-form">
              <div class="booking-success" role="status" aria-live="polite">Thank you for your feedback.</div>
              <h3>Feedback</h3>
              <fieldset class="rating-group">
                <legend>Rate your booking experience</legend>
                <label><input type="radio" name="rating" value="5" required /> 5</label>
                <label><input type="radio" name="rating" value="4" /> 4</label>
                <label><input type="radio" name="rating" value="3" /> 3</label>
                <label><input type="radio" name="rating" value="2" /> 2</label>
                <label><input type="radio" name="rating" value="1" /> 1</label>
              </fieldset>
              <label>Comments<textarea placeholder="Tell us how we did"></textarea></label>
              <button type="submit">Submit feedback</button>
            </form>
          </aside>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="share-toast" id="courtShareToast" role="status" aria-live="polite">Share link copied.</div>

<script>
(function(){
  const isLoggedIn = <?= !empty($_SESSION['user']) ? 'true' : 'false' ?>;
  const csrfToken = '<?= htmlspecialchars(pickled_csrf_token()) ?>';
  const availabilityEndpoint = '../app/api/availability.php';
  let availability = { dates: {} };
  const loginUrl = '../auth/login.php?notice=booking&redirect=resident/courts.php%23court-detail';
  const defaultCourtKey = <?= json_encode($defaultCourtKey) ?>;
  const state = {
    variant: <?= json_encode($defaultRate['variant'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    label: <?= json_encode($defaultRate['label'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    note: <?= json_encode($defaultRate['note'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    price: <?= json_encode((float) $defaultRate['price']) ?>,
    duration: <?= json_encode($defaultRate['duration'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    court: <?= json_encode($defaultRate['court'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    participantsLimit: <?= json_encode((int) ($defaultRate['participantsLimit'] ?? 1)) ?>,
    capacity: <?= json_encode((int) ($defaultRate['capacity'] ?? 1)) ?>,
    maxQty: <?= json_encode((int) ($defaultRate['maxPlayers'] ?? 1)) ?>,
    date: '',
    selectedTimes: [],
    qty: 1,
    feeRate: 0,
    paymentMethod: 'GCash',
    dateMode: <?= json_encode($defaultRate['dateMode'] ?? 'daily') ?>,
    coachId: <?= json_encode((int) $defaultCoach[0]) ?>,
    coach: <?= json_encode($defaultCoach[1], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    coachSchedule: <?= json_encode($defaultCoach[5], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    coachDays: <?= json_encode($defaultCoach[6], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    coachSlots: <?= json_encode($defaultCoach[7], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    timeFormat: '12',
    name: '',
    email: ''
  };
  const money = value => '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const courtRateCatalog = <?= json_encode($courtRateCatalog, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  const modal = document.getElementById('bookingModal');
  const dateStep = modal.querySelector('.booking-step--date');
  const detailsStep = modal.querySelector('.booking-step--details');
  const paymentStep = modal.querySelector('.booking-step--payment');
  const confirmationStep = modal.querySelector('.booking-step--confirmation');
  const calendarGrid = modal.querySelector('.calendar-grid');
  const calendarTitle = modal.querySelector('.calendar-head strong');
  const calendarNavButtons = modal.querySelectorAll('.calendar-head button');
  const rateList = document.querySelector('.rate-list');
  const courtThumbs = document.querySelector('.court-thumbs');
  const courtMainImage = document.getElementById('courtMainImage');
  const courtGalleryCatalog = <?= json_encode($courtGalleryCatalog, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  const personInput = document.getElementById('personInput');
  const coachRow = document.getElementById('coachRow');
  const coachSelect = document.getElementById('coachSelect');
  const coachSchedule = document.getElementById('coachSchedule');
  const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  const weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  let visibleMonth = new Date(<?= (int) $serverNow->format('Y') ?>, <?= (int) $serverNow->format('n') - 1 ?>, 1);

  function absoluteUrl(path){
    return new URL(path, window.location.href).href;
  }

  function showShareToast(message){
    const toast = document.getElementById('courtShareToast');
    toast.textContent = message;
    toast.classList.add('is-visible');
    window.clearTimeout(showShareToast.timer);
    showShareToast.timer = window.setTimeout(() => toast.classList.remove('is-visible'), 2200);
  }

  function sharePage(button){
    const shareData = {
      title: button.dataset.shareTitle || document.title,
      text: button.dataset.shareText || '',
      url: absoluteUrl(button.dataset.shareUrl || window.location.href)
    };

    if (navigator.share) {
      navigator.share(shareData).catch(() => {});
      return;
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(shareData.url).then(() => showShareToast('Share link copied.'));
      return;
    }

    showShareToast(shareData.url);
  }

  function formatDate(year, month, day){
    const date = new Date(year, month, day);
    return weekdays[date.getDay()] + ', ' + months[month] + ' ' + day + ', ' + year;
  }

  function needsCoach(){
    return state.dateMode === 'coach' || state.label.indexOf('PRIVATE COACHING') !== -1 || state.court === 'PRIVATE COACHING';
  }

  function dateAllowed(date){
    const day = date.getDay();
    if (state.dateMode === 'coach') return state.coachDays.split(',').map(Number).includes(day);
    return true;
  }

  function dateModeHint(){
    if (state.dateMode === 'coach') return 'Pick a coach below to match your booking with their schedule.';
    return state.note;
  }

  function selectedDayAvailability(){
    return availability.dates[state.date] || null;
  }

  function numericLimit(value, fallback){
    const number = Number(value);
    return Number.isFinite(number) && number > 0 ? Math.floor(number) : fallback;
  }

  function applyQuantityFields(source){
    state.participantsLimit = numericLimit(source.participantsLimit ?? source.participants_limit, state.participantsLimit || 1);
    state.capacity = numericLimit(source.capacity, state.capacity || state.participantsLimit || 1);
    state.maxQty = numericLimit(source.maxPlayers ?? source.max_players, Math.min(state.participantsLimit, state.capacity));
    state.maxQty = Math.max(1, Math.min(state.maxQty, state.participantsLimit, state.capacity));
  }

  function currentQuantityLimit(){
    let limit = Math.max(1, Number(state.maxQty || state.participantsLimit || state.capacity || 1));
    const dayAvailability = selectedDayAvailability();
    if (dayAvailability && dayAvailability.slots && state.selectedTimes.length) {
      state.selectedTimes.forEach(time => {
        const slot = dayAvailability.slots[time];
        if (slot && Number.isFinite(Number(slot.remaining))) {
          limit = Math.min(limit, Math.max(1, Number(slot.remaining)));
        }
      });
    }
    return Math.max(1, Math.floor(limit));
  }

  function syncQuantityInput(){
    const limit = currentQuantityLimit();
    personInput.max = String(limit);
    state.qty = Math.max(1, Math.min(Number(state.qty || 1), limit));
    personInput.value = String(state.qty);
  }

  function syncVariantFromAvailability(payload){
    const variant = payload && payload.variant ? payload.variant : {};
    if (!variant.slug) return;
    if (variant.name) state.label = String(variant.name).toUpperCase();
    if (variant.price !== undefined) {
      state.price = Number(variant.price || state.price);
      document.getElementById('selectedCourtPrice').textContent = money(state.price);
    }
    if (variant.duration_label) state.duration = variant.duration_label;
    if (variant.court) state.court = String(variant.court).toUpperCase();
    applyQuantityFields({
      participantsLimit: variant.participants_limit,
      capacity: variant.capacity,
      maxPlayers: Math.min(
        numericLimit(variant.participants_limit, state.participantsLimit || 1),
        numericLimit(variant.capacity, state.capacity || 1)
      )
    });
    if (variant.court_capacity !== undefined) {
      document.getElementById('selectedCourtCapacity').textContent = numericLimit(variant.court_capacity, state.capacity);
    }
  }

  async function loadAvailability(){
    const year = visibleMonth.getFullYear();
    const month = visibleMonth.getMonth() + 1;
    const response = await fetch(availabilityEndpoint + '?variant=' + encodeURIComponent(state.variant) + '&year=' + year + '&month=' + month + '&_=' + Date.now(), { cache: 'no-store' });
    availability = await response.json();
    syncVariantFromAvailability(availability);
    renderCalendar();
    updateTimeSlots();
    renderTimeLabels();
    updateTotals();
  }

  function to24Hour(time){
    const match = time.match(/^(\d{1,2}):(\d{2})\s?(AM|PM)$/i);
    if (!match) return time;
    let hour = Number(match[1]);
    const minute = match[2];
    const meridiem = match[3].toUpperCase();
    if (meridiem === 'PM' && hour !== 12) hour += 12;
    if (meridiem === 'AM' && hour === 12) hour = 0;
    return String(hour).padStart(2, '0') + ':' + minute;
  }

  function formatTimeRange(range){
    if (state.timeFormat !== '24') return range;
    return range.split(' - ').map(to24Hour).join(' - ');
  }

  function renderTimeLabels(){
    modal.querySelectorAll('.time-slot').forEach(button => {
      const booked = button.dataset.booked === 'true';
      button.innerHTML = formatTimeRange(button.dataset.time) + (booked ? ' <small>Booked</small>' : '');
    });
  }

  function renderCalendar(){
    const year = visibleMonth.getFullYear();
    const month = visibleMonth.getMonth();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const mondayOffset = (new Date(year, month, 1).getDay() + 6) % 7;
    let html = '<span>MO</span><span>TU</span><span>WE</span><span>TH</span><span>FR</span><span>SA</span><span>SU</span>';

    for (let i = 0; i < mondayOffset; i++) {
      html += '<button type="button" disabled></button>';
    }

    for (let day = 1; day <= daysInMonth; day++) {
      const date = new Date(year, month, day);
      const label = formatDate(year, month, day);
      const availableDate = availability.dates[label];
      const allowed = dateAllowed(date) && !!availableDate;
      const booked = allowed && !availableDate.available;
      const active = allowed && !booked && label === state.date ? ' is-date-selected' : '';
      const status = booked ? ' is-booked' : allowed ? ' is-available' : ' is-unavailable';
      const disabled = allowed && !booked ? '' : ' disabled title="' + (booked ? 'Booked' : 'Not available for this booking type') + '"';
      const labelText = booked ? day + '<small>Booked</small>' : day;
      html += '<button type="button" class="' + (status + active).trim() + '"' + disabled + ' data-date="' + label + '">' + labelText + '</button>';
    }

    calendarTitle.textContent = months[month] + ' ' + year;
    calendarGrid.setAttribute('aria-label', months[month] + ' ' + year + ' calendar');
    calendarGrid.innerHTML = html;
    if (!calendarGrid.querySelector('.is-date-selected')) {
      const firstDate = calendarGrid.querySelector('[data-date]:not(:disabled)');
      if (firstDate) {
        firstDate.classList.add('is-date-selected');
        state.date = firstDate.dataset.date;
      }
    }
  }

  function updateTotals(){
    syncQuantityInput();
    const hours = Math.max(state.selectedTimes.length, 1);
    const subtotal = state.price * state.qty * hours;
    const fee = subtotal * state.feeRate;
    const total = subtotal + fee;
    const hourText = hours + ' ' + (hours === 1 ? 'hour' : 'hours');
    const timeText = state.selectedTimes.length ? state.selectedTimes.map(formatTimeRange).join(', ') : 'No time selected';
    document.getElementById('personCount').textContent = state.qty;
    document.getElementById('bookingTotal').textContent = money(total);
    document.getElementById('summaryQty').textContent = state.qty;
    document.getElementById('summaryTime').textContent = timeText;
    document.getElementById('summaryDuration').textContent = hourText;
    document.getElementById('summarySubtotal').textContent = money(subtotal);
    document.getElementById('summaryFee').textContent = money(fee);
    document.getElementById('summaryTotal').textContent = money(total);
    document.getElementById('formSubtotal').textContent = money(subtotal);
    document.getElementById('formTotal').textContent = money(total);
    document.getElementById('confirmedProduct').textContent = state.label;
    document.getElementById('confirmedCourt').textContent = needsCoach() ? state.coach : state.court;
    document.getElementById('confirmedDate').textContent = state.date;
    document.getElementById('confirmedSchedule').textContent = timeText;
    document.getElementById('confirmedTotal').textContent = money(total);
    document.getElementById('confirmedGrandTotal').textContent = money(total);
  }

  function updateBookingCopy(){
    document.getElementById('bookingType').textContent = state.label;
    document.getElementById('bookingHint').innerHTML = dateModeHint();
    document.getElementById('bookingDuration').textContent = state.duration;
    document.getElementById('summaryProduct').textContent = state.label;
    document.getElementById('summaryNote').textContent = needsCoach() ? state.note + ' Coach: ' + state.coach + ' (' + state.coachSchedule + ')' : state.note;
    document.getElementById('summaryDate').textContent = state.date;
    document.getElementById('summaryCourt').textContent = needsCoach() ? state.coach : state.court;
    coachRow.hidden = !needsCoach();
    coachSelect.value = String(state.coachId || '');
    coachSchedule.textContent = state.coachSchedule;
    loadAvailability();
    renderTimeLabels();
    updateTotals();
  }

  function updateTimeSlots(){
    const allowedSlots = needsCoach() ? state.coachSlots.split('|') : [];
    const selected = [];
    modal.querySelectorAll('.time-slot').forEach(button => {
      const dayAvailability = selectedDayAvailability();
      const slot = dayAvailability && dayAvailability.slots ? dayAvailability.slots[button.dataset.time] : null;
      const isBooked = !slot || slot.full;
      const inCoachSchedule = !needsCoach() || allowedSlots.includes(button.dataset.time);
      button.hidden = !inCoachSchedule;
      button.disabled = isBooked || !inCoachSchedule;
      if (!inCoachSchedule || isBooked) button.classList.remove('is-selected');
      if (inCoachSchedule && button.classList.contains('is-selected')) selected.push(button.dataset.time);
    });
    if (!selected.length) {
      const first = modal.querySelector('.time-slot:not([hidden]):not(:disabled)');
      if (first) {
        first.classList.add('is-selected');
        selected.push(first.dataset.time);
      }
    }
    state.selectedTimes = selected;
    syncQuantityInput();
  }

  function applyBookingDataset(button){
    if (!button.dataset.bookingLabel) return;
    state.label = button.dataset.bookingLabel;
    state.variant = button.dataset.bookingVariant || state.variant;
    state.note = button.dataset.bookingNote || state.note;
    state.price = Number(button.dataset.bookingPrice || state.price);
    state.duration = button.dataset.bookingDuration || state.duration;
    state.court = button.dataset.bookingCourt || state.court;
    state.dateMode = button.dataset.dateMode || 'daily';
    applyQuantityFields(button.dataset);
    document.getElementById('selectedCourtPrice').textContent = money(state.price);
  }

  function applyRateOption(button){
    if (!button) return;
    rateList.querySelectorAll('.rate-option').forEach(item => item.classList.remove('is-selected'));
    button.classList.add('is-selected');
    state.label = button.dataset.label;
    state.variant = button.dataset.variant || state.variant;
    state.note = button.dataset.note || button.querySelector('span').textContent;
    state.price = Number(button.dataset.price);
    state.duration = button.dataset.duration;
    state.court = button.dataset.court;
    state.dateMode = button.dataset.dateMode || 'daily';
    applyQuantityFields(button.dataset);
    document.getElementById('selectedCourtPrice').textContent = money(state.price);
    updateBookingCopy();
  }

  function renderRateOptions(courtKey){
    const options = courtRateCatalog[courtKey] || courtRateCatalog[defaultCourtKey] || [];
    rateList.replaceChildren();
    if (!options.length) {
      const empty = document.createElement('p');
      empty.textContent = 'No active court services available yet.';
      rateList.appendChild(empty);
      return;
    }
    options.forEach((option, index) => {
      const button = document.createElement('button');
      button.className = 'rate-option' + (index === 0 ? ' is-selected' : '');
      button.type = 'button';
      button.dataset.variant = option.variant;
      button.dataset.label = option.label;
      button.dataset.note = option.note;
      button.dataset.price = String(option.price);
      button.dataset.duration = option.duration;
      button.dataset.court = option.court;
      button.dataset.participantsLimit = String(option.participantsLimit || 1);
      button.dataset.capacity = String(option.capacity || 1);
      button.dataset.maxPlayers = String(option.maxPlayers || 1);
      if (option.dateMode) button.dataset.dateMode = option.dateMode;

      const title = document.createElement('strong');
      title.textContent = option.title;
      const note = document.createElement('span');
      note.textContent = option.note;
      button.append(title, note);
      rateList.appendChild(button);
    });
    applyRateOption(rateList.querySelector('.rate-option'));
  }

  function renderCourtGallery(courtKey){
    const gallery = courtGalleryCatalog[courtKey] || courtGalleryCatalog[defaultCourtKey];
    if (!gallery) return;
    courtMainImage.src = gallery.image;
    courtMainImage.alt = gallery.alt;
    courtThumbs.replaceChildren();
    gallery.thumbs.forEach((src, index) => {
      const button = document.createElement('button');
      button.className = 'court-thumb' + (index === 0 ? ' is-active' : '');
      button.type = 'button';
      button.dataset.gallerySrc = src;

      const image = document.createElement('img');
      image.src = src;
      image.alt = gallery.thumbAlt + ' ' + (index + 1);
      button.appendChild(image);
      courtThumbs.appendChild(button);
    });
  }

  courtThumbs.addEventListener('click', event => {
    const button = event.target.closest('[data-gallery-src]');
    if (!button) return;
    courtThumbs.querySelectorAll('[data-gallery-src]').forEach(item => item.classList.remove('is-active'));
    button.classList.add('is-active');
    courtMainImage.src = button.dataset.gallerySrc;
  });

  function selectCourt(courtKey, shouldScroll){
      const gallery = courtGalleryCatalog[courtKey] || courtGalleryCatalog[defaultCourtKey];
      document.getElementById('selectedCourtTitle').textContent = gallery ? gallery.title : state.court;
      if (gallery) {
        document.getElementById('selectedCourtDescription').textContent = gallery.description || '';
        document.getElementById('selectedCourtCapacity').textContent = gallery.capacity || '—';
        document.getElementById('selectedCourtType').textContent = gallery.courtType || 'Indoor';
        document.getElementById('selectedCourtHours').textContent = gallery.operatingHours || '8AM - 10PM';
      }
      renderCourtGallery(courtKey);
      renderRateOptions(courtKey);
      if (shouldScroll) {
        document.getElementById('court-detail').scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
  }

  document.querySelectorAll('[data-jump-court]').forEach(button => {
    button.addEventListener('click', () => {
      selectCourt(button.dataset.jumpCourt || defaultCourtKey, true);
    });
  });

  const initialCourtHash = window.location.hash.replace('#', '');
  if (initialCourtHash && courtGalleryCatalog[initialCourtHash]) {
    selectCourt(initialCourtHash, false);
  }

  rateList.addEventListener('click', event => {
    const button = event.target.closest('.rate-option');
    if (button) applyRateOption(button);
  });

  document.querySelectorAll('.option-card').forEach(button => {
    button.addEventListener('click', () => {
      state.label = button.dataset.label;
      state.variant = button.dataset.variant || state.variant;
      state.note = button.dataset.note || button.querySelector('span').textContent;
      state.price = Number(button.dataset.price);
      state.duration = button.dataset.duration;
      state.court = button.dataset.court;
      state.dateMode = button.dataset.dateMode || 'daily';
      applyQuantityFields(button.dataset);
      document.getElementById('selectedCourtPrice').textContent = money(state.price);
      updateBookingCopy();
      openModal();
    });
  });

  function openModal(button){
    if (!isLoggedIn) {
      window.location.href = loginUrl;
      return;
    }
    if (button) applyBookingDataset(button);
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    dateStep.classList.add('is-active');
    detailsStep.classList.remove('is-active');
    paymentStep.classList.remove('is-active');
    confirmationStep.classList.remove('is-active');
    updateBookingCopy();
  }

  function closeModal(){
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
  }

  document.querySelectorAll('.book-trigger').forEach(button => button.addEventListener('click', () => openModal(button)));
  const courtCartForm = document.getElementById('courtCartForm');
  if (courtCartForm) {
    courtCartForm.addEventListener('submit', () => {
      courtCartForm.elements.variant_id.value = state.variant;
      courtCartForm.elements.date.value = state.date;
      courtCartForm.elements.time.value = state.selectedTimes[0] || '';
      courtCartForm.elements.quantity.value = state.qty;
      courtCartForm.elements.coach_user_id.value = needsCoach() && state.coachId ? String(state.coachId) : '';
    });
  }
  function submitBookingToCart() {
    const form = document.createElement('form');
    const selectedTimes = state.selectedTimes.length ? state.selectedTimes.join(', ') : state.time;
    const fields = {
      action: 'add_booking',
      csrf_token: csrfToken,
      variant_id: state.variant,
      date: state.date,
      time: selectedTimes,
      quantity: String(state.qty)
    };
    if (needsCoach() && state.coachId) {
      fields.coach_user_id = String(state.coachId);
    }
    form.method = 'post';
    form.action = 'cart.php';
    Object.keys(fields).forEach(name => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.value = fields[name];
      form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
  }
  document.querySelectorAll('[data-share-url]').forEach(button => button.addEventListener('click', () => sharePage(button)));
  document.querySelector('.booking-close').addEventListener('click', closeModal);
  modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });

  personInput.addEventListener('input', event => {
    state.qty = Number(event.target.value);
    updateTotals();
  });

  coachSelect.addEventListener('change', event => {
    const selected = event.target.selectedOptions[0];
    state.coachId = Number(event.target.value || 0);
    state.coach = selected ? (selected.dataset.name || selected.textContent || 'Coach') : 'Coach';
    state.coachSchedule = selected ? selected.dataset.schedule : '';
    state.coachDays = selected ? selected.dataset.days : '1,3,5';
    state.coachSlots = selected ? selected.dataset.slots : '09:00 AM - 10:00 AM|10:00 AM - 11:00 AM|11:00 AM - 12:00 PM';
    updateBookingCopy();
  });

  document.querySelectorAll('.booking-form input[name="payment"]').forEach(input => {
    input.addEventListener('change', () => {
      state.paymentMethod = input.value;
      state.feeRate = Number(input.dataset.feeRate);
      updateTotals();
    });
  });

  document.querySelectorAll('[data-time-format]').forEach(button => {
    button.addEventListener('click', () => {
      state.timeFormat = button.dataset.timeFormat;
      document.querySelectorAll('[data-time-format]').forEach(item => item.classList.remove('is-active'));
      button.classList.add('is-active');
      renderTimeLabels();
      updateTotals();
    });
  });

  calendarNavButtons[0].addEventListener('click', () => {
    visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() - 1, 1);
    loadAvailability();
  });

  calendarNavButtons[1].addEventListener('click', () => {
    visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + 1, 1);
    loadAvailability();
  });

  calendarGrid.addEventListener('click', event => {
    const button = event.target.closest('[data-date]');
    if (!button) return;
    calendarGrid.querySelectorAll('[data-date]').forEach(item => item.classList.remove('is-date-selected'));
    button.classList.add('is-date-selected');
    state.date = button.dataset.date;
    updateBookingCopy();
  });

  loadAvailability();

  modal.querySelectorAll('.time-slot:not(:disabled)').forEach(button => {
    button.addEventListener('click', () => {
      button.classList.toggle('is-selected');
      state.selectedTimes = Array.from(modal.querySelectorAll('.time-slot.is-selected')).map(item => item.dataset.time);
      document.querySelector('.slot-help').textContent = 'Select as many available hours as you want.';
      updateBookingCopy();
    });
  });

  document.querySelector('.continue-booking').addEventListener('click', () => {
    if (!state.selectedTimes.length) {
      document.querySelector('.slot-help').textContent = 'Please choose at least one available time.';
      return;
    }
    updateBookingCopy();
    dateStep.classList.remove('is-active');
    detailsStep.classList.add('is-active');
  });
  document.querySelector('.back-to-dates').addEventListener('click', () => {
    detailsStep.classList.remove('is-active');
    dateStep.classList.add('is-active');
  });
  document.querySelector('.back-to-details').addEventListener('click', () => {
    paymentStep.classList.remove('is-active');
    detailsStep.classList.add('is-active');
  });

  document.querySelector('.booking-details-form').addEventListener('submit', event => {
    event.preventDefault();
    const form = event.currentTarget;
    state.name = form.querySelector('input[type="text"]').value.trim();
    state.email = form.querySelector('input[type="email"]').value.trim();
    updateTotals();
    submitBookingToCart();
  });

  document.querySelector('.booking-payment-form').addEventListener('submit', event => {
    event.preventDefault();
    updateTotals();
    paymentStep.classList.remove('is-active');
    confirmationStep.classList.add('is-active');
  });

  document.querySelector('.feedback-form').addEventListener('submit', event => {
    event.preventDefault();
    event.currentTarget.classList.add('is-confirmed');
    event.currentTarget.querySelector('button[type="submit"]').textContent = 'Feedback submitted';
  });

  document.querySelectorAll('.coach-toggle').forEach(button => {
    button.addEventListener('click', () => {
      const card = button.closest('.coach-card');
      card.classList.toggle('is-open');
      button.textContent = card.classList.contains('is-open') ? '×' : '+';
      button.setAttribute('aria-expanded', card.classList.contains('is-open') ? 'true' : 'false');
    });
  });

  document.querySelectorAll('[data-coach-filter]').forEach(button => {
    button.addEventListener('click', () => {
      const filter = button.dataset.coachFilter;
      document.querySelectorAll('[data-coach-filter]').forEach(item => item.classList.remove('is-active'));
      button.classList.add('is-active');
      document.querySelectorAll('[data-coach-gender]').forEach(card => {
        const gender = (card.dataset.coachGender || '').toLowerCase();
        const shouldShow = filter === 'all' || gender === filter;
        card.hidden = !shouldShow;
        card.style.display = shouldShow ? '' : 'none';
      });
    });
  });

  document.querySelectorAll('[data-classes-carousel]').forEach(carousel => {
    const slides = Array.from(carousel.querySelectorAll('[data-class-slide]'));
    const dots = Array.from(carousel.querySelectorAll('[data-class-dot]'));
    const prev = carousel.querySelector('[data-class-prev]');
    const next = carousel.querySelector('[data-class-next]');
    let active = 0;

    function show(index){
      active = (index + slides.length) % slides.length;
      slides.forEach((slide, i) => {
        const isActive = i === active;
        slide.classList.toggle('is-active', isActive);
        slide.hidden = !isActive;
      });
      dots.forEach((dot, i) => dot.classList.toggle('is-active', i === active));
    }

    dots.forEach((dot, index) => dot.addEventListener('click', () => show(index)));
    prev.addEventListener('click', () => show(active - 1));
    next.addEventListener('click', () => show(active + 1));
  });

  function tickManila(){
    document.getElementById('manilaTime').textContent = new Intl.DateTimeFormat('en-PH', {
      timeZone: 'Asia/Manila',
      hour: '2-digit',
      minute: '2-digit'
    }).format(new Date());
  }
  tickManila();
  setInterval(tickManila, 30000);
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
