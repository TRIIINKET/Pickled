<?php
require_once __DIR__ . '/../../backend/includes/security.php';
pickled_init_csrf();
$pageTitle  = 'Courts - Pickled';
$activePage = 'courts.php';
$basePath   = '../';
$extraHead  = '<link rel="stylesheet" href="../css/courts.css?v=20260430d"/>';
include __DIR__ . '/../includes/_header.php';

$courtImages = [
  'green' => [
    'title' => 'COURT GREEN',
    'tag' => "EVERYONE'S GAME",
    'image' => 'https://pickleand.club/cdn/shop/files/250411_-_Pickle__008.jpg?v=1744701445&width=1946',
    'thumbs' => [
      'https://pickleand.club/cdn/shop/files/250411_-_Pickle__058.jpg?v=1744700811&width=400',
      'https://pickleand.club/cdn/shop/files/250411_-_Pickle__061.jpg?v=1744700811&width=400',
      '../assets/Images/Hero.jpg',
      'https://pickleand.club/cdn/shop/files/250411_-_Pickle__055.jpg?v=1744709434&width=400',
    ],
  ],
  'pink' => [
    'title' => 'COURT PINK',
    'tag' => 'VIBE ON',
    'image' => 'https://pickleand.club/cdn/shop/files/250411_-_Pickle__024r.jpg?v=1744816152&width=1946',
    'thumbs' => [
      'https://pickleand.club/cdn/shop/files/250411_-_Pickle__055.jpg?v=1744709434&width=400',
      'https://pickleand.club/cdn/shop/files/250411_-_Pickle__058.jpg?v=1744700811&width=400',
      '../assets/Images/Hero.jpg',
      'https://pickleand.club/cdn/shop/files/250411_-_Pickle__061.jpg?v=1744700811&width=400',
    ],
  ],
];

$bookingOptions = [
  ['label' => 'FOUNDATIONAL AGES 6-10', 'note' => 'Comprehensive 4-session course focused on hand-eye coordination and fun.', 'price' => 1200, 'duration' => '4 sessions', 'court' => 'COURT PINK'],
  ['label' => 'YOUTH DEVELOPMENT AGES 11-17', 'note' => '4-session course building technical consistency and match confidence.', 'price' => 1200, 'duration' => '4 sessions', 'court' => 'COURT PINK'],
  ['label' => 'ADULT BEGINNER BOOTCAMP', 'note' => '4-session program covering essential rules and basic strokes.', 'price' => 1800, 'duration' => '4 sessions', 'court' => 'COURT PINK'],
  ['label' => 'INTRODUCTORY TRIAL CLASS', 'note' => 'A single-session experience for up to 8 students.', 'price' => 250, 'duration' => '1 hour', 'court' => 'COURT PINK'],
  ['label' => 'PARENT & CHILD TRIAL', 'note' => 'A combined session for one adult and one child, ages 6+.', 'price' => 500, 'duration' => '1 hour', 'court' => 'COURT PINK'],
];

$coaches = [
  ['01', 'Coach Martina', 'pink', 'Technical fundamentals and youth development coach focused on biomechanics and injury prevention.', 'women', 'Mon, Wed, Fri · 9:00 AM - 12:00 PM', '1,3,5', '09:00 AM - 10:00 AM|10:00 AM - 11:00 AM|11:00 AM - 12:00 PM', 'https://i.pinimg.com/1200x/b2/41/a4/b241a487eac25d97256b8b9820c7fc3a.jpg'],
  ['02', 'Coach David', 'green', 'Competitive singles and strategy coach with a background in collegiate tennis.', 'mens', 'Tue, Thu · 5:00 PM - 8:00 PM', '2,4', '05:00 PM - 06:00 PM|06:00 PM - 07:00 PM|07:00 PM - 08:00 PM', 'https://i.pinimg.com/1200x/a8/6e/22/a86e22941537296b1bd5f25fc67b0d3c.jpg'],
  ['03', 'Coach Anton', 'green', 'Defensive play and dinking mastery coach with Asian pickleball tournament experience.', 'mens', 'Sat · 8:00 AM - 12:00 PM', '6', '08:00 AM - 09:00 AM|09:00 AM - 10:00 AM|10:00 AM - 11:00 AM|11:00 AM - 12:00 PM', 'https://i.pinimg.com/736x/a4/78/00/a4780006b6f1c0029111f8ff54bd87c4.jpg'],
  ['04', 'Coach Kenji', 'green', 'Power hitting and offensive doubles coach specializing in third-shot drops.', 'mens', 'Mon, Thu · 1:00 PM - 4:00 PM', '1,4', '01:00 PM - 02:00 PM|02:00 PM - 03:00 PM|03:00 PM - 04:00 PM', 'https://i.pinimg.com/736x/73/49/a7/7349a7512c81363fb8045bb46b19c28b.jpg'],
  ['05', 'Coach Sophia', 'pink', 'Social play and women’s clinic coach focused on group dynamics and community.', 'women', 'Wed, Sun · 3:00 PM - 6:00 PM', '3,0', '03:00 PM - 04:00 PM|04:00 PM - 05:00 PM|05:00 PM - 06:00 PM', 'https://i.pinimg.com/1200x/54/e4/c8/54e4c8c91b1594d3b960154e980f40dd.jpg'],
];
?>

<main class="courts-page">
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
          <?php foreach ($courtImages['green']['thumbs'] as $index => $thumb): ?>
            <button class="court-thumb <?= $index === 0 ? 'is-active' : '' ?>" type="button" data-gallery-src="<?= htmlspecialchars($thumb) ?>">
              <img src="<?= htmlspecialchars($thumb) ?>" alt="Court Green view <?= $index + 1 ?>" />
            </button>
          <?php endforeach; ?>
        </div>
        <div class="court-gallery__main">
          <img id="courtMainImage" src="<?= htmlspecialchars($courtImages['green']['image']) ?>" alt="Court Green main view" />
        </div>
      </div>

      <div class="court-product__info">
        <p class="court-kicker">PICKLE &amp;</p>
        <h2 id="selectedCourtTitle">COURT GREEN</h2>
        <div class="member-callout">Join as a member for <strong>Zero Guest Fees and Advanced Booking!</strong></div>
        <p class="court-price"><span id="selectedCourtPrice">₱600.00</span> <small>/ session</small></p>

        <div class="rate-list" aria-label="Court rates">
          <button class="rate-option is-selected" type="button" data-variant="green-court-rentals" data-label="COURT RENTALS" data-price="600" data-duration="1 hour" data-court="COURT GREEN">
            <strong>COURT RENTALS ₱600</strong>
            <span>Reserve Court Green for casual or private play</span>
          </button>
          <button class="rate-option" type="button" data-variant="green-lessons" data-label="LESSONS" data-price="500" data-duration="1 hour" data-court="COURT GREEN">
            <strong>LESSONS ₱500</strong>
            <span>Beginner-friendly drills and guided class sessions</span>
          </button>
          <button class="rate-option" type="button" data-variant="green-private-coaching" data-label="PRIVATE COACHING" data-price="1200" data-duration="1 hour" data-court="COURT GREEN" data-date-mode="coach">
            <strong>PRIVATE COACHING ₱1,200</strong>
            <span>1-on-1 session with a certified coach</span>
          </button>
          <button class="rate-option" type="button" data-variant="green-training" data-label="TRAINING" data-price="800" data-duration="1 hour" data-court="COURT GREEN">
            <strong>TRAINING ₱800</strong>
            <span>Focused skills training for stronger gameplay</span>
          </button>
        </div>

        <div class="court-booking-actions">
          <button class="book-trigger" type="button" data-tooltip="Order now">Book now</button>
          <form method="post" action="cart.php" class="court-cart-form" id="courtCartForm">
            <input type="hidden" name="action" value="add_booking" />
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
            <input type="hidden" name="variant_id" value="green-court-rentals" />
            <input type="hidden" name="date" value="Thursday, May 7, 2026" />
            <input type="hidden" name="time" value="Selected schedule" />
            <input type="hidden" name="quantity" value="1" />
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
            <img src="../assets/Images/Hero.jpg" alt="Private and semi-private pickleball lesson" />
            <div>
              <p>Pickled Classes</p>
              <h3>PRIVATE AND SEMI-PRIVATE LESSON</h3>
              <span>Up to 6 players with an internationally certified coach</span>
              <button class="book-trigger" type="button" data-tooltip="Order now" data-booking-label="PRIVATE COACHING" data-booking-price="1200" data-booking-duration="1 hour" data-booking-court="PRIVATE COACHING" data-date-mode="coach">Book now</button>
            </div>
          </article>
          <article class="class-slide" data-class-slide hidden>
            <img src="<?= htmlspecialchars($courtImages['pink']['image']) ?>" alt="Kids pickleball class" />
            <div>
              <p>Kids Program</p>
              <h3>FOUNDATIONAL AND YOUTH DEVELOPMENT</h3>
              <span>Fun 4-session courses for ages 6-17</span>
              <button class="book-trigger" type="button" data-tooltip="Order now">Book now</button>
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
        <button class="book-trigger coaches-book" type="button" data-tooltip="Order now" data-booking-label="PRIVATE COACHING" data-booking-price="1200" data-booking-duration="1 hour" data-booking-court="PRIVATE COACHING" data-date-mode="coach">BOOK NOW ›</button>
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
?>

<div class="booking-modal" id="bookingModal" aria-hidden="true">
  <div class="booking-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="bookingTitle">
    <button class="booking-close" type="button" aria-label="Close booking">×</button>
    <div class="booking-step booking-step--date is-active">
      <div class="booking-calendar">
        <img src="<?= htmlspecialchars($courtImages['green']['image']) ?>" alt="" />
        <button class="booking-select" type="button" id="bookingType" tabindex="-1">COURT RENTALS⌄</button>
        <p id="bookingHint">Reserve Court Green for casual or private play.</p>
        <div class="calendar-head">
          <button type="button" aria-label="Previous month">‹</button>
          <strong>May 2026</strong>
          <button type="button" aria-label="Next month">›</button>
        </div>
        <div class="calendar-grid" aria-label="May 2026 calendar">
          <span>MO</span><span>TU</span><span>WE</span><span>TH</span><span>FR</span><span>SA</span><span>SU</span>
          <?php for ($i = 0; $i < 35; $i++): $day = $i - 3; ?>
            <?php if ($day < 1 || $day > 31): ?>
              <button type="button" disabled></button>
            <?php else: ?>
              <button type="button" class="<?= $day === 7 ? 'is-date-selected' : '' ?>" data-date="Thursday, May <?= $day ?>, 2026"><?= $day ?></button>
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
          <input id="personInput" type="range" min="1" max="8" value="1" />
          <strong id="bookingTotal">₱600.00</strong>
        </label>
        <label class="coach-row" id="coachRow" hidden>
          <span>Coach</span>
          <select id="coachSelect">
            <?php foreach ($coaches as $coach): ?>
              <option value="<?= htmlspecialchars($coach[1]) ?>" data-schedule="<?= htmlspecialchars($coach[5]) ?>" data-days="<?= htmlspecialchars($coach[6]) ?>" data-slots="<?= htmlspecialchars($coach[7]) ?>"><?= htmlspecialchars($coach[1]) ?> · <?= htmlspecialchars($coach[5]) ?></option>
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
          <button class="time-slot is-selected" type="button" data-time="07:00 AM - 08:00 AM">07:00 AM - 08:00 AM</button>
          <button class="time-slot is-selected" type="button" data-time="08:00 AM - 09:00 AM">08:00 AM - 09:00 AM</button>
          <button class="time-slot" type="button" data-time="09:00 AM - 10:00 AM">09:00 AM - 10:00 AM</button>
          <button class="time-slot" type="button" data-time="10:00 AM - 11:00 AM">10:00 AM - 11:00 AM</button>
          <button class="time-slot" type="button" data-time="11:00 AM - 12:00 PM">11:00 AM - 12:00 PM</button>
          <button class="time-slot" type="button" data-time="01:00 PM - 02:00 PM">01:00 PM - 02:00 PM</button>
          <button class="time-slot" type="button" data-time="02:00 PM - 03:00 PM">02:00 PM - 03:00 PM</button>
          <button class="time-slot" type="button" data-time="03:00 PM - 04:00 PM">03:00 PM - 04:00 PM</button>
          <button class="time-slot" type="button" data-time="04:00 PM - 05:00 PM">04:00 PM - 05:00 PM</button>
          <button class="time-slot" type="button" data-time="05:00 PM - 06:00 PM" data-booked="true" disabled>05:00 PM - 06:00 PM <small>Booked</small></button>
          <button class="time-slot" type="button" data-time="06:00 PM - 07:00 PM">06:00 PM - 07:00 PM</button>
          <button class="time-slot" type="button" data-time="07:00 PM - 08:00 PM">07:00 PM - 08:00 PM</button>
          <button class="time-slot" type="button" data-time="08:00 PM - 09:00 PM" data-booked="true" disabled>08:00 PM - 09:00 PM <small>Booked</small></button>
          <button class="time-slot" type="button" data-time="09:00 PM - 10:00 PM">09:00 PM - 10:00 PM</button>
        </div>
        <p class="slot-help">Select as many available hours as you want.</p>
        <button class="continue-booking" type="button">Continue</button>
      </div>
    </div>

    <div class="booking-step booking-step--details">
      <aside class="booking-summary">
        <button class="back-to-dates" type="button">‹ Back to dates</button>
        <img src="<?= htmlspecialchars($courtImages['green']['image']) ?>" alt="" />
        <h3 id="summaryProduct">COURT RENTALS</h3>
        <p id="summaryNote">Selected booking details</p>
        <dl>
          <dt>Date</dt><dd id="summaryDate">Thursday, May 7, 2026</dd>
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
          <label><input type="radio" name="payment" value="Pay at Club" data-fee-rate="0" checked /> Pay at Club</label>
          <label><input type="radio" name="payment" value="GCash" data-fee-rate="0" /> GCash</label>
          <label><input type="radio" name="payment" value="Credit / Debit Card" data-fee-rate="0" /> Credit / Debit Card</label>
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
            <img src="<?= htmlspecialchars($courtImages['green']['image']) ?>" alt="" />
            <div>
              <h3 id="confirmedProduct">COURT RENTALS</h3>
              <p id="confirmedCourt">COURT GREEN</p>
              <strong id="confirmedTotal">₱1,200.00</strong>
              <span>Name: <b id="confirmedName">Guest</b></span>
              <span>Email: <b id="confirmedEmail">guest@example.com</b></span>
              <span>Date: <b id="confirmedDate">Thursday, May 7, 2026</b></span>
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
  const availabilityEndpoint = '../../backend/api/availability.php';
  let availability = { dates: {} };
  const loginUrl = '../login.php?notice=booking&redirect=pages/courts.php%23court-detail';
  const state = {
    variant: 'green-court-rentals',
    label: 'COURT RENTALS',
    note: 'Reserve Court Green for casual or private play.',
    price: 600,
    duration: '1 hour',
    court: 'COURT GREEN',
    date: 'Thursday, May 7, 2026',
    selectedTimes: ['07:00 AM - 08:00 AM', '08:00 AM - 09:00 AM'],
    qty: 1,
    feeRate: 0,
    paymentMethod: 'Pay at Club',
    dateMode: 'daily',
    coach: 'Coach Martina',
    coachSchedule: 'Mon, Wed, Fri · 9:00 AM - 12:00 PM',
    coachDays: '1,3,5',
    coachSlots: '09:00 AM - 10:00 AM|10:00 AM - 11:00 AM|11:00 AM - 12:00 PM',
    timeFormat: '12',
    name: '',
    email: ''
  };
  const money = value => '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const modal = document.getElementById('bookingModal');
  const dateStep = modal.querySelector('.booking-step--date');
  const detailsStep = modal.querySelector('.booking-step--details');
  const paymentStep = modal.querySelector('.booking-step--payment');
  const confirmationStep = modal.querySelector('.booking-step--confirmation');
  const calendarGrid = modal.querySelector('.calendar-grid');
  const calendarTitle = modal.querySelector('.calendar-head strong');
  const calendarNavButtons = modal.querySelectorAll('.calendar-head button');
  const coachRow = document.getElementById('coachRow');
  const coachSelect = document.getElementById('coachSelect');
  const coachSchedule = document.getElementById('coachSchedule');
  const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  const weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  let visibleMonth = new Date(2026, 4, 1);

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

  async function loadAvailability(){
    const year = visibleMonth.getFullYear();
    const month = visibleMonth.getMonth() + 1;
    const response = await fetch(availabilityEndpoint + '?variant=' + encodeURIComponent(state.variant) + '&year=' + year + '&month=' + month);
    availability = await response.json();
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
    document.getElementById('bookingType').textContent = state.label + '⌄';
    document.getElementById('bookingHint').innerHTML = dateModeHint();
    document.getElementById('bookingDuration').textContent = state.duration;
    document.getElementById('summaryProduct').textContent = state.label;
    document.getElementById('summaryNote').textContent = needsCoach() ? state.note + ' Coach: ' + state.coach + ' (' + state.coachSchedule + ')' : state.note;
    document.getElementById('summaryDate').textContent = state.date;
    document.getElementById('summaryCourt').textContent = needsCoach() ? state.coach : state.court;
    coachRow.hidden = !needsCoach();
    coachSelect.value = state.coach;
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
  }

  function applyBookingDataset(button){
    if (!button.dataset.bookingLabel) return;
    state.label = button.dataset.bookingLabel;
    state.variant = button.dataset.bookingVariant || 'green-private-coaching';
    state.note = button.dataset.bookingNote || 'Private coaching with your preferred certified coach.';
    state.price = Number(button.dataset.bookingPrice);
    state.duration = button.dataset.bookingDuration;
    state.court = button.dataset.bookingCourt;
    state.dateMode = button.dataset.dateMode || 'daily';
    document.getElementById('selectedCourtPrice').textContent = money(state.price);
  }

  document.querySelectorAll('[data-gallery-src]').forEach(button => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-gallery-src]').forEach(item => item.classList.remove('is-active'));
      button.classList.add('is-active');
      document.getElementById('courtMainImage').src = button.dataset.gallerySrc;
    });
  });

  document.querySelectorAll('[data-jump-court]').forEach(button => {
    button.addEventListener('click', () => {
      document.getElementById('court-detail').scrollIntoView({ behavior: 'smooth', block: 'start' });
      if (button.dataset.jumpCourt === 'pink') {
        document.getElementById('selectedCourtTitle').textContent = 'COURT PINK';
        document.getElementById('selectedCourtPrice').textContent = '₱400.00';
        document.getElementById('courtMainImage').src = '<?= htmlspecialchars($courtImages['pink']['image']) ?>';
        state.label = 'COURT PINK BASE RATE';
        state.note = 'Community and development court for beginners, families, and future champions.';
        state.price = 400;
        state.duration = '1 hour';
        state.court = 'COURT PINK';
        state.dateMode = 'daily';
        state.variant = 'pink-base-rate';
      } else {
        document.getElementById('selectedCourtTitle').textContent = 'COURT GREEN';
        document.getElementById('selectedCourtPrice').textContent = '₱600.00';
        document.getElementById('courtMainImage').src = '<?= htmlspecialchars($courtImages['green']['image']) ?>';
        state.label = 'COURT RENTALS';
        state.note = 'Reserve Court Green for casual or private play.';
        state.price = 600;
        state.duration = '1 hour';
        state.court = 'COURT GREEN';
        state.dateMode = 'daily';
        state.variant = 'green-court-rentals';
      }
      updateBookingCopy();
    });
  });

  document.querySelectorAll('.rate-option, .option-card').forEach(button => {
    button.addEventListener('click', () => {
      document.querySelectorAll('.rate-option').forEach(item => item.classList.remove('is-selected'));
      if (button.classList.contains('rate-option')) button.classList.add('is-selected');
      state.label = button.dataset.label;
      state.variant = button.dataset.variant || state.variant;
      state.note = button.dataset.note || button.querySelector('span').textContent;
      state.price = Number(button.dataset.price);
      state.duration = button.dataset.duration;
      state.court = button.dataset.court;
      state.dateMode = button.dataset.dateMode || 'daily';
      document.getElementById('selectedCourtPrice').textContent = money(state.price);
      updateBookingCopy();
      if (button.classList.contains('option-card')) openModal();
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
      courtCartForm.elements.time.value = state.selectedTimes[0] || 'Selected schedule';
      courtCartForm.elements.quantity.value = state.qty;
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

  document.getElementById('personInput').addEventListener('input', event => {
    state.qty = Number(event.target.value);
    updateTotals();
  });

  coachSelect.addEventListener('change', event => {
    const selected = event.target.selectedOptions[0];
    state.coach = event.target.value;
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

<?php include __DIR__ . '/../includes/_footer.php'; ?>
